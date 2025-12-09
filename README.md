# 🎉 CEU - Controle de Eventos Unificado

Um sistema web moderno e gratuito para gerenciar eventos de forma completa e eficiente. Desenvolvido por estudantes do IFMG Campus Sabará como solução para facilitar a criação, inscrição e certificação de eventos educacionais.

### Guia de Instalação
- **[Tutorial_Instalacao.md](Tutorial_Instalacao.md)** - Tutorial passo a passo completo

## ✨ Funcionalidades Principais

### Para Participantes
- 📝 **Inscrição em Eventos**: Cadastro simplificado e validação de dados
- 🎓 **Certificados Automáticos**: Geração automática de certificados autenticados com verificação
- 🔐 **Gerenciamento de Conta**: Atualização de perfil e redefinição de senha segura
- 📱 **Notificações**: Sistema de notificações em tempo real sobre seus eventos
- ⭐ **Favoritos**: Marque eventos como favoritos para acesso rápido

### Para Organizadores
- 📊 **Criação de Eventos**: Interface intuitiva para criar e gerenciar eventos
- 👥 **Gestão de Participantes**: Controle completo de inscrições e presença
- 🤝 **Colaboradores**: Convide outros organizadores para colaborar
- 📜 **Emissão de Certificados**: Geração e gerenciamento de certificados
- 📈 **Análise de Eventos**: Acompanhamento de inscrições e participação
- 🖼️ **Galeria de Imagens**: Adicione e gerencie imagens de seus eventos

### Recursos Técnicos
- 🌐 **PWA (Progressive Web App)**: Funciona offline e pode ser instalado como app
- 📱 **Totalmente Responsivo**: Adaptado para desktop, tablet e mobile
- 🎨 **Interface Intuitiva**: Design moderno e fácil de usar
- 🔒 **Segurança**: Autenticação, validação de dados e proteção de sessão
- 💾 **Banco de Dados Robusto**: MySQL com estrutura bem organizada

## 🚀 Como Começar

### Requisitos
- PHP 7.4+
- MySQL 5.7+
- XAMPP ou servidor similar
- Navegador moderno com suporte a PWA

### Instalação Rápida

1. **Clone ou copie o projeto** para `htdocs/CEU` do seu XAMPP

2. **Configure o banco de dados** via phpMyAdmin:
   ```
   Abra: http://localhost/phpmyadmin
   1. Crie um banco de dados chamado "CEU_bd"
   2. Vá para "Importar" e selecione BancoDados/BancodeDadosCEU.sql
   3. Execute a importação
   4. Importe também BancoDados/PopularBancoDados.sql (dados iniciais)
   ```

3. **Inicie o servidor**:
   - Abra XAMPP Control Panel
   - Inicie Apache e MySQL
   - Acesse: http://localhost/CEU

4. **Login de teste**:
   - Participante ou Organizador conforme populado no banco

## 📁 Estrutura do Projeto

