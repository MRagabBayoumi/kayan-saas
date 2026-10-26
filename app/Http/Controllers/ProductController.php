<?php

namespace App\Http\Controllers;

use App\Models\BarcodeSetting;
use App\Models\BatchCostMovement;
use App\Models\Category;
use App\Models\CompanySetting;
use App\Models\InventoryBatch;
use App\Models\PrintTemplate;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Services\BarcodeGeneratorService;
use App\Services\InventoryService;
use App\Services\Printing\PythonPrintEngineService;
use App\Services\PrintTemplateService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProductController extends Controller
{
    /**
     * Display a paginated listing of products with search and category filters.
     */
    public function index(Request $request): View
    {
        $categories = Category::active()->orderBy('name')->get();
        $units = Unit::active()->orderBy('name')->get();

        $query = Product::with(['category', 'stockBalances']);

        if ($request->filled('search')) {
            $query->search($request->input('search'));
        }

        $selectedCategoryIds = [];
        if ($request->has('category_ids') && is_array($request->input('category_ids'))) {
            $selectedCategoryIds = array_map('intval', array_filter($request->input('category_ids')));
        } elseif ($request->filled('categories')) {
            $selectedCategoryIds = array_map('intval', array_filter(explode(',', (string) $request->input('categories'))));
        } elseif ($request->filled('category_id')) {
            $selectedCategoryIds = [(int) $request->input('category_id')];
        }

        if (! empty($selectedCategoryIds)) {
            $query->whereIn('category_id', $selectedCategoryIds);
        }

        if ($request->filled('unit')) {
            $unitFilter = trim((string) $request->input('unit'));
            if (in_array($unitFilter, ['دستة', 'دسته', 'DZ'], true)) {
                $query->whereIn('unit', ['دستة', 'دسته', 'DZ']);
            } elseif (in_array($unitFilter, ['قطعة', 'قطعه', 'PCS'], true)) {
                $query->whereIn('unit', ['قطعة', 'قطعه', 'PCS']);
            } elseif (in_array($unitFilter, ['كرتونة', 'كرتونه', 'CTN'], true)) {
                $query->whereIn('unit', ['كرتونة', 'كرتونه', 'CTN']);
            } elseif (in_array($unitFilter, ['باكت', 'PKT'], true)) {
                $query->whereIn('unit', ['باكت', 'PKT']);
            } elseif (in_array($unitFilter, ['طقم', 'SET'], true)) {
                $query->whereIn('unit', ['طقم', 'SET']);
            } else {
                $query->where('unit', $unitFilter);
            }
        }

        if ($request->filled('status')) {
            if ($request->input('status') === 'active') {
                $query->where('is_active', true);
            } elseif ($request->input('status') === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $perPage = (int) $request->input('per_page', 15);
        if (! in_array($perPage, [10, 15, 25, 50, 100], true)) {
            $perPage = 15;
        }

        $products = $query->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('products.index', [
            'title' => 'المنتجات والأصناف',
            'moduleName' => 'المخزون والمنتجات',
            'products' => $products,
            'categories' => $categories,
            'units' => $units,
            'currentSearch' => $request->input('search'),
            'currentCategoryId' => count($selectedCategoryIds) === 1 ? $selectedCategoryIds[0] : null,
            'selectedCategoryIds' => $selectedCategoryIds,
            'currentUnit' => $request->input('unit'),
            'currentStatus' => $request->input('status'),
            'currentPerPage' => $perPage,
            'barcodeSettings' => BarcodeSetting::getSettings(),
        ]);
    }

    /**
     * Show the form for creating a new product.
     */
    public function create(): View
    {
        $categories = Category::active()->orderBy('name')->get();
        $warehouses = Warehouse::active()->orderBy('name')->get();
        $defaultWarehouse = Warehouse::where('is_default', true)->first() ?? $warehouses->first();

        // Generate a suggested SKU format e.g. KY-26-0001
        $suggestedSku = 'KY-'.date('y').'-'.str_pad((string) (Product::max('id') + 1), 4, '0', STR_PAD_LEFT);

        return view('products.create', [
            'title' => 'إضافة منتج جديد',
            'moduleName' => 'المخزون والمنتجات',
            'categories' => $categories,
            'warehouses' => $warehouses,
            'units' => Unit::active()->orderBy('id')->get(),
            'defaultWarehouse' => $defaultWarehouse,
            'suggestedSku' => $suggestedSku,
        ]);
    }

    /**
     * Store a newly created product in storage.
     */
    public function store(Request $request, InventoryService $inventoryService): RedirectResponse
    {
        $validated = $request->validate([
            'sku' => ['required', 'string', 'max:80', 'unique:products,sku'],
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'barcode' => ['nullable', 'string', 'max:100', 'unique:products,barcode'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'wholesale_price' => ['required', 'numeric', 'min:0'],
            'retail_price' => ['required', 'numeric', 'min:0'],
            'distributor_price' => ['nullable', 'numeric', 'min:0.01'],
            'unit' => ['required', 'string', 'max:50'],
            'pieces_per_package' => ['nullable', 'integer', 'min:1'],
            'min_stock_alert' => ['required', 'integer', 'min:0'],
            'opening_quantity' => ['nullable', 'numeric', 'min:0'],
            'opening_cost' => ['nullable', 'numeric', 'min:0'],
            'opening_warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'opening_balance_date' => ['nullable', 'date'],
            'product_image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'is_active' => ['sometimes', 'boolean'],
        ], [
            'sku.required' => 'كود الصنف (SKU) مطلوب.',
            'sku.unique' => 'كود الصنف مستخدم بالفعل في صنف آخر.',
            'name.required' => 'اسم المنتج مطلوب ولا يمكن تركه فارغاً.',
            'category_id.required' => 'يرجى اختيار تصنيف المنتج.',
            'category_id.exists' => 'التصنيف المختار غير موجود في النظام.',
            'barcode.unique' => 'الباركود المدخل مستخدم بالفعل في صنف آخر.',
            'cost_price.required' => 'سعر التكلفة مطلوب.',
            'cost_price.min' => 'سعر التكلفة لا يمكن أن يكون سالباً.',
            'wholesale_price.required' => 'سعر الجملة مطلوب.',
            'wholesale_price.min' => 'سعر الجملة لا يمكن أن يكون سالباً.',
            'retail_price.required' => 'سعر التجزئة مطلوب.',
            'retail_price.min' => 'سعر التجزئة لا يمكن أن يكون سالباً.',
            'distributor_price.min' => 'سعر الموزع يجب أن يكون أكبر من الصفر.',
            'distributor_price.numeric' => 'سعر الموزع يجب أن يكون قيمة رقمية صحيحة.',
            'pieces_per_package.min' => 'عدد القطع في الطرد يجب أن يكون 1 على الأقل.',
            'unit.required' => 'وحدة القياس مطلوبة.',
            'min_stock_alert.required' => 'حد الطلب الأدنى مطلوب.',
            'min_stock_alert.min' => 'حد الطلب الأدنى لا يمكن أن يكون سالباً.',
            'opening_quantity.min' => 'الكمية الافتتاحية لا يمكن أن تكون سالبة.',
            'opening_cost.min' => 'تكلفة الرصيد الافتتاحي لا يمكن أن تكون سالبة.',
            'opening_warehouse_id.exists' => 'المستودع المحدد للرصيد الافتتاحي غير موجود.',
            'product_image.image' => 'الملف المرفوع يجب أن يكون صورة صالحة.',
            'product_image.mimes' => 'صيغ الصور المدعومة هي: PNG, JPG, JPEG, WebP فقط.',
            'product_image.max' => 'الحد الأقصى لحجم صورة المنتج هو 2 ميجابايت.',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        // Auto-fill pieces_per_package from unit conversion factor if omitted
        if (empty($validated['pieces_per_package']) && ! empty($validated['unit'])) {
            $matchedUnit = Unit::where('name', $validated['unit'])->first();
            if ($matchedUnit && (float) $matchedUnit->conversion_factor > 1) {
                $validated['pieces_per_package'] = (int) round((float) $matchedUnit->conversion_factor);
            }
        }

        // Normalize empty barcode string to null so MySQL unique constraint permits it
        if (empty($validated['barcode'])) {
            $validated['barcode'] = null;
        }

        // Handle image upload
        if ($request->hasFile('product_image')) {
            $file = $request->file('product_image');
            $extension = $file->getClientOriginalExtension();
            $filename = 'prod_'.Str::random(24).'.'.$extension;
            $validated['image_path'] = $file->storeAs('products', $filename, 'public');
        }

        // Enforce role-based price definition protection
        if (! $request->user()?->can('edit-product-prices')) {
            $priceFields = ['cost_price', 'wholesale_price', 'retail_price', 'distributor_price'];
            foreach ($priceFields as $field) {
                if (isset($validated[$field]) && (float) $validated[$field] > 0) {
                    abort(403, 'غير مصرح لك بتحديد أو تعديل أسعار المنتجات. هذه الصلاحية مخصصة لمدير النظام فقط.');
                }
            }
            if (isset($validated['opening_cost']) && (float) $validated['opening_cost'] > 0) {
                abort(403, 'غير مصرح لك بتحديد تكلفة الرصيد الافتتاحي. هذه الصلاحية مخصصة لمدير النظام فقط.');
            }
        }

        // Validate opening stock logic
        $openingQty = isset($validated['opening_quantity']) ? (float) $validated['opening_quantity'] : 0.00;
        if ($openingQty > 0) {
            if (empty($validated['opening_warehouse_id'])) {
                return back()->withInput()->withErrors(['opening_warehouse_id' => 'يرجى تحديد المستودع المستلم للرصيد الافتتاحي.']);
            }
            if (! isset($validated['opening_cost']) || $validated['opening_cost'] === '' || $validated['opening_cost'] === null) {
                $validated['opening_cost'] = $validated['cost_price'];
            }
            if (empty($validated['opening_balance_date'])) {
                $validated['opening_balance_date'] = now()->toDateString();
            }
        } else {
            $validated['opening_quantity'] = 0.00;
            $validated['opening_cost'] = null;
            $validated['opening_warehouse_id'] = null;
            $validated['opening_balance_date'] = null;
        }

        DB::transaction(function () use ($validated, $inventoryService, $openingQty) {
            $product = Product::create($validated);

            if ($openingQty > 0) {
                $warehouseId = (int) $validated['opening_warehouse_id'];
                $openingCost = (float) $validated['opening_cost'];
                $receivedDate = Carbon::parse($validated['opening_balance_date']);

                $unitFactor = (float) ($product->unit_factor > 0 ? $product->unit_factor : 1.0);
                $baseOpeningQty = round($openingQty * $unitFactor, 4);
                $baseOpeningCost = round($openingCost / $unitFactor, 4);
                $baseRetailPrice = ! is_null($product->retail_price) ? round((float) $product->retail_price / $unitFactor, 4) : null;
                $baseWholesalePrice = ! is_null($product->wholesale_price) ? round((float) $product->wholesale_price / $unitFactor, 4) : null;
                $baseDistributorPrice = ! is_null($product->distributor_price) ? round((float) $product->distributor_price / $unitFactor, 4) : (! is_null($product->wholesale_price) ? round((float) $product->wholesale_price / $unitFactor, 4) : null);

                $inventoryService->addStock(
                    productId: $product->id,
                    warehouseId: $warehouseId,
                    quantity: $baseOpeningQty,
                    costPrice: $baseOpeningCost,
                    notes: 'رصيد افتتاحي عند تعريف الصنف: '.$product->name.($unitFactor > 1 ? " ({$openingQty} {$product->unit} = {$baseOpeningQty} قطعة)" : ''),
                    referenceType: 'opening_balance',
                    referenceId: $product->id,
                    userId: auth()->id(),
                    receivedAt: $receivedDate,
                    retailPrice: $baseRetailPrice,
                    wholesalePrice: $baseWholesalePrice,
                    distributorPrice: $baseDistributorPrice,
                    totalInitialCost: round($openingQty * $openingCost, 2),
                    sourceType: 'opening_balance',
                    sourceId: $product->id
                );
            }
        });

        return redirect()->route('products.index')
            ->with('status', 'success')
            ->with('message', 'تمت إضافة المنتج والرصيد الافتتاحي بنجاح.');
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product, BarcodeGeneratorService $barcodeService): View
    {
        $product->load(['category', 'stockBalances.warehouse']);
        $recentMovements = StockMovement::where('product_id', $product->id)
            ->with(['fromWarehouse', 'toWarehouse', 'user', 'customer', 'supplier'])
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        $barcodeSettings = BarcodeSetting::getSettings();
        $barcodeValue = $barcodeService->getBarcodeValue($product);
        $barcodeSvg = $barcodeService->generateSvg($barcodeValue);
        $completeLabelSvg = $barcodeService->generateCompleteLabelSvg(
            $product->name,
            $barcodeValue,
            $product->sku,
            (int) $barcodeSettings->label_width_mm,
            (int) $barcodeSettings->label_height_mm,
            $barcodeSettings,
            (float) $product->retail_price,
            (float) $product->wholesale_price
        );

        return view('products.show', [
            'title' => $product->name,
            'moduleName' => 'المخزون والمنتجات',
            'product' => $product,
            'recentMovements' => $recentMovements,
            'barcodeValue' => $barcodeValue,
            'barcodeSvg' => $barcodeSvg,
            'completeLabelSvg' => $completeLabelSvg,
            'barcodeSettings' => $barcodeSettings,
        ]);
    }

    /**
     * Show the form for editing the specified product.
     */
    public function edit(Product $product): View
    {
        $categories = Category::active()->orderBy('name')->get();
        $product->load(['category', 'openingWarehouse', 'batches', 'stockBalances.warehouse']);

        return view('products.edit', [
            'title' => 'تعديل: '.$product->name,
            'moduleName' => 'المخزون والمنتجات',
            'product' => $product,
            'categories' => $categories,
            'units' => Unit::active()->orderBy('id')->get(),
            'warehouses' => Warehouse::active()->orderBy('name')->get(),
            'barcodeSettings' => BarcodeSetting::getSettings(),
        ]);
    }

    /**
     * Update the specified product in storage.
     */
    public function update(Request $request, Product $product, InventoryService $inventoryService): RedirectResponse
    {
        $validated = $request->validate([
            'sku' => ['required', 'string', 'max:80', Rule::unique('products', 'sku')->ignore($product->id)],
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'barcode' => ['nullable', 'string', 'max:100', Rule::unique('products', 'barcode')->ignore($product->id)],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'wholesale_price' => ['required', 'numeric', 'min:0'],
            'retail_price' => ['required', 'numeric', 'min:0'],
            'distributor_price' => ['nullable', 'numeric', 'min:0.01'],
            'unit' => ['required', 'string', 'max:50'],
            'pieces_per_package' => ['nullable', 'integer', 'min:1'],
            'min_stock_alert' => ['required', 'integer', 'min:0'],
            'product_image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'is_active' => ['sometimes', 'boolean'],
        ], [
            'sku.required' => 'كود الصنف (SKU) مطلوب.',
            'sku.unique' => 'كود الصنف مستخدم بالفعل في صنف آخر.',
            'name.required' => 'اسم المنتج مطلوب ولا يمكن تركه فارغاً.',
            'category_id.required' => 'يرجى اختيار تصنيف المنتج.',
            'category_id.exists' => 'التصنيف المختار غير موجود في النظام.',
            'barcode.unique' => 'الباركود المدخل مستخدم بالفعل في صنف آخر.',
            'cost_price.required' => 'سعر التكلفة مطلوب.',
            'cost_price.min' => 'سعر التكلفة لا يمكن أن يكون سالباً.',
            'wholesale_price.required' => 'سعر الجملة مطلوب.',
            'wholesale_price.min' => 'سعر الجملة لا يمكن أن يكون سالباً.',
            'retail_price.required' => 'سعر التجزئة مطلوب.',
            'retail_price.min' => 'سعر التجزئة لا يمكن أن يكون سالباً.',
            'distributor_price.min' => 'سعر الموزع يجب أن يكون أكبر من الصفر.',
            'distributor_price.numeric' => 'سعر الموزع يجب أن يكون قيمة رقمية صحيحة.',
            'pieces_per_package.min' => 'عدد القطع في الطرد يجب أن يكون 1 على الأقل.',
            'unit.required' => 'وحدة القياس مطلوبة.',
            'min_stock_alert.required' => 'حد الطلب الأدنى مطلوب.',
            'min_stock_alert.min' => 'حد الطلب الأدنى لا يمكن أن يكون سالباً.',
            'product_image.image' => 'الملف المرفوع يجب أن يكون صورة صالحة.',
            'product_image.mimes' => 'صيغ الصور المدعومة هي: PNG, JPG, JPEG, WebP فقط.',
            'product_image.max' => 'الحد الأقصى لحجم صورة المنتج هو 2 ميجابايت.',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        // Auto-fill pieces_per_package from unit conversion factor if omitted
        if (empty($validated['pieces_per_package']) && ! empty($validated['unit'])) {
            $matchedUnit = Unit::where('name', $validated['unit'])->first();
            if ($matchedUnit && (float) $matchedUnit->conversion_factor > 1) {
                $validated['pieces_per_package'] = (int) round((float) $matchedUnit->conversion_factor);
            }
        }

        // Enforce role-based price modification protection
        if (! $request->user()?->can('edit-product-prices')) {
            $priceFields = ['cost_price', 'wholesale_price', 'retail_price', 'distributor_price'];
            foreach ($priceFields as $field) {
                $oldVal = (float) ($product->{$field} ?? 0);
                $newVal = (float) ($validated[$field] ?? 0);
                if (abs($oldVal - $newVal) > 0.0001) {
                    abort(403, 'غير مصرح لك بتعديل أسعار المنتجات. هذه الصلاحية مخصصة لمدير النظام فقط.');
                }
            }
        }

        $openingQty = null;
        $openingCost = null;
        $openingWarehouseId = null;
        $openingDate = null;

        // Admin-only opening balance modification
        if ($request->user()?->isAdmin() && $request->has('opening_quantity')) {
            $openingValidated = $request->validate([
                'opening_quantity' => ['required', 'numeric', 'min:0'],
                'opening_cost' => ['nullable', 'numeric', 'min:0'],
                'opening_warehouse_id' => ['nullable', 'exists:warehouses,id'],
                'opening_balance_date' => ['nullable', 'date'],
            ], [
                'opening_quantity.required' => 'الكمية الافتتاحية مطلوبة.',
                'opening_quantity.numeric' => 'الكمية الافتتاحية يجب أن تكون قيمة رقمية.',
                'opening_quantity.min' => 'الكمية الافتتاحية لا يمكن أن تكون سالبة.',
                'opening_cost.numeric' => 'تكلفة الرصيد الافتتاحي يجب أن تكون قيمة رقمية.',
                'opening_cost.min' => 'تكلفة الرصيد الافتتاحي لا يمكن أن تكون سالبة.',
                'opening_warehouse_id.exists' => 'المستودع المختار للرصيد الافتتاحي غير موجود.',
            ]);

            $openingQty = (float) $openingValidated['opening_quantity'];
            $openingWarehouseId = ! empty($openingValidated['opening_warehouse_id'])
                ? (int) $openingValidated['opening_warehouse_id']
                : ($product->opening_warehouse_id ? (int) $product->opening_warehouse_id : null);

            if ($openingQty > 0 && empty($openingWarehouseId)) {
                return back()->withInput()->withErrors(['opening_warehouse_id' => 'يرجى تحديد المستودع المستلم للرصيد الافتتاحي.']);
            }

            $openingCost = (! empty($openingValidated['opening_cost']) && $openingValidated['opening_cost'] !== '')
                ? (float) $openingValidated['opening_cost']
                : (float) $validated['cost_price'];

            $openingDate = ! empty($openingValidated['opening_balance_date'])
                ? Carbon::parse($openingValidated['opening_balance_date'])
                : now();

            if ($openingDate->year < 2000) {
                $openingDate = $product->created_at ? $product->created_at->copy() : now();
            }
        }

        // Normalize empty barcode string to null
        if (empty($validated['barcode'])) {
            $validated['barcode'] = null;
        }

        // Handle image upload and cleanup of old image
        if ($request->hasFile('product_image')) {
            $file = $request->file('product_image');
            $extension = $file->getClientOriginalExtension();
            $filename = 'prod_'.Str::random(24).'.'.$extension;
            $newPath = $file->storeAs('products', $filename, 'public');

            if ($product->image_path && Storage::disk('public')->exists($product->image_path)) {
                Storage::disk('public')->delete($product->image_path);
            }

            $validated['image_path'] = $newPath;
        }

        DB::transaction(function () use (
            $product,
            $validated,
            $request,
            $inventoryService,
            $openingQty,
            $openingCost,
            $openingWarehouseId,
            $openingDate
        ) {
            if ($request->user()?->isAdmin() && $openingQty !== null) {
                $oldWarehouseId = (int) ($product->opening_warehouse_id ?? 0);

                // Fill product attributes so unit & pieces_per_package reflect the submitted data
                $product->fill($validated);
                $unitFactor = (float) ($product->unit_factor > 0 ? $product->unit_factor : 1.0);
                $baseOpeningQty = round($openingQty * $unitFactor, 4);
                $baseOpeningCost = round($openingCost / $unitFactor, 4);
                $baseRetailPrice = ! is_null($product->retail_price) ? round((float) $product->retail_price / $unitFactor, 4) : null;
                $baseWholesalePrice = ! is_null($product->wholesale_price) ? round((float) $product->wholesale_price / $unitFactor, 4) : null;
                $baseDistributorPrice = ! is_null($product->distributor_price) ? round((float) $product->distributor_price / $unitFactor, 4) : (! is_null($product->wholesale_price) ? round((float) $product->wholesale_price / $unitFactor, 4) : null);

                // Find existing opening stock movement & batch
                $openingMovement = StockMovement::where('product_id', $product->id)
                    ->where('reference_type', 'opening_balance')
                    ->where('reference_id', $product->id)
                    ->first();

                $openingBatch = null;
                if ($openingMovement && $openingMovement->batch_id) {
                    $openingBatch = InventoryBatch::find($openingMovement->batch_id);
                }
                if (! $openingBatch) {
                    $openingBatch = InventoryBatch::where('product_id', $product->id)
                        ->where(function ($q) use ($product) {
                            $q->where(fn ($sq) => $sq->where('source_type', 'opening_balance')->where('source_id', $product->id))
                                ->orWhere('notes', 'like', '%رصيد افتتاحي%');
                        })->first();
                }

                if ($openingBatch) {
                    $consumedQty = (float) $openingBatch->initial_quantity - (float) $openingBatch->remaining_quantity;
                    if ($baseOpeningQty < $consumedQty) {
                        $consumedUnits = round($consumedQty / $unitFactor, 2);
                        throw ValidationException::withMessages([
                            'opening_quantity' => "لا يمكن تقليل الكمية الافتتاحية إلى {$openingQty} لأن هناك {$consumedUnits} {$product->unit} ({$consumedQty} قطعة) تم صرفها أو بيعها بالفعل من هذا الرصيد.",
                        ]);
                    }

                    $oldBatchInitialQty = (float) $openingBatch->initial_quantity;
                    $qtyDiff = $baseOpeningQty - $oldBatchInitialQty;
                    $newRemaining = (float) $openingBatch->remaining_quantity + $qtyDiff;
                    $targetWarehouseId = $openingWarehouseId ?: $openingBatch->warehouse_id;

                    // 1. Update batch
                    $openingBatch->initial_quantity = $baseOpeningQty;
                    $openingBatch->remaining_quantity = $newRemaining;
                    $openingBatch->cost_price = $baseOpeningCost;
                    $openingBatch->retail_price = $baseRetailPrice;
                    $openingBatch->wholesale_price = $baseWholesalePrice;
                    $openingBatch->distributor_price = $baseDistributorPrice;
                    $openingBatch->total_initial_cost = round($openingQty * $openingCost, 2);
                    $openingBatch->warehouse_id = $targetWarehouseId;
                    $openingBatch->received_at = $openingDate;
                    $openingBatch->is_exhausted = ($newRemaining <= 0);
                    $openingBatch->notes = 'رصيد افتتاحي (معدل بواسطة مدير النظام): '.$product->name.($unitFactor > 1 ? " ({$openingQty} {$product->unit} = {$baseOpeningQty} قطعة)" : '');
                    $openingBatch->save();

                    // 2. Update stock balance in warehouse(s)
                    $currentBatchWarehouseId = (int) ($oldWarehouseId ?: $openingBatch->warehouse_id);
                    if ($currentBatchWarehouseId && $targetWarehouseId && $currentBatchWarehouseId !== $targetWarehouseId) {
                        $oldBalance = StockBalance::where('product_id', $product->id)
                            ->where('warehouse_id', $currentBatchWarehouseId)
                            ->first();
                        if ($oldBalance) {
                            $oldBalance->decrement('quantity', min((float) $oldBalance->quantity, $oldBatchInitialQty));
                        }

                        $newBalance = StockBalance::firstOrCreate(
                            ['product_id' => $product->id, 'warehouse_id' => $targetWarehouseId],
                            ['quantity' => 0.00, 'reserved_quantity' => 0.00]
                        );
                        $newBalance->increment('quantity', $baseOpeningQty);
                    } elseif ($qtyDiff != 0) {
                        $balance = StockBalance::firstOrCreate(
                            ['product_id' => $product->id, 'warehouse_id' => $targetWarehouseId],
                            ['quantity' => 0.00, 'reserved_quantity' => 0.00]
                        );
                        if ($qtyDiff > 0) {
                            $balance->increment('quantity', $qtyDiff);
                        } else {
                            $balance->decrement('quantity', abs($qtyDiff));
                        }
                    }

                    // 3. Update stock movement
                    if ($openingMovement) {
                        $openingMovement->quantity = $baseOpeningQty;
                        $openingMovement->unit_cost = $baseOpeningCost;
                        $openingMovement->to_warehouse_id = $targetWarehouseId;
                        $openingMovement->notes = 'رصيد افتتاحي (معدل بواسطة مدير النظام): '.$product->name.($unitFactor > 1 ? " ({$openingQty} {$product->unit} = {$baseOpeningQty} قطعة)" : '');
                        $openingMovement->save();
                    }

                    // 4. Recalculate running balances chronologically for this product
                    $inventoryService->recalculateProductRunningBalances($product->id);

                    // 5. Update BatchCostMovement if present
                    BatchCostMovement::where('batch_id', $openingBatch->id)
                        ->where('movement_type', 'opening_in')
                        ->update([
                            'quantity_base' => $baseOpeningQty,
                            'unit_cost_base' => $baseOpeningCost,
                            'total_cost' => round($openingQty * $openingCost, 2),
                            'warehouse_id' => $targetWarehouseId,
                            'running_batch_quantity' => $newRemaining,
                        ]);
                } elseif ($openingQty > 0) {
                    // Previously had no opening batch, add new opening stock via inventory service
                    $inventoryService->addStock(
                        productId: $product->id,
                        warehouseId: $openingWarehouseId,
                        quantity: $baseOpeningQty,
                        costPrice: $baseOpeningCost,
                        notes: 'رصيد افتتاحي (مضاف بواسطة مدير النظام): '.$product->name.($unitFactor > 1 ? " ({$openingQty} {$product->unit} = {$baseOpeningQty} قطعة)" : ''),
                        referenceType: 'opening_balance',
                        referenceId: $product->id,
                        userId: auth()->id(),
                        receivedAt: $openingDate,
                        retailPrice: $baseRetailPrice,
                        wholesalePrice: $baseWholesalePrice,
                        distributorPrice: $baseDistributorPrice,
                        totalInitialCost: round($openingQty * $openingCost, 2),
                        sourceType: 'opening_balance',
                        sourceId: $product->id
                    );
                }

                $validated['opening_quantity'] = $openingQty;
                $validated['opening_cost'] = $openingCost;
                $validated['opening_warehouse_id'] = $openingWarehouseId;
                $validated['opening_balance_date'] = $openingDate?->toDateString();
            } else {
                unset(
                    $validated['opening_quantity'],
                    $validated['opening_cost'],
                    $validated['opening_warehouse_id'],
                    $validated['opening_balance_date']
                );
            }

            $product->update($validated);
        });

        return redirect()->route('products.index')
            ->with('status', 'success')
            ->with('message', 'تم تحديث بيانات المنتج بنجاح.');
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy(Product $product): RedirectResponse
    {
        // 1. Check if product is referenced in commercial invoices or returns
        $hasSales = Schema::hasTable('sales_invoice_items')
            ? DB::table('sales_invoice_items')->where('product_id', $product->id)->exists()
            : false;

        $hasPurchases = Schema::hasTable('purchase_invoice_items')
            ? DB::table('purchase_invoice_items')->where('product_id', $product->id)->exists()
            : false;

        $hasReturns = Schema::hasTable('sales_return_items')
            ? DB::table('sales_return_items')->where('product_id', $product->id)->exists()
            : false;

        // If referenced in commercial transactions, block deletion to preserve financial integrity
        if ($hasSales || $hasPurchases || $hasReturns) {
            return redirect()->route('products.index')
                ->with('status', 'error')
                ->with('message', 'لا يمكن حذف هذا المنتج ('.$product->name.') لوجود فواتير مبيعات أو مشتريات أو مرتجعات مرتبطة به. يمكنك تعطيل الصنف بدلاً من حذفه.');
        }

        try {
            DB::transaction(function () use ($product) {
                // Remove cost movements & stock movements (such as opening balances)
                if (Schema::hasTable('batch_cost_movements')) {
                    DB::table('batch_cost_movements')->where('product_id', $product->id)->delete();
                }
                if (Schema::hasTable('stock_movements')) {
                    DB::table('stock_movements')->where('product_id', $product->id)->delete();
                }
                if (Schema::hasTable('stock_balances')) {
                    DB::table('stock_balances')->where('product_id', $product->id)->delete();
                }
                if (Schema::hasTable('inventory_batches')) {
                    DB::table('inventory_batches')->where('product_id', $product->id)->delete();
                }

                if ($product->image_path && Storage::disk('public')->exists($product->image_path)) {
                    Storage::disk('public')->delete($product->image_path);
                }

                $product->delete();
            });

            return redirect()->route('products.index')
                ->with('status', 'success')
                ->with('message', 'تم حذف المنتج بنجاح.');
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('products.index')
                ->with('status', 'error')
                ->with('message', 'تعذر حذف المنتج: '.$e->getMessage());
        }
    }

    /**
     * Toggle the active status of a product (enable / disable).
     */
    public function toggleStatus(Product $product): RedirectResponse
    {
        $product->update([
            'is_active' => ! $product->is_active,
        ]);

        $statusText = $product->is_active ? 'تنشيط' : 'تعطيل';

        return back()
            ->with('status', 'success')
            ->with('message', "تم {$statusText} الصنف ({$product->name}) بنجاح.");
    }

    /**
     * Generate an internal collision-resistant barcode via AJAX.
     */
    public function generateBarcode(): JsonResponse
    {
        return response()->json([
            'barcode' => Product::generateInternalBarcode(),
        ]);
    }

    /**
     * Real-time barcode lookup endpoint for scanners and search.
     */
    public function lookupBarcode(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('barcode', $request->query('q', '')));
        // Normalize Arabic-Indic digits to Latin
        $arabicIndic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩', '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $standardDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $term = str_replace($arabicIndic, $standardDigits, $term);

        if (blank($term)) {
            return response()->json([
                'found' => false,
                'message' => 'يرجى تمرير كود الباركود المطلوب.',
            ], 422);
        }

        $product = Product::with(['category', 'stockBalances'])
            ->where(function ($query) use ($term) {
                $cleanAlpha = preg_replace('/[^a-zA-Z0-9\x{0600}-\x{06FF}]/u', '', $term);

                $query->where('barcode', $term)
                    ->orWhere('sku', $term)
                    ->orWhere('sku', strtoupper($term))
                    ->orWhere('sku', strtolower($term))
                    ->orWhere('name', $term);

                if (! empty($cleanAlpha) && mb_strlen($cleanAlpha) >= 2) {
                    $query->orWhereRaw("REPLACE(REPLACE(REPLACE(sku, '-', ''), ' ', ''), '_', '') = ?", [$cleanAlpha]);
                }
            })
            ->first();

        if (! $product) {
            return response()->json([
                'found' => false,
                'message' => 'المنتج غير موجود بهذا الباركود أو الكود.',
            ], 404);
        }

        return response()->json([
            'found' => true,
            'product' => [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'category_name' => $product->category?->name,
                'barcode' => (string) ($product->barcode ?? ''),
                'cost_price' => (float) $product->cost_price,
                'wholesale_price' => (float) $product->wholesale_price,
                'retail_price' => (float) $product->retail_price,
                'distributor_price' => (float) ($product->distributor_price ?? $product->wholesale_price),
                'unit' => $product->unit ?? 'قطعة',
                'unit_factor' => (float) ($product->unit_factor ?? 1.0),
                'package_factor' => (float) ($product->package_factor ?? 12.0),
                'pieces_per_package' => (int) ($product->pieces_per_package ?: (int) round($product->package_factor ?? 12)),
                'stocks' => $product->stockBalances->pluck('quantity', 'warehouse_id')->map(fn ($q) => (float) $q)->toArray(),
                'total_stock' => (float) $product->total_stock,
                'formatted_stock' => $product->formatStockBreakdown(),
                'min_stock_alert' => $product->min_stock_alert,
                'image_url' => $product->image_url,
                'is_active' => $product->is_active,
            ],
        ]);
    }

    /**
     * Print thermal barcode label(s) for a single product.
     */
    public function printLabel(Request $request, Product $product, BarcodeGeneratorService $barcodeService): View
    {
        $barcodeSettings = BarcodeSetting::getSettings();
        $quantity = max(1, min(500, (int) $request->input('quantity', 1)));

        $widthMm = (int) $barcodeSettings->label_width_mm;
        $heightMm = (int) $barcodeSettings->label_height_mm;

        $size = $request->input('size');
        if ($size && preg_match('/^(\d+)x(\d+)$/', $size, $matches)) {
            $widthMm = (int) $matches[1];
            $heightMm = (int) $matches[2];
        }

        $barcodeValue = $barcodeService->getBarcodeValue($product);
        $svg = $barcodeService->generateSvg($barcodeValue);
        $labelSvg = $barcodeService->generateCompleteLabelSvg(
            $product->name,
            $barcodeValue,
            $product->sku,
            $widthMm,
            $heightMm,
            $barcodeSettings,
            (float) $product->retail_price,
            (float) $product->wholesale_price
        );

        return view('products.print-label', [
            'title' => 'طباعة ملصق باركود: '.$product->name,
            'items' => [
                [
                    'product' => $product,
                    'barcode_value' => $barcodeValue,
                    'svg' => $svg,
                    'label_svg' => $labelSvg,
                    'quantity' => $quantity,
                ],
            ],
            'totalQuantity' => $quantity,
            'autoPrint' => $request->boolean('autoprint', false),
            'widthMm' => $widthMm,
            'heightMm' => $heightMm,
            'size' => $widthMm.'x'.$heightMm,
            'barcodeSettings' => $barcodeSettings,
        ]);
    }

    /**
     * Print thermal barcode labels for a batch of selected products.
     */
    public function printBatchLabels(Request $request, BarcodeGeneratorService $barcodeService): View|RedirectResponse
    {
        $barcodeSettings = BarcodeSetting::getSettings();
        $itemRequests = [];

        $widthMm = (int) $barcodeSettings->label_width_mm;
        $heightMm = (int) $barcodeSettings->label_height_mm;

        $size = $request->input('size');
        if ($size && preg_match('/^(\d+)x(\d+)$/', $size, $matches)) {
            $widthMm = (int) $matches[1];
            $heightMm = (int) $matches[2];
        }

        if ($request->has('items') && is_array($request->input('items'))) {
            foreach ($request->input('items') as $item) {
                if (isset($item['id']) && is_numeric($item['id'])) {
                    $itemRequests[(int) $item['id']] = max(1, min(500, (int) ($item['qty'] ?? 1)));
                }
            }
        } elseif ($request->has('products') && is_array($request->input('products'))) {
            foreach ($request->input('products') as $id => $qty) {
                if (is_numeric($id)) {
                    $itemRequests[(int) $id] = max(1, min(500, (int) $qty));
                }
            }
        } elseif ($request->has('ids')) {
            $ids = is_array($request->input('ids')) ? $request->input('ids') : explode(',', (string) $request->input('ids'));
            $defaultQty = max(1, min(500, (int) $request->input('quantity', 1)));
            foreach ($ids as $id) {
                if (is_numeric($id)) {
                    $itemRequests[(int) $id] = $defaultQty;
                }
            }
        }

        if (empty($itemRequests)) {
            return redirect()->route('products.index')
                ->with('status', 'error')
                ->with('message', 'لم يتم تحديد أي أصناف لطباعة ملصقات الباركود.');
        }

        $products = Product::whereIn('id', array_keys($itemRequests))->get()->keyBy('id');

        $items = [];
        $totalQuantity = 0;

        foreach ($itemRequests as $id => $qty) {
            if ($product = $products->get($id)) {
                $barcodeValue = $barcodeService->getBarcodeValue($product);
                $items[] = [
                    'product' => $product,
                    'barcode_value' => $barcodeValue,
                    'svg' => $barcodeService->generateSvg($barcodeValue),
                    'label_svg' => $barcodeService->generateCompleteLabelSvg(
                        $product->name,
                        $barcodeValue,
                        $product->sku,
                        $widthMm,
                        $heightMm,
                        $barcodeSettings,
                        (float) $product->retail_price,
                        (float) $product->wholesale_price
                    ),
                    'quantity' => $qty,
                ];
                $totalQuantity += $qty;
            }
        }

        if (empty($items)) {
            return redirect()->route('products.index')
                ->with('status', 'error')
                ->with('message', 'الأصناف المحددة غير موجودة في النظام.');
        }

        return view('products.print-label', [
            'title' => 'طباعة ملصقات باركود مجمعة ('.$totalQuantity.' ملصق)',
            'items' => $items,
            'totalQuantity' => $totalQuantity,
            'autoPrint' => $request->boolean('autoprint', false),
            'widthMm' => $widthMm,
            'heightMm' => $heightMm,
            'size' => $widthMm.'x'.$heightMm,
            'barcodeSettings' => $barcodeSettings,
        ]);
    }

    /**
     * Return SVG barcode preview for modal display via JSON.
     */
    public function barcodeSvg(Product $product, BarcodeGeneratorService $barcodeService): JsonResponse
    {
        $barcodeValue = $barcodeService->getBarcodeValue($product);
        $svg = $barcodeService->generateSvg($barcodeValue);

        return response()->json([
            'product_id' => $product->id,
            'sku' => $product->sku,
            'name' => $product->name,
            'barcode' => $barcodeValue,
            'svg' => $svg,
        ]);
    }

    /**
     * Direct print barcode sticker via the centralized Python printing engine.
     */
    public function directPrint(Request $request, Product $product, PythonPrintEngineService $printService): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => ['nullable', 'integer', 'min:1', 'max:500'],
            'printer' => ['nullable', 'string', 'max:120'],
            'size' => ['nullable', 'string', 'regex:/^\d+x\d+$/'],
        ]);

        $quantity = (int) ($validated['quantity'] ?? 1);
        $printer = $validated['printer'] ?? null;
        $overrides = [];

        if (! empty($validated['size']) && preg_match('/^(\d+)x(\d+)$/', $validated['size'], $matches)) {
            $overrides['width_mm'] = (float) $matches[1];
            $overrides['height_mm'] = (float) $matches[2];
        }

        $result = $printService->printBarcodeLabel($product, $quantity, $printer, $overrides);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Direct print multiple product labels via the centralized Python printing engine.
     */
    public function directPrintBatch(Request $request, PythonPrintEngineService $printService): JsonResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'exists:products,id'],
            'items.*.qty' => ['nullable', 'integer', 'min:1', 'max:500'],
            'printer' => ['nullable', 'string', 'max:120'],
            'size' => ['nullable', 'string', 'regex:/^\d+x\d+$/'],
        ]);

        $printer = $validated['printer'] ?? null;
        $overrides = [];
        if (! empty($validated['size']) && preg_match('/^(\d+)x(\d+)$/', $validated['size'], $matches)) {
            $overrides['width_mm'] = (float) $matches[1];
            $overrides['height_mm'] = (float) $matches[2];
        }

        $batchItems = [];
        $totalRequestedQty = 0;

        foreach ($validated['items'] as $item) {
            $product = Product::find($item['id']);
            if (! $product) {
                continue;
            }

            $qty = max(1, min(500, (int) ($item['qty'] ?? 1)));
            $batchItems[] = [
                'product' => $product,
                'qty' => $qty,
            ];
            $totalRequestedQty += $qty;
        }

        if (empty($batchItems)) {
            return response()->json([
                'success' => false,
                'status' => 'EMPTY_BATCH',
                'message' => 'لم يتم العثور على أي أصناف صالحة للطباعة.',
            ], 422);
        }

        $res = $printService->printBarcodeLabelBatch($batchItems, $printer, $overrides);

        $isSuccess = (bool) ($res['success'] ?? false);
        $totalCopies = (int) ($res['total_labels'] ?? $totalRequestedQty);
        $printedCount = (int) ($res['total_products'] ?? count($batchItems));

        return response()->json([
            'success' => $isSuccess,
            'status' => $res['status'] ?? ($isSuccess ? 'SPOOLED' : 'FAILED'),
            'message' => $isSuccess
                ? "تم إرسال {$totalCopies} ملصق لعدد {$printedCount} صنف للطباعة بنجاح."
                : ($res['message'] ?? 'فشل إرسال أمر الطباعة المجمعة.'),
            'total_copies' => $totalCopies,
            'printed_products_count' => $printedCount,
            'printer' => $res['printer'] ?? $printer,
            'items_summary' => $res['items_summary'] ?? [],
        ], $isSuccess ? 200 : 422);
    }

    /**
     * Display a dedicated, template-driven printable view of the product stock movement ledger.
     * ZERO data mutation, strictly read-only, respects filters, and supports A4, A5, and Thermal 80mm.
     */
    public function printLedger(Request $request, Product $product, PrintTemplateService $templateService): View
    {
        $product->loadMissing(['category', 'stockBalances.warehouse']);

        $query = StockMovement::where('product_id', $product->id)
            ->with(['fromWarehouse', 'toWarehouse', 'batch', 'user', 'customer', 'supplier']);

        $selectedWarehouse = null;
        if ($request->filled('warehouse_id')) {
            $wid = (int) $request->input('warehouse_id');
            $selectedWarehouse = Warehouse::find($wid);
            $query->where(function ($q) use ($wid) {
                $q->where('from_warehouse_id', $wid)
                    ->orWhere('to_warehouse_id', $wid);
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        // Print order: allow ascending (chronological) or descending
        $sortOrder = $request->input('sort', 'asc');
        if ($sortOrder === 'desc') {
            $query->orderByDesc('id');
        } else {
            $query->orderBy('created_at', 'asc')->orderBy('id', 'asc');
        }

        // Retrieve unpaginated movements up to safe report limit (1000 items)
        $movements = $query->limit(1000)->get();

        // Paper format resolution
        $format = (string) $request->query('format', PrintTemplate::FORMAT_A4);
        if (! in_array($format, PrintTemplateService::SUPPORTED_FORMATS, true)) {
            $format = PrintTemplate::FORMAT_A4;
        }

        $template = $templateService->getEffectiveTemplate(PrintTemplate::DOC_PURCHASE_INVOICE, $format);
        $companySetting = CompanySetting::getSettings();
        $autoPrint = $request->boolean('autoprint', false);

        // Movement totals
        $totalIn = (float) $movements->where('type', StockMovement::TYPE_IN)->sum('quantity');
        $totalOut = (float) $movements->where('type', StockMovement::TYPE_OUT)->sum('quantity');
        $totalTransfer = (float) $movements->where('type', StockMovement::TYPE_TRANSFER)->sum('quantity');
        $totalAdjustment = (float) $movements->where('type', StockMovement::TYPE_ADJUSTMENT)->sum('quantity');

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

        return view('products.ledger-print', [
            'title' => 'طباعة كشف حركة الصنف: '.$product->name,
            'product' => $product,
            'movements' => $movements,
            'movementsCount' => $movements->count(),
            'totalIn' => $totalIn,
            'totalOut' => $totalOut,
            'totalTransfer' => $totalTransfer,
            'totalAdjustment' => $totalAdjustment,
            'selectedWarehouse' => $selectedWarehouse,
            'currentWarehouseId' => $request->input('warehouse_id'),
            'currentType' => $request->input('type'),
            'currentDateFrom' => $request->input('date_from'),
            'currentDateTo' => $request->input('date_to'),
            'template' => $template,
            'settings' => $template->settings,
            'format' => $format,
            'colorClass' => $colorClass,
            'company' => $companySetting,
            'autoPrint' => $autoPrint,
            'sortOrder' => $sortOrder,
        ]);
    }

    /**
     * Display the granular stock movement ledger for a specific product.
     */
    public function ledger(Product $product, Request $request): View
    {
        $product->load(['category', 'stockBalances.warehouse']);

        $query = StockMovement::where('product_id', $product->id)
            ->with(['fromWarehouse', 'toWarehouse', 'batch', 'user', 'customer', 'supplier']);

        if ($request->filled('warehouse_id')) {
            $wid = (int) $request->input('warehouse_id');
            $query->where(function ($q) use ($wid) {
                $q->where('from_warehouse_id', $wid)
                    ->orWhere('to_warehouse_id', $wid);
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $movements = $query->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $warehouses = Warehouse::active()->orderBy('name')->get();

        return view('products.ledger', [
            'title' => 'كشف حركة الصنف: '.$product->name,
            'moduleName' => 'المخزون والمنتجات',
            'product' => $product,
            'movements' => $movements,
            'warehouses' => $warehouses,
            'currentWarehouseId' => $request->input('warehouse_id'),
            'currentType' => $request->input('type'),
            'currentDateFrom' => $request->input('date_from'),
            'currentDateTo' => $request->input('date_to'),
        ]);
    }

    /**
     * Direct print product stock movement ledger via the centralized Python printing engine.
     */
    public function directPrintLedger(Request $request, Product $product, PythonPrintEngineService $printService): JsonResponse
    {
        $validated = $request->validate([
            'printer' => ['nullable', 'string', 'max:120'],
            'format' => ['nullable', 'string', 'in:a4,a5'],
        ]);

        $query = StockMovement::where('product_id', $product->id)
            ->with(['fromWarehouse', 'toWarehouse', 'user', 'customer', 'supplier']);

        if ($request->filled('warehouse_id')) {
            $wid = (int) $request->input('warehouse_id');
            $query->where(function ($q) use ($wid) {
                $q->where('from_warehouse_id', $wid)
                    ->orWhere('to_warehouse_id', $wid);
            });
        }
        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $movements = $query->orderBy('created_at', 'asc')->orderBy('id', 'asc')->limit(1000)->get();
        $format = $validated['format'] ?? $request->query('format', 'a4');
        $printer = $validated['printer'] ?? null;

        $result = $printService->printProductLedger($product, $movements, $format, $printer);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Store a product quickly via AJAX modal from purchases/POS with piece & dozen support.
     */
    public function quickStore(Request $request): JsonResponse
    {
        // Auto-assign category if not provided or empty
        if (empty($request->category_id)) {
            $cat = Category::active()->first() ?? Category::firstOrCreate(['name' => 'عام'], ['slug' => 'general', 'is_active' => true]);
            $request->merge(['category_id' => $cat->id]);
        }

        // Auto-assign unit if empty
        if (empty($request->unit)) {
            $request->merge(['unit' => 'قطعة']);
        }

        $pkg = (float) ($request->pieces_per_package ?: 12);
        if ($pkg <= 0) {
            $pkg = 12.0;
        }

        // Two-way Fallbacks: If dozen prices were entered instead of piece prices
        if ($request->filled('cost_price_dozen') && (! $request->filled('cost_price') || (float) $request->cost_price <= 0)) {
            $request->merge(['cost_price' => round((float) $request->cost_price_dozen / $pkg, 2)]);
        }
        if ($request->filled('retail_price_dozen') && (! $request->filled('retail_price') || (float) $request->retail_price <= 0)) {
            $request->merge(['retail_price' => round((float) $request->retail_price_dozen / $pkg, 2)]);
        }
        if ($request->filled('wholesale_price_dozen') && (! $request->filled('wholesale_price') || (float) $request->wholesale_price <= 0)) {
            $request->merge(['wholesale_price' => round((float) $request->wholesale_price_dozen / $pkg, 2)]);
        }
        if ($request->filled('distributor_price_dozen') && (! $request->filled('distributor_price') || (float) $request->distributor_price <= 0)) {
            $request->merge(['distributor_price' => round((float) $request->distributor_price_dozen / $pkg, 2)]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'sku' => ['nullable', 'string', 'max:80', 'unique:products,sku'],
            'barcode' => ['nullable', 'string', 'max:100', 'unique:products,barcode'],
            'unit' => ['required', 'string', 'max:50'],
            'pieces_per_package' => ['nullable', 'integer', 'min:1'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'retail_price' => ['nullable', 'numeric', 'min:0'],
            'wholesale_price' => ['nullable', 'numeric', 'min:0'],
            'distributor_price' => ['nullable', 'numeric', 'min:0'],
            'min_stock_alert' => ['nullable', 'integer', 'min:0'],
        ], [
            'name.required' => 'اسم المنتج مطلوب ولا يمكن تركه فارغاً.',
            'category_id.required' => 'يرجى اختيار تصنيف المنتج.',
            'category_id.exists' => 'التصنيف المختار غير موجود في النظام.',
            'sku.unique' => 'كود الصنف (SKU) مستخدم بالفعل في صنف آخر.',
            'barcode.unique' => 'الباركود المدخل مستخدم بالفعل في صنف آخر.',
            'cost_price.required' => 'سعر شراء/تكلفة الصنف مطلوب.',
            'cost_price.min' => 'سعر التكلفة لا يمكن أن يكون سالباً.',
            'retail_price.min' => 'سعر التجزئة لا يمكن أن يكون سالباً.',
            'wholesale_price.min' => 'سعر الجملة لا يمكن أن يكون سالباً.',
            'distributor_price.min' => 'سعر الموزع لا يمكن أن يكون سالباً.',
            'unit.required' => 'وحدة القياس مطلوبة.',
        ]);

        if (empty($validated['sku'])) {
            $nextId = (Product::max('id') ?? 0) + 1;
            do {
                $candidateSku = 'KY-'.date('y').'-'.str_pad((string) $nextId, 4, '0', STR_PAD_LEFT);
                $nextId++;
            } while (Product::where('sku', $candidateSku)->exists());
            $validated['sku'] = $candidateSku;
        }

        $cost = (float) $validated['cost_price'];
        if (! isset($validated['retail_price']) || $validated['retail_price'] === '' || $validated['retail_price'] === null) {
            $validated['retail_price'] = round($cost * 1.25, 2);
        }
        if (! isset($validated['wholesale_price']) || $validated['wholesale_price'] === '' || $validated['wholesale_price'] === null) {
            $validated['wholesale_price'] = round($cost * 1.15, 2);
        }
        if (! isset($validated['distributor_price']) || $validated['distributor_price'] === '' || $validated['distributor_price'] === null) {
            $validated['distributor_price'] = round($cost * 1.10, 2);
        }

        $validated['pieces_per_package'] = (int) ($validated['pieces_per_package'] ?? 12);
        $validated['min_stock_alert'] = (int) ($validated['min_stock_alert'] ?? 5);
        $validated['is_active'] = true;

        if (empty($validated['barcode'])) {
            $validated['barcode'] = null;
        }

        $product = Product::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الصنف الجديد بنجاح.',
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'barcode' => (string) ($product->barcode ?? ''),
                'cost_price' => (float) $product->cost_price,
                'retail_price' => (float) $product->retail_price,
                'wholesale_price' => (float) $product->wholesale_price,
                'distributor_price' => (float) $product->distributor_price,
                'unit' => $product->unit ?? 'قطعة',
                'unit_factor' => 1.0,
                'package_factor' => (float) ($product->package_factor ?? 12.0),
                'pieces_per_package' => (int) ($product->pieces_per_package ?: 12),
                'stocks' => [],
                'total_stock' => 0.0,
                'formatted_stock' => '0 '.($product->unit ?? 'قطعة'),
            ],
        ]);
    }
}
