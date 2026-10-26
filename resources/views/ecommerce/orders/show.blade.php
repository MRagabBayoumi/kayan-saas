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
    </x-control-panel>
@endsection

@section('content')
<div class="space-y-6">

    @if(session('success'))
        <div class="erp-alert erp-alert-success" role="alert">
            <x-icon name="check" size="16" />
            <div>{{ session('success') }}</div>
        </div>
    @endif
    @if(session('error'))
        <div class="erp-alert erp-alert-danger" role="alert">
            <x-icon name="alert-triangle" size="16" />
            <div>{{ session('error') }}</div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Main Order Details & Items (2 Cols) -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Order Header Card -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs flex flex-wrap items-center justify-between gap-4">
                <div class="space-y-1">
                    <span class="text-xs text-slate-400 font-medium">رقم الطلب الرسمي</span>
                    <h2 class="text-xl font-bold font-mono text-slate-900">{{ $order->order_number }}</h2>
                    <span class="text-xs text-slate-500">تم الطلب بتاريخ: {{ $order->created_at->format('Y-m-d h:i A') }}</span>
                </div>

                <div class="flex items-center gap-2">
                    @php $badge = $order->status_badge; @endphp
                    <span class="px-3 py-1.5 rounded-xl text-xs font-bold border inline-flex items-center gap-1.5 {{ $badge['bg'] }}">
                        <x-icon :name="$badge['icon']" size="14" />
                        <span>{{ $badge['label'] }}</span>
                    </span>

                    @if($order->payment_status === 'paid')
                        <span class="px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                            مدفوع
                        </span>
                    @else
                        <span class="px-3 py-1.5 rounded-xl text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300">
                            الدفع عند الاستلام
                        </span>
                    @endif
                </div>
            </div>

            <!-- Items Table Card -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
                <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="font-bold text-slate-800 text-sm">أصناف الشحنة المطلوبة</h3>
                    <span class="text-xs text-slate-500 font-bold">{{ $order->items->count() }} صنف</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-right text-xs">
                        <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200">
                            <tr>
                                <th class="p-3">#</th>
                                <th class="p-3">اسم المنتج</th>
                                <th class="p-3">الوحدة</th>
                                <th class="p-3">الكمية</th>
                                <th class="p-3">سعر الوحدة</th>
                                <th class="p-3">الإجمالي</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($order->items as $idx => $item)
                                <tr>
                                    <td class="p-3 text-slate-400">{{ $idx + 1 }}</td>
                                    <td class="p-3 font-bold text-slate-900">
                                        {{ $item->product_name }}
                                        @if($item->product)
                                            <span class="block text-[11px] font-mono text-slate-400">SKU: {{ $item->product->sku }}</span>
                                        @endif
                                    </td>
                                    <td class="p-3 text-slate-600">{{ $item->unit }}</td>
                                    <td class="p-3 font-bold text-slate-900">{{ (float) $item->quantity }}</td>
                                    <td class="p-3 text-slate-700 font-numeric">{{ number_format($item->unit_price, 2) }} ج.م</td>
                                    <td class="p-3 font-bold text-indigo-700 font-numeric">{{ number_format($item->subtotal, 2) }} ج.م</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Financial Totals -->
                <div class="bg-slate-50 p-4 border-t border-slate-200 space-y-2 text-xs">
                    <div class="flex justify-between text-slate-600">
                        <span>المجموع الفرعي:</span>
                        <span class="font-bold text-slate-900 font-numeric">{{ number_format($order->subtotal, 2) }} ج.م</span>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>مصاريف الشحن والتوصيل:</span>
                        <span class="font-bold text-slate-900 font-numeric">{{ number_format($order->shipping_cost, 2) }} ج.م</span>
                    </div>
                    @if($order->discount_amount > 0)
                        <div class="flex justify-between text-rose-600">
                            <span>الخصم:</span>
                            <span class="font-bold font-numeric">-{{ number_format($order->discount_amount, 2) }} ج.م</span>
                        </div>
                    @endif
                    <div class="flex justify-between text-sm font-bold text-slate-900 pt-2 border-t border-slate-200">
                        <span>المبلغ الإجمالي المستحق:</span>
                        <span class="text-base font-black text-indigo-700 font-numeric">{{ number_format($order->total, 2) }} ج.م</span>
                    </div>
                </div>
            </div>

            <!-- Customer Notes -->
            @if($order->customer_notes)
                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs space-y-2">
                    <h4 class="font-bold text-slate-800 text-xs flex items-center gap-1.5">
                        <x-icon name="message-square" size="14" class="text-indigo-600" />
                        <span>ملاحظات العميل مع الطلب:</span>
                    </h4>
                    <p class="text-xs text-slate-600 leading-relaxed bg-slate-50 p-3 rounded-xl border border-slate-100">
                        {{ $order->customer_notes }}
                    </p>
                </div>
            @endif

        </div>

        <!-- Sidebar Actions & Customer Info (1 Col) -->
        <div class="space-y-6">

            <!-- Customer Information Card -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs space-y-4">
                <h3 class="font-bold text-slate-800 text-sm pb-2 border-b border-slate-100 flex items-center gap-2">
                    <x-icon name="user" size="16" class="text-indigo-600" />
                    <span>بيانات العميل المستلم</span>
                </h3>

                <div class="space-y-3 text-xs">
                    <div>
                        <span class="text-slate-400 block mb-0.5">الاسم:</span>
                        <strong class="text-slate-800 text-sm">{{ $order->customer_name }}</strong>
                    </div>

                    <div>
                        <span class="text-slate-400 block mb-0.5">رقم الهاتف:</span>
                        <div class="flex items-center gap-2">
                            <strong class="text-slate-900 font-mono" dir="ltr">{{ $order->customer_phone }}</strong>
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $order->customer_phone) }}" target="_blank" class="text-emerald-600 hover:text-emerald-700" title="مراسلة واتساب">
                                <x-icon name="message-circle" size="14" />
                            </a>
                        </div>
                    </div>

                    @if($order->customer_email)
                        <div>
                            <span class="text-slate-400 block mb-0.5">البريد الإلكتروني:</span>
                            <span class="text-slate-700 font-mono">{{ $order->customer_email }}</span>
                        </div>
                    @endif

                    <div>
                        <span class="text-slate-400 block mb-0.5">المحافظة / المدينة:</span>
                        <span class="text-slate-700 font-medium">{{ $order->customer_city ?? 'غير محدد' }}</span>
                    </div>

                    <div>
                        <span class="text-slate-400 block mb-0.5">عنوان التوصيل بالتفصيل:</span>
                        <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-100 text-slate-700 leading-relaxed">
                            {{ $order->customer_address }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Manager Card -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs space-y-4">
                <h3 class="font-bold text-slate-800 text-sm pb-2 border-b border-slate-100 flex items-center gap-2">
                    <x-icon name="activity" size="16" class="text-indigo-600" />
                    <span>تحديث حالة الطلب</span>
                </h3>

                <form action="{{ route('ecommerce.orders.update-status', $order) }}" method="POST" class="space-y-3">
                    @csrf
                    <div>
                        <label for="status" class="block text-xs font-bold text-slate-700 mb-1">حالة الطلب:</label>
                        <select name="status" id="status" class="w-full text-xs p-2 bg-slate-50 border border-slate-200 rounded-xl font-bold">
                            <option value="pending" {{ $order->status === 'pending' ? 'selected' : '' }}>قيد الانتظار (Pending)</option>
                            <option value="confirmed" {{ $order->status === 'confirmed' ? 'selected' : '' }}>تم التأكيد (Confirmed)</option>
                            <option value="processing" {{ $order->status === 'processing' ? 'selected' : '' }}>جاري التجهيز (Processing)</option>
                            <option value="shipped" {{ $order->status === 'shipped' ? 'selected' : '' }}>تم الشحن (Shipped)</option>
                            <option value="delivered" {{ $order->status === 'delivered' ? 'selected' : '' }}>تم التوصيل (Delivered)</option>
                            <option value="cancelled" {{ $order->status === 'cancelled' ? 'selected' : '' }}>ملغي (Cancelled)</option>
                        </select>
                    </div>

                    <div>
                        <label for="payment_status" class="block text-xs font-bold text-slate-700 mb-1">حالة الدفع:</label>
                        <select name="payment_status" id="payment_status" class="w-full text-xs p-2 bg-slate-50 border border-slate-200 rounded-xl font-bold">
                            <option value="unpaid" {{ $order->payment_status === 'unpaid' ? 'selected' : '' }}>غير مدفوع (عند الاستلام)</option>
                            <option value="paid" {{ $order->payment_status === 'paid' ? 'selected' : '' }}>تم سداد المبلغ بالكامل</option>
                        </select>
                    </div>

                    <button type="submit" class="w-full erp-btn erp-btn-primary py-2 text-xs justify-center">
                        <x-icon name="check" size="14" />
                        <span>حفظ التعديلات</span>
                    </button>
                </form>
            </div>

            <!-- Convert to Sales Invoice Card -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs space-y-4">
                <h3 class="font-bold text-slate-800 text-sm pb-2 border-b border-slate-100 flex items-center gap-2">
                    <x-icon name="file-text" size="16" class="text-indigo-600" />
                    <span>فاتورة المبيعات الرسمية (ERP)</span>
                </h3>

                @if($order->salesInvoice)
                    <div class="bg-indigo-50 border border-indigo-200 rounded-xl p-3 text-xs space-y-2">
                        <div class="flex items-center gap-1.5 text-indigo-800 font-bold">
                            <x-icon name="check-circle" size="16" class="text-indigo-600" />
                            <span>تم تحويل الطلب إلى فاتورة</span>
                        </div>
                        <div class="text-slate-600">
                            رقم الفاتورة: <strong class="font-mono text-slate-900">{{ $order->salesInvoice->invoice_number }}</strong>
                        </div>
                        <div class="text-slate-500">
                            تاريخ الإصدار: {{ $order->salesInvoice->invoice_date }}
                        </div>
                    </div>
                @else
                    <form action="{{ route('ecommerce.orders.convert-invoice', $order) }}" method="POST" class="space-y-3">
                        @csrf
                        <p class="text-xs text-slate-500 leading-relaxed">
                            تحويل هذا الطلب الإلكتروني تلقائياً إلى فاتورة مبيعات ERP مع خصم الأصناف من المستودع المختار.
                        </p>

                        <div>
                            <label for="warehouse_id" class="block text-xs font-bold text-slate-700 mb-1">
                                مستودع الصرف:
                            </label>
                            <select name="warehouse_id" id="warehouse_id" required class="w-full text-xs p-2 bg-slate-50 border border-slate-200 rounded-xl font-bold">
                                @foreach($warehouses as $wh)
                                    <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <button type="submit" 
                                onclick="return confirm('هل أنت متأكد من تحويل هذا الطلب إلى فاتورة مبيعات وخصم المخزون؟')"
                                class="w-full erp-btn bg-emerald-600 hover:bg-emerald-700 text-white py-2 text-xs justify-center font-bold">
                            <x-icon name="file-plus" size="14" />
                            <span>إصدار فاتورة مبيعات ERP</span>
                        </button>
                    </form>
                @endif
            </div>

        </div>

    </div>

</div>
@endsection
