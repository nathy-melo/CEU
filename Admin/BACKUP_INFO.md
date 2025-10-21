# 🔒 Sistema de Backup - CEU

## Como Funciona

O sistema de backup está integrado no **Painel Admin** do CEU. Não é complexo, é simples e direto!

### 📂 Localização

- **Script:** `Admin/GerenciadorBackup.php`
- **Backups:** `Admin/Backups/` (arquivos .sql)
- **Interface:** Seção "🔒 Backups" no Painel Admin

## ✨ Funcionalidades

- ✅ **Fazer Backup** - Cria backup completo do BD com timestamp
- ✅ **Listar** - Mostra todos os backups disponíveis
- ✅ **Baixar** - Faz download de um backup para seu PC
- ✅ **Restaurar** - Restaura um backup anterior
- ✅ **Deletar** - Remove um backup que não precisa mais

## 🚀 Como Usar

### Via Painel Admin

1. Abra: `http://localhost/CEU/Admin/PainelAdmin.html`
2. Clique no botão **"🔒 Backups"** na navegação
3. Use as opções:
   - **💾 Fazer Backup Agora** - Cria um novo backup
   - **🔄 Atualizar Lista** - Recarrega a lista
   - **Tabela de Backups:**
     - 📥 **Baixar** - Faz download
     - ↻ **Restaurar** - Restaura (com confirmação)
     - 🗑️ **Deletar** - Remove

### Via Terminal (para testes)

```bash
cd c:\xampp\htdocs\CEU\Admin
php testar_backup.php
```

## 📋 API Endpoints

Se precisar usar via código:

```
POST   /CEU/Admin/GerenciadorBackup.php?acao=fazer-backup    → Criar backup
POST   /CEU/Admin/GerenciadorBackup.php?acao=listar          → Listar
POST   /CEU/Admin/GerenciadorBackup.php?acao=restaurar       → Restaurar
POST   /CEU/Admin/GerenciadorBackup.php?acao=deletar         → Deletar
GET    /CEU/Admin/GerenciadorBackup.php?acao=baixar          → Baixar
POST   /CEU/Admin/GerenciadorBackup.php?acao=info            → Info
```

## 📊 Exemplo de Resposta

```json
{
  "sucesso": true,
  "arquivo": "backup_2025-10-21_03-20-39.sql",
  "tamanho": 9183,
  "mensagem": "Backup realizado com sucesso"
}
```

## ⚠️ Importante

- ✅ Backups são salvos em `Admin/Backups/`
- ✅ Cada backup tem um timestamp (data e hora)
- ✅ Restauração **SUBSTITUI** todos os dados atuais
- ✅ Sempre confirme antes de restaurar
- ✅ Faça backup regularmente antes de alterações importantes

## 📂 Estrutura

```
Admin/
├── GerenciadorBackup.php       ← Script do sistema
├── Backups/                    ← Pasta de backups
│   ├── backup_YYYY-MM-DD_HH-mm-ss.sql
│   └── .htaccess               ← Proteção
└── testar_backup.php           ← Script de teste
```

## 🧪 Teste Rápido

Abra o terminal e execute:

```bash
cd c:\xampp\htdocs\CEU\Admin
php testar_backup.php
```

Você verá algo como:

```
=== Teste de Backup ===

1. Informações do BD:
Array ( [sucesso] => 1 [tamanho] => 208 KB [numTabelas] => 8 )

2. Fazendo backup...
Array ( [sucesso] => 1 [arquivo] => backup_2025-10-21_03-20-39.sql ... )

3. Listando backups:
Total: 1
  - backup_2025-10-21_03-20-39.sql (8.97 KB)
```

## 💡 Dicas

- Faça backup **antes** de deletar eventos ou usuários
- Baixe backups importantes e guarde em outro lugar
- Delete backups antigos para economizar espaço
- Testagem periódica: Tente restaurar um backup antigo

---

**Simples, funcional e integrado ao admin!** 🎉
