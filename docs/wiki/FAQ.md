# TSiSIP FAQ

## General

### What is TSiSIP?
TSiSIP is a Docker-image-first SIP edge-proxy platform built on OpenSIPS 3.6 LTS.

### What does it do?
It acts as the public SIP signaling entry point and security boundary for private Asterisk PBX backends.

### Is it free?
TSiSIP is proprietary software. Contact us for licensing.

## Installation

### What are the requirements?
- Docker 24.0+
- Docker Compose 2.20+
- 4GB RAM
- 20GB disk

### How do I install?
```bash
./scripts/install.sh
```

### How do I update?
```bash
./scripts/update.sh
```

## Usage

### How do I login?
Default credentials:
- Username: `admin`
- Password: `admin123`

### How do I change my password?
Go to Profile > Change Password.

### How do I switch themes?
Click the moon/sun icon in the header or go to Profile.

### How do I change language?
Click EN/ES/PT in the header or go to Profile.

### How do I bookmark a page?
Click the star icon (☆) in the header.

## Troubleshooting

### Why can't I login?
- Check caps lock
- Account may be locked after 5 failed attempts
- Wait 15 minutes

### Why is the page slow?
- Check network connectivity
- Verify OpenSIPS MI is reachable
- Clear browser cache

### Why is data not updating?
- Check SSE connection indicator
- Refresh the page
- Check OpenSIPS status

## API

### How do I use the API?
See [API Reference](OCP-API-REFERENCE.md).

### Do I need authentication?
Yes, all endpoints require a valid session.

### What formats are supported?
JSON and CSV for exports.

## Support

### How do I get help?
- User Guide
- Admin Guide
- Troubleshooting Guide
- Email: devops@tsiapp.io

### How do I report a bug?
Use the Feedback page or email support.

### How do I request a feature?
Use the Feedback page or contact us.
