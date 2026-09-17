@extends('frontend.layouts.app')

@section('content')
    <div class="position-relative">
        <div class="position-absolute" id="particles-js"></div>
        <div class="position-relative container">
            <!-- Breadcrumb -->
            <section class="pt-4 mb-3">
                <div class="row">
                    <div class="col-lg-6 text-center text-lg-left">
                        <h1 class="fw-700 fs-20 fs-md-24 text-dark">{{ translate('Our Stores') }}</h1>
                    </div>
                    <div class="col-lg-6">
                        <ul class="breadcrumb bg-transparent p-0 justify-content-center justify-content-lg-end">
                            <li class="breadcrumb-item has-transition opacity-60 hov-opacity-100">
                                <a class="text-reset" href="{{ route('home') }}">{{ translate('Home')}}</a>
                            </li>
                            <li class="text-dark fw-600 breadcrumb-item">
                                "{{ translate('All Sellers') }}"
                            </li>
                        </ul>
                    </div>
                </div>
            </section>
            <!-- All Sellers -->
            <section class="mb-3 pb-3">
                <div class="bg-white px-3">
                    <div class="row row-cols-xl-4 row-cols-md-4 row-cols-sm-2 row-cols-1 gutters-16 border-top border-left">
                        @foreach ($shops as $key => $shop)
                            @if ($shop->user != null)
                                <div class="col text-center border-right border-bottom has-transition hov-shadow-out z-1">
                                    <div class="position-relative px-3" style="padding-top: 2rem; padding-bottom:2rem;">
                                        <!-- Shop logo & Verification Status -->
                                        <div class="position-relative mx-auto size-200px size-md-200px">
                                         
                                         
<img src="{{ static_asset('assets/img/placeholder.jpg') }}"
     data-src="{{ static_asset($shop->user->image) }}"
     alt="{{ $shop->name }}" height="200px" width="200px"
     class="img-fit lazyload has-transition"
     onerror="this.onerror=null;this.src='https://iyouthstore.in/public/uploads/all/kU21GQWrj8hG548noAvBhwxgJTAx7uRwMdVE3BxI.webp';">
                                                    
                                                    
                                           
                                            <div class="absolute-top-right z-1 mr-md-2 mt-1 rounded-content bg-white">
                                                @if ($shop->verification_status == 1)
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24.001" height="24" viewBox="0 0 24.001 24">
                                                        <g id="Group_25929" data-name="Group 25929" transform="translate(-480 -345)">
                                                            <circle id="Ellipse_637" data-name="Ellipse 637" cx="12" cy="12" r="12" transform="translate(480 345)" fill="#fff"/>
                                                            <g id="Group_25927" data-name="Group 25927" transform="translate(480 345)">
                                                            <path id="Union_5" data-name="Union 5" d="M0,12A12,12,0,1,1,12,24,12,12,0,0,1,0,12Zm1.2,0A10.8,10.8,0,1,0,12,1.2,10.812,10.812,0,0,0,1.2,12Zm1.2,0A9.6,9.6,0,1,1,12,21.6,9.611,9.611,0,0,1,2.4,12Zm5.115-1.244a1.083,1.083,0,0,0,0,1.529l3.059,3.059a1.081,1.081,0,0,0,1.529,0l5.1-5.1a1.084,1.084,0,0,0,0-1.53,1.081,1.081,0,0,0-1.529,0L11.339,13.05,9.045,10.756a1.082,1.082,0,0,0-1.53,0Z" transform="translate(0 0)" fill="#3490f3"/>
                                                            </g>
                                                        </g>
                                                    </svg>
                                                @else
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24.001" height="24" viewBox="0 0 24.001 24">
                                                        <g id="Group_25929" data-name="Group 25929" transform="translate(-480 -345)">
                                                            <circle id="Ellipse_637" data-name="Ellipse 637" cx="12" cy="12" r="12" transform="translate(480 345)" fill="#fff"/>
                                                            <g id="Group_25927" data-name="Group 25927" transform="translate(480 345)">
                                                            <path id="Union_5" data-name="Union 5" d="M0,12A12,12,0,1,1,12,24,12,12,0,0,1,0,12Zm1.2,0A10.8,10.8,0,1,0,12,1.2,10.812,10.812,0,0,0,1.2,12Zm1.2,0A9.6,9.6,0,1,1,12,21.6,9.611,9.611,0,0,1,2.4,12Zm5.115-1.244a1.083,1.083,0,0,0,0,1.529l3.059,3.059a1.081,1.081,0,0,0,1.529,0l5.1-5.1a1.084,1.084,0,0,0,0-1.53,1.081,1.081,0,0,0-1.529,0L11.339,13.05,9.045,10.756a1.082,1.082,0,0,0-1.53,0Z" transform="translate(0 0)" fill="red"/>
                                                            </g>
                                                        </g>
                                                    </svg>
                                                @endif
                                            </div>
                                        </div>
                                        <!-- Shop name -->
                                        <h2 class="fs-14 fw-700 text-dark text-truncate-2 mt-3">
                                 {{ $shop->name }}
                                        </h2>
                                        
                                        <p>Address - {{$shop->address}}, Bilaspur District</p>
                                        
                                        
                                     
                                    
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                    <!-- Pagination -->
                    <div class="aiz-pagination aiz-pagination-center mt-4">
                        {{ $shops->links() }}
                    </div>
                </div>
                
                
              
            </section>
        </div>
    </div>
    
      <div class="container-fluid row">
                   <iframe class="col-lg-12" src="https://www.google.com/maps/embed?pb=!1m16!1m12!1m3!1d945449.0907543172!2d81.59415193659616!3d22.233987945399914!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!2m1!1siyouth%20store!5e0!3m2!1sen!2sin!4v1788770093214!5m2!1sen!2sin"  height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe>
                </div>

@endsection

@section('script')
    <script>
        AIZ.plugins.particles();
    </script>
@endsection
