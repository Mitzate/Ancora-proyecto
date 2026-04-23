(function () {
  'use strict';

  const TOTAL_FRAMES = 9;
  const FRAME_DELAY  = 1050;   // ~1 segundo entre frames — elegante, apreciable
  const FADE_TIME    = 200;    // ms de fade suave
  const IMG_PATH     = 'img/prototipo360/';

  const img     = document.getElementById('protoImg');
  const counter = document.getElementById('protoCounter');

  if (!img) return;

  // Ocultar contador completamente
  if (counter) counter.style.display = 'none';

  // Pre-cargar todas las imágenes para evitar parpadeo
  const frames = [];
  for (let i = 1; i <= TOTAL_FRAMES; i++) {
    const image = new Image();
    image.src = IMG_PATH + i + '.png';
    frames.push(image);
  }

  let currentFrame = 0;

  // Transición de opacidad suave y lenta
  img.style.transition = 'opacity ' + (FADE_TIME / 1000) + 's ease';

  function nextFrame() {
    currentFrame = (currentFrame + 1) % TOTAL_FRAMES;
    const num = currentFrame + 1;

    // Fade out
    img.style.opacity = '0';

    setTimeout(function () {
      img.src = IMG_PATH + num + '.png';
      // Pequeño delay para que la imagen cargue antes del fade in
      requestAnimationFrame(function () {
        requestAnimationFrame(function () {
          img.style.opacity = '1';
        });
      });
    }, FADE_TIME);
  }

  setInterval(nextFrame, FRAME_DELAY);

}());