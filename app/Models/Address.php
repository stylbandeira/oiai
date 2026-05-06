<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;
    protected $table = 'address';
    protected $fillable = [
        'country',
        'area',
        'city',
        'street',
        'number',
        'state',
        'cep',
        'complement',
        'latitude',
        'longitude',
        'geocode_status',
        'geocode_error',
        'geocoded_at'
    ];

    protected $attributes = [
        'country' => 'Brasil'
    ];
}
