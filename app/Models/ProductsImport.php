<?php

namespace App\Models;

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\User;
use App\Traits\PreventDemoModeChanges;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Str;
use Auth;
use Carbon\Carbon;
use Storage;

//class ProductsImport implements ToModel, WithHeadingRow, WithValidation
class ProductsImport implements ToCollection, WithHeadingRow, WithValidation, ToModel
{
    use PreventDemoModeChanges;

    private $rows = 0;

    public function collection(Collection $rows)
    {
        $canImport = true;

        $user = Auth::user();

        // ==========================================
        // SELLER PACKAGE LIMIT CHECK
        // ==========================================
        if (
            $user->user_type == 'seller' &&
            addon_is_activated('seller_subscription')
        ) {
            if (
                (count($rows) + $user->products()->count()) > $user->shop->product_upload_limit
                || $user->shop->package_invalid_at == null
                || Carbon::now()->diffInDays(
                    Carbon::parse($user->shop->package_invalid_at),
                    false
                ) < 0
            ) {
                $canImport = false;

                flash(
                    translate('Please upgrade your package.')
                )->warning();
            }
        }

        if (!$canImport) {
            return;
        }

        // ==========================================
        // IMPORT ROWS
        // ==========================================
        foreach ($rows as $row) {

            /*
        |--------------------------------------------------------------------------
        | PRODUCT ID
        |--------------------------------------------------------------------------
        */

            $productId = !empty($row['product_id'])
                ? (int) $row['product_id']
                : null;


            /*
        |--------------------------------------------------------------------------
        | EXISTING PRODUCT / NEW PRODUCT
        |--------------------------------------------------------------------------
        */

            if ($productId) {

                // --------------------------------------
                // UPDATE EXISTING PRODUCT
                // --------------------------------------

                $product = Product::find($productId);

                if (!$product) {

                    flash(
                        translate('Product ID') . ' ' .
                            $productId . ' ' .
                            translate('not found.')
                    )->warning();

                    continue;
                }
            } else {

                // --------------------------------------
                // CREATE NEW PRODUCT
                // --------------------------------------

                $approved = 1;

                if (
                    $user->user_type == 'seller' &&
                    get_setting('product_approve_by_admin') == 1
                ) {
                    $approved = 0;
                }

                $product = new Product();

                $product->added_by =
                    $user->user_type == 'seller'
                    ? 'seller'
                    : 'admin';

                $product->user_id =
                    $user->user_type == 'seller'
                    ? $user->id
                    : User::where('user_type', 'admin')->first()->id;

                $product->approved = $approved;

                // Default JSON fields
                $product->attributes = '[]';
                $product->choice_options = '[]';
                $product->colors = '[]';
                $product->variations = '[]';
            }


            /*
        |--------------------------------------------------------------------------
        | BASIC PRODUCT DATA
        |--------------------------------------------------------------------------
        */

            if (
                isset($row['name']) &&
                trim($row['name']) !== ''
            ) {
                $product->name = trim($row['name']);
            }

            if (isset($row['description'])) {
                $product->description = $row['description'];
            }

            if (
                isset($row['category_id']) &&
                $row['category_id'] !== ''
            ) {
                $product->category_id = (int) $row['category_id'];
            }

            if (
                isset($row['brand_id']) &&
                $row['brand_id'] !== ''
            ) {
                $product->brand_id = (int) $row['brand_id'];
            }

            if (isset($row['video_provider'])) {
                $product->video_provider = $row['video_provider'];
            }

            if (isset($row['video_link'])) {
                $product->video_link = $row['video_link'];
            }

            if (isset($row['tags'])) {
                $product->tags = $row['tags'];
            }

            if (
                isset($row['unit_price']) &&
                $row['unit_price'] !== ''
            ) {
                $product->unit_price = (float) $row['unit_price'];
            }

            if (
                isset($row['seller_price']) &&
                $row['seller_price'] !== ''
            ) {
                $product->seller_price = (float) $row['seller_price'];
            }

            if (isset($row['unit'])) {
                $product->unit = $row['unit'];
            }

            if (
                isset($row['discount']) &&
                $row['discount'] !== ''
            ) {
                $product->discount = (float) $row['discount'];
            }

            if (isset($row['discount_type'])) {
                $product->discount_type = $row['discount_type'];
            }

            if (isset($row['meta_title'])) {
                $product->meta_title = $row['meta_title'];
            }

            if (isset($row['meta_description'])) {
                $product->meta_description = $row['meta_description'];
            }

            if (
                isset($row['est_shipping_days']) &&
                $row['est_shipping_days'] !== ''
            ) {
                $product->est_shipping_days =
                    (int) $row['est_shipping_days'];
            }


            /*
        |--------------------------------------------------------------------------
        | SLUG
        |--------------------------------------------------------------------------
        */

            if (!empty($row['slug'])) {

                $slug = Str::slug($row['slug']);
            } elseif (!empty($row['name'])) {

                $slug = Str::slug($row['name']);
            } else {

                $slug = 'product-' . time();
            }


            // Make slug unique for NEW products
            if (!$product->exists) {

                $originalSlug = $slug;
                $counter = 1;

                while (
                    Product::where('slug', $slug)->exists()
                ) {
                    $slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
            }

            $product->slug = $slug;


            /*
        |--------------------------------------------------------------------------
        | JSON FIELDS
        |--------------------------------------------------------------------------
        |
        | Important:
        | Only reset these fields for NEW products.
        | Existing products should retain their values.
        |
        */

            if (!$product->exists) {

                $product->attributes = '[]';
                $product->choice_options = '[]';
                $product->colors = '[]';
                $product->variations = '[]';
            }


            /*
        |--------------------------------------------------------------------------
        | IMAGES
        |--------------------------------------------------------------------------
        */

            if (
                isset($row['thumbnail_img']) &&
                trim($row['thumbnail_img']) !== ''
            ) {

                $product->thumbnail_img =
                    $this->downloadThumbnail(
                        $row['thumbnail_img']
                    );
            }

            if (
                isset($row['photos']) &&
                trim($row['photos']) !== ''
            ) {

                $product->photos =
                    $this->downloadGalleryImages(
                        $row['photos']
                    );
            }


            /*
        |--------------------------------------------------------------------------
        | CURRENT STOCK
        |--------------------------------------------------------------------------
        */

            if (
                isset($row['current_stock']) &&
                $row['current_stock'] !== ''
            ) {

                $product->current_stock =
                    (int) $row['current_stock'];
            }


            /*
        |--------------------------------------------------------------------------
        | SAVE PRODUCT
        |--------------------------------------------------------------------------
        */

            $product->save();


                    /*
                |--------------------------------------------------------------------------
                | PRODUCT STOCK
                |--------------------------------------------------------------------------
                */

            $qty =
                isset($row['current_stock']) &&
                $row['current_stock'] !== ''
                ? (float) $row['current_stock']
                : ($product->current_stock ?? 0);

            $price =
                isset($row['unit_price']) &&
                $row['unit_price'] !== ''
                ? (float) $row['unit_price']
                : ($product->unit_price ?? 0);

            $sku = isset($row['sku'])
                ? trim($row['sku'])
                : '';


            $stock = ProductStock::where(
                'product_id',
                $product->id
            )
                ->where('variant', '')
                ->first();


            if ($stock) {

                // UPDATE STOCK

                $stock->update([
                    'qty' => $qty,
                    'price' => $price,
                    'sku' => $sku,
                    'variant' => '',
                ]);
            } else {

                // CREATE STOCK

                ProductStock::create([
                    'product_id' => $product->id,
                    'qty' => $qty,
                    'price' => $price,
                    'sku' => $sku,
                    'variant' => '',
                ]);
            }


                        /*
                  |--------------------------------------------------------------------------
                 | MULTIPLE CATEGORIES
                 |--------------------------------------------------------------------------
                    */

            if (
                isset($row['multi_categories']) &&
                trim($row['multi_categories']) !== ''
            ) {

                ProductCategory::where(
                    'product_id',
                    $product->id
                )->delete();


                $categoryIds =
                    explode(
                        ',',
                        $row['multi_categories']
                    );


                foreach ($categoryIds as $categoryId) {

                    $categoryId = trim($categoryId);

                    if ($categoryId === '') {
                        continue;
                    }


                    ProductCategory::updateOrCreate(
                        [
                            'product_id' => $product->id,
                            'category_id' => (int) $categoryId,
                        ],
                        [
                            'product_id' => $product->id,
                            'category_id' => (int) $categoryId,
                        ]
                    );
                }
            }
        }


                 /*
                |--------------------------------------------------------------------------
                | SUCCESS MESSAGE
                |--------------------------------------------------------------------------
                */

        flash(
            translate(
                'Products imported/updated successfully'
            )
        )->success();
    }
    // public function collection(Collection $rows)
    // {
    //     $canImport = true;

    //     $user = Auth::user();

    //     // Seller package limit check
    //     if (
    //         $user->user_type == 'seller' &&
    //         addon_is_activated('seller_subscription')
    //     ) {
    //         if (
    //             (count($rows) + $user->products()->count()) > $user->shop->product_upload_limit
    //             || $user->shop->package_invalid_at == null
    //             || Carbon::now()->diffInDays(
    //                 Carbon::parse($user->shop->package_invalid_at),
    //                 false
    //             ) < 0
    //         ) {
    //             $canImport = false;

    //             flash(translate('Please upgrade your package.'))->warning();
    //         }
    //     }

    //     if (!$canImport) {
    //         return;
    //     }

    //     foreach ($rows as $row) {

    //         /* Product ID */
    //         $productId = !empty($row['product_id'])
    //             ? (int) $row['product_id']
    //             : null;

    //         /* Existing / New Product */

    //         if ($productId) {

    //             // Existing product update
    //             $product = Product::find($productId);

    //             if (!$product) {
    //                 flash(
    //                     translate('Product ID') . ' ' .
    //                         $productId . ' ' .
    //                         translate('not found.')
    //                 )->warning();

    //                 continue;
    //             }
    //         } else {

    //             // New product
    //             $approved = 1;

    //             if (
    //                 $user->user_type == 'seller' &&
    //                 get_setting('product_approve_by_admin') == 1
    //             ) {
    //                 $approved = 0;
    //             }

    //             $product = new Product();

    //             $product->added_by = $user->user_type == 'seller'
    //                 ? 'seller'
    //                 : 'admin';

    //             $product->user_id = $user->user_type == 'seller'
    //                 ? $user->id
    //                 : User::where('user_type', 'admin')->first()->id;

    //             $product->approved = $approved;

    //             /* IMPORTANT: Default JSON Fields
    //                 Edit page इन fields को json_decode() करता है।
    //                 इसलिए NULL नहीं, valid JSON array save करें। */

    //             $product->attributes = '[]';
    //             $product->choice_options = '[]';
    //             $product->colors = '[]';
    //             $product->variations = '[]';
    //         }


    //         /* Product Basic Data */
    //         if (isset($row['name']) && $row['name'] !== '') {
    //             $product->name = $row['name'];
    //         }

    //         if (isset($row['description'])) {
    //             $product->description = $row['description'];
    //         }

    //         if (isset($row['category_id']) && $row['category_id'] !== '') {
    //             $product->category_id = (int) $row['category_id'];
    //         }

    //         if (isset($row['brand_id']) && $row['brand_id'] !== '') {
    //             $product->brand_id = (int) $row['brand_id'];
    //         }

    //         if (isset($row['video_provider'])) {
    //             $product->video_provider = $row['video_provider'];
    //         }

    //         if (isset($row['video_link'])) {
    //             $product->video_link = $row['video_link'];
    //         }

    //         if (isset($row['tags'])) {
    //             $product->tags = $row['tags'];
    //         }

    //         if (isset($row['unit_price']) && $row['unit_price'] !== '') {
    //             $product->unit_price = (float) $row['unit_price'];
    //         }

    //         if (isset($row['seller_price']) && $row['seller_price'] !== '') {
    //             $product->seller_price = (float) $row['seller_price'];
    //         }

    //         // if (isset($row['seller_selling_price']) && $row['seller_selling_price'] !== '') {
    //         //     $product->seller_selling_price = (float) $row['seller_selling_price'];
    //         // }

    //         if (isset($row['unit'])) {
    //             $product->unit = $row['unit'];
    //         }

    //         if (isset($row['discount']) && $row['discount'] !== '') {
    //             $product->discount = (float) $row['discount'];
    //         }

    //         if (isset($row['discount_type'])) {
    //             $product->discount_type = $row['discount_type'];
    //         }

    //         if (isset($row['meta_title'])) {
    //             $product->meta_title = $row['meta_title'];
    //         }

    //         if (isset($row['meta_description'])) {
    //             $product->meta_description = $row['meta_description'];
    //         }

    //         if (isset($row['est_shipping_days']) && $row['est_shipping_days'] !== '') {
    //             $product->est_shipping_days = $row['est_shipping_days'];
    //         }

    //         /* Slug */
    //         if (!empty($row['slug'])) {
    //             $slug = preg_replace(
    //                 '/[^A-Za-z0-9\-]/',
    //                 '',
    //                 str_replace(
    //                     ' ',
    //                     '-',
    //                     strtolower(trim($row['slug']))
    //                 )
    //             );

    //             $product->slug = $slug;
    //         }

    //         /* JSON Fields - Keep Valid JSON  */
    //         $product->attributes = '[]';
    //         $product->choice_options = '[]';
    //         $product->colors = '[]';
    //         $product->variations = '[]';

    //         /* Images */

    //         if (!empty($row['thumbnail_img'])) {
    //             $product->thumbnail_img =
    //                 $this->downloadThumbnail($row['thumbnail_img']);
    //         }

    //         if (!empty($row['photos'])) {
    //             $product->photos =
    //                 $this->downloadGalleryImages($row['photos']);
    //         }

    //         /* Save Product */
    //         $product->save();

    //         /* Product Stock */
    //         $stockData = [

    //             'qty' => isset($row['current_stock']) &&
    //                 $row['current_stock'] !== ''
    //                 ? (float) $row['current_stock']
    //                 : 0,

    //             'price' => isset($row['unit_price']) &&
    //                 $row['unit_price'] !== ''
    //                 ? (float) $row['unit_price']
    //                 : ($product->unit_price ?? 0),

    //             'sku' => $row['sku'] ?? '',

    //             'variant' => '',
    //         ];

    //         /* Update Existing Stock / Create New Stock */
    //         $stock = ProductStock::where('product_id', $product->id)
    //             ->where('variant', '')
    //             ->first();

    //         if ($stock) {

    //             $stock->update($stockData);
    //         } else {

    //             ProductStock::create([
    //                 'product_id' => $product->id,
    //                 'qty' => $stockData['qty'],
    //                 'price' => $stockData['price'],
    //                 'sku' => $stockData['sku'],
    //                 'variant' => '',
    //             ]);
    //         }

    //         /* Multi Categories */
    //         if (
    //             isset($row['multi_categories']) &&
    //             trim($row['multi_categories']) !== ''
    //         ) {

    //             ProductCategory::where(
    //                 'product_id',
    //                 $product->id
    //             )->delete();

    //             foreach (
    //                 explode(',', $row['multi_categories'])
    //                 as $category_id
    //             ) {

    //                 $category_id = trim($category_id);

    //                 if ($category_id !== '') {

    //                     ProductCategory::updateOrCreate(
    //                         [
    //                             'product_id' => $product->id,
    //                             'category_id' => $category_id,
    //                         ],
    //                         [
    //                             'product_id' => $product->id,
    //                             'category_id' => $category_id,
    //                         ]
    //                     );
    //                 }
    //             }
    //         }
    //     }

    //     flash(
    //         translate('Products imported/updated successfully')
    //     )->success();
    // }

    // public function collection(Collection $rows)
    // {
    //     $canImport = true;
    //     $user = Auth::user();
    //     if ($user->user_type == 'seller' && addon_is_activated('seller_subscription')) {
    //         if ((count($rows) + $user->products()->count()) > $user->shop->product_upload_limit
    //             || $user->shop->package_invalid_at == null
    //             || Carbon::now()->diffInDays(Carbon::parse($user->shop->package_invalid_at), false) < 0
    //         ) {
    //             $canImport = false;
    //             flash(translate('Please upgrade your package.'))->warning();
    //         }
    //     }

    //     if ($canImport) {
    //         foreach ($rows as $row) {
    //             $approved = 1;
    //             if ($user->user_type == 'seller' && get_setting('product_approve_by_admin') == 1) {
    //                 $approved = 0;
    //             }

    //             $productId = Product::create([
    //                 'name' => $row['name'],
    //                 'description' => $row['description'],
    //                 'added_by' => $user->user_type == 'seller' ? 'seller' : 'admin',
    //                 'user_id' => $user->user_type == 'seller' ? $user->id : User::where('user_type', 'admin')->first()->id,
    //                 'approved' => $approved,
    //                 'category_id' => $row['category_id'],
    //                 'brand_id' => $row['brand_id'],
    //                 'video_provider' => $row['video_provider'],
    //                 'video_link' => $row['video_link'],
    //                 'tags' => $row['tags'],
    //                 'unit_price' => $row['unit_price'],
    //                 'seller_price' => $row['seller_price'],
    //                 'seller_selling_price' => $row['seller_selling_price'],
    //                 'unit' => $row['unit'],
    //                 'discount' => $row['discount'],
    //                 'discount_type' => $row['discount_type'],
    //                 'meta_title' => $row['meta_title'],
    //                 'meta_description' => $row['meta_description'],
    //                 'est_shipping_days' => $row['est_shipping_days'],
    //                 'colors' => json_encode(array()),
    //                 'choice_options' => json_encode(array()),
    //                 'variations' => json_encode(array()),
    //                 'slug' => preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', strtolower($row['slug']))) . '-' . Str::random(5),
    //                 'thumbnail_img' => $this->downloadThumbnail($row['thumbnail_img']),
    //                 'photos' => $this->downloadGalleryImages($row['photos']),
    //             ]);
    //             ProductStock::create([
    //                 'product_id' => $productId->id,
    //                 'qty' => $row['current_stock'],
    //                 'price' => $row['unit_price'],
    //                 'sku' => $row['sku'],
    //                 'variant' => '',
    //             ]);
    //             if ($row['multi_categories'] != null) {
    //                 foreach (explode(',', $row['multi_categories']) as $category_id) {
    //                     ProductCategory::insert([
    //                         "product_id" => $productId->id,
    //                         "category_id" => $category_id
    //                     ]);
    //                 }
    //             }
    //         }

    //         flash(translate('Products imported successfully'))->success();
    //     }
    // }

    public function model(array $row)
    {
        ++$this->rows;
    }

    public function getRowCount(): int
    {
        return $this->rows;
    }

    public function rules(): array
    {
        return [
            // Can also use callback validation rules
            'unit_price' => function ($attribute, $value, $onFailure) {
                if (!is_numeric($value)) {
                    $onFailure('Unit price is not numeric');
                }
            }
        ];
    }

    public function downloadThumbnail($url)
    {
        try {
            $upload = new Upload;
            $upload->external_link = $url;
            $upload->type = 'image';
            $upload->save();

            return $upload->id;
        } catch (\Exception $e) {
        }
        return null;
    }

    public function downloadGalleryImages($urls)
    {
        $data = array();
        foreach (explode(',', str_replace(' ', '', $urls)) as $url) {
            $data[] = $this->downloadThumbnail($url);
        }
        return implode(',', $data);
    }
}
