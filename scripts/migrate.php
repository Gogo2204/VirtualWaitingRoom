<?php

declare(strict_types=1);

$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

$host     = $_ENV['DB_HOST']     ?? 'mysql';
$port     = $_ENV['DB_PORT']     ?? '3306';
$dbName   = $_ENV['DB_NAME']     ?? 'queue_system';
$user     = $_ENV['DB_USER']     ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? '';

$dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    exit("Connection failed: " . $e->getMessage() . PHP_EOL);
}

$pdo->exec("
    CREATE TABLE IF NOT EXISTS migrations (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        filename   VARCHAR(255) NOT NULL UNIQUE,
        applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB
");

$applied = $pdo->query("SELECT filename FROM migrations")
               ->fetchAll(PDO::FETCH_COLUMN);
$applied = array_flip($applied);

$migrationsDir = dirname(__DIR__) . '/migrations';
$files = glob($migrationsDir . '/*.sql');
sort($files);

if (empty($files)) {
    echo "No migration files found in {$migrationsDir}." . PHP_EOL;
    exit(0);
}

$ran = 0;
foreach ($files as $file) {
    $filename = basename($file);

    if (isset($applied[$filename])) {
        echo "[skip]  {$filename}" . PHP_EOL;
        continue;
    }

    $sql = file_get_contents($file);
    if ($sql === false) {
        exit("Failed to read {$file}" . PHP_EOL);
    }

    try {
        $pdo->exec($sql);
        $stmt = $pdo->prepare("INSERT INTO migrations (filename) VALUES (?)");
        $stmt->execute([$filename]);
        echo "[ok]    {$filename}" . PHP_EOL;
        $ran++;
    } catch (PDOException $e) {
        exit("[error] {$filename}: " . $e->getMessage() . PHP_EOL);
    }
}

if ($ran === 0) {
    echo "Nothing to migrate." . PHP_EOL;
} else {
    echo "Done. {$ran} migration(s) applied." . PHP_EOL;
}