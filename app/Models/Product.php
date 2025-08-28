<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $table = 'products';

    public function userAddedProducts()
    {
        return $this->hasMany(UserAddedProducts::class, 'product_id');
    }
}
