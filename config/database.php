<?php

$host     = 'pg-19a87c6a-portal-si-esucri-pe4-full-slackers.e.aivencloud.com';
$dbname   = 'defaultdb';
$username = 'avnadmin';
$password = '';
$port     = '10910';

try {
    $pdo = new PDO(
        "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require",
        $username,
        $password
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    echo "Conexão com PostgreSQL estabelecida com sucesso!";

} catch (PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}