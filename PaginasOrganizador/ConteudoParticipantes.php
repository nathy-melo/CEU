<?php
// Apenas o conteúdo HTML da aba de Participantes
// Este arquivo será carregado dinamicamente via AJAX
?>

<div class="secao-gerenciamento">
    <!-- Seção: Ações Rápidas -->
    <div>
        <h2 class="secao-titulo">Ações Rápidas</h2>
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

    <div class="divisor-secao"></div>

    <!-- Seção: Buscar Participantes -->
    <div>
        <h2 class="secao-titulo">Buscar Participantes</h2>
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
        </div>
    </div>

    <div class="divisor-secao"></div>

    <!-- Seção: Ações em Massa -->
    <div>
        <h2 class="secao-titulo">Ações em Massa</h2>
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

    <div class="divisor-secao"></div>

    <!-- Seção: Lista de Participantes -->
    <div>
        <div class="contador-participantes">
            <span id="total-participantes">Total de participantes: 0</span>
        </div>

        <div class="envoltorio-tabela">
            <table class="tabela-participantes">
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
    </div>
</div>

<!-- Modal Adicionar Participante -->
<div class="modal-overlay" id="modalAdicionarParticipante" onclick="fecharModalSeForFundo(event, 'modalAdicionarParticipante')">
    <div class="modal-editar">
        <div class="modal-header">
            <h2>Adicionar Participante</h2>
            <button class="btn-fechar-modal" onclick="fecharModalAdicionar()">&times;</button>
        </div>
        <form id="formAdicionarParticipante" onsubmit="salvarNovoParticipante(event)">
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
                <button type="button" class="btn-modal btn-cancelar" onclick="fecharModalAdicionar()">Cancelar</button>
                <button type="submit" class="btn-modal btn-salvar">Adicionar Participante</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Enviar Mensagem -->
<div class="modal-overlay" id="modalEnviarMensagemPart" onclick="fecharModalSeForFundo(event, 'modalEnviarMensagemPart')">
    <div class="modal-editar">
        <div class="modal-header">
            <h2>Enviar Mensagem aos Participantes</h2>
            <button class="btn-fechar-modal" onclick="fecharModalMensagemPart()">&times;</button>
        </div>
        <form id="formEnviarMensagemPart" onsubmit="enviarMensagemParticipantes(event)">
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
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" id="msg-todos-part" checked style="width: 20px; height: 20px;">
                    <span>Enviar para todos os participantes</span>
                </label>
                <small id="msg-selecionados-part" style="color: #666; display: none; margin-top: 8px;"></small>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-modal btn-cancelar" onclick="fecharModalMensagemPart()">Cancelar</button>
                <button type="submit" class="btn-modal btn-salvar">Enviar Notificação</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Editar Dados -->
