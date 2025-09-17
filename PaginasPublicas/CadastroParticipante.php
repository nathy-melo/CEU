<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de participante</title>
</head>
<body>
    <?php
        $conexao = mysqli_connect("localhost","root","","ceu_bd") 
            or die ("Erro ao conectar." . mysqli_connect_error() );
    $nome_completo = $_POST['nome_completo'];
    $cpf = $_POST['cpf'];
    $email = $_POST['email'];
    $senha = $_POST['senha'];
    
    $sql = "INSERT INTO participante (CPF, Nome, Email, Senha) VALUES (
        '$cpf', '$nome_completo', '$email', '$senha')";

    mysqli_query($conexao, $sql) 
        or die ("Erro ao tentar cadastrar registro." . mysqli_error($conexao));
    //Tem que fazr o link com a pagina container publico (acho???)
    ?>
    
</body>
</html>