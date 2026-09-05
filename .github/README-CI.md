# CI/CD for Miniflux v1 Legacy

## Overview

This document describes the Continuous Integration and Deployment pipeline for the Miniflux v1 legacy fork.

## Features

- **Multi-PHP Support**: Tests run on PHP 7.3, 7.4, 8.0, 8.1, 8.2
- **Multi-Database Support**: SQLite, PostgreSQL, MySQL
- **Automatic Triggers**: Runs on push to master/main/feature/**/bugfix/** branches
- **Security Scanning**: Dependency checks and vulnerability scanning
- **Docker Build**: Automatic Docker image builds on successful tests

## Quick Start

```bash
# Run tests locally
make test
make full-test
```

## GitHub Secrets Required

For Docker builds, set in repository Settings → Secrets:
- `DOCKER_USERNAME`
- `DOCKER_PASSWORD`