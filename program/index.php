<?php
declare(strict_types=1);

ini_set('display_errors', '0');

if (PHP_SAPI === 'cli-server') {
    $staticFile = __DIR__ . (string)parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    if (is_file($staticFile)) {
        return false;
    }
}

require_once "db.php";
if (PHP_SAPI !== 'cli') {
    DB::init_session(900);
}
ob_start(function (string $html): string {
    $token = htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8');
    $field = '<input type="hidden" name="csrf_token" value="' . $token . '">';
    return (string)preg_replace('/(<form\b[^>]*\bmethod=["\']?post["\']?[^>]*>)/i', '$1' . $field, $html);
});
require_once "router.php";

$renderError = function (Throwable $e): void {
    if (!headers_sent()) {
        http_response_code($e instanceof PDOException ? 503 : 500);
    }
    $pageTitle    = 'Помилка системи';
    $errorTitle   = $e instanceof PDOException ? 'Помилка бази даних' : 'Помилка виконання запиту';
    $errorMessage = ($e instanceof RuntimeException)
        ? $e->getMessage()
        : 'Сервіс тимчасово недоступний або виникла внутрішня помилка. Перевірте з’єднання з БД.';
    include __DIR__ . '/views/error.php';
    exit;
};

set_exception_handler($renderError);

try {
    $path = (string)(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
    $router->route($_SERVER['REQUEST_METHOD'] ?? 'GET', $path);
} catch (Throwable $e) {
    $renderError($e);
}
