<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if (($_SERVER['REQUEST_METHOD'] ?? 'POST') !== 'POST') {
    json_error('Method not allowed', 405);
}

$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = trim((string)($body['action'] ?? ''));

switch ($action) {
    case 'register': handleRegister($body); break;
    case 'login':    handleLogin($body);    break;
    case 'me':       handleMe($body);       break;
    default:         json_error('Unknown action. Use: register, login, or me.', 400);
}

/* ── REGISTER ── */
function handleRegister(array $body): void {
    $username = trim((string)($body['username'] ?? ''));
    $email    = trim((string)($body['email']    ?? ''));
    $password = (string)($body['password'] ?? '');

    if (!$username) json_error('Username is required', 400);
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) json_error('Valid email is required', 400);
    if (strlen($password) < 6) json_error('Password must be at least 6 characters', 400);

    $hash = password_hash($password, PASSWORD_BCRYPT);

    try {
        $pdo = db();
        ensureUsersTable($pdo);

        $check = $pdo->prepare("SELECT id FROM libo_users WHERE email = :email LIMIT 1");
        $check->execute([':email' => $email]);
        if ($check->fetch()) json_error('Email already registered', 409);

        $stmt = $pdo->prepare("
            INSERT INTO libo_users (username, email, password_hash)
            VALUES (:username, :email, :hash)
        ");
        $stmt->execute([':username' => $username, ':email' => $email, ':hash' => $hash]);
        $newId = $pdo->lastInsertId();

        json_out(['ok' => true, 'user' => ['id' => $newId, 'username' => $username, 'email' => $email]]);
    } catch (Throwable $e) {
        json_error('Registration failed', 500, ['details' => $e->getMessage()]);
    }
}

/* ── LOGIN ── */
function handleLogin(array $body): void {
    $email    = trim((string)($body['email']    ?? ''));
    $password = (string)($body['password'] ?? '');

    if (!$email)    json_error('Email is required', 400);
    if (!$password) json_error('Password is required', 400);

    try {
        $pdo = db();
        ensureUsersTable($pdo);

        $stmt = $pdo->prepare("SELECT id, username, email, password_hash FROM libo_users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            json_error('Invalid email or password', 401);
        }

        json_out(['ok' => true, 'user' => ['id' => $user['id'], 'username' => $user['username'], 'email' => $user['email']]]);
    } catch (Throwable $e) {
        json_error('Login failed', 500, ['details' => $e->getMessage()]);
    }
}

/* ── ME ── */
function handleMe(array $body): void {
    $email = trim((string)($body['email'] ?? ''));
    if (!$email) json_error('Email is required', 400);

    try {
        $pdo = db();
        ensureUsersTable($pdo);

        $stmt = $pdo->prepare("SELECT id, username, email FROM libo_users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if (!$user) json_error('User not found', 404);
        json_out(['ok' => true, 'user' => $user]);
    } catch (Throwable $e) {
        json_error('Failed to fetch user', 500, ['details' => $e->getMessage()]);
    }
}

/* ── CREATE TABLE ── */
function ensureUsersTable(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS libo_users (
            id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            username     VARCHAR(100) NOT NULL,
            email        VARCHAR(255) NOT NULL UNIQUE,
            password_hash TEXT        NOT NULL,
            points       INT          NOT NULL DEFAULT 0,
            streak       INT          NOT NULL DEFAULT 0,
            created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}
