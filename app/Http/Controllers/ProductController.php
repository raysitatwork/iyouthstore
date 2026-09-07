<?php

namespace App\Http\Controllers;

use AizPackages\CombinationGenerate\Services\CombinationService;
use App\Http\Requests\ProductRequest;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductTranslation;
use App\Models\Category;
use App\Models\AttributeValue;
use App\Models\Cart;
use App\Models\CurrentStockExport;
use App\Models\OutOfStockRequest;
use App\Models\ProductStock;
use App\Models\ProductCategory;
use App\Models\SellerProduct;
use App\Models\Review;
use App\Models\SellerProductAssignment;
use App\Models\SellerProductImport;
use App\Models\Shop;
use DB;
use App\Models\Wishlist;
use App\Models\User;
use App\Notifications\ShopProductNotification;
use Carbon\Carbon;
use CoreComponentRepository;
use Artisan;
use Cache;
use App\Services\ProductService;
use App\Services\ProductTaxService;
use App\Services\ProductFlashDealService;
use App\Services\ProductStockService;
use App\Services\FrequentlyBoughtProductService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;
use Maatwebsite\Excel\Facades\Excel;

class ProductController extends Controller
{
    protected $productService;
    protected $productTaxService;
    protected $productFlashDealService;
    protected $productStockService;
    protected $frequentlyBoughtProductService;

    public function __construct(
        ProductService $productService,
        ProductTaxService $productTaxService,
        ProductFlashDealService $productFlashDealService,
        ProductStockService $productStockService,
        FrequentlyBoughtProductService $frequentlyBoughtProductService
    ) {
        $this->productService = $productService;
        $this->productTaxService = $productTaxService;
        $this->productFlashDealService = $productFlashDealService;
        $this->productStockService = $productStockService;
        $this->frequentlyBoughtProductService = $frequentlyBoughtProductService;

        // Staff Permission Check
        $this->middleware(['permission:add_new_product'])->only('create');
        $this->middleware(['permission:show_all_products'])->only('all_products');
        $this->middleware(['permission:show_in_house_products'])->only('admin_products');
        $this->middleware(['permission:show_seller_products'])->only('seller_products');
        $this->middleware(['permission:product_edit'])->only('admin_product_edit', 'seller_product_edit');
        $this->middleware(['permission:product_duplicate'])->only('duplicate');
        $this->middleware(['permission:product_delete'])->only('destroy');
        $this->middleware(['permission:set_category_wise_discount'])->only('categoriesWiseProductDiscount');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function admin_products(Request $request)
    {
        CoreComponentRepository::instantiateShopRepository();

        $type = 'In House';
        $col_name = null;
        $query = null;
        $sort_search = null;

        $products = Product::where('added_by', 'admin')->where('auction_product', 0)->where('wholesale_product', 0);

        if ($request->type != null) {
            $var = explode(",", $request->type);
            $col_name = $var[0];
            $query = $var[1];
            $products = $products->orderBy($col_name, $query);
            $sort_type = $request->type;
        }
        if ($request->search != null) {
            $sort_search = $request->search;
            $products = $products
                ->where('name', 'like', '%' . $sort_search . '%')
                ->orWhereHas('stocks', function ($q) use ($sort_search) {
                    $q->where('sku', 'like', '%' . $sort_search . '%');
                });
        }

        $products = $products->where('digital', 0)->orderBy('created_at', 'desc')->paginate(15);

        return view('backend.product.products.index', compact('products', 'type', 'col_name', 'query', 'sort_search'));
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function seller_products(Request $request, $product_type)
    {
        $col_name = null;
        $query = null;
        $seller_id = null;
        $sort_search = null;
        $products = Product::where('added_by', 'seller')->where('auction_product', 0)->where('wholesale_product', 0);
        if ($request->has('user_id') && $request->user_id != null) {
            $products = $products->where('user_id', $request->user_id);
            $seller_id = $request->user_id;
        }
        if ($request->search != null) {
            $products = $products
                ->where('name', 'like', '%' . $request->search . '%');
            $sort_search = $request->search;
        }
        if ($request->type != null) {
            $var = explode(",", $request->type);
            $col_name = $var[0];
            $query = $var[1];
            $products = $products->orderBy($col_name, $query);
            $sort_type = $request->type;
        }
        $products = $product_type == 'physical' ? $products->where('digital', 0) : $products->where('digital', 1);
        $products = $products->orderBy('created_at', 'desc')->paginate(15);
        $type = 'Seller';

        if ($product_type == 'digital') {
            return view('backend.product.digital_products.index', compact('products', 'sort_search', 'type'));
        }
        return view('backend.product.products.index', compact('products', 'type', 'col_name', 'query', 'seller_id', 'sort_search'));
    }

    public function all_products(Request $request)
    {
        $col_name = null;
        $query = null;
        $seller_id = null;
        $sort_search = null;
        $products = Product::where('auction_product', 0)->where('wholesale_product', 0);
        if (get_setting('vendor_system_activation') != 1) {
            $products = $products->where('added_by', 'admin');
        }
        if ($request->has('user_id') && $request->user_id != null) {
            $products = $products->where('user_id', $request->user_id);
            $seller_id = $request->user_id;
        }
        if ($request->search != null) {
            $sort_search = $request->search;
            $products = $products
                ->where('name', 'like', '%' . $sort_search . '%')
                ->orWhereHas('stocks', function ($q) use ($sort_search) {
                    $q->where('sku', 'like', '%' . $sort_search . '%');
                });
        }
        if ($request->type != null) {
            $var = explode(",", $request->type);
            $col_name = $var[0];
            $query = $var[1];
            $products = $products->orderBy($col_name, $query);
            $sort_type = $request->type;
        }

        $products = $products->orderBy('created_at', 'desc')->paginate(15);
        $type = 'All';

        return view('backend.product.products.index', compact('products', 'type', 'col_name', 'query', 'seller_id', 'sort_search'));
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        CoreComponentRepository::initializeCache();

        $categories = Category::where('parent_id', 0)
            ->where('digital', 0)
            ->with('childrenCategories')
            ->get();

        return view('backend.product.products.create', compact('categories'));
    }

    public function importSellerProducts(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',

            'file' => [
                'required',
                'file',
                'mimes:xlsx,xls,csv',
                'max:10240',
            ],
        ]);


        try {

            Excel::import(
                new SellerProductImport($request->user_id),
                $request->file('file')
            );


            return redirect()
                ->back()
                ->with(
                    'success',
                    'All products imported and assigned to seller successfully.'
                );
        } catch (\Throwable $e) {

            return redirect()
                ->back()
                ->with(
                    'error',
                    $e->getMessage()
                );
        }
    }

