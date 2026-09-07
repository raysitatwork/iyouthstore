@extends('backend.layouts.app')

@section('content')

    <div class="card">

        <form action="{{ route('seller-request-orders.index') }}" method="GET">

            <div class="card-header row gutters-5">

                <div class="col">
                    <h5 class="mb-md-0 h6">
                        {{ translate('Seller Request Orders') }}
                    </h5>
                </div>

                <div class="col-lg-3 ml-auto">
                    <input type="text"
                           class="form-control"
                           name="search"
                           value="{{ $search }}"
                           placeholder="{{ translate('Search Product') }}">
                </div>

                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">
                        {{ translate('Filter') }}
                    </button>
                </div>

            </div>


            <div class="card-body">

                <table class="table aiz-table mb-0">

                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ translate('Product') }}</th>
                            <th>{{ translate('Total Quantity') }}</th>
                            <th>{{ translate('Total Sellers') }}</th>
                            <th>{{ translate('Total Orders') }}</th>
                            <th class="text-right">
                                {{ translate('Options') }}
                            </th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse ($productRequests as $key => $request)

                            <tr>

                                <td>
                                    {{ $key + 1 + (($productRequests->currentPage() - 1) * $productRequests->perPage()) }}
                                </td>

                                <td>

                                    @if ($request->product)

                                        <div class="d-flex align-items-center">

                                            <div class="mr-3">

                                                @php
                                                    $productImage = $request->product->thumbnail_img
                                                        ? uploaded_asset($request->product->thumbnail_img)
                                                        : static_asset('assets/img/placeholder.jpg');
                                                @endphp

                                                <img src="{{ $productImage }}"
                                                     class="rounded"
                                                     width="50"
                                                     height="50"
                                                     style="object-fit: cover;">

                                            </div>

                                            <div>

                                                <div class="font-weight-bold">
                                                    {{ $request->product->name }}
                                                </div>

                                                <small class="text-muted">
                                                    Product ID: {{ $request->product_id }}
                                                </small>

                                            </div>

                                        </div>

                                    @else

                                        <span class="text-danger">
                                            {{ translate('Product Deleted') }}
                                        </span>

                                    @endif

                                </td>

                                <td>

                                    <span class="badge badge-inline badge-primary">
                                        {{ $request->total_quantity }}
                                    </span>

                                </td>

                                <td>

                                    <span class="badge badge-inline badge-info">
                                        {{ $request->total_sellers }}
                                    </span>

                                </td>

                                <td>

                                    <span class="badge badge-inline badge-secondary">
                                        {{ $request->total_orders }}
                                    </span>

                                </td>

                                <td class="text-right">

                                    <a href="{{ route('seller-request-orders.show', $request->product_id) }}"
                                       class="btn btn-soft-primary btn-icon btn-circle btn-sm"
                                       title="{{ translate('View Seller Requests') }}">

                                        <i class="las la-eye"></i>

                                    </a>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="6" class="text-center">
                                    {{ translate('No seller request orders found') }}
                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>


                <div class="aiz-pagination">

                    {{ $productRequests->appends(request()->input())->links() }}

                </div>

            </div>

        </form>

    </div>

@endsection
