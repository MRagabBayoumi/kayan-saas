<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CompanySetting;
use App\Models\PackingList;
use App\Models\Product;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Services\SimpleXlsxGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PackingListController extends Controller
{
    /**
     * Display the packing list landing page (archive / history of previously saved lists).
     * If load_id is provided, redirect to create / editor mode.
     */
    public function index(Request $request): View|RedirectResponse
    {
        if ($request->filled('load_id')) {
            return redirect()->route('packing-list.create', ['load_id' => $request->query('load_id')]);
        }

        return $this->archive($request);
    }

    /**
     * Display the dedicated Packing List builder interface (new or edit existing via load_id).
     */
    public function create(Request $request): View
    {
        $categories = Category::active()->orderBy('name')->get();
        $warehouses = Warehouse::active()->orderBy('name')->get();
        $units = Unit::active()->orderBy('name')->get();
        $company = CompanySetting::getSettings();

        $loadedPackingList = null;
        $loadedData = null;
        if ($request->filled('load_id')) {
            $loadedPackingList = PackingList::with('items.product.category')->find($request->query('load_id'));
            if ($loadedPackingList) {
                $skus = $loadedPackingList->items->pluck('sku')->filter()->unique();
                $barcodes = $loadedPackingList->items->pluck('barcode')->filter()->unique();
                $productIds = $loadedPackingList->items->pluck('product_id')->filter()->unique();

                $prodsById = Product::with(['category', 'stockBalances'])->whereIn('id', $productIds)->get()->keyBy('id');

                $foundSkuList = $prodsById->pluck('sku')->filter()->all();
                $missingSkus = $skus->diff($foundSkuList);
                $prodsBySku = $missingSkus->isNotEmpty()
                    ? Product::with(['category', 'stockBalances'])->whereIn('sku', $missingSkus)->get()->keyBy('sku')
                    : collect();

                $foundBarcodeList = $prodsById->pluck('barcode')->merge($prodsBySku->pluck('barcode'))->filter()->all();
                $missingBarcodes = $barcodes->diff($foundBarcodeList);
                $prodsByBarcode = $missingBarcodes->isNotEmpty()
                    ? Product::with(['category', 'stockBalances'])->whereIn('barcode', $missingBarcodes)->get()->keyBy('barcode')
                    : collect();

                $loadedData = [
                    'id' => $loadedPackingList->id,
                    'doc_number' => $loadedPackingList->packing_number,
                    'doc_date' => $loadedPackingList->packing_date ? $loadedPackingList->packing_date->format('Y-m-d') : date('Y-m-d'),
                    'customer_name' => $loadedPackingList->customer_name,
                    'warehouse_name' => $loadedPackingList->warehouse_name,
                    'price_type' => $loadedPackingList->price_type ?: 'wholesale',
                    'notes' => $loadedPackingList->notes,
                    'show_quantity' => (bool) $loadedPackingList->show_quantity,
                    'show_price' => (bool) $loadedPackingList->show_price,
                    'show_packages' => (bool) $loadedPackingList->show_packages,
                    'items' => $loadedPackingList->items->map(function ($it) use ($prodsById, $prodsBySku, $prodsByBarcode) {
                        $prod = ($it->product_id && isset($prodsById[$it->product_id])) ? $prodsById[$it->product_id] : $it->product;
                        if (! $prod && $it->sku && isset($prodsBySku[$it->sku])) {
                            $prod = $prodsBySku[$it->sku];
                        }
                        if (! $prod && $it->barcode && isset($prodsByBarcode[$it->barcode])) {
                            $prod = $prodsByBarcode[$it->barcode];
                        }

                        $unitPrice = (float) $it->unit_price;
                        $wPrice = $prod && (float) $prod->wholesale_price > 0 ? (float) $prod->wholesale_price : $unitPrice;
                        $rPrice = $prod && (float) $prod->retail_price > 0 ? (float) $prod->retail_price : $unitPrice;
                        $dPrice = $prod && (float) $prod->distributor_price > 0 ? (float) $prod->distributor_price : $wPrice;
                        $cPrice = $prod && (float) $prod->cost_price > 0 ? (float) $prod->cost_price : $unitPrice;

                        $unit = ($prod && ! empty($prod->unit)) ? $prod->unit : ($it->unit ?: 'قطعة');
                        $cleanUnit = trim($unit);
                        $piecesPerUnit = 1;
                        if ($prod) {
                            if (in_array($cleanUnit, ['دسته', 'دستة', 'دزينة', 'دزينه'])) {
                                $piecesPerUnit = ($prod->pieces_per_package > 1) ? (int) $prod->pieces_per_package : 12;
                            } elseif (in_array($cleanUnit, ['كرتونة', 'كرتونه'])) {
                                $piecesPerUnit = ($prod->pieces_per_package > 1) ? (int) $prod->pieces_per_package : 24;
                            }
                        }

                        $totalStockPieces = $prod ? (float) $prod->total_stock : 0.0;
                        $stockInUnit = $piecesPerUnit > 1 ? ($totalStockPieces / $piecesPerUnit) : $totalStockPieces;
                        if ($piecesPerUnit > 1 && abs(round($stockInUnit) - $stockInUnit) < 0.05) {
                            $stockInUnit = (float) round($stockInUnit);
                        }
                        $breakdown = $prod ? $prod->formatStockBreakdown() : '0';
                        $minStockAlert = $prod ? (float) $prod->min_stock_alert : 0;

                        return [
                            'id' => $prod ? $prod->id : $it->product_id,
                            'sku' => $it->sku,
                            'barcode' => $it->barcode,
                            'name' => $it->product_name,
                            'category_name' => $it->category_name ?: ($prod?->category?->name ?: 'عام'),
                            'unit' => $unit,
                            'qty' => (float) $it->quantity,
                            'packages_count' => (int) $it->packages_count,
                            'price' => $unitPrice,
                            'wholesale_price' => $wPrice,
                            'retail_price' => $rPrice,
                            'distributor_price' => $dPrice,
                            'cost_price' => $cPrice,
                            'total' => (float) $it->total_amount,
                            'notes' => $it->notes,
                            'total_stock_pieces' => $totalStockPieces,
                            'stock_in_unit' => round($stockInUnit, 2),
                            'stock_breakdown' => $breakdown,
                            'min_stock_alert' => $minStockAlert,
                            'pieces_per_unit' => $piecesPerUnit,
                            'is_in_stock' => ($stockInUnit > 0),
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
                    $productId = ! empty($it['id']) ? (int) $it['id'] : null;
                    if (! $productId && ! empty($it['sku'])) {
                        $productId = Product::where('sku', $it['sku'])->value('id');
                    }
                    if (! $productId && ! empty($it['barcode'])) {
                        $productId = Product::where('barcode', $it['barcode'])->value('id');
                    }

                    $packingList->items()->create([
                        'product_id' => $productId,
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

        $headers = ['م', 'كود الصنف (SKU)', 'الباركود', 'اسم المنتج / الصنف', 'التصنيف', 'الوحدة'];
        $columnTypes = [
            0 => 'number',
            1 => 'text',
            2 => 'text',
            3 => 'text',
            4 => 'text',
            5 => 'text',
        ];
        $columnWidths = [
            0 => 8,
            1 => 18,
            2 => 18,
            3 => 32,
            4 => 16,
            5 => 12,
        ];

        $colIdx = 6;
        if ($showQty) {
            $headers[] = 'الكمية المطلوبة';
            $columnTypes[$colIdx] = 'number';
            $columnWidths[$colIdx] = 16;
            $colIdx++;
        }
        if ($showPackages) {
            $headers[] = 'الطرود/الكراتين';
            $columnTypes[$colIdx] = 'number';
            $columnWidths[$colIdx] = 16;
            $colIdx++;
        }
        if ($showPrice) {
            $headers[] = 'سعر الوحدة';
            $columnTypes[$colIdx] = 'currency';
            $columnWidths[$colIdx] = 16;
            $colIdx++;
        }
        if ($showLineTotal) {
            $headers[] = 'إجمالي السعر';
            $columnTypes[$colIdx] = 'currency';
            $columnWidths[$colIdx] = 18;
            $colIdx++;
        }
        $headers[] = 'ملاحظات التعبئة والتجهيز';
        $columnTypes[$colIdx] = 'text';
        $columnWidths[$colIdx] = 26;

        $rows = [];
        $idx = 1;
        foreach ($packingList->items as $item) {
            $row = [
                $idx++,
                (string) $item->sku,
                (string) $item->barcode,
                (string) $item->product_name,
                (string) ($item->category_name ?: 'عام'),
                (string) ($item->unit ?: 'قطعة'),
            ];
            if ($showQty) {
                $row[] = (float) $item->quantity;
            }
            if ($showPackages) {
                $row[] = (int) $item->packages_count;
            }
            if ($showPrice) {
                $row[] = (float) $item->unit_price;
            }
            if ($showLineTotal) {
                $row[] = (float) $item->total_amount;
            }
            $row[] = (string) $item->notes;
            $rows[] = $row;
        }

        // Totals summary row
        $totalsRow = [
            'الإجمالي',
            '',
            '',
            $packingList->items->count().' أصناف',
            '',
            '',
        ];
        if ($showQty) {
            $totalsRow[] = (float) $packingList->total_quantity;
        }
        if ($showPackages) {
            $totalsRow[] = (int) $packingList->total_packages;
        }
        if ($showPrice) {
            $totalsRow[] = '';
        }
        if ($showLineTotal) {
            $totalsRow[] = (float) $packingList->total_amount;
        }
        $totalsRow[] = '';
        $rows[] = $totalsRow;

        $metaRows = [
            [$company->company_name ?: 'شركة كيان التجارية', 'بيان وقائمة التعبئة والتجهيز رقم: '.$docNumber],
            ['تاريخ الإصدار: '.$docDate, 'العميل: '.($customerName ?: 'عميل عام'), 'المستودع: '.($warehouseName ?: 'المخزن الرئيسي')],
            (! empty($notes) ? ['ملاحظات: '.$notes] : []),
            [],
        ];

        $xlsxContent = SimpleXlsxGenerator::create(
            headers: $headers,
            rows: $rows,
            columnTypes: $columnTypes,
            columnWidths: $columnWidths,
            sheetTitle: 'قائمة التعبئة',
            metaRows: $metaRows
        );

        $cleanFilename = 'packing_list_'.preg_replace('/[^a-zA-Z0-9_\-]/', '_', $docNumber).'.xlsx';

        return response($xlsxContent, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
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
        // Strip scanner symbology prefix (e.g. ]C1, ]e0, etc.)
        $term = preg_replace('/^\][A-Za-z0-9]{2}/', '', $term);
        $term = trim($term);

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
            })
                ->orderByRaw('CASE WHEN barcode = ? THEN 1 WHEN sku = ? THEN 2 WHEN barcode LIKE ? THEN 3 WHEN sku LIKE ? THEN 4 ELSE 5 END', [$term, $term, "{$term}%", "{$term}%"])
                ->take(25)->get();
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
                'distributor_price' => (float) ($product->distributor_price && (float) $product->distributor_price > 0 ? $product->distributor_price : ($product->wholesale_price ?? 0)),
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
     * Bulk fetch latest product prices, units, and categories for a given list of items.
     */
    public function refreshPrices(Request $request): JsonResponse
    {
        $items = $request->input('items', []);
        if (empty($items) || ! is_array($items)) {
            return response()->json([
                'success' => false,
                'message' => 'لا توجد أصناف لتحديث أسعارها.',
                'products' => [],
            ]);
        }

        $productIds = collect($items)->pluck('id')->filter()->unique()->values()->all();
        $skus = collect($items)->pluck('sku')->filter()->unique()->values()->all();
        $barcodes = collect($items)->pluck('barcode')->filter()->unique()->values()->all();

        $query = Product::with('category')->where('is_active', true);
        $query->where(function ($q) use ($productIds, $skus, $barcodes) {
            if (! empty($productIds)) {
                $q->orWhereIn('id', $productIds);
            }
            if (! empty($skus)) {
                $q->orWhereIn('sku', $skus);
            }
            if (! empty($barcodes)) {
                $q->orWhereIn('barcode', $barcodes);
            }
        });

        $products = $query->get();

        $byBarcode = $products->keyBy(fn ($p) => (string) $p->barcode);
        $bySku = $products->keyBy(fn ($p) => (string) $p->sku);
        $byId = $products->keyBy(fn ($p) => (int) $p->id);

        $refreshed = [];
        foreach ($items as $it) {
            $prod = null;
            $itId = ! empty($it['id']) ? (int) $it['id'] : null;
            $itSku = ! empty($it['sku']) ? (string) $it['sku'] : null;
            $itBarcode = ! empty($it['barcode']) ? (string) $it['barcode'] : null;

            if ($itId && isset($byId[$itId])) {
                $prod = $byId[$itId];
            } elseif ($itBarcode && isset($byBarcode[$itBarcode])) {
                $prod = $byBarcode[$itBarcode];
            } elseif ($itSku && isset($bySku[$itSku])) {
                $prod = $bySku[$itSku];
            }

            if ($prod) {
                $unitPrice = (float) ($it['price'] ?? 0);
                $wPrice = (float) $prod->wholesale_price > 0 ? (float) $prod->wholesale_price : $unitPrice;
                $rPrice = (float) $prod->retail_price > 0 ? (float) $prod->retail_price : $unitPrice;
                $dPrice = (float) $prod->distributor_price > 0 ? (float) $prod->distributor_price : $wPrice;
                $cPrice = (float) $prod->cost_price > 0 ? (float) $prod->cost_price : $unitPrice;

                $refreshed[] = [
                    'item_id' => $itId,
                    'sku' => $itSku ?: $prod->sku,
                    'barcode' => $itBarcode ?: $prod->barcode,
                    'product_id' => $prod->id,
                    'product_name' => $prod->name,
                    'category_name' => $prod->category?->name ?: 'عام',
                    'unit' => $prod->unit ?: 'قطعة',
                    'wholesale_price' => $wPrice,
                    'retail_price' => $rPrice,
                    'distributor_price' => $dPrice,
                    'cost_price' => $cPrice,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'count' => count($refreshed),
            'products' => $refreshed,
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

        $headers = ['م', 'كود الصنف (SKU)', 'الباركود', 'اسم المنتج / الصنف', 'التصنيف', 'الوحدة'];
        $columnTypes = [
            0 => 'number',
            1 => 'text',
            2 => 'text',
            3 => 'text',
            4 => 'text',
            5 => 'text',
        ];
        $columnWidths = [
            0 => 8,
            1 => 18,
            2 => 18,
            3 => 32,
            4 => 16,
            5 => 12,
        ];

        $colIdx = 6;
        if ($showQty) {
            $headers[] = 'الكمية المطلوبة';
            $columnTypes[$colIdx] = 'number';
            $columnWidths[$colIdx] = 16;
            $colIdx++;
        }
        if ($showPackages) {
            $headers[] = 'الطرود/الكراتين';
            $columnTypes[$colIdx] = 'number';
            $columnWidths[$colIdx] = 16;
            $colIdx++;
        }
        if ($showPrice) {
            $headers[] = 'سعر الوحدة';
            $columnTypes[$colIdx] = 'currency';
            $columnWidths[$colIdx] = 16;
            $colIdx++;
        }
        if ($showLineTotal) {
            $headers[] = 'إجمالي السعر';
            $columnTypes[$colIdx] = 'currency';
            $columnWidths[$colIdx] = 18;
            $colIdx++;
        }
        $headers[] = 'ملاحظات التعبئة والتجهيز';
        $columnTypes[$colIdx] = 'text';
        $columnWidths[$colIdx] = 26;

        $rows = [];
        $idx = 1;
        foreach ($items as $item) {
            $qty = (float) ($item['qty'] ?? 0);
            $packages = (int) ($item['packages_count'] ?? 0);
            $price = (float) ($item['price'] ?? 0);
            $lineTotal = (float) ($item['total'] ?? ($qty * $price));

            $row = [
                $idx++,
                (string) ($item['sku'] ?? ''),
                (string) ($item['barcode'] ?? ''),
                (string) ($item['name'] ?? ''),
                (string) ($item['category_name'] ?? 'عام'),
                (string) ($item['unit'] ?? 'قطعة'),
            ];
            if ($showQty) {
                $row[] = $qty;
            }
            if ($showPackages) {
                $row[] = $packages;
            }
            if ($showPrice) {
                $row[] = $price;
            }
            if ($showLineTotal) {
                $row[] = $lineTotal;
            }
            $row[] = (string) ($item['notes'] ?? '');
            $rows[] = $row;
        }

        // Totals summary row
        $totalsRow = [
            'الإجمالي',
            '',
            '',
            count($items).' أصناف',
            '',
            '',
        ];
        if ($showQty) {
            $totalsRow[] = $totalQty;
        }
        if ($showPackages) {
            $totalsRow[] = $totalPackages;
        }
        if ($showPrice) {
            $totalsRow[] = '';
        }
        if ($showLineTotal) {
            $totalsRow[] = $totalAmount;
        }
        $totalsRow[] = '';
        $rows[] = $totalsRow;

        $metaRows = [
            [$company->company_name ?: 'شركة كيان التجارية', 'بيان وقائمة التعبئة والتجهيز رقم: '.$docNumber],
            ['تاريخ الإصدار: '.$docDate, 'العميل: '.($customerName ?: 'عميل عام / بدون تحديد'), 'المستودع: '.($warehouseName ?: 'المخزن الرئيسي')],
            (! empty($notes) ? ['ملاحظات: '.$notes] : []),
            [],
        ];

        $xlsxContent = SimpleXlsxGenerator::create(
            headers: $headers,
            rows: $rows,
            columnTypes: $columnTypes,
            columnWidths: $columnWidths,
            sheetTitle: 'قائمة التعبئة',
            metaRows: $metaRows
        );

        $cleanFilename = 'packing_list_'.preg_replace('/[^a-zA-Z0-9_\-]/', '_', $docNumber).'.xlsx';

        return response($xlsxContent, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$cleanFilename}\"",
        ]);
    }

    /**
     * Bulk fetch products by ID, Barcode, or SKU to eliminate N+1 queries.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array<string, Product>
     */
    protected function bulkMapProducts(array $items): array
    {
        $ids = [];
        $barcodes = [];
        $skus = [];

        foreach ($items as $it) {
            if (! empty($it['id'])) {
                $ids[] = (int) $it['id'];
            }
            if (! empty($it['barcode'])) {
                $barcodes[] = trim((string) $it['barcode']);
            }
            if (! empty($it['sku'])) {
                $skus[] = trim((string) $it['sku']);
            }
        }

        $ids = array_unique(array_filter($ids));
        $barcodes = array_unique(array_filter($barcodes));
        $skus = array_unique(array_filter($skus));

        if (empty($ids) && empty($barcodes) && empty($skus)) {
            return [];
        }

        $query = Product::with(['category', 'stockBalances']);
        $query->where(function ($q) use ($ids, $barcodes, $skus) {
            $hasCondition = false;
            if (! empty($ids)) {
                $q->whereIn('id', $ids);
                $hasCondition = true;
            }
            if (! empty($barcodes)) {
                if ($hasCondition) {
                    $q->orWhereIn('barcode', $barcodes);
                } else {
                    $q->whereIn('barcode', $barcodes);
                    $hasCondition = true;
                }
            }
            if (! empty($skus)) {
                if ($hasCondition) {
                    $q->orWhereIn('sku', $skus);
                } else {
                    $q->whereIn('sku', $skus);
                }
            }
        });

        $products = $query->get();
        $map = [];
        foreach ($products as $p) {
            $map['id:'.$p->id] = $p;
            if ($p->barcode) {
                $map['bc:'.trim((string) $p->barcode)] = $p;
            }
            if ($p->sku) {
                $map['sku:'.trim((string) $p->sku)] = $p;
            }
        }

        return $map;
    }

    /**
     * Inspect and match real-time warehouse stock for all items currently in the packing list builder.
     */
    public function inspectStock(Request $request): JsonResponse
    {
        $rawItems = $request->input('items', []);
        if (is_string($rawItems)) {
            $rawItems = json_decode($rawItems, true) ?: [];
        }

        $inspectedItems = [];
        $availableCount = 0;
        $zeroCount = 0;
        $totalAvailValue = 0;

        $prodMap = $this->bulkMapProducts($rawItems);

        foreach ($rawItems as $it) {
            $prod = null;
            if (! empty($it['id']) && isset($prodMap['id:'.$it['id']])) {
                $prod = $prodMap['id:'.$it['id']];
            } elseif (! empty($it['barcode']) && isset($prodMap['bc:'.trim((string) $it['barcode'])])) {
                $prod = $prodMap['bc:'.trim((string) $it['barcode'])];
            } elseif (! empty($it['sku']) && isset($prodMap['sku:'.trim((string) $it['sku'])])) {
                $prod = $prodMap['sku:'.trim((string) $it['sku'])];
            }

            $unit = ($prod && ! empty($prod->unit)) ? $prod->unit : ($it['unit'] ?? 'قطعة');
            $cleanUnit = trim($unit);
            $piecesPerUnit = 1;
            if ($prod) {
                if (in_array($cleanUnit, ['دسته', 'دستة', 'دزينة', 'دزينه'])) {
                    $piecesPerUnit = ($prod->pieces_per_package > 1) ? (int) $prod->pieces_per_package : 12;
                } elseif (in_array($cleanUnit, ['كرتونة', 'كرتونه'])) {
                    $piecesPerUnit = ($prod->pieces_per_package > 1) ? (int) $prod->pieces_per_package : 24;
                }
            }

            $totalStockPieces = $prod ? (float) $prod->total_stock : 0.0;
            $stockInUnit = $piecesPerUnit > 1 ? ($totalStockPieces / $piecesPerUnit) : $totalStockPieces;
            if ($piecesPerUnit > 1 && abs(round($stockInUnit) - $stockInUnit) < 0.05) {
                $stockInUnit = (float) round($stockInUnit);
            }
            $breakdown = $prod ? $prod->formatStockBreakdown() : '0';
            $minStockAlert = $prod ? (float) $prod->min_stock_alert : 0;
            $price = (float) ($it['price'] ?? ($prod ? $prod->wholesale_price : 0));

            $isInStock = ($stockInUnit > 0);
            if ($isInStock) {
                $availableCount++;
                $totalAvailValue += ($stockInUnit * $price);
            } else {
                $zeroCount++;
            }

            $it['product_id'] = $prod?->id ?? ($it['id'] ?? null);
            $it['total_stock_pieces'] = $totalStockPieces;
            $it['stock_in_unit'] = round($stockInUnit, 2);
            $it['stock_breakdown'] = $breakdown;
            $it['min_stock_alert'] = $minStockAlert;
            $it['pieces_per_unit'] = $piecesPerUnit;
            $it['is_in_stock'] = $isInStock;

            $inspectedItems[] = $it;
        }

        $totalCount = count($inspectedItems);
        $ratio = $totalCount > 0 ? round(($availableCount / $totalCount) * 100, 1) : 0;

        return response()->json([
            'success' => true,
            'items' => $inspectedItems,
            'summary' => [
                'total_items' => $totalCount,
                'available_count' => $availableCount,
                'in_stock_count' => $availableCount,
                'zero_count' => $zeroCount,
                'zero_stock_count' => $zeroCount,
                'available_ratio' => $ratio,
                'availability_rate' => $ratio,
                'available_value' => round($totalAvailValue, 2),
            ],
        ]);
    }

    /**
     * Import a customer order file (Excel / CSV) and instantly match with warehouse stock.
     */
    public function importCustomerOrder(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:20480'],
            'warehouse_name' => ['nullable', 'string'],
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $ext = strtolower($file->getClientOriginalExtension());

        $grid = [];
        if (in_array($ext, ['xlsx', 'xlsm'])) {
            $grid = $this->parseXlsxGrid($file->getRealPath());
        } else {
            $grid = $this->parseCsvGrid($file->getRealPath());
        }

        if (empty($grid)) {
            return response()->json([
                'success' => false,
                'message' => 'تعذر قراءة البيانات من الملف المرفوع أو أن الملف فارغ.',
            ], 422);
        }

        // Detect Header Row & Column Indexes
        $headerRowIdx = -1;
        $colMap = [
            'barcode' => null,
            'sku' => null,
            'name' => null,
            'unit' => null,
            'price' => null,
            'qty' => null,
            'notes' => null,
        ];

        // Scan first 10 rows for recognizable headers
        for ($r = 0; $r < min(10, count($grid)); $r++) {
            $row = $grid[$r];
            $matchedCols = 0;
            foreach ($row as $colIdx => $cellVal) {
                $clean = mb_strtolower(trim((string) $cellVal));

                if (is_null($colMap['barcode']) && (str_contains($clean, 'باركود') || str_contains($clean, 'barcode'))) {
                    $colMap['barcode'] = $colIdx;
                    $matchedCols++;
                } elseif (is_null($colMap['sku']) && (str_contains($clean, 'كود') || str_contains($clean, 'sku') || str_contains($clean, 'رمز'))) {
                    $colMap['sku'] = $colIdx;
                    $matchedCols++;
                } elseif (is_null($colMap['name']) && (str_contains($clean, 'اسم') || str_contains($clean, 'صنف') || str_contains($clean, 'منتج') || str_contains($clean, 'بيان') || str_contains($clean, 'product') || str_contains($clean, 'name'))) {
                    $colMap['name'] = $colIdx;
                    $matchedCols++;
                } elseif (is_null($colMap['qty']) && (str_contains($clean, 'كمية') || str_contains($clean, 'مطلوب') || str_contains($clean, 'qty') || str_contains($clean, 'count') || str_contains($clean, 'quantity'))) {
                    $colMap['qty'] = $colIdx;
                    $matchedCols++;
                } elseif (is_null($colMap['unit']) && (str_contains($clean, 'وحدة') || str_contains($clean, 'unit') || str_contains($clean, 'uom'))) {
                    $colMap['unit'] = $colIdx;
                    $matchedCols++;
                } elseif (is_null($colMap['price']) && (str_contains($clean, 'سعر') || str_contains($clean, 'price'))) {
                    $colMap['price'] = $colIdx;
                    $matchedCols++;
                } elseif (is_null($colMap['notes']) && (str_contains($clean, 'ملاحظ') || str_contains($clean, 'note'))) {
                    $colMap['notes'] = $colIdx;
                    $matchedCols++;
                }
            }

            if ($matchedCols >= 2) {
                $headerRowIdx = $r;
                break;
            }
        }

        // If no header detected, default heuristic (SKU=1, Barcode=2, Name=3, Unit=5, Price=6)
        if ($headerRowIdx === -1) {
            $headerRowIdx = 0;
            $colMap['sku'] = 1;
            $colMap['barcode'] = 2;
            $colMap['name'] = 3;
            $colMap['unit'] = 5;
            $colMap['price'] = 6;
        }

        $dataRows = array_slice($grid, $headerRowIdx + 1);

        $barcodes = [];
        $skus = [];
        $names = [];

        foreach ($dataRows as $row) {
            $b = ! is_null($colMap['barcode']) && isset($row[$colMap['barcode']]) ? trim((string) $row[$colMap['barcode']]) : '';
            $s = ! is_null($colMap['sku']) && isset($row[$colMap['sku']]) ? trim((string) $row[$colMap['sku']]) : '';
            $n = ! is_null($colMap['name']) && isset($row[$colMap['name']]) ? trim((string) $row[$colMap['name']]) : '';

            if ($b !== '') {
                $barcodes[] = $b;
            }
            if ($s !== '') {
                $skus[] = $s;
            }
            if ($n !== '') {
                $names[] = $n;
            }
        }

        $barcodes = array_unique(array_filter($barcodes));
        $skus = array_unique(array_filter($skus));
        $names = array_unique(array_filter($names));

        $productsByBarcode = ! empty($barcodes) ? Product::with(['category', 'stockBalances'])->whereIn('barcode', $barcodes)->get()->keyBy('barcode') : collect();
        $productsBySku = ! empty($skus) ? Product::with(['category', 'stockBalances'])->whereIn('sku', $skus)->get()->keyBy('sku') : collect();
        $productsByName = ! empty($names) ? Product::with(['category', 'stockBalances'])->whereIn('name', $names)->get()->keyBy('name') : collect();

        $matchedItems = [];
        $unmatchedCount = 0;
        $availableCount = 0;
        $zeroCount = 0;

        foreach ($dataRows as $row) {
            $b = ! is_null($colMap['barcode']) && isset($row[$colMap['barcode']]) ? trim((string) $row[$colMap['barcode']]) : '';
            $s = ! is_null($colMap['sku']) && isset($row[$colMap['sku']]) ? trim((string) $row[$colMap['sku']]) : '';
            $n = ! is_null($colMap['name']) && isset($row[$colMap['name']]) ? trim((string) $row[$colMap['name']]) : '';

            if ($b === '' && $s === '' && $n === '') {
                continue;
            }

            $prod = null;
            if ($b !== '' && isset($productsByBarcode[$b])) {
                $prod = $productsByBarcode[$b];
            } elseif ($s !== '' && isset($productsBySku[$s])) {
                $prod = $productsBySku[$s];
            } elseif ($n !== '' && isset($productsByName[$n])) {
                $prod = $productsByName[$n];
            }

            if (! $prod) {
                $unmatchedCount++;

                continue;
            }

            $rawUnit = ! is_null($colMap['unit']) && isset($row[$colMap['unit']]) ? trim((string) $row[$colMap['unit']]) : '';
            $unit = ($prod && ! empty($prod->unit)) ? $prod->unit : ($rawUnit ?: 'قطعة');
            $cleanUnit = trim($unit);
            $piecesPerUnit = 1;
            if (in_array($cleanUnit, ['دسته', 'دستة', 'دزينة', 'دزينه'])) {
                $piecesPerUnit = ($prod->pieces_per_package > 1) ? (int) $prod->pieces_per_package : 12;
            } elseif (in_array($cleanUnit, ['كرتونة', 'كرتونه'])) {
                $piecesPerUnit = ($prod->pieces_per_package > 1) ? (int) $prod->pieces_per_package : 24;
            }

            $totalStockPieces = (float) $prod->total_stock;
            $stockInUnit = $piecesPerUnit > 1 ? ($totalStockPieces / $piecesPerUnit) : $totalStockPieces;
            if ($piecesPerUnit > 1 && abs(round($stockInUnit) - $stockInUnit) < 0.05) {
                $stockInUnit = (float) round($stockInUnit);
            }
            $breakdown = $prod->formatStockBreakdown();
            $minStockAlert = (float) $prod->min_stock_alert;

            $rawPrice = ! is_null($colMap['price']) && isset($row[$colMap['price']]) ? (float) preg_replace('/[^0-9.]/', '', (string) $row[$colMap['price']]) : 0;
            $price = $rawPrice > 0 ? $rawPrice : (float) ($prod->distributor_price > 0 ? $prod->distributor_price : $prod->wholesale_price);

            $rawQty = ! is_null($colMap['qty']) && isset($row[$colMap['qty']]) ? (float) preg_replace('/[^0-9.]/', '', (string) $row[$colMap['qty']]) : 0;

            $isInStock = ($stockInUnit > 0);
            if ($isInStock) {
                $availableCount++;
            } else {
                $zeroCount++;
            }

            $notes = ! is_null($colMap['notes']) && isset($row[$colMap['notes']]) ? trim((string) $row[$colMap['notes']]) : '';
            if (str_starts_with($notes, '#NAME?')) {
                $notes = '';
            }

            $matchedItems[] = [
                'id' => $prod->id,
                'product_id' => $prod->id,
                'sku' => $prod->sku ?: $s,
                'barcode' => $prod->barcode ?: $b,
                'name' => $prod->name ?: $n,
                'category_name' => $prod->category?->name ?: 'عام',
                'unit' => $unit,
                'qty' => $rawQty,
                'packages_count' => 0,
                'price' => $price,
                'price_tier' => 'distributor',
                'wholesale_price' => (float) $prod->wholesale_price,
                'retail_price' => (float) $prod->retail_price,
                'distributor_price' => (float) $prod->distributor_price,
                'cost_price' => (float) $prod->cost_price,
                'total' => round($rawQty * $price, 2),
                'notes' => $notes,
                'total_stock_pieces' => $totalStockPieces,
                'stock_in_unit' => round($stockInUnit, 2),
                'stock_breakdown' => $breakdown,
                'min_stock_alert' => $minStockAlert,
                'pieces_per_unit' => $piecesPerUnit,
                'is_in_stock' => $isInStock,
            ];
        }

        $totalCount = count($matchedItems);
        $ratio = $totalCount > 0 ? round(($availableCount / $totalCount) * 100, 1) : 0;

        return response()->json([
            'success' => true,
            'message' => "تم فحص الشيت بنجاح ومطابقة {$totalCount} صنف مع رصيد المخزن.",
            'items' => $matchedItems,
            'summary' => [
                'total_items' => $totalCount,
                'available_count' => $availableCount,
                'in_stock_count' => $availableCount,
                'zero_count' => $zeroCount,
                'zero_stock_count' => $zeroCount,
                'available_ratio' => $ratio,
                'availability_rate' => $ratio,
            ],
            'unmatched_count' => $unmatchedCount,
            'filename' => $originalName,
        ]);
    }

    /**
     * Parse uploaded XLSX into grid array.
     */
    protected function parseXlsxGrid(string $path): array
    {
        $zip = new \ZipArchive;
        if ($zip->open($path) !== true) {
            return $this->parseCsvGrid($path);
        }

        $sharedStrings = [];
        $stringsXmlContent = $zip->getFromName('xl/sharedStrings.xml');
        if ($stringsXmlContent) {
            $stringsXml = simplexml_load_string($stringsXmlContent);
            if ($stringsXml && isset($stringsXml->si)) {
                foreach ($stringsXml->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = (string) $si->t;
                    } elseif (isset($si->r)) {
                        $text = '';
                        foreach ($si->r as $r) {
                            $text .= (string) $r->t;
                        }
                        $sharedStrings[] = $text;
                    } else {
                        $sharedStrings[] = '';
                    }
                }
            }
        }

        $sheetXmlContent = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if (! $sheetXmlContent) {
            return [];
        }

        $sheetXml = simplexml_load_string($sheetXmlContent);
        if (! $sheetXml || ! isset($sheetXml->sheetData->row)) {
            return [];
        }

        $grid = [];
        foreach ($sheetXml->sheetData->row as $row) {
            $rowVals = [];
            $maxCol = 0;
            foreach ($row->c as $cell) {
                $ref = (string) $cell['r'];
                $colLetters = preg_replace('/[0-9]/', '', $ref);
                $colIdx = 0;
                $len = strlen($colLetters);
                for ($i = 0; $i < $len; $i++) {
                    $colIdx = $colIdx * 26 + (ord($colLetters[$i]) - 64);
                }
                $colIdx -= 1;
                $maxCol = max($maxCol, $colIdx);

                $t = (string) $cell['t'];
                $val = '';
                if ($t === 's') {
                    $idx = (int) $cell->v;
                    $val = $sharedStrings[$idx] ?? '';
                } elseif ($t === 'inlineStr' && isset($cell->is->t)) {
                    $val = (string) $cell->is->t;
                } else {
                    $val = (string) $cell->v;
                }
                $rowVals[$colIdx] = trim($val);
            }

            $fullRow = [];
            for ($c = 0; $c <= $maxCol; $c++) {
                $fullRow[$c] = $rowVals[$c] ?? '';
            }
            $grid[] = $fullRow;
        }

        return $grid;
    }

    /**
     * Parse uploaded CSV into grid array.
     */
    protected function parseCsvGrid(string $path): array
    {
        $handle = fopen($path, 'r');
        if (! $handle) {
            return [];
        }

        $grid = [];
        while (($row = fgetcsv($handle)) !== false) {
            $grid[] = array_map('trim', $row);
        }
        fclose($handle);

        return $grid;
    }

    /**
     * Dedicated Customer Stock Availability Excel Export (.xlsx with 2 separate sheets: Available + Zero Stock).
     */
    public function exportCustomerStockExcel(Request $request): Response
    {
        $payload = $this->extractPayload($request);
        $company = CompanySetting::getSettings();

        $docNumber = $payload['doc_number'] ?: 'PL-'.date('Ymd');
        $docDate = $payload['doc_date'] ?: date('Y-m-d');
        $customerName = $payload['customer_name'] ?: 'العميل';
        $warehouseName = $payload['warehouse_name'] ?: 'معرض المقطم';
        $items = $payload['items'];

        $availableRows = [];
        $zeroRows = [];

        $availIdx = 1;
        $zeroIdx = 1;
        $totalAvailQty = 0;
        $totalAvailPieces = 0;
        $totalAvailAmount = 0;

        $prodMap = $this->bulkMapProducts($items);

        foreach ($items as $it) {
            $prod = null;
            if (! empty($it['id']) && isset($prodMap['id:'.$it['id']])) {
                $prod = $prodMap['id:'.$it['id']];
            } elseif (! empty($it['barcode']) && isset($prodMap['bc:'.trim((string) $it['barcode'])])) {
                $prod = $prodMap['bc:'.trim((string) $it['barcode'])];
            } elseif (! empty($it['sku']) && isset($prodMap['sku:'.trim((string) $it['sku'])])) {
                $prod = $prodMap['sku:'.trim((string) $it['sku'])];
            }

            $unit = ($prod && ! empty($prod->unit)) ? $prod->unit : ($it['unit'] ?? 'قطعة');
            $cleanUnit = trim($unit);
            $piecesPerUnit = 1;
            if ($prod) {
                if (in_array($cleanUnit, ['دسته', 'دستة', 'دزينة', 'دزينه'])) {
                    $piecesPerUnit = ($prod->pieces_per_package > 1) ? (int) $prod->pieces_per_package : 12;
                } elseif (in_array($cleanUnit, ['كرتونة', 'كرتونه'])) {
                    $piecesPerUnit = ($prod->pieces_per_package > 1) ? (int) $prod->pieces_per_package : 24;
                }
            }

            $totalStockPieces = $prod ? (float) $prod->total_stock : 0.0;
            $stockInUnit = $piecesPerUnit > 1 ? ($totalStockPieces / $piecesPerUnit) : $totalStockPieces;
            if ($piecesPerUnit > 1 && abs(round($stockInUnit) - $stockInUnit) < 0.05) {
                $stockInUnit = (float) round($stockInUnit);
            }
            $breakdown = $prod ? $prod->formatStockBreakdown() : '0';
            $price = (float) ($it['price'] ?? ($prod ? $prod->wholesale_price : 0));
            $allocatedQty = (float) ($it['qty'] ?? $stockInUnit);
            if ($allocatedQty <= 0 && $stockInUnit > 0) {
                $allocatedQty = $stockInUnit;
            }

            $sku = (string) ($it['sku'] ?? $prod?->sku ?? '');
            $barcode = (string) ($it['barcode'] ?? $prod?->barcode ?? '');
            $name = (string) ($it['name'] ?? $prod?->name ?? '');
            $category = (string) ($it['category_name'] ?? $prod?->category?->name ?? 'عام');

            if ($stockInUnit > 0) {
                $lineTotal = round($price * $allocatedQty, 2);
                $totalAvailQty += $allocatedQty;
                $totalAvailPieces += $totalStockPieces;
                $totalAvailAmount += $lineTotal;

                $availableRows[] = [
                    $availIdx++,
                    $sku,
                    $barcode,
                    $name,
                    $category,
                    $unit,
                    $price,
                    round($allocatedQty, 2),
                    (int) $totalStockPieces,
                    round($stockInUnit, 2),
                    $breakdown,
                    $lineTotal,
                ];
            } else {
                $zeroRows[] = [
                    $zeroIdx++,
                    $sku,
                    $barcode,
                    $name,
                    $category,
                    $unit,
                    $price,
                    0,
                    'غير متوفر حالياً بالمخزن ✕',
                ];
            }
        }

        // Add summary totals row to available sheet
        if (! empty($availableRows)) {
            $availableRows[] = [
                'الإجمالي العام',
                '',
                '',
                count($availableRows).' أصناف متاحة',
                '',
                '',
                '',
                $totalAvailQty,
                $totalAvailPieces,
                '',
                '',
                $totalAvailAmount,
            ];
        }

        $meta1 = [
            [$company->company_name ?: 'شركة كيان التجارية', 'بيان الأصناف المتاحة بالمخزن لطلب العميل: '.$customerName],
            ['رقم القائمة: '.$docNumber, 'تاريخ الإصدار: '.$docDate, 'المستودع: '.$warehouseName],
            ['عدد الأصناف المتاحة للتسليم الفوري: '.count($availableRows).' صنف'],
            [],
        ];

        $sheet1 = [
            'sheetTitle' => 'الأصناف المتاحة بالمخزن',
            'headers' => [
                'م',
                'كود الصنف (SKU)',
                'الباركود',
                'اسم المنتج / الصنف',
                'التصنيف',
                'الوحدة',
                'سعر الوحدة (ج.م)',
                'الكمية المعتمدة',
                'الرصيد المتاح (قطع)',
                'الرصيد المتاح بوحدة الصنف',
                'تفصيل الرصيد الفعلي بالمخزن',
                'إجمالي القيمة (ج.م)',
            ],
            'columnTypes' => [
                0 => 'number',
                1 => 'text', // Forced text for SKU
                2 => 'text', // Forced text (@) for Barcode (NEVER scientific)
                3 => 'text',
                4 => 'text',
                5 => 'text',
                6 => 'currency',
                7 => 'number',
                8 => 'number',
                9 => 'number',
                10 => 'text',
                11 => 'currency',
            ],
            'columnWidths' => [
                0 => 6,
                1 => 16,
                2 => 16,
                3 => 32,
                4 => 16,
                5 => 12,
                6 => 16,
                7 => 16,
                8 => 18,
                9 => 20,
                10 => 28,
                11 => 22,
            ],
            'rows' => $availableRows,
            'metaRows' => $meta1,
            'headerStyleId' => 7, // Green Header
        ];

        $meta2 = [
            [$company->company_name ?: 'شركة كيان التجارية', 'بيان الأصناف غير المتوفرة (رصيد صفر) لطلب العميل: '.$customerName],
            ['رقم القائمة: '.$docNumber, 'تاريخ الإصدار: '.$docDate, 'المستودع: '.$warehouseName],
            ['عدد الأصناف غير المتوفرة حالياً: '.count($zeroRows).' صنف'],
            [],
        ];

        $sheet2 = [
            'sheetTitle' => 'أصناف غير متوفرة (رصيد صفر)',
            'headers' => [
                'م',
                'كود الصنف (SKU)',
                'الباركود',
                'اسم المنتج / الصنف',
                'التصنيف',
                'الوحدة',
                'سعر الوحدة (ج.م)',
                'الرصيد الفعلي الحالي',
                'حالة التوفر',
            ],
            'columnTypes' => [
                0 => 'number',
                1 => 'text', // Forced text for SKU
                2 => 'text', // Forced text (@) for Barcode
                3 => 'text',
                4 => 'text',
                5 => 'text',
                6 => 'currency',
                7 => 'number',
                8 => 'badge_red',
            ],
            'columnWidths' => [
                0 => 6,
                1 => 16,
                2 => 16,
                3 => 32,
                4 => 16,
                5 => 12,
                6 => 16,
                7 => 20,
                8 => 26,
            ],
            'rows' => $zeroRows,
            'metaRows' => $meta2,
            'headerStyleId' => 8, // Red Header
        ];

        $xlsxContent = SimpleXlsxGenerator::createTwoSheetWorkbook($sheet1, $sheet2);

        $cleanFilename = 'customer_order_availability_'.preg_replace('/[^a-zA-Z0-9_\-]/', '_', $docNumber).'.xlsx';

        return response($xlsxContent, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$cleanFilename}\"",
        ]);
    }

    /**
     * Direct 2-sheet Excel export from a saved packing list model.
     */
    public function exportCustomerStockExcelModel(PackingList $packingList): Response
    {
        $packingList->load('items');
        $request = new Request([
            'doc_number' => $packingList->packing_number,
            'doc_date' => $packingList->packing_date ? $packingList->packing_date->format('Y-m-d') : date('Y-m-d'),
            'customer_name' => $packingList->customer_name,
            'warehouse_name' => $packingList->warehouse_name,
            'notes' => $packingList->notes,
            'items' => $packingList->items->map(function ($it) {
                return [
                    'id' => $it->product_id,
                    'sku' => $it->sku,
                    'barcode' => $it->barcode,
                    'name' => $it->product_name,
                    'category_name' => $it->category_name,
                    'unit' => $it->unit,
                    'price' => (float) $it->unit_price,
                    'qty' => (float) $it->quantity,
                ];
            })->toArray(),
        ]);

        return $this->exportCustomerStockExcel($request);
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
            'التصنيف',
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
            $packages = (int) ($item['packages_count'] ?? 0);
            $price = (float) ($item['price'] ?? 0);
            $lineTotal = (float) ($item['total'] ?? ($qty * $price));

            $row = [
                $idx++,
                $item['sku'] ?? '',
                $item['barcode'] ?? '',
                $item['name'] ?? '',
                $item['category_name'] ?? 'عام',
                $item['unit'] ?? 'قطعة',
            ];

            if ($showQty) {
                $row[] = number_format($qty, 2, '.', '');
            }
            if ($showPackages) {
                $row[] = $packages > 0 ? (int) $packages : 0;
            }
            if ($showPrice) {
                $row[] = number_format($price, 2, '.', '');
            }
            if ($showLineTotal) {
                $row[] = number_format($lineTotal, 2, '.', '');
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
