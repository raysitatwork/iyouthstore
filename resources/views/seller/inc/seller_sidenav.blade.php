<div class="aiz-sidebar-wrap">
    <div class="aiz-sidebar left c-scrollbar">
        <div class="aiz-side-nav-logo-wrap">
            <div class="d-block text-center my-3">
                @if (optional(Auth::user()->shop)->logo != null)
                    <img class="mw-100 mb-3" src="{{ uploaded_asset(optional(Auth::user()->shop)->logo) }}"
                        class="brand-icon" alt="{{ get_setting('site_name') }}">
                @else
                    <img class="mw-100 mb-3" src="{{ uploaded_asset(get_setting('header_logo')) }}" class="brand-icon"
                        alt="{{ get_setting('site_name') }}">
                @endif
                <h3 class="fs-16  m-0 text-primary">{{ optional(Auth::user()->shop)->name }}</h3>
                <p class="text-primary">{{ Auth::user()->email }}</p>
            </div>
        </div>
        <div class="aiz-side-nav-wrap">
            <div class="px-20px mb-3">
                <input class="form-control bg-soft-secondary border-0 form-control-sm" type="text" name=""
                    placeholder="{{ translate('Search in menu') }}" id="menu-search" onkeyup="menuSearch()">
            </div>
            <ul class="aiz-side-nav-list" id="search-menu">
            </ul>
            <ul class="aiz-side-nav-list" id="main-menu" data-toggle="aiz-side-menu">
                <li class="aiz-side-nav-item">
                    <a href="{{ route('seller.dashboard') }}" class="aiz-side-nav-link">
                        <i class="las la-home aiz-side-nav-icon"></i>
                        <span class="aiz-side-nav-text">{{ translate('Dashboard') }}</span>
                    </a>
                </li>
                <li class="aiz-side-nav-item">
                    <a href="#" class="aiz-side-nav-link">
                        <i class="las la-shopping-cart aiz-side-nav-icon"></i>
                        <span class="aiz-side-nav-text">{{ translate('Products') }}</span>
                        <span class="aiz-side-nav-arrow"></span>
                    </a>


                    <!--Submenu-->
                    <ul class="aiz-side-nav-list level-2">
                        <li class="aiz-side-nav-item">
                            <a href="{{ route('seller.products') }}"
                                class="aiz-side-nav-link {{ areActiveRoutes(['seller.products', 'seller.products.create', 'seller.products.edit']) }}">
                                <span class="aiz-side-nav-text">{{ translate('Products') }}</span>
                            </a>
                        </li>

                        <!--<li class="aiz-side-nav-item">-->
                        <!--    <a href="{{ route('seller.products') }}"-->
                        <!--        class="aiz-side-nav-link {{ areActiveRoutes(['seller.products', 'seller.products.create', 'seller.products.edit']) }}">-->
                        <!--        <span class="aiz-side-nav-text">Purchase Product</span>-->
                        <!--    </a>-->
                        <!--</li>-->



                        <!--<li class="aiz-side-nav-item">-->
                        <!--    <a href="{{ route('seller.categories_wise_product_discount') }}"-->
                        <!--        class="aiz-side-nav-link">-->
                        <!--        <span class="aiz-side-nav-text">{{ translate('Category-Wise Discount') }}</span>-->
                        <!--    </a>-->
                        <!--</li>-->

                        <!--<li class="aiz-side-nav-item">-->
                        <!--    <a href="{{ route('seller.product_bulk_upload.index') }}"-->
                        <!--        class="aiz-side-nav-link {{ areActiveRoutes(['product_bulk_upload.index']) }}">-->
                        <!--        <span class="aiz-side-nav-text">{{ translate('Product Bulk Upload') }}</span>-->
                        <!--    </a>-->
                        <!--</li>-->
                        @if (get_setting('digital_product_manage_by_seller') == 1)
                            <li class="aiz-side-nav-item">
                                <a href="{{ route('seller.digitalproducts') }}"
                                    class="aiz-side-nav-link {{ areActiveRoutes(['seller.digitalproducts', 'seller.digitalproducts.create', 'seller.digitalproducts.edit']) }}">
                                    <span class="aiz-side-nav-text">{{ translate('Digital Products') }}</span>
                                </a>
                            </li>
                        @endif
                        <!--<li class="aiz-side-nav-item">-->
                        <!--    <a href="{{ route('seller.product-reviews') }}"-->
                        <!--        class="aiz-side-nav-link {{ areActiveRoutes(['seller.product-reviews', 'seller.detail-reviews']) }}">-->
                        <!--        <span class="aiz-side-nav-text">{{ translate('Product Reviews') }}</span>-->
                        <!--    </a>-->
                        <!--</li>-->
                    </ul>
                </li>

                @if (addon_is_activated('preorder') && get_setting('seller_preorder_product') == 1)
                    <li class="aiz-side-nav-item">
                        <a href="#" class="aiz-side-nav-link">
                            <i class="las la-clock aiz-side-nav-icon"></i>
                            <span class="aiz-side-nav-text">{{ translate('Preorder') }}</span>
                            <span class="aiz-side-nav-arrow"></span>
                        </a>
                        <!--Submenu-->
                        <ul class="aiz-side-nav-list level-2">
                            <li class="aiz-side-nav-item">
                                <a href="{{ route('seller.preorder.dashboard') }}" class="aiz-side-nav-link">
                                    <span class="aiz-side-nav-text">{{ translate('Dashboard') }}</span>
                                </a>
                            </li>
                            <li class="aiz-side-nav-item">
                                <a href="{{ route('seller.preorder-product.create') }}" class="aiz-side-nav-link">
                                    <span class="aiz-side-nav-text">{{ translate('Add New Preorder Product') }}</span>
                                </a>
                            </li>
                            <li class="aiz-side-nav-item">
                                <a href="{{ route('seller.preorder-product.index') }}"
                                    class="aiz-side-nav-link {{ areActiveRoutes(['seller.preorder-product.edit']) }}">
                                    <span class="aiz-side-nav-text">{{ translate('Preorder Products') }}</span>
                                </a>
                            </li>

                            <li class="aiz-side-nav-item">
                                <a href="javascript:void(0);" class="aiz-side-nav-link">
                                    <span class="aiz-side-nav-text">{{ translate('Orders (Preorder)') }}</span>
                                    <span class="aiz-side-nav-arrow"></span>
                                </a>
                                <ul class="aiz-side-nav-list level-3">
                                    <li class="aiz-side-nav-item">
                                        <a href="{{ route('seller.all_preorder.list') }}"
                                            class="aiz-side-nav-link {{ areActiveRoutes(['seller.preorder-order.show']) }} }}">
                                            <span class="aiz-side-nav-text">{{ translate('All Orders') }}</span>
                                        </a>
                                    </li>
                                    <li class="aiz-side-nav-item">
                                        <a href="{{ route('seller.delayed_prepayment_preorders.list') }}"
                                            class="aiz-side-nav-link">
                                            <span
                                                class="aiz-side-nav-text">{{ translate('Delayed Prepayment Orders') }}</span>
                                        </a>
                                    </li>
                                    <li class="aiz-side-nav-item">
                                        <a href="{{ route('seller.delayed_final_orders.list') }}"
                                            class="aiz-side-nav-link">
                                            <span
                                                class="aiz-side-nav-text">{{ translate('Delayed Final Orders') }}</span>
                                        </a>
                                    </li>
                                </ul>
                            </li>

                            <li class="aiz-side-nav-item">
                                <a href="{{ route('seller.preorder-settings') }}" class="aiz-side-nav-link">
                                    <span class="aiz-side-nav-text">{{ translate('Preorder Settings') }}</span>
                                </a>
                            </li>

                            <li class="aiz-side-nav-item">
                                <a href="{{ route('seller.preorder-commission-history') }}"
                                    class="aiz-side-nav-link">
                                    <span
                                        class="aiz-side-nav-text">{{ translate('Preorder Commission History') }}</span>
                                </a>
                            </li>

                            @if (get_setting('conversation_system') == 1)
                                <li class="aiz-side-nav-item">
                                    @php
                                        $preorderConversation = get_non_viewed_preorder_conversations();
                                    @endphp
                                    <a href="{{ route('seller.preorder-conversations.index') }}"
                                        class="aiz-side-nav-link {{ areActiveRoutes(['seller.preorder-conversations.index', 'seller.preorder-conversations.show']) }}">
                                        <span
                                            class="aiz-side-nav-text">{{ translate('Product Conversations') }}</span>
                                        @if ($preorderConversation > 0)
                                            <span class="badge badge-danger">({{ $preorderConversation }})</span>
                                        @endif
                                    </a>
                                </li>
                            @endif
                            @if (get_setting('product_query_activation') == 1)
                                <li class="aiz-side-nav-item">
                                    <a href="{{ route('seller.preorder_product_query.index') }}"
                                        class="aiz-side-nav-link {{ areActiveRoutes(['preorder_product_query.index', 'preorder_product_query.show']) }}">
                                        <span class="aiz-side-nav-text">{{ translate('Product Queries') }}</span>
                                    </a>
                                </li>
                            @endif
                            <li class="aiz-side-nav-item">
                                <a href="{{ route('seller.preorder_product_reviews') }}"
                                    class="aiz-side-nav-link {{ areActiveRoutes(['seller.preorder_product_detail_reviews']) }}">
                                    <span class="aiz-side-nav-text">{{ translate('Product Reviews') }}</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                @endif


                    {{-- @if (addon_is_activated('pos_system') &&
                        get_setting('pos_activation_for_seller') != null &&
                        get_setting('pos_activation_for_seller') != 0) --}}
                    <li class="aiz-side-nav-item">
                        <a href="#" class="aiz-side-nav-link">
                            <i class="las la-tasks aiz-side-nav-icon"></i>
                            <span class="aiz-side-nav-text">{{ translate('POS System') }}</span>
                            @if (env('DEMO_MODE') == 'On')
                                <span class="badge badge-inline badge-danger">Addon</span>
                            @endif
                            <span class="aiz-side-nav-arrow"></span>
                        </a>
                        <ul class="aiz-side-nav-list level-2">
                            <li class="aiz-side-nav-item">
                                <a href="{{ route('poin-of-sales.seller_index') }}"
                                    class="aiz-side-nav-link {{ areActiveRoutes(['poin-of-sales.seller_index']) }}">
                                    <i class="las la-fax aiz-side-nav-icon"></i>
                                    <span class="aiz-side-nav-text">{{ translate('Offline Store') }}</span>
                                </a>
                            </li>
                            <li class="aiz-side-nav-item">
                                <a href="{{ route('pos.configuration') }}" class="aiz-side-nav-link">
                                    <span class="aiz-side-nav-text">{{ translate('POS Configuration') }}</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                {{-- @endif --}}

                <li class="aiz-side-nav-item">
                    <a href="#" class="aiz-side-nav-link">
                        <i class="las la-store aiz-side-nav-icon"></i>
                        <span class="aiz-side-nav-text">{{ translate('Marketplace Products') }}</span>
                        <span class="aiz-side-nav-arrow"></span>
                    </a>
                    <!--Submenu-->
                    <ul class="aiz-side-nav-list level-2">
                        <li class="aiz-side-nav-item">
                            <a href="{{ route('seller.buy.products') }}"
                                class="aiz-side-nav-link {{ areActiveRoutes(['seller.buy.products']) }}">
                                <span class="aiz-side-nav-text">{{ translate('Buy Products') }}</span>
                            </a>
                        </li>
                        <li class="aiz-side-nav-item">
                            <a href="{{ route('seller.my-purchases') }}"
                                class="aiz-side-nav-link {{ areActiveRoutes(['seller.my-purchases']) }}">
                                <span class="aiz-side-nav-text">{{ translate('My Purchases') }}</span>
                            </a>
                        </li>
                        <li class="aiz-side-nav-item">
                            <a href="{{ route('seller.comming_soon') }}"
                                class="aiz-side-nav-link {{ areActiveRoutes(['seller.comming_soon']) }}">
                                <span class="aiz-side-nav-text">{{ translate('Comming Soon') }}</span>
                            </a>
                        </li>
                    </ul>
                </li>


                {{-- Note --}}
                <li class="aiz-side-nav-item">
                    <a href="#" class="aiz-side-nav-link">
                        <div class="aiz-side-nav-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="16.001"
                                viewBox="0 0 16 16.001">
                                <path id="Union_64" data-name="Union 64"
                                    d="M.333,16A.315.315,0,0,1,0,15.668V.335A.315.315,0,0,1,.333,0h9.31a.285.285,0,0,1,.123.014A.318.318,0,0,1,9.9.1l2.667,2.667.009.01a.293.293,0,0,1,.079.132.274.274,0,0,1,.012.112V5.835l1.267-1.267a.322.322,0,0,1,.466,0l1.5,1.5a.322.322,0,0,1,0,.466L12.667,9.768v5.9a.315.315,0,0,1-.333.333Zm.334-.666H12v-4.9L9.133,13.3a.3.3,0,0,1-.233.1H8.882L6.4,14.468a.2.2,0,0,1-.133.033.332.332,0,0,1-.3-.466l.589-1.368H2.667a.333.333,0,0,1,0-.667H6.843l.258-.6a.321.321,0,0,1,.176-.177L8.5,10H2.667a.333.333,0,0,1,0-.667h6.5L12,6.5V3.335H9.667A.315.315,0,0,1,9.333,3V.668H.667Zm6.233-1.8,1.4-.6-.8-.8-.1.239a.323.323,0,0,1-.074.172Zm2-.967,6.3-6.3-.283-.283-6.3,6.3ZM7.867,11.534l.284.284,6.3-6.3-.283-.283L12.624,6.777a.291.291,0,0,1-.115.115L9.558,9.844a.291.291,0,0,1-.115.115ZM10,2.668h1.533L10.767,1.9,10,1.135ZM2.667,7.335a.333.333,0,0,1,0-.667H10a.333.333,0,1,1,0,.667Zm0-2.668a.333.333,0,1,1,0-.666H10a.333.333,0,1,1,0,.666Z"
                                    fill="#575b6a" />
                            </svg>
                        </div>
                        <span class="aiz-side-nav-text">{{ translate('Notes') }}</span>
                        <span class="aiz-side-nav-arrow"></span>
                    </a>
                    <!--Submenu-->
                    <ul class="aiz-side-nav-list level-2">
                        @if (get_setting('seller_can_add_note'))
                            <li class="aiz-side-nav-item">
                                <a class="aiz-side-nav-link" href="{{ route('seller.note.create') }}">
                                    <span class="aiz-side-nav-text">{{ translate('Add New Note') }}</span>
                                </a>
                            </li>
                        @endif
                        <li class="aiz-side-nav-item">
                            <a href="{{ route('seller.note.index') }}"
                                class="aiz-side-nav-link {{ areActiveRoutes(['seller.note.edit']) }}">
                                <span class="aiz-side-nav-text">{{ translate('Note List') }}</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <!--<li class="aiz-side-nav-item">-->
                <!--    <a href="{{ route('seller.uploaded-files.index') }}"-->
                <!--        class="aiz-side-nav-link {{ areActiveRoutes(['seller.uploaded-files.index', 'seller.uploads.create']) }}">-->
                <!--        <i class="las la-folder-open aiz-side-nav-icon"></i>-->
                <!--        <span class="aiz-side-nav-text">{{ translate('Uploaded Files') }}</span>-->
                <!--    </a>-->
                <!--</li>-->

                <li class="aiz-side-nav-item">
                    <a href="{{ route('seller.orders.index') }}"
                        class="aiz-side-nav-link {{ areActiveRoutes(['seller.orders.index', 'seller.orders.show']) }}">
                        <i class="las la-money-bill aiz-side-nav-icon"></i>
                        <span class="aiz-side-nav-text">{{ translate('Customer Orders') }}</span>
                    </a>
                </li>

                
             <li class="aiz-side-nav-item">
                    <a href="#" class="aiz-side-nav-link">
                        <i class="las la-shopping-cart aiz-side-nav-icon"></i>
                        <span class="aiz-side-nav-text">{{ translate('Product Enquiry') }}</span>
                        <span class="aiz-side-nav-arrow"></span>
                    </a>


                    <!--Submenu-->
                    <ul class="aiz-side-nav-list level-2">
                        <li class="aiz-side-nav-item">
                            <a href="{{ route('seller.productsEnquiry.create') }}"
                                class="aiz-side-nav-link {{ areActiveRoutes(['seller.productsEnquiry.create', 'seller.productsEnquiry']) }}">
                                <span class="aiz-side-nav-text">{{ translate('Add') }}</span>
                            </a>
                        </li>

                            <li class="aiz-side-nav-item">
                            <a href="{{ route('seller.productsEnquiry') }}"
                                class="aiz-side-nav-link {{ areActiveRoutes(['seller.productsEnquiry.create', 'seller.productsEnquiry']) }}">
                                <span class="aiz-side-nav-text">{{ translate('View') }}</span>
                            </a>
                        </li>

                
                      
                    </ul>
                </li>


            </ul><!-- .aiz-side-nav -->
        </div><!-- .aiz-side-nav-wrap -->
    </div><!-- .aiz-sidebar -->
    <div class="aiz-sidebar-overlay"></div>
</div><!-- .aiz-sidebar -->
