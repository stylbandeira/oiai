<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $table = 'company';
    protected $fillable = [
        'address_id',
        'name',
        'cnpj',
        'img'
    ];

    public function products()
    {
        return $this->hasMany(CompanyProducts::class, 'product_id');
    }
}
