// ── Sophie's Stardust — Leaderboard Page ──────────────────

let allAttempts = [];
let activeTab   = 'all';

async function initLeaderboard() {
  allAttempts = await fetchAttempts();
  renderLeaderboard();
}

function renderLeaderboard() {
  const list     = document.getElementById('lb-list');
  const filterCat = activeTab === 'all' ? null : activeTab;
  const rows     = buildLeaderboard(allAttempts, filterCat);

  if (rows.length === 0) {
    list.innerHTML = '<div class="lb-empty">No scores yet — be the first! ✦</div>';
    return;
  }

  list.innerHTML = '';
  rows.forEach((p, i) => {
    const cls  = rankClass(i);
    const lbl  = rankLabel(i);
    const perf = p.pct === 1 ? '<div class="lb-perfect">✦ Perfect</div>' : '';
    const row  = document.createElement('div');
    row.className = `lb-row card ${cls}`;
    row.innerHTML = `
      <div class="lb-rank ${cls}">${lbl}</div>
      <div class="lb-info">
        <div class="lb-name">${escHtml(p.name)}</div>
        <div class="lb-meta">${p.score}/${p.total} · ${p.quizzes} quiz${p.quizzes !== 1 ? 'zes' : ''}</div>
      </div>
      <div style="text-align:right;flex-shrink:0">
        <div class="lb-pct">${fmtPct(p.pct)}</div>
        ${perf}
      </div>
    `;
    list.appendChild(row);
  });
}

document.addEventListener('DOMContentLoaded', () => {
  initLeaderboard();
  document.querySelectorAll('.lb-tab').forEach(tab => {
    tab.addEventListener('click', () => {
      document.querySelectorAll('.lb-tab').forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
      activeTab = tab.dataset.cat;
      renderLeaderboard();
    });
  });
});