```
CEU/
├── index.php                    # Página de boas-vindas
├── README.md                    # Este arquivo
├── TUTORIAL_INSTALACAO.md       # Tutorial completo
├── GUIA_RAPIDO.md              # Referência rápida
├── REQUISITOS_TECNICOS.md      # Detalhes técnicos
├── ARQUITETURA.md              # Diagramas e fluxos
│
├── PaginasPublicas/             # Páginas públicas (login, cadastro)
│   ├── ContainerPublico.php     # Container com menu dinâmico
│   ├── Inicio.php              # Página inicial autenticada
│   ├── CadastroParticipante.php # Cadastro de participantes
│   └── CadastroOrganizador.php  # Cadastro de organizadores
│
├── PaginasOrganizador/          # Painel do organizador
│   ├── ContainerOrganizador.php # Container com menu
│   ├── GerenciadorEventos.php   # Criar/editar eventos
│   ├── GerenciadorColaboradores.php # Gerenciar colaboradores
│   └── CertificadosOrganizador.php  # Emitir certificados
│
├── PaginasParticipante/         # Painel do participante
│   ├── ContainerParticipante.php # Container
│   ├── MeusEventos.php          # Eventos inscritos
│   └── PerfilParticipante.php   # Editar perfil
│
├── PaginasGlobais/              # Componentes compartilhados
│   ├── PainelNotificacoes.php   # Sistema de notificações
│   ├── BuscarOpcoesFiltro.php   # Filtros de busca
│   └── TemaDoSite.php           # Troca de tema
│
├── Certificacao/                # Sistema de certificados
│   ├── index.php               # Gerador de certificados
│   ├── instalador.php          # Instalador de dependências
│   ├── ProcessadorTemplate.php  # Processamento de templates
│   ├── verificar.php           # Verificação de autenticidade
│   ├── templates/              # Templates DOCX/PPTX
│   ├── certificados/           # PDFs gerados
│   └── bibliotecas/            # Dependências Composer
│
├── BancoDados/                  # Scripts SQL
│   ├── BancodeDadosCEU.sql     # Estrutura das tabelas
│   ├── PopularBancoDados.sql   # Dados iniciais
│   ├── conexao.php             # Configuração de conexão
│   └── VerificarBancoDados.php # Auto-instalação
│
├── Admin/                       # Painel administrativo
│   ├── index.php               # Login admin
│   ├── PainelAdmin.html        # Dashboard admin
│   ├── GeradorCodigoSeguro.php # Gerar códigos organizador
│   ├── GerenciadorBackup.php   # Backup do banco
│   └── Backups/                # Backups salvos
│
├── Imagens/                     # Assets estáticos (logo, ícones)
├── ImagensEventos/             # Uploads de eventos
├── ImagensPerfis/              # Fotos de perfil
│
├── manifest.json               # Configuração PWA
├── sw.js                       # Service Worker
└── pwa-config.js              # Script PWA
```

---

## 🔧 Configuração do Banco de Dados

### Método Automático (Recomendado) ✅

O sistema detecta e instala o banco automaticamente na primeira vez que você acessa!

1. Acesse: http://localhost/CEU
2. Se o banco não existir, aparecerá uma mensagem
3. Clique em "OK" para criar automaticamente
4. Pronto! O sistema está configurado

### Método Manual (phpMyAdmin)

Se preferir fazer manualmente ou tiver problemas:

1. Acesse: http://localhost/phpmyadmin
2. Crie um banco chamado `CEU_bd`
3. Vá em "Importar"
4. Selecione o arquivo `BancoDados/BancodeDadosCEU.sql`
5. Clique em "Executar"
6. (Opcional) Importe `BancoDados/PopularBancoDados.sql` para dados de teste

### Configuração de Conexão

O arquivo `BancoDados/conexao.php` contém as credenciais:
```php
$servidor = "localhost";
$usuario = "root";
$senha = "";
$banco = "CEU_bd";
```

Padrão XAMPP: `root` sem senha. Modifique se seu MySQL usar credenciais diferentes.

---

## 🎓 Sistema de Certificação

### Instalação de Dependências

O sistema de certificados requer bibliotecas PHP instaladas via Composer:

**Método Fácil (via interface):**
1. Acesse: http://localhost/CEU/Certificacao/
2. Clique no botão "Instalar Dependências"
3. Aguarde 1-3 minutos (dependendo da conexão)
4. Pronto! Certificados funcionando

**Método Alternativo (linha de comando):**
```bash
cd C:\xampp\htdocs\CEU\Certificacao
composer install
```

### LibreOffice (Opcional mas ALTAMENTE RECOMENDADO)

Para certificados com melhor qualidade, instale o LibreOffice:

- **Download:** https://www.libreoffice.org/download/download/
- **Versão:** 7.x ou superior
- **Sistema operacional:** Windows, Linux ou macOS

**Configuração automática:** O sistema detecta automaticamente se está instalado.

