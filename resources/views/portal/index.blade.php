@extends('layouts.portal', ['title' => 'لوحة المندوب'])

@section('content')
<div class="space-y-4 max-w-5xl mx-auto">

    <!-- 1. Hero Sales Summary Banner (Faithful to Reference Image 1 Right Phone) -->
    <div class="rounded-3xl text-white p-5 sm:p-7 shadow-lg shadow-[#714B67]/20 relative overflow-hidden"
         style="background: linear-gradient(135deg, #714B67 0%, #58334e 55%, #3d1d33 100%);">
        
        <!-- Ambient Decorative Glow -->
        <div class="absolute -left-12 -bottom-12 w-48 h-48 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
        <div class="absolute -right-12 -top-12 w-48 h-48 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>

        <div class="relative z-10 space-y-3">
            <!-- Top Row: User Avatar & Live Status Pill -->
            <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="w-10 h-10 rounded-full bg-white/20 border-2 border-white/40 flex items-center justify-center font-black text-white text-sm shadow-xs shrink-0">
                        {{ mb_substr($user->name, 0, 1) }}
                    </div>
                    <div class="min-w-0">
                        <div class="text-[11px] text-purple-200 font-medium">مرحباً بك مجدداً 👋</div>
                        <div class="text-sm sm:text-base font-bold text-white truncate">{{ $user->name }}</div>
                    </div>
                </div>

                @php $hasActiveSession = $user->hasActiveAttendanceSession(); @endphp
                @if($hasActiveSession)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-emerald-500/25 border border-emerald-400/40 text-emerald-200 backdrop-blur-md shrink-0">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-400"></span>
                        </span>
                        <span>جلسة نشطة GPS</span>
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-amber-500/25 border border-amber-400/40 text-amber-200 backdrop-blur-md shrink-0">
                        <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                        <span>الدوام مغلق</span>
                    </span>
                @endif
            </div>

            <!-- Big Metric: Net Sales Today (Like Image 1: ملخص المبيعات) -->
            <div class="pt-2 text-center sm:text-right">
                <div class="text-xs font-semibold text-purple-200 tracking-wide">ملخص المبيعات — مبيعات اليوم</div>
                <div class="font-numeric text-3xl sm:text-4xl lg:text-5xl font-black text-white tracking-tight leading-tight my-1">
                    {{ number_format($todaySalesTotal, 2) }} <span class="text-base sm:text-xl font-normal text-purple-200">ج.م</span>
                </div>
                <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2 pt-1 text-xs">
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg bg-white/15 text-white font-semibold backdrop-blur-xs">
                        <x-icon name="shopping-bag" size="13" class="text-purple-200" />
                        <span>{{ $todaySalesCount }} فواتير منجزة اليوم</span>
                    </span>
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg bg-white/15 text-white font-semibold backdrop-blur-xs">
                        <x-icon name="percent" size="13" class="text-emerald-300" strokeWidth="2.5" />
                        <span>نسبة العمولة: {{ (float) ($user->commission_rate ?? 0) }}{{ $user->commission_type === 'percentage' ? '%' : ' ج.م' }}</span>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Overlapping Floating Summary Card (Faithful to Reference Image 1 Right Phone) -->
    <div class="-mt-6 sm:-mt-8 relative z-20 mx-2 sm:mx-4 bg-white rounded-3xl p-4 sm:p-5 shadow-[0_10px_35px_rgba(0,0,0,0.06)] border border-slate-100">
        <div class="divide-y divide-slate-100">
            <!-- Row 1: Today's Sales -->
            <div class="flex items-center justify-between py-3">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-sm shrink-0">
                        ↑
                    </div>
                    <div>
                        <div class="text-xs font-bold text-slate-800">اليومية (مبيعات اليوم)</div>
                        <div class="text-[11px] text-slate-400 font-numeric">{{ $todaySalesCount }} فاتورة مسجلة</div>
                    </div>
                </div>
                <div class="text-left font-numeric font-black text-slate-900 text-sm sm:text-base">
                    {{ number_format($todaySalesTotal, 2) }} <span class="text-xs font-normal text-slate-500">ج.م</span>
                </div>
            </div>

            <!-- Row 2: Month's Commission -->
            <div class="flex items-center justify-between py-3">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-purple-50 text-[#714B67] flex items-center justify-center shrink-0">
                        <x-icon name="wallet" size="16" strokeWidth="2.4" />
                    </div>
                    <div>
                        <div class="text-xs font-bold text-slate-800">عمولات الشهر (المكتسبة)</div>
                        <div class="text-[11px] text-emerald-600 font-semibold flex items-center gap-1">
                            <x-icon name="check-circle" size="11" strokeWidth="2.5" />
                            <span>أرباحك المعتمدة</span>
                        </div>
                    </div>
                </div>
                <div class="text-left font-numeric font-black text-emerald-600 text-sm sm:text-base">
                    +{{ number_format($monthCommissions, 2) }} <span class="text-xs font-normal text-emerald-700">ج.م</span>
                </div>
            </div>

            <!-- Row 3: Pending Commission -->
            <div class="flex items-center justify-between py-3">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                        <x-icon name="clock" size="16" strokeWidth="2.4" />
                    </div>
                    <div>
                        <div class="text-xs font-bold text-slate-800">عمولات معلقة</div>
                        <div class="text-[11px] text-amber-600 font-semibold">قيد المراجعة والصرف</div>
                    </div>
                </div>
                <div class="text-left font-numeric font-bold text-amber-700 text-sm sm:text-base">
                    {{ number_format($pendingCommissions, 2) }} <span class="text-xs font-normal text-slate-500">ج.م</span>
                </div>
            </div>

            <!-- Row 4: Connected Customers -->
            <div class="flex items-center justify-between py-3">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                        <x-icon name="users" size="16" strokeWidth="2.4" />
                    </div>
                    <div>
                        <div class="text-xs font-bold text-slate-800">العملاء المربوطين</div>
                        <div class="text-[11px] text-slate-400">في خط سيرك الميداني</div>
                    </div>
                </div>
                <div class="text-left font-numeric font-bold text-blue-700 text-sm sm:text-base">
                    {{ $myCustomersCount }} <span class="text-xs font-normal text-slate-500">عميل</span>
                </div>
            </div>
        </div>

        <!-- Action Button (Faithful to Image 1 Left Phone "اتخاذ إجراء") -->
        <div class="pt-3 border-t border-slate-100">
            <a href="{{ $hasActiveSession ? route('portal.orders.create') : route('portal.attendance.index') }}" 
               class="w-full py-3.5 px-4 rounded-2xl bg-gradient-to-r from-[#714B67] to-[#8d5e82] hover:opacity-95 active:scale-98 text-white font-black text-xs sm:text-sm flex items-center justify-center gap-2 shadow-md shadow-[#714B67]/25 transition-all">
                <x-icon name="plus" size="18" strokeWidth="2.8" class="text-white" />
                <span>{{ $hasActiveSession ? 'اتخاذ إجراء: إنشاء فاتورة بيع جديدة' : 'فتح جلسة العمل بالـ GPS للبدء' }}</span>
            </a>
        </div>
    </div>

    <!-- 3. GPS Attendance Status Banner (Clean White Card) -->
    <div class="bg-white rounded-3xl p-4 sm:p-5 shadow-[0_4px_20px_rgba(0,0,0,0.04)] border border-slate-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
        <div class="flex items-center gap-3 min-w-0 flex-1">
            <div class="w-12 h-12 rounded-2xl {{ $hasActiveSession ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600' }} flex items-center justify-center shrink-0">
                <x-icon name="{{ $hasActiveSession ? 'map-pin' : 'clock' }}" size="24" strokeWidth="2.3" />
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2 flex-wrap mb-0.5">
                    <span class="text-xs font-bold text-slate-900">
                        {{ $hasActiveSession ? 'جلسة العمل الميداني نشطة وموثقة (GPS)' : 'جلسة العمل غير مفتوحة — الحضور إلزامي' }}
                    </span>
                    @if($hasActiveSession && $todayAttendance)
                        <span class="text-[11px] font-semibold text-slate-400 font-numeric">بدأت {{ $todayAttendance->check_in_time?->format('h:i A') }}</span>
                    @endif
                </div>
                <p class="text-[11px] text-slate-500 leading-relaxed truncate">
                    {{ $hasActiveSession ? 'موقعك موثق بالأقمار الصناعية — يمكنك إصدار الفواتير وتسجيل الزيارات والعملاء.' : 'قم بتسجيل حضورك الجغرافي لبدء يوم العمل وإضافة طلبات وعملاء جدد.' }}
                </p>
            </div>
        </div>
        <a href="{{ route('portal.attendance.index') }}" class="w-full sm:w-auto px-4 py-2.5 rounded-xl text-xs font-bold {{ $hasActiveSession ? 'bg-slate-100 hover:bg-slate-200 text-slate-800' : 'bg-amber-600 hover:bg-amber-700 text-white shadow-xs' }} flex items-center justify-center gap-1.5 transition-colors shrink-0">
            <x-icon name="clock" size="14" strokeWidth="2.2" />
            <span>{{ $hasActiveSession ? 'عرض سجل الدوام' : 'تسجيل الحضور وفتح الجلسة' }}</span>
        </a>
    </div>

    <!-- 4. Quick Actions (PWA Tiles with Soft Pastel Squircle Icons like Image 1 & 2) -->
    <div>
        <div class="text-xs font-bold text-slate-500 mb-2.5 px-1">الإجراءات السريعة للمندوب</div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            <!-- 1. New Order -->
            <a href="{{ $hasActiveSession ? route('portal.orders.create') : route('portal.attendance.index') }}" 
               class="bg-white rounded-2xl p-4 border border-slate-100 shadow-[0_2px_12px_rgba(0,0,0,0.03)] hover:shadow-md hover:border-[#714B67]/30 transition-all flex flex-col items-center justify-center text-center group relative">
                @if(! $hasActiveSession)
                    <span class="absolute top-2.5 left-2.5 px-1.5 py-0.5 rounded-md bg-amber-100 text-amber-800 text-[9px] font-bold flex items-center gap-0.5">
                        <x-icon name="lock" size="10" strokeWidth="2.5" />
                    </span>
                @endif
                <div class="w-12 h-12 rounded-2xl bg-[#714B67] text-white flex items-center justify-center shadow-md shadow-[#714B67]/25 group-hover:scale-108 active:scale-95 transition-transform">
                    <x-icon name="plus" size="24" strokeWidth="2.6" class="text-white" />
                </div>
                <span class="text-xs sm:text-sm font-bold text-slate-900 mt-2 truncate max-w-full">طلب جديد</span>
                <span class="text-[11px] text-slate-400 mt-0.5 truncate max-w-full">{{ $hasActiveSession ? 'تسجيل فاتورة' : 'يتطلب حضور' }}</span>
            </a>

            <!-- 2. Field Visit -->
            <a href="{{ $hasActiveSession ? route('portal.visits.create') : route('portal.attendance.index') }}" 
               class="bg-white rounded-2xl p-4 border border-slate-100 shadow-[0_2px_12px_rgba(0,0,0,0.03)] hover:shadow-md hover:border-rose-300 transition-all flex flex-col items-center justify-center text-center group relative">
                @if(! $hasActiveSession)
                    <span class="absolute top-2.5 left-2.5 px-1.5 py-0.5 rounded-md bg-amber-100 text-amber-800 text-[9px] font-bold flex items-center gap-0.5">
                        <x-icon name="lock" size="10" strokeWidth="2.5" />
                    </span>
                @endif
                <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 border border-rose-100 flex items-center justify-center group-hover:scale-108 active:scale-95 transition-transform">
                    <x-icon name="map-pin" size="23" strokeWidth="2.4" />
                </div>
                <span class="text-xs sm:text-sm font-bold text-slate-900 mt-2 truncate max-w-full">زيارة ميدانية</span>
                <span class="text-[11px] text-slate-400 mt-0.5 truncate max-w-full">{{ $hasActiveSession ? 'توثيق GPS' : 'يتطلب حضور' }}</span>
            </a>

            <!-- 3. Attendance GPS -->
            <a href="{{ route('portal.attendance.index') }}" 
               class="bg-white rounded-2xl p-4 border border-slate-100 shadow-[0_2px_12px_rgba(0,0,0,0.03)] hover:shadow-md hover:border-sky-300 transition-all flex flex-col items-center justify-center text-center group">
                <div class="w-12 h-12 rounded-2xl bg-sky-50 text-sky-600 border border-sky-100 flex items-center justify-center group-hover:scale-108 active:scale-95 transition-transform">
                    <x-icon name="clock" size="23" strokeWidth="2.4" />
                </div>
                <span class="text-xs sm:text-sm font-bold text-slate-900 mt-2 truncate max-w-full">دوام العمل (GPS)</span>
                <span class="text-[11px] text-slate-400 mt-0.5 truncate max-w-full">{{ $hasActiveSession ? 'دوام نشط' : 'حضور وانصراف' }}</span>
            </a>

            <!-- 4. Customer Register -->
            <a href="{{ $hasActiveSession ? route('portal.customers.create') : route('portal.attendance.index') }}" 
               class="bg-white rounded-2xl p-4 border border-slate-100 shadow-[0_2px_12px_rgba(0,0,0,0.03)] hover:shadow-md hover:border-emerald-300 transition-all flex flex-col items-center justify-center text-center group relative">
                @if(! $hasActiveSession)
                    <span class="absolute top-2.5 left-2.5 px-1.5 py-0.5 rounded-md bg-amber-100 text-amber-800 text-[9px] font-bold flex items-center gap-0.5">
                        <x-icon name="lock" size="10" strokeWidth="2.5" />
                    </span>
                @endif
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center group-hover:scale-108 active:scale-95 transition-transform">
                    <x-icon name="user-plus" size="23" strokeWidth="2.4" />
                </div>
                <span class="text-xs sm:text-sm font-bold text-slate-900 mt-2 truncate max-w-full">تسجيل عميل</span>
                <span class="text-[11px] text-slate-400 mt-0.5 truncate max-w-full">{{ $hasActiveSession ? 'إضافة عميل' : 'بيانات المحل' }}</span>
            </a>

            <!-- 5. Catalog -->
            <a href="{{ route('portal.catalog') }}" 
               class="bg-white rounded-2xl p-4 border border-slate-100 shadow-[0_2px_12px_rgba(0,0,0,0.03)] hover:shadow-md hover:border-amber-300 transition-all flex flex-col items-center justify-center text-center group">
                <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center group-hover:scale-108 active:scale-95 transition-transform">
                    <x-icon name="package" size="23" strokeWidth="2.4" />
                </div>
                <span class="text-xs sm:text-sm font-bold text-slate-900 mt-2 truncate max-w-full">كتالوج الأصناف</span>
                <span class="text-[11px] text-slate-400 mt-0.5 truncate max-w-full">الأسعار والمخزون</span>
            </a>

            <!-- 6. Orders History -->
            <a href="{{ route('portal.orders.index') }}" 
               class="bg-white rounded-2xl p-4 border border-slate-100 shadow-[0_2px_12px_rgba(0,0,0,0.03)] hover:shadow-md hover:border-indigo-300 transition-all flex flex-col items-center justify-center text-center group">
                <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center group-hover:scale-108 active:scale-95 transition-transform">
                    <x-icon name="receipt" size="23" strokeWidth="2.4" />
                </div>
                <span class="text-xs sm:text-sm font-bold text-slate-900 mt-2 truncate max-w-full">سجل الطلبيات</span>
                <span class="text-[11px] text-slate-400 mt-0.5 truncate max-w-full">متابعة الفواتير</span>
            </a>
        </div>
    </div>

    <!-- 5. Recent Activity: Orders & Customers (Faithful to Image 2 Product List Style) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 items-start pt-2">
        <!-- Recent Orders (Floating Cards like Image 2) -->
        <div>
            <div class="flex items-center justify-between mb-2.5 px-1">
                <h2 class="text-xs sm:text-sm font-bold text-slate-900 flex items-center gap-1.5">
                    <x-icon name="shopping-bag" size="16" class="text-[#714B67]" strokeWidth="2.4" />
                    <span>أحدث الطلبات والفواتير</span>
                </h2>
                <a href="{{ route('portal.orders.index') }}" class="text-xs font-bold text-[#714B67] hover:underline flex items-center gap-1">
                    <span>عرض الكل</span>
                    <x-icon name="arrow-left" size="12" strokeWidth="2.4" />
                </a>
            </div>

            <div class="space-y-2">
                @forelse($recentOrders as $order)
                    <a href="{{ route('portal.orders.show', $order) }}" class="p-3.5 bg-white rounded-2xl border border-slate-100 shadow-[0_2px_8px_rgba(0,0,0,0.03)] hover:shadow-md hover:border-[#714B67]/30 transition-all flex items-center justify-between block">
                        <div class="flex items-center gap-3 min-w-0 flex-1">
                            <div class="w-11 h-11 rounded-xl bg-purple-50 text-[#714B67] flex items-center justify-center font-bold text-sm shrink-0">
                                <x-icon name="file-text" size="18" strokeWidth="2.2" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 mb-0.5 flex-wrap">
                                    <span class="text-xs sm:text-sm font-bold text-slate-900 truncate max-w-[170px] sm:max-w-xs">{{ $order->customer?->name ?? 'عميل نقدي' }}</span>
                                    @if($order->status === 'posted')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-bold bg-emerald-100 text-emerald-800 shrink-0">مرحّلة</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-bold bg-amber-100 text-amber-800 shrink-0">مسودة</span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2 text-[11px] text-slate-400 font-numeric">
                                    <span>#{{ $order->invoice_number }}</span>
                                    <span>·</span>
                                    <span>{{ $order->invoice_date->format('Y/m/d') }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="text-left shrink-0 mr-2">
                            <div class="text-xs sm:text-sm font-black text-slate-900 font-numeric">
                                {{ number_format($order->total_amount, 2) }} ج.م
                            </div>
                            @if($order->commission)
                                <div class="text-[10px] text-emerald-600 font-bold font-numeric">
                                    عمولة: +{{ number_format($order->commission->commission_amount, 2) }} ج.م
                                </div>
                            @endif
                        </div>
                    </a>
                @empty
                    <div class="p-8 bg-white rounded-2xl border border-dashed border-slate-200 text-center text-slate-400 text-xs">
                        لم تقم بإنشاء أي طلبات حتى الآن. اضغط على «طلب جديد» للبدء.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Recent Customers (Floating Cards like Image 2) -->
        <div>
            <div class="flex items-center justify-between mb-2.5 px-1">
                <h2 class="text-xs sm:text-sm font-bold text-slate-900 flex items-center gap-1.5">
                    <x-icon name="user-check" size="16" class="text-emerald-600" strokeWidth="2.4" />
                    <span>أحدث العملاء المسجلين</span>
                </h2>
                <a href="{{ route('portal.customers.index') }}" class="text-xs font-bold text-[#714B67] hover:underline flex items-center gap-1">
                    <span>عرض الكل</span>
                    <x-icon name="arrow-left" size="12" strokeWidth="2.4" />
                </a>
            </div>

            <div class="space-y-2">
                @forelse($recentCustomers as $c)
                    <div class="p-3.5 bg-white rounded-2xl border border-slate-100 shadow-[0_2px_8px_rgba(0,0,0,0.03)] flex items-center justify-between gap-2">
                        <div class="flex items-center gap-3 min-w-0 flex-1">
                            <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-sm shrink-0">
                                <x-icon name="user" size="18" strokeWidth="2.2" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="text-xs sm:text-sm font-bold text-slate-900 truncate">{{ $c->name }}</div>
                                <div class="text-[11px] text-slate-400 font-numeric flex items-center gap-2 mt-0.5 truncate">
                                    <span>{{ $c->phone }}</span>
                                    @if($c->city)
                                        <span>· {{ $c->city }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-1.5 shrink-0">
                            @if($c->phone)
                                <a href="tel:{{ $c->phone }}" class="p-2 rounded-xl border border-slate-200 text-slate-600 hover:text-emerald-600 hover:border-emerald-300 hover:bg-emerald-50 transition-colors" title="اتصال">
                                    <x-icon name="phone" size="14" strokeWidth="2.2" />
                                </a>
                            @endif
                            <a href="{{ route('portal.orders.create', ['customer_id' => $c->id]) }}" class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-[#714B67] hover:text-white text-slate-700 text-xs font-bold transition-all flex items-center gap-1">
                                <x-icon name="plus" size="12" strokeWidth="2.5" />
                                <span>طلب</span>
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="p-8 bg-white rounded-2xl border border-dashed border-slate-200 text-center text-slate-400 text-xs">
                        لم تسجل عملاء حتى الآن. اضغط على «تسجيل عميل» لإضافة أول عميل.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

</div>
@endsection
