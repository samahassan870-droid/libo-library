<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

try {
  $genre = isset($_GET['genre']) ? trim((string)$_GET['genre']) : '';
  $q     = isset($_GET['q'])     ? trim((string)$_GET['q'])     : '';
  $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 200;
  if ($limit < 1)   $limit = 1;
  if ($limit > 500) $limit = 500;

  $pdo    = db();
  $where  = [];
  $params = [];

  if ($genre !== '' && strtolower($genre) !== 'all') {
    $where[]          = 'genre = :genre';
    $params[':genre'] = $genre;
  }

  if ($q !== '') {
    $where[]    = '(title LIKE :q OR author LIKE :q)';
    $params[':q'] = '%' . $q . '%';
  }

  $sql = "SELECT id, gutenberg_id, title, author, genre, description,
                 cover_url, language_code, downloads, updated_at
          FROM books";
  if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
  $sql .= " ORDER BY updated_at DESC, title ASC LIMIT {$limit}";

  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $rows = $stmt->fetchAll();

  json_out(['ok' => true, 'source' => 'mysql', 'books' => $rows]);
} catch (Throwable $e) {
  json_error('Failed to list books', 500, ['details' => $e->getMessage()]);
}
