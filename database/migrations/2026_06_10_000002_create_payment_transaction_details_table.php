<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transaction_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('payment_transaction_id')->index();
            $table->unsignedBigInteger('payment_id')->index();
            $table->unsignedBigInteger('quota_id')->index();
            $table->decimal('quota_balance_before', 10, 2);
            $table->decimal('amount_applied', 10, 2);
            $table->decimal('quota_balance_after', 10, 2);
            $table->unsignedInteger('sequence');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transaction_details');
    }
};
