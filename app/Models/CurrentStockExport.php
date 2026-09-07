<?php

namespace App\Models;

use App\Traits\PreventDemoModeChanges;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CurrentStockExport implements FromCollection, WithMapping, WithHeadings
{
    use PreventDemoModeChanges;

    /**
     * Export only admin physical products
     */
    public function collection()
    {
        return Product::where('added_by', 'admin')
            ->where('auction_product', 0)
            ->where('wholesale_product', 0)
            ->where('digital', 0)
            ->with('stocks')
            ->orderBy('id', 'asc')
            ->get();
    }

    /**
     * Excel headings
     */
    public function headings(): array
    {
        return [
            'product_id',

            'name',
            'description',
            'added_by',
            'user_id',
            'category_id',
            'brand_id',
            'tags',
            'video_provider',
            'video_link',
            'unit_price',
            'seller_price',
            'discount',
            'discount_type',
            'unit',
            'slug',

            'current_stock',

            // Admin will fill this column
            'assign_quantity',

            'est_shipping_days',
            'meta_title',
            'meta_description',
        ];
    }

    /**
     * Map Product to Excel row
     */
    public function map($product): array
    {
        /*
        |--------------------------------------------------------------------------
        | Calculate Current Stock
        |--------------------------------------------------------------------------
        */

        $currentStock = 0;

        foreach ($product->stocks as $stock) {
            $currentStock += (float) $stock->qty;
        }

        return [
            $product->id,

            $product->name,
            $product->description,
            $product->added_by,
            $product->user_id,
            $product->category_id,
            $product->brand_id,
            $product->tags,
            $product->video_provider,
            $product->video_link,
            $product->unit_price,
            $product->seller_price,
            $product->discount,
            $product->discount_type,
            $product->unit,
            $product->slug,

            // Current available stock
            $currentStock,

            // Admin will enter assignment quantity
            0,

            $product->est_shipping_days,
            $product->meta_title,
            $product->meta_description,
        ];
    }
}
