# Contributing to TSiSIP

## Getting Started

1. Fork the repository
2. Clone your fork
3. Create a feature branch
4. Make your changes
5. Submit a pull request

## Development Setup

```bash
# Install dependencies
docker compose build

# Start development environment
docker compose up -d

# Run tests
make test
```

## Code Style

- PHP: PSR-12
- JavaScript: Standard
- CSS: BEM methodology
- SQL: lowercase keywords

## Commit Messages

Follow Conventional Commits:
```
feat: Add new feature
fix: Fix bug
docs: Update documentation
test: Add tests
chore: Maintenance task
```

## Testing

- All changes must include tests
- Integration tests in `tests/integration/`
- Run `make test` before submitting

## Documentation

- Update README for user-facing changes
- Update admin guide for infrastructure changes
- Update API reference for endpoint changes

## Review Process

1. Automated tests must pass
2. Code review by maintainers
3. Documentation review
4. Merge by maintainers

## Questions?

- GitHub Discussions
- Email: devops@tsiapp.io
