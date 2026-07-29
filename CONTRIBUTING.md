# Contributing to cryptopanic-php

Thank you for your interest in contributing! Please follow these guidelines to ensure a smooth process.

## Development Setup

1. Clone the repository.
2. Run `composer install`.
3. Run `composer test` to verify the test suite passes.

## Code Style

- Use `declare(strict_types=1);` in every PHP file.
- Follow PSR-12 coding standard.
- Never log, expose, or embed the auth token in error messages, URLs, or test output.

## Testing

- Add tests for all new functionality.
- Use mocked transport for unit tests.
- Ensure tests are fast and deterministic — use the custom sleeper to control retry timing.
- Run `composer test` before submitting.

## Static Analysis

- Run `composer analyse` (PHPStan) and fix any issues.

## Pull Request Process

1. Fork the repository and create a feature branch.
2. Write tests for your changes.
3. Ensure `composer validate --strict`, `composer test`, and `composer analyse` all pass.
4. Update the CHANGELOG.md if applicable.
5. Submit a pull request with a clear description of the changes.

## Security

- Never commit real API credentials.
- Never hardcode tokens in examples or tests.
- Report security vulnerabilities to sovletig@gmail.com — do not open public issues.
