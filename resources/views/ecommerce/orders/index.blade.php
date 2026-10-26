@extends('layouts.app')

@section('module_name', 'طلبات المتجر الإلكتروني')

@section('control_panel')
    <x-control-panel title="طلبات المتجر الإلكتروني (E-Commerce)" :breadcrumbs="['المتجر الإلكتروني' => route('ecommerce.orders.index'), 'الطلبات' => route('ecommerce.orders.index')]">
        <x-slot:actions>
            <a href="{{ route('shop.catalog') }}" target="_blank" class="erp-btn erp-btn-secondary">
                <x-icon name="external-link" size="16" />
                <span>زيارة المتجر للعملاء</span>
            </a>
            <a href="{{ route('ecommerce.settings') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="settings" size="16" />
                <span>إعدادات المتجر</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-neutral font-numeric px-2.5 py-1 text-xs">
                <span>إجمالي الطلبات:</span>
                <strong>{{ $counts['all'] }}</strong>
            </span>
        </x-slot:extra>
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

    <!-- Status Tabs -->
    <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-thin">
        @php
            $tabList = [
                'all' => ['label' => 'كافة الطلبات', 'count' => $counts['all']],
                'pending' => ['label' => 'قيد الانتظار', 'count' => $counts['pending']],
                'confirmed' => ['label' => 'تم التأكيد', 'count' => $counts['confirmed']],
                'processing' => ['label' => 'جاري التجهيز', 'count' => $counts['processing']],
                'shipped' => ['label' => 'تم الشحن', 'count' => $counts['shipped']],
                'delivered' => ['label' => 'تم التوصيل', 'count' => $counts['delivered']],
                'cancelled' => ['label' => 'ملغي', 'count' => $counts['cancelled']],
            ];
        @endphp

        @foreach($tabList as $key => $tab)
            <a href="{{ route('ecommerce.orders.index', ['status' => $key, 'search' => $search]) }}" 
               class="px-4 py-2 rounded-xl text-xs font-bold shrink-0 transition-all flex items-center gap-2 {{ $status === $key ? 'bg-indigo-600 text-white shadow-xs' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200' }}">
                <span>{{ $tab['label'] }}</span>
                <span class="px-1.5 py-0.5 rounded-full text-[11px] {{ $status === $key ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' }}">
                    {{ $tab['count'] }}
                </span>
            </a>
        @endforeach
    </div>

    <!-- Search Form -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs">
        <form action="{{ route('ecommerce.orders.index') }}" method="GET" class="flex flex-col sm:flex-row gap-3">
            <input type="hidden" name="status" value="{{ $status }}">
            <div class="relative flex-1">
                <input type="text" 
                       name="search" 
                       value="{{ $search }}" 
                       placeholder="ابحث برقم الطلب، اسم العميل، الهاتف، أو المدينة..." 
                       class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:border-indigo-500 outline-none">
                <div class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                    <x-icon name="search" size="16" />
                </div>
            </div>
            <button type="submit" class="erp-btn erp-btn-primary shrink-0">
                <x-icon name="search" size="16" />
                <span>بحث</span>
            </button>
            @if($search)
                <a href="{{ route('ecommerce.orders.index', ['status' => $status]) }}" class="erp-btn erp-btn-secondary shrink-0">
                    <span>إلغاء البحث</span>
                </a>
            @endif
        </form>
    </div>

    <!-- Orders Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
        @if($orders->isEmpty())
            <div class="p-12 text-center text-slate-400">
                <x-icon name="shopping-cart" size="48" class="mx-auto mb-3 text-slate-300" />
                <h4 class="font-bold text-slate-700 text-base mb-1">لا توجد طلبات متجر إلكتروني</h4>
                <p class="text-xs text-slate-500">لم يتم العثور على أي طلبات في هذه الحالة حالياً.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-right text-xs">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold">
                        <tr>
                            <th class="p-3.5">رقم الطلب</th>
                            <th class="p-3.5">تاريخ الطلب</th>
                            <th class="p-3.5">العميل</th>
                            <th class="p-3.5">الهاتف والمدينة</th>
                            <th class="p-3.5">الأصناف</th>
                            <th class="p-3.5">الإجمالي</th>
                            <th class="p-3.5">حالة الدفع</th>
                            <th class="p-3.5">حالة الطلب</th>
                            <th class="p-3.5">فاتورة المبيعات</th>
                            <th class="p-3.5 text-center">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($orders as $order)
                            @php
                                $badge = $order->status_badge;
                            @endphp
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="p-3.5 font-bold font-mono text-indigo-700">
                                    <a href="{{ route('ecommerce.orders.show', $order) }}" class="hover:underline">
                                        {{ $order->order_number }}
                                    </a>
                                </td>
                                <td class="p-3.5 text-slate-500 whitespace-nowrap">
                                    {{ $order->created_at->format('Y-m-d H:i') }}
                                </td>
                                <td class="p-3.5 font-bold text-slate-800">
                                    {{ $order->customer_name }}
                                </td>
                                <td class="p-3.5 text-slate-600">
                                    <div dir="ltr" class="text-right font-mono">{{ $order->customer_phone }}</div>
                                    <div class="text-[11px] text-slate-400">{{ $order->customer_city ?? 'بدون تحديد' }}</div>
                                </td>
                                <td class="p-3.5">
                                    <span class="inline-flex items-center gap-1 font-bold text-slate-700">
                                        {{ $order->items->count() }} صنف
                                    </span>
                                </td>
                                <td class="p-3.5 font-bold text-slate-900 font-numeric whitespace-nowrap">
                                    {{ number_format($order->total, 2) }} ج.م
                                </td>
                                <td class="p-3.5 whitespace-nowrap">
                                    @if($order->payment_status === 'paid')
                                        <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                            مدفوع
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                            عند الاستلام
                                        </span>
                                    @endif
                                </td>
                                <td class="p-3.5 whitespace-nowrap">
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold border inline-flex items-center gap-1.5 {{ $badge['bg'] }}">
                                        <x-icon :name="$badge['icon']" size="12" />
                                        <span>{{ $badge['label'] }}</span>
                                    </span>
                                </td>
                                <td class="p-3.5 whitespace-nowrap">
                                    @if($order->salesInvoice)
                                        <span class="text-indigo-600 font-bold font-mono">
                                            {{ $order->salesInvoice->invoice_number }}
                                        </span>
                                    @else
                                        <span class="text-slate-400">لم تُصدر بعد</span>
                                    @endif
                                </td>
                                <td class="p-3.5 text-center">
                                    <a href="{{ route('ecommerce.orders.show', $order) }}" class="erp-btn erp-btn-secondary py-1 px-2.5 text-xs">
                                        <x-icon name="eye" size="14" />
                                        <span>عرض</span>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-slate-100">
                {{ $orders->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
