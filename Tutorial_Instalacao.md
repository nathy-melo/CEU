# 📘 Tutorial Completo de Instalação - CEU

## 📋 Índice
1. [Requisitos do Sistema](#requisitos-do-sistema)
2. [Instalação do XAMPP](#instalação-do-xampp)
3. [Instalação do Projeto](#instalação-do-projeto)
4. [Configuração do Banco de Dados](#configuração-do-banco-de-dados)
5. [Instalação de Dependências do Sistema de Certificação](#instalação-de-dependências-do-sistema-de-certificação)
6. [Instalação da Fonte Inter (Necessária para Certificados)](#instalação-da-fonte-inter-necessária-para-certificados)
7. [Instalação do LibreOffice (Opcional mas Recomendado)](#instalação-do-libreoffice-opcional-mas-altamente-recomendado)
8. [Configuração do PHP](#configuração-do-php)
9. [Configuração do Painel Administrativo](#configuração-do-painel-administrativo)
10. [Configuração de Pastas e Permissões](#configuração-de-pastas-e-permissões)
11. [Primeiro Acesso e Verificação](#primeiro-acesso-e-verificação)
12. [Configuração do PWA](#configuração-do-pwa)
13. [Solução de Problemas Comuns](#solução-de-problemas-comuns)
14. [Modo de Desenvolvimento](#modo-de-desenvolvimento)

---

## 1️⃣ Requisitos do Sistema

### Software Necessário
- **Sistema Operacional**: Windows 7/8/10/11, Linux, ou macOS
- **PHP**: 7.4 ou superior
- **MySQL**: 5.7 ou superior
- **Apache**: 2.4 ou superior
- **Navegador**: Chrome, Firefox, Edge, Safari (versões atualizadas)

### Extensões PHP Obrigatórias
O sistema requer as seguintes extensões PHP habilitadas:
- ✅ `mysqli` - Conexão com MySQL
- ✅ `session` - Gerenciamento de sessões
- ✅ `zip` - Manipulação de arquivos DOCX/PPTX
- ✅ `mbstring` - Suporte a strings multibyte (UTF-8)
- ✅ `json` - Processamento JSON
- ✅ `curl` - Download de dependências (para Composer)
- ✅ `gd` - Processamento de imagens (usado pelo sistema de certificação)
- ✅ `fileinfo` - Detecção segura de tipos MIME em uploads de imagens

---

## 2️⃣ Instalação do XAMPP

### Windows

1. **Baixar o XAMPP**
   - Acesse: https://www.apachefriends.org/pt_br/download.html
   - Baixe a versão para Windows (PHP 7.4 ou superior)

2. **Instalar o XAMPP**
   - Execute o instalador baixado
   - Escolha os componentes: Apache, MySQL, PHP, phpMyAdmin
   - Pasta de instalação padrão: `C:\xampp`
   - Conclua a instalação

3. **Iniciar os Serviços**
   - Abra o "XAMPP Control Panel"
   - Clique em "Start" ao lado de Apache
   - Clique em "Start" ao lado de MySQL
   - Verifique se ambos ficam com fundo verde

4. **Verificar Instalação**
   - Abra o navegador
   - Acesse: http://localhost
   - Você deve ver a página de boas-vindas do XAMPP

### Linux (Ubuntu/Debian)

```bash
# Atualizar repositórios
sudo apt update

# Instalar Apache
sudo apt install apache2

# Instalar MySQL
sudo apt install mysql-server

# Instalar PHP e extensões
sudo apt install php php-mysqli php-mbstring php-zip php-json php-curl php-xml

# Reiniciar Apache
sudo systemctl restart apache2
```

### macOS

1. Baixe o XAMPP para macOS em: https://www.apachefriends.org
2. Monte o arquivo .dmg e arraste XAMPP para Applications
3. Abra XAMPP e inicie Apache e MySQL

---

## 3️⃣ Instalação do Projeto

### Passo 1: Obter os Arquivos

**Opção A: Download Direto**
1. Baixe o arquivo ZIP do projeto
2. Extraia para `C:\xampp\htdocs\CEU` (Windows) ou `/opt/lampp/htdocs/CEU` (Linux)

**Opção B: Git Clone**
```bash
cd C:\xampp\htdocs
git clone https://github.com/nathy-melo/CEU CEU
```

### Passo 2: Verificar Estrutura de Pastas

Após a instalação, a estrutura deve estar assim:
```
C:\xampp\htdocs\CEU\
├── index.php
├── BancoDados/
├── PaginasPublicas/
├── PaginasParticipante/
├── PaginasOrganizador/
├── PaginasGlobais/
├── Admin/
├── Certificacao/
├── Imagens/
└── ...
```

---

## 4️⃣ Configuração do Banco de Dados

### Método 1: Automático (Recomendado)

1. **Acesse o Site**
   - Abra o navegador
   - Vá para: http://localhost/CEU
   - O sistema detectará automaticamente que o banco não existe

2. **Instalação Automática**
   - Uma mensagem aparecerá: "⚠️ BANCO DE DADOS NÃO ENCONTRADO!"
   - Clique em "OK" para criar e importar automaticamente
   - Aguarde a conclusão (pode levar alguns segundos)
   - O site recarregará automaticamente

### Método 2: Manual (phpMyAdmin)

1. **Acessar phpMyAdmin**
   - Abra: http://localhost/phpmyadmin
   - Usuário: `root`
   - Senha: (deixe em branco)

2. **Criar o Banco de Dados**
   - Clique em "Novo" na barra lateral
   - Nome do banco: `CEU_bd`
   - Cotejamento: `utf8mb4_unicode_ci`
   - Clique em "Criar"

3. **Importar Estrutura das Tabelas**
   - Selecione o banco `CEU_bd` na barra lateral
   - Clique na aba "Importar"
   - Clique em "Escolher arquivo"
   - Navegue até: `C:\xampp\htdocs\CEU\BancoDados\BancodeDadosCEU.sql`
   - Clique em "Executar"
   - Aguarde a mensagem de sucesso

4. **Importar Dados Iniciais (Opcional)**
   - Ainda em "Importar"
   - Escolha o arquivo: `BancoDados\PopularBancoDados.sql`
   - Clique em "Executar"
   - Isso criará usuários de teste

### Verificar Configuração de Conexão

O arquivo `BancoDados/conexao.php` contém as credenciais do banco:

```php
$servidor = "localhost";     // Servidor do MySQL
$usuario = "root";          // Usuário padrão do XAMPP
$senha = "";               // Senha (vazia no XAMPP padrão)
$banco = "CEU_bd";         // Nome do banco de dados
```

⚠️ **IMPORTANTE**: Se você configurou uma senha para o MySQL, edite este arquivo!

---

## 5️⃣ Instalação de Dependências do Sistema de Certificação

O sistema de certificação requer bibliotecas PHP para gerar certificados em PDF.

### Método 1: Interface Web (Recomendado)

1. **Acessar o Instalador**
   - Após configurar o banco de dados
   - Vá em: http://localhost/CEU/certificacao/
   - Se as dependências não estiverem instaladas, você verá um botão "Instalar Dependências"

2. **Executar Instalação**
   - Clique em "Instalar Dependências"
   - Aguarde o download e instalação (pode levar 1-3 minutos)
   - Uma mensagem de sucesso aparecerá quando concluído

### Método 2: Linha de Comando (Composer)

**Pré-requisito**: Ter o Composer instalado globalmente

```bash
# Navegar para a pasta de certificação
cd C:\xampp\htdocs\CEU\Certificacao\bibliotecas

# Instalar dependências
composer install
```

Se você não tem o Composer instalado:

**Windows:**
1. Baixe de: https://getcomposer.org/download/
2. Execute o instalador
3. Reinicie o terminal

**Linux/macOS:**
```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### Verificar Instalação

Após a instalação, deve existir:
- ✅ Pasta: `Certificacao/bibliotecas/vendor/`
- ✅ Arquivo: `Certificacao/bibliotecas/vendor/autoload.php`

### Dependências Instaladas

O sistema instala automaticamente:
- **PHPWord** (phpoffice/phpword) - Manipulação de documentos DOCX
- **PHPPresentation** (phpoffice/phppresentation) - Manipulação de PPTX
- **mPDF** (mpdf/mpdf) - Geração de PDFs

---

## 6️⃣ Instalação da Fonte Inter (Necessária para Certificados)

O sistema de certificados padrão utiliza a fonte **Inter**, que precisa estar instalada no sistema para que os certificados sejam gerados com a formatação correta.

### Por que instalar?

- ✅ **Certificados padrão** dependem desta fonte
- ✅ **Renderização correta** dos textos nos PDFs
- ⚠️ **Sem ela**: O sistema tentará usar fontes substitutas, mas o resultado visual será diferente do esperado

### Instalação da Fonte Inter

#### Windows

**Opção 1: Download Direto**
1. Acesse o Google Fonts: https://fonts.google.com/specimen/Inter
2. Clique em "Download family" (botão no canto superior direito)
3. Extraia o arquivo ZIP baixado
4. Abra a pasta `static/` ou a raiz do ZIP
5. Selecione todos os arquivos `.ttf` (Inter-Regular.ttf, Inter-Bold.ttf, etc.)
6. Clique com botão direito → "Instalar" ou "Instalar para todos os usuários"
7. Aguarde a instalação concluir

**Opção 2: Instalação Rápida**
1. Baixe diretamente: https://github.com/rsms/inter/releases/latest
2. Procure por `Inter-*.zip` nos assets
3. Extraia e instale os arquivos `.ttf` como acima

**Verificar Instalação:**
- Abra o Painel de Controle → Fontes
- Procure por "Inter" na lista
- Você deve ver: Inter Regular, Inter Bold, Inter SemiBold, etc.

#### Linux (Ubuntu/Debian)

```bash
# Instalar via repositório (se disponível)
sudo apt update
sudo apt install fonts-inter

# OU instalar manualmente:
# 1. Baixar fonte
wget https://github.com/rsms/inter/releases/download/v3.19/Inter-3.19.zip

# 2. Extrair
unzip Inter-3.19.zip -d inter-font

# 3. Copiar para pasta de fontes do sistema
sudo mkdir -p /usr/share/fonts/truetype/inter
sudo cp inter-font/*.ttf /usr/share/fonts/truetype/inter/

# 4. Atualizar cache de fontes
sudo fc-cache -f -v

# 5. Verificar instalação
fc-list | grep Inter
```

#### macOS

**Opção 1: Google Fonts**
1. Acesse: https://fonts.google.com/specimen/Inter
2. Clique em "Download family"
3. Extraia o ZIP
4. Abra cada arquivo `.ttf`
5. Clique em "Instalar Fonte" no Font Book

**Opção 2: Homebrew**
```bash
brew tap homebrew/cask-fonts
brew install --cask font-inter
```

**Verificar Instalação:**
- Abra o Font Book (Livro de Fontes)
- Procure por "Inter" na lista

### Reiniciar Serviços (Importante!)

Após instalar a fonte, **reinicie o Apache** para que o PHP reconheça a nova fonte:

**Windows (XAMPP):**
- XAMPP Control Panel → Stop Apache → Start Apache

**Linux:**
```bash
sudo systemctl restart apache2
```

**Se usar LibreOffice para conversão, reinicie-o também** ou reinicie o computador.

### Testar a Fonte

Para verificar se a fonte está sendo reconhecida:

1. Acesse uma das contas de teste de organizador
2. Gere um certificado
3. Verifique se o texto está com a fonte correta (sem partes fora do lugar por exemplo)

---

## 7️⃣ Instalação do LibreOffice (Opcional mas ALTAMENTE RECOMENDADO)

O LibreOffice é **opcional** para geração de certificados em PDF. O sistema funciona sem ele, mas a qualidade pode variar em muito!

### Por que instalar?

- ✅ **Melhor preservação do layout** dos certificados
- ✅ **Conversão perfeita** de PPTX → PDF
- ✅ **Templates complexos** mantêm formatação exata
- ⚠️ **Sem ele**: O sistema usa fallback (PHPWord + mPDF) que funciona, mas pode ter pequenas diferenças visuais em layouts

### Instalação

#### Windows

1. **Baixar LibreOffice**
   - Acesse: https://www.libreoffice.org/download/download/
   - Baixe a versão para Windows (64-bit recomendado)

2. **Instalar**
   - Execute o instalador
   - Siga as instruções padrão
   - Pasta padrão: `C:\Program Files\LibreOffice`

3. **Verificar Instalação**
   - O sistema detectará automaticamente o LibreOffice em:
     - `C:\Program Files\LibreOffice\program\soffice.exe`
     - `C:\Program Files (x86)\LibreOffice\program\soffice.exe`

#### Linux (Ubuntu/Debian)

```bash
sudo apt update
sudo apt install libreoffice
```

#### macOS

1. Baixe de: https://www.libreoffice.org/download/download/
2. Abra o .dmg e arraste para Applications

### Configuração Manual (se necessário)

Se o LibreOffice foi instalado em local diferente, você pode configurar manualmente:

**Windows:**
1. Vá em: Painel de Controle → Sistema → Variáveis de Ambiente
2. Crie uma nova variável de sistema:
   - Nome: `SOFFICE_PATH`
   - Valor: `C:\SeuCaminho\LibreOffice\program\soffice.exe`

**Linux/macOS:**
```bash
export SOFFICE_PATH="/usr/bin/soffice"
```

Ou edite `Certificacao/config.php`:
```php
'caminho_soffice' => 'C:\\Caminho\\Completo\\soffice.exe',
```

---

## 8️⃣ Configuração do PHP

### Editar php.ini

Localize o arquivo `php.ini`:
- **Windows XAMPP**: `C:\xampp\php\php.ini`
- **Linux**: `/etc/php/7.4/apache2/php.ini`
- **macOS XAMPP**: `/Applications/XAMPP/xamppfiles/etc/php.ini`

### Extensões Obrigatórias

Procure e descomente (remova o `;` no início) as seguintes linhas:

```ini
; === Extensões Obrigatórias ===
extension=mysqli
extension=mbstring
extension=zip
extension=curl
extension=fileinfo
extension=gd
```

### Reiniciar Apache

Após editar o `php.ini`:

**Windows (XAMPP Control Panel):**
- Clique em "Stop" no Apache
- Clique em "Start" no Apache

**Linux:**
```bash
sudo systemctl restart apache2
```

## 9️⃣ Configuração do Painel Administrativo

O sistema possui um painel administrativo para gerenciar códigos de organizador e realizar backups.

### Credenciais Padrão

O arquivo `Admin/ConfigAdmin.php` contém as credenciais (em hash SHA-256):

```php
// Padrão de teste:
// Usuário: infofriends
// Senha: 12345678
define('ADMIN_USER_HASH', 'b99a59b57641f97c9aa0e5204343aa0ce55564c9c90cdb4cd11001e04123e048');
define('ADMIN_PASS_HASH', 'ef797c8118f02dfb649607dd5d3f8c7623048c9c063d532cc95c5ed7a898a64f');
```

### Acessar o Painel Admin

- URL: http://localhost/CEU/Admin/
- Use as credenciais configuradas

---

## 🔟 Configuração de Pastas e Permissões

### Pastas que Precisam de Permissão de Escrita

O sistema precisa criar e modificar arquivos nas seguintes pastas:

```
CEU/
├── Admin/Backups/              # Armazenamento de backups
├── Certificacao/certificados/  # PDFs de certificados gerados
├── Certificacao/templates/     # Templates de certificados
├── ImagensEventos/             # Imagens enviadas de eventos
├── ImagensPerfis/              # Fotos de perfil dos usuários
└── Certificacao/bibliotecas/   # Dependências do Composer
```

---

## 1️⃣1️⃣ Primeiro Acesso e Verificação

### 1. Acessar o Sistema

1. Abra o navegador
2. Acesse: http://localhost/CEU
3. Você verá a página inicial do CEU

### 2. Verificação Automática do Banco

Na primeira vez que acessar, o sistema:
- ✅ Verifica se o banco existe
- ✅ Verifica se todas as tabelas estão corretas
- ✅ Oferece instalação/atualização automática se necessário

### 3. Cadastrar Primeiro Usuário

**Opção A: Usar Dados Populados (se importou PopularBancoDados.sql)**

Se você importou o arquivo de dados iniciais, já existem usuários de teste.

**Opção B: Criar Novo Usuário**

1. Clique em "Cadastre-se"
2. Escolha "Participante" ou "Organizador"
3. Preencha os dados

**Para cadastrar Organizador:**
- Você precisa de um código de organizador
- Gere códigos pelo Painel Admin (http://localhost/CEU/Admin/)

### 4. Realizar Login

1. Use as credenciais criadas
2. Você será redirecionado para o painel correspondente

### 5. Testar Funcionalidades Básicas

**Como Participante:**
- ✅ Visualizar eventos disponíveis
- ✅ Editar perfil
- ✅ Favoritar eventos

**Como Organizador:**
- ✅ Criar um evento de teste
- ✅ Adicionar descrição e imagem
- ✅ Gerenciar participantes

### 6. Testar Sistema de Certificação

1. Como Organizador, crie um evento com certificado
2. Configure um template (use o modelo padrão)
3. Gere um certificado de teste
4. Verifique se o PDF foi criado

### Lista de Verificação Completa

Execute esta checklist:
```
☐ XAMPP instalado e rodando (Apache + MySQL)
☐ Projeto em C:\xampp\htdocs\CEU
☐ Banco de dados CEU_bd criado e populado
☐ Extensões PHP habilitadas (mysqli, zip, mbstring, gd, fileinfo)
☐ Dependências do Composer instaladas
☐ Fonte Inter instalada no sistema
☐ Apache reiniciado após instalação da fonte
☐ LibreOffice instalado (opcional)
☐ Permissões de pastas configuradas
☐ Acesso ao site funcionando (http://localhost/CEU)
☐ Login/cadastro funcionando
☐ Criação de evento funcionando
☐ Geração de certificado funcionando (com fonte correta)
☐ Painel Admin acessível
```

---

## 1️⃣2️⃣ Configuração do PWA

O CEU é um Progressive Web App (PWA), permitindo instalação como aplicativo. Porém, somente se o sistema identificar a quantidade de pixels vertical maior que a horizontal, considerando o dispositivo como mobile.

### O que já está Configurado

✅ Service Worker (`sw.js`)
✅ Manifest (`manifest.json`)
✅ Ícones e metadados
✅ Modo offline básico

---

## 1️⃣3️⃣ Solução de Problemas Comuns

### Problema: "Erro na conexão com o banco de dados"

**Causas possíveis:**
- MySQL não está rodando
- Credenciais incorretas em `BancoDados/conexao.php`
- Banco de dados não foi criado

**Solução:**
```
1. Abrir XAMPP Control Panel
2. Verificar se MySQL está com status "Running" (verde)
3. Se não, clicar em "Start"
4. Verificar credenciais em conexao.php
5. Criar banco manualmente via phpMyAdmin
```

### Problema: "Extensão mysqli não encontrada"

**Solução:**
```
1. Abrir php.ini
2. Procurar: ;extension=mysqli
3. Remover o ; (ponto e vírgula)
4. Salvar arquivo
5. Reiniciar Apache
```

### Problema: "Erro ao gerar certificado"

**Causas possíveis:**
- Dependências do Composer não instaladas
- Pasta sem permissão de escrita
- Template não encontrado
- Fonte Inter não instalada

**Solução:**
```
1. Verificar se existe: Certificacao/bibliotecas/vendor/
2. Se não existe, instalar dependências
3. Verificar permissões da pasta Certificacao/certificados
4. Verificar se template existe em Certificacao/templates
5. Instalar fonte Inter no sistema (ver seção 6)
6. Reiniciar Apache após instalar a fonte
```

### Problema: "Certificado gerado com fonte errada"

**Causa:**
- Fonte Inter não está instalada no sistema

**Solução:**
```
1. Baixar fonte Inter: https://fonts.google.com/specimen/Inter
2. Instalar todos os arquivos .ttf
3. Reiniciar Apache
4. Se usar LibreOffice, reiniciar o computador
5. Gerar certificado novamente
```

### Problema: "Upload de imagem falha"

**Causas possíveis:**
- Limite de upload muito baixo
- Pasta sem permissão
- Formato de arquivo inválido

**Solução:**
```
1. Editar php.ini:
   upload_max_filesize = 10M
   post_max_size = 12M
2. Reiniciar Apache
3. Verificar permissões de ImagensEventos e ImagensPerfis
```

### Problema: "Session timeout muito curto"

**Solução:**
O sistema já configura 6 minutos (360 segundos), mas pode aumentar em `VerificarSessao.php`:

```php
ini_set('session.gc_maxlifetime', 7200); // 2 horas
```

### Problema: "Composer install falha"

**Causas possíveis:**
- Sem conexão com internet
- Função `exec()` desabilitada
- Limite de memória baixo

**Solução:**
```
1. Verificar conexão internet
2. No php.ini, procurar disable_functions
3. Remover "exec" da lista (se presente)
4. Aumentar memory_limit para 512M
5. Reiniciar Apache
```

### Problema: "LibreOffice não detectado"

**Solução:**
```
1. Verificar instalação: soffice --version
2. Se instalado em local diferente, configurar variável:
   SOFFICE_PATH=C:\Caminho\soffice.exe
3. Ou editar Certificacao/config.php
```

### Problema: "Banco desatualizado"

O sistema detecta automaticamente diferenças no banco.

**Solução:**
```
1. Aceitar a atualização automática quando solicitado
2. Ou executar manualmente BancodeDadosCEU.sql via phpMyAdmin
```

### Logs de Erro

**Onde encontrar logs:**

**Apache:**
- Windows: `C:\xampp\apache\logs\error.log`
- Linux: `/var/log/apache2/error.log`

**PHP:**
- Configure em php.ini:
```ini
error_log = "C:\xampp\php\logs\php_error.log"
log_errors = On
```

**MySQL:**
- Windows: `C:\xampp\mysql\data\mysql_error.log`
- Linux: `/var/log/mysql/error.log`

---

## 1️⃣4️⃣ Modo de Desenvolvimento

Para facilitar testes durante desenvolvimento, o sistema possui configurações especiais.

### Desabilitar Validações de Cadastro

**Arquivo**: `PaginasPublicas/ValidacoesCadastro.js`

```javascript
// ========== CONFIGURAÇÕES PARA TESTES ==========
var VALIDAR_CPF = false;        // Aceita CPFs inválidos
var VALIDAR_EMAIL = false;      // Aceita emails inválidos
var VALIDAR_SENHA = false;      // Senha fraca permitida
var SENHA_MINIMA = 0;           // Sem tamanho mínimo
var NOME_MINIMO = 0;            // mínimo de caracteres (0 = desativar)
```

### Modo de Teste de Login

**Arquivo**: `PaginasPublicas/ProcessarLogin.php`

```php
// MODO DE TESTE - Desabilita verificação de senha
define('MODO_TESTE_LOGIN', false); // true = permite login sem senha
```

### Debug de SQL

Para ver queries executadas:

```php
// Em qualquer arquivo PHP após incluir conexao.php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
```

### Ambiente de Desenvolvimento

**Arquivo**: `Admin/ConfigAdmin.php`

```php
define('SYSTEM_ENV', 'DEVELOPMENT'); // ou 'PRODUCTION'
```

Em modo DEVELOPMENT:
- Logs mais detalhados
- Mensagens de erro visíveis
- Validações menos rígidas

### Gerar Dados de Teste

Use o arquivo `PopularBancoDados.sql` que cria:
- Usuários de teste
- Eventos de exemplo
- Inscrições simuladas

```bash
# Via linha de comando MySQL
mysql -u root CEU_bd < BancoDados/PopularBancoDados.sql
```

---

## 📚 Recursos Adicionais

### Documentação do Sistema

- **README.md** - Visão geral e início rápido
- **Este arquivo** - Tutorial completo de instalação
- Comentários no código - Explicações detalhadas

### Estrutura do Banco de Dados

Principais tabelas:
- `usuario` - Dados de login e perfil
- `participantes` - Informações de participantes
- `organizadores` - Informações de organizadores
- `evento` - Dados de eventos
- `inscricoes` - Registros de participação
- `certificado` - Certificados emitidos
- `notificacoes` - Sistema de notificações
- `favoritos` - Eventos favoritados

### Fluxo de Certificação

```
1. Organizador cria evento com certificado
2. Participante se inscreve
3. Organizador marca presença
4. Sistema gera certificado (DOCX/PPTX → PDF)
5. Certificado armazenado e código gerado
6. Participante baixa certificado
7. Qualquer pessoa pode verificar autenticidade
```

### Segurança Implementada

✅ Sessões com timeout
✅ Proteção contra SQL Injection (mysqli_real_escape_string)
✅ Validação de dados no frontend e backend
✅ Hashing de senhas (password_hash)
✅ Verificação de tipos de arquivo
✅ Códigos de verificação únicos para certificados

### Backup e Recuperação

O sistema oferece backup via Painel Admin:

1. Acesse: http://localhost/CEU/Admin/
2. Menu: Backups
3. Gerar Backup → cria cópia SQL
4. Baixar backups anteriores
5. Restaurar quando necessário

**Backup Manual:**
```bash
mysqldump -u root CEU_bd > backup.sql
```

**Restaurar:**
```bash
mysql -u root CEU_bd < backup.sql
```

## 📝 Checklist Final de Instalação

### Pré-requisitos
- [ ] XAMPP instalado
- [ ] Apache rodando
- [ ] MySQL rodando
- [ ] PHP 7.4+ instalado

### Projeto
- [ ] Arquivos em `C:\xampp\htdocs\CEU`

### Banco de Dados
- [ ] Banco `CEU_bd` criado
- [ ] Arquivo `BancodeDadosCEU.sql` importado
- [ ] Arquivo `PopularBancoDados.sql` importado (opcional)
- [ ] Conexão testada

### Certificação
- [ ] Composer instalado
- [ ] Dependências instaladas (`vendor/` existe)
- [ ] Fonte Inter instalada no sistema
- [ ] Apache reiniciado após instalar fonte
- [ ] LibreOffice instalado (recomendado)
- [ ] Template de teste existe.sql` importado
- [ ] Arquivo `PopularBancoDados.sql` importado (opcional)
- [ ] Conexão testada

### PHP
- [ ] Extensão `mysqli` habilitada
- [ ] Extensão `mbstring` habilitada
- [ ] Extensão `zip` habilitada
- [ ] Extensão `curl` habilitada
- [ ] `upload_max_filesize` ajustado
- [ ] `memory_limit` ajustado
- [ ] Apache reiniciado

### Certificação
- [ ] Composer instalado
- [ ] Dependências instaladas (`vendor/` existe)
- [ ] LibreOffice instalado (recomendado)
- [ ] Template de teste existe

### Permissões
- [ ] `Admin/Backups` gravável
- [ ] `Certificacao/certificados` gravável
- [ ] `ImagensEventos` gravável
- [ ] `ImagensPerfis` gravável

### Testes
- [ ] Site acessível em `http://localhost/CEU`
- [ ] Cadastro funcionando
- [ ] Login funcionando
- [ ] Criação de evento funcionando
- [ ] Upload de imagem funcionando
- [ ] Geração de certificado funcionando
