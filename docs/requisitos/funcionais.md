# Requisitos Funcionais (RF)

Os Requisitos Funcionais descrevem os comportamentos, fluxos operacionais e serviços providos pelo sistema de **Monitoramento de Preços**. Eles estão alinhados às 5 atividades do [Story Map](story-map.md) e organizados por fatias de entrega (*Release 1 - MVP*, *Release 2* e *Release 3*).

---

## 📊 Matriz Resumo de Requisitos Funcionais

| ID | Nome do Requisito | Atividade do Story Map | Release | Prioridade | Regras Associadas |
| :--- | :--- | :--- | :---: | :---: | :---: |
| [`RF-001`](#rf-001-cadastro-de-usuario) | Cadastro de Usuário | 1. Acesso & Conta | R1 (MVP) | Must | [RN-001](regras-de-negocio.md#rn-001-unicidade-de-credenciais-de-usuario) |
| [`RF-002`](#rf-002-autenticacao-e-gestao-de-sessao) | Autenticação e Gestão de Sessão | 1. Acesso & Conta | R1 (MVP) | Must | [RN-002](regras-de-negocio.md#rn-002-bloqueio-por-tentativas-invalidas-de-login) |
| [`RF-003`](#rf-003-recuperacao-de-senha) | Recuperação de Senha | 1. Acesso & Conta | R2 | Should | - |
| [`RF-004`](#rf-004-gerenciamento-de-perfil-e-canais-de-contato) | Gerenciamento de Perfil e Canais de Contato | 1. Acesso & Conta | R2 | Should | - |
| [`RF-005`](#rf-005-cadastro-de-produto-para-monitoramento) | Cadastro de Produto para Monitoramento | 2. Gestão de Produtos | R1 (MVP) | Must | [RN-003](regras-de-negocio.md#rn-003-validacao-de-dominios-suportados), [RN-004](regras-de-negocio.md#rn-004-unicidade-de-url-por-usuario) |
| [`RF-006`](#rf-006-listagem-e-consulta-de-produtos) | Listagem e Consulta de Produtos Monitorados | 2. Gestão de Produtos | R1 (MVP) | Must | - |
| [`RF-007`](#rf-007-edicao-de-parametros-do-produto) | Edição de Parâmetros do Produto | 2. Gestão de Produtos | R2 | Should | - |
| [`RF-008`](#rf-008-pausar-e-retomar-monitoramento) | Pausar e Retomar Monitoramento | 2. Gestão de Produtos | R2 | Should | - |
| [`RF-009`](#rf-009-exclusao-de-produto-monitorado) | Exclusão de Produto do Monitoramento | 2. Gestão de Produtos | R1 (MVP) | Must | - |
| [`RF-010`](#rf-010-coleta-automatica-e-periodica-de-precos) | Coleta Automática e Periódica de Preços | 3. Coleta & Monitoramento | R1 (MVP) | Must | [RN-005](regras-de-negocio.md#rn-005-intervalo-minimo-de-coleta-por-dominio) |
| [`RF-011`](#rf-011-extracao-de-preco-e-disponibilidade) | Extração de Preço, Moeda e Disponibilidade | 3. Coleta & Monitoramento | R1 (MVP) | Must | - |
| [`RF-012`](#rf-012-politica-de-retentativas-em-falha) | Tratamento de Falhas com Retentativas Exponenciais | 3. Coleta & Monitoramento | R1 (MVP) | Must | [RN-006](regras-de-negocio.md#rn-006-politica-de-retentativas-em-falha) |
| [`RF-013`](#rf-013-armazenamento-do-historico-de-precos) | Armazenamento do Histórico de Preços | 4. Histórico & Análise | R1 (MVP) | Must | [RN-007](regras-de-negocio.md#rn-007-descarte-de-leituras-duplicadas-sem-variacao) |
| [`RF-014`](#rf-014-visualizacao-grafica-da-evolucao-de-precos) | Visualização Gráfica da Evolução de Preços | 4. Histórico & Análise | R2 | Should | - |
| [`RF-015`](#rf-015-destaque-de-menor-preco-historico) | Destaque de Menor Preço Histórico e Economia | 4. Histórico & Análise | R2 | Should | - |
| [`RF-016`](#rf-016-disparo-de-notificacao-por-e-mail) | Disparo de Notificação por E-mail ao Atingir Alvo | 5. Notificações & Alertas | R1 (MVP) | Must | [RN-008](regras-de-negocio.md#rn-008-condicao-de-disparo-de-alerta), [RN-009](regras-de-negocio.md#rn-009-evitar-notificacoes-repetitivas-cooldown) |
| [`RF-017`](#rf-017-alertas-instantaneos-via-telegram) | Alertas Instantâneos via Telegram | 5. Notificações & Alertas | R2 | Should | [RN-008](regras-de-negocio.md#rn-008-condicao-de-disparo-de-alerta), [RN-009](regras-de-negocio.md#rn-009-evitar-notificacoes-repetitivas-cooldown) |
| [`RF-018`](#rf-018-alerta-de-retorno-ao-estoque) | Notificação de Retorno ao Estoque | 5. Notificações & Alertas | R2 | Should | - |

---

## 🔐 1. Acesso & Conta (Autenticação)

### RF-001: Cadastro de Usuário
- **Descrição:** O sistema deve permitir que novos usuários criem uma conta informando nome completo, e-mail válido e senha segura.
- **Release / Prioridade:** Release 1 (MVP) | Must Have
- **Atores:** Visitante
- **Entradas:** Nome, e-mail e senha (com confirmação).
- **Saídas:** Conta criada com autenticação automática e redirecionamento para o dashboard principal.
- **Regras de Negócio:** [RN-001](regras-de-negocio.md#rn-001-unicidade-de-credenciais-de-usuario)

### RF-002: Autenticação e Gestão de Sessão
- **Descrição:** O sistema deve prover autenticação por e-mail e senha, gerenciamento seguro de cookies de sessão via Laravel/Inertia e funcionalidade de logout.
- **Release / Prioridade:** Release 1 (MVP) | Must Have
- **Atores:** Usuário Cadastrado
- **Entradas:** E-mail e senha.
- **Saídas:** Sessão autenticada ou mensagem de credenciais inválidas.
- **Regras de Negócio:** [RN-002](regras-de-negocio.md#rn-002-bloqueio-por-tentativas-invalidas-de-login)

### RF-003: Recuperação de Senha
- **Descrição:** O sistema deve possibilitar a recuperação de acesso esquecido através do envio de e-mail contendo link seguro e temporário para redefinição de senha.
- **Release / Prioridade:** Release 2 | Should Have
- **Atores:** Usuário Cadastrado
- **Entradas:** E-mail cadastrado.
- **Saídas:** E-mail com token temporário de redefinição.

### RF-004: Gerenciamento de Perfil e Canais de Contato
- **Descrição:** O usuário poderá atualizar seus dados cadastrais, alterar senha e configurar canais adicionais de contato (ex: Telegram Chat ID).
- **Release / Prioridade:** Release 2 | Should Have
- **Atores:** Usuário Autenticado

---

## 📦 2. Gestão de Produtos (CRUD)

### RF-005: Cadastro de Produto para Monitoramento
- **Descrição:** O usuário autenticado poderá cadastrar uma URL de produto e definir o Preço Alvo (R$) para início do acompanhamento.
- **Release / Prioridade:** Release 1 (MVP) | Must Have
- **Atores:** Usuário Autenticado
- **Entradas:** URL do produto, Preço Alvo (R$) e apelido opcional.
- **Saídas:** Confirmação de cadastro com título, imagem e preço inicial extraídos.
- **Regras de Negócio:** [RN-003](regras-de-negocio.md#rn-003-validacao-de-dominios-suportados), [RN-004](regras-de-negocio.md#rn-004-unicidade-de-url-por-usuario)

### RF-006: Listagem e Consulta de Produtos
- **Descrição:** A interface SPA (Inertia/Vue) deve exibir a lista de itens monitorados pelo usuário, informando imagem, título, preço atual, preço alvo, status de estoque e data da última checagem.
- **Release / Prioridade:** Release 1 (MVP) | Must Have
- **Atores:** Usuário Autenticado

### RF-007: Edição de Parâmetros do Produto
- **Descrição:** O usuário poderá alterar o preço alvo definido, o apelido e a categoria atribuída ao produto sem perder o histórico existente.
- **Release / Prioridade:** Release 2 | Should Have
- **Atores:** Usuário Autenticado

### RF-008: Pausar e Retomar Monitoramento
- **Descrição:** O usuário poderá desativar temporariamente a checagem automática de um produto e reativá-la quando desejar.
- **Release / Prioridade:** Release 2 | Should Have
- **Atores:** Usuário Autenticado

### RF-009: Exclusão de Produto Monitorado
- **Descrição:** O usuário poderá remover definitivamente um produto de sua lista de monitoramento.
- **Release / Prioridade:** Release 1 (MVP) | Must Have
- **Atores:** Usuário Autenticado

---

## 🕷️ 3. Coleta & Monitoramento (Scraping)

### RF-010: Coleta Automática e Periódica de Preços
- **Descrição:** O sistema deve executar tarefas agendadas em segundo plano (*Laravel Scheduler / Queues*) para rastrear os preços dos produtos ativos.
- **Release / Prioridade:** Release 1 (MVP) | Must Have
- **Atores:** Sistema (Scheduler/Queue Worker)
- **Regras de Negócio:** [RN-005](regras-de-negocio.md#rn-005-intervalo-minimo-de-coleta-por-dominio)

### RF-011: Extração de Preço e Disponibilidade
- **Descrição:** O coletor deve analisar o HTML da página do produto e extrair com precisão o valor monetário atual, moeda e status de disponibilidade em estoque.
- **Release / Prioridade:** Release 1 (MVP) | Must Have
- **Atores:** Sistema

### RF-012: Política de Retentativas em Falha
- **Descrição:** Em caso de indisponibilidade momentânea ou timeout da loja, o sistema deve executar retentativas controladas com intervalo exponencial (*exponential backoff*).
- **Release / Prioridade:** Release 1 (MVP) | Must Have
- **Atores:** Sistema
- **Regras de Negócio:** [RN-006](regras-de-negocio.md#rn-006-politica-de-retentativas-em-falha)

---

## 📈 4. Histórico & Análise de Preços

### RF-013: Armazenamento do Histórico de Preços
- **Descrição:** O sistema deve registrar na tabela de série temporal cada variação de preço identificada, mantendo data, hora e valor coletado.
- **Release / Prioridade:** Release 1 (MVP) | Must Have
- **Atores:** Sistema
- **Regras de Negócio:** [RN-007](regras-de-negocio.md#rn-007-descarte-de-leituras-duplicadas-sem-variacao)

### RF-014: Visualização Gráfica da Evolução de Preços
- **Descrição:** O sistema deve renderizar um gráfico interativo no frontend Vue 3 ilustrando a oscilação de valores ao longo dos últimos 7, 30 ou 90 dias.
- **Release / Prioridade:** Release 2 | Should Have
- **Atores:** Usuário Autenticado

### RF-015: Destaque de Menor Preço Histórico
- **Descrição:** A interface deve exibir de forma clara qual foi o menor preço já detectado desde o início do rastreamento e o percentual de desconto em relação à média.
- **Release / Prioridade:** Release 2 | Should Have
- **Atores:** Usuário Autenticado

---

## 🔔 5. Notificações & Alertas

### RF-016: Disparo de Notificação por E-mail
- **Descrição:** Quando o preço coletado for menor ou igual ao preço alvo definido pelo usuário, o sistema deve despachar um e-mail formatado com link direto para a loja.
- **Release / Prioridade:** Release 1 (MVP) | Must Have
- **Atores:** Sistema
- **Regras de Negócio:** [RN-008](regras-de-negocio.md#rn-008-condicao-de-disparo-de-alerta), [RN-009](regras-de-negocio.md#rn-009-evitar-notificacoes-repetitivas-cooldown)

### RF-017: Alertas Instantâneos via Telegram
- **Descrição:** O sistema deve permitir o recebimento de notificações imediatas através de um bot no Telegram com resumo da oferta e link de compra.
- **Release / Prioridade:** Release 2 | Should Have
- **Atores:** Sistema
- **Regras de Negócio:** [RN-008](regras-de-negocio.md#rn-008-condicao-de-disparo-de-alerta), [RN-009](regras-de-negocio.md#rn-009-evitar-notificacoes-repetitivas-cooldown)

### RF-018: Alerta de Retorno ao Estoque
- **Descrição:** O sistema deve notificar o usuário quando um produto que se encontrava esgotado voltar a ter unidades disponíveis para compra.
- **Release / Prioridade:** Release 2 | Should Have
- **Atores:** Sistema
