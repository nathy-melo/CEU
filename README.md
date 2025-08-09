A fazer:
- Tabela de banco de dados para participante e organizador
- Link entre páginas
- Dar funcionalidade a páginas de cadastro e edição de eventos com js (dar uma atenção para a página de organizador)
- Fazer conecção entre BD e front com php
- Criar o manual de uso



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
