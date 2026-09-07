<?php

namespace App\Models;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SellerProductImport implements ToCollection, WithHeadingRow
{
    protected $sellerId;

    public function __construct($sellerId)
    {
        $this->sellerId = $sellerId;
    }

    public function collection(Collection $rows)
    {
        /*
        |--------------------------------------------------------------------------
        | Empty File Check
        |--------------------------------------------------------------------------
        */

        if ($rows->isEmpty()) {
            throw new \Exception('Excel/CSV file is empty.');
        }

        /*
        |--------------------------------------------------------------------------
        | Aggregate Same Product
        |--------------------------------------------------------------------------
        |
        | Example:
        |
        | product_id | name       | current_stock | assign_quantity
        | 1          | Product A  | 100           | 20
        | 2          | Product B  | 50            |
        | 3          | Product C  | 80            | 10
        |
        | केवल assign_quantity वाली rows import होंगी।
        |
        */

        $productQuantities = [];

        foreach ($rows as $index => $row) {

            $productId = $row['product_id'] ?? null;
            $quantity  = $row['assign_quantity'] ?? null;

            /*
            |--------------------------------------------------------------------------
            | Product ID Required
            |--------------------------------------------------------------------------
            */

            if ($productId === null || $productId === '') {
                throw new \Exception(
                    'Product ID missing at Excel row ' . ($index + 2)
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Product ID Numeric Check
            |--------------------------------------------------------------------------
            */

            if (!is_numeric($productId)) {
                throw new \Exception(
                    'Invalid Product ID "' . $productId .
                    '" at Excel row ' . ($index + 2)
                );
            }

            $productId = (int) $productId;

            if ($productId <= 0) {
                throw new \Exception(
                    'Invalid Product ID: ' . $productId .
                    ' at Excel row ' . ($index + 2)
                );
            }

            /*
            |--------------------------------------------------------------------------
            | IMPORTANT:
            | assign_quantity खाली है तो row SKIP होगी
            |--------------------------------------------------------------------------
            |
            | यही आपकी मुख्य requirement है।
            |
            */

            if (
                $quantity === null ||
                $quantity === '' ||
                trim((string) $quantity) === ''
            ) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Quantity Numeric Check
            |--------------------------------------------------------------------------
            */

            if (!is_numeric($quantity)) {
                throw new \Exception(
                    'Invalid assign quantity "' . $quantity .
                    '" for Product ID ' . $productId .
                    ' at Excel row ' . ($index + 2)
                );
            }

            $quantity = (int) $quantity;

            /*
            |--------------------------------------------------------------------------
            | Quantity 0 है तो भी SKIP
            |--------------------------------------------------------------------------
            */

            if ($quantity <= 0) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Aggregate Same Product
            |--------------------------------------------------------------------------
            */

            if (!isset($productQuantities[$productId])) {
                $productQuantities[$productId] = 0;
            }

            $productQuantities[$productId] += $quantity;
        }

        /*
        |--------------------------------------------------------------------------
        | अगर कोई भी product assign नहीं किया गया
        |--------------------------------------------------------------------------
        */

        if (empty($productQuantities)) {
            throw new \Exception(
                'Excel में किसी भी product के लिए assign_quantity नहीं भरी गई है.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Start Transaction
        |--------------------------------------------------------------------------
        */

        DB::beginTransaction();

        try {

            /*
            |--------------------------------------------------------------------------
            | FIRST: Validate All Products
            |--------------------------------------------------------------------------
            */

            $validatedProducts = [];

            foreach ($productQuantities as $productId => $quantity) {

                /*
                |--------------------------------------------------------------------------
                | Lock Product
                |--------------------------------------------------------------------------
                */

                $product = Product::lockForUpdate()
                    ->find($productId);

                if (!$product) {
                    throw new \Exception(
                        'Product ID ' . $productId . ' not found.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Product Current Stock
                |--------------------------------------------------------------------------
                */

                $availableStock = (float) ($product->current_stock ?? 0);

                if ($quantity > $availableStock) {

                    throw new \Exception(
                        'Product "' . $product->name .
                        '" has only ' . $availableStock .
                        ' stock available, but seller requires ' .
                        $quantity . '.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Seller Minimum Purchase Limit
                |--------------------------------------------------------------------------
                */

                $minQuantity = (int) (
                    $product->seller_min_purchase_limit ?? 1
                );

                if ($quantity < $minQuantity) {

                    throw new \Exception(
                        'Product "' . $product->name .
                        '" minimum quantity is ' . $minQuantity .
                        '. Seller requires only ' . $quantity . '.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Seller Maximum Purchase Limit
                |--------------------------------------------------------------------------
                */

                $maxQuantity = (int) (
                    $product->seller_purchase_limit ?? 999999999
                );

                if ($quantity > $maxQuantity) {

                    throw new \Exception(
                        'Product "' . $product->name .
                        '" maximum quantity is ' . $maxQuantity .
                        '. Seller requires ' . $quantity . '.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | ProductStock Check
                |--------------------------------------------------------------------------
                */

                $productStock = ProductStock::where(
                    'product_id',
                    $productId
                )
                    ->where('variant', '')
                    ->lockForUpdate()
                    ->first();

                if (!$productStock) {

                    throw new \Exception(
                        'Stock record not found for product "' .
                        $product->name . '".'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | ProductStock Quantity
                |--------------------------------------------------------------------------
                */

                $stockQty = (float) ($productStock->qty ?? 0);

                if ($quantity > $stockQty) {

                    throw new \Exception(
                        'Product "' . $product->name .
                        '" ProductStock has only ' . $stockQty .
                        ' quantity available, but seller requires ' .
                        $quantity . '.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Store Valid Product
                |--------------------------------------------------------------------------
                */

                $validatedProducts[] = [
                    'product'       => $product,
                    'product_stock' => $productStock,
                    'quantity'      => $quantity,
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | SECOND: Assign All Products
            |--------------------------------------------------------------------------
            */

            foreach ($validatedProducts as $item) {

                $product      = $item['product'];
                $productStock = $item['product_stock'];
                $quantity     = $item['quantity'];

                /*
                |--------------------------------------------------------------------------
                | Decrease Main Product Stock
                |--------------------------------------------------------------------------
                */

                $product->current_stock =
                    (float) $product->current_stock - $quantity;

                $product->save();

                /*
                |--------------------------------------------------------------------------
                | Decrease ProductStock
                |--------------------------------------------------------------------------
                */

                $productStock->qty =
                    (float) $productStock->qty - $quantity;

                $productStock->save();

                /*
                |--------------------------------------------------------------------------
                | Seller Product
                |--------------------------------------------------------------------------
                */

                $sellerProduct = SellerProduct::firstOrNew([
                    'seller_id'  => $this->sellerId,
                    'product_id' => $product->id,
                ]);

                $sellerProduct->stock =
                    (float) ($sellerProduct->stock ?? 0) + $quantity;

                $sellerProduct->save();

                /*
                |--------------------------------------------------------------------------
                | Assignment History
                |--------------------------------------------------------------------------
                */

                SellerProductAssignment::create([
                    'seller_id'  => $this->sellerId,
                    'product_id' => $product->id,
                    'quantity'   => $quantity,
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Commit Transaction
            |--------------------------------------------------------------------------
            */

            DB::commit();

        } catch (\Throwable $e) {

            /*
            |--------------------------------------------------------------------------
            | Rollback
            |--------------------------------------------------------------------------
            */

            DB::rollBack();

            throw $e;
        }
    }
}


