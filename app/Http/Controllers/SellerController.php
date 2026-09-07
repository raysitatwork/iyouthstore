<?php

namespace App\Http\Controllers;

use App\Exports\SellersExport;
use App\Models\Addon;
use App\Models\Block;
use App\Models\Cart;
use App\Models\CgCity;
use App\Models\City;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Shop;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Payment;
use App\Models\Product;
use App\Models\SellerProduct;
use App\Models\SellerProductAssignment;
use App\Models\State;
use App\Models\SubDistrict;
use App\Models\Wishlist;
use Illuminate\Support\Facades\Hash;
use App\Notifications\ShopVerificationNotification;
use App\Services\PreorderService;
use App\Utility\EmailUtility;
use Cache;
use Carbon\Carbon;
use DateTime;
use File;
use Illuminate\Support\Facades\Log as FacadesLog;
use Illuminate\Support\Facades\Notification;
use Log;
use DB;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class SellerController extends Controller
{
    public function __construct()
    {
        // Staff Permission Check
        $this->middleware(['permission:view_all_seller|view_all_seller_rating_and_followers'])->only('index');
        $this->middleware(['permission:add_seller'])->only('create');
        $this->middleware(['permission:view_seller_profile'])->only('sellerProfile');
        $this->middleware(['permission:login_as_seller'])->only('login');
        $this->middleware(['permission:pay_to_seller'])->only('payment_modal');
        $this->middleware(['permission:edit_seller'])->only('edit');
        $this->middleware(['permission:delete_seller'])->only('destroy');
        $this->middleware(['permission:ban_seller'])->only('ban');
        $this->middleware(['permission:edit_seller_custom_followers'])->only('editSellerCustomFollowers');
        $this->middleware(['permission:view_pending_seller'])->only('pendingSellers');
        $this->middleware(['permission:mark_seller_suspected'])->only('suspicious');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $sort_search = $request->search ?? null;
        $approved = $request->approved_status ?? null;
        $verification_status =  $request->verification_status ?? null;
        $block_id = $request->block_id ?? null;
        $sub_district_id = $request->sub_district_id ?? null;

        $shops = Shop::where('registration_approval', 1)->whereIn('user_id', function ($query) {
            $query->select('id')
                ->from(with(new User)->getTable())
                ->where('user_type', 'seller');
        })->latest();

        if (
            $sort_search != null || $verification_status != null || $block_id != null ||
            $sub_district_id != null
        ) {
            $user_ids = User::where('user_type', 'seller');
            if ($sort_search != null) {
                $user_ids = $user_ids->where(function ($user) use ($sort_search) {
                    $user->where('name', 'like', '%' . $sort_search . '%')
                        ->orWhere('email', 'like', '%' . $sort_search . '%')
                        ->orWhere('phone', 'like', '%' . $sort_search . '%');
                });
            }
            if ($verification_status != null) {
                $user_ids = $verification_status == 'verified' ? $user_ids->where('email_verified_at', '!=', null) : $user_ids->where('email_verified_at', null);
            }
            if ($block_id != null) {
                $user_ids = $user_ids->where('block', $block_id);
            }

            if ($sub_district_id != null) {
                $user_ids = $user_ids->where('sub_district', $sub_district_id);
            }
            $user_ids = $user_ids->pluck('id')->toArray();
            $shops = $shops->where(function ($shops) use ($user_ids) {
                $shops->whereIn('user_id', $user_ids);
            });
        }
        if ($approved != null) {
            $shops = $shops->where('verification_status', $approved);
        }
        $shops = $shops->paginate(15);
        return view('backend.sellers.index', compact('shops', 'sort_search', 'approved', 'verification_status', 'block_id', 'sub_district_id'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */

    // public function sellerInventory()
    // {


    //     $shops = SellerProduct::join('shops', 'seller_products.seller_id', '=', 'shops.user_id')
    //         ->select(
    //             'shops.name as shop_name',
    //             'seller_products.seller_id',
    //             DB::raw('SUM(seller_products.stock) as total_stock')
    //         )
    //         ->groupBy('shops.user_id', 'shops.name')
    //         ->get();
    //     return view('backend.sellers.inventory', compact('shops'));
    // }


    public function sellerInventory(Request $request)
    {
        $sort_search = $request->search ?? null;

        $district_id = $request->district_id ?? null;
        $block_id = $request->block_id ?? null;
        $sub_district_id = $request->sub_district_id ?? null;

        $shops = SellerProduct::join(
            'shops',
            'seller_products.seller_id',
            '=',
            'shops.user_id'
        )
            ->select(
                'shops.name as shop_name',
                'seller_products.seller_id',
                DB::raw(
                    'SUM(seller_products.stock) as total_stock'
                )
            );

        if (
            $sort_search != null ||
            $district_id != null ||
            $block_id != null ||
            $sub_district_id != null
        ) {

            $user_ids = User::where(
                'user_type',
                'seller'
            );

            if ($sort_search != null) {
                $user_ids = $user_ids->where(
                    'name',
                    'like',
                    '%' . $sort_search . '%'
                );
            }

            if ($district_id != null) {
                $user_ids = $user_ids->where(
                    'district',
                    $district_id
                );
            }

            if ($block_id != null) {
                $user_ids = $user_ids->where(
                    'block',
                    $block_id
                );
            }

            if ($sub_district_id != null) {
                $user_ids = $user_ids->where(
                    'sub_district',
                    $sub_district_id
                );
            }

            $shops = $shops->whereIn(
                'seller_products.seller_id',
                $user_ids->pluck('id')
            );
        }

        $shops = $shops
            ->groupBy(
                'shops.user_id',
                'shops.name'
            )
            ->get();

        return view(
            'backend.sellers.inventory',
            compact(
                'shops',
                'sort_search',
                'district_id',
                'block_id',
                'sub_district_id'
            )
        );
    }

    public function sellerInventoryDetail($id)
    {

        $seller = Shop::where('user_id', $id)->first();
        $sellerProducts = SellerProduct::where('seller_id', $id)->with('product')->get();
        return view('backend.sellers.inventory_detail', compact('sellerProducts', 'seller'));
    }

    public function create()
    {
        $districts = City::where('status', 1)->get();

        $blocks = Block::where('status', 1)->get();

        $subDistricts = SubDistrict::where('status', 1)->get();
        return view('backend.sellers.create', compact('districts', 'blocks', 'subDistricts'));
    }

    public function getCities(Request $request)
    {

        $cities = CgCity::where('sub_district_id', $request->sub_district_id)
            ->get();

        return response()->json($cities);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    // public function store(Request $request)
    // {
    //     $request->validate(
    //         [
    //             'name' => 'required|max:255',
    //             'email' => 'required|email|unique:users',
    //             // 'shop_name' => 'max:200',
    //             'phone' => 'required|unique:users,phone|max:20',
    //             'password' => 'required|min:6|confirmed',
    //             // 'address' => 'max:500',
    //         ],
    //         [
    //             'name.required' => translate('Name is required'),
    //             'name.max' => translate('Max 255 Character'),
    //             'email.required' => translate('Email is required'),
    //             'email.email' => translate('Email must be a valid email address'),
    //             'email.unique' => translate('An user exists with this email'),
    //             'shop_name.max' => translate('Max 200 Character'),
    //             'address.max' => translate('Max 255 Character'),
    //             'phone.required' => translate('Phone is required'),
    //             'phone.unique' => translate('Phone already exists'),
    //             'password.required' => translate('Password is required'),
    //             'password.confirmed' => translate('Password confirmation does not match'),
    //         ]
    //     );


    //     if (User::where('email', $request->email)->first() != null) {
    //         flash(translate('Email already exists!'))->error();
    //         return back();
    //     }
    //     // $password = substr(hash('sha512', rand()), 0, 8);

    //     $user           = new User;
    //     $user->name     = $request->name;
    //     $user->email    = $request->email;
    //     $user->user_type = "seller";
    //     $user->phone = $request->phone;
    //     // $user->password = Hash::make($password);
    //     $user->password = Hash::make($request->password);

    //     if ($user->save()) {

    //         // Create shop automatically
    //         $shop = new Shop;
    //         $shop->user_id = $user->id;
    //         $shop->name = 'Seller-' . $user->id;   // better than N/A
    //         $shop->slug = 'seller-' . $user->id;
    //         $shop->registration_approval = 1;      // allow seller login
    //         $shop->verification_status = 0;        // seller must verify shop later
    //         $shop->address = 'N/A';
    //         $shop->save();



    //         // try {
    //         //     // EmailUtility::selelr_registration_email('registration_from_system_email_to_seller', $user, $password);
    //         //     EmailUtility::selelr_registration_email('registration_from_system_email_to_seller', $user, $request->password);
    //         // } catch (\Exception $e) {
    //         //     // $shop->delete();
    //         //     $user->delete();
    //         //     flash(translate('Registration failed. Please try again later.'))->error();
    //         //     return back();
    //         // }

    //         // Verification email send
    //         if (get_setting('email_verification') != 1) {
    //             $user->email_verified_at = date('Y-m-d H:m:s');
    //             $user->save();
    //         } else {
    //             EmailUtility::email_verification($user, 'seller');
    //         }

    //         // Seller Account Opening Email to Admin
    //         // if ((get_email_template_data('seller_reg_email_to_admin', 'status') == 1)) {
    //         //     try {
    //         //         EmailUtility::selelr_registration_email('seller_reg_email_to_admin', $user, null);
    //         //     } catch (\Exception $e) {
    //         //     }
    //         // }

    //         flash(translate('Seller has been added successfully'))->success();
    //         return back();
    //     }
    //     flash(translate('Something went wrong'))->error();
    //     return back();
    // }


    public function store(Request $request)
    {
        $request->validate(
            [
                'name' => 'required|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|min:6|confirmed',
                'state' => 'required',
                'district_id' => 'required',
                'block_id' => 'required',
                'sub_district_id' => 'required',
                'city' => 'required|max:255',
                'gender' => 'nullable|max:50',
                'father_husband_name' => 'nullable|max:255',
                'dob' => 'nullable|date',
                'age' => 'nullable|max:10',
                'aadhaar' => 'nullable|max:50',
                'pan' => 'nullable|max:50',
                'address' => 'nullable|max:500',
                'postal_code' => 'nullable|max:20',
                'phone' => 'nullable|max:20',
                'alternate_phone' => 'nullable|max:20',
                'whatsapp_number' => 'nullable|max:20',
                'qualification' => 'nullable|max:255',
                'experience' => 'nullable|max:255',
                'shop_address' => 'nullable|max:500',
                'shop_size' => 'nullable|max:100',
                'rent_type' => 'nullable|max:100',
                'monthly_rent' => 'nullable|max:100',
                'bank_acc_no' => 'nullable|max:100',
                'bank_name' => 'nullable|max:255',
                'bank_acc_name' => 'nullable|max:255',
                'bank_routing_no' => 'nullable|max:100',
                'security_deposit' => 'nullable|max:100',
                'payment_status' => 'nullable|max:100',
                'payment_mode' => 'nullable|max:100',
            ],
            [
                'name.required' => translate('Name is required'),
                'name.max' => translate('Max 255 Character'),
                'email.required' => translate('Email is required'),
                'email.email' => translate('Email must be a valid email address'),
                'email.unique' => translate('An user exists with this email'),
                // 'shop_name.max' => translate('Max 200 Character'),
                // 'address.max' => translate('Max 255 Character'),
                'password.required' => translate('Password is required'),
                'password.confirmed' => translate('Password confirmation does not match'),
            ]
        );


        // if (User::where('email', $request->email)->first() != null) {
        //     flash(translate('Email already exists!'))->error();
        //     return back();
        // }

        $user           = new User;
        $user->name     = $request->name;
        $user->email    = $request->email;
        $user->user_type = "seller";
        $user->gender = $request->gender;
        $user->father_husband_name = $request->father_husband_name;
        $user->dob = $request->dob;
        $user->age = $request->age;
        $user->aadhaar = $request->aadhaar;
        $user->pan = $request->pan;
        $user->address = $request->address;
        $user->postal_code = $request->postal_code;
        $user->phone = $request->phone;
        $user->alternate_phone = $request->alternate_phone;
        $user->whatsapp_number = $request->whatsapp_number;
        $user->qualification = $request->qualification;
        $user->experience = $request->experience;

        $user->state = $request->state;
        $user->district = $request->district_id;
        $user->block = $request->block_id;
        $user->sub_district = $request->sub_district_id;
        $user->city = $request->city;
        $user->email_verified_at = now();



        $user->password = Hash::make($request->password);

        if ($user->save()) {
            $shop           = new Shop;
            $shop->user_id  = $user->id;
            $shop->name = $request->name . "'s Shop";
            $shop->address = $request->shop_address;
            $shop->shop_size = $request->shop_size;
            $shop->rent_type = $request->rent_type;
            $shop->monthly_rent = $request->monthly_rent;
            $shop->bank_acc_no = $request->bank_acc_no;
            $shop->bank_name = $request->bank_name;
            $shop->bank_acc_name = $request->bank_acc_name;
            $shop->bank_routing_no = $request->bank_routing_no;
            $shop->security_deposit = $request->security_deposit;
            $shop->payment_status = $request->payment_status;
            $shop->payment_mode = $request->payment_mode;
            $shop->registration_approval = 1;
            // $shop->shop_id = $this->generateLocationUniqueId($request->district_id, $request->block_id, $request->sub_district_id);
            $shop->shop_id = $this->generateLocationUniqueId(
                $request->district_id,
                $request->block_id,
                $request->sub_district_id
            );
            $shop->save();

            // try {
            //     EmailUtility::selelr_registration_email('registration_from_system_email_to_seller', $user, $password);
            // } catch (\Exception $e) {
            //     $shop->delete();
            //     $user->delete();
            //     flash(translate('Registration failed. Please try again later.'))->error();
            //     return back();
            // }

            // Verification email send
            // if (get_setting('email_verification') != 1) {
            //     $user->email_verified_at = date('Y-m-d H:m:s');
            //     $user->save();
            // } else {
            //     EmailUtility::email_verification($user, 'seller');
            // }

            // Seller Account Opening Email to Admin
            // if ((get_email_template_data('seller_reg_email_to_admin', 'status') == 1)) {
            //     try {
            //         EmailUtility::selelr_registration_email('seller_reg_email_to_admin', $user, null);
            //     } catch (\Exception $e) {
            //     }
            // }

            flash(translate('Seller has been added successfully'))->success();
            return back();
        }
        flash(translate('Something went wrong'))->error();
        return back();
    }


    private function generateLocationUniqueId($districtId, $blockId)
    {
        return DB::transaction(function () use ($districtId, $blockId) {

            // District
            $district = City::find($districtId);

            if (!$district) {
                throw new \Exception('District not found.');
            }

            // District Code
            // Example: CG05
            $districtCode = strtoupper(trim($district->district_code));

            // CG05 → 05
            $districtCode = preg_replace('/^CG/i', '', $districtCode);

            // Block Name
            $blockName = Block::where('id', $blockId)->value('name');

            if (!$blockName) {
                throw new \Exception('Block not found.');
            }

            $blockName = strtoupper(trim($blockName));

            // Space / special character remove
            $blockName = preg_replace('/[^A-Z0-9]+/', '', $blockName);

            // Same District + Same Block seller count
            $sellerCount = User::where('user_type', 'seller')
                ->where('district', $districtId)
                ->where('block', $blockId)
                ->count();

            // 01, 02, 03...
            $serialNumber = str_pad(
                $sellerCount,
                2,
                '0',
                STR_PAD_LEFT
            );

            // Final Shop ID
            return "IYS/{$districtCode}/{$blockName}/{$serialNumber}";
        });
    }

    // Old code
    // function generateLocationUniqueId($districtId, $blockId, $subDistrictId)
    // {
    //     return DB::transaction(function () use ($districtId, $blockId, $subDistrictId) {

    //         $district = City::find($districtId);
    //         if (!$district) {
    //             return null;
    //         }

    //         $districtCode = $district->district_code;

    //         $blockName = Block::where('id', $blockId)->value('name');
    //         $subDistrictName = SubDistrict::where('id', $subDistrictId)->value('name');

    //         $prefix = $districtCode . '-' . $blockName . '-' . $subDistrictName;

    //         $lastShop = Shop::where('shop_id', 'like', $prefix . '-%')
    //             ->lockForUpdate()
    //             ->orderBy('id', 'desc')
    //             ->first();

    //         if ($lastShop) {
    //             $lastNumber = (int) substr(
    //                 $lastShop->shop_id,
    //                 strrpos($lastShop->shop_id, '-') + 1
    //             );
    //             $newNumber = $lastNumber + 1;
    //         } else {
    //             $newNumber = 1;
    //         }

    //         return $prefix . '-' . $newNumber;
    //     });
    // }

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
    public function edit($id)
    {
        // $shop = Shop::findOrFail(decrypt($id));
        // return view('backend.sellers.edit', compact('shop'));
        $shop = Shop::with('user')->findOrFail(decrypt($id));

        $districts = City::where('status', 1)->get();
        $blocks = Block::where('status', 1)->get();
        $subDistricts = SubDistrict::where('status', 1)->get();

        return view('backend.sellers.edit', compact(
            'shop',
            'districts',
            'blocks',
            'subDistricts'
        ));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    // public function update(Request $request, $id)
    // {

    //     $shop = Shop::findOrFail($id);
    //     $user = $shop->user;
    //     $user->name = $request->name;
    //     $user->email = $request->email;
    //     if (strlen($request->password) > 0) {
    //         $user->password = Hash::make($request->password);
    //     }
    //     if ($user->save()) {
    //         if ($shop->save()) {
    //             flash(translate('Seller has been updated successfully'))->success();
    //             return redirect()->route('sellers.index');
    //         }
    //     }

    //     flash(translate('Something went wrong'))->error();
    //     return back();
    // }

    public function update(Request $request, $id)
    {
        $shop = Shop::findOrFail($id);
        $user = $shop->user;

        $request->validate([
            'name' => 'required|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|min:6|confirmed',
            'state' => 'required',
            'district_id' => 'required',
            'block_id' => 'required',
            'sub_district_id' => 'required',
            'city' => 'required|max:255',
            'gender' => 'nullable|max:50',
            'father_husband_name' => 'nullable|max:255',
            'dob' => 'nullable|date',
            'age' => 'nullable|max:10',
            'aadhaar' => 'nullable|max:50',
            'pan' => 'nullable|max:50',
            'address' => 'nullable|max:500',
            'postal_code' => 'nullable|max:20',
            'phone' => 'nullable|max:20',
            'alternate_phone' => 'nullable|max:20',
            'whatsapp_number' => 'nullable|max:20',
            'qualification' => 'nullable|max:255',
            'experience' => 'nullable|max:255',
            'shop_address' => 'nullable|max:500',
            'shop_size' => 'nullable|max:100',
            'rent_type' => 'nullable|max:100',
            'monthly_rent' => 'nullable|max:100',
            'bank_acc_no' => 'nullable|max:100',
            'bank_name' => 'nullable|max:255',
            'bank_acc_name' => 'nullable|max:255',
            'bank_routing_no' => 'nullable|max:100',
            'security_deposit' => 'nullable|max:100',
            'payment_status' => 'nullable|max:100',
            'payment_mode' => 'nullable|max:100',
        ]);



        $user->name = $request->name;
        $user->email = $request->email;
        $user->gender = $request->gender;
        $user->father_husband_name = $request->father_husband_name;
        $user->dob = $request->dob;
        $user->age = $request->age;
        $user->aadhaar = $request->aadhaar;
        $user->pan = $request->pan;
        $user->address = $request->address;
        $user->postal_code = $request->postal_code;
        $user->phone = $request->phone;
        $user->alternate_phone = $request->alternate_phone;
        $user->whatsapp_number = $request->whatsapp_number;
        $user->qualification = $request->qualification;
        $user->experience = $request->experience;
        $user->state = $request->state;
        $user->district = $request->district_id;
        $user->block = $request->block_id;
        $user->sub_district = $request->sub_district_id;
        $user->city = $request->city;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        /* Shop Details */
        $shop->name = $request->name . "'s Shop";

        $shop->address = $request->shop_address;
        $shop->shop_size = $request->shop_size;
        $shop->rent_type = $request->rent_type;
        $shop->monthly_rent = $request->monthly_rent;

        $shop->bank_acc_no = $request->bank_acc_no;
        $shop->bank_name = $request->bank_name;
        $shop->bank_acc_name = $request->bank_acc_name;
        $shop->bank_routing_no = $request->bank_routing_no;

        $shop->security_deposit = $request->security_deposit;
        $shop->payment_status = $request->payment_status;
        $shop->payment_mode = $request->payment_mode;

        // Generate / Update Shop ID
        $shop->shop_id = $this->generateLocationUniqueId($request->district_id, $request->block_id);

        $user->save();
        $shop->save();

        flash(translate('Seller has been updated successfully'))->success();

        return redirect()->route('sellers.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $shop = Shop::findOrFail($id);

        // Seller Product and product related data delete
        $products = $shop->user->products;
        foreach ($products as $product) {
            $product_id = $product->id;
            $product->product_translations()->delete();
            $product->categories()->detach();
            $product->stocks()->delete();
            $product->taxes()->delete();
            $product->frequently_bought_products()->delete();
            $product->last_viewed_products()->delete();
            $product->flash_deal_products()->delete();

            if ($product->delete()) {
                Cart::where('product_id', $product_id)->delete();
                Wishlist::where('product_id', $product_id)->delete();
            }
        }

        $orders = Order::where('user_id', $shop->user_id)->get();

        foreach ($orders as $key => $order) {
            OrderDetail::where('order_id', $order->id)->delete();
        }
        Order::where('user_id', $shop->user_id)->delete();

        // If Preorder addon is installed, delete preorder products and related data.
        if (Addon::where('unique_identifier', 'preorder')->first()) {
            $preorderProducts = $shop->user->preorderProducts;
            foreach ($preorderProducts as $preorderProduct) {
                (new PreorderService)->productdestroy($preorderProduct->id);
            }
        }

        User::destroy($shop->user->id);

        if (Shop::destroy($id)) {
            flash(translate('Seller has been deleted successfully'))->success();
            return redirect()->route('sellers.index');
        } else {
            flash(translate('Something went wrong'))->error();
            return back();
        }
    }

    public function bulk_seller_delete(Request $request)
    {
        if ($request->id) {
            foreach ($request->id as $shop_id) {
                $this->destroy($shop_id);
            }
        }

        return 1;
    }

    public function show_verification_request($id)
    {
        $shop = Shop::findOrFail($id);
        return view('backend.sellers.verification', compact('shop'));
    }

    public function approve_seller($id)
    {
        $shop = Shop::findOrFail($id);
        $shop->verification_status = 1;
        $shop->save();
        Cache::forget('verified_sellers_id');

        $users = User::findMany([$shop->user->id]);
        $data = array();
        $data['shop'] = $shop;
        $data['status'] = 'approved';
        $data['notification_type_id'] = get_notification_type('shop_verify_request_approved', 'type')->id;
        Notification::send($users, new ShopVerificationNotification($data));

        flash(translate('Seller has been approved successfully'))->success();
        return back();
    }

    public function reject_seller($id)
    {
        $shop = Shop::findOrFail($id);
        $shop->verification_status = 0;
        $shop->verification_info = null;
        $shop->save();
        Cache::forget('verified_sellers_id');

        $users = User::findMany([$shop->user->id]);
        $data = array();
        $data['shop'] = $shop;
        $data['status'] = 'rejected';
        $data['notification_type_id'] = get_notification_type('shop_verify_request_rejected', 'type')->id;
        Notification::send($users, new ShopVerificationNotification($data));

        flash(translate('Seller verification request has been rejected successfully'))->success();
        return back();
    }


    public function payment_modal(Request $request)
    {
        $shop = shop::findOrFail($request->id);
        return view('backend.sellers.payment_modal', compact('shop'));
    }

    public function verification_info_modal(Request $request)
    {
        $shop = Shop::findOrFail($request->id);
        return view('backend.sellers.verification_info_modal', compact('shop'));
    }

    public function updateApproved(Request $request)
    {
        $shop = Shop::findOrFail($request->id);
        $shop->verification_status = $request->status;
        $shop->save();
        Cache::forget('verified_sellers_id');

        $status = $request->status == 1 ? 'approved' : 'rejected';
        $users = User::findMany([$shop->user->id]);
        $data = array();
        $data['shop'] = $shop;
        $data['status'] = $status;
        $data['notification_type_id'] = $status == 'approved' ?
            get_notification_type('shop_verify_request_approved', 'type')->id :
            get_notification_type('shop_verify_request_rejected', 'type')->id;

        Notification::send($users, new ShopVerificationNotification($data));
        return 1;
    }

    public function login($id)
    {
        $shop = Shop::findOrFail(decrypt($id));
        $user  = $shop->user;
        auth()->login($user, true);

        return redirect()->route('seller.dashboard');
    }

    public function ban($id)
    {
        $shop = Shop::findOrFail($id);

        if ($shop->user->banned == 1) {
            $shop->user->banned = 0;
            if ($shop->verification_info) {
                $shop->verification_status = 1;
            }
            flash(translate('Seller has been unbanned successfully'))->success();
        } else {
            $shop->user->banned = 1;
            $shop->verification_status = 0;
            flash(translate('Seller has been banned successfully'))->success();
        }
        $shop->save();
        $shop->user->save();
        return back();
    }

    // Seller Based Commission
    public function sellerBasedCommission(Request $request)
    {
        $sort_search = $request->search ?? null;
        $approved = $request->approved_status ?? null;
        $verification_status =  $request->verification_status ?? null;

        $shops = Shop::whereIn('user_id', function ($query) {
            $query->select('id')
                ->from(with(new User)->getTable())
                ->where('user_type', 'seller');
        })->latest();

        if ($sort_search != null || $verification_status != null) {
            $user_ids = User::where('user_type', 'seller');
            if ($sort_search != null) {
                $user_ids = $user_ids->where(function ($user) use ($sort_search) {
                    $user->where('name', 'like', '%' . $sort_search . '%')
                        ->orWhere('email', 'like', '%' . $sort_search . '%')
                        ->orWhere('phone', 'like', '%' . $sort_search . '%');
                });
            }
            if ($verification_status != null) {
                $user_ids = $verification_status == 'verified' ? $user_ids->where('email_verified_at', '!=', null) : $user_ids->where('email_verified_at', null);
            }
            $user_ids = $user_ids->pluck('id')->toArray();
            $shops = $shops->where(function ($shops) use ($user_ids) {
                $shops->whereIn('user_id', $user_ids);
            });
        }
        if ($approved != null) {
            $shops = $shops->where('verification_status', $approved);
        }
        $shops = $shops->paginate(15);
        return view('backend.sellers.seller_based_commission.set_commission', compact('shops', 'sort_search', 'approved', 'verification_status'));
    }



    public function setSellerBasedCommission(Request $request)
    {
        if ($request->seller_ids != null) {
            foreach (explode(",", $request->seller_ids) as $shop) {
                $shop = Shop::where('id', $shop)->first();
                $shop->commission_percentage = $request->commission_percentage;
                $shop->save();
            }
            flash(translate('Seller commission is added successfully.'))->success();
        } else {
            flash(translate('Something went wrong!.'))->warning();
        }
        return back();
    }

    public function setSellerCommission(Request $request)
    {
        if ($request->seller_id != null) {
            $shop = Shop::where('id', $request->seller_id)->first();
            $shop->commission_percentage = $request->commission_percentage;
            $shop->save();

            return 1;
        } else {
            return 0;
        }
    }

    // Edit Seller Custom Followers
    public function editSellerCustomFollowers(Request $request)
    {
        $shop = Shop::where('id', $request->shop_id)->first();
        $shop->custom_followers = $request->custom_followers;
        $shop->save();
        flash(translate('Seller custom follower has been updated successfully.'))->success();
        return back();
    }

    // public function pendingSellers(Request $request)
    // {
    //     $sort_search = $request->search ?? null;
    //     $shops = Shop::where('registration_approval', 0)->with('user');

    //     if ($sort_search != null) {
    //         $user_ids = User::where('user_type', 'seller')
    //             ->where(function ($query) use ($sort_search) {
    //                 $query->where('name', 'like', '%' . $sort_search . '%')
    //                     ->orWhere('email', 'like', '%' . $sort_search . '%')
    //                     ->orWhere('phone', 'like', '%' . $sort_search . '%');
    //             })
    //             ->pluck('id')
    //             ->toArray();
    //         $shops = $shops->whereIn('user_id', $user_ids);
    //     }

    //     $shops = $shops->paginate(15);

    //     return view('backend.sellers.pending_seller', compact('shops', 'sort_search'));
    // }


    public function pendingSellers(Request $request)
    {
        $sort_search = $request->search ?? null;

        $shops = Shop::where('registration_approval', 0)->with('user')->orderBy('created_at', 'desc');


        $district_id = $request->district_id ?? null;
        $block_id = $request->block_id ?? null;
        $sub_district_id = $request->sub_district_id ?? null;

        $shops = Shop::where('registration_approval', 0);

        $user_ids = User::where('user_type', 'seller');


        if ($sort_search != null) {
            $user_ids = $user_ids->where(function ($query) use ($sort_search) {
                $query->where('name', 'like', '%' . $sort_search . '%')
                    ->orWhere('email', 'like', '%' . $sort_search . '%')
                    ->orWhere('phone', 'like', '%' . $sort_search . '%');
            });
        }

        if ($district_id != null) {
            $districtName = City::where('id', $district_id)->value('name');
            $user_ids = $user_ids->where('district', $districtName);
        }

        if ($block_id != null) {
            $blockName = Block::where('id', $block_id)->value('name');
            $user_ids = $user_ids->where('block', $blockName);
        }

        if ($sub_district_id != null) {
            $subDistrictName = SubDistrict::where('id', $sub_district_id)->value('name');
            $user_ids = $user_ids->where('sub_district', $subDistrictName);
        }

        $shops = $shops->whereIn(
            'user_id',
            $user_ids->pluck('id')
        );

        $shops = $shops->with('user')->paginate(15);

        return view(
            'backend.sellers.pending_seller',
            compact(
                'shops',
                'sort_search',
                'district_id',
                'block_id',
                'sub_district_id'
            )
        );
    }

    public function UpdateSellerRegistration(Request $request)
    {
        $shop = Shop::findOrFail($request->id);
        $shop->registration_approval = $request->registration_approval;
        if ($shop->save()) {
            try {
                EmailUtility::seller_shop_approval_email('seller_shop_approval_email', $shop);
            } catch (\Exception $e) {
            }
            return 1;
        }
        return 0;
    }

    public function sellerProfile(Request $request)
    {
        $shop_id = decrypt($request->id);
        $shop = Shop::findOrFail($shop_id);
        $shop->last_login = $this->getsellerLastLogin($shop->user_id);
        $addresses = $shop->user->addresses->where('set_default', 0);
        $default_shipping_address = $shop->user->addresses()->where('set_default', 1)->first();
        $products = Product::where('user_id', $shop->user_id)->where('digital', 0)->where('auction_product', 0)->where('wholesale_product', 0)->orderBy('created_at', 'desc');
        if ($request->has('search')) {
            $search = $request->search;
            $products = $products->where('name', 'like', '%' . $search . '%');
        }
        $products = $products->paginate(2);
        return view('backend.sellers.profile.index', compact('shop', 'addresses', 'default_shipping_address', 'products'));
    }

    // public function getSellerProfileTab(Shop $shop, Request $request)
    // {
    //     $tab = $request->get('tab', 'overview');
    //     $page = $request->get('page', 1);
    //     $addresses = $shop->user->addresses->where('set_default', 0);
    //     $default_shipping_address = $shop->user->addresses()->where('set_default', 1)->first();
    //     $shop->last_login = $this->getsellerLastLogin($shop->user_id);
    //     $payments = Payment::where('seller_id', $shop->user_id)->orderBy('created_at', 'desc')->paginate(15);
    //     $products = Product::where('user_id', $shop->user_id)->where('digital', 0)->where('auction_product', 0)->where('wholesale_product', 0)->orderBy('created_at', 'desc')->paginate(15);
    //     $type = 'SellerProfile';
    //     $unpaid_order_payment_notification = get_notification_type('complete_unpaid_order_payment', 'type');
    //     $orders = Order::where('seller_id', $shop->user_id)
    //         ->orderBy('id', 'desc')
    //         ->select('orders.id')
    //         ->distinct()->paginate(15);
    //     $assignment_history = SellerProductAssignment::join('products', 'products.id', '=', 'seller_product_assignments.product_id')
    //         ->select(
    //             'products.name as product_name',
    //             'seller_product_assignments.quantity',
    //             'seller_product_assignments.created_at'
    //         )
    //         ->where('seller_product_assignments.seller_id', $shop->user_id)
    //         ->orderBy('seller_product_assignments.created_at', 'desc')
    //         ->paginate(15);

    //     $low_stock = SellerProduct::join('products', 'products.id', '=', 'seller_products.product_id')
    //         ->select(
    //             'products.name as product_name',
    //             'seller_products.stock'
    //         )
    //         ->where('seller_products.seller_id', $shop->user_id)
    //         ->where('seller_products.stock', '<=', 5)
    //         ->paginate(15);

    //     $inactive_products = collect();

    //     if ($tab == 'inactive_products') {

    //         $fromDate = now()->subMonths(2)->toDateString();
    //         $toDate = now()->toDateString();

    //         $inactive_products = DB::select("
    //     SELECT
    //         p.id,
    //         p.name,
    //         sp.created_at AS assigned_date,
    //         last_sales.last_sold_date

    //     FROM seller_products sp

    //     JOIN products p
    //         ON p.id = sp.product_id

    //     LEFT JOIN
    //     (
    //         SELECT
    //             seller_id,
    //             product_id,
    //             MAX(created_at) AS last_sold_date
    //         FROM order_details
    //         WHERE DATE(created_at) <= ?
    //         GROUP BY seller_id, product_id
    //     ) AS last_sales
    //         ON last_sales.product_id = sp.product_id
    //         AND last_sales.seller_id = sp.seller_id

    //     WHERE sp.seller_id = ?

    //     AND
    //     (
    //         (last_sales.last_sold_date IS NULL AND DATE(sp.created_at) <= ?)
    //         OR DATE(last_sales.last_sold_date) < ?
    //     )

    //     ORDER BY sp.created_at ASC
    // ", [$toDate, $shop->user_id, $toDate, $fromDate]);
    //     }
    //     $html = view('backend.sellers.profile.seller_' . $tab, compact(
    //         'products',
    //         'shop',
    //         'addresses',
    //         'default_shipping_address',
    //         'page',
    //         'orders',
    //         'type',
    //         'unpaid_order_payment_notification',
    //         'payments',
    //         'assignment_history',
    //         'low_stock',
    //         'inactive_products'
    //     ))->render();
    //     return response()->json(['html' => $html]);
    // }

    public function getSellerProfileTab(Shop $shop, Request $request)
    {
        $tab = $request->get('tab', 'overview');
        $page = $request->get('page', 1);
        $addresses = $shop->user->addresses->where('set_default', 0);
        $default_shipping_address = $shop->user->addresses()->where('set_default', 1)->first();
        $shop->last_login = $this->getsellerLastLogin($shop->user_id);
        $payments = Payment::where('seller_id', $shop->user_id)->orderBy('created_at', 'desc')->paginate(15);
        $sellerProducts = \App\Models\SellerProduct::where('seller_id', $shop->user_id)
            ->with('product')
            ->latest()
            ->paginate(15);
        $products = Product::where('user_id', $shop->user_id)->where('digital', 0)->where('auction_product', 0)->where('wholesale_product', 0)->orderBy('created_at', 'desc')->paginate(15);
        $type = 'SellerProfile';
        $unpaid_order_payment_notification = get_notification_type('complete_unpaid_order_payment', 'type');
        $orders = Order::where('seller_id', $shop->user_id)
            ->orderBy('id', 'desc')
            ->select('orders.id')
            ->distinct()->paginate(15);
        $assignment_history = SellerProductAssignment::join('products', 'products.id', '=', 'seller_product_assignments.product_id')
            ->select(
                'products.name as product_name',
                'seller_product_assignments.quantity',
                'seller_product_assignments.created_at'
            )
            ->where('seller_product_assignments.seller_id', $shop->user_id)
            ->orderBy('seller_product_assignments.created_at', 'desc')
            ->paginate(15);

        $low_stock = SellerProduct::join('products', 'products.id', '=', 'seller_products.product_id')
            ->select(
                'products.name as product_name',
                'seller_products.stock'
            )
            ->where('seller_products.seller_id', $shop->user_id)
            ->where('seller_products.stock', '<=', 5)
            ->paginate(15);


        $purchase_orders = collect();

        if ($tab == 'purchase_history') {

            $purchase_orders = Order::where('user_id', $shop->user_id)
                ->where('order_from', 'seller_panel')
                ->latest()
                ->paginate(15);
        }

        $inactive_products = collect();

        $fromDate = $request->input('from_date', now()->subMonths(2)->toDateString());
        $toDate   = $request->input('to_date', now()->toDateString());

        if ($fromDate > $toDate) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
        }

        $inactive_products = collect();

        if ($tab == 'inactive_products') {

            $inactive_products = DB::select("
        SELECT
            p.id,
            p.name,
            sp.created_at AS assigned_date,
            last_sales.last_sold_date

        FROM seller_products sp

        JOIN products p
            ON p.id = sp.product_id

        LEFT JOIN
        (
            SELECT
                seller_id,
                product_id,
                MAX(created_at) AS last_sold_date
            FROM order_details
            WHERE DATE(created_at) <= ?
            GROUP BY seller_id, product_id
        ) AS last_sales
            ON last_sales.product_id = sp.product_id
            AND last_sales.seller_id = sp.seller_id

        WHERE sp.seller_id = ?

        AND
        (
            (last_sales.last_sold_date IS NULL AND DATE(sp.created_at) <= ?)
            OR DATE(last_sales.last_sold_date) < ?
        )

        ORDER BY sp.created_at ASC
        ", [$toDate, $shop->user_id, $toDate, $fromDate]);
        }

        \Log::info($sellerProducts->first());

        $html = view('backend.sellers.profile.seller_' . $tab, compact(
            'products',
            'shop',
            'addresses',
            'default_shipping_address',
            'page',
            'orders',
            'type',
            'sellerProducts',
            'unpaid_order_payment_notification',
            'payments',
            'assignment_history',
            'low_stock',
            'inactive_products',
            'toDate',
            'fromDate',
            'purchase_orders'
        ))->render();
        return response()->json(['html' => $html]);
    }

    private function getsellerLastLogin($user_id)
    {
        $logFile = storage_path('logs/seller_login.log');
        $lastLoginTime = null;

        if (File::exists($logFile)) {
            $lines = array_reverse(File::lines($logFile)->toArray());

            foreach ($lines as $line) {
                if (str_contains($line, '"user_id":' . $user_id)) {

                    $jsonStart = strpos($line, '{');
                    if ($jsonStart !== false) {
                        $jsonData = json_decode(substr($line, $jsonStart), true);
                        if ($jsonData && isset($jsonData['time'])) {
                            $lastLoginTime = Carbon::parse($jsonData['time']);
                            break;
                        }
                    }
                }
            }
            return $lastLoginTime;
        }
        return null;
    }

    public function suspicious($id)
    {
        $user = User::findOrFail(decrypt($id));

        if ($user->is_suspicious == 1) {
            $user->is_suspicious = 0;
            flash(translate('Sellert unsuspected  Successfully'))->success();
        } else {
            $user->is_suspicious = 1;
            flash(translate('Seller suspected Successfully'))->success();
        }

        $user->save();

        return back();
    }

    public function deleteVerificationFile(Request $request)
    {
        try {
            $index = $request->input('index');
            $shopId = $request->input('shop_id');
            $filePath = $request->input('file_path');
            $shop = Shop::find($shopId);
            $verificationInfo = json_decode($shop->verification_info, true);
            if (file_exists(public_path($filePath))) {
                @unlink(public_path($filePath));
            }

            unset($verificationInfo[$index]);
            $verificationInfo = array_values($verificationInfo);
            $shop->verification_info = json_encode($verificationInfo);
            $shop->save();
            flash(translate('Verification file deleted successfully'))->success();
            return back();
        } catch (\Exception $e) {
            flash(translate('Failed to delete verification file. Please try again later.'))->error();
            return back();
        }
    }
    public function bulk_upload()
    {
        return view('backend.sellers.bulk_upload');
    }

    // public function bulk_store(Request $request)
    // {
    //     $request->validate([
    //         'file' => 'required|mimes:csv,xlsx'
    //     ]);

    //     if ($request->file('file')->getClientOriginalExtension() === 'xlsx' && !extension_loaded('zip')) {
    //         flash(translate('Please enable the Zip extension to import XLSX files.'))->error();
    //         return back();
    //     }

    //     $rows = Excel::toArray([], $request->file('file'));

    //     $success = 0;
    //     $skipped = 0;
    //     $skipReasons = [];

    //     foreach ($rows[0] as $key => $row) {
    //         $rowNumber = $key + 1;

    //         if ($key == 0) {
    //             continue; // skip header
    //         }

    //         if (!isset($row[0]) || count(array_filter($row, function ($value) {
    //             return $value !== null && trim((string) $value) !== '';
    //         })) === 0) {
    //             continue;
    //         }

    //         $name = trim($row[0]);
    //         $email = trim($row[1] ?? '');
    //         $password = trim($row[2] ?? '');
    //         $state = trim($row[3] ?? '');
    //         $district_name = trim($row[4] ?? '');
    //         $block_name = trim($row[5] ?? '');
    //         $sub_name = trim($row[6] ?? '');
    //         $city = trim($row[7] ?? '');

    //         if ($name === '' || $email === '' || $password === '' || $state === '' || $district_name === '' || $block_name === '' || $sub_name === '' || $city === '') {
    //             $skipped++;
    //             $skipReasons[] = 'Row ' . $rowNumber . ': missing one or more required columns.';
    //             continue;
    //         }

    //         $district = City::whereRaw('LOWER(name) = ?', [strtolower($district_name)])->first();
    //         if (!$district || empty($district->district_code)) {
    //             $skipped++;
    //             $skipReasons[] = 'Row ' . $rowNumber . ': district "' . $district_name . '" not found or missing district code.';
    //             continue;
    //         }

    //         $block = Block::whereRaw('LOWER(name) = ?', [strtolower($block_name)])
    //             ->where('district_id', $district->id)
    //             ->first();
    //         if (!$block) {
    //             $skipped++;
    //             $skipReasons[] = 'Row ' . $rowNumber . ': block "' . $block_name . '" not found for district "' . $district_name . '".';
    //             continue;
    //         }

    //         $sub = SubDistrict::whereRaw('LOWER(name) = ?', [strtolower($sub_name)])
    //             ->where('block_id', $block->id)
    //             ->first();
    //         if (!$sub) {
    //             $skipped++;
    //             $skipReasons[] = 'Row ' . $rowNumber . ': sub-district "' . $sub_name . '" not found for block "' . $block_name . '".';
    //             continue;
    //         }

    //         if (User::where('email', $email)->exists()) {
    //             $skipped++;
    //             $skipReasons[] = 'Row ' . $rowNumber . ': email "' . $email . '" already exists.';
    //             continue;
    //         }

    //         $user = new User;
    //         $user->name = $name;
    //         $user->email = $email;
    //         $user->password = Hash::make($password);
    //         $user->user_type = "seller";

    //         $user->state = $state;
    //         $user->district = $district->id;
    //         $user->block = $block->id;
    //         $user->sub_district = $sub->id;
    //         $user->city = $city;
    //         $user->email_verified_at = now();

    //         if ($user->save()) {

    //             $shop = new Shop;
    //             $shop->user_id = $user->id;
    //             $shop->registration_approval = 1;

    //             $shop->shop_id = $this->generateLocationUniqueId(
    //                 $district->id,
    //                 $block->id,
    //                 $sub->id
    //             );

    //             $shop->save();

    //             $success++;
    //         }
    //     }

    //     if (!empty($skipReasons)) {
    //         session()->flash('bulk_import_skip_reasons', $skipReasons);
    //     }

    //     flash(translate($success . ' sellers imported, ' . $skipped . ' skipped.'))->success();
    //     return back();
    // }

    public function bulk_store(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,xlsx'
        ]);

        $worksheets = Excel::toArray([], $request->file('file'));

        $success = 0;
        $skipped = 0;
        $skipReasons = [];

        foreach ($worksheets as $sheetIndex => $rows) {
            $titleRow = $rows[0] ?? [];
            $headerRow = $rows[1] ?? [];
            $sheetTitle = trim((string) ($titleRow[0] ?? ''));

            $normalizedHeaders = [];
            foreach ($headerRow as $headerIndex => $headerValue) {
                $normalizedHeaders[$headerIndex] = strtolower(trim((string) $headerValue));
            }

            $findColumn = function (array $candidates, $defaultIndex = null) use ($normalizedHeaders) {
                foreach ($normalizedHeaders as $index => $header) {
                    if (in_array($header, $candidates, true)) {
                        return $index;
                    }
                }

                return $defaultIndex;
            };

            $nameCol = $findColumn(['name'], 1);
            $genderCol = $findColumn(['gender'], 2);
            $fatherCol = $findColumn(['pita/pati'], 3);
            $dobCol = $findColumn(['dob'], 4);
            $ageCol = $findColumn(['age'], 5);
            $aadhaarCol = $findColumn(['adhar', 'aadhaar'], 6);
            $panCol = $findColumn(['pan'], 7);
            $addressCol = $findColumn(['address'], 8);
            $gramCol = $findColumn(['grampanchayat'], 9);
            $subCol = $findColumn(['janpat panchayat'], 10);
            $districtCol = $findColumn(['zila', 'district'], null);
            $stateCol = $findColumn(['state'], null);
            $pincodeCol = $findColumn(['pin code', 'pincode'], 13);
            $phoneCol = $findColumn(['mobile no.', 'mobile no', 'mobile'], 14);
            $altCol = $findColumn(['alternate numbers', 'alternate number'], 15);
            $whatsappCol = $findColumn(['whatsapp no.', 'whatsapp no'], 16);
            $emailCol = $findColumn(['email id', 'email'], 17);
            $qualificationCol = $findColumn(['qualification'], 18);
            $experienceCol = $findColumn(['exprience', 'experience'], 19);
            $shopAddressCol = $findColumn(["store's add.", 'store add.', 'store address'], 20);
            $shopSizeCol = $findColumn(['shop size'], 21);
            $rentTypeCol = $findColumn(['own / rented', 'own/rented'], 22);
            $monthlyRentCol = $findColumn(['monthly rent'], 23);
            $accountNoCol = $findColumn(['bank ac/no.', 'bank ac/no', 'bank account no.'], 24);
            $bankNameCol = $findColumn(['bank name'], 25);
            $branchCol = $findColumn(['branch name'], 26);
            $ifscCol = $findColumn(['ifsc code', 'ifsc'], 27);
            $securityCol = $findColumn(['security deposit'], 28);
            $paymentStatusCol = $findColumn(['paid/unpaid'], 29);
            $paymentModeCol = $findColumn(['mode of payment'], 30);

            foreach ($rows as $key => $row) {

                $displayRow = $key + 1;
                $sheetLabel = 'Sheet ' . ($sheetIndex + 1);

                // Row 1 is the report title, row 2 is the header row
                if ($key < 2) {
                    continue;
                }

                if (!isset($row[1]) || trim((string) $row[1]) == '') {
                    continue;
                }

                try {


                    // ========================
                    //  EXTRACT DATA
                    // ========================
                    $name = trim((string) ($row[$nameCol] ?? ''));
                    $gender = trim((string) ($row[$genderCol] ?? ''));
                    $father = trim((string) ($row[$fatherCol] ?? ''));
                    $dobValue = $row[$dobCol] ?? '';
                    $dob = trim((string) $dobValue);

                    if ($dobValue !== null && $dobValue !== '') {
                        try {
                            if (is_numeric($dobValue)) {
                                $dob = Carbon::instance(ExcelDate::excelToDateTimeObject($dobValue))->format('Y-m-d');
                            } else {
                                $dob = Carbon::parse($dobValue)->format('Y-m-d');
                            }
                        } catch (\Exception $e) {
                            // Keep the original value if parsing fails.
                        }
                    }
                    $age = trim((string) ($row[$ageCol] ?? ''));
                    $aadhaar = trim((string) ($row[$aadhaarCol] ?? ''));
                    $pan = trim((string) ($row[$panCol] ?? ''));
                    $address = trim((string) ($row[$addressCol] ?? ''));

                    $gram = trim((string) ($row[$gramCol] ?? ''));
                    $subName = trim((string) ($row[$subCol] ?? ''));
                    $districtName = $districtCol !== null ? trim((string) ($row[$districtCol] ?? '')) : '';
                    $stateName = $stateCol !== null ? trim((string) ($row[$stateCol] ?? '')) : '';

                    $pincode = trim((string) ($row[$pincodeCol] ?? ''));
                    $phone = trim((string) ($row[$phoneCol] ?? ''));
                    $alt = trim((string) ($row[$altCol] ?? ''));
                    $whatsapp = trim((string) ($row[$whatsappCol] ?? ''));
                    $email = trim((string) ($row[$emailCol] ?? ''));

                    $qualification = trim((string) ($row[$qualificationCol] ?? ''));
                    $experience = trim((string) ($row[$experienceCol] ?? ''));

                    // SHOP
                    $shop_address = trim((string) ($row[$shopAddressCol] ?? ''));
                    $shop_size = trim((string) ($row[$shopSizeCol] ?? ''));
                    $rent_type = trim((string) ($row[$rentTypeCol] ?? ''));
                    $monthly_rent = trim((string) ($row[$monthlyRentCol] ?? ''));

                    $account_no = trim((string) ($row[$accountNoCol] ?? ''));
                    $bank_name = trim((string) ($row[$bankNameCol] ?? ''));
                    $branch = trim((string) ($row[$branchCol] ?? ''));
                    $ifsc = trim((string) ($row[$ifscCol] ?? ''));

                    $security = trim((string) ($row[$securityCol] ?? ''));
                    $payment_status = trim((string) ($row[$paymentStatusCol] ?? ''));
                    $payment_mode = trim((string) ($row[$paymentModeCol] ?? ''));

                    if ($subName === '' && $sheetTitle !== '' && strtolower($sheetTitle) !== 'details for udyam registration') {
                        $subName = $sheetTitle;
                    }

                    if ($districtName === '') {
                        $districtName = 'Bilaspur';
                    }

                    if ($stateName === '') {
                        $stateName = 'Chhattisgarh';
                    }

                    // ========================
                    // BASIC VALIDATION
                    // ========================
                    if ($name == '') {
                        $skipped++;
                        $skipReasons[] = "$sheetLabel Row $displayRow: Missing required field(s): name";
                        continue;
                    }

                    if ($aadhaar == '') {
                        $aadhaar = 'MISSING-' . ($sheetIndex + 1) . '-' . $displayRow . '-' . time();
                    }

                    // ========================
                    // LOCATION MAPPING
                    // ========================
                    // $state = State::where('name', 'LIKE', "%$stateName%")->first();
                    // if (!$state) {
                    //     $skipped++;
                    //     continue;
                    // }

                    // $district = City::where('name', 'LIKE', "%$districtName%")
                    //     ->where('state_id', $state->id)
                    //     ->first();

                    // if (!$district) {
                    //     $skipped++;
                    //     continue;
                    // }

                    // // Janpat → SubDistrict
                    // $sub = SubDistrict::where('name', 'LIKE', "%$subName%")->first();
                    // if (!$sub) {
                    //     $skipped++;
                    //     continue;
                    // }

                    // $block = Block::where('id', $sub->block_id)->first();

                    $state = null;

                    if ($stateName !== '') {
                        $state = State::where('name', 'LIKE', "%$stateName%")->first();
                    }

                    if (!$state) {
                        $stateName = 'Chhattisgarh';
                        $state = State::where('name', 'LIKE', '%Chhattisgarh%')->first()
                            ?? State::where('name', 'LIKE', '%Chhatisgarh%')->first();
                    }

                    if (!$state) {
                        $skipped++;
                        $skipReasons[] = "$sheetLabel Row $displayRow: Default state 'Chhattisgarh' not found";
                        continue;
                    }

                    $district = null;

                    if ($districtName !== '') {
                        $district = City::where('name', 'LIKE', "%$districtName%")
                            ->where('state_id', $state->id)
                            ->first();
                    }

                    if (!$district && $stateName !== '') {
                        $district = City::where('name', 'LIKE', "%$stateName%")
                            ->where('state_id', $state->id)
                            ->first();
                    }

                    if (!$district) {
                        $districtName = 'Bilaspur';
                        $district = City::where('name', 'LIKE', '%Bilaspur%')
                            ->where('state_id', $state->id)
                            ->first();
                    }

                    if (!$district) {
                        $skipped++;
                        $skipReasons[] = "$sheetLabel Row $displayRow: Default district 'Bilaspur' not found";
                        continue;
                    }

                    // ✅ Janpat
                    $sub = SubDistrict::where('name', 'LIKE', "%$subName%")
                        ->where('district_id', $district->id)
                        ->first();

                    if (!$sub) {
                        $skipped++;
                        $skipReasons[] = "$sheetLabel Row $displayRow: Janpat '$subName' not found in district '{$district->name}'";
                        continue;
                    }

                    // ✅ Block from sub
                    $block = Block::where('id', $sub->block_id)->first();

                    if (!$block || (int) $block->district_id !== (int) $district->id) {
                        $skipped++;
                        $skipReasons[] = "$sheetLabel Row $displayRow: Block not found for Janpat '$subName' in district '{$district->name}'";
                        continue;
                    }

                    // ========================
                    // 👤 CREATE USER
                    // ========================
                    $user = new User;

                    $user->name = $name;
                    $user->gender = $gender;
                    $user->father_husband_name = $father;
                    $user->dob = $dob;
                    $user->age = $age;

                    $user->aadhaar = $aadhaar;
                    $user->phone = $phone ?: $aadhaar;
                    $user->pan = $pan;

                    $user->address = $address;
                    $user->city = $gram;
                    $user->postal_code = $pincode;
                    $user->alternate_phone = $alt;
                    $user->whatsapp_number = $whatsapp;
                    $generatedEmail = strtolower(preg_replace('/\s+/', '', $name)) . rand(100, 999) . '@gmail.com';
                    $candidateEmail = $email !== '' ? strtolower($email) : $generatedEmail;

                    if (User::where('email', $candidateEmail)->exists()) {
                        $candidateEmail = strtolower(preg_replace('/\s+/', '', $name)) . $aadhaar . '@iyouth.local';
                    }

                    if (User::where('email', $candidateEmail)->exists()) {
                        $skipped++;
                        $skipReasons[] = "$sheetLabel Row $displayRow: Email '$candidateEmail' already exists";
                        continue;
                    }

                    $user->email = $candidateEmail;

                    $user->qualification = $qualification;
                    $user->experience = $experience;

                    $user->state = $state->name;
                    $user->district = $district->id;
                    $user->block = $block->id;
                    $user->sub_district = $sub->id;

                    $user->password = Hash::make($user->phone);
                    $user->user_type = "seller";

                    $user->email_verified_at = now();
                    $user->save();

                    // ========================
                    // 🏪 CREATE SHOP
                    // ========================
                    $shop = new Shop;

                    $shop->user_id = $user->id;
                    $shop->name = $name . "'s Shop";

                    $shop->address = $shop_address;
                    $shop->shop_size = $shop_size;
                    $shop->rent_type = $rent_type;
                    $shop->monthly_rent = $monthly_rent;

                    $shop->bank_acc_no = $account_no;
                    $shop->bank_name = $bank_name;
                    $shop->bank_acc_name = $branch;
                    $shop->bank_routing_no = $ifsc;

                    $shop->security_deposit = $security;
                    $shop->payment_status = $payment_status;
                    $shop->payment_mode = $payment_mode;

                    $shop->registration_approval = 1;
                    $shop->verification_status = 1;
                    $shop->shop_id = $this->generateLocationUniqueId($district->id, $block->id, $sub->id);

                    $shop->save();

                    $success++;
                } catch (\Exception $e) {
                    $skipped++;
                    $skipReasons[] = "$sheetLabel Row $displayRow error: " . $e->getMessage();
                }
            }
        }

        if (!empty($skipReasons)) {
            session()->flash('bulk_import_skip_reasons', $skipReasons);
        }

        flash("$success imported, $skipped skipped")->success();
        return back();
    }

    public function export()
    {
        return Excel::download(new SellersExport, 'sellers.xlsx');
    }
}
