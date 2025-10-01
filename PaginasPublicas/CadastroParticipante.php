<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de participante</title>
</head>

<body>
    <?php
    include_once('../BancoDados/conexao.php');

    $nome_completo = $_POST['nome_completo'];
    $cpf = $_POST['cpf'];
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    // Verifica se o CPF já existe na tabela usuario
    $SQLVerificarCPF = "SELECT CPF FROM usuario WHERE CPF = '$cpf'";
    $ResultadoVerificarCPF = mysqli_query($conexao, $SQLVerificarCPF);

    if (mysqli_num_rows($ResultadoVerificarCPF) > 0) {
        // Se o CPF já existe, mostra erro
        mysqli_close($conexao);
        echo "<script>alert('CPF já cadastrado no sistema!'); history.back();</script>";
        exit();
    }

    // Verifica se o e-mail já existe na tabela usuario
    $SQLVerificarEmail = "SELECT Email FROM usuario WHERE Email = '$email'";
    $ResultadoVerificarEmail = mysqli_query($conexao, $SQLVerificarEmail);

    if (mysqli_num_rows($ResultadoVerificarEmail) > 0) {
        // Se o e-mail já existe, mostra erro
        mysqli_close($conexao);
        echo "<script>alert('E-mail já cadastrado no sistema!'); history.back();</script>";
        exit();
    }

    $senhaCriptografada = password_hash($senha, PASSWORD_DEFAULT);

    $sql = "INSERT INTO usuario (CPF, Nome, Email, Senha, Organizador) VALUES (
        '$cpf', '$nome_completo', '$email', '$senhaCriptografada', 0)";

    mysqli_query($conexao, $sql)
        or die("Erro ao tentar cadastrar registro." . mysqli_error($conexao));

    mysqli_close($conexao);
    header('Location: ContainerPublico.php?pagina=login');
    exit();
    ?>

</body>

</html>