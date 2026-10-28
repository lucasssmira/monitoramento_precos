# Visão Arquitetural

Esta página apresenta a arquitetura de alto nível do sistema de **Monitoramento de Preços**, baseada no ecossistema **Laravel**.

---

## 🏛️ Diagrama Conceitual da Arquitetura

```mermaid
graph TD
    subgraph Client["Camada do Cliente (SPA)"]
        Browser["Navegador Web / Mobile"]
        VueApp["SPA Vue 3 - Composition API & Tailwind"]
    end

    subgraph LaravelApp["Aplicação Laravel (Monolito Moderno)"]
        Routes["Web Routes / Middlewares"]
        InertiaAdapter["Inertia.js Middleware & Adapter"]
        Controllers["Controllers & Actions"]
        AuthModule["Laravel Breeze - Inertia/Vue"]
        Eloquent["Eloquent ORM / Models"]
        Scheduler["Laravel Task Scheduler (Cron)"]
        QueueWorker["Laravel Queue Workers"]
        NotificationService["Laravel Notification System"]
    end

    subgraph Scrapers["Mecanismo de Extração de Dados"]
        HttpScraper["GuzzleHTTP + Symfony DomCrawler"]
        JsScraper["Spatie Browsershot / Headless Chrome"]
    end

    subgraph Persistence["Camada de Dados & Mensageria"]
        DB[("Banco de Dados Relacional - PostgreSQL/MySQL")]
        CacheQueue[("Redis - Cache e Filas")]
    end

    subgraph External["Serviços & Lojas Externas"]
        Ecommerces["Lojas & E-commerces Alvo"]
        MailService["Serviço SMTP / Mailgun / SES"]
        TelegramBot["Bot Telegram API"]
    end

    Browser --> VueApp
    VueApp -->|Inertia Visits / JSON Props| Routes
    Routes --> InertiaAdapter
    InertiaAdapter --> Controllers
    Controllers --> AuthModule
    Controllers --> Eloquent
    Eloquent --> DB

    Scheduler -->|Despacha Jobs em Segundo Plano| QueueWorker
    QueueWorker --> CacheQueue
    QueueWorker --> HttpScraper & JsScraper
    HttpScraper & JsScraper -->|Requisições HTTP / Renderização| Ecommerces
    HttpScraper & JsScraper -->|Persiste Preços e Histórico| DB
    QueueWorker -->|Identifica Queda de Preço| NotificationService

    NotificationService -->|Envia Alerta por E-mail| MailService
    NotificationService -->|Mensagem Instantânea| TelegramBot
```

---

## 🛠️ Proposta de Stack Tecnológica

O sistema adota o ecossistema Laravel como núcleo da aplicação, aproveitando suas convenções, segurança integrada e facilidade de manutenção:

| Camada | Tecnologia Definida | Papel na Solução |
| :--- | :--- | :--- |
| **Ambiente de Desenvolvimento** | **Laravel Herd** | Ambiente local de alta performance com gerenciamento integrado de PHP, Nginx, Node.js e DNS local sem dependência de Docker pesado. |
| **Backend & Framework** | **Laravel 13.x** (PHP 8.3+) | Núcleo da aplicação: roteamento, controle de acesso, ORM Eloquent, filas assíncronas e agendamento de tarefas. |
| **Camada de Apresentação (Frontend)** | **Inertia.js + Vue 3** *(Composition API / `<script setup>`)* + **Tailwind CSS** | Experiência fluida de Single Page Application (SPA) sem a complexidade de gerenciar uma API REST/GraphQL separada. Componentes reativos para gráficos, formulários e listagens dinâmicas. |
| **Autenticação** | **Laravel Breeze** *(Stack Inertia/Vue)* | Scaffold oficial e seguro com telas em Vue 3 para login, registro, recuperação de senha e proteção CSRF integrada. |
| **Banco de Dados** | **PostgreSQL** *(ou MySQL / SQLite via Herd)* | Armazenamento relacional dos usuários, produtos monitorados e série temporal do histórico de preços. |
| **Filas & Cache** | **Redis** *(ou driver Database)* | Gerenciamento assíncrono das tarefas de monitoramento e disparos de notificações em segundo plano sem travar a interface. |
| **Extração de Dados (Scraping)** | **GuzzleHTTP + Symfony DomCrawler** | Requisições HTTP ultrarrápidas e parsing de HTML para e-commerces com renderização tradicional. |
| **Renderização Avançada (Opcional)** | **Spatie Browsershot** | Coleta headless baseada em Chromium para lojas que exigem renderização via JavaScript. |
| **Sistema de Notificações** | **Laravel Notifications** | Canal unificado para envio de e-mails (SMTP) e mensagens via Telegram (`laravel-notification-channels/telegram`). |

---

## ⚙️ Principais Módulos do Sistema

1. **Módulo de Autenticação & Contas:**
   - Gerencia credenciais, sessões seguras e perfis de usuários com suas respectivas configurações de contato.
2. **Módulo de Produtos (CRUD):**
   - Responsável pelo cadastro, edição, desativação temporária e exclusão dos produtos acompanhados.
3. **Módulo de Coleta & Agendamento (*Scraping Engine*):**
   - Executa rotinas em segundo plano através do `schedule:run` e filas do Laravel, distribuindo as checagens de preços em lotes.
4. **Módulo de Análise & Alertas:**
   - Compara o valor obtido na coleta contra o preço-alvo estipulado pelo usuário e aciona a camada de notificações quando uma oportunidade é detectada.
