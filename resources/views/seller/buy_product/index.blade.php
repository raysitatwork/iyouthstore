@extends('seller.layouts.app')

@section('panel_content')

    <div class="aiz-titlebar mt-2 mb-4">
        <h1 class="h3">Supplier Products</h1>
    </div>

    {{-- CATEGORY FILTER --}}
    <div class="card mb-4">
        <div class="card-body pb-2">
            <form method="GET">
                <div class="row">

                    <div class="col-md-4 mb-2">
                        <select name="category_id" class="form-control">
                            <option value="">All Categories</option>

                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}"
                                    {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->getTranslation('name') }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2 mb-2">
                        <button class="btn btn-primary w-100">
                            Filter
                        </button>
                    </div>

                </div>
            </form>
        </div>
    </div>

    {{-- PRODUCTS --}}
    <div class="card">
        <div class="card-body pt-3 pb-3">

            @if ($products->count() == 0)
                <div class="text-center p-5">
                    <h5>No Products Found</h5>
                </div>
            @else
                <div class="row">

                    @foreach ($products as $product)
                        @php
                            $stock = $product->stocks->sum('qty');
                        @endphp

                        <div class="col-lg-3 col-md-4 col-sm-6 mb-4">

                            <div class="card h-100 shadow-sm border-0">

                                <img src="{{ uploaded_asset($product->thumbnail_img) }}" class="card-img-top"
                                    style="height:200px;object-fit:cover;">

                                {{-- BODY --}}
                                <div class="card-body pb-1 pt-2">

                                    <h6 class="mb-1">
                                        {{ $product->getTranslation('name') }}
                                    </h6>

                                    <strong class="text-primary d-block mb-1">
                                        ₹ {{ $product->seller_price }}
                                    </strong>
                                    {{-- 
                                    <p class="small text-success mb-0">
                                        Available : {{ $stock }}
                                    </p> --}}

                                    <p class="small text-success mb-0">
                                        In My Stock : {{ $product->product_stock ?? 0 }}
                                    </p>

                                    <!--@if ($stock > 0)
    -->
                                    <!--    <span class="badge badge-inline badge-success mt-1">In Stock</span>-->
                                <!--@else-->
                                    <!--    <span class="badge badge-inline badge-danger mt-1">Out Of Stock</span>-->
                                    <!--
    @endif-->

                                </div>

                                {{-- FOOTER --}}
                                <div class="card-footer bg-white border-0 pt-1 pb-2">

                                    <form action="{{ route('seller.cart.add') }}" method="POST">
                                        @csrf

                                        <input type="hidden" name="product_id" value="{{ $product->id }}">

                                        <label class="small text-muted mb-1 d-block">Qty</label>

                                        <div class="d-flex align-items-center mb-2">

                                            <button type="button" class="btn btn-light border rounded-circle qty-minus"
                                                data-id="{{ $product->id }}" style="width:32px;height:32px;padding:0;">
                                                -
                                            </button>

                                            <input type="number" name="qty" id="qty-{{ $product->id }}"
                                                value="{{ $product->seller_min_purchase_limit ?? 1 }}"
                                                min="{{ $product->seller_min_purchase_limit ?? 1 }}"
                                                max="{{ $product->seller_purchase_limit ?? $stock }}"
                                                class="form-control text-center mx-2" style="max-width:60px;height:32px;"
                                                {{ $stock == 0 ? 'disabled' : '' }}>

                                            <button type="button" class="btn btn-light border rounded-circle qty-plus"
                                                data-id="{{ $product->id }}" style="width:32px;height:32px;padding:0;">
                                                +
                                            </button>

                                        </div>

                                        {{-- <button class="btn btn-primary btn-block btn-sm"
                                            {{ $stock == 0 ? 'disabled' : '' }}>
                                            Add To Cart
                                        </button> --}}

                                        <button type="button" class="btn btn-primary btn-block btn-sm ajax-add-cart"
                                            data-product="{{ $product->id }}">
                                            Add To Cart
                                        </button>


                                    </form>

                                </div>

                            </div>
                        </div>
                    @endforeach

                </div>

                  {{ $products->links() }}
            @endif

        </div>
    </div>

@endsection


@section('script')
    <script>
        $(document).ready(function() {

            // PLUS
            $(document).on('click', '.qty-plus', function() {

                let id = $(this).data('id');
                let input = $('#qty-' + id);

                let max = parseInt(input.attr('max')) || 9999;
                let current = parseInt(input.val()) || 1;

                if (current < max) {
                    input.val(current + 1);
                }

            });

            // MINUS
            $(document).on('click', '.qty-minus', function() {

                let id = $(this).data('id');
                let input = $('#qty-' + id);

                let current = parseInt(input.val()) || 1;

                // if (current > 1) {
                //     input.val(current - 1);
                // }

                let min = parseInt(input.attr('min')) || 1;

                if (current > min) {
                    input.val(current - 1);
                }

            });

            // $(document).on('click', '.ajax-add-cart', function() {

            //     let productId = $(this).data('product');
            //     let qty = $('#qty-' + productId).val();



            //     $.ajax({
            //         url: "{{ route('seller.cart.add') }}",
            //         type: "POST",
            //         data: {
            //             product_id: productId,
            //             qty: qty,
            //             _token: "{{ csrf_token() }}"
            //         },
            //         success: function(res) {

            //             if (res.status) {
            //                 AIZ.plugins.notify('success', res.message);
            //             } else {
            //                 AIZ.plugins.notify('danger', res.message);
            //             }
            //             if (res.cart_count !== undefined) {

            //                 $('#seller-cart-count').text(res.cart_count);

            //                 if (res.cart_count > 0) {
            //                     $('#seller-cart-count').show();
            //                 } else {
            //                     $('#seller-cart-count').hide();
            //                 }

            //             }

            //         },

            //         error: function() {
            //             AIZ.plugins.notify('danger', 'Something went wrong');
            //         }
            //     });

            // });

            $(document).on('click', '.ajax-add-cart', function() {

                let productId = $(this).data('product');
                let input = $('#qty-' + productId);

                let min = parseInt(input.attr('min')) || 1;
                let max = parseInt(input.attr('max')) || 9999;
                // let qty = parseInt(input.val());

                let raw = input.val();


                if (!raw || raw.trim() === '') {
                    AIZ.plugins.notify('danger', 'Please enter quantity');
                    input.val(input.attr('min') || 1); // reset to min
                    return;
                }

                let qty = parseInt(raw);

                if (isNaN(qty) || qty <= 0) {
                    AIZ.plugins.notify('danger', 'Quantity must be greater than 0');
                    input.val(min);
                    return;
                }

                if (qty < min) {
                    AIZ.plugins.notify('danger', 'Minimum quantity is ' + min);
                    return;
                }

                if (qty > max) {
                    AIZ.plugins.notify('danger', 'Maximum quantity is ' + max);
                    return;
                }

                $.ajax({
                    url: "{{ route('seller.cart.add') }}",
                    type: "POST",
                    data: {
                        product_id: productId,
                        qty: qty,
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(res) {

                        if (res.status) {
                            AIZ.plugins.notify('success', res.message);
                        } else {
                            AIZ.plugins.notify('danger', res.message);
                        }

                        if (res.cart_count !== undefined) {
                            $('#seller-cart-count').text(res.cart_count);

                            if (res.cart_count > 0) {
                                $('#seller-cart-count').show();
                            } else {
                                $('#seller-cart-count').hide();
                            }
                        }
                    },
                    error: function() {
                        AIZ.plugins.notify('danger', 'Something went wrong');
                    }
                });

            });

        });
    </script>
@endsection
