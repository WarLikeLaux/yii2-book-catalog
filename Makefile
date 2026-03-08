.PHONY: help install install-force init setup env configure perms clean \
        up down restart logs shell tinker sms-logs \
        composer req require req-dev require-dev \
        dev _dev_full _dev_file fix ci check lint lint-fix rector rector-fix analyze prettier prettier-fix deptrac arkitect arch audit \
        test test-unit test-integration test-e2e cov coverage test-coverage infection inf load-test test-migration \
        migrate seed db-mysql db-pgsql db-info db-fresh db-test-fresh queue-info \
        docs swagger repomix tree comments ai \
        ci-env ci-up ci-down ci-install ci-audit ci-openapi-check ci-quality ci-test-suite ci-e2e \
        diff d sdiff tag \
        bin-exec

COMPOSE=docker compose
PHP_CONTAINER=php
DB_TEST_NAME=yii2basic_test

.DEFAULT_GOAL := help
bin-exec: ; @if [ -d bin ]; then find bin -maxdepth 1 -type f -exec chmod +x {} +; fi

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

# Главное меню и управление

help:
	@echo "Использование: make [команда]"
	@echo ""
	@echo "🚀 СТАРТ:"
	@echo "  install              📥 Установить и запустить проект"
	@echo "  install-force        📥 Принудительная установка (без вопросов)"
	@echo ""
	@echo "💻 РАЗРАБОТКА И QA:"
	@echo "  dev                  🛠️  Полный цикл (PHPCS Fixer + Rector + Comments)"
	@echo "  dev [FILE]           🔍 Быстрая проверка файла (только PHPCS Fixer)"
	@echo "  arch                 🏛️  Архитектурная проверка (Deptrac + Arkitect)"
	@echo "  analyze              🔬 Статический анализ (lint + arch + rector + PHPStan)"
	@echo "  test                 ✅ Запуск unit-тестов"
	@echo "  test-integration     🔗 Запуск integration-тестов"
	@echo "  test-full            🧪 Полный прогон (unit + integration + coverage)"
	@echo "  test-e2e             🎭 Только E2E-тесты (acceptance)"
	@echo "  test-load            📈 Нагрузочное тестирование (K6)"
	@echo "  test-migration       🏗️  Тест миграций (up/down)"
	@echo "  cov                  📊 Отчет покрытия (из последнего запуска)"
	@echo "  infection            🧟 Мутационное тестирование (только полный прогон)"
	@echo "  check                🛡️  Экспресс-проверка (dev + arch + test)"
	@echo "  comments             📝 Показать TODO и заметки"
	@echo "  tree                 🌳 Показать структуру проекта"
	@echo "  clean                🧹 Очистка кэша и логов"
	@echo ""
	@echo "🛰️  GIT SHORTCUTS:"
	@echo "  diff (d)             🔎 Показать изменения (включая untracked файлы)"
	@echo "  sdiff                📌 Показать изменения в индексе (staged)"
	@echo ""
	@echo "📦 ПАКЕТЫ (COMPOSER):"
	@echo "  composer             📥 Установка зависимостей (install)"
	@echo "  update               🔝 Обновление пакетов (update)"
	@echo "  outdated             🔍 Проверка доступных обновлений пакетов"
	@echo "  req [package]        ➕ Добавить пакет (алиас: require)"
	@echo "  req-dev [pkg]        ➕ Добавить dev-пакет (алиас: require-dev)"
	@echo ""
	@echo "🐳 DOCKER & OPS:"
	@echo "  up                   ▶️  Запустить контейнеры"
	@echo "  down                 ⏹️  Остановить контейнеры"
	@echo "  restart              🔁 Перезапустить контейнеры"
	@echo "  logs                 📄 Смотреть логи"
	@echo "  sms-logs             📱 Логи отправленных SMS"
	@echo "  shell                🐚 Зайти в контейнер PHP"
	@echo "  tinker               🧪 Yii shell (php yii shell)"
	@echo ""
	@echo "🗄️  БАЗА ДАННЫХ:"
	@echo "  migrate              🏗️  Применить миграции"
	@echo "  seed                 🌱 Залить тестовые данные"
	@echo "  db-info              📊 Текущая конфигурация БД"
	@echo "  db-mysql             🐬 Переключить на MySQL"
	@echo "  db-pgsql             🐘 Переключить на PostgreSQL"
	@echo "  db-fresh             🚨 Полный сброс БД (fresh + seed)"
	@echo "  db-test-fresh        🧪 Полный сброс тестовой БД (fresh + migrations)"
	@echo "  queue-info           📥 Статус очереди задач"
	@echo ""
	@echo "📚 ДОКУМЕНТАЦИЯ:"
	@echo "  docs                 📑 Генерация Yii2 API Docs"
	@echo "  swagger              🌐 Генерация OpenAPI/Swagger"
	@echo "  repomix              🤖 Сборка контекста для LLM"

