# Requisitos Funcionais (RF)

Os Requisitos Funcionais descrevem os comportamentos, fluxos operacionais e servicos providos pelo sistema de **Monitoramento de Precos**, integralmente localizado em Portugues do Brasil (`pt_BR`). Eles estao alinhados as 5 atividades do [Story Map](story-map.md) e consolidados para entrega na **Release 1 (MVP Completo)**.

---

## Matriz Resumo de Requisitos Funcionais

| ID | Nome do Requisito | Atividade do Story Map | Release | Prioridade | Regras Associadas |
| :--- | :--- | :--- | :---: | :---: | :--- |
| [`RF-001`](#rf-001-cadastro-de-usuario-e-verificacao-de-e-mail) | Cadastro de Usuario e Verificacao de E-mail | 1. Acesso & Conta | R1 (MVP) | Must | [RN-001](regras-de-negocio.md#rn-001-unicidade-de-credenciais-de-usuario) |
| [`RF-002`](#rf-002-autenticacao-e-gestao-de-sessao) | Autenticacao e Gestao de Sessao | 1. Acesso & Conta | R1 (MVP) | Must | [RN-002](regras-de-negocio.md#rn-002-bloqueio-por-tentativas-invalidas-de-login) |
| [`RF-003`](#rf-003-recuperacao-de-senha) | Recuperacao de Senha | 1. Acesso & Conta | R1 (MVP) | Must | - |
| [`RF-004`](#rf-004-gerenciamento-de-perfil-e-conta) | Gerenciamento de Perfil e Conta | 1. Acesso & Conta | R1 (MVP) | Must | [RN-001](regras-de-negocio.md#rn-001-unicidade-de-credenciais-de-usuario) |
| [`RF-005`](#rf-005-cadastro-de-produto-para-monitoramento) | Cadastro de Produto para Monitoramento | 2. Gestao de Produtos | R1 (MVP) | Must | [RN-003](regras-de-negocio.md#rn-003-validacao-de-dominios-suportados), [RN-004](regras-de-negocio.md#rn-004-unicidade-de-url-por-usuario), [RN-010](regras-de-negocio.md#rn-010-limite-de-produtos-por-usuario) |
| [`RF-006`](#rf-006-listagem-e-consulta-de-produtos) | Listagem e Consulta de Produtos Monitorados | 2. Gestao de Produtos | R1 (MVP) | Must | - |
| [`RF-007`](#rf-007-edicao-de-parametros-do-produto) | Edicao de Parametros do Produto | 2. Gestao de Produtos | R1 (MVP) | Must | - |
| [`RF-008`](#rf-008-pausar-e-retomar-monitoramento) | Pausar e Retomar Monitoramento | 2. Gestao de Produtos | R1 (MVP) | Must | - |
| [`RF-009`](#rf-009-exclusao-de-produto-monitorado) | Exclusao de Produto do Monitoramento | 2. Gestao de Produtos | R1 (MVP) | Must | - |
| [`RF-010`](#rf-010-coleta-automatica-periodica-e-sob-demanda) | Coleta Automatica Periodica e Sob Demanda | 3. Coleta & Monitoramento | R1 (MVP) | Must | [RN-005](regras-de-negocio.md#rn-005-intervalo-minimo-de-coleta-por-dominio) |
| [`RF-011`](#rf-011-extracao-de-preco-a-vista-a-prazo-e-disponibilidade) | Extracao de Preco a Vista, a Prazo e Disponibilidade | 3. Coleta & Monitoramento | R1 (MVP) | Must | - |
| [`RF-012`](#rf-012-politica-de-retentativas-em-falha) | Tratamento de Falhas com Retentativas Exponenciais | 3. Coleta & Monitoramento | R1 (MVP) | Must | [RN-006](regras-de-negocio.md#rn-006-politica-de-retentativas-em-falha) |
| [`RF-013`](#rf-013-armazenamento-do-historico-de-precos) | Armazenamento do Historico de Precos | 4. Historico & Analise | R1 (MVP) | Must | [RN-007](regras-de-negocio.md#rn-007-descarte-de-leituras-duplicadas-sem-variacao) |
| [`RF-014`](#rf-014-visualizacao-grafica-da-evolucao-de-precos) | Visualizacao Grafica da Evolucao de Precos | 4. Historico & Analise | R1 (MVP) | Must | - |
| [`RF-015`](#rf-015-destaque-de-menor-preco-historico) | Destaque de Menor Preco Historico e Economia | 4. Historico & Analise | R1 (MVP) | Must | - |
| [`RF-016`](#rf-016-disparo-de-notificacao-por-e-mail-e-central-de-alertas) | Disparo de Notificacao por E-mail e Central de Alertas | 5. Notificacoes & Alertas | R1 (MVP) | Must | [RN-008](regras-de-negocio.md#rn-008-condicao-de-disparo-de-alerta), [RN-009](regras-de-negocio.md#rn-009-evitar-notificacoes-repetitivas-cooldown) |
| [`RF-017`](#rf-017-alerta-de-retorno-ao-estoque) | Notificacao de Retorno ao Estoque | 5. Notificacoes & Alertas | R1 (MVP) | Should | [RN-009](regras-de-negocio.md#rn-009-evitar-notificacoes-repetitivas-cooldown) |

---

## 1. Acesso & Conta (Autenticacao em `pt_BR`)

### RF-001: Cadastro de Usuario e Verificacao de E-mail
- **Descricao:** O sistema deve permitir que novos usuarios criem uma conta informando nome completo, e-mail valido e senha segura (minimo de 8 caracteres com confirmacao), exigindo a verificacao do endereco de e-mail (`MustVerifyEmail`) antes de liberar o acesso ao painel principal.
- **Release / Prioridade:** Release 1 (MVP) | Must Have
- **Atores:** Visitante
- **Entradas:** Nome, e-mail e senha (com confirmacao).
- **Saidas:** Conta criada, envio automatico de link de verificacao por e-mail e redirecionamento para a tela de aviso de verificacao de e-mail (e, apos confirmado, acesso ao dashboard).
- **Regras de Negocio:** [RN-001](regras-de-negocio.md#rn-001-unicidade-de-credenciais-de-usuario)

### RF-002: Autenticacao e Gestao de Sessao
- **Descricao:** O sistema deve prover autenticacao simples por e-mail e senha (sem 2FA ou Passkeys), gerenciamento seguro de cookies de sessao via Laravel Fortify/Inertia e funcionalidade de logout.
- **Release / Prioridade:** Release 1 (MVP) | Must Have
- **Atores:** Usuario Cadastrado
- **Entradas:** E-mail e senha.
- **Saidas:** Sessao autenticada ou mensagem de credenciais invalidas em Portugues (`pt_BR`).
- **Regras de Negocio:** [RN-002](regras-de-negocio.md#rn-002-bloqueio-por-tentativas-invalidas-de-login)

### RF-003: Recuperacao de Senha
- **Descricao:** O sistema deve possibilitar a recuperacao de acesso esquecido atraves do envio de e-mail contendo link seguro e temporario para redefinicao de senha.
- **Release / Prioridade:** Release 1 (MVP) | Must Have
- **Atores:** Usuario Cadastrado
- **Entradas:** E-mail cadastrado.
- **Saidas:** E-mail com token temporario de redefinicao e tela de cadastramento da nova senha.

### RF-004: Gerenciamento de Perfil e Conta
- **Descricao:** O usuario autenticado podera atualizar seus dados cadastrais (nome e e-mail, com nova verificacao caso o e-mail seja alterado), alterar sua senha atual e excluir permanentemente sua conta mediante confirmacao de senha.
- **Release / Prioridade:** Release 1 (MVP) | Must Have
- **Atores:** Usuario Autenticado
- **Regras de Negocio:** [RN-001](regras-de-negocio.md#rn-001-unicidade-de-credenciais-de-usuario)

---

## 2. Gestao de Produtos (CRUD Completo)

### RF-005: Cadastro de Produto para Monitoramento
- **Descricao:** O usuario autenticado podera cadastrar uma URL de produto de loja suportada, definir o Preco Alvo (R$), atribuir um apelido opcional, selecionar uma categoria e marcar a opcao (checkbox) de receber alerta caso o produto volte ao estoque.
- **Release / Prioridade:** Release 1 (MVP) | Must Have
- **Atores:** Usuario Autenticado
- **Entradas:** URL do produto, Preco Alvo (R$), apelido (opcional), categoria e checkbox *"Avisar tambem quando voltar ao estoque"*.
- **Saidas:** Registro imediato do produto com status `"Pendente / Coletando dados..."` e disparo assincrono do Job de coleta inicial em segundo plano para preenchimento automatico de titulo, imagem, preco a vista, preco a prazo e estoque.
- **Regras de Negocio:** [RN-003](regras-de-negocio.md#rn-003-validacao-de-dominios-suportados), [RN-004](regras-de-negocio.md#rn-004-unicidade-de-url-por-usuario), [RN-010](regras-de-negocio.md#rn-010-limite-de-produtos-por-usuario)

### RF-006: Listagem e Consulta de Produtos
- **Descricao:** A interface SPA (Inertia/Vue) deve exibir a lista de itens monitorados pelo usuario com filtro por categoria e status, informando imagem, titulo/apelido, categoria, preco atual a vista (PIX), preco parcelado (quando disponivel), preco alvo, status de estoque e data/hora da ultima checagem.
- **Release / Prioridade:** Release 1 (MVP) | Must Have
- **Atores:** Usuario Autenticado

### RF-007: Edicao de Parametros do Produto
- **Descricao:** O usuario podera alterar o preco alvo definido, o apelido, a categoria atribuida e a opcao de alerta de retorno ao estoque sem perder o historico de precos existente.
- **Release / Prioridade:** Release 1 (MVP) | Must Have
- **Atores:** Usuario Autenticado

### RF-008: Pausar e Retomar Monitoramento
- **Descricao:** O usuario podera desativar temporariamente a checagem automatica de um produto (`paused`) e reativa-la (`active`) quando desejar diretamente na listagem ou tela do produto.
- **Release / Prioridade:** Release 1 (MVP) | Must Have
- **Atores:** Usuario Autenticado

### RF-009: Exclusao de Produto Monitorado
- **Descricao:** O usuario podera remover definitivamente um produto e seu respectivo historico de sua lista de monitoramento apos confirmacao em modal.
- **Release / Prioridade:** Release 1 (MVP) | Must Have
- **Atores:** Usuario Autenticado

---

## 3. Coleta & Monitoramento (Scraping)

### RF-010: Coleta Automatica Periodica e Sob Demanda
- **Descricao:** O sistema deve executar tarefas agendadas em segundo plano (*Laravel Scheduler / Queues*) para rastrear os precos dos produtos ativos a cada **60 minutos** (configuravel via `.env`), alem de oferecer um botao **"Verificar agora"** no card do produto para coleta manual sob demanda (limitado a 1 solicitacao a cada 10 minutos por produto).
- **Release / Prioridade:** Release 1 (MVP) | Must Have
- **Atores:** Sistema (Scheduler/Queue Worker) e Usuario Autenticado
- **Regras de Negocio:** [RN-005](regras-de-negocio.md#rn-005-intervalo-minimo-de-coleta-por-dominio)

### RF-011: Extracao de Preco a Vista, a Prazo e Disponibilidade
- **Descricao:** O coletor HTTP (`GuzzleHTTP + Symfony DomCrawler`) deve analisar o HTML e metadados `JSON-LD` da pagina do produto para extrair o **menor preco disponivel a vista/PIX (`current_price`)**, o **preco a prazo/cartao (`installment_price`, quando exibido pela loja)**, o titulo, a imagem, a moeda e o status de disponibilidade em estoque.
- **Release / Prioridade:** Release 1 (MVP) | Must Have
- **Atores:** Sistema

### RF-012: Politica de Retentativas em Falha
- **Descricao:** Em caso de indisponibilidade momentanea ou timeout da loja, o sistema deve executar retentativas controladas na fila com intervalo exponencial (*exponential backoff*).
- **Release / Prioridade:** Release 1 (MVP) | Must Have
- **Atores:** Sistema
- **Regras de Negocio:** [RN-006](regras-de-negocio.md#rn-006-politica-de-retentativas-em-falha)

---

## 4. Historico & Analise de Precos

### RF-013: Armazenamento do Historico de Precos
- **Descricao:** O sistema deve registrar na tabela de serie temporal (`price_histories`) cada variacao identificada, mantendo data/hora, preco a vista (`price`), preco a prazo (`installment_price`) e status de estoque.
- **Release / Prioridade:** Release 1 (MVP) | Must Have
- **Atores:** Sistema
- **Regras de Negocio:** [RN-007](regras-de-negocio.md#rn-007-descarte-de-leituras-duplicadas-sem-variacao)

### RF-014: Visualizacao Grafica da Evolucao de Precos
- **Descricao:** O sistema deve renderizar um grafico interativo na pagina de detalhes do produto em Vue 3 ilustrando a oscilacao dos valores ao longo dos ultimos **7, 30 ou 90 dias**.
- **Release / Prioridade:** Release 1 (MVP) | Must Have
- **Atores:** Usuario Autenticado

### RF-015: Destaque de Menor Preco Historico
- **Descricao:** A interface deve exibir de forma clara qual foi o menor preco ja detectado desde o inicio do rastreamento do produto e o percentual de desconto atual em relacao a media do periodo.
- **Release / Prioridade:** Release 1 (MVP) | Must Have
- **Atores:** Usuario Autenticado

---

## 5. Notificacoes & Alertas

### RF-016: Disparo de Notificacao por E-mail e Central de Alertas
- **Descricao:** Quando o preco a vista (`current_price`) coletado for menor ou igual ao preco alvo definido pelo usuario e o produto estiver em estoque, o sistema deve despachar um e-mail formatado com link direto para a loja e registrar o evento na **Central de Alertas** visivel na interface web.
- **Release / Prioridade:** Release 1 (MVP) | Must Have
- **Atores:** Sistema
- **Regras de Negocio:** [RN-008](regras-de-negocio.md#rn-008-condicao-de-disparo-de-alerta), [RN-009](regras-de-negocio.md#rn-009-evitar-notificacoes-repetitivas-cooldown)

### RF-017: Alerta de Retorno ao Estoque
- **Descricao:** Caso o usuario tenha habilitado a opcao *"Avisar tambem quando voltar ao estoque"* no produto, o sistema deve enviar uma notificacao por e-mail e registrar na Central de Alertas quando o item passar do status esgotado (`in_stock = false`) para disponivel (`in_stock = true`).
- **Release / Prioridade:** Release 1 (MVP) | Should Have
- **Atores:** Sistema
- **Regras de Negocio:** [RN-009](regras-de-negocio.md#rn-009-evitar-notificacoes-repetitivas-cooldown)
