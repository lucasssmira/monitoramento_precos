# Sistema de Monitoramento de Precos

Plataforma web para rastreamento automatizado, analise de historico e disparo de alertas inteligentes de precos para os principais e-commerces brasileiros (**Amazon Brasil, KaBuM!, Mercado Livre, Magazine Luiza e Pichau**), integralmente localizada em Portugues do Brasil (`pt_BR`).

---

## Visao Geral e Funcionalidades (MVP Release 1)

- **Autenticacao e Gestao de Conta (`RF-001` a `RF-004`):** Cadastro com verificacao obrigatoria de e-mail (`MustVerifyEmail`), login protegido por *Rate Limiting* (`RN-002`), recuperacao de senha e gerenciamento de perfil/exclusao de conta.
- **Gestao Completa de Produtos (`RF-005` a `RF-009`):** Cadastro via URL com higienizacao de parametros de rastreamento (`RN-004`), validacao de dominios homologados (`RN-003`), limite configuravel de 20 produtos por usuario (`RN-010`), categorizacao, edicao de Preco Alvo, pausa/retomada e exclusao.
- **Motor de Coleta e Agendamento (`RF-010` a `RF-012`):** Extracao HTTP leve (`GuzzleHTTP + Symfony DomCrawler`) de preco a vista (PIX), preco parcelado e disponibilidade em estoque, executada periodicamente via *Laravel Scheduler* (a cada 60 min) ou manualmente sob demanda (*"Verificar agora"* com *cooldown* de 10 min), respeitando rate limit de 3s por dominio (`RN-005`) e 3 retentativas com recuo exponencial (`RN-006`).
- **Historico e Analise Grafica (`RF-013` a `RF-015`):** Armazenamento otimizado apenas quando ha variacao real de preco ou estoque (`RN-007`), destaque de Menor Preco Historico, calculo de percentual de economia sobre a media e grafico vetorial interativo para janelas de **7, 30 e 90 dias**.
- **Notificacoes e Central de Alertas (`RF-016` e `RF-017`):** Disparo automatico de e-mail e registro na Central de Alertas web quando o preco a vista (PIX) atinge ou fica abaixo do Preco Alvo (`RN-008`) ou quando um produto esgotado retorna ao estoque, com politica de silencio (*cooldown*) de 6 horas (`RN-009`).
- **Design System Nocturne Emerald & Warm Amber:** Interface SPA em modo escuro exclusivo (*Dark-First*), tipografia com numerais tabulares (`Plus Jakarta Sans` + `JetBrains Mono`), barra superior unificada, modal rapido de cadastro e *slide-over* da Central de Alertas.

---

## Stack Tecnologica

| Camada | Tecnologia |
| :--- | :--- |
| **Backend** | PHP 8.3+ / Laravel 13.x / Laravel Fortify |
| **Frontend (SPA)** | Vue 3 (Composition API + TypeScript) / Inertia.js v3 / Tailwind CSS v4 |
| **Banco de Dados & Filas** | PostgreSQL 16 (SQLite em testes) / Redis 7 |
| **Scraping Engine** | GuzzleHTTP + Symfony DomCrawler (HTML & JSON-LD) |
| **Qualidade & Testes** | Pest PHP, Laravel Pint, PHPStan (Larastan), Vite+ (`vp check` / `vue-tsc`) |
| **Documentacao** | MkDocs Material (`pt-BR`) |
| **Infraestrutura Local** | Docker Compose (`app`, `postgres`, `redis`, `mailpit`) / Laravel Herd |

---

## Como Executar a Aplicacao (`src/`)

### Opcao 1: Ambiente Containerizado (Docker Compose)

```powershell
docker compose up -d --build
docker compose exec app php artisan migrate
```

### Opcao 2: Ambiente Local (PHP / Node.js / Laravel Herd)

```powershell
cd src
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run build
php artisan serve
```

Para processar a fila de coleta e o agendador localmente:

```powershell
php artisan queue:work
php artisan schedule:work
```

---

## Verificacao de Qualidade e Testes

Dentro do diretorio `src/`:

```powershell
# Padrao de codigo PHP e Analise Estatica
php vendor/bin/pint --test
php vendor/bin/phpstan analyse --memory-limit=2G

# Suite de testes automatizados (Pest)
php artisan test

# Formatacao, Lint, Tipagem e Build do Frontend (Vue 3 / TypeScript)
npm run check
npm run types:check
npm run build
```

---

## Documentacao do Projeto (MkDocs Material)

A documentacao viva do sistema esta estruturada na pasta [`docs/`](docs/) e publicada via GitHub Pages.

### Como Rodar a Documentacao Localmente

1. **Ativar o Ambiente Virtual:**
   ```powershell
   .\.venv\Scripts\Activate.ps1
   ```
2. **Iniciar o Servidor com Live Reload:**
   ```powershell
   .\.venv\Scripts\mkdocs serve
   ```
3. **Validar Build Estrito:**
   ```powershell
   .\.venv\Scripts\mkdocs build --strict
   ```
4. **Acessar no Navegador:** [http://127.0.0.1:8000](http://127.0.0.1:8000)

---

## Estrutura de Artefatos da Documentacao

- [`mkdocs.yml`](mkdocs.yml): Configuracao visual, extensoes Mermaid, abas e navegacao da documentacao.
- [`docs/index.md`](docs/index.md): Visao geral do projeto e navegacao estruturada.
- [`docs/requisitos/index.md`](docs/requisitos/index.md): Convencoes de nomenclatura, metodo MoSCoW e ciclo de vida.
- [`docs/requisitos/story-map.md`](docs/requisitos/story-map.md): Mapeamento de historias e escopo consolidado da Release 1 (MVP Completo).
- [`docs/requisitos/funcionais.md`](docs/requisitos/funcionais.md): Especificacao dos Requisitos Funcionais (`RF-001` a `RF-017`).
- [`docs/requisitos/nao-funcionais.md`](docs/requisitos/nao-funcionais.md): Requisitos Nao-Funcionais categorizados por ISO 25010 com metricas quantificaveis.
- [`docs/requisitos/regras-de-negocio.md`](docs/requisitos/regras-de-negocio.md): Politicas e regras de dominio (`RN-001` a `RN-010`).
- [`docs/requisitos/historias-de-usuario.md`](docs/requisitos/historias-de-usuario.md): Historias de Usuario (`US-001` a `US-005`) com criterios de aceitacao em BDD (Gherkin).
- [`docs/requisitos/rastreabilidade.md`](docs/requisitos/rastreabilidade.md): Matriz cruzada integrando US, RF, RNF e RN.
- [`docs/arquitetura/visao-geral.md`](docs/arquitetura/visao-geral.md): Diagrama conceitual de arquitetura em Mermaid e stack tecnologica.
- [`docs/arquitetura/identidade-visual.md`](docs/arquitetura/identidade-visual.md): Especificacao da Identidade Visual e Design System (*Nocturne Emerald & Warm Amber*).
- [`docs/arquitetura/ci-cd.md`](docs/arquitetura/ci-cd.md): Especificacao da esteira de Integracao e Entrega Continua (CI/CD Day 0).
