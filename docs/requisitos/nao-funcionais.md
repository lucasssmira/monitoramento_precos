# Requisitos Nao-Funcionais (RNF)

Os Requisitos Nao-Funcionais definem os atributos de qualidade, restricoes tecnologicas e requisitos arquiteturais da solucao, estruturados com base na norma **ISO/IEC 25010** e alinhados a stack **Laravel 13**, **Inertia.js v3**, **Vue 3**, **Docker Compose** e **Laravel Herd**.

---

## Matriz Resumo dos Requisitos Nao-Funcionais

| ID | Categoria (ISO 25010) | Descricao do Requisito | Metrica / Alvo | Prioridade |
| :--- | :--- | :--- | :--- | :---: |
| [`RNF-001`](#rnf-001-desempenho-e-transicoes-spa) | Eficiencia de Desempenho | Tempo de resposta e transicao de paginas SPA | Transicao $\le 300\text{ ms}$ (p95) | Must |
| [`RNF-002`](#rnf-002-ambiente-hibrido-docker-compose-e-laravel-herd) | Compatibilidade / Portabilidade | Ambiente padronizado com Docker Compose e compatibilidade com Laravel Herd | PHP 8.3+, PostgreSQL 16, Redis 7 e Node.js 20 | Must |
| [`RNF-003`](#rnf-003-compatibilidade-framework-e-localizacao-pt_br) | Manutenibilidade / Usabilidade | Adocao do Laravel 13.x com localizacao integral em `pt_BR` | Laravel 13.x + 100% da interface e validacoes em `pt_BR` | Must |
| [`RNF-004`](#rnf-004-processamento-assincrono-e-filas) | Eficiencia de Desempenho | Execucao desacoplada de scraping e e-mails em filas | Bloqueio de UI = 0 ms (100% assincrono) | Must |
| [`RNF-005`](#rnf-005-resiliencia-de-scraping-http) | Confiabilidade | Resiliencia contra bloqueios e instabilidades de lojas via HTTP leve | Taxa de sucesso $\ge 95\%$ sem sobrecarga de Headless Browser | Must |
| [`RNF-006`](#rnf-006-seguranca-e-protecao-de-dados) | Seguranca | Protecao de credenciais, verificacao de e-mail, CSRF e prevencao de SSRF | Hash Bcrypt/Argon2 + Validacao Anti-SSRF | Must |
| [`RNF-007`](#rnf-007-interface-responsiva) | Usabilidade | Design responsivo adaptavel a multiplos dispositivos | Suporte fluido de 360px a 1920px (Mobile First) | Should |
| [`RNF-008`](#rnf-008-observabilidade-e-logs) | Manutenibilidade | Rastreamento estruturado de coletas e falhas | Logs estruturados via Laravel Monolog em JSON | Must |

---

## Detalhamento por Categoria

### 1. Desempenho e Eficiencia

#### RNF-001: Desempenho e Transicoes SPA
- **Declaracao:** A interface construida com **Inertia.js v3 e Vue 3** deve realizar transicoes de tela e carregamento de *props* em tempo inferior a 300 ms no percentil 95 sob rede estavel, sem recarregamento completo da pagina (*full page reload*).
- **Criterio de Aceitacao:** Transicao de rota fluida sem efeito de tela branca.

#### RNF-004: Processamento Assincrono e Filas
- **Declaracao:** Nenhuma operacao de rede externa voltada a coleta de precos (*scraping*) ou disparo de e-mails pode ser executada sincronamente dentro do ciclo de vida da requisicao HTTP do usuario.
- **Criterio de Aceitacao:** As acoes de cadastro de produtos (`RF-005`) ou atualizacao manual (`RF-010`) devem responder a UI em menos de 200 ms, despachando o trabalho de coleta para os *Queue Workers* do Laravel.

---

### 2. Compatibilidade, Ambiente e Localizacao

#### RNF-002: Ambiente Hibrido (Docker Compose e Laravel Herd)
- **Declaracao:** O projeto deve prover orquestracao completa via **Docker Compose** (`app` PHP 8.3, `postgres` 16, `redis` 7 e `mailpit` para testes locais de e-mail), mantendo compatibilidade para execucao nativa no host atraves do **Laravel Herd**.
- **Criterio de Aceitacao:** A aplicacao e a suite de testes devem executar sem erros de extensoes tanto dentro dos containers Docker quanto via Laravel Herd.

#### RNF-003: Compatibilidade, Framework e Localizacao (`pt_BR`)
- **Declaracao:** A aplicacao deve ser construida sobre o **Laravel 13.x**, adotando as convencoes arquiteturais do framework e configurada integralmente no idioma **Portugues do Brasil (`APP_LOCALE=pt_BR`)**, abrangendo todas as telas Vue, e-mails transacionais e mensagens de validacao.

---

### 3. Confiabilidade e Robustez

#### RNF-005: Resiliencia de Scraping HTTP
- **Declaracao:** Os adaptadores de extracao de dados devem operar de forma leve com `GuzzleHTTP + Symfony DomCrawler`, implementando rotacao de *User-Agents*, intervalos minimos por dominio (`RN-005`) e retentativas exponenciais (`RN-006`), garantindo que o coletor nao seja interrompido por falhas transitorias (erros HTTP 429, 502, 503 ou timeouts).
- **Criterio de Aceitacao:** Falhas em um e-commerce especifico nao podem afetar a fila de processamento dos demais e-commerces.

---

### 4. Seguranca

#### RNF-006: Seguranca e Protecao de Dados
- **Declaracao:** A aplicacao deve implementar as seguintes medidas de seguranca:
    - Armazenamento de senhas com algoritmo seguro de derivacao de chaves (**Bcrypt** ou **Argon2id**) e verificacao obrigatoria de e-mail no registro (`MustVerifyEmail`).
    - Autenticacao enxuta baseada em sessao segura com protecao contra **CSRF** e rate limiting de login (`RN-002`), sem exposicao de dados de terceiros.
    - Sanitizacao rigorosa das URLs de entrada informadas pelo usuario para prevenir ataques de *Server-Side Request Forgery* (**SSRF**).
    - Armazenamento de credenciais externas (banco de dados, Redis, SMTP) exclusivamente no arquivo `.env`.

---

### 5. Usabilidade

#### RNF-007: Interface Responsiva
- **Declaracao:** A interface em Vue 3 estilizada com **Tailwind CSS v4** deve ser responsiva (*Mobile-First*), com navegacao confortavel para listagem de produtos, filtros por categoria e visualizacao de graficos em smartphones, tablets e desktops.

---

### 6. Manutenibilidade e Observabilidade

#### RNF-008: Observabilidade e Logs
- **Declaracao:** Todas as rotinas de coleta periodica e sob demanda devem gerar registros estruturados nos canais de log do Laravel (*Monolog*) em formato JSON, contendo:
    - ID do produto
    - URL e dominio do e-commerce
    - Tempo total da requisicao (ms)
    - Status HTTP retornado
    - Status da extracao (Sucesso, Preco Inalterado, Falha Seletor ou Timeout)
