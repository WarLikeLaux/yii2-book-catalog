COMPOSE=docker compose
PHP_CONTAINER=php
QUEUE_CONTAINER=queue

include .env
export

init: perms copy-env up composer migrate seed
	@echo "🚀 Project initialized and running at http://localhost:8000"

up:
	$(COMPOSE) up -d

down:
	$(COMPOSE) down

restart: down up

restart: down up

composer:
	$(COMPOSE) exec $(PHP_CONTAINER) composer install

lint:
	$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/phpcs

lint-fix:
	$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/phpcbf

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

test-db-create:
	@echo "Creating test database..."
	@$(COMPOSE) exec -T db sh -c 'mysql -uroot -p"$${MYSQL_ROOT_PASSWORD}" -h127.0.0.1 -e "CREATE DATABASE IF NOT EXISTS yii2basic_test; GRANT ALL PRIVILEGES ON yii2basic_test.* TO \"$${MYSQL_USER}\"@\"%\"; FLUSH PRIVILEGES;"' 2>&1 | grep -v "Using a password" || true
	@echo "✅ Test database created"

test-db-migrate:
	@echo "Running migrations for test database..."
	@$(COMPOSE) exec -T $(PHP_CONTAINER) sh -c "DB_TEST_NAME=yii2basic_test ./yii migrate --interactive=0 --migrationPath=@app/migrations"

test-init: test-db-create test-db-migrate
	@echo "✅ Test database initialized"

test:
	$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/codecept run functional usecases

test-unit:
	$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/codecept run unit

test-integration:
	$(COMPOSE) exec $(PHP_CONTAINER) ./vendor/bin/codecept run functional usecases
