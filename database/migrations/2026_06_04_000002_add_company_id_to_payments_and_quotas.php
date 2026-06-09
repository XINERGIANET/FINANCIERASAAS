<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::table('quotas', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->after('id');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->after('id');
        });

        // Backfill quotas desde contracts
        DB::statement('
            UPDATE quotas
            SET company_id = (
                SELECT company_id FROM contracts WHERE contracts.id = quotas.contract_id LIMIT 1
            )
        ');

        // Backfill payments desde quotas -> contracts
        DB::statement('
            UPDATE payments
            SET company_id = (
                SELECT c.company_id
                FROM quotas q
                JOIN contracts c ON c.id = q.contract_id
                WHERE q.id = payments.quota_id
                LIMIT 1
            )
        ');
    }

    public function down()
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('company_id');
        });

        Schema::table('quotas', function (Blueprint $table) {
            $table->dropColumn('company_id');
        });
    }
};
