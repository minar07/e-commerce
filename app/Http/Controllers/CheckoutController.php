<?php

namespace App\Http\Controllers;

use App\Order;
use App\Product;
use App\OrderProduct;
use App\Mail\OrderPlaced;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Http\Requests\CheckoutRequest;
use Gloudemans\Shoppingcart\Facades\Cart;
use Cartalyst\Stripe\Laravel\Facades\Stripe;
use Cartalyst\Stripe\Exception\CardErrorException;

class CheckoutController extends Controller
{

        // 31:28 / 36:11
        // Laravel E-Commerce - Coupons & Discounts - Part 6
        private function getNumbers(){
            $tax = config('cart.tax') / 100;
            $discount = session()->get('coupon')['discount'] ?? 0;
            $newSubtotal = (Cart::subtotal() - $discount);
            $newTax = $newSubtotal * $tax;
            $newTotal = $newSubtotal * (1+$tax);

            return collect([
                'tax'=>$tax,
                'discount'=>$discount,
                'newSubtotal'=>$newSubtotal,
                'newTax'=>$newTax, 
                'newTotal'=>$newTotal
            ]);

        }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // if (Cart::instance('default')->count() == 0) {
        //     return redirect()->route('shop.index');
        // }

        // if (auth()->user() && request()->is('guestCheckout')) {
        //     return redirect()->route('checkout.index');
        // }

        // $gateway = new \Braintree\Gateway([
        //     'environment' => config('services.braintree.environment'),
        //     'merchantId' => config('services.braintree.merchantId'),
        //     'publicKey' => config('services.braintree.publicKey'),
        //     'privateKey' => conf  ig('services.braintree.privateKey')
        // ]);

        // $paypalToken = $gateway->ClientToken()->generate();

       

        // $tax = config('cart.tax') / 100;
        // $discount = session()->get('coupon')['discount'] ?? 0;
        // $newSubtotal = (Cart::subtotal() - $discount);
        // $newTax = $newSubtotal * $tax;
        // $newTotal = $newSubtotal * (1+$tax);

        // return view('checkout')->with(['discount'=>$discount, 'newSubtotal'=>$newSubtotal, 'newTax'=>$newTax, 'newTotal'=>$newTotal]);
    
