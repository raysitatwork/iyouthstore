@extends('backend.layouts.app')

@section('content')
    @if (env('MAIL_USERNAME') == null && env('MAIL_PASSWORD') == null)
        <div class="alert alert-info d-flex align-items-center">
            {{ translate('You need to configure SMTP correctly to to add Seller.') }}
            <a class="alert-link ml-2" href="{{ route('smtp_settings.index') }}">{{ translate('Configure Now') }}</a>
        </div>
    @endif

    <div class="aiz-titlebar text-left mt-2 mb-3">
        <h5 class="mb-0 h6">{{ translate('Add New Seller') }}</h5>
        <div class="col-lg-10 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 h6">{{ translate('Seller Information') }}</h5>
                </div>
                <div class="card-body">
                    {{-- <form action="{{ route('sellers.store') }}" method="POST"> --}}
                    <form action="{{ route('sellers.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">{{ translate('Name') }} <span class="text-danger">*</span></label>
                                    <input type="text"
                                        class="form-control @if ($errors->has('name')) is-invalid @endif"
                                        name="name" value="{{ old('name') }}" placeholder="{{ translate('Name') }}"
                                        required>
                                    @if ($errors->has('name'))
                                        <span class="invalid-feedback d-block" role="alert">
                                            <strong>{{ $errors->first('name') }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email">{{ translate('Email') }} <span
                                            class="text-danger">*</span></label>
                                    <input type="email"
                                        class="form-control @if ($errors->has('email')) is-invalid @endif"
                                        value="{{ old('email') }}" placeholder="{{ translate('Email') }}" name="email"
                                        required>
                                    @if ($errors->has('email'))
                                        <span class="invalid-feedback d-block" role="alert">
                                            <strong>{{ $errors->first('email') }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>


<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label>{{ translate('Seller Image') }}</label>
            <input type="file"
                class="form-control @if ($errors->has('image')) is-invalid @endif"
                name="image"
                accept="image/jpg,image/jpeg,image/png,image/webp">

            <small class="text-muted">
                Image is optional. JPG, JPEG, PNG, WEBP allowed.
            </small>

            @if ($errors->has('image'))
                <span class="invalid-feedback d-block">
                    <strong>{{ $errors->first('image') }}</strong>
                </span>
            @endif
        </div>
    </div>
</div>


                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{ translate('Gender') }}</label>
                                    <select class="form-control" name="gender">
                                        <option value="">Select Gender</option>
                                        <option value="Male" @if (old('gender') == 'Male') selected @endif>Male
                                        </option>
                                        <option value="Female" @if (old('gender') == 'Female') selected @endif>Female
                                        </option>
                                        <option value="Other" @if (old('gender') == 'Other') selected @endif>Other
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{ translate('Father / Husband Name') }}</label>
                                    <input type="text" class="form-control" name="father_husband_name"
                                        value="{{ old('father_husband_name') }}" placeholder="Father / Husband Name">
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>{{ translate('DOB') }}</label>
                                    <input type="date" class="form-control" name="dob" value="{{ old('dob') }}">
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>{{ translate('Age') }}</label>
                                    <input type="text" class="form-control" name="age" value="{{ old('age') }}"
                                        placeholder="Age">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{ translate('Aadhaar') }}</label>
                                    <input type="text" class="form-control" name="aadhaar" value="{{ old('aadhaar') }}"
                                        placeholder="Aadhaar">
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{ translate('PAN') }}</label>
                                    <input type="text" class="form-control" name="pan" value="{{ old('pan') }}"
                                        placeholder="PAN">
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{ translate('Qualification') }}</label>
                                    <input type="text" class="form-control" name="qualification"
                                        value="{{ old('qualification') }}" placeholder="Qualification">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>{{ translate('Address') }}</label>
                            <textarea class="form-control" name="address" rows="2" placeholder="Address">{{ old('address') }}</textarea>
                        </div>


                        {{-- State --}}
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Select State <span class="text-danger">*</span></label>
                                    <select class="form-control state-select" name="state" required>
                                        <option value="">Select State</option>
                                        <option value="chhattisgarh" @if (old('state', 'chhattisgarh') == 'chhattisgarh') selected @endif>
                                            Chhattisgarh
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Select District *</label>
                                    <select class="form-control district-select" name="district_id" required>
                                        <option value="">Select District</option>
                                        @foreach ($districts as $district)
                                            <option value="{{ $district->id }}"
                                                @if (old('district_id') == $district->id) selected @endif>
                                                {{ $district->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Select Block *</label>
                                    <select class="form-control block-select" name="block_id" required>
                                        <option value="">Select Block</option>
                                        @foreach ($blocks as $block)
                                            <option value="{{ $block->id }}"
                                                data-district="{{ $block->district_id }}"
                                                @if (old('block_id') == $block->id) selected @endif>
                                                {{ $block->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>


                        {{-- SubDistrict --}}
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Select SubDistrict *</label>
                                    <select class="form-control subdistrict-select" name="sub_district_id" required>
                                        <option value="">Select SubDistrict</option>
                                        @foreach ($subDistricts as $sub)
                                            <option value="{{ $sub->id }}" data-block="{{ $sub->block_id }}"
                                                @if (old('sub_district_id') == $sub->id) selected @endif>
                                                {{ $sub->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>City / Village *</label>
                                    <input type="text" class="form-control" name="city"
                                        value="{{ old('city') }}" placeholder="Enter City/Village" required>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{ translate('Postal Code') }}</label>
                                    <input type="text" class="form-control" name="postal_code"
                                        value="{{ old('postal_code') }}" placeholder="Postal Code">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{ translate('Phone') }}</label>
                                    <input type="text" class="form-control" name="phone"
                                        value="{{ old('phone') }}" placeholder="Phone">
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{ translate('Alternate Phone') }}</label>
                                    <input type="text" class="form-control" name="alternate_phone"
                                        value="{{ old('alternate_phone') }}" placeholder="Alternate Phone">
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{ translate('WhatsApp Number') }}</label>
                                    <input type="text" class="form-control" name="whatsapp_number"
                                        value="{{ old('whatsapp_number') }}" placeholder="WhatsApp Number">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>{{ translate('Experience') }}</label>
                            <input type="text" class="form-control" name="experience"
                                value="{{ old('experience') }}" placeholder="Experience">
                        </div>

                        <hr>
                        <h6 class="mb-3">{{ translate('Shop Information') }}</h6>

                        <div class="form-group">
                            <label>{{ translate('Shop Address') }}</label>
                            <textarea class="form-control" name="shop_address" rows="2" placeholder="Shop Address">{{ old('shop_address') }}</textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>{{ translate('Shop Size') }}</label>
                                    <input type="text" class="form-control" name="shop_size"
                                        value="{{ old('shop_size') }}" placeholder="Shop Size">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>{{ translate('Own / Rented') }}</label>
                                    <input type="text" class="form-control" name="rent_type"
                                        value="{{ old('rent_type') }}" placeholder="Own / Rented">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>{{ translate('Monthly Rent') }}</label>
                                    <input type="text" class="form-control" name="monthly_rent"
                                        value="{{ old('monthly_rent') }}" placeholder="Monthly Rent">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>{{ translate('Security Deposit') }}</label>
                                    <input type="text" class="form-control" name="security_deposit"
                                        value="{{ old('security_deposit') }}" placeholder="Security Deposit">
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h6 class="mb-3">{{ translate('Bank Information') }}</h6>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>{{ translate('Bank Account Number') }}</label>
                                    <input type="text" class="form-control" name="bank_acc_no"
                                        value="{{ old('bank_acc_no') }}" placeholder="Bank Account Number">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>{{ translate('Bank Name') }}</label>
                                    <input type="text" class="form-control" name="bank_name"
                                        value="{{ old('bank_name') }}" placeholder="Bank Name">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>{{ translate('Branch Name') }}</label>
                                    <input type="text" class="form-control" name="bank_acc_name"
                                        value="{{ old('bank_acc_name') }}" placeholder="Branch Name">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>{{ translate('IFSC / Routing') }}</label>
                                    <input type="text" class="form-control" name="bank_routing_no"
                                        value="{{ old('bank_routing_no') }}" placeholder="IFSC / Routing">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>{{ translate('Payment Status') }}</label>
                                    <input type="text" class="form-control" name="payment_status"
                                        value="{{ old('payment_status') }}" placeholder="Paid / Unpaid">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>{{ translate('Payment Mode') }}</label>
                                    <input type="text" class="form-control" name="payment_mode"
                                        value="{{ old('payment_mode') }}" placeholder="Cash / UTR / Online">
                                </div>
                            </div>
                        </div>

                        {{-- Password --}}
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Password <span class="text-danger">*</span></label>
                                    <input type="password"
                                        class="form-control @if ($errors->has('password')) is-invalid @endif"
                                        name="password" placeholder="Enter Password" required>
                                </div>
                                <small class="text-muted">
                                    Password must contain at least 6 characters
                                </small>
                                @if ($errors->has('password'))
                                    <span class="invalid-feedback d-block" role="alert">
                                        <strong>{{ $errors->first('password') }}</strong>
                                    </span>
                                @endif
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Confirm Password *</label>
                                    <input type="password" class="form-control" name="password_confirmation" required>
                                </div>
                            </div>


                            {{-- <div class="form-group row">
                                    <label class="col-sm-2 col-from-label" for="shop_name">{{ translate('Shop Name') }}</label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control rounded-0 @if ($errors->has('shop_name')) is-invalid @endif" value="{{ old('shop_name') }}" placeholder="{{  translate('Shop Name') }}" name="shop_name">
                                        @if ($errors->has('shop_name'))
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $errors->first('shop_name') }}</strong>
                                            </span>
                                        @endif
                                    </div>
                                </div> --}}
                            {{-- <div class="form-group row">
                                    <label class="col-sm-2 col-from-label" for="address">{{ translate('Address') }}</label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control rounded-0 @if ($errors->has('address')) is-invalid @endif" value="{{ old('address') }}" placeholder="{{  translate('Address') }}" name="address">
                                        @if ($errors->has('address'))
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $errors->first('address') }}</strong>
                                            </span>
                                        @endif
                                    </div>
                                </div> --}}
                            <div class="form-group mb-0 text-right">
                                <button type="submit" class="btn btn-primary">{{ translate('Save') }}</button>
                            </div>
                    </form>
                </div>
            </div>
        </div>
    @endsection



    @section('script')
        <script>
            $(document).ready(function() {


                $('.block-select option').hide();
                $('.block-select option:first').show();


                $('.subdistrict-select option').hide();
                $('.subdistrict-select option:first').show();


                $('.district-select').on('change', function() {

                    let district = $(this).val();

                    $('.block-select').val('');
                    $('.subdistrict-select').val('');

                    $('.block-select option').hide();
                    $('.block-select option:first').show();

                    $('.subdistrict-select option').hide();
                    $('.subdistrict-select option:first').show();

                    $('.block-select option').each(function() {
                        if ($(this).data('district') == district) {
                            $(this).show();
                        }
                    });

                });


                $('.block-select').on('change', function() {

                    let block = $(this).val();

                    $('.subdistrict-select').val('');

                    $('.subdistrict-select option').hide();
                    $('.subdistrict-select option:first').show();

                    $('.subdistrict-select option').each(function() {
                        if ($(this).data('block') == block) {
                            $(this).show();
                        }
                    });

                });

            });
        </script>
    @endsection
