.PHONY: archive
.PHONY: docker-image
.PHONY: js
.PHONY: unit-test-sqlite
.PHONY: unit-test-postgres
.PHONY: unit-test-mysql
.PHONY: all-tests
.PHONY: test
.PHONY: sync-locales
.PHONY: find-locales

# Default test target
test:
	@$(MAKE) unit-test-sqlite

# Run all unit tests across all database backends
all-tests: unit-test-sqlite unit-test-postgres unit-test-mysql
	@echo "All unit tests passed!"

# Run functional tests
functional-tests:
	@rm -f data/db.sqlite
	@./vendor/bin/phpunit -c tests/phpunit.functional.sqlite.xml

# Run all tests (unit + functional)
full-test: all-tests functional-tests
	@echo "All tests completed!"

# Lint PHP files
lint-php:
	@find . -name "*.php" -not -path "./vendor/*" -not -path "./assets/*" | xargs php -l

# Lint JavaScript files
lint-js:
	@npm install
	@./node_modules/.bin/jshint assets/js/src/*.js

CSS_FILE = assets/css/app.min.css
JS_FILE = assets/js/app.min.js
IMAGE = miniflux/miniflux
TAG = latest

docker-image:
	@ ./hooks/build

css: $(CSS_FILE)

$(CSS_FILE): assets/css/app.css
	@ npm install
	@ cat $^ | ./node_modules/.bin/cleancss -o $@

js: $(JS_FILE)

$(JS_FILE): assets/js/src/app.js \
	assets/js/src/feed.js \
	assets/js/src/item.js \
	assets/js/src/event.js \
	assets/js/src/nav.js
	@ yarn install || npm install
	@ ./node_modules/.bin/jshint assets/js/src/*.js
	@ cat $^ | node_modules/.bin/uglifyjs - > $@
	@ echo "Miniflux.App.Run();" >> $@

# Build a new archive: make archive version=1.2.3 dst=/tmp
archive:
	@ git archive --format=zip --prefix=miniflux/ v${version} -o ${dst}/miniflux-${version}.zip

functional-test-sqlite:
	@ rm -f data/db.sqlite
	@ ./vendor/bin/phpunit -c tests/phpunit.functional.sqlite.xml

unit-test-sqlite:
	@ ./vendor/bin/phpunit -c tests/phpunit.unit.sqlite.xml

unit-test-postgres:
	@ ./vendor/bin/phpunit -c tests/phpunit.unit.postgres.xml

unit-test-mysql:
	@ ./vendor/bin/phpunit -c tests/phpunit.unit.mysql.xml

sync-locales:
	@ php scripts/sync-locales.php

find-locales:
	@ php scripts/find-locales.php
