<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code'
    ];

    protected static function booted()
    {
        static::creating(function ($product) {
            // If product_code is not set, we'll set it after creation when we have the ID
            if (empty($product->code)) {
                $product->code = null;
            }
        });
        
        static::created(function ($product) {
            // Now that we have an ID, generate the product code if it wasn't set
            if (empty($product->code)) {
                // Format product code as 201, 202, 203, etc.
                // Adding 200 to the ID to start from 201
                $product->code = 100 + $product->id;
                $product->save();
            }
        });
    }
}
