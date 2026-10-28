# Regras de Negocio (RN)

As Regras de Negocio governam as politicas, restricoes e logicas fundamentais do dominio de **Monitoramento de Precos**.

---

## Tabela Resumo das Regras de Negocio

| ID | Nome da Regra | Impacto Principal |
| :--- | :--- | :--- |
| [`RN-001`](#rn-001-unicidade-de-credenciais-de-usuario) | Unicidade de Credenciais de Usuario | Autenticacao (`RF-001`, `RF-004`) |
| [`RN-002`](#rn-002-bloqueio-por-tentativas-invalidas-de-login) | Rate Limit de Tentativas de Login | Autenticacao (`RF-002`) |
| [`RN-003`](#rn-003-validacao-de-dominios-suportados) | Validacao de Dominios Suportados | Cadastro de Produtos (`RF-005`) |
| [`RN-004`](#rn-004-unicidade-de-url-por-usuario) | Unicidade de URL por Usuario | Cadastro de Produtos (`RF-005`) |
| [`RN-005`](#rn-005-intervalo-minimo-de-coleta-por-dominio) | Intervalo Minimo de Coleta por Dominio | Agendador e Coleta Manual (`RF-010`) |
| [`RN-006`](#rn-006-politica-de-retentativas-em-falha) | Politica de Retentativas em Falha | Coleta de Precos (`RF-012`) |
| [`RN-007`](#rn-007-descarte-de-leituras-duplicadas-sem-variacao) | Otimizacao de Historico por Variacao | Armazenamento (`RF-013`) |
| [`RN-008`](#rn-008-condicao-de-disparo-de-alerta) | Condicao de Disparo de Alerta | Notificacoes (`RF-016`) |
| [`RN-009`](#rn-009-evitar-notificacoes-repetitivas-cooldown) | Periodo de Silencio (*Cooldown*) de Alertas | Notificacoes (`RF-016`, `RF-017`) |
| [`RN-010`](#rn-010-limite-de-produtos-por-usuario) | Limite de Produtos Monitorados por Usuario | Cadastro de Produtos (`RF-005`) |

---

## Especificacao Detalhada

### RN-001: Unicidade de Credenciais de Usuario
- **Descricao:** Cada conta no sistema deve possuir um endereco de e-mail exclusivo.
- **Tratamento:** Tentativas de cadastro ou atualizacao de perfil com e-mail ja existente devem ser rejeitadas com a mensagem em Portugues: `"Este e-mail já está em uso."`

---

### RN-002: Bloqueio por Tentativas Invalidas de Login
- **Descricao:** Para prevencao de ataques de forca bruta (*brute force*), o sistema deve bloquear temporariamente requisicoes de autenticacao apos 5 tentativas consecutivas incorretas vindas do mesmo IP/usuario.
- **Tratamento:** Bloqueio de 60 segundos gerenciado pelo mecanismo nativo de *Rate Limiting* do Laravel Fortify (`Limit::perMinute(5)`).

---

### RN-003: Validacao de Dominios Suportados
- **Descricao:** Apenas URLs de e-commerces que possuem adaptadores (*parsers*) explicitamente implementados e homologados no sistema podem ser cadastradas.
- **Dominios Homologados na Release 1:**
    - Amazon Brasil (`amazon.com.br`)
    - Kabum (`kabum.com.br`)
    - Mercado Livre (`mercadolivre.com.br` / `produto.mercadolivre.com.br`)
    - Magazine Luiza (`magazineluiza.com.br` / `magazinevoce.com.br`)
    - Pichau (`pichau.com.br`)
- **Tratamento:** Caso o usuario submeta uma URL de dominio desconhecido, o sistema deve rejeitar o cadastro com a mensagem: `"O domínio informado ainda não é suportado pelo sistema."`

---

### RN-004: Unicidade de URL por Usuario
- **Descricao:** Um usuario nao pode cadastrar a mesma URL de produto mais de uma vez em sua conta.
- **Tratamento:** Parametros de rastreamento (como `utm_source`, `utm_medium`, `utm_campaign`, `ref`, `tag`, `fbclid`, `gclid`) devem ser higienizados antes da validacao de duplicidade.

---

### RN-005: Intervalo Minimo de Coleta por Dominio
- **Descricao:** Para prevenir bloqueios (*IP ban* ou *rate limiting* das lojas) e respeitar a infraestrutura dos e-commerces:
    - Requisicoes para um mesmo dominio devem observar um intervalo minimo de **3 segundos** entre chamadas pelo worker.
    - A coleta automatica periodica ocorre a cada **60 minutos** por produto ativo (configuravel via `.env`).
    - A coleta manual acionada pelo botao **"Verificar agora"** respeita um intervalo minimo de **10 minutos** por produto.
- **Tratamento:** A fila de coleta deve balancear os jobs alternando os dominios (*round-robin* de e-commerces).

---

### RN-006: Politica de Retentativas em Falha
- **Descricao:** Se uma coleta falhar por erro de rede ou timeout (codigo HTTP 5xx ou timeout de conexao):
    - Devem ser executadas no maximo **3 retentativas**.
    - O espacamento entre tentativas deve seguir recuo exponencial ($2^n$ minutos: 2, 4 e 8 minutos).
    - Erros persistentes apos 3 tentativas devem marcar o status do produto temporariamente como `"Instável"` e registrar log operacional estruturado.

---

### RN-007: Descarte de Leituras Duplicadas sem Variacao
- **Descricao:** Se a coleta registrar exatamente o mesmo preco a vista (`current_price`), preco a prazo (`installment_price`) e disponibilidade (`in_stock`) da leitura imediatamente anterior, o sistema deve apenas atualizar a data da ultima checagem (`last_checked_at`), sem criar uma nova linha na tabela `price_histories`.

---

### RN-008: Condicao de Disparo de Alerta
- **Descricao:** O preco utilizado como referencia para comparacao com o Preco Alvo e sempre o **menor preco disponivel (preco a vista / PIX)**. Um alerta de preco e considerado elegivel para disparo quando:
    $$\text{Preco Atual (a Vista / PIX)} \le \text{Preco Alvo Definido pelo Usuario}$$
- **Condicao Adicional:** O produto deve estar com status de estoque `"Disponível"` (`in_stock = true`). Caso o preco tenha baixado porem o item esteja esgotado, a notificacao de queda de preco nao deve ser disparada.

---

### RN-009: Evitar Notificacoes Repetitivas (*Cooldown*)
- **Descricao:** Um usuario nao deve receber alertas repetidos sobre o mesmo produto para o mesmo patamar de preco em um intervalo inferior a **6 horas**, a menos que o preco a vista sofra uma nova reducao adicional de pelo menos **5%** em relacao ao ultimo preco notificado.

---

### RN-010: Limite de Produtos por Usuario
- **Descricao:** Para garantir previsibilidade de recursos na fila de scraping e evitar sobrecarga por uma unica conta, cada usuario pode manter ate **20 produtos** cadastrados simultaneamente (limite padrao configuravel via variavel de ambiente `MAX_PRODUCTS_PER_USER=20`).
- **Tratamento:** Caso o usuario atinja o limite, novas tentativas de cadastro devem ser recusadas informando que o limite maximo de produtos monitorados foi atingido.