# Docker и окружение

install: bin-exec init
install-force: bin-exec init-force

init-force:
	@./bin/bootstrap init-force

init:
	@./bin/bootstrap init

up:
	@driver=$${DB_DRIVER:-mysql}; \
	if [ "$$driver" = "pgsql" ]; then \
		$(COMPOSE) up -d pgsql redis php nginx queue swagger-ui jaeger selenium --remove-orphans; \
	else \
		$(COMPOSE) up -d db redis php nginx queue swagger-ui jaeger selenium --remove-orphans; \
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

# Настройка

perms:
	@echo "🔧 Исправление прав..."
	@HOST_UID=$$(id -u) HOST_GID=$$(id -g); \
	$(COMPOSE) run --rm -u root $(PHP_CONTAINER) chown -R $$HOST_UID:$$HOST_GID /app 2>/dev/null || \
	echo "⚠️  Docker chown недоступен (rootless?), только chmod"
	@./bin/fix-perms

setup: bin-exec
	@./bin/bootstrap setup

configure: bin-exec
	@./bin/bootstrap configure

env: bin-exec
	@./bin/setup-env

ci-env:
	@cp .env.example .env
	@sed -i 's/^DB_DRIVER=.*/DB_DRIVER=$(DB_DRIVER)/' .env
	@sed -i 's/^DB_NAME=.*/DB_NAME=$(DB_TEST_NAME)/' .env
	@sed -i 's/^COOKIE_VALIDATION_KEY=.*/COOKIE_VALIDATION_KEY=testkeytestkeytestkeytestkeytestkey/' .env
	@sed -i "s/^UID=.*/UID=$$(id -u)/" .env
	@sed -i "s/^GID=.*/GID=$$(id -g)/" .env
	@mkdir -p runtime/cache runtime/logs runtime/sessions web/assets

clean:
	@echo "🧹 Очистка кэша и логов..."
	@$(COMPOSE) exec -T $(PHP_CONTAINER) sh -c "rm -rf /app/runtime/debug/* /app/runtime/logs/* /app/runtime/cache/*"
	@echo "✅ Очищено (runtime)."

composer:
	$(COMPOSE) exec $(PHP_CONTAINER) composer install
	$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/codecept build
	$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/grumphp git:init || true

ci-up:
	@driver=$${DB_DRIVER:-mysql}; \
	if [ "$$driver" = "pgsql" ]; then \
		$(COMPOSE) up -d --build pgsql redis php nginx selenium --remove-orphans; \
	else \
		$(COMPOSE) up -d --build db redis php nginx selenium --remove-orphans; \
	fi

ci-down:
	-$(COMPOSE) down -v --remove-orphans

ci-install:
	$(COMPOSE) exec $(PHP_CONTAINER) composer install --no-interaction --prefer-dist --optimize-autoloader
	$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/codecept build
	$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/grumphp git:init || true

update:
	$(COMPOSE) exec $(PHP_CONTAINER) composer update
	$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/codecept build
	$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/grumphp git:init || true

outdated:
	$(COMPOSE) exec $(PHP_CONTAINER) composer outdated

req require:
	$(COMPOSE) exec $(PHP_CONTAINER) composer require $(COMPOSER_ARGS)

req-dev require-dev:
	$(COMPOSE) exec $(PHP_CONTAINER) composer require --dev $(COMPOSER_ARGS)


# Контроль качества

