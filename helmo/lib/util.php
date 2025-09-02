<?php
require_once __DIR__ . '/csrf.php';

function redirect(string $path): void {
    header('Location: ' . $path);
    exit;
}

function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function require_post_csrf(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !CSRF::validate($_POST['csrf_token'] ?? '')) {
        http_response_code(400);
        echo 'Bad Request';
        exit;
    }
}

function export_csv(string $filename, array $headers, array $rows): void {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename=' . $filename);
    $out = fopen('php://output', 'w');
    fputcsv($out, $headers);
    foreach ($rows as $r) { fputcsv($out, $r); }
    fclose($out);
    exit;
}
