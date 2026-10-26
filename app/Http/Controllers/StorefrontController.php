<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\EcommerceSetting;
use App\Models\Product;
use App\Models\WebOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StorefrontController extends Controller
{
    /**
     * Storefront Catalog / Home Page.
     */
    public function catalog(Request $request): View
    {
        $settings = EcommerceSetting::getSettings();

        $query = Product::query()
            ->availableOnline()
            ->with(['category', 'stockBalances']);

        // Search filter
        if ($request->filled('search')) {
            $term = trim($request->query('search'));
            $query->where(function ($q) use ($term) {
                $q->where('name', 'LIKE', "%{$term}%")
                    ->orWhere('sku', 'LIKE', "%{$term}%")
                    ->orWhere('barcode', 'LIKE', "%{$term}%");
            });
        }

        // Category filter
        if ($request->filled('category')) {
            $query->where('category_id', $request->query('category'));
        }

        $products = $query->latest('id')->paginate(16)->withQueryString();

        $categories = Category::whereHas('products', function ($q) {
            $q->where('is_available_online', true)->where('is_active', true);
        })->get();

        $cart = session()->get('shop_cart', []);
        $cartCount = array_sum(array_column($cart, 'quantity'));

        return view('shop.index', compact('settings', 'products', 'categories', 'cart', 'cartCount'));
    }

    /**
     * Single product details page.
     */
    public function product(Product $product): View
    {
        if (! $product->is_available_online || ! $product->is_active) {
            abort(404, 'المنتج غير متوفر في المتجر');
        }

        $product->load(['category', 'stockBalances']);
        $settings = EcommerceSetting::getSettings();

        // Related products in same category
        $relatedProducts = Product::availableOnline()
            ->where('id', '!=', $product->id)
            ->where('category_id', $product->category_id)
            ->take(4)
            ->get();

        $cart = session()->get('shop_cart', []);
        $cartCount = array_sum(array_column($cart, 'quantity'));

        return view('shop.product', compact('product', 'settings', 'relatedProducts', 'cart', 'cartCount'));
    }

    /**
     * View shopping cart.
     */
    public function cart(): View
    {
        $settings = EcommerceSetting::getSettings();
        $cart = session()->get('shop_cart', []);

        $subtotal = 0;
        foreach ($cart as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }

        $shipping = 0;
        if ($subtotal > 0) {
            $shipping = ($settings->free_shipping_threshold > 0 && $subtotal >= (float) $settings->free_shipping_threshold)
                ? 0
                : (float) $settings->shipping_flat_rate;
        }

        $total = $subtotal + $shipping;

        return view('shop.cart', compact('settings', 'cart', 'subtotal', 'shipping', 'total'));
    }

    /**
     * Add product to shopping cart.
     */
    public function addToCart(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'nullable|numeric|min:1',
        ]);

        $product = Product::with('stockBalances')->findOrFail($request->product_id);

        if (! $product->is_available_online || ! $product->is_active) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'المنتج غير متاح للشراء أونلاين'], 422);
            }

            return back()->with('error', 'المنتج غير متاح للشراء أونلاين');
        }

        $requestedQty = (float) ($request->quantity ?? 1);
        $cart = session()->get('shop_cart', []);
        $currentCartQty = isset($cart[$product->id]) ? (float) $cart[$product->id]['quantity'] : 0;
        $newQty = $currentCartQty + $requestedQty;

        if ($newQty > $product->total_stock) {
            $msg = "عفواً، الكمية المطلوبة غير متوفرة بالمخزن. المتوفر حالياً: {$product->total_stock} {$product->unit}";
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            return back()->with('error', $msg);
        }

        $cart[$product->id] = [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'unit' => $product->unit ?? 'قطعة',
            'price' => $product->effective_online_price,
            'quantity' => $newQty,
            'image' => $product->image_path,
            'max_stock' => $product->total_stock,
        ];

        session()->put('shop_cart', $cart);
        $totalItems = array_sum(array_column($cart, 'quantity'));

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تمت إضافة المنتج إلى السلة بنجاح',
                'cart_count' => $totalItems,
            ]);
        }

        return back()->with('success', 'تمت إضافة المنتج إلى السلة');
    }

    /**
     * Update quantity of item in cart.
     */
    public function updateCart(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:0',
        ]);

        $cart = session()->get('shop_cart', []);
        $productId = $request->product_id;

        if (! isset($cart[$productId])) {
            return back();
        }

        $qty = (float) $request->quantity;

        if ($qty <= 0) {
            unset($cart[$productId]);
        } else {
            $product = Product::with('stockBalances')->find($productId);
            if ($qty > $product->total_stock) {
                $qty = $product->total_stock;
                session()->flash('warning', "تم تعديل الكمية لأقصى رصيد متاح ({$qty})");
            }
            $cart[$productId]['quantity'] = $qty;
        }

        session()->put('shop_cart', $cart);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'cart' => $cart]);
        }

        return redirect()->route('shop.cart')->with('success', 'تم تحديث السلة بنجاح');
    }

    /**
     * Remove item from cart.
     */
    public function removeFromCart(Request $request): RedirectResponse
    {
        $request->validate(['product_id' => 'required|exists:products,id']);

        $cart = session()->get('shop_cart', []);
        unset($cart[$request->product_id]);
        session()->put('shop_cart', $cart);

        return redirect()->route('shop.cart')->with('success', 'تم حذف المنتج من السلة');
    }

    /**
     * Checkout page.
     */
    public function checkout(): View|RedirectResponse
    {
        $cart = session()->get('shop_cart', []);
        if (empty($cart)) {
            return redirect()->route('shop.catalog')->with('error', 'سلة الشراء فارغة، برجاء إضافة منتجات أولاً');
        }

        $settings = EcommerceSetting::getSettings();
        if (! $settings->is_store_open) {
            return redirect()->route('shop.catalog')->with('error', 'المتجر مغلق حالياً لاستقبال الطلبات');
        }

        $subtotal = 0;
        foreach ($cart as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }

        $shipping = ($settings->free_shipping_threshold > 0 && $subtotal >= (float) $settings->free_shipping_threshold)
            ? 0
            : (float) $settings->shipping_flat_rate;

        $total = $subtotal + $shipping;

        return view('shop.checkout', compact('settings', 'cart', 'subtotal', 'shipping', 'total'));
    }

    /**
     * Place an online order.
     */
    public function placeOrder(Request $request): RedirectResponse
    {
        $cart = session()->get('shop_cart', []);
        if (empty($cart)) {
            return redirect()->route('shop.catalog')->with('error', 'السلة فارغة');
        }

        $settings = EcommerceSetting::getSettings();
        if (! $settings->is_store_open) {
            return redirect()->route('shop.catalog')->with('error', 'المتجر مغلق حالياً');
        }

        $validated = $request->validate([
            'customer_name' => 'required|string|max:150',
            'customer_phone' => 'required|string|max:50',
            'customer_email' => 'nullable|email|max:150',
            'customer_city' => 'nullable|string|max:100',
            'customer_address' => 'required|string|max:500',
            'customer_notes' => 'nullable|string|max:1000',
            'payment_method' => 'required|in:cod,google_pay,online',
        ], [
            'customer_name.required' => 'الاسم مطلوب',
            'customer_phone.required' => 'رقم الهاتف مطلوب للتواصل وتوصيل الطلب',
            'customer_address.required' => 'عنوان التوصيل بالتفصيل مطلوب',
        ]);

        return DB::transaction(function () use ($validated, $cart, $settings) {
            $subtotal = 0;
            $itemsToCreate = [];

            foreach ($cart as $productId => $item) {
                $product = Product::with('stockBalances')->lockForUpdate()->find($productId);

                if (! $product || ! $product->is_available_online || ! $product->is_active) {
                    throw new \Exception("المنتج {$item['name']} لم يعد متوفراً");
                }

                if ($item['quantity'] > $product->total_stock) {
                    throw new \Exception("الكمية المطلوبة من {$item['name']} تتجاوز المتوفر بالمخزن ({$product->total_stock})");
                }

                $price = $product->effective_online_price;
                $lineTotal = $price * $item['quantity'];
                $subtotal += $lineTotal;

                $itemsToCreate[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'unit' => $product->unit ?? 'قطعة',
                    'quantity' => $item['quantity'],
                    'unit_price' => $price,
                    'subtotal' => $lineTotal,
                ];
            }

            $shipping = ($settings->free_shipping_threshold > 0 && $subtotal >= (float) $settings->free_shipping_threshold)
                ? 0
                : (float) $settings->shipping_flat_rate;

            $total = $subtotal + $shipping;

            $order = WebOrder::create([
                'order_number' => WebOrder::generateOrderNumber(),
                'customer_name' => $validated['customer_name'],
                'customer_phone' => $validated['customer_phone'],
                'customer_email' => $validated['customer_email'] ?? null,
                'customer_city' => $validated['customer_city'] ?? null,
                'customer_address' => $validated['customer_address'],
                'customer_notes' => $validated['customer_notes'] ?? null,
                'subtotal' => $subtotal,
                'shipping_cost' => $shipping,
                'discount_amount' => 0,
                'total' => $total,
                'status' => 'pending',
                'payment_method' => $validated['payment_method'],
                'payment_status' => $validated['payment_method'] === 'cod' ? 'unpaid' : 'paid',
            ]);

            foreach ($itemsToCreate as $itemData) {
                $order->items()->create($itemData);
            }

            // Clear cart
            session()->forget('shop_cart');

            return redirect()->route('shop.order.success', $order->order_number)
                ->with('order_id', $order->id);
        });
    }

    /**
     * Order confirmation / success tracking page.
     */
    public function orderSuccess(string $orderNumber): View
    {
        $order = WebOrder::with('items.product')->where('order_number', $orderNumber)->firstOrFail();
        $settings = EcommerceSetting::getSettings();

        return view('shop.order-success', compact('order', 'settings'));
    }
}
