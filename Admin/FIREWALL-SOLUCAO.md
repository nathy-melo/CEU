# CONFIGURAR FIREWALL WINDOWS - CEU PWA

## MÉTODO RÁPIDO (Recomendado)

### 1. Abrir Firewall do Windows:
```
Windows + R → firewall.cpl → Enter
```

### 2. Permitir app pelo firewall:
- Clicar **"Permitir um app ou recurso através do Firewall do Windows"**
- Clicar **"Alterar configurações"**
- Procurar **"Apache HTTP Server"** na lista
- Marcar **Privada** (rede doméstica)
- Marcar **Pública** (se necessário)
- Clicar **OK**

### 3. OU criar regra manual:
- **"Configurações avançadas"**
- **"Regras de Entrada"** → **"Nova Regra"**
- Tipo: **Porta**
- Protocolo: **TCP**
- Porta: **80**
- Ação: **Permitir conexão**
- Perfis: **Domínio, Privado, Público**
- Nome: **"XAMPP Apache CEU"**

## COMANDOS PRONTOS

### Testar se firewall permite:
```bash
# PowerShell no PC:
Test-NetConnection -Port 80 -ComputerName 192.168.3.53

# No celular (Chrome):
# Tentar: http://192.168.3.53/CEU/
```

### Configurar XAMPP para IP específico:
```bash
# 1. Editar: C:\xampp\apache\conf\httpd.conf
# 2. Encontrar linha: Listen 80
# 3. Adicionar: Listen 192.168.3.53:80
# 4. Reiniciar Apache no XAMPP
```
