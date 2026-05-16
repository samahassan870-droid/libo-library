<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
  http_response_code(204);
  exit;
}

function local_config(): array {
  static $cfg = null;
  if (is_array($cfg)) return $cfg;

  $path = __DIR__ . '/_local_config.php';
  if (!is_file($path)) {
    $cfg = [];
    return $cfg;
  }

  $loaded = require $path;
  $cfg = is_array($loaded) ? $loaded : [];
  return $cfg;
}

function env(string $key, ?string $default = null): ?string {
  $v = getenv($key);
  if ($v === false || $v === '') {
    $cfg = local_config();
    if (array_key_exists($key, $cfg) && (string)$cfg[$key] !== '') {
      return (string)$cfg[$key];
    }
    return $default;
  }
  return $v;
}

function json_out(array $data, int $status = 200): void {
  http_response_code($status);
  echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}

function json_error(string $message, int $status = 400, array $extra = []): void {
  json_out(['ok' => false, 'error' => $message] + $extra, $status);
}

/**
 * MySQL PDO connection using XAMPP credentials from _local_config.php
 */
function db(): PDO {
  static $pdo = null;
  if ($pdo instanceof PDO) return $pdo;

  $host = env('DB_HOST', 'localhost');
  $port = env('DB_PORT', '3306');
  $name = env('DB_NAME', 'libo_library');
  $user = env('DB_USER', 'root');
  $pass = env('DB_PASSWORD', '');

  $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

  $pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8mb4'",
  ]);

  return $pdo;
}
