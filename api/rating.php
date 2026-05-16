<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

/* ── GET: average rating ── */
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    $book_id = isset($_GET['book_id']) ? trim((string)$_GET['book_id']) : '';
    if (!$book_id) json_error('Missing book_id', 400);

    try {
        $pdo = db();
        ensureRatingsTable($pdo);

        $stmt = $pdo->prepare("
            SELECT ROUND(AVG(stars), 2) AS avg_stars, COUNT(*) AS total
            FROM book_ratings WHERE book_id = :book_id
        ");
        $stmt->execute([':book_id' => $book_id]);
        $row = $stmt->fetch();
        json_out(['ok' => true, 'avg' => $row['avg_stars'] ?? 0, 'total' => (int)$row['total']]);
    } catch (Throwable $e) {
        json_error('Failed to get rating', 500, ['details' => $e->getMessage()]);
    }
}

/* ── POST: upsert rating ── */
$body      = json_decode(file_get_contents('php://input'), true) ?? [];
$book_id   = trim((string)($body['book_id']    ?? ''));
$stars     = (int)($body['stars']              ?? 0);
$user_email = trim((string)($body['user_email'] ?? 'anonymous'));

if (!$book_id)              json_error('Missing book_id', 400);
if ($stars < 1 || $stars > 5) json_error('Stars must be 1-5', 400);

try {
    $pdo = db();
    ensureRatingsTable($pdo);

    $stmt = $pdo->prepare("
        INSERT INTO book_ratings (book_id, user_email, stars)
        VALUES (:book_id, :user_email, :stars)
        ON DUPLICATE KEY UPDATE stars = VALUES(stars)
    ");
    $stmt->execute([':book_id' => $book_id, ':user_email' => $user_email, ':stars' => $stars]);
    json_out(['ok' => true, 'message' => 'Rating saved']);
} catch (Throwable $e) {
    json_error('Failed to save rating', 500, ['details' => $e->getMessage()]);
}

function ensureRatingsTable(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS book_ratings (
            id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            book_id    VARCHAR(60)  NOT NULL,
            user_email VARCHAR(255) NOT NULL DEFAULT 'anonymous',
            stars      TINYINT      NOT NULL CHECK (stars BETWEEN 1 AND 5),
            created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_book_user (book_id, user_email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}
