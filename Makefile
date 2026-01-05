.PHONY: help init up down restart logs shell sms-logs tinker perms setup env configure clean composer dev fix ci lint lint-fix rector rector-fix analyze deptrac audit test test-unit test-integration test-e2e test-coverage coverage cov infection load-test migrate seed db-info queue-info comments docs swagger repomix diff d dc ds diff-staged diff-cached req require req-dev require-dev ai _dev_full _dev_file

COMPOSE=docker compose
PHP_CONTAINER=php
DB_TEST_NAME=yii2basic_test
.DEFAULT_GOAL := help

ifeq ($(firstword $(MAKECMDGOALS)),$(filter $(firstword $(MAKECMDGOALS)),req require req-dev require-dev))
  COMPOSER_ARGS := $(wordlist 2,$(words $(MAKECMDGOALS)),$(MAKECMDGOALS))
  $(eval $(COMPOSER_ARGS):;@:)
endif

ifeq ($(firstword $(MAKECMDGOALS)),$(filter $(firstword $(MAKECMDGOALS)),dev))
  FILE_ARG := $(wordlist 2,$(words $(MAKECMDGOALS)),$(MAKECMDGOALS))
  $(eval $(FILE_ARG):;@:)
endif

ifneq (,$(wildcard .env))
    include .env
    export
endif

# =================================================================================================
# 🚀 ГЛАВНОЕ МЕНЮ И УПРАВЛЕНИЕ
# =================================================================================================

help:
	@echo "Использование: make [команда]"
	@echo ""
	@echo "🚀 СТАРТ:"
	@echo "  install          📥 Установить и запустить проект"
	@echo "  install-force    📥 Принудительная установка (без вопросов)"
	@echo ""
	@echo "🛡️  КОНТРОЛЬ КАЧЕСТВА (QA):"
	@echo "  test             ✅ Запуск тестов (unit + integration + coverage)"
	@echo "  test-e2e         🎭 Только E2E-тесты (acceptance)"
	@echo "  cov              📊 Отчет покрытия (из последнего запуска)"
	@echo "  infection        🧟 Мутационное тестирование (только полный прогон)"
	@echo "  deptrac          🏗️  Архитектурный анализ"
	@echo "  check            🛡️  Экспресс-проверка (dev + deptrac + test)"
	@echo "  pr               🚀 Полная проверка (check + e2e + infection)"
	@echo ""
	@echo "💻 РАЗРАБОТКА:"
	@echo "  dev              🛠️  Полный цикл (CS Fixer + Rector + PHPStan)"
	@echo "  dev [FILE]       🔍 Быстрая проверка файла (только CS Fixer)"
	@echo "  comments         📝 Показать TODO и заметки"
	@echo "  d                🔎 Показать изменения (вкл. новые файлы)"
	@echo "  dc               📌 Показать изменения в индексе (staged)"
	@echo "  tree             🌳 Показать структуру проекта"
	@echo ""
	@echo "📦 ПАКЕТЫ (COMPOSER):"
	@echo "  composer         📥 Установка зависимостей (install)"
	@echo "  req [package]    ➕ Добавить пакет (алиас: require)"
	@echo "  req-dev [pkg]    ➕ Добавить dev-пакет (алиас: require-dev)"
	@echo ""
	@echo "🐳 DOCKER & OPS:"
	@echo "  up               ▶️  Запустить контейнеры"
	@echo "  down             ⏹️  Остановить контейнеры"
	@echo "  restart          🔁 Перезапустить контейнеры"
	@echo "  logs             📄 Смотреть логи"
	@echo "  sms-logs         📱 Логи отправленных SMS"
	@echo "  shell            🐚 Зайти в контейнер PHP"
	@echo "  tinker           🧪 Yii shell (php yii shell)"
	@echo ""
	@echo "🗄️  БАЗА ДАННЫХ:"
	@echo "  migrate          🏗️  Применить миграции"
	@echo "  seed             🌱 Залить тестовые данные"
	@echo "  db-info          📊 Текущая конфигурация БД"
	@echo "  db-mysql         🐬 Переключить на MySQL"
	@echo "  db-pgsql         🐘 Переключить на PostgreSQL"
	@echo "  queue-info       📥 Статус очереди задач"
	@echo ""
	@echo "📚 ДОКУМЕНТАЦИЯ:"
	@echo "  docs             📑 Генерация Yii2 API Docs"
	@echo "  swagger          🌐 Генерация OpenAPI/Swagger"
	@echo "  repomix          🤖 Сборка контекста для LLM"

