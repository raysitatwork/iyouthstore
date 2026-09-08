<!-- Top Bar Banner -->
@php
    $topbar_banner = get_setting('topbar_banner');
    $topbar_banner_medium = get_setting('topbar_banner_medium');
    $topbar_banner_small = get_setting('topbar_banner_small');
    $topbar_banner_asset = uploaded_asset($topbar_banner);
@endphp
@if ($topbar_banner != null)
    <div class="position-relative top-banner removable-session z-1035 d-none" data-key="top-banner" data-value="removed">
        <a href="{{ get_setting('topbar_banner_link') }}" class="d-block text-reset h-40px h-lg-60px">
            <!-- For Large device -->
            <img src="{{ $topbar_banner_asset }}" class="d-none d-xl-block img-fit h-100"
                alt="{{ translate('topbar_banner') }}">
            <!-- For Medium device -->
            <img src="{{ $topbar_banner_medium != null ? uploaded_asset($topbar_banner_medium) : $topbar_banner_asset }}"
                class="d-none d-md-block d-xl-none img-fit h-100" alt="{{ translate('topbar_banner') }}">
            <!-- For Small device -->
            <img src="{{ $topbar_banner_small != null ? uploaded_asset($topbar_banner_small) : $topbar_banner_asset }}"
                class="d-md-none img-fit h-100" alt="{{ translate('topbar_banner') }}">
        </a>
        <button class="btn text-white h-100 absolute-top-right set-session" data-key="top-banner" data-value="removed"
            data-toggle="remove-parent" data-parent=".top-banner">
            <i class="la la-close la-2x"></i>
        </button>
    </div>
