<?php
$host     = "localhost";
$username = "root";
$password = "";
$database = "it_store_db";

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("เชื่อมต่อฐานข้อมูลไม่ได้โว้ยยยยยย: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");
?>