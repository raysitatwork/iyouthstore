@extends('frontend.layouts.user_panel')

@section('panel_content')
<div class="aiz-titlebar mb-4">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="fs-20 fw-700 text-dark">{{ translate('Manage Profile') }}</h1>
        </div>
    </div>
</div>

<!-- Basic Info-->
<div class="card rounded-0 shadow-none border">
    <div class="card-header pt-4 border-bottom-0">
        <h5 class="mb-0 fs-18 fw-700 text-dark">{{ translate('Basic Info')}}</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('user.profile.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <!-- Name-->
            <div class="form-group row">
                <label class="col-md-2 col-form-label fs-14 fs-14">{{ translate('Your Name') }}<span class="text-danger">*</span></label>
                <div class="col-md-10">
                    <input type="text" class="form-control rounded-0" placeholder="{{ translate('Your Name') }}" required name="name" value="{{ Auth::user()->name }}">
                </div>
            </div>
            <!-- Phone-->
            <div class="form-group row">
                <label class="col-md-2 col-form-label fs-14">{{ translate('Your Phone') }}<span class="text-danger">*</span></label>
                <div class="col-md-10">
                    <input type="text" maxlength="10" minlength="10"  required  class="form-control rounded-0" placeholder="{{ translate('Your Phone')}}" name="phone" value="{{ Auth::user()->phone }}">
                </div>
            </div>
          
            
            <div class="form-group row">
    <label class="col-md-2 col-form-label">
        {{ translate('Photo') }}
    </label>

    <div class="col-md-10">
        <input
            type="file"
            name="photo"
            class="form-control"
            accept="image/jpeg,image/png,image/jpg,image/webp"
            multiple
        >

      <div>
                          <small class="text-muted">
                            {{ translate('You can upload JPG, JPEG, PNG or WEBP images.') }}
                        </small>
                      </div>

                      <div>
                        <p>
                            Current Photo
                        </p>
                    
                        @if (Auth::user()->avatar_original != null)
                            <img src="{{ asset('public/' . Auth::user()->avatar_original) }}" height="80" width="80"
                                onerror="this.onerror=null;this.src='{{ static_asset('assets/img/avatar-place.png') }}';">
                        @endif
                          </div>
    </div>
</div>
            
       
<!-- Password -->
<div class="form-group row">
    <label class="col-md-2 col-form-label fs-14">
        {{ translate('Your Password') }}
    </label>
    <div class="col-md-10">
        <input
            type="password"
            class="form-control rounded-0"
            placeholder="{{ translate('New Password') }}"
            name="new_password"
            id="new_password" minlength="6"
        >
        <small class="text-danger d-none" id="password_error">
            Password is required.
        </small>
    </div>
</div>

<!-- Confirm Password -->
<div class="form-group row">
    <label class="col-md-2 col-form-label fs-14">
        {{ translate('Confirm Password') }}
    </label>
    <div class="col-md-10">
        <input
            type="password"
            class="form-control rounded-0"
            placeholder="{{ translate('Confirm Password') }}"
            name="confirm_password"
            id="confirm_password" minlength="6"
        >
        <small class="text-danger d-none" id="confirm_password_error">
            Passwords do not match.
        </small>
    </div>
</div>

            <!-- Submit Button-->
            <div class="form-group mb-0 text-right">
                <button type="submit" class="btn btn-primary rounded-0 w-150px mt-3">{{translate('Update Profile')}}</button>
            </div>
        </form>
    </div>
</div>

<!-- Address -->
<div class="card rounded-0 shadow-none border">
    <div class="card-header pt-4 border-bottom-0">
        <h5 class="mb-0 fs-18 fw-700 text-dark">{{ translate('Address')}}</h5>
    </div>
    <div class="card-body">
        @foreach (Auth::user()->addresses as $key => $address)
        <div class="">
            <div class="border p-4 mb-4 position-relative">
                <div class="row fs-14 mb-2 mb-md-0">
                    <span class="col-md-2 text-secondary">{{ translate('Address') }}:</span>
                    <span class="col-md-8 text-dark">{{ $address->address }}</span>
                </div>
                <div class="row fs-14 mb-2 mb-md-0">
                    <span class="col-md-2 text-secondary">{{ translate('Postal Code') }}:</span>
                    <span class="col-md-10 text-dark">{{ $address->postal_code }}</span>
                </div>
                @if ($address->area)
                <div class="row fs-14 mb-2 mb-md-0">
                    <span class="col-md-2 text-secondary">{{ translate('Area') }}</span>
                    <span class="col-md-10 text-dark">{{ optional($address->area)->name }}</span>
                </div>
                @endif
                <div class="row fs-14 mb-2 mb-md-0">
                    <span class="col-md-2 text-secondary">{{ translate('City') }}:</span>
                    <span class="col-md-10 text-dark">{{ optional($address->city)->name }}</span>
                </div>
                @if (get_setting('has_state') == 1)
                <div class="row fs-14 mb-2 mb-md-0">
                    <span class="col-md-2 text-secondary">{{ translate('State') }}:</span>
                    <span class="col-md-10 text-dark">{{ optional($address->state)->name }}</span>
                </div>
                @endif
                <div class="row fs-14 mb-2 mb-md-0">
                    <span class="col-md-2 text-secondary">{{ translate('Country') }}:</span>
                    <span class="col-md-10 text-dark">{{ optional($address->country)->name }}</span>
                </div>
                <div class="row fs-14 mb-2 mb-md-0">
                    <span class="col-md-2 text-secondary text-secondary">{{ translate('Phone') }}:</span>
                    <span class="col-md-10 text-dark">{{ $address->phone }}</span>
                </div>
                @if ($address->set_default)
                <div class="absolute-md-top-right pt-2 pt-md-4 pr-md-5">
                    <span class="badge badge-inline badge-secondary-base text-white p-3 fs-12" style="border-radius: 25px; min-width: 80px !important;">{{ translate('default') }}</span>
                </div>
                @endif
                <div class="dropdown position-absolute right-0 top-0 pt-4 mr-1">
                    <button class="btn bg-gray text-white px-1 py-1" type="button" data-toggle="dropdown">
                        <i class="la la-ellipsis-v"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuButton">
                        <a class="dropdown-item" onclick="edit_address('{{$address->id}}')">
                            {{ translate('Edit') }}
                        </a>
                        @if (!$address->set_default)
                        <a class="dropdown-item" href="{{ route('addresses.set_default', $address->id) }}">{{ translate('Make This Default') }}</a>
                        @endif
                        <a class="dropdown-item" href="{{ route('addresses.destroy', $address->id) }}">{{ translate('Delete') }}</a>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
        <!-- Add New Address -->
        <div class="" onclick="add_new_address()">
            <div class="border p-3 mb-3 c-pointer text-center bg-light has-transition hov-bg-soft-light">
                <i class="la la-plus la-2x"></i>
                <div class="alpha-7 fs-14 fw-700">{{ translate('Add New Address') }}</div>
            </div>
        </div>
    </div>
</div>


<!-- Change Email -->
<form action="{{ route('user.change.email') }}" method="POST">
    @csrf
    <div class="card rounded-0 shadow-none border">
        <div class="card-header pt-4 border-bottom-0">
            <h5 class="mb-0 fs-18 fw-700 text-dark">{{ translate('Change your email')}}</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-2">
                    <label class="fs-14">{{ translate('Your New Email') }}</label>
                </div>
                <div class="col-md-10">
                    <div class="input-group mb-3">
                        <input type="email" class="form-control rounded-0" placeholder="{{ translate('Your Email')}}" name="email" value="{{ Auth::user()->email }}" />
                        <div class="input-group-append">
                            <button type="button" class="btn btn-outline-secondary new-email-verification">
                                <span class="d-none loading">
                                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>{{ translate('Sending Email...') }}
                                </span>
                                <span class="default">{{ translate('Verify') }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row update-div">
                <div class="col-md-2">
                    <label class="fs-14">{{ translate('Verification Code') }}</label>
                </div>
                <div class="col-md-10">
                    <div class="input-group mb-3">
                        <input type="text" class="form-control rounded-0" placeholder="{{ translate('Enter Your Verification Code')}}" name="code" value="" disabled />
                        <div class="input-group-append">
                           
                        </div>
                    </div>
                    <div class="form-group mb-0 text-right">
                        <button type="submit" class="btn btn-primary rounded-0 w-150px mt-3" disabled >{{translate('Update Email')}}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

@endsection

@section('modal')
<!-- Address modal -->
@include('frontend.partials.address.address_modal')
@endsection

@section('script')
@include('frontend.partials.address.address_js')

<script type="text/javascript">
    $('.new-email-verification').on('click', function() {
        $(this).find('.loading').removeClass('d-none');
        $(this).find('.default').addClass('d-none');
        var email = $("input[name=email]").val();

        $.post('{{ route('user.email.update.verify.code') }}', {
                _token: '{{ csrf_token() }}',
                email: email
            },
            function(data) {
                data = JSON.parse(data);
                $('.default').removeClass('d-none');
                $('.loading').addClass('d-none');
                if (data.status == 2){
                    AIZ.plugins.notify('warning', data.message);
                }
                else if (data.status == 1){
                    AIZ.plugins.notify('success', data.message);
                    $('input[name="code"]').prop('disabled', false);
                    $('button[type="submit"]').prop('disabled', false);
                }
                else{
                    AIZ.plugins.notify('danger', data.message);
                }
            });
    });

    $(document).ready(function() {
        @if(get_setting('has_state') == 1)
            get_states(@json(get_active_countries()[0]->id));
        @else
            get_city_by_country(@json(get_active_countries()[0]->id));
        @endif
    });
    

$(document).ready(function () {

    const newPassword = $('#new_password');
    const confirmPassword = $('#confirm_password');
    const passwordError = $('#password_error');
    const confirmPasswordError = $('#confirm_password_error');

    // Check password match
    function validatePassword() {

        let password = newPassword.val();
        let confirmPasswordValue = confirmPassword.val();

        // If both are empty, password update is not required
        if (password === '' && confirmPasswordValue === '') {
            newPassword.removeClass('is-invalid');
            confirmPassword.removeClass('is-invalid');

            passwordError.addClass('d-none');
            confirmPasswordError.addClass('d-none');

            return true;
        }

        // If new password is entered but confirm password is empty
        if (password !== '' && confirmPasswordValue === '') {
            confirmPassword.addClass('is-invalid');
            confirmPasswordError
                .removeClass('d-none')
                .text('Please confirm your password.');

            return false;
        }

        // If passwords don't match
        if (password !== confirmPasswordValue) {
            confirmPassword.addClass('is-invalid');

            confirmPasswordError
                .removeClass('d-none')
                .text('Passwords do not match.');

            return false;
        }

        // Passwords match
        newPassword.removeClass('is-invalid');
        confirmPassword.removeClass('is-invalid');

        passwordError.addClass('d-none');
        confirmPasswordError.addClass('d-none');

        return true;
    }

    // Validate while typing
    newPassword.on('input', validatePassword);
    confirmPassword.on('input', validatePassword);

    // Validate before form submit
    $('form[action="{{ route('user.profile.update') }}"]').on('submit', function (e) {

        if (!validatePassword()) {
            e.preventDefault();

            confirmPassword.focus();
        }

    });

});
    
</script>

@if (get_setting('google_map') == 1)
@include('frontend.partials.google_map')
@endif

@endsection