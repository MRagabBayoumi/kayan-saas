<?php

namespace App\Services\Printing;

use App\Models\BarcodeSetting;
use App\Models\CashierShift;
use App\Models\CompanySetting;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturn;
use App\Models\SalesInvoice;
use App\Services\BarcodeGeneratorService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use Throwable;

class PythonPrintEngineService
{
    protected string $pythonPath;

    protected string $engineScript;

    protected string $defaultPrinter;

    protected int $timeout;

    protected bool $enabled;

    public function __construct()
    {
        $this->enabled = (bool) config('printing.enabled', config('services.printing.enabled', true));
        $this->pythonPath = (string) config('printing.python_path', config('services.printing.python_path', 'C:\\Users\\PC\\Desktop\\اعلانات\\barcode_printer\\.venv\\Scripts\\python.exe'));
        $this->engineScript = (string) config('printing.engine_script', config('services.printing.engine_script', base_path('scripts/printing/print_engine.py')));
        $this->defaultPrinter = (string) config('printing.default_printer', config('services.printing.default_printer', 'Xprinter XP-233B'));
        $this->timeout = (int) config('printing.timeout', config('services.printing.timeout', 15));
    }

    /**
     * Check if the Python print engine is enabled and executable.
     */
    public function isAvailable(): bool
    {
        if (! $this->enabled) {
            return false;
        }

        if (! file_exists($this->engineScript)) {
            return false;
        }

        if (file_exists($this->pythonPath)) {
            return true;
        }

        if (in_array(strtolower($this->pythonPath), ['python', 'python.exe', 'py', 'py.exe'], true)) {
            return true;
        }

        return false;
    }

    /**
     * Discover available printers installed on this machine or Windows network.
     *
     * @return array<int, string>
     */
    public function getSystemPrinters(): array
    {
        $printers = $this->listPrinters();
        if (! empty($printers)) {
            return $printers;
        }

        // Fallback: PowerShell Get-Printer on Windows
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            try {
                $ps = new Process([
                    'powershell',
                    '-NoProfile',
                    '-Command',
                    'Get-Printer | Select-Object -ExpandProperty Name',
                ]);
                $ps->setTimeout(8);
                $ps->run();

                if ($ps->isSuccessful()) {
                    $lines = array_map('trim', explode("\n", $ps->getOutput()));
                    $discovered = array_values(array_unique(array_filter($lines)));
                    if (! empty($discovered)) {
                        return $discovered;
                    }
                }
            } catch (Throwable $e) {
                Log::warning('PythonPrintEngine: PowerShell Get-Printer fallback failed', ['error' => $e->getMessage()]);
            }
        }

