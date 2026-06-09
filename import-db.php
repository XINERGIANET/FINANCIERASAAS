<?php
try {
    $pdo = new PDO("mysql:host=127.0.0.1;port=3306", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS credyfacil CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE credyfacil");
    
    // Disable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    echo "Disabled foreign key checks.\n";
    
    $sqlFile = 'credyfacil_db.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL dump file not found: $sqlFile");
    }
    
    echo "Importing SQL dump line-by-line...\n";
    $handle = fopen($sqlFile, 'r');
    if ($handle) {
        $tempLine = '';
        $count = 0;
        $pdo->beginTransaction();
        while (($line = fgets($handle)) !== false) {
            $trimmed = trim($line);
            if ($trimmed === '' || strpos($trimmed, '--') === 0 || strpos($trimmed, '/*') === 0 || strpos($trimmed, '#') === 0) {
                continue;
            }
            
            $tempLine .= $line;
            if (substr($trimmed, -1) === ';') {
                try {
                    $pdo->exec($tempLine);
                } catch (Exception $e) {
                    echo "Query error: " . $e->getMessage() . "\nQuery: " . substr($tempLine, 0, 100) . "...\n";
                }
                $tempLine = '';
                $count++;
                if ($count % 500 === 0) {
                    $pdo->commit();
                    $pdo->beginTransaction();
                }
            }
        }
        $pdo->commit();
        fclose($handle);
        echo "Import complete. Executed $count queries.\n";
    }
    
    // Re-enable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    echo "Re-enabled foreign key checks.\n";
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Error: " . $e->getMessage() . "\n";
}
