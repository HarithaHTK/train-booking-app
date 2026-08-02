#!/bin/sh
set -eu

cd /app

if [ ! -d node_modules ] || [ ! -f node_modules/.bin/vite ]; then
  npm install --no-fund --no-audit
fi

exec npm run dev -- --host 0.0.0.0 --port 5173