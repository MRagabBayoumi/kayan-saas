<?php

namespace App\Http\Controllers;

use App\Services\ProductImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ProductImportController extends Controller
{
    /**
     * Download pre-formatted ready-to-use template (CSV by default, or .xlsx if requested).
     */
    public function downloadTemplate(Request $request, ProductImportService $service): Response
    {
        $format = strtolower((string) $request->input('format', 'csv'));

        if ($format === 'xlsx') {
            $xlsxContent = $service->generateTemplateXlsx();

            return response($xlsxContent, 200, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="kayan_products_template.xlsx"',
            ]);
        }

        $csvContent = $service->generateTemplateCsv();

        return response($csvContent, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="kayan_products_template.csv"',
        ]);
    }

    /**
     * Show import interface (Upload Step).
     */
    public function showForm(): View
    {
        return view('products.import', [
            'title' => 'استيراد المنتجات من إكسيل / CSV',
            'moduleName' => 'المخزون والمنتجات',
            'reviewData' => session('import_review_data'),
        ]);
    }

    /**
     * Upload file and review/audit contents against database `kayan_erp` before importing.
     */
    public function review(Request $request, ProductImportService $service): View|RedirectResponse
    {
        @set_time_limit(300);

        $request->validate([
            'import_file' => ['required', 'file', 'max:10240', 'mimes:csv,txt,xlsx'],
        ], [
            'import_file.required' => 'يرجى اختيار ملف إكسيل أو CSV للرفع.',
            'import_file.file' => 'الملف المرفوع غير صالح.',
            'import_file.max' => 'الحد الأقصى لحجم الملف هو 10 ميجابايت.',
            'import_file.mimes' => 'صيغ الملفات المدعومة هي: CSV, XLSX فقط.',
        ]);

        try {
            $file = $request->file('import_file');
            $parsedRows = $service->parseFile($file);

            if (empty($parsedRows)) {
                return redirect()->route('products.import.form')
                    ->with('status', 'error')
                    ->with('message', 'الملف المرفوع فارغ أو لم يتم العثور على أي سطور بيانات مطابقة.');
            }

            $auditResult = $service->auditRows($parsedRows);

            // Store reviewed data in session for confirmation
            session(['import_review_data' => $auditResult]);

            return view('products.import', [
                'title' => 'مراجعة وتدقيق بيانات الاستيراد',
                'moduleName' => 'المخزون والمنتجات',
                'reviewData' => $auditResult,
            ]);
        } catch (\Throwable $e) {
            Log::error('Product import review failed: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return redirect()->route('products.import.form')
                ->with('status', 'error')
                ->with('message', 'حدث خطأ أثناء فحص ملف الاستيراد: '.$e->getMessage());
        }
    }

    /**
     * Confirm and execute the import safely inside a transaction.
     */
    public function confirm(Request $request, ProductImportService $service): RedirectResponse
    {
        @set_time_limit(300);

        $reviewData = session('import_review_data');

        if (! $reviewData || empty($reviewData['rows'])) {
            return redirect()->route('products.import.form')
                ->with('status', 'error')
                ->with('message', 'انتهت صلاحية جلسة المراجعة. يرجى رفع الملف مرة أخرى.');
        }

        try {
            $updateExisting = $request->boolean('update_existing', true);
            $updatePrices = $request->boolean('update_prices', false);
            $createCategories = $request->boolean('create_categories', true);
            $canEditPrices = $request->user()?->can('edit-product-prices') ?? false;

            $result = $service->executeImport($reviewData['rows'], $updateExisting, $createCategories, $canEditPrices, $updatePrices);

            // Clear session review data
            session()->forget('import_review_data');

            $message = "تمت عملية الاستيراد بنجاح! تم إدراج {$result['inserted']} صنف جديد";
            if ($result['updated'] > 0) {
                $message .= "، وتحديث بيانات {$result['updated']} صنف قائم";
            }
            if ($result['skipped'] > 0) {
                $message .= "، وتخطي {$result['skipped']} سطر";
            }
            $message .= '.';

            return redirect()->route('products.index')
                ->with('status', 'success')
                ->with('message', $message);
        } catch (\Throwable $e) {
            Log::error('Product import confirm failed: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return redirect()->route('products.import.form')
                ->with('status', 'error')
                ->with('message', 'حدث خطأ أثناء حفظ بيانات الاستيراد: '.$e->getMessage());
        }
    }

    /**
     * Cancel the current review session and return to upload.
     */
    public function cancel(): RedirectResponse
    {
        session()->forget('import_review_data');

        return redirect()->route('products.import.form');
    }
}
