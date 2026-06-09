<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('advisors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('name');
            $table->string('document', 8)->nullable();
            $table->string('phone', 15)->nullable();
            $table->string('email')->nullable();
            $table->tinyInteger('state')->default(0); // 0=activo, 1=inactivo
            $table->tinyInteger('deleted')->default(0); // 0=activo, 1=eliminado
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->unsignedBigInteger('advisor_id')->nullable()->after('seller_id');
        });
    }

    public function down()
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn('advisor_id');
        });
        Schema::dropIfExists('advisors');
    }
};
