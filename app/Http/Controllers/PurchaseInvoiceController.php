<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CompanySetting;
use App\Models\PrintTemplate;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\Supplier;
use App\Models\Treasury;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Services\AccountingService;
use App\Services\InventoryService;
use App\Services\Printing\PythonPrintEngineService;
use App\Services\PrintTemplateService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PurchaseInvoiceController extends Controller
{
    /**
     * Display a paginated listing of purchase invoices.
     */
    public function index(Request $request): View
    {
        $query = PurchaseInvoice::with(['supplier', 'warehouse', 'creator', 'items.product']);

        if ($request->filled('search')) {
            $term = trim($request->input('search'));
            $query->where(function ($q) use ($term) {
                $q->where('invoice_number', 'like', "%{$term}%")
                    ->orWhereHas('supplier', fn ($sq) => $sq->where('name', 'like', "%{$term}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', (int) $request->input('warehouse_id'));
        }

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', (int) $request->input('supplier_id'));
        }

        $invoices = $query->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $suppliers = Supplier::active()->orderBy('name')->get();
        $warehouses = Warehouse::active()->orderBy('name')->get();

        $metrics = [
            'total_count' => PurchaseInvoice::count(),
            'posted_total' => (float) PurchaseInvoice::where('status', PurchaseInvoice::STATUS_POSTED)->sum('total_amount'),
            'draft_count' => PurchaseInvoice::where('status', PurchaseInvoice::STATUS_DRAFT)->count(),
        ];

        return view('purchases.index', [
            'title' => 'فواتير الشراء والتوريد',
            'moduleName' => 'المشتريات والموردين',
            'invoices' => $invoices,
            'suppliers' => $suppliers,
            'warehouses' => $warehouses,
            'metrics' => $metrics,
            'currentSearch' => $request->input('search'),
            'currentStatus' => $request->input('status'),
            'currentWarehouseId' => $request->input('warehouse_id'),
            'currentSupplierId' => $request->input('supplier_id'),
        ]);
    }

    /**
     * Show the form for creating a new purchase invoice.
     */
    public function create(): View
    {
        $suppliers = Supplier::active()->orderBy('name')->get();
        $warehouses = Warehouse::active()->orderBy('name')->get();
        $defaultWarehouse = Warehouse::where('is_default', true)->first() ?? $warehouses->first();
        $products = Product::active()->with('stockBalances')->orderBy('name')->get();
        $units = class_exists(Unit::class) ? Unit::active()->orderBy('is_default', 'desc')->orderBy('name')->get() : collect();
        $categories = Category::active()->orderBy('name')->get();
        if ($categories->isEmpty()) {
            $categories = collect([Category::firstOrCreate(['name' => 'عام'], ['slug' => 'general', 'is_active' => true])]);
        }
        $suggestedSku = 'KY-'.date('y').'-'.str_pad((string) ((Product::max('id') ?? 0) + 1), 4, '0', STR_PAD_LEFT);
        $suggestedNumber = PurchaseInvoice::generateInvoiceNumber();

        $productsJson = $products->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'sku' => $p->sku,
            'barcode' => (string) ($p->barcode ?? ''),
            'cost_price' => (float) $p->cost_price,
            'unit' => $p->unit ?? 'قطعة',
            'unit_factor' => (float) ($p->unit_factor ?? 1.0),
            'package_factor' => (float) ($p->package_factor ?? 12.0),
            'pieces_per_package' => (int) ($p->pieces_per_package ?: (int) round($p->package_factor ?? 12.0)),
            'stocks' => $p->stockBalances->pluck('quantity', 'warehouse_id')->map(fn ($q) => (float) $q)->toArray(),
            'total_stock' => (float) $p->total_stock,
            'formatted_stock' => $p->formatStockBreakdown(),
        ])->values();

        $unitsJson = $units->map(fn ($u) => [
            'id' => $u->id,
            'name' => $u->name,
            'code' => $u->code,
            'factor' => (float) ($u->conversion_factor ?? 1.0),
            'is_default' => (bool) ($u->is_default ?? false),
        ])->values();

        return view('purchases.create', [
            'title' => 'فاتورة شراء جديدة',
            'moduleName' => 'المشتريات والموردين',
            'suppliers' => $suppliers,
            'warehouses' => $warehouses,
            'defaultWarehouse' => $defaultWarehouse,
            'products' => $products,
            'units' => $units,
            'categories' => $categories,
            'suggestedSku' => $suggestedSku,
            'productsJson' => $productsJson,
            'unitsJson' => $unitsJson,
            'suggestedNumber' => $suggestedNumber,
        ]);
    }

    /**
     * Store a newly created purchase invoice in storage.
     */
    public function store(Request $request, InventoryService $inventoryService): RedirectResponse
    {
        $validated = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'invoice_date' => ['required', 'date'],
            'payment_type' => ['nullable', 'string', 'in:cash,credit,partial'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'due_date' => ['nullable', 'date'],
            'shipping_cost' => ['nullable', 'numeric', 'min:0'],
            'discount_type' => ['nullable', 'string', 'in:fixed,percentage'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.unit_id' => ['nullable', 'exists:units,id'],
            'items.*.unit_name' => ['nullable', 'string', 'max:50'],
            'items.*.conversion_factor' => ['nullable', 'numeric', 'min:0.0001'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ], [
            'supplier_id.required' => 'يرجى اختيار المورد.',
            'supplier_id.exists' => 'المورد المختار غير موجود في النظام.',
            'warehouse_id.required' => 'يرجى تحديد مستودع الاستلام.',
            'warehouse_id.exists' => 'المستودع المختار غير موجود في النظام.',
            'invoice_date.required' => 'تاريخ الفاتورة مطلوب.',
            'items.required' => 'يجب إضافة صنف واحد على الأقل في الفاتورة.',
            'items.min' => 'يجب إضافة صنف واحد على الأقل في الفاتورة.',
            'items.*.product_id.required' => 'يرجى اختيار الصنف.',
            'items.*.quantity.required' => 'الكمية مطلوبة.',
            'items.*.quantity.min' => 'الكمية يجب أن تكون أكبر من الصفر.',
            'items.*.unit_cost.required' => 'سعر التكلفة مطلوب.',
            'items.*.unit_cost.min' => 'سعر التكلفة لا يمكن أن يكون سالباً.',
        ]);

        $shippingCost = (float) ($validated['shipping_cost'] ?? 0.00);
        $discountType = $validated['discount_type'] ?? 'fixed';
        $discountValue = (float) ($validated['discount_value'] ?? 0.00);

        $invoice = DB::transaction(function () use ($validated, $shippingCost, $discountType, $discountValue, $request, $inventoryService) {
            $subtotal = 0.00;
            $itemsData = [];

            foreach ($validated['items'] as $itemInput) {
                $qty = (float) $itemInput['quantity'];
                $cost = (float) $itemInput['unit_cost'];
                $lineSubtotal = round($qty * $cost, 2);
                $subtotal += $lineSubtotal;

                // Determine unit & conversion factor
                $prodId = (int) $itemInput['product_id'];
                $product = Product::find($prodId);
                $unitId = ! empty($itemInput['unit_id']) ? (int) $itemInput['unit_id'] : null;
                $factor = 1.0000;

                if (! empty($itemInput['conversion_factor']) && (float) $itemInput['conversion_factor'] > 0) {
                    $factor = (float) $itemInput['conversion_factor'];
                } elseif ($unitId) {
                    $unit = Unit::find($unitId);
                    if ($unit && (float) $unit->conversion_factor > 0) {
                        $factor = (float) $unit->conversion_factor;
                    }
                } elseif (! empty($itemInput['unit_name'])) {
                    $unit = Unit::where('name', trim($itemInput['unit_name']))->first();
                    if ($unit) {
                        $unitId = $unit->id;
                        $factor = (float) $unit->conversion_factor > 0 ? (float) $unit->conversion_factor : 1.0000;
                    }
                } elseif ($product && (float) $product->unit_factor > 1) {
                    $factor = (float) $product->unit_factor;
                }

                $baseQty = round($qty * $factor, 4);

                $itemsData[] = [
                    'product_id' => (int) $itemInput['product_id'],
                    'unit_id' => $unitId,
                    'conversion_factor' => $factor,
                    'quantity' => $qty,
                    'base_quantity' => $baseQty,
                    'unit_cost' => $cost,
                    'subtotal' => $lineSubtotal,
                    'notes' => $itemInput['notes'] ?? null,
                ];
            }

            $discountAmount = 0.00;
            if ($discountValue > 0) {
                if ($discountType === 'percentage') {
                    $discountAmount = round(($subtotal * min(100, $discountValue)) / 100, 2);
                } else {
                    $discountAmount = min($subtotal, $discountValue);
                }
            }

            $totalAmount = max(0, round($subtotal - $discountAmount + $shippingCost, 2));

            $paymentType = $validated['payment_type'] ?? PurchaseInvoice::PAYMENT_CASH;
            if ($paymentType === PurchaseInvoice::PAYMENT_CASH) {
                $paidAmount = $totalAmount;
                $remainingAmount = 0.00;
            } elseif ($paymentType === PurchaseInvoice::PAYMENT_CREDIT) {
                $paidAmount = 0.00;
                $remainingAmount = $totalAmount;
            } else {
                $paidAmount = min($totalAmount, max(0.00, (float) ($validated['paid_amount'] ?? 0.00)));
                $remainingAmount = max(0.00, round($totalAmount - $paidAmount, 2));
            }
            $dueDate = filled($validated['due_date'] ?? null) ? $validated['due_date'] : null;

            $notes = $validated['notes'] ?? null;
            if ($discountAmount > 0) {
                $discountNote = $discountType === 'percentage'
                    ? " [خصم: {$discountValue}% بقيمة {$discountAmount} ج.م]"
                    : " [خصم نقدي: {$discountAmount} ج.م]";
                $notes = trim(($notes ? $notes.' | ' : '').$discountNote);
            }

            $invoice = PurchaseInvoice::create([
                'invoice_number' => PurchaseInvoice::generateInvoiceNumber(),
                'supplier_id' => (int) $validated['supplier_id'],
                'warehouse_id' => (int) $validated['warehouse_id'],
                'invoice_date' => $validated['invoice_date'],
                'payment_type' => $paymentType,
                'status' => PurchaseInvoice::STATUS_DRAFT,
                'subtotal' => $subtotal,
                'shipping_cost' => $shippingCost,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'remaining_amount' => $remainingAmount,
                'due_date' => $dueDate,
                'notes' => $notes,
                'created_by' => auth()->id(),
            ]);

            foreach ($itemsData as $row) {
                $row['purchase_invoice_id'] = $invoice->id;
                PurchaseInvoiceItem::create($row);
            }

            // If user chose to review pricing before final posting
            if ($request->input('action') === 'review_pricing') {
                return $invoice;
            }

            // If user chose to post directly upon creation
            if ($request->input('action') === 'post') {
                $this->executePosting($invoice, $inventoryService);
            }

            return $invoice;
        });

        if ($request->input('action') === 'review_pricing') {
            return redirect()->route('purchases.pricing-review', $invoice)
                ->with('status', 'info')
                ->with('message', 'تم حفظ المسودة بنجاح. يرجى مراجعة واعتماد أسعار البيع قبل الترحيل النهائي للمخزن.');
        }

        $message = $invoice->status === PurchaseInvoice::STATUS_POSTED
            ? 'تم حفظ فاتورة الشراء وترحيل أصنافها للمخازن كدفعات واردة بنجاح.'
            : 'تم حفظ مسودة فاتورة الشراء بنجاح.';

        return redirect()->route('purchases.show', $invoice)
            ->with('status', 'success')
            ->with('message', $message);
    }

    /**
     * Display the specified purchase invoice.
     */
    public function show(PurchaseInvoice $purchase): View
    {
        $purchase->load(['supplier', 'warehouse', 'creator', 'items.product.category', 'batches', 'payments.creator', 'payments.treasury', 'returns.creator']);
        $treasuries = Treasury::where('is_active', true)->orderBy('name')->get();

        return view('purchases.show', [
            'title' => 'فاتورة شراء: '.$purchase->invoice_number,
            'moduleName' => 'المشتريات والموردين',
            'invoice' => $purchase,
            'treasuries' => $treasuries,
        ]);
    }

    /**
     * Show the form for editing an existing draft purchase invoice.
     */
    public function edit(PurchaseInvoice $purchase): View|RedirectResponse
    {
        if ($purchase->status !== PurchaseInvoice::STATUS_DRAFT) {
            return redirect()->route('purchases.show', $purchase)
                ->with('error', 'لا يمكن تعديل الفاتورة لأنها ليست في حالة مسودة.');
        }

        $purchase->load(['items.product', 'items.unit']);
        $suppliers = Supplier::active()->orderBy('name')->get();
        $warehouses = Warehouse::active()->orderBy('name')->get();
        $products = Product::active()->with('stockBalances')->orderBy('name')->get();
        $units = class_exists(Unit::class) ? Unit::active()->orderBy('is_default', 'desc')->orderBy('name')->get() : collect();
        $categories = Category::active()->orderBy('name')->get();
        if ($categories->isEmpty()) {
            $categories = collect([Category::firstOrCreate(['name' => 'عام'], ['slug' => 'general', 'is_active' => true])]);
        }
        $suggestedSku = 'KY-'.date('y').'-'.str_pad((string) ((Product::max('id') ?? 0) + 1), 4, '0', STR_PAD_LEFT);

        $productsJson = $products->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'sku' => $p->sku,
            'barcode' => (string) ($p->barcode ?? ''),
            'cost_price' => (float) $p->cost_price,
            'unit' => $p->unit ?? 'قطعة',
            'unit_factor' => (float) ($p->unit_factor ?? 1.0),
            'package_factor' => (float) ($p->package_factor ?? 12.0),
            'pieces_per_package' => (int) ($p->pieces_per_package ?: (int) round($p->package_factor ?? 12.0)),
            'stocks' => $p->stockBalances->pluck('quantity', 'warehouse_id')->map(fn ($q) => (float) $q)->toArray(),
            'total_stock' => (float) $p->total_stock,
            'formatted_stock' => $p->formatStockBreakdown(),
        ])->values();

        $unitsJson = $units->map(fn ($u) => [
            'id' => $u->id,
            'name' => $u->name,
            'code' => $u->code,
            'factor' => (float) ($u->conversion_factor ?? 1.0),
            'is_default' => (bool) ($u->is_default ?? false),
        ])->values();

        return view('purchases.edit', [
            'title' => 'تعديل فاتورة شراء: '.$purchase->invoice_number,
            'moduleName' => 'المشتريات والموردين',
            'invoice' => $purchase,
            'suppliers' => $suppliers,
            'warehouses' => $warehouses,
            'products' => $products,
            'units' => $units,
            'categories' => $categories,
            'suggestedSku' => $suggestedSku,
            'productsJson' => $productsJson,
            'unitsJson' => $unitsJson,
        ]);
    }

    /**
     * Update the specified draft purchase invoice in storage.
     */
    public function update(Request $request, PurchaseInvoice $purchase, InventoryService $inventoryService): RedirectResponse
    {
        if ($purchase->status !== PurchaseInvoice::STATUS_DRAFT) {
            return redirect()->route('purchases.show', $purchase)
                ->with('error', 'لا يمكن تعديل الفاتورة لأنها ليست في حالة مسودة.');
        }

        $validated = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'invoice_date' => ['required', 'date'],
            'payment_type' => ['nullable', 'string', 'in:cash,credit,partial'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'due_date' => ['nullable', 'date'],
            'shipping_cost' => ['nullable', 'numeric', 'min:0'],
            'discount_type' => ['nullable', 'string', 'in:fixed,percentage'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.unit_id' => ['nullable', 'exists:units,id'],
            'items.*.unit_name' => ['nullable', 'string', 'max:50'],
            'items.*.conversion_factor' => ['nullable', 'numeric', 'min:0.0001'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ], [
            'supplier_id.required' => 'يرجى اختيار المورد.',
            'warehouse_id.required' => 'يرجى تحديد مستودع الاستلام.',
            'invoice_date.required' => 'تاريخ الفاتورة مطلوب.',
            'items.required' => 'يجب إضافة صنف واحد على الأقل في الفاتورة.',
            'items.*.product_id.required' => 'يرجى اختيار الصنف.',
            'items.*.quantity.required' => 'الكمية مطلوبة.',
            'items.*.unit_cost.required' => 'سعر التكلفة مطلوب.',
        ]);

        $shippingCost = (float) ($validated['shipping_cost'] ?? 0.00);
        $discountType = $validated['discount_type'] ?? 'fixed';
        $discountValue = (float) ($validated['discount_value'] ?? 0.00);

        DB::transaction(function () use ($purchase, $validated, $shippingCost, $discountType, $discountValue, $request, $inventoryService) {
            $subtotal = 0.00;
            $itemsData = [];

            foreach ($validated['items'] as $itemInput) {
                $qty = (float) $itemInput['quantity'];
                $cost = (float) $itemInput['unit_cost'];
                $lineSubtotal = round($qty * $cost, 2);
                $subtotal += $lineSubtotal;

                $prodId = (int) $itemInput['product_id'];
                $product = Product::find($prodId);
                $unitId = ! empty($itemInput['unit_id']) ? (int) $itemInput['unit_id'] : null;
                $factor = 1.0000;

                if (! empty($itemInput['conversion_factor']) && (float) $itemInput['conversion_factor'] > 0) {
                    $factor = (float) $itemInput['conversion_factor'];
                } elseif ($unitId) {
                    $unit = Unit::find($unitId);
                    if ($unit && (float) $unit->conversion_factor > 0) {
                        $factor = (float) $unit->conversion_factor;
                    }
                } elseif (! empty($itemInput['unit_name'])) {
                    $unit = Unit::where('name', trim($itemInput['unit_name']))->first();
                    if ($unit) {
                        $unitId = $unit->id;
                        $factor = (float) $unit->conversion_factor > 0 ? (float) $unit->conversion_factor : 1.0000;
                    }
                } elseif ($product && (float) $product->unit_factor > 1) {
                    $factor = (float) $product->unit_factor;
                }

                $baseQty = round($qty * $factor, 4);

                $itemsData[] = [
                    'purchase_invoice_id' => $purchase->id,
                    'product_id' => (int) $itemInput['product_id'],
                    'unit_id' => $unitId,
                    'conversion_factor' => $factor,
                    'quantity' => $qty,
                    'base_quantity' => $baseQty,
                    'unit_cost' => $cost,
                    'subtotal' => $lineSubtotal,
                    'notes' => $itemInput['notes'] ?? null,
                ];
            }

            $discountAmount = 0.00;
            if ($discountValue > 0) {
                if ($discountType === 'percentage') {
                    $discountAmount = round(($subtotal * min(100, $discountValue)) / 100, 2);
                } else {
                    $discountAmount = min($subtotal, $discountValue);
                }
            }

            $totalAmount = max(0, round($subtotal - $discountAmount + $shippingCost, 2));

            $paymentType = $validated['payment_type'] ?? PurchaseInvoice::PAYMENT_CASH;
            if ($paymentType === PurchaseInvoice::PAYMENT_CASH) {
                $paidAmount = $totalAmount;
                $remainingAmount = 0.00;
            } elseif ($paymentType === PurchaseInvoice::PAYMENT_CREDIT) {
                $paidAmount = 0.00;
                $remainingAmount = $totalAmount;
            } else {
                $paidAmount = min($totalAmount, max(0.00, (float) ($validated['paid_amount'] ?? 0.00)));
                $remainingAmount = max(0.00, round($totalAmount - $paidAmount, 2));
            }
            $dueDate = filled($validated['due_date'] ?? null) ? $validated['due_date'] : null;

            $notes = $validated['notes'] ?? null;
            if ($discountAmount > 0) {
                $discountNote = $discountType === 'percentage'
                    ? " [خصم: {$discountValue}% بقيمة {$discountAmount} ج.م]"
                    : " [خصم نقدي: {$discountAmount} ج.م]";
                $notes = trim(($notes ? $notes.' | ' : '').$discountNote);
            }

            $purchase->update([
                'supplier_id' => (int) $validated['supplier_id'],
                'warehouse_id' => (int) $validated['warehouse_id'],
                'invoice_date' => $validated['invoice_date'],
                'payment_type' => $paymentType,
                'subtotal' => $subtotal,
                'shipping_cost' => $shippingCost,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'remaining_amount' => $remainingAmount,
                'due_date' => $dueDate,
                'notes' => $notes,
            ]);

            $purchase->items()->delete();
            foreach ($itemsData as $row) {
                PurchaseInvoiceItem::create($row);
            }

            if ($request->input('action') === 'post') {
                $this->executePosting($purchase, $inventoryService);
            }
        });

        if ($request->input('action') === 'review_pricing') {
            return redirect()->route('purchases.pricing-review', $purchase)
                ->with('status', 'info')
                ->with('message', 'تم تحديث المسودة بنجاح. يرجى مراجعة واعتماد أسعار البيع.');
        }

        $msg = $purchase->status === PurchaseInvoice::STATUS_POSTED
            ? 'تم تحديث فاتورة الشراء وترحيل أصنافها للمخازن بنجاح.'
            : 'تم تحديث مسودة فاتورة الشراء بنجاح.';

        return redirect()->route('purchases.show', $purchase)
            ->with('status', 'success')
            ->with('message', $msg);
    }

    /**
     * Display a dedicated, template-driven printable view of the purchase invoice.
     * ZERO data mutation, strictly read-only and authorization verified.
     */
    public function print(Request $request, PurchaseInvoice $purchase, PrintTemplateService $templateService): View
    {
        $purchase->loadMissing(['supplier', 'warehouse', 'creator', 'items.product.category']);

        $format = (string) $request->query('format', $request->query('paper_format', PrintTemplate::FORMAT_A4));
        if (! in_array($format, PrintTemplateService::SUPPORTED_FORMATS, true)) {
            $format = PrintTemplate::FORMAT_A4;
        }

        $template = $templateService->getEffectiveTemplate(PrintTemplate::DOC_PURCHASE_INVOICE, $format);
        $companySetting = CompanySetting::getSettings();
        $autoPrint = $request->boolean('autoprint', false);

        $colorHex = $template->getSetting('general.primary_color', '#1e293b');
        $colorClass = match ($colorHex) {
            '#059669', '#10b981' => 'theme-emerald',
            '#2563eb', '#3b82f6' => 'theme-blue',
            '#7c3aed', '#8b5cf6' => 'theme-purple',
            '#e11d48', '#f43f5e' => 'theme-rose',
            '#d97706', '#f59e0b' => 'theme-amber',
            '#000000' => 'theme-black',
            default => 'theme-navy',
        };

        return view('purchases.print', [
            'title' => 'طباعة فاتورة شراء: '.$purchase->invoice_number,
            'invoice' => $purchase,
            'template' => $template,
            'settings' => $template->settings,
            'format' => $format,
            'colorClass' => $colorClass,
            'company' => $companySetting,
            'autoPrint' => $autoPrint,
        ]);
    }

    /**
     * Direct print purchase invoice receipt or A4/A5 document via the centralized Python printing engine.
     */
    public function directPrint(Request $request, PurchaseInvoice $purchase, PythonPrintEngineService $printService): JsonResponse
    {
        $validated = $request->validate([
            'printer' => ['nullable', 'string', 'max:255'],
            'format' => ['nullable', 'string', 'in:thermal_80mm,a4,a5'],
        ]);

        $printer = $validated['printer'] ?? null;
        $format = $validated['format'] ?? $request->query('format', 'thermal_80mm');
        $result = $printService->printPurchaseInvoice($purchase, $format, $printer);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Post a draft purchase invoice and receive items into the warehouse atomically.
     */
    /**
     * Dedicated Product Pricing Review Page before final posting of purchase invoice.
     */
    public function pricingReview(PurchaseInvoice $purchase): View|RedirectResponse
    {
        if ($purchase->status !== PurchaseInvoice::STATUS_DRAFT) {
            return redirect()->route('purchases.show', $purchase)
                ->with('error', 'هذه الفاتورة تم ترحيلها بالفعل ولا يمكن مراجعة تسعيرها مجدداً.');
        }

        $purchase->loadMissing(['supplier', 'warehouse', 'items.product.category', 'items.unit']);

        return view('purchases.pricing-review', [
            'purchase' => $purchase,
            'canEditPrices' => auth()->user()?->can('edit-product-prices') ?? false,
        ]);
    }

    /**
     * Confirm pricing decisions and finalize posting into FIFO layers.
     */
    public function confirmPricingAndPost(Request $request, PurchaseInvoice $purchase, InventoryService $inventoryService): RedirectResponse
    {
        if ($purchase->status !== PurchaseInvoice::STATUS_DRAFT) {
            return redirect()->route('purchases.show', $purchase)
                ->with('error', 'هذه الفاتورة تم ترحيلها مسبقاً.');
        }

        $canEditPrices = auth()->user()?->can('edit-product-prices') ?? false;

        $validated = $request->validate([
            'prices' => ['nullable', 'array'],
            'prices.*.retail_price' => ['nullable', 'numeric', 'min:0'],
            'prices.*.wholesale_price' => ['nullable', 'numeric', 'min:0'],
            'prices.*.distributor_price' => ['nullable', 'numeric', 'min:0'],
            'update_master_prices' => ['nullable', 'boolean'],
        ]);

        $customPrices = $validated['prices'] ?? [];
        $updateMaster = ! empty($validated['update_master_prices']) && $canEditPrices;

        DB::transaction(function () use ($purchase, $inventoryService, $customPrices, $updateMaster) {
            $invoice = PurchaseInvoice::where('id', $purchase->id)->lockForUpdate()->first();
            if ($invoice->status !== PurchaseInvoice::STATUS_DRAFT) {
                throw new \InvalidArgumentException('تم ترحيل هذه الفاتورة بالفعل من قبل مستخدم آخر.');
            }

            // Update master product prices if authorized and requested
            if ($updateMaster) {
                foreach ($customPrices as $productId => $prices) {
                    $product = Product::lockForUpdate()->find((int) $productId);
                    if ($product) {
                        $updates = [];
                        if (isset($prices['retail_price']) && is_numeric($prices['retail_price'])) {
                            $updates['retail_price'] = (float) $prices['retail_price'];
                        }
                        if (isset($prices['wholesale_price']) && is_numeric($prices['wholesale_price'])) {
                            $updates['wholesale_price'] = (float) $prices['wholesale_price'];
                        }
                        if (isset($prices['distributor_price']) && is_numeric($prices['distributor_price'])) {
                            $updates['distributor_price'] = (float) $prices['distributor_price'];
                        }
                        if (! empty($updates)) {
                            $product->update($updates);
                        }
                    }
                }
            }

            $this->executePosting($invoice, $inventoryService, $customPrices);
        });

        return redirect()->route('purchases.show', $purchase)
            ->with('status', 'success')
            ->with('message', 'تم اعتماد أسعار البيع وترحيل فاتورة الشراء للمخازن وتوليد طبقات FIFO بنجاح.');
    }

    /**
     * Post a draft purchase invoice and receive items into the warehouse atomically.
     */
    public function post(Request $request, PurchaseInvoice $purchase, InventoryService $inventoryService): RedirectResponse
    {
        if ($purchase->status !== PurchaseInvoice::STATUS_DRAFT) {
            return back()->with('error', 'لا يمكن ترحيل هذه الفاتورة لأنها ليست في حالة مسودة.');
        }

        if ($request->has('review')) {
            return redirect()->route('purchases.pricing-review', $purchase);
        }

        DB::transaction(function () use ($purchase, $inventoryService) {
            $this->executePosting($purchase, $inventoryService);
        });

        return redirect()->route('purchases.show', $purchase)
            ->with('status', 'success')
            ->with('message', 'تم ترحيل الفاتورة بنجاح وتوليد دفعات المخزون وفق سياسة الوارد أولاً يصرف أولاً (FIFO).');
    }

    /**
     * Cancel a draft purchase invoice.
     */
    public function cancel(PurchaseInvoice $purchase): RedirectResponse
    {
        if ($purchase->status !== PurchaseInvoice::STATUS_DRAFT) {
            return back()->with('error', 'يمكن فقط إلغاء الفواتير التي في حالة مسودة.');
        }

        $purchase->update(['status' => PurchaseInvoice::STATUS_CANCELLED]);

        return redirect()->route('purchases.show', $purchase)
            ->with('status', 'success')
            ->with('message', 'تم إلغاء فاتورة الشراء.');
    }

    /**
     * Remove the specified purchase invoice from storage.
     */
    public function destroy(PurchaseInvoice $purchase): RedirectResponse
    {
        if ($purchase->status === PurchaseInvoice::STATUS_POSTED) {
            return back()->with('status', 'error')
                ->with('message', 'لا يمكن مسح فاتورة مشتريات مرحلة للمخازن مباشرة حفاظاً على توازن جرد FIFO؛ يمكنك تسجيل مرتجع مشتريات أو إلغاء استلام.');
        }

        DB::transaction(function () use ($purchase) {
            $purchase->payments()->delete();
            $purchase->items()->delete();
            $purchase->delete();
        });

        return redirect()->route('purchases.index')
            ->with('status', 'success')
            ->with('message', 'تم مسح فاتورة المشتريات بنجاح.');
    }

    /**
     * Internal atomic posting helper with unit conversion and optional batch price overrides.
     *
     * @param  array<int, array{retail_price?: float, wholesale_price?: float, distributor_price?: float}>  $customPrices
     */
    private function executePosting(PurchaseInvoice $invoice, InventoryService $inventoryService, array $customPrices = []): void
    {
        $invoice->loadMissing(['items.product', 'items.unit']);

        foreach ($invoice->items as $item) {
            $product = $item->product;
            $factor = (float) ($item->conversion_factor > 0 ? $item->conversion_factor : 1.0);
            $baseQty = (float) ($item->base_quantity ?: round((float) $item->quantity * $factor, 4));
            $costPerBaseUnit = $baseQty > 0 ? round((float) $item->subtotal / $baseQty, 4) : (float) $item->unit_cost;

            $pricing = $customPrices[$item->product_id] ?? [];

            $retailPrice = isset($pricing['retail_price']) && is_numeric($pricing['retail_price'])
                ? (float) $pricing['retail_price']
                : (! is_null($product?->retail_price) ? (float) $product->retail_price : null);

            $wholesalePrice = isset($pricing['wholesale_price']) && is_numeric($pricing['wholesale_price'])
                ? (float) $pricing['wholesale_price']
                : (! is_null($product?->wholesale_price) ? (float) $product->wholesale_price : null);

            $distributorPrice = isset($pricing['distributor_price']) && is_numeric($pricing['distributor_price'])
                ? (float) $pricing['distributor_price']
                : (! is_null($product?->distributor_price) ? (float) $product->distributor_price : (! is_null($product?->wholesale_price) ? (float) $product->wholesale_price : null));

            $inventoryService->addStock(
                productId: $item->product_id,
                warehouseId: $invoice->warehouse_id,
                quantity: $baseQty,
                costPrice: $costPerBaseUnit,
                notes: 'فاتورة شراء رقم: '.$invoice->invoice_number,
                referenceType: 'purchase_invoice',
                referenceId: $invoice->id,
                userId: auth()->id(),
                receivedAt: Carbon::parse($invoice->invoice_date),
                supplierId: $invoice->supplier_id,
                retailPrice: $retailPrice,
                wholesalePrice: $wholesalePrice,
                distributorPrice: $distributorPrice,
                totalInitialCost: (float) $item->subtotal,
                unitId: $item->unit_id,
                conversionFactor: $factor,
                sourceType: 'purchase_invoice',
                sourceId: $invoice->id
            );
        }

        $invoice->update([
            'status' => PurchaseInvoice::STATUS_POSTED,
            'posted_at' => now(),
        ]);

        if ((float) $invoice->remaining_amount > 0 && $invoice->supplier_id) {
            $supplier = Supplier::lockForUpdate()->find($invoice->supplier_id);
            if ($supplier) {
                $supplier->increment('current_balance', (float) $invoice->remaining_amount);
            }
        }

        // Record general ledger journal entry
        app(AccountingService::class)->recordPurchaseInvoice($invoice->fresh());
    }
}