**Fallback:** Se não tiver LibreOffice, usa conversão PHP (Provavelmente perderá qualidade e pode desconfigurar).

---

## 👨‍💼 Painel Administrativo

### Acesso Inicial

**URL:** http://localhost/CEU/Admin

**Credenciais padrão:**
- **Usuário:** `infofriends`
- **Senha:** `12345678`

### Funcionalidades Admin
- 🔐 Gerar códigos seguros para novos organizadores
- 📦 Fazer backup do banco de dados
- 📊 Visualizar estatísticas do sistema

### Gerar Código de Organizador

1. Acesse o painel admin
2. Clique em "Gerar Código"
3. Copie o código gerado
4. Forneça para o novo organizador
5. Ele usa no cadastro para ter acesso organizador

---

## 🌐 Progressive Web App (PWA)

O CEU é um PWA completo! Significa que você pode:

### Instalar no Celular
1. Abra no navegador do celular
2. Toque no menu (⋮) → "Adicionar à tela inicial"
3. Use como aplicativo!

### Funcionalidades PWA
- ✅ Funciona offline (páginas visitadas)
- ✅ Notificações push
- ✅ Instalável como app
- ✅ Atualizações automáticas

---

## 🔍 Primeiros Passos Após Instalação

### 1️⃣ Como Participante
1. Acesse: http://localhost/CEU
2. Clique em "Cadastre-se" → "Participante"
3. Preencha seus dados
4. Faça login
5. Explore eventos disponíveis na página inicial
6. Inscreva-se em eventos
7. Acompanhe suas inscrições em "Meus Eventos"

### 2️⃣ Como Organizador
1. Acesse: http://localhost/CEU
2. Clique em "Cadastre-se" → "Organizador"
3. **Obtenha um código de organizador** com o admin
4. Preencha seus dados e o código
5. Faça login
6. Crie seu primeiro evento em "Gerenciar Eventos"
7. Configure colaboradores (opcional)
8. Acompanhe inscrições
9. Emita certificados após o evento

### 3️⃣ Como Administrador
1. Acesse: http://localhost/CEU/Admin
2. Login com credenciais padrão
3. Gere códigos para organizadores
4. Tenha um panorama do sistema

---

## ❓ Problemas Comuns

### ❌ "Erro ao conectar ao banco de dados"
- ✅ Verifique se MySQL está rodando no XAMPP
- ✅ Confira as credenciais em `BancoDados/conexao.php`
- ✅ Certifique-se que o banco `CEU_bd` existe

### ❌ "Página não encontrada" (404)
- ✅ Confirme que o projeto está em `C:\xampp\htdocs\CEU`
- ✅ Acesse http://localhost/CEU (com /CEU no final)
- ✅ Verifique se Apache está rodando

### ❌ Certificados não geram
- ✅ Instale as dependências via http://localhost/CEU/Certificacao/
- ✅ Verifique permissões da pasta `Certificacao/certificados/`
- ✅ Instale LibreOffice para melhor compatibilidade (opcional)

### ❌ Imagens não carregam
- ✅ Verifique permissões das pastas:
  - `ImagensEventos/`
  - `ImagensPerfis/`
- ✅ No Windows, garanta que o Apache pode escrever nessas pastas

### 🔍 Mais Ajuda?

Consulte o **[Tutorial_Instalacao.md](Tutorial_Instalacao.md)** para:
- Solução detalhada de problemas
- Configuração avançada do PHP
- Ajustes de performance
- Modo desenvolvedor

---

---

## ✨ Funcionalidades Principais

### Para Organizadores
- 📝 Criar e gerenciar eventos
- 👥 Controlar inscrições de participantes
- 📸 Galeria de imagens dos eventos
- 👔 Gerenciar colaboradores
- 🎓 Emitir certificados em massa (DOCX/PPTX → PDF)
- 📊 Estatísticas e relatórios

