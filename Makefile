COMPOSE=docker compose
PHP_CONTAINER=php
QUEUE_CONTAINER=queue

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
