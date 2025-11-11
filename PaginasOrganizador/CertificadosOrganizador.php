<div id="main-content">
    <div style="display: flex; justify-content: center; padding: 2rem 1rem; width: 100%;">
        <div class="cartao-certificados">
            <header class="cabecalho-cartao">
                <h1>Certificados</h1>
            </header>
            <div class="corpo-cartao">
                <div id="lista-certificados" class="lista-certificados">
                    <div class="carregando" style="text-align: center; padding: 2rem; color: var(--azul-escuro);">
                        Carregando certificados...
                    </div>
                </div>
                <div class="paginacao" id="paginacao-certificados" style="display: none;">
                    <a href="#" class="botao botao-paginacao" onclick="event.preventDefault(); mudarPagina(-1)">Anterior</a>
                    <span id="info-pagina" style="margin: 0 1rem; color: var(--azul-escuro); font-weight: 700;"></span>
                    <a href="#" class="botao botao-paginacao" onclick="event.preventDefault(); mudarPagina(1)">Próxima</a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* CSS da seção: certificados */
    .cartao-certificados {
        max-width: 30rem;
        width: 100%;
        background-color: var(--branco);
        border-radius: 0.525rem;
        box-shadow: 0px 0.175rem 0.875rem 0px var(--sombra-padrao);
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    .cabecalho-cartao {
        background-color: var(--caixas);
        padding: 0.525rem 1.05rem;
    }

    .cabecalho-cartao h1 {
        color: var(--branco);
        font-size: clamp(1.26rem, 2.8vw, 2.1rem);
        font-weight: 700;
        text-align: center;
        margin: 0;
        text-shadow: 0px 0.175rem 0.875rem var(--sombra-padrao);
        letter-spacing: -0.085625rem;
    }

    .corpo-cartao {
        padding: 1.4rem 1.75rem;
    }

    .lista-certificados {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(16rem, 1fr));
        gap: 1.2rem;
    }

    .item-certificado {
        background-color: var(--branco);
        border-radius: 0.525rem;
        padding: 1.2rem;
        box-shadow: 0px 0.2rem 0.6rem 0px rgba(0, 0, 0, 0.4);
        display: flex;
        flex-direction: column;
        gap: 0.8rem;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .item-certificado:hover {
        transform: translateY(-3px);
        box-shadow: 0px 0.4rem 1rem 0px rgba(0, 0, 0, 0.2);
    }

    .nome-item {
        font-size: clamp(0.9rem, 1.6vw, 1.3rem);
        font-weight: 700;
        margin: 0;
        letter-spacing: -0.044375rem;
        color: var(--azul-escuro);
        line-height: 1.3;
        min-height: 2.6rem;
    }

    .info-certificado {
        display: flex;
        flex-direction: column;
        gap: 0.4rem;
    }

    .info-linha {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: clamp(0.7rem, 1.2vw, 0.9rem);
        color: var(--cinza-escuro);
    }

    .info-linha strong {
        font-weight: 700;
        color: var(--azul-escuro);
    }

    .tipo-badge {
        display: inline-block;
        padding: 0.3rem 0.8rem;
        border-radius: 0.25rem;
        font-size: clamp(0.65rem, 1.1vw, 0.8rem);
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .tipo-participante {
        background-color: var(--verde);
        color: var(--branco);
    }

    .tipo-organizador {
        background-color: var(--botao);
        color: var(--branco);
    }

    .acoes-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-top: auto;
    }

    .botao {
        display: inline-block;
        color: var(--branco);
        font-family: 'Inter', sans-serif;
        font-weight: 700;
        font-size: clamp(0.6125rem, 1.05vw, 1.05rem);
        text-align: center;
        text-decoration: none;
        border: none;
        border-radius: 0.13125rem;
        padding: 0.5rem 1rem;
        cursor: pointer;
        transition: opacity 0.3s ease, transform 0.2s ease;
        letter-spacing: -0.039375rem;
        white-space: nowrap;
        flex: 1;
    }

    .botao:hover {
        opacity: 0.85;
        transform: translateY(-1px);
    }

    .icone-botao {
        width: 1rem;
        height: 1rem;
        display: inline-block;
        margin-right: 0.4rem;
        vertical-align: middle;
    }

    .botao:not(.botao-ver):not(.botao-paginacao) {
        background-color: var(--verde);
    }

    .botao-ver {
        background-color: var(--botao);
    }

    .paginacao {
        display: flex;
        justify-content: center;
        margin-top: 1.5rem;
        gap: 0.8rem;
    }

    .botao-paginacao {
        padding: 0.5rem 1.8rem;
        background-color: var(--caixas);
    }

    .sem-certificados {
        grid-column: 1 / -1;
        text-align: center;
        padding: 3rem 1rem;
        color: var(--azul-escuro);
        font-size: clamp(0.8rem, 1.5vw, 1.1rem);
        font-weight: 700;
    }

    @media (max-width: 768px) {
        .lista-certificados {
            grid-template-columns: 1fr;
        }

        .corpo-cartao {
            padding: 1rem;
        }
    }
</style>

<script>
    // Usa variáveis globais do window para evitar redeclaração
    if (typeof window.todosCertificados === 'undefined') {
        window.todosCertificados = [];
    }
    if (typeof window.paginaAtualCertificados === 'undefined') {
        window.paginaAtualCertificados = 1;
    }
    if (typeof window.itensPorPaginaCertificados === 'undefined') {
        window.itensPorPaginaCertificados = 6;
    }

    async function carregarCertificados() {
        try {
            const response = await fetch('BuscarCertificados.php');
            const data = await response.json();

            if (data.sucesso) {
                window.todosCertificados = data.certificados;
                renderizarCertificados();
            } else {
                mostrarErro('Erro ao carregar certificados');
            }
        } catch (error) {
            console.error('Erro:', error);
            mostrarErro('Erro ao carregar certificados');
        }
    }

    function renderizarCertificados() {
        const container = document.getElementById('lista-certificados');
        const paginacao = document.getElementById('paginacao-certificados');

        if (window.todosCertificados.length === 0) {
            container.innerHTML = '<div class="sem-certificados">Nenhum certificado encontrado</div>';
            paginacao.style.display = 'none';
            return;
        }

        const inicio = (window.paginaAtualCertificados - 1) * window.itensPorPaginaCertificados;
        const fim = inicio + window.itensPorPaginaCertificados;
        const certificadosPagina = window.todosCertificados.slice(inicio, fim);

        container.innerHTML = certificadosPagina.map(cert => {
            const dataEmissao = cert.criado_em ? new Date(cert.criado_em).toLocaleDateString('pt-BR') : 'Data não informada';
            const horaEmissao = cert.criado_em ? new Date(cert.criado_em).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' }) : '';
            const dataHoraEmissao = horaEmissao ? `${dataEmissao} às ${horaEmissao}` : dataEmissao;
            const tipoCertificado = cert.tipo || 'participante';
            const tipoCssClass = tipoCertificado === 'organizador' ? 'tipo-organizador' : 'tipo-participante';
            const tipoTexto = tipoCertificado === 'organizador' ? 'Organização' : tipoCertificado.charAt(0).toUpperCase() + tipoCertificado.slice(1);

            return `
                <div class="item-certificado">
                    <p class="nome-item" title="${cert.nome_evento || 'Evento'}">${cert.nome_evento || 'Evento'}</p>
                    <div class="info-certificado">
                        <div class="info-linha">
                            <span class="tipo-badge ${tipoCssClass}">${tipoTexto}</span>
                        </div>
                        <div class="info-linha">
                            <strong>Emitido em:</strong> ${dataHoraEmissao}
                        </div>
                        <div class="info-linha">
                            <strong>Código:</strong> ${cert.cod_verificacao}
                        </div>
                    </div>
                    <div class="acoes-item">
                        <a href="#" class="botao" onclick="event.preventDefault(); baixarCertificado('${cert.arquivo}', '${cert.cod_verificacao}')">
                            <svg class="icone-botao" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                <polyline points="7 10 12 15 17 10"/>
                                <line x1="12" y1="15" x2="12" y2="3"/>
                            </svg>
                            Baixar
                        </a>
                        <a href="#" class="botao botao-ver" onclick="event.preventDefault(); visualizarCertificado('${cert.cod_verificacao}')">
                            <svg class="icone-botao" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                            Ver
                        </a>
                    </div>
                </div>
            `;
        }).join('');

        // Atualizar paginação
        const totalPaginas = Math.ceil(window.todosCertificados.length / window.itensPorPaginaCertificados);
        if (totalPaginas > 1) {
            paginacao.style.display = 'flex';
            const infoPagina = document.getElementById('info-pagina');
            if (infoPagina) {
                infoPagina.textContent = `${window.paginaAtualCertificados} / ${totalPaginas}`;
            }
        } else {
            paginacao.style.display = 'none';
        }
    }

    function mudarPagina(direcao) {
        const totalPaginas = Math.ceil(window.todosCertificados.length / window.itensPorPaginaCertificados);
        window.paginaAtualCertificados += direcao;

        if (window.paginaAtualCertificados < 1) window.paginaAtualCertificados = 1;
        if (window.paginaAtualCertificados > totalPaginas) window.paginaAtualCertificados = totalPaginas;

        renderizarCertificados();
    }

    function visualizarCertificado(codigo) {
        // Navega para a página de visualização dentro do container
        window.location.href = 'ContainerOrganizador.php?pagina=visualizarCertificado&codigo=' + encodeURIComponent(codigo);
    }

    function baixarCertificado(arquivo, codigo) {
        const link = document.createElement('a');
        link.href = '../' + arquivo;
        link.download = `certificado_${codigo}.pdf`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    function mostrarErro(mensagem) {
        const container = document.getElementById('lista-certificados');
        container.innerHTML = `<div class="sem-certificados" style="color: var(--vermelho);">${mensagem}</div>`;
    }

    // Reseta as variáveis ao carregar a página
    window.todosCertificados = [];
    window.paginaAtualCertificados = 1;

    // Carregar certificados sempre que esta página for carregada
    // Executa imediatamente, sem depender de DOMContentLoaded
    carregarCertificados();
</script>