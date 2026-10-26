# Kayan ERP — التدقيق الشامل لوظائف الطباعة (Comprehensive Printing Audit)

> **وثيقة تدقيق هندسية لحصر وتوثيق كافة وظائف وأزرار ومسارات الطباعة في نظام Kayan ERP**  
> تاريخ التدقيق: 14 أكتوبر 2026

---

## 1. جدول حصر وظائف الطباعة في النظام

| # | اسم الوظيفة (Function Name) | الصفحة الحاضنة (Page) | اسم الزر / الرابط (Button / Link) | المسار المستخدم (Route) | مصدر البيانات الفعلي (Data Source) | حالة التنفيذ (Status) | المقاسات المدعومة (Supported Sizes) | نتيجة الاختبار (Test Result) | البنود المطلوب إصلاحها (Items Requiring Repair) |
|---|-----------------------------|-----------------------|-----------------------------------|-------------------------|------------------------------------|-----------------------|--------------------------------------|------------------------------|-------------------------------------------------|
| 1 | **طباعة فواتير المشتريات** | `/purchases/{purchase}` و `/purchases` | زر "طباعة الفاتورة" (قائمة A4، A5، 80 مم) وأيقونة الطباعة بالجدول | `GET /purchases/{purchase}/print` (`purchases.print`) | `PurchaseInvoice` مع علاقات `supplier`, `warehouse`, `items.product`, `creator` + `CompanySetting` | **Working** (مكتمل ويعمل بدقة) | A4 قياسي، A5 وسط، Thermal 80mm | ناجح: 14/14 اختباراً في `PurchaseInvoicePrintTest` | لا يوجد (معتمد بالكامل في المرحلة 2-ب). |
| 2 | **طباعة كشف حركة الصنف** | `/products/{product}` و `/products/{product}/ledger` | "طباعة حركة الصنف" (صفحة الصنف) / "طباعة كشف الحركة" (صفحة الكشف) | `GET /products/{product}/ledger/print` (`products.ledger.print`) | `Product`, `StockMovement` (مع علاقات `fromWarehouse`, `toWarehouse`, `batch`, `supplier`, `customer`, `user`), `StockBalance` | **Incomplete** (الزر القديم كان `print-window` شكلي بدون مسار مخصص وبدون دعم المقاسات أو الفلاتر) | A4 قياسي، A5 وسط، Thermal 80mm | قيد التنفيذ في المرحلة الثانية الحالية | بناء مسار مخصص، وثيقة طباعة مستقلة، دعم المقاسات الثلاثة، تمرير فلاتر التاريخ والمستودع، إلغاء الترقيم في الطباعة، وإضافة الزر بصفحة تفاصيل الصنف. |
| 3 | **طباعة ملصق باركود صنف فردي** | `/products/{product}` و `/products` | "طباعة ملصق الباركود" / "طباعة فورية على الطابعة" / أيقونة الباركود بالجدول | `GET /products/{product}/print-label` (`products.print-label`) | `Product`, `BarcodeSetting`, `BarcodeGeneratorService` | **Working** (شغال عبر المعاينة والـ iframe) | ملصقات حرارية مخصصة (38×25 مم، 38×15 مم لـ Xprinter XP-233B) | ناجح في `BarcodePrintTest` و `UiButtonIntegrityTest` | لا يوجد. |
| 4 | **طباعة ملصقات باركود مجمعة للأصناف** | `/products` | "طباعة ملصقات للأصناف المحددة" (شريط العمليات الجماعية) | `MATCH /products/print-labels/batch` (`products.print-batch`) | مصفوفة الأصناف والكميات المختارة، `BarcodeSetting`, `BarcodeGeneratorService` | **Working** (مربوط بالـ Checkboxes والعداد التفاعلي) | ملصقات باركود متعددة حسب الكمية | ناجح في `BarcodePrintTest` و `UiButtonIntegrityTest` | لا يوجد. |
| 5 | **معاينة ومصمم قوالب الطباعة** | `/settings?tab=print_templates` | أزرار "معاينة القالب"، حفظ، استعادة الافتراضي، تكرار | `/settings/print-templates/*` | `PrintTemplate`, `CompanySetting`, `PrintTemplateService` | **Working** (شاشة تفاعلية كاملة مع محاكاة الورق) | A4، A5، Thermal 80mm | ناجح: 12 اختباراً في `PrintTemplateDesignerTest` و 24 في `PrintTemplateModelAndServiceTest` | لا يوجد. |
| 6 | **كشف حساب عميل** | لا يوجد في الواجهات التشغيلية حالياً | غير متوفر (مجدول في المرحلة 10) | غير مسجل | نموذج `Customer` موجود، ولكن دفتر الأستاذ المحاسبي مجدول للمرحلة 10 | **Planned / Not Implemented in Business Layer** | مسجل بالقوالب: A4, A5, 80mm | غير متاح (لم تبدأ المرحلة 10) | ممنوع وضع بيانات وهمية؛ توثيق الارتباط باكتمال دورة القيود والمقبوضات بالمرحلة 10. |
| 7 | **كشف حساب مورد** | لا يوجد في الواجهات التشغيلية حالياً | غير متوفر (مجدول في المرحلة 10) | غير مسجل | نموذج `Supplier` موجود، وسجل المدفوعات والقيود مجدول للمرحلة 10 | **Planned / Not Implemented in Business Layer** | مسجل بالقوالب: A4, A5, 80mm | غير متاح (لم تبدأ المرحلة 10) | ممنوع وضع بيانات وهمية؛ توثيق الارتباط باكتمال دورة القيود والمدفوعات بالمرحلة 10. |
| 8 | **فواتير المبيعات** | لا يوجد في الواجهات حالياً | غير متوفر (مجدول في المرحلة 8) | غير مسجل | دورة أوامر البيع والفوترة مجدولة للمرحلة 8 | **Planned / Not Implemented in Business Layer** | مسجل بالقوالب: A4, A5, 80mm | غير متاح (لم تبدأ المرحلة 8) | توثيق الارتباط بالمرحلة 8 وفق ROADMAP.md. |

