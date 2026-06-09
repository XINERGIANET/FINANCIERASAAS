<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class SeedSuperadminUser extends Migration
{
    private const SUPERADMIN_LOGIN = 'superadmin';
    private const SUPERADMIN_PASSWORD = 'Xinergia@2026';

    private const DEFAULT_MODULES = [
        'sellers',
        'contracts',
        'cobranzas',
        'egresos',
        'caja_y_cuentas',
        'traslados',
        'metas',
    ];

    public function up()
    {
        if (!Schema::hasTable('companies') || !Schema::hasTable('users')) {
            return;
        }

        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('seller','admin','viewer','credit','payments','operations','superadmin') NOT NULL");

        if (!DB::table('companies')->where('id', 1)->exists()) {
            $config = Schema::hasTable('config') ? DB::table('config')->first() : null;

            DB::table('companies')->insert([
                'id' => 1,
                'name' => 'CREDYFACIL SOLUCIONES S.A.C',
                'ruc' => '20615044394',
                'logo' => 'assets/images/logo.png',
                'address' => 'Zona Registral N° I – Sede Piura / Oficina Registral Piura',
                'city' => 'Piura',
                'registry_info' => 'Partida Electrónica N° 11325302 del Registro de Personas Jurídicas',
                'permissions' => json_encode(self::DEFAULT_MODULES),
                'status' => 1,
                'insurance_amount' => $config->insurance ?? 0,
                'number_pagare' => $config->number_pagare ?? 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $tenantTables = [
            'contracts',
            'payment_methods',
            'expenses',
            'transfers',
            'goals',
            'account_movements',
            'bitacoras',
        ];

        foreach ($tenantTables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'company_id')) {
                DB::table($table)->whereNull('company_id')->update(['company_id' => 1]);
            }
        }

        if (Schema::hasColumn('users', 'company_id')) {
            DB::table('users')
                ->where('role', '!=', 'superadmin')
                ->whereNull('company_id')
                ->update(['company_id' => 1]);
        }

        $superadminPayload = [
            'company_id' => null,
            'document' => '00000000',
            'name' => 'Super Administrador SaaS',
            'address' => 'Xinergia',
            'phone' => '999999999',
            'email' => 'superadmin@xinergia.net',
            'user' => self::SUPERADMIN_LOGIN,
            'password' => Hash::make(self::SUPERADMIN_PASSWORD),
            'role' => 'superadmin',
            'state' => 0,
            'deleted' => 0,
        ];

        $existing = DB::table('users')->where('user', self::SUPERADMIN_LOGIN)->first();

        if ($existing) {
            DB::table('users')->where('id', $existing->id)->update($superadminPayload);
        } else {
            DB::table('users')->insert($superadminPayload);
        }
    }

    public function down()
    {
        if (Schema::hasTable('users')) {
            DB::table('users')
                ->where('user', self::SUPERADMIN_LOGIN)
                ->where('role', 'superadmin')
                ->delete();
        }
    }
}
