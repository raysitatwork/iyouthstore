@extends('frontend.layouts.user_panel')

@section('panel_content')
<div class="card shadow-none rounded-0 border p-2">
    <h5 class="mb-2 fs-20 fw-700 text-dark">{{ translate('Purchase History') }}</h5>

    <!-- Tabs & Filters -->
    <div class=" justify-content-between align-items-center border-bottom pb-3">
        <!--<ul class="nav nav-tabs purchase-history-tab border-0 fs-12 ml-n3" id="orderTabs">-->
        <!--    @foreach (['All', 'Unpaid', 'Confirmed', 'Picked_Up', 'Delivered', 'To Review'] as $status)-->
        <!--    <li class="nav-item">-->
        <!--        <button class="nav-link {{ $loop->first ? 'active' : '' }}"-->
        <!--            onclick="changeTab(this, '{{ Str::slug($status) }}')">-->
        <!--            {{ translate($status) }}-->
        <!--        </button>-->
        <!--    </li>-->
        <!--    @endforeach-->
        <!--</ul>-->

        <div class="form-group mb-0 w-50">
            <!--<select class="form-control aiz-selectpicker purchase-history" name="delivery_status" id="delivery_status"-->
            <!--    data-style="btn-light" data-width="100%">-->
            <!--    <option value="">{{ translate('All') }}</option>-->
            <!--    <option value="pending" {{ request('delivery_status') == 'pending' ? 'selected' : '' }}>{{ translate('Pending') }}</option>-->
            <!--    <option value="on_the_way" {{ request('delivery_status') == 'on_the_way' ? 'selected' : '' }}>{{ translate('On The Way') }}</option>-->
            <!--    <option value="delivered" {{ request('delivery_status') == 'delivered' ? 'selected' : '' }}>{{ translate('Delivered') }}</option>-->
            <!--    <option value="cancelled" {{ request('delivery_status') == 'cancelled' ? 'selected' : '' }}>{{ translate('Cancelled') }}</option>-->
            <!--</select>-->
            
            <select data-style="btn-light"
        data-width="100%"
        class="form-control aiz-selectpicker purchase-history"
        name="delivery_status"
        id="delivery_status">

    <option value="">All</option>
    <option value="confirmed">Confirmed</option>
    <option value="pending">Pending</option>
    <option value="on_the_way">On The Way</option>
    <option value="delivered">Delivered</option>
    <option value="cancelled">Cancelled</option>

