<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Product;
use App\Models\Order;

use http\Env\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::all();
        return view('product.index', compact('products'));
    }

    public function checkout()
    {
        $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));
        // \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));

        // Assume this is our Cart Items
        $products = Product::all();

        $lineItems = [];

        $totalPrice = 0;

        foreach($products as $product) {

            $totalPrice += $product->price;

            $lineItems[] = [
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => $product->name,
                        'images' => [$product->image],
                    ],
                    'unit_amount' => $product->price * 100,
                ],
                'quantity' => 1,
            ];
        }

        $checkout_session = $stripe->checkout->sessions->create([
        'line_items' => $lineItems,
        'mode' => 'payment',
        // 'success_url' => 'http://localhost:8000/success',
        // 'cancel_url' => 'http://localhost:8000/cancel',

        'success_url' => route('checkout.success', [], true),
        'cancel_url' => route('checkout.cancel', [], true),
        ]);


        // Now send the session Order table and save it on order table
        $order = new Order();
        $order->status = 'unpaid';
        $order->total_price = $totalPrice;
        $order->session_id = $checkout_session->id;
        $order->save();

        // header("HTTP/1.1 303 See Other");
        // header("Location: " . $checkout_session->url);
        return redirect($checkout_session->url);
    }

    public function success()
    {
        return view('product.checkout-success');
    }

    public function cancel()
    {
        return view('product.checkout-cancel');
    }

}
