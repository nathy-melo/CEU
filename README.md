# Como criar uma nova página para funcionar com o menu expansível

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
