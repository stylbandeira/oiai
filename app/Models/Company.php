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
        'img',
        'site',
        'email',
        'status',
        'phone',
        'description',
        'raw_address'
    ];
    protected $attributes = [
        'img' => './'
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'company_products')
            ->withPivot(['average_price']);
    }

    public function owners()
    {
        return $this->belongsToMany(User::class, 'company_owners', 'company_id', 'user_id');
    }
}
