(function () {
  'use strict';
 
  const TOTAL_FRAMES = 9;          // cuántas imágenes tienes
  const SCROLL_RANGE = 600;        // px de scroll para una vuelta completa
  const IMG_PATH     = 'img/prototipo360/'; // carpeta de imágenes
 
  const img     = document.getElementById('protoImg');
  const counter = document.getElementById('protoCounter');
  const hint    = document.getElementById('protoHint');
  const section = document.getElementById('seccion360');
 
  if (!img || !section) return;
 
  // Pre-cargar todas las imágenes para que no parpadeen
  const frames = [];
  for (let i = 1; i <= TOTAL_FRAMES; i++) {
    const image = new Image();
    image.src = IMG_PATH + i + '.png';
    frames.push(image);
  }
 
  let currentFrame = 0;
  let hintHidden   = false;
 
  function updateFrame() {
    const rect       = section.getBoundingClientRect();
    const sectionTop = window.scrollY + rect.top;
    const scrolled   = window.scrollY - sectionTop + window.innerHeight * 0.3;
 
    if (scrolled < 0) {
      setFrame(0);
      return;
    }
 
    // Normalizar 0→1 durante SCROLL_RANGE px
    const progress   = Math.min(Math.max(scrolled / SCROLL_RANGE, 0), 1);
    const frameIndex = Math.round(progress * (TOTAL_FRAMES - 1));
 
    setFrame(frameIndex);
 
    // Ocultar hint al primer scroll
    if (!hintHidden && scrolled > 50) {
      hint.classList.add('hidden');
      hintHidden = true;
    }
  }
 
  function setFrame(index) {
    if (index === currentFrame) return;
    currentFrame = index;
 
    const num = index + 1; // las imágenes van 1.png … 9.png
    img.src = IMG_PATH + num + '.png';
 
    if (counter) {
      counter.textContent =
        String(num).padStart(2, '0') + ' / ' + String(TOTAL_FRAMES).padStart(2, '0');
    }
  }
 
  // Escuchar scroll con requestAnimationFrame para rendimiento
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