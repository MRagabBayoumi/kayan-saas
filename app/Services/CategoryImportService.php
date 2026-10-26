<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ZipArchive;

class CategoryImportService
{
    /**
     * Header aliases mapping for flexible CSV and XLSX column names.
     */
    protected array $headerAliases = [
        'code' => ['code', 'كود القسم', 'كود التصنيف', 'الكود', 'الرمز', 'رمز القسم', 'كود'],
        'name' => ['name', 'اسم القسم', 'اسم التصنيف', 'القسم', 'التصنيف', 'الاسم', 'title'],
        'parent' => ['parent', 'parent_code', 'parent_name', 'القسم الرئيسي', 'التصنيف الأب', 'القسم الأب', 'التصنيف الرئيسي', 'الأب'],
        'is_active' => ['is_active', 'status', 'الحالة', 'نشط', 'مفعل', 'الحاله'],
        'description' => ['description', 'desc', 'الوصف', 'ملاحظات', 'بيان', 'الوصف التفصيلي'],
    ];

    /**
     * Generate standard CSV template content with UTF-8 BOM.
     */
    public function generateTemplateCsv(): string
    {
        $headers = [
            'كود القسم (Code)',
            'اسم القسم / التصنيف (Name)',
            'القسم الرئيسي (Parent - اختياري)',
            'الحالة (نشط/معطل)',
            'الوصف (Description - اختياري)',
        ];

        $sampleRows = [
            [
                'DEP-ACC',
                'إكسسوارات حريمي',
                '',
                'نشط',
                'قسم الإكسسوارات النسائية والحلي',
            ],
            [
                'DEP-HAIR',
                'أطواق وتوك شعر',
                'DEP-ACC',
                'نشط',
                'أطواق وتوك وبكلات الشعر بأنواعها',
            ],
            [
                'DEP-MAKEUP',
                'أدوات تجميل وميك اب',
                '',
                'نشط',
                'أدوات ومستحضرات التجميل والعناية',
            ],
            [
                'DEP-BAGS',
                'شنط ومحافظ جلدية',
                '',
                'معطل',
                'حقائب يد ومحافظ استيراد صيني',
            ],
        ];

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
     * Parse uploaded file (CSV or XLSX) into raw rows.
     */
    public function parseFile(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (in_array($extension, ['csv', 'txt'], true)) {
            return $this->readCsvRows($file->getRealPath());
        }

        if (in_array($extension, ['xlsx'], true)) {
            return $this->readXlsxRows($file->getRealPath());
        }

        throw new \InvalidArgumentException('صيغة الملف غير مدعومة. يرجى رفع ملف بصيغة Excel (.xlsx) أو CSV (.csv).');
    }

    /**
     * Parse and validate all rows, detecting duplicates and mapping hierarchical parents.
     */
    public function validateImport(array $rawRows): array
    {
        if (empty($rawRows)) {
            return [
                'success' => false,
                'message' => 'الملف المرفوع فارغ ولا يحتوي على أي بيانات.',
                'valid_rows' => [],
                'invalid_rows' => [],
                'summary' => [
                    'total' => 0,
                    'valid' => 0,
                    'invalid' => 0,
                    'duplicates' => 0,
                ],
            ];
        }

        $headerRow = array_shift($rawRows);
        $columnMap = $this->mapHeaders($headerRow);

        if (! isset($columnMap['name'])) {
            return [
                'success' => false,
                'message' => 'الملف لا يحتوي على عمود إلزامي لاسم القسم أو التصنيف (اسم القسم / التصنيف).',
                'valid_rows' => [],
                'invalid_rows' => [],
                'summary' => [
                    'total' => 0,
                    'valid' => 0,
                    'invalid' => 0,
                    'duplicates' => 0,
                ],
            ];
        }

        $existingCodes = Category::whereNotNull('code')->pluck('id', 'code')->toArray();
        $existingNames = Category::pluck('id', 'name')->toArray();
        $existingSlugs = Category::pluck('id', 'slug')->toArray();

        $seenCodesInFile = [];
        $seenNamesInFile = [];

        $validRows = [];
        $invalidRows = [];
        $duplicatesCount = 0;

        foreach ($rawRows as $index => $rowValues) {
            $rowNumber = $index + 2; // 1-indexed, accounting for header

            // Skip empty rows
            $hasContent = false;
            foreach ($rowValues as $v) {
                if (trim((string) $v) !== '') {
                    $hasContent = true;
                    break;
                }
            }
            if (! $hasContent) {
                continue;
            }

            $name = isset($columnMap['name']) ? trim((string) ($rowValues[$columnMap['name']] ?? '')) : '';
            $code = isset($columnMap['code']) ? trim((string) ($rowValues[$columnMap['code']] ?? '')) : '';
            $parent = isset($columnMap['parent']) ? trim((string) ($rowValues[$columnMap['parent']] ?? '')) : '';
            $statusRaw = isset($columnMap['is_active']) ? trim((string) ($rowValues[$columnMap['is_active']] ?? '')) : '';
            $description = isset($columnMap['description']) ? trim((string) ($rowValues[$columnMap['description']] ?? '')) : '';

            $errors = [];

            // 1. Validate Name
            if ($name === '') {
                $errors[] = 'اسم القسم / التصنيف مطلوب.';
            }

            // 2. Validate Code and Duplicate Detection
            if ($code !== '') {
                if (isset($seenCodesInFile[$code])) {
                    $errors[] = "كود القسم ({$code}) مكرر داخل نفس الملف في السطر {$seenCodesInFile[$code]}.";
                    $duplicatesCount++;
                } elseif (isset($existingCodes[$code])) {
                    $errors[] = "كود القسم ({$code}) مسجل مسبقاً بالنظام في تصنيف آخر.";
                    $duplicatesCount++;
                } else {
                    $seenCodesInFile[$code] = $rowNumber;
                }
            } else {
                // Auto-generate code from slug if blank
                $generatedSlug = Str::slug($name, '-', null) ?: 'cat-'.Str::random(6);
                $code = strtoupper('CAT-'.Str::random(6));
            }

            // Check Duplicate Name in File
            $nameKey = mb_strtolower($name);
            if (isset($seenNamesInFile[$nameKey])) {
                $errors[] = "اسم القسم ({$name}) مكرر في السطر {$seenNamesInFile[$nameKey]}.";
                $duplicatesCount++;
            } elseif (isset($existingNames[$name])) {
                $errors[] = "اسم القسم ({$name}) موجود مسبقاً بالنظام.";
                $duplicatesCount++;
            } else {
                $seenNamesInFile[$nameKey] = $rowNumber;
            }

            // 3. Parse Status
            $isActive = true;
            if ($statusRaw !== '') {
                $statusLower = mb_strtolower($statusRaw);
                if (in_array($statusLower, ['معطل', 'غير نشط', 'لا', '0', 'false', 'inactive', 'disabled'], true)) {
                    $isActive = false;
                }
            }

            if (! empty($errors)) {
                $invalidRows[] = [
                    'row_number' => $rowNumber,
                    'name' => $name ?: '—',
                    'code' => $code ?: '—',
                    'parent' => $parent ?: '—',
                    'is_active' => $isActive,
                    'errors' => $errors,
                ];
            } else {
                $validRows[] = [
                    'row_number' => $rowNumber,
                    'name' => $name,
                    'code' => $code,
                    'parent_ref' => $parent,
                    'description' => $description ?: null,
                    'is_active' => $isActive,
                ];
            }
        }

        return [
            'success' => true,
            'valid_rows' => $validRows,
            'invalid_rows' => $invalidRows,
            'summary' => [
                'total' => count($validRows) + count($invalidRows),
                'valid' => count($validRows),
                'invalid' => count($invalidRows),
                'duplicates' => $duplicatesCount,
            ],
        ];
    }

    /**
     * Atomically commit valid rows to the database inside a transaction.
     */
    public function commitImport(array $validRows): array
    {
        return DB::transaction(function () use ($validRows) {
            $createdCount = 0;
            $codeToIdMap = Category::whereNotNull('code')->pluck('id', 'code')->toArray();
            $nameToIdMap = Category::pluck('id', 'name')->toArray();

            // First Pass: Create categories
            $pendingParents = [];

            foreach ($validRows as $row) {
                $slug = Str::slug($row['name'], '-', null) ?: 'cat-'.Str::random(8);
                if (Category::where('slug', $slug)->exists()) {
                    $slug .= '-'.Str::random(4);
                }

                $category = Category::create([
                    'name' => $row['name'],
                    'code' => $row['code'] ?: null,
                    'slug' => $slug,
                    'description' => $row['description'] ?? null,
                    'is_active' => (bool) $row['is_active'],
                    'parent_id' => null,
                ]);

                $createdCount++;

                if (! empty($row['code'])) {
                    $codeToIdMap[$row['code']] = $category->id;
                }
                $nameToIdMap[$row['name']] = $category->id;

                if (! empty($row['parent_ref'])) {
                    $pendingParents[] = [
                        'category_id' => $category->id,
                        'parent_ref' => $row['parent_ref'],
                    ];
                }
            }

            // Second Pass: Link hierarchy parents
            foreach ($pendingParents as $item) {
                $ref = $item['parent_ref'];
                $parentId = $codeToIdMap[$ref] ?? ($nameToIdMap[$ref] ?? null);

                if ($parentId && $parentId !== $item['category_id']) {
                    Category::where('id', $item['category_id'])->update(['parent_id' => $parentId]);
                }
            }

            return [
                'success' => true,
                'created_count' => $createdCount,
                'message' => "تم استيراد وتسجيل {$createdCount} قسماً وتصنيفاً جديداً بنجاح.",
            ];
        });
    }

    /**
     * Map header text to known standard keys.
     */
    protected function mapHeaders(array $headers): array
    {
        $map = [];

        // Pass 1: Exact matches
        foreach ($headers as $colIdx => $headerText) {
            $clean = mb_strtolower(trim((string) $headerText));
            $clean = preg_replace('/[\(\)\*\#\-]/u', '', $clean);
            $clean = trim($clean);

            foreach ($this->headerAliases as $key => $aliases) {
                if (isset($map[$key])) {
                    continue;
                }
                foreach ($aliases as $alias) {
                    $cleanAlias = mb_strtolower(preg_replace('/[\(\)\*\#\-]/u', '', $alias));
                    if ($clean === $cleanAlias) {
                        $map[$key] = $colIdx;
                        break;
                    }
                }
            }
        }

        // Pass 2: Substring matches for remaining unmapped keys
        foreach ($headers as $colIdx => $headerText) {
            $clean = mb_strtolower(trim((string) $headerText));
            $clean = preg_replace('/[\(\)\*\#\-]/u', '', $clean);
            $clean = trim($clean);

            foreach ($this->headerAliases as $key => $aliases) {
                if (isset($map[$key])) {
                    continue;
                }
                if ($key === 'name' && (str_contains($clean, 'كود') || str_contains($clean, 'code'))) {
                    continue;
                }
                if ($key === 'code' && (str_contains($clean, 'اسم') || str_contains($clean, 'name'))) {
                    continue;
                }

                foreach ($aliases as $alias) {
                    $cleanAlias = mb_strtolower(preg_replace('/[\(\)\*\#\-]/u', '', $alias));
                    if (str_contains($clean, $cleanAlias)) {
                        $map[$key] = $colIdx;
                        break;
                    }
                }
            }
        }

        return $map;
    }

    /**
     * Read CSV rows safely with encoding and BOM detection.
     */
    protected function readCsvRows(string $path): array
    {
        $content = file_get_contents($path);
        // Strip UTF-8 BOM
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        }

        $lines = preg_split('/\r\n|\r|\n/', trim($content));
        $rows = [];

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $row = str_getcsv($line);
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Read XLSX rows natively using ZipArchive and sheet XML.
     */
    protected function readXlsxRows(string $path): array
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('تعذر فتح ملف Excel. يرجى التأكد من سلامة الملف.');
        }

        // Shared strings
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

        // Sheet 1
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
                    $sIndex = (int) $c->v;
                    $val = $sharedStrings[$sIndex] ?? '';
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
