/**
 * js/auth-nav.js  – Voyage Session + Dynamic Navbar
 *
 * Include AFTER js/api.js on every page.
 * This script:
 *   1. Calls checkSession() to see if the user is logged in.
 *   2. Rewrites every <nav> / mobile-menu AUTH anchor cluster
 *      (Login / Signup) into a user-greeting + Logout.
 *   3. Stores session info in sessionStorage for quick use.
 *   4. Exposes window.VoyageAuth = { user, role, logout() }.
 */

(async function initAuthNav() {

  /* ── 1. Try to load cached session first (fast) ──────────── */
  let session = null;
  try {
    const cached = sessionStorage.getItem('voyage_session');
    if (cached) session = JSON.parse(cached);
  } catch (_) {}

  /* ── 2. Verify / refresh via API ─────────────────────────── */
  try {
    const res = await checkSession();
    if (res && res.success) {
      session = { role: res.role, name: res.name };
      sessionStorage.setItem('voyage_session', JSON.stringify(session));
    } else {
      session = null;
      sessionStorage.removeItem('voyage_session');
    }
  } catch (_) {
    /* server unreachable – keep cached session if any */
  }

  /* ── 3. Expose globally ───────────────────────────────────── */
  window.VoyageAuth = {
    user: session ? session.name : null,
    role: session ? session.role : null,
    isLoggedIn: !!session,
    async logout() {
      try { await logoutUser(); } catch (_) {}
      sessionStorage.removeItem('voyage_session');
      window.VoyageAuth.user = null;
      window.VoyageAuth.role = null;
      window.VoyageAuth.isLoggedIn = false;
      window.location.href = 'index.html';
    }
  };

  if (!session) return; // not logged in – leave nav as-is

  /* ── 4. Replace Login / Signup links in all navs ─────────── */
  _patchNav(session);

})();

/* ──────────────────────────────────────────────────────────────
   _patchNav: finds Login+Signup anchors and replaces them with
   greeting + logout across desktop nav AND mobile menu.
   Works for both the pattern in most pages (login.html link
   adjacent to signup link) and index.html's double-ul pattern.
───────────────────────────────────────────────────────────────── */
function _patchNav(session) {
  const firstName = (session.name || 'Traveller').split(' ')[0];
  const isAdmin   = session.role === 'admin';

  /* The greeting HTML injected in place of login/signup */
  function greetingHTML(flex) {
    const adminLink = isAdmin
      ? `<a href="admin.html" class="text-indigo-600 hover:text-indigo-800 font-semibold transition-colors duration-200 ${flex ? '' : 'block px-3 py-2'}">⚙️ Admin</a>`
      : '';
    const bookingsLink = !isAdmin
      ? `<a href="my-bookings.html" class="text-slate-600 hover:text-orange-500 font-semibold transition-colors duration-200 ${flex ? '' : 'block px-3 py-2'}">🎒 My Bookings</a>`
      : '';
    return `
      ${adminLink}
      ${bookingsLink}
      <span class="font-medium text-orange-500 ${flex ? '' : 'block px-3 py-2'}">👋 ${firstName}</span>
      <button onclick="window.VoyageAuth.logout()"
        class="mat-btn mat-btn-outlined text-sm ${flex ? '' : 'block w-full text-center mt-1'}"
        style="padding:8px 20px;font-size:0.82rem;">
        Logout
      </button>
    `;
  }

  /* ── Desktop nav ──────────────────────────────────────────── */
  // Pattern A: <a href="login.html">  immediately followed by  <a href="signup.html">
  const allAnchors = Array.from(document.querySelectorAll('nav a'));
  const loginA  = allAnchors.find(a => a.getAttribute('href') === 'login.html');
  const signupA = allAnchors.find(a => a.getAttribute('href') === 'signup.html');

  if (loginA && signupA) {
    /* Wrap replacement in a flex span */
    const wrapper = document.createElement('span');
    wrapper.className = 'flex items-center gap-4';
    wrapper.innerHTML = greetingHTML(true);
    loginA.replaceWith(wrapper);
    signupA.remove();
  }

  /* ── index.html: double <ul> pattern inside a div ── */
  // Login is in a <li> inside the second <ul>, Signup is the next <li>
  const liLogin  = document.querySelector('header ul li a[href="login.html"]');
  const liSignup = document.querySelector('header ul li a[href="signup.html"]');
  if (liLogin && liSignup && liLogin !== loginA) {
    const wrapLi = document.createElement('li');
    wrapLi.className = 'flex items-center gap-4';
    wrapLi.innerHTML = greetingHTML(true);
    liLogin.closest('li').replaceWith(wrapLi);
    liSignup.closest('li').remove();
  }

  /* ── Mobile menu ──────────────────────────────────────────── */
  // id="mob-menu" pattern
  const mobMenu = document.getElementById('mob-menu');
  if (mobMenu) {
    const mLoginA  = mobMenu.querySelector('a[href="login.html"]');
    const mSignupA = mobMenu.querySelector('a[href="signup.html"]');
    if (mLoginA && mSignupA) {
      const mWrap = document.createElement('div');
      mWrap.className = 'flex flex-col gap-2 pt-2';
      mWrap.innerHTML = greetingHTML(false);
      mLoginA.replaceWith(mWrap);
      mSignupA.remove();
    }
  }

  // index.html mobile menu id="mobile-menu" pattern
  const mobileMenu = document.getElementById('mobile-menu');
  if (mobileMenu) {
    const mL = mobileMenu.querySelector('a[href="login.html"]');
    const mS = mobileMenu.querySelector('a[href="signup.html"]');
    if (mL && mS) {
      const mW = document.createElement('div');
      mW.className = 'flex flex-col gap-2 pt-2 border-t border-gray-100 mt-4';
      mW.innerHTML = isAdmin
        ? `<a href="admin.html" class="block rounded-lg px-3 py-2.5 text-base font-semibold text-indigo-600 hover:bg-indigo-50">⚙️ Admin Dashboard</a>` 
        : `<a href="my-bookings.html" class="block rounded-lg px-3 py-2.5 text-base font-semibold text-slate-700 hover:bg-slate-50">🎒 My Bookings</a>`;
      mW.innerHTML += `
        <span class="block rounded-lg px-3 py-2 text-base font-semibold text-orange-500">👋 ${firstName}</span>
        <button onclick="window.VoyageAuth.logout()"
          class="block w-full text-left rounded-lg px-3 py-2.5 text-base font-semibold text-white bg-gradient-to-r from-[#F1A501] to-[#FF946D]">
          Logout
        </button>`;
      mL.closest('div') ? mL.closest('div').replaceWith(mW) : mL.replaceWith(mW);
      // remove signup li/a
      try { mS.closest('a').remove(); } catch(_) {}
      try { mS.remove(); } catch(_) {}
    }
  }
}
