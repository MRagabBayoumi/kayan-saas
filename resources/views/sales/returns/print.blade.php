<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إذن مرتجع مبيعات: {{ $return->return_number }}</title>
    <link rel="stylesheet" href="{{ asset('fonts/cairo/cairo.css') }}">
    @vite(['resources/css/print/invoice-document.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Cairo', sans-serif; background: #f1f5f9; margin: 0; padding: 20px; color: #1e293b; }
        .print-card { max-width: 800px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .header-section { display: flex; justify-content: space-between; border-bottom: 2px solid #e2e8f0; padding-bottom: 15px; margin-bottom: 20px; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; background: #fee2e2; color: #991b1b; }
        .table-custom { width: 100%; border-collapse: collapse; margin-top: 15px; margin-bottom: 20px; }
        .table-custom th, .table-custom td { border: 1px solid #cbd5e1; padding: 8px 12px; text-align: right; font-size: 13px; }
        .table-custom th { background-color: #f8fafc; font-weight: bold; }
        .total-box { margin-right: auto; width: 260px; border: 1px solid #cbd5e1; padding: 12px; border-radius: 6px; background: #f8fafc; }
        @media print {
            body { background: #fff; padding: 0; }
            .print-card { box-shadow: none; padding: 15px; max-width: 100%; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="max-width: 800px; margin: 0 auto 15px; display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap;">
        <div style="display: flex; gap: 5px;">
            <a href="{{ request()->fullUrlWithQuery(['format' => 'a4']) }}" style="padding: 6px 12px; border-radius: 4px; font-size: 12px; text-decoration: none; font-weight: bold; background: {{ ($format ?? 'a4') === 'a4' ? '#0284c7; color: #fff;' : '#e2e8f0; color: #334155;' }}">A4 قياسي</a>
            <a href="{{ request()->fullUrlWithQuery(['format' => 'a5']) }}" style="padding: 6px 12px; border-radius: 4px; font-size: 12px; text-decoration: none; font-weight: bold; background: {{ ($format ?? 'a4') === 'a5' ? '#0284c7; color: #fff;' : '#e2e8f0; color: #334155;' }}">A5 وسط</a>
            <a href="{{ request()->fullUrlWithQuery(['format' => 'thermal_80mm']) }}" style="padding: 6px 12px; border-radius: 4px; font-size: 12px; text-decoration: none; font-weight: bold; background: {{ ($format ?? 'a4') === 'thermal_80mm' ? '#0284c7; color: #fff;' : '#e2e8f0; color: #334155;' }}">80 مم حراري</a>
        </div>
        <div style="display: flex; gap: 8px;">
            <button onclick="window.print()" style="padding: 8px 16px; background: #0284c7; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-family: inherit; font-weight: bold;">
                طباعة المتصفح
            </button>
            <button onclick="window.close()" style="padding: 8px 16px; background: #64748b; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-family: inherit;">
                إغلاق
            </button>
        </div>
    </div>

    <div class="print-card">
        <div class="header-section">
            <div>
                <h1 style="margin: 0 0 5px; font-size: 22px; color: #0f172a;">{{ $companySetting->company_name ?? 'مؤسسة كيان' }}</h1>
                <p style="margin: 0; font-size: 13px; color: #64748b;">إذن مرتجع بضاعة مبيعات من العميل (Sales Return)</p>
            </div>
            <div style="text-align: left;">
                <span class="badge">مرتجع مبيعات</span>
                <div style="font-family: monospace; font-weight: bold; font-size: 15px; margin-top: 5px;">{{ $return->return_number }}</div>
                <div style="font-size: 12px; color: #64748b; margin-top: 3px;">التاريخ: {{ $return->return_date ? $return->return_date->format('Y-m-d') : date('Y-m-d') }}</div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; font-size: 13px;">
            <div style="background: #f8fafc; padding: 12px; border-radius: 6px; border: 1px solid #e2e8f0;">
                <strong style="display: block; margin-bottom: 5px; color: #334155;">بيانات العميل:</strong>
                <div>العميل: <strong>{{ $return->customer?->name ?? 'عميل نقدي' }}</strong></div>
                <div>الهاتف: {{ $return->customer?->phone ?? '—' }}</div>
            </div>
            <div style="background: #f8fafc; padding: 12px; border-radius: 6px; border: 1px solid #e2e8f0;">
                <strong style="display: block; margin-bottom: 5px; color: #334155;">بيانات المستودع والفاتورة:</strong>
                <div>المستودع المسترجع إليه: {{ $return->warehouse?->name ?? '—' }}</div>
                @if($return->salesInvoice)
                    <div>مرتبط بفاتورة بيع: <strong>{{ $return->salesInvoice->invoice_number }}</strong></div>
                @endif
            </div>
        </div>

        @if($return->notes)
            <div style="margin-bottom: 15px; font-size: 12px; background: #fffbeb; border: 1px solid #fef3c7; padding: 8px 12px; border-radius: 4px;">
                <strong>سبب الإرجاع / ملاحظات:</strong> {{ $return->notes }}
            </div>
        @endif

        <table class="table-custom">
            <thead>
                <tr>
                    <th style="width: 30px; text-align: center;">#</th>
                    <th>الصنف</th>
                    <th style="width: 90px; text-align: center;">الوحدة</th>
                    <th style="width: 90px; text-align: center;">الكمية</th>
                    <th style="width: 110px; text-align: left;">سعر الاسترداد</th>
                    <th style="width: 120px; text-align: left;">إجمالي المرتجع</th>
                </tr>
            </thead>
            <tbody>
                @foreach($return->items as $idx => $item)
                    <tr>
                        <td style="text-align: center;">{{ $idx + 1 }}</td>
                        <td>
                            <strong>{{ $item->product?->name }}</strong>
                            <div style="font-size: 11px; color: #64748b; font-family: monospace;">كود: {{ $item->product?->sku }}</div>
                        </td>
                        <td style="text-align: center;">{{ $item->unit?->name ?? $item->product?->unit ?? 'قطعة' }}</td>
                        <td style="text-align: center; font-weight: bold;">{{ (float)$item->quantity }}</td>
                        <td style="text-align: left; font-family: monospace;">{{ number_format((float)$item->unit_refund_price, 2) }} ج.م</td>
                        <td style="text-align: left; font-weight: bold; font-family: monospace; color: #b91c1c;">{{ number_format((float)$item->total_refund, 2) }} ج.م</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div style="display: flex; justify-content: flex-end; margin-top: 15px;">
            <div class="total-box">
                <div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 15px; color: #b91c1c;">
                    <span>صافي قيمة المرتجع:</span>
                    <span style="font-family: monospace;">{{ number_format((float)$return->total_amount, 2) }} ج.م</span>
                </div>
            </div>
        </div>

        <div style="margin-top: 40px; display: flex; justify-content: space-between; font-size: 13px; color: #64748b; border-top: 1px dashed #cbd5e1; padding-top: 20px;">
            <div>توقيع المستلم بالمخزن: ........................</div>
            <div>توقيع العميل / المندوب: ........................</div>
        </div>
    </div>
</body>
</html>
