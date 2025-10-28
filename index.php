<?php
session_start();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>CEU - Carregando...</title>
    <script>
        // Verifica o banco de dados ao carregar
        fetch('./BancoDados/VerificarBancoDados.php?verificar=1')
            .then(response => response.text())
            .then(text => {
                // Remove qualquer HTML/texto antes do JSON e faz parse tolerante
                let jsonText = (text || '').trim();
                let jsonStart = jsonText.indexOf('{');
                if (jsonStart > 0) jsonText = jsonText.substring(jsonStart);
                try {
                    return JSON.parse(jsonText);
                } catch (e) {
                    console.warn('Resposta não-JSON (verificar):', text);
                    return { erro: true, mensagem: 'Resposta não-JSON do servidor', raw: (text || '').slice(0,500) };
                }
            })
            .then(data => {
                console.log('Verificação do BD:', data);
                
                // Se retornou erro de conexão
                if (data.erro) {
                    alert('❌ ERRO DE CONEXÃO\n\n' + data.mensagem + '\n\nPor favor:\n1. Abra o XAMPP Control Panel\n2. Inicie o MySQL\n3. Recarregue esta página');
                    return;
                }
                
                if (!data.bancoExiste) {
                    // Banco não existe
                    if (confirm('⚠️ BANCO DE DADOS NÃO ENCONTRADO!\n\nO banco de dados CEU_bd não existe.\n\nDeseja criar e importar o banco de dados agora?\n(Isso executará o arquivo BancodeDadosCEU.sql)')) {
                        atualizarBanco();
                    } else {
                        alert('❌ Não é possível continuar sem o banco de dados.');
                        redirecionarUsuario();
                    }
                } else if (!data.atualizado) {
                    // Banco existe mas está desatualizado
                    let mensagem = '⚠️ BANCO DE DADOS DESATUALIZADO!\n\n';
                    mensagem += 'Diferenças encontradas:\n';
                    data.diferencas.forEach(dif => {
                        mensagem += '• ' + dif + '\n';
                    });
                    mensagem += '\nDeseja atualizar o banco de dados agora?';
                    
                    if (confirm(mensagem)) {
                        atualizarBanco();
                    } else {
                        console.log('Usuário optou por não atualizar o banco.');
                        redirecionarUsuario();
                    }
                } else {
                    // Tudo OK
                    console.log('✅ Banco de dados está atualizado!');
                    redirecionarUsuario();
                }
            })
            .catch(error => {
                console.error('Erro ao verificar BD:', error);
                alert('❌ ERRO AO VERIFICAR BANCO DE DADOS\n\nNão foi possível conectar ao servidor.\n\nVerifique se:\n• XAMPP está rodando\n• MySQL está iniciado\n• Servidor Apache está rodando\n\nDetalhes: ' + error.message);
            });
        
        function atualizarBanco() {
            console.log('Atualizando banco de dados...');
            
            fetch('./BancoDados/VerificarBancoDados.php?atualizar=1')
                .then(response => response.text())
                .then(text => {
                    // Remove qualquer HTML/texto antes do JSON e faz parse tolerante
                    let jsonText = (text || '').trim();
                    let jsonStart = jsonText.indexOf('{');
                    if (jsonStart > 0) jsonText = jsonText.substring(jsonStart);
                    try {
                        return JSON.parse(jsonText);
                    } catch (e) {
                        console.warn('Resposta não-JSON (atualizar):', text);
                        return { sucesso: false, erro: 'Resposta não-JSON do servidor', raw: (text || '').slice(0,500) };
                    }
                })
                .then(data => {
                    console.log('Resultado da atualização:', data);
                    
                    // Após atualizar, verifica novamente se ficou tudo OK
                    fetch('./BancoDados/VerificarBancoDados.php?verificar=1')
                        .then(response => response.text())
                        .then(text => {
                            let jsonText = (text || '').trim();
                            let jsonStart = jsonText.indexOf('{');
                            if (jsonStart > 0) jsonText = jsonText.substring(jsonStart);
                            try {
                                return JSON.parse(jsonText);
                            } catch (e) {
                                console.warn('Resposta não-JSON (pós verificação):', text);
                                return { atualizado: false, diferencas: ['Resposta inválida do servidor após atualização'] };
                            }
                        })
                        .then(verificacao => {
                            console.log('Verificação pós-atualização:', verificacao);
                            
                            if (verificacao.atualizado) {
                                // Sucesso!
                                alert('✅ Banco de dados atualizado com sucesso!');
                                redirecionarUsuario();
                            } else {
                                // Ainda tem problemas
                                let mensagem = '⚠️ Atualização parcial realizada.\n\n';
                                
                                if (verificacao.diferencas && verificacao.diferencas.length > 0) {
                                    mensagem += 'Problemas que ainda existem:\n';
                                    verificacao.diferencas.forEach(dif => {
                                        mensagem += '• ' + dif + '\n';
                                    });
                                }
                                
                                if (data.erros && data.erros.length > 0) {
                                    mensagem += '\nErros durante a atualização:\n';
                                    data.erros.slice(0, 3).forEach(erro => {
                                        mensagem += '• ' + erro.substring(0, 100) + '\n';
                                    });
                                }
                                
                                alert(mensagem);
                                redirecionarUsuario();
                            }
                        });
                })
                .catch(error => {
                    console.error('Erro ao atualizar BD:', error);
                    alert('❌ Erro ao atualizar banco de dados.\n\n' + error.message);
                    redirecionarUsuario();
                });
        }
        
        function redirecionarUsuario() {
            <?php if (isset($_SESSION['cpf']) && !empty($_SESSION['cpf'])): ?>
                // Atualiza timestamp de atividade
                <?php $_SESSION['ultima_atividade'] = time(); ?>
                
                // Determina para onde redirecionar baseado no tipo de usuário
                <?php if (isset($_SESSION['organizador']) && $_SESSION['organizador'] == 1): ?>
                    window.location.href = './PaginasOrganizador/ContainerOrganizador.php?pagina=inicio';
                <?php else: ?>
                    window.location.href = './PaginasParticipante/ContainerParticipante.php?pagina=inicio';
                <?php endif; ?>
            <?php else: ?>
                // Se não está logado, redireciona para as páginas públicas
                window.location.href = './PaginasPublicas/ContainerPublico.php?pagina=inicio';
            <?php endif; ?>
        }
    </script>
</head>
<body>
    <div style="text-align: center; padding: 50px; font-family: Arial, sans-serif;">
        <h2>Verificando banco de dados...</h2>
        <p>Aguarde um momento.</p>
    </div>
</body>
</html>