@endif
@include('header.' . get_element_type_by_id(get_setting('header_element')))
<!-- Top Menu Sidebar -->
<div class="aiz-top-menu-sidebar collapse-sidebar-wrap sidebar-xl sidebar-left d-lg-none z-1035">
    <div class="overlay overlay-fixed dark c-pointer" data-toggle="class-toggle" data-target=".aiz-top-menu-sidebar"
        data-same=".hide-top-menu-bar"></div>
    <div class="collapse-sidebar c-scrollbar-light text-left">
        <button type="button" class="btn btn-sm p-4 hide-top-menu-bar" data-toggle="class-toggle"
            data-target=".aiz-top-menu-sidebar">
            <i class="las la-times la-2x text-primary"></i>
        </button>
        @auth
            <span class="d-flex align-items-center nav-user-info pl-4">
                <!-- Image -->
                <span class="size-40px rounded-circle overflow-hidden border border-transparent nav-user-img">
                    @if ($user->avatar_original != null)
                        <img src="{{ $user_avatar }}" class="img-fit h-100" alt="{{ translate('avatar') }}"
                            onerror="this.onerror=null;this.src='{{ static_asset('assets/img/avatar-place.png') }}';">
                    @else
                        <img src="{{ static_asset('assets/img/avatar-place.png') }}" class="image"
                            alt="{{ translate('avatar') }}"
                            onerror="this.onerror=null;this.src='{{ static_asset('assets/img/avatar-place.png') }}';">
                    @endif
                </span>
                <!-- Name -->
                <h4 class="h5 fs-14 fw-700 text-dark ml-2 mb-0">{{ $user->name }}</h4>
            </span>
        @else
            <!--Login & Registration -->
            <span class="d-flex align-items-center nav-user-info pl-4">
                <!-- Image -->
                <span
                    class="size-40px rounded-circle overflow-hidden border d-flex align-items-center justify-content-center nav-user-img">
                    <svg xmlns="http://www.w3.org/2000/svg" width="19.902" height="20.012" viewBox="0 0 19.902 20.012">
                        <path id="fe2df171891038b33e9624c27e96e367"
                            d="M15.71,12.71a6,6,0,1,0-7.42,0,10,10,0,0,0-6.22,8.18,1.006,1.006,0,1,0,2,.22,8,8,0,0,1,15.9,0,1,1,0,0,0,1,.89h.11a1,1,0,0,0,.88-1.1,10,10,0,0,0-6.25-8.19ZM12,12a4,4,0,1,1,4-4A4,4,0,0,1,12,12Z"
                            transform="translate(-2.064 -1.995)" fill="#91919b" />
                    </svg>
                </span>

                <a href="{{ route('user.login') }}"
                    class="text-reset opacity-60 hov-opacity-100 hov-text-primary fs-12 d-inline-block border-right border-soft-light border-width-2 pr-2 ml-3">{{ translate('Login') }}</a>
                <a href="{{ route(get_setting('customer_registration_verify') === '1' ? 'registration.verification' : 'user.registration') }}"
                    {{-- <a href="{{ route('user.registration') }}" --}}
                    class="text-reset opacity-60 hov-opacity-100 hov-text-primary fs-12 d-inline-block py-2 pl-2">{{ translate('Registration') }}</a>
            </span>
        @endauth
        <hr>
        <ul class="mb-0 pl-3 pb-3 h-100">
            @if (get_setting('header_menu_labels') != null)
                @foreach (json_decode(get_setting('header_menu_labels'), true) as $key => $value)
                    <li class="mr-0">
                        <a href="{{ json_decode(get_setting('header_menu_links'), true)[$key] }}"
                            class="fs-13 px-3 py-3 w-100 d-inline-block fw-700 text-dark header_menu_links
                                    @if (url()->current() == json_decode(get_setting('header_menu_links'), true)[$key]) active @endif">
                            {{ translate($value) }}
                        </a>
                    </li>
                @endforeach
            @endif
            @auth
                @if (isAdmin())
                    <hr>
                    <li class="mr-0">
                        <a href="{{ route('admin.dashboard') }}"
                            class="fs-13 px-3 py-3 w-100 d-inline-block fw-700 text-dark header_menu_links">
                            {{ translate('My Account') }}
                        </a>
                    </li>
                @else
                    <hr>
                    <li class="mr-0">
                        <a href="{{ route('dashboard') }}"
                            class="fs-13 px-3 py-3 w-100 d-inline-block fw-700 text-dark header_menu_links
                                        {{ areActiveRoutes(['dashboard'], ' active') }}">
                            {{ translate('My Account') }}
                        </a>
                    </li>
                @endif
                @if (isCustomer())
                    <li class="mr-0">
                        <a href="{{ route('customer.all-notifications') }}"
                            class="fs-13 px-3 py-3 w-100 d-inline-block fw-700 text-dark header_menu_links
                                        {{ areActiveRoutes(['customer.all-notifications'], ' active') }}">
                            {{ translate('Notifications') }}
                        </a>
                    </li>
                    <li class="mr-0">
                        <a href="{{ route('wishlists.index') }}"
                            class="fs-13 px-3 py-3 w-100 d-inline-block fw-700 text-dark header_menu_links
                                        {{ areActiveRoutes(['wishlists.index'], ' active') }}">
                            {{ translate('Wishlist') }}
                        </a>
                    </li>
                    <li class="mr-0">
                        <a href="{{ route('compare') }}"
                            class="fs-13 px-3 py-3 w-100 d-inline-block fw-700 text-dark header_menu_links
                                        {{ areActiveRoutes(['compare'], ' active') }}">
                            {{ translate('Compare') }}
                        </a>
                    </li>
                @endif
                <hr>
                <li class="mr-0">
                    <a href="{{ route('logout') }}"
                        class="fs-13 px-3 py-3 w-100 d-inline-block fw-700 text-primary header_menu_links">
                        {{ translate('Logout') }}
                    </a>
                </li>
            @endauth
        </ul>
        <br>
        <br>
    </div>
</div>



<!--show only in home page-->
@if (request()->routeIs('home') or request()->routeIs('cart'))
    <div class="p-2">
        Service Available -
        <span id="service_available">

            @if (session()->has('is_within_radius'))
                {{ session('is_within_radius') ? 'Yes' : 'No' }}
            @else
                <i class="las la-spinner la-spin"></i> Checking...
            @endif

        </span>

        <button onclick="openLocationModal()" class="btn btn-sm btn-outline-primary ml-2">
            Set Location
        </button>
    </div>

     <div class="p-2">
           <span id="shopDetails"></span>
    </div>
