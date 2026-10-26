# Kayan ERP — Enterprise Resource Planning System

> **Languages & Documentation:** [النسخة العربية (README.md)](README.md) · [Implementation Roadmap & Gap Analysis (ROADMAP.md)](ROADMAP.md)

Enterprise Resource Planning (**ERP**) web platform built for **Kayan**, an import and distribution enterprise specializing in women's fashion accessories, hair supplies, beauty accessories, and novelty items imported from China to domestic wholesale and retail merchants.

The user interface and user experience are engineered following the architectural and interaction standards of **Odoo Web Client** and **Daftara**, featuring full Arabic localization, native Right-to-Left (RTL) layout support, and strict architectural separation (**Zero Inline JS / Zero Inline CSS**).

---

## 📌 Project Phase Status & Roadmap

| Phase | Module & Scope | Official Status |
| :--- | :--- | :---: |
| **Phase 1 — Project Audit & Database Isolation** | Toolchain audit, PHP 8.4/8.5 verification, `kayan_erp` target confirmation, and strict legacy database isolation. | ✅ **Verified Complete** |
| **Phase 2 — Design System & Application Shell** | Master layout, Cairo typography, Light/Dark themes, Enterprise density switcher, modular Blade components. | ✅ **Verified Complete** |
| **Visual QA & Responsive Grid Auditing** | Browser verification via Edge/Chrome, sidebar collapse fix, and responsive grid layout preventing card truncation. | ✅ **Verified Complete** |
| **Phase 3A — Company Settings & Template Designer (`/settings`)** | Branding, international contact numbers, live logo preview, and centralized print template management. | ✅ **Verified Complete** |
| **Phase 3B — Dynamic Live Dashboard** | Real-time master dashboard connected to MySQL database with live counters for products, customers, and suppliers. | ✅ **Verified Complete** |
| **Phase 4 — Products, Pricing, Import & Barcode Printing** | 4-tier pricing, pieces-per-pack, indexed barcode (1.3ms), atomic Excel importer, and thermal barcode printing. | ✅ **Verified Complete** |
| **Phase 5 — Warehouses, Movements & Ledger Printing** | Multi-warehouse stock balances, transactions (in/out/transfer/adjust), low-stock alerts, and stock ledger printing. | ✅ **Verified Complete** |
| **Phase 6 — Wholesale Customers, Suppliers & Shipping Directory** | Customer accounts, credit terms, China manufacturers (WeChat ID), shipping offices, and quick-add modal dialogs. | ✅ **Verified Complete** |
| **Phase 9 (Part 1) — Purchases, FIFO Batches & Invoice Printing** | Multi-item purchase invoices, atomic FIFO batch generation (`inventory_batches`), and 3-format invoice printing. | ✅ **Verified Complete** |
| **Universal Print Template Engine** | Service layer (`PrintTemplateService`), standard support for A4, A5, and Thermal 80mm with dynamic CSS `@page`. | ✅ **Verified Complete** |
| **Production Architecture & Deployment** | Separate dev/live environments, background auto-start on Windows boot, and automated 1-click update scripts. | ✅ **Verified Complete** |
| **Phase 7 & 8 — Cashier, Point of Sale (POS) & Sales Invoicing** | Fast POS interface, barcode scanner, instant stock deduction (FIFO), shift & cash drawer management, and thermal receipts. | ⏳ **Planned** *(Immediate Next Phase)* |
| **Phase 10 — Cash Register & Treasury Accounting** | Cash boxes, operating expense vouchers, supplier payments, profit & loss statements, and balance sheets. | 📋 **Planned** |
| **Phase 11 — Live Dynamic Dashboard & BI Analytics** | Interactive financial charts, real-time sales KPIs, and aggregated analytics derived from real business transactions. | 📋 **Planned** |
| **Phase 12 — Production Hardening & Deployment** | End-to-end integration testing, concurrency lock tests, security audit, and deployment pipeline. | 📋 **Planned** |

---

## 🛠️ Technology Stack

