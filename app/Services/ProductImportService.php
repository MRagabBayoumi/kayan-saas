<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ZipArchive;

class ProductImportService
{
    /**
     * Standard column mapping dictionary supporting both Arabic and English headers.
     */
    protected array $headerAliases = [
        'sku' => ['sku', 'كود الصنف', 'كود المنتج', 'الرمز', 'code', 'الكود'],
        'name' => ['name', 'اسم المنتج', 'اسم الصنف', 'الاسم', 'title', 'الصنف'],
        'category' => ['category', 'category_name', 'التصنيف', 'القسم', 'الفئة', 'المجموعة'],
        'barcode' => ['barcode', 'الباركود', 'باركود', 'كود الباركود'],
        'cost_price' => ['cost_price', 'cost', 'سعر التكلفة', 'التكلفة', 'سعر الشراء', 'تكلفة'],
        'wholesale_price' => ['wholesale_price', 'wholesale', 'سعر الجملة', 'الجملة', 'سعر بيع الجملة'],
        'retail_price' => ['retail_price', 'retail', 'سعر التجزئة', 'القطاعي', 'سعر البيع', 'التجزئة', 'سعر بيع التجزئة'],
        'distributor_price' => ['distributor_price', 'distributor', 'سعر الموزع', 'الموزع', 'سعر كبار الموزعين', 'سعر الوكيل', 'سعر بيع الموزع'],
        'unit' => ['unit', 'الوحدة', 'وحدة القياس', 'نوع الوحدة'],
        'pieces_per_package' => ['pieces_per_package', 'package_pieces', 'pieces', 'عدد القطع في الطرد', 'التعبئة', 'القطع بالكرتونة', 'قطع الكرتونة', 'قطع الطرد', 'تعبئة الطرد', 'القطع في الكرتونة'],
        'min_stock_alert' => ['min_stock_alert', 'حد الطلب', 'الحد الأدنى', 'حد النواقص', 'تنبيه النواقص', 'الحد الادنى'],
        'opening_quantity' => ['opening_quantity', 'opening_stock', 'initial_quantity', 'الكمية الافتتاحية', 'رصيد أول المدة', 'رصيد اول المدة', 'الرصيد الافتتاحي', 'كمية أول المدة', 'كمية اول المدة'],
        'opening_cost' => ['opening_cost', 'initial_cost', 'تكلفة الرصيد الافتتاحي', 'تكلفة اول المدة', 'تكلفة أول المدة', 'تكلفة الرصيد الأولي'],
        'opening_warehouse' => ['opening_warehouse', 'warehouse', 'warehouse_name', 'المستودع', 'المخزن', 'المستودع المستلم', 'اسم المستودع', 'اسم المخزن'],
        'opening_balance_date' => ['opening_balance_date', 'opening_date', 'تاريخ الرصيد الافتتاحي', 'تاريخ أول المدة', 'تاريخ اول المدة', 'تاريخ الرصيد', 'تاريخ الرصيد الأولي'],
    ];

    public function __construct(
        protected ?InventoryService $inventoryService = null
    ) {}

    /**
     * Clean and parse any numerical input into a standard float.
     * Supports Arabic numerals (٠-٩), Persian numerals (۰-۹), Arabic comma (،),
     * Arabic decimal mark (٫), European comma decimals (160,50), thousand separators,
     * currency symbols (ج.م, EGP), spaces, and scientific notation.
     */
    public function parseDecimal(mixed $value, ?float $default = null): ?float
    {
        if ($value === null) {
            return $default;
        }

        $str = trim((string) $value);
        if ($str === '') {
            return $default;
        }

        // 1. Remove currency symbols, abbreviations, and common currency words
        $str = preg_replace('/(ج\.م\.?|ج\s*م|جنيه\s*مصري|جنيه|egp|l\.e\.?|le|usd|\$|eur|€|sar|ر\.س|aed|د\.إ)/iu', '', $str);

        // 2. Convert Eastern Arabic (٠-٩) and Persian (۰-۹) digits to Western digits (0-9)
        $str = str_replace(
            ["\u{0660}", "\u{0661}", "\u{0662}", "\u{0663}", "\u{0664}", "\u{0665}", "\u{0666}", "\u{0667}", "\u{0668}", "\u{0669}"],
            ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
            $str
        );
        $str = str_replace(
            ["\u{06F0}", "\u{06F1}", "\u{06F2}", "\u{06F3}", "\u{06F4}", "\u{06F5}", "\u{06F6}", "\u{06F7}", "\u{06F8}", "\u{06F9}"],
            ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
            $str
        );

        // 3. Normalize Arabic decimal and thousands markers
        $str = str_replace(["\u{066B}", "\u{060C}", "\u{066C}"], ['.', ',', ''], $str);

        // 4. Remove spaces, non-breaking spaces, and non-numeric chars except digits, comma, dot, minus, plus, e, E
        $str = preg_replace('/[^\d.,\-+eE]/u', '', $str);
        $str = trim($str, " \t\n\r\0\x0B.");
        if ($str === '' || $str === '-' || $str === '+') {
            return $default;
        }

        // 5. Handle thousands vs decimal separators
        $hasDot = str_contains($str, '.');
        $hasComma = str_contains($str, ',');

        if ($hasDot && $hasComma) {
            $lastDot = strrpos($str, '.');
            $lastComma = strrpos($str, ',');
            if ($lastDot > $lastComma) {
                // Comma is thousands separator e.g. "1,250.50"
                $str = str_replace(',', '', $str);
            } else {
                // Dot is thousands separator e.g. "1.250,50"
                $str = str_replace('.', '', $str);
                $str = str_replace(',', '.', $str);
            }
        } elseif ($hasComma && ! $hasDot) {
            // Single comma used as decimal separator e.g. "160,50"
            $str = str_replace(',', '.', $str);
        } elseif ($hasDot && ! $hasComma && substr_count($str, '.') > 1) {
            // Multiple dots e.g. "1.250.50" -> keep only last dot as decimal
            $lastDot = strrpos($str, '.');
            $intPart = str_replace('.', '', substr($str, 0, $lastDot));
            $decPart = substr($str, $lastDot + 1);
            $str = $intPart.'.'.$decPart;
        }

        if (! is_numeric($str)) {
            return $default;
        }

        return (float) $str;
    }

