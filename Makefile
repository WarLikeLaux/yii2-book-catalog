.PHONY: init up down restart composer lint lint-fix migrate seed shell perms copy-env sms-logs queue-info logs test test-coverage test-unit test-functional test-acceptance docs repomix analyze deptrac

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

# Internal: Prepare test database (create if not exists & migrate)
_test-init:
	@echo "🔧 Preparing test database..."
	@$(COMPOSE) exec -T db sh -c 'mysql -uroot -p"$${MYSQL_ROOT_PASSWORD}" -h127.0.0.1 -e "CREATE DATABASE IF NOT EXISTS $(DB_TEST_NAME); GRANT ALL PRIVILEGES ON $(DB_TEST_NAME).* TO \"$${MYSQL_USER}\"@\"%\"; FLUSH PRIVILEGES;"' 2>&1 | grep -v "Using a password" || true
	@$(COMPOSE) exec -T $(PHP_CONTAINER) sh -c "DB_NAME=$(DB_TEST_NAME) ./yii migrate --interactive=0 --migrationPath=@app/migrations" > /dev/null

test: _test-init
	@echo "🚀 Running all tests..."
	@$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/codecept run functional,unit --no-colors

test-coverage: _test-init
	@echo "📊 Running tests with coverage..."
	@$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/codecept run functional,unit --coverage-html

test-unit:
	@$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/codecept run unit

test-functional: _test-init
	@$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/codecept run functional

test-acceptance:
	@$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/codecept run acceptance

docs:
	@$(COMPOSE) exec $(PHP_CONTAINER) ./yii docs/all

repomix:
	@command -v npx >/dev/null 2>&1 || { echo "❌ npx not found. Install Node.js first"; exit 1; }
	@echo "📦 Generating repomix output..."
	@npx -y repomix --style markdown --output repomix-output.md
	@echo "✅ Created repomix-output.md"
