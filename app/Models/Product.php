<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $table = 'products';

    protected $fillable = [
        'unit_id',
        'quantity',
        'name',
        'img',
        'sku',
        'average_price',
        'category_id'
    ];

    protected $attributes = [
        'category_id' => 1
    ];

    public function userAddedProducts()
    {
        return $this->hasMany(UserAddedProducts::class, 'product_id');
    }

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function unity()
    {
        return $this->belongsTo(Unity::class, 'unit_id');
    }
}
