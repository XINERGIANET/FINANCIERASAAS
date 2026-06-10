<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentTransaction extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'contract_id',
        'payment_method_id',
        'total_amount',
        'voucher_path',
        'date',
    ];

    protected $dates = ['date'];

    public $timestamps = false;

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function payment_method()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function details()
    {
        return $this->hasMany(PaymentTransactionDetail::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
