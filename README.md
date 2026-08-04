# Train Booking App

Train Booking App is a monorepo with a Laravel API backend and two independent Vue frontends:

- [backend/](backend) — Laravel 13 API server
- [portal/](portal) — member-facing Vue 3 + Vite app
- [admin/](admin) — admin-only Vue 3 + Vite app

## Full project scope

This repository covers the core booking workflow for a train reservation platform and is organized around the features currently implemented in the project:

- authentication and token-based session management through Laravel Sanctum
- role-based access control for admin and member users
- route, train, coach, seat, schedule, and reservation workflows
- seat visualization to support booking decisions
- separate member and admin experiences with isolated frontend sessions
- API documentation and Docker-based local development

## Project statement

This project implements the core functionality of a railway ticket booking system. The current release includes the main flows required for route management, train management, schedule management, seat visualization, and ticket booking.

The database is designed for flexibility and extensibility. Administrators can manage key system entities such as routes, trains, coaches, seats, and schedules through the backend API. Some administrative CRUD surfaces are still evolving, so the backend structure supports more complete operations as the project grows.

The system follows a modular architecture with a clear separation between the Laravel backend API and the Vue frontends. This keeps the application easy to maintain and extend, and the backend can support additional clients such as mobile applications, third-party ticket vendors, station master systems, or other external integrations without major architectural changes.

Further implementation details, architectural decisions, setup instructions, and future enhancement plans are documented below and in the app-specific READMEs.

## Planned enhancements

- concurrent seat reservation handling
- fuller administrative CRUD completion
- additional integration-ready client applications
- future workflow refinements for booking operations

## Application flow

### 1) Backend API

The backend is the source of truth for:

- user authentication
- token issuance and revocation
- role assignment and authorization
- business data for trains, schedules, reservations, stations, coaches, and engines

Typical auth flow:

1. A user registers or logs in through the API.
2. The backend validates credentials and resolves the user role.
3. Sanctum issues an API token.
4. The frontend stores the token and user payload in localStorage.
5. Protected API requests include the token.
6. Logout removes the active token.

The backend also exposes a role-protected admin API path and uses seeded users for development.

Common backend areas include:

- route definitions and controllers
- schedule and reservation logic
- entity management for trains, stations, coaches, engines, and seats
- authorization checks using role-aware middleware

### 2) Portal frontend

The portal is the member-facing app.

Primary flow:

1. Visitor opens the portal.
2. Visitor can register or log in.
3. After auth, the user is redirected to the member dashboard.
4. Protected views use route guards and stored auth state.
5. Members can browse booking-related data and manage reservations through the API.

Portal responsibilities include:

- member registration and login
- dashboard access after authentication
- browsing schedules and trip options
- viewing seat availability and booking-related information
- keeping portal auth separate from admin auth

Portal storage keys remain isolated:

- token: `auth_token`
- user: `auth_user`

### 3) Admin frontend

The admin app is login-only and must remain separate from the portal.

Primary flow:

1. Admin opens the admin app.
2. Admin logs in through the shared backend auth API.
3. On success, the app stores its own token and user state.
4. Protected admin routes remain accessible only to users with the correct role.
5. Admins manage operational data such as trains, stations, schedules, coaches, engines, and reservations.

Admin responsibilities include:

- login only, with no registration flow
- management of operational railway data
- maintaining backend-configured entities such as routes, trains, coaches, seats, and schedules
- enforcing admin-only route access

Admin storage keys remain isolated:

- token: `admin_auth_token`
- user: `admin_auth_user`

## Roles and access

The current role model is:

- `admin` — access to the admin app and admin-only API endpoints
- `member` — access to the portal app and member dashboard flow
- `guest` — seeded role, not used as an authenticated frontend access role
- `superadmin` — a user that has both `admin` and `member` roles

Access rules:

- portal accepts `member` and `superadmin`
- admin accepts `admin` and `superadmin`
- wrong-role users are rejected by login and route guards

## Repository structure

### backend/

Laravel API responsibilities include:

- auth endpoints
- role and permission handling
- domain controllers and services
- API documentation
- database migrations, factories, and seeders

### portal/

Vue member app responsibilities include:

- registration and login
- dashboard access
- booking and account-facing features
- portal-specific auth storage and route guarding

### admin/

Vue admin app responsibilities include:

- login only
- operational management UI
- admin-specific auth storage and route guarding
- no registration flow

## Local development

### Backend

```bash
cd backend
php artisan serve --host 127.0.0.1 --port 8000
```

Health endpoint:

```bash
curl http://127.0.0.1:8000/api/health
```

### Portal

```bash
cd portal
npm run dev -- --host 127.0.0.1 --port 5173
```

Open http://127.0.0.1:5173.

### Admin

```bash
cd admin
npm run dev -- --host 127.0.0.1 --port 5174
```

Open http://127.0.0.1:5174.

## Docker Compose flow

From the repository root:

```bash
docker compose up --build
```

This starts:

- MySQL database container
- Laravel backend on http://localhost:8000
- Portal app on http://localhost:5173
- Admin app on http://localhost:5174

Startup flow:

1. Docker brings up the database.
2. The backend container runs migrations and seeds.
3. The frontends start and proxy `/api` requests to the backend.
4. Seeded users are available for immediate login.

Default seeded users:

- Admin only
  - Email: admin@email.com
  - Password: Password@123
  - Role: admin
- Member only
  - Email: member@email.com
  - Password: Password@123
  - Role: member
- Superadmin
  - Email: superadmin@email.com
  - Password: Password@123
  - Roles: admin, member

## API documentation

Documentation is available through Swagger/OpenAPI outputs in the backend, including the generated JSON under [backend/public/docs/](backend/public/docs) and the UI routes exposed by the API.

## Future direction

The current codebase is structured so the booking workflow can grow without reworking the foundation. That means the project can later add stronger seat locking, richer administrative workflows, and integrations for external clients while preserving the existing backend-first design.

## Key behavior guarantees

- portal and admin sessions stay isolated
- auth remains token-based through Sanctum
- role checks remain enforced on both frontend and backend
- default seeded accounts are recreated on clean startup
- admin stays login-only
- portal keeps registration and member onboarding

## Related docs

- Backend overview: [backend/README.md](backend/README.md)
- Portal starter notes: [portal/README.md](portal/README.md)
- Admin starter notes: [admin/README.md](admin/README.md)
