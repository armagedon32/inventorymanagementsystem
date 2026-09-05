#!/usr/bin/env bash
# Deploy script for the AWS EC2 (Amazon Linux) host.
# Runs on the server; invoked automatically by GitHub Actions (aws-deploy.yml)
# or manually via SSH: bash deploy/aws-deploy.sh
set -euo pipefail

APP_PATH="${APP_PATH:-/home/ec2-user/inventory_custodian}"
BRANCH="${BRANCH:-main}"
APP_NAME="${APP_NAME:-inventory-custodian}"

echo "==> Target: $APP_PATH (branch: $BRANCH)"

if git -C "$APP_PATH" rev-parse --is-inside-work-tree >/dev/null 2>&1; then
  cd "$APP_PATH"
  echo "==> git fetch + reset --hard origin/$BRANCH"
  git fetch --all --prune
  git reset --hard "origin/$BRANCH"
else
  echo "ERROR: '$APP_PATH' is not a git repository." >&2
  echo "Point APP_PATH to the folder where the app is cloned." >&2
  exit 1
fi

echo "==> npm install"
npm install --no-audit --no-fund
echo "==> npm run build"
npm run build

echo "==> Restarting service"
RESTARTED=0

if command -v pm2 >/dev/null 2>&1; then
  if pm2 jlist 2>/dev/null | grep -q "\"name\":\"$APP_NAME\""; then
    pm2 restart "$APP_NAME" --update-env
  else
    pm2 start server/index.js --name "$APP_NAME"
  fi
  pm2 save >/dev/null 2>&1 || true
  RESTARTED=1
fi

if [ "$RESTARTED" = "0" ] && systemctl list-unit-files 2>/dev/null | grep -q "$APP_NAME"; then
  sudo systemctl restart "$APP_NAME" || systemctl --user restart "$APP_NAME" || true
  RESTARTED=1
fi

if [ "$RESTARTED" = "0" ]; then
  pkill -f "server/index.js" || true
  sleep 1
  nohup node server/index.js >> server/app.log 2>&1 &
  RESTARTED=1
fi

sleep 3
echo "==> Health check"
curl -sf http://localhost:5000/api/health && echo "OK" || echo "WARN: health check failed"