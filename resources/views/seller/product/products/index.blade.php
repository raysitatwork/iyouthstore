@extends('seller.layouts.app')

@section('panel_content')

    <div class="aiz-titlebar mt-2 mb-4">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="h3">{{ translate('Products') }}</h1>
            </div>
        </div>
    </div>

    <div class="row gutters-10 justify-content-center">
        @if (addon_is_activated('seller_subscription'))
            <div class="col-md-4 mx-auto mb-3">
                <div class="bg-grad-1 text-white rounded-lg overflow-hidden">
                    <span
                        class="size-30px rounded-circle mx-auto bg-soft-primary d-flex align-items-center justify-content-center mt-3">
                        <i class="las la-upload la-2x text-white"></i>
                    </span>
                    <div class="px-3 pt-3 pb-3">
                        <div class="h4 fw-700 text-center">
                            {{ max(0, auth()->user()->shop->product_upload_limit - auth()->user()->products()->count()) }}
                        </div>
                        <div class="opacity-50 text-center">{{ translate('Remaining Uploads') }}</div>
                    </div>
                </div>
            </div>
        @endif

        {{-- <div class="col-md-4 mx-auto mb-3" >
            <a href="{{ route('seller.products.create')}}">
              <div class="p-3 rounded mb-3 c-pointer text-center bg-white shadow-sm hov-shadow-lg has-transition">
                  <span class="size-60px rounded-circle mx-auto bg-secondary d-flex align-items-center justify-content-center mb-3">
                      <i class="las la-plus la-3x text-white"></i>
                  </span>
                  <div class="fs-18 text-primary">{{ translate('Add New Product') }}</div>
              </div>
            </a>
        </div> --}}

        @if (addon_is_activated('seller_subscription'))
            @php
                $seller_package = \App\Models\SellerPackage::find(Auth::user()->shop->seller_package_id);
            @endphp
            <div class="col-md-4">
                <a href="{{ route('seller.seller_packages_list') }}"
                    class="text-center bg-white shadow-sm hov-shadow-lg text-center d-block p-3 rounded">
                    @if ($seller_package != null)
                        <img src="{{ uploaded_asset($seller_package->logo) }}" height="44" class="mw-100 mx-auto">
                        <span class="d-block sub-title mb-2">{{ translate('Current Package') }}:
                            {{ $seller_package->getTranslation('name') }}</span>
                    @else
                        <i class="la la-frown-o mb-2 la-3x"></i>
                        <div class="d-block sub-title mb-2">{{ translate('No Package Found') }}</div>
                    @endif
                    <div class="btn btn-outline-primary py-1">{{ translate('Upgrade Package') }}</div>
                </a>
            </div>
        @endif

    </div>

    <div class="card">
        <form class="" id="sort_products" action="" method="GET">
            <div class="card-header row gutters-5">
                <div class="col">
                    <h5 class="mb-md-0 h6">{{ translate('All Products') }}</h5>
                </div>

                <div class="dropdown mb-2 mb-md-0">
                    <button class="btn border dropdown-toggle" type="button" data-toggle="dropdown">
                        {{ translate('Bulk Action') }}
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a class="dropdown-item confirm-alert" href="javascript:void(0)" data-target="#bulk-delete-modal">
                            {{ translate('Delete selection') }}</a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control" id="search" name="search"
                            @isset($search) value="{{ $search }}" @endisset
                            placeholder="{{ translate('Search product') }}">
                    </div>
                </div>
            </div>
            <div class="card-body">
                <table class="table aiz-table mb-0">
                    <thead>
                        <tr>
                            <th>
                                <div class="form-group">
                                    <div class="aiz-checkbox-inline">
                                        <label class="aiz-checkbox">
                                            <input type="checkbox" class="check-all">
                                            <span class="aiz-square-check"></span>
                                        </label>
                                    </div>
                                </div>
                            </th>
                            <th>{{ translate('Name') }}</th>
                            <th>Image</th>
                            <th data-breakpoints="md">{{ translate('Current Qty') }}</th>

                            <th data-breakpoints="md">Category</th>
                            <th data-breakpoints="md">SKU</th>
                            <th>{{ translate('Base Price') }}</th>


                        </tr>
                    </thead>

                    <tbody>

                        {{-- @foreach ($products as $product)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $product->product->getTranslation('name') }}</td>
                                <td>
                                    <img src="{{ uploaded_asset($product->product->thumbnail_img) }}" height="44"
                                        class="mw-100 mx-auto">
                                </td>
                                <td>{{ $product->stock }}</td>
                                <td>
                                    {{ $product->categories->first()->name ?? 'N/A' }}
                                </td>
                            </tr>
                        @endforeach --}}
                        
