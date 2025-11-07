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
                <span>Adicionar Organizador</span>
                <img src="../Imagens/Adicionar_participante.svg" alt="Adicionar icon">
            </button>
            <button class="botao botao-acao" id="btn-enviar-mensagem-organizacao">
                <span>Enviar Mensagem</span>
                <img src="../Imagens/Email.svg" alt="Mensagem icon">
            </button>
        </div>
    </div>

    <div class="divisor-secao"></div>

    <!-- Seção: Buscar Membros -->
    <div>
        <h2 class="secao-titulo">Buscar Membros</h2>
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
        </div>
    </div>

    <div class="divisor-secao"></div>

    <!-- Seção: Ações em Massa -->
    <div>
        <h2 class="secao-titulo">Ações em Massa</h2>
        <div class="acoes-em-massa" id="acoes-em-massa-org">
            <button class="botao botao-em-massa botao-branco" id="botao-toggle-selecao-org">
                <span id="texto-toggle-selecao-org">Selecionar Todos</span>
                <img src="../Imagens/Grupo_de_pessoas.svg" alt="">
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
                        <th class="Titulo_Tabela">Selecionar</th>
                        <th class="Titulo_Tabela">Tipo</th>
                        <th class="Titulo_Tabela">Dados do Membro</th>
                        <th class="Titulo_Tabela">Modificar</th>
                        <th class="Titulo_Tabela">Certificado</th>
                    </tr>
                </thead>
                <tbody id="tbody-organizacao">
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 30px; color: var(--botao);">
                            Carregando membros...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Adicionar Organizador -->
<div class="modal-overlay" id="modalAdicionarColaborador">
    <div class="modal-editar">
        <div class="modal-header">
            <h2>Adicionar Organizador</h2>
            <button class="btn-fechar-modal" onclick="fecharModalAdicionarColaborador(); event.stopPropagation();">&times;</button>
        </div>
        <form id="formAdicionarColaborador" onsubmit="salvarNovoColaborador(event); event.stopPropagation();">
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

            <div class="form-group">
                <label for="add-tipo-colab">Tipo*</label>
                <select id="add-tipo-colab" required style="width: 100%; padding: 12px; border: 1px solid var(--azul-escuro); border-radius: 8px; font-size: 15px;">
                    <option value="Organizador">Organizador</option>
                </select>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-modal btn-cancelar" onclick="fecharModalAdicionarColaborador(); event.stopPropagation();">Cancelar</button>
                <button type="submit" class="btn-modal btn-salvar" onclick="event.stopPropagation();">Adicionar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Enviar Mensagem -->
<div class="modal-overlay" id="modalEnviarMensagemOrg">
    <div class="modal-editar">
        <div class="modal-header">
            <h2>Enviar Mensagem aos Membros da Organização</h2>
            <button class="btn-fechar-modal" onclick="fecharModalMensagemOrganizacao(); event.stopPropagation();">&times;</button>
        </div>
        <form id="formEnviarMensagemOrg" onsubmit="enviarMensagemOrganizacao(event); event.stopPropagation();">
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
                <small style="color: #666;">A mensagem será enviada para todos os membros da organização (${todosOrganizacao.length} ${todosOrganizacao.length === 1 ? 'membro' : 'membros'})</small>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-modal btn-cancelar" onclick="fecharModalMensagemOrganizacao(); event.stopPropagation();">Cancelar</button>
                <button type="submit" class="btn-modal btn-salvar" onclick="event.stopPropagation();">Enviar Notificação</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Editar Membro -->
