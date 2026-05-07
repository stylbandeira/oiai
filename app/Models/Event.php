<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    const TYPE_PRODUCT_INSERT = 'product_insert';
    const TYPE_COMPANY_OWNER_REQUEST = 'company_ownership_request';

    use HasFactory;
    protected $table = 'event';

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'where',
        'type',
        'points',
        'link',
        'checked'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
