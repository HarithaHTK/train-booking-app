<template>
  <div class="home-container">
    <!-- Header with Navigation -->
    <div class="home-header">
      <div class="brand">
        <p class="eyebrow">Train Booking App</p>
        <h1>Book your next journey with confidence</h1>
      </div>
    </div>

    <!-- Hero Card -->
    <Card class="hero-card">
      <template #content>
        <p class="intro-label">Project overview</p>
        <h2>Simple train booking platform</h2>
        <p class="description">
          The portal gives passengers a clean experience to register, log in, and access their
          personal dashboard. Authentication is handled by the Laravel backend, while the Vue
          frontend talks only to API endpoints and token-based auth.
        </p>

        <!-- Feature Grid -->
        <div class="feature-grid">
          <Card class="feature-card">
            <template #content>
              <h3>Secure access</h3>
              <p>Register, log in, and log out through backend-issued tokens.</p>
            </template>
          </Card>

          <Card class="feature-card">
            <template #content>
              <h3>Clean UI</h3>
              <p>Public pages are separated from authenticated dashboard views.</p>
            </template>
          </Card>

          <Card class="feature-card">
            <template #content>
              <h3>Scalable structure</h3>
              <p>Views are organized into auth, main, and public folders for future growth.</p>
            </template>
          </Card>
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import Card from 'primevue/card'
</script>

<style scoped>
.home-container {
  min-height: 100vh;
  padding: 2rem;
  background:
    radial-gradient(circle at top right, var(--accent-light), transparent 30%),
    linear-gradient(135deg, var(--bg-secondary), var(--bg-tertiary));
  transition: background-color 0.3s ease;
}

.home-header {
  display: grid;
  gap: 0.75rem;
  max-width: 1100px;
  margin: 0 auto 2rem;
}

.brand h1 {
  margin: 0;
  font-size: clamp(2rem, 4vw, 3.5rem);
  line-height: 1.1;
  color: var(--text-primary);
}

.eyebrow {
  margin: 0 0 0.5rem;
  text-transform: uppercase;
  letter-spacing: 0.28em;
  font-size: 0.75rem;
  color: var(--accent-primary);
  transition: color 0.3s ease;
}

.hero-card {
  max-width: 1100px;
  margin: 0 auto;
  transition: all 0.3s ease;
}

:deep(.p-card) {
  background: var(--panel-bg);
  border: 1px solid var(--border-color);
  color: var(--text-primary);
}

.intro-label {
  margin: 0 0 0.5rem 0;
  color: var(--accent-primary);
  text-transform: uppercase;
  letter-spacing: 0.2em;
  font-size: 0.78rem;
  transition: color 0.3s ease;
}

.hero-card h2 {
  margin: 0 0 1rem;
  font-size: clamp(1.6rem, 3vw, 2.4rem);
  color: var(--text-primary);
}

.description {
  max-width: 70ch;
  line-height: 1.7;
  color: var(--text-secondary);
  margin: 0 0 2rem;
}

.feature-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 1.5rem;
}

.feature-card h3 {
  margin: 0 0 0.5rem;
  color: var(--text-primary);
}

.feature-card p {
  margin: 0;
  color: var(--text-secondary);
  line-height: 1.6;
}

@media (max-width: 900px) {
  .feature-grid {
    grid-template-columns: 1fr;
  }
}
</style>
