# Regras de Negócio (RN)

As Regras de Negócio governam as políticas, restrições e lógicas fundamentais do domínio de **Monitoramento de Preços**.

---

## 📋 Tabela Resumo das Regras de Negócio

| ID | Nome da Regra | Impacto Principal |
| :--- | :--- | :--- |
| [`RN-001`](#rn-001-unicidade-de-credenciais-de-usuario) | Unicidade de Credenciais de Usuário | Autenticação (`RF-001`) |
| [`RN-002`](#rn-002-bloqueio-por-tentativas-invalidas-de-login) | Rate Limit de Tentativas de Login | Autenticação (`RF-002`) |
| [`RN-003`](#rn-003-validacao-de-dominios-suportados) | Validação de Domínios Suportados | Cadastro de Produtos (`RF-005`) |
| [`RN-004`](#rn-004-unicidade-de-url-por-usuario) | Unicidade de URL por Usuário | Cadastro de Produtos (`RF-005`) |
| [`RN-005`](#rn-005-intervalo-minimo-de-coleta-por-dominio) | Intervalo Mínimo de Coleta por Domínio | Agendador de Coleta (`RF-010`) |
| [`RN-006`](#rn-006-politica-de-retentativas-em-falha) | Política de Retentativas em Falha | Coleta de Preços (`RF-012`) |
| [`RN-007`](#rn-007-descarte-de-leituras-duplicadas-sem-variacao) | Otimização de Histórico por Variação | Armazenamento (`RF-013`) |
| [`RN-008`](#rn-008-condicao-de-disparo-de-alerta) | Condição de Disparo de Alerta | Notificações (`RF-016`, `RF-017`) |
| [`RN-009`](#rn-009-evitar-notificacoes-repetitivas-cooldown) | Período de Silêncio (*Cooldown*) de Alertas | Notificações (`RF-016`, `RF-017`) |

---

## 📌 Especificação Detalhada

### RN-001: Unicidade de Credenciais de Usuário
- **Descrição:** Cada conta no sistema deve possuir um endereço de e-mail exclusivo.
- **Tratamento:** Tentativas de cadastro com e-mail já existente devem ser rejeitadas com validação amigável sem expor dados de terceiros.

---

### RN-002: Bloqueio por Tentativas Inválidas de Login
- **Descrição:** Para prevenção de ataques de força bruta (*brute force*), o sistema deve bloquear temporariamente requisições de autenticação após 5 tentativas consecutivas incorretas vindas do mesmo IP/usuário.
- **Tratamento:** Bloqueio progressivo de 60 segundos gerenciado pelo mecanismo nativo de *Rate Limiting* do Laravel.

---

### RN-003: Validação de Domínios Suportados
- **Descrição:** Apenas URLs de e-commerces que possuem adaptadores (*parsers*) explicitamente implementados e homologados no sistema podem ser cadastradas.
- **Tratamento:** Caso o usuário submeta uma URL de domínio desconhecido, o sistema deve rejeitar o cadastro com a mensagem: `"O domínio informado ainda não é suportado pelo sistema."`

---

### RN-004: Unicidade de URL por Usuário
- **Descrição:** Um usuário não pode cadastrar a mesma URL de produto mais de uma vez em sua conta.
- **Tratamento:** Parâmetros de rastreamento (como `utm_source`, `ref`, `tag`, etc.) devem ser higienizados antes da validação de duplicidade.

---

### RN-005: Intervalo Mínimo de Coleta por Domínio
- **Descrição:** Para prevenir bloqueios (*IP ban* ou *Cloudflare challenge*) e respeitar a infraestrutura das lojas, requisições para um mesmo domínio devem observar um intervalo mínimo de 3 segundos entre chamadas pelo mesmo worker.
- **Tratamento:** A fila de coleta deve balancear os jobs alternando os domínios (*round-robin* de e-commerces).

---

### RN-006: Política de Retentativas em Falha
- **Descrição:** Se uma coleta falhar por erro de rede ou timeout (código HTTP 5xx ou timeout de conexão):
    - Devem ser executadas no máximo 3 retentativas.
    - O espaçamento entre tentativas deve seguir recuo exponencial ($2^n$ minutos).
    - Erros persistentes após 3 tentativas devem marcar o status do produto temporariamente como `"Instável"` e registrar log operacional.

---

### RN-007: Descarte de Leituras Duplicadas sem Variação
- **Descrição:** Se a coleta registrar exatamente o mesmo preço e disponibilidade da leitura imediatamente anterior, o sistema deve apenas atualizar a data da última checagem (`last_checked_at`), sem criar uma nova linha na série histórica de preços, economizando espaço em disco.

---

### RN-008: Condição de Disparo de Alerta
- **Descrição:** Um alerta é considerado elegível para disparo quando:
    $$\text{Preço Atual} \le \text{Preço Alvo Definido pelo Usuário}$$
- **Condição Adicional:** O produto deve estar com status de estoque `"Disponível"`. Caso o preço tenha baixado porém o item esteja esgotado, a notificação não deve ser disparada.

---

### RN-009: Evitar Notificações Repetitivas (*Cooldown*)
- **Descrição:** Um usuário não deve receber alertas repetidos sobre o mesmo produto para o mesmo patamar de preço em um intervalo inferior a 6 horas, a menos que o preço sofra uma nova redução adicional de pelo menos 5%.
