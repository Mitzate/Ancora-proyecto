/* ════════════════════════════════════════════════════════════
   ANCORA — Animación 360° v2
   REEMPLAZA el contenido de js/section360.js
   ════════════════════════════════════════════════════════════ */

(function () {
  'use strict';

  const TOTAL_FRAMES = 9;
  const SCROLL_RANGE = 320;          // ← más rápido (antes 600)
  const IMG_PATH     = 'img/prototipo360/';

  const img     = document.getElementById('protoImg');
  const counter = document.getElementById('protoCounter');
  const hint    = document.getElementById('protoHint');
  const section = document.getElementById('seccion360');

  if (!img || !section) return;

  // Pre-cargar todas las imágenes
  const frames = [];
  for (let i = 1; i <= TOTAL_FRAMES; i++) {
    const image = new Image();
    image.src = IMG_PATH + i + '.png';
    frames.push(image);
  }

  let currentFrame = 0;
  let hintHidden   = false;

  function updateFrame() {
    const sectionTop = section.getBoundingClientRect().top + window.scrollY;
    // Empieza a animar cuando el centro de la sección llega al centro de la pantalla
    const triggerPoint = sectionTop - window.innerHeight * 0.45;
    const scrolled     = window.scrollY - triggerPoint;

    if (scrolled < 0) {
      setFrame(0);
      return;
    }

    const progress   = Math.min(Math.max(scrolled / SCROLL_RANGE, 0), 1);
    const frameIndex = Math.round(progress * (TOTAL_FRAMES - 1));

    setFrame(frameIndex);

    if (!hintHidden && scrolled > 30) {
      if (hint) hint.classList.add('hidden');
      hintHidden = true;
    }
  }

  function setFrame(index) {
    if (index === currentFrame && img.src.includes((index + 1) + '.png')) return;
    currentFrame = index;

    const num = index + 1;
    img.src = IMG_PATH + num + '.png';

    if (counter) {
      counter.textContent =
        String(num).padStart(2, '0') + ' / ' +
        String(TOTAL_FRAMES).padStart(2, '0');
    }
  }

  let ticking = false;
  window.addEventListener('scroll', function () {
    if (!ticking) {
      requestAnimationFrame(function () {
        updateFrame();
        ticking = false;
      });
      ticking = true;
    }
  }, { passive: true });

  // Estado inicial
  updateFrame();

})();