<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItensList extends Model
{
    use HasFactory;

    protected $table = "list";

    public $fillable = [
        'user_id',
        'name',
        'favorite',
        'total'
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'list_products', 'list_id', 'product_id')->withPivot('quantity');
    }
}
