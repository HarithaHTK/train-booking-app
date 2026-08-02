# Admin Frontend

This is a standalone Vue 3 + TypeScript + Vite app for the admin panel.

- Login only (no registration route)
- Root route (`/`) goes directly to login
- Uses backend auth API via `/api` proxy
- Admin and super admin accounts can sign in; member accounts are rejected at login

## Local development

```bash
npm install
npm run dev -- --host 127.0.0.1 --port 5174
```

## Build

```bash
npm run build
```
