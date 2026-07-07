<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ListProducts extends BaseModel
{
    use HasFactory;

    const STATUS_LIST_ACTIVE = 'active';
    const STATUS_LIST_COMPLETED = 'completed';
    const STATUS_LIST_INACTIVE = 'inactive';

    const VALID_STATUSES = [
        self::STATUS_LIST_ACTIVE,
        self::STATUS_LIST_COMPLETED,
        self::STATUS_LIST_INACTIVE,
    ];

    protected $table = 'list_products';

    protected $attributes = [
        'company_product_id' => 0
    ];

    public $fillable = [
        'list_id',
        'product_id',
        'quantity'
    ];

    public function list()
    {
        return $this->belongsTo(ItensList::class, 'list_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function companyProduct()
    {
        return $this->belongsTo(CompanyProducts::class, 'company_product_id');
    }
}
