/**
 * ANCORA — main.js  (versión defensiva final)
 *
 * Estrategia de diseño:
 * ─────────────────────
 * Cada bloque funcional está envuelto en try/catch independiente.
 * Un error en cualquier sección NO detiene el resto del script.
 *
 * Todos los listeners de clic usan fase de CAPTURA (tercer arg = true)
 * para ejecutarse ANTES que cualquier handler de jQuery, Bootstrap,
 * section360.js u Owl Carousel.
 *
 * El carrusel se inicializa en window "load" (no DOMContentLoaded)
 * para garantizar que jQuery y Owl estén completamente cargados.
 */

/* ── Diagnóstico de carga (visible en consola del navegador) ── */
console.log("[Ancora] main.js cargado —", new Date().toLocaleTimeString());

/* ════════════════════════════════════════════════════════════════
   BLOQUE 1 — SPINNER
   ════════════════════════════════════════════════════════════════ */
try {
    window.addEventListener("load", function () {
        var spinner = document.getElementById("spinner");
        if (spinner) {
            spinner.style.transition = "opacity 0.6s ease-out";
            spinner.style.opacity   = "0";
            setTimeout(function () { spinner.style.display = "none"; }, 700);
        }
    });
} catch (err) {
    console.warn("[Ancora] spinner:", err.message);
}

/* ════════════════════════════════════════════════════════════════
   BLOQUE 2 — WOW ANIMATIONS
   ════════════════════════════════════════════════════════════════ */
try {
    if (typeof WOW !== "undefined") {
        new WOW().init();
        console.log("[Ancora] WOW.js inicializado.");
    }
} catch (err) {
    console.warn("[Ancora] WOW.js:", err.message);
}

/* ════════════════════════════════════════════════════════════════
   BLOQUE 3 — NAVBAR + FOOTER + SCROLL
   ════════════════════════════════════════════════════════════════ */
document.addEventListener("DOMContentLoaded", function () {

    console.log("[Ancora] DOMContentLoaded — navbar, footer, scroll.");

    var acNav     = document.getElementById("acNav");
    var navLinks  = document.getElementById("navLinks");
    var navToggle = document.getElementById("navToggle");
    var backTop   = document.getElementById("backTop");

    function getNavHeight() {
        return acNav ? acNav.offsetHeight : 80;
    }

    function smoothScrollTo(selector) {
        try {
            var target = document.querySelector(selector);
            if (!target) return;
            var top = target.getBoundingClientRect().top + window.pageYOffset - getNavHeight();
            window.scrollTo({ top: top, behavior: "smooth" });
        } catch (err) {
            console.warn("[Ancora] smoothScrollTo:", err.message);
        }
    }

    /* ── Scroll: navbar sombra + back-to-top ── */
    try {
        window.addEventListener("scroll", function () {
            if (acNav)   acNav.classList.toggle("ac-nav-scrolled", window.scrollY > 60);
            if (backTop) backTop.classList.toggle("show", window.scrollY > 300);
            updateActiveLink();
        }, { passive: true });
    } catch (err) {
        console.warn("[Ancora] scroll handler:", err.message);
    }

    /* ── Menú móvil toggle ── */
    try {
        if (navToggle && navLinks) {
            navToggle.addEventListener("click", function () {
                navLinks.classList.toggle("open");
                navToggle.classList.toggle("open");
            });
        }
    } catch (err) {
        console.warn("[Ancora] mobile menu:", err.message);
    }

    /* ──────────────────────────────────────────────────────────
       NAVBAR — Clics (fase de CAPTURA, antes que cualquier otro script)
    ────────────────────────────────────────────────────────── */
    try {
        var navLinkEls = document.querySelectorAll(".ac-nav-links a");
        console.log("[Ancora] Navbar links encontrados:", navLinkEls.length);

        navLinkEls.forEach(function (link) {
            link.addEventListener("click", function (e) {
                var href = this.getAttribute("href");
                console.log("[Ancora] Navbar clic →", href);

                if (navLinks)  navLinks.classList.remove("open");
                if (navToggle) navToggle.classList.remove("open");

                if (!href || href === "#" || href === "#!") {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    return;
                }

                /* Enlace externo: login.html, etc. */
                if (!href.startsWith("#")) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    console.log("[Ancora] Redirigiendo a:", href);
                    window.location.href = href;
                    return;
                }

                /* Ancla interna: smooth scroll */
                e.preventDefault();
                e.stopImmediatePropagation();
                smoothScrollTo(href);

            }, true); /* ← fase de CAPTURA */
        });

    } catch (err) {
        console.error("[Ancora] navbar links:", err.message);
    }

    /* ──────────────────────────────────────────────────────────
       FOOTER — Clics (Set para evitar doble listener)
    ────────────────────────────────────────────────────────── */
    try {
        var footerSet = new Set();
        document.querySelectorAll("footer a").forEach(function (el) { footerSet.add(el); });
        document.querySelectorAll(".ac-footer a").forEach(function (el) { footerSet.add(el); });

        console.log("[Ancora] Footer links (deduplicados):", footerSet.size);

        footerSet.forEach(function (link) {
            link.addEventListener("click", function (e) {
                var href = this.getAttribute("href");
                console.log("[Ancora] Footer clic →", href);

                if (!href || href === "#" || href === "#!") {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    return;
                }

                if (!href.startsWith("#")) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    console.log("[Ancora] Footer redirigiendo a:", href);
                    window.location.href = href;
                    return;
                }

                e.preventDefault();
                e.stopImmediatePropagation();
                smoothScrollTo(href);

            }, true); /* ← fase de CAPTURA */
        });

    } catch (err) {
        console.error("[Ancora] footer links:", err.message);
    }

    /* ── Active links según sección visible ── */
    try {
        var sections  = document.querySelectorAll("section[id]");
        var menuLinks = document.querySelectorAll(".ac-nav-links a:not(.ac-nav-cta)");

        function updateActiveLink() {
            var scrollPos = window.scrollY + getNavHeight() + 50;
            sections.forEach(function (section) {
                var top    = section.offsetTop;
                var bottom = top + section.offsetHeight;
                if (scrollPos >= top && scrollPos < bottom) {
                    menuLinks.forEach(function (link) {
                        link.classList.remove("active");
                        if (link.getAttribute("href") === "#" + section.id) {
                            link.classList.add("active");
                        }
                    });
                }
            });
        }

        /* Hacer updateActiveLink accesible para el scroll handler de arriba */
        window.__ancoraUpdateActive = updateActiveLink;

    } catch (err) {
        console.warn("[Ancora] active links:", err.message);
        window.__ancoraUpdateActive = function () {};
    }

    function updateActiveLink() {
        if (typeof window.__ancoraUpdateActive === "function") {
            window.__ancoraUpdateActive();
        }
    }

    /* ── Video reveal ── */
    try {
        var videoSection = document.getElementById("videoSection");
        if (videoSection && "IntersectionObserver" in window) {
            new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        videoSection.classList.add("in-view");
                    }
                });
            }, { threshold: 0.2 }).observe(videoSection);
        }
    } catch (err) {
        console.warn("[Ancora] video observer:", err.message);
    }

    /* ── Back to top ── */
    try {
        if (backTop) {
            backTop.addEventListener("click", function (e) {
                e.preventDefault();
                window.scrollTo({ top: 0, behavior: "smooth" });
            });
        }
    } catch (err) {
        console.warn("[Ancora] back-to-top:", err.message);
    }

    console.log("[Ancora] DOMContentLoaded — completado.");

}); /* fin DOMContentLoaded */

