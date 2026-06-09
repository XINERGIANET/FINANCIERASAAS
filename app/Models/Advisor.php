<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToCompany;

class Advisor extends Model
{
    use HasFactory, BelongsToCompany;

    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'name',
        'document',
        'phone',
        'email',
        'state',
        'deleted',
    ];

    public function scopeActive($query)
    {
        return $query->where('deleted', 0);
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }
}
