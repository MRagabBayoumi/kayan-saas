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
        .print-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #0f172a; padding-bottom: 8px; margin-bottom: 15px; }
        .company-name { font-size: 16px; font-weight: 800; color: #0f172a; }
        .company-subtitle { font-size: 10px; color: #64748b; }
        .report-title-box { text-align: center; }
        .report-title { font-size: 16px; font-weight: 800; color: #0f172a; margin-bottom: 3px; }
        .report-period { font-size: 10px; color: #475569; }
        .meta-box { text-align: left; font-size: 9px; color: #64748b; }
        .account-badge-box { background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 4px; padding: 6px 12px; margin-bottom: 10px; display: flex; justify-content: space-between; font-weight: 700; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #cbd5e1; padding: 4px 6px; font-size: 10.5px; }
        th { background-color: #f8fafc; font-weight: 700; text-align: center; font-size: 10px; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .text-center { text-align: center; }
        .font-bold { font-weight: 700; }
        .font-numeric { font-family: Tahoma, 'Cairo', sans-serif; direction: ltr; display: inline-block; }
        tfoot tr td { background-color: #e2e8f0; font-weight: 800; }
        .signatures { display: flex; justify-content: space-between; margin-top: 35px; font-weight: 700; font-size: 11px; }
        .sig-block { text-align: center; width: 180px; }
        .sig-line { border-top: 1px dashed #94a3b8; margin-top: 35px; }
        .no-print-bar { position: fixed; top: 0; left: 0; right: 0; background: #0f172a; color: #fff; padding: 10px 20px; display: flex; justify-content: space-between; align-items: center; z-index: 1000; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .btn-print { background: #714B67; color: #fff; border: none; padding: 6px 16px; border-radius: 4px; font-weight: 700; cursor: pointer; font-family: inherit; }
        .btn-close { background: #475569; color: #fff; border: none; padding: 6px 14px; border-radius: 4px; font-weight: 700; cursor: pointer; font-family: inherit; margin-right: 8px; }
        @media print {
            .no-print-bar { display: none !important; }
            body { padding: 0; }
            @page { size: A4 portrait; margin: 10mm; }
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
            <div class="company-subtitle">{{ $company->address ?? 'إدارة الحسابات المالية والأستاذ العام' }}</div>
        </div>
        <div class="report-title-box">
            <div class="report-title">كشف حساب الأستاذ العام (General Ledger)</div>
            <div class="report-period">
                الفترة: من {{ $ledger['filters']['date_from'] ?: 'بداية الحركات' }} إلى {{ $ledger['filters']['date_to'] }}
            </div>
        </div>
        <div class="meta-box">
            <div>تاريخ الاستخراج: {{ now()->format('Y-m-d H:i') }}</div>
            <div>المستخدم: {{ auth()->user()->name ?? 'مدير النظام' }}</div>
        </div>
    </div>

    <div class="account-badge-box">
        <div>
            <span>الحساب: <strong>{{ $account->name }}</strong></span>
            <span style="margin-right: 15px;">كود الحساب: <strong class="font-numeric">[{{ $account->code }}]</strong></span>
            <span style="margin-right: 15px;">النوع: <strong>{{ $account->type_label }}</strong></span>
        </div>
        <div>
            <span>الرصيد الافتتاحي: <strong class="font-numeric">{{ number_format($ledger['opening_balance'], 2) }} ج.م</strong></span>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 75px;">التاريخ</th>
                <th style="width: 100px;">رقم القيد / المرجع</th>
                <th>البيان والشرح</th>
                <th style="width: 110px;">الطرف المقابل</th>
                <th style="width: 80px;">مدين (+)</th>
                <th style="width: 80px;">دائن (-)</th>
                <th style="width: 90px;">الرصيد التراكمي</th>
            </tr>
        </thead>
        <tbody>
            @if($ledger['filters']['date_from'] && $ledger['opening_balance'] != 0)
                <tr style="background-color: #f8fafc; font-weight: 700;">
                    <td class="text-center font-numeric">{{ $ledger['filters']['date_from'] }}</td>
                    <td class="text-center">-</td>
                    <td colspan="2">رصيد افتتاحي منقول قبل تاريخ {{ $ledger['filters']['date_from'] }}</td>
                    <td class="text-center font-numeric">{{ $account->nature === 'debit' && $ledger['opening_balance'] > 0 ? number_format($ledger['opening_balance'], 2) : '-' }}</td>
                    <td class="text-center font-numeric">{{ $account->nature === 'credit' && $ledger['opening_balance'] > 0 ? number_format($ledger['opening_balance'], 2) : '-' }}</td>
                    <td class="text-center font-numeric">{{ number_format($ledger['opening_balance'], 2) }}</td>
                </tr>
            @endif

            @forelse($ledger['lines'] as $line)
                <tr>
                    <td class="text-center font-numeric">{{ $line['entry_date'] }}</td>
                    <td class="text-center font-numeric">{{ $line['entry_number'] }}</td>
                    <td class="text-right">{{ $line['description'] }}</td>
                    <td class="text-right">{{ $line['party_name'] ?: '-' }}</td>
                    <td class="text-center font-numeric">{{ $line['debit'] > 0 ? number_format($line['debit'], 2) : '-' }}</td>
                    <td class="text-center font-numeric">{{ $line['credit'] > 0 ? number_format($line['credit'], 2) : '-' }}</td>
                    <td class="text-center font-numeric font-bold">{{ number_format($line['running_balance'], 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="padding: 15px; color: #64748b;">
                        لا توجد حركات مسجلة للحساب خلال الفترة المحددة.
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="text-right font-bold">الإجمالي العام والرصيد الختامي للحساب:</td>
                <td class="text-center font-numeric">{{ number_format($ledger['total_debit'], 2) }}</td>
                <td class="text-center font-numeric">{{ number_format($ledger['total_credit'], 2) }}</td>
                <td class="text-center font-numeric font-bold" style="font-size: 12px;">{{ number_format($ledger['closing_balance'], 2) }}</td>
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
