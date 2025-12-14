<?php
$host = "localhost";
$db_name = "pos_db";
$username = "root";
$password = "";


$conn = mysqli_connect($host, $username, $password, $db_name);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8");
echo "Connected successfully";

?>
