<template>
  <main class="home-shell">
    <header class="home-header">
      <div class="brand">
        <p class="eyebrow">Train Booking App</p>
        <h1>Book your next journey with confidence</h1>
      </div>

      <nav class="actions" aria-label="Authentication actions">
        <router-link :to="{ name: 'auth-login' }" class="ghost-button link-button">Login</router-link>
        <router-link :to="{ name: 'auth-register' }" class="primary-button link-button">
          Register
        </router-link>
      </nav>
    </header>

    <section class="hero-card">
      <p class="intro-label">Project overview</p>
      <h2>Simple train booking platform</h2>
      <p class="description">
        The portal gives passengers a clean experience to register, log in, and access their
        personal dashboard. Authentication is handled by the Laravel backend, while the Vue
        frontend talks only to API endpoints and token-based auth.
      </p>

      <div class="feature-grid">
        <article>
          <h3>Secure access</h3>
          <p>Register, log in, and log out through backend-issued tokens.</p>
        </article>
        <article>
          <h3>Clean UI</h3>
          <p>Public pages are separated from authenticated dashboard views.</p>
        </article>
        <article>
          <h3>Scalable structure</h3>
          <p>Views are organized into auth, main, and public folders for future growth.</p>
        </article>
      </div>
    </section>
  </main>
</template>

<style scoped>
.home-shell {
  min-height: 100vh;
  padding: 2rem;
  color: #f8fafc;
  background:
    radial-gradient(circle at top right, rgba(56, 189, 248, 0.18), transparent 30%),
    linear-gradient(135deg, #0f172a, #111827);
}

.home-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
  max-width: 1100px;
  margin: 0 auto 2rem;
}

.brand h1 {
  margin: 0;
  font-size: clamp(2rem, 4vw, 3.5rem);
  line-height: 1.1;
}

.eyebrow {
  margin: 0 0 0.5rem;
  text-transform: uppercase;
  letter-spacing: 0.28em;
  font-size: 0.75rem;
  color: #38bdf8;
}

.actions {
  display: flex;
  gap: 0.75rem;
  align-items: center;
  margin-left: auto;
}

.primary-button,
.ghost-button {
  border: 0;
  border-radius: 999px;
  padding: 0.85rem 1.2rem;
  font-weight: 700;
  cursor: pointer;
  text-decoration: none;
}

.primary-button {
  background: #38bdf8;
  color: #082f49;
}

.ghost-button {
  background: rgba(148, 163, 184, 0.18);
  color: #e2e8f0;
}

.link-button {
  display: inline-flex;
  align-items: center;
}

.hero-card {
  max-width: 1100px;
  margin: 0 auto;
  padding: 2.5rem;
  border-radius: 28px;
  background: rgba(15, 23, 42, 0.82);
  border: 1px solid rgba(148, 163, 184, 0.2);
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.35);
}

.intro-label {
  margin: 0 0 0.5rem;
  color: #93c5fd;
  text-transform: uppercase;
  letter-spacing: 0.2em;
  font-size: 0.78rem;
}

.hero-card h2 {
  margin: 0 0 1rem;
  font-size: clamp(1.6rem, 3vw, 2.4rem);
}

.description {
  max-width: 70ch;
  line-height: 1.7;
  color: #cbd5e1;
}

.feature-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 1rem;
  margin-top: 2rem;
}

.feature-grid article {
  padding: 1.25rem;
  border-radius: 18px;
  background: rgba(56, 189, 248, 0.08);
  border: 1px solid rgba(56, 189, 248, 0.12);
}

.feature-grid h3 {
  margin: 0 0 0.5rem;
}

.feature-grid p {
  margin: 0;
  color: #cbd5e1;
  line-height: 1.6;
}

@media (max-width: 900px) {
  .home-header,
  .feature-grid {
    grid-template-columns: 1fr;
    flex-direction: column;
  }

  .actions {
    margin-left: 0;
  }
}
</style>
