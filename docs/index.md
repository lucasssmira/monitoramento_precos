# Sistema de Monitoramento de Precos

Bem-vindo a documentacao oficial do projeto **Monitoramento de Precos**. Este espaco centraliza os artefatos de engenharia de software, especificacoes de requisitos, arquitetura e decisoes tecnicas da solucao.

---

## Objetivo do Projeto

O sistema tem como finalidade rastrear, comparar e analisar variacoes historicas de precos de produtos em diferentes plataformas de e-commerce, alertando usuarios sobre quedas de preco e identificando promocoes reais.

```mermaid
graph LR
    A[E-commerces & Lojas] -->|Scraping / APIs| B(Coletor de Precos)
    B --> C[(Base Historica)]
    C --> D[Motor de Analise]
    D -->|Deteccao de Queda| E[Notificacoes / Alertas]
    D -->|Dashboards| F[Painel do Usuario]
```

---

## Estrutura da Documentacao

A documentacao esta organizada nas seguintes secoes principais:

=== "Requisitos do Sistema"
    - [**Visao Geral de Requisitos**](requisitos/index.md): Metodologia, priorizacao MoSCoW e convencoes de nomenclatura.
    - [**Story Map**](requisitos/story-map.md): Mapa de historias e planejamento incremental (MVP e releases futuras).
    - [**Requisitos Funcionais (RF)**](requisitos/funcionais.md): Funcionalidades e comportamentos esperados pelo sistema.
    - [**Requisitos Nao-Funcionais (RNF)**](requisitos/nao-funcionais.md): Criterios de desempenho, seguranca, disponibilidade e usabilidade.
    - [**Regras de Negocio (RN)**](requisitos/regras-de-negocio.md): Politicas e regras de calculo e validacao do dominio.
    - [**Historias de Usuario (US)**](requisitos/historias-de-usuario.md): Visao agil com criterios de aceitacao (Given-When-Then).
    - [**Matriz de Rastreabilidade**](requisitos/rastreabilidade.md): Cruzamento de requisitos para garantia de cobertura.

=== "Arquitetura"
    - [**Visao Arquitetural**](arquitetura/visao-geral.md): Componentes, diagramas conceituais e visao macro da infraestrutura.
    - [**Pipeline CI/CD (Day 0)**](arquitetura/ci-cd.md): Esteira automatizada de verificacoes de estilo, testes e deploy.

---

## Como Executar esta Documentacao Localmente

Para iniciar o servidor local de documentacao com recarga em tempo real (*live reload*):

```bash
# 1. Criar e ativar o ambiente virtual (recomendado)
python -m venv .venv
.\.venv\Scripts\Activate.ps1   # PowerShell no Windows

# 2. Instalar dependencias
pip install -r requirements.txt

# 3. Executar o servidor local
mkdocs serve
```

Acesse em seu navegador: [http://127.0.0.1:8000](http://127.0.0.1:8000)
