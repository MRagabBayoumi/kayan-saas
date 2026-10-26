# BayoSoft ERP — خطة المعمارية والتنفيذ الشاملة لمركز قوالب الطباعة المرئي (Print Template Center)

> **وثيقة تدقيق المرحلة الصفرية (Phase 0 — Read-Only Audit & Architectural Implementation Plan)**  
> **تاريخ التدقيق:** 14 أكتوبر 2026  
> **الإصدار المستهدف:** BayoSoft ERP v2.0  
> **حالة التدقيق:** مكتمل بنجاح دون أي تعديلات في كود المشروع بانتظار الاعتماد الرسمي قبل البدء.

---

## 1. الفحص الفعلي لبيئة المشروع والمكونات المتاحة (Codebase & Readiness Audit)

تم إجراء فحص شامل وقراءة دقيقة لمستودع الكود الفعلي، وقاعدة البيانات النشطة `kayan_erp`، ونظام التصميم، والمتحكمات:

### أ. حالة المستودع ونظام الإصدارات
- **الفرع الحالي:** `main` (سليم ومتزامن بالكامل، وآخر مرحلة مثبتة هي فواتير الشراء وكشف حركة الصنف `f6d94cd`).
- **قاعدة البيانات النشطة:** `kayan_erp` (MySQL 8.0). قاعدة البيانات القديمة `kayan` معزولة تماماً ولم تُمس.
- **حالة الاختبارات الحالية:** **95 اختباراً من أصل 95 ناجحاً بنسبة 100% (524 توكيداً)**.
- **فصل الواجهة (Zero-Inline):** تم التحقق عبر `scripts/audit_blade_separation.cjs` بنجاح على **29 ملف Blade** بنتيجة: **0 نصوص برمجية مضمنة، 0 تنسيقات CSS مضمنة، و 0 معالجات أحداث**.

### ب. المكونات القائمة القابلة لإعادة الاستخدام (Reusable Components)
1. **بيانات الشركة والشعار (`CompanySetting`):**
   - النموذج [`CompanySetting.php`](app/Models/CompanySetting.php) يوفر اسم المنشأة، الهاتف، الشعار عبر الرابط `logo_url` على القرص العام `public`، وحالة النظام.
2. **إعدادات الباركود والطباعة الحالية (`BarcodeSetting`):**
   - النموذج [`BarcodeSetting.php`](app/Models/BarcodeSetting.php) يحتوي بالفعل على مقاسات طابعة الملصقات (38×25 مم و 38×15 مم) ومحرك توليد باركود SVG [`BarcodeGeneratorService.php`](app/Services/BarcodeGeneratorService.php).
3. **نظام التصميم والأيقونات ومكتبة الرموز (Odoo Design System):**
   - توفر مكونات Blade الجاهزة: `<x-control-panel>`, `<x-icon>`, `<x-modal>`, ونظام البطاقات `erp-card` والجداول `erp-table`.
   - توفر شريط تبويبات الإعدادات [`settings/index.blade.php`](resources/views/settings/index.blade.php) المستعد برمجياً لاستقبال تبويب `"قوالب الطباعة"` (`tab=print_templates`).
4. **دورة فواتير الشراء (`PurchaseInvoice`):**
   - النموذج مكتمل بالكامل مع البنود (`PurchaseInvoiceItem`)، المورد، المستودع، التكاليف، ودفعات الـ FIFO.
5. **أصول بناء Vite والأوزان الخطية:**
   - خط `Cairo` العربي القياسي متوفر ومحمّل محلياً بنسخه المتعددة (400, 500, 600, 700).

### ج. تحليل الفجوات والوظائف غير المكتملة بعد (Gap Analysis & Missing Data Sources)
1. **فواتير المبيعات (`Sales Invoices`):**
   - وفقاً للوثيقة الرسمية [`ROADMAP.md`](ROADMAP.md) (المرحلة 8: *أوامر البيع الرسمية والفوترة وخصم المخزون — Planned*)، **لا يوجد حالياً جدول أو نموذج لفواتير المبيعات في قاعدة البيانات**.
   - **الحل المعماري الموصى به:**
     - بناء محرك القوالب ليدعم `sales_invoice` بنسبة 100% مع نموذج بيانات قياسي وصارم (`Contract/DTO`).
     - توفير بيانات تجريبية واقعية ممثلة للنشاط التجاري (إكسسوارات حريمي، توك شعر، أسعار جملة وقطاعي) في المعاينة الحية وشاشة الاختبار، مع وضع علامة مائية واضحة (*"معاينة تجريبية"*).
     - فور تنفيذ المرحلة 8 من خارطة الطريق، سيتم ربط فواتير المبيعات الحية بمحرك الطباعة هذا فوراً دون الحاجة لإعادة كتابة القوالب.
