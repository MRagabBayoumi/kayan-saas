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
        .report-title { font-size: 16px; font-weight: 800; color: #0f172a; }
        .report-period { font-size: 10px; color: #475569; }
        .meta-box { text-align: left; font-size: 9px; color: #64748b; }
        .bs-grid { display: flex; gap: 15px; }
        .bs-col { flex: 1; border: 1px solid #cbd5e1; border-radius: 4px; overflow: hidden; }
        .col-header { background: #0f172a; color: #fff; padding: 6px 10px; font-weight: 800; font-size: 12px; display: flex; justify-content: space-between; }
        .col-body { padding: 8px 10px; }
        .section-header { font-weight: 800; border-bottom: 1px solid #cbd5e1; padding-bottom: 3px; margin: 8px 0 4px 0; color: #1e293b; font-size: 11px; display: flex; justify-content: space-between; }
        .item-row { display: flex; justify-content: space-between; padding: 3px 0; border-bottom: 1px dashed #e2e8f0; font-size: 10.5px; }
        .font-numeric { font-family: Tahoma, 'Cairo', sans-serif; direction: ltr; display: inline-block; }
        .col-footer { background: #1e293b; color: #fff; padding: 6px 10px; font-weight: 800; font-size: 12px; display: flex; justify-content: space-between; }
        .balance-check-bar { margin-top: 15px; padding: 8px 12px; border: 1px solid #cbd5e1; background: #f8fafc; font-weight: 800; display: flex; justify-content: space-between; align-items: center; }
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
            <div class="company-subtitle">{{ $company->address ?? 'إدارة الحسابات المالية والختامية' }}</div>
        </div>
        <div class="report-title-box">
            <div class="report-title">الميزانية العمومية والمركز المالي</div>
            <div class="report-period">
                حتى تاريخ: {{ $report['as_of_date'] }}
            </div>
        </div>
        <div class="meta-box">
            <div>تاريخ الاستخراج: {{ now()->format('Y-m-d H:i') }}</div>
            <div>المستخدم: {{ auth()->user()->name ?? 'مدير النظام' }}</div>
            <div>الحالة: {{ $report['is_balanced'] ? 'متوازنة ومطابقة 100%' : 'يوجد فارق' }}</div>
        </div>
    </div>

    <div class="bs-grid">
        <!-- ASSETS -->
        <div class="bs-col">
            <div class="col-header">
                <span>الأصول (Assets)</span>
                <span class="font-numeric">{{ number_format($report['total_assets'], 2) }} ج.م</span>
            </div>
            <div class="col-body">
                <div class="section-header">
                    <span>1. الأصول المتداولة (Current Assets)</span>
                    <span class="font-numeric">{{ number_format($report['total_current_assets'], 2) }}</span>
                </div>
                @forelse($report['current_assets'] as $asset)
                    @php $b = (float)$asset['balance']; @endphp
                    <div class="item-row">
                        <span>{{ $asset['name'] }} [{{ $asset['code'] }}]</span>
                        <span class="font-numeric {{ $b < -0.001 ? 'text-danger' : '' }}">{{ $b < -0.001 ? '('.number_format(abs($b), 2).')' : number_format($b, 2) }} ج.م</span>
                    </div>
                @empty
                    <div class="item-row" style="color: #64748b; font-style: italic;">لا توجد أصول متداولة.</div>
                @endforelse

                <div class="section-header" style="margin-top: 15px;">
                    <span>2. الأصول الثابتة (Fixed Assets)</span>
                    <span class="font-numeric">{{ number_format($report['total_non_current_assets'], 2) }}</span>
                </div>
                @forelse($report['non_current_assets'] as $asset)
                    @php $b = (float)$asset['balance']; @endphp
                    <div class="item-row">
                        <span>{{ $asset['name'] }} [{{ $asset['code'] }}]</span>
                        <span class="font-numeric {{ $b < -0.001 ? 'text-danger' : '' }}">{{ $b < -0.001 ? '('.number_format(abs($b), 2).')' : number_format($b, 2) }} ج.م</span>
                    </div>
                @empty
                    <div class="item-row" style="color: #64748b; font-style: italic;">لا توجد أصول ثابتة.</div>
                @endforelse
            </div>
            <div class="col-footer">
                <span>إجمالي الأصول:</span>
                <span class="font-numeric">{{ number_format($report['total_assets'], 2) }} ج.م</span>
            </div>
        </div>

        <!-- LIABILITIES & EQUITY -->
        <div class="bs-col">
            <div class="col-header" style="background: #334155;">
                <span>الخصوم وحقوق الملكية (Liabilities & Equity)</span>
                <span class="font-numeric">{{ number_format($report['total_liabilities_and_equity'], 2) }} ج.م</span>
            </div>
            <div class="col-body">
                <div class="section-header">
                    <span>1. الخصوم المتداولة (Current Liabilities)</span>
                    <span class="font-numeric">{{ number_format($report['total_current_liabilities'], 2) }}</span>
                </div>
                @forelse($report['current_liabilities'] as $liab)
                    <div class="item-row">
                        <span>{{ $liab['name'] }} [{{ $liab['code'] }}]</span>
                        <span class="font-numeric">{{ number_format($liab['balance'], 2) }} ج.م</span>
                    </div>
                @empty
                    <div class="item-row" style="color: #64748b; font-style: italic;">لا توجد التزامات متداولة.</div>
                @endforelse

                @if($report['total_non_current_liabilities'] > 0)
                    <div class="section-header" style="margin-top: 15px;">
                        <span>2. الخصوم طويلة الأجل</span>
                        <span class="font-numeric">{{ number_format($report['total_non_current_liabilities'], 2) }}</span>
                    </div>
                    @foreach($report['non_current_liabilities'] as $liab)
                        <div class="item-row">
                            <span>{{ $liab['name'] }} [{{ $liab['code'] }}]</span>
                            <span class="font-numeric">{{ number_format($liab['balance'], 2) }} ج.م</span>
                        </div>
                    @endforeach
                @endif

                <div class="section-header" style="margin-top: 15px;">
                    <span>3. حقوق الملكية (Owners' Equity)</span>
                    <span class="font-numeric">{{ number_format($report['total_equity'], 2) }}</span>
                </div>
                @foreach($report['equity_items'] as $eq)
                    <div class="item-row">
                        <span>{{ $eq['name'] }} [{{ $eq['code'] }}]</span>
                        <span class="font-numeric">{{ number_format($eq['balance'], 2) }} ج.م</span>
                    </div>
                @endforeach
                <div class="item-row" style="background: #f1f5f9; font-weight: 700;">
                    <span>صافي أرباح الفترة الحالية (من قائمة الدخل):</span>
                    <span class="font-numeric">{{ number_format($report['current_period_net_profit'], 2) }} ج.م</span>
                </div>
            </div>
            <div class="col-footer" style="background: #334155;">
                <span>إجمالي الخصوم وحقوق الملكية:</span>
                <span class="font-numeric">{{ number_format($report['total_liabilities_and_equity'], 2) }} ج.م</span>
            </div>
        </div>
    </div>

    <div class="balance-check-bar">
        <span>معادلة المركز المالي: الأصول ({{ number_format($report['total_assets'], 2) }}) = الخصوم وحقوق الملكية ({{ number_format($report['total_liabilities_and_equity'], 2) }})</span>
        <span style="color: {{ $report['is_balanced'] ? '#15803d' : '#b91c1c' }};">
            {{ $report['is_balanced'] ? '✓ الميزانية متوازنة ومطابقة تماماً' : '⚠ فارق: ' . number_format($report['diff'], 2) }}
        </span>
    </div>

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
