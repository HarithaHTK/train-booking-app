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
