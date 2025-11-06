<?php
// Apenas o conteúdo HTML da aba de Organização
// Este arquivo será carregado dinamicamente via AJAX
?>

<div class="secao-gerenciamento">
    <!-- Seção: Ações Rápidas -->
    <div>
        <h2 class="secao-titulo">Ações Rápidas</h2>
        <div class="grade-acoes-gerenciamento">
            <button class="botao botao-acao" id="btn-adicionar-organizacao">
                <span>Adicionar Colaborador</span>
                <img src="../Imagens/Adicionar_participante.svg" alt="Adicionar icon">
            </button>
            <button class="botao botao-acao" id="btn-enviar-mensagem-organizacao">
                <span>Enviar Mensagem</span>
                <img src="../Imagens/Email.svg" alt="Mensagem icon">
            </button>
        </div>
    </div>

    <div class="divisor-secao"></div>

    <!-- Seção: Lista da Organização -->
    <div>
        <div class="contador-participantes">
            <span id="total-organizacao">Total de membros: 0</span>
        </div>

        <div class="envoltorio-tabela">
            <table class="tabela-participantes">
                <thead>
                    <tr>
                        <th class="Titulo_Tabela" style="width: 15%;">Tipo</th>
                        <th class="Titulo_Tabela" style="width: 60%;">Dados do Membro</th>
                        <th class="Titulo_Tabela" style="width: 25%;">Certificado</th>
                    </tr>
                </thead>
                <tbody id="tbody-organizacao">
                    <tr>
                        <td colspan="3" style="text-align: center; padding: 30px; color: var(--botao);">
                            Carregando membros...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // Variáveis globais para a aba de organização
    if (typeof todosOrganizacao === 'undefined') {
        var todosOrganizacao = [];
    }

    // Função para carregar dados da organização
    function carregarOrganizacao() {
        if (typeof codEventoAtual === 'undefined' || !codEventoAtual) {
            console.error('❌ Código do evento não definido');
            return;
        }

        console.log('📊 Carregando organização do evento:', codEventoAtual);

        fetch(`GerenciarEvento.php?action=buscar_organizacao&cod_evento=${codEventoAtual}`)
            .then(response => response.json())
            .then(dados => {
                if (!dados.sucesso) {
                    console.error('❌ Erro ao carregar organização:', dados.erro);
                    alert('Erro ao carregar organização: ' + (dados.erro || 'Erro desconhecido'));
                    return;
                }

                todosOrganizacao = dados.membros || [];
                console.log(`✓ ${todosOrganizacao.length} membros carregados`);
                renderizarOrganizacao();
            })
            .catch(erro => {
                console.error('❌ Erro ao carregar organização:', erro);
                alert('Erro ao carregar organização: ' + erro.message);
            });
    }

    // Função para renderizar a tabela de organização
    function renderizarOrganizacao() {
        const tbody = document.getElementById('tbody-organizacao');
        const totalSpan = document.getElementById('total-organizacao');

        if (!tbody || !totalSpan) {
            console.error('❌ Elementos da tabela não encontrados');
            return;
        }

        totalSpan.textContent = `Total de membros: ${todosOrganizacao.length}`;

        if (todosOrganizacao.length === 0) {
            tbody.innerHTML = `
            <tr>
                <td colspan="3" style="text-align: center; padding: 30px; color: var(--botao);">
                    Nenhum membro encontrado
                </td>
            </tr>
        `;
            return;
        }

        tbody.innerHTML = todosOrganizacao.map(membro => {
            const tipoBadge = membro.tipo === 'Organizador' ?
                '<span class="tipo-membro tipo-organizador">Organizador</span>' :
                '<span class="tipo-membro tipo-colaborador">Colaborador</span>';

            const statusCertificado = membro.certificado_emitido ?
                `<span class="emblema-status confirmado">
                 <img src="../Imagens/Certo.svg" alt="Emitido" style="width: 18px; height: 18px;">
                 Emitido
               </span>` :
                `<button class="emblema-status pendente" onclick="emitirCertificadoOrganizacao('${membro.cpf}')">
                 <img src="../Imagens/Certificado.svg" alt="Emitir" style="width: 18px; height: 18px;">
                 Emitir
               </button>`;

            return `
            <tr>
                <td style="text-align: center;">
                    ${tipoBadge}
                </td>
                <td class="coluna-dados">
                    <p><strong>Nome:</strong> ${membro.nome || '-'}</p>
                    <p><strong>CPF:</strong> ${membro.cpf || '-'}</p>
                    <p><strong>E-mail:</strong> ${membro.email || '-'}</p>
                    <p><strong>RA:</strong> ${membro.ra || '-'}</p>
                </td>
                <td style="text-align: center;">
                    ${statusCertificado}
                </td>
            </tr>
        `;
        }).join('');

        console.log('✓ Tabela de organização renderizada');
    }

    // Função para emitir certificado
    function emitirCertificadoOrganizacao(cpf) {
        if (!confirm('Deseja emitir certificado para este colaborador?')) {
            return;
        }

        console.log('📜 Emitindo certificado para:', cpf);

        fetch('GerenciarEvento.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'emitir_certificado_organizacao',
                    cod_evento: codEventoAtual,
                    cpf: cpf
                })
            })
            .then(response => response.json())
            .then(dados => {
                if (dados.sucesso) {
                    alert('Certificado emitido com sucesso!');
                    carregarOrganizacao(); // Recarrega a lista
                } else {
                    alert('Erro ao emitir certificado: ' + (dados.erro || 'Erro desconhecido'));
                }
            })
            .catch(erro => {
                console.error('❌ Erro ao emitir certificado:', erro);
                alert('Erro ao emitir certificado');
            });
    }

    // Inicializa quando o conteúdo for carregado
    console.log('✓ ConteudoOrganizacao.php carregado');
    carregarOrganizacao();
</script>