2. **كشوف حسابات العملاء والموردين (`Customer & Supplier Account Statements`):**
   - يوجد حالياً في قاعدة البيانات:
     - جدول `customers` يحتوي على `current_balance` و `credit_limit`.
     - جدول `suppliers` يحتوي على `current_balance` والعملة.
     - جدول `purchase_invoices` مرتبط بالموردين.
     - جدول `stock_movements` مرتبط بالعملاء (`customer_id`) والموردين (`supplier_id`).
   - لا يوجد حالياً جدول لقيود اليومية المحاسبية المزدوجة أو سندات القبض والصرف (Double-entry Ledger Vouchers).
   - **الحل المعماري الموصى به:**
     - تصميم كشف حساب المورد ليعتمد على العمليات الفعلية الموجودة (فواتير الشراء المسجلة، التوريدات المخزنية، والرصيد الحالي).
     - تصميم كشف حساب العميل ليعتمد على حركات الصرف المرتبطة به، وحد الائتمان، والرصيد الحالي.
     - توفير معاينة حية متقدمة ببيانات نموذجية للمدين والدائن والرصيد التراكمي في شاشة الإعدادات.

---

## 2. التصميم المعماري لقاعدة البيانات (Database Architecture)

سننشئ جدولاً موحداً وآمناً باسم `print_templates`، يمنع التكرار ويدعم التخصيص المستقل لكل نوع مستند ومقاس ورق.

