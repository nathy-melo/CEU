# 🎉 CEU - Controle de Eventos Unificado

Um sistema web moderno e gratuito para gerenciar eventos de forma completa e eficiente. Desenvolvido por estudantes do IFMG Campus Sabará como solução para facilitar a criação, inscrição e certificação de eventos educacionais.

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
├── PaginasPublicas/             # Páginas públicas (login, cadastro)
│   ├── ContainerPublico.php     # Container com menu dinâmico
│   ├── Inicio.php              # Página inicial autenticada
│   ├── CadastroParticipante.php # Cadastro de participantes
│   ├── CadastroOrganizador.php  # Cadastro de organizadores
│   └── ...
├── PaginasOrganizador/          # Painel do organizador
│   ├── ContainerOrganizador.php # Container com menu do organizador
│   ├── GerenciadorEventos.php   # Criar/editar eventos
│   ├── GerenciadorColaboradores.php # Gerenciar colaboradores
│   ├── CertificadosOrganizador.php  # Emitir certificados
│   └── ...
├── PaginasParticipante/         # Painel do participante
├── PaginasGlobais/              # Componentes compartilhados
│   ├── PainelNotificacoes.php   # Sistema de notificações
│   ├── BuscarOpcoesFiltro.php   # Filtros de busca
│   └── ...
├── Certificacao/                # Sistema de certificados
│   ├── index.php               # Gerador de certificados
│   ├── ProcessadorTemplate.php  # Processamento de templates
│   └── verificar.php           # Verificação de certificados
├── BancoDados/                  # Scripts SQL
│   ├── BancodeDadosCEU.sql     # Estrutura das tabelas
│   ├── PopularBancoDados.sql   # Dados iniciais
│   └── conexao.php             # Configuração de conexão
├── Admin/                       # Painel administrativo
├── sw.js                        # Service Worker (PWA)
├── manifest.json                # Configuração PWA
└── pwa-config.js               # Configuração de PWA
```

## 🔧 Configuração do Banco de Dados

### Arquivo: `BancoDados/conexao.php`
```php
$servidor = "localhost";
$usuario = "root";
$senha = "";
$banco = "CEU_bd";
```

### Estrutura Principal das Tabelas
- **usuarios**: Dados de login (participantes e organizadores)
- **participantes**: Perfil de participantes
- **organizadores**: Perfil de organizadores
- **eventos**: Informações dos eventos
- **inscricoes**: Registros de inscrições
- **certificados**: Certificados emitidos
- **notificacoes**: Notificações do sistema

## 🎨 Desenvolvimento

### Adicionando Novas Páginas Públicas

1. Crie um arquivo PHP em `PaginasPublicas/`
2. Use a estrutura padrão:
```php
<div id="main-content">
    <!-- Seu conteúdo aqui -->
    <h1>Minha Nova Página</h1>
</div>
```

3. Registre em `ContainerPublico.php`:
```php
$paginasPermitidas = [
    'inicio' => 'Inicio.php',
    'minhanova' => 'MinhaNovaPagina.php',
    // ...
];
```

### Estilos
- CSS Global: `styleGlobal.css` e `styleGlobalMobile.css`
- O menu adaptável é gerenciado automaticamente via `ContainerPublico.php`

## 🧪 Testes

### Desabilitar Validações (Desenvolvimento)

Edite `PaginasPublicas/ValidacoesCadastro.js`:

```javascript
// ========== CONFIGURAÇÕES PARA TESTES ==========
var VALIDAR_CPF = false;    // Desabilita validação de CPF
var VALIDAR_EMAIL = false;  // Desabilita validação de email
var VALIDAR_SENHA = false;  // Desabilita validação de senha
var SENHA_MINIMA = 0;       // Sem mínimo de caracteres
// ================================================
```

## 🔐 Segurança

- ✅ Validação de CPF e email no cadastro
- ✅ Senhas com hash (bcrypt)
- ✅ Proteção contra SQL injection
- ✅ Validação de sessão em todas as páginas
- ✅ Verificação de autenticação

## 📱 PWA (Progressive Web App)

O CEU funciona como uma Progressive Web App:
- **Offline**: Funciona sem conexão (com cache)
- **Instalável**: Pode ser instalado como app nativo
- **Rápido**: Carregamento otimizado com Service Worker
- **Responsivo**: Funciona em qualquer dispositivo

Configure em `pwa-config.js` e `manifest.json`

## 👥 Equipe de Desenvolvimento

- Ana Clara
- Caike
- Jean
- Júlia
- Nathally
- Pâmela
- Roxane
- Victória

**Instituto**: IFMG - Campus Sabará  
**Disciplina**: Projetec  
**Objetivo**: Facilitar a gestão de eventos e certificação em instituições educacionais

---

## 🔄 Como criar uma nova página para funcionar com o menu expansível

Siga os passos abaixo para garantir que sua nova página funcione corretamente com o menu expansível/retraível e o layout sincronizado:

## 1. Estrutura do arquivo da nova página
- Crie um novo arquivo PHP (ex: MinhaNovaPagina.php).
- Todo o conteúdo principal da página deve estar dentro de uma única `<div id="main-content"> ... </div>`.
- Não coloque mais de um elemento com o id `main-content`.
- Não adicione scripts de sincronização do menu na nova página (isso já está centralizado em `ContainerPublico.php`).

**Exemplo básico:**
```php
<div id="main-content">
    <!-- Seu conteúdo aqui -->
    <h1>Título da Nova Página</h1>
    <p>Conteúdo da nova página...</p>
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
var VALIDAR_CPF = true;           // true = valida CPF, false = não valida
var VALIDAR_EMAIL = true;         // true = valida email, false = não valida  
var VALIDAR_SENHA = true;         // true = valida senha, false = não valida
var SENHA_MINIMA = 8;             // mínimo de caracteres (0 = desativar)
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

**Para voltar ao normal (produção):**
```javascript
var VALIDAR_CPF = true;
var VALIDAR_EMAIL = true;
var VALIDAR_SENHA = true;
var SENHA_MINIMA = 8;
```

## ⚠️ Importante:
- As configurações se aplicam tanto aos formulários de **Participante** quanto **Organizador**
- As mudanças afetam tanto a validação no envio quanto a validação em tempo real (ao sair dos campos)
- **Sempre volte às configurações padrão antes de colocar em produção**


---
