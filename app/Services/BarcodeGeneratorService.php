<?php

namespace App\Services;

use App\Models\BarcodeSetting;
use App\Models\Product;

class BarcodeGeneratorService
{
    /**
     * Code 128 pattern dictionary for values 0 to 106.
     * Each pattern contains alternating bar and space module widths.
     * Values 0-105 sum to 11 modules; value 106 (Stop) sums to 13 modules.
     */
    private const PATTERNS = [
        '212222', '222122', '222221', '121223', '121322', '131222', '122213', '122312', '132212', '221213',
        '221312', '231212', '112232', '122132', '122231', '113222', '123122', '123221', '223211', '221132',
        '221231', '213212', '223112', '312131', '311222', '321122', '321221', '312212', '322112', '322211',
        '212123', '212321', '232121', '111323', '131123', '131321', '112313', '132113', '132311', '211313',
        '231113', '231311', '112133', '112331', '132131', '113123', '113321', '133121', '313121', '211331',
        '231131', '213113', '213311', '213131', '311123', '311321', '331121', '312113', '312311', '332111',
        '314111', '221411', '431111', '111224', '111422', '121124', '121421', '141122', '141221', '112214',
        '112412', '122114', '122411', '142112', '142211', '241211', '221114', '413111', '241112', '134111',
        '111242', '121142', '121241', '114212', '124112', '124211', '411212', '421112', '421211', '212141',
        '214121', '412121', '111143', '111341', '131141', '114113', '114311', '411113', '411311', '113141',
        '114131', '311141', '411131', '211412', '211214', '211232', '2331112',
    ];

    /**
     * Generate pure SVG string for a Code 128 barcode.
     *
     * @param  string  $code  Barcode value (numbers or ASCII text)
     * @param  int  $height  Barcode vertical height in SVG coordinates
     * @param  int  $moduleWidth  Width per module unit
     * @param  int  $quietZoneModules  Quiet zone padding on each side
     */
    public function generateSvg(string $code, int $height = 30, int $moduleWidth = 2, int $quietZoneModules = 8): string
    {
        $code = trim($code);
        if ($code === '') {
            $code = '000000000000';
        }

        // Check if string is even-length numeric for dense Code C encoding
        $isAllDigits = ctype_digit($code) && (strlen($code) % 2 === 0);

        $values = [];
        if ($isAllDigits) {
            $startVal = 105; // Start Code C
            $pairs = str_split($code, 2);
            foreach ($pairs as $p) {
                $values[] = (int) $p;
            }
        } else {
            $startVal = 104; // Start Code B
            $len = strlen($code);
            for ($i = 0; $i < $len; $i++) {
                $ascii = ord($code[$i]);
                $values[] = ($ascii >= 32 && $ascii <= 126) ? ($ascii - 32) : 0;
            }
        }

        // Calculate Modulo 103 Checksum
        $checksum = $startVal;
        foreach ($values as $idx => $val) {
            $checksum += ($idx + 1) * $val;
        }
        $checksum %= 103;

        // Assemble full symbol stream: [Start, ...Data, Checksum, Stop]
        $symbols = array_merge([$startVal], $values, [$checksum, 106]);

        // Build SVG elements
        $x = $quietZoneModules * $moduleWidth;
        $rects = [];

        foreach ($symbols as $sym) {
            $pattern = self::PATTERNS[$sym];
            $patternLen = strlen($pattern);
            for ($p = 0; $p < $patternLen; $p++) {
                $width = (int) $pattern[$p] * $moduleWidth;
                if ($p % 2 === 0) {
                    $rects[] = sprintf('<rect x="%d" y="0" width="%d" height="%d" fill="#000000" />', $x, $width, $height);
                }
                $x += $width;
            }
        }

        $totalWidth = $x + ($quietZoneModules * $moduleWidth);
        $rectsHtml = implode("\n    ", $rects);

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {$totalWidth} {$height}" width="100%" height="{$height}" preserveAspectRatio="xMidYMid meet" shape-rendering="crispEdges">
    {$rectsHtml}
</svg>
SVG;
    }

