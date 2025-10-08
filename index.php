<?php
session_start();

// Se usuário já está logado, redireciona para a área apropriada
if (isset($_SESSION['cpf']) && !empty($_SESSION['cpf'])) {
    // Atualiza timestamp de atividade
    $_SESSION['ultima_atividade'] = time();
    
    // Determina para onde redirecionar baseado no tipo de usuário
    if (isset($_SESSION['organizador']) && $_SESSION['organizador'] == 1) {
        header('Location: ./PaginasOrganizador/ContainerOrganizador.php?pagina=inicio');
    } else {
        header('Location: ./PaginasParticipante/ContainerParticipante.php?pagina=inicio');
    }
    exit();
}

// Se não está logado, redireciona para as páginas públicas
header('Location: ./PaginasPublicas/ContainerPublico.php?pagina=inicio');
exit();
?>