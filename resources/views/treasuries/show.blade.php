@extends('layouts.app')

@section('control_panel')
    <x-control-panel :title="'كشف حركة: ' . $treasury->name" :breadcrumbs="['المالية والحسابات' => route('treasuries.index'), 'الخزائن والبنوك' => route('treasuries.index'), $treasury->name => route('treasuries.show', $treasury)]">
        <x-slot:actions>
            <button type="button" onclick="openManualTrx('deposit')" class="erp-btn erp-btn-success">
                <x-icon name="plus-circle" size="16" />
                <span>إيداع / تغذية نقدية (+)</span>
            </button>
            <button type="button" onclick="openManualTrx('withdrawal')" class="erp-btn erp-btn-danger">
                <x-icon name="minus-circle" size="16" />
                <span>صرف / سحب نقدي (-)</span>
            </button>
            <button type="button" onclick="document.getElementById('transferModal').style.display='flex'" class="erp-btn erp-btn-secondary">
                <x-icon name="arrow-left-right" size="16" />
                <span>تحويل لخزينة أخرى</span>
            </button>
            <a href="{{ route('treasuries.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="arrow-right" size="16" />
                <span>كافة الخزائن</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-neutral font-numeric px-3 py-1 text-xs">
                <span>الرصيد الفعلي الحالي:</span>
                <strong class="mr-1 {{ (float)$treasury->current_balance >= 0 ? 'text-[var(--success-text)]' : 'text-[var(--danger)]' }}">
                    {{ number_format((float)$treasury->current_balance, 2) }} ج.م
                </strong>
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
    <div data-page="treasury-show">
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

        <!-- Treasury Info & KPIs -->
        <div class="finance-kpi-grid">
            <div class="finance-kpi-card">
                <div class="finance-kpi-icon primary">
                    <x-icon name="landmark" size="22" />
                </div>
                <div class="finance-kpi-info">
                    <div class="finance-kpi-label">بيانات الخزينة / الحساب</div>
                    <div class="finance-kpi-value text-base font-bold">{{ $treasury->name }}</div>
                    <div class="finance-kpi-sub mt-1">
                        <span class="erp-badge {{ $treasury->type === 'bank' ? 'erp-badge-info' : ($treasury->type === 'wallet' ? 'erp-badge-warning' : 'erp-badge-success') }}">
                            {{ $treasury->type_label }}
                        </span>
                        @if($treasury->account_number)
                            <span class="font-mono text-xs mr-1 text-[var(--text-muted)]">{{ $treasury->account_number }}</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="finance-kpi-card">
                <div class="finance-kpi-icon {{ (float)$treasury->current_balance >= 0 ? 'success' : 'danger' }}">
                    <x-icon name="vault" size="22" />
                </div>
                <div class="finance-kpi-info">
                    <div class="finance-kpi-label">الرصيد الفعلي الحالي</div>
                    <div class="finance-kpi-value font-numeric {{ (float)$treasury->current_balance >= 0 ? 'text-[var(--success-text)]' : 'text-[var(--danger)]' }}">
                        {{ number_format((float)$treasury->current_balance, 2) }} <span class="text-xs font-normal text-[var(--text-muted)]">ج.م</span>
                    </div>
                    <div class="finance-kpi-sub">افتتاحي: {{ number_format((float)$treasury->opening_balance, 2) }} ج.م</div>
                </div>
            </div>

            <div class="finance-kpi-card">
                <div class="finance-kpi-icon success">
                    <x-icon name="arrow-down-left" size="22" />
                </div>
                <div class="finance-kpi-info">
                    <div class="finance-kpi-label">إجمالي الإيداعات (الوارد)</div>
                    <div class="finance-kpi-value font-numeric text-[var(--success-text)]">
                        +{{ number_format($totalDeposits, 2) }} <span class="text-xs font-normal text-[var(--text-muted)]">ج.م</span>
                    </div>
                    <div class="finance-kpi-sub">حسب نتائج التصفية</div>
                </div>
            </div>

            <div class="finance-kpi-card">
                <div class="finance-kpi-icon danger">
                    <x-icon name="arrow-up-right" size="22" />
                </div>
                <div class="finance-kpi-info">
                    <div class="finance-kpi-label">إجمالي المسحوبات (المنصرف)</div>
                    <div class="finance-kpi-value font-numeric text-[var(--danger)]">
                        -{{ number_format($totalWithdrawals, 2) }} <span class="text-xs font-normal text-[var(--text-muted)]">ج.م</span>
                    </div>
                    <div class="finance-kpi-sub">حسب نتائج التصفية</div>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="finance-filter-bar">
            <form method="GET" action="{{ route('treasuries.show', $treasury) }}" class="finance-filter-inputs">
                <select name="type" class="finance-filter-input">
                    <option value="">-- كل الحركات --</option>
                    <option value="deposit" {{ request('type') === 'deposit' ? 'selected' : '' }}>إيداع / وارد فقط (+)</option>
                    <option value="withdrawal" {{ request('type') === 'withdrawal' ? 'selected' : '' }}>صرف / سحب فقط (-)</option>
                </select>

                <input type="date" name="from_date" value="{{ request('from_date') }}" class="finance-filter-input font-numeric" placeholder="من تاريخ">
                <input type="date" name="to_date" value="{{ request('to_date') }}" class="finance-filter-input font-numeric" placeholder="إلى تاريخ">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="بحث بالرقم أو البيان..." class="finance-filter-input" style="flex: 1; min-width: 180px;">

                <button type="submit" class="erp-btn erp-btn-primary">
                    <x-icon name="filter" size="14" />
                    <span>تصفية</span>
                </button>

                @if(request()->hasAny(['type', 'from_date', 'to_date', 'search']))
                    <a href="{{ route('treasuries.show', $treasury) }}" class="erp-btn erp-btn-secondary" title="إلغاء التصفية">
                        <x-icon name="x" size="14" />
                    </a>
                @endif
            </form>
        </div>

        <!-- Transactions Ledger Table Card -->
        <div class="erp-card">
            <div class="erp-card-header">
                <div class="erp-card-title">
                    <x-icon name="list" size="18" />
                    <span>سجل الحركات التفصيلي لكشف حساب الخزينة</span>
                </div>
                <span class="text-xs text-[var(--text-muted)] font-numeric">إجمالي الحركات: {{ $transactions->total() }}</span>
            </div>

            @if($transactions->isEmpty())
                <div class="text-center py-12 text-[var(--text-muted)] text-sm">
                    لا توجد حركات مسجلة مطابقة لخيارات البحث في هذه الخزينة.
                </div>
            @else
                <div class="erp-table-responsive">
                    <table class="erp-table">
                        <thead>
                            <tr>
                                <th>رقم الحركة</th>
                                <th>التاريخ</th>
                                <th>النوع</th>
                                <th>المبلغ</th>
                                <th>الرصيد بعدها</th>
                                <th>مصدر الحركة</th>
                                <th>البيان والوصف</th>
                                <th>المسؤول</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($transactions as $tx)
                                <tr class="erp-table-row">
                                    <td class="font-mono font-medium text-[var(--text-main)]">{{ $tx->transaction_number }}</td>
                                    <td class="font-numeric text-[var(--text-secondary)]">{{ $tx->transaction_date ? \Carbon\Carbon::parse($tx->transaction_date)->format('Y-m-d') : '—' }}</td>
                                    <td>
                                        <span class="erp-badge {{ $tx->type === 'deposit' ? 'erp-badge-success' : 'erp-badge-danger' }}">
                                            {{ $tx->type_label }}
                                        </span>
                                    </td>
                                    <td class="font-bold font-numeric {{ $tx->type === 'deposit' ? 'text-[var(--success-text)]' : 'text-[var(--danger-text)]' }}">
                                        {{ $tx->type === 'deposit' ? '+' : '-' }}{{ number_format((float)$tx->amount, 2) }} ج.م
                                    </td>
                                    <td class="font-numeric font-bold text-[var(--text-main)]">
                                        {{ number_format((float)$tx->balance_after, 2) }} ج.م
                                    </td>
                                    <td>
                                        @if($tx->source_type === 'customer_payment')
                                            <span class="erp-badge erp-badge-success">سند قبض عميل</span>
                                        @elseif($tx->source_type === 'supplier_payment')
                                            <span class="erp-badge erp-badge-danger">سند صرف مورد</span>
                                        @elseif($tx->source_type === 'expense')
                                            <span class="erp-badge erp-badge-warning">مصروف جاري</span>
                                        @elseif($tx->source_type === 'transfer_in' || $tx->source_type === 'transfer_out')
                                            <span class="erp-badge erp-badge-info">تحويل داخلي</span>
                                        @elseif($tx->source_type === 'opening_balance')
                                            <span class="erp-badge erp-badge-neutral">رصيد افتتاحي</span>
                                        @else
                                            <span class="erp-badge erp-badge-neutral">يدوي / مباشر</span>
                                        @endif
                                    </td>
                                    <td class="text-[var(--text-secondary)] max-w-sm truncate" title="{{ $tx->description }}">{{ $tx->description ?: '—' }}</td>
                                    <td class="text-[var(--text-muted)]">{{ $tx->creator?->name ?? 'النظام' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($transactions->hasPages())
                    <div style="padding: 12px 16px; border-top: 1px solid var(--border-light);">
                        {{ $transactions->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>

    <!-- Modal: Manual Deposit / Withdrawal -->
    <div id="manualTrxModal" class="erp-modal-overlay">
        <div class="erp-modal-container">
            <div class="erp-modal-header">
                <h3 id="manualTrxModalTitle" class="erp-modal-title">
                    تسجيل حركة نقدية مباشرة
                </h3>
                <button type="button" class="erp-modal-close" onclick="document.getElementById('manualTrxModal').style.display='none'">&times;</button>
            </div>

            <form action="{{ route('treasuries.manual-transaction', $treasury) }}" method="POST">
                @csrf
                <input type="hidden" name="type" id="manualTrxType" value="deposit">

                <div class="erp-modal-body">
                    <div class="erp-form-group">
                        <label class="erp-form-label">المبلغ (ج.م) <span class="required">*</span></label>
                        <input type="number" step="0.01" min="0.01" name="amount" required placeholder="0.00" class="erp-input font-numeric font-bold text-base text-[var(--primary)]">
                    </div>

                    <div class="erp-form-group">
                        <label class="erp-form-label">تاريخ الحركة <span class="required">*</span></label>
                        <input type="date" name="transaction_date" required value="{{ date('Y-m-d') }}" class="erp-input font-numeric">
                    </div>

                    <div class="erp-form-group">
                        <label class="erp-form-label">البيان / سبب الإيداع أو السحب <span class="required">*</span></label>
                        <textarea name="description" rows="3" required placeholder="اكتب وصفاً واضحاً للحركة..." class="erp-input" style="height: auto; padding: 8px 12px;"></textarea>
                    </div>
                </div>

                <div class="erp-modal-footer">
                    <button type="button" onclick="document.getElementById('manualTrxModal').style.display='none'" class="erp-btn erp-btn-secondary">إلغاء</button>
                    <button type="submit" id="manualTrxSubmitBtn" class="erp-btn erp-btn-primary">تأكيد العملية</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Transfer -->
    <div id="transferModal" class="erp-modal-overlay">
        <div class="erp-modal-container">
            <div class="erp-modal-header">
                <h3 class="erp-modal-title">
                    <x-icon name="arrow-left-right" size="18" />
                    <span>تحويل نقدية من [{{ $treasury->name }}]</span>
                </h3>
                <button type="button" class="erp-modal-close" onclick="document.getElementById('transferModal').style.display='none'">&times;</button>
            </div>

            <form action="{{ route('treasuries.transfer') }}" method="POST">
                @csrf
                <input type="hidden" name="from_treasury_id" value="{{ $treasury->id }}">

                <div class="erp-modal-body">
                    <div class="erp-form-group">
                        <label class="erp-form-label">تحويل إلى خزينة / حساب <span class="required">*</span></label>
                        <select name="to_treasury_id" required class="erp-input">
                            <option value="">-- اختر الحساب المستقبل --</option>
                            @foreach($allTreasuries as $dest)
                                <option value="{{ $dest->id }}">{{ $dest->name }} ({{ $dest->type_label }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="erp-form-grid">
                        <div class="erp-form-group">
                            <label class="erp-form-label">المبلغ المراد تحويله (ج.م) <span class="required">*</span></label>
                            <input type="number" step="0.01" min="0.01" max="{{ (float)$treasury->current_balance }}" name="amount" required placeholder="0.00" class="erp-input font-numeric font-bold text-[var(--primary)]">
                        </div>
                        <div class="erp-form-group">
                            <label class="erp-form-label">تاريخ التحويل <span class="required">*</span></label>
                            <input type="date" name="transfer_date" required value="{{ date('Y-m-d') }}" class="erp-input font-numeric">
                        </div>
                    </div>

                    <div class="erp-form-group">
                        <label class="erp-form-label">ملاحظات التحويل</label>
                        <textarea name="notes" rows="2" placeholder="السبب أو المسؤول عن النقل..." class="erp-input" style="height: auto; padding: 8px 12px;"></textarea>
                    </div>
                </div>

                <div class="erp-modal-footer">
                    <button type="button" onclick="document.getElementById('transferModal').style.display='none'" class="erp-btn erp-btn-secondary">إلغاء</button>
                    <button type="submit" class="erp-btn erp-btn-primary">تأكيد التحويل</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openManualTrx(type) {
            const modal = document.getElementById('manualTrxModal');
            const typeInput = document.getElementById('manualTrxType');
            const title = document.getElementById('manualTrxModalTitle');
            const submitBtn = document.getElementById('manualTrxSubmitBtn');

            typeInput.value = type;
            if (type === 'deposit') {
                title.textContent = 'إيداع / تغذية نقدية في ' + @json($treasury->name);
                submitBtn.className = 'erp-btn erp-btn-success';
                submitBtn.textContent = 'تأكيد الإيداع (+)';
            } else {
                title.textContent = 'صرف / سحب نقدي من ' + @json($treasury->name);
                submitBtn.className = 'erp-btn erp-btn-danger';
                submitBtn.textContent = 'تأكيد الصرف (-)';
            }

            modal.style.display = 'flex';
        }
    </script>
@endsection
