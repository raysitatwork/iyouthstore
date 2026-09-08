@extends('backend.layouts.app')

@section('content')
    @php
        $sellerDob = old('dob');
        if ($sellerDob === null) {
            $sellerDob = $shop->user->dob ? \Illuminate\Support\Carbon::parse($shop->user->dob)->format('Y-m-d') : '';
        }
    @endphp

    <div class="aiz-titlebar text-left mt-2 mb-3">
        <h5 class="mb-0 h6">{{ translate('Edit Seller') }}</h5>
    </div>

    <div class="col-lg-10 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{ translate('Seller Information') }}</h5>
            </div>

            <div class="card-body">
                {{-- <form action="{{ route('sellers.update', $shop->id) }}" method="POST"> --}}
                <form action="{{ route('sellers.update', $shop->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PATCH')
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name">{{ translate('Name') }} <span class="text-danger">*</span></label>
                                <input type="text"
                                    class="form-control @if ($errors->has('name')) is-invalid @endif" name="name"
                                    value="{{ old('name', $shop->user->name) }}" placeholder="{{ translate('Name') }}"
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
                                <label for="email">{{ translate('Email') }} <span class="text-danger">*</span></label>
                                <input type="email"
                                    class="form-control @if ($errors->has('email')) is-invalid @endif"
                                    value="{{ old('email', $shop->user->email) }}" placeholder="{{ translate('Email') }}"
                                    name="email" required>
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
                                <input type="file" class="form-control" name="image" accept="image/*">
                                <small class="text-muted">Image is optional.</small>

                                @if ($shop->user->image)
                                    <div class="mt-2">
                                        <img src="{{ asset('storage/' . $shop->user->image) }}"
                                            class="size-40px img-fit rounded-circle mr-2" alt="User Image"
                                            onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
                                    </div>
                                @endif

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
                                    <option value="Male" @if (old('gender', $shop->user->gender) == 'Male') selected @endif>Male</option>
                                    <option value="Female" @if (old('gender', $shop->user->gender) == 'Female') selected @endif>Female</option>
                                    <option value="Other" @if (old('gender', $shop->user->gender) == 'Other') selected @endif>Other</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ translate('Father / Husband Name') }}</label>
                                <input type="text" class="form-control" name="father_husband_name"
                                    value="{{ old('father_husband_name', $shop->user->father_husband_name) }}"
                                    placeholder="Father / Husband Name">
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group">
                                <label>{{ translate('DOB') }}</label>
                                <input type="date" class="form-control" name="dob" value="{{ $sellerDob }}">
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group">
                                <label>{{ translate('Age') }}</label>
                                <input type="text" class="form-control" name="age"
                                    value="{{ old('age', $shop->user->age) }}" placeholder="Age">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ translate('Aadhaar') }}</label>
                                <input type="text" class="form-control" name="aadhaar"
                                    value="{{ old('aadhaar', $shop->user->aadhaar) }}" placeholder="Aadhaar">
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ translate('PAN') }}</label>
                                <input type="text" class="form-control" name="pan"
                                    value="{{ old('pan', $shop->user->pan) }}" placeholder="PAN">
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ translate('Qualification') }}</label>
                                <input type="text" class="form-control" name="qualification"
                                    value="{{ old('qualification', $shop->user->qualification) }}"
                                    placeholder="Qualification">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>{{ translate('Address') }}</label>
                        <textarea class="form-control" name="address" rows="2" placeholder="Address">{{ old('address', $shop->user->address) }}</textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Select State <span class="text-danger">*</span></label>
                                <select class="form-control state-select" name="state" required>
                                    <option value="">Select State</option>
                                    <option value="chhattisgarh" @if (old('state', $shop->user->state) == 'chhattisgarh') selected @endif>
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
                                            @if (old('district_id', $shop->user->district) == $district->id) selected @endif>
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
                                        <option value="{{ $block->id }}" data-district="{{ $block->district_id }}"
                                            @if (old('block_id', $shop->user->block) == $block->id) selected @endif>
                                            {{ $block->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Select SubDistrict *</label>
                                <select class="form-control subdistrict-select" name="sub_district_id" required>
                                    <option value="">Select SubDistrict</option>
                                    @foreach ($subDistricts as $sub)
                                        <option value="{{ $sub->id }}" data-block="{{ $sub->block_id }}"
                                            @if (old('sub_district_id', $shop->user->sub_district) == $sub->id) selected @endif>
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
                                    value="{{ old('city', $shop->user->city) }}" placeholder="Enter City/Village"
                                    required>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ translate('Postal Code') }}</label>
                                <input type="text" class="form-control" name="postal_code"
                                    value="{{ old('postal_code', $shop->user->postal_code) }}" placeholder="Postal Code">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ translate('Phone') }}</label>
                                <input type="text" class="form-control" name="phone"
                                    value="{{ old('phone', $shop->user->phone) }}" placeholder="Phone">
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ translate('Alternate Phone') }}</label>
                                <input type="text" class="form-control" name="alternate_phone"
                                    value="{{ old('alternate_phone', $shop->user->alternate_phone) }}"
                                    placeholder="Alternate Phone">
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ translate('WhatsApp Number') }}</label>
                                <input type="text" class="form-control" name="whatsapp_number"
                                    value="{{ old('whatsapp_number', $shop->user->whatsapp_number) }}"
                                    placeholder="WhatsApp Number">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>{{ translate('Experience') }}</label>
                        <input type="text" class="form-control" name="experience"
                            value="{{ old('experience', $shop->user->experience) }}" placeholder="Experience">
                    </div>

                    <hr>
                    <h6 class="mb-3">{{ translate('Shop Information') }}</h6>

                    <div class="form-group">
                        <label>{{ translate('Shop Address') }}</label>
                        <textarea class="form-control" name="shop_address" rows="2" placeholder="Shop Address">{{ old('shop_address', $shop->address) }}</textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>{{ translate('Shop Size') }}</label>
                                <input type="text" class="form-control" name="shop_size"
                                    value="{{ old('shop_size', $shop->shop_size) }}" placeholder="Shop Size">
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label>{{ translate('Own / Rented') }}</label>
                                <input type="text" class="form-control" name="rent_type"
                                    value="{{ old('rent_type', $shop->rent_type) }}" placeholder="Own / Rented">
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label>{{ translate('Monthly Rent') }}</label>
                                <input type="text" class="form-control" name="monthly_rent"
                                    value="{{ old('monthly_rent', $shop->monthly_rent) }}" placeholder="Monthly Rent">
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label>{{ translate('Security Deposit') }}</label>
                                <input type="text" class="form-control" name="security_deposit"
                                    value="{{ old('security_deposit', $shop->security_deposit) }}"
                                    placeholder="Security Deposit">
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
                                    value="{{ old('bank_acc_no', $shop->bank_acc_no) }}"
                                    placeholder="Bank Account Number">
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label>{{ translate('Bank Name') }}</label>
                                <input type="text" class="form-control" name="bank_name"
                                    value="{{ old('bank_name', $shop->bank_name) }}" placeholder="Bank Name">
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label>{{ translate('Branch Name') }}</label>
                                <input type="text" class="form-control" name="bank_acc_name"
                                    value="{{ old('bank_acc_name', $shop->bank_acc_name) }}" placeholder="Branch Name">
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label>{{ translate('IFSC / Routing') }}</label>
                                <input type="text" class="form-control" name="bank_routing_no"
                                    value="{{ old('bank_routing_no', $shop->bank_routing_no) }}"
                                    placeholder="IFSC / Routing">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ translate('Payment Status') }}</label>
                                <input type="text" class="form-control" name="payment_status"
                                    value="{{ old('payment_status', $shop->payment_status) }}"
                                    placeholder="Paid / Unpaid">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ translate('Payment Mode') }}</label>
                                <input type="text" class="form-control" name="payment_mode"
                                    value="{{ old('payment_mode', $shop->payment_mode) }}"
                                    placeholder="Cash / UTR / Online">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Password</label>
                                <input type="password"
                                    class="form-control @if ($errors->has('password')) is-invalid @endif"
                                    name="password" placeholder="Enter New Password">
                            </div>
                            <small class="text-muted">
                                Leave blank to keep the current password
                            </small>
                            @if ($errors->has('password'))
                                <span class="invalid-feedback d-block" role="alert">
                                    <strong>{{ $errors->first('password') }}</strong>
                                </span>
                            @endif
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Confirm Password</label>
                                <input type="password" class="form-control" name="password_confirmation"
                                    placeholder="Confirm New Password">
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-0 text-right">
                        <button type="submit" class="btn btn-primary">{{ translate('Update Seller') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script>
        $(document).ready(function() {

            let selectedDistrict = $('.district-select').val();
            let selectedBlock = $('.block-select').val();
            let selectedSub = $('.subdistrict-select').val();


            $('.block-select option').hide();
            $('.block-select option:first').show();

            $('.block-select option').each(function() {
                if ($(this).data('district') == selectedDistrict) {
                    $(this).show();
                }
            });

            $('.block-select').val(selectedBlock);

            $('.subdistrict-select option').hide();
            $('.subdistrict-select option:first').show();

            $('.subdistrict-select option').each(function() {
                if ($(this).data('block') == selectedBlock) {
                    $(this).show();
                }
            });

            $('.subdistrict-select').val(selectedSub);


            $('.district-select').on('change', function() {

                let district = $(this).val();

                $('.block-select').val('');
                $('.subdistrict-select').val('');

                $('.block-select option').hide();
                $('.block-select option:first').show();

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
