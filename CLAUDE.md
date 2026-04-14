# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Sophie's Stardust is a mobile-first quiz web app built for Sophie (~13), designed by her and built collaboratively with her father David. It hosts multiple-choice quizzes across Books, Musical Theatre, and Movies categories.

**Hosting:** 123reg shared hosting, deployed to `public_html/sophiestardust/` via GitHub Actions FTP on push to `main`. PHP + MySQL on the server; static HTML/CSS/vanilla JS on the front end — no build step.

## Running Locally

The API calls (`/api/attempts.php`, `/api/quizzes.php`) require a PHP server. For local development, use the built-in PHP server from the project root:

```bash
php -S localhost:8080
```

Then open `http://localhost:8080`. You'll need a local MySQL database and `api/config.php` populated with its credentials.

## Deployment

Push to the `main` branch — GitHub Actions runs [`.github/workflows/deploy.yml`](.github/workflows/deploy.yml) and FTP-deploys to `public_html/sophiestardust/` on 123reg. The workflow needs three repository secrets set in GitHub: `FTP_HOST`, `FTP_USERNAME`, `FTP_PASSWORD`.

`api/config.php` is gitignored and is **never deployed by the workflow** — it must be created on the server once manually (copy `api/config.example.php`, fill in credentials, upload via 123reg file manager or FTP).

## Configuration

`config.js` — front-end only, just `QUESTIONS_PER_QUIZ: 15`.

`api/config.php` — server-side only (gitignored). Contains DB credentials and `ADMIN_PASSWORD`. See `api/config.example.php` for the template.

## Design System — Do Not Change Without Asking

The visual identity is Sophie's. Do not alter colours, fonts, or the logo motif without instruction.

| Token | Value |
|---|---|
| Background | `#0D0D1A` |
| Card background | `#1A1A2E` |
| Deep background | `#12102a` |
| Primary accent | `#7B2FBE` (violet) |
| Gold | `#F5C842` |
| Teal | `#2DD4BF` |
| Text | `#F0EEF6` |
| Muted text | `#9988bb` |
| Border | `#2a1a4a` |
| Heading font | Playfair Display (Google Fonts) |
| Body font | DM Sans (Google Fonts) |

The starfield background is `radial-gradient` dots in a `body::before` pseudo-element in `style.css`. The logo motif is `✦` (U+2736) in gold.

## Architecture

### Pages and Their Controllers

| Page | JS loaded |
|------|-----------|
| `index.html` | inline script + `js/app.js` |
| `quiz.html` | `js/app.js` + `js/quiz.js` |
| `results.html` | `js/app.js` (inline script handles submission) |
| `leaderboard.html` | `js/app.js` + `js/leaderboard.js` |
| `admin.html` | self-contained inline script (password: `stardust`) |

### Data Flow

```
index.html
  → loads data/quizzes.json (static, all quiz content lives here)
  → fetches JSONbin for leaderboard preview
  ↓
quiz.html?category=X&quiz=Y
  → quiz.js shuffles and renders questions
  → stores {score, total, quizId, categoryId} in sessionStorage keys
    quizResult_meta and quizResult_score
  ↓
results.html
  → reads sessionStorage, pre-fetches all attempts (for duplicate check)
  → on name submit: writes new attempt to JSONbin
  ↓
leaderboard.html
  → fetches all attempts, runs buildLeaderboard(), filters by tab
```

### Server API (`api/`)

| File | Purpose |
|------|---------|
| `attempts.php` | `GET` → all attempts as `{"attempts":[…]}`; `POST` → insert new attempt, returns `{"ok":true,"firstAttempt":true\|false}` |
| `quizzes.php` | `GET` → quizzes JSON from DB (falls back to `data/quizzes.json` if DB empty); `POST` → saves quizzes JSON, requires `X-Admin-Password` header |
| `_db.php` | PDO connection + shared helpers (`json_response`, `sanitise_name`) — not a public endpoint |
| `config.php` | DB credentials + `ADMIN_PASSWORD` — gitignored, must be created on server manually |
| `.htaccess` | Blocks direct HTTP access to `_db.php`, `config.php`, `config.example.php` |

### Shared Logic in `js/app.js`

