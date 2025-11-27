<?php
// Apenas o conteúdo HTML da aba de Organização
// Este arquivo será carregado dinamicamente via AJAX
?>

<div class="secao-gerenciamento">
    <!-- Seção Superior: Busca e Ações Rápidas Integradas -->
    <div class="secao-superior-compacta">
        <!-- Barra de Pesquisa no Topo -->
        <div class="barra-pesquisa-wrapper-compacta">
            <div class="barra-pesquisa-container">
                <div class="barra-pesquisa">
                    <div class="campo-pesquisa-wrapper">
                        <input class="campo-pesquisa" type="text" id="busca-organizacao" name="busca_organizacao" placeholder="Procure por nome, RA ou CPF" autocomplete="off" />
                        <button class="botao-pesquisa" id="btn-buscar-org" aria-label="Procurar">
                            <div class="icone-pesquisa">
                                <img src="../Imagens/lupa.png" alt="Lupa">
                            </div>
                        </button>
                    </div>
                </div>
                <button class="botao botao-filtrar" id="btn-filtrar-organizadores">
                    <span>Filtrar</span>
                    <img src="../Imagens/filtro.png" alt="Filtro">
                </button>
            </div>
        </div>

        <!-- Ações Rápidas -->
        <div class="acoes-rapidas-wrapper">
            <h2 class="secao-titulo-compacta">Ações Rápidas</h2>
            <div class="grade-acoes-gerenciamento">
                <button class="botao botao-acao" id="btn-adicionar-organizacao">
                    <span>Adicionar Organizador</span>
                    <img src="../Imagens/Adicionar_participante.svg" alt="Adicionar icon">
                </button>
                <button class="botao botao-acao" id="btn-enviar-mensagem-organizacao">
                    <span>Enviar Mensagem</span>
                    <img src="../Imagens/Email.svg" alt="Mensagem icon">
                </button>
            </div>
        </div>
    </div>

    <!-- Seção: Ações em Massa -->
    <div class="secao-acoes-massa-compacta">
        <h2 class="secao-titulo-compacta">Ações em Massa</h2>
        <div class="acoes-em-massa" id="acoes-em-massa-org">
            <button class="botao botao-em-massa botao-branco" id="botao-toggle-selecao-org">
                <span id="texto-toggle-selecao-org">Selecionar Todos</span>
                <img src="../Imagens/Grupo_de_pessoas.svg" alt="">
            </button>
            <button class="botao botao-em-massa botao-verde" id="btn-confirmar-presencas-massa-org">
                <span>Confirmar Presenças</span>
                <img src="../Imagens/Certo.svg" alt="">
            </button>
            <button class="botao botao-em-massa botao-azul" id="btn-emitir-certificados-massa-org">
                <span>Emitir Certificados</span>
                <img src="../Imagens/Certificado.svg" alt="">
            </button>
            <button class="botao botao-em-massa botao-vermelho" id="btn-excluir-membros-massa">
                <span>Excluir Membros</span>
                <img src="../Imagens/Excluir.svg" alt="">
            </button>
        </div>
    </div>

    <!-- Seção: Lista da Organização -->
    <div>
        <div class="contador-participantes">
            <span id="total-organizacao">Total de membros: 0</span>
            
            <!-- Navegação de Páginas -->
            <div id="navegacao-paginas-tabela-org" style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap; justify-content: center;"></div>
            
            <div style="display: flex; gap: 0.5rem; align-items: center;">
                <label for="select-linhas-por-pagina-org" style="font-size: 0.9rem;">Linhas por página:</label>
                <select id="select-linhas-por-pagina-org" class="botao" style="padding: 0.4rem 0.8rem; font-size: 0.9rem; cursor: pointer; border: 1px solid var(--caixas); border-radius: 0.3rem; background: var(--botao); color: var(--texto);">
                    <option value="30" selected>30</option>
                    <option value="40">40</option>
                    <option value="50">50</option>
                    <option value="60">60</option>
                    <option value="80">80</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>

        <div class="envoltorio-tabela table-wrapper">
            <table class="tabela-participantes" id="tabela-organizadores">
                <thead>
                    <tr>
                        <th class="Titulo_Tabela">Selecionar</th>
                        <th class="Titulo_Tabela">Dados do Membro</th>
                        <th class="Titulo_Tabela">Modificar</th>
                        <th class="Titulo_Tabela">Status</th>
                    </tr>
                </thead>
                <tbody id="tbody-organizacao">
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 30px; color: var(--botao);">
                            Carregando membros...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Container para cards mobile -->
        <div class="mobile-cards-container" id="mobile-cards-organizacao">
            <p style="text-align:center; padding:30px; color:var(--botao);">Carregando membros...</p>
        </div>
    </div>
</div>

<!-- Modal Adicionar Organizador -->
<div class="modal-overlay" id="modalAdicionarColaborador" onclick="if(event.target.id === 'modalAdicionarColaborador') fecharModalAdicionarColaborador();">
    <div class="modal-editar">
        <div class="modal-header">
            <h2>Adicionar Organizador</h2>
            <button class="btn-fechar-modal" onclick="fecharModalAdicionarColaborador();">&times;</button>
        </div>
        <form id="formAdicionarColaborador" onsubmit="salvarNovoColaborador(event);">
            <div class="form-group">
                <label for="add-cpf-colab">CPF*</label>
                <input type="text" id="add-cpf-colab" maxlength="14" placeholder="000.000.000-00" required onblur="verificarCPFColaborador()">
                <small id="msg-cpf-colab-existente" style="color: #666; display: none; margin-top: 4px;">✓ Usuário cadastrado no sistema</small>
            </div>

            <div class="form-group">
                <label for="add-nome-colab">Nome Completo*</label>
                <input type="text" id="add-nome-colab" required>
            </div>

            <div class="form-group">
                <label for="add-email-colab">E-mail*</label>
                <input type="email" id="add-email-colab" required>
            </div>

            <div class="form-group">
                <label for="add-ra-colab">Registro Acadêmico (RA)</label>
                <input type="text" id="add-ra-colab" maxlength="7">
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-modal btn-cancelar" onclick="fecharModalAdicionarColaborador();">Cancelar</button>
                <button type="submit" class="btn-modal btn-salvar">Adicionar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Enviar Mensagem -->
