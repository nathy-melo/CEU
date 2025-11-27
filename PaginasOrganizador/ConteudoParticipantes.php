<?php
// Apenas o conteúdo HTML da aba de Participantes
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
                        <input class="campo-pesquisa" type="text" id="busca-participantes" name="busca_participantes" placeholder="Procure por nome, RA ou CPF" autocomplete="off" />
                        <button class="botao-pesquisa" id="btn-buscar-part" aria-label="Procurar">
                            <div class="icone-pesquisa">
                                <img src="../Imagens/lupa.png" alt="Lupa">
                            </div>
                        </button>
                    </div>
                </div>
                <button class="botao botao-filtrar" id="btn-filtrar-participantes">
                    <span>Filtrar</span>
                    <img src="../Imagens/filtro.png" alt="Filtro">
                </button>
            </div>
        </div>

        <!-- Ações Rápidas -->
        <div class="acoes-rapidas-wrapper">
            <h2 class="secao-titulo-compacta">Ações Rápidas</h2>
            <div class="grade-acoes-gerenciamento">
                <button class="botao botao-acao" id="btn-adicionar-participante">
                    <span>Adicionar</span>
                    <img src="../Imagens/Adicionar_participante.svg" alt="Adicionar icon">
                </button>
                <button class="botao botao-acao" id="btn-importar-presenca">
                    <span>Importar Lista de Presença</span>
                    <img src="../Imagens/Importar.svg" alt="Importar icon">
                </button>
                <button class="botao botao-acao" id="btn-exportar-presenca">
                    <span>Exportar Lista de Presença</span>
                    <img src="../Imagens/Exportar.svg" alt="Exportar icon">
                </button>
                <button class="botao botao-acao" id="btn-enviar-mensagem-part">
                    <span>Enviar Mensagem</span>
                    <img src="../Imagens/Email.svg" alt="Mensagem icon">
                </button>
                <button class="botao botao-acao" id="btn-importar-inscritos">
                    <span>Importar Lista de Inscritos</span>
                    <img src="../Imagens/Importar_lista.svg" alt="Importar icon">
                </button>
                <button class="botao botao-acao" id="btn-exportar-inscritos">
                    <span>Exportar Lista de Inscritos</span>
                    <img src="../Imagens/Exportar_lista.svg" alt="Exportar icon">
                </button>
            </div>
        </div>
    </div>

    <!-- Seção: Ações em Massa -->
    <div class="secao-acoes-massa-compacta">
        <h2 class="secao-titulo-compacta">Ações em Massa</h2>
        <div class="acoes-em-massa" id="acoes-em-massa-part">
            <button class="botao botao-em-massa botao-branco" id="botao-toggle-selecao-part">
                <span id="texto-toggle-selecao-part">Selecionar Todos</span>
                <img src="../Imagens/Grupo_de_pessoas.svg" alt="">
            </button>
            <button class="botao botao-em-massa botao-verde" id="btn-confirmar-presencas-massa">
                <span>Confirmar Presenças</span>
                <img src="../Imagens/Certo.svg" alt="">
            </button>
            <button class="botao botao-em-massa botao-azul" id="btn-emitir-certificados-massa">
                <span>Emitir Certificados</span>
                <img src="../Imagens/Certificado.svg" alt="">
            </button>
            <button class="botao botao-em-massa botao-vermelho" id="btn-excluir-participantes-massa">
                <span>Excluir Participantes</span>
                <img src="../Imagens/Excluir.svg" alt="">
            </button>
        </div>
    </div>

    <!-- Seção: Lista de Participantes -->
    <div>
        <div class="contador-participantes">
            <span id="total-participantes">Total de participantes: 0</span>
            
            <!-- Navegação de Páginas -->
            <div id="navegacao-paginas-tabela" style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap; justify-content: center;"></div>
            
            <div style="display: flex; gap: 0.5rem; align-items: center;">
                <label for="select-linhas-por-pagina" style="font-size: 0.9rem;">Linhas por página:</label>
                <select id="select-linhas-por-pagina" class="botao" style="padding: 0.4rem 0.8rem; font-size: 0.9rem; cursor: pointer; border: 1px solid var(--caixas); border-radius: 0.3rem; background: var(--botao); color: var(--texto);">
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
            <table class="tabela-participantes" id="tabela-participantes">
                <thead>
                    <tr>
                        <th class="Titulo_Tabela">Selecionar</th>
                        <th class="Titulo_Tabela">Dados do Participante</th>
                        <th class="Titulo_Tabela">Modificar</th>
                        <th class="Titulo_Tabela">Status</th>
                    </tr>
                </thead>
                <tbody id="tbody-participantes">
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 30px; color: var(--botao);">
                            Carregando participantes...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Container para cards mobile -->
        <div class="mobile-cards-container" id="mobile-cards-participantes">
            <p style="text-align:center; padding:30px; color:var(--botao);">Carregando participantes...</p>
        </div>
    </div>
</div>

<!-- Modal Adicionar Participante -->
<div class="modal-overlay" id="modalAdicionarParticipante" onclick="if(event.target.id === 'modalAdicionarParticipante') fecharModalAdicionar();">
    <div class="modal-editar" onclick="event.stopPropagation();">
        <div class="modal-header">
            <h2>Adicionar Participante</h2>
            <button class="btn-fechar-modal" onclick="event.stopPropagation(); fecharModalAdicionar();">&times;</button>
        </div>
        <form id="formAdicionarParticipante" onsubmit="salvarNovoParticipante(event);">
            <div class="form-group">
                <label for="add-cpf">CPF*</label>
                <input type="text" id="add-cpf" maxlength="14" placeholder="000.000.000-00" required>
                <small id="msg-cpf-existente" style="color: #666; display: none; margin-top: 4px;">✓ Usuário cadastrado no sistema</small>
            </div>

            <div class="form-group">
                <label for="add-nome">Nome Completo*</label>
                <input type="text" id="add-nome" required>
            </div>

            <div class="form-group">
                <label for="add-email">E-mail*</label>
                <input type="email" id="add-email" required>
            </div>

            <div class="form-group">
                <label for="add-ra">Registro Acadêmico (RA)</label>
                <input type="text" id="add-ra" maxlength="7">
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-modal btn-cancelar" onclick="event.stopPropagation(); fecharModalAdicionar();">Cancelar</button>
                <button type="submit" class="btn-modal btn-salvar">Adicionar Participante</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Enviar Mensagem -->
<div class="modal-overlay" id="modalEnviarMensagemPart" onclick="if(event.target.id === 'modalEnviarMensagemPart') fecharModalMensagemPart();">
    <div class="modal-editar">
        <div class="modal-header">
            <h2>Enviar Mensagem aos Participantes</h2>
            <button class="btn-fechar-modal" onclick="fecharModalMensagemPart();">&times;</button>
        </div>
        <form id="formEnviarMensagemPart" onsubmit="enviarMensagemParticipantes(event);">
            <div class="form-group">
                <label for="msg-titulo-part">Título da Notificação*</label>
                <input type="text" id="msg-titulo-part" maxlength="100" required>
            </div>

            <div class="form-group">
                <label for="msg-conteudo-part">Mensagem*</label>
                <textarea id="msg-conteudo-part" rows="6" style="width: 100%; padding: 12px; border: 1px solid var(--azul-escuro); border-radius: 8px; font-size: 15px; font-family: inherit; resize: vertical;" maxlength="500" required></textarea>
                <small style="color: #666;">Máximo 500 caracteres</small>
            </div>

            <div class="form-group">
                <label for="msg-todos-part" style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" id="msg-todos-part" checked style="width: 20px; height: 20px;">
                    <span>Enviar para todos os participantes</span>
                </label>
                <small id="msg-selecionados-part" style="color: #666; display: none; margin-top: 8px;"></small>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-modal btn-cancelar" onclick="fecharModalMensagemPart();">Cancelar</button>
                <button type="submit" class="btn-modal btn-salvar">Enviar Notificação</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Editar Dados -->
