# Sistema de Monitoramento de Preços

Repositório do projeto **Monitoramento de Preços**, contendo a documentação técnica, especificação de requisitos e código-fonte.

## 📖 Documentação do Projeto (MkDocs Material)

A documentação viva do sistema está estruturada na pasta [`docs/`](docs/) e utiliza **MkDocs** com o tema **Material**.

### Como Rodar a Documentação Localmente

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

## 📂 Estrutura de Artefatos Criados

- [`mkdocs.yml`](mkdocs.yml): Configuração visual, paleta (Dark/Light), extensões Mermaid, Abas e Navegação.
- [`docs/index.md`](docs/index.md): Visão geral do projeto e fluxo conceitual.
- [`docs/requisitos/index.md`](docs/requisitos/index.md): Convenções de nomenclatura, método MoSCoW e ciclo de vida.
- [`docs/requisitos/funcionais.md`](docs/requisitos/funcionais.md): Especificação dos Requisitos Funcionais (RF-001 a RF-007) com template.
- [`docs/requisitos/nao-funcionais.md`](docs/requisitos/nao-funcionais.md): Requisitos Não-Funcionais categorizados por ISO 25010 com métricas quantificáveis.
- [`docs/requisitos/regras-de-negocio.md`](docs/requisitos/regras-de-negocio.md): Políticas e regras de domínio (RN-001 a RN-007).
- [`docs/requisitos/historias-de-usuario.md`](docs/requisitos/historias-de-usuario.md): Histórias de Usuário (US-001 a US-003) com critérios de aceitação em BDD (Gherkin).
- [`docs/requisitos/rastreabilidade.md`](docs/requisitos/rastreabilidade.md): Matriz cruzada integrando US ↔ RF ↔ RNF ↔ RN.
- [`docs/arquitetura/visao-geral.md`](docs/arquitetura/visao-geral.md): Diagrama conceitual de arquitetura em Mermaid.