        return view('checkout')->with([
                // 'paypalToken' => $paypalToken,
                'discount' => $this->getNumbers()->get('discount'),
                'newSubtotal' => $this->getNumbers()->get('newSubtotal'),
                'newTax' => $this->getNumbers()->get('newTax'),
                'newTotal' => $this->getNumbers()->get('newTotal'),
            ]);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CheckoutRequest $request)
    {

       // dd($request->all());
        // // Check race condition when there are less items available to purchase
        // if ($this->productsAreNoLongerAvailable()) {
        //     return back()->withErrors('Sorry! One of the items in your cart is no longer avialble.');
        // }

        // 26:52 / 37:27
        // Laravel E-Commerce - Checkout with Stripe - Part 3
        $contents = Cart::content()->map(function ($item) {
            return $item->model->slug.', '.$item->qty;
        })->values()->toJson();

        try {
            $charge = Stripe::charges()->create([
                'amount' => $this->getNumbers()->get('newTotal') / 100,
                'currency' => 'USD',
                'source' => $request->stripeToken,
                'description' => 'Order',
                'receipt_email' => $request->email,
                'metadata' => [
                     'contents' => $contents,
                     'quantity' => Cart::instance('default')->count(),
                     'discount' => collect(session()->get('coupon'))->toJson(),
                ],
            ]);

        //     $order = $this->addToOrdersTables($request, null);
        //     Mail::send(new OrderPlaced($order));

        //     // decrease the quantities of all the products in the cart
        //     $this->decreaseQuantities();

        // 24:36 / 37:27
        // Laravel E-Commerce - Checkout with Stripe - Part 3
           Cart::instance('default')->destroy();
           session()->forget('coupon');

            return redirect()->route('confirmation.index')->with('success_message', 'Thank you! Your payment has been successfully accepted!');
         } catch (CardErrorException $e) {
             return back()->withErrors('Error! '.$e->getMessage());
            // $this->addToOrdersTables($request, $e->getMessage());
            // return back()->withErrors('Error! ' . $e->getMessage());
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function paypalCheckout(Request $request)
    {
        // Check race condition when there are less items available to purchase
        if ($this->productsAreNoLongerAvailable()) {
            return back()->withErrors('Sorry! One of the items in your cart is no longer avialble.');
        }

        $gateway = new \Braintree\Gateway([
            'environment' => config('services.braintree.environment'),
            'merchantId' => config('services.braintree.merchantId'),
            'publicKey' => config('services.braintree.publicKey'),
            'privateKey' => config('services.braintree.privateKey')
        ]);

        $nonce = $request->payment_method_nonce;

        $result = $gateway->transaction()->sale([
            'amount' => round(getNumbers()->get('newTotal') / 100, 2),
            'paymentMethodNonce' => $nonce,
            'options' => [
                'submitForSettlement' => true
            ]
        ]);

        $transaction = $result->transaction;

        if ($result->success) {
            $order = $this->addToOrdersTablesPaypal(
                $transaction->paypal['payerEmail'],
                $transaction->paypal['payerFirstName'].' '.$transaction->paypal['payerLastName'],
                null
            );

            Mail::send(new OrderPlaced($order));

            // decrease the quantities of all the products in the cart
            $this->decreaseQuantities();

            Cart::instance('default')->destroy();
            session()->forget('coupon');

            return redirect()->route('confirmation.index')->with('success_message', 'Thank you! Your payment has been successfully accepted!');
        } else {
            $order = $this->addToOrdersTablesPaypal(
                $transaction->paypal['payerEmail'],
                $transaction->paypal['payerFirstName'].' '.$transaction->paypal['payerLastName'],
                $result->message
            );

            return back()->withErrors('An error occurred with the message: '.$result->message);
        }
    }

    protected function addToOrdersTables($request, $error)
    {
        // Insert into orders table
        $order = Order::create([
            'user_id' => auth()->user() ? auth()->user()->id : null,
            'billing_email' => $request->email,
            'billing_name' => $request->name,
            'billing_address' => $request->address,
            'billing_city' => $request->city,
            'billing_province' => $request->province,
            'billing_postalcode' => $request->postalcode,
            'billing_phone' => $request->phone,
            'billing_name_on_card' => $request->name_on_card,
            'billing_discount' => getNumbers()->get('discount'),
            'billing_discount_code' => getNumbers()->get('code'),
            'billing_subtotal' => getNumbers()->get('newSubtotal'),
            'billing_tax' => getNumbers()->get('newTax'),
            'billing_total' => getNumbers()->get('newTotal'),
            'error' => $error,
        ]);

        // Insert into order_product table
        foreach (Cart::content() as $item) {
            OrderProduct::create([
                'order_id' => $order->id,
                'product_id' => $item->model->id,
                'quantity' => $item->qty,
            ]);
        }

        return $order;
    }

    protected function addToOrdersTablesPaypal($email, $name, $error)
    {
        // Insert into orders table
        $order = Order::create([
            'user_id' => auth()->user() ? auth()->user()->id : null,
            'billing_email' => $email,
            'billing_name' => $name,
            'billing_discount' => getNumbers()->get('discount'),
            'billing_discount_code' => getNumbers()->get('code'),
            'billing_subtotal' => getNumbers()->get('newSubtotal'),
            'billing_tax' => getNumbers()->get('newTax'),
            'billing_total' => getNumbers()->get('newTotal'),
            'error' => $error,
            'payment_gateway' => 'paypal',
        ]);

        // Insert into order_product table
        foreach (Cart::content() as $item) {
            OrderProduct::create([
                'order_id' => $order->id,
                'product_id' => $item->model->id,
                'quantity' => $item->qty,
            ]);
        }

        return $order;
    }

    protected function decreaseQuantities()
    {
        foreach (Cart::content() as $item) {
            $product = Product::find($item->model->id);

            $product->update(['quantity' => $product->quantity - $item->qty]);
        }
    }

    protected function productsAreNoLongerAvailable()
    {
        foreach (Cart::content() as $item) {
            $product = Product::find($item->model->id);
            if ($product->quantity < $item->qty) {
                return true;
            }
        }

        return false;
    }
}