# =================================================================================================
# 🐳 DOCKER И ОКРУЖЕНИЕ
# =================================================================================================

install: init
install-force: init-force

init-force: _mkdirs
	@echo "🚀 Принудительная установка (Без вопросов)..."
	@chmod +x bin/setup-env
	@./bin/setup-env -y
	@$(MAKE) up
	@echo "⏳ Ожидание запуска базы данных..."
	@sleep 5
	@$(MAKE) composer
	@$(MAKE) migrate
	@$(MAKE) seed
	@APP_PORT=$$(grep '^APP_PORT=' .env | cut -d '=' -f2 | tr -d '"' | tr -d ' ' || echo 8000); \
	echo ""; \
	echo "✅ Проект установлен: http://localhost:$$APP_PORT"

init: _init_confirm setup ai up composer migrate seed
	@APP_PORT=$$(grep '^APP_PORT=' .env | cut -d '=' -f2 | tr -d '"' | tr -d ' ' || echo 8000); \
	BUG_PORT=$$(grep '^BUGGREGATOR_UI_PORT=' .env | cut -d '=' -f2 | tr -d '"' | tr -d ' ' || echo 9913); \
	echo ""; \
	echo "======================================================================"; \
	echo "🚀 ПРОЕКТ ГОТОВ К РАБОТЕ"; \
	echo "======================================================================"; \
	echo "🌍 Сайт:        http://localhost:$$APP_PORT"; \
	echo "📄 API Docs:    http://localhost:$$APP_PORT/api"; \
	echo "🐞 Buggregator: http://localhost:$$BUG_PORT"; \
	echo "======================================================================"

_init_confirm:
	@echo ""
	@echo "======================================================================"
	@echo "🚨  ВНИМАНИЕ: ПОЛНАЯ ИНИЦИАЛИЗАЦИЯ ПРОЕКТА"
	@echo "======================================================================"
	@echo "Будут выполнены следующие действия:"
	@echo "  1. 🛠  Настройка окружения (права, папки, .env)"
	@echo "  2. 🔗 Создание симлинков для AI агентов"
	@echo "  3. 🐳 Пересоздание и запуск контейнеров (docker compose up)"
	@echo "  4. 📦 Установка зависимостей (composer install)"
	@echo "  5. 🗄  Применение миграций и заливка тестовых данных (seed)"
	@echo ""
	@read -p "   Вы готовы продолжить? [y/N] " ans; \
	if [ "$$ans" != "y" ] && [ "$$ans" != "Y" ]; then \
		echo "❌ Отменено пользователем."; \
		exit 1; \
	fi

up:
	@driver=$${DB_DRIVER:-mysql}; \
	if [ "$$driver" = "pgsql" ]; then \
		$(COMPOSE) up -d pgsql redis php nginx queue swagger-ui buggregator selenium --remove-orphans; \
	else \
		$(COMPOSE) up -d db redis php nginx queue swagger-ui buggregator selenium --remove-orphans; \
	fi

down:
	$(COMPOSE) down

restart: down up

logs:
	$(COMPOSE) logs -f

shell:
	$(COMPOSE) exec $(PHP_CONTAINER) sh

tinker:
	$(COMPOSE) exec $(PHP_CONTAINER) php yii shell

