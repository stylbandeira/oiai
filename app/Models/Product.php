<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends BaseModel
{
    use HasFactory;
    protected $table = 'products';
    const AVERAGE_PRICE_JOB_CONSTANCY_DAYS = 1;
    const AVERAGE_PRICE_PURCHASE_DATE_LIMIT_WEEKS = 4;

    protected $fillable = [
        'unit_id',
        'quantity',
        'name',
        'img',
        'sku',
        'average_price',
        'category_id',
        'ean',
        'description',
        'validated'
    ];

    protected $attributes = [
        'category_id' => 1,
        'listAdded' => 0,
        'description' => '',
        'average_price' => NULL
    ];

    public function companies()
    {
        return $this->belongsToMany(Company::class, 'company_products')
            ->withPivot(['average_price']);
    }

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

    public function userFavorites()
    {
        return $this->belongsToMany(User::class, 'favorite_products', 'product_id', 'user_id');
    }

    public function getMentionedQuantityVariantAttribute()
    {
        if ($this->mentioned_quantity > 100) {
            return 'perfect';
        } else if ($this->mentioned_quantity > 50) {
            return 'secondary';
        } else {
            return 'destructive';
        }
    }

    /**
     * Define getter and setter for average_price attribute
     *
     * @return Attribute
     */
    public function averagePrice(): Attribute
    {
        return Attribute::make(
            get: fn(float $value) => number_format($value, 2)
        );
    }
}
