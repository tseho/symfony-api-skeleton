MAKEFLAGS += --no-print-directory

##
## Environment variables
##

ifneq (,$(wildcard ./.env))
	DOTENV_PATH=/tmp/$(shell echo $$(pwd) | base64)
	include $(shell cat .env | grep -v --perl-regexp '^('$$(env | sed 's/=.*//'g | tr '\n' '|')')\=' | sed 's/=/?=/g' > $(DOTENV_PATH); echo '$(DOTENV_PATH)')
endif

APP_ENV ?= dev
BUILD_ENV ?= ${APP_ENV}
DOCKER_PORT_HTTP ?= 8080

DOCKER_IMAGE_VERSION ?= latest

ifeq ($(APP_ENV), prod)
	COMPOSER_ARGS += --no-dev
endif

# Binaries
DOCKER_COMPOSE = docker-compose
COMPOSER = $(DOCKER_COMPOSE) run --rm --no-deps composer $(COMPOSER_ARGS)
PHP = $(DOCKER_COMPOSE) run --rm --no-deps php

# Export all variables so they are accessible in the shells created by make
export

##
## Entrypoints
##

.PHONY: up
up:
	$(MAKE) build
	$(DOCKER_COMPOSE) up -d --remove-orphan
	$(MAKE) jwt-keys
	@echo "\e[30m\e[42m\n"
	@echo " API is up and running at http://localhost:$(DOCKER_PORT_HTTP)"
	@echo "\e[49m\e[39m\n"

.PHONY: down
down:
	$(DOCKER_COMPOSE) down --remove-orphan

.PHONY: destroy
destroy:
	$(DOCKER_COMPOSE) down --remove-orphan --volumes --rmi local

.PHONY: build
build:
	$(MAKE) .env
	$(DOCKER_COMPOSE) build
	$(MAKE) dependencies
	$(MAKE) cache
	$(PHP) bin/console assets:install public

.PHONY: docker-image
docker-image: APP_ENV=prod
docker-image: PHP_PCOV_ENABLED=0
docker-image: PHP_XDEBUG_MODE=off
docker-image:
	docker build . \
		--build-arg BUILD_ENV=prod \
		--build-arg USER=www-data \
		--target web \
		-t $(DOCKER_IMAGE_NAME):$(DOCKER_IMAGE_VERSION)

##
## Setup
##

.env:
	cp -n .env.dist .env

##
## Dependencies
##

.PHONY: dependencies
dependencies:
	$(COMPOSER) install \
		--no-interaction \
		--no-ansi \
		--prefer-dist \
		--optimize-autoloader

##
## Misc
##

.PHONY: cache
cache:
	$(PHP) rm -rf var/cache
	$(PHP) bin/console cache:warmup

.PHONY: cs-fix
cs-fix:
	$(PHP) vendor/bin/php-cs-fixer fix

.PHONY: jwt-keys
jwt-keys:
	$(PHP) bin/console lexik:jwt:generate-keypair --skip-if-exists
	chmod 660 config/jwt/*.pem

##
## Tests
##

.PHONY: tests
tests: APP_ENV=test
tests: PHP_PCOV_ENABLED=0
tests: PHP_XDEBUG_MODE=off
tests:
	$(MAKE) cache
	$(MAKE) tests-static
	$(MAKE) tests-unit
	$(MAKE) tests-integration

.PHONY: tests-static
tests-static:
	$(PHP) vendor/bin/php-cs-fixer fix --dry-run --diff
	$(PHP) vendor/bin/phpstan analyse --level 8 src
	$(PHP) vendor/bin/phpstan analyse --level 6 tests
	$(PHP) vendor/bin/psalm

.PHONY: tests-unit
tests-unit: APP_ENV=test
tests-unit: PHP_PCOV_ENABLED=1
tests-unit: PHP_XDEBUG_MODE=off
tests-unit:
	$(PHP) vendor/bin/phpunit --testsuite "Unit" \
		--coverage-html coverage/unit/ \
		--coverage-clover coverage/unit/coverage.xml

.PHONY: tests-integration
tests-integration: APP_ENV=test
tests-integration: PHP_PCOV_ENABLED=1
tests-integration: PHP_XDEBUG_MODE=off
tests-integration:
	$(PHP) vendor/bin/phpunit --testsuite "Integration" \
		--coverage-html coverage/integration/ \
		--coverage-clover coverage/integration/coverage.xml
