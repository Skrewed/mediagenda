<?php

    $host_bd = "localhost";
    $login_bd = "root";
    $password_bd = "";
    $nome_bd = "labdbprog2";
    $port = 3306;

    $conexao_bd = mysqli_connect($host_bd, $login_bd, $password_bd,$nome_bd, $port);

    if (!$conexao_bd) {
        die("Falha na conexão com o banco de dados. Tente novamente mais tarde.");
    }
?>