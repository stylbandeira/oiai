<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
// implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    const POINTS = 'points';
    const ALLOWED_ACTIVITY_TYPE = [
        Event::TYPE_PRODUCT_INSERT,
        Event::TYPE_COMPANY_OWNER_REQUEST
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'type',
        'email',
        'password',
        'cpf',
        'status',
        'points',
        'hasNotification'
    ];

    protected $attributes = [
        'cpf' => null
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'password_confirmation',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function sendEmailVerificationNotification()
    {
        $this->notify(new VerifyEmailNotification);
    }

    public function companies()
    {
        return $this->belongsToMany(Company::class, 'company_owners', 'user_id', 'company_id');
    }

    public function activeCompanies()
    {
        return $this->belongsToMany(Company::class, 'company_owners', 'user_id', 'company_id')
            ->wherePivot('status', 'active');
    }


    public function favoriteProducts()
    {
        return $this->belongsToMany(Product::class, 'favorite_products', 'user_id', 'product_id');
    }

    public function lists()
    {
        return $this->hasOne(ItensList::class, 'user_id');
    }

    public function activeLists()
    {
        return $this->lists()->where('status', 'active');
    }

    /**
     * TODO EPIC 015
     *
     * @return void
     */
    public function getMonthEconomyAttribute()
    {
        return 0;
    }

    public function recentActivity()
    {
        return $this->hasMany(Event::class)->wherein('type', $this::ALLOWED_ACTIVITY_TYPE)->orderBy('created_at', 'DESC')->take(3);
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function getNotificationsAttribute()
    {
        return $this->hasNotification ? 0 : $this->events()->where('checked', false)->count();
    }
}
