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
        'contract_format',
        'contract_leader_name',
        'contract_leader_charge',
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
            // Si las permissions son null (empresa recién creada sin config explícita),
            // cuota_catorcenal, seller_contract_delete y contract_show_leader arrancan desactivadas por defecto
            if (in_array($module, ['quota_catorcenal', 'seller_contract_delete', 'contract_show_leader'])) {
                return false;
            }
            return true;
        }
        $perms = is_array($this->permissions) ? $this->permissions : json_decode($this->permissions, true);
        
        // Si la empresa ya tenía un array guardado previamente donde todavía no existían
        // las llaves 'quota_semanal' o 'quota_quincenal', asumimos que sí las tenía permitidas
        // a menos que se hayan guardado alguna vez configuraciones de cuotas.
        if (in_array($module, ['quota_semanal', 'quota_quincenal'])) {
            $hasAnyQuotaConfigured = in_array('quota_semanal', $perms ?? [], true)
                || in_array('quota_quincenal', $perms ?? [], true)
                || in_array('quota_catorcenal', $perms ?? [], true);

            if (!$hasAnyQuotaConfigured) {
                return true; // retrocompatibilidad para empresas existentes
            }
        }

        return in_array($module, $perms ?? []);
    }

    public function allowsSellerContractDeletion(): bool
    {
        $perms = is_array($this->permissions) ? $this->permissions : json_decode($this->permissions, true);

        return in_array('seller_contract_delete', $perms ?? [], true);
    }

    public function allowsSemanalQuota(): bool
    {
        return $this->hasPermission('quota_semanal');
    }

    public function allowsQuincenalQuota(): bool
    {
        return $this->hasPermission('quota_quincenal');
    }

    public function allowsCatorcenalQuota(): bool
    {
        return $this->hasPermission('quota_catorcenal');
    }

    public function hasContractLeader(): bool
    {
        return $this->hasPermission('contract_show_leader') && !empty(trim($this->contract_leader_name ?? ''));
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
