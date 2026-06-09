<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Http\Kernel');
$kernel->handle($request = Illuminate\Http\Request::capture());

$tables = DB::select('SHOW TABLES');
$tableKey = 'Tables_in_credyfacil';

foreach ($tables as $table) {
    $tableName = $table->$tableKey;
    $columns = DB::select("SHOW COLUMNS FROM `$tableName`");
    $colNames = array_map(function($c) { return $c->Field; }, $columns);
    echo "Table: $tableName\nColumns: " . implode(', ', $colNames) . "\n\n";
}
