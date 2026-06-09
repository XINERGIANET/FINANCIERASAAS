<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddOperationsRoleToUsersTable extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('seller','admin','viewer','credit','payments','operations','superadmin') NOT NULL");
    }

    public function down()
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('seller','admin','viewer','credit','payments','superadmin') NOT NULL");
    }
}
