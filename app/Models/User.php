<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'company_id',
        'document',
        'name',
        'address',
        'phone',
        'email',
        'user',
        'password',
        'role',
        'state',
        'deleted'
    ];

    protected $hidden = [
        'password'
    ];

    public $timestamps = false;

    public function scopeSeller($query){
        $query->where('role', 'seller');
        if (auth()->check() && auth()->user()->company_id) {
            $query->where('company_id', auth()->user()->company_id);
        }
        return $query;
    }

    public function scopeActive($query){
        return $query->where('deleted', 0);
    }

    public function hasRole(...$roles){
        $list = array_map('trim', $roles);
        return in_array($this->role, $list, true);
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class, 'seller_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
