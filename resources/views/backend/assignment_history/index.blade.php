@extends('backend.layouts.app')

@section('content')
    <div class="card">

        {{-- HEADER --}}
        <div class="card-header d-flex justify-content-between align-items-center">

            <h5>Seller Assignment History</h5>

            {{-- SEARCH --}}
            <form method="GET" action="" class="d-flex">
                <input type="text" name="search" class="form-control" placeholder="Search seller..."
                    value="{{ request('search') }}">

                <button type="submit" class="btn btn-primary ml-2">
                    Search
                </button>
            </form>

        </div>


        {{-- FILTERS --}}
        <div class="px-3 pt-3">

            <form id="sort_sellers" method="GET">

                {{-- Retain search --}}
                <input type="hidden" name="search" value="{{ request('search') }}">

                <div class="row">

                    {{-- DISTRICT --}}
                    <div class="col-md-2 mb-3">

                        <select class="form-control aiz-selectpicker" name="district_id" data-live-search="true"
                            onchange="sort_sellers()">

                            <option value="">
                                District
                            </option>

                            @foreach (\App\Models\City::where('status', 1)->get() as $district)
                                <option value="{{ $district->id }}"
                                    {{ request('district_id') == $district->id ? 'selected' : '' }}>

                                    {{ $district->name }}

                                </option>
                            @endforeach

                        </select>

                    </div>


                    {{-- BLOCK --}}
                    <div class="col-md-2 mb-3">

                        <select class="form-control aiz-selectpicker" name="block_id" data-live-search="true"
                            onchange="sort_sellers()">

                            <option value="">
                                Block
                            </option>

                            @foreach (\App\Models\Block::where('status', 1)->get() as $block)
                                <option value="{{ $block->id }}"
                                    {{ request('block_id') == $block->id ? 'selected' : '' }}>

                                    {{ $block->name }}

                                </option>
                            @endforeach

                        </select>

                    </div>


                    {{-- SUB DISTRICT --}}
                    <div class="col-md-2 mb-3">

                        <select class="form-control aiz-selectpicker" name="sub_district_id" data-live-search="true"
                            onchange="sort_sellers()">

                            <option value="">
                                Subdistrict
                            </option>

                            @foreach (\App\Models\SubDistrict::where('status', 1)->get() as $sub)
                                <option value="{{ $sub->id }}"
                                    {{ request('sub_district_id') == $sub->id ? 'selected' : '' }}>

                                    {{ $sub->name }}

                                </option>
                            @endforeach

                        </select>

                    </div>

                </div>

            </form>

        </div>


        {{-- TABLE --}}
        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-bordered table-hover">

                    <thead>

                        <tr>
                            <th>#</th>
                            <th>Seller Name</th>
                            <th>Shop ID</th>
                            <th>History</th>
                        </tr>

                    </thead>


                    <tbody>

                        @forelse($sellers as $key => $seller)
                            <tr>

                                {{-- NUMBER --}}
                                <td>
                                    {{ ($sellers->currentPage() - 1) * $sellers->perPage() + $key + 1 }}
                                </td>


                                {{-- SELLER NAME --}}
                                <td>
                                    {{ $seller->seller_name }}
                                </td>


                                {{-- SHOP ID --}}
                                <td>
                                    {{ $seller->shop_id }}
                                </td>


                                {{-- HISTORY --}}
                                <td>

                                    <a href="{{ route('assignment.history.show', $seller->seller_id) }}"
                                        class="btn btn-info btn-sm">

                                        <i class="las la-history"></i>
                                        View History

                                    </a>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="4" class="text-center">

                                    No sellers found

                                </td>

                            </tr>
                        @endforelse

                    </tbody>

                </table>

            </div>


            {{-- PAGINATION --}}
            <div class="mt-3">

                {{ $sellers->appends(request()->query())->links() }}

            </div>

        </div>

    </div>
@endsection


@section('script')
    <script>
        function sort_sellers() {

            $('#sort_sellers').submit();

        }
    </script>
@endsection
