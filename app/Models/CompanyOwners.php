<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyOwners extends Model
{
    protected $table = 'company_owners';
    protected $fillable = [
        'user_id',
        'company_id'
    ];
}