@foreach ($products as $product)
    @php
        $productData = $product->product;
    @endphp

    <tr>
        {{-- Serial Number --}}
        <td>{{ $loop->iteration }}</td>

        {{-- Product Name --}}
        <td>
            @if ($productData)
                {{ $productData->getTranslation('name') }}
            @else
                N/A
            @endif
        </td>

        {{-- Product Image --}}
        <td>
            @if ($productData && $productData->thumbnail_img)
                <img src="{{ uploaded_asset($productData->thumbnail_img) }}"
                    height="44"
                    class="mw-100 mx-auto">
            @else
                N/A
            @endif
        </td>

        {{-- Current Quantity --}}
        <td>
            {{ $product->stock ?? 0 }}
        </td>

        {{-- Category --}}
        <td>
            {{ $product->categories->first()->name ?? 'N/A' }}
        </td>

        {{-- SKU --}}
        <td>
            @if ($productData)
                {{ $productData->sku ?? 'N/A' }}
            @else
                N/A
            @endif
        </td>

        {{-- Base Price --}}
        <td>
            @if ($productData)
                {{ single_price($productData->unit_price ?? 0) }}
            @else
                {{ single_price(0) }}
            @endif
        </td>
    </tr>
@endforeach


                    </tbody>
                </table>
                <div class="aiz-pagination">

                </div>
            </div>
        </form>
    </div>

@endsection

@section('modal')
    <!-- Delete modal -->
    {{-- @include('modals.delete_modal')
    <!-- Bulk Delete modal -->
    @include('modals.bulk_delete_modal') --}}
@endsection

@section('script')
    <script type="text/javascript">
        $(document).on("change", ".check-all", function() {
            if (this.checked) {
                // Iterate each checkbox
                $('.check-one:checkbox').each(function() {
                    this.checked = true;
                });
            } else {
                $('.check-one:checkbox').each(function() {
                    this.checked = false;
                });
            }

        });

        // function update_featured(el){
        //     if(el.checked){
        //         var status = 1;
        //     }
        //     else{
        //         var status = 0;
        //     }
        //     $.post('{{ route('seller.products.featured') }}', {_token:'{{ csrf_token() }}', id:el.value, status:status}, function(data){
        //         if(data == 1){
        //             AIZ.plugins.notify('success', '{{ translate('Featured products updated successfully') }}');
        //         }
        //         else{
        //             AIZ.plugins.notify('danger', '{{ translate('Something went wrong') }}');
        //             location.reload();
        //         }
        //     });
        // }

        // function update_published(el){
        //     if(el.checked){
        //         var status = 1;
        //     }
        //     else{
        //         var status = 0;
        //     }
        //     $.post('{{ route('seller.products.published') }}', {_token:'{{ csrf_token() }}', id:el.value, status:status}, function(data){
        //         if(data == 1){
        //             AIZ.plugins.notify('success', '{{ translate('Published products updated successfully') }}');
        //         }
        //         else if(data == 2){
        //             AIZ.plugins.notify('danger', '{{ translate('Please upgrade your package.') }}');
        //             location.reload();
        //         }
        //         else{
        //             AIZ.plugins.notify('danger', '{{ translate('Something went wrong') }}');
        //             location.reload();
        //         }
        //     });
        // }

        // function bulk_delete() {
        //     var data = new FormData($('#sort_products')[0]);
        //     $.ajax({
        //         headers: {
        //             'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        //         },
        //         url: "{{ route('seller.products.bulk-delete') }}",
        //         type: 'POST',
        //         data: data,
        //         cache: false,
        //         contentType: false,
        //         processData: false,
        //         success: function (response) {
        //             if(response == 1) {
        //                 location.reload();
        //             }
        //         }
        //     });
        // }
    </script>
@endsection
