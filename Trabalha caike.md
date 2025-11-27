### 1. Correções Críticas (Prioridade Máxima)
- ✅ Corrigir bug de pesquisa na página "Meus Eventos" que quebra o layout  
  **Dificuldade:** Média - **CONCLUÍDO**

- ✅ Corrigir login quebrando o layout com avisos  
  **Dificuldade:** Baixa - **CONCLUÍDO** (Login mantém 500px fixos e só aumenta quando há aviso)

- ✅ Corrigir necessidade de dar F5 para validações funcionarem (cadastro, login, adicionar colaborador, etc.)  
  **Dificuldade:** Média - **CONCLUÍDO** (Validações ativadas, mensagens específicas por campo, validação de nome mínimo 5 caracteres)

- ✅ Corrigir F5 na página do evento visto pelo organizador que quebra o site  
  **Dificuldade:** Alta - **CONCLUÍDO** (Removidos alerts e redirecionamentos forçados, detecção inteligente de dados carregados)

- ✅ Corrigir sistema de sessões não funcionando corretamente (timeout, avisos e mensagens de expiração)  
  **Dificuldade:** Alta - **CONCLUÍDO** (Modo teste desativado, VerificarSessao.php corrigido, modal de expiração melhorado, mensagens específicas)

- Corrigir responsividade e otimização do site
  **Dificuldade:** Alta

- ✅ Corrigir parte de certificação que está muito pesada/lenta para emitir certificado  
  **Dificuldade:** Alta

- ✅ Corrigir setas laterais mudando de cor (branca/preta) de forma inconsistente  
  **Dificuldade:** Baixa

- ✅ Corrigir "Gerenciar" aparecendo como "Participante" no "Meus Eventos" do organizador e o * aparecendo onde não deveria  
  **Dificuldade:** Baixa - **CONCLUÍDO** (Asteriscos só aparecem em modo edição, botão renomeado para Gerenciar)

- ✅ Corrigir importar/exportar no gerenciador (cores erradas)  
  **Dificuldade:** Baixa - **CONCLUÍDO** (Cores dos modais corrigidas de branco para cinza-escuro)

- ✅ Melhorar mensagem notificação  
  **Dificuldade:** Baixa - **CONCLUÍDO** (Mensagens agora mostram remetente, evento e conteúdo formatados de forma clara e legível, sem os separadores confusos)

### 2. Melhorias de Usabilidade e Organização
- ✅ Limitar quantidade de eventos exibidos (máx. 72, inicia com 16, opção de "Ver eventos já finalizados" no rodapé e no filtro)  
  **Dificuldade:** Média - **CONCLUÍDO** (Sistema de paginação implementado, checkbox de eventos finalizados no filtro lateral)

- ✅ Implementar a mesma limitação de exibição em tabelas de participantes e admin  
  **Dificuldade:** Média - **CONCLUÍDO** (Sistema PaginacaoTabelas.js criado e pronto para uso nas tabelas)

- ✅ Não mostrar eventos finalizados no início, deixar como opção de filtro  
  **Dificuldade:** Baixa - **CONCLUÍDO** (Eventos finalizados ocultos por padrão, opção no filtro para exibir)

- ✅ Adicionar campo de carga horária para organizador (além do participante)  
  **Dificuldade:** Baixa - **CONCLUÍDO** (Campos separados para participante e organizador em criar/editar evento)

### 3. Funções Extras e Ideias Futuras
- ✅ Inspirar-se na função de enviar mensagem para CPF específico (mas remover essa função, pois já está coberta)  
  **Dificuldade:** Baixa

- ✅ Alterar botão para enviar certificado após confirmação de presença do participante  
  **Dificuldade:** Baixa

- ✅ Adicionar modelo de certificado ao criar/editar evento  
  **Dificuldade:** Média - **CONCLUÍDO** (Organizador pode selecionar/fazer upload de modelos personalizados de certificado para participantes e organizadores)

- ✅ Adicionar botão de "Verificar Certificado" no index  
  **Dificuldade:** Baixa
