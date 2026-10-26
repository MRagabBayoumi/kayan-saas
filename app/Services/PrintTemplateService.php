<?php

namespace App\Services;

use App\Models\CompanySetting;
use App\Models\PrintTemplate;
use InvalidArgumentException;

class PrintTemplateService
{
    /**
     * Supported document types.
     */
    public const SUPPORTED_DOCUMENTS = [
        PrintTemplate::DOC_SALES_INVOICE,
        PrintTemplate::DOC_PURCHASE_INVOICE,
        PrintTemplate::DOC_CUSTOMER_STATEMENT,
        PrintTemplate::DOC_SUPPLIER_STATEMENT,
    ];

    /**
     * Supported paper formats.
     */
    public const SUPPORTED_FORMATS = [
        PrintTemplate::FORMAT_A4,
        PrintTemplate::FORMAT_A5,
        PrintTemplate::FORMAT_THERMAL_80MM,
    ];

    /**
     * Get default settings for a given document type and paper format.
     *
     * @return array<string, mixed>
     */
    public function getDefaultSettings(string $documentType, string $paperFormat): array
    {
        $this->assertSupported($documentType, $paperFormat);

        $isThermal = ($paperFormat === PrintTemplate::FORMAT_THERMAL_80MM);
        $isA5 = ($paperFormat === PrintTemplate::FORMAT_A5);

        $general = [
            'show_logo' => true,
            'logo_size' => ($isThermal || $isA5) ? 'sm' : 'md',
            'custom_logo' => null,
            'show_company_name' => true,
            'show_company_phone' => true,
            'show_company_address' => ! $isThermal,
            'custom_title' => $this->getDefaultTitle($documentType),
            'custom_subtitle' => $this->getDefaultSubtitle($documentType),
            'primary_color' => $isThermal ? '#000000' : '#1e293b',
            'font_size' => ($isThermal || $isA5) ? 'sm' : 'md',
        ];

        $documentMeta = [
            'show_doc_number' => true,
            'show_doc_date' => true,
            'show_due_date' => false,
            'show_warehouse' => true,
            'show_status_badge' => ! $isThermal,
            'show_barcode' => true,
            'show_qr_code' => true,
        ];

        $partyMeta = [
            'party_type_label' => $this->getDefaultPartyLabel($documentType),
            'show_party_name' => true,
            'show_party_phone' => true,
            'show_party_address' => ! $isThermal,
            'show_party_tax_no' => false,
        ];

        $tableColumns = [
            'show_index' => true,
            'show_sku' => ! $isThermal,
            'show_barcode' => false,
            'show_unit' => true,
            'show_warehouse' => ! $isThermal,
            'show_price' => true,
            'show_discount' => ! $isThermal,
            'show_tax' => false,
            'show_total' => true,
            'show_notes' => (! $isThermal && ! $isA5),
            'density' => ($isThermal || $isA5) ? 'compact' : 'comfortable',
        ];

        $financialSummary = [
            'show_subtotal' => true,
            'show_discount' => true,
            'show_shipping' => true,
            'show_extra_expenses' => ($documentType === PrintTemplate::DOC_PURCHASE_INVOICE),
            'show_tax' => false,
            'show_net_total' => true,
            'show_paid' => true,
            'show_remaining' => true,
        ];

        $statementOptions = [
            'show_date_range' => true,
            'show_opening_balance' => true,
            'show_debit_credit' => true,
            'show_running_balance' => true,
            'show_summary_cards' => ! $isThermal,
        ];

        $notesAndTerms = [
            'show_notes' => true,
            'notes_text' => 'شكراً لتعاملكم مع كيان للتجارة والتوزيع.',
            'show_terms' => ! $isThermal,
            'terms_text' => $this->getDefaultTermsText($documentType),
        ];

        $signatures = [
            'show_signatures' => ! $isThermal,
            'first_party_title' => $this->getDefaultFirstPartySignature($documentType),
            'second_party_title' => 'المسؤول / المحاسب',
            'show_stamp_box' => (! $isThermal && ! $isA5),
        ];

        return [
            'general' => $general,
            'document_meta' => $documentMeta,
            'party_meta' => $partyMeta,
            'table_columns' => $tableColumns,
            'financial_summary' => $financialSummary,
            'statement_options' => $statementOptions,
            'notes_and_terms' => $notesAndTerms,
            'signatures' => $signatures,
        ];
    }

