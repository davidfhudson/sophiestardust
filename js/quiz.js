// ── Sophie's Stardust — Quiz Page ─────────────────────────

let quizData  = null;
let category  = null;
let quizMeta  = null;   // { id, name }
let questions = [];
let current   = 0;
let score     = 0;
let answered  = false;

async function initQuiz() {
  const params = getParams();
  if (!params.category || !params.quiz) { window.location.href = 'index.html'; return; }

  quizData = await loadQuizData();
  category = findCategory(quizData, params.category);
  if (!category) { window.location.href = 'index.html'; return; }

  // ── Random Mix ──────────────────────────────────────────
  if (params.quiz === 'random') {
    const allQ = category.quizzes.flatMap(q => q.questions);
    if (allQ.length === 0) { window.location.href = 'index.html'; return; }
    questions = shuffle(allQ).slice(0, CONFIG.QUESTIONS_PER_QUIZ);
    quizMeta  = {
      id:   `${category.id}-random`,
      name: `${category.name} — Random Mix`
    };
  } else {
    // ── Standard quiz ──────────────────────────────────────
    const found = findQuiz(category, params.quiz);
    if (!found) { window.location.href = 'index.html'; return; }
    questions = shuffle([...found.questions]).slice(0, CONFIG.QUESTIONS_PER_QUIZ);
    quizMeta  = { id: found.id, name: found.name };
  }

  // Store for results page
  sessionStorage.setItem('quizResult_meta', JSON.stringify({
    quizId:     quizMeta.id,
    quizName:   quizMeta.name,
    categoryId: category.id,
    category:   category.name,
    quizUrl:    window.location.search   // for Play Again
  }));

  document.getElementById('quiz-category-tag').textContent = category.name;
  document.title = `${quizMeta.name} — Sophie's Stardust`;

  renderQuestion();
}

function renderQuestion() {
  answered = false;
  const q = questions[current];

  document.getElementById('progress-fill').style.width = (current / questions.length * 100) + '%';
  document.getElementById('q-current').textContent     = current + 1;
  document.getElementById('q-total').textContent       = questions.length;
  document.getElementById('score-display').textContent = `Score: ${score}`;
  document.getElementById('question-text').textContent = q.q;

  const container = document.getElementById('answers');
  container.innerHTML = '';
  ['A','B','C','D'].forEach((letter, i) => {
    const btn = document.createElement('button');
    btn.className = 'answer-btn card fade-in';
    btn.style.animationDelay = (i * 0.06) + 's';
    btn.innerHTML = `<span class="answer-letter">${letter}</span><span class="answer-text">${q.options[i]}</span>`;
    btn.addEventListener('click', () => handleAnswer(i, q.answer, q.fact, q.options[q.answer]));
    container.appendChild(btn);
  });

  document.getElementById('feedback-box').classList.remove('show','wrong-feedback');
  document.getElementById('next-wrap').classList.add('hidden');
}

function handleAnswer(chosen, correct, fact, correctText) {
  if (answered) return;
  answered = true;

  const btns = document.querySelectorAll('.answer-btn');
  btns.forEach(b => b.setAttribute('disabled', true));

  const isCorrect = chosen === correct;
  if (isCorrect) score++;

  btns[chosen].classList.add(isCorrect ? 'correct' : 'wrong');
  if (!isCorrect) btns[correct].classList.add('correct');

  document.getElementById('score-display').textContent = `Score: ${score}`;

  const fb = document.getElementById('feedback-box');
  if (isCorrect) {
    fb.textContent = fact ? `✓ Correct! ${fact}` : '✓ Correct!';
    fb.classList.remove('wrong-feedback');
  } else {
    fb.textContent = fact
      ? `✗ The answer was "${correctText}". ${fact}`
      : `✗ The answer was "${correctText}".`;
    fb.classList.add('wrong-feedback');
  }
  fb.classList.add('show');

  const nextWrap = document.getElementById('next-wrap');
  nextWrap.classList.remove('hidden');
  document.getElementById('next-btn').textContent =
    current === questions.length - 1 ? 'See Results ✦' : 'Next Question →';
}

function nextQuestion() {
  current++;
  if (current >= questions.length) {
    sessionStorage.setItem('quizResult_score', JSON.stringify({ score, total: questions.length }));
    window.location.href = 'results.html';
    return;
  }
  renderQuestion();
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

document.addEventListener('DOMContentLoaded', () => {
  initQuiz();
  document.getElementById('next-btn').addEventListener('click', nextQuestion);
});
