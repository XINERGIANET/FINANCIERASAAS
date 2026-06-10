<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'ruc',
        'logo',
        'address',
        'city',
        'registry_info',
        'permissions',
        'status',
        'insurance_amount',
        'number_pagare',
        'client_type_config',
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function hasPermission($module)
    {
        if (is_null($this->permissions)) {
            return true;
        }
        $perms = is_array($this->permissions) ? $this->permissions : json_decode($this->permissions, true);
        return in_array($module, $perms ?? []);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }
}
