<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyProducts extends Model
{
    use HasFactory;
    protected $table = 'company_products';

    protected $fillable = [
        'product_id',
        'company_id',
        'average_price'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