ci: analyze
fix: lint-fix rector-fix
dev:
	@lockdir="$(CURDIR)/.dev.lock"; \
	pidfile="$$lockdir/pid"; \
	cleanup() { rm -rf "$$lockdir"; }; \
	trap cleanup EXIT; \
	if mkdir "$$lockdir" 2>/dev/null; then \
		echo $$BASHPID > "$$pidfile"; \
	elif [ -f "$$pidfile" ] && ! kill -0 $$(cat "$$pidfile" 2>/dev/null) 2>/dev/null; then \
		echo "⚠️  Найден mёртвый lock. Очищаю..."; \
		rm -rf "$$lockdir"; \
		mkdir "$$lockdir" || { echo "⛔ Не удалось создать lock"; exit 1; }; \
		echo $$BASHPID > "$$pidfile"; \
	else \
		echo "⛔ dev уже запущен (PID: $$(cat "$$pidfile" 2>/dev/null || echo '???'))"; \
		exit 1; \
	fi; \
	if [ -z "$(FILE_ARG)" ]; then \
		$(MAKE) _dev_full; \
	else \
		$(MAKE) _dev_file; \
	fi
_dev_full: lint-fix rector-fix comments
_dev_file:
	@echo "🔍 Проверяем: $(FILE_ARG)"
	@$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/phpcbf $(FILE_ARG) || true
	@$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/rector process --clear-cache $(FILE_ARG) || true
	@$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/phpcs $(FILE_ARG) || true
	@echo "✅ Готово"

lint:
	$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/phpcs

lint-fix:
	-$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/phpcbf

rector:
	$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/rector process --dry-run --clear-cache

rector-fix:
	$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/rector process --clear-cache

analyze: lint arch rector
	$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/phpstan clear-result-cache
	$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/phpstan analyse --memory-limit=2G

prettier:
	$(COMPOSE) exec $(PHP_CONTAINER) sh -c "{ git ls-files '*.md'; git ls-files --others --exclude-standard '*.md'; } | xargs node ./vendor/npm-asset/prettier/bin/prettier.cjs --check"

prettier-fix:
	$(COMPOSE) exec $(PHP_CONTAINER) sh -c "{ git ls-files '*.md'; git ls-files --others --exclude-standard '*.md'; } | xargs node ./vendor/npm-asset/prettier/bin/prettier.cjs --write"

deptrac:
	$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/deptrac analyze

arkitect:
	$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/phparkitect check

arch: deptrac arkitect

audit:
	$(COMPOSE) exec $(PHP_CONTAINER) composer audit

ci-audit:
	$(COMPOSE) exec $(PHP_CONTAINER) composer audit

ci-openapi-check: swagger
	git diff --exit-code -- docs/api/openapi.yaml

ci-quality: ci-audit analyze ci-openapi-check

# Тесты

_test-init:
	@DB_DRIVER=$(DB_DRIVER) DB_TEST_NAME=$(DB_TEST_NAME) COMPOSE="$(COMPOSE)" ./bin/test-db-prepare

test: test-unit

test-full:
	@lockdir="$(CURDIR)/.test.lock"; \
	pidfile="$$lockdir/pid"; \
	cleanup() { rm -rf "$$lockdir"; }; \
	trap cleanup EXIT; \
	if mkdir "$$lockdir" 2>/dev/null; then \
		echo $$BASHPID > "$$pidfile"; \
	elif [ -f "$$pidfile" ] && ! kill -0 $$(cat "$$pidfile" 2>/dev/null) 2>/dev/null; then \
		echo "⚠️  Найден мёртвый lock для тестов. Очищаю..."; \
		rm -rf "$$lockdir"; \
		mkdir "$$lockdir" || { echo "⛔ Не удалось создать lock"; exit 1; }; \
		echo $$BASHPID > "$$pidfile"; \
	else \
		echo "⛔ Тесты уже запущены (PID: $$(cat "$$pidfile" 2>/dev/null || echo '???'))"; \
		exit 1; \
	fi; \
	$(MAKE) _test-init; \
	echo "🚀 Запуск всех тестов (unit + integration) с генерацией coverage..."; \
	$(COMPOSE) exec $(PHP_CONTAINER) php -d memory_limit=2G -d pcov.directory=/app -d pcov.exclude="~/(vendor|tests|runtime|web/assets)/~" ./vendor/bin/codecept run integration,unit \
		--ext DotReporter \
		--skip-group migration \
		--coverage-text \
		--coverage-xml \
		--coverage-phpunit --xml=junit.xml --no-colors && \
	sed -i "s|/app/|$(CURDIR)/|g" tests/_output/coverage.xml && \
	$(MAKE) cov

test-unit:
	@echo "🚀 Запуск Unit тестов..."
	@$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/phpunit