<div class="modal-overlay" id="modalEnviarMensagemOrg" onclick="if(event.target.id === 'modalEnviarMensagemOrg') fecharModalMensagemOrganizacao();">
    <div class="modal-editar">
        <div class="modal-header">
            <h2>Enviar Mensagem aos Membros da Organização</h2>
            <button class="btn-fechar-modal" onclick="fecharModalMensagemOrganizacao();">&times;</button>
        </div>
        <form id="formEnviarMensagemOrg" onsubmit="enviarMensagemOrganizacao(event);">
            <div class="form-group">
                <label for="msg-titulo-org">Título da Notificação*</label>
                <input type="text" id="msg-titulo-org" maxlength="100" required>
            </div>

            <div class="form-group">
                <label for="msg-conteudo-org">Mensagem*</label>
                <textarea id="msg-conteudo-org" rows="6" style="width: 100%; padding: 12px; border: 1px solid var(--azul-escuro); border-radius: 8px; font-size: 15px; font-family: inherit; resize: vertical;" maxlength="500" required></textarea>
                <small style="color: #666;">Máximo 500 caracteres</small>
            </div>

            <div class="form-group">
                <label for="msg-todos-org" style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" id="msg-todos-org" checked style="width: 20px; height: 20px;">
                    <span>Enviar para todos os membros</span>
                </label>
                <small id="info-destinatarios-org" style="color: #666; display: block; margin-top: 8px;">A mensagem será enviada para todos os membros da organização</small>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-modal btn-cancelar" onclick="fecharModalMensagemOrganizacao();">Cancelar</button>
                <button type="submit" class="btn-modal btn-salvar">Enviar Notificação</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Editar Membro -->
