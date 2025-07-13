# https://makefile.site

.DEFAULT_GOAL : help
# This will output the help for each task. thanks to https://marmelab.com/blog/2016/02/29/auto-documented-makefile.html
help: ## Show this help
	@printf "\033[33m%s:\033[0m\n" 'Available commands'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z0-9_-]+:.*?## / {printf "  \033[32m%-18s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

ARGS = $(filter-out $@,$(MAKECMDGOALS))
%:
 @:

update: ## Update openrouter models
	php ./update_openrouter_models.php

test-integration: ## Run tests
	./vendor/phpunit tests/Integration/ --verbose --testdox