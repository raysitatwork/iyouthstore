@extends('backend.layouts.app')

@section('content')

<div class="aiz-titlebar mt-2 mb-3">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="h3 mb-0">{{ translate('Products Cart Report') }}</h1>
    </div>
</div>

<div class="row">
    <div class="col-md-12">

        <div class="card">
            <div class="card-body">

                {{-- FILTER SECTION --}}
                <form action="{{ route('cart_report.index') }}" method="GET">
                    <div class="row align-items-end mb-3">

                        <div class="col-md-3">
                            <label class="form-label">
                                {{ translate('Sort by Category') }}
                            </label>

                            <select class="form-control aiz-selectpicker"
                                    name="category_id"
                                    data-live-search="true">

                                <option value="">
                                    {{ translate('All Categories') }}
                                </option>

                                @foreach (\App\Models\Category::all() as $category)
                                    <option value="{{ $category->id }}"
                                        {{ $category->id == $sort_by ? 'selected' : '' }}>

                                        {{ $category->getTranslation('name') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <button class="btn btn-primary mr-2" type="submit">
                                <i class="las la-filter"></i>
                                {{ translate('Filter') }}
                            </button>

                            <a href="{{ route('cart_report.index') }}"
                               class="btn btn-light">
                                {{ translate('Reset') }}
                            </a>
                        </div>

                    </div>
                </form>

                {{-- TABLE --}}
                <div class="table-responsive">
                    <table class="table table-bordered aiz-table mb-0">
                        <thead>
                            <tr>
                                <th width="25%">{{ translate('Product') }}</th>
                                <th width="20%">{{ translate('Customer') }}</th>
                                <th width="25%">{{ translate('Location') }}</th>
                                <th width="10%">{{ translate('Qty') }}</th>
                                <th width="20%">{{ translate('Added Date') }}</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($carts as $cart)

                             @if(!empty($wishlist->user->name))
                                <tr>

                                    {{-- PRODUCT --}}
                                    <td>
                                        {{ $cart->product->getTranslation('name') ?? '-' }}
                                    </td>

                                    {{-- CUSTOMER --}}
                                    <td>
                                        {{ $cart->customer->name ?? '-' }}
                                    </td>

                                    {{-- LOCATION --}}
                                    <td class="text-muted">
                                        {{ $cart->address->address ?? '-' }}
                                    </td>

                                    {{-- QTY --}}
                                    <td>
                                        {{ $cart->quantity ?? 1 }}
                                    </td>

                                    {{-- DATE --}}
                                    <td>
                                        {{ optional($cart->created_at)->format('d M Y, h:i A') }}
                                    </td>

                                </tr>

                                @endif
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4">
                                        <div class="text-muted">
                                            {{ translate('No cart data found') }}
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- PAGINATION --}}
                <div class="aiz-pagination mt-4">
                    {{ $carts->appends(request()->input())->links() }}
                </div>

            </div>
        </div>

    </div>
</div>

@endsection
