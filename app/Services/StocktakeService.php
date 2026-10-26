<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use ZipArchive;

class StocktakeService
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    /**
     * Generate prefilled stocktake CSV sheet for the selected warehouse.
     */
    public function generateStocktakeTemplate(Warehouse $warehouse): string
    {
        $headers = [
            'كود المستودع',
            'اسم المستودع',
            'كود الصنف (SKU)',
            'الباركود',
            'اسم المنتج',
            'التصنيف',
            'الوحدة',
            'رصيد الدفتر (النظام)',
            'الرصيد الفعلي بعد الجرد (أدخل الكمية هنا)',
            'ملاحظات الجرد (اختياري)',
        ];

        // Fetch products with their current stock balance in this warehouse
        $products = Product::query()
            ->with(['category'])
            ->with(['stockBalances' => function ($q) use ($warehouse) {
                $q->where('warehouse_id', $warehouse->id);
            }])
            ->orderBy('name')
            ->get();

        $rows = [];
        foreach ($products as $p) {
            $bookBalance = (float) ($p->stockBalances->first()?->quantity ?? 0.0);

            $rows[] = [
                $warehouse->code,
                $warehouse->name,
                $p->sku,
                $p->barcode ?: '',
                $p->name,
                $p->category?->name ?: 'عام',
                $p->unit ?: 'قطعة',
                $bookBalance,
                '', // Blank for physical count entry
                '',
            ];
        }

        $output = "\xEF\xBB\xBF";
        $handle = fopen('php://memory', 'r+');

        fputcsv($handle, $headers);
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $output .= stream_get_contents($handle);
        fclose($handle);

        return $output;
    }

    /**
     * Parse and validate physical count sheet against real-time book balances.
     */
    public function parseAndValidateStocktake(UploadedFile $file, Warehouse $warehouse): array
    {
        $rawRows = $this->parseFile($file);
        if (empty($rawRows)) {
            return [
                'success' => false,
                'message' => 'الملف المرفوع فارغ ولا يحتوي على أي بيانات.',
                'discrepancies' => [],
                'matched' => [],
                'invalid_rows' => [],
                'summary' => [],
            ];
        }

        $headerRow = array_shift($rawRows);
        $colMap = $this->mapHeaders($headerRow);

        if (! isset($colMap['sku']) && ! isset($colMap['barcode'])) {
            return [
                'success' => false,
                'message' => 'الملف لا يحتوي على عمود كود الصنف (SKU) أو الباركود للتعرف على المنتجات.',
                'discrepancies' => [],
                'matched' => [],
                'invalid_rows' => [],
                'summary' => [],
            ];
        }

        if (! isset($colMap['counted_quantity'])) {
            return [
                'success' => false,
                'message' => 'الملف لا يحتوي على عمود الرصيد الفعلي بعد الجرد.',
                'discrepancies' => [],
                'matched' => [],
                'invalid_rows' => [],
                'summary' => [],
            ];
        }

        $allProducts = Product::with(['stockBalances' => function ($q) use ($warehouse) {
            $q->where('warehouse_id', $warehouse->id);
        }])->get();

        $skuMap = $allProducts->keyBy('sku');
        $barcodeMap = $allProducts->whereNotNull('barcode')->where('barcode', '!=', '')->keyBy('barcode');

        $discrepancies = [];
        $matched = [];
        $invalidRows = [];

        $totalSurplusCost = 0.0;
        $totalDeficitCost = 0.0;

        foreach ($rawRows as $idx => $row) {
            $rowNum = $idx + 2;

            // Check non-empty
            $hasContent = false;
            foreach ($row as $v) {
                if (trim((string) $v) !== '') {
                    $hasContent = true;
                    break;
                }
            }
            if (! $hasContent) {
                continue;
            }

            $sku = isset($colMap['sku']) ? trim((string) ($row[$colMap['sku']] ?? '')) : '';
            $barcode = isset($colMap['barcode']) ? trim((string) ($row[$colMap['barcode']] ?? '')) : '';
            $countedRaw = isset($colMap['counted_quantity']) ? trim((string) ($row[$colMap['counted_quantity']] ?? '')) : '';
            $notes = isset($colMap['notes']) ? trim((string) ($row[$colMap['notes']] ?? '')) : '';

            // If counted cell is blank, skip this item
            if ($countedRaw === '') {
                continue;
            }

            // Find product
            $product = null;
            if ($sku !== '' && isset($skuMap[$sku])) {
                $product = $skuMap[$sku];
            } elseif ($barcode !== '' && isset($barcodeMap[$barcode])) {
                $product = $barcodeMap[$barcode];
            }

            if (! $product) {
                $invalidRows[] = [
                    'row_number' => $rowNum,
                    'sku' => $sku ?: '—',
                    'barcode' => $barcode ?: '—',
                    'error' => 'لم يتم العثور على صنف مسجل في النظام بهذا الكود أو الباركود.',
                ];

                continue;
            }

            // Parse numerical count
            $countedQty = $this->parseNumber($countedRaw);
            if ($countedQty === null || $countedQty < 0) {
                $invalidRows[] = [
                    'row_number' => $rowNum,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'error' => "الكمية الفعلية المدخلة ({$countedRaw}) غير صالحة أو سالبة. يجب أن تكون رقماً أكبر من أو يساوي الصفر.",
                ];

                continue;
            }

            $bookBalance = (float) ($product->stockBalances->first()?->quantity ?? 0.0);
            $diff = round($countedQty - $bookBalance, 4);
            $costPrice = (float) ($product->base_cost_price ?? $product->cost_price);
            $costImpact = round($diff * $costPrice, 2);

            $entry = [
                'row_number' => $rowNum,
                'product_id' => $product->id,
                'product_sku' => $product->sku,
                'product_barcode' => $product->barcode,
                'product_name' => $product->name,
                'unit' => $product->unit ?: 'قطعة',
                'cost_price' => $costPrice,
                'book_balance' => $bookBalance,
                'counted_quantity' => $countedQty,
                'difference' => $diff,
                'cost_impact' => $costImpact,
                'status' => $diff > 0 ? 'surplus' : ($diff < 0 ? 'deficit' : 'match'),
                'notes' => $notes ?: null,
            ];

            if ($diff != 0.0) {
                if ($diff > 0) {
                    $totalSurplusCost += $costImpact;
                } else {
                    $totalDeficitCost += abs($costImpact);
                }
                $discrepancies[] = $entry;
            } else {
                $matched[] = $entry;
            }
        }

        return [
            'success' => true,
            'warehouse' => $warehouse,
            'discrepancies' => $discrepancies,
            'matched' => $matched,
            'invalid_rows' => $invalidRows,
            'summary' => [
                'total_processed' => count($discrepancies) + count($matched),
                'discrepancies_count' => count($discrepancies),
                'matched_count' => count($matched),
                'invalid_count' => count($invalidRows),
                'surplus_items_count' => count(array_filter($discrepancies, fn ($d) => $d['difference'] > 0)),
                'deficit_items_count' => count(array_filter($discrepancies, fn ($d) => $d['difference'] < 0)),
                'total_surplus_cost' => round($totalSurplusCost, 2),
                'total_deficit_cost' => round($totalDeficitCost, 2),
                'net_cost_impact' => round($totalSurplusCost - $totalDeficitCost, 2),
            ],
        ];
    }

    /**
     * Atomically commit approved stocktake adjustments into inventory balances and FIFO logs.
     */
    public function commitStocktakeAdjustments(
        Warehouse $warehouse,
        array $discrepancies,
        ?string $reason = null,
        ?int $userId = null
    ): array {
        return DB::transaction(function () use ($warehouse, $discrepancies, $reason, $userId) {
            $appliedCount = 0;
            $userId = $userId ?? auth()->id();
            $baseReason = $reason ?: 'تسوية جرد دوري معتمد للمستودع ('.$warehouse->name.')';

            foreach ($discrepancies as $item) {
                $productId = (int) $item['product_id'];
                $countedQty = (float) $item['counted_quantity'];
                $rowNote = $item['notes'] ? " [{$item['notes']}]" : '';

                $this->inventoryService->adjustStock(
                    productId: $productId,
                    warehouseId: $warehouse->id,
                    actualQuantity: $countedQty,
                    reason: $baseReason.$rowNote,
                    userId: $userId
                );

                $appliedCount++;
            }

            return [
                'success' => true,
                'applied_count' => $appliedCount,
                'message' => "تم اعتماد وتسجيل تسويات الجرد لعدد {$appliedCount} صنفاً بنجاح وتحديث أرصدة المستودع وقيود FIFO.",
            ];
        });
    }

    /**
     * Get detailed warehouse stock breakdown with secondary units and batch details.
     */
    public function getWarehouseStockBreakdown(Warehouse $warehouse, ?string $search = null, ?int $categoryId = null): array
    {
        $query = Product::query()
            ->with(['category'])
            ->with(['stockBalances' => function ($q) use ($warehouse) {
                $q->where('warehouse_id', $warehouse->id);
            }])
            ->with(['inventoryBatches' => function ($q) use ($warehouse) {
                $q->where('warehouse_id', $warehouse->id)
                    ->where('is_exhausted', false)
                    ->orderBy('created_at');
            }]);

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        if ($search) {
            $query->search($search);
        }

        $products = $query->orderBy('name')->get();

        $rows = [];
        $totalQuantity = 0.0;
        $totalCostValuation = 0.0;

        foreach ($products as $p) {
            $bookBalance = (float) ($p->stockBalances->first()?->quantity ?? 0.0);
            $costPrice = (float) ($p->base_cost_price ?? $p->cost_price);
            $rowValuation = round($bookBalance * $costPrice, 2);

            $totalQuantity += $bookBalance;
            $totalCostValuation += $rowValuation;

            // Secondary / Packaged unit breakdown
            $packageBreakdown = '—';
            $piecesPerPkg = (int) ($p->pieces_per_package ?? 1);
            if ($piecesPerPkg > 1 && $bookBalance > 0) {
                $packages = floor($bookBalance / $piecesPerPkg);
                $remPieces = (int) round(fmod($bookBalance, $piecesPerPkg));
                if ($packages > 0 && $remPieces > 0) {
                    $packageBreakdown = "{$packages} طرد/كرتونة + {$remPieces} قطعة";
                } elseif ($packages > 0) {
                    $packageBreakdown = "{$packages} طرد/كرتونة بالكامل";
                } else {
                    $packageBreakdown = "{$remPieces} قطعة فقط";
                }
            }

            // Batch details
            $batches = [];
            foreach ($p->inventoryBatches as $batch) {
                $batches[] = [
                    'id' => $batch->id,
                    'batch_number' => $batch->batch_number,
                    'quantity' => (float) $batch->quantity_remaining,
                    'cost_price' => (float) $batch->cost_price,
                    'created_at' => $batch->created_at ? $batch->created_at->format('Y-m-d') : null,
                ];
            }

            $rows[] = [
                'product' => $p,
                'id' => $p->id,
                'sku' => $p->sku,
                'barcode' => $p->barcode,
                'name' => $p->name,
                'category_name' => $p->category?->name ?: 'عام',
                'base_unit' => $p->unit ?: 'قطعة',
                'pieces_per_package' => $piecesPerPkg,
                'book_balance' => $bookBalance,
                'package_breakdown' => $packageBreakdown,
                'cost_price' => $costPrice,
                'valuation' => $rowValuation,
                'batches' => $batches,
            ];
        }

        return [
            'warehouse' => $warehouse,
            'items' => $rows,
            'items_count' => count($rows),
            'total_quantity' => round($totalQuantity, 4),
            'total_valuation' => round($totalCostValuation, 2),
        ];
    }

    /**
     * Map headers to known keys.
     */
    protected function mapHeaders(array $headers): array
    {
        $map = [];
        $aliases = [
            'sku' => ['sku', 'كود الصنف', 'كود المنتج', 'الرمز', 'code', 'الكود'],
            'barcode' => ['barcode', 'الباركود', 'باركود'],
            'counted_quantity' => ['counted_quantity', 'الرصيد الفعلي', 'الكمية الفعلية', 'الفعلية', 'الجرد الفعلي', 'الرصيد الفعلي بعد الجرد'],
            'notes' => ['notes', 'ملاحظات', 'بيان', 'ملاحظات الجرد'],
        ];

        foreach ($headers as $idx => $headerText) {
            $clean = mb_strtolower(trim((string) $headerText));
            $clean = preg_replace('/[\(\)\*\#\-]/u', '', $clean);

            foreach ($aliases as $key => $names) {
                if (isset($map[$key])) {
                    continue;
                }
                foreach ($names as $name) {
                    $cleanName = mb_strtolower(preg_replace('/[\(\)\*\#\-]/u', '', $name));
                    if (str_contains($clean, $cleanName)) {
                        $map[$key] = $idx;
                        break;
                    }
                }
            }
        }

        return $map;
    }

    /**
     * Parse floating number from diverse inputs.
     */
    protected function parseNumber(mixed $val): ?float
    {
        if ($val === null) {
            return null;
        }
        $str = trim((string) $val);
        if ($str === '') {
            return null;
        }

        $str = str_replace(
            ["\u{0660}", "\u{0661}", "\u{0662}", "\u{0663}", "\u{0664}", "\u{0665}", "\u{0666}", "\u{0667}", "\u{0668}", "\u{0669}"],
            ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
            $str
        );
        $str = str_replace(',', '', $str);

        if (! is_numeric($str)) {
            return null;
        }

        return (float) $str;
    }

    /**
     * Parse CSV or XLSX.
     */
    protected function parseFile(UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension());
        if (in_array($ext, ['csv', 'txt'], true)) {
            return $this->readCsv($file->getRealPath());
        }
        if (in_array($ext, ['xlsx'], true)) {
            return $this->readXlsx($file->getRealPath());
        }

        throw new \InvalidArgumentException('صيغة الملف غير مدعومة. يرجى رفع ملف Excel (.xlsx) أو CSV (.csv).');
    }

    protected function readCsv(string $path): array
    {
        $content = file_get_contents($path);
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        }
        $lines = preg_split('/\r\n|\r|\n/', trim($content));
        $rows = [];
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $rows[] = str_getcsv($line);
        }

        return $rows;
    }

    protected function readXlsx(string $path): array
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('تعذر فتح ملف Excel.');
        }

        $sharedStrings = [];
        $stringsXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($stringsXml !== false) {
            $xml = simplexml_load_string($stringsXml);
            if ($xml) {
                foreach ($xml->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = (string) $si->t;
                    } elseif (isset($si->r)) {
                        $t = '';
                        foreach ($si->r as $r) {
                            $t .= (string) $r->t;
                        }
                        $sharedStrings[] = $t;
                    } else {
                        $sharedStrings[] = '';
                    }
                }
            }
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetXml === false) {
            $zip->close();
            throw new \RuntimeException('لم يتم العثور على ورقة عمل صالحة في ملف Excel.');
        }
        $xml = simplexml_load_string($sheetXml);
        $zip->close();

        if (! $xml || ! isset($xml->sheetData)) {
            return [];
        }

        $rows = [];
        foreach ($xml->sheetData->row as $row) {
            $rowData = [];
            foreach ($row->c as $c) {
                $val = '';
                $type = (string) $c['t'];
                if ($type === 's' && isset($c->v)) {
                    $val = $sharedStrings[(int) $c->v] ?? '';
                } elseif (isset($c->v)) {
                    $val = (string) $c->v;
                }
                $rowData[] = $val;
            }
            $rows[] = $rowData;
        }

        return $rows;
    }
}
