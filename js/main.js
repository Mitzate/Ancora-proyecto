/* =========================================================
   ANCORA — main.js limpio y funcional
   v10 — Fix: team grid estático + privacy link funcional
========================================================= */

console.log("[Ancora] main.js v10 cargado");

/* =========================
   SPINNER
========================= */
window.addEventListener("load", function () {
    const spinner = document.getElementById("spinner");
    if (spinner) {
        spinner.style.opacity = "0";
        setTimeout(() => {
            spinner.style.display = "none";
        }, 500);
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

    const nav        = document.getElementById("acNav");
    const navToggle  = document.getElementById("navToggle");
    const navLinks   = document.getElementById("navLinks");
    const backTop    = document.getElementById("backTop");

    /* ── NAV TOGGLE (mobile) ── */
    if (navToggle && navLinks) {
        navToggle.addEventListener("click", function () {
            navLinks.classList.toggle("open");
            navToggle.classList.toggle("open");
        });
    }

    /* ── SMOOTH SCROLL ──────────────────────────────────────
       IMPORTANT: Only intercept pure anchor links (#section).
       Links that go to real pages (login.html, privacy.html,
       any href without "#" as first char) are left alone so
       the browser navigates normally.
    ─────────────────────────────────────────────────────── */
    document.querySelectorAll('a[href^="#"]').forEach(link => {
        link.addEventListener("click", function (e) {
            const href = this.getAttribute("href");

            // Safety: ignore empty, "#" and "#!" placeholders
            if (!href || href === "#" || href === "#!") {
                e.preventDefault();
                return;
            }

            const target = document.querySelector(href);

            if (target) {
                e.preventDefault();

                const navHeight = nav ? nav.offsetHeight : 80;
                const top       = target.getBoundingClientRect().top
                                  + window.scrollY
                                  - navHeight;

                window.scrollTo({ top, behavior: "smooth" });

                if (navLinks) navLinks.classList.remove("open");
                if (navToggle) navToggle.classList.remove("open");
            }
            // If target not found, let the browser handle it normally
        });
    });

    /* ── FOOTER: privacy link hard-navigation fix ──────────
       The footer <a href="privacy.html"> does NOT start with
       "#", so the smooth-scroll listener above already skips
       it. This block is an extra safety net: it finds any
       footer link pointing to a real .html file and ensures
       a clean page navigation (no preventDefault, no scroll).
    ─────────────────────────────────────────────────────── */
    document.querySelectorAll('.ac-footer a[href$=".html"]').forEach(link => {
        link.addEventListener("click", function () {
            // Remove open mobile menu if present, then navigate normally
            if (navLinks) navLinks.classList.remove("open");
            if (navToggle) navToggle.classList.remove("open");
            // DO NOT call e.preventDefault() — let the browser navigate
        });
    });

    /* ── SCROLL EVENTS ── */
    window.addEventListener("scroll", function () {
        if (nav) {
            nav.classList.toggle("ac-nav-scrolled", window.scrollY > 60);
        }
        if (backTop) {
            backTop.classList.toggle("show", window.scrollY > 300);
        }
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
        const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    videoSection.classList.add("in-view");
                }
            });
        }, { threshold: 0.2 });

        observer.observe(videoSection);
    }

    /* ==============================================
       TEAM SECTION — Static grid (no carousel)
       The Owl Carousel markup is replaced by a CSS
       grid in the HTML patch. This block only runs
       if (for some reason) the old carousel markup
       is still present, to avoid JS errors.
    ============================================== */
    if (typeof jQuery !== "undefined" && typeof jQuery.fn.owlCarousel !== "undefined") {
        const $carousel = jQuery("#teamCarousel");
        if ($carousel.length && $carousel.hasClass("owl-carousel")) {
            // Only init if still using carousel markup
            setTimeout(function () {
                $carousel.owlCarousel({
                    loop: true, margin: 20, nav: false, dots: false,
                    autoplay: false, smartSpeed: 600,
                    mouseDrag: true, touchDrag: true, pullDrag: true,
                    responsive: {
                        0:    { items: 1 },
                        576:  { items: 2 },
                        768:  { items: 3 },
                        1200: { items: 6 }
                    }
                });

                document.getElementById("teamPrev")?.addEventListener("click", function (e) {
                    e.preventDefault();
                    $carousel.trigger("prev.owl.carousel");
                });
                document.getElementById("teamNext")?.addEventListener("click", function (e) {
                    e.preventDefault();
                    $carousel.trigger("next.owl.carousel");
                });

                console.log("[Ancora] Carrusel (legacy) inicializado");
            }, 300);
        }
    }

}); // end DOMContentLoaded