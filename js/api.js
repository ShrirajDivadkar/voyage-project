/**
 * js/api.js  –  Voyage AJAX Client
 * Replaces localStorage-based data.js with real PHP+MySQL calls.
 * All functions are async and return plain objects/arrays (like data.js did).
 */

// Determine the absolute base path for the API
const BASE_URL = (() => {
  if (window.location.protocol === 'file:') return 'http://localhost/voyage-main/api/';
  // Simple relative path is most reliable for this project structure
  return 'api/';
})();

const API = {
  auth:         BASE_URL + 'auth.php',
  destinations: BASE_URL + 'destinations.php',
  bookings:     BASE_URL + 'bookings.php',
  stats:        BASE_URL + 'stats.php',
};

async function _post(url, data) {
  try {
    const res = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data),
      credentials: 'include',
    });
    const text = await res.text();
    try {
      return JSON.parse(text);
    } catch (e) {
      console.error('API Error: Non-JSON response from ' + url, text);
      throw new Error('Server returned invalid data format.');
    }
  } catch (e) {
    console.error('Network/Fetch Error:', e);
    throw e;
  }
}

async function _get(url) {
  try {
    const res = await fetch(url, {
      credentials: 'include'
    });
    const text = await res.text();
    try {
      return JSON.parse(text);
    } catch (e) {
      console.error('API Error: Non-JSON response from ' + url, text);
      throw new Error('Server returned invalid data format.');
    }
  } catch (e) {
    console.error('Network/Fetch Error:', e);
    throw e;
  }
}

// ── AUTH ──────────────────────────────────────────────────────
async function loginUser(email, password) {
  return _post(API.auth, { action: 'login', email, password });
}

async function signupUser(name, email, password) {
  return _post(API.auth, { action: 'signup', name, email, password });
}

async function logoutUser() {
  return _post(API.auth, { action: 'logout' });
}

async function checkSession() {
  return _post(API.auth, { action: 'check' });
}

// ── DESTINATIONS ──────────────────────────────────────────────
async function getDestinations() {
  return _get(API.destinations);
}

async function searchDestinations(query = '', availableOnly = false) {
  const params = new URLSearchParams();
  if (query) params.append('search', query);
  if (availableOnly) params.append('available', '1');
  return _get(API.destinations + '?' + params.toString());
}

async function getAvailableDestinations() {
  return _get(API.destinations + '?available=1');
}

async function getDestinationById(id) {
  return _get(API.destinations + '?id=' + id);
}

async function addDestination(dest) {
  return _post(API.destinations, { action: 'add', ...dest });
}

async function updateDestination(id, updates) {
  return _post(API.destinations, { action: 'update', id, ...updates });
}

async function deleteDestination(id) {
  return _post(API.destinations, { action: 'delete', id });
}

// ── BOOKINGS ──────────────────────────────────────────────────
async function getBookings() {
  return _get(API.bookings);
}

async function addBooking(data) {
  return _post(API.bookings, { action: 'add', ...data });
}

async function cancelBooking(id) {
  return _post(API.bookings, { action: 'cancel', id });
}

// ── STATS ─────────────────────────────────────────────────────
async function getStats() {
  return _get(API.stats);
}
