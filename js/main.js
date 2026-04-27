/* =========================================================
   ANCORA — main.js v12
   Fix: carousel init con timing correcto + privacy link
========================================================= */

console.log("[Ancora] main.js v12 cargado");

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
       y el browser navega normalmente.
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

    /* ── FOOTER: privacy.html — forzar navegación directa ──
       Busca CUALQUIER link en el footer que apunte a .html
       y lo ejecuta con window.location para garantizar la
       navegación sin importar otros listeners activos.
    ─────────────────────────────────────────────────────── */
    document.querySelectorAll('.ac-footer a').forEach(link => {
        const href = link.getAttribute("href");
        if (!href || href.startsWith("#")) return; /* solo links reales */

        link.addEventListener("click", function (e) {
            e.preventDefault();
            e.stopPropagation();
            if (navLinks) navLinks.classList.remove("open");
            if (navToggle) navToggle.classList.remove("open");
            window.location.href = href;
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
       TEAM CAROUSEL — Vanilla JS puro
       FIX v12: init() se ejecuta en window.load para que
       viewport.clientWidth ya tenga las dimensiones reales.
       En DOMContentLoaded el layout aún no está pintado y
       clientWidth puede devolver 0, haciendo que slideW = 0
       y las flechas muevan 0px (parecen no funcionar).
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

        function calcVisible() {
            const vw = viewport.clientWidth;
            if      (vw <= 575) visible = 1;
            else if (vw <= 991) visible = 3;
            else                visible = 4;
            slideW = (vw - GAP * (visible - 1)) / visible + GAP;
        }

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

        function updateButtons() {
            btnPrev.disabled = current <= 0;
            btnNext.disabled = current >= total - visible;
        }

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

        function updateDots() {
            if (!dotsWrap) return;
            dotsWrap.querySelectorAll(".acv-dot").forEach((dot, i) => {
                dot.classList.toggle("active", i === current);
            });
        }

        function init() {
            calcVisible();
            buildDots();
            /* Sin animación en la posición inicial */
            track.style.transition = "none";
            track.style.transform  = "translateX(0px)";
            updateButtons();
            updateDots();
            /* Habilitar animaciones tras un frame */
            requestAnimationFrame(() => {
                track.style.transition = "transform 0.48s cubic-bezier(0.22,1,0.36,1)";
            });
            console.log("[Ancora] Carousel init — slideW:", slideW, "visible:", visible);
        }

        /* Clicks en flechas */
        btnPrev.addEventListener("click", () => {
            if (!animating) goTo(current - 1, true);
        });
        btnNext.addEventListener("click", () => {
            if (!animating) goTo(current + 1, true);
        });

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

        /* Resize */
        let resizeTimer;
        window.addEventListener("resize", () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {
                calcVisible();
                buildDots();
                goTo(current, false);
            }, 150);
        });

        /* ── CLAVE DEL FIX: inicializar en window.load, no en
           DOMContentLoaded, para que clientWidth sea correcto ── */
        if (document.readyState === "complete") {
            /* La página ya cargó (ej: script diferido) */
            init();
        } else {
            window.addEventListener("load", init);
        }
    }

}); // end DOMContentLoaded