install:
	composer install

dump:
	composer dump-autoload

lint:
	composer exec --verbose phpcs -- --standard=PSR12 src public

test:
	composer exec --verbose phpunit tests

test-coverage:
	XDEBUG_MODE=coverage
	composer exec --verbose phpunit tests -- --coverage-clover build/logs/clover.xml

PORT ?= 8000
start:
	PHP_CLI_SERVER_WORKERS=5 php -S 0.0.0.0:$(PORT) -t public

start-sql:
	sudo service postgresql start

start-local:
	php -S localhost:8080 -t public public/index.php
