<?php

namespace App\Services;

use ZipArchive;

class SimpleXlsxGenerator
{
    /**
     * Generate a valid .xlsx file in memory and return its binary content.
     *
     * @param  array  $headers  Array of string header titles.
     * @param  array  $rows  Array of row data.
     * @param  array  $columnTypes  Mapping of col index (0-based) to type ('text', 'number', 'currency').
     * @param  array  $columnWidths  Optional mapping of col index to width.
     * @param  string  $sheetTitle  Sheet tab title.
     * @param  array  $metaRows  Optional array of string rows to place before the header row.
     * @return string Binary xlsx content.
     */
    public static function create(
        array $headers,
        array $rows,
        array $columnTypes = [],
        array $columnWidths = [],
        string $sheetTitle = 'Sheet1',
        array $metaRows = []
    ): string {
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');
        $zip = new ZipArchive;
        $zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        // 1. [Content_Types].xml
        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.
            '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'.
            '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'.
            '<Default Extension="xml" ContentType="application/xml"/>'.
            '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'.
            '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'.
            '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'.
            '</Types>';
        $zip->addFromString('[Content_Types].xml', $contentTypes);

        // 2. _rels/.rels
        $rootRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'.
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'.
            '</Relationships>';
        $zip->addFromString('_rels/.rels', $rootRels);

        // 3. xl/_rels/workbook.xml.rels
        $wbRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'.
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'.
            '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'.
            '</Relationships>';
        $zip->addFromString('xl/_rels/workbook.xml.rels', $wbRels);

        // 4. xl/workbook.xml
        $safeSheetTitle = htmlspecialchars(mb_substr($sheetTitle, 0, 31), ENT_XML1, 'UTF-8');
        $workbookXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.
            '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'.
            '<sheets>'.
            '<sheet name="'.$safeSheetTitle.'" sheetId="1" r:id="rId1"/>'.
            '</sheets>'.
            '</workbook>';
        $zip->addFromString('xl/workbook.xml', $workbookXml);

        // 5. xl/styles.xml
        $zip->addFromString('xl/styles.xml', self::getStylesXml());

        // 6. xl/worksheets/sheet1.xml
        $sheetXml = self::buildSheetXml($headers, $rows, $columnTypes, $columnWidths, $metaRows, 1, true);
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);

        $zip->close();

        $content = file_get_contents($tempFile);
        @unlink($tempFile);

