@extends('layout')

@section('title', 'Products')

@section('extra-css')
    <link rel="stylesheet" href="{{ asset('css/algolia.css') }}">
@endsection

@section('content')

    @component('components.breadcrumbs')
        <a href="/">Home</a>
        <i class="fa fa-chevron-right breadcrumb-separator"></i>
        <span>Shop</span>
    @endcomponent

    <div class="container">
        @if (session()->has('success_message'))
            <div class="alert alert-success">
                {{ session()->get('success_message') }}
            </div>
        @endif

        @if(count($errors) > 0)
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    <div class="products-section container">
        <div class="sidebar">
            <h3>By Category</h3>
            <ul>
                {{-- 9:13 / 36:41
                Laravel E-Commerce - Categories & Pagination - Part 5 (Category List) --}}
                @foreach ($categories as $category)
                <li class="{{ setActiveCategory($category->slug) }}"><a href="{{ route('shop.index', ['category' => $category->slug]) }}">{{ $category->name }}</a></li>
                {{-- <li class="{{ setActiveCategory($category->slug) }}"><a href="{{ route('shop.index', ['category' => $category->slug]) }}">{{ $category->name }}</a></li> --}}
                @endforeach
            </ul>
        </div> <!-- end sidebar -->
        <div>
            <div class="products-header">
                <h1 class="stylish-heading">{{ $categoryName }}</h1>
                {{-- 18:17 / 36:41
                Laravel E-Commerce - Categories & Pagination - Part 5 --}}
                <div>
                    <strong>Price: </strong>
                    <a href="{{ route('shop.index', ['category'=> request()->category, 'sort' => 'low_high']) }}">Low to High </a>|
                    <a href="{{ route('shop.index', ['category'=> request()->category, 'sort' => 'high_low']) }}">High to Low</a>
                </div>
            </div>

            <div class="products text-center">
                {{-- 16:19 / 36:41
                Laravel E-Commerce - Categories & Pagination - Part 5, (ForElse)--}}
                @forelse ($products as $product)
                <div class="product">
                    <a href="{{ route('shop.show', $product->slug) }}"><img src="{{ asset('img/products/'.$product->slug.'.jpg') }}" alt="product"></a>
                    <a href="{{ route('shop.show', $product->slug) }}"><div class="product-name">{{ $product->name }}</div></a>
                    <div class="product-price">{{ $product->presentPrice() }}</div>
                </div>
                @empty
                    <div style="text-align: left">No items found</div>
                @endforelse
            </div> <!-- end products -->

            
            {{-- 22:53 / 36:41
            Laravel E-Commerce - Categories & Pagination - Part 5 (Pagination) --}}
            <div class="spacer"></div>
            {{-- {{ $products->links() }} --}}
            {{ $products->appends(request()->input())->links() }}
        </div>
    </div>

@endsection

@section('extra-js')
    <!-- Include AlgoliaSearch JS Client and autocomplete.js library -->
    <script src="https://cdn.jsdelivr.net/algoliasearch/3/algoliasearch.min.js"></script>
    <script src="https://cdn.jsdelivr.net/autocomplete.js/0/autocomplete.min.js"></script>
    <script src="{{ asset('js/algolia.js') }}"></script>
@endsection
