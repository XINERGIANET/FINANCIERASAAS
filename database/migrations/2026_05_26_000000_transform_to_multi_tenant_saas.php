<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TransformToMultiTenantSaas extends Migration
{
    public function up()
    {
        // 1. Create companies table
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('ruc', 20)->nullable();
            $table->string('logo')->nullable();
            $table->string('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('registry_info')->nullable();
            $table->text('permissions')->nullable(); // stored as JSON
            $table->tinyInteger('status')->default(1); // 1 = Active, 0 = Inactive
            $table->decimal('insurance_amount', 10, 2)->default(0);
            $table->bigInteger('number_pagare')->default(0);
            $table->timestamps();
        });

        // 2. Insert default company "CREDYFACIL"
        $config = DB::table('config')->first();
        $insurance = $config ? $config->insurance : 0;
        $numberPagare = $config ? $config->number_pagare : 0;

        DB::table('companies')->insert([
            'id' => 1,
            'name' => 'CREDYFACIL SOLUCIONES S.A.C',
            'ruc' => '20615044394',
            'logo' => 'assets/images/logo.png',
            'address' => 'Zona Registral N° I – Sede Piura / Oficina Registral Piura',
            'city' => 'Piura',
            'registry_info' => 'Partida Electrónica N° 11325302 del Registro de Personas Jurídicas',
            'permissions' => json_encode(['sellers', 'contracts', 'cobranzas', 'egresos', 'caja_y_cuentas', 'traslados', 'metas']),
            'status' => 1,
            'insurance_amount' => $insurance,
            'number_pagare' => $numberPagare,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Add company_id column to tables
        $tablesToScope = [
            'users' => true, // nullable for superadmin
            'contracts' => false,
            'payment_methods' => false,
            'expenses' => false,
            'transfers' => false,
            'goals' => false,
            'account_movements' => false,
            'bitacoras' => false,
        ];

        foreach ($tablesToScope as $table => $nullable) {
            Schema::table($table, function (Blueprint $bp) use ($nullable) {
                if ($nullable) {
                    $bp->unsignedBigInteger('company_id')->nullable()->after('id');
                } else {
                    $bp->unsignedBigInteger('company_id')->default(1)->after('id');
                }
            });
        }

        // Set existing records to company_id = 1
        foreach (array_keys($tablesToScope) as $table) {
            DB::table($table)->update(['company_id' => 1]);
        }

        // Add foreign key constraints
        foreach ($tablesToScope as $table => $nullable) {
            Schema::table($table, function (Blueprint $bp) use ($table) {
                $bp->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            });
        }

        // 4. Create global superadmin user
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('seller','admin','viewer','credit','payments','superadmin') NOT NULL");

        DB::table('users')->insert([
            'company_id' => null,
            'document' => '00000000',
            'name' => 'SaaS Super Admin',
            'address' => 'SaaS Global Office',
            'phone' => '999999999',
            'email' => 'superadmin@xinergia.net',
            'user' => 'superadmin',
            'password' => Hash::make('superadmin_secret'),
            'role' => 'superadmin',
            'state' => 0,
            'deleted' => 0,
        ]);
    }

    public function down()
    {
        // Drop foreign keys first
        $tablesToScope = [
            'users',
            'contracts',
            'payment_methods',
            'expenses',
            'transfers',
            'goals',
            'account_movements',
            'bitacoras',
        ];

        foreach ($tablesToScope as $table) {
            Schema::table($table, function (Blueprint $bp) use ($table) {
                $bp->dropForeign([ 'company_id' ]);
                $bp->dropColumn('company_id');
            });
        }

        Schema::dropIfExists('companies');
    }
}
