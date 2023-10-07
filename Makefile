install:
	@docker compose run --rm -it php composer install

phpunit:
	@docker compose run --rm -it php composer test

coverage:
	@docker compose run --rm -it php composer test-coverage

phpstan:
	@docker compose run --rm -it php composer stan

cs-check:
	@docker compose run --rm -it php composer cs-check

cs-fix:
	@docker compose run --rm -it php composer cs-fix

test: cs-check phpstan phpunit
