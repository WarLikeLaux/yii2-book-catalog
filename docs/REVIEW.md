# Задачи по ревью PR - #30

**Источник:** [PR #30 на GitHub](https://github.com/WarLikeLaux/yii2-book-catalog/pull/30)
**Сгенерировано:** 2/7/2026, 12:26:29 PM

> [!NOTE]
> Этот файл создан автоматически. Отмечайте выполненные пункты как [x].

✅ Все замечания обработаны!

## Итого: 31 из 31 замечаний закрыты

### Ранее исправлены (до текущей сессии)

| # | Файл | Замечание | threadId |
|---|------|-----------|----------|
| 1 | `Makefile:259` | `make cov`/`make infection` → `make test-full` | PRRT_kwDOQgvvlc5sGwrH |
| 2 | `Makefile:437` | `.PHONY: review-fetch review-resolve` | PRRT_kwDOQgvvlc5tPLbt |
| 3 | `BookCommandHandler.php:54` | `@var int` → `assert(is_int($result))` | PRRT_kwDOQgvvlc5tLMPV |
| 4 | `author/update.php` | null-check для breadcrumbs | PRRT_kwDOQgvvlc5r_mmP |
| 5 | `book/update.php:14` | null-check для breadcrumbs | PRRT_kwDOQgvvlc5r_mmQ |
| 6 | `rector.php:35-37` | skip-паттерны: `*` → прямые пути | PRRT_kwDOQgvvlc5r_mmZ |
| 7 | `messages/ru-RU/app.php:84` | `authors_not_found` → множественное число | PRRT_kwDOQgvvlc5sSMWg |
| 8 | `messages/en-US/app.php:84` | `authors_not_found` → множественное число | PRRT_kwDOQgvvlc5sSMWb |
| 9 | `.agent/workflows/review.md:13` | security-заметка про `.env` и PAT | PRRT_kwDOQgvvlc5sCQfb |
| 10 | `AuthorIdCollection.php:52` | `is_numeric` → `ctype_digit` | PRRT_kwDOQgvvlc5sCQfc |
| 11 | `ErrorSummaryWidget.php:126` | добавлена опция `showAllErrors` | PRRT_kwDOQgvvlc5sCQff |
| 12 | `book/_form.php:17` | PHPDoc `$form` → `ActiveForm` | PRRT_kwDOQgvvlc5sCQfg |
| 13 | `site/index.php:126` | `dataType: 'json'` в AJAX | PRRT_kwDOQgvvlc5sCQfh |
| 14 | `site/index.php:94` | `htmx:configRequest` | PRRT_kwDOQgvvlc5tPVEp |
| 15 | `site/login.php:12` | PHPDoc `$form` → `ActiveForm` | PRRT_kwDOQgvvlc5sCQfi |
| 16 | `resolve-pr-threads.mjs:12` | `{ ...process.env }` merge | PRRT_kwDOQgvvlc5sCQfl |
| 17 | `fetch-pr-comments.mjs:94` | Node.js 18+ — `{ ...process.env }` | PRRT_kwDOQgvvlc5sCRoP |
| 18 | `fetch-pr-comments.mjs:101` | regex CodeRabbit fixed | PRRT_kwDOQgvvlc5sCRoT |
| 19 | `fetch-pr-comments.mjs:176` | лишний пробел убран | PRRT_kwDOQgvvlc5sGxda |
| 20 | `BookItemViewFactory.php:47` | один вызов `getBookById()` | PRRT_kwDOQgvvlc5sGwrJ |
| 21 | `commit.md:19` | опечатка «пытайяся» → «пытайся» | PRRT_kwDOQgvvlc5sG_cK |
| 22 | `routes.yaml:103` | `/book/publish` → POST | PRRT_kwDOQgvvlc5sG_cS |
| 23 | `routes.yaml:167` | `/subscription/subscribe` → только POST | PRRT_kwDOQgvvlc5sG_cU |
| 24 | `UploadedFileStorageTest.php:46` | проверка `tempnam()` на false | PRRT_kwDOQgvvlc5sG_cY |
| 25 | `UpdateBookUseCaseTest.php:278` | `storedCover: ':284'` → `null` | PRRT_kwDOQgvvlc5sR3Gg |
| 26 | `CreateBookUseCase.php` | `RuntimeException` → `OperationFailedException` | PRRT_kwDOQgvvlc5tIKj2 |
| 27 | `DomainErrorMappingRegistry.php:46` | `array_key_exists` check | PRRT_kwDOQgvvlc5tPXgO |
| 28 | `LoginFormCest.php:38` | ключи `ui.username/password/remember_me` | PRRT_kwDOQgvvlc5tPVEq |

### Исправлены в текущей сессии

| # | Файл | Замечание | threadId |
|---|------|-----------|----------|
| 29 | `TracerBootstrapTest.php:119` | `expects(never())` вынесен из цикла | PRRT_kwDOQgvvlc5sG_cb |
| 30 | `AuthorListViewFactoryTest.php:53` | `willReturnMap` — 2 args + return | PRRT_kwDOQgvvlc5tIKkA |
| 31 | `UpdateAuthorUseCaseTest.php:49` | имена тестов → соответствуют исключениям | PRRT_kwDOQgvvlc5tL4la |

### Отклонены (неактуальны)

| # | Файл | Причина |
|---|------|---------|
| — | `messages/ru-RU/app.php:37` (footer HTML) | `<?=` не экранирует HTML. Замечание ложноположительное. | PRRT_kwDOQgvvlc5sAylQ |
| — | `ErrorMappingTraitTest.php` | Файл не существует в кодовой базе | PRRT_kwDOQgvvlc5sSMWk |
| — | `MultilineViewVarAnnotationRector.php:123` | Исправлено: `isSingleLineVarDoc()` | PRRT_kwDOQgvvlc5sApXs |