- **API**: `fetchAttempts()` (GET `/api/attempts.php`), `submitAttempt(entry)` (POST `/api/attempts.php` — server enforces first-attempt rule), `hasPlayedQuiz(name, quizId, attempts)` (checks a pre-fetched array)
- **Leaderboard algorithm** (`buildLeaderboard(attempts, filterCategoryId)`): pure JS derivation from raw attempts — runs client-side
- **Quiz data helpers**: `loadQuizData()`, `findCategory()`, `findQuiz()`
- **Utilities**: `getParams()` (URL params), `shuffle()`, `escHtml()` (XSS guard), `savePlayerName()` / `loadPlayerName()` (1-year cookie `stardust_player`)

### Leaderboard Scoring Model — Critical, Do Not Change

This is the core game mechanic. Any DB schema must preserve it exactly.

1. Only a player's **first attempt at each quiz** counts — replays are silently ignored
2. Players are ranked by **cumulative first-attempt percentage**: `total_score / total_possible × 100`
3. **Tiebreak:** more quizzes played ranks higher (rewards breadth)
4. As Sophie adds new quizzes, everyone's percentage is at risk — nobody coasts on old perfect scores
5. Player identity is **name only, case-insensitive** (no login)

Random Mix attempts are recorded with quiz ID `{categoryId}-random`.

### Data Structures

**Attempts array** (stored in JSONbin as `{"attempts": [...]}`)
```json
{
  "name": "StargazerEmma",
  "quizId": "theatre-general",
  "categoryId": "musical-theatre",
  "category": "Musical Theatre",
  "quiz": "West End & Broadway",
  "score": 14,
  "total": 15,
  "date": "2025-04-10T14:23:11.000Z"
}
```

**`data/quizzes.json` structure**
```json
{
  "categories": [{
    "id": "books",
    "name": "Books",
    "icon": "📚",
    "iconClass": "cat-icon-books",
    "quizzes": [{
      "id": "books-general",
      "questions": [{ "q": "...", "options": [...], "answer": 2, "fact": "..." }]
    }]
  }]
}
```
`answer` is a 0-based index into `options`. `fact` is shown after answering — Sophie writes these and they matter to her. Do not remove or rename this field.

### Admin Panel

`admin.html` is a self-contained visual editor. On login it fetches quiz data from `/api/quizzes.php`. Cmd+S (or "Save to server") POSTs directly to `/api/quizzes.php` with the `X-Admin-Password` header — the server validates the password and writes to the DB. A "Download" button is available as a local backup.

The client-side password constant (`ADMIN_PASSWORD` in `admin.html`) gates the UI. The server-side `ADMIN_PASSWORD` in `api/config.php` is the authority — keep them in sync.

### Special Behaviours

- **Random Mix mode**: if a category has 2+ quizzes, an extra "✦ Random Mix" tile appears — URL `quiz.html?category={id}&quiz=random`, pulls all questions from all quizzes in the category, shuffles, takes first 15
- **Duplicate detection**: results.html pre-fetches all attempts before submission; the submit endpoint (when migrated) should return `{ firstAttempt: true/false }` so the client can trust that instead
- **Maintenance mode**: rename `maintenance.html` to `index.html` to activate a "back soon" page

## Migration Priorities

1. **Replace JSONbin with a real DB** — swap `fetchAttempts()` / `saveAttempts()` in `app.js` with REST endpoints (`GET /api/attempts`, `POST /api/attempts`). `buildLeaderboard()` can move server-side.
2. **Admin page server save** — make the download button POST to `/api/quizzes` instead of generating a local file, eliminating the manual deploy step for content changes.
3. **Proper admin auth** — the current plaintext password should become a real session on a real server.
4. **Optional: per-quiz leaderboard view** — show best first-attempt scores per individual quiz, not just the cumulative board.

## What Not to Change

- Visual design, colour palette, fonts — Sophie's choices
- The `✦` logo motif
- The leaderboard scoring model (first-attempt cumulative percentage)
- `answer` field being zero-indexed in the JSON
- The `fact` field on questions
- Result title tiers (e.g. "West End Legend!", "Rising Star") — Sophie approved these