    /**
     * Clean and parse integer values.
     */
    public function parseInteger(mixed $value, ?int $default = null): ?int
    {
        $float = $this->parseDecimal($value, null);

        return $float !== null ? (int) round($float) : $default;
    }

    /**
     * Clean and sanitize numeric strings like barcodes or SKUs from Excel.
     * Converts float representations (10280.0) or scientific notation (1.028E+4) to pure integer string "10280".
     */
    public function sanitizeIntegerString(mixed $val): string
    {
        if ($val === null) {
            return '';
        }

        $str = trim((string) $val);
        if ($str === '') {
            return '';
        }

        // Remove spaces, non-breaking spaces, and commas
        $str = str_replace([' ', "\xc2\xa0", ','], '', $str);

        // If scientific notation e.g. "1.0284E+4" or "1.0284e4"
        if (preg_match('/^[+-]?\d+(?:\.\d+)?[eE][+-]?\d+$/', $str)) {
            return (string) number_format((float) $str, 0, '', '');
        }

        // If ends with .0, .00, .0000 e.g. "10280.0" -> "10280"
        if (preg_match('/^([+-]?\d+)\.0+$/', $str, $m)) {
            return $m[1];
        }

        // If it's a numeric float that represents an integer e.g. "10280.00"
        if (is_numeric($str) && floor((float) $str) == (float) $str) {
            return (string) (int) round((float) $str);
        }

        return $str;
    }

    /**
     * Convert Excel column letters (A, B, Z, AA, AB...) into a 0-based column index.
     */
    protected function columnLetterToIndex(string $col): int
    {
        $col = strtoupper($col);
        $len = strlen($col);
        $index = 0;
        for ($i = 0; $i < $len; $i++) {
            $index = $index * 26 + (ord($col[$i]) - 64);
        }

        return max(0, $index - 1);
    }

    /**
     * Generate standard CSV template content with UTF-8 BOM for perfect Excel Arabic rendering.
     * Contains all product fields including distributor price, packaging, and opening stock balance.
     */
    public function generateTemplateCsv(): string
    {
        $headers = [
            'كود الصنف (SKU)',
            'اسم المنتج',
            'التصنيف',
            'الباركود (اختياري)',
            'سعر التكلفة (ج.م)',
            'سعر الجملة (ج.م)',
            'سعر التجزئة (ج.م)',
            'سعر الموزع (اختياري)',
            'وحدة القياس',
            'عدد القطع في الطرد',
            'حد الطلب الأدنى',
            'الكمية الافتتاحية (رصيد أول المدة)',
            'تكلفة الرصيد الافتتاحي',
            'المستودع المستلم',
            'تاريخ الرصيد الافتتاحي',
        ];

        $sampleRows = [
            [
                'KY-26-1001',
                'توكة شعر لؤلؤ أبيض كوري فاخر',
                'توك شعر',
                '6920011223344',
                '14.50',
                '25.00',
                '40.00',
                '22.50',
                'دستة',
                '12',
                '10',
                '50.00',
                '14.50',
                'المستودع الرئيسي',
                date('Y-m-d'),
            ],
            [
                'KY-26-1002',
                'طوق شعر ستان كلاسيك وردي',
                'أطواق وبكلات',
                '6920011223355',
                '18.75',
                '32.50',
                '50.00',
                '29.00',
                'قطعة',
                '24',
                '5',
                '100.00',
                '18.75',
                'المستودع الرئيسي',
                date('Y-m-d'),
            ],
            [
                'KY-26-1003',
                'بكلة شعر فراشة مرصعة كريستال',
                'بكلات كريستال',
                '',
                '11.25',
                '20.00',
                '35.00',
                '18.00',
                'قطعة',
                '1',
                '12',
                '0.00',
                '',
                '',
                '',
            ],
        ];

        // Output with UTF-8 BOM so Microsoft Excel opens Arabic text cleanly without distortion
        $output = "\xEF\xBB\xBF";
        $handle = fopen('php://memory', 'r+');

        fputcsv($handle, $headers);
        foreach ($sampleRows as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $output .= stream_get_contents($handle);
        fclose($handle);

        return $output;
    }

    /**
     * Parse uploaded file (CSV or XLSX) into normalized associative rows.
     */
    public function parseFile(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension === 'xlsx') {
            return $this->parseXlsx($file->getRealPath());
        }

        return $this->parseCsv($file->getRealPath());
    }

