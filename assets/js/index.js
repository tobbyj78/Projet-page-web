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
