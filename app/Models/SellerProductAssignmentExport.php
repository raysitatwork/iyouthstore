<?php

namespace App\Models;

use App\Traits\PreventDemoModeChanges;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SellerProductAssignmentExport implements FromCollection, WithMapping, WithHeadings
{
    use PreventDemoModeChanges;

    /**
     * Product Assign page के लिए products
     */
    public function collection()
    {
        return Product::where('added_by', 'admin')
            ->where('auction_product', 0)
            ->where('wholesale_product', 0)
            ->where('digital', 0)
            ->with('stocks')
            ->with('main_category')
            ->orderBy('id', 'asc')
            ->get();
    }

    /**
     * Excel Headings
     */
    public function headings(): array
    {
        return [
            'product_id',
            'name',
            'current_stock',
            'assign_quantity',
            'category',
            'Amount',
            'NLC',
        ];
    }

    /**
     * Excel Row Data
     */
    public function map($product): array
    {
        /*
        |--------------------------------------------------------------------------
        | Current Stock
        |--------------------------------------------------------------------------
        */

        $currentStock = 0;

        foreach ($product->stocks as $stock) {
            if ($stock->variant === '') {
                $currentStock += (float) $stock->qty;
            }
        }

        return [
            $product->id,
            $product->name,
            $currentStock,
            '',
            $product->main_category->name,
            $product->unit_price,
            $product->seller_price,

            // Seller quantity बाद में Excel में भरेगा
            '',
        ];
    }
}
