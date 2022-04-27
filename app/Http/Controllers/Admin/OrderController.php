<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Model\BusinessSetting;
use App\Model\Coupon;
use App\Model\DeliveryMan;
use App\Model\Order;
use App\Model\OrderDetail;
use App\Model\Product;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Rap2hpoutre\FastExcel\FastExcel;
use App\CentralLogics\CouponLogic;
use App\Model\Category;
use App\Model\DeliveryHistory;
use App\Model\DMReview;
use App\Model\OrderDeliveryHistory;
use App\Model\Review;
use App\Model\TrackDeliveryman;
use Exception;

class OrderController extends Controller
{
    public function list(Request $request, $status)
    {
        $query_param = [];
        $search = $request['search'];
        if ($request->has('search')) {
            $key = explode(' ', $request['search']);
            $query = Order::where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('id', 'like', "%{$value}%")
                        ->orWhere('order_status', 'like', "%{$value}%")
                        ->orWhere('transaction_reference', 'like', "%{$value}%");
                }
            });
            $query_param = ['search' => $request['search']];
        } else {
            if (session()->has('branch_filter') == false) {
                session()->put('branch_filter', 0);
            }
            Order::where(['checked' => 0])->update(['checked' => 1]);
            if (session('branch_filter') == 0) {
                if ($status != 'all') {
                    $query = Order::with(['customer', 'branch'])->where(['order_status' => $status]);
                } else {
                    $query = Order::with(['customer', 'branch']);
                }
            } else {
                if ($status != 'all') {
                    $query = Order::with(['customer', 'branch'])->where(['order_status' => $status, 'branch_id' => session('branch_filter')]);
                } else {
                    $query = Order::with(['customer', 'branch'])->where(['branch_id' => session('branch_filter')]);
                }
            }
        }

        $orders = $query->where('order_type', '!=', 'pos')->latest()->paginate(Helpers::getPagination())->appends($query_param);
        return view('admin-views.order.list', compact('orders', 'status', 'search'));
    }


    protected function getProductCategories($departmentsIds)
    {
        $departmentsRows = Category::whereIn('id', $departmentsIds)->select('id', 'name')->get()->toArray();
        $departments = [];
        foreach($departmentsRows as $dep) {
            $departments[$dep['id']] = [
                'id' => $dep['id'],
                'name' => $dep['name']
            ];
        }
        return $departments;
    }

    /**
     * Get all categories in the given order
     *
     * @param \App\Model\Order;
     * @return Array;
     */
    protected function getOrderDepartments(Order $order)
    {
        $departmentsIds = collect($order->products)->map(function ($item) {
            return collect(json_decode($item->category_ids))->map(function ($cat) {
                return $cat->id;
            });
        })->flatten();

        return $this->getProductCategories($departmentsIds);
    }

    public function details(Request $request, $id)
    {
        $order = Order::with(['details', 'products', 'delivery_man'])->where(['id' => $id])->first();
        $departments = $this->getOrderDepartments($order);
        $editing = false;
        /**
         * Updates
         *  add condition to enable editing if empty cart
         */
        if ($request->session()->has('order_cart')) {
            $cart = session()->get('order_cart');
            if (count($cart) > 0 && $cart[0]->order_id == $order->id) {
                $editing = true;
            } else if (count($cart) === 0) {
                $editing = true;
            } else {
                session()->forget('order_cart');
            }
        }
        $deliveryMen = DeliveryMan::all();
        $category = $request->query('category_id', 0);
        $products = Product::when($request->category_id, function ($query) use ($request) {
            $query->where('category_ids', 'LIKE', '%"id":"' . $request->category_id . '"%');
        })
            ->when($request->keyword, function ($query) use ($request) {
                $query->where('name', 'LIKE', "%{$request->keyword}%");
            })
            ->latest()->paginate(6);
        $categories = Category::active()->get();
        $campaign_order = isset($order->details[0]->campaign) ? true : false;
        if (isset($order)) {
            //return view('admin-views.order.order-view', compact('order','editing','deliveryMen'));
            return view('admin-views.order.order-view')->with([
                'order' => $order,
                'editing' => $editing,
                'deliveryMen' => $deliveryMen,
                'campaign_order' => $campaign_order,
                'categories' => $categories,
                'category' => $category,
                'products' => $products,
                'keyword' => $request->query('keyword', false),
                'departments' => $departments
            ]);
        } else {
            Toastr::info('No more orders!');
            return back();
        }
    }

    public function edit(Request $request, Order $order)
    {
        /*  $order = Order::with(['details', 'restaurant'=>function($query){
            return $query->withCount('orders');
        }, 'customer'=>function($query){
            return $query->withCount('orders');
        },'delivery_man'=>function($query){
            return $query->withCount('orders');
        }, 'details.product'=>function($query){
            return $query->withoutGlobalScope(RestaurantScope::class);
        }, 'details.campaign'=>function($query){
            return $query->withoutGlobalScope(RestaurantScope::class);
        }])->where(['id' => $order->id])->Notpos()->first(); */
        $order = Order::with([
            'details',
            'customer' => function ($query) {
                return $query->withCount('orders');
            }, 'delivery_man' => function ($query) {
                return $query->withCount('orders');
            }, 'details.product' => function ($query) {
                return $query->withoutGlobalScope(RestaurantScope::class);
            }

        ])
            ->where(['id' => $order->id])->first();
        if ($request->cancle) {
            if ($request->session()->has(['order_cart'])) {
                session()->forget(['order_cart']);
            }
            return back();
        }
        $cart = collect([]);
        foreach ($order->details as $details) {
            unset($details['product_details']);
            $details['status'] = true;
            $cart->push($details);
        }

        if ($request->session()->has('order_cart')) {
            session()->forget('order_cart');
        } else {
            $request->session()->put('order_cart', $cart);
        }
        return back();
    }

    public function search(Request $request)
    {
        $key = explode(' ', $request['search']);
        $orders = Order::where(function ($q) use ($key) {
            foreach ($key as $value) {
                $q->orWhere('id', 'like', "%{$value}%")
                    ->orWhere('order_status', 'like', "%{$value}%")
                    ->orWhere('transaction_reference', 'like', "%{$value}%");
            }
        })->get();
        return response()->json([
            'view' => view('admin-views.order.partials._table', compact('orders'))->render()
        ]);
    }


    public function quick_view(Request $request)
    {

        $product = $product = Product::findOrFail($request->product_id);
        $item_type = 'product';
        $order_id = $request->order_id;
        $productDepartmentsIds = [];
        $productCategories = [];
        if (isset($product->category_ids)) {
            foreach(json_decode($product->category_ids) as $cat) {
                $productDepartmentsIds[] = $cat->id;
                $productCategories[] = [
                    'id' => $cat->id
                ];
            }
        }
        $departments = $this->getProductCategories($productDepartmentsIds);
        return response()->json([
            'success' => 1,
            'view' => view('admin-views.order.partials._quick-view', compact(
                'product', 'order_id', 'item_type', 'departments', 'productCategories'
                ))->render(),
        ]);
    }

    public function status(Request $request)
    {
        $order = Order::find($request->id);
        if ($request->order_status == 'out_for_delivery' && $order['delivery_man_id'] == null && $order['order_type'] != 'take_away') {
            Toastr::warning('Please assign delivery man first!');
            return back();
        }
        $order->order_status = $request->order_status;
        $order->save();

        $fcm_token = $order->customer->cm_firebase_token;
        $value = Helpers::order_status_update_message($request->order_status);
        try {
            if ($value) {
                $data = [
                    'title' => 'Order',
                    'description' => $value,
                    'order_id' => $order['id'],
                    'image' => '',
                ];
                Helpers::send_push_notif_to_device($fcm_token, $data);
            }
        } catch (\Exception $e) {
            Toastr::warning(\App\CentralLogics\translate('Push notification failed for Customer!'));
        }

        //delivery man notification
        if ($request->order_status == 'processing' && $order->delivery_man != null) {
            $fcm_token = $order->delivery_man->fcm_token;
            $value = \App\CentralLogics\translate('One of your order is in processing');
            try {
                if ($value) {
                    $data = [
                        'title' => 'Order',
                        'description' => $value,
                        'order_id' => $order['id'],
                        'image' => '',
                    ];
                    Helpers::send_push_notif_to_device($fcm_token, $data);
                }
            } catch (\Exception $e) {
                Toastr::warning(\App\CentralLogics\translate('Push notification failed for DeliveryMan!'));
            }
        }

        Toastr::success('Order status updated!');
        return back();
    }


    public function add_delivery_man($order_id, $delivery_man_id)
    {
        if ($delivery_man_id == 0) {
            return response()->json([], 401);
        }
        $order = Order::find($order_id);
        $rejectedOrderStatus = ['delivered', 'returned', 'failed', 'canceled', 'scheduled'];

        /* if($order->order_status == 'delivered' || $order->order_status == 'returned' || $order->order_status == 'failed' || $order->order_status == 'canceled' || $order->order_status == 'scheduled') {
            return response()->json(['status' => false], 200);
        } */

        /**
         * Collect all rejected status into one array to be more clean and readable and check if current order status in this
         * array, if in array it will return json with status => false
         * else it will change current delivery man
         */
        if (in_array($order->order_status, $rejectedOrderStatus)) {
            return response()->json(['status' => false, 'message' => "Cannot change delivery man, order is {$order->order_status}"], 200);
        }

        $order->delivery_man_id = $delivery_man_id;
        $order->save();

        $fcm_token = $order->delivery_man->fcm_token;
        $value = Helpers::order_status_update_message('del_assign');
        try {
            if ($value) {
                $data = [
                    'title' => 'Order',
                    'description' => $value,
                    'order_id' => $order['id'],
                    'image' => '',
                ];
                Helpers::send_push_notif_to_device($fcm_token, $data);
            }
        } catch (\Exception $e) {
            Toastr::warning(\App\CentralLogics\translate('Push notification failed for DeliveryMan!'));
        }

        return response()->json(['status' => true], 200);
    }

    public function payment_status(Request $request)
    {
        $order = Order::find($request->id);
        if ($request->payment_status == 'paid' && $order['transaction_reference'] == null && $order['payment_method'] != 'cash_on_delivery') {
            Toastr::warning('Add your payment reference code first!');
            return back();
        }
        $order->payment_status = $request->payment_status;
        $order->save();
        Toastr::success('Payment status updated!');
        return back();
    }

    public function update_shipping(Request $request, $id)
    {
        $request->validate([
            'contact_person_name' => 'required',
            'address_type' => 'required',
            'contact_person_number' => 'required',
            'address' => 'required'
        ]);

        $address = [
            'contact_person_name' => $request->contact_person_name,
            'contact_person_number' => $request->contact_person_number,
            'address_type' => $request->address_type,
            'address' => $request->address,
            'longitude' => $request->longitude,
            'latitude' => $request->latitude,
            'created_at' => now(),
            'updated_at' => now()
        ];

        DB::table('customer_addresses')->where('id', $id)->update($address);
        Toastr::success('Payment status updated!');
        return back();
    }

    public function generate_invoice($id)
    {
        $order = Order::where('id', $id)->first();
        return view('admin-views.order.invoice', compact('order'));
    }

    public function add_payment_ref_code(Request $request, $id)
    {
        Order::where(['id' => $id])->update([
            'transaction_reference' => $request['transaction_reference']
        ]);

        Toastr::success('Payment reference code is added!');
        return back();
    }

    public function branch_filter($id)
    {
        session()->put('branch_filter', $id);
        return back();
    }

    public function export_data()
    {
        $orders = Order::all();
        return (new FastExcel($orders))->download('orders.xlsx');
    }

    public function quick_view_cart_item(Request $request)
    {
        //return session('order_cart')[$request->key];
        $cart_item = session('order_cart')[$request->key];
        $order_id = $request->order_id;
        $item_key = $request->key;
        $product = $cart_item->product ? $cart_item->product : $cart_item->campaign;
        $item_type = $cart_item->product ? 'product' : 'campaign';
        $departments = $this->getOrderDepartments(Order::find($request->order_id));
        return response()->json([
            'success' => 1,
            'view' => view('admin-views.order.partials._quick-view-cart-item', compact('order_id', 'product', 'cart_item', 'item_key', 'item_type', 'departments'))->render(),
        ]);
    }

    public function add_to_cart(Request $request)
    {
        if ($request->item_type == 'product') {
            $product = Product::find($request->id);
        }
        /* else
        {
            $product = ItemCampaign::find($request->id);
        } */
        $data = OrderDetail::find($request->order_details_id);
        if ($data) {

            $data = $data->replicate();
        } else {
            $data = new OrderDetail();
        }
        if ($request->order_details_id) {
            $data['id'] = $request->order_details_id;
        }

        $data['product_id'] = $request->item_type == 'product' ? $product->id : null;
        //$data['item_campaign_id'] = $request->item_type=='campaign'?$product->id:null;
        $data['order_id'] = $request->order_id;
        $str = '';
        $price = 0;
        $addon_price = 0;

        //Gets all the choice values of customer choice option and generate a string like Black-S-Cotton
        foreach (json_decode($product->choice_options) as $key => $choice) {
            if ($str != null) {
                $str .= '-' . str_replace(' ', '', $request[$choice->name]);
            } else {
                $str .= str_replace(' ', '', $request[$choice->name]);
            }
        }
        $data['variant'] = json_encode([]);
        $data['variation'] = json_encode([]);
        if ($request->session()->has('order_cart') && !isset($request->cart_item_key)) {
            if (count($request->session()->get('order_cart')) > 0) {
                foreach ($request->session()->get('order_cart') as $key => $cartItem) {
                    if ($cartItem['product_id'] == $request['id'] && $cartItem['status'] == true) {
                        if (count(json_decode($cartItem['variation'], true)) > 0) {
                            if (json_decode($cartItem['variation'], true)[0]['type'] == $str) {
                                return response()->json([
                                    'data' => 1
                                ]);
                            }
                        } else {
                            return response()->json([
                                'data' => 1
                            ]);
                        }
                    }
                }
            }
        }
        //Check the string and decreases quantity for the stock
        if ($str != null) {
            $count = count(json_decode($product->variations));
            for ($i = 0; $i < $count; $i++) {
                if (json_decode($product->variations)[$i]->type == $str) {
                    $price = json_decode($product->variations)[$i]->price;
                }
            }
            $data['variation'] = json_encode([["type" => $str, "price" => $price]]);
        } else {
            $price = $product->price;
        }

        $data['quantity'] = $request['quantity'];
        $data['price'] = $price;
        $data['status'] = true;
        $data['discount_on_product'] = Helpers::product_discount_calculate($product, $price, $product->restaurant);
        $data["discount_type"] = "discount_on_product";
        $data["tax_amount"] = Helpers::tax_calculate($product, $price);
        $add_on_ids = [];
        $add_on_qtys = [];

        if ($request['addon_id']) {
            foreach ($request['addon_id'] as $id) {
                $addon_price += $request['addon-price' . $id] * $request['addon-quantity' . $id];
                $add_on_qtys[] = $request['addon-quantity' . $id];
            }
            $add_on_ids = $request['addon_id'];
        }

        $addon_data = Helpers::calculate_addon_price(\App\Model\AddOn::whereIn('id', $add_on_ids)->get(), $add_on_qtys);
        $data['add_on_ids'] = json_encode($addon_data['addons']);
        //$data['total_add_on_price'] = $addon_data['total_add_on_price'];
        // dd($data);
        $cart = $request->session()->get('order_cart', collect([]));
        if (isset($request->cart_item_key)) {
            $cart[$request->cart_item_key] = $data;
            return response()->json([
                'data' => 2
            ]);
        } else {
            $cart->push($data);
        }

        return response()->json([
            'data' => 0
        ]);
    }

    public function remove_from_cart(Request $request)
    {
        $cart = $request->session()->get('order_cart', collect([]));
        $cart[$request->key]->status = false;
        $request->session()->put('order_cart', $cart);

        return response()->json([], 200);
    }
    public function update(Request $request, Order $order)
    {
        $order = Order::with(['details', 'customer' => function ($query) {
            return $query->withCount('orders');
        }, 'delivery_man' => function ($query) {
            return $query->withCount('orders');
        }, 'details.product' => function ($query) {
            return $query->withoutGlobalScope(RestaurantScope::class);
        }],)->where(['id' => $order->id])->Notpos()->first();


        if (!$request->session()->has('order_cart')) {
            Toastr::error(trans('messages.order_data_not_found'));
            return back();
        }
        $cart = $request->session()->get('order_cart', collect([]));
        //$restaurant = $order->restaurant;
        $coupon = null;
        $total_addon_price = 0;
        $product_price = 0;
        $restaurant_discount_amount = 0;
        if ($order->coupon_code) {
            $coupon = Coupon::where(['code' => $request['coupon_code']])->first();
        }
        foreach ($cart as $c) {
            if ($c['status'] == true) {
                unset($c['status']);
                /* if ($c['item_campaign_id'] != null)
                {
                    $product = ItemCampaign::find($c['item_campaign_id']);
                    if ($product) {

                        $price = $c['price'];

                        $product = Helpers::product_data_formatting($product);

                        $c->food_details = json_encode($product);
                        $c->updated_at = now();
                        if(isset($c->id))
                        {
                            OrderDetail::where('id', $c->id)->update(
                                [
                                    'food_id' => $c->food_id,
                                    'item_campaign_id' => $c->item_campaign_id,
                                    'food_details' => $c->food_details,
                                    'quantity' => $c->quantity,
                                    'price' => $c->price,
                                    'tax_amount' => $c->tax_amount,
                                    'discount_on_food' => $c->discount_on_food,
                                    'discount_type' => $c->discount_type,
                                    'variant' => $c->variant,
                                    'variation' => $c->variation,
                                    'add_ons' => $c->add_ons,
                                    'total_add_on_price' => $c->total_add_on_price,
                                    'updated_at' => $c->updated_at
                                ]
                            );
                        }
                        else
                        {
                            $c->save();
                        }

                        $total_addon_price += $c['total_add_on_price'];
                        $product_price += $price*$c['quantity'];
                        $restaurant_discount_amount += $c['discount_on_food']*$c['quantity'];
                    } else {
                        Toastr::error(trans('messages.food_not_found'));
                        return back();
                    }
                } else {

                } */
                $product = Product::find($c['product_id']);
                if ($product) {

                    $price = $c['price'];

                    $product = Helpers::product_data_formatting($product);

                    $c->product_details = json_encode($product);
                    $c->updated_at = now();
                    if (isset($c->id)) {
                        if ($c->add_on_ids === "0") {
                            $c->add_on_ids = json_encode([]);
                        }
                        OrderDetail::where('id', $c->id)->update(
                            [
                                'product_id' => $c->product_id,
                                'product_details' => $c->product_details,
                                'quantity' => $c->quantity,
                                'price' => $c->price,
                                'tax_amount' => $c->tax_amount,
                                'discount_on_product' => $c->discount_on_product,
                                'discount_type' => $c->discount_type,
                                'variant' => $c->variant,
                                'variation' => $c->variation,
                                'add_on_ids' => $c->add_on_ids,
                                'updated_at' => $c->updated_at
                            ]
                        );
                    } else {
                        $c->save();
                    }

                    $total_addon_price += $c['total_add_on_price'];
                    $product_price += $price * $c['quantity'];
                    $restaurant_discount_amount += $c['discount_on_product'] * $c['quantity'];
                } else {
                    Toastr::error(trans('messages.product_not_found'));
                    return back();
                }
            } else {
                $c->delete();
            }
        }

        //dd($cart);

        /* $restaurant_discount = Helpers::get_restaurant_discount($restaurant);
        if(isset($restaurant_discount))
        {
            if($product_price + $total_addon_price < $restaurant_discount['min_purchase'])
            {
                $restaurant_discount_amount = 0;
            }

            if($restaurant_discount_amount > $restaurant_discount['max_discount'])
            {
                $restaurant_discount_amount = $restaurant_discount['max_discount'];
            }
        } */
        //$order->delivery_charge = $order->original_delivery_charge;
        if ($coupon) {
            if ($coupon->coupon_type == 'free_delivery') {
                $order->delivery_charge = 0;
                $coupon = null;
            }
        }

        /*   if($order->restaurant->free_delivery)
        {
            $order->delivery_charge = 0;
        } */


        $coupon_discount_amount = $coupon ? CouponLogic::get_discount($coupon, $product_price + $total_addon_price - $restaurant_discount_amount) : 0;
        $total_price = $product_price + $total_addon_price - $restaurant_discount_amount - $coupon_discount_amount;

        //$tax = $restaurant->tax;
        $tax = 0;
        $total_tax_amount = ($tax > 0) ? (($total_price * $tax) / 100) : 0;
        /* if($restaurant->minimum_order > $product_price + $total_addon_price )
        {
            Toastr::error(trans('messages.you_need_to_order_at_least', ['amount'=>$restaurant->minimum_order.' '.Helpers::currency_code()]));
            return back();
        } */
        $free_delivery_over = BusinessSetting::where('key', 'free_delivery_over')->first();
        if ($free_delivery_over) {

            $free_delivery_over = $free_delivery_over->value;
        }
        if (isset($free_delivery_over)) {
            if ($free_delivery_over <= $product_price + $total_addon_price - $coupon_discount_amount - $restaurant_discount_amount) {
                $order->delivery_charge = 0;
            }
        }
        $total_order_ammount = $total_price + $total_tax_amount + $order->delivery_charge;
        $adjustment = $order->order_amount - $total_order_ammount;

        $order->coupon_discount_amount = $coupon_discount_amount;
        //$order->restaurant_discount_amount= $restaurant_discount_amount;
        $order->total_tax_amount = $total_tax_amount;
        $order->order_amount = $total_order_ammount;
        //$order->adjusment = $adjustment;
        //$order->edited = true;
        $order->save();
        session()->forget('order_cart');
        Toastr::success(trans('messages.order_updated_successfully'));
        return back();
    }

    /**
     * Delete the given resource
     *
     * @param int $id
     * @return void
     */
    public function delete($id)
    {
        try {

            DeliveryHistory::where('order_id', $id)->delete();
            DMReview::where('order_id', $id)->delete();
            OrderDeliveryHistory::where('order_id', $id)->delete();
            Review::where('order_id', $id)->delete();
            TrackDeliveryman::where('order_id', $id)->delete();
            OrderDetail::where('order_id', $id)->delete();
            Order::destroy($id);
            return back()->with('success', trans('messages.order_deleted_successfully'));
        } catch (Exception $e) {
            report($e);
            return back()->with('error', trans('messages.unable_to_delete_order'));
        }
    }
}
