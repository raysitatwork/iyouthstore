@if (isset($coming_soon_products) && count($coming_soon_products) > 0)

    @php
        $xxl_items = 6;
        $xl_items = 5;
        $lg_items = 4;
        $md_items = 3;
    @endphp

    <section class="mb-2 mb-md-3 mt-2 mt-md-3">

        <div class="container">

            <div class="row gutters-15">

                <div class="col" id="section_coming_soon_div">

                    <div class="border">

                        <!-- Top Section -->
                        <div class="d-flex px-4 py-3 align-items-baseline justify-content-between">

                            <!-- Title -->
                            <h3 class="fs-16 fs-md-20 fw-700 mb-2 mb-sm-0">
                                <span>
                                    {{ translate('Coming Soon Product') }}
                                </span>
                            </h3>

                            <!-- Links -->
                            <div class="d-flex">

                                <a type="button"
                                    class="arrow-prev slide-arrow link-disable text-secondary mr-2"
                                    onclick="clickToSlide('slick-prev','section_coming_soon_div')">

                                    <i class="las la-angle-left fs-20 fw-600"></i>

                                </a>

                                <a type="button"
                                    class="arrow-next slide-arrow text-secondary ml-2"
                                    onclick="clickToSlide('slick-next','section_coming_soon_div')">

                                    <i class="las la-angle-right fs-20 fw-600"></i>

                                </a>

                            </div>

                        </div>


                        <!-- Products Section -->
                        <div class="px-xl-1">

                            <div class="aiz-carousel arrow-none"
                                data-items="{{ $xxl_items }}"
                                data-xxl-items="{{ $xxl_items }}"
                                data-xl-items="{{ $xl_items }}"
                                data-lg-items="{{ $lg_items }}"
                                data-md-items="{{ $md_items }}"
                                data-sm-items="2"
                                data-xs-items="2"
                                data-arrows="true"
                                data-infinite="false">

                                @foreach ($coming_soon_products as $key => $product)

                                    <div class=" pt-5 pb-5carousel-box position-relative px-0 has-transition hov-animate-outline">

                                        <div class="px-3">

                                            <!-- Product Image -->
                                            <div class="position-relative">

                                                <div class="img h-160px h-md-180px overflow-hidden pt- pb-5">

                                                    <img class="lazyload img-fit m-auto has-transition"
                                                        src="{{ static_asset('assets/img/placeholder.jpg') }}"
                                                        data-src="{{ get_image($product->thumbnail) }}"
                                                        alt="{{ $product->getTranslation('name') }}"
                                                        onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">

                                                </div>

                                            </div>


                                            <!-- Product Details -->
                                            <div class="pt-2 text-center">

                                                <!-- Product Name -->
                                                <div class="fs-14 fw-700 text-dark text-truncate-2">

                                                    {{ $product->getTranslation('name') }}

                                                </div>


                                                <!-- Coming Soon -->
                                                <div class="mt-1 pb-5">

                                                    <span class="badge badge-inline badge-warning">

                                                        {{ translate('Coming Soon') }}

                                                    </span>

                                                </div>

                                            </div>

                                        </div>

                                    </div>

                                @endforeach

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </section>

@endif
