<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

try {
  $id  = isset($_GET['id'])  ? trim((string)$_GET['id'])  : '';
  $gid = isset($_GET['gid']) ? (int)$_GET['gid'] : 0;

  if ($id === '' && $gid <= 0) {
    json_error('Missing id or gid', 400);
  }

  $pdo = db();

  if ($id !== '') {
    $stmt = $pdo->prepare("
      SELECT id, gutenberg_id, title, author, genre, cover_url,
             language_code, content_html, content_source, updated_at
      FROM books WHERE id = :id LIMIT 1
    ");
    $stmt->execute([':id' => $id]);
  } else {
    $stmt = $pdo->prepare("
      SELECT id, gutenberg_id, title, author, genre, cover_url,
             language_code, content_html, content_source, updated_at
      FROM books WHERE gutenberg_id = :gid
      ORDER BY updated_at DESC LIMIT 1
    ");
    $stmt->execute([':gid' => $gid]);
  }

  $row = $stmt->fetch();
  if (!$row) json_error('Book not found', 404);

  json_out(['ok' => true, 'source' => 'mysql', 'book' => $row]);
} catch (Throwable $e) {
  json_error('Failed to fetch book', 500, ['details' => $e->getMessage()]);
}
