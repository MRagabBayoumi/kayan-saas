@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="المصروفات التشغيلية والعمومية" :breadcrumbs="['المالية والحسابات' => route('treasuries.index'), 'المصروفات' => route('expenses.index')]">
        <x-slot:actions>
            <button type="button" onclick="document.getElementById('newExpenseModal').style.display='flex'" class="erp-btn erp-btn-primary">
                <x-icon name="plus" size="16" />
                <span>تسجيل إذن صرف مصروف</span>
            </button>
            <button type="button" onclick="document.getElementById('newCategoryModal').style.display='flex'" class="erp-btn erp-btn-secondary">
                <x-icon name="tag" size="16" />
                <span>إضافة بند مصروف</span>
            </button>
            <a href="{{ route('treasuries.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="vault" size="16" />
                <span>الخزائن والبنوك</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-neutral font-numeric px-3 py-1 text-xs">
                <span>مصروفات الشهر الحالي:</span>
                <strong class="text-[var(--danger)] mr-1">{{ number_format($metrics['month_amount'], 2) }} ج.م</strong>
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
    <div data-page="expenses">
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

        <!-- KPI Summary Cards -->
        <div class="finance-kpi-grid">
            <div class="finance-kpi-card">
                <div class="finance-kpi-icon danger">
                    <x-icon name="trending-down" size="22" />
                </div>
                <div class="finance-kpi-info">
                    <div class="finance-kpi-label">إجمالي المصروفات (التصفية)</div>
                    <div class="finance-kpi-value font-numeric text-[var(--danger)]">
                        {{ number_format($metrics['total_amount'], 2) }} <span class="text-xs font-normal text-[var(--text-muted)]">ج.م</span>
                    </div>
                    <div class="finance-kpi-sub">{{ $metrics['count'] }} إذن صرف مسجل</div>
                </div>
            </div>

            <div class="finance-kpi-card">
                <div class="finance-kpi-icon warning">
                    <x-icon name="calendar" size="22" />
                </div>
                <div class="finance-kpi-info">
                    <div class="finance-kpi-label">مصروفات اليوم</div>
                    <div class="finance-kpi-value font-numeric text-[var(--warning-text)]">
                        {{ number_format($metrics['today_amount'], 2) }} <span class="text-xs font-normal text-[var(--text-muted)]">ج.م</span>
                    </div>
                    <div class="finance-kpi-sub font-numeric">{{ date('Y-m-d') }}</div>
                </div>
            </div>

            <div class="finance-kpi-card">
                <div class="finance-kpi-icon primary">
                    <x-icon name="credit-card" size="22" />
                </div>
                <div class="finance-kpi-info">
                    <div class="finance-kpi-label">مصروفات الشهر الجاري</div>
                    <div class="finance-kpi-value font-numeric">
                        {{ number_format($metrics['month_amount'], 2) }} <span class="text-xs font-normal text-[var(--text-muted)]">ج.م</span>
                    </div>
                    <div class="finance-kpi-sub">من بداية الشهر حتى الآن</div>
                </div>
            </div>

            <div class="finance-kpi-card">
                <div class="finance-kpi-icon success">
                    <x-icon name="layers" size="22" />
                </div>
                <div class="finance-kpi-info">
                    <div class="finance-kpi-label">بنود وتصنيفات الصرف</div>
                    <div class="finance-kpi-value font-numeric">
                        {{ $categories->count() }}
                    </div>
                    <div class="finance-kpi-sub">بند تبويب مصروفات نشط</div>
                </div>
            </div>
        </div>

        <!-- Categories Breakdown Chips -->
        @if($categoryBreakdown->isNotEmpty())
            <div class="erp-card" style="margin-bottom: 20px;">
                <div class="erp-card-header" style="padding: 10px 16px;">
                    <div class="erp-card-title" style="font-size: 13px;">
                        <x-icon name="pie-chart" size="16" />
                        <span>أعلى بنود وتصنيفات الصرف والتكلفة</span>
                    </div>
                </div>
                <div class="erp-card-body" style="padding: 12px 16px;">
                    <div class="category-chips-bar" style="margin-bottom: 0;">
                        @foreach($categoryBreakdown as $b)
                            <div class="category-chip">
                                <span class="category-chip-name">{{ $b->name }}:</span>
                                <span class="category-chip-amount font-numeric">{{ number_format((float)$b->total_amount, 2) }} ج.م</span>
                                <span class="text-[10px] text-[var(--text-muted)]">({{ $b->count }})</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <!-- Filter Bar -->
        <div class="finance-filter-bar">
            <form method="GET" action="{{ route('expenses.index') }}" class="finance-filter-inputs">
                <select name="category_id" class="finance-filter-input">
                    <option value="">-- كل البنود --</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>

                <select name="treasury_id" class="finance-filter-input">
                    <option value="">-- كل الخزائن --</option>
                    @foreach($treasuries as $tr)
                        <option value="{{ $tr->id }}" {{ request('treasury_id') == $tr->id ? 'selected' : '' }}>
                            {{ $tr->name }}
                        </option>
                    @endforeach
                </select>

                <input type="date" name="from_date" value="{{ request('from_date') }}" class="finance-filter-input font-numeric" placeholder="من تاريخ">
                <input type="date" name="to_date" value="{{ request('to_date') }}" class="finance-filter-input font-numeric" placeholder="إلى تاريخ">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="بحث بالبيان أو المستلم..." class="finance-filter-input" style="flex: 1; min-width: 160px;">

                <button type="submit" class="erp-btn erp-btn-primary">
                    <x-icon name="filter" size="14" />
                    <span>تصفية</span>
                </button>

                @if(request()->hasAny(['category_id', 'treasury_id', 'from_date', 'to_date', 'search']))
                    <a href="{{ route('expenses.index') }}" class="erp-btn erp-btn-secondary" title="إلغاء التصفية">
                        <x-icon name="x" size="14" />
                    </a>
                @endif
            </form>
        </div>

        <!-- Expenses Table Card -->
        <div class="erp-card">
            <div class="erp-card-header">
                <div class="erp-card-title">
                    <x-icon name="receipt" size="18" />
                    <span>سجل أذونات وسندات صرف المصروفات</span>
                </div>
                <span class="text-xs text-[var(--text-muted)] font-numeric">إجمالي النتائج: {{ $expenses->total() }}</span>
            </div>

            @if($expenses->isEmpty())
                <div class="text-center py-12 text-[var(--text-muted)] text-sm">
                    لا توجد مصروفات مسجلة مطابقة لخيارات البحث.
                </div>
            @else
                <div class="erp-table-responsive">
                    <table class="erp-table">
                        <thead>
                            <tr>
                                <th>رقم السند</th>
                                <th>التاريخ</th>
                                <th>بند المصروف</th>
                                <th>المبلغ</th>
                                <th>الجهة / المستلم</th>
                                <th>الخزينة / المصدر</th>
                                <th>طريقة الدفع</th>
                                <th>البيان والملاحظات</th>
                                <th>المسؤول</th>
                                <th style="text-align: center;">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($expenses as $exp)
                                <tr class="erp-table-row">
                                    <td class="font-mono font-medium text-[var(--text-main)]">{{ $exp->expense_number }}</td>
                                    <td class="font-numeric text-[var(--text-secondary)]">{{ $exp->expense_date ? \Carbon\Carbon::parse($exp->expense_date)->format('Y-m-d') : '—' }}</td>
                                    <td class="font-bold">
                                        <span class="erp-badge erp-badge-neutral">
                                            {{ $exp->category?->name ?? 'غير محدد' }}
                                        </span>
                                    </td>
                                    <td class="font-bold font-numeric text-[var(--danger)]">
                                        -{{ number_format((float)$exp->amount, 2) }} ج.م
                                    </td>
                                    <td class="font-medium text-[var(--text-main)]">{{ $exp->paid_to ?: '—' }}</td>
                                    <td>
                                        @if($exp->shift)
                                            <span class="erp-badge erp-badge-warning">درج الوردية (كاشير)</span>
                                        @else
                                            <span class="text-[var(--text-secondary)]">{{ $exp->treasury?->name ?? 'الخزينة' }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="erp-badge {{ $exp->payment_method === 'cash' ? 'erp-badge-success' : 'erp-badge-info' }}">
                                            {{ $exp->payment_method_label }}
                                        </span>
                                    </td>
                                    <td class="text-[var(--text-secondary)] max-w-xs truncate" title="{{ $exp->notes }}">{{ $exp->notes ?: '—' }}</td>
                                    <td class="text-[var(--text-muted)]">{{ $exp->creator?->name ?? 'النظام' }}</td>
                                    <td style="text-align: center;">
                                        <div style="display: inline-flex; align-items: center; gap: 6px;">
                                            <a href="{{ route('expenses.voucher', $exp) }}" target="_blank" class="erp-btn erp-btn-secondary text-xs py-1 px-2" title="طباعة إذن الصرف">
                                                <x-icon name="printer" size="14" />
                                            </a>
                                            <form action="{{ route('expenses.destroy', $exp) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من إلغاء سند المصروف رقم {{ $exp->expense_number }}؟ سيتم استرداد المبلغ للخزينة.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="erp-btn erp-btn-danger text-xs py-1 px-2" title="إلغاء واسترداد">
                                                    <x-icon name="trash-2" size="14" />
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($expenses->hasPages())
                    <div style="padding: 12px 16px; border-top: 1px solid var(--border-light);">
                        {{ $expenses->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>

    <!-- Modal: Record New Expense -->
    <div id="newExpenseModal" class="erp-modal-overlay">
        <div class="erp-modal-container">
            <div class="erp-modal-header">
                <h3 class="erp-modal-title">
                    <x-icon name="receipt" size="18" />
                    <span>تسجيل إذن صرف مصروف جديد</span>
                </h3>
                <button type="button" class="erp-modal-close" onclick="document.getElementById('newExpenseModal').style.display='none'">&times;</button>
            </div>

            <form action="{{ route('expenses.store') }}" method="POST">
                @csrf
                <div class="erp-modal-body">
                    <div class="erp-form-grid">
                        <div class="erp-form-group">
                            <label class="erp-form-label">بند المصروف <span class="required">*</span></label>
                            <select name="expense_category_id" required class="erp-input">
                                <option value="">-- اختر البند --</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="erp-form-group">
                            <label class="erp-form-label">المبلغ (ج.م) <span class="required">*</span></label>
                            <input type="number" step="0.01" min="0.01" name="amount" required placeholder="0.00" class="erp-input font-numeric font-bold text-base text-[var(--danger)]">
                        </div>
                    </div>

                    <div class="erp-form-grid">
                        <div class="erp-form-group">
                            <label class="erp-form-label">تاريخ الصرف <span class="required">*</span></label>
                            <input type="date" name="expense_date" required value="{{ date('Y-m-d') }}" class="erp-input font-numeric">
                        </div>
                        <div class="erp-form-group">
                            <label class="erp-form-label">طريقة الصرف <span class="required">*</span></label>
                            <select name="payment_method" required class="erp-input">
                                <option value="cash">نقداً</option>
                                <option value="bank_transfer">تحويل بنكي</option>
                                <option value="card">فيزا / بطاقة</option>
                                <option value="cheque">شيك</option>
                            </select>
                        </div>
                    </div>

                    <div class="erp-form-grid">
                        <div class="erp-form-group">
                            <label class="erp-form-label">يُصرف من خزينة / حساب</label>
                            <select name="treasury_id" class="erp-input">
                                <option value="">-- الخزينة الافتراضية --</option>
                                @foreach($treasuries as $tr)
                                    <option value="{{ $tr->id }}">{{ $tr->name }} (رصيد: {{ number_format((float)$tr->current_balance, 2) }} ج.م)</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="erp-form-group">
                            <label class="erp-form-label">الجهة / المستلم (الموظف أو الشركة)</label>
                            <input type="text" name="paid_to" placeholder="اسم المستلم أو الجهة..." class="erp-input">
                        </div>
                    </div>

                    @if($activeShift)
                        <div class="erp-alert erp-alert-warning" style="margin-bottom: 14px;">
                            <input type="checkbox" name="pay_from_shift" value="1" id="pay_from_shift_check" class="rounded">
                            <label for="pay_from_shift_check" class="text-xs font-semibold cursor-pointer text-[var(--text-main)]">
                                خصم المصروف مباشرة من وردية الكاشير النشطة الحالية (درج الكاشير)
                            </label>
                        </div>
                    @endif

                    <div class="erp-form-grid">
                        <div class="erp-form-group">
                            <label class="erp-form-label">رقم الفاتورة / المرجع (إن وجد)</label>
                            <input type="text" name="reference_number" placeholder="رقم إيصال أو فاتورة..." class="erp-input font-mono">
                        </div>
                        <div class="erp-form-group">
                            <label class="erp-form-label">بيان وتفاصيل المصروف</label>
                            <input type="text" name="notes" placeholder="ملاحظات توضيحية..." class="erp-input">
                        </div>
                    </div>
                </div>

                <div class="erp-modal-footer">
                    <button type="button" onclick="document.getElementById('newExpenseModal').style.display='none'" class="erp-btn erp-btn-secondary">إلغاء</button>
                    <button type="submit" class="erp-btn erp-btn-primary">تأكيد وتسجيل المصروف</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Add New Expense Category -->
    <div id="newCategoryModal" class="erp-modal-overlay">
        <div class="erp-modal-container">
            <div class="erp-modal-header">
                <h3 class="erp-modal-title">
                    <x-icon name="tag" size="18" />
                    <span>إضافة بند مصروف جديد</span>
                </h3>
                <button type="button" class="erp-modal-close" onclick="document.getElementById('newCategoryModal').style.display='none'">&times;</button>
            </div>

            <form action="{{ route('expense-categories.store') }}" method="POST">
                @csrf
                <div class="erp-modal-body">
                    <div class="erp-form-group">
                        <label class="erp-form-label">اسم بند المصروف <span class="required">*</span></label>
                        <input type="text" name="name" required placeholder="مثال: مصاريف بوفيه، تأمينات، شحن جوي" class="erp-input">
                    </div>

                    <div class="erp-form-group">
                        <label class="erp-form-label">الوصف والتوضيح</label>
                        <textarea name="description" rows="2" placeholder="تفاصيل متى يُستخدم هذا البند..." class="erp-input" style="height: auto; padding: 8px 12px;"></textarea>
                    </div>
                </div>

                <div class="erp-modal-footer">
                    <button type="button" onclick="document.getElementById('newCategoryModal').style.display='none'" class="erp-btn erp-btn-secondary">إلغاء</button>
                    <button type="submit" class="erp-btn erp-btn-primary">حفظ البند</button>
                </div>
            </form>
        </div>
    </div>
@endsection
