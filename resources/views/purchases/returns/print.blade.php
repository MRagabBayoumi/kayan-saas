<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إذن مرتجع مشتريات: {{ $return->return_number }}</title>
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
            <button type="button" data-action="python-direct-print" data-url="{{ route('purchase-returns.direct-print', [$return, 'format' => $format ?? 'a4']) }}" style="padding: 8px 16px; background: #059669; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-family: inherit; font-weight: bold;">
                {{ ($format ?? 'a4') === 'thermal_80mm' ? 'طباعة فورية (XP-80C) ⚡' : 'طباعة بايثون المباشرة' }}
            </button>
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
                <p style="margin: 0; font-size: 13px; color: #64748b;">إذن مرتجع بضاعة إلى المورد (Purchase Return)</p>
            </div>
            <div style="text-align: left;">
                <span class="badge">مرتجع مشتريات</span>
                <div style="font-family: monospace; font-weight: bold; font-size: 15px; margin-top: 5px;">{{ $return->return_number }}</div>
                <div style="font-size: 12px; color: #64748b; margin-top: 3px;">التاريخ: {{ $return->return_date ? $return->return_date->format('Y-m-d') : date('Y-m-d') }}</div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; font-size: 13px;">
            <div style="background: #f8fafc; padding: 12px; border-radius: 6px; border: 1px solid #e2e8f0;">
                <strong style="display: block; margin-bottom: 5px; color: #334155;">بيانات المورد:</strong>
                <div>المورد: <strong>{{ $return->supplier?->name ?? '—' }}</strong></div>
                <div>الهاتف: {{ $return->supplier?->phone ?? '—' }}</div>
            </div>
            <div style="background: #f8fafc; padding: 12px; border-radius: 6px; border: 1px solid #e2e8f0;">
                <strong style="display: block; margin-bottom: 5px; color: #334155;">بيانات الصرف والتسوية:</strong>
                <div>المستودع المصروف منه: {{ $return->warehouse?->name ?? '—' }}</div>
                <div>طريقة التسوية: <strong>{{ $return->settlement_type_label }}</strong></div>
                @if($return->purchaseInvoice)
                    <div>مرتبط بفاتورة شراء: <strong>{{ $return->purchaseInvoice->invoice_number }}</strong></div>
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
                    <th style="width: 110px; text-align: left;">سعر التكلفة</th>
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
                        <td style="text-align: center;">{{ $item->unit?->name ?? 'قطعة' }}</td>
                        <td style="text-align: center; font-weight: bold;">{{ (float)$item->quantity }}</td>
                        <td style="text-align: left; font-family: monospace;">{{ number_format((float)$item->unit_cost, 2) }} ج.م</td>
                        <td style="text-align: left; font-weight: bold; font-family: monospace;">{{ number_format((float)$item->total_cost, 2) }} ج.م</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div style="font-size: 12px; color: #64748b;">
                <div>المستخدم: {{ $return->creator?->name ?? auth()->user()->name }}</div>
                <div>وقت الطباعة: {{ now()->format('Y-m-d H:i') }}</div>
            </div>

            <div class="total-box">
                <div style="display: flex; justify-content: space-between; font-size: 15px; font-weight: bold; color: #0f172a;">
                    <span>صافي قيمة المرتجع:</span>
                    <span style="font-family: monospace; color: #dc2626;">{{ number_format((float)$return->total_amount, 2) }} ج.م</span>
                </div>
            </div>
        </div>

        <div style="display: flex; justify-content: space-around; margin-top: 50px; font-size: 13px; text-align: center;">
            <div>
                <div>أمين المخزن (المُسلّم)</div>
                <div style="margin-top: 35px; border-top: 1px dotted #94a3b8; width: 140px;">التوقيع</div>
            </div>
            <div>
                <div>مندوب / ممثل المورد (المُستلم)</div>
                <div style="margin-top: 35px; border-top: 1px dotted #94a3b8; width: 140px;">التوقيع والختم</div>
            </div>
        </div>
    </div>

    @if($autoPrint)
        <script>
            window.addEventListener('DOMContentLoaded', () => {
                window.print();
            });
        </script>
    @endif
</body>
</html>