<div class="modal-overlay" id="modalEditarDadosPart" onclick="fecharModalSeForFundo(event, 'modalEditarDadosPart')">
    <div class="modal-editar">
        <div class="modal-header">
            <h2>Editar Dados do Participante</h2>
            <button class="btn-fechar-modal" onclick="fecharModalEditarPart()">&times;</button>
        </div>
        <form id="formEditarDadosPart" onsubmit="salvarEdicaoPart(event)">
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
                <label>CPF</label>
                <input type="text" id="edit-cpf-display-part" disabled>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-modal btn-cancelar" onclick="fecharModalEditarPart()">Cancelar</button>
                <button type="submit" class="btn-modal btn-salvar">Salvar Alterações</button>
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

    // Função para carregar participantes
    function carregarParticipantes() {
        if (typeof codEventoAtual === 'undefined' || !codEventoAtual) {
            console.error('❌ Código do evento não definido');
            return;
        }

        console.log('📊 Carregando participantes do evento:', codEventoAtual);

        fetch(`GerenciarEvento.php?action=buscar&cod_evento=${codEventoAtual}`)
            .then(response => response.json())
            .then(dados => {
                if (!dados.sucesso) {
                    console.error('❌ Erro ao carregar participantes:', dados.erro);
                    alert('Erro ao carregar participantes: ' + (dados.erro || 'Erro desconhecido'));
                    return;
                }

                todosParticipantes = dados.participantes || [];
                console.log(`✓ ${todosParticipantes.length} participantes carregados`);

                renderizarParticipantes();
                inicializarEventosParticipantes();
            })
            .catch(erro => {
                console.error('❌ Erro ao carregar participantes:', erro);
                alert('Erro ao carregar participantes. Tente novamente.');
            });
    }

    // Função para renderizar participantes
    function renderizarParticipantes() {
        const tbody = document.getElementById('tbody-participantes');
        const totalSpan = document.getElementById('total-participantes');

        if (!tbody || !totalSpan) {
            console.error('❌ Elementos da tabela não encontrados');
            return;
        }

        totalSpan.textContent = `Total de participantes: ${todosParticipantes.length}`;

        if (todosParticipantes.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 30px; color: var(--botao);">Nenhum participante inscrito neste evento ainda</td></tr>';
            return;
        }

        tbody.innerHTML = todosParticipantes.map((p, i) => {
            const statusPresenca = p.presenca_confirmada ?
                '<span class="emblema-status confirmado">Confirmada <img src="../Imagens/Certo.svg" alt=""></span>' :
                '<span class="emblema-status negado">Não Confirmada <img src="../Imagens/Errado.svg" alt=""></span>';

            const statusCertificado = p.certificado_emitido ?
                '<span class="emblema-status confirmado">Enviado <img src="../Imagens/Certo.svg" alt=""></span>' :
                '<span class="emblema-status negado">Não enviado <img src="../Imagens/Errado.svg" alt=""></span>';

            const btnCertificado = p.certificado_emitido ?
                '<button class="botao botao-acao-tabela botao-neutro" onclick="verificarCertificadoPart(\'' + p.cpf + '\')"><span>Verificar Certificado</span><img src="../Imagens/Certificado.svg" alt=""></button>' :
                '';

            return `
            <tr data-cpf="${p.cpf}">
                <td class="coluna-selecionar">
                    <input type="checkbox" class="checkbox-selecionar checkbox-part" id="part-${i}" value="${p.cpf}">
                </td>
                <td class="coluna-dados">
                    <p><strong>Nome:</strong> ${p.nome}</p>
                    <p><strong>E-mail:</strong> ${p.email}</p>
                    <p><strong>Registro Acadêmico:</strong> ${p.ra}</p>
                    <p><strong>Data de Inscrição:</strong> ${p.data_inscricao}</p>
                </td>
                <td class="coluna-modificar">
                    <div class="grupo-acoes">
                        <button class="botao botao-acao-tabela botao-verde" onclick="confirmarPresencaPart('${p.cpf}')">
                            <span>Confirmar Presença</span><img src="../Imagens/Certo.svg" alt="">
                        </button>
                        <button class="botao botao-acao-tabela botao-vermelho" onclick="excluirParticipantePart('${p.cpf}')">
                            <span>Excluir Participante</span><img src="../Imagens/Excluir.svg" alt="">
                        </button>
                        <button class="botao botao-acao-tabela botao-neutro" onclick="editarDadosPart('${p.cpf}')">
                            <span>Editar Dados</span><img src="../Imagens/Editar.svg" alt="">
                        </button>
                    </div>
                </td>
                <td class="coluna-status">
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

        console.log('✓ Tabela de participantes renderizada');
    }

    // Função para inicializar eventos
    function inicializarEventosParticipantes() {
        // Checkboxes de seleção
        document.addEventListener('change', function(e) {
            if (e.target.classList && e.target.classList.contains('checkbox-part')) {
                const tr = e.target.closest('tr');
                tr.classList.toggle('linha-selecionada', e.target.checked);
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
                        cb.closest('tr').classList.remove('linha-selecionada');
                        participantesSelecionados.delete(cb.value);
                    });
                } else {
                    participantesSelecionados.clear();
                    document.querySelectorAll('.checkbox-part').forEach(cb => {
                        cb.checked = true;
                        cb.closest('tr').classList.add('linha-selecionada');
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
                fn: importarListaPresenca
            },
            {
                id: 'btn-exportar-presenca',
                fn: exportarListaPresenca
            },
            {
                id: 'btn-enviar-mensagem-part',
                fn: abrirModalMensagemPart
            },
            {
                id: 'btn-importar-inscritos',
                fn: importarListaInscritos
            },
            {
                id: 'btn-exportar-inscritos',
                fn: exportarListaInscritos
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

        console.log('✓ Eventos dos participantes inicializados');
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
        if (!tbody || todosParticipantes.length === 0) return;

        const termo = (document.getElementById('busca-participantes')?.value || '').toLowerCase();
        let visiveis = 0;

        tbody.querySelectorAll('tr').forEach(linha => {
            if (!linha.hasAttribute('data-cpf')) return;
            const match = linha.textContent.toLowerCase().includes(termo);
            linha.style.display = match ? '' : 'none';
            if (match) visiveis++;
        });

        const idMsg = 'linha-sem-resultados-busca-part';
        const existente = document.getElementById(idMsg);
        if (visiveis === 0 && !existente) {
            const tr = document.createElement('tr');
            tr.id = idMsg;
            tr.innerHTML = '<td colspan="4" style="text-align: center; padding: 30px; color: var(--botao);">Nenhum participante encontrado para a busca</td>';
            tbody.appendChild(tr);
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

        document.getElementById('modalEditarDadosPart').classList.add('ativo');
    }

    function verificarCertificadoPart(cpf) {
        alert('Funcionalidade em desenvolvimento');
    }

    function formatarCPF(cpf) {
        return cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
    }

    // Modals
    function fecharModalSeForFundo(event, modalId) {
        if (event.target.id === modalId) {
            if (modalId === 'modalAdicionarParticipante') fecharModalAdicionar();
            else if (modalId === 'modalEnviarMensagemPart') fecharModalMensagemPart();
            else if (modalId === 'modalEditarDadosPart') fecharModalEditarPart();
        }
    }

    function abrirModalAdicionar() {
        document.getElementById('modalAdicionarParticipante').classList.add('ativo');
    }

    function fecharModalAdicionar() {
        document.getElementById('modalAdicionarParticipante').classList.remove('ativo');
        document.getElementById('formAdicionarParticipante').reset();
        document.getElementById('msg-cpf-existente').style.display = 'none';
        document.getElementById('add-nome').disabled = false;
        document.getElementById('add-email').disabled = false;
        document.getElementById('add-ra').disabled = false;
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

        document.getElementById('modalEnviarMensagemPart').classList.add('ativo');
    }

    function fecharModalMensagemPart() {
        document.getElementById('modalEnviarMensagemPart').classList.remove('ativo');
        document.getElementById('formEnviarMensagemPart').reset();
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
    console.log('✓ ConteudoParticipantes.php carregado');
    carregarParticipantes();
</script>