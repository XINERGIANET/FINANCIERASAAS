<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToCompany;

class Bitacora extends Model
{
    use HasFactory, BelongsToCompany;

    protected $guarded = [];

     protected $dates = [
        'date'
    ];

    public function usuario()
    {
        return $this->belongsTo( User::class, 'user_id');
    }

}