<div id="modalEditarMembro" class="modal-overlay">
    <div class="modal-content" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h3>Editar Membro da Organização</h3>
            <button class="botao-fechar" onclick="fecharModalEditarMembro(); event.stopPropagation()">×</button>
        </div>
        <form id="formEditarMembro" onsubmit="salvarEdicaoMembro(event); event.stopPropagation()">
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

            <div class="campo-formulario">
                <label for="edit-tipo-org">Tipo</label>
                <select id="edit-tipo-org" required>
                    <option value="Organizador">Organizador</option>
                </select>
            </div>

            <div class="botoes-modal">
                <button type="button" class="botao-secundario" onclick="fecharModalEditarMembro(); event.stopPropagation()">Cancelar</button>
                <button type="submit" class="botao-primario" onclick="event.stopPropagation()">Salvar Alterações</button>
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

        if (!tbody || !totalSpan) {
            return;
        }

        totalSpan.textContent = `Total de membros: ${todosOrganizacao.length}`;

        if (todosOrganizacao.length === 0) {
            tbody.innerHTML = `
            <tr>
                <td colspan="5" style="text-align: center; padding: 30px; color: var(--botao);">
                    Nenhum membro encontrado
                </td>
            </tr>
        `;
            return;
        }

        tbody.innerHTML = todosOrganizacao.map(membro => {
            const isChecked = membrosSelecionados.has(membro.cpf);
            const rowClass = isChecked ? 'linha-selecionada' : '';
            
            const tipoBadge = '<span class="tipo-membro tipo-organizador">Organizador</span>';

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
            <tr class="${rowClass}">
                <td style="text-align: center;">
                    <input type="checkbox" class="checkbox-selecionar-org" value="${membro.cpf}" ${isChecked ? 'checked' : ''}>
                </td>
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
                    <button class="botao-editar" onclick="abrirModalEditarMembro('${membro.cpf}')">
                        <img src="../Imagens/Editar.svg" alt="Editar">
                    </button>
                    <button class="botao-excluir" onclick="excluirMembro('${membro.cpf}')">
                        <img src="../Imagens/Excluir.svg" alt="Excluir">
                    </button>
                </td>
                <td style="text-align: center;">
                    ${statusCertificado}
                </td>
            </tr>
        `;
        }).join('');

        inicializarEventosOrganizacao();
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
                    alert('Erro ao emitir certificado: ' + (dados.erro || 'Erro desconhecido'));
                }
            })
            .catch(erro => {
                alert('Erro ao emitir certificado');
            });
    }

    // ===== AÇÕES RÁPIDAS =====
    
    // Funções dos modais - declaradas globalmente primeiro
    function abrirModalAdicionarColaborador() {
        const modal = document.getElementById('modalAdicionarColaborador');
        if (modal) {
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
    }

    function abrirModalMensagemOrganizacao() {
        const modal = document.getElementById('modalEnviarMensagemOrg');
        if (modal) {
            modal.classList.add('ativo');
        }
    }

    function fecharModalMensagemOrganizacao() {
        document.getElementById('modalEnviarMensagemOrg').classList.remove('ativo');
        document.getElementById('formEnviarMensagemOrg').reset();
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
        const tipo = document.getElementById('add-tipo-colab').value;

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
                    ra: ra,
                    tipo: tipo
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

        const destinatarios = todosOrganizacao.map(m => m.cpf);

        if (destinatarios.length === 0) {
            alert('Não há membros para enviar mensagem');
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
        document.getElementById('edit-tipo-org').value = membro.tipo || 'Colaborador';
        document.getElementById('edit-cpf-display-org').value = cpf;

        document.getElementById('modalEditarMembro').classList.add('ativo');
    }

    function fecharModalEditarMembro() {
        document.getElementById('modalEditarMembro').classList.remove('ativo');
        document.getElementById('formEditarMembro').reset();
    }

    async function salvarEdicaoMembro(event) {
        event.preventDefault();

        const cpf = document.getElementById('edit-cpf-org').value;
        const nome = document.getElementById('edit-nome-org').value;
        const email = document.getElementById('edit-email-org').value;
        const ra = document.getElementById('edit-ra-org').value;
        const tipo = document.getElementById('edit-tipo-org').value;

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
                    ra: ra,
                    tipo: tipo
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
        if (!confirm('Deseja realmente excluir este membro da organização?')) {
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

    // ===== FUNÇÕES DE AÇÕES EM MASSA =====
    
    async function emitirCertificadosEmMassaOrg() {
        if (todosOrganizacao.length === 0) {
            alert('Não há membros na organização');
            return;
        }

        if (membrosSelecionados.size === 0) {
            alert('Selecione pelo menos um membro');
            return;
        }

        if (!confirm(`Emitir certificado para ${membrosSelecionados.size} membro(s)?`)) {
            return;
        }

        let emitidos = 0;
        let erros = 0;

        for (const cpf of membrosSelecionados) {
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
        // Eventos de checkbox
        if (!window.__orgCheckboxBound) {
            window.__orgCheckboxBound = true;
            document.addEventListener('change', function(e) {
                if (e.target.classList && e.target.classList.contains('checkbox-selecionar-org')) {
                    const tr = e.target.closest('tr');
                    tr.classList.toggle('linha-selecionada', e.target.checked);
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
                        cb.closest('tr').classList.remove('linha-selecionada');
                        membrosSelecionados.delete(cb.value);
                    });
                } else {
                    membrosSelecionados.clear();
                    document.querySelectorAll('.checkbox-selecionar-org').forEach(cb => {
                        cb.checked = true;
                        cb.closest('tr').classList.add('linha-selecionada');
                        membrosSelecionados.add(cb.value);
                    });
                }

                atualizarVisibilidadeBotoesAcaoOrg();
                atualizarTextoBotaoToggleOrg();
            });
        }

        // Botões de ação em massa
        const btnEmitirCertMassa = document.getElementById('btn-emitir-certificados-massa-org');
        const btnExcluirMassa = document.getElementById('btn-excluir-membros-massa');

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
        const termo = document.getElementById('busca-organizacao').value.toLowerCase().trim();
        const linhas = document.querySelectorAll('#tbody-organizacao tr');

        if (!termo) {
            linhas.forEach(linha => linha.style.display = '');
            return;
        }

        linhas.forEach(linha => {
            const texto = linha.textContent.toLowerCase();
            linha.style.display = texto.includes(termo) ? '' : 'none';
        });
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
</script>