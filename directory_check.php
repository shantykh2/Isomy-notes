<?php
// File sederhana untuk memeriksa dan membuat direktori upload

// Set error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Direktori yang akan diperiksa/dibuat
$directories = [
    'images',
    'images/avatars',
    'uploads'
];

echo "<h1>Pemeriksaan Direktori</h1>";
echo "<pre>";

foreach ($directories as $dir) {
    echo "Memeriksa direktori: $dir ... ";
    
    if (!file_exists($dir)) {
        echo "tidak ditemukan.\n";
        echo "Mencoba membuat direktori $dir ... ";
        
        if (mkdir($dir, 0777, true)) {
            echo "berhasil dibuat!\n";
            echo "Mengatur izin direktori... ";
            
            if (chmod($dir, 0777)) {
                echo "berhasil!\n";
            } else {
                echo "gagal mengatur izin.\n";
            }
        } else {
            echo "gagal! Error: " . error_get_last()['message'] . "\n";
        }
    } else {
        echo "ditemukan.\n";
        echo "Memeriksa izin... ";
        
        if (is_writable($dir)) {
            echo "direktori dapat ditulis.\n";
        } else {
            echo "direktori tidak dapat ditulis. Mencoba mengatur izin... ";
            
            if (chmod($dir, 0777)) {
                echo "berhasil!\n";
            } else {
                echo "gagal mengatur izin.\n";
            }
        }
    }
    
    echo "\n";
}

// Cek konfigurasi PHP untuk upload
echo "Konfigurasi PHP Upload:\n";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "post_max_size: " . ini_get('post_max_size') . "\n";
echo "max_file_uploads: " . ini_get('max_file_uploads') . "\n";

// Cek informasi server
echo "\nInformasi Server:\n";
echo "Server OS: " . PHP_OS . "\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "\n";

echo "</pre>";
?>