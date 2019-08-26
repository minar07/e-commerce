<?php

namespace App\Http\Controllers;

use App\Coupon;
use Gloudemans\Shoppingcart\Facades\Cart;
use App\Jobs\UpdateCoupon;
use Illuminate\Http\Request;

class CouponsController extends Controller
{


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        // 11:23 / 36:09
        // Laravel E-Commerce - Coupons & Discounts - Part 6
        $coupon = Coupon::where('code', $request->coupon_code)->first();

        if (!$coupon) {
            return back()->withErrors('Invalid coupon code. Please try again.');
        }

        // making a coupon sesson. method discount() has been defined in the Coupon model.
        session()->put('coupon', ['name'=>$coupon->code, 'discount'=>$coupon->discount(Cart::subtotal())]);

        // dispatch_now(new UpdateCoupon($coupon));

        return back()->with('success_message', 'Coupon has been applied!');
    }



    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy()
    {
    
        // 19:56 / 36:10
        // Laravel E-Commerce - Coupons & Discounts - Part 6
        session()->forget('coupon');

        return back()->with('success_message', 'Coupon has been removed.');
    }
}
