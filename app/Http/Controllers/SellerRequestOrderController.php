<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SellerRequestOrderController extends Controller
{
    /**
     * Seller Request Orders - Product Wise
     */
    public function index(Request $request)
    {
        $search = $request->search;

        $productRequests = OrderDetail::query()
            ->join('orders', 'orders.id', '=', 'order_details.order_id')
            ->where('orders.order_from', 'seller_panel')
            ->select(
                'order_details.product_id',
                DB::raw('SUM(order_details.quantity) as total_quantity'),
                DB::raw('COUNT(DISTINCT orders.user_id) as total_sellers'),
                DB::raw('COUNT(DISTINCT orders.id) as total_orders')
            )
            ->groupBy('order_details.product_id');

        /*
        |--------------------------------------------------------------------------
        | Search Product
        |--------------------------------------------------------------------------
        */
        if ($search != null) {
            $productRequests->whereHas('product', function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            });
        }

        $productRequests = $productRequests
            ->orderByDesc('total_quantity')
            ->paginate(15);

        // Load product relationship
        $productRequests->load('product');

        return view(
            'backend.sellers.request_orders.index',
            compact('productRequests', 'search')
        );
    }


    /**
     * Product wise seller requests
     */
    public function show($product_id)
{
    $product = \App\Models\Product::findOrFail($product_id);

    $sellerRequests = OrderDetail::query()
        ->join('orders', 'orders.id', '=', 'order_details.order_id')
        ->leftJoin('users', 'users.id', '=', 'orders.user_id')
        ->where('orders.order_from', 'seller_panel')
        ->where('order_details.product_id', $product_id)
        ->select(
            'orders.id as order_id',
            'orders.code as order_code',
            'orders.user_id as seller_id',
            'users.name as seller_name',
            'order_details.quantity',
            'order_details.price',
            'orders.delivery_status',
            'orders.payment_status',
            'orders.created_at'
        )
        ->orderByDesc('orders.id')
        ->get();

    $totalQuantity = $sellerRequests->sum('quantity');

    $totalSellers = $sellerRequests
        ->whereNotNull('seller_id')
        ->pluck('seller_id')
        ->unique()
        ->count();

    return view(
        'backend.sellers.request_orders.show',
        compact(
            'product',
            'sellerRequests',
            'totalQuantity',
            'totalSellers'
        )
    );
}
}
