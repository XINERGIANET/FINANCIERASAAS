<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToCompany;

class ClientImage extends Model
{
    use HasFactory, BelongsToCompany;

    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'document',
        'group_name',
        'path',
        'deleted',
    ];

    public function scopeActive($query)
    {
        return $query->where('deleted', 0);
    }
}
