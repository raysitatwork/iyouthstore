@extends('backend.layouts.app')

@section('content')

    <div class="card">

        <div class="card-header d-flex align-items-center justify-content-between">

            <div>

                <h5 class="mb-1">
                    {{ translate('Seller Product Requests') }}
                </h5>

                <h6 class="mb-0 text-muted">
                    {{ $product->name }}
                </h6>

            </div>

            <a href="{{ route('seller-request-orders.index') }}"
               class="btn btn-light">

                <i class="las la-arrow-left"></i>
                {{ translate('Back') }}

            </a>

        </div>


        <div class="card-body">

            {{-- Summary --}}
            <div class="row mb-4">

                <div class="col-md-4">

                    <div class="card bg-soft-primary border-0">

                        <div class="card-body">

                            <h6 class="text-muted">
                                {{ translate('Product') }}
                            </h6>

                            <h5 class="mb-0">
                                {{ $product->name }}
                            </h5>

                        </div>

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="card bg-soft-success border-0">

                        <div class="card-body">

                            <h6 class="text-muted">
                                {{ translate('Total Required Quantity') }}
                            </h6>

                            <h3 class="mb-0">
                                {{ $totalQuantity }}
                            </h3>

                        </div>

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="card bg-soft-info border-0">

                        <div class="card-body">

                            <h6 class="text-muted">
                                {{ translate('Total Sellers') }}
                            </h6>

                            <h3 class="mb-0">
                                {{ $totalSellers }}
                            </h3>

                        </div>

                    </div>

                </div>

            </div>


            {{-- Seller Requests --}}
            <div class="table-responsive">

                <table class="table aiz-table mb-0">

                    <thead>

                        <tr>

                            <th>#</th>

                            <th>{{ translate('Seller') }}</th>

                            <th>{{ translate('Order Code') }}</th>

                            <th>{{ translate('Required Quantity') }}</th>

                            <th>{{ translate('Delivery Status') }}</th>

                            <th>{{ translate('Payment Status') }}</th>

                            <th>{{ translate('Date') }}</th>

                            <th class="text-right">
                                {{ translate('Order') }}
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @forelse ($sellerRequests as $key => $request)

                            <tr>

                                <td>
                                    {{ $key + 1 }}
                                </td>


                                <td>

                                    <strong>
                                        {{ $request->seller_name }}
                                    </strong>

                                </td>


                                <td>
                                    {{ $request->order_code }}
                                </td>


                                <td>

                                    <span class="badge badge-inline badge-primary">

                                        {{ $request->quantity }}

                                    </span>

                                </td>


                                <td>

                                    @php
                                        $deliveryStatus = ucfirst(
                                            str_replace('_', ' ', $request->delivery_status)
                                        );
                                    @endphp

                                    <span class="badge badge-inline badge-secondary">

                                        {{ translate($deliveryStatus) }}

                                    </span>

                                </td>


                                <td>

                                    @if ($request->payment_status == 'paid')

                                        <span class="badge badge-inline badge-success">
                                            {{ translate('Paid') }}
                                        </span>

                                    @else

                                        <span class="badge badge-inline badge-danger">
                                            {{ translate('Unpaid') }}
                                        </span>

                                    @endif

                                </td>


                                <td>

                                    {{ date('d-m-Y', strtotime($request->created_at)) }}

                                </td>


                                <td class="text-right">

                                    <a href="{{ route('seller-purchases.show', encrypt($request->order_id)) }}"
                                       class="btn btn-soft-primary btn-icon btn-circle btn-sm"
                                       title="{{ translate('View Order') }}">

                                        <i class="las la-eye"></i>

                                    </a>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="8" class="text-center">

                                    {{ translate('No seller requests found') }}

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>

@endsection
