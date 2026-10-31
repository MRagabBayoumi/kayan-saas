<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CompanySetting;
use App\Models\PackingList;
use App\Models\Product;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PackingListController extends Controller
{
    /**
     * Display the dedicated Packing List builder interface.
     */
    public function index(Request $request): View
    {
        $categories = Category::active()->orderBy('name')->get();
        $warehouses = Warehouse::active()->orderBy('name')->get();
        $units = Unit::active()->orderBy('name')->get();
        $company = CompanySetting::getSettings();

        $loadedPackingList = null;
        $loadedData = null;
        if ($request->filled('load_id')) {
            $loadedPackingList = PackingList::with('items')->find($request->query('load_id'));
            if ($loadedPackingList) {
                $loadedData = [
                    'id' => $loadedPackingList->id,
                    'doc_number' => $loadedPackingList->packing_number,
                    'doc_date' => $loadedPackingList->packing_date ? $loadedPackingList->packing_date->format('Y-m-d') : date('Y-m-d'),
                    'customer_name' => $loadedPackingList->customer_name,
                    'warehouse_name' => $loadedPackingList->warehouse_name,
                    'price_type' => $loadedPackingList->price_type,
                    'notes' => $loadedPackingList->notes,
                    'show_quantity' => (bool) $loadedPackingList->show_quantity,
                    'show_price' => (bool) $loadedPackingList->show_price,
                    'show_packages' => (bool) $loadedPackingList->show_packages,
                    'items' => $loadedPackingList->items->map(function ($it) {
                        return [
                            'id' => $it->product_id,
                            'sku' => $it->sku,
                            'barcode' => $it->barcode,
                            'name' => $it->product_name,
                            'category_name' => $it->category_name,
                            'unit' => $it->unit,
                            'qty' => (float) $it->quantity,
                            'packages_count' => (int) $it->packages_count,
                            'price' => (float) $it->unit_price,
                            'total' => (float) $it->total_amount,
                            'notes' => $it->notes,
                        ];
                    })->values()->all(),
                ];
            }
        }

        $docNumber = $loadedPackingList
            ? $loadedPackingList->packing_number
            : PackingList::generateNextNumber();

        return view('packing-list.index', [
            'categories' => $categories,
            'warehouses' => $warehouses,
            'units' => $units,
            'company' => $company,
            'docNumber' => $docNumber,
            'loadedPackingList' => $loadedPackingList,
            'loadedData' => $loadedData,
        ]);
    }

    /**
     * Display the searchable archive and history of all saved packing lists.
     */
    public function archive(Request $request): View
    {
        $query = PackingList::withCount('items')->with(['creator']);

        if ($request->filled('search')) {
            $query->search($request->query('search'));
        }

        if ($request->filled('from_date')) {
            $query->whereDate('packing_date', '>=', $request->query('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('packing_date', '<=', $request->query('to_date'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $packingLists = $query->latest('id')->paginate(15)->withQueryString();

        $metrics = [
            'total_lists' => PackingList::count(),
            'total_quantity' => (float) PackingList::sum('total_quantity'),
            'total_packages' => (int) PackingList::sum('total_packages'),
            'total_amount' => (float) PackingList::sum('total_amount'),
        ];

        return view('packing-list.archive', [
            'packingLists' => $packingLists,
            'metrics' => $metrics,
            'search' => $request->query('search', ''),
            'fromDate' => $request->query('from_date', ''),
            'toDate' => $request->query('to_date', ''),
            'status' => $request->query('status', ''),
        ]);
    }

    /**
     * Save or update a packing list persistently into the database archive.
     */
    public function store(Request $request): JsonResponse
    {
        $payload = $this->extractPayload($request);

        if (empty($payload['items'])) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حفظ قائمة تعبئة فارغة. يرجى إضافة صنف واحد على الأقل.',
            ], 422);
        }

        $listId = $request->input('packing_list_id');

        $showQty = (bool) ($payload['show_quantity'] ?? true);
        $showPrice = (bool) ($payload['show_price'] ?? true);
        $showPackages = (bool) ($payload['show_packages'] ?? true);
        $showLineTotal = $showQty && $showPrice;

        $totalQty = 0;
        $totalPackages = 0;
        $totalAmount = 0;

        foreach ($payload['items'] as $item) {
            if ($showQty) {
                $totalQty += (float) ($item['qty'] ?? 0);
            }
            if ($showPackages) {
                $totalPackages += (int) ($item['packages_count'] ?? 0);
            }
            if ($showLineTotal) {
                $totalAmount += (float) (($item['qty'] ?? 0) * ($item['price'] ?? 0));
            }
        }

        try {
            $packingList = DB::transaction(function () use ($payload, $listId, $totalQty, $totalPackages, $totalAmount, $showQty, $showPrice, $showPackages, $request) {
                $docNumber = $payload['doc_number'];

                if ($listId) {
                    $packingList = PackingList::findOrFail($listId);
                } else {
                    // Check if document number exists, generate unique one if so
                    if (PackingList::where('packing_number', $docNumber)->exists()) {
                        $docNumber = PackingList::generateNextNumber();
                    }
                    $packingList = new PackingList;
                    $packingList->created_by = $request->user()?->id;
                }

                $packingList->packing_number = $docNumber;
                $packingList->packing_date = $payload['doc_date'];
                $packingList->customer_name = $payload['customer_name'] ?: 'عميل عام';
                $packingList->warehouse_name = $payload['warehouse_name'] ?: null;
                $packingList->price_type = $payload['price_type'];
                $packingList->show_quantity = $showQty;
                $packingList->show_price = $showPrice;
                $packingList->show_packages = $showPackages;
                $packingList->total_quantity = $totalQty;
                $packingList->total_packages = $totalPackages;
                $packingList->total_amount = $totalAmount;
                $packingList->notes = $payload['notes'] ?: null;
                $packingList->status = $request->input('status', 'draft');
                $packingList->save();

                // Re-create items
                $packingList->items()->delete();

                $sort = 1;
                foreach ($payload['items'] as $it) {
                    $packingList->items()->create([
                        'product_id' => $it['id'] ?: null,
                        'sku' => $it['sku'] ?: null,
                        'barcode' => $it['barcode'] ?: null,
                        'product_name' => $it['name'],
                        'category_name' => $it['category_name'] ?: null,
                        'unit' => $it['unit'] ?: 'قطعة',
                        'quantity' => (float) ($it['qty'] ?? 1),
                        'packages_count' => (int) ($it['packages_count'] ?? 0),
                        'unit_price' => (float) ($it['price'] ?? 0),
                        'total_amount' => (float) ($it['total'] ?? 0),
                        'notes' => $it['notes'] ?: null,
                        'sort_order' => $sort++,
                    ]);
                }

                return $packingList;
            });

            return response()->json([
                'success' => true,
                'message' => 'تم حفظ قائمة التعبئة بنجاح في السجل برقم: '.$packingList->packing_number,
                'id' => $packingList->id,
                'packing_number' => $packingList->packing_number,
                'archive_url' => route('packing-list.archive'),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حفظ القائمة في قاعدة البيانات: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a saved packing list from the archive.
     */
    public function destroy(PackingList $packingList): RedirectResponse
    {
        $num = $packingList->packing_number;
        $packingList->delete();

        return redirect()->route('packing-list.archive')
            ->with('success', "تم حذف قائمة التعبئة رقم {$num} من السجل بنجاح.");
    }

    /**
     * Print directly from a saved database packing list model.
     */
    public function printModel(Request $request, PackingList $packingList): View
    {
        $packingList->load('items');
        $format = $request->query('format', 'a4');
        $company = CompanySetting::getSettings();

        $items = $packingList->items->map(function ($it) {
            return [
                'id' => $it->product_id,
                'sku' => $it->sku,
                'barcode' => $it->barcode,
                'name' => $it->product_name,
                'category_name' => $it->category_name,
                'unit' => $it->unit,
                'qty' => (float) $it->quantity,
                'packages_count' => (int) $it->packages_count,
                'price' => (float) $it->unit_price,
                'total' => (float) $it->total_amount,
                'notes' => $it->notes,
            ];
        })->toArray();

        $showQty = (bool) $packingList->show_quantity;
        $showPrice = (bool) $packingList->show_price;
        $showPackages = (bool) $packingList->show_packages;
        $showLineTotal = $showQty && $showPrice;

        return view('packing-list.print', [
            'title' => 'بيان تعبئة وتجهيز بضاعة - '.$packingList->packing_number,
            'docNumber' => $packingList->packing_number,
            'docDate' => $packingList->packing_date->format('Y-m-d'),
            'customerName' => $packingList->customer_name,
            'warehouseName' => $packingList->warehouse_name,
            'priceType' => $packingList->price_type,
            'notes' => $packingList->notes,
            'items' => $items,
            'showQuantity' => $showQty,
            'showPrice' => $showPrice,
            'showPackages' => $showPackages,
            'showLineTotal' => $showLineTotal,
            'format' => $format,
            'company' => $company,
            'totalQty' => $showQty ? (float) $packingList->total_quantity : 0,
            'totalPackages' => $showPackages ? (int) $packingList->total_packages : 0,
            'totalAmount' => $showLineTotal ? (float) $packingList->total_amount : 0,
            'autoPrint' => $request->boolean('auto', false),
        ]);
    }

    /**
     * Direct Excel export from a saved database packing list model.
     */
    public function exportExcelModel(PackingList $packingList): Response
    {
        $packingList->load('items');
        $company = CompanySetting::getSettings();

        $docNumber = $packingList->packing_number;
        $docDate = $packingList->packing_date->format('Y-m-d');
        $customerName = $packingList->customer_name;
        $warehouseName = $packingList->warehouse_name;
        $notes = $packingList->notes;

        $showQty = (bool) $packingList->show_quantity;
        $showPrice = (bool) $packingList->show_price;
        $showPackages = (bool) $packingList->show_packages;
        $showLineTotal = $showQty && $showPrice;

        $html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
        $html .= '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
        $html .= '<style>';
        $html .= 'body { font-family: Tahoma, Arial, sans-serif; direction: rtl; text-align: right; }';
        $html .= '.title { font-size: 18px; font-weight: bold; color: #1e293b; text-align: center; margin-bottom: 5px; }';
        $html .= '.subtitle { font-size: 13px; color: #64748b; text-align: center; margin-bottom: 15px; }';
        $html .= '.meta-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; font-size: 12px; }';
        $html .= '.meta-table td { padding: 6px; border: 1px solid #cbd5e1; background: #f8fafc; }';
        $html .= '.meta-label { font-weight: bold; color: #334155; width: 18%; }';
        $html .= '.meta-val { color: #0f172a; width: 32%; }';
        $html .= '.items-table { width: 100%; border-collapse: collapse; font-size: 11px; }';
        $html .= '.items-table th { background: #714B67; color: #ffffff; padding: 8px 6px; border: 1px solid #5d3b54; font-weight: bold; text-align: center; }';
        $html .= '.items-table td { padding: 6px; border: 1px solid #cbd5e1; text-align: center; }';
        $html .= '.items-table tr:nth-child(even) td { background: #f8fafc; }';
        $html .= '.text-right { text-align: right !important; }';
        $html .= '.text-center { text-align: center !important; }';
        $html .= '.total-row td { background: #f4ebf2 !important; font-weight: bold; font-size: 12px; border-top: 2px solid #714B67; }';
        $html .= '</style></head>';
        $html .= '<body dir="rtl">';

        $html .= '<div class="title">'.htmlspecialchars($company->company_name ?: 'شركة كيان التجارية').'</div>';
        $html .= '<div class="subtitle">بيان وقائمة التعبئة والتجهيز (Packing & Dispatch List)</div>';

        // Meta Box
        $html .= '<table class="meta-table">';
        $html .= '<tr>';
        $html .= '<td class="meta-label">رقم قائمة التعبئة:</td><td class="meta-val">'.htmlspecialchars($docNumber).'</td>';
        $html .= '<td class="meta-label">تاريخ الإصدار:</td><td class="meta-val">'.htmlspecialchars($docDate).'</td>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '<td class="meta-label">العميل / المستلم:</td><td class="meta-val">'.htmlspecialchars($customerName ?: 'عميل عام').'</td>';
        $html .= '<td class="meta-label">المستودع المصدّر:</td><td class="meta-val">'.htmlspecialchars($warehouseName ?: 'المخزن الرئيسي').'</td>';
        $html .= '</tr>';
        if (! empty($notes)) {
            $html .= '<tr>';
            $html .= '<td class="meta-label">ملاحظات الشحن والتجهيز:</td><td colspan="3" class="meta-val">'.htmlspecialchars($notes).'</td>';
            $html .= '</tr>';
        }
        $html .= '</table>';

        // Items Table
        $html .= '<table class="items-table">';
        $html .= '<thead><tr>';
        $html .= '<th style="width: 4%;">#</th>';
        $html .= '<th style="width: 15%;">كود الصنف (SKU)</th>';
        $html .= '<th style="width: 15%;">الباركود</th>';
        $html .= '<th style="width: 30%;" class="text-right">اسم المنتج والتصنيف</th>';
        $html .= '<th style="width: 8%;">الوحدة</th>';
        if ($showQty) {
            $html .= '<th style="width: 10%;">الكمية</th>';
        }
        if ($showPackages) {
            $html .= '<th style="width: 10%;">الطرود/الكراتين</th>';
        }
        if ($showPrice) {
            $html .= '<th style="width: 10%;">سعر الوحدة</th>';
        }
        if ($showLineTotal) {
            $html .= '<th style="width: 10%;">الإجمالي</th>';
        }
        $html .= '<th>ملاحظات التعبئة</th>';
        $html .= '</tr></thead><tbody>';

        $idx = 1;
        foreach ($packingList->items as $item) {
            $qty = (float) $item->quantity;
            $packages = (int) $item->packages_count;
            $price = (float) $item->unit_price;
            $lineTotal = (float) $item->total_amount;

            $html .= '<tr>';
            $html .= '<td>'.$idx++.'</td>';
            $html .= '<td>'.htmlspecialchars((string) $item->sku).'</td>';
            $html .= '<td>'.htmlspecialchars((string) $item->barcode).'</td>';
            $html .= '<td class="text-right"><strong>'.htmlspecialchars((string) $item->product_name).'</strong></td>';
            $html .= '<td>'.htmlspecialchars((string) $item->unit).'</td>';
            if ($showQty) {
                $html .= '<td><strong>'.number_format($qty, 2).'</strong></td>';
            }
            if ($showPackages) {
                $html .= '<td>'.($packages > 0 ? number_format($packages, 0) : '-').'</td>';
            }
            if ($showPrice) {
                $html .= '<td>'.number_format($price, 2).' ج.م</td>';
            }
            if ($showLineTotal) {
                $html .= '<td><strong>'.number_format($lineTotal, 2).' ج.م</strong></td>';
            }
            $html .= '<td>'.htmlspecialchars((string) $item->notes).'</td>';
            $html .= '</tr>';
        }

        // Totals Row
        $html .= '<tr class="total-row">';
        $html .= '<td colspan="5" class="text-right"><strong>الإجمالي الكلي ('.$packingList->items->count().' أصناف)</strong></td>';
        if ($showQty) {
            $html .= '<td><strong>'.number_format((float) $packingList->total_quantity, 2).'</strong></td>';
        }
        if ($showPackages) {
            $html .= '<td><strong>'.($packingList->total_packages > 0 ? number_format($packingList->total_packages, 0) : '-').'</strong></td>';
        }
        if ($showPrice) {
            $html .= '<td>-</td>';
        }
        if ($showLineTotal) {
            $html .= '<td><strong>'.number_format((float) $packingList->total_amount, 2).' ج.م</strong></td>';
        }
        $html .= '<td>-</td>';
        $html .= '</tr>';

        $html .= '</tbody></table>';
        $html .= '<div style="margin-top: 25px; font-size: 11px; color: #64748b; text-align: center;">تم التصدير آلياً عبر نظام كيان ERP بتاريخ '.date('Y-m-d H:i').'</div>';
        $html .= '</body></html>';

        $cleanFilename = 'packing_list_'.preg_replace('/[^a-zA-Z0-9_\-]/', '_', $docNumber).'.xls';

        return response($html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$cleanFilename}\"",
        ]);
    }

    /**
     * Live search products by name, SKU, or barcode for instant addition.
     */
    public function searchProducts(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', $request->query('barcode', '')));

        // Normalize Arabic-Indic digits to Latin
        $arabicIndic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩', '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $standardDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $term = str_replace($arabicIndic, $standardDigits, $term);

        $query = Product::with(['category', 'stockBalances'])->where('is_active', true);

        if (blank($term)) {
            $products = $query->latest('id')->take(20)->get();
        } else {
            $normalizedTerm = str_replace(
                ['أ', 'إ', 'آ', 'ة', 'ى'],
                ['ا', 'ا', 'ا', 'ه', 'ي'],
                $term
            );

            $products = $query->where(function ($q) use ($term, $normalizedTerm) {
                $cleanAlpha = preg_replace('/[^a-zA-Z0-9\x{0600}-\x{06FF}]/u', '', $term);

                $q->where('barcode', $term)
                    ->orWhere('barcode', 'like', "%{$term}%")
                    ->orWhere('sku', $term)
                    ->orWhere('sku', 'like', "%{$term}%")
                    ->orWhere('name', 'like', "%{$term}%")
                    ->orWhere('name', 'like', "%{$normalizedTerm}%")
                    ->orWhereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(name, 'أ', 'ا'), 'إ', 'ا'), 'آ', 'ا'), 'ة', 'ه'), 'ى', 'ي') LIKE ?", ["%{$normalizedTerm}%"]);

                if (! empty($cleanAlpha) && mb_strlen($cleanAlpha) >= 2) {
                    $q->orWhereRaw("REPLACE(REPLACE(REPLACE(sku, '-', ''), ' ', ''), '_', '') LIKE ?", ["%{$cleanAlpha}%"]);
                }
            })->take(25)->get();
        }

        $results = $products->map(function (Product $product) {
            return [
                'id' => $product->id,
                'sku' => (string) ($product->sku ?? ''),
                'barcode' => (string) ($product->barcode ?? ''),
                'name' => $product->name,
                'category_name' => $product->category?->name ?: 'عام',
                'unit' => $product->unit ?: 'قطعة',
                'unit_factor' => (float) ($product->unit_factor ?? 1.0),
                'package_factor' => (float) ($product->package_factor ?? 12.0),
                'pieces_per_package' => (int) ($product->pieces_per_package ?: (int) round($product->package_factor ?? 12)),
                'cost_price' => (float) $product->cost_price,
                'wholesale_price' => (float) $product->wholesale_price,
                'retail_price' => (float) $product->retail_price,
                'distributor_price' => (float) ($product->distributor_price ?? $product->wholesale_price),
                'total_stock' => (float) $product->total_stock,
                'formatted_stock' => $product->formatStockBreakdown(),
                'stocks' => $product->stockBalances->pluck('quantity', 'warehouse_id')->map(fn ($q) => (float) $q)->toArray(),
            ];
        });

        return response()->json([
            'success' => true,
            'count' => $results->count(),
            'products' => $results,
        ]);
    }

    /**
     * Export on-the-fly packing list to Microsoft Excel format (.xls with Arabic styling).
     */
    public function exportExcel(Request $request): Response
    {
        $payload = $this->extractPayload($request);
        $company = CompanySetting::getSettings();

        $docNumber = $payload['doc_number'];
        $docDate = $payload['doc_date'];
        $customerName = $payload['customer_name'];
        $warehouseName = $payload['warehouse_name'];
        $notes = $payload['notes'];
        $items = $payload['items'];

        $showQty = (bool) $payload['show_quantity'];
        $showPrice = (bool) $payload['show_price'];
        $showPackages = (bool) $payload['show_packages'];
        $showLineTotal = $showQty && $showPrice;

        $totalQty = 0;
        $totalPackages = 0;
        $totalAmount = 0;

        foreach ($items as $item) {
            if ($showQty) {
                $totalQty += (float) ($item['qty'] ?? 0);
            }
            if ($showPackages) {
                $totalPackages += (float) ($item['packages_count'] ?? 0);
            }
            if ($showLineTotal) {
                $totalAmount += (float) (($item['qty'] ?? 0) * ($item['price'] ?? 0));
            }
        }

        $html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
        $html .= '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
        $html .= '<style>';
        $html .= 'body { font-family: Tahoma, Arial, sans-serif; direction: rtl; text-align: right; }';
        $html .= '.title { font-size: 18px; font-weight: bold; color: #1e293b; text-align: center; margin-bottom: 5px; }';
        $html .= '.subtitle { font-size: 13px; color: #64748b; text-align: center; margin-bottom: 15px; }';
        $html .= '.meta-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; font-size: 12px; }';
        $html .= '.meta-table td { padding: 6px; border: 1px solid #cbd5e1; background: #f8fafc; }';
        $html .= '.meta-label { font-weight: bold; color: #334155; width: 18%; }';
        $html .= '.meta-val { color: #0f172a; width: 32%; }';
        $html .= '.items-table { width: 100%; border-collapse: collapse; font-size: 11px; }';
        $html .= '.items-table th { background: #714B67; color: #ffffff; padding: 8px 6px; border: 1px solid #5d3b54; font-weight: bold; text-align: center; }';
        $html .= '.items-table td { padding: 6px; border: 1px solid #cbd5e1; text-align: center; }';
        $html .= '.items-table tr:nth-child(even) td { background: #f8fafc; }';
        $html .= '.text-right { text-align: right !important; }';
        $html .= '.text-center { text-align: center !important; }';
        $html .= '.total-row td { background: #f4ebf2 !important; font-weight: bold; font-size: 12px; border-top: 2px solid #714B67; }';
        $html .= '</style></head>';
        $html .= '<body dir="rtl">';

        $html .= '<div class="title">'.htmlspecialchars($company->company_name ?: 'شركة كيان التجارية').'</div>';
        $html .= '<div class="subtitle">بيان وقائمة التعبئة والتجهيز (Packing & Dispatch List)</div>';

        // Meta Box
        $html .= '<table class="meta-table">';
        $html .= '<tr>';
        $html .= '<td class="meta-label">رقم قائمة التعبئة:</td><td class="meta-val">'.htmlspecialchars($docNumber).'</td>';
        $html .= '<td class="meta-label">تاريخ الإصدار:</td><td class="meta-val">'.htmlspecialchars($docDate).'</td>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '<td class="meta-label">العميل / المستلم:</td><td class="meta-val">'.htmlspecialchars($customerName ?: 'عميل عام / بدون تحديد').'</td>';
        $html .= '<td class="meta-label">المستودع المصدّر:</td><td class="meta-val">'.htmlspecialchars($warehouseName ?: 'المخزن الرئيسي').'</td>';
        $html .= '</tr>';
        if (! empty($notes)) {
            $html .= '<tr>';
            $html .= '<td class="meta-label">ملاحظات الشحن والتجهيز:</td><td colspan="3" class="meta-val">'.htmlspecialchars($notes).'</td>';
            $html .= '</tr>';
        }
        $html .= '</table>';

        // Items Table
        $html .= '<table class="items-table">';
        $html .= '<thead><tr>';
        $html .= '<th style="width: 4%;">#</th>';
        $html .= '<th style="width: 15%;">كود الصنف (SKU)</th>';
        $html .= '<th style="width: 15%;">الباركود</th>';
        $html .= '<th style="width: 30%;" class="text-right">اسم المنتج والتصنيف</th>';
        $html .= '<th style="width: 8%;">الوحدة</th>';
        if ($showQty) {
            $html .= '<th style="width: 10%;">الكمية</th>';
        }
        if ($showPackages) {
            $html .= '<th style="width: 10%;">الطرود/الكراتين</th>';
        }
        if ($showPrice) {
            $html .= '<th style="width: 10%;">سعر الوحدة</th>';
        }
        if ($showLineTotal) {
            $html .= '<th style="width: 10%;">الإجمالي</th>';
        }
        $html .= '<th>ملاحظات التعبئة</th>';
        $html .= '</tr></thead><tbody>';

        $idx = 1;
        foreach ($items as $item) {
            $qty = (float) ($item['qty'] ?? 0);
            $packages = (float) ($item['packages_count'] ?? 0);
            $price = (float) ($item['price'] ?? 0);
            $lineTotal = (float) ($item['total'] ?? ($qty * $price));

            $html .= '<tr>';
            $html .= '<td>'.$idx++.'</td>';
            $html .= '<td>'.htmlspecialchars((string) ($item['sku'] ?? '')).'</td>';
            $html .= '<td>'.htmlspecialchars((string) ($item['barcode'] ?? '')).'</td>';
            $html .= '<td class="text-right"><strong>'.htmlspecialchars((string) ($item['name'] ?? '')).'</strong></td>';
            $html .= '<td>'.htmlspecialchars((string) ($item['unit'] ?? 'قطعة')).'</td>';
            if ($showQty) {
                $html .= '<td><strong>'.number_format($qty, 2).'</strong></td>';
            }
            if ($showPackages) {
                $html .= '<td>'.($packages > 0 ? number_format($packages, 0) : '-').'</td>';
            }
            if ($showPrice) {
                $html .= '<td>'.number_format($price, 2).' ج.م</td>';
            }
            if ($showLineTotal) {
                $html .= '<td><strong>'.number_format($lineTotal, 2).' ج.م</strong></td>';
            }
            $html .= '<td>'.htmlspecialchars((string) ($item['notes'] ?? '')).'</td>';
            $html .= '</tr>';
        }

        // Totals Row
        $html .= '<tr class="total-row">';
        $html .= '<td colspan="5" class="text-right"><strong>الإجمالي الكلي ('.count($items).' أصناف)</strong></td>';
        if ($showQty) {
            $html .= '<td><strong>'.number_format($totalQty, 2).'</strong></td>';
        }
        if ($showPackages) {
            $html .= '<td><strong>'.($totalPackages > 0 ? number_format($totalPackages, 0) : '-').'</strong></td>';
        }
        if ($showPrice) {
            $html .= '<td>-</td>';
        }
        if ($showLineTotal) {
            $html .= '<td><strong>'.number_format($totalAmount, 2).' ج.م</strong></td>';
        }
        $html .= '<td>-</td>';
        $html .= '</tr>';

        $html .= '</tbody></table>';

        $html .= '<div style="margin-top: 25px; font-size: 11px; color: #64748b; text-align: center;">تم التصدير آلياً عبر نظام كيان ERP بتاريخ '.date('Y-m-d H:i').'</div>';
        $html .= '</body></html>';

        $cleanFilename = 'packing_list_'.preg_replace('/[^a-zA-Z0-9_\-]/', '_', $docNumber).'.xls';

        return response($html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$cleanFilename}\"",
        ]);
    }

    /**
     * Export the packing list as standard UTF-8 CSV.
     */
    public function exportCsv(Request $request): Response
    {
        $payload = $this->extractPayload($request);
        $docNumber = $payload['doc_number'];
        $items = $payload['items'];

        $showQty = (bool) $payload['show_quantity'];
        $showPrice = (bool) $payload['show_price'];
        $showPackages = (bool) $payload['show_packages'];
        $showLineTotal = $showQty && $showPrice;

        $headers = [
            'م',
            'كود الصنف (SKU)',
            'الباركود',
            'اسم الصنف',
            'الوحدة',
        ];

        if ($showQty) {
            $headers[] = 'الكمية';
        }
        if ($showPackages) {
            $headers[] = 'عدد الطرود/الكراتين';
        }
        if ($showPrice) {
            $headers[] = 'سعر الوحدة';
        }
        if ($showLineTotal) {
            $headers[] = 'الإجمالي';
        }

        $headers[] = 'ملاحظات التعبئة';

        $output = "\xEF\xBB\xBF"; // UTF-8 BOM
        $handle = fopen('php://memory', 'r+');

        fputcsv($handle, $headers);

        $idx = 1;
        foreach ($items as $item) {
            $qty = (float) ($item['qty'] ?? 0);
            $packages = (float) ($item['packages_count'] ?? 0);
            $price = (float) ($item['price'] ?? 0);
            $lineTotal = (float) ($item['total'] ?? ($qty * $price));

            $row = [
                $idx++,
                $item['sku'] ?? '',
                $item['barcode'] ?? '',
                $item['name'] ?? '',
                $item['unit'] ?? 'قطعة',
            ];

            if ($showQty) {
                $row[] = $qty;
            }
            if ($showPackages) {
                $row[] = $packages > 0 ? $packages : '';
            }
            if ($showPrice) {
                $row[] = $price;
            }
            if ($showLineTotal) {
                $row[] = $lineTotal;
            }

            $row[] = $item['notes'] ?? '';

            fputcsv($handle, $row);
        }

        rewind($handle);
        $output .= stream_get_contents($handle);
        fclose($handle);

        $cleanFilename = 'packing_list_'.preg_replace('/[^a-zA-Z0-9_\-]/', '_', $docNumber).'.csv';

        return response($output, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$cleanFilename}\"",
        ]);
    }

    /**
     * Render high-fidelity print document in 3 sizes: A4, A5, and Thermal 80mm.
     */
    public function print(Request $request): View
    {
        $payload = $this->extractPayload($request);

        // Store into session so format switching links (A4 / A5 / 80mm) keep the user data!
        if ($request->isMethod('post') || ! empty($payload['items'])) {
            $request->session()->put('current_packing_list', $payload);
        } elseif ($request->session()->has('current_packing_list')) {
            $saved = $request->session()->get('current_packing_list');
            if ($request->filled('format')) {
                $saved['format'] = $request->query('format');
            }
            $payload = $saved;
        }

        $format = $request->query('format', $payload['format'] ?? 'a4');
        if (! in_array($format, ['a4', 'a5', 'thermal_80mm'])) {
            $format = 'a4';
        }

        $company = CompanySetting::getSettings();

        $showQty = (bool) ($payload['show_quantity'] ?? true);
        $showPrice = (bool) ($payload['show_price'] ?? true);
        $showPackages = (bool) ($payload['show_packages'] ?? true);
        $showLineTotal = $showQty && $showPrice;

        $totalQty = 0;
        $totalPackages = 0;
        $totalAmount = 0;

        foreach ($payload['items'] as $item) {
            if ($showQty) {
                $totalQty += (float) ($item['qty'] ?? 0);
            }
            if ($showPackages) {
                $totalPackages += (float) ($item['packages_count'] ?? 0);
            }
            if ($showLineTotal) {
                $totalAmount += (float) (($item['qty'] ?? 0) * ($item['price'] ?? 0));
            }
        }

        return view('packing-list.print', [
            'title' => 'بيان تعبئة وتجهيز بضاعة - '.$payload['doc_number'],
            'docNumber' => $payload['doc_number'],
            'docDate' => $payload['doc_date'],
            'customerName' => $payload['customer_name'],
            'warehouseName' => $payload['warehouse_name'],
            'priceType' => $payload['price_type'],
            'notes' => $payload['notes'],
            'items' => $payload['items'],
            'showQuantity' => $showQty,
            'showPrice' => $showPrice,
            'showPackages' => $showPackages,
            'showLineTotal' => $showLineTotal,
            'format' => $format,
            'company' => $company,
            'totalQty' => $totalQty,
            'totalPackages' => $totalPackages,
            'totalAmount' => $totalAmount,
            'autoPrint' => $request->boolean('auto', false),
        ]);
    }

    /**
     * Extract and sanitize packing list payload from request (POST or GET).
     *
     * @return array<string, mixed>
     */
    protected function extractPayload(Request $request): array
    {
        $rawItems = $request->input('items', []);

        if (is_string($rawItems)) {
            $decoded = json_decode($rawItems, true);
            $rawItems = is_array($decoded) ? $decoded : [];
        }

        $items = [];
        foreach ($rawItems as $row) {
            if (empty($row['name']) && empty($row['sku']) && empty($row['barcode'])) {
                continue;
            }

            $qty = (float) ($row['qty'] ?? 1);
            $price = (float) ($row['price'] ?? 0);
            $lineTotal = (float) ($row['total'] ?? ($qty * $price));

            $items[] = [
                'id' => $row['id'] ?? null,
                'sku' => trim((string) ($row['sku'] ?? '')),
                'barcode' => trim((string) ($row['barcode'] ?? '')),
                'name' => trim((string) ($row['name'] ?? 'صنف غير مسمى')),
                'category_name' => trim((string) ($row['category_name'] ?? 'عام')),
                'unit' => trim((string) ($row['unit'] ?? 'قطعة')),
                'qty' => $qty > 0 ? $qty : 1.0,
                'packages_count' => (float) ($row['packages_count'] ?? 0),
                'price' => $price,
                'total' => $lineTotal,
                'notes' => trim((string) ($row['notes'] ?? '')),
            ];
        }

        return [
            'doc_number' => trim((string) $request->input('doc_number', 'PL-'.date('Ymd').'-001')),
            'doc_date' => trim((string) $request->input('doc_date', date('Y-m-d'))),
            'customer_name' => trim((string) $request->input('customer_name', '')),
            'warehouse_name' => trim((string) $request->input('warehouse_name', '')),
            'price_type' => trim((string) $request->input('price_type', 'wholesale')),
            'format' => trim((string) $request->input('format', 'a4')),
            'notes' => trim((string) $request->input('notes', '')),
            'show_quantity' => $request->boolean('show_quantity', true),
            'show_price' => $request->boolean('show_price', true),
            'show_packages' => $request->boolean('show_packages', true),
            'items' => $items,
        ];
    }
}
