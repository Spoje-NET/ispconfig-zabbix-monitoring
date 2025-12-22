# vim: set tabstop=8 softtabstop=8 noexpandtab:
.PHONY: help
help: ## Displays this list of targets with descriptions
	@grep -E '^[a-zA-Z0-9_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}'

.PHONY: static-code-analysis
static-code-analysis: vendor ## Runs a static code analysis with phpstan/phpstan
	vendor/bin/phpstan analyse --configuration=phpstan-default.neon.dist --memory-limit=-1

.PHONY: static-code-analysis-baseline
static-code-analysis-baseline: check-symfony vendor ## Generates a baseline for static code analysis with phpstan/phpstan
	symfony php vendor/bin/phpstan analyze --configuration=phpstan-default.neon.dist --generate-baseline=phpstan-default-baseline.neon --memory-limit=-1

.PHONY: tests
tests: vendor
	symfony php vendor/bin/phpunit tests

.PHONY: vendor
vendor: composer.json ## Installs composer dependencies
	composer install

.PHONY: cs
cs:
	vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php --diff --verbose

.PHONY: phpunit
phpunit:
	vendor/bin/phpunit

.PHONY: test
test: vendor ## Run PHPUnit tests
	vendor/bin/phpunit

.PHONY: test-coverage
test-coverage: vendor ## Run PHPUnit tests with coverage
	vendor/bin/phpunit --coverage-html tests/coverage

.PHONY: test-ci
test-ci: vendor ## Run PHPUnit tests for CI
	vendor/bin/phpunit --coverage-text --colors=never

companylld:
	cd src; php company_lld.php
