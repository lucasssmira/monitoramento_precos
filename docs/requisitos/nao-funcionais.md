# Requisitos Não-Funcionais (RNF)

Os Requisitos Não-Funcionais definem os atributos de qualidade, restrições tecnológicas e requisitos arquiteturais da solução, estruturados com base na norma **ISO/IEC 25010** e alinhados à stack **Laravel 13**, **Inertia.js**, **Vue 3** e **Laravel Herd**.

---

## 📊 Matriz Resumo dos Requisitos Não-Funcionais

| ID | Categoria (ISO 25010) | Descrição do Requisito | Métrica / Alvo | Prioridade |
| :--- | :--- | :--- | :--- | :---: |
| [`RNF-001`](#rnf-001-desempenho-e-transicoes-spa) | Eficiência de Desempenho | Tempo de resposta e transição de páginas SPA | Transição $\le 300\text{ ms}$ (p95) | Must |
| [`RNF-002`](#rnf-002-ambiente-de-desenvolvimento-herd) | Compatibilidade / Portabilidade | Ambiente local padronizado com Laravel Herd | Compatível com PHP 8.3+, Nginx e Node.js | Must |
| [`RNF-003`](#rnf-003-compatibilidade-e-framework) | Manutenibilidade | Adoção do framework Laravel na versão mais recente | Laravel 13.x | Must |
| [`RNF-004`](#rnf-004-processamento-assincrono-e-filas) | Eficiência de Desempenho | Execução desacoplada de scraping em filas | Bloqueio de UI = 0 ms (100% assíncrono) | Must |
| [`RNF-005`](#rnf-005-resiliencia-de-scraping) | Confiabilidade | Resiliência contra bloqueios e instabilidades de lojas | Taxa de sucesso $\ge 95\%$ | Must |
| [`RNF-006`](#rnf-006-seguranca-e-protecao-de-dados) | Segurança | Proteção de credenciais, CSRF e senhas | Hash Argon2/Bcrypt + Tokens em variáveis de ambiente | Must |
| [`RNF-007`](#rnf-007-interface-responsiva) | Usabilidade | Design responsivo adaptável a múltiplos dispositivos | Suporte fluido de 360px a 1920px (Mobile First) | Should |
| [`RNF-008`](#rnf-008-observabilidade-e-logs) | Manutenibilidade | Rastreamento estruturado de coletas e falhas | Logs estruturados via Laravel Monolog em JSON | Must |

---

## 🔍 Detalhamento por Categoria

### ⚡ 1. Desempenho e Eficiência

#### RNF-001: Desempenho e Transições SPA
- **Declaração:** A interface construída com **Inertia.js e Vue 3** deve realizar transições de tela e carregamento de *props* em tempo inferior a 300 ms no percentil 95 sob rede estável, sem recarregamento completo da página (*full page reload*).
- **Critério de Aceitação:** Auditoria no navegador (Chrome DevTools Performance) comprovando transição de rota fluida sem efeito de tela branca.

#### RNF-004: Processamento Assíncrono e Filas
- **Declaração:** Nenhuma operação de rede externa voltada à coleta de preços (*scraping*) ou disparo de e-mails/Telegram pode ser executada sincronamente dentro do ciclo de vida da requisição HTTP do usuário.
- **Critério de Aceitação:** As ações de cadastro de produtos ou disparo de testes de notificação devem responder à UI em menos de 200 ms, despachando o trabalho pesado para os *Queue Workers* do Laravel.

---

### 💻 2. Compatibilidade e Ambiente

#### RNF-002: Ambiente de Desenvolvimento (Herd)
- **Declaração:** O ambiente de desenvolvimento deve ser gerenciado primariamente através do **Laravel Herd**, garantindo suporte nativo a PHP 8.3+, Nginx, Node.js e gerenciamento de domínios `.test` sem necessidade de virtualização pesada.
- **Critério de Aceitação:** O projeto deve ser clonado e executado através de comandos `herd` / `composer` / `npm` sem erros de compatibilidade de extensões (ex: cURL, OpenSSL, PDO).

#### RNF-003: Compatibilidade e Framework
- **Declaração:** A aplicação deve ser construída na versão estável mais recente do framework: **Laravel 13.x**, adotando as convenções e práticas recomendadas da arquitetura do framework.

---

### 🛡️ 3. Confiabilidade e Robustez

#### RNF-005: Resiliência de Scraping
- **Declaração:** Os adaptadores de extração de dados devem implementar rotação de *User-Agents*, intervalos dinâmicos (*jitter*) e retentativas exponenciais, garantindo que o coletor não seja interrompido por falhas transitórias (erros HTTP 429, 502, 503 ou timeouts).
- **Critério de Aceitação:** Falhas em um e-commerce específico não podem afetar a fila de processamento dos demais e-commerces.

---

### 🔒 4. Segurança

#### RNF-006: Segurança e Proteção de Dados
- **Declaração:** A aplicação deve implementar as seguintes medidas de segurança:
    - Armazenamento de senhas com algoritmo seguro de derivação de chaves (**Bcrypt** ou **Argon2id**).
    - Proteção automática contra ataques de **CSRF** e injeção de SQL via Eloquent ORM.
    - Sanitização rigorosa das URLs de entrada informadas pelo usuário para prevenir ataques de *Server-Side Request Forgery* (SSRF).
    - Armazenamento de credenciais externas (SMTP, Telegram Token) exclusivamente no arquivo `.env`.

---

### 🎨 5. Usabilidade

#### RNF-007: Interface Responsiva
- **Declaração:** A interface em Vue 3 estilizada com **Tailwind CSS** deve ser responsiva (*Mobile-First*), com navegação confortável em smartphones, tablets e telas widescreen.
- **Critério de Aceitação:** Pontuação mínima de 90 no Google Lighthouse (categoria *Acessibilidade* e *Melhores Práticas*).

---

### 🛠️ 6. Manutenibilidade e Observabilidade

#### RNF-008: Observabilidade e Logs
- **Declaração:** Todas as rotinas de coleta periódica devem gerar registros estruturados nos canais de log do Laravel (*Monolog*), contendo:
    - ID do produto
    - URL do e-commerce
    - Tempo total da requisição (ms)
    - Status HTTP retornado
    - Status da extração (Sucesso, Preço Inalterado, Falha Seletor ou Timeout)
