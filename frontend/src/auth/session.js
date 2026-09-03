import { computed, reactive } from 'vue'
import { apiRequest, TOKEN_KEY } from '@/api/client'

const state = reactive({
  token: localStorage.getItem(TOKEN_KEY),
  user: null,
  initialized: false,
})

let initialization = null

function storeToken(token) {
  state.token = token
  localStorage.setItem(TOKEN_KEY, token)
}

function clearSession() {
  state.token = null
  state.user = null
  localStorage.removeItem(TOKEN_KEY)
}

async function fetchCurrentUser() {
  state.user = await apiRequest('/auth/me')
  return state.user
}

async function initialize() {
  if (state.initialized) return
  if (initialization) return initialization

  initialization = (async () => {
    if (state.token) {
      try {
        await fetchCurrentUser()
      } catch {
        clearSession()
      }
    }
    state.initialized = true
  })()

  return initialization
}

async function login(email, password) {
  const result = await apiRequest('/auth/login', {
    method: 'POST',
    body: { email, password },
    authenticated: false,
  })
  storeToken(result.token)
  await fetchCurrentUser()
  return state.user
}

async function register(email, password) {
  await apiRequest('/auth/register', {
    method: 'POST',
    body: { email, password },
    authenticated: false,
  })
  return login(email, password)
}

function logout() {
  clearSession()
}

window.addEventListener('surveyflow:session-expired', clearSession)

export const auth = {
  state,
  isAuthenticated: computed(() => Boolean(state.token)),
  initialize,
  login,
  register,
  logout,
  fetchCurrentUser,
}