### أ. مخطط جدول `print_templates`
```sql
CREATE TABLE print_templates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    document_type VARCHAR(40) NOT NULL, -- 'sales_invoice', 'purchase_invoice', 'customer_statement', 'supplier_statement'
    paper_format VARCHAR(20) NOT NULL,  -- 'a4', 'thermal_80mm'
    name VARCHAR(100) NOT NULL,         -- اسم القالب (مثل: "القالب الافتراضي A4")
    is_default BOOLEAN NOT NULL DEFAULT FALSE,
    settings JSON NOT NULL,             -- إعدادات القالب المحققة وفق Schema صارم
    version INT UNSIGNED NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_doc_paper (document_type, paper_format),
    INDEX idx_doc_paper_default (document_type, paper_format, is_default)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### ب. هيكل إعدادات القالب المحمية (`Settings JSON Schema & Whitelist`)
لا يُسمح بأي أكواد HTML أو CSS أو جافاسكريبت داخل الإعدادات. كل خيار يتم التحقق منه بقيم محددة سلفاً:

```json
{
  "general": {
    "show_logo": true,
    "logo_size": "md",          // "sm" (25mm), "md" (35mm), "lg" (45mm)
    "show_company_name": true,
    "show_company_phone": true,
    "show_company_address": true,
    "custom_title": "فاتورة ضريبية", // نص معقم ومفلتر من أي وسوم
    "custom_subtitle": "شركة كيان للتجارة والتوزيع",
    "primary_color": "#1e293b", // من قائمة ألوان آمنة محددة مسبقاً
    "font_size": "md"            // "sm" (12px), "md" (13.5px), "lg" (15px)
  },
  "document_meta": {
    "show_doc_number": true,
    "show_doc_date": true,
    "show_due_date": false,
    "show_warehouse": true,
    "show_status_badge": true,
    "show_barcode": true,
    "show_qr_code": true
  },
  "party_meta": {
    "show_party_name": true,
    "show_party_phone": true,
    "show_party_address": true,
    "show_party_tax_no": false
  },
  "table_columns": {
    "show_index": true,
    "show_sku": true,
    "show_barcode": false,
    "show_unit": true,
    "show_price": true,
    "show_discount": false,
    "show_tax": false,
    "show_total": true,
    "show_notes": true,
    "density": "comfortable"    // "compact", "comfortable"
  },
  "financial_summary": {
    "show_subtotal": true,
    "show_shipping": true,
    "show_discount": true,
    "show_tax": false,
    "show_grand_total": true,
    "show_paid": true,
    "show_balance": true
  },
  "footer": {
    "notes_text": "البضاعة المباعة لا ترد ولا تستبدل بعد 14 يوماً من تاريخ الفاتورة.",
    "terms_conditions": "شكراً لتعاملكم مع كيان للتجارة والتوزيع.",
    "show_signatures": true,
    "signature_1_label": "توقيع المحاسب / البائع",
    "signature_2_label": "توقيع المستلم"
  },
  "page_layout": {
    "margin_top_mm": 8,
    "margin_bottom_mm": 8,
    "margin_left_mm": 8,
    "margin_right_mm": 8,
    "repeat_table_header": true,
    "show_page_number": true
  }
}
```

---

## 3. معمارية واجهة التعديل المرئي في الإعدادات (Visual Editor UI Architecture)

تضاف تبويبة `"قوالب الطباعة"` إلى صفحة الإعدادات الحالية عبر المسار:
`GET /settings?tab=print_templates`

```
+-------------------------------------------------------------------------------------------------------+
|  شريط التحكم: مركز قوالب الطباعة (BayoSoft ERP)  [حفظ التعديلات]  [استعادة الافتراضي]  [طباعة تجريبية]|
+-------------------------------------------------------------------------------------------------------+
|  [بيانات وهوية الشركة]   [إعدادات الباركود]   [قوالب الطباعة ★]                                       |
+-------------------------------------------------------------------------------------------------------+
|                                  محدد نوع المستند والمقاس                                              |
|  نوع المستند: [فواتير المبيعات v] [فواتير المشتريات] [كشف حساب عميل] [كشف حساب مورد]                    |
|  المقاس:      [ورق A4 قياسي (210×297 مم)]   [شريط حراري 80 مم (إيصالات كاشير)]                         |
+-------------------------------------------------------------------+-----------------------------------+
|  أدوات التخصيص المرئي (Visual Control Panels)                      |  المعاينة الحية الفورية (Live)     |
|                                                                   |  (تتحدث لحظياً بدون ريفريش)       |
|  1. ترويسة وهوية الشركة (اللوجو، المقاس، اسم الشركة، العناوين)    |                                   |
|  2. بيانات المستند والطرف (الفاتورة، التاريخ، المستودع، العميل)   |    +-------------------------+    |
|  3. أعمدة الجدول وحجم الخط والمسافات                              |    |   [شعار الشركة]         |    |
|  4. الملخص المالي والضرائب والشحن                                 |    |   فاتورة مبيعات #INV... |    |
|  5. التذييل والشروط وسياسة الاستبدال والتواقيع                   |    |   ===================== |    |
|  6. الألوان والهوامش القياسية                                     |    |   جدول الأصناف          |    |
|                                                                   |    |   --------------------- |    |
|                                                                   |    |   الإجمالي: 1,450 ج.م   |    |
|                                                                   |    +-------------------------+    |
|                                                                   |    [A4 Preview] [80mm Preview]    |
+-------------------------------------------------------------------+-----------------------------------+
```

---

## 4. معمارية محرك الطباعة والملفات المخصصة (Dedicated Print Architecture)

التزاماً بالشرط المعماري الحاسم: **عدم وجود أي جافاسكريبت أو CSS مضمن داخل Blade نهائياً**:

1. **ملف التنسيق الأساسي للمحرر المرئي:**
   - [`resources/css/pages/print-templates.css`](resources/css/pages/print-templates.css):
     - تنسيق شاشة المحرر، المقسمة لقسم أدوات التحكم وقسم المعاينة الحية.
     - تمثيل حاوية الورقة البيضاء المضيئة بحواف وظلال ورقة الـ A4 الواقعية، أو حاوية الشريط الحراري ذو العرض الثابت 80 مم.
2. **ملف التنسيق المخصص للطباعة الفعلية (`@media print`):**
   - [`resources/css/print/document-print.css`](resources/css/print/document-print.css):
     - قواعد `@page` دقيقة:
       - للـ A4: `size: A4 portrait; margin: 10mm;` مع تكرار ترويسة الجداول `thead { display: table-header-group; }`.
       - للـ 80 مم: `size: 80mm auto; margin: 0;` مع منع الانكسار العرضي، وتنسيق أسماء الأصناف الطويلة عربياً.
     - إخفاء تام لجميع أدوات التنقل والأزرار والأشرطة عبر `.no-print { display: none !important; }`.
     - منع قطع وتشويه الأسطر والتواقيع بين الصفحات `page-break-inside: avoid;`.
3. **ملف الجافاسكريبت التفاعلي للمحرر والمعاينة:**
   - [`resources/js/pages/print-templates.js`](resources/js/pages/print-templates.js):
     - محرك جافاسكريبت نقي (Vanilla JS) يربط كافة المدخلات ومفاتيح التبديل (Toggles) بالحاوية المرئية لحظياً (*Instant DOM Reactive Sync*) دون إعادة تحميل الصفحة.
     - معالجة تبديل المستندات والمقاسات واستدعاء إعدادات القالب المحفوظة عبر AJAX/Fetch.
     - إرسال طلبات الحفظ والنسخ واستعادة الافتراضي مع إظهار Toast notifications واضحة.
     - تشغيل نافذة الطباعة المنفصلة للطباعة الفعلية أو الطباعة التجريبية.

---

## 5. مصفوفة الملفات المطلوب إنشاؤها وتعديلها (File Impact Matrix)

### أ. ملفات جديدة سيتم إنشاؤها (New Files)
| الملف | الوصف والمسؤولية |
| :--- | :--- |
| `database/migrations/2026_10_14_000005_create_print_templates_table.php` | هجرة آمنة لإنشاء جدول قوالب الطباعة وفهارسها. |
| `app/Models/PrintTemplate.php` | نموذج Eloquent للقالب مع الـ Casts والـ Scopes والـ Default Fallbacks. |
| `app/Services/PrintTemplateService.php` | خدمة معالجة القوالب، توفير الإعدادات الافتراضية الآمنة، والتحقق الصارم من الـ Schema. |
| `app/Http/Controllers/PrintTemplateController.php` | متحكم استرجاع القالب، تحديث الإعدادات، التكرار، والطباعة التجريبية. |
| `resources/views/settings/tabs/print-templates.blade.php` | واجهة تبويب قوالب الطباعة داخل شاشة الإعدادات. |
| `resources/views/print/document-layout.blade.php` | الهيكل العام الموحد لوثائق الطباعة (A4 و 80 مم) مع ضبط خط Cairo و RTL. |
| `resources/views/print/templates/sales-invoice.blade.php` | قالب فاتورة المبيعات (متوافق مع A4 و 80 مم). |
| `resources/views/print/templates/purchase-invoice.blade.php` | قالب فاتورة المشتريات (متوافق مع A4 و 80 مم). |
| `resources/views/print/templates/customer-statement.blade.php` | قالب كشف حساب العميل (متوافق مع A4 و 80 مم). |
| `resources/views/print/templates/supplier-statement.blade.php` | قالب كشف حساب المورد (متوافق مع A4 و 80 مم). |
| `resources/css/pages/print-templates.css` | أنماط واجهة المحرر وقسم المعاينة الحية. |
| `resources/css/print/document-print.css` | الأنماط الدقيقة للطباعة الورقية والحرارية `@media print`. |
| `resources/js/pages/print-templates.js` | وحدة JS للمحرر التفاعلي وتحديث المعاينة اللحظي بدون ريفريش. |
| `tests/Feature/PrintTemplateTest.php` | حزمة اختبارات Pest متكاملة للتحقق من الصلاحيات والـ Validation والطباعة. |

### ب. ملفات قائمة سيتم تعديلها بأمان (Modified Files)
| الملف | التعديل المطلوب |
| :--- | :--- |
| `routes/web.php` | إضافة مسارات حفظ قوالب الطباعة، ومسار الطباعة التجريبية، ومسار طباعة فاتورة الشراء بالقالب. |
| `app/Http/Controllers/CompanySettingController.php` | حقن خدمة القوالب لتمرير القوالب النشطة عند فتح التبويب. |
| `resources/views/settings/index.blade.php` | إتاحة زر التبويب الجديد وتضمين واجهة المحرر عند اختياره. |
| `resources/views/purchases/show.blade.php` | ربط أزرار الطباعة الحالية بالقالب المخصص (خياري A4 وحراري 80 مم). |
| `resources/css/erp.css` | استيراد `print-templates.css`. |
| `resources/js/app.js` | استيراد وتهيئة `initPrintTemplates()`. |
| `vite.config.js` | تسجيل `resources/css/print/document-print.css` ضمن أصول Vite المترجمة للإنتاج. |

---

## 6. تقييم المخاطر الأمنية وحماية البيانات (Security & Risk Assessment)

1. **الوقاية من هجمات XSS و Code Injection:**
   - **الخطر:** محاولة المستخدم حقن وسوم `<script>` أو أكواد CSS ضارة أو أوامر عبر حقول العناوين والشروط.
   - **الحماية:** يتم تمرير جميع النصوص المدخلة عبر خوارزمية فلترة تنزع أي وسوم HTML (`strip_tags`) وتخضعها لتوكيد أحرف آمن، ولا يتم استخدام أي دالة تقييم ديناميكي (`eval` أو Blade runtime eval) نهائياً.
2. **عزل المعاينة التجريبية عن البيانات الحقيقية:**
   - المعاينة تستخدم كائن بيانات وهمي مستقل في الذاكرة يحمل علامة واضحة `is_preview = true`، ولا تمس أي سجل مالي أو رصيد مخزني مطلقاً.
3. **حماية الفواتير الحالية من الآثار الجانبية:**
   - عملية استعراض أو طباعة أي فاتورة شراء أو كشف حساب هي عملية قراءة فقط (`Idempotent GET / Read-Only`). لا يُسمح بإجراء أي ترحيل أو خصم مخزني أو تعديل حالة كأثر جانبي للطباعة.

---

## 7. خطة التنفيذ المرحلية المقترحة (Phased Execution Plan)

- **المرحلة 1: نموذج البيانات وقاعدة البيانات**
  - إنشاء وتشغيل هجرة جدول `print_templates`.
  - بناء النموذج `PrintTemplate` وخدمة `PrintTemplateService` مع توفير مصفوفة الإعدادات الافتراضية الآمنة لجميع المستندات والمقاسات.
- **المرحلة 2: واجهة المحرر المرئي في الإعدادات**
  - بناء التبويب الجديد في شاشة الإعدادات.
  - تطوير أدوات التخصيص المنظمة ولوحة المعاينة الحية التفاعلية مع دعم التحويل بين A4 و 80 مم.
  - كتابة `print-templates.js` و `print-templates.css` وتحديث حزمة Vite.
- **المرحلة 3: قوالب ومسارات الطباعة للوثائق الأربع**
  - بناء قوالب العرض الطباعية: فاتورة المبيعات، فاتورة المشتريات، كشف حساب العميل، وكشف حساب المورد.
  - ربط فواتير الشراء الحالية `purchases.show` بزر طباعة مباشر يتيح للمستخدم اختيار (A4 أو 80 مم).
- **المرحلة 4: الاختبارات الآلية والتحقق الشامل**
  - كتابة اختبارات Pest شاملة لجميع العمليات والتحقق من الصلاحيات والمدخلات.
  - تشغيل فحص Blade Separation للتأكد من استمرار **0 وسوم جافاسكريبت و 0 وسوم CSS**.
  - تشغيل البناء الكامل للإنتاج عبر Vite.
- **المرحلة 5: دليل الفحص والاختبار اليدوي للطابعات الحقيقية**
  - توفير قائمة فحص للمستخدم لاختبار المخرجات على ورق A4 وعلى طابعات الإيصالات الحرارية 80 مم وتوثيق أي إعدادات خاصة بهوامش تعريف الطابعة (Printer Driver Margin Settings).

---

> ✋ **التزام صارم:**  
> وفقاً للتعليمات، يتوقف المهندس هنا تماماً عند انتهاء المرحلة الصفرية، بانتظار مراجعة واعتماد خطة التنفيذ والانتقال للمرحلة التالية.
