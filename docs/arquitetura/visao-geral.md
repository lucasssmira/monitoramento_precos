# Visao Arquitetural

Esta pagina apresenta a arquitetura de alto nivel do sistema de **Monitoramento de Precos**, baseada no ecossistema **Laravel 13**, **Inertia.js v3**, **Vue 3** e infraestrutura containerizada via **Docker Compose** (com suporte hibrido ao **Laravel Herd**).

---

## Diagrama Conceitual da Arquitetura

```mermaid
graph TD
    subgraph Client["Camada do Cliente (SPA pt-BR)"]
        Browser["Navegador Web / Mobile"]
        VueApp["SPA Vue 3 - Composition API & Tailwind CSS"]
    end

    subgraph LaravelApp["Aplicacao Laravel 13 (Monolito Moderno)"]
        Routes["Web Routes / Middlewares"]
        InertiaAdapter["Inertia.js v3 Middleware & Adapter"]
        Controllers["Controllers, FormRequests & Actions"]
        AuthModule["Laravel Fortify (Login, Registro e Verificacao de E-mail)"]
        Eloquent["Eloquent ORM / Models"]
        Scheduler["Laravel Task Scheduler (Cron - 60 min)"]
        QueueWorker["Laravel Queue Workers"]
        NotificationService["Laravel Notification System"]
    end

    subgraph Scrapers["Mecanismo de Extracao de Dados (HTTP Leve)"]
        HttpScraper["GuzzleHTTP + Symfony DomCrawler (HTML / JSON-LD)"]
    end

    subgraph Persistence["Camada de Dados & Mensageria"]
        DB[("PostgreSQL 16 - Usuarios, Produtos, Historico e Alertas")]
        CacheQueue[("Redis 7 - Cache, Rate Limit e Filas")]
    end

    subgraph External["Servicos & Lojas Externas"]
        Ecommerces["E-commerces Homologados (Amazon, Kabum, Mercado Livre, Magalu, Pichau)"]
        MailService["Servico de E-mail SMTP / Mailpit (Dev)"]
    end

    Browser --> VueApp
    VueApp -->|Inertia Visits / JSON Props| Routes
    Routes --> InertiaAdapter
    InertiaAdapter --> Controllers
    Controllers --> AuthModule
    Controllers --> Eloquent
    Eloquent --> DB

    Scheduler -->|Despacha Jobs em Segundo Plano| QueueWorker
    Controllers -->|Atualizacao Manual Sob Demanda| QueueWorker
    QueueWorker --> CacheQueue
    QueueWorker --> HttpScraper
    HttpScraper -->|Requisicoes HTTP com Rate Limit 3s/dominio| Ecommerces
    HttpScraper -->|Persiste Preco a Vista, Prazo e Historico| DB
    QueueWorker -->|Avalia Queda de Preco e Retorno ao Estoque| NotificationService

    NotificationService -->|Envia E-mail de Alerta e Verificacao| MailService
```

---

## Proposta de Stack Tecnologica

O sistema adota o ecossistema Laravel 13 como nucleo da aplicacao, aproveitando suas convencoes, seguranca integrada e facilidade de manutencao:

| Camada | Tecnologia Definida | Papel na Solucao |
| :--- | :--- | :--- |
| **Infraestrutura & Ambiente Local** | **Docker Compose** *(PHP 8.3, PostgreSQL 16, Redis 7, Mailpit)* / **Laravel Herd** | Ambiente containerizado padronizado e reprodutivel, mantendo compatibilidade total com execucao local via Laravel Herd. |
| **Backend & Framework** | **Laravel 13.x** (PHP 8.3+) | Nucleo da aplicacao: roteamento, controle de acesso, ORM Eloquent, filas assincronas e agendamento de tarefas, configurado integralmente em `pt_BR`. |
| **Camada de Apresentacao (Frontend)** | **Inertia.js v3 + Vue 3** *(Composition API / `<script setup>` / TypeScript)* + **Tailwind CSS v4** | Experiencia fluida de Single Page Application (SPA) sem a complexidade de gerenciar uma API REST separada. Componentes reativos para graficos de historico, formularios e listagens por categoria. |
| **Autenticacao** | **Laravel Fortify** *(Starter Kit Enxuto - Estilo Breeze)* | Autenticacao enxuta e segura com telas em Vue 3 (`pt_BR`) para login, registro, verificacao obrigatoria de e-mail (`MustVerifyEmail`), recuperacao de senha e gerenciamento de perfil, sem complexidade de 2FA ou Passkeys. |
| **Banco de Dados** | **PostgreSQL 16** *(SQLite em memoria para testes)* | Armazenamento relacional dos usuarios, produtos monitorados, serie temporal do historico de precos (a vista e a prazo) e logs de alertas enviados. |
| **Filas, Cache & Rate Limiting** | **Redis 7** | Gerenciamento assincrono das tarefas de coleta de precos, controle de intervalo minimo por dominio (`RN-005`) e disparos de notificacoes em segundo plano. |
| **Extracao de Dados (Scraping)** | **GuzzleHTTP + Symfony DomCrawler** | Requisicoes HTTP leves e parsing de HTML / metadados estruturados (`JSON-LD`) para os 5 e-commerces homologados no sistema. |
| **Sistema de Notificacoes** | **Laravel Notifications (SMTP / Mailpit)** | Envio assincrono de e-mails de verificacao de conta, redefinicao de senha, alertas de queda de preco e alertas de retorno ao estoque, além de registro na Central de Alertas da interface web. |

---

## Principais Modulos do Sistema

1. **Modulo de Autenticacao & Contas:**
   - Gerencia credenciais (e-mail e senha), verificacao obrigatoria de e-mail no cadastro, sessoes seguras, recuperacao de senha e edicao de perfil em `pt_BR`.
2. **Modulo de Produtos (CRUD Completo):**
   - Responsavel pelo cadastro (com higienizacao de URL e validacao de dominio), categorizacao, configuracao de alerta de retorno ao estoque, edicao de parametros, pausa/retomada, atualizacao manual sob demanda e exclusao dos produtos acompanhados.
3. **Modulo de Coleta & Agendamento (*Scraping Engine*):**
   - Executa rotinas em segundo plano a cada 60 minutos atraves do `schedule:run` e filas do Laravel no Redis, extraindo preco a vista/PIX, preco parcelado e disponibilidade em estoque com retentativas exponenciais.
4. **Modulo de Historico, Analise & Alertas:**
   - Registra variacoes reais de preco na serie temporal, apresenta graficos evolutivos de 7, 30 e 90 dias com destaque de menor preco historico e aciona notificacoes por e-mail respeitando a politica de *cooldown* de 6 horas.