    /**
     * Validate and sanitize settings against whitelist schema.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function validateSettings(array $input, string $documentType, string $paperFormat): array
    {
        $defaults = $this->getDefaultSettings($documentType, $paperFormat);

        $validated = [];

        // 1. General
        $validated['general'] = [
            'show_logo' => (bool) ($input['general']['show_logo'] ?? $defaults['general']['show_logo']),
            'logo_size' => in_array($input['general']['logo_size'] ?? '', ['sm', 'md', 'lg'], true)
                ? $input['general']['logo_size']
                : $defaults['general']['logo_size'],
            'custom_logo' => ! empty($input['general']['custom_logo']) ? (string) $input['general']['custom_logo'] : null,
            'show_company_name' => (bool) ($input['general']['show_company_name'] ?? $defaults['general']['show_company_name']),
            'show_company_phone' => (bool) ($input['general']['show_company_phone'] ?? $defaults['general']['show_company_phone']),
            'show_company_address' => (bool) ($input['general']['show_company_address'] ?? $defaults['general']['show_company_address']),
            'custom_title' => $this->sanitizeText($input['general']['custom_title'] ?? $defaults['general']['custom_title'], 100),
            'custom_subtitle' => $this->sanitizeText($input['general']['custom_subtitle'] ?? $defaults['general']['custom_subtitle'], 150),
            'primary_color' => $this->sanitizeColor($input['general']['primary_color'] ?? '', $defaults['general']['primary_color']),
            'font_size' => in_array($input['general']['font_size'] ?? '', ['sm', 'md', 'lg'], true)
                ? $input['general']['font_size']
                : $defaults['general']['font_size'],
        ];

        // 2. Document Meta
        $validated['document_meta'] = [
            'show_doc_number' => (bool) ($input['document_meta']['show_doc_number'] ?? $defaults['document_meta']['show_doc_number']),
            'show_doc_date' => (bool) ($input['document_meta']['show_doc_date'] ?? $defaults['document_meta']['show_doc_date']),
            'show_due_date' => (bool) ($input['document_meta']['show_due_date'] ?? $defaults['document_meta']['show_due_date']),
            'show_warehouse' => (bool) ($input['document_meta']['show_warehouse'] ?? $defaults['document_meta']['show_warehouse']),
            'show_status_badge' => (bool) ($input['document_meta']['show_status_badge'] ?? $defaults['document_meta']['show_status_badge']),
            'show_barcode' => (bool) ($input['document_meta']['show_barcode'] ?? $defaults['document_meta']['show_barcode']),
            'show_qr_code' => (bool) ($input['document_meta']['show_qr_code'] ?? $defaults['document_meta']['show_qr_code']),
        ];

        // 3. Party Meta
        $validated['party_meta'] = [
            'party_type_label' => $this->sanitizeText($input['party_meta']['party_type_label'] ?? $defaults['party_meta']['party_type_label'], 50),
            'show_party_name' => (bool) ($input['party_meta']['show_party_name'] ?? $defaults['party_meta']['show_party_name']),
            'show_party_phone' => (bool) ($input['party_meta']['show_party_phone'] ?? $defaults['party_meta']['show_party_phone']),
            'show_party_address' => (bool) ($input['party_meta']['show_party_address'] ?? $defaults['party_meta']['show_party_address']),
            'show_party_tax_no' => (bool) ($input['party_meta']['show_party_tax_no'] ?? $defaults['party_meta']['show_party_tax_no']),
        ];

        // 4. Table Columns
        $validated['table_columns'] = [
            'show_index' => (bool) ($input['table_columns']['show_index'] ?? $defaults['table_columns']['show_index']),
            'show_sku' => (bool) ($input['table_columns']['show_sku'] ?? $defaults['table_columns']['show_sku']),
            'show_barcode' => (bool) ($input['table_columns']['show_barcode'] ?? $defaults['table_columns']['show_barcode']),
            'show_unit' => (bool) ($input['table_columns']['show_unit'] ?? $defaults['table_columns']['show_unit']),
            'show_warehouse' => (bool) ($input['table_columns']['show_warehouse'] ?? $defaults['table_columns']['show_warehouse']),
            'show_price' => (bool) ($input['table_columns']['show_price'] ?? $defaults['table_columns']['show_price']),
            'show_discount' => (bool) ($input['table_columns']['show_discount'] ?? $defaults['table_columns']['show_discount']),
            'show_tax' => (bool) ($input['table_columns']['show_tax'] ?? $defaults['table_columns']['show_tax']),
            'show_total' => (bool) ($input['table_columns']['show_total'] ?? $defaults['table_columns']['show_total']),
            'show_notes' => (bool) ($input['table_columns']['show_notes'] ?? $defaults['table_columns']['show_notes']),
            'density' => in_array($input['table_columns']['density'] ?? '', ['compact', 'comfortable'], true)
                ? $input['table_columns']['density']
                : $defaults['table_columns']['density'],
        ];

        // 5. Financial Summary
        $validated['financial_summary'] = [
            'show_subtotal' => (bool) ($input['financial_summary']['show_subtotal'] ?? $defaults['financial_summary']['show_subtotal']),
            'show_discount' => (bool) ($input['financial_summary']['show_discount'] ?? $defaults['financial_summary']['show_discount']),
            'show_shipping' => (bool) ($input['financial_summary']['show_shipping'] ?? $defaults['financial_summary']['show_shipping']),
            'show_extra_expenses' => (bool) ($input['financial_summary']['show_extra_expenses'] ?? $defaults['financial_summary']['show_extra_expenses']),
            'show_tax' => (bool) ($input['financial_summary']['show_tax'] ?? $defaults['financial_summary']['show_tax']),
            'show_net_total' => (bool) ($input['financial_summary']['show_net_total'] ?? $defaults['financial_summary']['show_net_total']),
            'show_paid' => (bool) ($input['financial_summary']['show_paid'] ?? $defaults['financial_summary']['show_paid']),
            'show_remaining' => (bool) ($input['financial_summary']['show_remaining'] ?? $defaults['financial_summary']['show_remaining']),
        ];

        // 6. Statement Options
        $validated['statement_options'] = [
            'show_date_range' => (bool) ($input['statement_options']['show_date_range'] ?? $defaults['statement_options']['show_date_range']),
            'show_opening_balance' => (bool) ($input['statement_options']['show_opening_balance'] ?? $defaults['statement_options']['show_opening_balance']),
            'show_debit_credit' => (bool) ($input['statement_options']['show_debit_credit'] ?? $defaults['statement_options']['show_debit_credit']),
            'show_running_balance' => (bool) ($input['statement_options']['show_running_balance'] ?? $defaults['statement_options']['show_running_balance']),
            'show_summary_cards' => (bool) ($input['statement_options']['show_summary_cards'] ?? $defaults['statement_options']['show_summary_cards']),
        ];

        // 7. Notes and Terms
        $validated['notes_and_terms'] = [
            'show_notes' => (bool) ($input['notes_and_terms']['show_notes'] ?? $defaults['notes_and_terms']['show_notes']),
            'notes_text' => $this->sanitizeText($input['notes_and_terms']['notes_text'] ?? $defaults['notes_and_terms']['notes_text'], 500),
            'show_terms' => (bool) ($input['notes_and_terms']['show_terms'] ?? $defaults['notes_and_terms']['show_terms']),
            'terms_text' => $this->sanitizeText($input['notes_and_terms']['terms_text'] ?? $defaults['notes_and_terms']['terms_text'], 1000),
        ];

        // 8. Signatures
        $validated['signatures'] = [
            'show_signatures' => (bool) ($input['signatures']['show_signatures'] ?? $defaults['signatures']['show_signatures']),
            'first_party_title' => $this->sanitizeText($input['signatures']['first_party_title'] ?? $defaults['signatures']['first_party_title'], 60),
            'second_party_title' => $this->sanitizeText($input['signatures']['second_party_title'] ?? $defaults['signatures']['second_party_title'], 60),
            'show_stamp_box' => (bool) ($input['signatures']['show_stamp_box'] ?? $defaults['signatures']['show_stamp_box']),
        ];

        return $validated;
    }

    /**
     * Get the effective template model or fallback to safe defaults.
     */
    public function getEffectiveTemplate(string $documentType, string $paperFormat, ?int $templateId = null): PrintTemplate
    {
        $this->assertSupported($documentType, $paperFormat);

        if ($templateId !== null) {
            $template = PrintTemplate::forDocument($documentType)
                ->forFormat($paperFormat)
                ->find($templateId);

            if ($template) {
                return $template;
            }
        }

        // Try to load default from DB
        $defaultTemplate = PrintTemplate::forDocument($documentType)
            ->forFormat($paperFormat)
            ->default()
            ->first();

        if ($defaultTemplate) {
            return $defaultTemplate;
        }

        // Return a fresh in-memory instance with defaults
        $instance = new PrintTemplate([
            'document_type' => $documentType,
            'paper_format' => $paperFormat,
            'name' => $this->getDefaultTemplateName($documentType, $paperFormat),
            'is_default' => true,
            'settings' => $this->getDefaultSettings($documentType, $paperFormat),
            'version' => 1,
        ]);

        return $instance;
    }

