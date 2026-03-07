<?php
declare(strict_types=1);
session_start();

$DB_HOST = "ferreteria-mysql-server.mysql.database.azure.com";
$DB_NAME = "ferreteria";

/*
 * En Azure MySQL Flexible Server normalmente se usa
 * el "Server admin login name" tal como aparece en el portal.
 * Verifica el nombre exacto en Overview.
 */
$DB_USER = "Pablo123";
$DB_PASS = "ProyectoSeguridad2026";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $mysqli = mysqli_init();

  

    

    $mysqli->real_connect(
        $DB_HOST,
        $DB_USER,
        $DB_PASS,
        $DB_NAME,
        3306,
        null,
        MYSQLI_CLIENT_SSL
    );

    $mysqli->set_charset("utf8mb4");

} catch (Throwable $e) {
    http_response_code(500);
    exit("Error de conexión a la BD: " . htmlspecialchars($e->getMessage()));
}

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function require_login(): void {
    if (empty($_SESSION['user'])) {
        header("Location: login.php");
        exit;
    }
}

function require_role(string $role): void {
    require_login();
    if (($_SESSION['user']['role'] ?? '') !== $role) {
        http_response_code(403);
        exit("No autorizado.");
    }
}