<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de organizador</title>
</head>
<body>
    <?php
        $conexao = mysqli_connect("localhost","root","","ceu_bd") 
            or die ("Erro ao conectar." . mysqli_connect_error() );
            
    $nome_completo = $_POST['nome_completo'];
    $cpf = $_POST['cpf'];
    $email = $_POST['email'];
    $senha = $_POST['senha'];
    $codigo_acesso = $_POST['codigo_acesso'];
    
    $sql = "INSERT INTO organizador (CPF, Nome, Email, Senha, Codigo) VALUES (
        '$cpf', '$nome_completo', '$email', '$senha', '$codigo_acesso')";

    mysqli_query($conexao, $sql) 
        or die ("Erro ao tentar cadastrar registro." . mysqli_error($conexao));
    
    header('Location: ../PaginasOrganizador/ContainerOrganizador.php?pagina=inicio');
    exit();
    ?>
    
</body>
</html>