        // Built-in reasonable defaults
        return [
            'XP-80C',
            'Xprinter XP-233B',
            'TOSHIBA Generic Printer XL',
            'Microsoft Print to PDF',
        ];
    }

    /**
     * Get the configured receipt printer name or UNC network share path.
     */
    public function getReceiptPrinter(): string
    {
        $configPath = config('printing.thermal_printer_path');
        if (! empty($configPath) && $configPath !== 'XP-80C') {
            return (string) $configPath;
        }

        try {
            $companySetting = CompanySetting::getSettings();
            if (! empty($companySetting->thermal_printer_name)) {
                return $companySetting->thermal_printer_name;
            }
        } catch (Throwable) {
            // fallback
        }

        return (string) ($configPath ?: 'XP-80C');
    }

    /**
     * Get the configured document printer name.
     */
    public function getDocumentPrinter(): string
    {
        $configPrinter = config('printing.default_document_printer');
        if (! empty($configPrinter) && $configPrinter !== 'TOSHIBA Generic Printer XL') {
            return (string) $configPrinter;
        }

        try {
            $companySetting = CompanySetting::getSettings();
            if (! empty($companySetting->document_printer_name)) {
                return $companySetting->document_printer_name;
            }
        } catch (Throwable) {
            // fallback
        }

        return (string) ($configPrinter ?: 'TOSHIBA Generic Printer XL');
    }

    /**
     * Execute a print job via the centralized Python print engine.
     *
     * @param  string  $templateId  Registered template identifier
     * @param  array<string, mixed>  $data  Template data dictionary
     * @param  int  $copies  Number of copies (min: 1, max: 500)
     * @param  string|null  $printer  Target Windows printer name
     * @param  bool  $dryRun  If true, render only without sending to Windows spooler
     * @param  string|null  $outputImage  Optional file path to save rendered PNG
     * @return array<string, mixed>
     */
    public function executeJob(
        string $templateId,
        array $data,
        int $copies = 1,
        ?string $printer = null,
        bool $dryRun = false,
        ?string $outputImage = null
    ): array {
        $copies = max(1, min(500, $copies));
        $targetPrinter = $printer ?: $this->defaultPrinter;
        $userId = Auth::id() ?? 'system';

        if (! $this->isAvailable()) {
            $msg = 'محرك الطباعة المركزي غير متاح أو لم يتم ضبط مسار بايثون بشكل صحيح.';
            Log::warning('[PrintEngine] Engine unavailable', [
                'user_id' => $userId,
                'python_path' => $this->pythonPath,
                'engine_script' => $this->engineScript,
                'enabled' => $this->enabled,
            ]);

            return [
                'success' => false,
                'status' => 'UNAVAILABLE',
                'message' => $msg,
            ];
        }

        $jobPayload = [
            'template_id' => $templateId,
            'data' => $data,
            'copies' => $copies,
            'printer_name' => $targetPrinter,
        ];

        if ($networkUser = config('printing.network_user')) {
            $jobPayload['network_user'] = $networkUser;
        }
        if ($networkPassword = config('printing.network_password')) {
            $jobPayload['network_password'] = $networkPassword;
        }

        try {
            $companySetting = CompanySetting::getSettings();
            if (empty($jobPayload['network_user']) && ! empty($companySetting->printer_network_user)) {
                $jobPayload['network_user'] = $companySetting->printer_network_user;
            }
            if (empty($jobPayload['network_password']) && ! empty($companySetting->printer_network_password)) {
                $jobPayload['network_password'] = $companySetting->printer_network_password;
            }
        } catch (Throwable) {
            // ignore
        }

        if (app()->environment('testing')) {
            $dryRun = true;
        }

        $command = [$this->pythonPath, $this->engineScript, '--stdin'];

        if ($dryRun) {
            $command[] = '--dry-run';
        }

        if ($outputImage) {
            $command[] = '--output-image';
            $command[] = $outputImage;
        }

        $jsonInput = json_encode($jobPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        Log::info('[PrintEngine] Dispatching print job', [
            'user_id' => $userId,
            'template_id' => $templateId,
            'copies' => $copies,
            'printer' => $targetPrinter,
            'dry_run' => $dryRun,
        ]);

        $process = new Process($command, null, ['PYTHONIOENCODING' => 'utf-8']);
        $process->setInput($jsonInput);
        $process->setTimeout($this->timeout);

        try {
            $process->run();
            $output = trim($process->getOutput());
            $errorOutput = trim($process->getErrorOutput());

            if (! empty($errorOutput)) {
                Log::debug('[PrintEngine] Engine stderr', ['stderr' => $errorOutput]);
            }

            $decoded = json_decode($output, true);

            if (! is_array($decoded)) {
                Log::error('[PrintEngine] Invalid JSON response from engine', [
                    'exit_code' => $process->getExitCode(),
                    'raw_output' => $output,
                    'stderr' => $errorOutput,
                ]);

                return [
                    'success' => false,
                    'status' => 'INVALID_OUTPUT',
                    'message' => 'استجابة غير صالحة من محرك الطباعة.',
                    'exit_code' => $process->getExitCode(),
                ];
            }

            if (! ($decoded['success'] ?? false)) {
                Log::error('[PrintEngine] Job execution failed', [
                    'template_id' => $templateId,
                    'error' => $decoded['error'] ?? 'Unknown error',
                    'details' => $decoded['details'] ?? null,
                ]);

                return [
                    'success' => false,
                    'status' => $decoded['status'] ?? 'ERROR',
                    'message' => $decoded['message'] ?? 'فشل تنفيذ أمر الطباعة.',
                    'error' => $decoded['error'] ?? null,
                    'details' => $decoded['details'] ?? null,
                ];
            }

            Log::info('[PrintEngine] Job completed successfully', [
                'template_id' => $templateId,
                'status' => $decoded['status'] ?? 'SUCCESS',
                'printer' => $decoded['printer'] ?? $targetPrinter,
            ]);

            return [
                'success' => true,
                'status' => $decoded['status'] ?? 'SUCCESS',
                'message' => $decoded['message'] ?? 'تم إرسال أمر الطباعة بنجاح.',
                'printer' => $decoded['printer'] ?? $targetPrinter,
                'copies' => $copies,
                'template_id' => $templateId,
            ];
        } catch (ProcessTimedOutException $e) {
            Log::error('[PrintEngine] Execution timed out', [
                'timeout' => $this->timeout,
                'template_id' => $templateId,
            ]);

            return [
                'success' => false,
                'status' => 'TIMEOUT',
                'message' => 'تجاوزت عملية الطباعة المهلة الزمنية المحددة ('.$this->timeout.' ثانية).',
            ];
        } catch (Throwable $e) {
            Log::error('[PrintEngine] Unexpected exception during print job', [
                'exception' => $e->getMessage(),
                'template_id' => $templateId,
            ]);

            return [
                'success' => false,
                'status' => 'EXCEPTION',
                'message' => 'حدث خطأ غير متوقع أثناء معالجة أمر الطباعة.',
            ];
        }
    }

    /**
     * Print a thermal barcode sticker for a product.
     *
     * @param  Product  $product  Authoritative product instance
     * @param  int  $quantity  Number of copies
     * @param  string|null  $printer  Target printer
     * @param  array<string, mixed>  $overrides  Optional dimensions or overrides
     * @param  bool  $dryRun  If true, render only without sending to spooler
     * @return array<string, mixed>
     */
    public function printBarcodeLabel(Product $product, int $quantity = 1, ?string $printer = null, array $overrides = [], bool $dryRun = false): array
    {
        $barcodeSettings = BarcodeSetting::getSettings();
        $barcodeService = app(BarcodeGeneratorService::class);
        $barcodeValue = $barcodeService->getBarcodeValue($product);

        $widthMm = (float) ($overrides['width_mm'] ?? $barcodeSettings->label_width_mm ?? 38);
        $heightMm = (float) ($overrides['height_mm'] ?? $barcodeSettings->label_height_mm ?? 15);
        $targetPrinter = $printer ?: ($barcodeSettings->printer_name ?: null);

        $data = [
            'product_name' => $product->name,
            'barcode' => $barcodeValue,
            'sku' => $product->sku ?? '',
            'retail_price' => (float) ($product->retail_price ?? 0),
            'wholesale_price' => (float) ($product->wholesale_price ?? 0),
            'currency' => 'ج.م',
            'show_company_name' => (bool) $barcodeSettings->show_company_name,
            'company_name' => $barcodeSettings->getEffectiveCompanyName(),
            'show_product_name' => (bool) $barcodeSettings->show_product_name,
            'show_barcode_bars' => (bool) $barcodeSettings->show_barcode_bars,
            'show_item_sku' => (bool) $barcodeSettings->show_item_sku,
            'print_price_type' => (string) ($barcodeSettings->print_price_type ?? 'none'),
            'product_font_size' => (int) $barcodeSettings->product_font_size,
            'details_font_size' => (int) $barcodeSettings->details_font_size,
            'barcode_width_px' => (int) $barcodeSettings->barcode_width_px,
            'barcode_height_px' => (int) $barcodeSettings->barcode_height_px,
            'width_mm' => $widthMm,
            'height_mm' => $heightMm,
        ];

        // Apply any caller overrides directly
        foreach ($overrides as $k => $v) {
            $data[$k] = $v;
        }

        return $this->executeJob('barcode_label', $data, $quantity, $targetPrinter, $dryRun);
    }

    /**
     * Print a batch of multiple product barcode labels in a single atomic RAW print job.
     * Prevents blank label feeds between products by maintaining a single continuous stream.
     *
     * @param  array<int, array{product: Product, qty?: int, quantity?: int}>  $items  List of product items and quantities
     * @param  string|null  $printer  Target printer
     * @param  array<string, mixed>  $overrides  Optional dimensions or overrides
     * @param  bool  $dryRun  If true, render only without sending to spooler
     * @return array<string, mixed>
     */
    public function printBarcodeLabelBatch(array $items, ?string $printer = null, array $overrides = [], bool $dryRun = false): array
    {
        if (empty($items)) {
            return [
                'success' => false,
                'status' => 'EMPTY_BATCH',
                'message' => 'لا توجد أصناف صالحة للطباعة في الدفعة.',
            ];
        }

        $barcodeSettings = BarcodeSetting::getSettings();
        $barcodeService = app(BarcodeGeneratorService::class);
        $targetPrinter = $printer ?: ($barcodeSettings->printer_name ?: null);

        $widthMm = (float) ($overrides['width_mm'] ?? $barcodeSettings->label_width_mm ?? 38);
        $heightMm = (float) ($overrides['height_mm'] ?? $barcodeSettings->label_height_mm ?? 15);

        $batchPayload = [];
        $totalQty = 0;

        foreach ($items as $entry) {
            $product = $entry['product'] ?? null;
            if (! $product instanceof Product) {
                continue;
            }

            $quantity = max(1, min(500, (int) ($entry['qty'] ?? $entry['quantity'] ?? 1)));
            $barcodeValue = $barcodeService->getBarcodeValue($product);

            $data = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'barcode' => $barcodeValue,
                'sku' => $product->sku ?? '',
                'retail_price' => (float) ($product->retail_price ?? 0),
                'wholesale_price' => (float) ($product->wholesale_price ?? 0),
                'currency' => 'ج.م',
                'show_company_name' => (bool) $barcodeSettings->show_company_name,
                'company_name' => $barcodeSettings->getEffectiveCompanyName(),
                'show_product_name' => (bool) $barcodeSettings->show_product_name,
                'show_barcode_bars' => (bool) $barcodeSettings->show_barcode_bars,
                'show_item_sku' => (bool) $barcodeSettings->show_item_sku,
                'print_price_type' => (string) ($barcodeSettings->print_price_type ?? 'none'),
                'product_font_size' => (int) $barcodeSettings->product_font_size,
                'details_font_size' => (int) $barcodeSettings->details_font_size,
                'barcode_width_px' => (int) $barcodeSettings->barcode_width_px,
                'barcode_height_px' => (int) $barcodeSettings->barcode_height_px,
                'width_mm' => $widthMm,
                'height_mm' => $heightMm,
            ];

            foreach ($overrides as $k => $v) {
                $data[$k] = $v;
            }

            $batchPayload[] = [
                'template_id' => 'barcode_label',
                'copies' => $quantity,
                'data' => $data,
            ];

            $totalQty += $quantity;
        }

        if (empty($batchPayload)) {
            return [
                'success' => false,
                'status' => 'EMPTY_BATCH',
                'message' => 'لم يتم العثور على أصناف صالحة في الدفعة.',
            ];
        }

        return $this->executeBatchJob($batchPayload, $targetPrinter, $dryRun);
    }

    /**
     * Execute a batch print job payload via the Python printing engine.
     *
     * @param  array<int, array<string, mixed>>  $batchItems  Array of batch entries
     * @param  string|null  $printer  Target Windows printer name
     * @param  bool  $dryRun  If true, render only without sending to spooler
     * @return array<string, mixed>
     */
    public function executeBatchJob(array $batchItems, ?string $printer = null, bool $dryRun = false): array
    {
        $targetPrinter = $printer ?: $this->defaultPrinter;
        $userId = Auth::id() ?? 'system';

        if (! $this->isAvailable()) {
            return [
                'success' => false,
                'status' => 'UNAVAILABLE',
                'message' => 'محرك الطباعة المركزي غير متاح أو لم يتم ضبط مسار بايثون بشكل صحيح.',
            ];
        }

        $jobPayload = [
            'printer_name' => $targetPrinter,
            'batch' => $batchItems,
        ];

        if ($networkUser = config('printing.network_user')) {
            $jobPayload['network_user'] = $networkUser;
        }
        if ($networkPassword = config('printing.network_password')) {
            $jobPayload['network_password'] = $networkPassword;
        }

        try {
            $companySetting = CompanySetting::getSettings();
            if (empty($jobPayload['network_user']) && ! empty($companySetting->printer_network_user)) {
                $jobPayload['network_user'] = $companySetting->printer_network_user;
            }
            if (empty($jobPayload['network_password']) && ! empty($companySetting->printer_network_password)) {
                $jobPayload['network_password'] = $companySetting->printer_network_password;
            }
        } catch (Throwable) {
            // ignore
        }

        if (app()->environment('testing')) {
            $dryRun = true;
        }

        $command = [$this->pythonPath, $this->engineScript, '--stdin'];
        if ($dryRun) {
            $command[] = '--dry-run';
        }

        $jsonInput = json_encode($jobPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        Log::info('[PrintEngine] Dispatching batch print job', [
            'user_id' => $userId,
            'total_items' => count($batchItems),
            'printer' => $targetPrinter,
            'dry_run' => $dryRun,
        ]);

        $process = new Process($command, null, ['PYTHONIOENCODING' => 'utf-8']);
        $process->setInput($jsonInput);
        $process->setTimeout($this->timeout);

        try {
            $process->run();
            $output = trim($process->getOutput());
            $errorOutput = trim($process->getErrorOutput());

            if (! empty($errorOutput)) {
                Log::debug('[PrintEngine] Engine stderr', ['stderr' => $errorOutput]);
            }

            $decoded = json_decode($output, true);
            if (! is_array($decoded)) {
                return [
                    'success' => false,
                    'status' => 'INVALID_OUTPUT',
                    'message' => 'استجابة غير صالحة من محرك الطباعة.',
                    'exit_code' => $process->getExitCode(),
                ];
            }

            if (! ($decoded['success'] ?? false)) {
                return [
                    'success' => false,
                    'status' => $decoded['status'] ?? 'ERROR',
                    'message' => $decoded['message'] ?? 'فشل تنفيذ أمر الطباعة المجمعة.',
                    'error' => $decoded['error'] ?? null,
                ];
            }

            return [
                'success' => true,
                'status' => $decoded['status'] ?? 'SUCCESS',
                'batch_mode' => true,
                'message' => $decoded['message'] ?? 'تم إرسال أمر الطباعة المجمعة بنجاح.',
                'printer' => $decoded['printer'] ?? $targetPrinter,
                'total_products' => $decoded['total_products'] ?? count($batchItems),
                'total_labels' => $decoded['total_labels'] ?? count($batchItems),
                'items_summary' => $decoded['items_summary'] ?? [],
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'status' => 'EXCEPTION',
                'message' => 'حدث خطأ أثناء الاتصال بمحرك الطباعة: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Print a purchase invoice as an 80mm thermal receipt or A4/A5 office document.
     * Supports both signature variants:
     * - ($invoice, $format, $printer, $dryRun)
     * - ($invoice, $printer, $dryRun)
     */
    public function printPurchaseInvoice(PurchaseInvoice $invoice, mixed $formatOrPrinter = 'thermal_80mm', mixed $printerOrDryRun = null, bool $dryRun = false): array
    {
        $format = 'thermal_80mm';
        $printer = null;

        if (is_bool($printerOrDryRun)) {
            $dryRun = $printerOrDryRun;
            if (is_string($formatOrPrinter) && in_array(strtolower($formatOrPrinter), ['thermal_80mm', 'a4', 'a5'], true)) {
                $format = strtolower($formatOrPrinter);
            } else {
                $printer = is_string($formatOrPrinter) ? $formatOrPrinter : null;
            }
        } else {
            if (is_string($formatOrPrinter) && in_array(strtolower($formatOrPrinter), ['thermal_80mm', 'a4', 'a5'], true)) {
                $format = strtolower($formatOrPrinter);
            }
            $printer = is_string($printerOrDryRun) ? $printerOrDryRun : null;
        }

        $invoice->loadMissing(['supplier', 'warehouse', 'creator', 'items.product']);
        $companySetting = CompanySetting::getSettings();
        $isDoc = in_array($format, ['a4', 'a5'], true);

        $items = $invoice->items->map(function ($item) use ($invoice) {
            return [
                'sku' => $item->product?->sku ?? '',
                'name' => $item->product?->name ?? 'صنف غير محدد',
                'quantity' => (float) $item->quantity,
                'unit' => $item->product?->unit ?? 'قطعة',
                'warehouse' => $invoice->warehouse?->name ?? 'الرئيسي',
                'price' => (float) ($item->unit_cost ?? $item->unit_price ?? 0),
                'cost_price' => (float) ($item->unit_cost ?? $item->unit_price ?? 0),
                'total' => (float) ($item->subtotal ?? $item->total_price ?? 0),
            ];
        })->toArray();

        $formattedDate = $invoice->invoice_date ? $invoice->invoice_date->format('Y-m-d') : now()->format('Y-m-d');

        $data = [
            'format' => $format,
            'company_name' => $companySetting->company_name ?: 'شركة كيان للتجارة والتوزيع',
            'company_phone' => $companySetting->phone ?: '',
            'company_address' => $companySetting->address ?: '',
            'invoice_number' => $invoice->invoice_number,
            'date' => $formattedDate,
            'invoice_date' => $formattedDate,
            'supplier_code' => $invoice->supplier?->code ?? ($invoice->supplier?->id ? (string) $invoice->supplier->id : ''),
            'supplier_name' => $invoice->supplier?->name ?? 'مورد عام',
            'supplier_phone' => $invoice->supplier?->phone ?? '',
            'supplier_address' => $invoice->supplier?->address ?? '',
            'warehouse_name' => $invoice->warehouse?->name ?? 'المخزن الرئيسي',
            'creator_name' => $invoice->creator?->name ?? 'أمين المستودع',
            'shipping_company' => 'استلام مستودع',
            'shipping_amount' => (float) ($invoice->shipping_cost ?? 0),
            'notes' => $invoice->notes ?? '',
            'subtotal' => (float) ($invoice->subtotal ?? $invoice->total_amount),
            'total_amount' => (float) $invoice->total_amount,
            'paid_amount' => (float) ($invoice->paid_amount ?? 0),
            'remaining_amount' => (float) ($invoice->remaining_amount ?? 0),
            'items' => $items,
        ];

        $templateId = $isDoc ? 'purchase_invoice_document' : 'purchase_invoice_receipt';
        $defaultPrinter = $isDoc
            ? $this->getDocumentPrinter()
            : $this->getReceiptPrinter();
        $targetPrinter = $printer ?: $defaultPrinter;

        return $this->executeJob($templateId, $data, 1, $targetPrinter, $dryRun);
    }

    /**
     * Print a purchase return as an 80mm thermal receipt or A4/A5 office document.
     */
    public function printPurchaseReturn(PurchaseReturn $return, mixed $formatOrPrinter = 'thermal_80mm', mixed $printerOrDryRun = null, bool $dryRun = false): array
    {
        $format = 'thermal_80mm';
        $printer = null;

        if (is_bool($printerOrDryRun)) {
            $dryRun = $printerOrDryRun;
            if (is_string($formatOrPrinter) && in_array(strtolower($formatOrPrinter), ['thermal_80mm', 'a4', 'a5'], true)) {
                $format = strtolower($formatOrPrinter);
            } else {
                $printer = is_string($formatOrPrinter) ? $formatOrPrinter : null;
            }
        } else {
            if (is_string($formatOrPrinter) && in_array(strtolower($formatOrPrinter), ['thermal_80mm', 'a4', 'a5'], true)) {
                $format = strtolower($formatOrPrinter);
            }
            $printer = is_string($printerOrDryRun) ? $printerOrDryRun : null;
        }

        $return->loadMissing(['supplier', 'warehouse', 'creator', 'items.product', 'items.unit']);
        $companySetting = CompanySetting::getSettings();
        $isDoc = in_array($format, ['a4', 'a5'], true);

        $items = $return->items->map(function ($item) use ($return) {
            return [
                'sku' => $item->product?->sku ?? '',
                'name' => $item->product?->name ?? 'صنف غير محدد',
                'quantity' => (float) $item->quantity,
                'unit' => $item->unit?->name ?? $item->product?->unit ?? 'قطعة',
                'warehouse' => $return->warehouse?->name ?? 'الرئيسي',
                'price' => (float) $item->unit_cost,
                'cost_price' => (float) $item->unit_cost,
                'total' => (float) $item->subtotal,
            ];
        })->toArray();

        $formattedDate = $return->return_date ? $return->return_date->format('Y-m-d') : now()->format('Y-m-d');

        $data = [
            'format' => $format,
            'company_name' => $companySetting->company_name ?: 'شركة كيان للتجارة والتوزيع',
            'company_phone' => $companySetting->phone ?: '',
            'company_address' => $companySetting->address ?: '',
            'invoice_number' => $return->return_number,
            'date' => $formattedDate,
            'invoice_date' => $formattedDate,
            'supplier_code' => $return->supplier?->code ?? ($return->supplier?->id ? (string) $return->supplier->id : ''),
            'supplier_name' => $return->supplier?->name ?? 'مورد عام',
            'supplier_phone' => $return->supplier?->phone ?? '',
            'supplier_address' => $return->supplier?->address ?? '',
            'warehouse_name' => $return->warehouse?->name ?? 'المخزن الرئيسي',
            'creator_name' => $return->creator?->name ?? 'أمين المستودع',
            'shipping_company' => 'إرجاع بضاعة',
            'shipping_amount' => 0.0,
            'notes' => $return->notes ?? '',
            'subtotal' => (float) $return->total_amount,
            'total_amount' => (float) $return->total_amount,
            'paid_amount' => (float) $return->total_amount,
            'remaining_amount' => 0.0,
            'items' => $items,
        ];

        $templateId = $isDoc ? 'purchase_invoice_document' : 'purchase_invoice_receipt';
        $defaultPrinter = $isDoc
            ? $this->getDocumentPrinter()
            : $this->getReceiptPrinter();
        $targetPrinter = $printer ?: $defaultPrinter;

        return $this->executeJob($templateId, $data, 1, $targetPrinter, $dryRun);
    }

    /**
     * Print a sales invoice (POS) as an 80mm thermal receipt or A4/A5 office document.
     * Supports both signature variants:
     * - ($sale, $format, $printer, $dryRun)
     * - ($sale, $printer, $dryRun)
     */
    public function printSalesInvoice(SalesInvoice $sale, mixed $formatOrPrinter = 'thermal_80mm', mixed $printerOrDryRun = null, bool $dryRun = false): array
    {
        $format = 'thermal_80mm';
        $printer = null;

        if (is_bool($printerOrDryRun)) {
            $dryRun = $printerOrDryRun;
            if (is_string($formatOrPrinter) && in_array(strtolower($formatOrPrinter), ['thermal_80mm', 'a4', 'a5'], true)) {
                $format = strtolower($formatOrPrinter);
            } else {
                $printer = is_string($formatOrPrinter) ? $formatOrPrinter : null;
            }
        } else {
            if (is_string($formatOrPrinter) && in_array(strtolower($formatOrPrinter), ['thermal_80mm', 'a4', 'a5'], true)) {
                $format = strtolower($formatOrPrinter);
            }
            $printer = is_string($printerOrDryRun) ? $printerOrDryRun : null;
        }

        $sale->loadMissing(['customer', 'warehouse', 'creator', 'items.product', 'items.unit']);
        $companySetting = CompanySetting::getSettings();
        $isDoc = in_array($format, ['a4', 'a5'], true);

        $items = $sale->items->map(function ($item) use ($sale) {
            return [
                'sku' => $item->product?->sku ?? '',
                'name' => $item->product?->name ?? 'صنف غير محدد',
                'quantity' => (float) $item->quantity,
                'unit' => $item->unit?->name ?? $item->product?->unit ?? 'قطعة',
                'warehouse' => $sale->warehouse?->name ?? 'الرئيسي',
                'price' => (float) $item->unit_price,
                'unit_price' => (float) $item->unit_price,
                'total' => (float) $item->subtotal,
            ];
        })->toArray();

        $formattedDate = $sale->invoice_date ? $sale->invoice_date->format('Y-m-d H:i') : now()->format('Y-m-d H:i');

        $data = [
            'format' => $format,
            'company_name' => $companySetting->company_name ?: 'شركة كيان التجارية',
            'company_phone' => $companySetting->phone ?: '',
            'company_address' => $companySetting->address ?: '',
            'invoice_number' => $sale->invoice_number,
            'customer_code' => $sale->customer?->code ?? ($sale->customer?->id ? (string) $sale->customer->id : ''),
            'customer_name' => $sale->customer?->name ?? 'عميل نقدي',
            'customer_phone' => $sale->customer?->phone ?? '',
            'customer_address' => $sale->customer?->address ?? '',
            'cashier_name' => $sale->creator?->name ?? 'كاشير',
            'warehouse_name' => $sale->warehouse?->name ?? 'المستودع الرئيسي',
            'shipping_company' => 'تسليم مباشر',
            'shipping_amount' => (float) ($sale->shipping_cost ?? 0),
            'notes' => $sale->notes ?? '',
            'date' => $formattedDate,
            'invoice_date' => $formattedDate,
            'subtotal' => (float) $sale->subtotal,
            'discount_amount' => (float) ($sale->discount_amount ?? 0),
            'total_amount' => (float) $sale->total_amount,
            'paid_amount' => (float) ($sale->paid_amount ?? $sale->total_amount),
            'change_amount' => (float) ($sale->change_amount ?? 0),
            'remaining_amount' => max(0.0, (float) $sale->total_amount - (float) ($sale->paid_amount ?? $sale->total_amount)),
            'items' => $items,
        ];

        $templateId = $isDoc ? 'sales_invoice_document' : 'sales_invoice_receipt';
        $defaultPrinter = $isDoc
            ? $this->getDocumentPrinter()
            : $this->getReceiptPrinter();
        $targetPrinter = $printer ?: $defaultPrinter;

        return $this->executeJob($templateId, $data, 1, $targetPrinter, $dryRun);
    }

    /**
     * Print an 80mm thermal receipt for a cashier shift closure.
     *
     * @param  CashierShift  $shift  Authoritative cashier shift instance
     * @param  string|null  $printer  Target printer (e.g. XP-80C)
     * @param  bool  $dryRun  If true, render only without sending to spooler
     * @return array<string, mixed>
     */
    public function printCashierShift(CashierShift $shift, ?string $printer = null, bool $dryRun = false): array
    {
        $shift->loadMissing(['user', 'warehouse', 'closedBy']);
        $companySetting = CompanySetting::getSettings();

        // Get aggregated sold items
        $soldItems = DB::table('sales_invoice_items')
            ->join('sales_invoices', 'sales_invoices.id', '=', 'sales_invoice_items.sales_invoice_id')
            ->join('products', 'products.id', '=', 'sales_invoice_items.product_id')
            ->where('sales_invoices.cashier_shift_id', $shift->id)
            ->where('sales_invoices.status', SalesInvoice::STATUS_POSTED)
            ->select(
                'products.name as name',
                DB::raw('SUM(sales_invoice_items.quantity) as quantity'),
                DB::raw('SUM(sales_invoice_items.subtotal) as total')
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('quantity')
            ->get()
            ->map(function ($row) {
                return [
                    'name' => (string) $row->name,
                    'quantity' => (float) $row->quantity,
                    'total' => (float) $row->total,
                ];
            })
            ->toArray();

        $data = [
            'company_name' => $companySetting->company_name ?: 'شركة كيان التجارية',
            'shift_number' => $shift->shift_number,
            'cashier_name' => $shift->user?->name ?? 'كاشير',
            'closed_by' => $shift->closedBy?->name ?? '',
            'warehouse_name' => $shift->warehouse?->name ?? 'الفرع الرئيسي',
            'opened_at' => $shift->opened_at ? $shift->opened_at->format('Y-m-d H:i') : '',
            'closed_at' => $shift->closed_at ? $shift->closed_at->format('Y-m-d H:i') : '',
            'opening_balance' => (float) $shift->opening_balance,
            'cash_sales' => (float) $shift->cash_sales_amount,
            'cash_additions' => (float) $shift->cash_additions_amount,
            'cash_expenses' => (float) $shift->cash_expenses_amount,
            'cash_returns' => (float) $shift->cash_returns_amount,
            'expected_balance' => (float) $shift->expected_closing_balance,
            'actual_balance' => (float) $shift->actual_closing_balance,
            'difference' => (float) $shift->difference,
            'sold_items' => $soldItems,
        ];

        $receiptPrinter = $printer ?: $this->getReceiptPrinter();

        return $this->executeJob('shift_closing_receipt', $data, 1, $receiptPrinter, $dryRun);
    }

    /**
     * Print a product stock movement ledger as an A4/A5 office document.
     *
     * @param  Product  $product  Authoritative product instance
     * @param  iterable  $movements  Collection or array of StockMovement models
     * @param  string|null  $format  Paper format ('a4', 'a5')
     * @param  string|null  $printer  Target printer
     * @param  bool  $dryRun  If true, render only without sending to spooler
     * @return array<string, mixed>
     */
    public function printProductLedger(Product $product, iterable $movements, ?string $format = 'a4', ?string $printer = null, bool $dryRun = false): array
    {
        $companySetting = CompanySetting::getSettings();
        $fmt = strtolower((string) ($format ?: 'a4'));

        $movementsData = [];
        $runningBalance = 0.0;

        foreach ($movements as $m) {
            $qty = (float) ($m->quantity ?? 0);
            $type = (string) ($m->type ?? 'movement');
            $typeName = method_exists($m, 'getTypeArabicName') ? $m->getTypeArabicName() : $type;

            $movementsData[] = [
                'date' => $m->created_at ? $m->created_at->format('Y-m-d H:i') : '',
                'type_name' => $typeName,
                'reference' => (string) ($m->reference_number ?? $m->reference ?? '-'),
                'quantity_in' => $qty > 0 ? $qty : 0,
                'quantity_out' => $qty < 0 ? abs($qty) : 0,
                'balance_after' => (float) ($m->balance_after ?? 0),
            ];
        }

        $data = [
            'format' => $fmt,
            'company_name' => $companySetting->company_name ?: 'شركة كيان للتجارة والتوزيع',
            'product_name' => $product->name,
            'sku' => $product->sku ?? '',
            'barcode' => $product->barcode ?? '',
            'current_balance' => (float) ($product->total_stock ?? $product->stock_balances_sum_quantity ?? 0),
            'unit' => $product->unit ?? 'قطعة',
            'movements' => $movementsData,
        ];

        $targetPrinter = $printer ?: config('printing.default_document_printer', 'TOSHIBA Generic Printer XL');

        return $this->executeJob('product_ledger_document', $data, 1, $targetPrinter, $dryRun);
    }

    /**
     * Print a low stock inventory shortages report as an A4 document.
     *
     * @param  iterable  $products  Collection or array of Product models with stock balances
     * @param  string|null  $categoryName  Optional category filter name
     * @param  string|null  $printer  Target printer
     * @param  bool  $dryRun  If true, render only without sending to spooler
     * @return array<string, mixed>
     */
    public function printLowStockReport(iterable $products, ?string $categoryName = null, ?string $printer = null, bool $dryRun = false): array
    {
        $companySetting = CompanySetting::getSettings();

        $productsData = [];
        foreach ($products as $p) {
            $curStock = (float) ($p->total_stock ?? $p->stock_balances_sum_quantity ?? 0);
            $minAlert = (float) ($p->min_stock_alert ?? $p->minimum_stock ?? 0);

            $productsData[] = [
                'sku' => $p->sku ?? $p->barcode ?? '-',
                'barcode' => $p->barcode ?? '',
                'name' => $p->name,
                'min_stock_alert' => $minAlert,
                'current_stock' => $curStock,
                'category_name' => $p->category?->name ?? '',
            ];
        }

        $data = [
            'format' => 'a4',
            'company_name' => $companySetting->company_name ?: 'شركة كيان للتجارة والتوزيع',
            'report_date' => now()->format('Y-m-d H:i'),
            'category_name' => $categoryName ?: 'جميع التصنيفات',
            'products' => $productsData,
        ];

        $targetPrinter = $printer ?: config('printing.default_document_printer', 'TOSHIBA Generic Printer XL');

        return $this->executeJob('low_stock_document', $data, 1, $targetPrinter, $dryRun);
    }

    /**
     * Retrieve list of registered templates from the Python engine.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listTemplates(): array
    {
        if (! $this->isAvailable()) {
            return [];
        }

        $process = new Process([$this->pythonPath, $this->engineScript, '--list-templates']);
        $process->setTimeout(5);

        try {
            $process->run();
            $decoded = json_decode($process->getOutput(), true);

            return is_array($decoded) && isset($decoded['templates']) ? $decoded['templates'] : [];
        } catch (Throwable $e) {
            Log::error('[PrintEngine] Failed to list templates', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * Retrieve list of installed Windows printers from the Python engine.
     *
     * @return array<int, string>
     */
    public function listPrinters(): array
    {
        if (! $this->isAvailable()) {
            return [];
        }

        $process = new Process([$this->pythonPath, $this->engineScript, '--list-printers']);
        $process->setTimeout(5);

        try {
            $process->run();
            $decoded = json_decode($process->getOutput(), true);

            return is_array($decoded) && isset($decoded['printers']) ? $decoded['printers'] : [];
        } catch (Throwable $e) {
            Log::error('[PrintEngine] Failed to list printers', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * Dispatch a test print to verify hardware connectivity and template rendering.
     *
     * @param  string  $type  ('thermal_80mm', 'document_a4', 'barcode')
     * @param  string|null  $printerName  Target printer name
     * @param  bool  $dryRun  If true, render only without sending to spooler
     * @return array<string, mixed>
     */
    public function testPrinter(string $type, ?string $printerName = null, bool $dryRun = false): array
    {
        $companySetting = CompanySetting::getSettings();

        if ($type === 'barcode') {
            $barcodeSettings = BarcodeSetting::getSettings();
            $targetPrinter = $printerName ?: ($barcodeSettings->printer_name ?: 'Xprinter XP-233B');

            $data = [
                'product_name' => 'تجربة طباعة باركود - كيان ERP',
                'barcode' => '202610210001',
                'sku' => 'TEST-LABEL',
                'retail_price' => 75.00,
                'wholesale_price' => 50.00,
                'currency' => 'ج.م',
                'show_company_name' => true,
                'company_name' => $barcodeSettings->getEffectiveCompanyName(),
                'show_product_name' => true,
                'show_barcode_bars' => true,
                'show_item_sku' => true,
                'print_price_type' => 'retail',
                'product_font_size' => (int) ($barcodeSettings->product_font_size ?? 14),
                'details_font_size' => (int) ($barcodeSettings->details_font_size ?? 8),
                'barcode_width_px' => (int) ($barcodeSettings->barcode_width_px ?? 260),
                'barcode_height_px' => (int) ($barcodeSettings->barcode_height_px ?? 80),
                'width_mm' => (float) ($barcodeSettings->label_width_mm ?? 38),
                'height_mm' => (float) ($barcodeSettings->label_height_mm ?? 25),
            ];

            return $this->executeJob('barcode_label', $data, 1, $targetPrinter, $dryRun);
        }

        if ($type === 'document_a4' || $type === 'document') {
            $targetPrinter = $printerName ?: $this->getDocumentPrinter();

            $data = [
                'format' => 'a4',
                'company_name' => $companySetting->company_name ?: 'شركة كيان للتجارة والتوزيع',
                'company_phone' => $companySetting->company_phone ?: '01000000000',
                'company_address' => 'القاهرة، جمهورية مصر العربية',
                'invoice_number' => 'TEST-A4-'.date('His'),
                'customer_code' => 'CUST-TEST',
                'customer_name' => 'فحص واختبار طابعة المستندات A4',
                'customer_phone' => '01012345678',
                'customer_address' => 'صفحة اختبارية لنظام كيان ERP',
                'cashier_name' => Auth::user()?->name ?? 'مدير النظام',
                'warehouse_name' => 'المستودع الرئيسي',
                'shipping_company' => 'تسليم يدوي مباشر',
                'shipping_amount' => 0.0,
                'notes' => 'تم إصدار هذه الصفحة كاختبار للتأكد من ربط طابعة المستندات الرسمية والفواتير الضريبية A4.',
                'date' => now()->format('Y-m-d H:i:s'),
                'invoice_date' => now()->format('Y-m-d H:i:s'),
                'subtotal' => 250.00,
                'discount_amount' => 0.00,
                'total_amount' => 250.00,
                'paid_amount' => 250.00,
                'change_amount' => 0.00,
                'remaining_amount' => 0.00,
                'items' => [
                    [
                        'sku' => 'DOC-01',
                        'name' => 'بند اختبار المستندات الرسمية A4 رقم 1',
                        'quantity' => 1,
                        'unit' => 'قطعة',
                        'warehouse' => 'الرئيسي',
                        'price' => 150.00,
                        'unit_price' => 150.00,
                        'total' => 150.00,
                    ],
                    [
                        'sku' => 'DOC-02',
                        'name' => 'بند اختبار المستندات الرسمية A4 رقم 2',
                        'quantity' => 2,
                        'unit' => 'قطعة',
                        'warehouse' => 'الرئيسي',
                        'price' => 50.00,
                        'unit_price' => 50.00,
                        'total' => 100.00,
                    ],
                ],
            ];

            return $this->executeJob('sales_invoice_document', $data, 1, $targetPrinter, $dryRun);
        }

        // Default: Thermal 80mm Cashier Receipt
        $targetPrinter = $printerName ?: $this->getReceiptPrinter();

        $data = [
            'format' => 'thermal_80mm',
            'company_name' => $companySetting->company_name ?: 'شركة كيان للتجارة والتوزيع',
            'company_phone' => $companySetting->company_phone ?: '',
            'company_address' => 'فحص واختبار طابعة الكاشير',
            'invoice_number' => 'TEST-POS-'.date('His'),
            'customer_code' => 'POS-001',
            'customer_name' => 'عميل اختبار طابعة الكاشير',
            'customer_phone' => '',
            'customer_address' => '',
            'cashier_name' => Auth::user()?->name ?? 'كاشير المحل',
            'warehouse_name' => 'المستودع الرئيسي',
            'shipping_company' => 'تسليم مباشر',
            'shipping_amount' => 0.0,
            'notes' => 'تجربة طباعة ناجحة لطابعة إيصالات الكاشير 80 مم.',
            'date' => now()->format('Y-m-d H:i:s'),
            'invoice_date' => now()->format('Y-m-d H:i:s'),
            'subtotal' => 100.00,
            'discount_amount' => 0.00,
            'total_amount' => 100.00,
            'paid_amount' => 100.00,
            'change_amount' => 0.00,
            'remaining_amount' => 0.00,
            'items' => [
                [
                    'sku' => 'TEST-POS',
                    'name' => 'إيصال تجريبي لاختبار طابعة الكاشير',
                    'quantity' => 1,
                    'unit' => 'إيصال',
                    'warehouse' => 'المحل',
                    'price' => 100.00,
                    'unit_price' => 100.00,
                    'total' => 100.00,
                ],
            ],
        ];

        return $this->executeJob('sales_invoice_receipt', $data, 1, $targetPrinter, $dryRun);
    }
}
