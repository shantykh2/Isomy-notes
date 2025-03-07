<?php
$servername = "localhost";
$username = "root"; // Ganti dengan username MySQL Anda
$password = ""; // Jika MySQL Anda punya password, masukkan di sini
$dbname = "community_notes";

// Membuat koneksi
$conn = new mysqli($servername, $username, $password, $dbname);

// Memeriksa koneksi
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset ke utf8
$conn->set_charset("utf8");

// Function untuk mencegah SQL injection
function sanitize($conn, $input) {
    if (is_array($input)) {
        foreach ($input as $key => $value) {
            $input[$key] = sanitize($conn, $value);
        }
        return $input;
    }
    return $conn->real_escape_string($input);
}
?>