    /**
     * Seed all 8 standard default templates if they do not exist.
     *
     * @return int Count of newly created templates
     */
    public function seedDefaultTemplates(): int
    {
        $created = 0;

        foreach (self::SUPPORTED_DOCUMENTS as $docType) {
            foreach (self::SUPPORTED_FORMATS as $format) {
                $exists = PrintTemplate::forDocument($docType)
                    ->forFormat($format)
                    ->default()
                    ->exists();

                if (! $exists) {
                    PrintTemplate::create([
                        'document_type' => $docType,
                        'paper_format' => $format,
                        'name' => $this->getDefaultTemplateName($docType, $format),
                        'is_default' => true,
                        'settings' => $this->getDefaultSettings($docType, $format),
                        'version' => 1,
                    ]);
                    $created++;
                }
            }
        }

        return $created;
    }

    /**
     * Generate representative sample data for live preview.
     * ZERO database mutations, strictly read-only and memory-bound.
     *
     * @return array<string, mixed>
     */
    public function getPreviewSampleData(string $documentType, string $paperFormat): array
    {
        $this->assertSupported($documentType, $paperFormat);

        $companySetting = class_exists(CompanySetting::class) ? CompanySetting::first() : null;
        $commonCompany = [
            'name' => $companySetting?->company_name ?: 'شركة كيان للتجارة والتوزيع',
            'phone' => $companySetting?->company_phone ?: '01012345678',
            'address' => 'شارع الجمهورية، المنصورة، مصر',
            'tax_number' => '123-456-789',
            'commercial_reg' => '54321',
            'logo_url' => $companySetting?->logo_url ?: null,
        ];

        switch ($documentType) {
            case PrintTemplate::DOC_SALES_INVOICE:
                return [
                    'is_sample' => true,
                    'watermark' => 'معاينة تجريبية - فواتير المبيعات قيد التطوير (المرحلة 8)',
                    'company' => $commonCompany,
                    'document' => [
                        'type' => 'sales_invoice',
                        'type_label' => 'إذن صرف',
                        'number' => 'INV-2026-0042',
                        'date' => '2026-10-14',
                        'due_date' => '2026-10-21',
                        'warehouse' => 'المخزن الرئيسي (المنصورة)',
                        'shipping_company' => 'تسليم مباشر',
                        'status' => 'مؤكدة',
                        'status_color' => 'success',
                        'barcode' => 'INV20260042',
                        'qr_payload' => 'INV:INV-2026-0042|TOTAL:1850.00|DATE:2026-10-14',
                    ],
                    'party' => [
                        'type' => 'customer',
                        'code' => 'C-104',
                        'name' => 'بوتيك الأناقة للملابس والإكسسوارات',
                        'contact_person' => 'أستاذة مروة إبراهيم',
                        'phone' => '01123456789',
                        'address' => 'المشاية السفلية، برج الصفوة، المنصورة',
                        'tax_no' => '987-654-321',
                    ],
                    'items' => [
                        [
                            'index' => 1,
                            'name' => 'توكة شعر ستان سواريه فيونكة كلاسيك',
                            'sku' => 'HAIR-STN-01',
                            'barcode' => '62210001001',
                            'unit' => 'قطعة',
                            'warehouse' => 'الرئيسي',
                            'quantity' => 20.00,
                            'unit_price' => 35.00,
                            'discount' => 50.00,
                            'subtotal' => 650.00,
                            'notes' => 'ألوان مشكلة (وردي، أسود، كحلي)',
                        ],
                        [
                            'index' => 2,
                            'name' => 'طوق شعر لؤلؤ مائل بريميوم',
                            'sku' => 'HAIR-PRL-02',
                            'barcode' => '62210001002',
                            'unit' => 'درزن',
                            'warehouse' => 'الرئيسي',
                            'quantity' => 5.00,
                            'unit_price' => 180.00,
                            'discount' => 0.00,
                            'subtotal' => 900.00,
                            'notes' => 'تغليف هدايا خاص',
                        ],
                        [
                            'index' => 3,
                            'name' => 'دبوس شعر معدني أشكال هندسية مذهبة',
                            'sku' => 'PIN-GOLD-03',
                            'barcode' => '62210001003',
                            'unit' => 'طقم',
                            'warehouse' => 'الرئيسي',
                            'quantity' => 15.00,
                            'unit_price' => 20.00,
                            'discount' => 0.00,
                            'subtotal' => 300.00,
                            'notes' => 'مقاوم للصدأ',
                        ],
                    ],
                    'financial' => [
                        'subtotal' => 1900.00,
                        'discount' => 50.00,
                        'shipping' => 50.00,
                        'tax' => 0.00,
                        'net_total' => 1900.00,
                        'paid' => 1500.00,
                        'remaining' => 400.00,
                    ],
                ];

            case PrintTemplate::DOC_PURCHASE_INVOICE:
                return [
                    'is_sample' => true,
                    'watermark' => 'معاينة تجريبية - إذن استلام وفحص توريد',
                    'company' => $commonCompany,
                    'document' => [
                        'type' => 'purchase_invoice',
                        'type_label' => 'إذن استلام',
                        'number' => 'PO-2026-0018',
                        'date' => '2026-10-14',
                        'due_date' => '2026-10-28',
                        'warehouse' => 'مستودع الوارد والجمالية',
                        'shipping_company' => 'استلام مستودع',
                        'status' => 'مستلمة ومخزنة (Received)',
                        'status_color' => 'primary',
                        'barcode' => 'PO20260018',
                        'qr_payload' => 'PO:PO-2026-0018|SUPP:مصنع الفخامة|TOTAL:8450.00',
                    ],
                    'party' => [
                        'type' => 'supplier',
                        'code' => 'S-205',
                        'name' => 'مصنع الفخامة للتوك والإكسسوارات الحريمي',
                        'contact_person' => 'م. عصام عبد الرحمن',
                        'phone' => '01234567890',
                        'address' => 'المنطقة الصناعية الثالثة، العاشر من رمضان',
                        'tax_no' => '456-789-123',
                    ],
                    'items' => [
                        [
                            'index' => 1,
                            'name' => 'توكة شعر أستيك قطن مخملي 12 قطعة',
                            'sku' => 'RAW-COT-09',
                            'barcode' => '62220002001',
                            'unit' => 'باكيت',
                            'warehouse' => 'الرئيسي',
                            'quantity' => 100.00,
                            'unit_price' => 45.00,
                            'discount' => 100.00,
                            'subtotal' => 4400.00,
                            'notes' => 'تم الفحص المخزني ومطابقة الدفعة',
                        ],
                        [
                            'index' => 2,
                            'name' => 'مشبك شعر أكريليك كوري كبير',
                            'sku' => 'CLP-KOR-15',
                            'barcode' => '62220002002',
                            'unit' => 'درزن',
                            'warehouse' => 'الرئيسي',
                            'quantity' => 50.00,
                            'unit_price' => 75.00,
                            'discount' => 0.00,
                            'subtotal' => 3750.00,
                            'notes' => 'تاريخ الاستلام 14-10-2026',
                        ],
                    ],
                    'financial' => [
                        'subtotal' => 8250.00,
                        'discount' => 100.00,
                        'shipping' => 200.00,
                        'extra_expenses' => 100.00,
                        'tax' => 0.00,
                        'net_total' => 8450.00,
                        'paid' => 5000.00,
                        'remaining' => 3450.00,
                    ],
                ];

            case PrintTemplate::DOC_CUSTOMER_STATEMENT:
                return [
                    'is_sample' => true,
                    'watermark' => 'معاينة تجريبية - نموذج كشف حساب العميل',
                    'company' => $commonCompany,
                    'document' => [
                        'type' => 'customer_statement',
                        'type_label' => 'كشف حساب عميل',
                        'number' => 'STMT-CUST-104',
                        'date' => '2026-10-14',
                        'period' => 'من 01-10-2026 إلى 14-10-2026',
                        'warehouse' => 'كافة المستودعات والفروع',
                        'status' => 'نشط',
                        'barcode' => 'STMT-CUST-104',
                        'qr_payload' => 'STMT:CUST-104|BAL:3400.00|DATE:2026-10-14',
                    ],
                    'party' => [
                        'type' => 'customer',
                        'name' => 'أتيليه روز ومحل الملكة',
                        'contact_person' => 'مدام هالة سليمان',
                        'phone' => '01099887766',
                        'address' => 'شارع السكة الجديدة، المنصورة',
                        'tax_no' => '112-233-445',
                        'credit_limit' => 10000.00,
                        'opening_balance' => 1200.00,
                        'current_balance' => 3400.00,
                    ],
                    'transactions' => [
                        [
                            'date' => '2026-10-01',
                            'ref_number' => 'OB-2026',
                            'description' => 'رصيد افتتاحي مرحل للشهر الحالي',
                            'debit' => 1200.00,
                            'credit' => 0.00,
                            'balance' => 1200.00,
                        ],
                        [
                            'date' => '2026-10-04',
                            'ref_number' => 'INV-2026-0012',
                            'description' => 'فاتورة مبيعات إكسسوارات وتوك هدايا',
                            'debit' => 3500.00,
                            'credit' => 0.00,
                            'balance' => 4700.00,
                        ],
                        [
                            'date' => '2026-10-08',
                            'ref_number' => 'REC-2026-0021',
                            'description' => 'سند تحصيل نقدي من الخزينة الرئيسية',
                            'debit' => 0.00,
                            'credit' => 2000.00,
                            'balance' => 2700.00,
                        ],
                        [
                            'date' => '2026-10-12',
                            'ref_number' => 'INV-2026-0038',
                            'description' => 'فاتورة مبيعات درازن أطواق وتوك ستان',
                            'debit' => 1700.00,
                            'credit' => 0.00,
                            'balance' => 4400.00,
                        ],
                        [
                            'date' => '2026-10-13',
                            'ref_number' => 'REC-2026-0034',
                            'description' => 'تحويل إلكتروني بنكي',
                            'debit' => 0.00,
                            'credit' => 1000.00,
                            'balance' => 3400.00,
                        ],
                    ],
                    'summary' => [
                        'opening_balance' => 1200.00,
                        'total_debit' => 5200.00,
                        'total_credit' => 3000.00,
                        'closing_balance' => 3400.00,
                    ],
                ];

            case PrintTemplate::DOC_SUPPLIER_STATEMENT:
                return [
                    'is_sample' => true,
                    'watermark' => 'معاينة تجريبية - نموذج كشف حساب المورد',
                    'company' => $commonCompany,
                    'document' => [
                        'type' => 'supplier_statement',
                        'type_label' => 'كشف حساب مورد',
                        'number' => 'STMT-SUPP-208',
                        'date' => '2026-10-14',
                        'period' => 'من 01-10-2026 إلى 14-10-2026',
                        'warehouse' => 'مستودع الوارد العام',
                        'status' => 'نشط',
                        'barcode' => 'STMT-SUPP-208',
                        'qr_payload' => 'STMT:SUPP-208|BAL:11500.00|DATE:2026-10-14',
                    ],
                    'party' => [
                        'type' => 'supplier',
                        'name' => 'شركة النور لاستيراد خامات التوك والشعر',
                        'contact_person' => 'الحاج نور الدين محمود',
                        'phone' => '01511223344',
                        'address' => 'الموسكي، القاهرة',
                        'tax_no' => '334-556-778',
                        'credit_limit' => 50000.00,
                        'opening_balance' => 5000.00,
                        'current_balance' => 11500.00,
                    ],
                    'transactions' => [
                        [
                            'date' => '2026-10-02',
                            'ref_number' => 'OB-SUPP-2026',
                            'description' => 'رصيد افتتاحي مستحق للمورد',
                            'debit' => 0.00,
                            'credit' => 5000.00,
                            'balance' => 5000.00,
                        ],
                        [
                            'date' => '2026-10-05',
                            'ref_number' => 'PO-2026-0005',
                            'description' => 'فاتورة توريد خامات شعر وأطواق كريستال',
                            'debit' => 0.00,
                            'credit' => 14000.00,
                            'balance' => 19000.00,
                        ],
                        [
                            'date' => '2026-10-09',
                            'ref_number' => 'PAY-2026-0014',
                            'description' => 'سداد دفعة نقدية بشيك مصرفي',
                            'debit' => 10000.00,
                            'credit' => 0.00,
                            'balance' => 9000.00,
                        ],
                        [
                            'date' => '2026-10-11',
                            'ref_number' => 'PO-2026-0012',
                            'description' => 'فاتورة توريد دبابيس شعر وأكسسوارات',
                            'debit' => 0.00,
                            'credit' => 7500.00,
                            'balance' => 16500.00,
                        ],
                        [
                            'date' => '2026-10-13',
                            'ref_number' => 'PAY-2026-0019',
                            'description' => 'سداد نقدي من الخزينة الرئيسية',
                            'debit' => 5000.00,
                            'credit' => 0.00,
                            'balance' => 11500.00,
                        ],
                    ],
                    'summary' => [
                        'opening_balance' => 5000.00,
                        'total_purchases' => 21500.00,
                        'total_payments' => 15000.00,
                        'closing_balance' => 11500.00,
                    ],
                ];

            default:
                throw new InvalidArgumentException("Unsupported document type: {$documentType}");
        }
    }

