<?php

namespace App\Http\Controllers;

use App\Models\BarcodeSetting;
use App\Models\Category;
use App\Models\CompanySetting;
use App\Models\PrintTemplate;
use App\Services\CategoryImportService;
use App\Services\Printing\PythonPrintEngineService;
use App\Services\PrintTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class CompanySettingController extends Controller
{
    /**
     * Show the company, system, barcode printer, and print template settings.
     */
    public function index(): View
    {
        $settings = CompanySetting::getSettings();
        $barcodeSettings = BarcodeSetting::getSettings();
        $activeTab = request('tab', 'company');

        $printTemplateData = [];
        if ($activeTab === 'print_templates') {
            $service = app(PrintTemplateService::class);
            if (PrintTemplate::count() === 0) {
                $service->seedDefaultTemplates();
            }
            $docType = request('doc_type', PrintTemplate::DOC_SALES_INVOICE);
            $paperFormat = request('paper_format', PrintTemplate::FORMAT_A4);
            $templateId = request('template_id') ? (int) request('template_id') : null;

            if (! in_array($docType, PrintTemplateService::SUPPORTED_DOCUMENTS, true)) {
                $docType = PrintTemplate::DOC_SALES_INVOICE;
            }
            if (! in_array($paperFormat, PrintTemplateService::SUPPORTED_FORMATS, true)) {
                $paperFormat = PrintTemplate::FORMAT_A4;
            }

            $printTemplateData = [
                'currentDocType' => $docType,
                'currentPaperFormat' => $paperFormat,
                'currentTemplate' => $service->getEffectiveTemplate($docType, $paperFormat, $templateId),
                'sampleData' => $service->getPreviewSampleData($docType, $paperFormat),
                'defaults' => $service->getDefaultSettings($docType, $paperFormat),
                'templatesList' => PrintTemplate::forDocument($docType)
                    ->forFormat($paperFormat)
                    ->orderByDesc('is_default')
                    ->orderBy('name')
                    ->get(),
                'docTypeLabels' => PrintTemplate::getDocumentTypeLabels(),
                'paperFormatLabels' => PrintTemplate::getPaperFormatLabels(),
            ];
        }

        $departmentsData = [];
        if ($activeTab === 'departments') {
            $departmentsData = [
                'categoriesList' => Category::with('parent')->withCount('products')->orderBy('name')->get(),
                'importPreview' => session('department_import_preview'),
            ];
        }

        $backupsData = [];
        if ($activeTab === 'backups') {
            $backupsData = [
                'backupsList' => app(BackupController::class)->getBackupsList(),
            ];
        }

        $printersData = [];
        if ($activeTab === 'printers') {
            $printersData = [
                'systemPrinters' => app(PythonPrintEngineService::class)->getSystemPrinters(),
            ];
        }

        return view('settings.index', array_merge([
            'title' => 'إعدادات الشركة والنظام',
            'moduleName' => 'الإعدادات',
            'settings' => $settings,
            'barcodeSettings' => $barcodeSettings,
            'activeTab' => $activeTab,
        ], $printTemplateData, $departmentsData, $backupsData, $printersData));
    }

    /**
     * Update the company and system settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $settings = CompanySetting::getSettings();

        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'company_phone' => ['nullable', 'string', 'max:35', 'regex:/^[0-9+\s\-()]+$/'],
            'company_logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'program_status' => ['required', 'string', 'in:active,maintenance'],
        ], [
            'company_name.required' => 'اسم الشركة مطلوب ولا يمكن تركه فارغاً.',
            'company_name.string' => 'اسم الشركة يجب أن يكون نصاً صالحاً.',
            'company_name.max' => 'اسم الشركة يجب ألا يتجاوز 255 حرفاً.',
            'company_phone.max' => 'رقم الهاتف يجب ألا يتجاوز 35 حرفاً.',
            'company_phone.regex' => 'رقم الهاتف يحتوي على أحرف غير صالحة. يرجى إدخال أرقام صحيحة وعلامة + الدولية.',
            'company_logo.image' => 'الملف المرفوع يجب أن يكون صورة صالحة.',
            'company_logo.mimes' => 'صيغ الصور المدعومة هي: PNG, JPG, JPEG, WebP فقط.',
            'company_logo.max' => 'الحد الأقصى لحجم شعار الشركة هو 2 ميجابايت.',
            'program_status.required' => 'حالة البرنامج مطلوبة.',
            'program_status.in' => 'قيمة حالة البرنامج غير صالحة.',
        ]);

        // Handle logo upload safely
        if ($request->hasFile('company_logo')) {
            $file = $request->file('company_logo');
            $extension = $file->getClientOriginalExtension();
            $filename = 'company_'.Str::random(24).'.'.$extension;

            // Store with public disk
            $storedPath = $file->storeAs('company', $filename, 'public');

            // Delete previous logo if a new one was saved successfully
            if ($settings->logo_path && Storage::disk('public')->exists($settings->logo_path)) {
                Storage::disk('public')->delete($settings->logo_path);
            }

            $settings->logo_path = $storedPath;
        }

        // Update fields
        $settings->company_name = $validated['company_name'];
        $settings->company_phone = $validated['company_phone'] ?? null;
        $settings->program_status = $validated['program_status'];
        $settings->save();

        return redirect()->route('settings.index')
            ->with('status', 'success')
            ->with('message', 'تم حفظ إعدادات الشركة والنظام بنجاح.');
    }

    /**
     * Update the barcode printer settings.
     */
    public function updateBarcode(Request $request): RedirectResponse
    {
        $barcodeSettings = BarcodeSetting::getSettings();

        $validated = $request->validate([
            'barcode_prefix' => ['required', 'string', 'max:10'],
            'printer_name' => ['required', 'string', 'max:100'],
            'label_width_mm' => ['required', 'integer', 'min:10', 'max:150'],
            'label_height_mm' => ['required', 'integer', 'min:10', 'max:150'],
            'barcode_width_px' => ['required', 'integer', 'min:50', 'max:1000'],
            'barcode_height_px' => ['required', 'integer', 'min:15', 'max:300'],
            'product_font_size' => ['required', 'integer', 'min:8', 'max:36'],
            'details_font_size' => ['required', 'integer', 'min:6', 'max:28'],
            'company_name_override' => ['nullable', 'string', 'max:255'],
            'print_price_type' => ['required', 'string', 'in:none,retail,wholesale,both'],
        ], [
            'barcode_prefix.required' => 'بادئة الباركود مطلوبة.',
            'printer_name.required' => 'اسم الطابعة مطلوب.',
            'label_width_mm.required' => 'عرض الاستيكر مطلوب بالملليمتر.',
            'label_width_mm.integer' => 'عرض الاستيكر يجب أن يكون رقماً صحيحاً.',
            'label_height_mm.required' => 'ارتفاع الاستيكر مطلوب بالملليمتر.',
            'label_height_mm.integer' => 'ارتفاع الاستيكر يجب أن يكون رقماً صحيحاً.',
            'barcode_width_px.required' => 'عرض صورة الباركود مطلوب بالبكسل.',
            'barcode_height_px.required' => 'ارتفاع صورة الباركود مطلوب بالبكسل.',
            'product_font_size.required' => 'حجم خط اسم المنتج مطلوب.',
            'details_font_size.required' => 'حجم خط تفاصيل الباركود مطلوب.',
            'print_price_type.in' => 'خيار طباعة السعر غير صالح.',
        ]);

        $validated['show_company_name'] = $request->boolean('show_company_name');
        $validated['show_product_name'] = $request->boolean('show_product_name');
        $validated['show_barcode_bars'] = $request->boolean('show_barcode_bars');
        $validated['show_item_sku'] = $request->boolean('show_item_sku');

        $barcodeSettings->update($validated);

        return redirect()->route('settings.index', ['tab' => 'barcode'])
            ->with('status', 'success')
            ->with('message', 'تم حفظ إعدادات طابعة الباركود بنجاح.');
    }

    /**
     * Update printer device hardware assignments and network configurations.
     */
    public function updatePrinters(Request $request): RedirectResponse
    {
        $settings = CompanySetting::getSettings();

        $validated = $request->validate([
            'thermal_printer_name' => ['nullable', 'string', 'max:255'],
            'document_printer_name' => ['nullable', 'string', 'max:255'],
            'barcode_printer_name' => ['nullable', 'string', 'max:255'],
            'receipt_print_mode' => ['required', 'string', 'in:browser_kiosk,server_python'],
            'printer_network_user' => ['nullable', 'string', 'max:100'],
            'printer_network_password' => ['nullable', 'string', 'max:255'],
            'auto_print_on_save' => ['nullable', 'boolean'],
        ], [
            'receipt_print_mode.required' => 'يرجى اختيار طريقة طباعة إيصالات الكاشير.',
            'receipt_print_mode.in' => 'طريقة طباعة الإيصالات المختارة غير صالحة.',
        ]);

        $settings->thermal_printer_name = $validated['thermal_printer_name'] ?? null;
        $settings->document_printer_name = $validated['document_printer_name'] ?? null;
        $settings->receipt_print_mode = $validated['receipt_print_mode'];
        $settings->printer_network_user = $validated['printer_network_user'] ?? null;
        if (! empty($validated['printer_network_password'])) {
            $settings->printer_network_password = $validated['printer_network_password'];
        } elseif ($request->has('clear_network_password')) {
            $settings->printer_network_password = null;
        }
        $settings->auto_print_on_save = $request->boolean('auto_print_on_save', true);
        $settings->save();

        if (! empty($validated['barcode_printer_name'])) {
            $barcodeSettings = BarcodeSetting::getSettings();
            $barcodeSettings->printer_name = $validated['barcode_printer_name'];
            $barcodeSettings->save();
        }

        return redirect()->route('settings.index', ['tab' => 'printers'])
            ->with('status', 'success')
            ->with('message', 'تم حفظ إعدادات وتخصيصات الطابعات بنجاح.');
    }

    /**
     * Discover all printers installed on Windows or locally available.
     */
    public function discoverPrinters(PythonPrintEngineService $printService): JsonResponse
    {
        $printers = $printService->getSystemPrinters();

        return response()->json([
            'success' => true,
            'count' => count($printers),
            'printers' => $printers,
            'message' => 'تم فحص النظام واسترجاع قائمة الطابعات المتاحة بنجاح.',
        ]);
    }

    /**
     * Execute a hardware test print job for a specific printer.
     */
    public function testPrint(Request $request, PythonPrintEngineService $printService): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:thermal_80mm,document_a4,barcode'],
            'printer_name' => ['nullable', 'string', 'max:255'],
        ]);

        $type = $validated['type'];
        $printerName = $validated['printer_name'] ?? null;

        $result = $printService->testPrinter($type, $printerName);

        if (! empty($result['success'])) {
            return response()->json([
                'success' => true,
                'message' => 'تم إرسال أمر الطباعة التجريبية بنجاح إلى ('.($result['printer'] ?? $printerName ?? 'الطابعة المحددة').'). يرجى التأكد من خروج الورقة.',
                'details' => $result,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'] ?? 'فشل إرسال أمر الطباعة التجريبية.',
            'error' => $result['error'] ?? null,
            'details' => $result,
        ], 422);
    }

    /**
     * Download the standard CSV template for Department / Category import.
     */
    public function downloadDepartmentTemplate(CategoryImportService $importService): Response
    {
        $csvContent = $importService->generateTemplateCsv();

        return response($csvContent, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="departments_categories_template.csv"',
        ]);
    }

    /**
     * Upload and preview departments file before commit.
     */
    public function previewDepartmentImport(Request $request, CategoryImportService $importService): RedirectResponse
    {
        $request->validate([
            'import_file' => ['required', 'file', 'mimes:xlsx,csv,txt', 'max:10240'],
        ], [
            'import_file.required' => 'يرجى اختيار ملف Excel أو CSV للاستيراد.',
            'import_file.mimes' => 'صيغة الملف غير مدعومة. الصيغ المسموحة هي: .xlsx, .csv',
            'import_file.max' => 'حجم الملف يجب ألا يتجاوز 10 ميجابايت.',
        ]);

        try {
            $rawRows = $importService->parseFile($request->file('import_file'));
            $preview = $importService->validateImport($rawRows);

            return redirect()->route('settings.index', ['tab' => 'departments'])
                ->with('department_import_preview', $preview);
        } catch (\Throwable $e) {
            return redirect()->route('settings.index', ['tab' => 'departments'])
                ->with('error', 'حدث خطأ أثناء قراءة الملف: '.$e->getMessage());
        }
    }

    /**
     * Commit valid department rows to database inside a transaction.
     */
    public function importDepartments(Request $request, CategoryImportService $importService): RedirectResponse
    {
        $validated = $request->validate([
            'valid_rows' => ['required', 'string'],
        ]);

        $rows = json_decode($validated['valid_rows'], true);
        if (! is_array($rows) || empty($rows)) {
            return redirect()->route('settings.index', ['tab' => 'departments'])
                ->with('error', 'لا توجد أسطر صالحة للاستيراد.');
        }

        try {
            $result = $importService->commitImport($rows);

            return redirect()->route('settings.index', ['tab' => 'departments'])
                ->with('success', $result['message']);
        } catch (\Throwable $e) {
            return redirect()->route('settings.index', ['tab' => 'departments'])
                ->with('error', 'فشلت عملية حفظ الأقسام: '.$e->getMessage());
        }
    }
}
