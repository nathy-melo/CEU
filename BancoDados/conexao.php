<?php
// Configurações simples para conectar com o banco de dados
$servidor = "localhost";     // Servidor do XAMPP
$usuario = "root";          // Usuário padrão do XAMPP
$senha = "";               // Senha padrão do XAMPP (vazia)
$banco = "CEU_bd";         // Nome do banco criado no SQL

// Conectar com o banco de dados
$conexao = mysqli_connect($servidor, $usuario, $senha, $banco);

// Verificar se a conexão funcionou
if (!$conexao) {
    die("Erro na conexão: " . mysqli_connect_error());
}

// Definir codificação para caracteres especiais
mysqli_set_charset($conexao, "utf8");
?>