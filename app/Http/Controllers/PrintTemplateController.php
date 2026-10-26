<?php

namespace App\Http\Controllers;

use App\Models\PrintTemplate;
use App\Services\PrintTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PrintTemplateController extends Controller
{
    public function __construct(
        protected PrintTemplateService $templateService
    ) {}

    /**
     * Fetch template configuration and live preview sample data.
     */
    public function data(Request $request): JsonResponse
    {
        $docType = $request->query('doc_type', PrintTemplate::DOC_SALES_INVOICE);
        $paperFormat = $request->query('paper_format', PrintTemplate::FORMAT_A4);
        $templateId = $request->query('template_id') ? (int) $request->query('template_id') : null;

        if (! in_array($docType, PrintTemplateService::SUPPORTED_DOCUMENTS, true)) {
            $docType = PrintTemplate::DOC_SALES_INVOICE;
        }

        if (! in_array($paperFormat, PrintTemplateService::SUPPORTED_FORMATS, true)) {
            $paperFormat = PrintTemplate::FORMAT_A4;
        }

        $template = $this->templateService->getEffectiveTemplate($docType, $paperFormat, $templateId);
        $sampleData = $this->templateService->getPreviewSampleData($docType, $paperFormat);
        $defaults = $this->templateService->getDefaultSettings($docType, $paperFormat);

        $templatesList = PrintTemplate::forDocument($docType)
            ->forFormat($paperFormat)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get(['id', 'name', 'is_default']);

        return response()->json([
            'success' => true,
            'template' => $template,
            'sample_data' => $sampleData,
            'defaults' => $defaults,
            'templates_list' => $templatesList,
        ]);
    }

    /**
     * Update an existing print template configuration.
     */
    public function update(Request $request, PrintTemplate $template): JsonResponse|RedirectResponse
    {
        // Support both AJAX JSON payloads and standard form submissions
        $name = $request->input('name') ?? $request->input('template_name');
        $isDefault = $request->has('template_is_default')
            ? $request->boolean('template_is_default')
            : ($request->has('is_default') ? $request->boolean('is_default') : $template->is_default);

        $request->merge([
            'name' => $name,
            'is_default' => $isDefault,
        ]);

        $validatedData = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'is_default' => ['sometimes', 'boolean'],
            'settings' => ['required', 'array'],
        ], [
            'name.required' => 'اسم القالب مطلوب ولا يمكن تركه فارغاً.',
            'name.max' => 'اسم القالب يجب ألا يتجاوز 100 حرف.',
            'settings.required' => 'بيانات إعدادات القالب مطلوبة.',
            'settings.array' => 'هيكل بيانات الإعدادات غير صالح.',
        ]);

        $sanitizedSettings = $this->templateService->validateSettings(
            $validatedData['settings'],
            $template->document_type,
            $template->paper_format
        );

        if ($isDefault && ! $template->is_default) {
            // Unmark any existing default for this combination
            PrintTemplate::forDocument($template->document_type)
                ->forFormat($template->paper_format)
                ->where('id', '!=', $template->id)
                ->update(['is_default' => false]);
        }

        $template->update([
            'name' => trim(strip_tags($validatedData['name'])),
            'is_default' => $isDefault,
            'settings' => $sanitizedSettings,
            'version' => $template->version + 1,
        ]);

        if (! $request->wantsJson()) {
            return redirect()
                ->route('settings.index', [
                    'tab' => 'print_templates',
                    'doc_type' => $template->document_type,
                    'paper_format' => $template->paper_format,
                    'template_id' => $template->id,
                ])
                ->with('status', 'success')
                ->with('message', 'تم حفظ إعدادات القالب بنجاح.');
        }

        return response()->json([
            'success' => true,
            'message' => 'تم حفظ إعدادات القالب بنجاح.',
            'template' => $template->fresh(),
        ]);
    }

    /**
     * Reset a print template to system defaults.
     */
    public function reset(Request $request, PrintTemplate $template): JsonResponse
    {
        $defaultSettings = $this->templateService->getDefaultSettings(
            $template->document_type,
            $template->paper_format
        );

        $template->update([
            'settings' => $defaultSettings,
            'version' => $template->version + 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تمت استعادة الإعدادات الافتراضية للقالب بنجاح.',
            'template' => $template->fresh(),
        ]);
    }

    /**
     * Duplicate a template as a custom copy.
     */
    public function duplicate(Request $request, PrintTemplate $template): JsonResponse
    {
        $copyName = trim(strip_tags($request->input('name', "{$template->name} (نسخة)")));

        // Ensure unique name
        $counter = 1;
        $originalCopyName = $copyName;
        while (PrintTemplate::forDocument($template->document_type)
            ->forFormat($template->paper_format)
            ->where('name', $copyName)
            ->exists()) {
            $copyName = "{$originalCopyName} - {$counter}";
            $counter++;
        }

        $newTemplate = PrintTemplate::create([
            'document_type' => $template->document_type,
            'paper_format' => $template->paper_format,
            'name' => $copyName,
            'is_default' => false,
            'settings' => $template->settings,
            'version' => 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء نسخة جديدة من القالب بنجاح.',
            'template' => $newTemplate,
        ]);
    }
}
