<?php

namespace App\Models;

use App\Services\GeocodeService;
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

    public static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            if ($model->isDirty('endereco') && !empty($model->endereco)) {
                $geocode = app(GeocodeService::class);
                $resultado = $geocode->geocode($model->endereco);

                if ($resultado['success']) {
                    $model->latitude = $resultado['lat'];
                    $model->longitude = $resultado['lon'];
                    $model->precisao = $resultado['precisao'];
                }
            }
        });
    }
}
