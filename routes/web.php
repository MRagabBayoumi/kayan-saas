<?php

use App\Http\Controllers\AccountingController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\CashierShiftController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CentralSaaSController;
use App\Http\Controllers\CompanySettingController;
use App\Http\Controllers\CustomerApprovalController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerInstallmentController;
use App\Http\Controllers\CustomerPaymentController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FinancialReportController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\PortalAttendanceController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\PortalVisitController;
use App\Http\Controllers\PrintTemplateController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductImportController;
use App\Http\Controllers\PurchaseInvoiceController;
use App\Http\Controllers\PurchaseReturnController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SalesInvoiceController;
use App\Http\Controllers\SalesRepAttendanceController;
use App\Http\Controllers\SalesRepController;
use App\Http\Controllers\SalesRepVisitController;
use App\Http\Controllers\SalesReturnController;
use App\Http\Controllers\ShippingOfficeController;
use App\Http\Controllers\StorefrontController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SupplierInstallmentController;
use App\Http\Controllers\SupplierPaymentController;
use App\Http\Controllers\TreasuryController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\WebOrderController;
use App\Http\Middleware\EnsureCentralDomain;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Central SaaS Administration Routes (Strictly secured by EnsureCentralDomain middleware)
Route::middleware(EnsureCentralDomain::class)->prefix('saas')->name('saas.')->group(function () {
    Route::get('/', [CentralSaaSController::class, 'index'])->name('index');
    Route::post('/tenants', [CentralSaaSController::class, 'store'])->name('tenants.store');
    Route::get('/tenants/{tenant}', [CentralSaaSController::class, 'show'])->name('tenants.show');
    Route::post('/tenants/{tenant}/payments', [CentralSaaSController::class, 'recordPayment'])->name('tenants.record-payment');
    Route::post('/tenants/{tenant}/toggle-module', [CentralSaaSController::class, 'toggleModule'])->name('tenants.toggle-module');
    Route::post('/tenants/{tenant}/toggle-status', [CentralSaaSController::class, 'toggleStatus'])->name('tenants.toggle-status');
    Route::post('/tenants/{tenant}/update-subscription', [CentralSaaSController::class, 'updateSubscription'])->name('tenants.update-subscription');

    // Plans & Pricing Management
    Route::get('/plans', [CentralSaaSController::class, 'plans'])->name('plans.index');
    Route::post('/plans', [CentralSaaSController::class, 'storePlan'])->name('plans.store');
    Route::put('/plans/{plan}', [CentralSaaSController::class, 'updatePlan'])->name('plans.update');
    Route::post('/plans/{plan}/toggle', [CentralSaaSController::class, 'togglePlanStatus'])->name('plans.toggle');

    // Subscriptions Management
    Route::get('/subscriptions', [CentralSaaSController::class, 'subscriptions'])->name('subscriptions.index');
    Route::post('/subscriptions/{tenant}/renew', [CentralSaaSController::class, 'renewSubscription'])->name('subscriptions.renew');
    Route::post('/subscriptions/{tenant}/change-plan', [CentralSaaSController::class, 'changePlan'])->name('subscriptions.change-plan');
});

// Root Route: Central -> SaaS Dashboard; Tenant -> Authenticated Home or Login
Route::get('/', function (Request $request) {
    if ($request->attributes->get('is_central')) {
        return redirect()->route('saas.index');
    }

    if (auth()->check()) {
        return app(HomeController::class)->home($request);
    }

    return redirect()->route('login');
})->name('home');

// Guest Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

// Browser Telemetry and Log Endpoint (Silences 405 Method Not Allowed)
Route::any('/_boots/browser-log', function () {
    return response()->json(['status' => 'ok'], 200);
})->name('boots.browser-log');

// Public Storefront (E-Commerce) Routes
Route::middleware('module:ecommerce')->prefix('shop')->name('shop.')->group(function () {
    Route::get('/', [StorefrontController::class, 'catalog'])->name('catalog');
    Route::get('/product/{product}', [StorefrontController::class, 'product'])->name('product');
    Route::get('/cart', [StorefrontController::class, 'cart'])->name('cart');
    Route::post('/cart/add', [StorefrontController::class, 'addToCart'])->name('cart.add');
    Route::post('/cart/update', [StorefrontController::class, 'updateCart'])->name('cart.update');
    Route::post('/cart/remove', [StorefrontController::class, 'removeFromCart'])->name('cart.remove');
    Route::get('/checkout', [StorefrontController::class, 'checkout'])->name('checkout');
    Route::post('/checkout', [StorefrontController::class, 'placeOrder'])->name('checkout.place');
    Route::get('/order/{orderNumber}', [StorefrontController::class, 'orderSuccess'])->name('order.success');
});

