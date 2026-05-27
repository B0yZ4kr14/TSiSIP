# TSiSIP 1.0.0 Release Notes

## Release Date
2026-05-27

## Highlights

### New Features
- Complete OCP rebrand with premium theme
- Dark mode support
- Internationalization (English, Spanish, Portuguese)
- Mobile responsive design
- Real-time updates via Server-Sent Events
- Customizable dashboard widgets
- Global search across all data
- User profile with preferences
- System health dashboard
- System reports with export
- API documentation
- Cache management
- Bookmark system
- Toast notifications
- Personal notes
- Feedback system
- Theme color presets
- Keyboard shortcuts
- Guided tour

### Infrastructure
- Docker Compose multi-network topology
- PostgreSQL schema with extensions
- MI HTTP wrapper with circuit breaker
- Backup and restore scripts
- System monitor
- Maintenance automation
- GitHub Actions CI
- Pre-commit hooks

### Documentation
- User guide
- Admin guide
- API reference and examples
- Deployment guide
- Troubleshooting guide
- Security hardening guide
- Performance guide
- Monitoring guide
- Backup and recovery guide

## Known Issues
- WebSocket support planned for Q3
- PWA support planned for Q3
- Advanced analytics planned for Q3

## Upgrade Notes
Run migrations:
```bash
for f in db/init/*.sql; do
    docker compose exec postgres psql -U opensips -d opensips -f "$f"
done
```

## Compatibility
- Docker 24.0+
- Docker Compose 2.20+
- PostgreSQL 15+
- OpenSIPS 3.6 LTS

## Support
- Documentation: docs/
- Issues: GitHub
- Email: devops@tsiapp.io

## Thank You
To all contributors and users!
