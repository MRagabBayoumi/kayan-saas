<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <link rel="stylesheet" href="{{ asset('fonts/cairo/cairo.css') }}">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Cairo', Tahoma, sans-serif; background: #fff; color: #1e293b; direction: rtl; font-size: 11px; line-height: 1.4; padding: 15mm 10mm; }
        .print-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #0f172a; padding-bottom: 8px; margin-bottom: 12px; }
        .company-name { font-size: 16px; font-weight: 800; color: #0f172a; }
        .company-subtitle { font-size: 10px; color: #64748b; }
        .report-title-box { text-align: center; }
        .report-title { font-size: 16px; font-weight: 800; color: #0f172a; margin-bottom: 4px; }
        .report-period { font-size: 10px; color: #475569; }
        .meta-box { text-align: left; font-size: 9px; color: #64748b; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #cbd5e1; padding: 4px 6px; }
        th { background-color: #f8fafc; font-weight: 700; text-align: center; font-size: 10px; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .text-center { text-align: center; }
        .font-bold { font-weight: 700; }
        .font-numeric { font-family: Tahoma, 'Cairo', sans-serif; direction: ltr; display: inline-block; }
        .bg-sub { background-color: #f1f5f9; font-weight: 700; }
        .bg-leaf { background-color: #ffffff; }
        tfoot tr td { background-color: #e2e8f0; font-weight: 800; }
        .signatures { display: flex; justify-content: space-between; margin-top: 30px; padding: 0 20px; font-weight: 700; font-size: 11px; }
        .sig-block { text-align: center; width: 180px; }
        .sig-line { border-top: 1px dashed #94a3b8; margin-top: 35px; }
        .no-print-bar { position: fixed; top: 0; left: 0; right: 0; background: #0f172a; color: #fff; padding: 10px 20px; display: flex; justify-content: space-between; align-items: center; z-index: 1000; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .btn-print { background: #714B67; color: #fff; border: none; padding: 6px 16px; border-radius: 4px; font-weight: 700; cursor: pointer; font-family: inherit; }
        .btn-close { background: #475569; color: #fff; border: none; padding: 6px 14px; border-radius: 4px; font-weight: 700; cursor: pointer; font-family: inherit; margin-right: 8px; }
        @media print {
            .no-print-bar { display: none !important; }
            body { padding: 0; }
            @page { size: A4 landscape; margin: 10mm; }
        }
    </style>
</head>
<body onload="@if($autoPrint) window.print(); @endif">

    <div class="no-print-bar">
        <div><strong>{{ $title }}</strong> - نظام كيان لإدارة الموارد</div>
        <div>
            <button type="button" class="btn-print" onclick="window.print()">طباعة الآن (Ctrl + P)</button>
            <button type="button" class="btn-close" onclick="window.close()">إغلاق</button>
        </div>
    </div>

    <div class="print-header">
        <div>
            <div class="company-name">{{ $company->company_name ?? 'شركة كيان للتجارة والتوزيع' }}</div>
            <div class="company-subtitle">{{ $company->address ?? 'الجمهورية - إدارة الحسابات المالية' }}</div>
        </div>
        <div class="report-title-box">
            <div class="report-title">ميزان المراجعة بالأرصدة والمجاميع</div>
            <div class="report-period">
                الفترة: من {{ $report['filters']['date_from'] ?: 'بداية النشاط' }} إلى {{ $report['filters']['date_to'] ?: now()->toDateString() }}
            </div>
        </div>
        <div class="meta-box">
            <div>تاريخ الاستخراج: {{ now()->format('Y-m-d H:i') }}</div>
            <div>المستخدم: {{ auth()->user()->name ?? 'مدير النظام' }}</div>
            <div>الحالة: {{ $report['totals']['is_balanced'] ? 'متوازن ومطابق' : 'يوجد فارق غير متوازن' }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th rowspan="2" style="width: 70px;">كود الحساب</th>
                <th rowspan="2">اسم الحساب</th>
                <th rowspan="2" style="width: 60px;">النوع</th>
                <th colspan="2">الرصيد الافتتاحي</th>
                <th colspan="2">حركات الفترة</th>
                <th colspan="2">الرصيد الختامي</th>
            </tr>
            <tr>
                <th style="width: 80px;">مدين</th>
                <th style="width: 80px;">دائن</th>
                <th style="width: 80px;">مدين</th>
                <th style="width: 80px;">دائن</th>
                <th style="width: 85px;">مدين</th>
                <th style="width: 85px;">دائن</th>
            </tr>
        </thead>
        <tbody>
            @foreach($report['accounts'] as $item)
                <tr class="{{ $item['level'] === 1 ? 'bg-sub' : 'bg-leaf' }}">
                    <td class="text-center font-numeric">{{ $item['code'] }}</td>
                    <td class="text-right" style="padding-right: {{ ($item['level'] - 1) * 12 + 6 }}px;">
                        {{ $item['name'] }}
                    </td>
                    <td class="text-center">{{ $item['nature'] === 'debit' ? 'مدين' : 'دائن' }}</td>
                    <td class="text-center font-numeric">{{ $item['opening_debit'] > 0 ? number_format($item['opening_debit'], 2) : '-' }}</td>
                    <td class="text-center font-numeric">{{ $item['opening_credit'] > 0 ? number_format($item['opening_credit'], 2) : '-' }}</td>
                    <td class="text-center font-numeric">{{ $item['period_debit'] > 0 ? number_format($item['period_debit'], 2) : '-' }}</td>
                    <td class="text-center font-numeric">{{ $item['period_credit'] > 0 ? number_format($item['period_credit'], 2) : '-' }}</td>
                    <td class="text-center font-numeric font-bold">{{ $item['closing_debit'] > 0 ? number_format($item['closing_debit'], 2) : '-' }}</td>
                    <td class="text-center font-numeric font-bold">{{ $item['closing_credit'] > 0 ? number_format($item['closing_credit'], 2) : '-' }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="text-right font-bold">الإجمالي العام للحسابات الرئيسية:</td>
                <td class="text-center font-numeric">{{ number_format($report['totals']['opening_debit'], 2) }}</td>
                <td class="text-center font-numeric">{{ number_format($report['totals']['opening_credit'], 2) }}</td>
                <td class="text-center font-numeric">{{ number_format($report['totals']['period_debit'], 2) }}</td>
                <td class="text-center font-numeric">{{ number_format($report['totals']['period_credit'], 2) }}</td>
                <td class="text-center font-numeric">{{ number_format($report['totals']['closing_debit'], 2) }}</td>
                <td class="text-center font-numeric">{{ number_format($report['totals']['closing_credit'], 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="signatures">
        <div class="sig-block">
            <div>المحاسب المالي</div>
            <div class="sig-line"></div>
        </div>
        <div class="sig-block">
            <div>رئيس الحسابات</div>
            <div class="sig-line"></div>
        </div>
        <div class="sig-block">
            <div>المدير المالي / العام</div>
            <div class="sig-line"></div>
        </div>
    </div>

</body>
</html>
