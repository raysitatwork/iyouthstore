@extends('backend.layouts.app')

@section('content')

<div class="aiz-titlebar mt-2 mb-3">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="h3 mb-0">{{ translate('Product Wish Report') }}</h1>
    </div>
</div>

<div class="row">
    <div class="col-md-12">

        <div class="card">
            <div class="card-body">

                {{-- FILTER SECTION --}}
                <form action="{{ route('wish_report.index') }}" method="GET">
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

                            <a href="{{ route('wish_report.index') }}"
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
                                <th>No</th>
                                <th width="25%">{{ translate('Product') }}</th>
                                <th width="20%">{{ translate('Customer') }}</th>
                                <th width="25%">{{ translate('Location') }}</th>
                                {{-- <th width="10%">{{ translate('Qty') }}</th> --}}
                                <th width="20%">{{ translate('Wish Date') }}</th>
                            </tr>
                        </thead>

                        <tbody>

                            @forelse ($wishlists as $wishlist)
 @if(!empty($wishlist->user->name))
                                <tr>

                                   
                                    <td>{{ $loop->iteration  }}</td>

                                    {{-- PRODUCT --}}
                                    <td>
                                        {{ $wishlist->product->getTranslation('name') ?? '-' }}
                                    </td>

                                    {{-- CUSTOMER --}}
                                    <td>
                                        {{ $wishlist->user->name ?? '-' }}
                                    </td>

                                    {{-- LOCATION --}}
                                    <td class="text-muted">
                                        {{ $wishlist->user->address ?? '-' }}
                                    </td>

                                    {{-- QTY --}}
                                    {{-- <td>
                                        1
                                    </td> --}}

                                    {{-- DATE --}}
                                    <td>
                                        {{ optional($wishlist->created_at)->format('d M Y, h:i A') }}
                                    </td>
                            

                                </tr>
        @endif
                            @empty

                                <tr>
                                    <td colspan="5" class="text-center py-4">
                                        <div class="text-muted">
                                            {{ translate('No wishlist data found') }}
                                        </div>
                                    </td>
                                </tr>

                            @endforelse

                        </tbody>

                    </table>
                </div>


                {{-- PAGINATION --}}
                <div class="aiz-pagination mt-4">
                    {{ $wishlists->appends(request()->input())->links() }}
                </div>


            </div>
        </div>

    </div>
</div>

@endsection
