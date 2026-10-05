@extends('seller.layouts.app')

@section('panel_content')
    <div class="aiz-titlebar mt-2 mb-4">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="h3">{{ translate('Products Enquiry') }}</h1>
            </div>
        </div>
    </div>


    <div class="card">
        <form class="" id="sort_products" action="" method="GET">
            <div class="card-header row gutters-5">
                <div class="col">
                    <h5 class="mb-md-0 h6">{{ translate('All Products Enquiry') }}</h5>
                </div>

                {{-- <div class="dropdown mb-2 mb-md-0">
                    <button class="btn border dropdown-toggle" type="button" data-toggle="dropdown">
                        {{ translate('Bulk Action') }}
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a class="dropdown-item confirm-alert" href="javascript:void(0)" data-target="#bulk-delete-modal">
                            {{ translate('Delete selection') }}</a>
                    </div>
                </div> --}}
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
                           
                            <th>{{ translate('Product') }}</th>
                            <th>Image</th>
                            <th data-breakpoints="md">{{ translate('Price ') }}</th>

                            <th data-breakpoints="md">Stock</th>
                            <th data-breakpoints="md">Remark/Description</th>
                            <th data-breakpoints="md">Action</th>


                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($enquiry as $row)

                            <tr>

                               

                                <td>{{ $loop->iteration }}</td>

                                 <td>
                                    
                                </td>

                                {{-- Product Name --}}
                                <td>
                                    @if ($row)
                                        {{ $row->name }}
                                    @else
                                        N/A
                                    @endif
                                </td>

                                {{-- Product Image --}}
                                <td>
                                    @if ($row && $row->image)
                                        <img src="{{ uploaded_asset($row->image) }}" height="44"
                                            class="mw-100 mx-auto">
                                    @else
                                        N/A
                                    @endif
                                </td>

                                {{-- Current Quantity --}}
                                <td>
                                    {{ $row->price ?? 0 }}
                                </td>

                                {{-- Category --}}
                                <td>
                                    {{ $row->stock ?? 'N/A' }}
                                </td>

                                <td>
                                    @if ($row)
                                        {{ $row->remark ?? 'N/A' }}
                                    @else
                                        N/A
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
    </script>
@endsection
