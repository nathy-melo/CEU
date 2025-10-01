<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de organizador</title>
</head>

<body>
    <?php
    include_once('../BancoDados/conexao.php');

    $nome_completo = $_POST['nome_completo'];
    $cpf = $_POST['cpf'];
    $email = $_POST['email'];
    $senha = $_POST['senha'];
    $codigo_acesso = $_POST['codigo_acesso'];

    // Criptografa a senha antes de salvar
    $senhaCriptografada = password_hash($senha, PASSWORD_DEFAULT);

    // Verifica se o código existe e se está disponível (dados NULL) ou já está sendo usado
    $SQLVerificar = "SELECT CPF, Nome, Email FROM usuario WHERE Codigo = '$codigo_acesso'";
    $ResultadoVerificar = mysqli_query($conexao, $SQLVerificar);

    if (mysqli_num_rows($ResultadoVerificar) > 0) {
        // Se o código EXISTE, verifica se está disponível
        $usuario = mysqli_fetch_assoc($ResultadoVerificar);

        if (is_null($usuario['CPF']) || is_null($usuario['Nome']) || is_null($usuario['Email'])) {
            // Se os dados estão NULL, pode atualizar com as informações do usuário
            $sql = "UPDATE usuario SET CPF = '$cpf', Nome = '$nome_completo', Email = '$email', Senha = '$senhaCriptografada', Organizador = 1 WHERE Codigo = '$codigo_acesso'";

            mysqli_query($conexao, $sql)
                or die("Erro ao tentar atualizar registro." . mysqli_error($conexao));

            mysqli_close($conexao);
            header('Location: ContainerPublico.php?pagina=login');
            exit();
        } else {
            // Se os dados já estão preenchidos, código já está sendo usado
            mysqli_close($conexao);
            echo "<script>alert('Código de acesso já está sendo usado por outro organizador!'); history.back();</script>";
            exit();
        }
    } else {
        // Se o código NÃO existe, código inválido
        mysqli_close($conexao);
        echo "<script>alert('Código de acesso inválido!'); history.back();</script>";
        exit();
    }
    ?>

</body>

</html>