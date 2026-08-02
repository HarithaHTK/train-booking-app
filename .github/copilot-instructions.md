# Copilot Instructions for Train Booking App

## Project structure
This repository is a monorepo with three main parts:

- `backend/` — Laravel 13 API server
- `portal/` — Vue 3 + Vite frontend for members
- `admin/` — Vue 3 + Vite frontend for admins

The root README describes the overall architecture. Keep the three apps independent unless a change is explicitly meant to affect all of them.

## Application boundaries

### Backend
The backend is the source of truth for authentication, token issuance, and role data.
It uses:

- Laravel Sanctum for token auth
- Spatie Laravel Permission for roles
- a custom `RequireRole` middleware for role-based API protection

### Portal frontend
The portal is the member-facing app.
It supports:

- registration
- login
- dashboard access after auth

It must continue using its own auth storage keys and route guards.

### Admin frontend
The admin app is login-only.
It must not gain a registration flow.
It must continue using its own auth storage keys and route guards.

## User roles
The current role model is:

- `admin` — can access the admin app and admin-only API endpoints
- `member` — can access the portal app and member dashboard flow
- `guest` — seeded role, but not used as an authenticated frontend access role
- `superadmin` — a user that has both `admin` and `member` roles

If you add or change features, do not rename these roles or change their meaning without an explicit requirement.

## Auth and RBAC contracts that must not change

Treat the following as stable contracts:

- login/register endpoints and response shape
- token-based auth with Sanctum
- role names and role checks
- route guard behavior in both frontends
- redirect behavior for unauthorized or wrong-role users
- seeded default users and roles

Never change auth flow or RBAC as part of unrelated work.
If a task touches auth, roles, tokens, middleware, seeders, or guarded routes, review the existing flow first and preserve it unless the user explicitly asks for a change.

## Backend auth flow
The backend auth flow currently works like this:

- `POST /api/register` creates a user and returns a token
- `POST /api/login` validates credentials, deletes old tokens, creates a new token, and returns the user plus roles
- `POST /api/logout` deletes the current user's tokens
- `GET /api/me` returns the authenticated user and roles
- `GET /api/dashboard` is admin-only and protected by `RequireRole:admin`

The backend user model uses the `HasRoles` trait. Preserve that trait and the token auth setup.

## Frontend auth rules

### Portal
- uses `auth_token` and `auth_user` in localStorage
- login/register pages should remain member-only for successful access
- wrong-role users must be rejected and redirected out of protected routes
- dashboard routes remain protected by route meta and guard logic

### Admin
- uses `admin_auth_token` and `admin_auth_user` in localStorage
- login should remain admin-only
- admin routes must remain protected by route meta and guard logic
- no registration route should be added

## Seed data expectations
The seeder currently ensures these accounts exist:

- admin user with `admin` role
- member user with `member` role
- superadmin user with both `admin` and `member` roles

Preserve idempotent seeding behavior. Do not break default credentials or role assignment behavior unless the user explicitly requests it.

## Files to protect carefully
If you change anything related to auth or RBAC, inspect these files first:

- `backend/routes/api.php`
- `backend/app/Http/Controllers/AuthController.php`
- `backend/app/Http/Middleware/RequireRole.php`
- `backend/app/Models/User.php`
- `backend/database/seeders/DatabaseSeeder.php`
- `backend/config/auth.php`
- `backend/config/permission.php`
- `portal/src/api/auth.ts`
- `portal/src/router/index.ts`
- `portal/src/views/auth/LoginView.vue`
- `portal/src/views/auth/RegisterView.vue`
- `admin/src/api/auth.ts`
- `admin/src/router/index.ts`
- `admin/src/views/auth/LoginView.vue`

## Change guidelines
When implementing new features:

1. Prefer minimal changes.
2. Keep auth and RBAC behavior unchanged unless the task is explicitly about auth.
3. Do not repurpose the existing login, register, token, or role checks.
4. Do not change localStorage keys or route guard logic casually.
5. Keep portal and admin concerns separate.
6. If a change introduces new roles or permissions, update the backend first and then the frontends.

## Validation mindset
Before finishing any auth-related change, verify that:

- the backend still issues and consumes tokens correctly
- the correct role still gates each app
- portal and admin still reject the wrong role
- seeded users still get the intended roles
- builds and tests still pass

## Default expectation for Copilot
Unless the task explicitly says otherwise, assume:

- backend auth and RBAC must remain stable
- portal is member-facing
- admin is admin-only
- superadmin has access to both apps
- new work should not alter auth flow or authorization rules
