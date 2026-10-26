@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="دليل الحسابات الشجري (Chart of Accounts)" :breadcrumbs="['المالية والحسابات' => route('treasuries.index'), 'شجرة الحسابات' => route('accounting.chart-of-accounts')]">
        <x-slot:actions>
            <button type="button" onclick="document.getElementById('newAccountModal').style.display='flex'" class="erp-btn erp-btn-primary">
                <x-icon name="plus" size="16" />
                <span>إضافة حساب فرعي جديد</span>
            </button>
            <a href="{{ route('accounting.journal-entries') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="file-text" size="16" />
                <span>دفتر قيود اليومية</span>
            </a>
            <form action="{{ route('accounting.recalculate-balances') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="erp-btn erp-btn-secondary" title="إعادة احتساب وتحديث مجاميع الأرصدة في الشجرة بالكامل">
                    <x-icon name="refresh-cw" size="16" />
                    <span>تحديث أرصدة الشجرة</span>
                </button>
            </form>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-info font-numeric px-3 py-1 text-xs">
                <span>إجمالي الحسابات:</span>
                <strong class="mr-1">{{ $metrics['total_accounts_count'] }} حساب</strong>
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
    <div data-page="chart-of-accounts">
        @include('accounting.nav-tabs')

        @if(session('message'))
            <div class="erp-alert erp-alert-success mb-4" role="alert">
                <x-icon name="check-circle" size="18" />
                <div>{{ session('message') }}</div>
            </div>
        @endif

        @if($errors->any())
            <div class="erp-alert erp-alert-danger mb-4" role="alert">
                <x-icon name="alert-triangle" size="18" />
                <div>
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <!-- Financial Position & Overview KPI Cards -->
        <div class="finance-kpi-grid">
            <!-- 1. Assets -->
            <div class="finance-kpi-card">
                <div class="finance-kpi-icon info">
                    <x-icon name="layers" size="22" />
                </div>
                <div class="finance-kpi-info">
                    <div class="finance-kpi-label">1. الأصول (Assets)</div>
                    <div class="finance-kpi-value font-numeric text-[var(--info-text)]">
                        {{ number_format($metrics['total_assets'], 2) }} <span class="text-xs font-normal text-[var(--text-muted)]">ج.م</span>
                    </div>
                    <div class="finance-kpi-sub">إجمالي ممتلكات وأصول الشركة</div>
                </div>
            </div>

            <!-- 2. Liabilities -->
            <div class="finance-kpi-card">
                <div class="finance-kpi-icon warning">
                    <x-icon name="credit-card" size="22" />
                </div>
                <div class="finance-kpi-info">
                    <div class="finance-kpi-label">2. الخصوم والالتزامات</div>
                    <div class="finance-kpi-value font-numeric text-[var(--warning-text)]">
                        {{ number_format($metrics['total_liabilities'], 2) }} <span class="text-xs font-normal text-[var(--text-muted)]">ج.م</span>
                    </div>
                    <div class="finance-kpi-sub">الموردين والمستحقات والالتزامات</div>
                </div>
            </div>

            <!-- 3. Equity -->
            <div class="finance-kpi-card">
                <div class="finance-kpi-icon primary">
                    <x-icon name="shield" size="22" />
                </div>
                <div class="finance-kpi-info">
                    <div class="finance-kpi-label">3. حقوق الملكية (Equity)</div>
                    <div class="finance-kpi-value font-numeric text-[var(--primary)]">
                        {{ number_format($metrics['total_equity'], 2) }} <span class="text-xs font-normal text-[var(--text-muted)]">ج.م</span>
                    </div>
                    <div class="finance-kpi-sub">رأس المال والأرباح المرحلة</div>
                </div>
            </div>

            <!-- 4. Revenues -->
            <div class="finance-kpi-card">
                <div class="finance-kpi-icon success">
                    <x-icon name="trending-up" size="22" />
                </div>
                <div class="finance-kpi-info">
                    <div class="finance-kpi-label">4. الإيرادات والمبيعات</div>
                    <div class="finance-kpi-value font-numeric text-[var(--success-text)]">
                        {{ number_format($metrics['total_revenues'], 2) }} <span class="text-xs font-normal text-[var(--text-muted)]">ج.م</span>
                    </div>
                    <div class="finance-kpi-sub">إجمالي مبيعات وإيرادات النشاط</div>
                </div>
            </div>

            <!-- 5. Expenses -->
            <div class="finance-kpi-card">
                <div class="finance-kpi-icon danger">
                    <x-icon name="trending-down" size="22" />
                </div>
                <div class="finance-kpi-info">
                    <div class="finance-kpi-label">5. المصروفات والتكلفة</div>
                    <div class="finance-kpi-value font-numeric text-[var(--danger)]">
                        {{ number_format($metrics['total_expenses'], 2) }} <span class="text-xs font-normal text-[var(--text-muted)]">ج.م</span>
                    </div>
                    <div class="finance-kpi-sub">تكلفة بضاعة + مصاريف تشغيل</div>
                </div>
            </div>

            <!-- 6. Net Profit / Income -->
            <div class="finance-kpi-card">
                <div class="finance-kpi-icon {{ $metrics['net_income'] >= 0 ? 'success' : 'danger' }}">
                    <x-icon name="{{ $metrics['net_income'] >= 0 ? 'award' : 'alert-octagon' }}" size="22" />
                </div>
                <div class="finance-kpi-info">
                    <div class="finance-kpi-label">صافي ربح النشاط التشغيلي</div>
                    <div class="finance-kpi-value font-numeric {{ $metrics['net_income'] >= 0 ? 'text-[var(--success-text)]' : 'text-[var(--danger)]' }}">
                        {{ number_format($metrics['net_income'], 2) }} <span class="text-xs font-normal text-[var(--text-muted)]">ج.م</span>
                    </div>
                    <div class="finance-kpi-sub">{{ $metrics['net_income'] >= 0 ? 'فائض أرباح نشاط' : 'عجز خسائر نشاط' }}</div>
                </div>
            </div>
        </div>

        <!-- Filter, Search & Level Controls Card -->
        <div class="finance-filter-bar mb-4">
            <div class="flex flex-col lg:flex-row items-center justify-between gap-3">
                <!-- Type Filter Pills -->
                <div class="flex flex-wrap items-center gap-1.5 w-full lg:w-auto">
                    <a href="{{ route('accounting.chart-of-accounts') }}" 
                       class="erp-btn {{ empty($currentType) ? 'erp-btn-primary' : 'erp-btn-secondary' }} text-xs py-1.5 px-3">
                        <span>كافة الحسابات</span>
                    </a>
                    <a href="{{ route('accounting.chart-of-accounts', ['type' => 'asset']) }}" 
                       class="erp-btn {{ $currentType === 'asset' ? 'erp-btn-primary' : 'erp-btn-secondary' }} text-xs py-1.5 px-2.5">
                        <span class="w-2 h-2 rounded-full inline-block bg-sky-500 mr-1"></span>
                        <span>الأصول</span>
                    </a>
                    <a href="{{ route('accounting.chart-of-accounts', ['type' => 'liability']) }}" 
                       class="erp-btn {{ $currentType === 'liability' ? 'erp-btn-primary' : 'erp-btn-secondary' }} text-xs py-1.5 px-2.5">
                        <span class="w-2 h-2 rounded-full inline-block bg-amber-500 mr-1"></span>
                        <span>الخصوم</span>
                    </a>
                    <a href="{{ route('accounting.chart-of-accounts', ['type' => 'equity']) }}" 
                       class="erp-btn {{ $currentType === 'equity' ? 'erp-btn-primary' : 'erp-btn-secondary' }} text-xs py-1.5 px-2.5">
                        <span class="w-2 h-2 rounded-full inline-block bg-indigo-500 mr-1"></span>
                        <span>حقوق الملكية</span>
                    </a>
                    <a href="{{ route('accounting.chart-of-accounts', ['type' => 'revenue']) }}" 
                       class="erp-btn {{ $currentType === 'revenue' ? 'erp-btn-primary' : 'erp-btn-secondary' }} text-xs py-1.5 px-2.5">
                        <span class="w-2 h-2 rounded-full inline-block bg-emerald-500 mr-1"></span>
                        <span>الإيرادات</span>
                    </a>
                    <a href="{{ route('accounting.chart-of-accounts', ['type' => 'expense']) }}" 
                       class="erp-btn {{ $currentType === 'expense' ? 'erp-btn-primary' : 'erp-btn-secondary' }} text-xs py-1.5 px-2.5">
                        <span class="w-2 h-2 rounded-full inline-block bg-rose-500 mr-1"></span>
                        <span>المصروفات</span>
                    </a>
                </div>

                <!-- Instant Search & Quick Expansion Depth Controls -->
                <div class="flex flex-wrap items-center gap-2 w-full lg:w-auto justify-between lg:justify-end">
                    <!-- Live Search -->
                    <div class="relative w-full sm:w-64">
                        <input type="text" 
                               id="accountTreeSearch" 
                               placeholder="بحث بكود أو اسم الحساب..." 
                               class="finance-filter-input pr-8 pl-6 w-full text-xs" 
                               onkeyup="filterAccountTree(this.value)">
                        <div class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[var(--text-muted)] pointer-events-none">
                            <x-icon name="search" size="14" />
                        </div>
                        <button type="button" 
                                id="clearSearchBtn"
                                class="absolute left-2.5 top-1/2 -translate-y-1/2 text-[var(--text-muted)] hover:text-[var(--text-main)] hidden"
                                onclick="clearSearch()">
                            <x-icon name="x" size="12" />
                        </button>
                    </div>

                    <!-- Expansion Level Controls -->
                    <div class="inline-flex rounded-lg border border-[var(--border-color)] bg-[var(--bg-surface)] p-0.5 text-xs font-semibold text-[var(--text-muted)]">
                        <button type="button" class="px-2.5 py-1 rounded hover:bg-[var(--bg-hover)] hover:text-[var(--text-main)] transition-colors" onclick="expandToLevel(1)" title="طي لكافة الحسابات الرئيسية (المستوى 1)">
                            <span>مستوى 1</span>
                        </button>
                        <button type="button" class="px-2.5 py-1 rounded hover:bg-[var(--bg-hover)] hover:text-[var(--text-main)] transition-colors" onclick="expandToLevel(2)" title="توسيع الحسابات العامة (المستوى 2)">
                            <span>مستوى 2</span>
                        </button>
                        <button type="button" class="px-2.5 py-1 rounded hover:bg-[var(--bg-hover)] hover:text-[var(--text-main)] transition-colors" onclick="expandAllNodes()" title="توسيع كافة الفروع والحسابات">
                            <span>توسيع الكل</span>
                        </button>
                        <button type="button" class="px-2.5 py-1 rounded hover:bg-[var(--bg-hover)] hover:text-[var(--text-main)] transition-colors" onclick="collapseAllNodes()" title="طي الشجرة بالكامل">
                            <span>طي الكل</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- The Chart of Accounts Tree Card -->
        <div class="coa-tree-container">
            <!-- Header Bar -->
            <div class="coa-header-row">
                <div class="flex items-center gap-2">
                    <x-icon name="list" size="15" />
                    <span>هيكل الحسابات والأدلة المحاسبية العامة</span>
                </div>
                <div class="flex items-center gap-6">
                    <span class="hidden xl:inline">طبيعة الحساب</span>
                    <span class="hidden sm:inline">تصنيف القائمة</span>
                    <span class="min-w-[130px] text-left">الرصيد الدفتري الحالي</span>
                    <span class="w-[52px] text-center">إجراءات</span>
                </div>
            </div>

            <!-- Tree Nodes Root -->
            <div id="accountsTreeContainer">
                @forelse($rootAccounts as $root)
                    @include('accounting.partials.account-node', ['account' => $root, 'level' => 1])
                @empty
                    <div class="p-12 text-center text-muted">
                        <x-icon name="alert-circle" size="40" class="mx-auto mb-2 opacity-30" />
                        <p class="font-bold text-sm">لا توجد حسابات مسجلة مطابقة لمعايير البحث.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Modal: Add Sub-Account -->
    <div id="newAccountModal" class="erp-modal-backdrop" style="display: none;">
        <div class="erp-modal-box max-w-lg">
            <div class="erp-modal-header">
                <div class="flex items-center gap-2">
                    <span class="p-1.5 rounded-lg bg-[var(--primary-soft)] text-[var(--primary)]">
                        <x-icon name="plus" size="16" />
                    </span>
                    <h3 class="erp-modal-title">إضافة حساب فرعي جديد إلى الدليل الشجري</h3>
                </div>
                <button type="button" onclick="document.getElementById('newAccountModal').style.display='none'" class="erp-modal-close">
                    <x-icon name="x" size="18" />
                </button>
            </div>

            <form action="{{ route('accounting.accounts.store') }}" method="POST">
                @csrf
                <div class="erp-modal-body space-y-4">
                    <!-- Parent Account Selection -->
                    <div>
                        <label for="modalParentSelect" class="erp-label required">الحساب الرئيسي (الأب / المتفرع منه)</label>
                        <select name="parent_id" id="modalParentSelect" required class="erp-input text-sm font-sans" onchange="updateSuggestedCode(this)">
                            <option value="">-- اختر الحساب الأب --</option>
                            @foreach($eligibleParents as $p)
                                <option value="{{ $p->id }}" data-code="{{ $p->code }}" data-type="{{ $p->type_label }}" data-nature="{{ $p->nature_label }}">
                                    {{ str_repeat('— ', $p->level - 1) }}{{ $p->code }} - {{ $p->name }} ({{ $p->type_label }})
                                </option>
                            @endforeach
                        </select>
                        <div id="parentMetaHint" class="text-[11px] text-[var(--text-muted)] mt-1 hidden">
                            <span>النوع: <strong id="hintType" class="text-[var(--text-main)]"></strong></span> |
                            <span>الطبيعة: <strong id="hintNature" class="text-[var(--text-main)]"></strong></span>
                        </div>
                    </div>

                    <!-- Account Name Arabic -->
                    <div>
                        <label for="name" class="erp-label required">اسم الحساب بالعربية</label>
                        <input type="text" name="name" id="name" required class="erp-input text-sm" placeholder="مثال: خزينة فرع المنصورة / بنك مصر حساب جاري">
                    </div>

                    <!-- Account Name English -->
                    <div>
                        <label for="name_en" class="erp-label">اسم الحساب بالإنجليزية (اختياري)</label>
                        <input type="text" name="name_en" id="name_en" class="erp-input text-sm font-sans" placeholder="e.g. Mansoura Branch Cash Safe">
                    </div>

                    <!-- Custom Code (Optional) -->
                    <div>
                        <div class="flex items-center justify-between">
                            <label for="code" class="erp-label">كود الحساب الشجري</label>
                            <span class="text-[11px] text-[var(--text-muted)]">يترك فارغاً للترقيم التلقائي المتسلسل</span>
                        </div>
                        <input type="text" name="code" id="code" class="erp-input text-sm font-mono" placeholder="اتركه فارغاً للتوليد الآلي">
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="description" class="erp-label">بيان أو ملاحظات على استخدام الحساب</label>
                        <textarea name="description" id="description" rows="2" class="erp-input text-sm" placeholder="أية ملاحظات إضافية أو استخدامات خاصة بهذا الحساب..."></textarea>
                    </div>
                </div>

                <div class="erp-modal-footer">
                    <button type="button" onclick="document.getElementById('newAccountModal').style.display='none'" class="erp-btn erp-btn-secondary">إلغاء</button>
                    <button type="submit" class="erp-btn erp-btn-primary">
                        <x-icon name="plus" size="16" />
                        <span>تأكيد وحفظ الحساب</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Floating Toast for Copied Code -->
    <div id="copyToast" class="fixed bottom-6 left-1/2 -translate-x-1/2 bg-[var(--text-main)] text-[var(--bg-surface)] text-xs font-bold py-2 px-4 rounded-full shadow-lg z-50 opacity-0 pointer-events-none transition-opacity duration-200">
        تم نسخ كود الحساب إلى الحافظة!
    </div>

    <!-- Tree Interaction Scripts -->
    <script>
        function toggleAccountNode(btn, childrenId) {
            const container = document.getElementById(childrenId);
            if (!container) return;
            const icon = btn.querySelector('.erp-icon');
            
            if (container.style.display === 'none') {
                container.style.display = 'block';
                btn.classList.remove('is-collapsed');
                if (icon) icon.classList.remove('-rotate-90');
            } else {
                container.style.display = 'none';
                btn.classList.add('is-collapsed');
                if (icon) icon.classList.add('-rotate-90');
            }
        }

        function expandAllNodes() {
            document.querySelectorAll('.account-children').forEach(el => el.style.display = 'block');
            document.querySelectorAll('.node-toggle-btn').forEach(btn => {
                btn.classList.remove('is-collapsed');
                const icon = btn.querySelector('.erp-icon');
                if (icon) icon.classList.remove('-rotate-90');
            });
        }

        function collapseAllNodes() {
            document.querySelectorAll('.account-children').forEach(el => el.style.display = 'none');
            document.querySelectorAll('.node-toggle-btn').forEach(btn => {
                btn.classList.add('is-collapsed');
                const icon = btn.querySelector('.erp-icon');
                if (icon) icon.classList.add('-rotate-90');
            });
        }

        function expandToLevel(targetLevel) {
            collapseAllNodes();
            document.querySelectorAll('.account-row-wrapper').forEach(row => {
                const lvl = parseInt(row.dataset.level || '1', 10);
                if (lvl <= targetLevel) {
                    const id = row.dataset.accountId;
                    const childrenContainer = document.getElementById('children-' + id);
                    if (childrenContainer) {
                        childrenContainer.style.display = 'block';
                        const toggleBtn = row.querySelector('.node-toggle-btn');
                        if (toggleBtn) {
                            toggleBtn.classList.remove('is-collapsed');
                            const icon = toggleBtn.querySelector('.erp-icon');
                            if (icon) icon.classList.remove('-rotate-90');
                        }
                    }
                }
            });
        }

        function openAddSubAccountModal(parentId, parentLabel) {
            const modal = document.getElementById('newAccountModal');
            const select = document.getElementById('modalParentSelect');
            if (select) {
                select.value = parentId;
                updateSuggestedCode(select);
            }
            modal.style.display = 'flex';
        }

        function updateSuggestedCode(select) {
            const selectedOpt = select.options[select.selectedIndex];
            const hintDiv = document.getElementById('parentMetaHint');
            if (!selectedOpt || !selectedOpt.value) {
                if (hintDiv) hintDiv.classList.add('hidden');
                return;
            }

            if (hintDiv) {
                document.getElementById('hintType').textContent = selectedOpt.dataset.type || '—';
                document.getElementById('hintNature').textContent = selectedOpt.dataset.nature || '—';
                hintDiv.classList.remove('hidden');
            }
        }

        function filterAccountTree(query) {
            query = query.trim().toLowerCase();
            const clearBtn = document.getElementById('clearSearchBtn');
            const rows = document.querySelectorAll('.account-row-wrapper');
            
            if (clearBtn) {
                clearBtn.style.display = query ? 'block' : 'none';
            }

            if (!query) {
                rows.forEach(r => r.style.display = 'block');
                document.querySelectorAll('.account-children').forEach(c => c.style.display = 'block');
                return;
            }

            rows.forEach(row => {
                const name = (row.dataset.accountName || '').toLowerCase();
                const code = (row.dataset.accountCode || '').toLowerCase();
                
                if (name.includes(query) || code.includes(query)) {
                    row.style.display = 'block';
                    
                    // Uncollapse and show all ancestor parent containers
                    let p = row.closest('.account-children');
                    while (p) {
                        p.style.display = 'block';
                        const parentRow = p.previousElementSibling;
                        if (parentRow && parentRow.classList.contains('account-row-wrapper')) {
                            parentRow.style.display = 'block';
                            const btn = parentRow.querySelector('.node-toggle-btn');
                            if (btn) {
                                btn.classList.remove('is-collapsed');
                                const icon = btn.querySelector('.erp-icon');
                                if (icon) icon.classList.remove('-rotate-90');
                            }
                        }
                        p = p.parentElement?.closest('.account-children');
                    }
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function clearSearch() {
            const input = document.getElementById('accountTreeSearch');
            if (input) {
                input.value = '';
                filterAccountTree('');
                input.focus();
            }
        }

        function copyAccountCode(code, event) {
            event.stopPropagation();
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(code).then(() => showToast('تم نسخ كود الحساب [' + code + ']'));
            } else {
                showToast('كود الحساب: ' + code);
            }
        }

        function showToast(msg) {
            const toast = document.getElementById('copyToast');
            if (!toast) return;
            toast.textContent = msg;
            toast.style.opacity = '1';
            setTimeout(() => {
                toast.style.opacity = '0';
            }, 2000);
        }
    </script>
@endsection
