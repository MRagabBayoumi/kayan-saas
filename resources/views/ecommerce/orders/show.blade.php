@extends('layouts.app')

@section('module_name', 'تفاصيل طلب المتجر الإلكتروني')

@section('control_panel')
    <x-control-panel title="طلب رقم: {{ $order->order_number }}" :breadcrumbs="['المتجر الإلكتروني' => route('ecommerce.orders.index'), 'الطلبات' => route('ecommerce.orders.index'), $order->order_number => '#']">
        <x-slot:actions>
            <a href="{{ route('ecommerce.orders.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="arrow-right" size="16" />
                <span>العودة لقائمة الطلبات</span>
            </a>
            <button type="button" onclick="window.print()" class="erp-btn erp-btn-secondary">
                <x-icon name="printer" size="16" />
                <span>طباعة إذن التوصيل</span>
            </button>
        </x-slot:actions>

        <x-slot:extra>
            @php $badge = $order->status_badge; @endphp
            <span class="erp-badge" style="background-color: var(--bg-surface-alt); border: 1px solid var(--border-color);">
                <x-icon :name="$badge['icon']" size="14" />
                <span>{{ $badge['label'] }}</span>
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
<div data-page="ecommerce-order-show">

    @if(session('success'))
        <div class="erp-alert erp-alert-success mb-4" role="alert">
            <x-icon name="check-circle" size="18" />
            <div>{{ session('success') }}</div>
        </div>
    @endif

    @if(session('error'))
        <div class="erp-alert erp-alert-danger mb-4" role="alert">
            <x-icon name="alert-triangle" size="18" />
            <div>{{ session('error') }}</div>
        </div>
    @endif

    <!-- 2-Column Responsive Layout -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 16px; align-items: start;">
        
        <!-- Main Column: Order Items & Financial Totals -->
        <div style="display: flex; flex-direction: column; gap: 16px;">
            
            <!-- Items Card -->
            <div class="erp-card">
                <div class="erp-card-header">
                    <h3 class="erp-card-title">
                        <x-icon name="package" size="18" style="color: var(--primary);" />
                        <span>أصناف الشحنة المطلوبة ({{ $order->items->count() }} صنف)</span>
                    </h3>
                    <span class="font-numeric text-xs font-bold" style="color: var(--text-muted);">
                        تاريخ الطلب: {{ $order->created_at->format('Y-m-d h:i A') }}
                    </span>
                </div>

                <div class="erp-table-responsive">
                    <table class="erp-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>اسم المنتج</th>
                                <th>الوحدة</th>
                                <th>الكمية</th>
                                <th>سعر الوحدة</th>
                                <th>الإجمالي</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $idx => $item)
                                <tr>
                                    <td class="font-numeric text-xs" style="color: var(--text-muted);">{{ $idx + 1 }}</td>
                                    <td>
                                        <strong style="color: var(--text-main);">{{ $item->product_name }}</strong>
                                        @if($item->product)
                                            <span style="display: block; font-size: 11px; font-family: monospace; color: var(--text-muted);">كود: {{ $item->product->sku }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $item->unit }}</td>
                                    <td class="font-numeric font-bold">{{ (float) $item->quantity }}</td>
                                    <td class="font-numeric">{{ number_format($item->unit_price, 2) }} ج.م</td>
                                    <td class="font-numeric font-bold" style="color: var(--primary);">{{ number_format($item->subtotal, 2) }} ج.م</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Financial Totals -->
                <div style="padding: 16px; background-color: var(--bg-surface-alt); border-top: 1px solid var(--border-light); display: flex; flex-direction: column; gap: 8px; font-size: 13px;">
                    <div style="display: flex; justify-content: space-between; color: var(--text-muted);">
                        <span>المجموع الفرعي للمنتجات:</span>
                        <strong class="font-numeric" style="color: var(--text-main);">{{ number_format($order->subtotal, 2) }} ج.م</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; color: var(--text-muted);">
                        <span>مصاريف الشحن والتوصيل:</span>
                        <strong class="font-numeric" style="color: var(--text-main);">{{ number_format($order->shipping_cost, 2) }} ج.م</strong>
                    </div>
                    @if($order->discount_amount > 0)
                        <div style="display: flex; justify-content: space-between; color: var(--danger);">
                            <span>الخصم:</span>
                            <strong class="font-numeric">-{{ number_format($order->discount_amount, 2) }} ج.م</strong>
                        </div>
                    @endif
                    <div style="display: flex; justify-content: space-between; align-items: baseline; padding-top: 8px; border-top: 1px solid var(--border-color);">
                        <strong style="font-size: 14px; color: var(--text-main);">المبلغ الإجمالي المستحق:</strong>
                        <strong class="font-numeric" style="font-size: 18px; color: var(--primary);">{{ number_format($order->total, 2) }} ج.م</strong>
                    </div>
                </div>
            </div>

            <!-- Customer Notes -->
            @if($order->customer_notes)
                <div class="erp-card">
                    <div class="erp-card-header">
                        <h4 class="erp-card-title">
                            <x-icon name="message-square" size="16" style="color: var(--primary);" />
                            <span>ملاحظات العميل مع الطلب</span>
                        </h4>
                    </div>
                    <div class="erp-card-body" style="font-size: 13px; color: var(--text-main); line-height: 1.6;">
                        {{ $order->customer_notes }}
                    </div>
                </div>
            @endif

        </div>

        <!-- Sidebar Column: Customer, Status, Invoice -->
        <div style="display: flex; flex-direction: column; gap: 16px;">
            
            <!-- Customer Details Card -->
            <div class="erp-card">
                <div class="erp-card-header">
                    <h3 class="erp-card-title">
                        <x-icon name="user" size="18" style="color: var(--primary);" />
                        <span>بيانات العميل المستلم</span>
                    </h3>
                </div>
                <div class="erp-card-body" style="display: flex; flex-direction: column; gap: 12px; font-size: 13px;">
                    <div>
                        <span style="font-size: 11px; color: var(--text-muted); display: block;">اسم العميل:</span>
                        <strong style="font-size: 14px; color: var(--text-main);">{{ $order->customer_name }}</strong>
                    </div>

                    <div>
                        <span style="font-size: 11px; color: var(--text-muted); display: block;">رقم الهاتف:</span>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <strong class="font-numeric" dir="ltr" style="font-size: 14px; color: var(--text-main);">{{ $order->customer_phone }}</strong>
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $order->customer_phone) }}" target="_blank" class="erp-btn erp-btn-secondary" style="padding: 2px 8px; font-size: 11px;" title="مراسلة واتساب">
                                <x-icon name="message-circle" size="13" style="color: #059669;" />
                                <span>واتساب</span>
                            </a>
                        </div>
                    </div>

                    @if($order->customer_email)
                        <div>
                            <span style="font-size: 11px; color: var(--text-muted); display: block;">البريد الإلكتروني:</span>
                            <span class="font-numeric" style="color: var(--text-main);">{{ $order->customer_email }}</span>
                        </div>
                    @endif

                    <div>
                        <span style="font-size: 11px; color: var(--text-muted); display: block;">المحافظة / المدينة:</span>
                        <span style="color: var(--text-main); font-weight: 600;">{{ $order->customer_city ?? 'غير محدد' }}</span>
                    </div>

                    <div>
                        <span style="font-size: 11px; color: var(--text-muted); display: block;">عنوان التوصيل بالتفصيل:</span>
                        <div style="padding: 8px 12px; background: var(--bg-surface-alt); border: 1px solid var(--border-light); border-radius: var(--border-radius-sm); font-size: 12px; line-height: 1.5; color: var(--text-main);">
                            {{ $order->customer_address }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Update Card -->
            <div class="erp-card">
                <div class="erp-card-header">
                    <h3 class="erp-card-title">
                        <x-icon name="activity" size="18" style="color: var(--primary);" />
                        <span>تحديث حالة الطلب والدفع</span>
                    </h3>
                </div>
                <div class="erp-card-body">
                    <form action="{{ route('ecommerce.orders.update-status', $order) }}" method="POST" style="display: flex; flex-direction: column; gap: 12px;">
                        @csrf
                        <div class="erp-form-group" style="margin-bottom: 0;">
                            <label for="status" class="erp-form-label">حالة الطلب:</label>
                            <select name="status" id="status" class="erp-select">
                                <option value="pending" {{ $order->status === 'pending' ? 'selected' : '' }}>قيد الانتظار (Pending)</option>
                                <option value="confirmed" {{ $order->status === 'confirmed' ? 'selected' : '' }}>تم التأكيد (Confirmed)</option>
                                <option value="processing" {{ $order->status === 'processing' ? 'selected' : '' }}>جاري التجهيز (Processing)</option>
                                <option value="shipped" {{ $order->status === 'shipped' ? 'selected' : '' }}>تم الشحن (Shipped)</option>
                                <option value="delivered" {{ $order->status === 'delivered' ? 'selected' : '' }}>تم التوصيل (Delivered)</option>
                                <option value="cancelled" {{ $order->status === 'cancelled' ? 'selected' : '' }}>ملغي (Cancelled)</option>
                            </select>
                        </div>

                        <div class="erp-form-group" style="margin-bottom: 0;">
                            <label for="payment_status" class="erp-form-label">حالة الدفع:</label>
                            <select name="payment_status" id="payment_status" class="erp-select">
                                <option value="unpaid" {{ $order->payment_status === 'unpaid' ? 'selected' : '' }}>غير مدفوع (الدفع عند الاستلام)</option>
                                <option value="paid" {{ $order->payment_status === 'paid' ? 'selected' : '' }}>تم سداد المبلغ بالكامل</option>
                            </select>
                        </div>

                        <button type="submit" class="erp-btn erp-btn-primary" style="justify-content: center; margin-top: 4px;">
                            <x-icon name="check" size="14" />
                            <span>تحديث الحالة</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Convert to Sales Invoice Card -->
            <div class="erp-card">
                <div class="erp-card-header">
                    <h3 class="erp-card-title">
                        <x-icon name="file-text" size="18" style="color: var(--primary);" />
                        <span>فاتورة المبيعات الرسمية (ERP)</span>
                    </h3>
                </div>
                <div class="erp-card-body">
                    @if($order->salesInvoice)
                        <div class="erp-alert erp-alert-success" style="margin-bottom: 0;">
                            <x-icon name="check-circle" size="16" />
                            <div>
                                <strong style="display: block; font-size: 13px;">تم إصدار فاتورة مبيعات لهذا الطلب</strong>
                                <span class="font-numeric" style="font-size: 12px;">رقم الفاتورة: <strong>{{ $order->salesInvoice->invoice_number }}</strong></span>
                                <div style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">تاريخ الإصدار: {{ $order->salesInvoice->invoice_date }}</div>
                            </div>
                        </div>
                    @else
                        <form action="{{ route('ecommerce.orders.convert-invoice', $order) }}" method="POST" style="display: flex; flex-direction: column; gap: 12px;">
                            @csrf
                            <p style="font-size: 12px; color: var(--text-muted); margin: 0; line-height: 1.5;">
                                تحويل هذا الطلب تلقائياً إلى فاتورة مبيعات رسمية وخصم الكميات من المستودع بنظام FIFO.
                            </p>

                            <div class="erp-form-group" style="margin-bottom: 0;">
                                <label for="warehouse_id" class="erp-form-label">مستودع الصرف:</label>
                                <select name="warehouse_id" id="warehouse_id" required class="erp-select">
                                    @foreach($warehouses as $wh)
                                        <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <button type="submit" 
                                    onclick="return confirm('هل أنت متأكد من تحويل هذا الطلب إلى فاتورة مبيعات وخصم المخزون؟')"
                                    class="erp-btn erp-btn-success" 
                                    style="justify-content: center; background-color: var(--success); color: white;">
                                <x-icon name="file-plus" size="15" />
                                <span>إصدار فاتورة مبيعات ERP</span>
                            </button>
                        </form>
                    @endif
                </div>
            </div>

        </div>

    </div>

</div>
@endsection
