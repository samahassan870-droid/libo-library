<?php
declare(strict_types=1);
require __DIR__ . '/api/_bootstrap.php';

/**
 * Test endpoint - يتأكد ان الاتصال بـ MySQL شغال
 * افتحه في المتصفح: http://localhost/libo-liberary-complete/subabase.php
 */
try {
    $pdo  = db();
    $stmt = $pdo->query("SELECT id, gutenberg_id, title, author, genre, language_code, updated_at
                         FROM books ORDER BY updated_at DESC LIMIT 5");
    $rows = $stmt->fetchAll();

    json_out([
        'ok'      => true,
        'message' => 'MySQL connection success',
        'books'   => $rows,
    ]);
} catch (Throwable $e) {
    json_error('MySQL test failed', 500, ['details' => $e->getMessage()]);
}
