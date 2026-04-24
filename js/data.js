/**
 * Voyage – Shared Data Layer
 * All destination data + bookings are stored in localStorage so the admin
 * dashboard and the public-facing pages stay in sync automatically.
 */

// ─── Keys ────────────────────────────────────────────────────────────────────
const DEST_KEY     = 'voyage_destinations';
const BOOKING_KEY  = 'voyage_bookings';
const ADMIN_EMAIL  = 'shrirajadmin@gmail.com';
const ADMIN_PASS   = 'password';

// ─── Default seed data ────────────────────────────────────────────────────────
const DEFAULT_DESTINATIONS = [
  {
    id       : 'dest-1',
    title    : 'Goa',
    subtitle : 'Goa, India',
    price    : '₹45,000',
    priceNum : 45000,
    duration : '7 Days Trip',
    description: 'Sun-kissed beaches, vibrant nightlife, Portuguese heritage and fresh seafood. India\'s beach paradise.',
    image    : 'images/goa_beach.png',
    tag      : '🏖️ Beach',
    rating   : 4.8,
    bookings : 312,
    details  : [
      '🛬 Day 1: Arrival at Goa airport, check-in & welcome dinner at a beachside restaurant',
      '🏖️ Day 2–3: Baga, Calangute & Anjuna beach explorations, water sports & jet skiing',
      '⛵ Day 4: Goa sunset cruise on the Mandovi River with live music & cocktails',
      '🕌 Day 5: Heritage trail – Old Goa churches, Fontainhas Latin Quarter & spice plantation',
      '🤿 Day 6: Optional scuba diving or dolphin watching tour at Grand Island',
      '✈️ Day 7: Leisure morning, checkout & departure',
      '✅ Includes: Return flights · 4★ beach resort · Airport transfers · Professional guide · Travel insurance'
    ]
  },
  {
    id       : 'dest-2',
    title    : 'Jaipur, Rajasthan',
    subtitle : 'Jaipur, Rajasthan',
    price    : '₹35,000',
    priceNum : 35000,
    duration : '5 Days Trip',
    description: 'The Pink City – Amber Fort, Hawa Mahal, royal palaces and vibrant bazaars. A regal experience.',
    image    : 'images/jaipur_palace.png',
    tag      : '🏰 Heritage',
    rating   : 4.7,
    bookings : 224,
    details  : [
      '🛬 Day 1: Arrival in Jaipur, check-in at heritage haveli hotel, evening at Chokhi Dhani',
      '🏰 Day 2: Amber Fort elephant ride, Sheesh Mahal & Jaigarh Fort panoramic views',
      '🌸 Day 3: City Palace, Hawa Mahal photo-stop, Jantar Mantar observatory tour',
      '🛍️ Day 4: Johari Bazaar gem shopping, block printing workshop & local cuisine trail',
      '✈️ Day 5: Nahargarh Fort sunrise, checkout & departure',
      '✅ Includes: Return flights · Heritage haveli stay · All city transfers · Licensed guide · Travel insurance'
    ]
  },
  {
    id       : 'dest-3',
    title    : 'Kerala Backwaters',
    subtitle : 'Kerala, India',
    price    : '₹55,000',
    priceNum : 55000,
    duration : '8 Days Trip',
    description: 'Serene houseboats, lush paddy fields, Ayurveda spa retreats and misty hill stations.',
    image    : 'images/kerala_backwaters.png',
    tag      : '🌿 Nature',
    rating   : 4.9,
    bookings : 198,
    details  : [
      '🛬 Day 1: Arrival at Cochin, Fort Kochi heritage walk & Chinese fishing nets at sunset',
      '🌿 Day 2: Drive to Munnar, tea garden plantation tour & Eravikulam National Park',
      '🧖 Day 3: Ayurveda spa full-day treatment at an authentic Kottakkal centre',
      '🚢 Day 4–5: Private houseboat cruise through Alleppey backwaters, village stops & fresh Kerala meals on board',
      '🐘 Day 6: Periyar Wildlife Sanctuary jeep safari & spice garden visit',
      '🏖️ Day 7: Kovalam beach relaxation, Lighthouse sunset & seafood dinner',
      '✈️ Day 8: Checkout & departure from Trivandrum',
      '✅ Includes: Return flights · Houseboat & hotel stays · All transfers · Expert guides · Travel insurance'
    ]
  },
  {
    id       : 'dest-4',
    title    : 'Manali, Himachal',
    subtitle : 'Manali, Himachal Pradesh',
    price    : '₹25,000',
    priceNum : 25000,
    duration : '6 Days Trip',
    description: 'Snow-capped peaks, adventure sports, Rohtang Pass and the magical Solang Valley.',
    image    : 'images/manali_mountains.png',
    tag      : '⛷️ Adventure',
    rating   : 4.6,
    bookings : 275,
    details  : [
      '🛬 Day 1: Arrival at Bhuntar airport, scenic drive to Manali & check-in at snow-view resort',
      '⛷️ Day 2: Solang Valley – skiing, snowboarding & snowmobile rides (seasonal)',
      '🏔️ Day 3: Rohtang Pass excursion (subject to permits) – snow play & breathtaking views',
      '🛕 Day 4: Hadimba Devi Temple, Old Manali village stroll & Manu Temple visit',
      '🏄 Day 5: Beas River white-water rafting & paragliding at Dobhi',
      '✈️ Day 6: Leisure morning at the famous Mall Road, checkout & departure',
      '✅ Includes: Return flights · Mountain resort stay · All activity transfers · Guide · Travel insurance'
    ]
  },
  {
    id       : 'dest-5',
    title    : 'Andaman Islands',
    subtitle : 'Andaman & Nicobar',
    price    : '₹65,000',
    priceNum : 65000,
    duration : '10 Days Trip',
    description: 'Crystal-clear waters, coral reefs, Radhanagar Beach and thrilling scuba diving adventures.',
    image    : 'images/andaman_islands.png',
    tag      : '🤿 Diving',
    rating   : 4.9,
    bookings : 143,
    details  : [
      '🛬 Day 1: Arrival at Port Blair, Cellular Jail evening light & sound show',
      '🚤 Day 2: Ferry to Havelock Island, check-in & Radhanagar Beach sunset (Asia\'s best beach)',
      '🤿 Day 3–4: Certified scuba diving & snorkelling at Elephant Beach & Neil Island coral reefs',
      '🏝️ Day 5: Neil Island day trip – natural beach bridge & rock formations',
      '🎣 Day 6: Deep-sea fishing excursion & glass-bottom boat ride over coral gardens',
      '🐢 Day 7: Night turtle nesting walk at Kalipur beach (seasonal)',
      '🏖️ Day 8–9: Free leisure days at North Bay for more water sports or relaxation',
      '✈️ Day 10: Return ferry to Port Blair & departure',
      '✅ Includes: Return flights · 4★ island resort · All ferry transfers · Diving gear · Travel insurance'
    ]
  },
  {
    id       : 'dest-6',
    title    : 'Agra, Uttar Pradesh',
    subtitle : 'Agra, Uttar Pradesh',
    price    : '₹20,000',
    priceNum : 20000,
    duration : '3 Days Trip',
    description: 'Home to the eternal Taj Mahal, Agra Fort and Fatehpur Sikri – a UNESCO World Heritage journey.',
    image    : 'images/103705059.jpg',
    tag      : '🕌 Culture',
    rating   : 4.7,
    bookings : 389,
    details  : [
      '🛬 Day 1: Arrival in Agra, check-in & evening visit to Mehtab Bagh for sunset Taj Mahal silhouette views',
      '🕌 Day 2 (Morning): Taj Mahal sunrise visit – guided tour of the mausoleum, gardens & reflecting pool',
      '🏰 Day 2 (Afternoon): Agra Fort – guided walk through Diwan-i-Khas, Jehangir\'s Palace & Musamman Burj',
      '🕍 Day 3: Full-day Fatehpur Sikri excursion – Mughal ghost city, Buland Darwaza & Salim Chishti shrine',
      '🛍️ Day 3 (Evening): Marble inlay craft workshop & local bazaar shopping before departure',
      '✅ Includes: Return train/flight tickets · Boutique hotel stay · Skip-the-line monument passes · Expert guide · Travel insurance'
    ]
  }
];