---

## 2. تحليل الفجوة التشغيلية للطباعة (Operational Gap Analysis)

1. **كشف حركة الصنف (Stock Movement):**
   - هو الوظيفة الأهم المتبقية التي تمتلك بالفعل بيانات حقيقية في قاعدة البيانات (`stock_movements`, `inventory_batches`, `stock_balances`).
   - الرابط القديم في صفحة الكشف [`resources/views/products/ledger.blade.php`](resources/views/products/ledger.blade.php) كان يحمل صفة `data-action="print-window"` فقط، مما كان يعرض لوحة التحكم بالكامل (Sidebar و Header وأزرار الفلترة) دون تنسيق طباعة وثائقي.
   - صفحة تفاصيل الصنف [`resources/views/products/show.blade.php`](resources/views/products/show.blade.php) تفتقر لزر مباشر لطباعة كشف الحركة وقسم ملخص الحركة السريعة.
   - الحل المعتمد: بناء صفحة وثيقة طباعة مستقلة ومتجاوبة مع المقاسات الثلاثة (A4، A5، 80 مم حراري)، تمرر فلاتر المستودع والتواريخ ونوع الحركة، وتطبع كامل الحركات المطابقة دون بتر Pagination.

2. **كشوفات الحسابات وفواتير المبيعات:**
   - التزاماً بتوجيهات النظام الصارمة: **"لا تولد كشوف حسابات أو تقارير مالية وهمية إذا لم يكن المنطق المحاسبي الداعم قد تم تنفيذه بعد. صنّفها كـ ميزات غير متوفرة بدلاً من إظهار بيانات وهمية"**.
   - كشوف حسابات العملاء والموردين مسجلة ومجهزة هيكلياً في خدمة قوالب الطباعة [`PrintTemplateService`](app/Services/PrintTemplateService.php)، ولكنها تظل غير مفعلة في واجهات العملاء والموردين حتى بناء قيود المقبوضات والمدفوعات في المرحلة 10.
