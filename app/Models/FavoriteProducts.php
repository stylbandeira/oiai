<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FavoriteProducts extends Model
{
    use HasFactory;

    protected $table = 'favorite_products';
    protected $fillable = [
        'user_id',
        'product_id'
    ];
}