<div class="modal-overlay" id="modalEditarDadosPart" onclick="if(event.target.id === 'modalEditarDadosPart') fecharModalEditarPart();">
    <div class="modal-editar">
        <div class="modal-header">
            <h2>Editar Dados do Participante</h2>
            <button class="btn-fechar-modal" onclick="fecharModalEditarPart();">&times;</button>
        </div>
        <form id="formEditarDadosPart" onsubmit="salvarEdicaoPart(event);">
            <input type="hidden" id="edit-cpf-part">

            <div class="form-group">
                <label for="edit-nome-part">Nome Completo*</label>
                <input type="text" id="edit-nome-part" required>
            </div>

            <div class="form-group">
                <label for="edit-email-part">E-mail*</label>
                <input type="email" id="edit-email-part" required>
            </div>

            <div class="form-group">
                <label for="edit-ra-part">Registro Acadêmico (RA)</label>
                <input type="text" id="edit-ra-part" maxlength="7">
            </div>

            <div class="form-group">
                <label for="edit-cpf-display-part">CPF</label>
                <input type="text" id="edit-cpf-display-part" disabled>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-modal btn-cancelar" onclick="fecharModalEditarPart(); event.stopPropagation();">Cancelar</button>
                <button type="submit" class="btn-modal btn-salvar" onclick="event.stopPropagation();">Salvar Alterações</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Variáveis globais para a aba de participantes
    if (typeof todosParticipantes === 'undefined') {
        var todosParticipantes = [];
    }
    if (typeof participantesSelecionados === 'undefined') {
        var participantesSelecionados = new Set();
    }

    // ===== FUNÇÕES DE IMPORTAR/EXPORTAR (DECLARADAS NO INÍCIO) =====

    // Salva o HTML original do modal de importação
    window.modalImportacaoOriginalHTML = null;

    window.importarListaPresenca = function() {
        const modal = document.getElementById('modalInfoImportacao');
        if (modal) {
            // Move modal para fora do conteudo-dinamico
            const modaisGlobais = document.getElementById('modais-globais');
            if (modaisGlobais && modal.parentElement.id !== 'modais-globais') {
                modaisGlobais.appendChild(modal);
            }

            // Salva o HTML original na primeira vez
            if (!window.modalImportacaoOriginalHTML) {
                const modalBody = modal.querySelector('.modal-body');
                if (modalBody) {
                    window.modalImportacaoOriginalHTML = modalBody.innerHTML;
                }
            }

            // Restaura o HTML original toda vez que abrir
            const modalBody = modal.querySelector('.modal-body');
            if (modalBody && window.modalImportacaoOriginalHTML) {
                modalBody.innerHTML = window.modalImportacaoOriginalHTML;
            }

            // Limpa qualquer arquivo selecionado anteriormente
            window.arquivoSelecionado = null;

            document.body.style.overflow = 'hidden';
            modal.classList.add('ativo');
            window.tipoImportacaoAtual = 'presenca';
        }
    };

    window.exportarListaPresenca = function() {
        const modal = document.getElementById('modalEscolherFormato');
        if (modal) {
            // Move modal para fora do conteudo-dinamico
            const modaisGlobais = document.getElementById('modais-globais');
            if (modaisGlobais && modal.parentElement.id !== 'modais-globais') {
                modaisGlobais.appendChild(modal);
            }

            document.body.style.overflow = 'hidden';
            modal.classList.add('ativo');
            window.tipoExportacaoAtual = 'presenca';
        }
    };

    window.importarListaInscritos = function() {
        const modal = document.getElementById('modalInfoImportacao');
        if (modal) {
            // Move modal para fora do conteudo-dinamico
            const modaisGlobais = document.getElementById('modais-globais');
            if (modaisGlobais && modal.parentElement.id !== 'modais-globais') {
                modaisGlobais.appendChild(modal);
            }

            // Salva o HTML original na primeira vez
            if (!window.modalImportacaoOriginalHTML) {
                const modalBody = modal.querySelector('.modal-body');
                if (modalBody) {
                    window.modalImportacaoOriginalHTML = modalBody.innerHTML;
                }
            }

            // Restaura o HTML original toda vez que abrir
            const modalBody = modal.querySelector('.modal-body');
            if (modalBody && window.modalImportacaoOriginalHTML) {
                modalBody.innerHTML = window.modalImportacaoOriginalHTML;
            }

            // Limpa qualquer arquivo selecionado anteriormente
            window.arquivoSelecionado = null;

            document.body.style.overflow = 'hidden';
            modal.classList.add('ativo');
            window.tipoImportacaoAtual = 'inscritos';
        }
    };

    window.exportarListaInscritos = function() {
        const modal = document.getElementById('modalEscolherFormato');
        if (modal) {
            // Move modal para fora do conteudo-dinamico
            const modaisGlobais = document.getElementById('modais-globais');
            if (modaisGlobais && modal.parentElement.id !== 'modais-globais') {
                modaisGlobais.appendChild(modal);
            }

            document.body.style.overflow = 'hidden';
            modal.classList.add('ativo');
            window.tipoExportacaoAtual = 'inscritos';
        }
    };

    window.fecharModalFormato = function() {
        const modal = document.getElementById('modalEscolherFormato');
        if (modal) {
            modal.classList.remove('ativo');
            document.body.style.overflow = '';
        }
    };

    window.fecharModalImportacao = function() {
        const modal = document.getElementById('modalInfoImportacao');
        if (modal) {
            // Restaura o HTML original antes de fechar
            const modalBody = modal.querySelector('.modal-body');
            if (modalBody && window.modalImportacaoOriginalHTML) {
                modalBody.innerHTML = window.modalImportacaoOriginalHTML;
            }

            // Limpa o arquivo selecionado
            window.arquivoSelecionado = null;

            modal.classList.remove('ativo');
            document.body.style.overflow = '';
        }
    };

    window.executarExportacao = function(formato) {
        const tipo = window.tipoExportacaoAtual || 'presenca';
        const action = tipo === 'presenca' ? 'exportar_presenca' : 'exportar_inscritos';

        if (typeof codEventoAtual === 'undefined' || !codEventoAtual) {
            alert('Código do evento não encontrado');
            return;
        }

        const url = `GerenciarEvento.php?action=${action}&formato=${formato}&cod_evento=${codEventoAtual}`;
        const link = document.createElement('a');
        link.href = url;
        link.download = '';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        window.fecharModalFormato();
    };

    window.selecionarArquivoImportacao = function() {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = '.csv,.xlsx,.xls,.ods';
        input.onchange = async (e) => {
            const arquivo = e.target.files[0];
            if (!arquivo) return;

            // Verifica o tamanho do arquivo (10MB)
            if (arquivo.size > 10 * 1024 * 1024) {
                alert('O arquivo excede o limite de 10MB');
                return;
            }

            if (typeof codEventoAtual === 'undefined' || !codEventoAtual) {
                alert('Código do evento não encontrado');
                return;
            }

            // Atualiza o modal para mostrar o arquivo selecionado
            const modalBody = document.querySelector('#modalInfoImportacao .info-importacao');
            const tamanhoMB = (arquivo.size / 1024 / 1024).toFixed(2);

            modalBody.innerHTML = `
                <div class="info-item" style="text-align: center; padding: 20px;">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--azul-escuro)" stroke-width="2" style="margin-bottom: 16px;">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="12" y1="11" x2="12" y2="17"></line>
                        <polyline points="9 14 12 17 15 14"></polyline>
                    </svg>
                    <h3 style="color: var(--azul-escuro); margin: 16px 0 8px 0;">Arquivo Selecionado</h3>
                    <p style="font-size: 16px; font-weight: 600; color: #333; margin: 8px 0;">${arquivo.name}</p>
                    <p style="color: #666; margin: 4px 0;">Tamanho: ${tamanhoMB} MB</p>
                    <p style="color: #666; margin: 4px 0;">Tipo: ${arquivo.type || 'Desconhecido'}</p>
                </div>
            `;

            // Atualiza os botões do footer
            const modalFooter = document.querySelector('#modalInfoImportacao .modal-footer');
            modalFooter.innerHTML = `
                <button class="botao botao-secundario" onclick="window.cancelarImportacao(); event.stopPropagation();">
                    Cancelar
                </button>
                <button class="botao botao-primario" onclick="window.confirmarImportacao(); event.stopPropagation();">
                    Confirmar Importação
                </button>
            `;

            // Armazena o arquivo para uso posterior
            window.arquivoSelecionado = arquivo;
        };

        input.click();
    };

    window.cancelarImportacao = function() {
        window.arquivoSelecionado = null;

        // Restaura o HTML original do modal
        const modal = document.getElementById('modalInfoImportacao');
        if (modal && window.modalImportacaoOriginalHTML) {
            const modalBody = modal.querySelector('.modal-body');
            if (modalBody) {
                modalBody.innerHTML = window.modalImportacaoOriginalHTML;
            }
        }

        // Fecha o modal
        window.fecharModalImportacao();
    };

    window.confirmarImportacao = async function() {
        const arquivo = window.arquivoSelecionado;
        if (!arquivo) return;

        const tipo = window.tipoImportacaoAtual || 'presenca';
        const action = tipo === 'presenca' ? 'importar_presenca' : 'importar_inscritos';

        // Fecha o modal antes de processar
        window.fecharModalImportacao();

        // Envia o arquivo
        const formData = new FormData();
        formData.append('arquivo', arquivo);
        formData.append('action', action);
        formData.append('cod_evento', codEventoAtual);

        try {
            const response = await fetch('GerenciarEvento.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            if (data.sucesso) {
                alert(data.mensagem || 'Importação realizada com sucesso!');
                if (typeof carregarParticipantes === 'function') {
                    carregarParticipantes();
                }
            } else {
                alert(data.erro || 'Erro ao importar arquivo');
            }
        } catch (error) {
            console.error('Erro ao importar:', error);
            alert('Erro ao processar importação');
        }

        window.arquivoSelecionado = null;
    };

    window.fecharModalSeForFundo = function(event, modalId) {
        // Verifica se o clique foi no overlay (fundo) e não no conteúdo do modal
        if (event.target.classList && event.target.classList.contains('modal-overlay')) {
            // Fecha e restaura o estado original para modais de importação/exportação
            if (modalId === 'modalEscolherFormato') {
                window.fecharModalFormato();
            } else if (modalId === 'modalInfoImportacao') {
                window.fecharModalImportacao();
            }
        }
    };

    // Função para carregar participantes
    function carregarParticipantes() {
        if (typeof codEventoAtual === 'undefined' || !codEventoAtual) {
            return;
        }

        fetch(`GerenciarEvento.php?action=buscar&cod_evento=${codEventoAtual}`)
            .then(response => response.json())
            .then(dados => {
                if (!dados.sucesso) {
                    alert('Erro ao carregar participantes: ' + (dados.erro || 'Erro desconhecido'));
                    return;
                }

                todosParticipantes = dados.participantes || [];

                renderizarParticipantes();
                inicializarEventosParticipantes();
            })
            .catch(erro => {
                console.error('Erro ao carregar participantes:', erro);
                alert('Erro ao carregar participantes. Tente novamente.');
            });
    }

    // Função para renderizar participantes
    function renderizarParticipantes() {
        const tbody = document.getElementById('tbody-participantes');
        const totalSpan = document.getElementById('total-participantes');
        const mobileContainer = document.getElementById('mobile-cards-participantes');
        const isMobile = window.matchMedia('(max-width: 768px)').matches;

        if (!tbody || !totalSpan || !mobileContainer) {
            return;
        }

        totalSpan.textContent = `Total de participantes: ${todosParticipantes.length}`;

        // LIMPA ambos os containers sempre para evitar duplicação
        tbody.innerHTML = '';
        mobileContainer.innerHTML = '';

        if (todosParticipantes.length === 0) {
            if (isMobile) {
                mobileContainer.innerHTML = '<p style="text-align:center; padding:30px; color:var(--botao);">Nenhum participante inscrito neste evento ainda</p>';
            } else {
                tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 30px; color: var(--botao);">Nenhum participante inscrito neste evento ainda</td></tr>';
            }
            window.__lastParticipantesIsMobile = isMobile;
            return;
        }

        if (isMobile) {
            // Renderizar cards mobile
            let cardsHTML = '';
            todosParticipantes.forEach((p, i) => {
                const statusPresenca = p.presenca_confirmada ? 'Confirmada' : 'Não Confirmada';
                const statusCertificado = p.certificado_emitido ? 'Enviado' : 'Não enviado';

                let btnAcaoPrincipal = '';
                if (p.certificado_emitido) {
                    btnAcaoPrincipal = '';
                } else if (p.presenca_confirmada) {
                    btnAcaoPrincipal = `<button class="btn-small botao-azul" onclick="emitirCertificadoPart('${p.cpf}')"><img src="../Imagens/Certificado.svg" alt=""> Emitir Certificado</button>`;
                } else {
                    btnAcaoPrincipal = `<button class="btn-small botao-verde" onclick="confirmarPresencaPart('${p.cpf}')"><img src="../Imagens/Certo.svg" alt=""> Confirmar Presença</button>`;
                }

                let btnExcluir = '';
                if (p.certificado_emitido) {
                    btnExcluir = `<button class="btn-small botao-cinza" disabled title="Certificado do participante já foi emitido"><img src="../Imagens/Excluir.svg" alt=""> Excluir</button>`;
                } else {
                    btnExcluir = `<button class="btn-small botao-vermelho" onclick="excluirParticipantePart('${p.cpf}')"><img src="../Imagens/Excluir.svg" alt=""> Excluir</button>`;
                }

                const btnCertificado = p.certificado_emitido ?
                    `<button class="btn-small botao-neutro" onclick="verificarCertificadoPart('${p.cod_verificacao || ''}')"><img src="../Imagens/Certificado.svg" alt=""> Ver Certificado</button>` : '';

                cardsHTML += `
                    <div class="mobile-card" data-cpf="${p.cpf}">
                        <div class="mobile-card-row">
                            <span class="mobile-card-label">Selecionar</span>
                            <span class="mobile-card-value">
                                <input type="checkbox" class="checkbox-selecionar checkbox-part" id="part-mobile-${i}" value="${p.cpf}">
                            </span>
                        </div>
                        <div class="mobile-card-row">
                            <span class="mobile-card-label">Nome</span>
                            <span class="mobile-card-value"><strong>${p.nome}</strong></span>
                        </div>
                        <div class="mobile-card-row">
                            <span class="mobile-card-label">E-mail</span>
                            <span class="mobile-card-value">${p.email}</span>
                        </div>
                        <div class="mobile-card-row">
                            <span class="mobile-card-label">RA</span>
                            <span class="mobile-card-value">${p.ra}</span>
                        </div>
                        <div class="mobile-card-row">
                            <span class="mobile-card-label">Data de Inscrição</span>
                            <span class="mobile-card-value">${p.data_inscricao}</span>
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
                            <button class="btn-small botao-neutro" onclick="editarDadosPart('${p.cpf}')"><img src="../Imagens/Editar.svg" alt=""> Editar Dados</button>
                            ${btnCertificado}
                        </div>
                    </div>
                `;
            });
            mobileContainer.innerHTML = cardsHTML;
        } else {
            // Renderizar tabela desktop
            tbody.innerHTML = todosParticipantes.map((p, i) => {
                const statusPresenca = p.presenca_confirmada ?
                    '<span class="emblema-status confirmado">Confirmada <img src="../Imagens/Certo.svg" alt=""></span>' :
                    '<span class="emblema-status negado">Não Confirmada <img src="../Imagens/Errado.svg" alt=""></span>';

                const statusCertificado = p.certificado_emitido ?
                    '<span class="emblema-status confirmado">Enviado <img src="../Imagens/Certo.svg" alt=""></span>' :
                    '<span class="emblema-status negado">Não enviado <img src="../Imagens/Errado.svg" alt=""></span>';

                let btnAcaoPrincipal = '';
                if (p.certificado_emitido) {
                    btnAcaoPrincipal = '';
                } else if (p.presenca_confirmada) {
                    btnAcaoPrincipal = `<button class="botao botao-acao-tabela botao-azul" onclick="emitirCertificadoPart('${p.cpf}')">
                        <span>Emitir Certificado</span><img src="../Imagens/Certificado.svg" alt="">
                    </button>`;
                } else {
                    btnAcaoPrincipal = `<button class="botao botao-acao-tabela botao-verde" onclick="confirmarPresencaPart('${p.cpf}')">
                        <span>Confirmar Presença</span><img src="../Imagens/Certo.svg" alt="">
                    </button>`;
                }

                let btnExcluir = '';
                if (p.certificado_emitido) {
                    btnExcluir = `<button class="botao botao-acao-tabela botao-cinza" disabled title="Certificado do participante já foi emitido. Não é possível excluir o participante.">
                        <span>Excluir Participante</span><img src="../Imagens/Excluir.svg" alt="">
                    </button>`;
                } else {
                    btnExcluir = `<button class="botao botao-acao-tabela botao-vermelho" onclick="excluirParticipantePart('${p.cpf}')">
                        <span>Excluir Participante</span><img src="../Imagens/Excluir.svg" alt="">
                    </button>`;
                }

                const btnCertificado = p.certificado_emitido ?
                    '<button class="botao botao-acao-tabela botao-neutro" onclick="verificarCertificadoPart(\'' + (p.cod_verificacao || '') + '\')"><span>Verificar Certificado</span><img src="../Imagens/Certificado.svg" alt=""></button>' :
                    '';

                return `
                <tr data-cpf="${p.cpf}">
                    <td class="coluna-selecionar" data-label="Selecionar">
                        <input type="checkbox" class="checkbox-selecionar checkbox-part" id="part-${i}" value="${p.cpf}">
                    </td>
                    <td class="coluna-dados" data-label="Dados do Participante">
                        <p><strong>Nome:</strong> ${p.nome}</p>
                        <p><strong>E-mail:</strong> ${p.email}</p>
                        <p><strong>Registro Acadêmico:</strong> ${p.ra}</p>
                        <p><strong>Data de Inscrição:</strong> ${p.data_inscricao}</p>
                    </td>
                    <td class="coluna-modificar" data-label="Modificar">
                        <div class="grupo-acoes">
                            ${btnAcaoPrincipal}
                            ${btnExcluir}
                            <button class="botao botao-acao-tabela botao-neutro" onclick="editarDadosPart('${p.cpf}')">
                                <span>Editar Dados</span><img src="../Imagens/Editar.svg" alt="">
                            </button>
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
        window.__lastParticipantesIsMobile = isMobile;

        // Re-inicializa eventos após re-renderizar (quando muda entre mobile/desktop)
        inicializarEventosParticipantes();
        
        // Inicializar paginação de tabelas (apenas para desktop)
        if (!isMobile && typeof window.inicializarPaginacaoTabela === 'function') {
            window.inicializarPaginacaoTabela('tabela-participantes', {
                linhasPorPagina: 30,
                maximoLinhas: 100,
                selectId: 'select-linhas-por-pagina'
            });
        }

        // Inicializar filtro de participantes APÓS garantir que tudo está pronto
        if (!isMobile) {
            // Usar setTimeout para garantir que a tabela está completamente renderizada
            setTimeout(function() {
                if (typeof window.inicializarFiltroParticipantes === 'function') {
                    window.inicializarFiltroParticipantes('tabela-participantes');
                    
                    // Forçar ordenação A-Z imediatamente após inicializar
                    setTimeout(function() {
                        if (window.filtroParticipantesConfig && window.filtroParticipantesConfig.tabelaId) {
                            if (typeof aplicarOrdenacaoInicial === 'function') {
                                aplicarOrdenacaoInicial();
                            }
                        }
                    }, 150);
                }
            }, 100);
        }
    }

    // Função para inicializar eventos
    function inicializarEventosParticipantes() {
        // Checkboxes de seleção (funciona para tabela E cards mobile)
        document.addEventListener('change', function(e) {
            if (e.target.classList && e.target.classList.contains('checkbox-part')) {
                const container = e.target.closest('tr') || e.target.closest('.mobile-card');
                if (container) {
                    container.classList.toggle('linha-selecionada', e.target.checked);
                }
                e.target.checked ? participantesSelecionados.add(e.target.value) : participantesSelecionados.delete(e.target.value);
                atualizarVisibilidadeBotoesAcaoPart();
                atualizarTextoBotaoTogglePart();
            }
        });

        // Toggle selecionar todos
        const btnToggle = document.getElementById('botao-toggle-selecao-part');
        if (btnToggle && !btnToggle.dataset.bound) {
            btnToggle.dataset.bound = '1';
            btnToggle.addEventListener('click', function() {
                if (todosParticipantes.length === 0) {
                    alert('Não há participantes inscritos neste evento');
                    return;
                }

                const todosSelecionados = participantesSelecionados.size === todosParticipantes.length && participantesSelecionados.size > 0;

                if (todosSelecionados) {
                    document.querySelectorAll('.checkbox-part').forEach(cb => {
                        cb.checked = false;
                        const container = cb.closest('tr') || cb.closest('.mobile-card');
                        if (container) container.classList.remove('linha-selecionada');
                        participantesSelecionados.delete(cb.value);
                    });
                } else {
                    participantesSelecionados.clear();
                    document.querySelectorAll('.checkbox-part').forEach(cb => {
                        cb.checked = true;
                        const container = cb.closest('tr') || cb.closest('.mobile-card');
                        if (container) container.classList.add('linha-selecionada');
                        participantesSelecionados.add(cb.value);
                    });
                }

                atualizarVisibilidadeBotoesAcaoPart();
                atualizarTextoBotaoTogglePart();
            });
        }

        // Barra de pesquisa
        const campoPesquisa = document.getElementById('busca-participantes');
        const btnPesquisa = document.getElementById('btn-buscar-part');
        if (campoPesquisa && btnPesquisa) {
            if (!btnPesquisa.dataset.bound) {
                btnPesquisa.dataset.bound = '1';
                btnPesquisa.addEventListener('click', (e) => {
                    e.preventDefault();
                    filtrarParticipantes();
                });
            }
            if (!campoPesquisa.dataset.bound) {
                campoPesquisa.dataset.bound = '1';
                campoPesquisa.addEventListener('keydown', e => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        filtrarParticipantes();
                    }
                });
                campoPesquisa.addEventListener('input', filtrarParticipantes);
            }
        }

        // Botões de ação do topo
        const bindsTopo = [{
                id: 'btn-adicionar-participante',
                fn: abrirModalAdicionar
            },
            {
                id: 'btn-importar-presenca',
                fn: window.importarListaPresenca
            },
            {
                id: 'btn-exportar-presenca',
                fn: window.exportarListaPresenca
            },
            {
                id: 'btn-enviar-mensagem-part',
                fn: abrirModalMensagemPart
            },
            {
                id: 'btn-enviar-mensagem-cpf',
                fn: () => {
                    if (typeof window.abrirModalMensagemCPF === 'function') {
                        window.abrirModalMensagemCPF();
                    } else {
                        alert('Função não disponível. Recarregue a página.');
                    }
                }
            },
            {
                id: 'btn-importar-inscritos',
                fn: window.importarListaInscritos
            },
            {
                id: 'btn-exportar-inscritos',
                fn: window.exportarListaInscritos
            }
        ];
        bindsTopo.forEach(({
            id,
            fn
        }) => {
            const el = document.getElementById(id);
            if (el && !el.dataset.bound) {
                el.dataset.bound = '1';
                el.addEventListener('click', fn);
            }
        });

        // Botões de ação em massa
        const bindsMassa = [{
                id: 'btn-confirmar-presencas-massa',
                fn: confirmarPresencasEmMassa
            },
            {
                id: 'btn-emitir-certificados-massa',
                fn: emitirCertificadosEmMassa
            },
            {
                id: 'btn-excluir-participantes-massa',
                fn: excluirParticipantesEmMassa
            }
        ];
        bindsMassa.forEach(({
            id,
            fn
        }) => {
            const el = document.getElementById(id);
            if (el && !el.dataset.bound) {
                el.dataset.bound = '1';
                el.addEventListener('click', fn);
            }
        });

        // CPF input com máscara
        const addCpfInput = document.getElementById('add-cpf');
        if (addCpfInput && !addCpfInput.dataset.bound) {
            addCpfInput.dataset.bound = '1';
            addCpfInput.addEventListener('input', function(e) {
                let valor = e.target.value.replace(/\D/g, '');
                if (valor.length <= 11) {
                    valor = valor.replace(/(\d{3})(\d)/, '$1.$2');
                    valor = valor.replace(/(\d{3})(\d)/, '$1.$2');
                    valor = valor.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
                    e.target.value = valor;
                }
            });
            addCpfInput.addEventListener('blur', verificarCPFExistente);
        }
    }

    // Funções auxiliares
    function atualizarVisibilidadeBotoesAcaoPart() {
        const acoesEmMassa = document.getElementById('acoes-em-massa-part');
        if (acoesEmMassa) {
            if (participantesSelecionados.size > 0) {
                acoesEmMassa.classList.add('com-selecao');
            } else {
                acoesEmMassa.classList.remove('com-selecao');
            }
        }
    }

    function atualizarTextoBotaoTogglePart() {
        const txtToggle = document.getElementById('texto-toggle-selecao-part');
        if (txtToggle) {
            const todosSelecionados = participantesSelecionados.size === todosParticipantes.length && participantesSelecionados.size > 0;
            txtToggle.textContent = todosSelecionados ? 'Desselecionar Todos' : 'Selecionar Todos';
        }
    }

    function filtrarParticipantes() {
        const tbody = document.getElementById('tbody-participantes');
        const mobileContainer = document.getElementById('mobile-cards-participantes');
        const isMobile = window.matchMedia('(max-width: 768px)').matches;

        if (!isMobile && (!tbody || todosParticipantes.length === 0)) return;
        if (isMobile && (!mobileContainer || todosParticipantes.length === 0)) return;

        const termo = (document.getElementById('busca-participantes')?.value || '').toLowerCase();
        let visiveis = 0;

        if (isMobile) {
            // Filtrar cards mobile
            mobileContainer.querySelectorAll('.mobile-card').forEach(card => {
                const match = card.textContent.toLowerCase().includes(termo);
                card.style.display = match ? '' : 'none';
                if (match) visiveis++;
            });
        } else {
            // Filtrar tabela desktop
            tbody.querySelectorAll('tr').forEach(linha => {
                if (!linha.hasAttribute('data-cpf')) return;
                const match = linha.textContent.toLowerCase().includes(termo);
                linha.style.display = match ? '' : 'none';
                if (match) visiveis++;
            });
        }

        const idMsg = 'linha-sem-resultados-busca-part';
        const container = isMobile ? mobileContainer : tbody;
        const existente = document.getElementById(idMsg);

        if (visiveis === 0 && !existente) {
            if (isMobile) {
                const div = document.createElement('div');
                div.id = idMsg;
                div.style.cssText = 'text-align: center; padding: 30px; color: var(--botao);';
                div.textContent = 'Nenhum participante encontrado para a busca';
                container.appendChild(div);
            } else {
                const tr = document.createElement('tr');
                tr.id = idMsg;
                tr.innerHTML = '<td colspan="4" style="text-align: center; padding: 30px; color: var(--botao);">Nenhum participante encontrado para a busca</td>';
                container.appendChild(tr);
            }
        } else if (visiveis > 0 && existente) {
            existente.remove();
        }
    }

    // Ações individuais
    function confirmarPresencaPart(cpf) {
        if (!confirm('Confirmar presença deste participante?')) return;

        fetch('GerenciarEvento.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'confirmar_presenca',
                    cod_evento: codEventoAtual,
                    cpf: cpf
                })
            })
            .then(r => r.json())
            .then(d => {
                if (d.sucesso) {
                    alert('Presença confirmada com sucesso!');
                    carregarParticipantes();
                } else {
                    alert('Erro: ' + (d.erro || 'Erro desconhecido'));
                }
            })
            .catch(() => alert('Erro ao confirmar presença'));
    }

    function emitirCertificadoPart(cpf) {
        if (!confirm('Emitir certificado para este participante?')) return;

        fetch('GerenciarEvento.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'emitir_certificado',
                    cod_evento: codEventoAtual,
                    cpf: cpf
                })
            })
            .then(r => r.json())
            .then(d => {
                if (d.sucesso) {
                    alert('Certificado emitido com sucesso!');
                    carregarParticipantes();
                } else {
                    alert('Erro: ' + (d.erro || 'Erro desconhecido'));
                }
            })
            .catch(() => alert('Erro ao emitir certificado'));
    }

    function excluirParticipantePart(cpf) {
        if (!confirm('Tem certeza que deseja excluir este participante?')) return;

        fetch('GerenciarEvento.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'excluir',
                    cod_evento: codEventoAtual,
                    cpf: cpf
                })
            })
            .then(r => r.json())
            .then(d => {
                if (d.sucesso) {
                    alert('Participante excluído com sucesso!');
                    carregarParticipantes();
                } else {
                    alert('Erro: ' + (d.erro || 'Erro desconhecido'));
                }
            })
            .catch(() => alert('Erro ao excluir participante'));
    }

    function editarDadosPart(cpf) {
        const participante = todosParticipantes.find(p => p.cpf === cpf);
        if (!participante) {
            alert('Participante não encontrado');
            return;
        }

        document.getElementById('edit-cpf-part').value = participante.cpf;
        document.getElementById('edit-cpf-display-part').value = formatarCPF(participante.cpf);
        document.getElementById('edit-nome-part').value = participante.nome;
        document.getElementById('edit-email-part').value = participante.email;
        document.getElementById('edit-ra-part').value = participante.ra || '';

        const modal = document.getElementById('modalEditarDadosPart');
        const modaisGlobais = document.getElementById('modais-globais');
        if (modal && modaisGlobais && modal.parentElement.id !== 'modais-globais') {
            modaisGlobais.appendChild(modal);
        }
        document.body.style.overflow = 'hidden';
        modal.classList.add('ativo');
    }

    function verificarCertificadoPart(codigo) {
        if (!codigo) {
            alert('Código de verificação não disponível');
            return;
        }

        // Abre a página de visualização do certificado em uma nova aba
        const url = `ContainerOrganizador.php?pagina=visualizarCertificado&codigo=${encodeURIComponent(codigo)}`;
        window.open(url, '_blank');
    }

    function formatarCPF(cpf) {
        return cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
    }

    // Modals - Removido o fechamento ao clicar fora para evitar perda de dados por missclick
    function fecharModalSeForFundo(event, modalId) {
        // Não fecha mais ao clicar fora - usuário deve usar botão Cancelar
        // Isso evita perda acidental de dados preenchidos
    }

    function abrirModalAdicionar() {
        const modal = document.getElementById('modalAdicionarParticipante');
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

    function fecharModalAdicionar() {
        const modal = document.getElementById('modalAdicionarParticipante');
        if (modal) {
            modal.classList.remove('ativo');
        }
        const form = document.getElementById('formAdicionarParticipante');
        if (form) {
            form.reset();
        }
        const msgCpf = document.getElementById('msg-cpf-existente');
        if (msgCpf) msgCpf.style.display = 'none';
        const addNome = document.getElementById('add-nome');
        const addEmail = document.getElementById('add-email');
        const addRa = document.getElementById('add-ra');
        if (addNome) addNome.disabled = false;
        if (addEmail) addEmail.disabled = false;
        if (addRa) addRa.disabled = false;
        document.body.style.overflow = '';
    }

    async function verificarCPFExistente() {
        const cpfInput = document.getElementById('add-cpf');
        const cpf = cpfInput.value.replace(/\D/g, '');

        if (cpf.length !== 11) return;

        try {
            const response = await fetch(`GerenciarEvento.php?action=verificar_cpf&cpf=${cpf}`);
            const data = await response.json();

            if (data.existe) {
                document.getElementById('add-nome').value = data.usuario.nome;
                document.getElementById('add-email').value = data.usuario.email;
                document.getElementById('add-ra').value = data.usuario.ra || '';

                document.getElementById('add-nome').disabled = true;
                document.getElementById('add-email').disabled = true;
                document.getElementById('add-ra').disabled = true;
                document.getElementById('add-cpf').disabled = true;

                document.getElementById('msg-cpf-existente').style.display = 'block';
            } else {
                document.getElementById('add-nome').disabled = false;
                document.getElementById('add-email').disabled = false;
                document.getElementById('add-ra').disabled = false;
                document.getElementById('msg-cpf-existente').style.display = 'none';
            }
        } catch (error) {
            console.error('Erro ao verificar CPF:', error);
        }
    }

    async function salvarNovoParticipante(event) {
        event.preventDefault();

        const cpf = document.getElementById('add-cpf').value.replace(/\D/g, '');
        const nome = document.getElementById('add-nome').value;
        const email = document.getElementById('add-email').value;
        const ra = document.getElementById('add-ra').value;

        if (cpf.length !== 11) {
            alert('CPF inválido');
            return;
        }

        try {
            const response = await fetch('GerenciarEvento.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    action: 'adicionar_participante',
                    cod_evento: codEventoAtual,
                    cpf: cpf,
                    nome: nome,
                    email: email,
                    ra: ra
                })
            });

            const data = await response.json();

            if (data.sucesso) {
                alert('Participante adicionado com sucesso!');
                fecharModalAdicionar();
                carregarParticipantes();
            } else {
                alert('Erro ao adicionar participante: ' + (data.erro || 'Erro desconhecido'));
            }
        } catch (error) {
            console.error('Erro:', error);
            alert('Erro ao adicionar participante');
        }
    }

    function abrirModalMensagemPart() {
        const modal = document.getElementById('modalEnviarMensagemPart');
        const modaisGlobais = document.getElementById('modais-globais');
        if (modal && modaisGlobais && modal.parentElement.id !== 'modais-globais') {
            modaisGlobais.appendChild(modal);
        }

        const msgSel = document.getElementById('msg-selecionados-part');
        const checkTodos = document.getElementById('msg-todos-part');

        if (participantesSelecionados.size > 0) {
            checkTodos.checked = false;
            msgSel.textContent = `Enviando para ${participantesSelecionados.size} participante(s) selecionado(s)`;
            msgSel.style.display = 'block';
        } else {
            checkTodos.checked = true;
            msgSel.style.display = 'none';
        }

        document.body.style.overflow = 'hidden';
        modal.classList.add('ativo');
    }

    function fecharModalMensagemPart() {
        document.getElementById('modalEnviarMensagemPart').classList.remove('ativo');
        document.getElementById('formEnviarMensagemPart').reset();
        document.body.style.overflow = '';
    }

    async function enviarMensagemParticipantes(event) {
        event.preventDefault();

        const titulo = document.getElementById('msg-titulo-part').value;
        const conteudo = document.getElementById('msg-conteudo-part').value;
        const enviarTodos = document.getElementById('msg-todos-part').checked;

        const destinatarios = enviarTodos ?
            todosParticipantes.map(p => p.cpf) :
            Array.from(participantesSelecionados);

        if (destinatarios.length === 0) {
            alert('Selecione pelo menos um participante');
            return;
        }

        if (!confirm(`Enviar notificação para ${destinatarios.length} participante(s)?`)) {
            return;
        }

        try {
            const response = await fetch('GerenciarEvento.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    action: 'enviar_notificacao',
                    cod_evento: codEventoAtual,
                    titulo: titulo,
                    conteudo: conteudo,
                    destinatarios: JSON.stringify(destinatarios)
                })
            });

            const data = await response.json();

            if (data.sucesso) {
                alert(`Notificação enviada com sucesso para ${data.total_enviadas} participante(s)!`);
                fecharModalMensagemPart();
            } else {
                alert('Erro ao enviar notificação: ' + (data.erro || 'Erro desconhecido'));
            }
        } catch (error) {
            console.error('Erro:', error);
            alert('Erro ao enviar notificação');
        }
    }

    function fecharModalEditarPart() {
        document.getElementById('modalEditarDadosPart').classList.remove('ativo');
        document.getElementById('formEditarDadosPart').reset();
        document.body.style.overflow = '';
    }

    async function salvarEdicaoPart(event) {
        event.preventDefault();

        const cpf = document.getElementById('edit-cpf-part').value;
        const nome = document.getElementById('edit-nome-part').value;
        const email = document.getElementById('edit-email-part').value;
        const ra = document.getElementById('edit-ra-part').value;

        try {
            const response = await fetch('GerenciarEvento.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    action: 'editar_dados',
                    cpf: cpf,
                    nome: nome,
                    email: email,
                    ra: ra
                })
            });

            const data = await response.json();

            if (data.sucesso) {
                alert('Dados atualizados com sucesso!');
                fecharModalEditarPart();
                carregarParticipantes();
            } else {
                alert('Erro ao atualizar dados: ' + (data.erro || 'Erro desconhecido'));
            }
        } catch (error) {
            console.error('Erro:', error);
            alert('Erro ao salvar alterações');
        }
    }

    // Importar/Exportar
    function importarListaPresenca() {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = '.csv,.xlsx';
        input.onchange = async (e) => {
            const file = e.target.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('action', 'importar_presenca');
            formData.append('cod_evento', codEventoAtual);
            formData.append('arquivo', file);

            try {
                const response = await fetch('GerenciarEvento.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                if (data.sucesso) {
                    alert(`Importação concluída!\nConfirmados: ${data.confirmados}\nErros: ${data.erros}`);
                    carregarParticipantes();
                } else {
                    alert('Erro ao importar: ' + (data.erro || 'Erro desconhecido'));
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro ao importar arquivo');
            }
        };
        input.click();
    }

    function exportarListaPresenca() {
        window.location.href = `GerenciarEvento.php?action=exportar_presenca&cod_evento=${codEventoAtual}`;
    }

    function importarListaInscritos() {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = '.csv,.xlsx';
        input.onchange = async (e) => {
            const file = e.target.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('action', 'importar_inscritos');
            formData.append('cod_evento', codEventoAtual);
            formData.append('arquivo', file);

            try {
                const response = await fetch('GerenciarEvento.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                if (data.sucesso) {
                    alert(`Importação concluída!\nInscritos: ${data.inscritos}\nErros: ${data.erros}`);
                    carregarParticipantes();
                } else {
                    alert('Erro ao importar: ' + (data.erro || 'Erro desconhecido'));
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro ao importar arquivo');
            }
        };
        input.click();
    }

    function exportarListaInscritos() {
        window.location.href = `GerenciarEvento.php?action=exportar_inscritos&cod_evento=${codEventoAtual}`;
    }

    // Ações em massa
    async function confirmarPresencasEmMassa() {
        if (todosParticipantes.length === 0) {
            alert('Não há participantes inscritos neste evento');
            return;
        }

        if (participantesSelecionados.size === 0) {
            alert('Selecione pelo menos um participante');
            return;
        }

        if (!confirm(`Confirmar presença de ${participantesSelecionados.size} participante(s)?`)) {
            return;
        }

        let confirmados = 0;
        let erros = 0;

        for (const cpf of participantesSelecionados) {
            try {
                const response = await fetch('GerenciarEvento.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'confirmar_presenca',
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
                console.error('Erro ao confirmar presença:', error);
            }
        }

        alert(`Operação concluída!\nConfirmados: ${confirmados}\nErros: ${erros}`);
        participantesSelecionados.clear();
        carregarParticipantes();
    }

    async function emitirCertificadosEmMassa() {
        if (todosParticipantes.length === 0) {
            alert('Não há participantes inscritos neste evento');
            return;
        }

        if (participantesSelecionados.size === 0) {
            alert('Selecione pelo menos um participante');
            return;
        }

        if (!confirm(`Emitir certificado para ${participantesSelecionados.size} participante(s)?\n\nAtenção: Apenas participantes com presença confirmada receberão o certificado.`)) {
            return;
        }

        let emitidos = 0;
        let erros = 0;
        let semPresenca = 0;

        for (const cpf of participantesSelecionados) {
            try {
                const response = await fetch('GerenciarEvento.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'emitir_certificado',
                        cod_evento: codEventoAtual,
                        cpf: cpf
                    })
                });

                const data = await response.json();
                if (data.sucesso) {
                    emitidos++;
                } else if (data.erro === 'presenca_nao_confirmada') {
                    semPresenca++;
                } else {
                    erros++;
                }
            } catch (error) {
                erros++;
                console.error('Erro ao emitir certificado:', error);
            }
        }

        let mensagem = `Operação concluída!\nCertificados emitidos: ${emitidos}`;
        if (semPresenca > 0) {
            mensagem += `\nSem presença confirmada: ${semPresenca}`;
        }
        if (erros > 0) {
            mensagem += `\nErros: ${erros}`;
        }

        alert(mensagem);
        participantesSelecionados.clear();
        carregarParticipantes();
    }

    async function excluirParticipantesEmMassa() {
        if (todosParticipantes.length === 0) {
            alert('Não há participantes inscritos neste evento');
            return;
        }

        if (participantesSelecionados.size === 0) {
            alert('Selecione pelo menos um participante');
            return;
        }

        if (!confirm(`ATENÇÃO: Excluir ${participantesSelecionados.size} participante(s)?\n\nEsta ação não pode ser desfeita!`)) {
            return;
        }

        let excluidos = 0;
        let erros = 0;

        for (const cpf of participantesSelecionados) {
            try {
                const response = await fetch('GerenciarEvento.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'excluir',
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
                console.error('Erro ao excluir participante:', error);
            }
        }

        alert(`Operação concluída!\nExcluídos: ${excluidos}\nErros: ${erros}`);
        participantesSelecionados.clear();
        carregarParticipantes();
    }

    // Inicializa quando o conteúdo for carregado
    carregarParticipantes();

    // Listener para resize: re-renderiza se mudar entre mobile e desktop
    if (!window.__participantesResizeAttached) {
        window.__participantesResizeAttached = true;
        window.addEventListener('resize', () => {
            const nowMobile = window.matchMedia('(max-width: 768px)').matches;
            if (nowMobile !== window.__lastParticipantesIsMobile && todosParticipantes && todosParticipantes.length > 0) {
                renderizarParticipantes();
            }
        });
    }

    // Expõe funções globalmente para serem chamadas por GerenciarEvento.php
    window.renderizarParticipantes = renderizarParticipantes;
    window.carregarParticipantes = carregarParticipantes;
</script>

<!-- Modal: Escolher Formato de Exportação -->
<div id="modalEscolherFormato" class="modal-overlay" onclick="window.fecharModalSeForFundo(event, 'modalEscolherFormato')">
    <div class="modal-conteudo" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2>Escolha o formato de exportação</h2>
            <button class="btn-fechar-modal" onclick="window.fecharModalFormato(); event.stopPropagation();" aria-label="Fechar">&times;</button>
        </div>
        <div class="modal-body">
            <div class="formatos-exportacao">
                <button class="btn-formato" onclick="window.executarExportacao('csv'); event.stopPropagation();">
                    <img src="../Imagens/CSV.svg" alt="CSV">
                    <span>CSV</span>
                    <small>Valores separados por vírgula</small>
                </button>
                <button class="btn-formato" onclick="window.executarExportacao('xlsx'); event.stopPropagation();">
                    <img src="../Imagens/Excel.svg" alt="Excel">
                    <span>Excel</span>
                    <small>Microsoft Excel (.xlsx)</small>
                </button>
                <button class="btn-formato" onclick="window.executarExportacao('ods'); event.stopPropagation();">
                    <img src="../Imagens/LibreOffice.svg" alt="LibreOffice">
                    <span>LibreOffice</span>
                    <small>Planilha ODS</small>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Informações sobre Importação -->
<div id="modalInfoImportacao" class="modal-overlay" onclick="window.fecharModalSeForFundo(event, 'modalInfoImportacao')">
    <div class="modal-conteudo" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2>Importar Arquivo</h2>
            <button class="btn-fechar-modal" onclick="window.fecharModalImportacao(); event.stopPropagation();" aria-label="Fechar">&times;</button>
        </div>
        <div class="modal-body">
            <div class="info-importacao">
                <div class="info-item">
                    <h3>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 8px;">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                        </svg>
                        Formatos aceitos:
                    </h3>
                    <p>CSV (.csv), Excel (.xlsx, .xls), LibreOffice (.ods)</p>
                </div>
                <div class="info-item">
                    <h3>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 8px;">
                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                            <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                            <line x1="12" y1="22.08" x2="12" y2="12"></line>
                        </svg>
                        Tamanho máximo:
                    </h3>
                    <p>10 MB por arquivo</p>
                </div>
                <div class="info-item">
                    <h3>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 8px;">
                            <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
                            <rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
                        </svg>
                        Estrutura do arquivo:
                    </h3>
                    <p>O arquivo deve conter as seguintes colunas:</p>
                    <ul>
                        <li><strong>CPF</strong> - CPF do participante (obrigatório)</li>
                        <li><strong>Nome</strong> - Nome completo</li>
                        <li><strong>RA</strong> - Registro Acadêmico (opcional)</li>
                        <li><strong>Email</strong> - E-mail do participante</li>
                    </ul>
                </div>
                <div class="info-item">
                    <h3>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 8px;">
                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                            <line x1="12" y1="9" x2="12" y2="13"></line>
                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                        </svg>
                        Importante:
                    </h3>
                    <ul>
                        <li>A primeira linha deve conter os nomes das colunas</li>
                        <li>CPFs duplicados serão ignorados</li>
                        <li>Participantes já cadastrados serão atualizados</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button class="botao botao-primario" onclick="window.selecionarArquivoImportacao(); event.stopPropagation();">
                    Selecionar Arquivo
                </button>
                <button class="botao botao-secundario" onclick="window.fecharModalImportacao(); event.stopPropagation();">
                    Cancelar
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    /* Estilos dos Modais */
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.6);
        z-index: 10000;
        justify-content: center;
        align-items: center;
    }

    .modal-overlay.ativo {
        display: flex;
    }

    .modal-conteudo {
        background: white;
        border-radius: 12px;
        max-width: 600px;
        width: 90%;
        max-height: 85vh;
        overflow-y: auto;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        z-index: 10001;
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 24px;
        border-bottom: 1px solid #e0e0e0;
    }

    .modal-header h2 {
        margin: 0;
        font-size: 1.5rem;
        color: var(--azul-escuro);
    }

    .btn-fechar-modal {
        background: none;
        border: none;
        font-size: 2rem;
        color: #666;
        cursor: pointer;
        padding: 0;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: background-color 0.2s;
    }

    .btn-fechar-modal:hover {
        background-color: #f0f0f0;
    }

    .modal-body {
        padding: 24px;
    }

    .modal-footer {
        display: flex;
        gap: 12px;
        justify-content: flex-end;
        padding: 16px 24px;
        border-top: 1px solid #e0e0e0;
    }

    /* Estilos dos botões de formato */
    .formatos-exportacao {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 16px;
    }

    .btn-formato {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
        padding: 20px;
        background: var(--branco);
        border: 2px solid var(--azul-escuro);
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s;
        font-size: 15px;
    }

    .btn-formato:hover {
        border-color: var(--azul-escuro);
        background-color: #f0f7ff;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(101, 152, 210, 0.3);
    }

    .btn-formato img {
        width: 48px;
        height: 48px;
    }

    .btn-formato span {
        font-weight: 600;
        color: var(--azul-escuro);
        font-size: 1.1rem;
    }

    .btn-formato small {
        color: var(--cinza-escuro);
        font-size: 0.85rem;
    }

    /* Estilos da informação de importação */
    .info-importacao {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .info-item h3 {
        margin: 0 0 8px 0;
        color: var(--azul-escuro);
        font-size: 1.1rem;
    }

    .info-item p {
        margin: 0;
        color: var(--cinza-escuro);
        line-height: 1.6;
    }

    .info-item ul {
        margin: 8px 0 0 0;
        padding-left: 20px;
        color: var(--cinza-escuro);
    }

    .info-item li {
        margin: 4px 0;
        line-height: 1.6;
    }

    .botao-primario {
        background-color: var(--azul-escuro);
        color: var(--branco);
        padding: 12px 28px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        font-size: 15px;
        transition: all 0.2s;
    }

    .botao-primario:hover {
        background-color: var(--azul-escuro);
        opacity: 0.9;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(101, 152, 210, 0.3);
    }

    .botao-secundario {
        background-color: var(--branco);
        color: var(--azul-escuro);
        padding: 12px 28px;
        border: 2px solid var(--azul-escuro);
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        font-size: 15px;
        transition: all 0.2s;
    }

    .botao-secundario:hover {
        background-color: #f0f7ff;
        transform: translateY(-1px);
    }

    /* ===== ESTILOS PARA LAYOUT COMPACTO E VISÍVEL ===== */
    .secao-superior-compacta {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-bottom: 12px;
    }

    .barra-pesquisa-wrapper-compacta {
        width: 100%;
        margin-bottom: 4px;
    }

    .barra-pesquisa-wrapper-compacta .barra-pesquisa-container {
        display: flex;
        justify-content: center;
        align-items: center;
        width: 100%;
    }

    .barra-pesquisa-wrapper-compacta .barra-pesquisa {
        width: 100%;
        max-width: 580px;
        display: flex;
        justify-content: center;
    }

    .acoes-rapidas-wrapper {
        width: 100%;
    }

    .secao-titulo-compacta {
        color: var(--branco);
        font-size: 18px;
        font-weight: 700;
        text-align: center;
        margin: 0 0 8px 0;
        text-shadow: 0px 2px 8px rgba(0, 0, 0, 0.3);
        letter-spacing: 0.3px;
    }

    .secao-acoes-massa-compacta {
        margin-top: 8px;
        margin-bottom: 12px;
    }

    .secao-acoes-massa-compacta .acoes-em-massa {
        margin-top: 4px;
    }

    /* Reduzir espaçamento geral da seção de gerenciamento */
    .secao-gerenciamento {
        gap: 10px;
        padding: 0;
    }

    /* Melhorar visibilidade dos botões de ação */
    .botao-acao {
        background-color: var(--branco) !important;
        border: 2px solid rgba(101, 152, 210, 0.3) !important;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.12), 0 1px 4px rgba(0, 0, 0, 0.08) !important;
        font-weight: 600 !important;
        transition: all 0.2s ease !important;
    }

    .botao-acao:hover {
        transform: translateY(-3px) !important;
        box-shadow: 0 6px 20px rgba(101, 152, 210, 0.25), 0 3px 8px rgba(0, 0, 0, 0.15) !important;
        border-color: var(--azul-escuro) !important;
        background-color: #f0f7ff !important;
    }

    .botao-acao:active {
        transform: translateY(-1px) !important;
    }

    /* Melhorar visibilidade dos botões de ação em massa */
    .botao-em-massa {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15), 0 2px 6px rgba(0, 0, 0, 0.1) !important;
        font-weight: 700 !important;
        transition: all 0.2s ease !important;
        border: 2px solid transparent !important;
    }

    .botao-em-massa:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.2), 0 3px 8px rgba(0, 0, 0, 0.15) !important;
        filter: brightness(1.1) !important;
    }

    .botao-em-massa:active {
        transform: translateY(0) !important;
    }

    .botao-em-massa.botao-branco {
        border-color: rgba(0, 0, 0, 0.1) !important;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.12) !important;
    }

    .botao-em-massa.botao-branco:hover {
        background-color: #f8f9fa !important;
        border-color: rgba(0, 0, 0, 0.2) !important;
    }

    /* Reduzir espaçamento antes da lista */
    .secao-gerenciamento>div:last-child {
        margin-top: 8px;
    }

    .contador-participantes {
        margin-bottom: 12px !important;
    }

    .envoltorio-tabela {
        margin-top: 0 !important;
    }

    /* Melhorar grade de ações */
    .grade-acoes-gerenciamento {
        gap: 10px !important;
    }

    /* ===== RESPONSIVIDADE PARA TABLETS (768px - 1024px) ===== */
    @media (min-width: 769px) and (max-width: 1024px) {
        .secao-superior-compacta {
            gap: 12px;
            margin-bottom: 14px;
        }

        .barra-pesquisa-wrapper-compacta .barra-pesquisa {
            max-width: 100%;
        }

        .secao-titulo-compacta {
            font-size: 17px;
            margin-bottom: 10px;
        }

        .grade-acoes-gerenciamento {
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)) !important;
            gap: 10px !important;
            max-width: 100% !important;
        }

        .botao-acao {
            padding: 10px 14px !important;
            font-size: 12px !important;
        }

        .botao-acao span {
            font-size: 12px !important;
        }

        .botao-acao img {
            height: 18px !important;
            width: 18px !important;
        }

        .secao-acoes-massa-compacta {
            margin-top: 10px;
            margin-bottom: 14px;
        }

        .acoes-em-massa {
            gap: 12px !important;
            flex-wrap: wrap !important;
        }

        .botao-em-massa {
            padding: 6px 18px !important;
            font-size: 14px !important;
        }

        .botao-em-massa img {
            height: 20px !important;
        }

        .secao-gerenciamento {
            gap: 12px;
        }

        .contador-participantes {
            padding: 12px 20px !important;
            margin-bottom: 14px !important;
        }

        .contador-participantes span {
            font-size: 15px !important;
        }
    }

    /* ===== RESPONSIVIDADE PARA CELULARES (até 768px) ===== */
    @media (max-width: 768px) {
        .secao-superior-compacta {
            gap: 8px;
            margin-bottom: 10px;
        }

        .barra-pesquisa-wrapper-compacta {
            margin-bottom: 6px;
        }

        .barra-pesquisa-wrapper-compacta .barra-pesquisa {
            max-width: 100%;
        }

        .campo-pesquisa-wrapper {
            width: 100% !important;
            max-width: 100% !important;
        }

        .secao-titulo-compacta {
            font-size: 16px;
            margin-bottom: 8px;
            padding: 0 10px;
        }

        .grade-acoes-gerenciamento {
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)) !important;
            gap: 8px !important;
            max-width: 100% !important;
        }

        .botao-acao {
            padding: 10px 12px !important;
            font-size: 11px !important;
            min-height: 44px !important;
            flex-direction: column !important;
            gap: 4px !important;
        }

        .botao-acao span {
            font-size: 11px !important;
            white-space: normal !important;
            text-align: center !important;
            line-height: 1.2 !important;
        }

        .botao-acao img {
            height: 18px !important;
            width: 18px !important;
        }

        .secao-acoes-massa-compacta {
            margin-top: 8px;
            margin-bottom: 12px;
        }

        .acoes-em-massa {
            flex-direction: column !important;
            gap: 10px !important;
            align-items: stretch !important;
        }

        .botao-em-massa {
            width: 100% !important;
            padding: 10px 16px !important;
            font-size: 14px !important;
            justify-content: center !important;
            border-radius: 8px !important;
        }

        .botao-em-massa img {
            height: 20px !important;
        }

        .secao-gerenciamento {
            gap: 8px;
            padding: 0 8px;
        }

        .contador-participantes {
            padding: 10px 16px !important;
            margin-bottom: 10px !important;
            border-radius: 8px !important;
        }

        .contador-participantes span {
            font-size: 14px !important;
        }

        .envoltorio-tabela {
            overflow-x: auto !important;
            -webkit-overflow-scrolling: touch !important;
        }

        .tabela-participantes {
            font-size: 12px !important;
            min-width: 600px !important;
        }

        .tabela-participantes th,
        .tabela-participantes td {
            padding: 8px 10px !important;
            font-size: 12px !important;
        }

        .coluna-dados {
            max-width: 250px !important;
        }

        .coluna-dados p {
            font-size: 11px !important;
            margin: 0 0 2px 0 !important;
        }

        .coluna-dados strong {
            font-size: 11px !important;
        }

        .grupo-acoes,
        .grupo-status {
            min-width: 150px !important;
            gap: 4px !important;
        }

        .botao-acao-tabela {
            padding: 6px 10px !important;
            font-size: 11px !important;
        }

        .botao-acao-tabela img {
            height: 16px !important;
            width: 16px !important;
        }

        .linha-status {
            font-size: 11px !important;
            gap: 6px !important;
        }

        .emblema-status {
            font-size: 10px !important;
            padding: 2px 8px !important;
        }

        .emblema-status img {
            height: 14px !important;
            width: 14px !important;
        }
    }

    /* ===== RESPONSIVIDADE PARA CELULARES PEQUENOS (até 480px) ===== */
    @media (max-width: 480px) {
        .secao-superior-compacta {
            gap: 6px;
            margin-bottom: 8px;
        }

        .secao-titulo-compacta {
            font-size: 14px;
            margin-bottom: 6px;
            padding: 0 5px;
        }

        .grade-acoes-gerenciamento {
            grid-template-columns: 1fr 1fr !important;
            gap: 6px !important;
        }

        .botao-acao {
            padding: 8px 10px !important;
            font-size: 10px !important;
            min-height: 40px !important;
        }

        .botao-acao span {
            font-size: 10px !important;
        }

        .botao-acao img {
            height: 16px !important;
            width: 16px !important;
        }

        .secao-acoes-massa-compacta {
            margin-top: 6px;
            margin-bottom: 10px;
        }

        .acoes-em-massa {
            gap: 8px !important;
        }

        .botao-em-massa {
            padding: 8px 14px !important;
            font-size: 13px !important;
        }

        .secao-gerenciamento {
            gap: 6px;
            padding: 0 5px;
        }

        .contador-participantes {
            padding: 8px 12px !important;
            margin-bottom: 8px !important;
        }

        .contador-participantes span {
            font-size: 13px !important;
        }

        .tabela-participantes {
            min-width: 550px !important;
        }

        .tabela-participantes th,
        .tabela-participantes td {
            padding: 6px 8px !important;
            font-size: 11px !important;
        }
    }

    /* ===== AJUSTES ADICIONAIS PARA RESPONSIVIDADE ===== */

    /* Barra de pesquisa responsiva */
    @media (max-width: 768px) {
        .campo-pesquisa {
            font-size: 14px !important;
            padding: 0 12px !important;
        }

        .botao-pesquisa {
            width: 50px !important;
            height: 50px !important;
        }

        .icone-pesquisa {
            width: 50px !important;
            height: 50px !important;
        }
    }

    @media (max-width: 480px) {
        .campo-pesquisa {
            font-size: 13px !important;
            padding: 0 10px !important;
        }

        .botao-pesquisa {
            width: 45px !important;
            height: 45px !important;
        }

        .icone-pesquisa {
            width: 45px !important;
            height: 45px !important;
        }
    }

    /* Modais responsivos */
    @media (max-width: 768px) {

        .modal-editar,
        .modal-conteudo {
            width: 95% !important;
            max-width: 95% !important;
            padding: 20px 16px !important;
            margin: 10px !important;
        }

        .modal-header {
            padding: 16px !important;
        }

        .modal-header h2 {
            font-size: 1.3rem !important;
        }

        .modal-body {
            padding: 16px !important;
        }

        .modal-footer {
            padding: 12px 16px !important;
            flex-direction: column-reverse !important;
            gap: 8px !important;
        }

        .btn-modal {
            width: 100% !important;
            padding: 12px !important;
        }

        .form-group {
            margin-bottom: 16px !important;
        }

        .form-group input,
        .form-group textarea {
            font-size: 14px !important;
            padding: 10px !important;
        }
    }

    @media (max-width: 480px) {

        .modal-editar,
        .modal-conteudo {
            width: 98% !important;
            padding: 16px 12px !important;
        }

        .modal-header h2 {
            font-size: 1.2rem !important;
        }

        .formatos-exportacao {
            grid-template-columns: 1fr !important;
            gap: 12px !important;
        }

        .btn-formato {
            padding: 16px !important;
        }
    }

    /* Ajustes para tabela em telas pequenas - melhorar scroll horizontal */
    @media (max-width: 768px) {
        .envoltorio-tabela {
            position: relative !important;
        }

        .envoltorio-tabela::before {
            content: '← Role para ver mais →';
            display: block;
            text-align: center;
            padding: 8px;
            background: rgba(101, 152, 210, 0.1);
            color: var(--azul-escuro);
            font-size: 12px;
            font-weight: 600;
            border-radius: 8px 8px 0 0;
        }
    }

    /* ===== TRANSFORMAR TABELA EM CARDS NO MOBILE ===== */
    @media (max-width: 768px) {

        /* Oculta a tabela e mostra cards */
        .envoltorio-tabela {
            overflow: visible !important;
        }

        .envoltorio-tabela::before {
            display: none;
        }

        .tabela-participantes {
            display: none;
        }

        /* Container de cards */
        .envoltorio-tabela::after {
            content: '';
            display: block;
        }

        /* Cria os cards a partir das linhas da tabela usando JavaScript */
        .tabela-participantes tbody tr {
            display: block;
            background: var(--branco);
            border: 1px solid var(--azul-escuro);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .tabela-participantes tbody tr:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .tabela-participantes tbody tr td {
            display: block;
            border: none !important;
            padding: 8px 0 !important;
            text-align: left !important;
        }

        .tabela-participantes tbody tr td::before {
            content: attr(data-label);
            font-weight: 600;
            color: var(--azul-escuro);
            display: block;
            margin-bottom: 4px;
            font-size: 12px;
            text-transform: uppercase;
        }

        .tabela-participantes tbody tr td:first-child {
            border-bottom: 2px solid var(--azul-escuro);
            padding-bottom: 12px !important;
            margin-bottom: 8px;
            text-align: center !important;
        }

        .tabela-participantes tbody tr td:first-child::before {
            content: none;
        }

        .tabela-participantes thead {
            display: none;
        }

        /* Ajusta checkbox de seleção no card */
        .tabela-participantes tbody tr td.coluna-selecionar {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* Ajusta grupo de ações e status para melhor visualização em card */
        .grupo-acoes,
        .grupo-status {
            min-width: auto;
            width: 100%;
        }

        .botao-acao-tabela {
            width: 100%;
            justify-content: center;
        }

        .linha-status {
            justify-content: space-between;
        }
    }

    @media (max-width: 480px) {
        .tabela-participantes tbody tr {
            padding: 12px;
            margin-bottom: 10px;
        }

        .tabela-participantes tbody tr td {
            padding: 6px 0 !important;
        }

        .tabela-participantes tbody tr td::before {
            font-size: 11px;
        }

        .coluna-dados p {
            font-size: 12px !important;
        }

        .botao-acao-tabela {
            padding: 8px 12px !important;
            font-size: 11px !important;
        }
    }
</style>