sms-logs:
	$(COMPOSE) exec $(PHP_CONTAINER) tail -f runtime/logs/sms.log

# =================================================================================================
# 🛠 НАСТРОЙКА (SETUP)
# =================================================================================================

perms:
	@echo "🔧 Исправление прав..."
	@HOST_UID=$$(id -u) HOST_GID=$$(id -g); \
	$(COMPOSE) run --rm -u root $(PHP_CONTAINER) chown -R $$HOST_UID:$$HOST_GID /app 2>/dev/null || \
	echo "⚠️  Docker chown недоступен (rootless?), только chmod"
	@$(MAKE) _fix_code_perms
	@echo "✅ Права доступа восстановлены."

setup: perms ai _mkdirs
	@chmod +x bin/setup-env
	@chmod +x bin/list-comments
	@if [ -f .env ]; then \
		echo "❓ Файл .env найден."; \
		read -p "   Перезаписать его (сбросить настройки)? [y/N] " ans; \
		if [ "$$ans" = "y" ] || [ "$$ans" = "Y" ]; then \
			./bin/setup-env; \
		else \
			echo "✅ .env оставлен без изменений."; \
		fi \
	else \
		./bin/setup-env -y; \
	fi

configure: perms _mkdirs
	@echo "⚠️  Вы запускаете полную перенастройку окружения."
	@echo "   Это обновит файл .env и может изменить порты."
	@read -p "   Вы уверены? [y/N] " ans; \
	if [ "$$ans" != "y" ] && [ "$$ans" != "Y" ]; then \
		echo "❌ Отменено."; \
		exit 1; \
	fi
	@chmod +x bin/setup-env
	@./bin/setup-env

env:
	@chmod +x bin/setup-env
	@./bin/setup-env

_fix_code_perms:
	@echo "🔒 Нормализация прав (dirs=755, files=644)..."
	@find . -maxdepth 1 -type f \( -name "*.php" -o -name "*.json" -o -name "*.lock" -o -name "*.xml" -o -name "*.dist" -o -name "*.yaml" -o -name "*.yml" -o -name "*.md" -o -name "*.neon" -o -name ".env*" -o -name ".git*" -o -name "Makefile" -o -name "Dockerfile" \) -exec chmod 644 {} + 2>/dev/null || true
	@find application domain infrastructure presentation config tests migrations docs web -type d -exec chmod 755 {} + 2>/dev/null || true
	@find application domain infrastructure presentation config tests migrations docs -type f -exec chmod 644 {} + 2>/dev/null || true
	@find web -type f \( -name "*.php" -o -name "*.css" -o -name "*.js" -o -name "*.html" -o -name "*.ico" -o -name "*.txt" \) -exec chmod 644 {} + 2>/dev/null || true
	@chmod -R 755 bin 2>/dev/null || true
	@chmod 755 yii 2>/dev/null || true

_mkdirs:
	mkdir -p web/uploads runtime/debug runtime/logs runtime/cache runtime/sessions

clean:
	@echo "🧹 Очистка кэша и логов..."
	@$(COMPOSE) exec -T $(PHP_CONTAINER) sh -c "rm -rf /app/runtime/debug/* /app/runtime/logs/* /app/runtime/cache/*"
	@echo "✅ Очищено (runtime)."

composer:
	$(COMPOSE) exec $(PHP_CONTAINER) composer install
	$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/codecept build
	$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/grumphp git:init || true

req require:
	$(COMPOSE) exec $(PHP_CONTAINER) composer require $(COMPOSER_ARGS)

req-dev require-dev:
	$(COMPOSE) exec $(PHP_CONTAINER) composer require --dev $(COMPOSER_ARGS)


# =================================================================================================
# 🛡️ КОНТРОЛЬ КАЧЕСТВА (QA)
# =================================================================================================

