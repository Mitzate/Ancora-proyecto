/* =========================================================
   ANCORA — main.js v11
   Carousel vanilla integrado — sin Owl, sin jQuery para team
========================================================= */

console.log("[Ancora] main.js v11 cargado");

/* =========================
   SPINNER
========================= */
window.addEventListener("load", function () {
    const spinner = document.getElementById("spinner");
    if (spinner) {
        spinner.style.opacity = "0";
        setTimeout(() => { spinner.style.display = "none"; }, 500);
    }
});

/* =========================
   WOW
========================= */
if (typeof WOW !== "undefined") {
    new WOW().init();
}

/* =========================
   DOM READY
========================= */
document.addEventListener("DOMContentLoaded", function () {

    const nav       = document.getElementById("acNav");
    const navToggle = document.getElementById("navToggle");
    const navLinks  = document.getElementById("navLinks");
    const backTop   = document.getElementById("backTop");

    /* ── NAV TOGGLE (mobile) ── */
    if (navToggle && navLinks) {
        navToggle.addEventListener("click", function () {
            navLinks.classList.toggle("open");
            navToggle.classList.toggle("open");
        });
    }

    /* ── SMOOTH SCROLL ──────────────────────────────────────
       Solo intercepta anclas puras (#seccion).
       Links reales como login.html y privacy.html se ignoran
       para que el browser navegue normalmente.
    ─────────────────────────────────────────────────────── */
    document.querySelectorAll('a[href^="#"]').forEach(link => {
        link.addEventListener("click", function (e) {
            const href = this.getAttribute("href");

            if (!href || href === "#" || href === "#!") {
                e.preventDefault();
                return;
            }

            const target = document.querySelector(href);

            if (target) {
                e.preventDefault();

                const navHeight = nav ? nav.offsetHeight : 80;
                const top = target.getBoundingClientRect().top + window.scrollY - navHeight;

                window.scrollTo({ top, behavior: "smooth" });

                if (navLinks) navLinks.classList.remove("open");
                if (navToggle) navToggle.classList.remove("open");
            }
        });
    });

    /* ── FOOTER: privacy link — navegación directa ── */
    document.querySelectorAll('.ac-footer a[href$=".html"]').forEach(link => {
        link.addEventListener("click", function () {
            if (navLinks) navLinks.classList.remove("open");
            if (navToggle) navToggle.classList.remove("open");
        });
    });

    /* ── SCROLL EVENTS ── */
    window.addEventListener("scroll", function () {
        if (nav) nav.classList.toggle("ac-nav-scrolled", window.scrollY > 60);
        if (backTop) backTop.classList.toggle("show", window.scrollY > 300);
    });

    /* ── BACK TO TOP ── */
    if (backTop) {
        backTop.addEventListener("click", function (e) {
            e.preventDefault();
            window.scrollTo({ top: 0, behavior: "smooth" });
        });
    }

    /* ── VIDEO SECTION REVEAL ── */
    const videoSection = document.getElementById("videoSection");
    if (videoSection && "IntersectionObserver" in window) {
        new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) videoSection.classList.add("in-view");
            });
        }, { threshold: 0.2 }).observe(videoSection);
    }

    /* ════════════════════════════════════════════════════════
       TEAM CAROUSEL — Vanilla JS puro (sin Owl, sin jQuery)
    ════════════════════════════════════════════════════════ */
    const track    = document.getElementById("acvTrack");
    const viewport = document.getElementById("acvViewport");
    const btnPrev  = document.getElementById("acvPrev");
    const btnNext  = document.getElementById("acvNext");
    const dotsWrap = document.getElementById("acvDots");

    if (track && viewport && btnPrev && btnNext) {

        const slides  = Array.from(track.querySelectorAll(".acv-slide"));
        const total   = slides.length;
        const GAP     = 20;
        let current   = 0;
        let visible   = 4;
        let slideW    = 0;
        let animating = false;

        /* Cuántos slides caben según el ancho del viewport */
        function calcVisible() {
            const vw = viewport.clientWidth;
            if      (vw <= 575) visible = 1;
            else if (vw <= 991) visible = 3;
            else                visible = 4;

            slideW = (vw - GAP * (visible - 1)) / visible + GAP;
        }

        /* Mover el track al índice indicado */
        function goTo(index, animate) {
            const maxIndex = Math.max(0, total - visible);
            current = Math.max(0, Math.min(index, maxIndex));

            track.style.transition = animate
                ? "transform 0.48s cubic-bezier(0.22,1,0.36,1)"
                : "none";

            track.style.transform = `translateX(${-(current * slideW)}px)`;

            updateDots();
            updateButtons();
        }

        /* Estado de los botones */
        function updateButtons() {
            btnPrev.disabled = current <= 0;
            btnNext.disabled = current >= total - visible;
        }

        /* Crear dots */
        function buildDots() {
            if (!dotsWrap) return;
            dotsWrap.innerHTML = "";
            const steps = total - visible + 1;
            for (let i = 0; i < steps; i++) {
                const dot = document.createElement("button");
                dot.type = "button";
                dot.className = "acv-dot" + (i === 0 ? " active" : "");
                dot.setAttribute("aria-label", `Posición ${i + 1}`);
                dot.addEventListener("click", () => goTo(i, true));
                dotsWrap.appendChild(dot);
            }
        }

        /* Actualizar dot activo */
        function updateDots() {
            if (!dotsWrap) return;
            dotsWrap.querySelectorAll(".acv-dot").forEach((dot, i) => {
                dot.classList.toggle("active", i === current);
            });
        }

        /* Inicializar */
        function init() {
            calcVisible();
            buildDots();
            goTo(0, false);
            requestAnimationFrame(() => {
                track.style.transition = "transform 0.48s cubic-bezier(0.22,1,0.36,1)";
            });
        }

        /* Clicks en flechas */
        btnPrev.addEventListener("click", () => { if (!animating) goTo(current - 1, true); });
        btnNext.addEventListener("click", () => { if (!animating) goTo(current + 1, true); });

        /* Bloquear clicks durante la animación */
        track.addEventListener("transitionstart", () => { animating = true; });
        track.addEventListener("transitionend",   () => { animating = false; });

        /* Touch / Swipe */
        let touchStartX = 0;
        let touchDeltaX = 0;

        viewport.addEventListener("touchstart", e => {
            touchStartX = e.touches[0].clientX;
            touchDeltaX = 0;
        }, { passive: true });

        viewport.addEventListener("touchmove", e => {
            touchDeltaX = e.touches[0].clientX - touchStartX;
        }, { passive: true });

        viewport.addEventListener("touchend", () => {
            if (Math.abs(touchDeltaX) > 40) {
                goTo(touchDeltaX < 0 ? current + 1 : current - 1, true);
            }
        });

        /* Recalcular en resize */
        let resizeTimer;
        window.addEventListener("resize", () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {
                calcVisible();
                buildDots();
                goTo(current, false);
            }, 150);
        });

        init();
        console.log("[Ancora] Carousel vanilla inicializado");
    }

}); // end DOMContentLoaded