<div id="modalEditarMembro" class="modal-overlay" onclick="if(event.target.id === 'modalEditarMembro') fecharModalEditarMembro();">
    <div class="modal-content" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h3>Editar Membro da Organização</h3>
            <button class="botao-fechar" onclick="fecharModalEditarMembro()">×</button>
        </div>
        <form id="formEditarMembro" onsubmit="salvarEdicaoMembro(event);">
            <input type="hidden" id="edit-cpf-org">

            <div class="campo-formulario">
                <label for="edit-cpf-display-org">CPF</label>
                <input type="text" id="edit-cpf-display-org" disabled style="background-color: #f5f5f5; cursor: not-allowed;">
            </div>

            <div class="campo-formulario">
                <label for="edit-nome-org">Nome Completo</label>
                <input type="text" id="edit-nome-org" required>
            </div>

            <div class="campo-formulario">
                <label for="edit-email-org">Email</label>
                <input type="email" id="edit-email-org" required>
            </div>

            <div class="campo-formulario">
                <label for="edit-ra-org">RA</label>
                <input type="text" id="edit-ra-org" required>
            </div>

            <div class="botoes-modal">
                <button type="button" class="botao-secundario" onclick="fecharModalEditarMembro()">Cancelar</button>
                <button type="submit" class="botao-primario">Salvar Alterações</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Variáveis globais para a aba de organização
    if (typeof todosOrganizacao === 'undefined') {
        var todosOrganizacao = [];
    }
    if (typeof membrosSelecionados === 'undefined') {
        var membrosSelecionados = new Set();
    }

    // Função para carregar dados da organização
    function carregarOrganizacao() {
        if (typeof codEventoAtual === 'undefined' || !codEventoAtual) {
            return;
        }

        fetch(`GerenciarEvento.php?action=buscar_organizacao&cod_evento=${codEventoAtual}`)
            .then(response => response.json())
            .then(dados => {
                if (!dados.sucesso) {
                    alert('Erro ao carregar organização: ' + (dados.erro || 'Erro desconhecido'));
                    return;
                }

                todosOrganizacao = dados.membros || [];
                renderizarOrganizacao();
            })
            .catch(erro => {
                alert('Erro ao carregar organização: ' + erro.message);
            });
    }

    // Função para renderizar a tabela de organização
    function renderizarOrganizacao() {
        const tbody = document.getElementById('tbody-organizacao');
        const totalSpan = document.getElementById('total-organizacao');
        const mobileContainer = document.getElementById('mobile-cards-organizacao');
        const isMobile = window.matchMedia('(max-width: 768px)').matches;

        if (!tbody || !totalSpan || !mobileContainer) {
            return;
        }

        totalSpan.textContent = `Total de membros: ${todosOrganizacao.length}`;

        // LIMPA ambos os containers sempre para evitar duplicação
        tbody.innerHTML = '';
        mobileContainer.innerHTML = '';

        if (todosOrganizacao.length === 0) {
            if (isMobile) {
                mobileContainer.innerHTML = '<p style="text-align:center; padding:30px; color:var(--botao);">Nenhum membro encontrado</p>';
            } else {
                tbody.innerHTML = `
                <tr>
                    <td colspan="5" style="text-align: center; padding: 30px; color: var(--botao);">
                        Nenhum membro encontrado
                    </td>
                </tr>
            `;
            }
            window.__lastOrganizacaoIsMobile = isMobile;
            return;
        }

        if (isMobile) {
            // Renderizar cards mobile
            let cardsHTML = '';
            todosOrganizacao.forEach((membro, i) => {
                const statusPresenca = membro.presenca_confirmada ? 'Confirmada' : 'Não Confirmada';
                const statusCertificado = membro.certificado_emitido ? 'Enviado' : 'Não enviado';

                let btnAcaoPrincipal = '';
                if (membro.certificado_emitido) {
                    btnAcaoPrincipal = '';
                } else if (membro.presenca_confirmada) {
                    btnAcaoPrincipal = `<button class="btn-small botao-azul" onclick="emitirCertificadoOrganizacao('${membro.cpf}')"><img src="../Imagens/Certificado.svg" alt=""> Emitir Certificado</button>`;
                } else {
                    btnAcaoPrincipal = `<button class="btn-small botao-verde" onclick="confirmarPresencaOrg('${membro.cpf}')"><img src="../Imagens/Certo.svg" alt=""> Confirmar Presença</button>`;
                }

                let btnExcluir = '';
                if (membro.certificado_emitido) {
                    btnExcluir = `<button class="btn-small botao-cinza" disabled title="Certificado do membro já foi emitido"><img src="../Imagens/Excluir.svg" alt=""> Excluir</button>`;
                } else {
                    btnExcluir = `<button class="btn-small botao-vermelho" onclick="excluirMembroOrg('${membro.cpf}')"><img src="../Imagens/Excluir.svg" alt=""> Excluir</button>`;
                }

                const btnCertificado = membro.certificado_emitido ?
                    `<button class="btn-small botao-neutro" onclick="verificarCertificadoOrg('${membro.cod_verificacao || ''}')"><img src="../Imagens/Certificado.svg" alt=""> Ver Certificado</button>` : '';

                cardsHTML += `
                    <div class="mobile-card" data-cpf="${membro.cpf}">
                        <div class="mobile-card-row">
                            <span class="mobile-card-label">Selecionar</span>
                            <span class="mobile-card-value">
                                <input type="checkbox" class="checkbox-selecionar-org" id="org-mobile-${i}" value="${membro.cpf}">
                            </span>
                        </div>
                        <div class="mobile-card-row">
                            <span class="mobile-card-label">Nome</span>
                            <span class="mobile-card-value"><strong>${membro.nome || '-'}</strong></span>
                        </div>
                        <div class="mobile-card-row">
                            <span class="mobile-card-label">E-mail</span>
                            <span class="mobile-card-value">${membro.email || '-'}</span>
                        </div>
                        <div class="mobile-card-row">
                            <span class="mobile-card-label">RA</span>
                            <span class="mobile-card-value">${membro.ra || '-'}</span>
                        </div>
                        <div class="mobile-card-row">
                            <span class="mobile-card-label">Data de Inscrição</span>
                            <span class="mobile-card-value">${membro.data_inscricao || '-'}</span>
                        </div>
                        <div class="mobile-card-row">
                            <span class="mobile-card-label">Presença</span>
                            <span class="mobile-card-value">${statusPresenca}</span>
                        </div>
                        <div class="mobile-card-row">
                            <span class="mobile-card-label">Certificado</span>
                            <span class="mobile-card-value">${statusCertificado}</span>
                        </div>
                        <div class="mobile-card-actions">
                            ${btnAcaoPrincipal}
                            ${btnExcluir}
                            ${btnCertificado}
                        </div>
                    </div>
                `;
            });
            mobileContainer.innerHTML = cardsHTML;
        } else {
            // Renderizar tabela desktop
            tbody.innerHTML = todosOrganizacao.map((membro, i) => {
                const isChecked = membrosSelecionados.has(membro.cpf);
                const rowClass = isChecked ? 'linha-selecionada' : '';

                const statusPresenca = membro.presenca_confirmada ?
                    '<span class="emblema-status confirmado">Confirmada <img src="../Imagens/Certo.svg" alt=""></span>' :
                    '<span class="emblema-status negado">Não Confirmada <img src="../Imagens/Errado.svg" alt=""></span>';

                const statusCertificado = membro.certificado_emitido ?
                    '<span class="emblema-status confirmado">Enviado <img src="../Imagens/Certo.svg" alt=""></span>' :
                    '<span class="emblema-status negado">Não enviado <img src="../Imagens/Errado.svg" alt=""></span>';

                const btnCertificado = membro.certificado_emitido ?
                    '<button class="botao botao-acao-tabela botao-neutro" onclick="verificarCertificadoOrg(\'' + (membro.cod_verificacao || '') + '\')"><span>Verificar Certificado</span><img src="../Imagens/Certificado.svg" alt=""></button>' :
                    '';

                let btnAcaoPrincipal = '';
                if (membro.certificado_emitido) {
                    btnAcaoPrincipal = '';
                } else if (membro.presenca_confirmada) {
                    btnAcaoPrincipal = `<button class="botao botao-acao-tabela botao-azul" onclick="emitirCertificadoOrganizacao('${membro.cpf}')"><span>Emitir Certificado</span><img src="../Imagens/Certificado.svg" alt=""></button>`;
                } else {
                    btnAcaoPrincipal = `<button class="botao botao-acao-tabela botao-verde" onclick="confirmarPresencaOrg('${membro.cpf}')"><span>Confirmar Presença</span><img src="../Imagens/Certo.svg" alt=""></button>`;
                }

                let btnExcluir = '';
                if (membro.certificado_emitido) {
                    btnExcluir = `<button class="botao botao-acao-tabela botao-cinza" disabled title="Certificado do membro já foi emitido. Não é possível excluir o membro.">
                        <span>Excluir Membro</span><img src="../Imagens/Excluir.svg" alt="">
                    </button>`;
                } else {
                    btnExcluir = `<button class="botao botao-acao-tabela botao-vermelho" onclick="excluirMembroOrg('${membro.cpf}')">
                        <span>Excluir Membro</span><img src="../Imagens/Excluir.svg" alt="">
                    </button>`;
                }

                return `
                <tr class="${rowClass}" data-cpf="${membro.cpf}" data-tipo="${membro.tipo || ''}">
                    <td class="coluna-selecionar" data-label="Selecionar">
                        <input type="checkbox" class="checkbox-selecionar-org" id="org-${i}" value="${membro.cpf}" ${isChecked ? 'checked' : ''}>
                    </td>
                    <td class="coluna-dados" data-label="Dados do Membro">
                        <p><strong>Nome:</strong> ${membro.nome || '-'}</p>
                        <p><strong>E-mail:</strong> ${membro.email || '-'}</p>
                        <p><strong>Registro Acadêmico:</strong> ${membro.ra || '-'}</p>
                        <p><strong>Data de Inscrição:</strong> ${membro.data_inscricao || '-'}</p>
                        <p><strong>Tipo:</strong> ${membro.tipo || '-'}</p>
                    </td>
                    <td class="coluna-modificar" data-label="Modificar">
                        <div class="grupo-acoes">
                            ${btnAcaoPrincipal}
                            ${btnExcluir}
                        </div>
                    </td>
                    <td class="coluna-status" data-label="Status">
                        <div class="grupo-status">
                            <div class="linha-status"><span>Inscrição:</span><span class="emblema-status confirmado">Confirmada <img src="../Imagens/Certo.svg" alt=""></span></div>
                            <div class="linha-status"><span>Presença:</span>${statusPresenca}</div>
                            <div class="linha-status"><span>Certificado:</span>${statusCertificado}</div>
                            ${btnCertificado}
                        </div>
                    </td>
                </tr>
            `;
            }).join('');
        }

        // Armazena estado para detecção de resize
        window.__lastOrganizacaoIsMobile = isMobile;

        // Re-inicializa eventos após re-renderizar (quando muda entre mobile/desktop)
        inicializarEventosOrganizacao();
        
        // Inicializar paginação de tabelas (apenas para desktop)
        if (!isMobile && typeof window.inicializarPaginacaoTabela === 'function') {
            window.inicializarPaginacaoTabela('tabela-organizadores', {
                linhasPorPagina: 30,
                maximoLinhas: 100,
                selectId: 'select-linhas-por-pagina-org'
            });
        }

        // Inicializar filtro de organizadores APÓS garantir que tudo está pronto
        if (!isMobile) {
            // Usar setTimeout para garantir que a tabela está completamente renderizada
            setTimeout(function() {
                if (typeof window.inicializarFiltroOrganizadores === 'function') {
                    window.inicializarFiltroOrganizadores('tabela-organizadores');
                    
                    // Forçar ordenação A-Z imediatamente após inicializar
                    setTimeout(function() {
                        if (window.filtroOrganizadoresConfig && window.filtroOrganizadoresConfig.tabelaId) {
                            if (typeof aplicarOrdenacaoInicialOrg === 'function') {
                                aplicarOrdenacaoInicialOrg();
                            }
                        }
                    }, 150);
                }
            }, 100);
        }
    }

    // Função para emitir certificado
    function emitirCertificadoOrganizacao(cpf) {
        if (!confirm('Deseja emitir certificado para este organizador?')) {
            return;
        }

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
                    const mensagemErro = dados.detalhe ? `${dados.erro}: ${dados.detalhe}` : (dados.erro || 'Erro desconhecido');
                    alert('Erro ao emitir certificado: ' + mensagemErro);
                    console.error('Detalhes do erro:', dados);
                }
            })
            .catch(erro => {
                alert('Erro ao emitir certificado');
            });
    }

    // Função para verificar certificado
    function verificarCertificadoOrg(codigo) {
        if (!codigo) {
            alert('Código de verificação não disponível');
            return;
        }
        // Abre a página de visualização do certificado em uma nova aba
        const url = `ContainerOrganizador.php?pagina=visualizarCertificado&codigo=${encodeURIComponent(codigo)}`;
        window.open(url, '_blank');
    }

    // Função para confirmar presença
    function confirmarPresencaOrg(cpf) {
        if (!confirm('Deseja confirmar a presença deste organizador?')) {
            return;
        }

        fetch('GerenciarEvento.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'confirmar_presenca_organizacao',
                    cod_evento: codEventoAtual,
                    cpf: cpf
                })
            })
            .then(response => response.json())
            .then(dados => {
                if (dados.sucesso) {
                    alert('Presença confirmada com sucesso!');
                    carregarOrganizacao();
                } else {
                    const mensagemErro = dados.detalhe ? `${dados.erro}: ${dados.detalhe}` : (dados.erro || 'Erro desconhecido');
                    alert('Erro ao confirmar presença: ' + mensagemErro);
                    console.error('Detalhes do erro:', dados);
                }
            })
            .catch(erro => {
                alert('Erro ao confirmar presença');
            });
    }

    // ===== AÇÕES RÁPIDAS =====

    // Funções dos modais - declaradas globalmente primeiro
    function abrirModalAdicionarColaborador() {
        const modal = document.getElementById('modalAdicionarColaborador');
        if (modal) {
            // Move modal para fora do conteudo-dinamico
            const modaisGlobais = document.getElementById('modais-globais');
            if (modaisGlobais && modal.parentElement.id !== 'modais-globais') {
                modaisGlobais.appendChild(modal);
            }
            
            document.body.style.overflow = 'hidden';
            modal.classList.add('ativo');
        }
    }

    function fecharModalAdicionarColaborador() {
        document.getElementById('modalAdicionarColaborador').classList.remove('ativo');
        document.getElementById('formAdicionarColaborador').reset();
        const msgCPF = document.getElementById('msg-cpf-colab-existente');
        if (msgCPF) msgCPF.style.display = 'none';
        document.getElementById('add-nome-colab').disabled = false;
        document.getElementById('add-email-colab').disabled = false;
        document.getElementById('add-ra-colab').disabled = false;
        document.body.style.overflow = '';
    }

    function abrirModalMensagemOrganizacao() {
        const modal = document.getElementById('modalEnviarMensagemOrg');
        if (modal) {
            // Move modal para fora do conteudo-dinamico
            const modaisGlobais = document.getElementById('modais-globais');
            if (modaisGlobais && modal.parentElement.id !== 'modais-globais') {
                modaisGlobais.appendChild(modal);
            }
            
            document.body.style.overflow = 'hidden';
            modal.classList.add('ativo');

            // Atualizar informação de destinatários
            const infoDestinatarios = document.getElementById('info-destinatarios-org');
            const checkTodos = document.getElementById('msg-todos-org');
            
            if (membrosSelecionados.size > 0) {
                if (checkTodos) checkTodos.checked = false;
                const texto = membrosSelecionados.size === 1 ? 'membro selecionado' : 'membros selecionados';
                if (infoDestinatarios) {
                    infoDestinatarios.textContent = `Enviando para ${membrosSelecionados.size} ${texto}`;
                }
            } else {
                if (checkTodos) checkTodos.checked = true;
                if (infoDestinatarios && typeof todosOrganizacao !== 'undefined') {
                    const total = todosOrganizacao.length;
                    const texto = total === 1 ? 'membro' : 'membros';
                    infoDestinatarios.textContent = `A mensagem será enviada para todos os membros da organização (${total} ${texto})`;
                }
            }
        }
    }

    function fecharModalMensagemOrganizacao() {
        document.getElementById('modalEnviarMensagemOrg').classList.remove('ativo');
        document.getElementById('formEnviarMensagemOrg').reset();
        document.body.style.overflow = '';
    }

    // Inicializa as ações rápidas
    function inicializarAcoesRapidas() {
        const btnAdicionarOrg = document.getElementById('btn-adicionar-organizacao');
        const btnEnviarMsgOrg = document.getElementById('btn-enviar-mensagem-organizacao');
        const cpfColabInput = document.getElementById('add-cpf-colab');

        // Botão adicionar colaborador
        if (btnAdicionarOrg && !btnAdicionarOrg.dataset.bound) {
            btnAdicionarOrg.dataset.bound = '1';
            btnAdicionarOrg.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                abrirModalAdicionarColaborador();
            });
        }

        // Botão enviar mensagem
        if (btnEnviarMsgOrg && !btnEnviarMsgOrg.dataset.bound) {
            btnEnviarMsgOrg.dataset.bound = '1';
            btnEnviarMsgOrg.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                abrirModalMensagemOrganizacao();
            });
        }

        // Máscara de CPF
        if (cpfColabInput && !cpfColabInput.dataset.bound) {
            cpfColabInput.dataset.bound = '1';
            cpfColabInput.addEventListener('input', function(e) {
                let valor = e.target.value.replace(/\D/g, '');
                if (valor.length <= 11) {
                    valor = valor.replace(/(\d{3})(\d)/, '$1.$2');
                    valor = valor.replace(/(\d{3})(\d)/, '$1.$2');
                    valor = valor.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
                    e.target.value = valor;
                }
            });
        }

        // Listener para o checkbox de enviar para todos
        const checkTodosOrg = document.getElementById('msg-todos-org');
        if (checkTodosOrg && !checkTodosOrg.dataset.bound) {
            checkTodosOrg.dataset.bound = '1';
            checkTodosOrg.addEventListener('change', function(e) {
                const infoDestinatarios = document.getElementById('info-destinatarios-org');
                if (!e.target.checked && membrosSelecionados.size > 0) {
                    const texto = membrosSelecionados.size === 1 ? 'membro selecionado' : 'membros selecionados';
                    infoDestinatarios.textContent = `Enviando para ${membrosSelecionados.size} ${texto}`;
                } else if (e.target.checked && todosOrganizacao) {
                    const total = todosOrganizacao.length;
                    const texto = total === 1 ? 'membro' : 'membros';
                    infoDestinatarios.textContent = `A mensagem será enviada para todos os membros da organização (${total} ${texto})`;
                }
            });
        }
    }

    // Inicializa quando o conteúdo for carregado
    setTimeout(function() {
        inicializarAcoesRapidas();
        carregarOrganizacao();
    }, 100);

    // ===== FUNÇÕES DE VERIFICAÇÃO E SALVAMENTO =====

    async function verificarCPFColaborador() {
        const cpfInput = document.getElementById('add-cpf-colab');
        const cpf = cpfInput.value.replace(/\D/g, '');

        if (cpf.length !== 11) return;

        try {
            const response = await fetch(`GerenciarEvento.php?action=verificar_cpf&cpf=${cpf}`);
            const data = await response.json();

            if (data.existe) {
                document.getElementById('msg-cpf-colab-existente').style.display = 'block';
                document.getElementById('add-nome-colab').value = data.nome || '';
                document.getElementById('add-email-colab').value = data.email || '';
                document.getElementById('add-ra-colab').value = data.ra || '';
                document.getElementById('add-nome-colab').disabled = true;
                document.getElementById('add-email-colab').disabled = true;
                document.getElementById('add-ra-colab').disabled = true;
            } else {
                document.getElementById('msg-cpf-colab-existente').style.display = 'none';
                document.getElementById('add-nome-colab').disabled = false;
                document.getElementById('add-email-colab').disabled = false;
                document.getElementById('add-ra-colab').disabled = false;
            }
        } catch (error) {
            console.error('Erro ao verificar CPF:', error);
        }
    }

    async function salvarNovoColaborador(event) {
        event.preventDefault();

        const cpf = document.getElementById('add-cpf-colab').value.replace(/\D/g, '');
        const nome = document.getElementById('add-nome-colab').value;
        const email = document.getElementById('add-email-colab').value;
        const ra = document.getElementById('add-ra-colab').value;

        try {
            const response = await fetch('GerenciarEvento.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'adicionar_colaborador',
                    cod_evento: codEventoAtual,
                    cpf: cpf,
                    nome: nome,
                    email: email,
                    ra: ra
                })
            });

            const data = await response.json();

            if (data.sucesso) {
                alert('Organizador adicionado com sucesso!');
                fecharModalAdicionarColaborador();
                carregarOrganizacao();
            } else {
                alert('Erro ao adicionar organizador: ' + (data.erro || 'Erro desconhecido'));
            }
        } catch (error) {
            console.error('Erro:', error);
            alert('Erro ao adicionar organizador');
        }
    }

    async function enviarMensagemOrganizacao(event) {
        event.preventDefault();

        const titulo = document.getElementById('msg-titulo-org').value;
        const conteudo = document.getElementById('msg-conteudo-org').value;
        const enviarTodos = document.getElementById('msg-todos-org').checked;

        const destinatarios = enviarTodos ?
            todosOrganizacao.map(m => m.cpf) :
            Array.from(membrosSelecionados);

        if (destinatarios.length === 0) {
            alert('Selecione pelo menos um membro ou marque "Enviar para todos"');
            return;
        }

        try {
            const response = await fetch('GerenciarEvento.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'enviar_notificacao',
                    cod_evento: codEventoAtual,
                    titulo: titulo,
                    conteudo: conteudo,
                    destinatarios: destinatarios
                })
            });

            const data = await response.json();

            if (data.sucesso) {
                alert('Mensagem enviada com sucesso!');
                fecharModalMensagemOrganizacao();
            } else {
                alert('Erro ao enviar mensagem: ' + (data.erro || 'Erro desconhecido'));
            }
        } catch (error) {
            console.error('Erro:', error);
            alert('Erro ao enviar mensagem');
        }
    }

    // ===== FUNÇÕES DE EDIÇÃO E EXCLUSÃO =====

    function abrirModalEditarMembro(cpf) {
        const membro = todosOrganizacao.find(m => m.cpf === cpf);
        if (!membro) return;

        document.getElementById('edit-cpf-org').value = cpf;
        document.getElementById('edit-nome-org').value = membro.nome || '';
        document.getElementById('edit-email-org').value = membro.email || '';
        document.getElementById('edit-ra-org').value = membro.ra || '';
        document.getElementById('edit-cpf-display-org').value = cpf;

        const modal = document.getElementById('modalEditarMembro');
        const modaisGlobais = document.getElementById('modais-globais');
        if (modal && modaisGlobais && modal.parentElement.id !== 'modais-globais') {
            modaisGlobais.appendChild(modal);
        }
        document.body.style.overflow = 'hidden';
        modal.classList.add('ativo');
    }

    function fecharModalEditarMembro() {
        document.getElementById('modalEditarMembro').classList.remove('ativo');
        document.getElementById('formEditarMembro').reset();
        document.body.style.overflow = '';
    }

    async function salvarEdicaoMembro(event) {
        event.preventDefault();

        const cpf = document.getElementById('edit-cpf-org').value;
        const nome = document.getElementById('edit-nome-org').value;
        const email = document.getElementById('edit-email-org').value;
        const ra = document.getElementById('edit-ra-org').value;

        try {
            const response = await fetch('GerenciarEvento.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'editar_membro',
                    cod_evento: codEventoAtual,
                    cpf: cpf,
                    nome: nome,
                    email: email,
                    ra: ra
                })
            });

            const data = await response.json();

            if (data.sucesso) {
                alert('Dados atualizados com sucesso!');
                fecharModalEditarMembro();
                carregarOrganizacao();
            } else {
                alert('Erro ao atualizar: ' + (data.erro || 'Erro desconhecido'));
            }
        } catch (error) {
            alert('Erro ao atualizar dados');
        }
    }

    async function excluirMembro(cpf) {
        if (!confirm('Deseja excluir este membro da organização?')) {
            return;
        }

        try {
            const response = await fetch('GerenciarEvento.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'excluir_membro',
                    cod_evento: codEventoAtual,
                    cpf: cpf
                })
            });

            const data = await response.json();

            if (data.sucesso) {
                alert('Membro excluído com sucesso!');
                carregarOrganizacao();
            } else {
                alert('Erro ao excluir: ' + (data.erro || 'Erro desconhecido'));
            }
        } catch (error) {
            alert('Erro ao excluir membro');
        }
    }

    // Alias para usar o mesmo nome em onclick de botões
    if (typeof excluirMembroOrg === 'undefined') {
        window.excluirMembroOrg = excluirMembro;
    } else {
        // Se já existe, apenas reatribui a função
        window.excluirMembroOrg = excluirMembro;
    }

    // ===== FUNÇÕES DE AÇÕES EM MASSA =====

    async function confirmarPresencasEmMassaOrg() {
        if (todosOrganizacao.length === 0) {
            alert('Não há membros na organização');
            return;
        }

        if (membrosSelecionados.size === 0) {
            alert('Selecione pelo menos um membro');
            return;
        }

        if (!confirm(`Confirmar presença de ${membrosSelecionados.size} membro(s)?`)) {
            return;
        }

        let confirmados = 0;
        let erros = 0;

        for (const cpf of membrosSelecionados) {
            try {
                const response = await fetch('GerenciarEvento.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'confirmar_presenca_organizacao',
                        cod_evento: codEventoAtual,
                        cpf: cpf
                    })
                });

                const data = await response.json();
                if (data.sucesso) {
                    confirmados++;
                } else {
                    erros++;
                }
            } catch (error) {
                erros++;
            }
        }

        alert(`Operação concluída!\nPresenças confirmadas: ${confirmados}\nErros: ${erros}`);
        membrosSelecionados.clear();
        carregarOrganizacao();
    }

    async function emitirCertificadosEmMassaOrg() {
        if (todosOrganizacao.length === 0) {
            alert('Não há membros na organização');
            return;
        }

        if (membrosSelecionados.size === 0) {
            alert('Selecione pelo menos um membro');
            return;
        }

        // Filtrar apenas membros com presença confirmada
        const membrosComPresenca = Array.from(membrosSelecionados).filter(cpf => {
            const membro = todosOrganizacao.find(m => m.cpf === cpf);
            return membro && membro.presenca_confirmada;
        });

        if (membrosComPresenca.length === 0) {
            alert('Nenhum dos membros selecionados possui presença confirmada.\nConfirme a presença antes de emitir certificados.');
            return;
        }

        const ignorados = membrosSelecionados.size - membrosComPresenca.length;
        let mensagemConfirmacao = `Emitir certificado para ${membrosComPresenca.length} membro(s)?`;
        if (ignorados > 0) {
            mensagemConfirmacao += `\n\nObs: ${ignorados} membro(s) será(ão) ignorado(s) por não ter presença confirmada.`;
        }

        if (!confirm(mensagemConfirmacao)) {
            return;
        }

        let emitidos = 0;
        let erros = 0;

        for (const cpf of membrosComPresenca) {
            try {
                const response = await fetch('GerenciarEvento.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'emitir_certificado_organizacao',
                        cod_evento: codEventoAtual,
                        cpf: cpf
                    })
                });

                const data = await response.json();
                if (data.sucesso) {
                    emitidos++;
                } else {
                    erros++;
                }
            } catch (error) {
                erros++;
            }
        }

        alert(`Operação concluída!\nCertificados emitidos: ${emitidos}\nErros: ${erros}`);
        membrosSelecionados.clear();
        carregarOrganizacao();
    }

    async function excluirMembrosEmMassa() {
        if (todosOrganizacao.length === 0) {
            alert('Não há membros na organização');
            return;
        }

        if (membrosSelecionados.size === 0) {
            alert('Selecione pelo menos um membro');
            return;
        }

        if (!confirm(`ATENÇÃO: Excluir ${membrosSelecionados.size} membro(s)?\n\nEsta ação não pode ser desfeita!`)) {
            return;
        }

        let excluidos = 0;
        let erros = 0;

        for (const cpf of membrosSelecionados) {
            try {
                const response = await fetch('GerenciarEvento.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'excluir_membro',
                        cod_evento: codEventoAtual,
                        cpf: cpf
                    })
                });

                const data = await response.json();
                if (data.sucesso) {
                    excluidos++;
                } else {
                    erros++;
                }
            } catch (error) {
                erros++;
            }
        }

        alert(`Operação concluída!\nExcluídos: ${excluidos}\nErros: ${erros}`);
        membrosSelecionados.clear();
        carregarOrganizacao();
    }

    // ===== FUNÇÕES DE EVENTOS E PESQUISA =====

    function inicializarEventosOrganizacao() {
        // Eventos de checkbox (funciona para tabela E cards mobile)
        if (!window.__orgCheckboxBound) {
            window.__orgCheckboxBound = true;
            document.addEventListener('change', function(e) {
                if (e.target.classList && e.target.classList.contains('checkbox-selecionar-org')) {
                    const container = e.target.closest('tr') || e.target.closest('.mobile-card');
                    if (container) {
                        container.classList.toggle('linha-selecionada', e.target.checked);
                    }
                    if (e.target.checked) {
                        membrosSelecionados.add(e.target.value);
                    } else {
                        membrosSelecionados.delete(e.target.value);
                    }
                    atualizarVisibilidadeBotoesAcaoOrg();
                    atualizarTextoBotaoToggleOrg();
                }
            });
        }

        // Botão toggle selecionar todos
        const btnToggle = document.getElementById('botao-toggle-selecao-org');
        if (btnToggle && !btnToggle.dataset.bound) {
            btnToggle.dataset.bound = '1';
            btnToggle.addEventListener('click', function() {
                if (todosOrganizacao.length === 0) {
                    alert('Não há membros na organização');
                    return;
                }

                const todosSelecionados = membrosSelecionados.size === todosOrganizacao.length && membrosSelecionados.size > 0;

                if (todosSelecionados) {
                    document.querySelectorAll('.checkbox-selecionar-org').forEach(cb => {
                        cb.checked = false;
                        const container = cb.closest('tr') || cb.closest('.mobile-card');
                        if (container) container.classList.remove('linha-selecionada');
                        membrosSelecionados.delete(cb.value);
                    });
                } else {
                    membrosSelecionados.clear();
                    document.querySelectorAll('.checkbox-selecionar-org').forEach(cb => {
                        cb.checked = true;
                        const container = cb.closest('tr') || cb.closest('.mobile-card');
                        if (container) container.classList.add('linha-selecionada');
                        membrosSelecionados.add(cb.value);
                    });
                }

                atualizarVisibilidadeBotoesAcaoOrg();
                atualizarTextoBotaoToggleOrg();
            });
        }

        // Botões de ação em massa
        const btnConfirmarPresencaMassa = document.getElementById('btn-confirmar-presencas-massa-org');
        const btnEmitirCertMassa = document.getElementById('btn-emitir-certificados-massa-org');
        const btnExcluirMassa = document.getElementById('btn-excluir-membros-massa');

        if (btnConfirmarPresencaMassa && !btnConfirmarPresencaMassa.dataset.bound) {
            btnConfirmarPresencaMassa.dataset.bound = '1';
            btnConfirmarPresencaMassa.addEventListener('click', confirmarPresencasEmMassaOrg);
        }

        if (btnEmitirCertMassa && !btnEmitirCertMassa.dataset.bound) {
            btnEmitirCertMassa.dataset.bound = '1';
            btnEmitirCertMassa.addEventListener('click', emitirCertificadosEmMassaOrg);
        }

        if (btnExcluirMassa && !btnExcluirMassa.dataset.bound) {
            btnExcluirMassa.dataset.bound = '1';
            btnExcluirMassa.addEventListener('click', excluirMembrosEmMassa);
        }

        // Pesquisa
        const campoPesquisa = document.getElementById('busca-organizacao');
        const btnPesquisa = document.getElementById('btn-buscar-org');

        if (campoPesquisa) {
            campoPesquisa.addEventListener('input', filtrarMembros);
            campoPesquisa.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    filtrarMembros();
                }
            });
        }

        if (btnPesquisa && !btnPesquisa.dataset.bound) {
            btnPesquisa.dataset.bound = '1';
            btnPesquisa.addEventListener('click', filtrarMembros);
        }
    }

    function filtrarMembros() {
        const tbody = document.getElementById('tbody-organizacao');
        const mobileContainer = document.getElementById('mobile-cards-organizacao');
        const isMobile = window.matchMedia('(max-width: 768px)').matches;
        const termo = document.getElementById('busca-organizacao').value.toLowerCase().trim();

        if (!termo) {
            if (isMobile && mobileContainer) {
                mobileContainer.querySelectorAll('.mobile-card').forEach(card => card.style.display = '');
            } else if (tbody) {
                tbody.querySelectorAll('tr').forEach(linha => linha.style.display = '');
            }
            return;
        }

        if (isMobile && mobileContainer) {
            // Filtrar cards mobile
            mobileContainer.querySelectorAll('.mobile-card').forEach(card => {
                const texto = card.textContent.toLowerCase();
                card.style.display = texto.includes(termo) ? '' : 'none';
            });
        } else if (tbody) {
            // Filtrar tabela desktop
            tbody.querySelectorAll('tr').forEach(linha => {
                const texto = linha.textContent.toLowerCase();
                linha.style.display = texto.includes(termo) ? '' : 'none';
            });
        }
    }

    function atualizarVisibilidadeBotoesAcaoOrg() {
        const acoesEmMassa = document.getElementById('acoes-em-massa-org');
        if (acoesEmMassa) {
            if (membrosSelecionados.size > 0) {
                acoesEmMassa.classList.add('com-selecao');
            } else {
                acoesEmMassa.classList.remove('com-selecao');
            }
        }
    }

    function atualizarTextoBotaoToggleOrg() {
        const txtToggle = document.getElementById('texto-toggle-selecao-org');
        if (txtToggle) {
            const todosSelecionados = membrosSelecionados.size === todosOrganizacao.length && membrosSelecionados.size > 0;
            txtToggle.textContent = todosSelecionados ? 'Desselecionar Todos' : 'Selecionar Todos';
        }
    }

    // Listener para resize: re-renderiza se mudar entre mobile e desktop
    if (!window.__organizacaoResizeAttached) {
        window.__organizacaoResizeAttached = true;
        window.addEventListener('resize', () => {
            const nowMobile = window.matchMedia('(max-width: 768px)').matches;
            if (nowMobile !== window.__lastOrganizacaoIsMobile && todosOrganizacao && todosOrganizacao.length > 0) {
                renderizarOrganizacao();
            }
        });
    }

    // Expõe funções globalmente para serem chamadas por GerenciarEvento.php
    window.renderizarOrganizacao = renderizarOrganizacao;
    window.carregarOrganizacao = carregarOrganizacao;
</script>