    /**
     * Assert that document type and paper format are valid.
     */
    protected function assertSupported(string $documentType, string $paperFormat): void
    {
        if (! in_array($documentType, self::SUPPORTED_DOCUMENTS, true)) {
            throw new InvalidArgumentException("Invalid document type: {$documentType}");
        }

        if (! in_array($paperFormat, self::SUPPORTED_FORMATS, true)) {
            throw new InvalidArgumentException("Invalid paper format: {$paperFormat}");
        }
    }

    protected function getDefaultTitle(string $documentType): string
    {
        return match ($documentType) {
            PrintTemplate::DOC_SALES_INVOICE => 'إذن صرف',
            PrintTemplate::DOC_PURCHASE_INVOICE => 'إذن استلام',
            PrintTemplate::DOC_CUSTOMER_STATEMENT => 'كشف حساب عميل',
            PrintTemplate::DOC_SUPPLIER_STATEMENT => 'كشف حساب مورد',
            default => 'مستند رسمي',
        };
    }

    protected function getDefaultSubtitle(string $documentType): string
    {
        return match ($documentType) {
            PrintTemplate::DOC_SALES_INVOICE => 'BayoSoft ERP - قسم المبيعات والتوزيع',
            PrintTemplate::DOC_PURCHASE_INVOICE => 'BayoSoft ERP - قسم المشتريات وإدارة المخزون',
            PrintTemplate::DOC_CUSTOMER_STATEMENT => 'حركة الحساب والمسحوبات والأرصدة',
            PrintTemplate::DOC_SUPPLIER_STATEMENT => 'حركة التوريدات والمدفوعات والمستحقات',
            default => 'وثيقة إدارية رسمية',
        };
    }