ci: lint analyze
fix: lint-fix rector-fix
dev:
	@if [ -z "$(FILE_ARG)" ]; then \
		$(MAKE) _dev_full; \
	else \
		$(MAKE) _dev_file; \
	fi
_dev_full: fix ci
_dev_file:
	@echo "🔍 Проверяем: $(FILE_ARG)"
	@$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/phpcbf $(FILE_ARG) || true
	@$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/rector process $(FILE_ARG) || true
	@$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/phpcs $(FILE_ARG) || true
	@echo "✅ Готово"
check: dev deptrac test
pr: docs check test-e2e infection

lint:
	$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/phpcs

lint-fix:
	-$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/phpcbf

rector:
	$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/rector process --dry-run

rector-fix:
	$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/rector process

analyze:
	$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/phpstan analyse --memory-limit=2G

deptrac:
	$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/deptrac analyze

audit:
	$(COMPOSE) exec $(PHP_CONTAINER) composer audit

# =================================================================================================
# 🧪 ТЕСТЫ
# =================================================================================================

_test-init:
	@echo "🔧 Подготовка тестовой базы ($(DB_DRIVER))..."
ifeq ($(DB_DRIVER),pgsql)
	@$(COMPOSE) exec -T pgsql sh -c 'psql -U "$$POSTGRES_USER" -d postgres -c "SELECT 1 FROM pg_database WHERE datname = '\''$(DB_TEST_NAME)'\''" | grep -q 1 || psql -U "$$POSTGRES_USER" -d postgres -c "CREATE DATABASE $(DB_TEST_NAME)"' 2>/dev/null || true
else
	@$(COMPOSE) exec -T db sh -c 'mysql -uroot -p"$$MYSQL_ROOT_PASSWORD" -h127.0.0.1 -e "CREATE DATABASE IF NOT EXISTS $(DB_TEST_NAME); GRANT ALL PRIVILEGES ON $(DB_TEST_NAME).* TO \"$$MYSQL_USER\"@\"%\"; FLUSH PRIVILEGES;"' 2>&1 | grep -v "Using a password" || true
endif
	@$(COMPOSE) exec -T $(PHP_CONTAINER) sh -c "DB_NAME=$(DB_TEST_NAME) ./yii migrate --interactive=0 --migrationPath=@app/migrations" > /dev/null

test: _test-init
	@echo "🚀 Запуск всех тестов с генерацией отчетов..."
	@$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/codecept run integration,unit \
		--coverage --coverage-xml --coverage-html --coverage-text \
		--coverage-phpunit --xml=junit.xml --no-colors
	@sed -i 's|/app/|$(CURDIR)/|g' tests/_output/coverage.xml
	@$(MAKE) cov

test-unit:
	@echo "🚀 Запуск Unit тестов..."
	@$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/codecept run unit --no-colors

test-integration: _test-init
	@echo "🚀 Запуск Integration тестов..."
	@$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/codecept run integration --no-colors

test-e2e: _test-init
	@echo "🚀 Запуск E2E тестов..."
	@$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/codecept run e2e --no-colors

test-coverage coverage cov:
	@if [ ! -f tests/_output/coverage.xml ]; then $(MAKE) test; fi
	@echo "----------------------------------------------------------------------"
	@$(COMPOSE) exec $(PHP_CONTAINER) head -n 9 tests/_output/coverage.txt
	@echo "----------------------------------------------------------------------"
	@php -r '$$xml = simplexml_load_file("tests/_output/coverage.xml"); $$out = ""; foreach ($$xml->project->xpath("//file") as $$file) { $$miss = []; foreach ($$file->line as $$line) { if ((string)$$line["count"] === "0" && (string)$$line["type"] === "stmt") { $$miss[] = (string)$$line["num"]; } } if (!empty($$miss)) { $$name = str_replace("$(CURDIR)/", "", (string)$$file["name"]); $$out .= "\033[1;31m✘ $$name\033[0m" . PHP_EOL . "   Lines: " . implode(", ", $$miss) . PHP_EOL; } } if ($$out !== "") { echo "🔍 Непокрытые строки:" . PHP_EOL . $$out . "----------------------------------------------------------------------" . PHP_EOL; }'
	@echo "Полный отчет: tests/_output/coverage/index.html"

