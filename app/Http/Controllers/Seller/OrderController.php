<?php

namespace App\Http\Controllers\Seller;

use App\Models\Order;
use App\Models\OrderSellerQueue;
use App\Models\OrdersExport;
use App\Models\ProductStock;
use App\Models\SellerProduct;
use App\Models\SmsTemplate;
use App\Models\User;
use App\Utility\EmailUtility;
use App\Utility\NotificationUtility;
use App\Utility\SmsUtility;
use Auth;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource to seller.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $payment_status = null;
        $delivery_status = null;
        $sort_search = null;
        $orders = DB::table('orders')
            ->orderBy('id', 'desc')
            ->where('seller_id', Auth::user()->id)
            ->select('orders.id')
            ->distinct();

        if ($request->payment_status != null) {
            $orders = $orders->where('payment_status', $request->payment_status);
            $payment_status = $request->payment_status;
        }
        if ($request->delivery_status != null) {
            $orders = $orders->where('delivery_status', $request->delivery_status);
            $delivery_status = $request->delivery_status;
        }
        if ($request->has('search')) {
            $sort_search = $request->search;
            $orders = $orders->where('code', 'like', '%' . $sort_search . '%');
        }

        $orders = $orders->paginate(15);

        foreach ($orders as $key => $value) {
            $order = Order::find($value->id);
            $order->viewed = 1;
            $order->save();
        }

        return view('seller.orders.index', compact('orders', 'payment_status', 'delivery_status', 'sort_search'));
    }

    public function show($id)
    {
        $order = Order::findOrFail(decrypt($id));
        $order_shipping_address = json_decode($order->shipping_address);
        $delivery_boys = User::where('city', $order_shipping_address->city)
            ->where('user_type', 'delivery_boy')
            ->get();

        $order->viewed = 1;
        $order->save();
        return view('seller.orders.show', compact('order', 'delivery_boys'));
    }

    // Update Delivery Status
    public function update_delivery_status(Request $request)
    {
        $authUser = Auth::user();
        $order = Order::findOrFail($request->order_id);
        $order->delivery_viewed = '0';
        $order->delivery_status = $request->status;
        $order->save();

        if ($request->status == 'delivered') {
            $order->delivered_date = date("Y-m-d H:i:s");
            $order->save();
        }

        if ($request->status == 'cancelled' && $order->payment_type == 'wallet') {
            $user = User::where('id', $order->user_id)->first();
            $user->balance += $order->grand_total;
            $user->save();
        }

        // If the order is cancelled and the seller commission is calculated, deduct seller earning
        if ($request->status == 'cancelled' && $order->payment_status == 'paid' && $order->commission_calculated == 1) {
            $sellerEarning = $order->commissionHistory->seller_earning;
            $shop = $order->shop;
            $shop->admin_to_pay -= $sellerEarning;
            $shop->save();
        }

        foreach ($order->orderDetails->where('seller_id', $authUser->id) as $key => $orderDetail) {
            $orderDetail->delivery_status = $request->status;
            $orderDetail->save();

            if ($request->status == 'cancelled') {
                product_restock($orderDetail);
            }
        }

        // Delivery Status change email notification to Admin, seller, Customer
        EmailUtility::order_email($order, $request->status);


        // Delivery Status change SMS notification
        if (addon_is_activated('otp_system') && SmsTemplate::where('identifier', 'delivery_status_change')->first()->status == 1) {
            try {
                SmsUtility::delivery_status_change(json_decode($order->shipping_address)->phone, $order);
            } catch (\Exception $e) {
            }
        }

        //Sends Web Notifications to user
        NotificationUtility::sendNotification($order, $request->status);

        //Sends Firebase Notifications to user

        if (get_setting('google_firebase') == 1 && $order->user->device_token != null) {
            $request->device_token = $order->user->device_token;
            $request->title = "Order updated !";
            $status = str_replace("_", "", $order->delivery_status);
            $request->text = " Your order {$order->code} has been {$status}";

            $request->type = "order";
            $request->id = $order->id;
            $request->user_id = $order->user->id;

            NotificationUtility::sendFirebaseNotification($request);
        }


        if (addon_is_activated('delivery_boy')) {
            if ($authUser->user_type == 'delivery_boy') {
                $deliveryBoyController = new DeliveryBoyController;
                $deliveryBoyController->store_delivery_history($order);
            }
        }

        return 1;
    }


    public function uploadPayment(Request $request)
    {
        $orderId = $request->order_id;

        $order = DB::table('orders')->where('id', $orderId)->first();

        if (!$order) {
            return back()->with('error', 'Order not found.');
        }

        do {
            $transactionId = 'TRX-' . strtoupper(Str::random(8));
        } while (DB::table('orders')->where('transaction_id', $transactionId)->exists());

        if ($request->hasFile('payment_proof')) {

            $file = $request->file('payment_proof');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/payment_proof'), $filename);

            DB::table('orders')
                ->where('id', $orderId)
                ->update([
                    'payment_type'   => $request->payment_method,
                    'transaction_id' => $transactionId,
                    'payment_proof'  => $filename,
                    'payment_status' => 'paid',
                    'updated_at'     => now()
                ]);
        }

        flash(translate('Payment uploaded successfully'))->success();

        return redirect()->route('seller.orders.show', encrypt($orderId));
    }

    // Update Payment Status
    public function update_payment_status(Request $request)
    {
        $order = Order::findOrFail($request->order_id);
        $order->payment_status_viewed = '0';
        $order->save();

        foreach ($order->orderDetails->where('seller_id', Auth::user()->id) as $key => $orderDetail) {
            $orderDetail->payment_status = $request->status;
            $orderDetail->save();
        }

        $status = 'paid';
        foreach ($order->orderDetails as $key => $orderDetail) {
            if ($orderDetail->payment_status != 'paid') {
                $status = 'unpaid';
            }
        }
        $order->payment_status = $status;
        $order->save();


        if ($order->payment_status == 'paid' && $order->commission_calculated == 0) {
            calculateCommissionAffilationClubPoint($order);
        }

        // Payment Status change email notification to Admin, seller, Customer
        if ($request->status == 'paid') {
            EmailUtility::order_email($order, $request->status);
        }

        //Sends Firebase Notifications to Admin, seller, Customer
        NotificationUtility::sendNotification($order, $request->status);
        if (get_setting('google_firebase') == 1 && $order->user->device_token != null) {
            $request->device_token = $order->user->device_token;
            $request->title = "Order updated !";
            $status = str_replace("_", "", $order->payment_status);
            $request->text = " Your order {$order->code} has been {$status}";

            $request->type = "order";
            $request->id = $order->id;
            $request->user_id = $order->user->id;

            NotificationUtility::sendFirebaseNotification($request);
        }


        if (addon_is_activated('otp_system') && SmsTemplate::where('identifier', 'payment_status_change')->first()->status == 1) {
            try {
                SmsUtility::payment_status_change(json_decode($order->shipping_address)->phone, $order);
            } catch (\Exception $e) {
            }
        }
        return 1;
    }

    public function orderBulkExport(Request $request)
    {
        if ($request->id) {
            return Excel::download(new OrdersExport($request->id), 'orders.xlsx');
        }
        return back();
    }

    // public function rejectOrder(Request $request)
    // {
    //     $request->validate([
    //         'order_id' => ['required', 'integer'],
    //     ]);

    //     $order = Order::findOrFail($request->order_id);

    //     if ((int) $order->seller_id !== (int) Auth::id()) {
    //         flash(translate('You are not allowed to reject this order.'))->error();
    //         return back();
    //     }

    //     DB::transaction(function () use ($order) {
    //         $activeQueue = OrderSellerQueue::where('order_id', $order->id)
    //             ->where('seller_id', Auth::id())
    //             ->where('status', 'pending')
    //             ->first();

    //         if ($activeQueue) {
    //             $activeQueue->update(['status' => 'rejected']);
    //         }

    //         $nextSeller = OrderSellerQueue::where('order_id', $order->id)
    //             ->where('status', 'pending')
    //             ->orderBy('priority')
    //             ->first();

    //         if ($nextSeller) {
    //             $order->seller_id = $nextSeller->seller_id;
    //             $order->status = 'pending_acceptance';
    //             $order->save();

    //             $order->orderDetails()->update([
    //                 'seller_id' => $nextSeller->seller_id,
    //             ]);
    //         } else {
    //             $order->seller_id = null;
    //             $order->status = 'failed';
    //             $order->save();
    //         }
    //     });

    //     flash(translate('Order rejected successfully'))->success();

    //     return back();
    // }

    // public function acceptOrder(Request $request)
    // {
    //     $request->validate([
    //         'order_id' => ['required', 'integer'],
    //     ]);

    //     $order = Order::findOrFail($request->order_id);

    //     if ((int) $order->seller_id !== (int) Auth::id()) {
    //         flash(translate('You are not allowed to accept this order.'))->error();
    //         return back();
    //     }

    //     DB::transaction(function () use ($order) {
    //         $activeQueue = OrderSellerQueue::where('order_id', $order->id)
    //             ->where('seller_id', Auth::id())
    //             ->where('status', 'pending')
    //             ->first();

    //         $order->seller_id = Auth::id();
    //         $order->status = 'confirmed';
    //         $order->delivery_status = 'confirmed';
    //         $order->delivery_viewed = 0;
    //         $order->save();

    //         $order->orderDetails()->update([
    //             'seller_id' => Auth::id(),
    //             'delivery_status' => 'confirmed',
    //         ]);

    //         if ($activeQueue) {
    //             OrderSellerQueue::where('order_id', $order->id)
    //                 ->where('seller_id', Auth::id())
    //                 ->update(['status' => 'accepted']);

    //             OrderSellerQueue::where('order_id', $order->id)
    //                 ->where('seller_id', '!=', Auth::id())
    //                 ->update(['status' => 'skipped']);
    //         }
    //     });

    //     flash(translate('Order accepted successfully'))->success();

    //     return back();
    // }

    // public function rejectOrder(Request $request)
    // {
    //     $order = Order::lockForUpdate()->findOrFail($request->order_id);

    //     if ($order->seller_id != Auth::id()) {
    //         return back();
    //     }

    //     DB::transaction(function () use ($order) {

    //         OrderSellerQueue::where('order_id', $order->id)
    //             ->where('seller_id', Auth::id())
    //             ->update(['status' => 'rejected']);


    //         $nextSeller = OrderSellerQueue::where('order_id', $order->id)
    //             ->where('status', 'pending')
    //             ->orderBy('priority')
    //             ->first();

    //         if ($nextSeller) {
    //             $order->seller_id = $nextSeller->seller_id;
    //             $order->status = 'pending_acceptance';
    //             $order->save();

    //             $order->orderDetails()->update([
    //                 'seller_id' => $nextSeller->seller_id
    //             ]);
    //         } else {
    //             $order->status = 'failed';
    //             $order->seller_id = null;
    //             $order->save();
    //         }
    //     });

    //     return back();
    // }


    //     public function rejectOrder(Request $request)
    // {
    //     $order = Order::lockForUpdate()->findOrFail($request->order_id);

    //     if ($order->seller_id != Auth::id()) {
    //         return back();
    //     }

    //     DB::beginTransaction();

    //     try {

    //         OrderSellerQueue::where('order_id', $order->id)
    //             ->where('seller_id', Auth::id())
    //             ->update(['status' => 'rejected']);


    //         $nextSeller = OrderSellerQueue::where('order_id', $order->id)
    //             ->where('status', 'pending')
    //             ->orderBy('priority')
    //             ->first();

    //         if ($nextSeller) {

    //             $order->seller_id = $nextSeller->seller_id;
    //             $order->status = 'pending_acceptance';
    //             $order->save();


    //             $order->orderDetails()->update([
    //                 'seller_id' => $nextSeller->seller_id
    //             ]);

    //         } else {

    //             $order->status = 'failed';
    //             $order->seller_id = null;
    //             $order->save();
    //         }

    //         DB::commit();

    //         return back()->with('success', 'Order rejected successfully');

    //     } catch (\Exception $e) {
    //         DB::rollBack();

    //         // optional: log error
    //         \Log::error('Reject Order Error: ' . $e->getMessage());

    //         return back()->with('error', 'Something went wrong');
    //     }
    // }



    public function rejectOrder(Request $request)
    {
        DB::beginTransaction();

        try {

            $order = Order::lockForUpdate()->findOrFail($request->order_id);

            if ($order->seller_id != Auth::id()) {
                DB::rollBack();
                return back();
            }

            OrderSellerQueue::where('order_id', $order->id)
                ->where('seller_id', Auth::id())
                ->update(['status' => 'rejected']);

            $nextSeller = OrderSellerQueue::where('order_id', $order->id)
                ->where('status', 'pending')
                ->orderBy('priority')
                ->first();

            if ($nextSeller) {
                $order->seller_id = $nextSeller->seller_id;
                $order->status = 'pending_acceptance';
                $order->save();

                $order->orderDetails()->update([
                    'seller_id' => $nextSeller->seller_id
                ]);
            } else {
                $order->status = 'failed';
                $order->seller_id = null;
                $order->save();
            }

            DB::commit();

            return back()->with('success', 'Order rejected successfully');
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Reject Order Error: ' . $e->getMessage());

            return back()->with('error', 'Something went wrong');
        }
    }


    public function acceptOrder(Request $request)
    {
        $order = Order::lockForUpdate()->findOrFail($request->order_id);

        if ($order->seller_id != Auth::id()) {
            return back();
        }

        DB::transaction(function () use ($order) {

            foreach ($order->orderDetails as $detail) {

                $sellerProduct = SellerProduct::where('product_id', $detail->product_id)
                    ->where('seller_id', Auth::id())
                    ->lockForUpdate()
                    ->first();

                if (!$sellerProduct || $sellerProduct->stock < $detail->quantity) {
                    throw new \Exception("Out of stock");
                }

                $sellerProduct->decrement('stock', $detail->quantity);
            }

            $order->status = 'confirmed';
            $order->save();

            OrderSellerQueue::where('order_id', $order->id)
                ->where('seller_id', Auth::id())
                ->update(['status' => 'accepted']);

            OrderSellerQueue::where('order_id', $order->id)
                ->where('seller_id', '!=', Auth::id())
                ->update(['status' => 'skipped']);
        });

        return back();
    }


    // public function getOrderProducts($id)
    // {
    //     $order = Order::findOrFail($id);

    //     $html = '';

    //     foreach ($order->orderDetails->where('seller_id', Auth::id())->where('delivery_status', 'pending') as $detail) {

    //         $html .= '
    //         <div>
    //             <input type="checkbox" name="order_detail_ids[]" value="' . $detail->id . '">
    //             ' . $detail->product->name . ' - Qty: ' . $detail->quantity . '
    //         </div>
    //     ';
    //     }

    //     return $html;
    // }


    public function getOrderProducts($id)
{
    $order = Order::findOrFail($id);

    $html = '';

    foreach ($order->orderDetails->where('seller_id', Auth::id())->where('delivery_status', 'pending') as $detail) {

        $html .= '
        <div class="card mb-2 shadow-sm border-0">
            <div class="card-body d-flex align-items-center justify-content-between">

                <div class="form-check d-flex align-items-center">
                    <input class="form-check-input me-3" type="checkbox"
                        name="order_detail_ids[]" value="' . $detail->id . '"
                        id="product_' . $detail->id . '">

                    <label class="form-check-label fw-semibold" for="product_' . $detail->id . '">
                        ' . $detail->product->name . '
                    </label>
                </div>

                <span class="badge bg-primary text-white px-3 py-2">
                    Qty: ' . $detail->quantity . '
                </span>

            </div>
        </div>
        ';
    }

    return $html;
}


    // public function acceptPartialOrder(Request $request)
    // {
    //     $order = Order::findOrFail($request->order_id);

    //     // ✔ security check
    //     if ($order->seller_id != Auth::id()) {
    //         return back();
    //     }

    //     $selected = $request->product_ids ?? [];

    //     if (empty($selected)) {
    //         flash('Select at least one product')->error();
    //         return back();
    //     }

    //     DB::beginTransaction();

    //     try {

    //         $remaining = [];

    //         foreach ($order->orderDetails as $detail) {

    //             if (in_array($detail->product_id, $selected)) {

    //                 // ✔ accepted product
    //                 $detail->delivery_status = 'confirmed';
    //                 $detail->save();
    //             } else {


    //                 $remaining[] = $detail;
    //             }
    //         }


    //         $order->status = 'confirmed';
    //         $order->save();



    //         if (!empty($remaining)) {

    //             // ✔ create new order
    //             $newOrder = $order->replicate();
    //             $newOrder->status = 'pending_acceptance';
    //             $newOrder->delivery_status = 'pending';
    //             $newOrder->seller_id = null;
    //             $newOrder->code = date('Ymd-His') . rand(10, 99);
    //             $newOrder->save();



    //             $nextSeller = OrderSellerQueue::where('order_id', $order->id)
    //                 ->where('seller_id', '!=', Auth::id())
    //                 ->orderBy('priority')
    //                 ->first();

    //             if ($nextSeller) {
    //                 $newOrder->seller_id = $nextSeller->seller_id;
    //                 $newOrder->save();
    //             }



    //             foreach ($remaining as $detail) {
    //                 $detail->order_id = $newOrder->id;
    //                 $detail->seller_id = $newOrder->seller_id;
    //                 $detail->delivery_status = 'pending';
    //                 $detail->save();
    //             }
    //         }

    //         DB::commit();
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         dd($e);
    //     }

    //     return back();
    // }

    // public function acceptPartialOrderSimple(Request $request)
    // {
    //     $selectedDetailIds = $request->order_detail_ids ?? [];

    //     if (empty($selectedDetailIds)) {
    //         flash('Select at least one product')->error();
    //         return back();
    //     }

    //     DB::beginTransaction();

    //     try {
    //         $order = Order::lockForUpdate()->findOrFail($request->order_id);

    //         if ((int) $order->seller_id !== (int) Auth::id()) {
    //             DB::rollBack();
    //             return back();
    //         }

    //         $queueRows = OrderSellerQueue::where('order_id', $order->id)
    //             ->orderBy('priority')
    //             ->lockForUpdate()
    //             ->get();

    //         $pendingQueueRows = $queueRows->where('status', 'pending')->values();

    //         $currentSellerQueue = $pendingQueueRows->firstWhere('seller_id', Auth::id());

    //         if (!$currentSellerQueue) {
    //             DB::rollBack();
    //             flash('This order is not pending for you')->error();
    //             return back();
    //         }

    //         $orderDetails = $order->orderDetails()
    //             ->where('seller_id', Auth::id())
    //             ->where('delivery_status', 'pending')
    //             ->get();

    //         $acceptedTotal = 0;
    //         $remainingTotal = 0;
    //         $remainingDetails = [];

    //         foreach ($orderDetails as $detail) {
    //             if (in_array($detail->id, $selectedDetailIds)) {
    //                 // $sellerProduct = SellerProduct::where('product_id', $detail->product_id)
    //                 //     ->where('seller_id', Auth::id())
    //                 //     ->lockForUpdate()
    //                 //     ->first();

    //                 // if (!$sellerProduct || $sellerProduct->stock < $detail->quantity) {
    //                 //     throw new \Exception('Out of stock');
    //                 // }

    //                 // $sellerProduct->decrement('stock', $detail->quantity);

    //                 $detail->seller_id = Auth::id();
    //                 $detail->delivery_status = 'confirmed';
    //                 $detail->save();

    //                 $acceptedTotal += $detail->price;
    //             } else {
    //                 $remainingDetails[] = $detail;
    //                 $remainingTotal += $detail->price;
    //             }
    //         }

    //         $order->grand_total = $acceptedTotal;
    //         $order->seller_id = Auth::id();
    //         $order->status = 'confirmed';
    //         $order->delivery_status = 'confirmed';
    //         $order->save();

    //         OrderSellerQueue::where('order_id', $order->id)
    //             ->where('seller_id', Auth::id())
    //             ->update(['status' => 'accepted']);

    //         OrderSellerQueue::where('order_id', $order->id)
    //             ->where('seller_id', '!=', Auth::id())
    //             ->update(['status' => 'skipped']);

    //         if (!empty($remainingDetails)) {
    //             $nextQueueRows = $pendingQueueRows
    //                 ->filter(function ($row) {
    //                     return (int) $row->seller_id !== (int) Auth::id();
    //                 })
    //                 ->values();

    //             $newOrder = $order->replicate();
    //             $newOrder->seller_id = null;
    //             $newOrder->status = 'pending_acceptance';
    //             $newOrder->delivery_status = 'pending';
    //             $newOrder->grand_total = $remainingTotal;
    //             $newOrder->code = date('Ymd-His') . rand(10, 99);
    //             $newOrder->save();

    //             $newSellerId = null;

    //             foreach ($nextQueueRows as $index => $queueRow) {
    //                 OrderSellerQueue::create([
    //                     'order_id' => $newOrder->id,
    //                     'seller_id' => $queueRow->seller_id,
    //                     'priority' => $index + 1,
    //                     'status' => 'pending'
    //                 ]);

    //                 if ($newSellerId === null) {
    //                     $newSellerId = $queueRow->seller_id;
    //                 }
    //             }

    //             if ($newSellerId !== null) {
    //                 $newOrder->seller_id = $newSellerId;
    //                 $newOrder->save();
    //             } else {
    //                 $newOrder->status = 'failed';
    //                 $newOrder->save();
    //             }

    //             foreach ($remainingDetails as $detail) {
    //                 $detail->order_id = $newOrder->id;
    //                 $detail->seller_id = $newSellerId;
    //                 $detail->delivery_status = 'pending';
    //                 $detail->save();
    //             }
    //         }

    //         DB::commit();
    //         return back();
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         flash($e->getMessage())->error();
    //         return back();
    //     }
    // }

    public function acceptPartialOrderSimple(Request $request)
    {
        $selectedDetailIds = $request->order_detail_ids ?? [];

        if (empty($selectedDetailIds)) {
            flash('Select at least one product')->error();
            return back();
        }

        DB::beginTransaction();

        try {
            $order = Order::lockForUpdate()->findOrFail($request->order_id);

            if ((int) $order->seller_id !== (int) Auth::id()) {
                DB::rollBack();
                return back();
            }

            $queueRows = OrderSellerQueue::where('order_id', $order->id)
                ->orderBy('priority')
                ->lockForUpdate()
                ->get();

            $pendingQueueRows = $queueRows->where('status', 'pending')->values();

            $currentSellerQueue = $pendingQueueRows->firstWhere('seller_id', Auth::id());

            if (!$currentSellerQueue) {
                DB::rollBack();
                flash('This order is not pending for you')->error();
                return back();
            }

            $orderDetails = $order->orderDetails()
                ->where('seller_id', Auth::id())
                ->where('delivery_status', 'pending')
                ->get();

            $acceptedTotal = 0;
            $remainingTotal = 0;
            $remainingDetails = [];

            foreach ($orderDetails as $detail) {
                if (in_array($detail->id, $selectedDetailIds)) {

                    $detail->delivery_status = 'confirmed';
                    $detail->save();

                    $acceptedTotal += $detail->price;
                } else {
                    $remainingDetails[] = $detail;
                    $remainingTotal += $detail->price;
                }
            }


            $order->grand_total = $acceptedTotal;
            $order->seller_id = Auth::id();
            $order->status = 'confirmed';
            $order->delivery_status = 'confirmed';
            $order->save();


            OrderSellerQueue::where('order_id', $order->id)
                ->where('seller_id', Auth::id())
                ->update(['status' => 'accepted']);

            OrderSellerQueue::where('order_id', $order->id)
                ->where('seller_id', '!=', Auth::id())
                ->update(['status' => 'skipped']);


            if (!empty($remainingDetails)) {

                $nextQueueRows = $pendingQueueRows
                    ->filter(function ($row) {
                        return (int) $row->seller_id !== (int) Auth::id();
                    })
                    ->values();


                $newOrder = $order->replicate();
                $newOrder->seller_id = null;
                $newOrder->status = 'pending_acceptance';
                $newOrder->delivery_status = 'pending';
                $newOrder->grand_total = $remainingTotal;
                $newOrder->code = date('Ymd-His') . rand(10, 99);
                $newOrder->save();

                $validQueue = [];

                foreach ($nextQueueRows as $queueRow) {

                    $hasAnyStock = false;

                    foreach ($remainingDetails as $detail) {

                        $sellerProduct = SellerProduct::where('product_id', $detail->product_id)
                            ->where('seller_id', $queueRow->seller_id)
                            ->where('stock', '>', 0)
                            ->first();

                        if ($sellerProduct && $sellerProduct->stock >= $detail->quantity) {
                            $hasAnyStock = true;
                            break;
                        }
                    }

                    if ($hasAnyStock) {
                        $validQueue[] = $queueRow->seller_id;
                    }
                }

                foreach ($validQueue as $index => $sellerId) {
                    OrderSellerQueue::create([
                        'order_id' => $newOrder->id,
                        'seller_id' => $sellerId,
                        'priority' => $index + 1,
                        'status' => 'pending'
                    ]);
                }

                $newSellerId = $validQueue[0] ?? null;

                if ($newSellerId !== null) {
                    $newOrder->seller_id = $newSellerId;
                    $newOrder->save();
                } else {
                    $newOrder->status = 'failed';
                    $newOrder->save();
                }

                foreach ($remainingDetails as $detail) {
                    $detail->order_id = $newOrder->id;
                    $detail->seller_id = $newSellerId;
                    $detail->delivery_status = 'pending';
                    $detail->save();
                }
            }

            DB::commit();
            return back();
        } catch (\Exception $e) {
            DB::rollBack();
            flash($e->getMessage())->error();
            return back();
        }
    }
}