// ─── Default mock bookings ─────────────────────────────────────────────────────
const DEFAULT_BOOKINGS = [
  { id:'bk-1', traveller:'Arjun Sharma',   email:'arjun@example.com',   destination:'Goa',              duration:'7 Days', travellers:2, departure:'2026-04-10', amount:'₹90,000',  status:'Confirmed' },
  { id:'bk-2', traveller:'Priya Mehta',    email:'priya@example.com',   destination:'Kerala Backwaters', duration:'8 Days', travellers:3, departure:'2026-04-18', amount:'₹1,65,000',status:'Pending'   },
  { id:'bk-3', traveller:'Rohan Verma',    email:'rohan@example.com',   destination:'Manali, Himachal', duration:'6 Days', travellers:1, departure:'2026-05-02', amount:'₹25,000',  status:'Confirmed' },
  { id:'bk-4', traveller:'Sneha Iyer',     email:'sneha@example.com',   destination:'Jaipur, Rajasthan',duration:'5 Days', travellers:4, departure:'2026-05-15', amount:'₹1,40,000',status:'Confirmed' },
  { id:'bk-5', traveller:'Kabir Das',      email:'kabir@example.com',   destination:'Andaman Islands',  duration:'10 Days',travellers:2, departure:'2026-06-01', amount:'₹1,30,000',status:'Pending'   },
  { id:'bk-6', traveller:'Nisha Gupta',    email:'nisha@example.com',   destination:'Agra, Uttar Pradesh',duration:'3 Days',travellers:2,departure:'2026-06-20', amount:'₹40,000',  status:'Cancelled' },
  { id:'bk-7', traveller:'Vikram Nair',    email:'vikram@example.com',  destination:'Goa',              duration:'7 Days', travellers:5, departure:'2026-07-04', amount:'₹2,25,000',status:'Confirmed' },
  { id:'bk-8', traveller:'Deepa Pillai',   email:'deepa@example.com',   destination:'Kerala Backwaters', duration:'8 Days', travellers:2, departure:'2026-07-15', amount:'₹1,10,000',status:'Confirmed' },
];