    /**
     * Resolve the barcode value for a product, falling back to SKU.
     */
    public function getBarcodeValue(Product $product): string
    {
        return ! empty($product->barcode) ? trim($product->barcode) : trim($product->sku);
    }

    /**
     * Generate complete thermal label SVG matching Xprinter XP-233B and user settings.
     * Supports configurable dimensions, fonts, company name, item code, and prices.
     */
    public function generateCompleteLabelSvg(
        string $productName,
        string $barcodeValue,
        string $sku,
        ?int $widthMm = null,
        ?int $heightMm = null,
        ?BarcodeSetting $settings = null,
        ?float $retailPrice = null,
        ?float $wholesalePrice = null
    ): string {
        $settings ??= BarcodeSetting::getSettings();
        $widthMm ??= (int) $settings->label_width_mm;
        $heightMm ??= (int) $settings->label_height_mm;

        $code = trim($barcodeValue);
        if ($code === '') {
            $code = '000000000000';
        }

        $totalWidthPx = (int) round($widthMm / 25.4 * 203);
        $totalHeightPx = (int) round($heightMm / 25.4 * 203);
        $centerX = (int) round($totalWidthPx / 2);

        $showCompany = (bool) $settings->show_company_name;
        $companyName = $settings->getEffectiveCompanyName();
        $showProduct = (bool) $settings->show_product_name;
        $showBars = (bool) $settings->show_barcode_bars;
        $showSku = (bool) $settings->show_item_sku;
        $priceType = (string) $settings->print_price_type;

        $productFontSize = max(8, min(36, (int) $settings->product_font_size));
        $detailsFontSize = max(6, min(28, (int) $settings->details_font_size));
        $companyFontSize = max(7, min(24, (int) round($detailsFontSize * 1.05)));

        // Determine price text to display
        $priceText = '';
        if ($priceType === 'retail' && $retailPrice !== null) {
            $priceText = 'السعر: '.number_format((float) $retailPrice, 2).' ج.م';
        } elseif ($priceType === 'wholesale' && $wholesalePrice !== null) {
            $priceText = 'جملة: '.number_format((float) $wholesalePrice, 2).' ج.م';
        } elseif ($priceType === 'both' && $retailPrice !== null && $wholesalePrice !== null) {
            $priceText = 'تجزئة: '.number_format((float) $retailPrice, 0).' | جملة: '.number_format((float) $wholesalePrice, 0);
        }

        // Layout calculations from top down
        $currentY = ($heightMm >= 25) ? 8 : 4;
        $textElements = [];

        if ($showCompany && $companyName !== '') {
            $currentY += $companyFontSize;
            $safeCompany = htmlspecialchars(mb_substr($companyName, 0, 40, 'UTF-8'), ENT_QUOTES, 'UTF-8');
            $textElements[] = sprintf(
                '<text x="%d" y="%d" class="comp-title" style="font-size: %dpx;">%s</text>',
                $centerX,
                $currentY,
                $companyFontSize,
                $safeCompany
            );
            $currentY += 4;
        }

        if ($showProduct && $productName !== '') {
            $currentY += $productFontSize;
            $safeName = htmlspecialchars(mb_substr($productName, 0, 36, 'UTF-8'), ENT_QUOTES, 'UTF-8');
            $textElements[] = sprintf(
                '<text x="%d" y="%d" class="prod-title" style="font-size: %dpx;">%s</text>',
                $centerX,
                $currentY,
                $productFontSize,
                $safeName
            );
            $currentY += 4;
        }

        // Bottom text elements (SKU & Price)
        $bottomY = $totalHeightPx - (($heightMm >= 25) ? 10 : 6);
        $bottomTextParts = [];
        if ($showSku && $sku !== '') {
            $safeSku = htmlspecialchars($sku, ENT_QUOTES, 'UTF-8');
            $bottomTextParts[] = 'ITEM-NO: '.$safeSku;
        }
        if ($priceText !== '') {
            $bottomTextParts[] = htmlspecialchars($priceText, ENT_QUOTES, 'UTF-8');
        }

        if (! empty($bottomTextParts)) {
            $joinedBottom = implode('  |  ', $bottomTextParts);
            $textElements[] = sprintf(
                '<text x="%d" y="%d" class="item-sku" style="font-size: %dpx;">%s</text>',
                $centerX,
                $bottomY,
                $detailsFontSize,
                $joinedBottom
            );
        }

        // Barcode bars generation if enabled
        $barcodeSvgGroup = '';
        if ($showBars) {
            $isAllDigits = ctype_digit($code) && (strlen($code) % 2 === 0);
            $values = [];
            if ($isAllDigits) {
                $startVal = 105;
                $pairs = str_split($code, 2);
                foreach ($pairs as $p) {
                    $values[] = (int) $p;
                }
            } else {
                $startVal = 104;
                $len = strlen($code);
                for ($i = 0; $i < $len; $i++) {
                    $ascii = ord($code[$i]);
                    $values[] = ($ascii >= 32 && $ascii <= 126) ? ($ascii - 32) : 0;
                }
            }

            $checksum = $startVal;
            foreach ($values as $idx => $val) {
                $checksum += ($idx + 1) * $val;
            }
            $checksum %= 103;

            $symbols = array_merge([$startVal], $values, [$checksum, 106]);
            $totalModules = 0;
            foreach ($symbols as $sym) {
                $pattern = self::PATTERNS[$sym];
                $totalModules += array_sum(str_split($pattern));
            }

            // Target barcode width and height
            $configuredBarWidth = (int) ($settings->barcode_width_px ?: 260);
            $targetWidth = min((float) $totalWidthPx * 0.96, max(80.0, (float) $configuredBarWidth));

            $availableHeight = ($bottomY - $detailsFontSize - 6) - $currentY;
            $configuredBarHeight = (int) ($settings->barcode_height_px ?: 80);
            $barcodeHeight = (int) max(15, min($configuredBarHeight, max(15, $availableHeight)));
            $barcodeY = (int) max($currentY + 2, $currentY + round(($availableHeight - $barcodeHeight) / 2));

            $moduleWidth = $targetWidth / max(1, $totalModules);
            $startX = ($totalWidthPx - $targetWidth) / 2.0;

            $rects = [];
            $curX = $startX;
            foreach ($symbols as $sym) {
                $pattern = self::PATTERNS[$sym];
                $patternLen = strlen($pattern);
                for ($p = 0; $p < $patternLen; $p++) {
                    $w = (int) $pattern[$p] * $moduleWidth;
                    if ($p % 2 === 0) {
                        $rects[] = sprintf(
                            '<rect x="%.2f" y="%d" width="%.2f" height="%d" fill="#000000" />',
                            $curX,
                            $barcodeY,
                            $w,
                            $barcodeHeight
                        );
                    }
                    $curX += $w;
                }
            }

            $rectsHtml = implode("\n        ", $rects);
            $barcodeSvgGroup = <<<BARCODE
    <g id="barcode-bars">
        {$rectsHtml}
    </g>
BARCODE;
        }

        $allTextsHtml = implode("\n    ", $textElements);

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {$totalWidthPx} {$totalHeightPx}" width="{$widthMm}mm" height="{$heightMm}mm" preserveAspectRatio="none" shape-rendering="crispEdges">
    <style>
        .comp-title { font-family: 'Cairo', 'Tahoma', Arial, sans-serif; font-weight: 700; fill: #1e293b; text-anchor: middle; direction: rtl; }
        .prod-title { font-family: 'Cairo', 'Tahoma', Arial, sans-serif; font-weight: 700; fill: #000000; text-anchor: middle; direction: rtl; }
        .item-sku { font-family: 'Cairo', 'Courier New', Arial, monospace; font-weight: 700; fill: #000000; text-anchor: middle; letter-spacing: 0.5px; }
    </style>
    {$allTextsHtml}
{$barcodeSvgGroup}
</svg>
SVG;
    }
}
