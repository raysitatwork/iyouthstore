@extends('backend.layouts.app')

@section('content')
    <div class="page-content">
        <div class="flex-grow-1 p-4">

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

            <h4>Assign Product to Sellers</h4>

            <form action="{{ route('product.assign.store') }}" method="POST">
                @csrf
                {{--
                <div class="mb-3">
                    <label>Select Seller</label>
                    <select name="user_id" class="form-control" required>
                        <option value="">Select Seller</option>
                        @foreach ($shops as $shop)
                            <option value="{{ $shop->user_id }}">
                                {{ $shop->name }}
                            </option>
                        @endforeach
                    </select>
                </div> --}}

                <div class="mb-3">
                    <label>Select Seller</label>

                    {{-- <select name="user_id" class="form-control aiz-selectpicker" data-live-search="true" required> --}}
                    <select name="user_id" id="user_id" class="form-control aiz-selectpicker" data-live-search="true"
                        required>

                        <option value="">Select Seller</option>

                        @foreach ($shops as $shop)
                            <option value="{{ $shop->user_id }}">
                                {{ $shop->name }}
                            </option>
                        @endforeach

                    </select>
                </div>

                {{-- <a href="{{ route('products.assign.export') }}" class="btn btn-success">
                    <i class="las la-file-excel"></i>
                    Download Current Stock Excel
                </a> --}}

                {{-- Import Export --}}
                <div class="mb-3">
                    <button type="button" class="btn btn-success" onclick="openImportModal()"> <i
                            class="las la-file-import"></i>
                        Import Excel / CSV
                    </button>
                    <a href="{{ route('products.assignment.export') }}" class="btn btn-primary"> <i
                            class="las la-download"></i>
                        Export Excel
                    </a>
                </div>

                <div class="alert alert-info mt-3">
                    <h6 class="mb-2">
                        <strong>
                            <i class="las la-info-circle"></i>
                            Import / Export Instructions
                        </strong>
                    </h6>

                    <ol class="mb-0 pl-3">

                        <li class="mb-2">
                            <strong>पहले Export करें:</strong>
                            पहले <strong>Export Excel</strong> पर क्लिक करके
                            current product assignment की Excel file download करें।
                        </li>

                        <li class="mb-2">
                            <strong>Excel में बदलाव करें:</strong>
                            Export की गई Excel file में केवल
                            <strong>assign_quantity</strong> को अपनी आवश्यकता के अनुसार बदलें।
                        </li>

                        <li class="mb-2">
                            <strong>Product ID न बदलें:</strong>
                            <strong>product_id</strong> को change या delete न करें।
                            यह product की पहचान के लिए जरूरी है।
                        </li>

                        <li class="mb-2">
                            <strong>Current Stock:</strong>
                            <strong>current_stock</strong> केवल reference के लिए है।
                            इसे manually change न करें।
                        </li>

                        <li class="mb-2">
                            <strong>Assign Quantity:</strong>
                            जिस product को seller को assign करना है,
                            केवल उसकी <strong>assign_quantity</strong> भरें।
                            बाकी products की quantity blank छोड़ दें।
                        </li>

                        <li class="mb-2">
                            <strong>Seller Select करें:</strong>
                            Import करने से पहले ऊपर से जिस seller को product assign करना है,
                            उस seller को select करना जरूरी है।
                        </li>

                        <li>
                            <strong>Import करें:</strong>
                            Excel/CSV तैयार करने के बाद
                            <strong>Import Excel / CSV</strong> पर क्लिक करके file upload करें।
                        </li>

                    </ol>

                    <div class="alert alert-warning mt-3 mb-0">
                        <strong>
                            <i class="las la-exclamation-triangle"></i>
                            Important:
                        </strong>
                        Import करते समय केवल सही और exported format वाली Excel/CSV file का ही उपयोग करें।
                        <strong>product_id, name और current_stock</strong> को बिना आवश्यकता change न करें।
                    </div>

                </div>


                <div class="row">
                    {{-- <div class="col-md-5">
                        <label>Select Product</label>
                        <select id="product_id" class="form-control">
                            <option value="">Select Product</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}"
                                    data-min="{{ $product->seller_min_purchase_limit ?? 1 }}"
                                    data-max="{{ $product->seller_purchase_limit ?? 9999 }}">
                                    {{ $product->name }}
                                </option>
                            @endforeach
                        </select>
                    </div> --}}

                    <div class="col-md-5">
                        <label>Select Product</label>

                        <select id="product_id" class="form-control aiz-selectpicker" data-live-search="true">

                            <option value="">Select Product</option>

                            @foreach ($products as $product)
                                <option value="{{ $product->id }}"
                                    data-min="{{ $product->seller_min_purchase_limit ?? 1 }}"
                                    data-max="{{ $product->seller_purchase_limit ?? 9999 }}">

                                    {{ $product->name }}

                                </option>
                            @endforeach

                        </select>
                    </div>

                    <div class="col-md-3">
                        <label>Quantity</label>
                        <input type="number" id="quantity" class="form-control">
                    </div>

                    <div class="col-md-2 mt-4">
                        <button type="button" class="btn btn-primary mt-2" onclick="addProduct()">
                            Add
                        </button>
                    </div>
                </div>

                <table class="table table-bordered mt-4">
                    <thead>
                        <tr>
                            <th>SN</th>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="product_table_body">
                    </tbody>
                </table>

                <button type="submit" class="btn btn-success">
                    Submit
                </button>

            </form>
        </div>
    </div>

    <div class="modal fade" id="limitModal">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Invalid Quantity</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body text-center">
                    <p id="limitMessage"></p>
                    <button class="btn btn-primary mt-2" data-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="importModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-md modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Import Products to Seller</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('product.assign.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <strong>Excel / CSV Format</strong>
                            <br><br>
                            Excel/CSV में ये 4 columns होने चाहिए:
                            <br><br>
                            <strong>product_id</strong>
                            &nbsp;&nbsp;
                            <strong>name</strong>
                            &nbsp;&nbsp;
                            <strong>current_stock</strong>
                            &nbsp;&nbsp;
                            <strong>assign_quantity</strong>
                            <br><br>
                            <small>
                                जिस product को seller को assign करना है, केवल उसी की
                                <strong>assign_quantity</strong> भरें।
                                बाकी products की quantity खाली छोड़ दें।
                            </small>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm mt-3 mb-0">
                                    <thead>
                                        <tr>
                                            <th>product_id</th>
                                            <th>name</th>
                                            <th>current_stock</th>
                                            <th>assign_quantity</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>101</td>
                                            <td>Product A</td>
                                            <td>100</td>
                                            <td>10</td>
                                        </tr>
                                        <tr>
                                            <td>105</td>
                                            <td>Product B</td>
                                            <td>50</td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td>110</td>
                                            <td>Product C</td>
                                            <td>80</td>
                                            <td>5</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        {{-- Selected Seller --}}
                        <div class="form-group">
                            <label>Seller</label>
                            <input type="text" id="importSellerName" class="form-control" readonly>
                        </div>
                        {{-- Seller ID --}}
                        <input type="hidden" name="user_id" id="importUserId">
                        {{-- File --}}
                        <div class="form-group">
                            <label>Select Excel / CSV File</label>
                            <input type="file" name="file" id="importFile" class="form-control"
                                accept=".xlsx,.xls,.csv" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="las la-upload"></i>
                            Upload & Assign
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let count = 0;

        //     function addProduct() {

        //         let productSelect = document.getElementById('product_id');
        //         let quantity = document.getElementById('quantity').value;

        //         let productId = productSelect.value;
        //         let productName = productSelect.options[productSelect.selectedIndex].text;

        //         if (productId == "" || quantity == "") {
        //             alert("Please select product and enter quantity");
        //             return;
        //         }

        //         count++;

        //         let row = `
    //     <tr id="row${count}">
    //         <td>${count}</td>
    //         <td>
    //             ${productName}
    //             <input type="hidden" name="products[${count}][product_id]" value="${productId}">
    //         </td>
    //         <td>
    //             ${quantity}
    //             <input type="hidden" name="products[${count}][quantity]" value="${quantity}">
    //         </td>
    //         <td>
    //             <button type="button" class="btn btn-danger btn-sm"
    //                 onclick="removeRow(${count})">
    //                 Remove
    //             </button>
    //         </td>
    //     </tr>
    // `;

        //         document.getElementById('product_table_body').innerHTML += row;

        //         document.getElementById('product_id').value = "";
        //         document.getElementById('quantity').value = "";
        //     }

        //     function removeRow(id) {
        //         document.getElementById('row' + id).remove();
        //     }


        function addProduct() {

            let productSelect = document.getElementById('product_id');
            let quantity = parseInt(document.getElementById('quantity').value);

            let selected = productSelect.options[productSelect.selectedIndex];

            let productId = productSelect.value;
            let productName = selected.text;

            let min = parseInt(selected.getAttribute('data-min')) || 1;
            let max = parseInt(selected.getAttribute('data-max')) || 9999;

            if (productId == "" || !quantity) {
                alert("Please select product and enter quantity");
                return;
            }

            let exists = document.querySelector(
                `input[name^="products"][value="${productId}"]`
            );

            if (exists) {
                showModal("Product already added");
                return;
            }

            if (quantity < min) {
                showModal("Minimum quantity is " + min);
                return;
            }

            if (quantity > max) {
                showModal("Maximum quantity is " + max);
                return;
            }

            count++;

            let row = `
        <tr id="row${count}">
            <td>${count}</td>
            <td>
                ${productName}
                <input type="hidden" name="products[${count}][product_id]" value="${productId}">
            </td>
            <td>
                ${quantity}
                <input type="hidden" name="products[${count}][quantity]" value="${quantity}">
            </td>
            <td>
                <button type="button" class="btn btn-danger btn-sm"
                    onclick="removeRow(${count})">
                    Remove
                </button>
            </td>
        </tr>
    `;

            document.getElementById('product_table_body').innerHTML += row;

            document.getElementById('product_id').value = "";
            document.getElementById('quantity').value = "";
        }

        function showModal(message) {
            document.getElementById('limitMessage').innerText = message;
            $('#limitModal').modal('show');
        }

        function removeRow(id) {
            document.getElementById('row' + id).remove();
        }
        $(document).ready(function() {

            $('.aiz-selectpicker').selectpicker();

            $('.aiz-selectpicker').selectpicker('refresh');

        });

        function openImportModal() {
            let sellerSelect = document.getElementById('user_id');

            if (!sellerSelect) {

                alert('Seller select field not found.');

                return;
            }


            let sellerId = sellerSelect.value;


            /*
            |--------------------------------------------------------------------------
            | Seller Required
            |--------------------------------------------------------------------------
            */

            if (sellerId === '') {

                showModal(
                    'Please select seller first.'
                );

                return;
            }


            /*
            |--------------------------------------------------------------------------
            | Get Seller Name
            |--------------------------------------------------------------------------
            */

            let selectedOption =
                sellerSelect.options[
                    sellerSelect.selectedIndex
                ];


            let sellerName =
                selectedOption.text.trim();


            /*
            |--------------------------------------------------------------------------
            | Set Hidden Seller ID
            |--------------------------------------------------------------------------
            */

            document.getElementById(
                'importUserId'
            ).value = sellerId;


            /*
            |--------------------------------------------------------------------------
            | Show Seller Name
            |--------------------------------------------------------------------------
            */

            document.getElementById(
                'importSellerName'
            ).value = sellerName;


            /*
            |--------------------------------------------------------------------------
            | Reset File
            |--------------------------------------------------------------------------
            */

            document.getElementById(
                'importFile'
            ).value = '';


            /*
            |--------------------------------------------------------------------------
            | Open Import Modal
            |--------------------------------------------------------------------------
            */

            $('#importModal').modal('show');
        }
    </script>
@endsection