    /**
     * Parse CSV file with auto-delimiter detection and UTF-8 handling.
     */
    protected function parseCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if (! $handle) {
            return [];
        }

        // Read first line to detect BOM and delimiter
        $firstLine = fgets($handle);
        if (! $firstLine) {
            fclose($handle);

            return [];
        }

        // Strip UTF-8 BOM if present
        $firstLine = preg_replace('/^\xEF\xBB\xBF/', '', $firstLine);

        // Detect delimiter: comma, semicolon, tab
        $delimiters = [',', ';', "\t"];
        $chosenDelimiter = ',';
        $maxCount = 0;
        foreach ($delimiters as $delim) {
            $count = substr_count($firstLine, $delim);
            if ($count > $maxCount) {
                $maxCount = $count;
                $chosenDelimiter = $delim;
            }
        }

        rewind($handle);
        // Strip BOM from stream if present
        $bomCheck = fread($handle, 3);
        if ($bomCheck !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $rawHeaders = fgetcsv($handle, 0, $chosenDelimiter);
        if (! $rawHeaders) {
            fclose($handle);

            return [];
        }

        $headerMap = $this->mapHeaders($rawHeaders);
        $rows = [];
        $lineNum = 1;

        while (($data = fgetcsv($handle, 0, $chosenDelimiter)) !== false) {
            $lineNum++;
            // Skip empty rows
            if (empty(array_filter($data, fn ($v) => trim((string) $v) !== ''))) {
                continue;
            }

            $rows[] = $this->extractRowData($data, $headerMap, $lineNum);
        }

        fclose($handle);

        return $rows;
    }

