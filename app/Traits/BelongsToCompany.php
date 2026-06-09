<?php

namespace App\Traits;

use App\Models\Company;
use App\Scopes\CompanyScope;

trait BelongsToCompany
{
    public static function bootBelongsToCompany()
    {
        static::addGlobalScope(new CompanyScope);

        static::creating(function ($model) {
            if (auth()->check() && !$model->company_id) {
                $user = auth()->user();
                if ($user->company_id !== null) {
                    $model->company_id = $user->company_id;
                }
            }
        });
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
