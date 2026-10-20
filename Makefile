PROJECT_NAME := italy-cookie-choices
COMPOSE := docker-compose -f .docker/docker-compose.yml --env-file .docker/test.env
CONTAINER := $(PROJECT_NAME)_test
PLUGIN_DIR := /var/www/html/wp-content/plugins/$(PROJECT_NAME)
EXEC := docker exec --user www-data -w $(PLUGIN_DIR) $(CONTAINER)
EXEC_TTY := docker exec -it --user www-data -w $(PLUGIN_DIR) $(CONTAINER)

# Extra arguments, e.g. make composer ARGS="show -D" or make e2e ARGS="--debug"
ARGS ?=

.DEFAULT_GOAL := help

.PHONY: help
help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-18s\033[0m %s\n", $$1, $$2}'

.PHONY: up
up: ## Start the containers
	$(COMPOSE) up -d

.PHONY: down
down: ## Stop and remove the containers
	$(COMPOSE) down

.PHONY: build
build: ## Rebuild the WordPress image
	$(COMPOSE) build wordpress

.PHONY: shell
shell: ## Open a shell in the WordPress container
	$(EXEC_TTY) bash

.PHONY: composer
composer: ## Run Composer in the container, pass the command with ARGS
	$(EXEC) composer $(ARGS)

.PHONY: install
install: ## Install the Composer dependencies
	$(EXEC) composer install

.PHONY: codecept
codecept: ## Run Codeception in the container, pass the command with ARGS
	$(EXEC) vendor/bin/codecept $(ARGS)

.PHONY: build-tests
build-tests: ## Generate the Codeception actor classes
	$(EXEC) vendor/bin/codecept build

.PHONY: integration
integration: ## Run the integration suite
	$(EXEC) vendor/bin/codecept run integration $(ARGS)

.PHONY: e2e
e2e: ## Run the e2e suite in Chrome, watch it at http://localhost:7900
	$(EXEC) vendor/bin/codecept run e2e $(ARGS)

.PHONY: tests
tests: integration e2e ## Run all the suites

.PHONY: e2e-dump
e2e-dump: ## Rebuild tests/_data/dump.sql from a clean install
	$(EXEC) sh .docker/e2e-dump

.PHONY: infection
infection: ## Run the mutation tests on the integration suite
	$(EXEC) vendor/bin/infection --threads=4 $(ARGS)

.PHONY: clean
clean: ## Remove the Codeception output
	$(EXEC) vendor/bin/codecept clean
