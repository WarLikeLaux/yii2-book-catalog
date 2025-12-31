.PHONY: help init up down restart logs shell sms-logs perms setup env configure clean composer dev fix ci lint lint-fix rector rector-fix analyze deptrac audit test test-e2e test-coverage infection load-test migrate seed queue-info comments docs swagger repomix

COMPOSE=docker compose
PHP_CONTAINER=php
DB_TEST_NAME=yii2basic_test
.DEFAULT_GOAL := help

# Загружаем переменные из .env, если он существует, чтобы Makefile видел их
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
	@echo "🚀 \033[1;32mБЫСТРЫЙ СТАРТ:\033[0m"
	@echo "  \033[32minstall\033[0m          📥 Установить и запустить проект (рекомендуется)"
	@echo "  \033[32minstall-force\033[0m    📥 Принудительная установка без вопросов (CI/CD)"
	@echo "  \033[32minit\033[0m             ⚙️  Интерактивная инициализация"
	@echo ""
	@echo "🛡️  \033[1;35mКОНТРОЛЬ КАЧЕСТВА (ADVANCED QA):\033[0m"
	@echo "  \033[35mtest\033[0m             ✅ Запуск всех тестов (Unit + Integration + E2E)"
	@echo "  \033[35mtest-unit\033[0m        ⚡ Только Unit-тесты (Быстрые)"
	@echo "  \033[35mtest-integration\033[0m 🌐 Только Integration-тесты (С БД)"
	@echo "  \033[35mtest-e2e\033[0m         🎭 Только E2E-тесты (Acceptance)"
	@echo "  \033[35minfection\033[0m        🧟 \033[1mМутационное тестирование\033[0m"
	@echo "  \033[35mdeptrac\033[0m          🏗️  Архитектурный анализ"
	@echo "  \033[35manalyze\033[0m          🔍 Статический анализ (PHPStan Level 9)"
	@echo "  \033[35maudit\033[0m            🛡️  Аудит безопасности зависимостей"
	@echo "  \033[35mpr\033[0m               🚀 Полная проверка перед Pull Request (All of the above)"
	@echo ""
	@echo "💻 \033[1;33mРАЗРАБОТКА:\033[0m"
	@echo "  \033[33mdev\033[0m              🛠️  Стандартный цикл (fix + test)"
	@echo "  \033[33mfix\033[0m              🧹 Авто-исправление стиля кода (CS-Fixer + Rector)"
	@echo "  \033[33mcomments\033[0m         📝 Показать TODO и заметки"
	@echo ""
	@echo "🐳 \033[1;34mDOCKER & OPS:\033[0m"
	@echo "  \033[34mup\033[0m               ▶️  Запустить контейнеры"
	@echo "  \033[34mdown\033[0m             ⏹️  Остановить контейнеры"
	@echo "  \033[34mlogs\033[0m             📄 Смотреть логи"
	@echo "  \033[34mshell\033[0m            🐚 Зайти в контейнер PHP"
	@echo ""
	@echo "📚 \033[1;36mДОКУМЕНТАЦИЯ:\033[0m"
	@echo "  \033[36mdocs\033[0m             📑 Генерация Yii2 API Docs"
	@echo "  \033[36mswagger\033[0m          🌐 Генерация OpenAPI/Swagger"
	@echo "  \033[36mrepomix\033[0m          🤖 Сборка контекста для LLM"

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

init: _init_confirm setup up composer migrate seed
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
	@echo "  2. 🐳 Пересоздание и запуск контейнеров (docker compose up)"
	@echo "  3. 📦 Установка зависимостей (composer install)"
	@echo "  4. 🗄  Применение миграций и заливка тестовых данных (seed)"
	@echo ""
	@read -p "   Вы готовы продолжить? [y/N] " ans; \
	if [ "$$ans" != "y" ] && [ "$$ans" != "Y" ]; then \
		echo "❌ Отменено пользователем."; \
		exit 1; \
	fi

up:
	$(COMPOSE) up -d --remove-orphans

down:
	$(COMPOSE) down

restart: down up

logs:
	$(COMPOSE) logs -f

shell:
	$(COMPOSE) exec $(PHP_CONTAINER) sh

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

setup: perms _mkdirs
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

# =================================================================================================
# 🛡️ КОНТРОЛЬ КАЧЕСТВА (QA)
# =================================================================================================

dev: fix ci
fix: lint-fix rector-fix
ci: lint analyze
pr: ci test deptrac infection

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
	@echo "🔧 Подготовка тестовой базы..."
	@$(COMPOSE) exec -T db sh -c 'mysql -uroot -p"$${MYSQL_ROOT_PASSWORD}" -h127.0.0.1 -e "CREATE DATABASE IF NOT EXISTS $(DB_TEST_NAME); GRANT ALL PRIVILEGES ON $(DB_TEST_NAME).* TO \"$${MYSQL_USER}\"@\"%\"; FLUSH PRIVILEGES;"' 2>&1 | grep -v "Using a password" || true
	@$(COMPOSE) exec -T $(PHP_CONTAINER) sh -c "DB_NAME=$(DB_TEST_NAME) ./yii migrate --interactive=0 --migrationPath=@app/migrations" > /dev/null

test: _test-init
	@echo "🚀 Запуск всех тестов с генерацией отчетов..."
	@$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/codecept run integration,unit \
		--coverage --coverage-xml --coverage-html --coverage-text \
		--coverage-phpunit --xml=junit.xml --no-colors
	@sed -i 's|/app/|$(CURDIR)/|g' tests/_output/coverage.xml

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

# =================================================================================================
# 📚 ДОКУМЕНТАЦИЯ И УТИЛИТЫ
# =================================================================================================

queue-info:
	$(COMPOSE) exec $(PHP_CONTAINER) ./yii queue/info

comments:
	@./bin/list-comments

docs:
	@$(COMPOSE) exec $(PHP_CONTAINER) ./yii docs/all

swagger:
	$(COMPOSE) exec $(PHP_CONTAINER) php docs/api/generate.php

repomix:
	@npx -y repomix --style markdown --output repomix-output.md
