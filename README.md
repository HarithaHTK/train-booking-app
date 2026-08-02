# Train Booking App

This workspace contains:

- `backend/` — Laravel 13 API server
- `portal/` — Vue 3 + Vite frontend
- `admin/` — Vue 3 + Vite admin frontend (login only)

## Architecture Overview

The application is split into **two separate frontend apps**:

### Portal (`portal/`)
- User-facing application for booking and account management
- Supports **registration**, login, and dashboard access
- Runs on **port 5173** (local dev) or via Docker service `portal`
- Browser tab title: **Train Booking**

### Admin (`admin/`)
- Admin-only interface for system management
- **Login-only** (no registration route; direct route `/` goes to login)
- Runs on **port 5174** (local dev) or via Docker service `admin`
- Browser tab title: **admin - Train Booking**

### Session Isolation

Portal and Admin are completely independent:
- **Separate browser storage keys**: portal uses `auth_token`, admin uses `admin_auth_token`
- **Different roots**: `/` in portal → Home view, `/` in admin → Login view
- **No interference**: You can log in/out in both apps simultaneously in the same browser session without affecting each other

## Run the backend

```bash
cd backend
php artisan serve --host 127.0.0.1 --port 8000
```

Health endpoint:

```bash
curl http://127.0.0.1:8000/api/health
```

## API Documentation

View Swagger/OpenAPI documentation:

- **Swagger UI**: http://127.0.0.1:8000/api/docs
- **OpenAPI JSON**: http://127.0.0.1:8000/api/docs/swagger.json
- **L5-Swagger UI** (alternative): http://127.0.0.1:8000/api/documentation (requires manual generation)

The documentation is generated from `backend/public/docs/swagger.json` and can be updated by editing that file or adding OpenAPI annotations to controllers.

## Run the frontend

```bash
cd portal
npm run dev -- --host 127.0.0.1 --port 5173
```

Open http://127.0.0.1:5173 to view the portal.

## Run the admin frontend

```bash
cd admin
npm run dev -- --host 127.0.0.1 --port 5174
```

Open http://127.0.0.1:5174 to view the admin app.

## Run with Docker Compose

From the repository root:

```bash
docker compose up --build
```

This starts:

- MySQL database in a separate container
- Laravel backend on http://localhost:8000
- Vue/Vite portal on http://localhost:5173
- Vue/Vite admin app on http://localhost:5174

All frontends proxy `/api` requests to the backend, and both can authenticate independently using the same server and token-based auth system.

Default login after the first startup:

- Email: admin@email.com
- Password: Password@123

Both portal and admin apps can log in with the same credentials. After login, each maintains its own session using isolated storage keys.

Notes:

- The backend container runs migrations and seeds automatically on startup.
- If you reset the volumes, the default admin account will be created again on the next `docker compose up`.
