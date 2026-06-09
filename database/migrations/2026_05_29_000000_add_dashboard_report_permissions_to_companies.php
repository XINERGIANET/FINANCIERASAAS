<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddDashboardReportPermissionsToCompanies extends Migration
{
    private const REPORT_PERMISSIONS = [
        'reporte_cartera_dia',
        'reporte_cartera_morosa',
    ];

    public function up()
    {
        if (!Schema::hasTable('companies')) {
            return;
        }

        foreach (DB::table('companies')->get() as $company) {
            $permissions = json_decode($company->permissions, true);
            if (!is_array($permissions)) {
                $permissions = [];
            }

            foreach (self::REPORT_PERMISSIONS as $permission) {
                if (!in_array($permission, $permissions, true)) {
                    $permissions[] = $permission;
                }
            }

            DB::table('companies')->where('id', $company->id)->update([
                'permissions' => json_encode(array_values($permissions)),
                'updated_at' => now(),
            ]);
        }
    }

    public function down()
    {
        if (!Schema::hasTable('companies')) {
            return;
        }

        foreach (DB::table('companies')->get() as $company) {
            $permissions = json_decode($company->permissions, true);
            if (!is_array($permissions)) {
                continue;
            }

            $permissions = array_values(array_diff($permissions, self::REPORT_PERMISSIONS));

            DB::table('companies')->where('id', $company->id)->update([
                'permissions' => json_encode($permissions),
                'updated_at' => now(),
            ]);
        }
    }
}