    protected function getDefaultPartyLabel(string $documentType): string
    {
        return match ($documentType) {
            PrintTemplate::DOC_SALES_INVOICE, PrintTemplate::DOC_CUSTOMER_STATEMENT => 'العميل',
            PrintTemplate::DOC_PURCHASE_INVOICE, PrintTemplate::DOC_SUPPLIER_STATEMENT => 'المورد',
            default => 'الطرف المقابل',
        };
    }

    protected function getDefaultTermsText(string $documentType): string
    {
        return match ($documentType) {
            PrintTemplate::DOC_SALES_INVOICE => 'البضاعة المباعة ترد أو تستبدل خلال 14 يوماً من تاريخ الفاتورة بالحالة الأصلية، وفقاً لقانون حماية المستهلك.',
            PrintTemplate::DOC_PURCHASE_INVOICE => 'تم استلام وفحص البضاعة ومطابقتها للمواصفات القياسية وأمر الشراء المعتمد، وتُعد الفاتورة سند توريد مخزني رسمي.',
            PrintTemplate::DOC_CUSTOMER_STATEMENT => 'يرجى مراجعة العمليات والأرصدة المدونة أعلاه، وفي حال وجود أي ملاحظات أو فروقات يُرجى إخطار الإدارة المالية خلال 7 أيام من تاريخ الاستلام.',
            PrintTemplate::DOC_SUPPLIER_STATEMENT => 'كشف حساب رسمي مطابق لسجلات المستودع وأذونات التوريد، ويُعتمد للمطابقة الدورية وتسوية الالتزامات المالية.',
            default => '',
        };
    }

