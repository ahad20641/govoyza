#!/usr/bin/env bash
# Asks for a password twice without showing it, then passes it to a govoyza
# password command via --password-stdin. One line to run, nothing to paste after it.
#
# Run from the app folder (staging-app or app):
#   bash deploy/password.sh reset-password you@example.com
#   bash deploy/password.sh staging-password
#   bash deploy/password.sh create-admin --name="Your Name" --email=you@example.com --role=owner
set -euo pipefail
cd "$(dirname "$0")/.."

case "${1:-}" in
  reset-password|staging-password|create-admin) ;;
  *) echo "Usage: bash deploy/password.sh reset-password|staging-password|create-admin [args]" >&2; exit 2 ;;
esac
cmd="$1"; shift

# Read from the terminal itself, so pasted text can never be taken as the password.
read -rs -p 'Password: ' p1 </dev/tty; echo
read -rs -p 'Password again: ' p2 </dev/tty; echo

printf '%s\n%s\n' "$p1" "$p2" | php artisan "govoyza:$cmd" "$@" --password-stdin