test-integration: _test-init
	@echo "🚀 Запуск Integration тестов..."
	@$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/codecept run integration --ext DotReporter --skip-group migration --no-colors

test-e2e: _test-init
	@echo "🌱 Заполнение тестовых данных для E2E..."
	@DB_NAME=$(DB_TEST_NAME) $(COMPOSE) exec $(PHP_CONTAINER) ./yii seed --interactive=0
	@echo "🚀 Запуск E2E тестов..."
	@$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/codecept run e2e --ext DotReporter --no-colors

test-migration:
	bin/test-migration

test-coverage coverage cov:
	@if [ ! -f tests/_output/coverage.xml ]; then $(MAKE) test-full; fi
	@./bin/coverage-report

test-infection infection inf:
	@if [ ! -f tests/_output/coverage-phpunit.xml ]; then $(MAKE) test-full; fi
	@echo "🧟 Запуск мутационного тестирования..."
	@$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/infection --coverage=tests/_output --threads=max --test-framework-options="integration,unit --skip-group migration"

test-load:
	@echo "🚀 Load Testing (K6)..."
	$(COMPOSE) run --rm k6 run /scripts/smoke.js

ci-test-suite: test-full infection

ci-e2e: test-e2e

# База данных

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

db-fresh:
	@echo "🚨 ВНИМАНИЕ: Это полностью удалит все данные из текущей БД и создаст структуру заново."
	@read -p "   Вы уверены? [y/N] " ans; \
	if [ "$$ans" != "y" ] && [ "$$ans" != "Y" ]; then \
		echo "❌ Отменено."; \
		exit 1; \
	fi
	@$(MAKE) clean
	@$(COMPOSE) exec $(PHP_CONTAINER) ./yii migrate/fresh --interactive=0
	@$(COMPOSE) exec $(PHP_CONTAINER) ./yii seed --interactive=0
	@echo "✅ База данных очищена и заполнена заново."

db-test-fresh:
	@DB_DRIVER=$(DB_DRIVER) DB_TEST_NAME=$(DB_TEST_NAME) COMPOSE="$(COMPOSE)" ./bin/test-db-fresh

# Документация и утилиты

queue-info:
	$(COMPOSE) exec $(PHP_CONTAINER) ./yii queue/info

comments:
	@./bin/list-comments

docs:
	@$(COMPOSE) exec $(PHP_CONTAINER) ./yii docs/all
	@./bin/update-sitemap
	@echo "✅ Документация обновлена (docs/generated)."

tree:
	@$(COMPOSE) exec $(PHP_CONTAINER) ./yii docs/tree

swagger:
	$(COMPOSE) exec $(PHP_CONTAINER) php docs/api/generate.php

repomix:
	@npx -y repomix --style markdown --output repomix-output.md

ai:
	@./bin/agent-links

TAG := $(word 2,$(MAKECMDGOALS))
ifneq ($(TAG),)
$(TAG):
	@:
endif

tag:
	@if [ -z "$(TAG)" ]; then echo "Usage: make tag TAG (где TAG — заголовок раздела в markdown)"; exit 1; fi
	@if [ ! -d "docs/ai" ]; then echo "❌ Error: docs/ai directory not found."; exit 1; fi
	@if [ -z "$$(ls -A docs/ai/*.md 2>/dev/null)" ]; then echo "❌ Error: No markdown files found in docs/ai."; exit 1; fi
	@awk -v tag="$(TAG)" ' \
		function heading_level(line, m) { return match(line, /^(#+)[[:space:]]+/, m) ? length(m[1]) : 0 } \
		function is_tag_heading(text, t, n, c) { \
			n = length(t); \
			if (substr(text, 1, n) != t) return 0; \
			c = substr(text, n + 1, 1); \
			return c == "" || c !~ /[[:alnum:]_]/; \
		} \
		FNR==1 { p=0; level=0 } \
		{ \
			current = heading_level($$0); \
			if (!p) { \
				if (current > 0) { \
					text = substr($$0, current + 2); \
					if (is_tag_heading(text, tag)) { p=1; level=current; print; } \
				} \
				next; \
			} \
			if (current > 0 && current <= level) { p=0; next; } \
			print; \
		} \
	' docs/ai/*.md

# Git

diff d:
	@git diff || true
	@git ls-files -o --exclude-standard -z | xargs -0 -n1 git diff --no-index /dev/null -- 2>/dev/null || true

sdiff:
	@git diff --staged || true
