---
name: github-pr
description: >-
  Use this skill whenever creating, drafting, formatting, or automating the publication of Pull Requests (PRs)
  for this project, enforcing branch standards, Conventional Commits, BDD verification checklists,
  and zero-emoji compliance.
---

# GitHub Pull Request Workflow

Esta skill define o procedimento padronizado para confeccao e publicacao de Pull Requests no repositorio GitHub do projeto Monitoramento de Precos.

## 1. Diretrizes Obrigatorias

1. Proibicao Estrita de Emojis: Sob nenhuma circunstancia utilize emojis no titulo do PR, descricao, commits ou revisoes de codigo.
2. Convencao de Commits (Conventional Commits): O titulo do PR deve seguir rigorosamente o formato:
   - `feat(escopo): descricao em caixa baixa sem ponto final`
   - `fix(escopo): descricao em caixa baixa sem ponto final`
   - `docs(escopo): descricao em caixa baixa sem ponto final`
   - `test(escopo): descricao em caixa baixa sem ponto final`
   - `refactor(escopo): descricao em caixa baixa sem ponto final`
   - `chore(escopo): descricao em caixa baixa sem ponto final`
3. Vinculacao com Issue: Todo PR deve referenciar a Issue que resolve (ex: `Closes #12`).
4. Criterios de Aceite Testados: Declarar expressamente os cenarios BDD validados durante o ciclo de desenvolvimento.

## 2. Estrutura Padrao do Pull Request

O corpo do PR deve seguir o modelo disponibilizado em [pr_template.md](./resources/pr_template.md):
- Tipo de Alteracao (selecao unaria ou multipla)
- Descricao Tecnica detalhada
- Lista de Arquivos Afetados
- Issue Vinculada (`Closes #X` ou `Fixes #X`)
- Criterios de Aceite Testados (cenarios BDD)
- Checklist de Qualidade

## 3. Procedimento Operacional

### Passo 1: Inspecao de Branch e Diff
1. Verificar a branch atual: nunca abrir PR diretamente de `main` para `main`.
2. Verificar o status do git e arquivos modificados:
   ```powershell
   git status
   git diff --stat origin/main
   ```

### Passo 2: Verificacao de Testes e Build
1. Assegurar que os testes automatizados foram executados e passaram localmente sem erros.
2. Confirmar que a documentacao compila sem avisos (`mkdocs build --strict`).

### Passo 3: Envio da Branch para o Repositorio Remoto
```powershell
git push -u origin [nome-da-branch]
```

### Passo 4: Criacao do Pull Request no GitHub

Se o GitHub CLI (`gh`) estiver disponivel:
```powershell
gh pr create --title "[tipo(escopo)]: [descricao]" --body-file pr_body.md --base main
```

Caso o GitHub CLI nao esteja disponivel no terminal:
1. Formatar o conteudo completo do PR e disponibilizar para o usuario.
2. Fornecer o link direto do GitHub para abertura do PR comparando a branch criada com a branch `main`.