// Authenticated Application Routes (Protected by auth middleware)
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // CSRF Token Refresh Endpoint for Long-Lived Sessions & Direct Printing
    Route::get('/csrf-token', function () {
        return response()->json(['token' => csrf_token()]);
    })->name('csrf.token');

    // General Administrative Dashboard (Hidden from cashiers)
    Route::get('/dashboard', [HomeController::class, 'dashboard'])->name('dashboard');

    // Company Settings & Print Templates (Admin Only)
    Route::middleware('can:manage-company-settings')->group(function () {
        Route::get('/settings', [CompanySettingController::class, 'index'])->name('settings.index');
        Route::put('/settings', [CompanySettingController::class, 'update'])->name('settings.update');
        Route::put('/settings/barcode', [CompanySettingController::class, 'updateBarcode'])->name('settings.barcode.update');
        Route::put('/settings/printers', [CompanySettingController::class, 'updatePrinters'])->name('settings.printers.update');
        Route::get('/settings/printers/discover', [CompanySettingController::class, 'discoverPrinters'])->name('settings.printers.discover');
        Route::post('/settings/printers/test', [CompanySettingController::class, 'testPrint'])->name('settings.printers.test');

        // Department / Category Import
        Route::get('/settings/departments/template', [CompanySettingController::class, 'downloadDepartmentTemplate'])->name('settings.departments.template');
        Route::post('/settings/departments/preview', [CompanySettingController::class, 'previewDepartmentImport'])->name('settings.departments.preview');
        Route::post('/settings/departments/import', [CompanySettingController::class, 'importDepartments'])->name('settings.departments.import');

        Route::prefix('settings/print-templates')->name('settings.print-templates.')->group(function () {
            Route::get('/data', [PrintTemplateController::class, 'data'])->name('data');
            Route::put('/{template}', [PrintTemplateController::class, 'update'])->name('update');
            Route::post('/{template}/reset', [PrintTemplateController::class, 'reset'])->name('reset');
            Route::post('/{template}/duplicate', [PrintTemplateController::class, 'duplicate'])->name('duplicate');
        });

        // Backups Management (Spatie Backup)
        Route::post('/settings/backups', [BackupController::class, 'create'])->name('settings.backups.create');
        Route::put('/settings/backups/custom-path', [BackupController::class, 'updateCustomPath'])->name('settings.backups.custom-path');
        Route::get('/settings/backups/download/{filename}', [BackupController::class, 'download'])->name('settings.backups.download');
        Route::delete('/settings/backups/{filename}', [BackupController::class, 'destroy'])->name('settings.backups.destroy');
    });

    // Users & Roles Management
    Route::middleware('can:manage-users')->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
    });

    Route::middleware('can:manage-roles')->group(function () {
        Route::resource('roles', RoleController::class)->except(['show']);
    });

    // Dynamic Units of Measurement Management
    Route::middleware('can:manage-units')->group(function () {
        Route::resource('units', UnitController::class)->except(['show']);
    });

    // Product & Category Management
    // Read-only product routes accessible to admin, warehouse, and cashier
    Route::middleware('can:view-products')->group(function () {
        Route::get('/products/lookup-barcode', [ProductController::class, 'lookupBarcode'])->name('products.lookup-barcode');
        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
        Route::get('/products/{product}', [ProductController::class, 'show'])->whereNumber('product')->name('products.show');
        Route::get('/products/{product}/barcode-svg', [ProductController::class, 'barcodeSvg'])->whereNumber('product')->name('products.barcode-svg');
        Route::get('/products/{product}/ledger/print', [ProductController::class, 'printLedger'])->whereNumber('product')->name('products.ledger.print');
        Route::post('/products/{product}/ledger/direct-print', [ProductController::class, 'directPrintLedger'])->whereNumber('product')->name('products.ledger.direct-print');
        Route::get('/products/{product}/ledger', [ProductController::class, 'ledger'])->whereNumber('product')->name('products.ledger');
        Route::get('/products/{product}/print-label', [ProductController::class, 'printLabel'])->whereNumber('product')->name('products.print-label');
        Route::match(['get', 'post'], '/products/print-labels/batch', [ProductController::class, 'printBatchLabels'])->name('products.print-batch');
        Route::post('/products/{product}/direct-print', [ProductController::class, 'directPrint'])->whereNumber('product')->name('products.direct-print');
        Route::post('/products/direct-print/batch', [ProductController::class, 'directPrintBatch'])->name('products.direct-print-batch');
    });

    // Product modifications & categories (Admin & Warehouse)
    Route::middleware('can:manage-products')->group(function () {
        Route::get('/products/import/template', [ProductImportController::class, 'downloadTemplate'])->name('products.import.template');
        Route::get('/products/import', [ProductImportController::class, 'showForm'])->name('products.import.form');
        Route::post('/products/import/review', [ProductImportController::class, 'review'])->name('products.import.review');
        Route::post('/products/import/confirm', [ProductImportController::class, 'confirm'])->name('products.import.confirm');
        Route::get('/products/import/cancel', [ProductImportController::class, 'cancel'])->name('products.import.cancel');

        Route::get('/products/generate-barcode', [ProductController::class, 'generateBarcode'])->name('products.generate-barcode');
        Route::post('/products/quick-store', [ProductController::class, 'quickStore'])->name('products.quick-store');
        Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
        Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::post('/products/{product}/toggle-status', [ProductController::class, 'toggleStatus'])->name('products.toggle-status');
        Route::post('/products/{product}/toggle-online', [ProductController::class, 'toggleOnline'])->name('products.toggle-online');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

        Route::resource('categories', CategoryController::class)->except(['create', 'show', 'edit']);
    });

    // Purchasing & Suppliers Invoices (FIFO Receipt)
    Route::middleware('can:access-purchases')->group(function () {
        // Admin-only operations on purchase invoices
        Route::post('/purchases/{purchase}/post', [PurchaseInvoiceController::class, 'post'])
            ->middleware('can:post-purchase-invoice')
            ->name('purchases.post');

        Route::post('/purchases/{purchase}/cancel', [PurchaseInvoiceController::class, 'cancel'])
            ->middleware('can:cancel-purchase-invoice')
            ->name('purchases.cancel');

        Route::get('/purchases/{purchase}/pricing-review', [PurchaseInvoiceController::class, 'pricingReview'])
            ->middleware('can:post-purchase-invoice')
            ->name('purchases.pricing-review');

        Route::post('/purchases/{purchase}/confirm-pricing', [PurchaseInvoiceController::class, 'confirmPricingAndPost'])
            ->middleware('can:post-purchase-invoice')
            ->name('purchases.confirm-pricing');

        Route::post('/purchases/{purchase}/direct-print', [PurchaseInvoiceController::class, 'directPrint'])->name('purchases.direct-print');
        Route::get('/purchases/{purchase}/print', [PurchaseInvoiceController::class, 'print'])->name('purchases.print');
        Route::resource('purchases', PurchaseInvoiceController::class);

        // Purchase Returns (مرتجعات المشتريات والتوريد)
        Route::get('/purchase-returns/{purchase_return}/print', [PurchaseReturnController::class, 'print'])->name('purchase-returns.print');
        Route::post('/purchase-returns/{purchase_return}/direct-print', [PurchaseReturnController::class, 'directPrint'])->name('purchase-returns.direct-print');
        Route::post('/purchase-returns/{purchase_return}/post', [PurchaseReturnController::class, 'post'])
            ->middleware('can:post-purchase-invoice')
            ->name('purchase-returns.post');
        Route::resource('purchase-returns', PurchaseReturnController::class)->except(['edit', 'update', 'destroy']);
    });

    // Sales & Outgoing Invoices (FIFO Costing & Dynamic Pricing)
    Route::middleware('can:access-sales')->group(function () {
        Route::post('/sales/{sale}/post', [SalesInvoiceController::class, 'post'])
            ->middleware('can:post-sales-invoice')
            ->name('sales.post');

        Route::post('/sales/{sale}/void', [SalesInvoiceController::class, 'void'])
            ->middleware('can:void-sales-invoice')
            ->name('sales.void');

        Route::post('/sales/{sale}/unpost-and-edit', [SalesInvoiceController::class, 'unpostAndEdit'])
            ->middleware('can:manage-sales')
            ->name('sales.unpost-and-edit');

        Route::get('/sales/{sale}/edit', [SalesInvoiceController::class, 'edit'])
            ->middleware('can:manage-sales')
            ->name('sales.edit');

        Route::put('/sales/{sale}', [SalesInvoiceController::class, 'update'])
            ->middleware('can:manage-sales')
            ->name('sales.update');

        Route::get('/sales/{sale}/print', [SalesInvoiceController::class, 'print'])->name('sales.print');
        Route::post('/sales/{sale}/direct-print', [SalesInvoiceController::class, 'directPrint'])->name('sales.direct-print');

        // Sales Payments & Installments (سندات القبض والدفعات)
        Route::post('/sales/{sale}/payments', [CustomerPaymentController::class, 'storeForInvoice'])->name('sales.payments.store');
        Route::get('/sales/payments/{payment}/receipt', [CustomerPaymentController::class, 'receipt'])->name('sales.payments.receipt');
        Route::delete('/sales/payments/{payment}', [CustomerPaymentController::class, 'destroy'])->name('sales.payments.destroy');

        Route::resource('sales', SalesInvoiceController::class)->except(['edit', 'update']);

        // Sales Returns (مرتجعات فواتير المبيعات)
        Route::get('/sales-returns/{sales_return}/print', [SalesReturnController::class, 'print'])->name('sales-returns.print');
        Route::post('/sales-returns/{sales_return}/direct-print', [SalesReturnController::class, 'directPrint'])->name('sales-returns.direct-print');
        Route::post('/sales-returns/{sales_return}/post', [SalesReturnController::class, 'post'])
            ->middleware('can:manage-sales')
            ->name('sales-returns.post');
        Route::resource('sales-returns', SalesReturnController::class)->except(['edit', 'update', 'destroy']);

        // Cashier Shifts & Cash Drawer
        Route::get('/shifts', [CashierShiftController::class, 'index'])->name('shifts.index');
        Route::post('/shifts', [CashierShiftController::class, 'store'])->name('shifts.store');
        Route::get('/shifts/{shift}', [CashierShiftController::class, 'show'])->name('shifts.show');
        Route::get('/shifts/{shift}/print', [CashierShiftController::class, 'print'])->name('shifts.print');
        Route::post('/shifts/{shift}/direct-print', [CashierShiftController::class, 'directPrint'])->name('shifts.direct-print');
        Route::post('/shifts/{shift}/movement', [CashierShiftController::class, 'movement'])->name('shifts.movement');
        Route::post('/shifts/{shift}/close', [CashierShiftController::class, 'close'])->name('shifts.close');

        // Cashier Shift Expenses & Vouchers
        Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
        Route::get('/expenses/{expense}/voucher', [ExpenseController::class, 'voucher'])->name('expenses.voucher');
    });

    // Warehouse & Inventory Management (Admin & Warehouse)
    Route::middleware('can:access-inventory')->group(function () {
        // Stocktake & Inventory Reconciliation
        Route::get('/inventory/stocktake', [InventoryController::class, 'stocktake'])->name('inventory.stocktake');
        Route::get('/inventory/stocktake/template', [InventoryController::class, 'downloadStocktakeTemplate'])->name('inventory.stocktake.template');
        Route::post('/inventory/stocktake/preview', [InventoryController::class, 'previewStocktake'])->name('inventory.stocktake.preview');
        Route::post('/inventory/stocktake/reconcile', [InventoryController::class, 'reconcileStocktake'])->name('inventory.stocktake.reconcile');

        Route::get('/warehouses/{warehouse}/stock/print', [WarehouseController::class, 'printStock'])->name('warehouses.stock.print');
        Route::get('/warehouses/{warehouse}', [WarehouseController::class, 'show'])->name('warehouses.show');
        Route::resource('warehouses', WarehouseController::class)->except(['create', 'show', 'edit']);
        Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::get('/inventory/movements', [InventoryController::class, 'movements'])->name('inventory.movements');
        Route::get('/inventory/low-stock', [InventoryController::class, 'lowStock'])->name('inventory.low-stock');
        Route::get('/inventory/low-stock/print', [InventoryController::class, 'printLowStock'])->name('inventory.low-stock.print');
        Route::post('/inventory/low-stock/direct-print', [InventoryController::class, 'directPrintLowStock'])->name('inventory.low-stock.direct-print');
        Route::post('/inventory/movements', [InventoryController::class, 'storeMovement'])->name('inventory.movements.store');
    });

    // Directory of Customers & POS Customer Creation
    Route::get('/customers/lookup', [CustomerController::class, 'lookup'])
        ->middleware('can:access-customers')
        ->name('customers.lookup');

    Route::post('/customers', [CustomerController::class, 'store'])
        ->middleware('can:create-customers-pos')
        ->name('customers.store');

    Route::middleware('can:manage-customers')->group(function () {
        Route::get('/customers/{customer}/statement', [CustomerController::class, 'statement'])->name('customers.statement');
        Route::get('/customers/{customer}/statement/print', [CustomerController::class, 'printStatement'])->name('customers.statement.print');
        Route::get('/customers/{customer}/purchased-items', [CustomerController::class, 'purchasedItems'])->name('customers.purchased-items');
        Route::get('/customers/{customer}/purchased-items/print', [CustomerController::class, 'printPurchasedItems'])->name('customers.purchased-items.print');
        Route::get('/customers/{customer}/whatsapp', [CustomerController::class, 'whatsappShare'])->name('customers.whatsapp');
        Route::post('/customers/{customer}/payments', [CustomerPaymentController::class, 'storeForCustomer'])->name('customers.payments.store');
        Route::get('/customers/{customer}/unpaid-invoices', [CustomerInstallmentController::class, 'invoicesForCustomer'])->name('customers.unpaid-invoices');
        Route::resource('customers', CustomerController::class)->except(['store']);

        // Customer Installments & Collection Scheduling
        Route::get('/customer-installments', [CustomerInstallmentController::class, 'index'])->name('customer-installments.index');
        Route::post('/customer-installments', [CustomerInstallmentController::class, 'store'])->name('customer-installments.store');
        Route::post('/customer-installments/{installment}/collect', [CustomerInstallmentController::class, 'collect'])->name('customer-installments.collect');
        Route::delete('/customer-installments/{installment}', [CustomerInstallmentController::class, 'destroy'])->name('customer-installments.destroy');
    });

    // Directory of Suppliers & Shipping Offices (Purchases & Inventory)
    Route::middleware('can:manage-purchases')->group(function () {
        Route::get('/suppliers/balance-due-report', [SupplierController::class, 'balanceDueReport'])->name('suppliers.balance-due-report');
        Route::get('/suppliers/balance-due-report/print', [SupplierController::class, 'printBalanceDueReport'])->name('suppliers.balance-due-report.print');
        Route::get('/suppliers/{supplier}/statement', [SupplierController::class, 'statement'])->name('suppliers.statement');
        Route::get('/suppliers/{supplier}/statement/print', [SupplierController::class, 'printStatement'])->name('suppliers.statement.print');
        Route::get('/suppliers/lookup', [SupplierController::class, 'lookup'])->name('suppliers.lookup');
        Route::resource('suppliers', SupplierController::class);

        Route::resource('shipping-offices', ShippingOfficeController::class)->except(['create', 'show', 'edit']);
        Route::get('/suppliers/{supplier}/payables-data', [SupplierPaymentController::class, 'payablesData'])->name('suppliers.payables-data');
        Route::post('/purchases/{purchase}/payments', [SupplierPaymentController::class, 'storeForInvoice'])->name('purchases.payments.store');
        Route::post('/suppliers/{supplier}/payments', [SupplierPaymentController::class, 'storeForSupplier'])->name('suppliers.payments.store');

        // Supplier Installments & Payment Scheduling
        Route::get('/supplier-installments', [SupplierInstallmentController::class, 'index'])->name('supplier-installments.index');
        Route::post('/supplier-installments', [SupplierInstallmentController::class, 'store'])->name('supplier-installments.store');
        Route::delete('/supplier-installments/{installment}', [SupplierInstallmentController::class, 'destroy'])->name('supplier-installments.destroy');
    });

    // Finance, Treasuries & Accounting (المرحلة الأولى: دورة النقدية والصرف)
    Route::middleware('can:manage-company-settings')->group(function () {
        // Treasuries & Bank Accounts
        Route::get('/treasuries', [TreasuryController::class, 'index'])->name('treasuries.index');
        Route::post('/treasuries', [TreasuryController::class, 'store'])->name('treasuries.store');
        Route::get('/treasuries/{treasury}', [TreasuryController::class, 'show'])->name('treasuries.show');
        Route::post('/treasuries/transfer', [TreasuryController::class, 'transfer'])->name('treasuries.transfer');
        Route::post('/treasuries/{treasury}/manual-transaction', [TreasuryController::class, 'manualTransaction'])->name('treasuries.manual-transaction');

        // Customer Payments Index
        Route::get('/sales-payments', [CustomerPaymentController::class, 'index'])->name('sales.payments.index');

        // Supplier Payments Registry & Vouchers
        Route::get('/supplier-payments', [SupplierPaymentController::class, 'index'])->name('supplier-payments.index');
        Route::get('/supplier-payments/{payment}/receipt', [SupplierPaymentController::class, 'receipt'])->name('supplier-payments.receipt');
        Route::delete('/supplier-payments/{payment}', [SupplierPaymentController::class, 'destroy'])->name('supplier-payments.destroy');

        // Expenses & Categories (Admin & Treasury Management)
        Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
        Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');
        Route::post('/expense-categories', [ExpenseController::class, 'storeCategory'])->name('expense-categories.store');

        // General Ledger & Chart of Accounts (شجرة الحسابات وقيود اليومية العامة)
        Route::middleware('module:accounting')->group(function () {
            Route::get('/accounting/chart-of-accounts', [AccountingController::class, 'chartOfAccounts'])->name('accounting.chart-of-accounts');
            Route::post('/accounting/accounts', [AccountingController::class, 'storeAccount'])->name('accounting.accounts.store');
            Route::get('/accounting/journal-entries', [AccountingController::class, 'journalEntries'])->name('accounting.journal-entries');
            Route::get('/accounting/journal-entries/{entry}', [AccountingController::class, 'journalEntryShow'])->name('accounting.journal-entries.show');
            Route::get('/accounting/journal-entries/{entry}/print', [AccountingController::class, 'journalEntryPrint'])->name('accounting.journal-entries.print');
            Route::post('/accounting/recalculate-balances', [AccountingController::class, 'recalculateBalances'])->name('accounting.recalculate-balances');

            // Financial Reports & General Ledger (التقارير المالية الختامية والأستاذ العام)
            Route::get('/accounting/reports/trial-balance', [FinancialReportController::class, 'trialBalance'])->name('accounting.reports.trial-balance');
            Route::get('/accounting/reports/trial-balance/print', [FinancialReportController::class, 'trialBalancePrint'])->name('accounting.reports.trial-balance.print');
            Route::get('/accounting/reports/income-statement', [FinancialReportController::class, 'incomeStatement'])->name('accounting.reports.income-statement');
            Route::get('/accounting/reports/income-statement/print', [FinancialReportController::class, 'incomeStatementPrint'])->name('accounting.reports.income-statement.print');
            Route::get('/accounting/reports/balance-sheet', [FinancialReportController::class, 'balanceSheet'])->name('accounting.reports.balance-sheet');
            Route::get('/accounting/reports/balance-sheet/print', [FinancialReportController::class, 'balanceSheetPrint'])->name('accounting.reports.balance-sheet.print');
            Route::get('/accounting/accounts/{account}/ledger', [FinancialReportController::class, 'accountLedger'])->whereNumber('account')->name('accounting.accounts.ledger');
            Route::get('/accounting/accounts/{account}/ledger/print', [FinancialReportController::class, 'accountLedgerPrint'])->whereNumber('account')->name('accounting.accounts.ledger.print');
        });
    });

    // Sales Representatives Management & Performance Analytics (Admin)
    Route::middleware(['can:manage-sales-reps', 'module:sales_reps'])->group(function () {
        Route::get('/sales-reps', [SalesRepController::class, 'index'])->name('sales-reps.index');
        Route::get('/sales-reps/create', [SalesRepController::class, 'create'])->name('sales-reps.create');
        Route::post('/sales-reps', [SalesRepController::class, 'store'])->name('sales-reps.store');

        // Customer Approval Requests
        Route::get('/sales-reps/customer-requests', [CustomerApprovalController::class, 'index'])->name('sales-reps.customer-requests.index');
        Route::post('/sales-reps/customer-requests/{customer}/approve', [CustomerApprovalController::class, 'approve'])->name('sales-reps.customer-requests.approve');
        Route::post('/sales-reps/customer-requests/{customer}/reject', [CustomerApprovalController::class, 'reject'])->name('sales-reps.customer-requests.reject');

        // Reps Attendance Monitoring
        Route::get('/sales-reps/attendance', [SalesRepAttendanceController::class, 'index'])->name('sales-reps.attendance.index');

        // Reps Visits Monitoring
        Route::get('/sales-reps/visits', [SalesRepVisitController::class, 'index'])->name('sales-reps.visits.index');

        // Rep Details & Commissions
        Route::get('/sales-reps/{rep}', [SalesRepController::class, 'show'])->name('sales-reps.show');
        Route::put('/sales-reps/{rep}', [SalesRepController::class, 'update'])->name('sales-reps.update');
        Route::post('/sales-reps/commissions/{commission}/pay', [SalesRepController::class, 'payCommission'])->name('sales-reps.commissions.pay');
    });

    // Sales Representative & Field Agent Mobile Portal
    Route::middleware(['can:access-sales-portal', 'module:sales_reps'])->prefix('portal')->name('portal.')->group(function () {
        Route::get('/', [PortalController::class, 'index'])->name('index');
        Route::get('/catalog', [PortalController::class, 'catalog'])->name('catalog');

        // GPS Attendance & Work Hours
        Route::get('/attendance', [PortalAttendanceController::class, 'index'])->name('attendance.index');
        Route::post('/attendance/check-in', [PortalAttendanceController::class, 'checkIn'])->name('attendance.check-in');
        Route::post('/attendance/check-out', [PortalAttendanceController::class, 'checkOut'])->name('attendance.check-out');

        // Customer field directory (Viewing)
        Route::get('/customers', [PortalController::class, 'customers'])->name('customers.index');

        // Commissions Statement
        Route::get('/commissions', [PortalController::class, 'commissions'])->name('commissions');

        // Active Working Session Gated Operations (Requires GPS-Verified Attendance)
        Route::middleware('attendance.required')->group(function () {
            Route::get('/visits/create', [PortalVisitController::class, 'create'])->name('visits.create');
            Route::post('/visits', [PortalVisitController::class, 'store'])->name('visits.store');
            Route::post('/visits/{visit}/complete', [PortalVisitController::class, 'complete'])->name('visits.complete');

            Route::get('/customers/create', [PortalController::class, 'createCustomer'])->name('customers.create');
            Route::post('/customers', [PortalController::class, 'storeCustomer'])->name('customers.store');

            Route::get('/orders/create', [PortalController::class, 'createOrder'])->name('orders.create');
            Route::post('/orders', [PortalController::class, 'storeOrder'])->name('orders.store');
        });

        // GPS Customer Visits (Viewing & Parameterized)
        Route::get('/visits', [PortalVisitController::class, 'index'])->name('visits.index');
        Route::get('/visits/{visit}', [PortalVisitController::class, 'show'])->name('visits.show');

        // Mobile Orders & Invoicing (Viewing & Parameterized)
        Route::get('/orders', [PortalController::class, 'orders'])->name('orders.index');
        Route::get('/orders/{invoice}', [PortalController::class, 'showOrder'])->name('orders.show');
    });

    // Tenant Back-Office E-Commerce Management (طلبات وإعدادات المتجر الإلكتروني)
    Route::middleware('module:ecommerce')->prefix('ecommerce')->name('ecommerce.')->group(function () {
        Route::get('/orders', [WebOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [WebOrderController::class, 'show'])->name('orders.show');
        Route::post('/orders/{order}/update-status', [WebOrderController::class, 'updateStatus'])->name('orders.update-status');
        Route::post('/orders/{order}/convert-invoice', [WebOrderController::class, 'convertToInvoice'])->name('orders.convert-invoice');
        Route::get('/settings', [WebOrderController::class, 'settings'])->name('settings');
        Route::post('/settings', [WebOrderController::class, 'updateSettings'])->name('settings.update');
    });
});