@endif

<!-- Modal -->
<div class="modal fade" id="order_details" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
        <div class="modal-content">
            <div id="order-details-modal-body">

            </div>
        </div>
    </div>
</div>

<!-- Location Modal -->
<div class="modal fade" id="locationModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-center p-4">

            <h5 class="mb-2">📍 Location availability </h5>

            <p id="location_message">
                We need your location to check service availability.
            </p>

            <div id="location_modal_actions">

                <button class="btn btn-primary" onclick="requestUserLocation()">
                    Detect My Location
                </button>

                <button class="btn btn-secondary" data-dismiss="modal">
                    Not Now
                </button>

                <button onclick="openAnotherLocationModal()" class="btn btn-sm btn-outline-primary ml-2 mt-2 mt-md-0">
                    Set Location Manually
                </button>

            </div>

        </div>
    </div>
</div>

{{-- take location --}}



<div class="modal fade" id="AnotherlocationModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-center p-4">

            <div class="form-group mt-3 position-relative">

                <h5 class="mb-2"> Set Location Manually</h5>

                <input type="text" id="address_input" class="form-control" placeholder="Type delivery address">


                <div id="address_suggestions" class="list-group position-absolute w-100" style="z-index:9999;"></div>

            </div>


        </div>
    </div>
</div>



<script>
    document.addEventListener("DOMContentLoaded", function() {

        let savedLat = localStorage.getItem("user_lat");
        let savedLng = localStorage.getItem("user_lng");
        let verified = localStorage.getItem("location_verified");
        let cachedStatus = localStorage.getItem("service_status");


        if (savedLat && savedLng && verified === "true") {

            document.getElementById('service_available').textContent =
                cachedStatus ?? "Yes";

            return;
        }


        if (savedLat && savedLng) {
            sendLocation(savedLat, savedLng);
            return;
        }

        // setTimeout(openLocationModal, 800);
    });


    /* ==========================
       OPEN MODAL
    ==========================*/
    function openLocationModal() {
        $('#locationModal').modal('show');
    }

    function openstoreFormModal() {
        $('#storeForm').modal('show');
    }


    function openAnotherLocationModal() {
        $('#locationModal').modal('hide');
        $('#AnotherlocationModal').modal('show');
    }



    function openLocationModal() {
        $('#locationModal').modal('show');
    }

    /* ==========================
       REQUEST USER LOCATION
    ==========================*/
    function requestUserLocation() {

        if (!navigator.geolocation) {
            updateModalMessage("Geolocation not supported ❌");
            return;
        }

        navigator.geolocation.getCurrentPosition(function(position) {

            let latitude = position.coords.latitude;
            let longitude = position.coords.longitude;

            // save locally
            localStorage.setItem("user_lat", latitude);
            localStorage.setItem("user_lng", longitude);

            updateModalMessage("Location Accepted ✅");

            sendLocation(latitude, longitude);

            setTimeout(() => {
                $('#locationModal').modal('hide');
            }, 1200);

        }, function(error) {

            let msg = "";

            switch (error.code) {
                case error.PERMISSION_DENIED:
                    msg = "Location permission denied ❌<br>Enable from browser settings (🔒 icon near URL)";
                    break;

                case error.POSITION_UNAVAILABLE:
                    msg = "Location unavailable. Please enable GPS.";
                    break;

                case error.TIMEOUT:
                    msg = "Location request timed out. Try again.";
                    break;

                default:
                    msg = "Unknown location error.";
            }

            updateModalMessage(msg);
            showRetryButton();

        }, {
            enableHighAccuracy: true,
            timeout: 10000
        });
    }



    function sendLocation(latitude, longitude) {

        if (window.locationSending) return;
        window.locationSending = true;

        fetch('{{ route('store-location') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    latitude: latitude,
                    longitude: longitude
                })
            })
            .then(response => response.json())
            .then(data => {

                const status = data.is_within_radius ? "Yes" : "No";

                document.getElementById('service_available').textContent = status;
                localStorage.setItem("location_verified", "true");
                localStorage.setItem("service_status", status);

                window.locationSending = false;
                document.reload();
            })
            .catch(error => {
                console.error(error);
                window.locationSending = false;
            });
    }


    function updateModalMessage(message) {
        document.getElementById('location_message').innerHTML = message;
    }


    function showRetryButton() {

        document.getElementById('location_modal_actions').innerHTML = `
        <button class="btn btn-success"
            onclick="retryLocation()">
            Try Again
        </button>
    `;
    }


    function retryLocation() {

        localStorage.removeItem("user_lat");
        localStorage.removeItem("user_lng");
        localStorage.removeItem("location_verified");
        localStorage.removeItem("service_status");

        location.reload();
    }
