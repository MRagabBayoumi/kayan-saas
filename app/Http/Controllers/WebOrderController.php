<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\EcommerceSetting;
use App\Models\SalesInvoice;
use App\Models\Warehouse;
use App\Models\WebOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class WebOrderController extends Controller
{
    /**
     * Web Orders Listing (ERP Back-office).
     */
    public function index(Request $request): View
    {
        $status = $request->query('status', 'all');
        $search = $request->query('search');

        $query = WebOrder::with(['items.product', 'salesInvoice'])->latest('id');

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'LIKE', "%{$search}%")
                    ->orWhere('customer_name', 'LIKE', "%{$search}%")
                    ->orWhere('customer_phone', 'LIKE', "%{$search}%")
                    ->orWhere('customer_city', 'LIKE', "%{$search}%");
            });
        }

        $orders = $query->paginate(15)->withQueryString();

        // Status counts for badge tabs
        $counts = [
            'all' => WebOrder::count(),
            'pending' => WebOrder::where('status', 'pending')->count(),
            'confirmed' => WebOrder::where('status', 'confirmed')->count(),
            'processing' => WebOrder::where('status', 'processing')->count(),
            'shipped' => WebOrder::where('status', 'shipped')->count(),
            'delivered' => WebOrder::where('status', 'delivered')->count(),
            'cancelled' => WebOrder::where('status', 'cancelled')->count(),
        ];

        return view('ecommerce.orders.index', compact('orders', 'counts', 'status', 'search'));
    }

    /**
     * View single web order.
     */
    public function show(WebOrder $order): View
    {
        $order->load(['items.product', 'salesInvoice']);
        $warehouses = Warehouse::where('is_active', true)->get();

        return view('ecommerce.orders.show', compact('order', 'warehouses'));
    }

    /**
     * Update order status or payment status.
     */
    public function updateStatus(Request $request, WebOrder $order): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,processing,shipped,delivered,cancelled',
            'payment_status' => 'required|in:unpaid,paid',
        ]);

        $order->update($validated);

        return back()->with('success', "تم تحديث حالة الطلب #{$order->order_number} بنجاح");
    }

    /**
     * Convert Web Order to Official Sales Invoice.
     */
    public function convertToInvoice(Request $request, WebOrder $order): RedirectResponse
    {
        if ($order->sales_invoice_id) {
            return back()->with('error', 'تم إصدار فاتورة مبيعات لهذا الطلب بالفعل');
        }

        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
        ]);

        return DB::transaction(function () use ($request, $order) {
            // Find or create customer
            $customer = Customer::where('phone', $order->customer_phone)->first();
            if (! $customer) {
                $customer = Customer::create([
                    'name' => $order->customer_name,
                    'phone' => $order->customer_phone,
                    'city' => $order->customer_city,
                    'address' => $order->customer_address,
                    'customer_type' => 'retail',
                    'pricing_tier' => 'retail',
                    'approval_status' => Customer::APPROVAL_APPROVED,
                    'is_active' => true,
                ]);
            }

            // Generate invoice number
            $invPrefix = 'INV-'.date('Ymd').'-';
            $lastInv = SalesInvoice::where('invoice_number', 'LIKE', $invPrefix.'%')->orderByDesc('id')->first();
            $seq = 1;
            if ($lastInv && preg_match('/-(\d+)$/', $lastInv->invoice_number, $m)) {
                $seq = ((int) $m[1]) + 1;
            }
            $invNumber = $invPrefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);

            // Create sales invoice
            $invoice = SalesInvoice::create([
                'invoice_number' => $invNumber,
                'customer_id' => $customer->id,
                'warehouse_id' => $request->warehouse_id,
                'invoice_date' => now()->toDateString(),
                'price_tier' => 'retail',
                'payment_type' => $order->payment_method === 'cod' ? SalesInvoice::PAYMENT_CASH : SalesInvoice::PAYMENT_CASH,
                'status' => SalesInvoice::STATUS_DRAFT,
                'subtotal' => $order->subtotal,
                'shipping_cost' => $order->shipping_cost,
                'discount_amount' => $order->discount_amount,
                'total_amount' => $order->total,
                'paid_amount' => $order->payment_status === 'paid' ? $order->total : 0,
                'remaining_amount' => $order->payment_status === 'paid' ? 0 : $order->total,
                'notes' => "طلب متجر إلكتروني رقم: {$order->order_number}\nملاحظات العميل: {$order->customer_notes}",
            ]);

            // Add items
            foreach ($order->items as $item) {
                $invoice->items()->create([
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'subtotal' => $item->subtotal,
                    'total' => $item->subtotal,
                ]);
            }

            // Link to order
            $order->update([
                'sales_invoice_id' => $invoice->id,
                'status' => 'confirmed',
            ]);

            return redirect()->route('ecommerce.orders.show', $order)
                ->with('success', "تم تحويل الطلب إلى فاتورة مبيعات رقم {$invNumber} بنجاح!");
        });
    }

    /**
     * E-Commerce Storefront Settings page.
     */
    public function settings(): View
    {
        $settings = EcommerceSetting::getSettings();

        return view('ecommerce.settings', compact('settings'));
    }

    /**
     * Update Storefront Settings.
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'store_name' => 'nullable|string|max:150',
            'store_tagline' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'contact_whatsapp' => 'nullable|string|max:50',
            'shipping_flat_rate' => 'required|numeric|min:0',
            'free_shipping_threshold' => 'required|numeric|min:0',
            'is_store_open' => 'nullable|boolean',
            'allow_cod' => 'nullable|boolean',
            'announcement_text' => 'nullable|string|max:255',
        ]);

        $validated['is_store_open'] = $request->has('is_store_open');
        $validated['allow_cod'] = $request->has('allow_cod');

        $settings = EcommerceSetting::getSettings();
        $settings->update($validated);

        return back()->with('success', 'تم حفظ إعدادات المتجر الإلكتروني بنجاح');
    }
}