test-infection infection inf:
	@if [ ! -f tests/_output/coverage-phpunit.xml ]; then $(MAKE) test; fi
	@echo "🧟 Запуск мутационного тестирования..."
	@$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/infection --coverage=tests/_output --threads=max --test-framework-options="integration,unit"

load-test:
	@echo "🚀 Load Testing (K6)..."
	$(COMPOSE) run --rm k6 run /scripts/smoke.js

# =================================================================================================
# 📦 БАЗА ДАННЫХ
# =================================================================================================

migrate:
	$(COMPOSE) exec $(PHP_CONTAINER) ./yii migrate --interactive=0

seed:
	$(COMPOSE) exec $(PHP_CONTAINER) ./yii seed

db-mysql:
	@sed -i 's/^DB_DRIVER=.*/DB_DRIVER=mysql/' .env
	@echo "✅ DB_DRIVER=mysql (host=db:3306 авто)"

db-pgsql:
	@sed -i 's/^DB_DRIVER=.*/DB_DRIVER=pgsql/' .env
	@echo "✅ DB_DRIVER=pgsql (host=pgsql:5432 авто)"
db-info:
	@driver=$$(grep '^DB_DRIVER=' .env | cut -d= -f2); \
	if [ "$$driver" = "pgsql" ]; then \
		host=$$(grep '^PGSQL_DB_HOST=' .env | cut -d= -f2); \
		port=$$(grep '^PGSQL_DB_PORT=' .env | cut -d= -f2); \
	else \
		host=$$(grep '^MYSQL_DB_HOST=' .env | cut -d= -f2); \
		port=$$(grep '^MYSQL_DB_PORT=' .env | cut -d= -f2); \
	fi; \
	echo "📊 DB_DRIVER=$$driver → $$host:$$port"

# =================================================================================================
# 📚 ДОКУМЕНТАЦИЯ И УТИЛИТЫ
# =================================================================================================

queue-info:
	$(COMPOSE) exec $(PHP_CONTAINER) ./yii queue/info

comments:
	@./bin/list-comments

docs:
	@$(COMPOSE) exec $(PHP_CONTAINER) ./yii docs/all
	@echo "✅ Документация обновлена (docs/auto)."

tree:
	@$(COMPOSE) exec $(PHP_CONTAINER) ./yii docs/tree

swagger:
	$(COMPOSE) exec $(PHP_CONTAINER) php docs/api/generate.php

repomix:
	@npx -y repomix --style markdown --output repomix-output.md

ai:
	@echo "🔗 Создание симлинков для AI агентов..."
	@ln -sf CLAUDE.md GEMINI.md
	@ln -sf CLAUDE.md AGENTS.md
	@ln -sf CLAUDE.md GROK.md
	@ln -sf CLAUDE.md .cursorrules
	@ln -sf CLAUDE.md .clinerules
	@ln -sf CLAUDE.md .windsurfrules
	@mkdir -p .antigravity
	@ln -sf ../CLAUDE.md .antigravity/rules.md
	@mkdir -p .agent/rules
	@ln -sf ../../CLAUDE.md .agent/rules/rules.md
	@echo "✅ Симлинки созданы: GEMINI.md, AGENTS.md, GROK.MD, .cursorrules, .clinerules, .windsurfrules, .antigravity/rules.md, .agent/rules/rules.md -> CLAUDE.md"

diff d:
	@git diff || true
	@git ls-files -o --exclude-standard -z | xargs -0 -r -I{} git diff --no-index /dev/null {} || true

diff-staged diff-cached ds dc:
	@git diff --staged || true