### Para Participantes
- 🔍 Descobrir eventos disponíveis
- ⭐ Favoritar eventos de interesse
- ✍️ Inscrever-se em eventos
- 📱 Receber notificações
- 📧 Mensagens com organizadores
- 🎖️ Download de certificados

### Para Administradores
- 🔐 Gerar códigos de acesso para organizadores
- 📦 Realizar backups do banco de dados
- 🗑️ Gerenciar exclusões de contas
- 👁️ Monitorar o sistema

---

## 📚 Módulos do Sistema

### 🌍 Módulo Público
- **Início:** Descoberta de eventos com filtros avançados (categoria, data, status)
- **Visualização:** Detalhes completos de cada evento
- **Favoritos:** Marque eventos para acompanhar
- **Temas:** Modo claro/escuro personalizável
- **PWA:** Instalável como aplicativo

### 👤 Módulo Participante
- **Cadastro:** Criação de conta gratuita
- **Perfil:** Gerenciamento de dados pessoais e foto
- **Inscrições:** Sistema de registro em eventos
- **Certificados:** Download automático após conclusão
- **Notificações:** Avisos sobre eventos inscritos
- **Mensagens:** Comunicação direta com organizadores

### 👨‍🏫 Módulo Organizador
- **Criação de Eventos:** Interface completa com todos os detalhes
- **Galeria:** Upload múltiplo de imagens do evento
- **Colaboradores:** Adicionar ajudantes ao evento
- **Gerenciamento:** Visualizar e aprovar inscrições
- **Certificados:** Emissão em lote usando templates personalizados
- **Estatísticas:** Dashboard com números e métricas

### 🔐 Módulo Admin
- **Códigos:** Geração de códigos seguros para novos organizadores
- **Backup:** Exportação completa do banco de dados
- **Exclusões:** Processar solicitações de remoção de conta
- **Auditoria:** Logs de ações administrativas

---

## 🛠️ Tecnologias Utilizadas

**Backend:**
- PHP 7.4+
- MySQL 5.7+

**Frontend:**
- HTML5, CSS3, JavaScript
- PWA (Service Worker, Manifest)

