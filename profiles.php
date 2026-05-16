<?php
declare(strict_types=1);
require __DIR__ . '/api/_bootstrap.php';

header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

/* ─── Helpers ─── */
function ensureProfilesTable(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS profiles (
            id                 VARCHAR(60)  NOT NULL PRIMARY KEY,
            username           VARCHAR(100) NOT NULL,
            email              VARCHAR(255) NOT NULL UNIQUE,
            avatar_text        VARCHAR(10)  NULL,
            points             INT          NOT NULL DEFAULT 0,
            streak             INT          NOT NULL DEFAULT 0,
            books_opened_count INT          NOT NULL DEFAULT 0,
            favorites_count    INT          NOT NULL DEFAULT 0,
            created_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function get_all_profiles(PDO $pdo): array {
    $stmt = $pdo->query("SELECT id, username, email, avatar_text, points, streak,
                                books_opened_count, favorites_count, created_at, updated_at
                         FROM profiles");
    return $stmt->fetchAll();
}
function get_profile_by_id(PDO $pdo, string $id): ?array {
    $stmt = $pdo->prepare("SELECT * FROM profiles WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    return $stmt->fetch() ?: null;
}
function get_profile_by_username(PDO $pdo, string $username): ?array {
    $stmt = $pdo->prepare("SELECT * FROM profiles WHERE username = :u LIMIT 1");
    $stmt->execute([':u' => $username]);
    return $stmt->fetch() ?: null;
}
function get_profile_by_email(PDO $pdo, string $email): ?array {
    $stmt = $pdo->prepare("SELECT * FROM profiles WHERE email = :e LIMIT 1");
    $stmt->execute([':e' => $email]);
    return $stmt->fetch() ?: null;
}
function create_profile(PDO $pdo, array $d): array {
    $stmt = $pdo->prepare("
        INSERT INTO profiles (id, username, email, avatar_text, points, streak, books_opened_count, favorites_count)
        VALUES (:id, :username, :email, :avatar_text, :points, :streak, :books_opened_count, :favorites_count)
    ");
    $stmt->execute([
        ':id'                 => $d['id'],
        ':username'           => $d['username'],
        ':email'              => $d['email'],
        ':avatar_text'        => $d['avatar_text']        ?? null,
        ':points'             => $d['points']             ?? 0,
        ':streak'             => $d['streak']             ?? 0,
        ':books_opened_count' => $d['books_opened_count'] ?? 0,
        ':favorites_count'    => $d['favorites_count']    ?? 0,
    ]);
    return get_profile_by_id($pdo, $d['id']) ?? [];
}
function update_profile(PDO $pdo, string $id, array $fields): bool {
    $allowed = ['username','email','avatar_text','points','streak','books_opened_count','favorites_count'];
    $fields  = array_intersect_key($fields, array_flip($allowed));
    if (!$fields) return false;

    $sets   = implode(', ', array_map(fn($k) => "`$k` = :$k", array_keys($fields)));
    $params = array_combine(array_map(fn($k) => ":$k", array_keys($fields)), array_values($fields));
    $params[':id'] = $id;

    $stmt = $pdo->prepare("UPDATE profiles SET $sets WHERE id = :id");
    $stmt->execute($params);
    return true;
}
function get_leaderboard(PDO $pdo, int $limit = 10): array {
    $stmt = $pdo->prepare("SELECT id, username, avatar_text, points, streak
                           FROM profiles ORDER BY points DESC LIMIT :lim");
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/* ─── Router ─── */
try {
    $pdo    = db();
    ensureProfilesTable($pdo);

    $method = $_SERVER['REQUEST_METHOD'];
    $id     = $_GET['id']     ?? null;
    $action = $_GET['action'] ?? null;

    /* GET */
    if ($method === 'GET') {
        if ($action === 'leaderboard') {
            $data = get_leaderboard($pdo, (int)($_GET['limit'] ?? 10));
        } elseif ($id) {
            $data = get_profile_by_id($pdo, $id) ?? [];
        } elseif (isset($_GET['username'])) {
            $data = get_profile_by_username($pdo, $_GET['username']) ?? [];
        } elseif (isset($_GET['email'])) {
            $data = get_profile_by_email($pdo, $_GET['email']) ?? [];
        } else {
            $data = get_all_profiles($pdo);
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /* POST */
    if ($method === 'POST') {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        if (empty($body['id']) || empty($body['username']) || empty($body['email'])) {
            http_response_code(400);
            echo json_encode(['error' => 'id, username, and email are required']);
            exit;
        }
        $result = create_profile($pdo, $body);
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /* PATCH */
    if ($method === 'PATCH') {
        if (!$id) { http_response_code(400); echo json_encode(['error' => 'id is required']); exit; }
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        if ($action === 'add_points' && isset($body['amount'])) {
            $profile = get_profile_by_id($pdo, $id);
            if (!$profile) { http_response_code(404); echo json_encode(['error' => 'Not found']); exit; }
            update_profile($pdo, $id, ['points' => $profile['points'] + (int)$body['amount']]);

        } elseif ($action === 'increment_streak') {
            $profile = get_profile_by_id($pdo, $id);
            if (!$profile) { http_response_code(404); echo json_encode(['error' => 'Not found']); exit; }
            update_profile($pdo, $id, ['streak' => $profile['streak'] + 1]);

        } elseif ($action === 'increment_books_opened') {
            $profile = get_profile_by_id($pdo, $id);
            if (!$profile) { http_response_code(404); echo json_encode(['error' => 'Not found']); exit; }
            update_profile($pdo, $id, ['books_opened_count' => $profile['books_opened_count'] + 1]);

        } elseif ($action === 'increment_favorites') {
            $profile = get_profile_by_id($pdo, $id);
            if (!$profile) { http_response_code(404); echo json_encode(['error' => 'Not found']); exit; }
            update_profile($pdo, $id, ['favorites_count' => $profile['favorites_count'] + 1]);

        } else {
            update_profile($pdo, $id, $body);
        }

        echo json_encode(get_profile_by_id($pdo, $id) ?? [], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /* DELETE */
    if ($method === 'DELETE') {
        if (!$id) { http_response_code(400); echo json_encode(['error' => 'id is required']); exit; }
        $stmt = $pdo->prepare("DELETE FROM profiles WHERE id = :id");
        $stmt->execute([':id' => $id]);
        echo json_encode(['message' => 'Profile deleted']);
        exit;
    }

    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