</select>
            
        </div>
    </div>

    <!-- Dynamic Tab Content -->
    <div class=" mt-4" id="">
        <div class="tab-pane fade show active" id="tab-content">
            <!-- AJAX content will load here -->
       
              @foreach($orders as $order)
  <div class="mb-4">
      <div class="d-flex justify-content-between align-items-center mb-2">
          <div>
              <p class="mb-2 fs-16 fw-700 deep-blue mb-0"><a class="deep-blue" href="{{route('purchase_history.details', encrypt($order->id))}}">{{ translate('Order ID')}} - {{ $order->code }}</a></p>
              <span class="text-muted d-block d-md-none fs-12">{{ translate('Date')}}: {{ date('d-m-Y', $order->date) }}</span>
          </div>

          <!-- Mobile-only buttons -->
          <div class="d-flex gap-2 d-md-none">
              <!--<a type="button" href="{{ route('re_order', encrypt($order->id)) }}" class="btn btn-sm border  rounded px-4 py-1 text-muted reorder-btn">-->
              <!--    {{ translate('Reorder') }}-->
              <!--</a>-->

              <div class="dropdown">
                  <button type="button"
                      class="btn btn-sm dropdown-toggle text-white px-4 py-1 rounded btn-options ml-2"
                      data-toggle="dropdown">
                      {{ translate('Options') }}
                  </button>
                  <div class="dropdown-menu dropdown-menu-right">
                      <a class="dropdown-item text-secondary dropdown-bg-hover" href="{{route('purchase_history.details', encrypt($order->id))}}"><i class="las la-eye mr-2"></i>{{ translate('View') }}</a>
                      <a class="dropdown-item text-secondary dropdown-bg-hover" href="{{ route('invoice.download', $order->id) }}"><i class="las la-download mr-2"></i>{{ translate('Invoice') }}</a>
                      @if ($order->delivery_status == 'pending' && $order->payment_status == 'unpaid')
                        <a href="javascript:void(0)"  class="dropdown-item text-secondary dropdown-bg-hover confirm-delete" data-href="{{route('purchase_history.destroy', $order->id)}}"><i class="las la-trash mr-2"></i> {{ translate('Cancel') }}</a>
                      @endif
                  </div>
              </div>
          </div>

      </div>

      <!-- Desktop-only buttons (original position) -->
      <div class="row align-items-center mb-2 d-none d-md-flex">
          <div class="col-md-6">
              <div class="row fs-12">
                  <!--<div class="col-auto w-200px">-->
                  <!--    <span class="font-weight-bold light-blue">{{ get_shop_by_user_id($order->seller_id)->name??"Inhouse Products" }}</span>-->
                  <!--</div>-->
                  <div class="col">
                      <span class="text-muted">{{ translate('Date')}}: {{ date('d-m-Y', $order->date) }}</span>
                  </div>
              </div>
          </div>
          <div class="col-md-6 text-right">
              <!--<a type="button" class="btn btn-sm border rounded px-4 py-1 text-muted reorder-btn" href="{{ route('re_order', encrypt($order->id)) }}">-->
              <!--    {{ translate('Reorder') }}-->
              <!--</a>-->

              <div class="d-inline-block dropdown ml-1">
                  <button type="button"
                      class="btn btn-sm dropdown-toggle text-white px-4 py-1 rounded btn-options"
                      data-toggle="dropdown">
                      {{ translate('Options') }}
                  </button>

                  <div class="dropdown-menu dropdown-menu-right ">
                      <a class="dropdown-item text-secondary dropdown-bg-hover" href="{{route('purchase_history.details', encrypt($order->id))}}"><i class="las la-eye mr-2"></i>{{ translate('View') }}</a>
                      <a class="dropdown-item text-secondary dropdown-bg-hover" href="{{ route('invoice.download', $order->id) }}"><i class="las la-download mr-2"></i>{{ translate('Invoice') }}</a>
                      @if ($order->delivery_status == 'pending' && $order->payment_status == 'unpaid')
                      <a href="javascript:void(0)"  class="dropdown-item text-secondary dropdown-bg-hover confirm-delete" data-href="{{route('purchase_history.destroy', $order->id)}}"><i class="las la-trash mr-2"></i> {{ translate('Cancel') }}</a>
                      @endif
                  </div>
              </div>
          </div>
      </div>
      <!-- Mobile-only on the way and paid buttons -->
      <div class="d-flex d-md-none text-start mb-2 mt-3">
          <span class="btn btn-sm rounded-pill btn-on-the-way">
              {{ translate(ucfirst(str_replace('_', ' ', $order->delivery_status))) }}
          </span>
          @if ($order->payment_status == 'paid')
          <span class="btn btn-sm rounded-pill btn-paid ml-2">
              {{ translate('Paid') }}
          </span>
          @else
          <span class="btn btn-sm rounded-pill btn-unpaid">
              {{ translate('Unpaid') }}
          </span>
          @endif
      </div>

      <hr class="border-dashed">

      <!-- image,product name,price,on the way,paid button row -->
      <!-- image,product name,price,on the way,paid button row -->


      <div class="row align-items-center mb-3">


          <div class="col-md-9">
              @foreach($order->orderDetails as $orderDetail)
              @if (!$loop->first)
              <hr class="hr-split">
              @endif
              <div class="row">
                  <div class="col-md-8 d-flex align-items-center">
                      <img src="{{ uploaded_asset($orderDetail->product->thumbnail_img) }}"
                          class="img-fluid mr-3 product-history-img">

                      <div class=" text-wrap">
                          <div class="font-weight-semibold fs-14 product-name-color mobile-title-shift text-truncate-2"
                              title="{{ $orderDetail->product->getTranslation('name') }}">
                              {{ $orderDetail->product->getTranslation('name') }}
                          </div>
                          <div class="text-muted small mb-2 mobile-title-shift">{{ $orderDetail->variation }}</div>
                      </div>

                  </div>

                  <div class="col-md-4">
                      <div class="font-weight-bold">{{ single_price($orderDetail->price) }}</div>
                      <div class="text-muted small">{{ translate('Qty')}} {{ $orderDetail->quantity }}</div>
                  </div>

              </div>
              @endforeach
          </div>


          <!-- Desktop-only buttons in right column -->
          <div class="col-md-3 text-right d-none d-md-block align-self-start">
              <div>
                  <span class="btn btn-sm rounded-pill btn-on-the-way">
                      {{ translate(ucfirst(str_replace('_', ' ', $order->delivery_status))) }}
                  </span>
              </div>
              @if ($order->payment_status == 'paid')
              <div class="mt-2">
                  <span class="btn btn-sm rounded-pill btn-paid">
                      {{ translate('Paid') }}
                  </span>
              </div>
              @else
              <div class="mt-2">
                  <span class="btn btn-sm rounded-pill btn-unpaid">
                      {{ translate('Unpaid') }}
                  </span>    
              </div>
              @endif
          </div>
      </div>


      <hr class="hr-split">
      <hr>

  </div>

  @endforeach

  <div class="aiz-pagination mt-4" id="pagination">
      {{ $orders->links() }}
  </div>
        </div>
    </div>