    /**
     * Native XLSX parsing preserving sparse cell coordinates and column positions.
     */
    protected function parseXlsx(string $path): array
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            return $this->parseCsv($path); // Fallback to CSV reader
        }

        // 1. Read shared strings
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

        // 2. Read sheet1
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
        $consecutiveEmpty = 0;

        foreach ($sheetXml->sheetData->row as $row) {
            $rowValues = [];
            $maxColIdx = 0;
            $hasCellData = false;

            foreach ($row->c as $cell) {
                $cellRef = (string) $cell['r'];
                $colIndex = 0;

                // Fast column letter extraction without regex overhead
                $len = strlen($cellRef);
                $colLetters = '';
                for ($i = 0; $i < $len; $i++) {
                    $ch = $cellRef[$i];
                    if ($ch >= 'A' && $ch <= 'Z') {
                        $colLetters .= $ch;
                    } else {
                        break;
                    }
                }

                if ($colLetters !== '') {
                    $colIndex = $this->columnLetterToIndex($colLetters);
                } else {
                    $colIndex = count($rowValues);
                }

                if ($colIndex > 40) {
                    continue; // Skip out-of-bounds phantom columns
                }

                $maxColIdx = max($maxColIdx, $colIndex);
                $cellType = (string) $cell['t'];
                $val = '';

                if ($cellType === 's') {
                    $sIndex = (int) $cell->v;
                    $val = isset($sharedStrings[$sIndex]) ? $sharedStrings[$sIndex] : '';
                } elseif ($cellType === 'inlineStr' && isset($cell->is->t)) {
                    $val = (string) $cell->is->t;
                } else {
                    $val = (string) $cell->v;
                }

                $valTrim = trim($val);
                if ($valTrim !== '') {
                    $hasCellData = true;
                }

                $rowValues[$colIndex] = $val;
            }

            if ($hasCellData) {
                $consecutiveEmpty = 0;
                // Fill missing cells to maintain strict column alignment
                $fullRow = [];
                for ($c = 0; $c <= $maxColIdx; $c++) {
                    $fullRow[$c] = isset($rowValues[$c]) ? $rowValues[$c] : '';
                }
                $grid[] = $fullRow;
            } else {
                $consecutiveEmpty++;
                // If table data has already started and we encounter 15 consecutive empty rows, stop scanning phantom Excel rows
                if (! empty($grid) && $consecutiveEmpty >= 15) {
                    break;
                }
            }
        }

        if (empty($grid)) {
            return [];
        }

        $rawHeaders = array_shift($grid);
        $headerMap = $this->mapHeaders($rawHeaders);
        $rows = [];
        $lineNum = 1;

        foreach ($grid as $data) {
            $lineNum++;
            $rows[] = $this->extractRowData($data, $headerMap, $lineNum);
        }

        return $rows;
    }

    /**
     * Map header column indexes to normalized property keys.
     */
    protected function mapHeaders(array $rawHeaders): array
    {
        $map = [];

        foreach ($rawHeaders as $idx => $header) {
            $cleanHeader = trim(mb_strtolower((string) $header));
            // Remove parenthetical notes e.g. "كود الصنف (SKU)" -> "كود الصنف"
            $normalized = trim(preg_replace('/\s*\(.*?\)\s*/u', '', $cleanHeader));

            foreach ($this->headerAliases as $standardKey => $aliases) {
                foreach ($aliases as $alias) {
                    if ($cleanHeader === $alias || $normalized === $alias || Str::contains($cleanHeader, $alias)) {
                        if (! isset($map[$standardKey])) {
                            $map[$standardKey] = $idx;
                            break 2;
                        }
                    }
                }
            }
        }

        return $map;
    }

    /**
     * Extract normalized row data using the header map.
     */
    protected function extractRowData(array $data, array $headerMap, int $lineNum): array
    {
        $rawBarcode = isset($headerMap['barcode']) && isset($data[$headerMap['barcode']]) ? trim((string) $data[$headerMap['barcode']]) : null;
        if ($rawBarcode !== null && $rawBarcode !== '') {
            $rawBarcode = $this->sanitizeIntegerString($rawBarcode);
        }

        $rawSku = isset($headerMap['sku']) && isset($data[$headerMap['sku']]) ? trim((string) $data[$headerMap['sku']]) : null;
        if ($rawSku !== null && $rawSku !== '') {
            $rawSku = $this->sanitizeIntegerString($rawSku);
        }

        $rawPieces = isset($headerMap['pieces_per_package']) && isset($data[$headerMap['pieces_per_package']]) ? trim((string) $data[$headerMap['pieces_per_package']]) : null;
        $pieces = null;
        if ($rawPieces !== null && $rawPieces !== '') {
            $pieces = $this->parseInteger($rawPieces);
        }

        $rawMinAlert = isset($headerMap['min_stock_alert']) && isset($data[$headerMap['min_stock_alert']]) ? trim((string) $data[$headerMap['min_stock_alert']]) : '5';
        $minAlert = $this->parseInteger($rawMinAlert, 5);

        $rawOpeningQty = isset($headerMap['opening_quantity']) && isset($data[$headerMap['opening_quantity']]) ? trim((string) $data[$headerMap['opening_quantity']]) : '0';
        if ($rawOpeningQty !== '') {
            $rawOpeningQty = $this->sanitizeIntegerString($rawOpeningQty);
        }

        return [
            '_line_number' => $lineNum,
            'sku' => $rawSku,
            'name' => isset($headerMap['name']) && isset($data[$headerMap['name']]) ? trim((string) $data[$headerMap['name']]) : null,
            'category' => isset($headerMap['category']) && isset($data[$headerMap['category']]) ? trim((string) $data[$headerMap['category']]) : null,
            'barcode' => $rawBarcode,
            'cost_price' => isset($headerMap['cost_price']) && isset($data[$headerMap['cost_price']]) ? trim((string) $data[$headerMap['cost_price']]) : '0',
            'wholesale_price' => isset($headerMap['wholesale_price']) && isset($data[$headerMap['wholesale_price']]) ? trim((string) $data[$headerMap['wholesale_price']]) : '0',
            'retail_price' => isset($headerMap['retail_price']) && isset($data[$headerMap['retail_price']]) ? trim((string) $data[$headerMap['retail_price']]) : '0',
            'distributor_price' => isset($headerMap['distributor_price']) && isset($data[$headerMap['distributor_price']]) ? trim((string) $data[$headerMap['distributor_price']]) : null,
            'unit' => isset($headerMap['unit']) && isset($data[$headerMap['unit']]) && trim((string) $data[$headerMap['unit']]) !== '' ? trim((string) $data[$headerMap['unit']]) : 'قطعة',
            'pieces_per_package' => $pieces,
            'min_stock_alert' => $minAlert,
            'opening_quantity' => $rawOpeningQty,
            'opening_cost' => isset($headerMap['opening_cost']) && isset($data[$headerMap['opening_cost']]) ? trim((string) $data[$headerMap['opening_cost']]) : null,
            'opening_warehouse' => isset($headerMap['opening_warehouse']) && isset($data[$headerMap['opening_warehouse']]) ? trim((string) $data[$headerMap['opening_warehouse']]) : null,
            'opening_balance_date' => isset($headerMap['opening_balance_date']) && isset($data[$headerMap['opening_balance_date']]) ? trim((string) $data[$headerMap['opening_balance_date']]) : null,
        ];
    }

    public function auditRows(array $parsedRows): array
    {
        $audited = [];
        $existingSkus = [];
        $existingBarcodes = [];
        $allProducts = Product::select('id', 'name', 'sku', 'barcode', 'cost_price', 'wholesale_price', 'retail_price', 'distributor_price')
            ->get()
            ->keyBy('id');

        foreach ($allProducts as $p) {
            if (! empty($p->sku)) {
                $existingSkus[mb_strtolower(trim($p->sku))] = $p->id;
            }
            if (! empty($p->barcode)) {
                $cleanBc = trim((string) $p->barcode);
                if ($cleanBc !== '') {
                    $existingBarcodes[$cleanBc] = $p->id;
                    $cleanBcSanitized = $this->sanitizeIntegerString($cleanBc);
                    if ($cleanBcSanitized !== '') {
                        $existingBarcodes[$cleanBcSanitized] = $p->id;
                    }
                }
            }
        }
        $warehouses = Warehouse::all();
        if ($warehouses->isEmpty()) {
            $createdWh = Warehouse::create([
                'code' => 'WH-1',
                'name' => 'المستودع الرئيسي',
                'location' => 'المقر الرئيسي',
                'is_active' => true,
                'is_default' => true,
            ]);
            $warehouses = collect([$createdWh]);
        }
        $defaultWarehouse = $warehouses->firstWhere('is_default', true) ?? $warehouses->first();
        $warehousesByName = [];
        foreach ($warehouses as $wh) {
            $warehousesByName[mb_strtolower(trim($wh->name))] = $wh;
            $warehousesByName[mb_strtolower(trim($wh->code))] = $wh;
            // Also alias common Arabic variations
            if (str_contains($wh->name, 'مستودع') || str_contains($wh->name, 'مخزن')) {
                $altName1 = str_replace('مستودع', 'مخزن', $wh->name);
                $altName2 = str_replace('مخزن', 'مستودع', $wh->name);
                $warehousesByName[mb_strtolower(trim($altName1))] = $wh;
                $warehousesByName[mb_strtolower(trim($altName2))] = $wh;
            }
        }

        $stats = [
            'total' => count($parsedRows),
            'new_count' => 0,
            'update_count' => 0,
            'invalid_count' => 0,
        ];

        $seenInFileSkus = [];
        $seenInFileBarcodes = [];

        foreach ($parsedRows as $row) {
            $line = $row['_line_number'];
            $errors = [];
            $action = 'new';
            $existingProduct = null;
            $skuAdjusted = false;
            $barcodeGenerated = false;

            // 1. Validate Product Name
            if (empty($row['name'])) {
                $errors[] = "السطر {$line}: اسم المنتج مفقود ولا يمكن تركه فارغاً.";
            }

            // 2. Extract & Sanitize Barcode and SKU (Strip floats like .0 and scientific notation)
            $barcode = ! empty($row['barcode']) ? $this->sanitizeIntegerString($row['barcode']) : null;
            $sku = ! empty($row['sku']) ? $this->sanitizeIntegerString($row['sku']) : null;
            $barcodeKey = $barcode !== null ? trim($barcode) : '';
            $skuKey = $sku !== null ? mb_strtolower(trim($sku)) : '';

            // Check Barcode uniqueness inside this uploaded file
            if ($barcode) {
                if (isset($seenInFileBarcodes[$barcodeKey])) {
                    $errors[] = "السطر {$line}: الباركود '{$barcode}' مكرر داخل نفس الملف.";
                } else {
                    $seenInFileBarcodes[$barcodeKey] = true;
                }
            }

            // 3. Validate Prices & Numerical Quantities (robust float & integer parsing)
            $costPrice = $this->parseDecimal($row['cost_price'], -1.0);
            $wholesalePrice = $this->parseDecimal($row['wholesale_price'], -1.0);
            $retailPrice = $this->parseDecimal($row['retail_price'], -1.0);
            $distributorPrice = $row['distributor_price'] !== null && $row['distributor_price'] !== ''
                ? $this->parseDecimal($row['distributor_price'], -1.0)
                : null;
            $piecesPerPackage = $row['pieces_per_package'] !== null && $row['pieces_per_package'] !== ''
                ? $this->parseInteger($row['pieces_per_package'], -1)
                : null;
            $minAlert = $this->parseInteger($row['min_stock_alert'], 5);
            $openingQty = $row['opening_quantity'] !== null && $row['opening_quantity'] !== ''
                ? $this->parseDecimal($row['opening_quantity'], 0.0)
                : 0.0;
            $openingCost = $row['opening_cost'] !== null && $row['opening_cost'] !== ''
                ? $this->parseDecimal($row['opening_cost'], null)
                : null;

            if ($costPrice === null || $costPrice < 0) {
                $errors[] = "السطر {$line}: سعر التكلفة غير صالح أو سالب ({$row['cost_price']}).";
            }
            if ($wholesalePrice === null || $wholesalePrice < 0) {
                $errors[] = "السطر {$line}: سعر الجملة غير صالح أو سالب ({$row['wholesale_price']}).";
            }
            if ($retailPrice === null || $retailPrice < 0) {
                $errors[] = "السطر {$line}: سعر التجزئة غير صالح أو سالب ({$row['retail_price']}).";
            }
            if ($distributorPrice !== null && $distributorPrice < 0) {
                $errors[] = "السطر {$line}: سعر الموزع لا يمكن أن يكون سالباً ({$row['distributor_price']}).";
            }
            if ($piecesPerPackage !== null && $piecesPerPackage < 1) {
                $errors[] = "السطر {$line}: عدد القطع في الطرد يجب أن يكون 1 على الأقل.";
            }
            if ($minAlert === null || $minAlert < 0) {
                $errors[] = "السطر {$line}: حد الطلب غير صالح أو سالب.";
            }
            if ($openingQty === null || $openingQty < 0) {
                $errors[] = "السطر {$line}: الكمية الافتتاحية لا يمكن أن تكون سالبة.";
            }
            if ($openingCost !== null && $openingCost < 0) {
                $errors[] = "السطر {$line}: تكلفة الرصيد الافتتاحي لا يمكن أن تكون سالبة.";
            }

            // Resolve Warehouse for Opening Stock
            $targetWarehouse = $defaultWarehouse;
            if (! empty($row['opening_warehouse'])) {
                $whName = mb_strtolower(trim($row['opening_warehouse']));
                if (isset($warehousesByName[$whName])) {
                    $targetWarehouse = $warehousesByName[$whName];
                }
            }

            // Opening cost defaults to cost price if not explicitly provided
            if ($openingQty > 0 && ($openingCost === null || $openingCost <= 0)) {
                $openingCost = max(0, (float) $costPrice);
            }

            // Opening date defaults to today
            $openingDate = ! empty($row['opening_balance_date'])
                ? trim((string) $row['opening_balance_date'])
                : date('Y-m-d');

            // 4. Primary Anchor Matching: Barcode takes precedence over SKU
            if (empty($errors)) {
                if (! empty($barcode)) {
                    // Match by Barcode
                    if (isset($existingBarcodes[$barcodeKey])) {
                        // Barcode exists in DB -> UPDATE existing product
                        $action = 'update';
                        $existingProduct = $allProducts->get($existingBarcodes[$barcodeKey]);

                        // Protect existing SKU if file has a conflicting SKU taken by another DB product
                        if (! empty($sku) && isset($existingSkus[$skuKey]) && $existingSkus[$skuKey] !== $existingProduct?->id) {
                            $sku = $existingProduct->sku;
                            $skuKey = mb_strtolower(trim($sku));
                            $row['sku'] = $existingProduct->sku;
                        } elseif (empty($sku) && $existingProduct) {
                            $sku = $existingProduct->sku;
                            $skuKey = mb_strtolower(trim($sku));
                            $row['sku'] = $existingProduct->sku;
                        }
                    } else {
                        // Barcode NOT in DB -> STRICTLY NEW PRODUCT!
                        $action = 'new';
                        $existingProduct = null;

                        // Auto-assign SKU if empty
                        if (empty($sku)) {
                            $sku = 'SKU-'.$barcode;
                            $skuKey = mb_strtolower(trim($sku));
                        }

                        // Disambiguate SKU if it collides with another product in DB or file
                        if (isset($existingSkus[$skuKey]) || isset($seenInFileSkus[$skuKey])) {
                            $candidateSku = $sku.'-'.$barcode;
                            $candidateKey = mb_strtolower(trim($candidateSku));
                            $c = 1;
                            while (isset($existingSkus[$candidateKey]) || isset($seenInFileSkus[$candidateKey])) {
                                $c++;
                                $candidateSku = $sku.'-'.$barcode.'-'.$c;
                                $candidateKey = mb_strtolower(trim($candidateSku));
                            }
                            $sku = $candidateSku;
                            $skuKey = $candidateKey;
                            $row['sku'] = $sku;
                            $skuAdjusted = true;
                        }
                    }
                } else {
                    // Barcode is EMPTY -> Fallback to SKU
                    if (! empty($sku)) {
                        if (isset($existingSkus[$skuKey])) {
                            // SKU exists in DB -> UPDATE
                            $action = 'update';
                            $existingProduct = $allProducts->get($existingSkus[$skuKey]);
                        } else {
                            // SKU is new -> NEW PRODUCT!
                            $action = 'new';
                            $existingProduct = null;
                            // Auto-generate internal barcode
                            $barcode = Product::generateInternalBarcode();
                            $barcodeKey = trim($barcode);
                            $barcodeGenerated = true;
                        }
                    } else {
                        $errors[] = "السطر {$line}: يجب إدخال الباركود أو كود الصنف (SKU).";
                    }
                }
            }

            // Track seen SKU in file
            if (! empty($skuKey)) {
                $seenInFileSkus[$skuKey] = true;
            }

            // Check Name Mismatch on Update
            $isNameMismatch = false;
            if ($existingProduct && ! empty($row['name'])) {
                $cleanExisting = trim(preg_replace('/[^\p{L}\p{N}]/u', '', mb_strtolower($existingProduct->name)));
                $cleanNew = trim(preg_replace('/[^\p{L}\p{N}]/u', '', mb_strtolower($row['name'])));
                if ($cleanExisting !== '' && $cleanNew !== '' && ! str_contains($cleanExisting, $cleanNew) && ! str_contains($cleanNew, $cleanExisting)) {
                    $isNameMismatch = true;
                }
            }

            if (! empty($errors)) {
                $action = 'invalid';
                $stats['invalid_count']++;
            } elseif ($action === 'update') {
                $stats['update_count']++;
            } else {
                $stats['new_count']++;
            }

            $audited[] = [
                'line' => $line,
                'action' => $action,
                'name_mismatch' => $isNameMismatch,
                'sku_adjusted' => $skuAdjusted,
                'barcode_generated' => $barcodeGenerated,
                'sku' => $sku,
                'name' => $row['name'],
                'category_name' => $row['category'] ?: 'عام',
                'barcode' => $barcode,
                'cost_price' => max(0, (float) $costPrice),
                'wholesale_price' => max(0, (float) $wholesalePrice),
                'retail_price' => max(0, (float) $retailPrice),
                'distributor_price' => $distributorPrice !== null ? max(0, (float) $distributorPrice) : null,
                'unit' => $row['unit'] ?: 'قطعة',
                'pieces_per_package' => $piecesPerPackage,
                'min_stock_alert' => max(0, (int) $minAlert),
                'opening_quantity' => max(0, (float) $openingQty),
                'opening_cost' => $openingCost !== null ? max(0, (float) $openingCost) : null,
                'opening_warehouse_id' => $targetWarehouse?->id,
                'opening_warehouse_name' => $targetWarehouse?->name,
                'opening_balance_date' => $openingDate,
                'errors' => $errors,
                'existing_product' => $existingProduct ? [
                    'id' => $existingProduct->id,
                    'name' => $existingProduct->name,
                    'old_cost_price' => (float) $existingProduct->cost_price,
                    'old_wholesale_price' => (float) $existingProduct->wholesale_price,
                    'old_retail_price' => (float) $existingProduct->retail_price,
                    'old_distributor_price' => (float) $existingProduct->distributor_price,
                ] : null,
            ];
        }

        return [
            'stats' => $stats,
            'rows' => $audited,
        ];
    }

    /**
     * Execute the verified import safely inside an atomic database transaction.
     * Records new products, updates existing products, creates categories,
     * and initializes warehouse FIFO batches for opening inventory balances.
     */
    public function executeImport(array $reviewedRows, bool $updateExisting = true, bool $createCategories = true, ?bool $canEditPrices = null): array
    {
        $canEditPrices = $canEditPrices ?? (auth()->user()?->can('edit-product-prices') ?? false);

        return DB::transaction(function () use ($reviewedRows, $updateExisting, $createCategories, $canEditPrices) {
            $insertedCount = 0;
            $updatedCount = 0;
            $skippedCount = 0;

            // Cache categories
            $categories = Category::all()->keyBy('name');

            foreach ($reviewedRows as $item) {
                if ($item['action'] === 'invalid') {
                    $skippedCount++;

                    continue;
                }

                // Resolve Category
                $categoryName = trim((string) ($item['category_name'] ?: 'عام'));
                $category = $categories->get($categoryName);

                if (! $category) {
                    if ($createCategories) {
                        $category = Category::create([
                            'name' => $categoryName,
                            'slug' => Str::slug($categoryName, '-', null) ?: 'cat-'.Str::random(8),
                            'description' => 'تم إنشاؤه تلقائياً أثناء استيراد الأصناف',
                            'is_active' => true,
                        ]);
                        $categories->put($categoryName, $category);
                    } else {
                        $category = Category::first() ?: Category::create(['name' => 'عام', 'slug' => 'general']);
                    }
                }

                $openingQty = isset($item['opening_quantity']) ? (float) $item['opening_quantity'] : 0.00;
                $openingCost = isset($item['opening_cost']) ? (float) $item['opening_cost'] : (float) $item['cost_price'];
                $itemCostPrice = (float) $item['cost_price'];

                // Smart safeguard: if unit is piece and opening_cost equals dozen cost (cost_price * 12), normalize to single piece cost
                $itemUnit = trim((string) ($item['unit'] ?: 'قطعة'));
                if (($itemUnit === 'قطعة' || $itemUnit === 'قطعه' || empty($item['unit'])) && $itemCostPrice > 0 && $openingCost > 0) {
                    if (abs($openingCost - ($itemCostPrice * 12)) < 1.0) {
                        $openingCost = $itemCostPrice;
                    }
                }

                $openingWarehouseId = $item['opening_warehouse_id'] ?? null;
                $openingDate = ! empty($item['opening_balance_date']) ? $item['opening_balance_date'] : date('Y-m-d');

                $payload = [
                    'sku' => $item['sku'],
                    'name' => $item['name'],
                    'category_id' => $category->id,
                    'barcode' => ! empty($item['barcode']) ? $item['barcode'] : null,
                    'cost_price' => $item['cost_price'],
                    'wholesale_price' => $item['wholesale_price'],
                    'retail_price' => $item['retail_price'],
                    'distributor_price' => $item['distributor_price'] ?? null,
                    'unit' => $item['unit'] ?: 'قطعة',
                    'pieces_per_package' => $item['pieces_per_package'] ?? null,
                    'min_stock_alert' => $item['min_stock_alert'] ?? 5,
                    'opening_quantity' => $openingQty,
                    'opening_cost' => $openingQty > 0 ? $openingCost : null,
                    'opening_warehouse_id' => $openingQty > 0 ? $openingWarehouseId : null,
                    'opening_balance_date' => $openingQty > 0 ? $openingDate : null,
                    'is_active' => true,
                ];

                if (! $canEditPrices) {
                    if ($item['action'] === 'update') {
                        // Protect existing prices from being overwritten by unauthorized users
                        unset(
                            $payload['cost_price'],
                            $payload['wholesale_price'],
                            $payload['retail_price'],
                            $payload['distributor_price'],
                            $payload['opening_cost']
                        );
                    } else {
                        // For newly created items by users without price permissions, zero out prices
                        $payload['cost_price'] = 0.00;
                        $payload['wholesale_price'] = 0.00;
                        $payload['retail_price'] = 0.00;
                        $payload['distributor_price'] = null;
                        $payload['opening_cost'] = null;
                    }
                    $openingCost = 0.00;
                }

                if ($item['action'] === 'update') {
                    if ($updateExisting) {
                        $product = null;
                        if (! empty($item['barcode'])) {
                            $product = Product::where('barcode', $item['barcode'])->first();
                        }
                        if (! $product && ! empty($item['sku'])) {
                            $product = Product::where('sku', $item['sku'])->first();
                        }

                        if ($product) {
                            // Ensure updating SKU does not collide with another product
                            if (! empty($payload['sku']) && $payload['sku'] !== $product->sku) {
                                if (Product::where('sku', $payload['sku'])->where('id', '!=', $product->id)->exists()) {
                                    $payload['sku'] = $product->sku;
                                }
                            }

                            $hasStockHistory = $product->batches()->exists()
                                || $product->stockBalances()->where('quantity', '>', 0)->exists()
                                || ((float) $product->opening_quantity > 0);

                            if (! $hasStockHistory && $openingQty > 0) {
                                // Existing item never had opening balance or stock; initialize opening balance now
                                $product->update($payload);
                                $this->recordOpeningStockIfApplicable($product, $openingQty, $openingCost, $openingWarehouseId, $openingDate);
                            } else {
                                // Protect existing opening balance history
                                unset($payload['opening_quantity'], $payload['opening_cost'], $payload['opening_warehouse_id'], $payload['opening_balance_date']);
                                $product->update($payload);
                            }
                            $updatedCount++;
                        } else {
                            if (Product::where('sku', $payload['sku'])->exists()) {
                                $payload['sku'] = $payload['sku'].'-'.($payload['barcode'] ?: Str::random(4));
                            }
                            if (empty($payload['barcode'])) {
                                $payload['barcode'] = Product::generateInternalBarcode();
                            }
                            $product = Product::create($payload);
                            $insertedCount++;
                            $this->recordOpeningStockIfApplicable($product, $openingQty, $openingCost, $openingWarehouseId, $openingDate);
                        }
                    } else {
                        $skippedCount++;
                    }
                } else {
                    if (Product::where('sku', $payload['sku'])->exists()) {
                        $candidate = $payload['sku'].'-'.($payload['barcode'] ?: Str::random(4));
                        $c = 1;
                        while (Product::where('sku', $candidate)->exists()) {
                            $c++;
                            $candidate = $payload['sku'].'-'.($payload['barcode'] ?: Str::random(4)).'-'.$c;
                        }
                        $payload['sku'] = $candidate;
                    }
                    if (empty($payload['barcode'])) {
                        $payload['barcode'] = Product::generateInternalBarcode();
                    }
                    $product = Product::create($payload);
                    $insertedCount++;
                    $this->recordOpeningStockIfApplicable($product, $openingQty, $openingCost, $openingWarehouseId, $openingDate);
                }
            }

            return [
                'inserted' => $insertedCount,
                'updated' => $updatedCount,
                'skipped' => $skippedCount,
            ];
        });
    }

    /**
     * Record FIFO inventory batch and stock movement for opening quantity.
     */
    protected function recordOpeningStockIfApplicable(
        Product $product,
        float $quantity,
        float $costPrice,
        ?int $warehouseId,
        string $receivedDate
    ): void {
        if ($quantity <= 0) {
            return;
        }

        if (! $warehouseId) {
            $wh = Warehouse::where('is_default', true)->first() ?? Warehouse::first();
            if (! $wh) {
                $wh = Warehouse::create([
                    'code' => 'WH-1',
                    'name' => 'المستودع الرئيسي',
                    'location' => 'المقر الرئيسي',
                    'is_active' => true,
                    'is_default' => true,
                ]);
            }
            $warehouseId = $wh->id;
        }

        $unitFactor = (float) ($product->unit_factor > 0 ? $product->unit_factor : 1.0);
        $baseQuantity = round($quantity * $unitFactor, 4);
        $baseCostPrice = round($costPrice / $unitFactor, 4);

        $retailPrice = ! is_null($product->retail_price) ? round((float) $product->retail_price / $unitFactor, 4) : null;
        $wholesalePrice = ! is_null($product->wholesale_price) ? round((float) $product->wholesale_price / $unitFactor, 4) : null;
        $distributorPrice = ! is_null($product->distributor_price) ? round((float) $product->distributor_price / $unitFactor, 4) : $wholesalePrice;

        $service = $this->inventoryService ?? app(InventoryService::class);
        $service->addStock(
            productId: $product->id,
            warehouseId: $warehouseId,
            quantity: $baseQuantity,
            costPrice: $baseCostPrice,
            notes: 'رصيد افتتاحي مسجل من استيراد الإكسيل: '.$product->name.($unitFactor > 1 ? " ({$quantity} {$product->unit} = {$baseQuantity} قطعة)" : ''),
            referenceType: 'opening_balance',
            referenceId: $product->id,
            userId: auth()->id(),
            receivedAt: Carbon::parse($receivedDate),
            retailPrice: $retailPrice,
            wholesalePrice: $wholesalePrice,
            distributorPrice: $distributorPrice,
            totalInitialCost: round($quantity * $costPrice, 2)
        );
    }
}
