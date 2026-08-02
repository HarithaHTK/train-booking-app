# Train Booking App

This workspace contains:

- `backend/` — Laravel 13 API server
- `portal/` — Vue 3 + Vite frontend

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

## Run with Docker Compose

From the repository root:

```bash
docker compose up --build
```

This starts:

- MySQL database in a separate container
- Laravel backend on http://localhost:8000
- Vue/Vite frontend on http://localhost:5173

The frontend proxies `/api` requests to the backend container, so the browser can talk to the API without extra configuration.

Default login after the first startup:

- Email: admin@email.com
- Password: Password@123

Notes:

- The backend container runs migrations and seeds automatically on startup.
- If you reset the volumes, the default admin account will be created again on the next `docker compose up`.