    protected function getDefaultFirstPartySignature(string $documentType): string
    {
        return match ($documentType) {
            PrintTemplate::DOC_SALES_INVOICE => 'توقيع المستلم / العميل',
            PrintTemplate::DOC_PURCHASE_INVOICE => 'أمين المستودع المستلم',
            PrintTemplate::DOC_CUSTOMER_STATEMENT => 'اعتماد العميل',
            PrintTemplate::DOC_SUPPLIER_STATEMENT => 'اعتماد المورد',
            default => 'الطرف الأول',
        };
    }

    protected function getDefaultTemplateName(string $documentType, string $paperFormat): string
    {
        $docLabel = PrintTemplate::getDocumentTypeLabels()[$documentType] ?? $documentType;
        $formatLabel = match ($paperFormat) {
            PrintTemplate::FORMAT_A4 => 'A4',
            PrintTemplate::FORMAT_A5 => 'A5',
            PrintTemplate::FORMAT_THERMAL_80MM => '80mm حراري',
            default => $paperFormat,
        };

        return "{$docLabel} الافتراضي ({$formatLabel})";
    }

    /**
     * Sanitize free-form text input: strip script/style contents, strip HTML tags, trim, clamp length.
     */
    protected function sanitizeText(?string $input, int $maxLength): string
    {
        if ($input === null) {
            return '';
        }

        // Strip script and style tags along with their contents
        $withoutExecutableBlocks = preg_replace('/<(script|style)\b[^>]*>(.*?)<\/\1>/is', '', $input) ?? $input;
        $clean = trim(strip_tags($withoutExecutableBlocks));

        return mb_substr($clean, 0, $maxLength, 'UTF-8');
    }

    /**
     * Sanitize hex color, fallback to safe default if invalid.
     */
    protected function sanitizeColor(?string $color, string $fallback): string
    {
        if ($color === null) {
            return $fallback;
        }

        $trimmed = trim($color);
        if (preg_match('/^#[0-9a-fA-F]{6}$/', $trimmed)) {
            return strtolower($trimmed);
        }

        return $fallback;
    }
}
