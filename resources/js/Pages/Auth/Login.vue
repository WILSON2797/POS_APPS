<script setup>
import { ref } from 'vue'
import { useForm, Head } from '@inertiajs/vue3'

const form = useForm({
  email: '',
  password: '',
  remember: false,
})

const showPassword = ref(false)

const togglePassword = () => {
  showPassword.value = !showPassword.value
}

const submit = () => {
  form.post('/login', {
    onFinish: () => form.reset('password'),
  })
}
</script>

<template>
  <Head title="Login" />
  <div class="login-wrapper">
    <div class="login-card">
      <div class="card-accent-line"></div>
      
      <!-- Logo & Branding -->
      <div class="login-header">
        <div class="brand-logo">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="logo-svg">
            <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
            <line x1="8" y1="21" x2="16" y2="21"></line>
            <line x1="12" y1="17" x2="12" y2="21"></line>
            <path d="M17 9H7"></path>
          </svg>
        </div>
        <h1 class="brand-title">POS Apps</h1>
        <p class="brand-subtitle">Selamat datang kembali! Silakan masuk untuk mengelola toko Anda.</p>
      </div>

      <!-- Form -->
      <form @submit.prevent="submit" class="login-form">
        <!-- Error Alert -->
        <transition name="fade">
          <div v-if="form.errors.email || form.errors.password" class="error-alert">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="alert-icon">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="12" y1="8" x2="12" y2="12"></line>
              <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <span class="alert-text">{{ form.errors.email || form.errors.password }}</span>
          </div>
        </transition>

        <!-- Input Email -->
        <div class="form-group">
          <label for="email" class="form-label">Alamat Email</label>
          <div class="input-container">
            <span class="input-icon-left">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                <polyline points="22,6 12,13 2,6"></polyline>
              </svg>
            </span>
            <input
              id="email"
              v-model="form.email"
              type="email"
              placeholder="nama@email.com"
              autocomplete="email"
              required
              class="form-input"
            />
          </div>
        </div>

        <!-- Input Password -->
        <div class="form-group">
          <div class="label-wrapper">
            <label for="password" class="form-label">Password</label>
          </div>
          <div class="input-container">
            <span class="input-icon-left">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
              </svg>
            </span>
            <input
              id="password"
              v-model="form.password"
              :type="showPassword ? 'text' : 'password'"
              placeholder="Masukkan password Anda"
              autocomplete="current-password"
              required
              class="form-input"
            />
            <button type="button" @click="togglePassword" class="password-toggle-btn" aria-label="Toggle Password Visibility">
              <svg v-if="showPassword" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-icon">
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                <line x1="1" y1="1" x2="23" y2="23"></line>
              </svg>
              <svg v-else xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-icon">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                <circle cx="12" cy="12" r="3"></circle>
              </svg>
            </button>
          </div>
        </div>

        <!-- Remember Me Checkbox -->
        <div class="form-actions">
          <label class="remember-checkbox-label">
            <input type="checkbox" v-model="form.remember" class="remember-checkbox-input" />
            <span class="remember-checkbox-custom"></span>
            <span class="remember-text">Ingat saya di perangkat ini</span>
          </label>
        </div>

        <!-- Submit Button -->
        <button
          type="submit"
          class="submit-button"
          :disabled="form.processing"
        >
          <div v-if="form.processing" class="spinner">
            <div class="double-bounce1"></div>
            <div class="double-bounce2"></div>
          </div>
          <span v-else>Masuk ke Aplikasi</span>
        </button>
      </form>
    </div>
  </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');

.login-wrapper {
  font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
  min-height: 100vh;
  display: flex;
  justify-content: center;
  align-items: center;
  background-color: #f8fafc;
  background-image: 
    radial-gradient(at 0% 0%, rgba(99, 102, 241, 0.03) 0px, transparent 50%),
    radial-gradient(at 100% 100%, rgba(168, 85, 247, 0.03) 0px, transparent 50%);
  padding: 1rem;
  box-sizing: border-box;
}

.login-card {
  width: 100%;
  max-width: 350px;
  background: #ffffff;
  border-radius: 12px;
  box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.02), 0 10px 25px -5px rgba(15, 23, 42, 0.03);
  border: 1px solid rgba(226, 232, 240, 0.7);
  position: relative;
  overflow: hidden;
  padding: 1.75rem 1.5rem;
  box-sizing: border-box;
}