</div>
@endsection

@section('modal')
<!-- Product Review Modal -->
<div class="modal fade" id="product-review-modal">
    <div class="modal-dialog">
        <div class="modal-content" id="product-review-modal-content"></div>
    </div>
</div>

<!-- Delete modal -->
<div id="delete-modal" class="modal fade">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title h6">{{translate('Cancel Confirmation')}}</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
            </div>
            <div class="modal-body text-center">
                <p class="mt-1 fs-14">{{translate('Are you sure to Cancel this Order?')}}</p>
                <button type="button" class="btn btn-secondary rounded-5 mt-2 btn-sm" data-dismiss="modal">{{translate('No')}}</button>
                <a href="" id="delete-link" class="btn btn-primary rounded-5 mt-2 btn-sm">{{translate('Yes')}}</a>
            </div>
        </div>
    </div>
</div>
@endsection



@section('script')


<script>
$(document).on('changed.bs.select', '#delivery_status', function () {

    let status = $(this).val();

    $.ajax({
        url: "{{ route('purchase_history.filter') }}",
        type: "GET",
        data: {
            delivery_status: status
        },

        beforeSend: function () {
            $('#tab-content').html(
                '<div class="text-center py-5">Loading...</div>'
            );
        },

        success: function (response) {

            if (response.success) {

                if (response.html && response.html.trim() !== '') {
                    $('#tab-content').html(response.html);
                    
                } else {
                    $('#tab-content').html(`
                        <div class="text-center py-5">
                            <h5 class="text-muted">No Record Found</h5>
                        </div>
                    `);
                }

            } else {

                $('#tab-content').html(`
                    <div class="text-center py-5">
                        <h5 class="text-muted">No Record Found</h5>
                    </div>
                `);
            }
        },

        error: function (xhr) {

            console.log(xhr.responseText);

            $('#tab-content').html(`
                <div class="text-center text-danger py-5">
                    Failed to load orders.
                </div>
            `);
        }
    });

});
</script>
@endsection