- **Backend Framework:** [Laravel 12 / 13](https://laravel.com) · [PHP 8.4 / 8.5](https://php.net)
- **Database:** [MySQL 8.0](https://mysql.com) — Isolated database: `kayan_erp`
- **Frontend Architecture:** Reusable Blade Components & Partials with strict separation (Zero inline JS / CSS)
- **Styling:** CSS Design Tokens (`erp.css`) + Tailwind CSS v4 + Vite v8 + [Cairo](https://fonts.google.com/specimen/Cairo) font with tabular numbers (`tabular-nums`)
- **Testing & Quality Assurance:** [Pest PHP](https://pestphp.com) (**165 automated tests covering all modules, printing, and UI**) + [Laravel Pint](https://laravel.com/docs/pint)
- **Document & Thermal Printing Engines:**
  - Standard document printing engine supporting A4, A5, and Thermal 80mm via dynamic CSS `@page` styling.
  - Direct Python thermal printing engine (TSPL & Raw) for 38×25mm and 38×15mm labels with 90° and 270° rotation.

---

## 🎨 Implemented Modules & Architecture

### 1. Document Printing & Universal Template Engine
- **Standard Document Formats Supported:**
  - **A4 (210×297 mm):** Standard format for formal audits and accounting statements.
  - **A5 (148×210 mm):** Compact format saving 50% paper, ideal for daily invoices.
  - **Thermal Receipt (80mm):** High-speed POS rolls and warehouse issue slips.
- **Configurable Print Templates (`PrintTemplate` & `PrintTemplateService`):**
  - Database-driven template schema allowing customized headers, footers, logo display, barcodes, and print margins.
  - Settings designer tab (`/settings?tab=print-templates`) for visual template selection and dimension adjustments.
- **Purchase Invoice Printing (`/purchases/{purchase}/print`):**
  - Live data binding for supplier info, shipping agent, line items, discounts, taxes, and net balances.
  - Dynamic toolbar switcher to preview A4, A5, and 80mm before printing.
- **Product Stock Movement Ledger Printing (`/products/{product}/ledger/print`):**
  - Complete unpaginated audit report of stock movements with running balances, supporting A4, A5, and 80mm.
- **Thermal Barcode Label Printing (38×25mm & 38×15mm):**
  - Landscape orientation support with 90° and 270° rotation for narrow thermal printers (e.g. Xprinter XP-233B).

### 2. Products, 4-Tier Pricing & Atomic Excel Import (`/products`)
- **4-Tier Wholesale & Retail Pricing Hierarchy:**
  1. Cost Price (China import in local currency).
  2. Wholesale Price (for accessory store merchants).
  3. Distributor / Agency Price (for bulk volume purchases).
  4. Retail Consumer Price.
- **Packaging & Stock Thresholds:** Package/carton piece counts and automated low-stock reorder thresholds.
- **Atomic Opening Stock:** Automatic inventory batch creation on product setup for FIFO cost tracking.
- **Refactored Product Details Page (`/products/{product}`):**
  - Framed image display `.erp-product-image-frame` preserving aspect ratio and preventing distortions.
  - Separate modular cards for image, barcode sticker preview, and product deletion.
  - Responsive breakpoint at 1080px to eliminate any horizontal card clipping on laptops and tablets.
- **Atomic Excel / CSV Import (`/products/import`):**
  - UTF-8 BOM formatted template download ensuring Arabic compatibility in Microsoft Excel.
  - Pre-import validation comparing imported records against the database.
  - User options to insert new records, update existing items, or skip errors within a database transaction.

### 3. Purchases & FIFO Cost Tracking (`/purchases`)
- Complete purchasing workflow supporting itemized entries, supplier balances, shipping office charges, and received warehouse assignment.
- Automatic creation of FIFO receipt batches (`inventory_batches`) with cost per unit.

### 4. Warehouses & Stock Movements (`/stock-movements`)
- Multi-warehouse tracking for receipts, shipments, transfers, and inventory adjustments.
- Detailed item-level stock movement ledger showing date, movement type, quantity, running balance, and party involved.

### 5. Trading Partners Directory (Customers, Suppliers, Shipping Offices)
- Quick-add modal dialogs allowing instantaneous creation of customers, suppliers, and shipping offices directly from purchase/sales forms without page reload.
- Duplicate submission protection and automated select dropdown refreshes.

### 6. Architectural Discipline (Strict Blade Separation)
- 100% compliant with clean architecture: **0 inline `<script>` tags, 0 inline event handlers, and 0 inline `<style>` / `style="..."` attributes** across all 34 Blade templates.
- Enforced by automated audit script: `node scripts/audit_blade_separation.cjs`.

---

## 📁 Project Directory Structure

```text
kayan-erp-new/
├── app/
│   ├── Http/Controllers/
│   │   ├── CategoryController.php          # Category hierarchy management
│   │   ├── CompanySettingController.php    # Company settings and logo management
│   │   ├── CustomerController.php          # Customer directory & credit limits
│   │   ├── PrintTemplateController.php     # Print template designer controller
│   │   ├── ProductController.php           # Catalog, pricing, barcode, and ledger
│   │   ├── ProductImportController.php     # Excel import and audit controller
│   │   ├── PurchaseInvoiceController.php   # Purchase workflow, batches, and printing
│   │   ├── ShippingOfficeController.php    # International shipping offices
│   │   ├── StockAlertController.php        # Low-stock alerts controller
│   │   ├── StockMovementController.php     # Inventory movements and ledger
│   │   ├── SupplierController.php          # China manufacturers & local suppliers
│   │   └── WarehouseController.php         # Warehouse facilities & balances
│   ├── Models/
│   │   ├── BarcodeSetting.php              # Barcode dimensions and orientation
│   │   ├── Category.php                    # Hierarchical categories
│   │   ├── CompanySetting.php              # Singleton company profile
│   │   ├── Customer.php                    # Wholesale/retail customer accounts
│   │   ├── InventoryBatch.php              # FIFO inventory lots
│   │   ├── PrintTemplate.php               # Configurable print templates (A4, A5, 80mm)
│   │   ├── Product.php                     # Catalog items, 4-tier pricing, packaging
│   │   ├── PurchaseInvoice.php             # Purchase invoices
│   │   ├── PurchaseInvoiceItem.php         # Invoice line items and unit costs
│   │   ├── ShippingOffice.php              # Shipping agencies
│   │   ├── StockBalance.php                # Warehouse stock balances
│   │   ├── StockMovement.php               # Warehouse movement records
│   │   ├── Supplier.php                    # Supplier accounts (WeChat ID, currencies)
│   │   ├── User.php                        # System users and roles
│   │   └── Warehouse.php                   # Warehouse locations
│   └── Services/
│       ├── BarcodeGeneratorService.php     # Barcode generation service
│       ├── PrintTemplateService.php        # Print template service (A4, A5, 80mm)
│       ├── ProductImportService.php        # Atomic Excel/CSV import engine
│       └── Printing/
│           └── PythonPrintEngineService.php# Python direct thermal printing bridge
├── database/
│   ├── migrations/                         # 21 database migrations for kayan_erp
│   └── seeders/                            # Database & print template seeders
├── resources/
│   ├── css/
│   │   ├── app.css                         # Tailwind entry & Cairo typography
│   │   ├── erp.css                         # Design tokens, themes, and density
│   │   ├── pages/                          # Standalone page-specific CSS files
│   │   └── print/                          # Print stylesheets for documents and labels
│   ├── js/
│   │   ├── app.js                          # Core application script
│   │   └── pages/                          # Standalone page-specific JS modules
│   └── views/                              # Blade components, partials, and views
├── scripts/
│   ├── audit_blade_separation.cjs          # Blade architectural separation audit
│   └── printing/                           # Direct thermal printing engine in Python
└── tests/Feature/                          # 165 automated tests with Pest PHP
```

---

## 🚀 Getting Started

### Prerequisites
- **PHP:** >= 8.4
- **Composer:** >= 2.8
- **Node.js:** >= 20 & npm
- **Database:** MySQL 8.0 targeting `kayan_erp`

### Quick Start

1. **Install Dependencies:**
   ```bash
   composer install
   npm install
   ```

2. **Database Migration & Seeding:**
   ```bash
   php artisan migrate --seed
   ```

3. **Build Frontend Assets:**
   ```bash
   npm run build
   # Or for hot reload during development:
   npm run dev
   ```

4. **Start Application Server:**
   ```bash
   php artisan serve --port=8000
   ```
   *Access the application at:* [http://127.0.0.1:8000](http://127.0.0.1:8000)

5. **Run Quality Audits & Test Suite:**
   ```bash
   # Verify architectural Blade separation
   node scripts/audit_blade_separation.cjs

   # Run automated Pest test suite
   php artisan test
   ```

---

## 🔒 Safety & Architectural Rules

- **Target Database:** Strictly bound to `kayan_erp`. The legacy desktop database `kayan` is completely isolated.
- **Factual Data:** No fabricated or dummy financial numbers on dashboards.
- **Atomic Transactions:** All inventory movements, imports, and purchases run within `DB::transaction`.
- **Strict Separation:** 0% inline JS or inline CSS inside Blade templates.
