<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToCompany;

class PaymentMethod extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'name',
        'active'
    ];

    public $timestamps = false;

    public function scopeActive($query){
        return $query->where('active', 1)->whereIn('name', ['Efectivo', 'YAPE', 'Yape']);
    }

    public function sales(){
        return $this->hasMany(Sale::class);
    }

    /**
     * Pagos de gastos que usan este método
     */
    public function expensePayments()
    {
        return $this->hasMany(ExpensePayment::class, 'payment_method_id');
    }
}
