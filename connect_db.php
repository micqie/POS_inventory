<?php
// Enable error visibility for easier debugging in dev.
error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = "localhost";
$db_name = "pos_db";
$username = "root";
$password = "";

try {
    $conn = mysqli_connect($host, $username, $password, $db_name);
    mysqli_set_charset($conn, "utf8");
} catch (mysqli_sql_exception $e) {
    die("Database connection error: " . $e->getMessage());
}
?>
