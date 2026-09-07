@extends('seller.layouts.app')

@section('panel_content')

    <section class="mb-2 mb-md-3 mt-2 mt-md-3">
        <div class="container">

            <div class="row no-gutters">

                <div class="col-xl-12">

                    {{-- Main Card --}}
                    <div class="card border-0 shadow-sm">

                        {{-- Header --}}
                        <div class="card-header bg-white border-0 px-4 py-3">

                            <div class="d-flex flex-wrap align-items-center justify-content-between">

                                <h3 class="fs-16 fs-md-20 fw-700 mb-0 text-dark">
                                    {{ translate('Coming Soon Product') }}
                                </h3>

                            </div>

                        </div>


                        {{-- Products --}}
                        <div class="card-body px-4 pt-3 pb-4">

                            @if ($coming_soon_products->count() == 0)
                                {{-- Empty State --}}
                                <div class="text-center p-5">

                                    <div class="mb-3">
                                        <i class="las la-box-open fs-40 text-muted"></i>
                                    </div>

                                    <h5 class="text-muted mb-0">
                                        {{ translate('No Coming Soon Products Found') }}
                                    </h5>

                                </div>
                            @else
                                <div class="row">

                                    @foreach ($coming_soon_products as $product)
                                        {{-- Product --}}
                                        <div class="col-lg-3 col-md-4 col-sm-6 col-12 mb-4">

                                            <div class="card h-100 shadow-sm border-0">

                                                {{-- Product Image --}}
                                                <div class="position-relative overflow-hidden">

                                                    <img src="{{ static_asset('assets/img/placeholder.jpg') }}"
                                                        data-src="{{ get_image($product->thumbnail) }}"
                                                        alt="{{ $product->getTranslation('name') }}"
                                                        class="card-img-top lazyload has-transition"
                                                        style="height:200px; object-fit:cover;"
                                                        onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">

                                                </div>


                                                {{-- Product Body --}}
                                                <div class="card-body text-center pb-2 pt-3">

                                                    {{-- Product Name --}}
                                                    <h6 class="mb-2 text-dark fw-700">

                                                        {{ $product->getTranslation('name') }}

                                                    </h6>


                                                    {{-- Coming Soon --}}
                                                    <span class="badge badge-inline badge-warning mt-1 px-3 py-2">

                                                        {{ translate('Coming Soon') }}

                                                    </span>

                                                </div>


                                                {{-- Footer --}}
                                                <div class="card-footer bg-white border-0 text-center pt-1 pb-3">

                                                    <span class="small text-muted">
                                                        {{ translate('This Product will be available soon') }}
                                                    </span>

                                                </div>

                                            </div>

                                        </div>
                                    @endforeach

                                </div>
                            @endif

                        </div>

                    </div>

                </div>

            </div>

        </div>
    </section>

@endsection
