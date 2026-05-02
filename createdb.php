<?php
$pdo = new PDO('mysql:host=127.0.0.1;port=3306', 'root', '');
$pdo->exec('CREATE DATABASE IF NOT EXISTS mini_crm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
echo "Done";