        return $content;
    }

    /**
     * Generate a multi-sheet .xlsx workbook (e.g. Sheet 1: Available, Sheet 2: Zero Stock).
     */
    public static function createTwoSheetWorkbook(array $sheet1, array $sheet2): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_two_');
        $zip = new ZipArchive;
        $zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        // 1. [Content_Types].xml
        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.
            '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'.
            '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'.
            '<Default Extension="xml" ContentType="application/xml"/>'.
            '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'.
            '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'.
            '<Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'.
            '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'.
            '</Types>';
        $zip->addFromString('[Content_Types].xml', $contentTypes);

        // 2. _rels/.rels
        $rootRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'.
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'.
            '</Relationships>';
        $zip->addFromString('_rels/.rels', $rootRels);

        // 3. xl/_rels/workbook.xml.rels
        $wbRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'.
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'.
            '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'.
            '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/>'.
            '</Relationships>';
        $zip->addFromString('xl/_rels/workbook.xml.rels', $wbRels);

        // 4. xl/workbook.xml
        $title1 = htmlspecialchars(mb_substr($sheet1['sheetTitle'] ?? 'الأصناف المتاحة', 0, 31), ENT_XML1, 'UTF-8');
        $title2 = htmlspecialchars(mb_substr($sheet2['sheetTitle'] ?? 'أصناف غير متوفرة', 0, 31), ENT_XML1, 'UTF-8');

        $workbookXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.
            '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'.
            '<sheets>'.
            '<sheet name="'.$title1.'" sheetId="1" r:id="rId1"/>'.
            '<sheet name="'.$title2.'" sheetId="2" r:id="rId3"/>'.
            '</sheets>'.
            '</workbook>';
        $zip->addFromString('xl/workbook.xml', $workbookXml);

        // 5. xl/styles.xml
        $zip->addFromString('xl/styles.xml', self::getStylesXml());

        // 6. xl/worksheets/sheet1.xml & sheet2.xml
        $xml1 = self::buildSheetXml(
            $sheet1['headers'] ?? [],
            $sheet1['rows'] ?? [],
            $sheet1['columnTypes'] ?? [],
            $sheet1['columnWidths'] ?? [],
            $sheet1['metaRows'] ?? [],
            $sheet1['headerStyleId'] ?? 7, // Green Header
            true
        );
        $zip->addFromString('xl/worksheets/sheet1.xml', $xml1);

        $xml2 = self::buildSheetXml(
            $sheet2['headers'] ?? [],
            $sheet2['rows'] ?? [],
            $sheet2['columnTypes'] ?? [],
            $sheet2['columnWidths'] ?? [],
            $sheet2['metaRows'] ?? [],
            $sheet2['headerStyleId'] ?? 8, // Red Header
            false
        );
        $zip->addFromString('xl/worksheets/sheet2.xml', $xml2);

        $zip->close();

        $content = file_get_contents($tempFile);
        @unlink($tempFile);

        return $content;
    }

    /**
     * Build shared XML stylesheet.
     */
    public static function getStylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.
            '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'.
            '<numFmts count="3">'.
            '<numFmt numFmtId="164" formatCode="#,##0.00"/>'.
            '<numFmt numFmtId="165" formatCode="@"/>'.
            '<numFmt numFmtId="166" formatCode="#,##0.00 &quot;ج.م&quot;"/>'.
            '</numFmts>'.
            '<fonts count="5">'.
            '<font><sz val="11"/><name val="Segoe UI"/></font>'. // 0: Normal
            '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Segoe UI"/></font>'. // 1: Header White Bold
            '<font><b/><sz val="13"/><color rgb="FF1E293B"/><name val="Segoe UI"/></font>'. // 2: Title Dark Bold
            '<font><b/><sz val="11"/><color rgb="FF166534"/><name val="Segoe UI"/></font>'. // 3: Green Bold
            '<font><b/><sz val="11"/><color rgb="FF991B1B"/><name val="Segoe UI"/></font>'. // 4: Red Bold
            '</fonts>'.
            '<fills count="8">'.
            '<fill><patternFill patternType="none"/></fill>'.
            '<fill><patternFill patternType="gray125"/></fill>'.
            '<fill><patternFill patternType="solid"><fgColor rgb="FF714B67"/></patternFill></fill>'. // 2: Primary purple
            '<fill><patternFill patternType="solid"><fgColor rgb="FFF8FAFC"/></patternFill></fill>'. // 3: Meta light fill
            '<fill><patternFill patternType="solid"><fgColor rgb="FF166534"/></patternFill></fill>'. // 4: Green Header
            '<fill><patternFill patternType="solid"><fgColor rgb="FF991B1B"/></patternFill></fill>'. // 5: Red Header
            '<fill><patternFill patternType="solid"><fgColor rgb="FFDCFCE7"/></patternFill></fill>'. // 6: Green light bg
            '<fill><patternFill patternType="solid"><fgColor rgb="FFFEF2F2"/></patternFill></fill>'. // 7: Red light bg
            '</fills>'.
            '<borders count="3">'.
            '<border><left/><right/><top/><bottom/></border>'. // 0: None
            '<border>'. // 1: Thin border
            '<left style="thin"><color rgb="FFCBD5E1"/></left>'.
            '<right style="thin"><color rgb="FFCBD5E1"/></right>'.
            '<top style="thin"><color rgb="FFCBD5E1"/></top>'.
            '<bottom style="thin"><color rgb="FFCBD5E1"/></bottom>'.
            '</border>'.
            '<border>'. // 2: Double bottom
            '<left style="thin"><color rgb="FFCBD5E1"/></left>'.
            '<right style="thin"><color rgb="FFCBD5E1"/></right>'.
            '<top style="thin"><color rgb="FFCBD5E1"/></top>'.
            '<bottom style="double"><color rgb="FF1E293B"/></bottom>'.
            '</border>'.
            '</borders>'.
            '<cellStyleXfs count="1">'.
            '<xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>'.
            '</cellStyleXfs>'.
            '<cellXfs count="12">'.
            '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"/>'. // 0: Normal
            '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'. // 1: Primary Header
            '<xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1"><alignment horizontal="center"/></xf>'. // 2: Decimal
            '<xf numFmtId="3" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1"><alignment horizontal="center"/></xf>'. // 3: Integer
            '<xf numFmtId="165" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1"><alignment horizontal="center"/></xf>'. // 4: Text forced (@) for Barcode/SKU
            '<xf numFmtId="166" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1"><alignment horizontal="right"/></xf>'. // 5: Currency
            '<xf numFmtId="0" fontId="2" fillId="3" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'. // 6: Meta title
            '<xf numFmtId="0" fontId="1" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'. // 7: Green Header
            '<xf numFmtId="0" fontId="1" fillId="5" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'. // 8: Red Header
            '<xf numFmtId="0" fontId="3" fillId="6" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'. // 9: Green Badge
            '<xf numFmtId="0" fontId="4" fillId="7" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'. // 10: Red Badge
            '<xf numFmtId="166" fontId="2" fillId="6" borderId="2" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'. // 11: Total Currency
            '</cellXfs>'.
            '</styleSheet>';
    }

    /**
     * Build single sheet XML with RTL, widths, headers and rows.
     */
    private static function buildSheetXml(
        array $headers,
        array $rows,
        array $columnTypes,
        array $columnWidths,
        array $metaRows = [],
        int $headerStyleId = 1,
        bool $tabSelected = true
    ): string {
        $numCols = count($headers);
        $tabSelectedAttr = $tabSelected ? '1' : '0';

        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.
            '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'.
            '<sheetViews>'.
            '<sheetView tabSelected="'.$tabSelectedAttr.'" workbookViewId="0" rightToLeft="1"/>'.
            '</sheetViews>';

        // Column widths
        $sheetXml .= '<cols>';
        for ($i = 0; $i < $numCols; $i++) {
            $colNum = $i + 1;
            $w = $columnWidths[$i] ?? 18;
            $sheetXml .= '<col min="'.$colNum.'" max="'.$colNum.'" width="'.(float) $w.'" customWidth="1"/>';
        }
        $sheetXml .= '</cols>';

        $sheetXml .= '<sheetData>';

        $currentRow = 1;

        // Meta Header Rows
        if (! empty($metaRows)) {
            foreach ($metaRows as $metaRow) {
                if (empty($metaRow)) {
                    $sheetXml .= '<row r="'.$currentRow.'" ht="14"/>';
                    $currentRow++;

                    continue;
                }

                $sheetXml .= '<row r="'.$currentRow.'" ht="26" customHeight="1">';
                foreach ($metaRow as $cIdx => $metaCell) {
                    $cRef = self::getColLetter($cIdx).$currentRow;
                    $escaped = htmlspecialchars((string) $metaCell, ENT_XML1, 'UTF-8');
                    $sheetXml .= '<c r="'.$cRef.'" t="inlineStr" s="6"><is><t>'.$escaped.'</t></is></c>';
                }
                $sheetXml .= '</row>';
                $currentRow++;
            }
        }

        // Table Header Row
        $sheetXml .= '<row r="'.$currentRow.'" ht="28" customHeight="1">';
        foreach ($headers as $colIdx => $header) {
            $cellRef = self::getColLetter($colIdx).$currentRow;
            $escaped = htmlspecialchars((string) $header, ENT_XML1, 'UTF-8');
            $sheetXml .= '<c r="'.$cellRef.'" t="inlineStr" s="'.$headerStyleId.'"><is><t>'.$escaped.'</t></is></c>';
        }
        $sheetXml .= '</row>';
        $currentRow++;

        // Table Data Rows
        foreach ($rows as $row) {
            $isTotalRow = (isset($row[0]) && is_string($row[0]) && str_contains($row[0], 'الإجمالي'));
            $rowHeight = $isTotalRow ? '26' : '22';

            $sheetXml .= '<row r="'.$currentRow.'" ht="'.$rowHeight.'" customHeight="1">';
            foreach ($row as $colIdx => $val) {
                $cellRef = self::getColLetter($colIdx).$currentRow;
                $type = $columnTypes[$colIdx] ?? 'auto';

                if ($val === null || $val === '') {
                    $sheetXml .= '<c r="'.$cellRef.'" s="0"/>';

                    continue;
                }

                if ($type === 'text') {
                    // Barcode & SKU: ALWAYS forced text so Excel never truncates leading zeros or converts to scientific notation
                    $escaped = htmlspecialchars((string) $val, ENT_XML1, 'UTF-8');
                    $sheetXml .= '<c r="'.$cellRef.'" t="inlineStr" s="4"><is><t>'.$escaped.'</t></is></c>';
                } elseif ($type === 'badge_green') {
                    $escaped = htmlspecialchars((string) $val, ENT_XML1, 'UTF-8');
                    $sheetXml .= '<c r="'.$cellRef.'" t="inlineStr" s="9"><is><t>'.$escaped.'</t></is></c>';
                } elseif ($type === 'badge_red') {
                    $escaped = htmlspecialchars((string) $val, ENT_XML1, 'UTF-8');
                    $sheetXml .= '<c r="'.$cellRef.'" t="inlineStr" s="10"><is><t>'.$escaped.'</t></is></c>';
                } elseif ($type === 'currency' && is_numeric($val)) {
                    $styleId = $isTotalRow ? 11 : 5;
                    $sheetXml .= '<c r="'.$cellRef.'" t="n" s="'.$styleId.'"><v>'.(float) $val.'</v></c>';
                } elseif ($type === 'number' && is_numeric($val)) {
                    $numVal = (float) $val;
                    $styleId = (floor($numVal) == $numVal) ? 3 : 2;
                    $sheetXml .= '<c r="'.$cellRef.'" t="n" s="'.$styleId.'"><v>'.$numVal.'</v></c>';
                } elseif ($type === 'auto' && is_numeric($val) && ! preg_match('/^0\d+/', (string) $val) && strlen((string) $val) < 12) {
                    $numVal = (float) $val;
                    $styleId = (floor($numVal) == $numVal) ? 3 : 2;
                    $sheetXml .= '<c r="'.$cellRef.'" t="n" s="'.$styleId.'"><v>'.$numVal.'</v></c>';
                } else {
                    $escaped = htmlspecialchars((string) $val, ENT_XML1, 'UTF-8');
                    $sheetXml .= '<c r="'.$cellRef.'" t="inlineStr" s="0"><is><t>'.$escaped.'</t></is></c>';
                }
            }
            $sheetXml .= '</row>';
            $currentRow++;
        }

        $sheetXml .= '</sheetData>';
        $sheetXml .= '</worksheet>';

        return $sheetXml;
    }

    /**
     * Convert 0-based col index to Excel col letters (0 => A, 25 => Z, 26 => AA, etc.)
     */
    public static function getColLetter(int $colIndex): string
    {
        $letters = '';
        while ($colIndex >= 0) {
            $letters = chr(($colIndex % 26) + 65).$letters;
            $colIndex = intdiv($colIndex, 26) - 1;
        }

        return $letters;
    }
}
