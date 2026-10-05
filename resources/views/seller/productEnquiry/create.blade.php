@extends('seller.layouts.app')

@section('panel_content')
    <div class="aiz-titlebar mt-2 mb-4">

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif


        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="h3">{{ translate('Product Enquiry') }}</h1>
            </div>
        </div>
    </div>
  
    <form id="profileUpdateForm" action="{{ route('seller.productEnquiry.store') }}" method="POST"
        enctype="multipart/form-data">
        @csrf
        <!-- Basic Info-->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{ translate('If you are looking for any product, please fill out this form with your requirements.') }}</h5>
            </div>
            <div class="card-body">
                <div class="form-group row">
                    <label class="col-md-2 col-form-label" for="product">{{ translate('Product Name') }}</label>
                    <div class="col-md-10">
                        <input type="text" name="product" class="form-control"
                            placeholder="{{ translate('Product Name') }}" required>
                        @error('product')
                            <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-md-2 col-form-label" for="stock">{{ translate('Product Stock') }}</label>
                    <div class="col-md-10">
                        <input type="text" name="stock" id="stock"
                            class="form-control" required placeholder="{{ translate('Stock') }}">
                        @error('stock')
                            <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-md-2 col-form-label">{{ translate('Photo') }}</label>
                    <div class="col-md-10">
                        <div class="input-group" data-toggle="aizuploader" data-type="image">
                            <div class="input-group-prepend">
                                <div class="input-group-text bg-soft-secondary font-weight-medium">
                                    {{ translate('Browse') }}</div>
                            </div>
                            <div class="form-control file-amount">{{ translate('Choose File') }}</div>
                            <input type="hidden" name="photo" value="{{ $user->avatar_original }}"
                                class="selected-files">
                        </div>
                        <div class="file-preview box sm">
                        </div>
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-md-2 col-form-label" for="price">{{ translate('Product Price') }}</label>
                    <div class="col-md-10">
                        <input type="text" name="price" id="price" class="form-control" required
                            placeholder="{{ translate('Price [100-200]') }}">
                        @error('price')
                            <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-md-2 col-form-label"
                        for="remark">{{ translate('Remark/Description') }}</label>
                    <div class="col-md-10">
                    
                            <textarea class="form-control" name="remark" id="">

                            </textarea>

                
                    </div>
                </div>

            </div>
        </div>

        <!-- Payment System -->
        {{-- <div class="card">
        <div class="card-header">
            <h5 class="mb-0 h6">{{ translate('Payment Setting')}}</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <label class="col-md-3 col-form-label">{{ translate('Cash Payment') }}</label>
                <div class="col-md-9">
                    <label class="aiz-switch aiz-switch-success mb-3">
                        <input value="1" name="cash_on_delivery_status" type="checkbox" @if ($user->shop->cash_on_delivery_status == 1) checked @endif>
                        <span class="slider round"></span>
                    </label>
                </div>
            </div>
            <div class="row">
                <label class="col-md-3 col-form-label">{{ translate('Bank Payment') }}</label>
                <div class="col-md-9">
                    <label class="aiz-switch aiz-switch-success mb-3">
                        <input value="1" name="bank_payment_status" type="checkbox" @if ($user->shop->bank_payment_status == 1) checked @endif>
                        <span class="slider round"></span>
                    </label>
                </div>
            </div>
            <div class="row">
                <label class="col-md-3 col-form-label" for="bank_name">{{ translate('Bank Name') }}</label>
                <div class="col-md-9">
                    <input type="text" name="bank_name" value="{{ $user->shop->bank_name }}" id="bank_name" class="form-control mb-3" placeholder="{{ translate('Bank Name')}}">
                    @error('bank_name')
                    <small class="form-text text-danger">{{ $message }}</small>
                    @enderror
                </div>
            </div>
            <div class="row">
                <label class="col-md-3 col-form-label" for="bank_acc_name">{{ translate('Bank Account Name') }}</label>
                <div class="col-md-9">
                    <input type="text" name="bank_acc_name" value="{{ $user->shop->bank_acc_name }}" id="bank_acc_name" class="form-control mb-3" placeholder="{{ translate('Bank Account Name')}}">
                    @error('bank_acc_name')
                    <small class="form-text text-danger">{{ $message }}</small>
                    @enderror
                </div>
            </div>
            <div class="row">
                <label class="col-md-3 col-form-label" for="bank_acc_no">{{ translate('Bank Account Number') }}</label>
                <div class="col-md-9">
                    <input type="text" name="bank_acc_no" value="{{ $user->shop->bank_acc_no }}" id="bank_acc_no" class="form-control mb-3" placeholder="{{ translate('Bank Account Number')}}">
                    @error('bank_acc_no')
                    <small class="form-text text-danger">{{ $message }}</small>
                    @enderror
                </div>
            </div>
            <div class="row">
                <label class="col-md-3 col-form-label" for="bank_routing_no">{{ translate('Bank Routing Number') }}</label>
                <div class="col-md-9">
                    <input type="number" name="bank_routing_no" value="{{ $user->shop->bank_routing_no }}" id="bank_routing_no" lang="en" class="form-control mb-3" placeholder="{{ translate('Bank Routing Number')}}">
                    @error('bank_routing_no')
                    <small class="form-text text-danger">{{ $message }}</small>
                    @enderror
                </div>
            </div>
        </div>
    </div> --}}

        <div class="form-group mb-0 text-right">
            <button type="submit" class="btn btn-primary">{{ translate('Save') }}</button>
        </div>
    </form>

    <br>

@endsection

