(function () {
    "use strict";

    const TOTAL_FRAMES = 9;
    const FRAME_DELAY = 950;
    const FADE_TIME = 180;
    const IMG_PATH = "img/prototipo360/";

    const img = document.getElementById("protoImg");

    if (!img) return;

    let current = 1;
    const preloaded = [];

    for (let i = 1; i <= TOTAL_FRAMES; i++) {
        const frame = new Image();
        frame.src = `${IMG_PATH}${i}.png`;
        preloaded.push(frame);
    }

    img.style.transition = `opacity ${FADE_TIME}ms ease`;

    function changeFrame() {
        current++;
        if (current > TOTAL_FRAMES) current = 1;

        img.style.opacity = "0";

        setTimeout(() => {
            img.src = `${IMG_PATH}${current}.png`;
            img.style.opacity = "1";
        }, FADE_TIME);
    }

    setInterval(changeFrame, FRAME_DELAY);

})();