/* ════════════════════════════════════════════════════════════════
   BLOQUE 4 — TEAM CAROUSEL
   window "load" garantiza que jQuery y Owl estén 100% ejecutados
   ════════════════════════════════════════════════════════════════ */
window.addEventListener("load", function () {

    console.log("[Ancora] window load — iniciando carrusel.");

    try {

        if (typeof jQuery === "undefined") {
            console.error("[Ancora] jQuery no disponible. Verifica que cargue desde ajax.googleapis.com");
            return;
        }

        jQuery(function ($) {

            if (typeof $.fn.owlCarousel === "undefined") {
                console.error("[Ancora] Owl Carousel no disponible. Verifica: lib/owlcarousel/owl.carousel.min.js");
                return;
            }

            var $carousel = $(".ac-team-carousel");

            if (!$carousel.length) {
                console.warn("[Ancora] .ac-team-carousel no encontrado en el DOM.");
                return;
            }

            console.log("[Ancora] Owl: slides encontrados =",
                $carousel.find(".ac-team-slide").length);

            /* Destruir instancia previa si ya estaba inicializada */
            if ($carousel.hasClass("owl-loaded")) {
                $carousel.trigger("destroy.owl.carousel");
                $carousel.removeClass("owl-loaded owl-drag");
                console.log("[Ancora] Owl: instancia previa destruida.");
            }

            $carousel.owlCarousel({
                loop       : true,
                margin     : 20,
                nav        : false,
                dots       : false,
                autoplay   : false,
                smartSpeed : 400,
                responsive : {
                    0    : { items: 1 },
                    576  : { items: 2 },
                    768  : { items: 3 },
                    1200 : { items: 4 }
                },
                onInitialized: function (event) {
                    console.log("[Ancora] Owl inicializado. Items:", event.item.count);
                    setTimeout(function () {
                        $carousel.trigger("refresh.owl.carousel");
                        console.log("[Ancora] Owl refreshed.");
                    }, 150);
                }
            });

            /* ── Flechas: off().on() previene acumulación de listeners ── */
            $(document).off("click.ancora", "#teamPrev").on("click.ancora", "#teamPrev", function (e) {
                e.stopPropagation();
                console.log("[Ancora] Flecha PREV.");
                $carousel.trigger("prev.owl.carousel", [300]);
            });

            $(document).off("click.ancora", "#teamNext").on("click.ancora", "#teamNext", function (e) {
                e.stopPropagation();
                console.log("[Ancora] Flecha NEXT.");
                $carousel.trigger("next.owl.carousel", [300]);
            });

            console.log("[Ancora] Flechas #teamPrev y #teamNext conectadas.");

            /* ── Testimonial Carousel opcional ── */
            if ($(".testimonial-carousel").length) {
                $(".testimonial-carousel").owlCarousel({
                    items: 1, autoplay: true, smartSpeed: 1000,
                    animateIn: "fadeIn", animateOut: "fadeOut",
                    dots: true, loop: true, nav: false
                });
            }

        }); /* jQuery ready */

    } catch (err) {
        console.error("[Ancora] Carrusel error crítico:", err.message);
    }

    console.log("[Ancora] window load — completado.");

}); /* fin window load */