@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="الخزائن والحسابات البنكية" :breadcrumbs="['المالية والحسابات' => route('treasuries.index'), 'الخزائن والبنوك' => route('treasuries.index')]">
        <x-slot:actions>
            <button type="button" onclick="document.getElementById('newTreasuryModal').style.display='flex'" class="erp-btn erp-btn-primary">
                <x-icon name="plus" size="16" />
                <span>إضافة خزينة / حساب بنكي</span>
            </button>
            <button type="button" onclick="document.getElementById('transferModal').style.display='flex'" class="erp-btn erp-btn-secondary">
                <x-icon name="arrow-left-right" size="16" />
                <span>تحويل داخلي بين الخزائن</span>
            </button>
            <a href="{{ route('expenses.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="receipt" size="16" />
                <span>المصروفات الجارية</span>
            </a>
            <a href="{{ route('supplier-payments.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="credit-card" size="16" />
                <span>مدفوعات الموردين</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-success font-numeric px-3 py-1 text-xs">
                <span>إجمالي السيولة الحالية:</span>
                <strong class="mr-1">{{ number_format($totalLiquidity, 2) }} ج.م</strong>
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
    <div data-page="treasuries">
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

        <!-- Liquidity KPI Cards Grid -->
        <div class="finance-kpi-grid">
            <div class="finance-kpi-card">
                <div class="finance-kpi-icon success">
                    <x-icon name="vault" size="22" />
                </div>
                <div class="finance-kpi-info">
                    <div class="finance-kpi-label">إجمالي السيولة النقدية والمصرفية</div>
                    <div class="finance-kpi-value font-numeric text-[var(--success-text)]">{{ number_format($totalLiquidity, 2) }} <span class="text-xs font-normal text-[var(--text-muted)]">ج.م</span></div>
                    <div class="finance-kpi-sub">رصيد كافة الخزن والبنوك النشطة</div>
                </div>
            </div>

            <div class="finance-kpi-card">
                <div class="finance-kpi-icon primary">
                    <x-icon name="banknote" size="22" />
                </div>
                <div class="finance-kpi-info">
                    <div class="finance-kpi-label">رصيد الخزائن النقدية (كاش)</div>
                    <div class="finance-kpi-value font-numeric">{{ number_format($totalCash, 2) }} <span class="text-xs font-normal text-[var(--text-muted)]">ج.م</span></div>
                    <div class="finance-kpi-sub">الخزينة الرئيسية وخزن المعارض</div>
                </div>
            </div>

            <div class="finance-kpi-card">
                <div class="finance-kpi-icon info">
                    <x-icon name="landmark" size="22" />
                </div>
                <div class="finance-kpi-info">
                    <div class="finance-kpi-label">أرصدة الحسابات البنكية</div>
                    <div class="finance-kpi-value font-numeric">{{ number_format($totalBank, 2) }} <span class="text-xs font-normal text-[var(--text-muted)]">ج.م</span></div>
                    <div class="finance-kpi-sub">الحسابات المصرفية الجارية</div>
                </div>
            </div>

            <div class="finance-kpi-card">
                <div class="finance-kpi-icon warning">
                    <x-icon name="smartphone" size="22" />
                </div>
                <div class="finance-kpi-info">
                    <div class="finance-kpi-label">المحافظ الإلكترونية (إنستاباي / كاش)</div>
                    <div class="finance-kpi-value font-numeric">{{ number_format($totalWallet, 2) }} <span class="text-xs font-normal text-[var(--text-muted)]">ج.م</span></div>
                    <div class="finance-kpi-sub">فودافون كاش ومحافظ رقمية</div>
                </div>
            </div>
        </div>

        <!-- Treasuries Grid -->
        <div class="treasury-grid">
            @foreach($treasuries as $t)
                <div class="treasury-card">
                    @if($t->is_default)
                        <div class="treasury-card-badge">الخزينة الافتراضية</div>
                    @endif

                    <div>
                        <div class="treasury-card-header">
                            <div>
                                <div class="treasury-card-name">{{ $t->name }}</div>
                                <div class="mt-1 flex items-center gap-1.5">
                                    <span class="erp-badge {{ $t->type === 'bank' ? 'erp-badge-info' : ($t->type === 'wallet' ? 'erp-badge-warning' : 'erp-badge-success') }}">
                                        {{ $t->type_label }}
                                    </span>
                                    @if($t->bank_name)
                                        <span class="text-xs text-[var(--text-muted)]">({{ $t->bank_name }})</span>
                                    @endif
                                </div>
                            </div>

                            <div class="treasury-card-balance">
                                <div class="treasury-balance-label">الرصيد الفعلي</div>
                                <div class="treasury-balance-val font-numeric {{ (float)$t->current_balance >= 0 ? 'positive' : 'negative' }}">
                                    {{ number_format((float)$t->current_balance, 2) }}
                                    <span class="text-xs font-normal text-[var(--text-muted)]">ج.م</span>
                                </div>
                            </div>
                        </div>

                        @if($t->account_number)
                            <div class="treasury-card-meta">
                                <span>رقم الحساب / المحفظة:</span>
                                <strong class="font-mono text-[var(--text-main)]">{{ $t->account_number }}</strong>
                            </div>
                        @endif

                        @if($t->notes)
                            <div class="text-xs text-[var(--text-muted)] mt-1 line-clamp-1">{{ $t->notes }}</div>
                        @endif
                    </div>

                    <div class="treasury-card-footer">
                        <span>{{ $t->transactions_count }} حركة مسجلة</span>
                        <a href="{{ route('treasuries.show', $t) }}" class="erp-btn erp-btn-secondary text-xs py-1 px-3">
                            <x-icon name="list" size="14" />
                            <span>كشف الحركات</span>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Recent Transactions Table Card -->
        <div class="erp-card">
            <div class="erp-card-header">
                <div class="erp-card-title">
                    <x-icon name="arrow-down-up" size="18" />
                    <span>أحدث حركات النقدية والسيولة (وارد / منصرف)</span>
                </div>
                <span class="text-xs text-[var(--text-muted)] font-numeric">آخر 20 حركة مسجلة</span>
            </div>

            @if($recentTransactions->isEmpty())
                <div class="text-center py-10 text-[var(--text-muted)] text-sm">
                    لا توجد حركات نقدية مسجلة حتى الآن.
                </div>
            @else
                <div class="erp-table-responsive">
                    <table class="erp-table">
                        <thead>
                            <tr>
                                <th>رقم الحركة</th>
                                <th>التاريخ</th>
                                <th>الخزينة / الحساب</th>
                                <th>نوع الحركة</th>
                                <th>المبلغ</th>
                                <th>الرصيد بعدها</th>
                                <th>البيان والوصف</th>
                                <th>المسؤول</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentTransactions as $tx)
                                <tr class="erp-table-row">
                                    <td class="font-mono font-medium text-[var(--text-main)]">{{ $tx->transaction_number }}</td>
                                    <td class="font-numeric text-[var(--text-secondary)]">{{ $tx->transaction_date ? \Carbon\Carbon::parse($tx->transaction_date)->format('Y-m-d') : '—' }}</td>
                                    <td class="font-bold">
                                        <a href="{{ route('treasuries.show', $tx->treasury_id) }}" class="text-[var(--primary)] hover:underline">
                                            {{ $tx->treasury?->name ?? '—' }}
                                        </a>
                                    </td>
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
                                    <td class="text-[var(--text-secondary)] max-w-xs truncate" title="{{ $tx->description }}">{{ $tx->description ?: '—' }}</td>
                                    <td class="text-[var(--text-muted)]">{{ $tx->creator?->name ?? 'النظام' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <!-- Modal: Add New Treasury -->
    <div id="newTreasuryModal" class="erp-modal-overlay">
        <div class="erp-modal-container">
            <div class="erp-modal-header">
                <h3 class="erp-modal-title">
                    <x-icon name="plus-circle" size="18" />
                    <span>إضافة خزينة أو حساب بنكي جديد</span>
                </h3>
                <button type="button" class="erp-modal-close" onclick="document.getElementById('newTreasuryModal').style.display='none'">&times;</button>
            </div>

            <form action="{{ route('treasuries.store') }}" method="POST">
                @csrf
                <div class="erp-modal-body">
                    <div class="erp-form-group">
                        <label class="erp-form-label">اسم الخزينة / الحساب <span class="required">*</span></label>
                        <input type="text" name="name" required placeholder="مثال: الخزينة الفرعية، حساب بنك مصر، فودافون كاش" class="erp-input">
                    </div>

                    <div class="erp-form-grid">
                        <div class="erp-form-group">
                            <label class="erp-form-label">النوع <span class="required">*</span></label>
                            <select name="type" required class="erp-input">
                                <option value="cash">خزينة نقدية (كاش)</option>
                                <option value="bank">حساب بنكي</option>
                                <option value="wallet">محفظة إلكترونية (إنستاباي / كاش)</option>
                            </select>
                        </div>
                        <div class="erp-form-group">
                            <label class="erp-form-label">الرصيد الافتتاحي (ج.م)</label>
                            <input type="number" step="0.01" min="0" name="opening_balance" value="0.00" class="erp-input font-numeric font-bold">
                        </div>
                    </div>

                    <div class="erp-form-grid">
                        <div class="erp-form-group">
                            <label class="erp-form-label">اسم البنك (إن وجد)</label>
                            <input type="text" name="bank_name" placeholder="مثال: البنك الأهلي المصري" class="erp-input">
                        </div>
                        <div class="erp-form-group">
                            <label class="erp-form-label">رقم الحساب / رقم المحفظة</label>
                            <input type="text" name="account_number" placeholder="مثال: 010xxxxxxxx أو IBAN" class="erp-input font-mono">
                        </div>
                    </div>

                    <div class="erp-form-group">
                        <label class="erp-form-label">ملاحظات إضافية</label>
                        <textarea name="notes" rows="2" placeholder="وصف أو أمين الخزينة المسؤول..." class="erp-input" style="height: auto; padding: 8px 12px;"></textarea>
                    </div>

                    <div class="flex items-center gap-2 pt-2">
                        <input type="checkbox" name="is_default" value="1" id="is_default_check" class="rounded">
                        <label for="is_default_check" class="text-xs font-semibold cursor-pointer text-[var(--text-main)]">تعيين كخزينة افتراضية لعمليات الصرف والقبض</label>
                    </div>
                </div>

                <div class="erp-modal-footer">
                    <button type="button" onclick="document.getElementById('newTreasuryModal').style.display='none'" class="erp-btn erp-btn-secondary">إلغاء</button>
                    <button type="submit" class="erp-btn erp-btn-primary">حفظ الخزينة</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Internal Transfer -->
    <div id="transferModal" class="erp-modal-overlay">
        <div class="erp-modal-container">
            <div class="erp-modal-header">
                <h3 class="erp-modal-title">
                    <x-icon name="arrow-left-right" size="18" />
                    <span>تحويل نقدية وسيولة بين الخزائن</span>
                </h3>
                <button type="button" class="erp-modal-close" onclick="document.getElementById('transferModal').style.display='none'">&times;</button>
            </div>

            <form action="{{ route('treasuries.transfer') }}" method="POST">
                @csrf
                <div class="erp-modal-body">
                    <div class="erp-form-grid">
                        <div class="erp-form-group">
                            <label class="erp-form-label">من خزينة / حساب <span class="required">*</span></label>
                            <select name="from_treasury_id" required class="erp-input">
                                <option value="">-- اختر المصدر --</option>
                                @foreach($treasuries as $tr)
                                    <option value="{{ $tr->id }}">{{ $tr->name }} (رصيد: {{ number_format((float)$tr->current_balance, 2) }} ج.م)</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="erp-form-group">
                            <label class="erp-form-label">إلى خزينة / حساب <span class="required">*</span></label>
                            <select name="to_treasury_id" required class="erp-input">
                                <option value="">-- اختر الوجهة --</option>
                                @foreach($treasuries as $tr)
                                    <option value="{{ $tr->id }}">{{ $tr->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="erp-form-grid">
                        <div class="erp-form-group">
                            <label class="erp-form-label">المبلغ المراد تحويله (ج.م) <span class="required">*</span></label>
                            <input type="number" step="0.01" min="0.01" name="amount" required placeholder="0.00" class="erp-input font-numeric font-bold text-[var(--primary)]">
                        </div>
                        <div class="erp-form-group">
                            <label class="erp-form-label">تاريخ التحويل <span class="required">*</span></label>
                            <input type="date" name="transfer_date" required value="{{ date('Y-m-d') }}" class="erp-input font-numeric">
                        </div>
                    </div>

                    <div class="erp-form-group">
                        <label class="erp-form-label">ملاحظات التحويل / سبب النقل</label>
                        <textarea name="notes" rows="2" placeholder="مثال: توريد نقدية إلى البنك، تغذية فرع..." class="erp-input" style="height: auto; padding: 8px 12px;"></textarea>
                    </div>
                </div>

                <div class="erp-modal-footer">
                    <button type="button" onclick="document.getElementById('transferModal').style.display='none'" class="erp-btn erp-btn-secondary">إلغاء</button>
                    <button type="submit" class="erp-btn erp-btn-primary">تأكيد وتنفيذ التحويل</button>
                </div>
            </form>
        </div>
    </div>
@endsection
