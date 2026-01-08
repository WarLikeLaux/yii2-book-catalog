.PHONY: help install install-force init setup env configure perms clean \
        up down restart logs shell tinker sms-logs \
        composer req require req-dev require-dev \
        dev _dev_full _dev_file fix ci check pr lint lint-fix rector rector-fix analyze deptrac arkitect arch audit \
        test test-unit test-integration test-e2e cov coverage test-coverage infection inf load-test \
        migrate seed db-mysql db-pgsql db-info db-fresh queue-info \
        docs swagger repomix tree comments ai \
        diff d dc ds diff-staged diff-cached tag

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
	@echo "  arch             🏛️  Архитектурная проверка (Deptrac + Arkitect)"
	@echo "  check            🛡️  Экспресс-проверка (dev + arch + test)"
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
	@echo "  db-fresh         🚨 Полный сброс БД (fresh + seed)"
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

init-force:
	@chmod +x bin/*
	@./bin/bootstrap init-force

init:
	@chmod +x bin/*
	@./bin/bootstrap init

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
	@./bin/fix-perms

setup:
	@chmod +x bin/*
	@./bin/bootstrap setup

configure:
	@chmod +x bin/*
	@./bin/bootstrap configure

env:
	@chmod +x bin/*
	@./bin/setup-env

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

diff d:
	@git diff || true
	@git ls-files -o --exclude-standard -z | xargs -0 -r -I{} git diff --no-index /dev/null {} || true

diff-staged diff-cached ds dc:
	@git diff --staged || true
check: dev arch test
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

arkitect:
	$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/phparkitect check

_arch-deptrac:
	./vendor/bin/deptrac analyze

_arch-arkitect:
	./vendor/bin/phparkitect check

arch: deptrac arkitect

audit:
	$(COMPOSE) exec $(PHP_CONTAINER) composer audit

# =================================================================================================
# 🧪 ТЕСТЫ
# =================================================================================================

_test-init:
	@DB_DRIVER=$(DB_DRIVER) DB_TEST_NAME=$(DB_TEST_NAME) COMPOSE="$(COMPOSE)" ./bin/test-db-prepare

test: _test-init
	@echo "🚀 Запуск всех тестов с генерацией отчетов..."
	@$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/codecept run integration,unit \
		--ext DotReporter \
		--coverage-text --coverage-xml --coverage-html \
		--coverage-phpunit --xml=junit.xml --no-colors
	@sed -i 's|/app/|$(CURDIR)/|g' tests/_output/coverage.xml
	@$(MAKE) cov

test-unit:
	@echo "🚀 Запуск Unit тестов..."
	@$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/codecept run unit --ext DotReporter --no-colors

test-integration: _test-init
	@echo "🚀 Запуск Integration тестов..."
	@$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/codecept run integration --ext DotReporter --no-colors

test-e2e: _test-init
	@echo "🚀 Запуск E2E тестов..."
	@$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/codecept run e2e --ext DotReporter --no-colors

test-coverage coverage cov:
	@if [ ! -f tests/_output/coverage.xml ]; then $(MAKE) test; fi
	@./bin/coverage-report

test-infection infection inf:
	@if [ ! -f tests/_output/coverage-phpunit.xml ]; then $(MAKE) test; fi
	@echo "🧟 Запуск мутационного тестирования..."
	@$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/infection --coverage=tests/_output --threads=max --test-framework-options="integration,unit"

test-load:
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
	@./bin/agent-links

TAG := $(word 2,$(MAKECMDGOALS))
ifneq ($(TAG),)
$(TAG):
	@:
endif

tag:
	@if [ -z "$(TAG)" ]; then echo "Usage: make tag TAG"; exit 1; fi
	@awk -v tag="$(TAG)" 'BEGIN{p=0} $$0 ~ "^### "tag {p=1} $$0 ~ "^### " && $$0 !~ "^### "tag {p=0} p' docs/ai/*.md
