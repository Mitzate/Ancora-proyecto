/* ════════════════════════════════════════════════════════════
   ANCORA — Animación 360° AUTOMÁTICA (sin scroll)
   REEMPLAZA el contenido completo de js/section360.js
   ════════════════════════════════════════════════════════════ */

(function () {
  'use strict';

  const TOTAL_FRAMES  = 9;
  const FRAME_DELAY   = 180;      // ms entre cada frame (más lento = más elegante)
  const IMG_PATH      = 'img/prototipo360/';

  const img     = document.getElementById('protoImg');
  const counter = document.getElementById('protoCounter');

  if (!img) return;

  // Pre-cargar todas las imágenes para evitar parpadeo
  const frames = [];
  for (let i = 1; i <= TOTAL_FRAMES; i++) {
    const image = new Image();
    image.src = IMG_PATH + i + '.png';
    frames.push(image);
  }

  let currentFrame = 0;

  function nextFrame() {
    currentFrame = (currentFrame + 1) % TOTAL_FRAMES;
    const num = currentFrame + 1;

    // Fade out → cambia imagen → fade in
    img.style.opacity = '0';

    setTimeout(function () {
      img.src = IMG_PATH + num + '.png';
      img.style.opacity = '1';

      if (counter) {
        counter.textContent =
          String(num).padStart(2, '0') + ' / ' +
          String(TOTAL_FRAMES).padStart(2, '0');
      }
    }, 80); // mitad del tiempo de transición
  }

  // Asegurarse de que la transición CSS está puesta
  img.style.transition = 'opacity 0.15s ease';

  // Iniciar rotación automática
  setInterval(nextFrame, FRAME_DELAY);

})();