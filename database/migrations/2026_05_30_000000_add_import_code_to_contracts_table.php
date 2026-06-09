<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddImportCodeToContractsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('contracts')) {
            return;
        }

        Schema::table('contracts', function (Blueprint $table) {
            if (!Schema::hasColumn('contracts', 'import_code')) {
                $table->string('import_code', 80)->nullable()->after('company_id');
                $table->unique(['company_id', 'import_code'], 'contracts_company_import_code_unique');
            }
        });
    }

    public function down()
    {
        if (!Schema::hasTable('contracts') || !Schema::hasColumn('contracts', 'import_code')) {
            return;
        }

        Schema::table('contracts', function (Blueprint $table) {
            $table->dropUnique('contracts_company_import_code_unique');
            $table->dropColumn('import_code');
        });
    }
}