**Bibliotecas:**
- [PHPWord](https://github.com/PHPOffice/PHPWord) - Manipulação DOCX
- [PHPPresentation](https://github.com/PHPOffice/PHPPresentation) - Manipulação PPTX
- [mPDF](https://github.com/mpdf/mpdf) - Conversão para PDF
- [LibreOffice](https://www.libreoffice.org/) (Opcional) - Conversão PPTX de alta qualidade

**Servidor:**
- Apache 2.4+
- XAMPP (Recomendado para Windows)

---

## 👥 Equipe de Desenvolvimento

Desenvolvido por estudantes do **IFMG - Campus Sabará**:

- Ana Clara
- Caike
- Jean
- Júlia
- Nathally
- Pâmela
- Roxane
- Victória

**Disciplina**: Projetec  
**Objetivo**: Facilitar a gestão de eventos e certificação em instituições educacionais

---

## 🧩 Estrutura de Dados (Principais Tabelas)

```sql
usuarios           # Credenciais de login (participantes e organizadores)
participantes      # Perfil detalhado de participantes
organizadores      # Perfil detalhado de organizadores
eventos            # Informações completas dos eventos
inscricoes         # Registros de inscrições em eventos
certificados       # Certificados emitidos
notificacoes       # Sistema de notificações
colaboradores      # Ajudantes dos organizadores em eventos
mensagens          # Comunicação entre usuários
```

---

## 🎨 Para Desenvolvedores

### Adicionando Novas Páginas Públicas

**1. Crie o arquivo PHP**
```php
<!-- PaginasPublicas/MinhaNovaPagina.php -->
<div id="main-content">
    <h1>Minha Nova Página</h1>
    <p>Conteúdo aqui...</p>
</div>
```

## 2. CSS
- O espaçamento lateral do menu será aplicado automaticamente via a classe `.shifted` em `#main-content`.
- Não adicione margens ou transições extras relacionadas ao menu em outros elementos.
- Use apenas o CSS global e o que for necessário para o conteúdo interno.

## 3. Cadastro da nova página
- No arquivo `ContainerPublico.php`, adicione sua nova página ao array `$paginasPermitidas`:
```php
$paginasPermitidas = [
    'inicio' => 'PaginaInicio.php',
    'login' => 'Login.php',
    'minhanova' => 'MinhaNovaPagina.php', // Adicione esta linha
    // ...
];
```
- Para acessar, use: `carregarPagina('minhanova')` ou navegue para `ContainerPublico.php?pagina=minhanova`.

## 4. Não faça
- Não coloque scripts de sincronização do menu dentro da nova página.
- Não use mais de um elemento com id `main-content`.
- Não altere o script central de sincronização em `ContainerPublico.php`.

## 5. Dica
Se quiser adicionar botões no menu para navegar para a nova página, use:
```html
<button onclick="carregarPagina('minhanova')">Minha Nova Página</button>
```

# Configuração do Banco de Dados

Na pasta `BancoDados` existem dois arquivos importantes para preparar o ambiente no phpMyAdmin:

1. `BancodeDados.sql`  
   - Contém a estrutura (tabelas, chaves etc.).  
   - Caso o banco ainda não exista, acesse o phpMyAdmin, selecione (ou crie) o banco e importe este arquivo primeiro.

2. `InserirDados.sql`  
   - Contém dados iniciais (registros de exemplo / obrigatórios).  
   - Após importar o `BancodeDados.sql`, importe este arquivo para popular as tabelas.

Passos rápidos:
1. Abrir http://localhost/phpmyadmin
2. Criar (se necessário) o banco com o nome esperado pelo projeto (confira no código de conexão PHP).
3. Aba Importar -> selecionar `BancodeDados.sql` -> Executar.
4. Aba Importar -> selecionar `InserirDados.sql` -> Executar.
5. Verificar se as tabelas e registros foram criados.

Se fizer alterações futuras na estrutura, gerar novo script e atualizar o `BancodeDados.sql`. Para novos dados padrão, atualizar somente o `InserirDados.sql`.

# 🧪 Configurações de Teste para Validações

Para facilitar os testes durante o desenvolvimento, você pode desativar validações específicas nos formulários de cadastro.

## Como usar:

1. Abra o arquivo `PaginasPublicas/ValidacoesCadastro.js`
2. No topo do arquivo, encontre as configurações:

```javascript
// ========== CONFIGURAÇÕES PARA TESTES ==========
var VALIDAR_CPF = false;    // Desabilita validação de CPF
var VALIDAR_EMAIL = false;  // Desabilita validação de email
var VALIDAR_SENHA = false;  // Desabilita validação de senha
var SENHA_MINIMA = 0;       // Sem mínimo de caracteres
// ================================================
```

3. Mude os valores conforme sua necessidade:

### Exemplos de uso:

**Para testes rápidos (desativa tudo):**
```javascript
var VALIDAR_CPF = false;
var VALIDAR_EMAIL = false; 
var VALIDAR_SENHA = false;
```

**Para senha mais flexível:**
```javascript
var SENHA_MINIMA = 3;        // Aceita senha de 3 caracteres
// ou
var SENHA_MINIMA = 0;        // Não valida tamanho da senha
```

**Para testar apenas validação específica:**
```javascript
var VALIDAR_CPF = true;      // Só testa CPF
var VALIDAR_EMAIL = false;
var VALIDAR_SENHA = false;
```

**Para voltar ao normal:**
```javascript
var VALIDAR_CPF = true;
var VALIDAR_EMAIL = true;
var VALIDAR_SENHA = true;
var SENHA_MINIMA = 8;
```

## ⚠️ Importante:
- As configurações se aplicam tanto aos formulários de **Participante** quanto **Organizador**
- As mudanças afetam tanto a validação no envio quanto a validação em tempo real (ao sair dos campos)
- **Sempre volte às configurações padrão**

---
