.PHONY: install autoload serve test phpstan lint lint-fix db-setup migrate migrate-rollback migrate-status \
        config-cache config-clear route-cache route-clear route-list npm-install dev build \
        worker \
        docker-build docker-up docker-down docker-restart docker-shell docker-logs \
        docker-migrate docker-test docker-mysql docker-memcached docker-redis docker-worker-logs

install:
	composer install

npm-install:
	npm install

dev:
	@pids=$$(lsof -tiTCP:5173,5174,5175,5176,5177 -sTCP:LISTEN 2>/dev/null); \
	if [ -n "$$pids" ]; then \
		echo "Stopping Vite process(es): $$pids"; \
		kill $$pids 2>/dev/null || true; \
		sleep 1; \
	fi
	@rm -f public/build/hot
	npm run dev

build:
	npm run build

autoload:
	composer dump-autoload

worker:
	php bin/console queue:work --sleep=5

serve:
	@pids=$$(lsof -tiTCP:8080 -sTCP:LISTEN 2>/dev/null); \
	if [ -n "$$pids" ]; then \
		echo "Stopping process(es) on 127.0.0.1:8080: $$pids"; \
		kill $$pids; \
		sleep 1; \
	fi
	php -S 127.0.0.1:8080 -t public public/index.php

test:
	vendor/bin/phpunit

phpstan:
	vendor/bin/phpstan analyse --memory-limit=512M

lint:
	vendor/bin/pint --test

lint-fix:
	vendor/bin/pint

db-setup:
	php bin/setup.php

migrate:
	php bin/console migrate

migrate-rollback:
	php bin/console migrate:rollback

migrate-status:
	php bin/console migrate:status

config-cache:
	php bin/console config:cache

config-clear:
	php bin/console config:clear

route-cache:
	php bin/console route:cache

route-clear:
	php bin/console route:clear

route-list:
	php bin/console route:list

# ── Docker ───────────────────────────────────────────────────────────────────

docker-build:
	docker compose build

docker-up:
	docker compose up -d

docker-down:
	docker compose down

docker-restart:
	docker compose restart app nginx

docker-shell:
	docker compose exec app sh

docker-logs:
	docker compose logs -f

docker-migrate:
	docker compose exec app php bin/console migrate

docker-test:
	docker compose exec app vendor/bin/phpunit

# Запуск с MySQL (+ Memcached опционально)
docker-mysql:
	docker compose --profile mysql up -d

docker-memcached:
	docker compose --profile mysql --profile memcached up -d

docker-redis:
	docker compose --profile mysql --profile redis up -d

docker-worker-logs:
	docker compose logs -f worker
