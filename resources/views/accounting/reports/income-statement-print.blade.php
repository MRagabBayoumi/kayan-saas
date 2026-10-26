<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <link rel="stylesheet" href="{{ asset('fonts/cairo/cairo.css') }}">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Cairo', Tahoma, sans-serif; background: #fff; color: #1e293b; direction: rtl; font-size: 12px; line-height: 1.5; padding: 20mm 15mm; }
        .print-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #0f172a; padding-bottom: 10px; margin-bottom: 20px; }
        .company-name { font-size: 18px; font-weight: 800; color: #0f172a; }
        .company-subtitle { font-size: 11px; color: #64748b; }
        .report-title-box { text-align: center; }
        .report-title { font-size: 18px; font-weight: 800; color: #0f172a; }
        .report-period { font-size: 11px; color: #475569; }
        .meta-box { text-align: left; font-size: 10px; color: #64748b; }
        .statement-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .statement-table th, .statement-table td { padding: 6px 10px; border-bottom: 1px solid #e2e8f0; }
        .sec-title { background: #f8fafc; font-weight: 800; font-size: 13px; color: #0f172a; border-top: 2px solid #cbd5e1; }
        .sub-total { background: #f1f5f9; font-weight: 700; }
        .final-profit { background: #0f172a; color: #ffffff; font-weight: 800; font-size: 14px; }
        .font-numeric { font-family: Tahoma, 'Cairo', sans-serif; direction: ltr; display: inline-block; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .signatures { display: flex; justify-content: space-between; margin-top: 45px; font-weight: 700; font-size: 11px; }
        .sig-block { text-align: center; width: 180px; }
        .sig-line { border-top: 1px dashed #94a3b8; margin-top: 40px; }
        .no-print-bar { position: fixed; top: 0; left: 0; right: 0; background: #0f172a; color: #fff; padding: 10px 20px; display: flex; justify-content: space-between; align-items: center; z-index: 1000; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .btn-print { background: #714B67; color: #fff; border: none; padding: 6px 16px; border-radius: 4px; font-weight: 700; cursor: pointer; font-family: inherit; }
        .btn-close { background: #475569; color: #fff; border: none; padding: 6px 14px; border-radius: 4px; font-weight: 700; cursor: pointer; font-family: inherit; margin-right: 8px; }
        @media print {
            .no-print-bar { display: none !important; }
            body { padding: 0; }
            @page { size: A4 portrait; margin: 15mm; }
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
            <div class="company-subtitle">{{ $company->address ?? 'إدارة الحسابات المالية والختامية' }}</div>
        </div>
        <div class="report-title-box">
            <div class="report-title">قائمة الدخل والأرباح والخسائر</div>
            <div class="report-period">
                الفترة: من {{ $report['filters']['date_from'] ?: 'بداية النشاط' }} إلى {{ $report['filters']['date_to'] }}
            </div>
        </div>
        <div class="meta-box">
            <div>تاريخ الطباعة: {{ now()->format('Y-m-d H:i') }}</div>
            <div>المستخدم: {{ auth()->user()->name ?? 'مدير النظام' }}</div>
        </div>
    </div>

    <table class="statement-table">
        <tbody>
            <!-- 1. Operating Revenues -->
            <tr class="sec-title">
                <td colspan="2">1. إيرادات النشاط والمبيعات</td>
            </tr>
            @foreach($report['gross_sales'] as $sale)
                <tr>
                    <td style="padding-right: 25px;">{{ $sale['name'] }} [{{ $sale['code'] }}]</td>
                    <td class="text-left font-numeric">{{ number_format($sale['amount'], 2) }} ج.م</td>
                </tr>
            @endforeach
            @foreach($report['sales_deductions'] as $ded)
                <tr style="color: #b91c1c;">
                    <td style="padding-right: 25px;">(-) {{ $ded['name'] }} [{{ $ded['code'] }}]</td>
                    <td class="text-left font-numeric">({{ number_format($ded['amount'], 2) }}) ج.م</td>
                </tr>
            @endforeach
            <tr class="sub-total">
                <td><strong>صافي إيرادات النشاط (Net Revenue)</strong></td>
                <td class="text-left font-numeric"><strong>{{ number_format($report['net_revenue'], 2) }} ج.م</strong></td>
            </tr>

            <!-- 2. COGS -->
            <tr class="sec-title">
                <td colspan="2">2. تكلفة المبيعات (Cost of Goods Sold - FIFO)</td>
            </tr>
            @foreach($report['cogs'] as $cg)
                <tr>
                    <td style="padding-right: 25px;">{{ $cg['name'] }} [{{ $cg['code'] }}]</td>
                    <td class="text-left font-numeric">(-) {{ number_format($cg['amount'], 2) }} ج.م</td>
                </tr>
            @endforeach
            <tr class="sub-total" style="background-color: #fef3c7;">
                <td><strong>مجمل الربح التجاري (Gross Profit) — هامش {{ $report['gross_margin_pct'] }}%</strong></td>
                <td class="text-left font-numeric"><strong>{{ number_format($report['gross_profit'], 2) }} ج.م</strong></td>
            </tr>

            <!-- 3. Expenses -->
            <tr class="sec-title">
                <td colspan="2">3. المصروفات التشغيلية والعمومية</td>
            </tr>
            @forelse($report['operating_expenses'] as $exp)
                <tr>
                    <td style="padding-right: 25px;">{{ $exp['name'] }} [{{ $exp['code'] }}]</td>
                    <td class="text-left font-numeric">(-) {{ number_format($exp['amount'], 2) }} ج.م</td>
                </tr>
            @empty
                <tr>
                    <td colspan="2" style="padding-right: 25px; color: #64748b; font-style: italic;">لا توجد مصروفات مسجلة للفترة.</td>
                </tr>
            @endforelse
            <tr class="sub-total">
                <td><strong>صافي الربح التشغيلي (Operating Profit)</strong></td>
                <td class="text-left font-numeric"><strong>{{ number_format($report['operating_profit'], 2) }} ج.م</strong></td>
            </tr>

            <!-- 4. Other Items -->
            @if(count($report['other_items']) > 0)
                <tr class="sec-title">
                    <td colspan="2">4. إيرادات وتكاليف أخرى</td>
                </tr>
                @foreach($report['other_items'] as $other)
                    <tr>
                        <td style="padding-right: 25px;">{{ $other['name'] }} [{{ $other['code'] }}]</td>
                        <td class="text-left font-numeric">(+) {{ number_format($other['amount'], 2) }} ج.م</td>
                    </tr>
                @endforeach
            @endif

            <!-- Final Net Profit -->
            <tr class="final-profit">
                <td style="padding: 10px;">صافي الربح / الخسارة النهائي للفترة (Net Profit / Loss):</td>
                <td class="text-left font-numeric" style="padding: 10px; font-size: 15px;">{{ number_format($report['net_profit'], 2) }} ج.م</td>
            </tr>
        </tbody>
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
            <div>المدير العام / الاعتماد</div>
            <div class="sig-line"></div>
        </div>
    </div>

</body>
</html>
