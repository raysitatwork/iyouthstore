<?php

namespace App\Http\Controllers;

use App\Http\Controllers\OTPVerificationController;
use App\Http\Requests\SellerRegistrationRequest;
use App\Models\AffiliateConfig;
use App\Models\Block;
use App\Models\BusinessSetting;
use App\Models\CgCity;
use App\Models\City;
use App\Models\RegistrationVerificationCode;
use App\Models\Shop;
use App\Models\SmsTemplate;
use App\Models\SubDistrict;
use App\Models\User;
use App\Services\SendSmsService;
use App\Utility\EmailUtility;
use Auth;
use Cookie;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Session;

class ShopController extends Controller
{

    public function __construct()
    {
        $this->middleware('user', ['only' => ['index']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $shop = Auth::user()->shop;
        return view('seller.shop', compact('shop'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // check if the seller verification enable
        if (get_setting('seller_registration_verify') === '1') {
            abort(404);
        }

        $districts = City::all();
        $subDistricts = SubDistrict::where('status', 1)->get();
        $blocks    = Block::where('status', 1)->get();


        // default registration page
        $email = null;
        $phone = null;
        if (Auth::check()) {
            if ((Auth::user()->user_type == 'admin' || Auth::user()->user_type == 'customer')) {
                flash(translate('Admin or Customer cannot be a seller'))->error();
                return back();
            }
            if (Auth::user()->user_type == 'seller') {
                flash(translate('This user already a seller'))->error();
                return back();
            }
        } else {

            return view('auth.' . get_setting('authentication_layout_select') . '.seller_registration', compact('email', 'phone', 'districts', 'subDistricts', 'blocks'));
        }
    }

    public function getCities(Request $request)
    {
        $district = $request->district;

        $cities = CgCity::where('district_name', $district)
            ->select('city')
            ->get();

        return response()->json($cities);
    }


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

    private function generateLocationUniqueId($districtId, $blockId, $subDistrictId)
    {
        return DB::transaction(function () use ($districtId, $blockId, $subDistrictId) {

            /*
        |--------------------------------------------------------------------------
        | District
        |--------------------------------------------------------------------------
        */

            $district = City::find($districtId);

            if (!$district) {
                throw new \Exception('District not found.');
            }


            /*
        |--------------------------------------------------------------------------
        | District Code
        |--------------------------------------------------------------------------
        |
        | Example:
        | CG05 → 05
        |
        */

            $districtCode = strtoupper(trim($district->district_code));

            // CG हटाना
            $districtCode = preg_replace('/^CG/i', '', $districtCode);


            /*
        |--------------------------------------------------------------------------
        | Block Name
        |--------------------------------------------------------------------------
        */

            $blockName = Block::where('id', $blockId)->value('name');

<<<<<<< Updated upstream
            if (!$blockName) {
                throw new \Exception('Block not found.');
=======
            $prefix = $districtCode . '-' . $blockName;

            $lastShop = Shop::where('shop_id', 'like', $prefix . '-%')
                ->lockForUpdate()
                ->orderBy('id', 'desc')
                ->first();

            if ($lastShop) {
                $lastNumber = (int) substr(
                    $lastShop->shop_id,
                    strrpos($lastShop->shop_id, '-') + 1
                );
                $newNumber = $lastNumber + 1;
            } else {
                $newNumber = 1;
>>>>>>> Stashed changes
            }

            $blockName = strtoupper(trim($blockName));

            // Space / special character remove
            $blockName = preg_replace('/[^A-Z0-9]+/', '', $blockName);


            /*
        |--------------------------------------------------------------------------
        | Same District + Same Block Sellers
        |--------------------------------------------------------------------------
        |
        | इस registration में users table में
        | district और block के NAME save हो रहे हैं।
        |
        */

            $districtName = $district->name;

            $sellerCount = User::where('user_type', 'seller')
                ->where('district', $districtName)
                ->where('block', $blockName)
                ->count();


            /*
        |--------------------------------------------------------------------------
        | Serial Number
        |--------------------------------------------------------------------------
        |
        | First seller  = 01
        | Second seller = 02
        | Third seller  = 03
        |
        */

            $serialNumber = str_pad(
                $sellerCount,
                2,
                '0',
                STR_PAD_LEFT
            );


            /*
        |--------------------------------------------------------------------------
        | Final Shop ID
        |--------------------------------------------------------------------------
        |
        | IYS/05/MAGARLOD/01
        |
        */

            return "IYS/{$districtCode}/{$blockName}/{$serialNumber}";
        });
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(SellerRegistrationRequest $request)
    {

        $district = City::where('id', $request->district)->value('name');
        $block = Block::where('id', $request->block)->value('name');
        $subDistrict = SubDistrict::where('id', $request->sub_district)->value('name');

        $user = new User;
        $user->user_type = $request->user_type;
        $user->name = $request->name;
        $user->email = $request->email;
        $user->phone = $request->phone;

        $user->state = $request->state;
        $user->district = $district;
        $user->block = $block;
        $user->sub_district = $subDistrict;
        $user->city = $request->city;

        $user->password = Hash::make($request->password);
        $user->email_verified_at = date('Y-m-d H:m:s');

        if ($user->save()) {
            $shop = new Shop;
            $shop->user_id = $user->id;
            $shop->name = $request->shop_name;
            $shop->address = $request->address;
            $shop->latitude  = $request->latitude;
            $shop->longitude = $request->longitude;
            $shop->registration_approval = 0;

            // $district = City::where('name', $request->district)
            //     ->value('id');

            $shop->shop_id = $this->generateLocationUniqueId($request->district, $request->block, $request->sub_district);

            $shop->slug = preg_replace('/\s+/', '-', str_replace("/", " ", $request->shop_name));
            $shop->save();

            // Account Opening Email to Seller
            if ((get_email_template_data('registration_email_to_seller', 'status') == 1)) {
                try {
                    EmailUtility::selelr_registration_email('registration_email_to_seller', $user, null);
                } catch (\Exception $e) {
                }
            }

            // Seller Account Opening Email to Admin
            if ((get_email_template_data('seller_reg_email_to_admin', 'status') == 1)) {
                try {
                    EmailUtility::selelr_registration_email('seller_reg_email_to_admin', $user, null);
                } catch (\Exception $e) {
                }
            }

            flash(translate('Your Shop has been created successfully! Your seller account is under review. We will notify you once approved. '))->success();
            return redirect()->route('home');
        }

        flash(translate('Sorry! Something went wrong.'))->error();
        return back();
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
    public function edit($id)
    {
        //
    }

    public function destroy($id)
    {
        //
    }

    public function verifyRegEmailorPhone()
    {
        $type = 'seller';
        if (Auth::check()) {
            if ((Auth::user()->user_type == 'admin' || Auth::user()->user_type == 'customer')) {
                flash(translate('Admin or Customer cannot be a seller'))->error();
                return back();
            }
            if (Auth::user()->user_type == 'seller') {
                flash(translate('This user already a seller'))->error();
                return back();
            }
        } else {
            return view('auth.' . get_setting('authentication_layout_select') . '.reg_verification', compact('type'));
        }
    }

    public function sendRegVerificationCode(Request $request)
    {
        $email = $request->email ?? null;
        $phone = $request->phone != null ? '+' . $request->country_code . $request->phone : null;

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            if (User::where('email', $email)->first() != null) {
                flash(translate('Email already exists.'))->error();
                return back();
            }
        } elseif (User::where('phone', $phone)->first() != null) {
            flash(translate('Phone already exists.'))->error();
            return back();
        }

        $verificationCode = rand(100000, 999999);
        $sellerVerification = RegistrationVerificationCode::updateOrCreate(
            ['email' => $email, 'phone' => $phone],
            ['code' => $verificationCode]
        );
        $success = 1;

        if ($email) {
            try {
                EmailUtility::email_verification_for_registration_seller('email_verification_for_registration_seller', $email, $verificationCode);
            } catch (\Exception $e) {
                $success = 0;
            }
        } else {
            if (addon_is_activated('otp_system')) {
                $sms_template   = SmsTemplate::where('identifier', 'phone_number_verification')->first();
                $sms_body       = $sms_template->sms_body;
                $sms_body       = str_replace('[[code]]', $verificationCode, $sms_body);
                $sms_body       = str_replace('[[site_name]]', env('APP_NAME'), $sms_body);
                $template_id    = $sms_template->template_id;

                (new SendSmsService())->sendSMS($phone, env('APP_NAME'), $sms_body, $template_id);
            }
        }

        if ($success) {
            return redirect()->route('shop-reg.verify_code', encrypt($sellerVerification->id));
        } else {
            flash(translate('Something went wrong!'))->error();
            return back();
        }
    }

    public function regVerifyCode($id)
    {
        // $sellerVerification = $id;
        $sellerVerification = RegistrationVerificationCode::whereId(decrypt($id))->first();
        return view('auth.' . get_setting('authentication_layout_select') . '.seller_verify_confirmation', compact('sellerVerification'));
    }

    public function regVerifyCodeConfirmation(Request $request)
    {
        $email = isset($request->email) ? $request->email : null;
        $phone = isset($request->phone) ? $request->phone  : null;

        $sellerVerification = RegistrationVerificationCode::where('code', $request->verification_code);
        $sellerVerification = $request->email != null ?
            $sellerVerification->where('email', $email) :
            $sellerVerification->where('phone', $phone);
        $sellerVerification = $sellerVerification->first();
        if ($sellerVerification == null) {
            flash(translate('Verification code do not matched'))->error();
            return back();
        } else {
            $sellerVerification->is_verified = 1;
            $sellerVerification->save();
            return view('auth.' . get_setting('authentication_layout_select') . '.seller_registration', compact('sellerVerification', 'email', 'phone'));
        }
    }
}
