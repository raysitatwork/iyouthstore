<div class="row">



    {{-- RIGHT COLUMN --}}
    <div class="col-lg-7 d-flex flex-column gap-3">

        {{-- Verification Status --}}
        @if($shop->verification_status == 1 && $shop->verification_info)
            <div class="rounded-2 p-4 bg-color mb-3">
                <div class="d-flex align-items-center">
                    <div>{{ translate('Verification Status') }}</div>
                    <p class="font-weight-bold ml-3 mb-0">{{ translate('Verified') }}</p>

                    <a href="javascript:void();"
                       onclick="show_seller_verification_info('{{ $shop->id }}');"
                       class="ml-auto text-muted fs-12">
                       {{ translate('View Submitted Form') }}
                    </a>
                </div>
            </div>

        @elseif($shop->verification_status == 1)

            <div class="rounded-2 p-4 bg-color mb-3">
                <div class="d-flex align-items-center">
                    <div>{{ translate('Verification Status') }}</div>
                    <p class="font-weight-bold ml-3 mb-0">{{ translate('Verified') }}</p>
                    <span class="ml-auto text-muted fs-12">{{ translate('By Admin') }}</span>
                </div>
            </div>

        @else

            <div class="rounded-2 p-4 bg-color3 mb-3">
                <div class="d-flex align-items-center">
                    <div>{{ translate('Verification Status') }}</div>
                    <p class="font-weight-bold ml-3 mb-0">{{ translate('Not Applied') }}</p>
                </div>
            </div>

        @endif


        {{-- Seller Info --}}
        <div class="card rounded-2 border-color card-no-shadow">
            <div class="card-body p-0">

                <div class="border-bottom p-3 font-weight-bold">
                    {{ translate('Seller Info') }}
                </div>

                <div class="p-3 fs-13">

                    <div class="d-flex py-2 border-bottom-dashed2">
                        <div class="w-210px">{{ translate('Name') }}</div>
                        <div>{{ $shop->user->name }}</div>
                    </div>

                    <div class="d-flex py-2 border-bottom-dashed2">
                        <div class="w-210px">{{ translate('Email') }}</div>
                        <div>{{ $shop->user->email }}</div>
                    </div>

                    <div class="d-flex py-2 border-bottom-dashed2">
                        <div class="w-210px">{{ translate('Phone Number') }}</div>
                        <div>{{ $shop->user->phone ?? 'N/A' }}</div>
                    </div>

                    <div class="d-flex py-2 border-bottom-dashed2">
                        <div class="w-210px">{{ translate('Account Creation') }}</div>
                        <div>{{ optional($shop->created_at)->format('d F, Y') }}</div>
                    </div>

                    <div class="d-flex py-2 border-bottom-dashed2">
                        <div class="w-210px">{{ translate('Last Login Date') }}</div>
                        <div>
                            {{ $shop->last_login
                                ? \Carbon\Carbon::parse($shop->last_login)->format('d F, Y')
                                : 'N/A' }}
                        </div>
                    </div>

                </div>
            </div>
        </div>


        {{-- Address Card --}}
        <div class="card rounded-2 border-color card-no-shadow mt-2">

            <div class="border-bottom p-3 font-weight-bold">
                {{ translate('Address') }}
            </div>

            <div class="p-3 fs-13">


                {{-- vendor store address --}}
                    <div class="border-bottom-dashed2 pb-2">

                        <div class="font-weight-bold mb-1">
                            {{ translate('Office Address') }}
                        </div>
                    
                     <div class="d-flex py-2 border-bottom-dashed2">
                            <div class="w-210px">{{ translate('State') }}</div>
                        <div>{{ $shop->user->state ?? 'N/A' }}</div>
                    </div>
                    
                    
                     <div class="d-flex py-2 border-bottom-dashed2">
                        <div class="w-210px">{{ translate('District') }}</div>
                        <div>{{ $shop->user->district ?? 'N/A' }}</div>
                    </div>
                    
                    
                     <div class="d-flex py-2 border-bottom-dashed2">
                        <div class="w-210px">{{ translate('Block') }}</div>
                        <div>{{ $shop->user->block ?? 'N/A' }}</div>
                    </div>
                    
                    
                     <div class="d-flex py-2 border-bottom-dashed2">
                        <div class="w-210px">{{ translate('City') }}</div>
                        <div>{{ $shop->user->city ?? 'N/A' }}</div>
                    </div>

                    </div>

                {{-- Default Address --}}
                @if($default_shipping_address)

                    <div class="border-bottom-dashed2 pb-2">

                        <div class="font-weight-bold mb-1">
                            {{ translate('Default Shipping Address') }}
                        </div>

                        <div>
                            {{ $default_shipping_address->address }},
                            {{ optional($default_shipping_address->area)->name }},
                            {{ optional($default_shipping_address->city)->name }},
                            {{ optional($default_shipping_address->state)->name }},
                            -{{ $default_shipping_address->postal_code }},
                            {{ optional($default_shipping_address->country)->name }}
                        </div>

                    </div>

                @endif


                {{-- Other Addresses --}}
                @if($addresses && count($addresses))

                    <div class="mt-2">

                        <div class="font-weight-bold">
                            {{ translate('Other Address') }}
                        </div>

                        @foreach($addresses as $address)

                            <div class="mb-2">
                                {{ $address->address }},
                                {{ optional($address->area)->name }},
                                {{ optional($address->city)->name }},
                                {{ optional($address->state)->name }},
                                -{{ $address->postal_code }},
                                {{ optional($address->country)->name }}
                            </div>

                        @endforeach

                    </div>

                @endif

            </div>
        </div>

    </div>

</div>