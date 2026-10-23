# Wishes 4.1 — Correção de capas e edições

Esta versão atualiza a Wishes 4.0, inclusive a versão corrigida do `Genres.tsx`. Não modifica a estrutura do banco de dados, suas listas, perfis, imagens nem configurações de simulação.

## Atualizar a instalação no Windows 11

1. Feche `INICIAR_WINDOWS.bat` antes de copiar arquivos.
2. Faça backup de `data`, `.env` e, se existir, `data/media`.
3. Extraia o ZIP. Copie **o conteúdo da pasta `Wishes_Biblioteca`** para sua instalação atual, substituindo somente os arquivos de código. **Não apague `data`, `.env`, `.venv` ou `backups`.**
4. Execute `./ATUALIZAR_WINDOWS.bat` (ou `\.\ATUALIZAR_WINDOWS.bat` no PowerShell). O atualizador tentará criar mais um backup de `data`.
5. Inicie com `./INICIAR_WINDOWS.bat` e abra `http://127.0.0.1:8000`. Use `Ctrl+F5` se o navegador mantiver a tela anterior.

O pacote de correção contém apenas os arquivos alterados e segue as mesmas etapas.

## Como pesquisar

**Edição exata:** no modal de capas, cole na primeira caixa um ISBN-10/13, um link de volume do Google Livros (`https://books.google.com/books?id=...`), um URL de edição (`https://books.google.com/books/edition/TITULO/ID`) ou um endereço da Open Library (`https://openlibrary.org/books/OL...M`). Clique em **Localizar edição**. Só o registro correspondente ao ID é consultado; um ISBN pode recuperar duas fichas da mesma edição em catálogos diferentes. Não use links da página geral de pesquisa.

**Pesquisar por título:** preencha o título brasileiro (ou original), o autor, o ISBN, a editora e o idioma desejado. A busca usa Google Books e Open Library, prefere edições em português quando solicitado e classifica por ISBN, título, idioma, editora, presença de capa e autoria. O campo de idioma prioriza as fichas; não garante o país de publicação nem a correspondência física exata da edição.

**Comparar capas:** os resultados indicam se a imagem corresponde a um registro de edição ou apenas a uma obra; estas últimas podem mostrar uma edição diferente da brasileira. Por padrão, a interface exibe somente os resultados com imagem, mas isso pode ser desmarcado. Se uma imagem estiver ausente ou inacessível, use `Enviar imagem` ou `URL da capa personalizada` no editor. Nenhuma capa é aplicada automaticamente.

## Terceira fonte opcional — ISBNdb

ISBNdb é um serviço externo **pago**, que exige chave e permite procurar livros por ISBN e título. Se você contratar um plano com API e permissão de uso das capas, inclua a seguinte linha em seu `.env` local:

```env
ISBNDB_API_KEY=sua_chave_aqui
```

Reinicie o servidor. Sem essa chave, a Wishes consulta apenas Google Books e Open Library. As credenciais ficam exclusivamente no backend. Confira os termos de uso das imagens conforme seu plano.

A Amazon Creators API exige credenciamento como associado/afiliado aprovado e não está integrada; a Panini não conta aqui com uma API pública confirmada. Não foi implementado scraping de páginas de lojas. A API do Google Books continua documentada separadamente do site Google Livros.

## Testes

No PowerShell, execute:

```powershell
.\.venv\Scripts\python.exe -m pytest -q backend\tests
```

Os testes simulam respostas dos provedores; não demonstram que cada livro e ISBN consta nos catálogos externos ou que eles estarão online. A compilação do frontend é realizada pelo `ATUALIZAR_WINDOWS.bat` no seu computador.
