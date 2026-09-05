# CI/CD for Miniflux v1 Legacy

## Overview

This document describes the Continuous Integration and Deployment pipeline for the Miniflux v1 legacy fork.

## Features

- **Multi-PHP Support**: Tests run on PHP 7.4, 8.0, 8.1, 8.2
- **Multi-Database Support**: SQLite, PostgreSQL, MySQL
- **Automatic Triggers**: Runs on push to master/main/feature/**/bugfix/** branches
- **Security Scanning**: Composer audit for vulnerabilities
- **Docker Build**: Automatic Docker image builds on successful tests and master push

## How to Use

### Local Testing

```bash
# Unit tests (SQLite)
./vendor/bin/phpunit -c tests/phpunit.unit.sqlite.xml

# Functional tests
./vendor/bin/phpunit -c tests/phpunit.functional.sqlite.xml

# All tests
make test
```

### GitHub Secrets Required

For Docker builds, set in repository Settings → Secrets & variables → Actions:
- `DOCKER_USERNAME`
- `DOCKER_PASSWORD`

## Workflow Jobs

| Job | Description |
|-----|-------------|
| `php-tests` | Unit tests across PHP 7.4-8.2 and all databases |
| `functional-tests` | Functional API tests with SQLite |
| `security-scan` | Vulnerability scanning |
| `build-docker` | Build and push Docker image (master only) |