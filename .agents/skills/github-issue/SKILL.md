---
name: github-issue
description: >-
  Use this skill whenever creating, drafting, formatting, or automating the publication of GitHub Issues
  for this project, enforcing strict BDD acceptance criteria, technical task checklists,
  and zero-emoji compliance.
---

# GitHub Issue Creation Workflow

Esta skill define o procedimento obrigatorio para elaborar e publicar Issues no repositorio GitHub do projeto Monitoramento de Precos.

## 1. Diretrizes Obrigatorias

1. Proibicao Estrita de Emojis: Nunca inclua emojis no titulo, corpo da issue ou comentarios.
2. Formato BDD: Toda issue funcional deve conter cenarios de aceitacao no formato Dado / Quando / Entao.
3. Rastreabilidade: Toda issue deve referenciar o requisito funcional correspondente (ex: RF-001) documentado em docs/requisitos/.
4. Checklist Tecnico: Especificar tarefas tecnicas em camadas (Migrations, Models, Services, Controllers/Views, Tests).

## 2. Estrutura Padrao da Issue

O conteudo gerado deve seguir fielmente o arquivo de template em [issue_template.md](./resources/issue_template.md):

- Titulo: `[Tipo]: [Titulo da Issue]` (Exemplo: `feat(auth): implementar registro de usuario com email e senha`)
- Objetivo e Contexto: Explicacao clara do valor e contexto tecnico.
- Criterios de Aceite (BDD): Cenarios em Gherkin.
- Tarefas Tecnicas: Checklist executavel com caixas de selecao Markdown `- [ ]`.

## 3. Procedimento de Criacao

### Passo 1: Mapear o Requisito
Consulte os requisitos do sistema em `docs/requisitos/funcionais.md` e regras de negocio em `docs/requisitos/regras-de-negocio.md`.

### Passo 2: Redigir o Conteudo
Redija o corpo da issue atendendo a todas as secoes obrigatorias do template.

### Passo 3: Publicacao no GitHub

Caso a ferramenta GitHub CLI (`gh`) esteja disponivel e autenticada:
```powershell
gh issue create --title "[Tipo]: [Titulo]" --body-file issue_body.md
```

Caso a ferramenta `gh` nao esteja disponivel no terminal:
1. Apresente o conteudo completo e formatado da Issue diretamente ao usuario para abertura manual ou via API do GitHub.
2. Salve uma copia em formato markdown caso solicitado pelo usuario.