</script>

<script>
    let debounceTimer;

    document.getElementById("address_input")
        .addEventListener("input", function() {

            clearTimeout(debounceTimer);

            let query = this.value;

            if (query.length < 3) {
                document.getElementById("address_suggestions").innerHTML = "";
                return;
            }

            // debounce (important)
            // debounceTimer = setTimeout(() => {

            //     fetch(
            //             `https://nominatim.openstreetmap.org/search?format=json&q=${query}&countrycodes=in&limit=5`)
            //         .then(res => res.json())
            //         .then(data => showSuggestions(data));

            // }, 400);

            fetch(`{{ route('search-gram-panchayat') }}?q=${encodeURIComponent(query)}`)
                .then(res => res.json())
                .then(data => showSuggestions(data));

        });



    // function showSuggestions(places) {

    //     let box = document.getElementById("address_suggestions");
    //     box.innerHTML = "";

    //     places.forEach(place => {

    //         let item = document.createElement("a");
    //         item.className = "list-group-item list-group-item-action";
    //         item.innerText = place.display_name;

    //         item.onclick = function() {

    //             let lat = place.lat;
    //             let lng = place.lon;

    //             document.getElementById("address_input").value =
    //                 place.display_name;

    //             box.innerHTML = "";

    //             // Save locally
    //             localStorage.setItem("user_lat", lat);
    //             localStorage.setItem("user_lng", lng);
    //             localStorage.setItem("user_address", place.display_name);

    //             // Your existing function
    //             sendLocation(lat, lng);

    //             $('#locationModal').modal('hide');
    //             $('#AnotherlocationModal').modal('hide');
    //         };

    //         box.appendChild(item);
    //     });
    // }

    function showSuggestions(places) {
    let box = document.getElementById("address_suggestions");
    box.innerHTML = "";

    places.forEach(place => {
        let item = document.createElement("a");
        item.className = "list-group-item list-group-item-action";
        item.innerText = place.city;

        item.onclick = function() {
            let lat = place.latitude;
            let lng = place.longitude;

            document.getElementById("address_input").value = place.city;
            box.innerHTML = "";

            localStorage.setItem("user_lat", lat);
            localStorage.setItem("user_lng", lng);
            localStorage.setItem("user_address", place.city);

            sendLocation(lat, lng);

            $('#locationModal').modal('hide');
            $('#AnotherlocationModal').modal('hide');
        };

        box.appendChild(item);
    });
}

</script>


@section('script')
    <script type="text/javascript">
        function show_order_details(order_id) {
            $('#order-details-modal-body').html(null);

            if (!$('#modal-size').hasClass('modal-lg')) {
                $('#modal-size').addClass('modal-lg');
            }

            $.post('{{ route('orders.details') }}', {
                _token: AIZ.data.csrf,
                order_id: order_id
            }, function(data) {
                $('#order-details-modal-body').html(data);
                $('#order_details').modal();
                $('.c-preloader').hide();
                AIZ.plugins.bootstrapSelect('refresh');
            });
        }
    </script>
@endsection
