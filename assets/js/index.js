// effet typewritter

document.addEventListener('DOMContentLoaded', () => {
  const spans = document.querySelectorAll('.hero-title .tw-line');
  const lines = ['We made luxury food', 'affordable'];
  let lineIndex = 0;
  let charIndex = 0;

  function removeCursor() {
    document.querySelectorAll('.cursor').forEach(c => c.remove());
  }

  function type() {
    if (lineIndex >= lines.length) {
      setTimeout(removeCursor, 1000);
      return;
    }

    const currentLine = lines[lineIndex];

    if (charIndex <= currentLine.length) {
      spans[lineIndex].innerHTML = currentLine.slice(0, charIndex) + '<span class="cursor">|</span>';
      charIndex++;
      setTimeout(type, 75);
    } else {
      removeCursor();
      charIndex = 0;
      lineIndex++;
      setTimeout(type, 350);
    }
  }

  setTimeout(type, 1000);
});

// cross-fade images
setInterval(() => {
  document.querySelectorAll('.crossfade').forEach(fig => fig.classList.toggle('show-second'));
}, 8000);

// carousel avis
(function () {
  const track  = document.getElementById('reviewsTrack');
  if (!track) return;

  const cards  = track.querySelectorAll('.review-card');
  const dotsEl = document.getElementById('reviewsDots');
  const wrap   = document.querySelector('.reviews-carousel-wrap');
  let current  = 0;
  let timer    = null;

  function numVisible() {
    return window.innerWidth >= 992 ? 3 : window.innerWidth >= 600 ? 2 : 1;
  }
  function maxIdx() { return Math.max(0, cards.length - numVisible()); }
  function cardStep() { return cards.length > 1 ? cards[1].offsetLeft - cards[0].offsetLeft : 0; }

  function goTo(idx) {
    const len = maxIdx() + 1;
    current = ((idx % len) + len) % len;
    track.style.transform = `translateX(-${current * cardStep()}px)`;
    dotsEl?.querySelectorAll('.reviews-dot').forEach((d, i) => d.classList.toggle('is-active', i === current));
  }

  function buildDots() {
    if (!dotsEl) return;
    dotsEl.innerHTML = '';
    for (let i = 0; i <= maxIdx(); i++) {
      const btn = document.createElement('button');
      btn.className = 'reviews-dot';
      btn.addEventListener('click', () => { goTo(i); startTimer(); });
      dotsEl.appendChild(btn);
    }
  }

  document.querySelector('.reviews-prev')?.addEventListener('click', () => { goTo(current - 1); startTimer(); });
  document.querySelector('.reviews-next')?.addEventListener('click', () => { goTo(current + 1); startTimer(); });

  function startTimer() {
    clearInterval(timer);
    timer = setInterval(() => goTo(current + 1), 5000);
  }

  wrap?.addEventListener('mouseenter', () => clearInterval(timer));
  wrap?.addEventListener('mouseleave', startTimer);

  let resizeTO;
  window.addEventListener('resize', () => {
    clearTimeout(resizeTO);
    resizeTO = setTimeout(() => { buildDots(); goTo(Math.min(current, maxIdx())); }, 150);
  });

  buildDots();
  goTo(0);
  startTimer();
})();
