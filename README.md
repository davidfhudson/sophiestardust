# Sophie's Stardust — Setup Guide

## ✦ What's in the box

```
sophies-stardust/
├── index.html          Homepage + category/quiz selection
├── quiz.html           Quiz page
├── results.html        Results & score submission
├── leaderboard.html    Hall of Stars
├── maintenance.html    "Back soon" page
├── config.js           ← YOU FILL THIS IN (step 1)
├── css/
│   └── style.css       All shared styles
├── js/
│   ├── app.js          Shared utilities + JSONbin API
│   ├── quiz.js         Quiz page logic
│   └── leaderboard.js  Leaderboard page logic
└── data/
    └── quizzes.json    All quiz questions ← Sophie edits this
```

---

## Step 1 — Set up JSONbin (5 minutes)

1. Go to **https://jsonbin.io** and sign up (free)
2. Click **Create Bin**
3. Paste this as the content and save:
   ```json
   {"attempts":[]}
   ```
4. Copy the **Bin ID** shown in the URL: `https://jsonbin.io/.../{BIN_ID}`
5. Click **API Keys** in the top menu → **+ Add API Key** → copy the key
6. Open `config.js` and replace the placeholders:
   ```js
   JSONBIN_BIN_ID:  'your-bin-id-here',
   JSONBIN_API_KEY: '$2a$...',
   ```

---

## Step 2 — Deploy to Netlify

1. Drag the entire `sophies-stardust` folder to **https://app.netlify.com/drop**
2. That's it — Netlify gives you a URL instantly
3. Optional: set a custom domain in Netlify's settings

---

## Step 3 — Adding or editing quizzes

Open `data/quizzes.json` in any text editor.

**To add a question** to an existing quiz, add an object to the `questions` array:
```json
{
  "q": "Your question here?",
  "options": ["Option A", "Option B", "Option C", "Option D"],
  "answer": 1,
  "fact": "Fun fact shown after answering (optional — can be an empty string)"
}
```
> `"answer"` is the index of the correct option: 0 = A, 1 = B, 2 = C, 3 = D

**To add a new quiz** inside an existing category:
```json
{
  "id": "books-harry-potter",
  "name": "Harry Potter",
  "description": "Wizards, spells and Hogwarts",
  "questions": [ ... ]
}
```

**To add a new category**, add to the `categories` array:
```json
{
  "id": "tv-shows",
  "name": "TV Shows",
  "icon": "📺",
  "iconClass": "cat-icon-movies",
  "quizzes": [ ... ]
}
```

---

## Maintenance mode

To put the site in maintenance mode, rename `index.html` to `index-live.html`
and rename `maintenance.html` to `index.html`. Reverse to bring it back.

---

## Clearing the leaderboard

Log into jsonbin.io, open your bin, and replace the content with `{"attempts":[]}`.
