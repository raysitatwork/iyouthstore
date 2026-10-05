<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductEnquiry extends Model
{
    use HasFactory;

    protected $table = 'product_enquiry';

    protected $fillable = [
        'seller_id',
        'product',
        'stock',
        'price_range',
    ];

        public function user()
    {
        return $this->belongsTo(User::class);
    }
}
