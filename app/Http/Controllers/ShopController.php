<?php

namespace App\Http\Controllers;

use App\Product;
use App\Category;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $pagination = 9;
        $categories = Category::all();
    
        // 11:34 / 36:41
        // Laravel E-Commerce - Categories & Pagination - Part 5, Query Strings
        if (request()->category) {
            $products = Product::with('categories')->whereHas('categories', function ($query) {
                $query->where('slug', request()->category);
            }); // we removed get() method that's why paginate working. Because now it is a query string, not a collection.
            // $categories = Category::all();
            $categoryName = optional($categories->where('slug', request()->category)->first())->name;
        } else {

            //teke() method works as query builder but all() method works as an array or a collection.
            // $products = Product::take(12);
            $products = Product::where('featured', true);
            // $categories = Category::all();
            
            // 13:46 / 36:41
            // Laravel E-Commerce - Categories & Pagination - Part 5(Category Header Name changing)
            $categoryName = 'Featured';
        }

        // 21:15 / 36:40
        // Laravel E-Commerce - Categories & Pagination - Part 5 (Sorting By Price)
        if (request()->sort == 'low_high') {
            // $products = $products->sortBy('price');
            $products = $products->orderBy('price', 'asc')->paginate($pagination);

        } elseif (request()->sort == 'high_low') {
            // $products = $products->sortByDesc('price');
            $products = $products->orderBy('price', 'desc')->paginate($pagination);

        } else {
            $products = $products->paginate($pagination);
        }
  

        return view('shop')->with(['products'=> $products, 'categories'=> $categories, 'categoryName'=> $categoryName]);
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $slug
     * @return \Illuminate\Http\Response
     */
    public function show($slug)
    {
        $product = Product::where('slug', $slug)->firstOrFail();
        $mightAlsoLike = Product::where('slug', '!=', $slug)->MightAlsoLike()->get();

        // $stockLevel = getStockLevel($product->quantity);

        return view('product')->with([
            'product' => $product,
            'mightAlsoLike' => $mightAlsoLike,
        ]);
    }

    public function search(Request $request)
    {
        $request->validate([
            'query' => 'required|min:3',
        ]);

        $query = $request->input('query');

        // $products = Product::where('name', 'like', "%$query%")
        //                    ->orWhere('details', 'like', "%$query%")
        //                    ->orWhere('description', 'like', "%$query%")
        //                    ->paginate(10);

        $products = Product::search($query)->paginate(10);

        return view('search-results')->with('products', $products);
    }

    public function searchAlgolia(Request $request)
    {
        return view('search-results-algolia');
    }
}