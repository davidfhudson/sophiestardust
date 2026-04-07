// ── Sophie's Stardust — Shared Utilities ──────────────────

// ── JSONbin API ────────────────────────────────────────────
// Structure: { "attempts": [ { name, quizId, categoryId, category, quiz, score, total, date } ] }

async function fetchAttempts() {
  if (CONFIG.JSONBIN_BIN_ID === 'PASTE_YOUR_BIN_ID_HERE') return [];
  try {
    const res = await fetch(
      `https://api.jsonbin.io/v3/b/${CONFIG.JSONBIN_BIN_ID}/latest`,
      { headers: { 'X-Master-Key': CONFIG.JSONBIN_API_KEY } }
    );
    if (!res.ok) return [];
    const data = await res.json();
    return data.record?.attempts || [];
  } catch { return []; }
}

async function saveAttempts(attempts) {
  if (CONFIG.JSONBIN_BIN_ID === 'PASTE_YOUR_BIN_ID_HERE') return false;
  try {
    const res = await fetch(
      `https://api.jsonbin.io/v3/b/${CONFIG.JSONBIN_BIN_ID}`,
      {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'X-Master-Key': CONFIG.JSONBIN_API_KEY
        },
        body: JSON.stringify({ attempts })
      }
    );
    return res.ok;
  } catch { return false; }
}

// Returns true if this player has already submitted a score for this quiz
async function hasPlayedQuiz(name, quizId) {
  const attempts = await fetchAttempts();
  const n = sanitiseName(name).toLowerCase();
  return attempts.some(a => a.name.toLowerCase() === n && a.quizId === quizId);
}

// Submit — only records if this is a genuine first attempt for that player+quiz
async function submitAttempt(entry) {
  const attempts = await fetchAttempts();
  const name = sanitiseName(entry.name);
  const alreadyPlayed = attempts.some(
    a => a.name.toLowerCase() === name.toLowerCase() && a.quizId === entry.quizId
  );
  if (alreadyPlayed) return { ok: true, firstAttempt: false };

  attempts.push({
    name,
    quizId:     entry.quizId,
    categoryId: entry.categoryId,
    category:   entry.category,
    quiz:       entry.quiz,
    score:      entry.score,
    total:      entry.total,
    date:       new Date().toISOString()
  });
  const ok = await saveAttempts(attempts);
  return { ok, firstAttempt: true };
}

function sanitiseName(name) {
  return String(name).replace(/[<>&"']/g, '').trim().slice(0, 24) || 'Anonymous';
}

// ── Leaderboard derivation ─────────────────────────────────
// Rules:
//   · Only first attempt per player per quiz counts
//   · Ranked by cumulative % (total score ÷ total possible)
//   · Tiebreak: more quizzes played ranks higher

function buildLeaderboard(attempts, filterCategoryId = null) {
  const filtered = filterCategoryId
    ? attempts.filter(a => a.categoryId === filterCategoryId)
    : attempts;

  // Keep earliest attempt per player per quiz
  const firstAttempts = {};
  [...filtered]
    .sort((a, b) => new Date(a.date) - new Date(b.date))
    .forEach(a => {
      const key = `${a.name.toLowerCase()}::${a.quizId}`;
      if (!firstAttempts[key]) firstAttempts[key] = a;
    });

  // Aggregate per player
  const players = {};
  Object.values(firstAttempts).forEach(a => {
    const key = a.name.toLowerCase();
    if (!players[key]) players[key] = { name: a.name, score: 0, total: 0, quizzes: 0 };
    players[key].score  += a.score;
    players[key].total  += a.total;
    players[key].quizzes++;
  });

  return Object.values(players)
    .map(p => ({ ...p, pct: p.total > 0 ? p.score / p.total : 0 }))
    .sort((a, b) => b.pct - a.pct || b.quizzes - a.quizzes);
}

// ── Quiz data loader ───────────────────────────────────────

async function loadQuizData() {
  const res = await fetch('data/quizzes.json');
  return await res.json();
}

function findCategory(data, categoryId) {
  return data.categories.find(c => c.id === categoryId) || null;
}

function findQuiz(category, quizId) {
  return category?.quizzes.find(q => q.id === quizId) || null;
}

function getParams() {
  return Object.fromEntries(new URLSearchParams(window.location.search));
}

// ── Shuffle ────────────────────────────────────────────────

function shuffle(arr) {
  const a = [...arr];
  for (let i = a.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [a[i], a[j]] = [a[j], a[i]];
  }
  return a;
}

// ── Result titles ──────────────────────────────────────────

function getResultTitle(score, total, categoryId) {
  const pct = score / total;
  const titles = {
    'books': [
      [1.0, 'Literary Legend!',        'A perfect score — you know your books inside out'],
      [0.8, 'Bookworm Extraordinaire', 'Impressive knowledge of the literary world'],
      [0.6, 'Keen Reader',             'You clearly love a good book'],
      [0.4, 'Turning Pages',           "Keep reading — there's so much more to discover"],
      [0,   'Chapter One Begins…',     'Every great reader starts somewhere']
    ],
    'musical-theatre': [
      [1.0, 'West End Legend!', "A perfect score — you're a true theatre star"],
      [0.8, 'West End Star!',   'You really know your musicals'],
      [0.6, 'Rising Star',      "You've clearly got a love for the stage"],
      [0.4, 'In the Chorus',    "Keep watching those shows — you're getting there"],
      [0,   'Curtain Up…',      'Every great theatregoer has to start somewhere']
    ],
    'movies': [
      [1.0, 'Cinema Legend!',     'A perfect score — you belong on the red carpet'],
      [0.8, 'Silver Screen Star', 'Impressive — you really know your films'],
      [0.6, 'Movie Buff',         "You've watched a good few films in your time"],
      [0.4, 'Popcorn Fan',        'Keep watching — there are so many great films ahead'],
      [0,   'Lights, Camera…',    'The projector is warming up — keep watching!']
    ]
  };
  const set = titles[categoryId] || [
    [1.0, 'Stardust Legend!',   'A perfect score — extraordinary!'],
    [0.8, 'Star Performer',     'Really impressive knowledge'],
    [0.6, 'Rising Star',        "You're getting there"],
    [0.4, 'Keep Reaching',      'Every question is a chance to learn'],
    [0,   'The Journey Begins…','Keep going — you\'ll get there']
  ];
  for (const [threshold, title, sub] of set) {
    if (pct >= threshold) return { title, sub };
  }
  return { title: set[set.length - 1][1], sub: set[set.length - 1][2] };
}

// ── Rank helpers ───────────────────────────────────────────

function rankClass(i)  { return i === 0 ? 'gold' : i === 1 ? 'silver' : i === 2 ? 'bronze' : 'plain'; }
function rankLabel(i)  { return i < 3 ? ['1','2','3'][i] : String(i + 1); }
function fmtPct(pct)   { return (pct * 100).toFixed(1) + '%'; }
function escHtml(str)  {
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Cookie helpers ─────────────────────────────────────────

const COOKIE_NAME = 'stardust_player';
const COOKIE_DAYS = 365;

function savePlayerName(name) {
  const expires = new Date(Date.now() + COOKIE_DAYS * 864e5).toUTCString();
  document.cookie = `${COOKIE_NAME}=${encodeURIComponent(sanitiseName(name))}; expires=${expires}; path=/; SameSite=Lax`;
}

function loadPlayerName() {
  const match = document.cookie.match(new RegExp(`(?:^|; )${COOKIE_NAME}=([^;]*)`));
  return match ? decodeURIComponent(match[1]) : '';
}
