.PHONY: up down test lint kphp-check shell

up:
	docker compose up -d

down:
	docker compose down

shell:
	docker compose exec php sh

test:
	docker compose exec php ./vendor/bin/phpunit

lint:
	docker compose exec php ./vendor/bin/phpstan analyse

kphp-check:
	docker build -f Dockerfile.check -t lphenom-storage-check .

