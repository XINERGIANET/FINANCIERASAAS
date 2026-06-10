<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('contract_id')->index();
            $table->unsignedBigInteger('payment_method_id')->index();
            $table->decimal('total_amount', 10, 2);
            $table->string('voucher_path')->nullable();
            $table->date('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
