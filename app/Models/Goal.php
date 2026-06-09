<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToCompany;

class Goal extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'seller_id',
        'month',
        'year',
        'clients',
        'new_clients',
        'disbursement',
    ];

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }
}
