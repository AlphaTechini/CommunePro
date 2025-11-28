<?php
require_once __DIR__ . '/config.php';

function run_migrations() {
    $pdo = db();
    $migrations_dir = __DIR__ . '/migrations';
    $files = glob($migrations_dir . '/*.sql');
    sort($files);

    foreach ($files as $file) {
        $sql = file_get_contents($file);
        try {
            $pdo->exec($sql);
            echo "Migration successful: " . basename($file) . "\n";
        } catch (PDOException $e) {
            echo "Migration failed for " . basename($file) . ": " . $e->getMessage() . "\n";
        }
    }
}

run_migrations();