.card-accent-line {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: linear-gradient(90deg, #4f46e5 0%, #6366f1 50%, #8b5cf6 100%);
}

.login-header {
  text-align: center;
  margin-bottom: 1.25rem;
}

.brand-logo {
  width: 44px;
  height: 44px;
  background: linear-gradient(135deg, rgba(79, 70, 229, 0.08) 0%, rgba(139, 92, 246, 0.08) 100%);
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #4f46e5;
  margin: 0 auto 0.75rem;
}

.logo-svg {
  width: 22px;
  height: 22px;
}

.brand-title {
  font-size: 1.25rem;
  font-weight: 800;
  color: #0f172a;
  margin: 0 0 0.25rem 0;
  letter-spacing: -0.02em;
}

.brand-subtitle {
  font-size: 0.775rem;
  color: #64748b;
  line-height: 1.4;
  margin: 0;
}

.login-form {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}

.error-alert {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background-color: #fef2f2;
  border: 1px solid #fca5a5;
  border-radius: 8px;
  padding: 0.5rem 0.75rem;
  color: #b91c1c;
}

.alert-icon {
  flex-shrink: 0;
  width: 16px;
  height: 16px;
}

.alert-text {
  font-size: 0.75rem;
  font-weight: 500;
  line-height: 1.3;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.form-label {
  font-size: 0.775rem;
  font-weight: 600;
  color: #334155;
  margin: 0;
}

.input-container {
  position: relative;
  display: flex;
  align-items: center;
}

.input-icon-left {
  position: absolute;
  left: 0.75rem;
  color: #94a3b8;
  display: flex;
  align-items: center;
  justify-content: center;
  pointer-events: none;
  transition: color 0.2s ease;
}

.form-input {
  width: 100%;
  padding: 0.55rem 0.75rem 0.55rem 2.25rem;
  font-size: 0.825rem;
  font-weight: 500;
  color: #0f172a;
  background-color: #f8fafc;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  box-sizing: border-box;
  transition: all 0.2s ease;
}

.form-input::placeholder {
  color: #94a3b8;
}

.form-input:focus {
  outline: none;
  background-color: #ffffff;
  border-color: #4f46e5;
  box-shadow: 0 0 0 2.5px rgba(79, 70, 229, 0.08);
}

.form-input:focus + .input-icon-left {
  color: #4f46e5;
}

.password-toggle-btn {
  position: absolute;
  right: 0.75rem;
  background: none;
  border: none;
  color: #94a3b8;
  cursor: pointer;
  padding: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: color 0.2s ease;
}

.password-toggle-btn:hover {
  color: #4f46e5;
}

.eye-icon {
  width: 16px;
  height: 16px;
}

.form-actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 0.1rem;
}

.remember-checkbox-label {
  display: flex;
  align-items: center;
  cursor: pointer;
  user-select: none;
  gap: 0.4rem;
}

.remember-checkbox-input {
  position: absolute;
  opacity: 0;
  cursor: pointer;
  height: 0;
  width: 0;
}

.remember-checkbox-custom {
  height: 14px;
  width: 14px;
  background-color: #f1f5f9;
  border: 1px solid #cbd5e1;
  border-radius: 4px;
  display: inline-block;
  position: relative;
  transition: all 0.2s ease;
}

.remember-checkbox-label:hover .remember-checkbox-custom {
  border-color: #4f46e5;
}

.remember-checkbox-input:checked ~ .remember-checkbox-custom {
  background-color: #4f46e5;
  border-color: #4f46e5;
}

.remember-checkbox-custom::after {
  content: "";
  position: absolute;
  display: none;
  left: 3.5px;
  top: 1px;
  width: 3.5px;
  height: 6px;
  border: solid white;
  border-width: 0 2px 2px 0;
  transform: rotate(45deg);
}

.remember-checkbox-input:checked ~ .remember-checkbox-custom::after {
  display: block;
}

.remember-text {
  font-size: 0.75rem;
  font-weight: 500;
  color: #64748b;
}

.submit-button {
  width: 100%;
  padding: 0.6rem;
  font-size: 0.825rem;
  font-weight: 700;
  color: #ffffff;
  background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
  border: none;
  border-radius: 8px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  box-shadow: 0 3px 8px rgba(79, 70, 229, 0.15);
  margin-top: 0.15rem;
}

.submit-button:hover {
  transform: translateY(-0.5px);
  box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2);
  background: linear-gradient(135deg, #4338ca 0%, #4f46e5 100%);
}

.submit-button:active {
  transform: translateY(0.5px);
  box-shadow: 0 2px 4px rgba(79, 70, 229, 0.1);
}

.submit-button:disabled {
  background: #cbd5e1;
  color: #94a3b8;
  cursor: not-allowed;
  box-shadow: none;
  transform: none;
}

/* Custom CSS Spinner */
.spinner {
  width: 16px;
  height: 16px;
  position: relative;
}

.double-bounce1, .double-bounce2 {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  background-color: #ffffff;
  opacity: 0.6;
  position: absolute;
  top: 0;
  left: 0;
  animation: sk-bounce 2.0s infinite ease-in-out;
}

.double-bounce2 {
  animation-delay: -1.0s;
}

@keyframes sk-bounce {
  0%, 100% { 
    transform: scale(0.0);
  } 50% { 
    transform: scale(1.0);
  }
}

/* Animations */
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s ease, transform 0.3s ease;
}

.fade-enter-from, .fade-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

/* Mobile Responsive Optimization */
@media (max-width: 480px) {
  .login-wrapper {
    padding: 0.75rem;
  }
  
  .login-card {
    padding: 1.5rem 1.25rem;
    border-radius: 10px;
    box-shadow: 0 2px 12px -2px rgba(15, 23, 42, 0.01), 0 6px 15px -4px rgba(15, 23, 42, 0.02);
  }

  .form-input {
    padding: 0.5rem 0.75rem 0.5rem 2.15rem;
    font-size: 0.8rem;
  }

  .input-icon-left {
    left: 0.7rem;
  }

  .password-toggle-btn {
    right: 0.7rem;
  }

  .submit-button {
    padding: 0.55rem;
    font-size: 0.8rem;
  }
}
</style>
