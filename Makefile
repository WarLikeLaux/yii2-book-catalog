.PHONY: init up down restart composer lint lint-fix migrate seed shell perms copy-env sms-logs queue-info logs test test-coverage test-unit test-functional docs repomix analyze deptrac rector rector-fix infection ci pr dev fix audit swagger load-test

COMPOSE=docker compose
PHP_CONTAINER=php
QUEUE_CONTAINER=queue
DB_TEST_NAME=yii2basic_test

include .env
export

init: perms copy-env up composer migrate seed
	@echo "🚀 Project initialized and running at http://localhost:8000"

up:
	$(COMPOSE) up -d

down:
	$(COMPOSE) down

restart: down up

composer:
	$(COMPOSE) exec $(PHP_CONTAINER) composer install

lint:
	$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/phpcs

lint-fix:
	$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/phpcbf

analyze:
	$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/phpstan analyse --memory-limit=2G

deptrac:
	$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/deptrac analyze

migrate:
	$(COMPOSE) exec $(PHP_CONTAINER) ./yii migrate --interactive=0

seed:
	$(COMPOSE) exec $(PHP_CONTAINER) ./yii seed

shell:
	$(COMPOSE) exec $(PHP_CONTAINER) sh

perms:
	mkdir -p web/uploads runtime/debug runtime/logs runtime/cache
	$(COMPOSE) exec -T -u root $(PHP_CONTAINER) chmod -R 777 /app/runtime /app/web/uploads

copy-env:
	@if [ ! -f .env ]; then cp .env.example .env; echo "✅ .env created"; fi

sms-logs:
	$(COMPOSE) exec $(PHP_CONTAINER) tail -f runtime/logs/sms.log

queue-info:
	$(COMPOSE) exec $(PHP_CONTAINER) ./yii queue/info

logs:
	$(COMPOSE) logs -f

_test-init:
	@echo "🔧 Preparing test database..."
	@$(COMPOSE) exec -T db sh -c 'mysql -uroot -p"$${MYSQL_ROOT_PASSWORD}" -h127.0.0.1 -e "CREATE DATABASE IF NOT EXISTS $(DB_TEST_NAME); GRANT ALL PRIVILEGES ON $(DB_TEST_NAME).* TO \"$${MYSQL_USER}\"@\"%\"; FLUSH PRIVILEGES;"' 2>&1 | grep -v "Using a password" || true
	@$(COMPOSE) exec -T $(PHP_CONTAINER) sh -c "DB_NAME=$(DB_TEST_NAME) ./yii migrate --interactive=0 --migrationPath=@app/migrations" > /dev/null

test: _test-init
	@echo "🚀 Running all tests..."
	@$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/codecept run functional,unit --no-colors

test-coverage: _test-init
	@echo "📊 Running tests with coverage..."
	@$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/codecept run functional,unit --coverage --coverage-html --coverage-text
	@echo ""
	@echo "═══════════════════════════════════════"
	@$(COMPOSE) exec $(PHP_CONTAINER) cat tests/_output/coverage.txt 2>/dev/null | head -12 | tail -8 || echo "See HTML report: tests/_output/coverage/index.html"
	@echo "═══════════════════════════════════════"

test-unit:
	@$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/codecept run unit

test-functional: _test-init
	@$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/codecept run functional

docs:
	@$(COMPOSE) exec $(PHP_CONTAINER) ./yii docs/all

repomix:
	@command -v npx >/dev/null 2>&1 || { echo "❌ npx not found. Install Node.js first"; exit 1; }
	@echo "📦 Generating repomix output..."
	@npx -y repomix --style markdown --output repomix-output.md
	@echo "✅ Created repomix-output.md"

infection: _test-init
	$(COMPOSE) exec $(PHP_CONTAINER) mkdir -p runtime/coverage
	$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/codecept run functional,unit --no-colors --coverage-xml=coverage.xml --coverage-phpunit=coverage-phpunit.xml --xml=junit.xml -o "paths: output: runtime/coverage"
	$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/infection --coverage=runtime/coverage --threads=1 --test-framework-options="functional,unit"

swagger:
	$(COMPOSE) exec $(PHP_CONTAINER) php docs/api/generate.php

load-test:
	@echo "🚀 Running k6 load test..."
	$(COMPOSE) run --rm k6 run /scripts/smoke.js

rector:
	$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/rector process --dry-run

rector-fix:
	$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/rector process

audit:
	$(COMPOSE) exec $(PHP_CONTAINER) composer audit

fix: lint-fix rector-fix

ci: lint analyze test

dev: fix ci

pr: ci deptrac infection audit
