---
name: github-flow
description: >-
  Use this skill whenever starting, executing, or completing the implementation of a GitHub Issue
  in this repository, enforcing the project's end-to-end GitHub Flow lifecycle (branch hygiene,
  implementation, local verification, Conventional Commits, Pull Request publication, and branch cleanup)
  with strict zero-emoji compliance.
---

# GitHub Flow - Issue-Driven Development Lifecycle

Esta skill padroniza o fluxo ponta a ponta de implementacao de Issues no repositorio **Monitoramento de Precos**, integrando as skills [`github-issue`](../github-issue/SKILL.md) e [`github-pr`](../github-pr/SKILL.md) com as regras de [`.agents/rules/agentrules.md`](../../rules/agentrules.md).

## 1. Regras Inegociaveis do Fluxo

1. **Proibicao Estrita de Emojis:** Nenhum emoji deve ser utilizado em nomes de branches, mensagens de commit, codigo, testes, documentacao, Issues, Pull Requests ou comunicacao no chat.
2. **Proibicao de Commits Diretos na `main`:** Toda alteracao deve ocorrer em uma branch dedicada criada a partir da `main` atualizada e vinculada a uma Issue existente.
3. **Escopo Atomico por Issue:** Cada branch e Pull Request deve resolver exatamente a Issue alvo, sem misturar escopos nao relacionados.

---

## 2. Passo a Passo Operacional

### Etapa 1: Sincronizacao da `main` e Limpeza de Branches Ja Mescladas
Antes de iniciar qualquer nova Issue:
1. Buscar atualizacoes do remoto e posicionar na branch `main`:
   ```powershell
   git fetch origin --prune
   git checkout main
   git merge --ff-only origin/main
   ```
2. Caso a branch da tarefa anterior ja tenha tido seu Pull Request mesclado na `main`, remover a branch local e remota obsoleta:
   ```powershell
   git branch -d <branch-anterior>
   git push origin --delete <branch-anterior>
   ```

### Etapa 2: Criacao da Branch da Issue
Criar uma branch descritiva a partir da `main` seguindo o prefixo do tipo da Issue (`feat/`, `fix/`, `docs/`, `ci/`, `chore/`, `refactor/`, `test/`):
```powershell
git checkout -b <tipo>/issue-<numero>-<slug-curto>
```
Exemplos:
- `ci/issue-7-pipeline-and-mailpit`
- `feat/issue-8-auth-mvp-pt-br`
- `feat/issue-9-product-registration`

### Etapa 3: Implementacao Orientada a BDD e Camadas
1. Ler os **Criterios de Aceite (BDD)** e o **Checklist de Tarefas Tecnicas** da Issue alvo.
2. Implementar respeitando a separacao de responsabilidades do Laravel 13 / Inertia v3 / Vue 3:
   - Migrations e Models (`src/database/`, `src/app/Models/`)
   - Services, Actions, Jobs e Enums (`src/app/`)
   - FormRequests, Controllers e Policies (`src/app/Http/`)
   - Paginas e Componentes Vue 3 (`src/resources/js/`)
   - Testes automatizados em Pest (`src/tests/`) cobrindo cada cenario BDD (`Dado / Quando / Entao`).

### Etapa 4: Portao de Qualidade Local (Pre-Commit / Pre-PR)
Antes de realizar o commit final ou abrir o Pull Request, executar obrigatoriamente as verificacoes correspondentes aos arquivos alterados:

1. **Documentacao (se houve alteracao em `docs/` ou `mkdocs.yml`):**
   ```powershell
   .\.venv\Scripts\mkdocs build --strict
   ```
2. **Backend Laravel (dentro de `src/`):**
   ```powershell
   php vendor/bin/pint --test
   php vendor/bin/phpstan analyse --memory-limit=2G
   php artisan test
   ```
3. **Frontend Vue 3 / TypeScript (dentro de `src/`):**
   ```powershell
   npm run check
   npm run types:check
   npm run build
   ```

### Etapa 5: Commits Padronizados (Conventional Commits)
Registrar as alteracoes em commits atomicos, em caixa baixa, sem ponto final e sem emojis:
```powershell
git add <arquivos>
git commit -m "<tipo>(<escopo>): <descricao concisa em caixa baixa>"
```

### Etapa 6: Push e Abertura do Pull Request
1. Enviar a branch para o repositorio remoto:
   ```powershell
   git push -u origin <nome-da-branch>
   ```
2. Seguir a skill [`github-pr`](../github-pr/SKILL.md) e o template [`pr_template.md`](../github-pr/resources/pr_template.md), incluindo obrigatoriamente:
   - `Closes #<numero-da-issue>` na secao **Issue Vinculada**.
   - Os cenarios BDD testados e o checklist de qualidade preenchido.
