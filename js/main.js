document.addEventListener("DOMContentLoaded", function () {

    /* ── Spinner ── */
    window.addEventListener("load", function () {
        var spinner = document.getElementById("spinner");
        if (spinner) {
            spinner.style.opacity = "0";
            setTimeout(function () {
                spinner.style.display = "none";
            }, 700);
        }
    });

    /* ── WOW animations ── */
    if (typeof WOW !== "undefined") {
        new WOW().init();
    }

    /* ── Elementos principales ── */
    var acNav     = document.getElementById("acNav");
    var navLinks  = document.getElementById("navLinks");
    var navToggle = document.getElementById("navToggle");
    var backTop   = document.getElementById("backTop");

    function getNavHeight() {
        return acNav ? acNav.offsetHeight : 80;
    }

    /* ────────────────────────────────────────────────────────
       HELPER: scroll suave a un selector CSS con offset navbar
    ─────────────────────────────────────────────────────────── */
    function smoothScrollTo(selector) {
        var target = document.querySelector(selector);
        if (!target) return;
        var offsetTop =
            target.getBoundingClientRect().top +
            window.pageYOffset -
            getNavHeight();
        window.scrollTo({ top: offsetTop, behavior: "smooth" });
    }

    /* ── Scroll: navbar sombra + back-to-top ── */
    window.addEventListener("scroll", function () {
        if (acNav) {
            acNav.classList.toggle("ac-nav-scrolled", window.scrollY > 60);
        }
        if (backTop) {
            backTop.classList.toggle("show", window.scrollY > 300);
        }
        updateActiveLink();
    }, { passive: true });

    /* ── Menú móvil toggle ── */
    if (navToggle && navLinks) {
        navToggle.addEventListener("click", function () {
            navLinks.classList.toggle("open");
            navToggle.classList.toggle("open");
        });
    }

    /* ════════════════════════════════════════════════════════
       PRIORIDAD 1 — NAVBAR
       ─────────────────────────────────────────────────────────
       CAUSA RAÍZ DEL BUG:
       El selector original "document.querySelectorAll('.ac-nav-links a')"
       es correcto, pero la condición de desvío a window.location.href
       nunca se ejecutaba porque jQuery (cargado después en el HTML)
       puede agregar sus propios listeners que compiten con los listeners
       vanilla JS cuando preventDefault() ya fue llamado.

       SOLUCIÓN:
       1. Registrar el handler en la fase de CAPTURA (tercer argumento
          `true` en addEventListener). La fase de captura se ejecuta
          ANTES que cualquier listener de jQuery o Bootstrap, garantizando
          que nuestro handler sea el primero en procesar el evento.
       2. Para enlaces externos (.html) usar window.location.href explícito.
       3. Para anclas internas (#) usar smoothScrollTo.
       4. Para placeholders (#, #!) solo preventDefault sin más.
    ════════════════════════════════════════════════════════ */
    var navLinkEls = document.querySelectorAll(".ac-nav-links a");
    navLinkEls.forEach(function (link) {
        link.addEventListener("click", function (e) {
            var href = this.getAttribute("href");

            /* Siempre cerrar menú móvil primero */
            if (navLinks)  navLinks.classList.remove("open");
            if (navToggle) navToggle.classList.remove("open");

            /* Placeholder vacío o solo "#" — bloquear sin hacer nada */
            if (!href || href === "#" || href === "#!") {
                e.preventDefault();
                return;
            }

            /* ✅ ENLACE EXTERNO: login.html, registro.html, etc.
               Llamamos preventDefault() para cancelar el comportamiento
               nativo del navegador y luego navegamos de forma explícita.
               Esto evita que cualquier otro listener (jQuery, Bootstrap,
               section360.js, Owl Carousel) interfiera. */
            if (!href.startsWith("#")) {
                e.preventDefault();
                e.stopImmediatePropagation(); /* cancela listeners subsecuentes */
                window.location.href = href;
                return;
            }

            /* ✅ ANCLA INTERNA: #inicio, #acercade, #servicios, #contacto
               Prevenimos el salto nativo y aplicamos smooth scroll con
               offset de navbar. */
            e.preventDefault();
            smoothScrollTo(href);

        }, true); /* <-- true = fase de CAPTURA: se ejecuta antes que jQuery */
    });

    /* ════════════════════════════════════════════════════════
       PRIORIDAD 2 — FOOTER: enlace a privacy.html y otros
       ─────────────────────────────────────────────────────────
       CAUSA RAÍZ DEL BUG (doble listener):
       El selector original era "footer a, .ac-footer a".
       El <footer> en el HTML tiene AMBAS: es un <footer> (tag) Y
       tiene la clase .ac-footer. Por eso querySelectorAll devolvía
       el mismo nodo dos veces en el NodeList, y se registraban
       DOS event listeners en cada enlace del footer.

       El resultado: preventDefault() se llamaba dos veces, y en
       algunos navegadores el segundo listener volvía a procesar
       el evento ya prevenido, interfiriendo con window.location.href.

       SOLUCIÓN:
       1. Usar un Set para deduplicar los nodos antes de iterar.
       2. Registrar también en fase de captura para evitar interferencia
          de otros scripts (misma razón que el navbar).
       3. stopImmediatePropagation() para enlaces externos.
    ════════════════════════════════════════════════════════ */
    var footerLinkSet = new Set();
    document.querySelectorAll("footer a").forEach(function (el) { footerLinkSet.add(el); });
    document.querySelectorAll(".ac-footer a").forEach(function (el) { footerLinkSet.add(el); });

    footerLinkSet.forEach(function (link) {
        link.addEventListener("click", function (e) {
            var href = this.getAttribute("href");

            /* Placeholder vacío */
            if (!href || href === "#" || href === "#!") {
                e.preventDefault();
                return;
            }

            /* ✅ ENLACE EXTERNO: privacy.html, otras páginas */
            if (!href.startsWith("#")) {
                e.preventDefault();
                e.stopImmediatePropagation();
                window.location.href = href;
                return;
            }

            /* ✅ ANCLA INTERNA: smooth scroll con offset navbar */
            e.preventDefault();
            smoothScrollTo(href);

        }, true); /* fase de captura */
    });

    /* ── Active links según sección visible ── */
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

    /* ── Video section reveal con IntersectionObserver ── */
    var videoSection = document.getElementById("videoSection");
    if (videoSection && "IntersectionObserver" in window) {
        var videoObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    videoSection.classList.add("in-view");
                }
            });
        }, { threshold: 0.2 });
        videoObserver.observe(videoSection);
    }

    /* ── Back to top ── */
    if (backTop) {
        backTop.addEventListener("click", function (e) {
            e.preventDefault();
            window.scrollTo({ top: 0, behavior: "smooth" });
        });
    }

    /* ════════════════════════════════════════════════════════
       PRIORIDAD 3 — TEAM CAROUSEL (Owl Carousel + flechas)
       ─────────────────────────────────────────────────────────
       CAUSAS RAÍZ DEL BUG:

       A) TIMING: jQuery y Owl Carousel se cargan DESPUÉS del
          DOMContentLoaded (están al final del body en el HTML).
          Si este script corre demasiado rápido, $.fn.owlCarousel
          todavía no existe cuando se intenta inicializar.

       B) FLECHAS SIN RESPUESTA: Los triggers
          .trigger("prev.owl.carousel") y .trigger("next.owl.carousel")
          solo funcionan después de que owlCarousel() haya inicializado
          exitosamente. Si la inicialización falló silenciosamente (por
          el problema de timing), los triggers no hacen nada.

       C) WOW.js: Si el wrapper del carrusel tiene wow fadeIn y está
          en opacity:0 cuando Owl intenta medir el ancho de los items,
          Owl calcula width:0 y el carrusel queda roto (items sin ancho).
          El HTML ya removió wow del wrapper — esto está bien.

       SOLUCIÓN:
       1. Usar window.addEventListener("load") en lugar de
          document.addEventListener("DOMContentLoaded"). El evento
          "load" se dispara DESPUÉS de que todos los scripts externos
          (jQuery, Owl Carousel) han terminado de cargarse y ejecutarse,
          eliminando el problema de timing completamente.

       2. Verificar que $.fn.owlCarousel exista antes de inicializar.

       3. Usar delegación de eventos $(document).on() para las flechas,
          que funciona independientemente del orden de inicialización.

       4. Pasar duración explícita [300] al trigger de Owl.

       5. Llamar .trigger("refresh.owl.carousel") después de inicializar
          para forzar a Owl a recalcular dimensiones en caso de que el
          carrusel haya sido inicializado mientras estaba oculto.
    ════════════════════════════════════════════════════════ */
    window.addEventListener("load", function () {

        if (typeof jQuery === "undefined") {
            console.warn("Ancora: jQuery no está disponible. Verifica que ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js cargue correctamente.");
            return;
        }

        jQuery(function ($) {

            /* ── Team Carousel ── */
            var $carousel = $(".ac-team-carousel");

            if (!$carousel.length) {
                console.warn("Ancora: .ac-team-carousel no encontrado en el DOM.");
                return;
            }

            if (typeof $.fn.owlCarousel === "undefined") {
                console.warn("Ancora: owl.carousel.min.js no está cargado. Verifica la ruta lib/owlcarousel/owl.carousel.min.js");
                return;
            }

            /* Destruir instancia previa si existiera (evita doble init) */
            if ($carousel.hasClass("owl-loaded")) {
                $carousel.trigger("destroy.owl.carousel");
                $carousel.removeClass("owl-loaded owl-drag");
            }

            $carousel.owlCarousel({
                loop: true,
                margin: 20,
                nav: false,
                dots: false,
                autoplay: false,
                smartSpeed: 400,
                responsive: {
                    0:    { items: 1 },
                    576:  { items: 2 },
                    768:  { items: 3 },
                    1200: { items: 4 }
                },
                onInitialized: function () {
                    /*
                       ✅ FIX TIMING: Después de inicializar, forzamos
                       un refresh para que Owl recalcule dimensiones
                       correctamente aunque el contenedor haya estado
                       oculto previamente (ej. por WOW.js).
                    */
                    setTimeout(function () {
                        $carousel.trigger("refresh.owl.carousel");
                    }, 150);
                }
            });

            /*
               ✅ FLECHAS: Delegación de eventos en document.
               Funciona aunque los botones se rendericen tarde.
               stopPropagation() evita que el clic suba al handler
               del navbar o footer por error.
            */
            $(document).on("click", "#teamPrev", function (e) {
                e.stopPropagation();
                $carousel.trigger("prev.owl.carousel", [300]);
            });

            $(document).on("click", "#teamNext", function (e) {
                e.stopPropagation();
                $carousel.trigger("next.owl.carousel", [300]);
            });

            /* ── Testimonial Carousel (si existe en la página) ── */
            if ($(".testimonial-carousel").length) {
                $(".testimonial-carousel").owlCarousel({
                    items: 1,
                    autoplay: true,
                    smartSpeed: 1000,
                    animateIn: "fadeIn",
                    animateOut: "fadeOut",
                    dots: true,
                    loop: true,
                    nav: false
                });
            }

        }); /* jQuery ready */

    }); /* window load */

}); /* DOMContentLoaded */