// ─── Init (called automatically) ─────────────────────────────────────────────
(function initStore() {
  // Migrate existing destinations that lack `details`
  if (localStorage.getItem(DEST_KEY)) {
    const existing = JSON.parse(localStorage.getItem(DEST_KEY));
    const needsMigration = existing.some(d => !d.details);
    if (needsMigration) {
      // Merge details from DEFAULT_DESTINATIONS into existing entries by id
      const defaultMap = Object.fromEntries(DEFAULT_DESTINATIONS.map(d => [d.id, d]));
      const migrated = existing.map(d => {
        if (!d.details) {
          return { ...d, details: defaultMap[d.id]?.details || ['No additional details available for this tour.'] };
        }
        return d;
      });
      localStorage.setItem(DEST_KEY, JSON.stringify(migrated));
    }
  } else {
    localStorage.setItem(DEST_KEY, JSON.stringify(DEFAULT_DESTINATIONS));
  }

  if (!localStorage.getItem(BOOKING_KEY)) {
    localStorage.setItem(BOOKING_KEY, JSON.stringify(DEFAULT_BOOKINGS));
  }
})();

// ─── Destinations CRUD ────────────────────────────────────────────────────────
function getDestinations() {
  return JSON.parse(localStorage.getItem(DEST_KEY)) || [];
}

function getDestinationById(id) {
  return getDestinations().find(d => d.id === id) || null;
}

function addDestination(dest) {
  const list = getDestinations();
  dest.id = 'dest-' + Date.now();
  dest.bookings = dest.bookings || 0;
  dest.rating   = dest.rating   || 4.5;
  list.push(dest);
  localStorage.setItem(DEST_KEY, JSON.stringify(list));
  return dest;
}

function updateDestination(id, updates) {
  const list = getDestinations().map(d => d.id === id ? { ...d, ...updates } : d);
  localStorage.setItem(DEST_KEY, JSON.stringify(list));
}

function deleteDestination(id) {
  const list = getDestinations().filter(d => d.id !== id);
  localStorage.setItem(DEST_KEY, JSON.stringify(list));
}

// ─── Bookings READ ────────────────────────────────────────────────────────────
function getBookings() {
  return JSON.parse(localStorage.getItem(BOOKING_KEY)) || [];
}

// ─── Auth helper ──────────────────────────────────────────────────────────────
function isAdminCredentials(email, pass) {
  return email.trim().toLowerCase() === ADMIN_EMAIL && pass === ADMIN_PASS;
}
