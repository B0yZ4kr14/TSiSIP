#!/usr/bin/env bash
# TSiSIP Welcome
set -euo pipefail

cat << 'WELCOME'
╔═══════════════════════════════════════╗
║                                       ║
║   Welcome to TSiSIP!                  ║
║                                       ║
║   The SIP Edge Proxy Platform         ║
║                                       ║
╚═══════════════════════════════════════╝

Getting Started:
  make install    - Install TSiSIP
  make up         - Start services
  make test       - Run tests
  make help       - Show all commands

Documentation:
  docs/wiki/README.md

Support:
  devops@tsiapp.io

Happy SIPping! 🚀
WELCOME
