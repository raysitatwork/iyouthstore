<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\SellerPayments;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        // Get Categories
        $categories = \App\Models\Category::where('parent_id', 0)->get();

        // Base Query 
        $query = Product::query()
            ->leftJoin('seller_products as sp', function ($join) {
                $join->on('products.id', '=', 'sp.product_id')
                    ->where('sp.seller_id', auth()->id());
            })
            ->where('products.added_by', 'admin')
            ->select('products.*', 'sp.stock as product_stock')
            ->isApprovedPublished();

        // Category Filter
        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        $products = $query->latest()->paginate(30);

        return view('seller.buy_product.index', compact('products', 'categories'));
    }

    public function comming_soon()
    {
        $coming_soon_products = Product::with('thumbnail')
            ->where('coming_soon', 1)
            ->orderBy('id', 'desc')
            ->get();
        return view('seller.buy_product.comming_soon',compact('coming_soon_products'));
    }


    // public function sellerAddToCart(Request $request)
    // {

    //     $product = Product::findOrFail($request->product_id);

    //     $qty = $request->qty ?? 1;

    //     $stock = $product->stocks->sum('qty');

    //     if ($qty > $stock) {
    //         return back()->with('error', 'Quantity exceeds available stock');
    //     }

    //     Cart::updateOrCreate(
    //         [
    //             'user_id' => auth()->id(),
    //             'product_id' => $product->id,
    //         ],
    //         [
    //             'price' => $product->unit_price,
    //             'quantity' => $qty,
    //         ]
    //     );
    //     return back()->with('success', 'Product added to cart');
    // }


    // public function sellerAddToCart(Request $request)
    // {
    //     $product = Product::findOrFail($request->product_id);

    //     $qty = $request->qty ?? 1;

    //     $stock = $product->stocks->sum('qty');

    //     // Stock check
    //     if ($qty > $stock) {
    //         return back()->with('error', 'Quantity exceeds available stock');
    //     }

    //     // Check if already in cart
    //     $cart = Cart::where('user_id', auth()->id())
    //         ->where('product_id', $product->id)
    //         ->first();

    //     if ($cart) {
    //         return back()->with('error', 'Product already in cart. Please update quantity from cart page.');
    //     }

    //     // Create new cart item
    //     Cart::create([
    //         'user_id' => auth()->id(),
    //         'product_id' => $product->id,
    //         'price' => $product->unit_price,
    //         'quantity' => $qty,
    //     ]);

    //     return back()->with('success', 'Product added to cart');
    // }

    // public function sellerAddToCart(Request $request)
    // {
    //     $product = Product::findOrFail($request->product_id);

    //     $qty = $request->qty ?? 1;

    //     $stock = $product->stocks->sum('qty');

    //     if ($qty > $stock) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Quantity exceeds available stock'
    //         ]);
    //     }

    //     $cart = Cart::where('user_id', auth()->id())
    //         ->where('product_id', $product->id)
    //         ->first();

    //     if ($cart) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Already in cart. Update from cart page.'
    //         ]);
    //     }

    //     Cart::create([
    //         'user_id' => auth()->id(),
    //         'product_id' => $product->id,
    //         'price' => $product->unit_price,
    //         'quantity' => $qty,
    //     ]);

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Product added to cart'
    //     ]);
    // }


    public function sellerAddToCart(Request $request)
    {

        $shop = Shop::where('user_id', auth()->id())->firstOrFail();
        if ($shop->verification_status != 1 or $shop->registration_approval != 1) {

            return response()->json([
                'status' => false,
                'message' => 'your account is not authorized to buy products now. Please contact admin.',
            ]);
        }

        $product = Product::findOrFail($request->product_id);

        $qty = $request->qty ?? 1;

        $stock = $product->stocks->sum('qty');

        // if ($qty > $stock) {
        //     return response()->json([
        //         'status' => false,
        //         'message' => 'Quantity exceeds available stock'
        //     ]);
        // }

        $cart = Cart::where('user_id', auth()->id())
            ->where('product_id', $product->id)
            ->first();

        if ($cart) {

            $cartCount = Cart::where('user_id', auth()->id())->count();

            return response()->json([
                'status' => false,
                'message' => 'Already in cart. Update from cart page.',
                'cart_count' => $cartCount
            ]);
        }

        Cart::create([
            'user_id' => auth()->id(),
            'product_id' => $product->id,
            'owner_id' => $product->user_id,
            'price' => $product->seller_price,
            'quantity' => $qty,
        ]);

        $cartCount = Cart::where('user_id', auth()->id())->count();

        return response()->json([
            'status' => true,
            'message' => 'Product added to cart',
            'cart_count' => $cartCount
        ]);
    }



    public function cart()
    {
        $carts = Cart::where('user_id', auth()->id())
            ->with('product.thumbnail')
            ->get();

        return view('seller.buy_product.cart', compact('carts'));
    }

    // public function updateCart(Request $request)
    // {
    //     $cart = Cart::where('user_id', auth()->id())
    //         ->where('id', $request->cart_id)
    //         ->first();

    //     if (!$cart) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Cart item not found'
    //         ]);
    //     }

    //     $product = $cart->product;
    //     $stock = $product->stocks->sum('qty');

    //     if ($request->qty > $stock) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Stock not available'
    //         ]);
    //     }

    //     $cart->update([
    //         'quantity' => $request->qty
    //     ]);

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Cart updated'
    //     ]);
    // }

    public function updateCart(Request $request)
    {
        $cart = Cart::where('user_id', auth()->id())
            ->where('id', $request->cart_id)
            ->first();

        if (!$cart) {
            return response()->json([
                'status' => false,
                'message' => 'Cart item not found'
            ]);
        }

        $product = $cart->product;

        // Total stock available
        // $stock = $product->stocks->sum('qty');

        // Seller purchase limit
        // $limit = $product->seller_purchase_limit;

        // Final max allowed qty
        // $maxAllowed = $limit ? min($limit, $stock) : $stock;

        $limit = $product->seller_purchase_limit ?? 9999;
        $maxAllowed = $limit;

        // Validate against max allowed
        if ($request->qty > $maxAllowed) {
            return response()->json([
                'status' => false,
                'message' => 'Maximum allowed quantity is ' . $maxAllowed
            ]);
        }

        // Also prevent qty less than 1
        if ($request->qty < 1) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid quantity'
            ]);
        }

        // Update cart
        $cart->update([
            'quantity' => $request->qty
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Cart updated successfully'
        ]);
    }

    public function deleteCart(Request $request)
    {
        $cart = Cart::where('user_id', auth()->id())
            ->where('id', $request->cart_id)
            ->first();

        if (!$cart) {
            return response()->json([
                'status' => false,
                'message' => 'Cart item not found'
            ]);
        }

        $cart->delete();

        return response()->json([
            'status' => true,
            'message' => 'Removed from cart'
        ]);
    }

    public function checkout()
    {
        try {
            DB::beginTransaction();

            $seller = Auth::user();
            $carts = Cart::where('user_id', $seller->id)->get();

            if ($carts->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Cart is empty'
                ]);
            }

            $total = 0;
            foreach ($carts as $cart) {
                $total += $cart->price * $cart->quantity;
            }

            $combined_order_id = DB::table('combined_orders')->insertGetId([
                'user_id' => $seller->id,
                'shipping_address' => null,
                'grand_total' => $total,
                'created_at' => now(),
                'updated_at' => now()
            ]);


            $order = new Order();
            $order->user_id = $seller->id;
            $order->seller_id = $carts->first()->owner_id;
            $order->combined_order_id = $combined_order_id;
            $order->shipping_type = "home_delivery";
            $order->order_from = "seller_panel";
            $order->pickup_point_id = 0;
            $order->payment_type = "manual_payment";
            $order->payment_status = "unpaid";
            $order->delivery_status = "pending";
            $order->grand_total = $total;
            $order->coupon_discount = 0;
            $order->code = date('YmdHis');
            $order->date = time();
            $order->save();

            foreach ($carts as $cart) {
                $detail = new OrderDetail();
                $detail->order_id = $order->id;
                $detail->seller_id = $cart->owner_id;
                $detail->product_id = $cart->product_id;
                $detail->variation = $cart->variation;
                // $detail->price = $cart->price;
                $detail->price = $cart->price * $cart->quantity;
                $detail->quantity = $cart->quantity;
                $detail->tax = 0;
                $detail->shipping_cost = 0;
                $detail->payment_status = "unpaid";
                $detail->delivery_status = "pending";
                $detail->save();
            }

            Cart::where('user_id', $seller->id)->delete();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Order placed successfully',
                'order_id' => $order->id
            ]);
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    public function myPurchases()
    {
        // $orders = Order::where('user_id', Auth::id())
        //     ->where('order_from', 'seller_panel')
        //     ->latest()
        //     ->paginate(15);

        $orders = Order::where('user_id', Auth::id())
            ->where('order_from', 'seller_panel')

            ->when(request('search'), function ($query) {
                $query->where('code', 'like', '%' . request('search') . '%');
            })

            ->latest()
            ->paginate(15)
            ->appends(request()->query());

        return view('seller.buy_product.my_purchases', compact('orders'));
    }
    public function showPurchase($id)
    {
        $order = Order::with('orderDetails.product')
            ->where('user_id', auth()->id())
            ->findOrFail(decrypt($id));

        $payment = SellerPayments::where('order_id', $order->id)
            ->latest()
            ->first();


        return view('seller.buy_product.purchase_show', compact('order', 'payment'));
    }

    public function ensureAuthorizedSeller($sellerId)
    {
        $shop = Shop::where('user_id', $sellerId)->firstOrFail();

        if ($shop->verification_status != 1 || $shop->registration_approval != 1) {
            throw new RuntimeException('your account is not authorized to buy products now. Please contact admin.');
        }

        return $shop;
    }

    public function createSellerPanelOrderForUser($seller)
    {
        $carts = Cart::where('user_id', $seller->id)->get();

        if ($carts->isEmpty()) {
            throw new RuntimeException('Cart is empty');
        }

        $total = $carts->sum(function ($cart) {
            return $cart->price * $cart->quantity;
        });

        $combined_order_id = DB::table('combined_orders')->insertGetId([
            'user_id' => $seller->id,
            'shipping_address' => null,
            'grand_total' => $total,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $order = new Order();
        $order->user_id = $seller->id;
        $order->seller_id = $carts->first()->owner_id;
        $order->combined_order_id = $combined_order_id;
        $order->shipping_type = 'home_delivery';
        $order->order_from = 'seller_panel';
        $order->pickup_point_id = 0;
        $order->payment_type = 'manual_payment';
        $order->payment_status = 'unpaid';
        $order->delivery_status = 'pending';
        $order->grand_total = $total;
        $order->coupon_discount = 0;
        $order->code = date('YmdHis');
        $order->date = time();
        $order->save();

        foreach ($carts as $cart) {
            $detail = new OrderDetail();
            $detail->order_id = $order->id;
            $detail->seller_id = $cart->owner_id;
            $detail->product_id = $cart->product_id;
            $detail->variation = $cart->variation;
            $detail->price = $cart->price;
            $detail->quantity = $cart->quantity;
            $detail->tax = 0;
            $detail->shipping_cost = 0;
            $detail->payment_status = 'unpaid';
            $detail->delivery_status = 'pending';
            $detail->save();
        }

        Cart::where('user_id', $seller->id)->delete();

        return $order;
    }

    public function showPaymentDetails(Request $request)
    {
        $orderId = $request->order_id;

        if (!$orderId) {
            return redirect()->route('seller.cart')->with('error', 'Invalid access');
        }

        $order = Order::findOrFail($orderId);

        return view('seller.buy_product.payment', compact('order', 'orderId'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'utr' => 'required|string|max:150',
            'payment_date' => 'required|date',
            'screenshot' => 'required|file|mimes:jpg,jpeg,png,pdf|max:4096',
            'order_id' => 'required|exists:orders,id'
        ]);

        DB::beginTransaction();

        try {


            $screenshotPath = null;

            if ($request->hasFile('screenshot')) {
                $directory = public_path('uploads/all/seller-payments');

                if (!file_exists($directory)) {
                    mkdir($directory, 0755, true);
                }

                $file = $request->file('screenshot');
                $fileName = 'payment-' . time() . '.' . $file->getClientOriginalExtension();
                $file->move($directory, $fileName);

                $screenshotPath = 'uploads/all/seller-payments/' . $fileName;
            }

            SellerPayments::create([
                'user_id' => Auth::id(),
                'order_id' => $request->order_id,
                'payment_method' => 'manual_payment',
                'utr' => $request->utr,
                'payment_date' => $request->payment_date,
                'screenshot' => $screenshotPath,
                'note' => $request->note,
                'status' => 'pending',
            ]);

            DB::commit();

            return redirect()->route('seller.my-purchases')
                ->with('success', 'Payment submitted successfully');
        } catch (\Exception $e) {

            DB::rollBack();

            return back()->with('error', $e->getMessage());
        }
    }
}