    public function exportCurrentStock()
    {
        return Excel::download(
            new CurrentStockExport(),
            'current_stock_assignment.xlsx'
        );
    }

    public function exportAssignmentProducts()
    {
        return Excel::download(
            new \App\Models\SellerProductAssignmentExport(),
            'seller_product_assignment.xlsx'
        );
    }

    public function assign()
    {

        $shops = Shop::with('user')->get();

        $products = Product::where('added_by', 'admin')->where('auction_product', 0)->where('wholesale_product', 0)->where('digital', 0)->orderBy('created_at', 'desc')->get();

        return view('backend.product.products.assign', compact('shops', 'products'));
    }


    public function assignProduct(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'products' => 'required|array'
        ]);

        DB::beginTransaction();

        try {

            $assignedProducts = [];
            $failedProducts   = [];

            foreach ($request->products as $item) {

                $product = Product::lockForUpdate()->find($item['product_id']);

                if (!$product) {
                    $failedProducts[] = $item['product_id'];
                    continue;
                }

                // if ($product->current_stock < $item['quantity']) {
                //     $failedProducts[] = $item['product_id'];
                //     continue;
                // }

                $product->decrement('current_stock', $item['quantity']);

                ProductStock::where('product_id', $item['product_id'])
                    ->decrement('qty', $item['quantity']);

                $sellerProduct = SellerProduct::firstOrNew([
                    'seller_id' => $request->user_id,
                    'product_id' => $item['product_id']
                ]);

                $sellerProduct->stock =
                    ($sellerProduct->stock ?? 0) + $item['quantity'];

                $sellerProduct->save();

                SellerProductAssignment::create([
                    'seller_id' => $request->user_id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity']
                ]);

                $assignedProducts[] = $item['product_id'];
            }

            DB::commit();

            if (!empty($failedProducts)) {

                return redirect()->back()->with(
                    'error',
                    'Some products skipped due to insufficient stock: '
                        . implode(', ', $failedProducts)
                );
            }

            return redirect()->back()
                ->with('success', 'All products assigned successfully');
        } catch (\Exception $e) {

            DB::rollBack();

            return redirect()->back()
                ->with('error', 'Something went wrong');
        }
    }


    public function add_more_choice_option(Request $request)
    {
        $all_attribute_values = AttributeValue::with('attribute')->where('attribute_id', $request->attribute_id)->get();

        $html = '';

        foreach ($all_attribute_values as $row) {
            $html .= '<option value="' . $row->value . '">' . $row->value . '</option>';
        }

        echo json_encode($html);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    //change
    public function store(ProductRequest $request)
    {
        // Coming Soon checkbox:
        // checked = 1
        // unchecked = 0
        $request->merge([
            'coming_soon' => $request->has('coming_soon') ? 1 : 0,
        ]);

        $product = $this->productService->store($request->except([
            '_token',
            'sku',
            'choice',
            'tax_id',
            'tax',
            'tax_type',
            'flash_deal_id',
            'flash_discount',
            'flash_discount_type'
        ]));
        $request->merge(['product_id' => $product->id]);

        //Product categories
        $product->categories()->attach($request->category_ids);

        //VAT & Tax
        if ($request->tax_id) {
            $this->productTaxService->store($request->only([
                'tax_id',
                'tax',
                'tax_type',
                'product_id'
            ]));
        }

        //Flash Deal
        $this->productFlashDealService->store($request->only([
            'flash_deal_id',
            'flash_discount',
            'flash_discount_type'
        ]), $product);

        //Product Stock
        $this->productStockService->store($request->only([
            'colors_active',
            'colors',
            'choice_no',
            'unit_price',
            'seller_price',
            'sku',
            'current_stock',
            'product_id'
        ]), $product);

        // Frequently Bought Products
        $this->frequentlyBoughtProductService->store($request->only([
            'product_id',
            'frequently_bought_selection_type',
            'fq_bought_product_ids',
            'fq_bought_product_category_id'
        ]));

        // Product Translations
        $request->merge(['lang' => env('DEFAULT_LANGUAGE')]);
        ProductTranslation::create($request->only([
            'lang',
            'name',
            'unit',
            'description',
            'product_id'
        ]));

        flash(translate('Product has been inserted successfully'))->success();

        Artisan::call('view:clear');
        Artisan::call('cache:clear');

        return redirect()->route('products.admin');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function admin_product_edit(Request $request, $id)
    {
        CoreComponentRepository::initializeCache();

        $product = Product::findOrFail($id);
        if ($product->digital == 1) {
            return redirect('admin/digitalproducts/' . $id . '/edit');
        }

        $lang = $request->lang;
        $tags = json_decode($product->tags);
        $categories = Category::where('parent_id', 0)
            ->where('digital', 0)
            ->with('childrenCategories')
            ->get();
        return view('backend.product.products.edit', compact('product', 'categories', 'tags', 'lang'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function seller_product_edit(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        if ($product->digital == 1) {
            return redirect('digitalproducts/' . $id . '/edit');
        }
        $lang = $request->lang;
        $tags = json_decode($product->tags);
        // $categories = Category::all();
        $categories = Category::where('parent_id', 0)
            ->where('digital', 0)
            ->with('childrenCategories')
            ->get();

        return view('backend.product.products.edit', compact('product', 'categories', 'tags', 'lang'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    //change
    public function update(ProductRequest $request, Product $product)
    {
        // Coming Soon checkbox:
        // checked = 1
        // unchecked = 0
        $request->merge([
            'coming_soon' => $request->has('coming_soon') ? 1 : 0,
        ]);

        //Product
        $product = $this->productService->update($request->except([
            '_token',
            'sku',
            'choice',
            'tax_id',
            'tax',
            'tax_type',
            'flash_deal_id',
            'flash_discount',
            'flash_discount_type'
        ]), $product);

        $request->merge(['product_id' => $product->id]);

        //Product categories
        $product->categories()->sync($request->category_ids);


        //Product Stock
        $product->stocks()->delete();
        $this->productStockService->store($request->only([
            'colors_active',
            'colors',
            'choice_no',
            'unit_price',
            'sku',
            'current_stock',
            'product_id'
        ]), $product);

        //Flash Deal
        $this->productFlashDealService->store($request->only([
            'flash_deal_id',
            'flash_discount',
            'flash_discount_type'
        ]), $product);

        //VAT & Tax
        if ($request->tax_id) {
            $product->taxes()->delete();
            $this->productTaxService->store($request->only([
                'tax_id',
                'tax',
                'tax_type',
                'product_id'
            ]));
        }

        // Frequently Bought Products
        $product->frequently_bought_products()->delete();
        $this->frequentlyBoughtProductService->store($request->only([
            'product_id',
            'frequently_bought_selection_type',
            'fq_bought_product_ids',
            'fq_bought_product_category_id'
        ]));

        // Product Translations
        ProductTranslation::updateOrCreate(
            $request->only([
                'lang',
                'product_id'
            ]),
            $request->only([
                'name',
                'unit',
                'description'
            ])
        );

        flash(translate('Product has been updated successfully'))->success();

        Artisan::call('view:clear');
        Artisan::call('cache:clear');
        if ($request->has('tab') && $request->tab != null) {
            return Redirect::to(URL::previous() . "#" . $request->tab);
        }
        return back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $result =  $this->single_product_delete($id);
        if ($result) {
            flash(translate('Product has been deleted successfully'))->success();
        } else {
            flash(translate('Something went wrong'))->error();
        }
        return back();
    }

    public function single_product_delete($id)
    {
        $product = Product::findOrFail($id);

        $product->product_translations()->delete();
        $product->categories()->detach();
        $product->stocks()->delete();
        $product->taxes()->delete();
        $product->frequently_bought_products()->delete();
        $product->last_viewed_products()->delete();
        $product->flash_deal_products()->delete();
        deleteProductReview($product);
        if (Product::destroy($id)) {
            Cart::where('product_id', $id)->delete();
            Wishlist::where('product_id', $id)->delete();
            Artisan::call('view:clear');
            Artisan::call('cache:clear');

            return 1;
        } else {
            return 0;
        }
    }

    public function bulk_product_delete(Request $request)
    {
        if ($request->id) {
            foreach ($request->id as $product_id) {
                $this->single_product_delete($product_id);
            }
        }

        return 1;
    }

    /**
     * Duplicates the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function duplicate(Request $request, $id)
    {
        $product = Product::find($id);

        //Product
        $product_new = $this->productService->product_duplicate_store($product);

        //Product Stock
        $this->productStockService->product_duplicate_store($product->stocks, $product_new);

        //VAT & Tax
        $this->productTaxService->product_duplicate_store($product->taxes, $product_new);

        // Product Categories
        foreach ($product->product_categories as $product_category) {
            ProductCategory::insert([
                'product_id' => $product_new->id,
                'category_id' => $product_category->category_id,
            ]);
        }

        // Frequently Bought Products
        $this->frequentlyBoughtProductService->product_duplicate_store($product->frequently_bought_products, $product_new);

        flash(translate('Product has been duplicated successfully'))->success();
        if ($request->type == 'In House')
            return redirect()->route('products.admin');
        elseif ($request->type == 'Seller')
            return redirect()->route('products.seller');
        elseif ($request->type == 'All')
            return redirect()->route('products.all');
        elseif ($request->type == 'SellerProfile')
            return back();
    }

    public function get_products_by_brand(Request $request)
    {
        $products = Product::where('brand_id', $request->brand_id)->get();
        return view('partials.product_select', compact('products'));
    }

    public function updateTodaysDeal(Request $request)
    {
        $product = Product::findOrFail($request->id);
        $product->todays_deal = $request->status;
        $product->save();
        Cache::forget('todays_deal_products');
        return 1;
    }

    public function updateComingSoon(Request $request)
    {
        $product = Product::findOrFail($request->id);

        $product->coming_soon = $request->status;
        $product->save();

        Cache::forget('coming_soon_products');

        return 1;
    }

    public function updatePublished(Request $request)
    {
        $product = Product::findOrFail($request->id);
        $product->published = $request->status;

        if ($product->added_by == 'seller' && addon_is_activated('seller_subscription') && $request->status == 1) {
            $shop = $product->user->shop;
            if (
                $shop->package_invalid_at == null
                || Carbon::now()->diffInDays(Carbon::parse($shop->package_invalid_at), false) < 0
                || $shop->product_upload_limit <= $shop->user->products()->where('published', 1)->count()
            ) {
                return 0;
            }
        }

        $product->save();

        Artisan::call('view:clear');
        Artisan::call('cache:clear');
        return 1;
    }

    public function updateProductApproval(Request $request)
    {
        $product = Product::findOrFail($request->id);
        $product->approved = $request->approved;

        if ($product->added_by == 'seller' && addon_is_activated('seller_subscription')) {
            $shop = $product->user->shop;
            if (
                $shop->package_invalid_at == null
                || Carbon::now()->diffInDays(Carbon::parse($shop->package_invalid_at), false) < 0
                || $shop->product_upload_limit <= $shop->user->products()->where('published', 1)->count()
            ) {
                return 0;
            }
        }

        $product->save();

        $users                  = User::findMany($product->user_id);
        $data = array();
        $data['product_type']   = $product->digital ==  0 ? 'physical' : 'digital';
        $data['status']         = $request->approved == 1 ? 'approved' : 'rejected';
        $data['product']        = $product;
        $data['notification_type_id'] = get_notification_type('seller_product_approved', 'type')->id;
        Notification::send($users, new ShopProductNotification($data));

        Artisan::call('view:clear');
        Artisan::call('cache:clear');
        return 1;
    }

    public function updateFeatured(Request $request)
    {
        $product = Product::findOrFail($request->id);
        $product->featured = $request->status;
        if ($product->save()) {
            Artisan::call('view:clear');
            Artisan::call('cache:clear');
            return 1;
        }
        return 0;
    }

    public function sku_combination(Request $request)
    {
        $options = array();
        if ($request->has('colors_active') && $request->has('colors') && count($request->colors) > 0) {
            $colors_active = 1;
            array_push($options, $request->colors);
        } else {
            $colors_active = 0;
        }

        $unit_price = $request->unit_price;
        $product_name = $request->name;

        if ($request->has('choice_no')) {
            foreach ($request->choice_no as $key => $no) {
                $name = 'choice_options_' . $no;
                // foreach (json_decode($request[$name][0]) as $key => $item) {
                if (isset($request[$name])) {
                    $data = array();
                    foreach ($request[$name] as $key => $item) {
                        // array_push($data, $item->value);
                        array_push($data, $item);
                    }
                    array_push($options, $data);
                }
            }
        }

        $combinations = (new CombinationService())->generate_combination($options);
        return view('backend.product.products.sku_combinations', compact('combinations', 'unit_price', 'colors_active', 'product_name'));
    }

    public function sku_combination_edit(Request $request)
    {
        $product = Product::findOrFail($request->id);

        $options = array();
        if ($request->has('colors_active') && $request->has('colors') && count($request->colors) > 0) {
            $colors_active = 1;
            array_push($options, $request->colors);
        } else {
            $colors_active = 0;
        }

        $product_name = $request->name;
        $unit_price = $request->unit_price;

        if ($request->has('choice_no')) {
            foreach ($request->choice_no as $key => $no) {
                $name = 'choice_options_' . $no;
                // foreach (json_decode($request[$name][0]) as $key => $item) {
                if (isset($request[$name])) {
                    $data = array();
                    foreach ($request[$name] as $key => $item) {
                        // array_push($data, $item->value);
                        array_push($data, $item);
                    }
                    array_push($options, $data);
                }
            }
        }

        $combinations = (new CombinationService())->generate_combination($options);
        return view('backend.product.products.sku_combinations_edit', compact('combinations', 'unit_price', 'colors_active', 'product_name', 'product'));
    }

    public function product_search(Request $request)
    {
        $products = $this->productService->product_search($request->except(['_token']));
        return view('partials.product.product_search', compact('products'));
    }

    public function get_selected_products(Request $request)
    {
        $products = product::whereIn('id', $request->product_ids)->get();
        return  view('partials.product.frequently_bought_selected_product', compact('products'));
    }

    public function setProductDiscount(Request $request)
    {
        return $this->productService->setCategoryWiseDiscount($request->except(['_token']));
    }
    // public function assignmentHistoryIndex()
    // {
    //     $sellers = Shop::join('users', 'users.id', '=', 'shops.user_id')
    //         ->select(
    //             'users.id as seller_id',
    //             'users.name as seller_name',
    //             'shops.shop_id'
    //         )
    //         ->get();

    //     return view('backend.assignment_history.index', compact('sellers'));
    // }

    public function assignmentHistoryIndex(Request $request)
    {
        $district_id = $request->district_id ?? null;
        $block_id = $request->block_id ?? null;
        $sub_district_id = $request->sub_district_id ?? null;
        $query = Shop::join('users', 'users.id', '=', 'shops.user_id')
            ->select(
                'users.id as seller_id',
                'users.name as seller_name',
                'shops.shop_id'
            );

        // if ($request->search) {
        //     $query->where('users.name', 'like', '%' . $request->search . '%')
        //         ->orWhere('shops.shop_id', 'like', '%' . $request->search . '%');
        // }

        if ($request->search) {

            $query->where(function ($q) use ($request) {
                $q->where('users.name', 'like', '%' . $request->search . '%')
                    ->orWhere('shops.shop_id', 'like', '%' . $request->search . '%');
            });
        }

        $sellers = $query->paginate(10)->withQueryString();

        return view('backend.assignment_history.index', compact('sellers'));


        $query->where(function ($q) use ($request) {

            $q->where(
                'users.name',
                'like',
                '%' . $request->search . '%'
            )
                ->orWhere(
                    'shops.shop_id',
                    'like',
                    '%' . $request->search . '%'
                );
        });

        if ($district_id != null) {
            $query->where(
                'users.district',
                $district_id
            );
        }

        if ($block_id != null) {
            $query->where(
                'users.block',
                $block_id
            );
        }

        if ($sub_district_id != null) {
            $query->where(
                'users.sub_district',
                $sub_district_id
            );
        }

        $sellers = $query->get();
        return view('backend.assignment_history.index', compact(
            'sellers',
            'district_id',
            'block_id',
            'sub_district_id'
        ));
    }
    public function showAssignmentHistory($seller_id)
    {
        $seller = User::findOrFail($seller_id);

        $history = SellerProductAssignment::join('products', 'products.id', '=', 'seller_product_assignments.product_id')
            ->join('shops', 'shops.user_id', '=', 'seller_product_assignments.seller_id')
            ->select(
                'products.name as product_name',
                'shops.shop_id',
                'seller_product_assignments.quantity',
                'seller_product_assignments.created_at'
            )
            ->where('seller_product_assignments.seller_id', $seller_id)
            ->orderBy('seller_product_assignments.created_at', 'desc')
            ->get();

        return view('backend.assignment_history.show', compact('history', 'seller'));
    }
    // public function lowSellerStock(Request $request)
    // {
    //     $query = SellerProduct::join('users', 'users.id', '=', 'seller_products.seller_id')
    //         ->join('products', 'products.id', '=', 'seller_products.product_id')
    //         ->join('shops', 'shops.user_id', '=', 'seller_products.seller_id')
    //         ->select(
    //             'users.name as seller_name',
    //             'shops.shop_id',
    //             'products.name as product_name',
    //             'seller_products.stock'
    //         )
    //         ->where('seller_products.stock', '<=', 5);

    //     if ($request->seller_id) {
    //         $query->where('users.name', 'like', '%' . $request->seller_name . '%');
    //     }


    //     if ($request->product_id) {
    //         $query->where('products.name', 'like', '%' . $request->product_name . '%');
    //     }

    //     $lowStocks = $query->orderBy('seller_products.stock', 'asc')->get();
    //     $sellers = User::where('user_type','seller')->pluck('name','id');
    //     $products = Product::pluck('name','id');

    //     return view('backend.stock.index', compact('lowStocks','sellers','products'));
    // }

    // public function lowSellerStock(Request $request)
    // {
    //     $query = SellerProduct::join('users', 'users.id', '=', 'seller_products.seller_id')
    //         ->join('products', 'products.id', '=', 'seller_products.product_id')
    //         ->join('shops', 'shops.user_id', '=', 'seller_products.seller_id')
    //         ->select(
    //             'users.name as seller_name',
    //             'users.id as seller_id',
    //             'shops.shop_id',
    //             'products.name as product_name',
    //             'products.id as product_id',
    //             'seller_products.stock'
    //         )
    //         ->where('seller_products.stock', '<=', 5);


    //     if ($request->seller_id) {
    //         $query->where('users.id', $request->seller_id);
    //     }

    //     if ($request->product_id) {
    //         $query->where('products.id', $request->product_id);
    //     }

    //     $lowStocks = $query->orderBy('seller_products.stock', 'asc')->get();

    //     // dropdown data
    //     $sellers = User::where('user_type', 'seller')->pluck('name', 'id');
    //     $products = Product::pluck('name', 'id');

    //     return view('backend.stock.index', compact('lowStocks', 'sellers', 'products'));
    // }

    public function lowSellerStock(Request $request)
    {
        $district_id = $request->district_id ?? null;
        $block_id = $request->block_id ?? null;
        $sub_district_id = $request->sub_district_id ?? null;
        $query = SellerProduct::join('users', 'users.id', '=', 'seller_products.seller_id')
            ->join('products', 'products.id', '=', 'seller_products.product_id')
            ->join('shops', 'shops.user_id', '=', 'seller_products.seller_id')
            ->select(
                'users.name as seller_name',
                'users.id as seller_id',
                'shops.shop_id',
                'products.name as product_name',
                'products.id as product_id',
                'products.seller_purchase_limit',
                'products.seller_min_purchase_limit',
                'seller_products.stock'
            )
            ->where('seller_products.stock', '<=', 5);

        if ($request->seller_id) {
            $query->where('users.id', $request->seller_id);
        }

        if ($request->product_id) {
            $query->where('products.id', $request->product_id);
        }
        if ($district_id != null) {
            $query->where(
                'users.district',
                $district_id
            );
        }

        if ($block_id != null) {
            $query->where(
                'users.block',
                $block_id
            );
        }

        if ($sub_district_id != null) {
            $query->where(
                'users.sub_district',
                $sub_district_id
            );
        }

        $lowStocks = $query->orderBy('seller_products.stock', 'asc')->get();

        $lowStocks = $lowStocks->map(function ($item) {

            $max = $item->seller_purchase_limit ?? 0;
            $stock = $item->stock ?? 0;

            $item->remaining = max($max - $stock, 0);

            return $item;
        });

        $totalRemaining = 0;
        if ($request->product_id) {
            $totalRemaining = $lowStocks->sum('remaining');
        }

        // dropdown data
        $sellers = User::where('user_type', 'seller')->pluck('name', 'id');
        $products = Product::pluck('name', 'id');

        return view('backend.stock.index', compact(
            'lowStocks',
            'sellers',
            'products',
            'totalRemaining',
            'district_id',
            'block_id',
            'sub_district_id'
        ));
    }


    // public function OutOfStockRequests()
    // {
    //     $requests = OutOfStockRequest::with(['user', 'product'])
    //         ->latest()
    //         ->paginate();

    //     return view('backend.out_of_stock.out_of_stock', compact('requests'));

    // public function OutOfStockRequests(){
    //     $requests = OutOfStockRequest::with(['user','product'])
    //     ->latest()
    //     ->paginate();

    //     return view('backend.out_of_stock.out_of_stock',compact('requests'));
    // }

    public function OutOfStockRequests(Request $request)
    {
        $district_id = $request->district_id ?? null;
        $block_id = $request->block_id ?? null;
        $sub_district_id = $request->sub_district_id ?? null;

        // $requests = OutOfStockRequest::with(['user', 'product']);
        $requests = OutOfStockRequest::with(['user', 'product'])
            ->whereHas('user');

        if (
            $district_id != null ||
            $block_id != null ||
            $sub_district_id != null
        ) {

            // Get matching sellers
            $seller_query = User::where('user_type', 'seller');

            if ($district_id != null) {
                $seller_query->where(
                    'district',
                    $district_id
                );
            }

            if ($block_id != null) {
                $seller_query->where(
                    'block',
                    $block_id
                );
            }

            if ($sub_district_id != null) {
                $seller_query->where(
                    'sub_district',
                    $sub_district_id
                );
            }

            $seller_ids = $seller_query
                ->pluck('id')
                ->toArray();

            // Find matching request ids
            $request_ids = OutOfStockRequest::all()
                ->filter(function ($req) use ($seller_ids) {

                    $failed_sellers = $req->seller_ids ?? [];

                    return count(
                        array_intersect(
                            $failed_sellers,
                            $seller_ids
                        )
                    ) > 0;
                })
                ->pluck('id')
                ->toArray();

            $requests = $requests->whereIn(
                'id',
                $request_ids
            );
        }

        $requests = $requests
            ->latest()
            ->paginate(15);

        return view(
            'backend.out_of_stock.out_of_stock',
            compact(
                'requests',
                'district_id',
                'block_id',
                'sub_district_id'
            )
        );
    }
}
