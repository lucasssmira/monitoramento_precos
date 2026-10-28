# Sistema de Monitoramento de Precos

Repositorio do projeto **Monitoramento de Precos**, contendo a documentacao tecnica, especificacao de requisitos e codigo-fonte da aplicacao.

## Documentacao do Projeto (MkDocs Material)

A documentacao viva do sistema esta estruturada na pasta [`docs/`](docs/) e utiliza **MkDocs** com o tema **Material**.

### Como Rodar a Documentacao Localmente

1. **Ativar o Ambiente Virtual:**
   ```powershell
   .\.venv\Scripts\Activate.ps1
   ```

2. **Iniciar o Servidor com Live Reload:**
   ```powershell
   mkdocs serve
   ```
   *Ou diretamente:*
   ```powershell
   .\.venv\Scripts\mkdocs serve
   ```

3. **Acessar no Navegador:**
   Acesse: [http://127.0.0.1:8000](http://127.0.0.1:8000)

---

## Estrutura de Artefatos Criados

- [`mkdocs.yml`](mkdocs.yml): Configuracao visual, paleta (Dark/Light), extensoes Mermaid, Abas e Navegacao.
- [`docs/index.md`](docs/index.md): Visao geral do projeto e fluxo conceitual.
- [`docs/requisitos/index.md`](docs/requisitos/index.md): Convencoes de nomenclatura, metodo MoSCoW e ciclo de vida.
- [`docs/requisitos/story-map.md`](docs/requisitos/story-map.md): Mapeamento de historias e escopo consolidado da Release 1 (MVP Completo).
- [`docs/requisitos/funcionais.md`](docs/requisitos/funcionais.md): Especificacao dos Requisitos Funcionais (`RF-001` a `RF-017`).
- [`docs/requisitos/nao-funcionais.md`](docs/requisitos/nao-funcionais.md): Requisitos Nao-Funcionais categorizados por ISO 25010 com metricas quantificaveis.
- [`docs/requisitos/regras-de-negocio.md`](docs/requisitos/regras-de-negocio.md): Politicas e regras de dominio (`RN-001` a `RN-010`).
- [`docs/requisitos/historias-de-usuario.md`](docs/requisitos/historias-de-usuario.md): Historias de Usuario (`US-001` a `US-005`) com criterios de aceitacao em BDD (Gherkin).
- [`docs/requisitos/rastreabilidade.md`](docs/requisitos/rastreabilidade.md): Matriz cruzada integrando US, RF, RNF e RN.
- [`docs/arquitetura/visao-geral.md`](docs/arquitetura/visao-geral.md): Diagrama conceitual de arquitetura em Mermaid e stack tecnologica.
- [`docs/arquitetura/ci-cd.md`](docs/arquitetura/ci-cd.md): Especificacao da esteira de Integracao e Entrega Continua (CI/CD Day 0).
