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
            <span class="erp-badge erp-badge-neutral font-numeric">
                <x-icon name="shopping-bag" size="14" />
                <span>إجمالي الطلبات: {{ $counts['all'] }}</span>
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
<div data-page="ecommerce-orders">

    @if(session('success'))
        <div class="erp-alert erp-alert-success mb-4" role="alert">
            <x-icon name="check-circle" size="18" />
            <div>{{ session('success') }}</div>
        </div>
    @endif

    <!-- Status Tabs Bar -->
    <div class="erp-card erp-tab-nav-card mb-4">
        <nav class="erp-tab-bar" aria-label="أقسام وحالات طلبات المتجر">
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
                   class="erp-tab-link {{ $status === $key ? 'active' : '' }}">
                    <span>{{ $tab['label'] }}</span>
                    <span class="erp-badge erp-badge-neutral mr-1 px-1.5 py-0.5 text-xs font-numeric">
                        {{ $tab['count'] }}
                    </span>
                </a>
            @endforeach
        </nav>
    </div>

    <!-- Search & Filter Card -->
    <div class="erp-card p-4 mb-4">
        <form action="{{ route('ecommerce.orders.index') }}" method="GET" style="display: flex; flex-wrap: wrap; align-items: center; gap: 10px;">
            <input type="hidden" name="status" value="{{ $status }}">
            
            <div style="flex: 1; min-width: 260px; position: relative;">
                <input type="text" 
                       name="search" 
                       value="{{ $search }}" 
                       placeholder="ابحث برقم الطلب، اسم العميل، الهاتف، أو المدينة..." 
                       class="erp-input"
                       style="padding-right: 36px;">
                <div style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); pointer-events: none;">
                    <x-icon name="search" size="15" />
                </div>
            </div>

            <button type="submit" class="erp-btn erp-btn-primary">
                <x-icon name="search" size="14" />
                <span>بحث</span>
            </button>

            @if($search)
                <a href="{{ route('ecommerce.orders.index', ['status' => $status]) }}" class="erp-btn erp-btn-secondary" title="إلغاء البحث">
                    <x-icon name="x" size="14" />
                    <span>إلغاء البحث</span>
                </a>
            @endif
        </form>
    </div>

    <!-- Orders Table View -->
    <div class="erp-card">
        @if($orders->isEmpty())
            <div style="padding: 48px 16px; text-align: center; color: var(--text-muted);">
                <x-icon name="shopping-bag" size="44" style="margin: 0 auto 12px auto; opacity: 0.4;" />
                <h4 style="font-size: 15px; font-weight: 700; color: var(--text-main); margin-bottom: 4px;">لا توجد طلبات في هذا القسم</h4>
                <p style="font-size: 12px; color: var(--text-muted); margin: 0;">لم يتم العثور على أي طلبات مطابقة للمعايير المحددة.</p>
            </div>
        @else
            <div class="erp-table-responsive">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th>رقم الطلب</th>
                            <th>تاريخ وتوقيت الطلب</th>
                            <th>اسم العميل</th>
                            <th>الهاتف والمدينة</th>
                            <th>عدد الأصناف</th>
                            <th>المبلغ الإجمالي</th>
                            <th>طريقة وحالة الدفع</th>
                            <th>حالة الطلب</th>
                            <th>فاتورة الـ ERP</th>
                            <th style="text-align: center;">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($orders as $order)
                            @php $badge = $order->status_badge; @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('ecommerce.orders.show', $order) }}" class="font-numeric font-bold" style="color: var(--primary); text-decoration: none;">
                                        {{ $order->order_number }}
                                    </a>
                                </td>
                                <td class="font-numeric text-xs" style="color: var(--text-muted);">
                                    {{ $order->created_at->format('Y-m-d H:i') }}
                                </td>
                                <td>
                                    <strong style="color: var(--text-main);">{{ $order->customer_name }}</strong>
                                </td>
                                <td>
                                    <div dir="ltr" class="font-numeric text-xs font-bold">{{ $order->customer_phone }}</div>
                                    <div style="font-size: 11px; color: var(--text-muted);">{{ $order->customer_city ?? 'غير محدد' }}</div>
                                </td>
                                <td>
                                    <span class="erp-badge erp-badge-neutral text-xs font-numeric">
                                        {{ $order->items->count() }} صنف
                                    </span>
                                </td>
                                <td class="font-numeric font-bold" style="color: var(--text-main);">
                                    {{ number_format($order->total, 2) }} ج.م
                                </td>
                                <td>
                                    @if($order->payment_status === 'paid')
                                        <span class="erp-badge erp-badge-success text-xs">مدفوع</span>
                                    @else
                                        <span class="erp-badge erp-badge-warning text-xs">عند الاستلام</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="erp-badge text-xs" style="background-color: var(--bg-surface-alt); border: 1px solid var(--border-color);">
                                        <x-icon :name="$badge['icon']" size="12" />
                                        <span>{{ $badge['label'] }}</span>
                                    </span>
                                </td>
                                <td>
                                    @if($order->salesInvoice)
                                        <span class="font-numeric font-bold" style="color: var(--primary);">
                                            {{ $order->salesInvoice->invoice_number }}
                                        </span>
                                    @else
                                        <span style="color: var(--text-muted); font-size: 11px;">لم تُصدر بعد</span>
                                    @endif
                                </td>
                                <td style="text-align: center;">
                                    <a href="{{ route('ecommerce.orders.show', $order) }}" class="erp-btn erp-btn-secondary" style="padding: 4px 10px; font-size: 12px;">
                                        <x-icon name="eye" size="13" />
                                        <span>عرض</span>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div style="padding: 12px 16px; border-top: 1px solid var(--border-light);">
                {{ $orders->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
