<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if (($_SERVER['REQUEST_METHOD'] ?? 'POST') !== 'POST') {
    json_error('Method not allowed', 405);
}

$body = json_decode(file_get_contents('php://input'), true) ?? [];

$id            = trim((string)($body['id']            ?? ''));
$gutenberg_id  = isset($body['gutenberg_id']) ? (int)$body['gutenberg_id'] : null;
$title         = trim((string)($body['title']         ?? ''));
$author        = trim((string)($body['author']        ?? ''));
$genre         = trim((string)($body['genre']         ?? 'Fiction'));
$language_code = trim((string)($body['language_code'] ?? 'en'));
$cover_url     = trim((string)($body['cover_url']     ?? ''));
$description   = trim((string)($body['description']   ?? ''));
$content_html  = trim((string)($body['content_html']  ?? ''));

if (!$title)  json_error('Title is required', 400);
if (!$author) json_error('Author is required', 400);

$allowed_genres = ['Fiction', 'Science', 'History', 'Philosophy', 'Poetry', 'Other'];
if (!in_array($genre, $allowed_genres, true)) $genre = 'Fiction';
if (strlen($language_code) > 10) $language_code = 'en';
if ($id === '') $id = 'book_' . time() . '_' . rand(1000, 9999);

try {
    $pdo = db();

    // Ensure table exists (MySQL syntax)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS books (
            id             VARCHAR(60)      NOT NULL PRIMARY KEY,
            gutenberg_id   INT              NULL,
            title          VARCHAR(500)     NOT NULL,
            author         VARCHAR(300)     NOT NULL,
            genre          VARCHAR(50)      NOT NULL DEFAULT 'Fiction',
            language_code  VARCHAR(10)      NOT NULL DEFAULT 'en',
            cover_url      TEXT             NULL,
            description    TEXT             NULL,
            content_html   LONGTEXT         NULL,
            content_source VARCHAR(50)      NOT NULL DEFAULT 'manual',
            downloads      INT              NOT NULL DEFAULT 0,
            updated_at     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_at     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $stmt = $pdo->prepare("
        INSERT INTO books
            (id, gutenberg_id, title, author, genre, language_code,
             cover_url, description, content_html, content_source)
        VALUES
            (:id, :gid, :title, :author, :genre, :lang,
             :cover, :desc, :html, 'manual')
        ON DUPLICATE KEY UPDATE
            title          = VALUES(title),
            author         = VALUES(author),
            genre          = VALUES(genre),
            language_code  = VALUES(language_code),
            cover_url      = VALUES(cover_url),
            description    = VALUES(description),
            content_html   = VALUES(content_html),
            updated_at     = NOW()
    ");

    $stmt->execute([
        ':id'    => $id,
        ':gid'   => $gutenberg_id,
        ':title' => $title,
        ':author'=> $author,
        ':genre' => $genre,
        ':lang'  => $language_code,
        ':cover' => $cover_url  ?: null,
        ':desc'  => $description ?: null,
        ':html'  => $content_html ?: null,
    ]);

    json_out(['ok' => true, 'source' => 'mysql', 'id' => $id, 'message' => 'Book added successfully']);
} catch (Throwable $e) {
    json_error('Failed to add book', 500, ['details' => $e->getMessage()]);
}
