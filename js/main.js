/* ════════════════════════════════════════════════
   ANCORA — main.js
   Sin IIFE, sin wrappers. Código directo y simple.
   ════════════════════════════════════════════════ */

document.addEventListener("DOMContentLoaded", function () {

    /* ── Spinner ── */
    window.addEventListener("load", function () {
        var sp = document.getElementById("spinner");
        if (sp) {
            sp.style.opacity = "0";
            setTimeout(function () { sp.style.display = "none"; }, 700);
        }
    });

    /* ── WOW animations ── */
    if (typeof WOW !== "undefined") {
        new WOW().init();
    }

    /* ── Navbar: añadir clase al hacer scroll ── */
    var acNav   = document.getElementById("acNav");
    var backTop = document.getElementById("backTop");

    window.addEventListener("scroll", function () {
        if (acNav)   acNav.classList.toggle("ac-nav-scrolled", window.scrollY > 60);
        if (backTop) backTop.classList.toggle("show", window.scrollY > 300);
    }, { passive: true });

    /* ── Mobile menu toggle ── */
    var navToggle = document.getElementById("navToggle");
    var navLinks  = document.getElementById("navLinks");

    if (navToggle && navLinks) {
        navToggle.addEventListener("click", function () {
            navLinks.classList.toggle("open");
            navToggle.classList.toggle("open");
        });
    }

    /* ════════════════════════════════════════════
       NAVBAR — scroll suave a secciones
       Los IDs del HTML son:
         #inicio   #acercade   #servicios   #contacto
    ════════════════════════════════════════════ */
    var navHeight = acNav ? acNav.offsetHeight : 80;

    document.querySelectorAll(".ac-nav-links a").forEach(function (link) {
        link.addEventListener("click", function (e) {
            var href = link.getAttribute("href");

            /* Login → ir a login.html normalmente */
            if (!href || href === "login.html") return;

            /* Solo actuar con anclas internas */
            if (!href.startsWith("#")) return;

            /* Prevenir #! y otros placeholders */
            if (href === "#" || href === "#!") {
                e.preventDefault();
                return;
            }

            var target = document.getElementById(href.slice(1)); /* quita el # */
            if (!target) return;

            e.preventDefault();

            /* Cerrar menú móvil si está abierto */
            if (navLinks) navLinks.classList.remove("open");
            if (navToggle) navToggle.classList.remove("open");

            var offsetTop = target.getBoundingClientRect().top + window.scrollY - navHeight - 16;
            window.scrollTo({ top: offsetTop, behavior: "smooth" });
        });
    });

    /* ── Active link al hacer scroll ── */
    var allSections = document.querySelectorAll("section[id]");
    var allNavLinks = document.querySelectorAll(".ac-nav-links a:not(.ac-nav-cta)");

    window.addEventListener("scroll", function () {
        var scrollPos = window.scrollY + navHeight + 40;
        allSections.forEach(function (section) {
            if (scrollPos >= section.offsetTop &&
                scrollPos < section.offsetTop + section.offsetHeight) {
                allNavLinks.forEach(function (a) {
                    a.classList.remove("active");
                    if (a.getAttribute("href") === "#" + section.id) {
                        a.classList.add("active");
                    }
                });
            }
        });
    }, { passive: true });

    /* ── Video: añadir clase in-view cuando entra en pantalla ── */
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

    /* ── Back to top ── */
    if (backTop) {
        backTop.addEventListener("click", function (e) {
            e.preventDefault();
            window.scrollTo({ top: 0, behavior: "smooth" });
        });
    }

    /* ════════════════════════════════════════════
       TEAM CAROUSEL — Owl Carousel con jQuery
    ════════════════════════════════════════════ */
    if (typeof jQuery !== "undefined") {
        jQuery(function ($) {

            var $carousel = $(".ac-team-carousel");

            if ($carousel.length) {
                $carousel.owlCarousel({
                    loop:     true,
                    margin:   20,
                    nav:      false,
                    dots:     false,
                    autoplay: false,
                    responsive: {
                        0:    { items: 1 },
                        576:  { items: 2 },
                        768:  { items: 3 },
                        1200: { items: 4 }
                    }
                });

                var teamPrev = document.getElementById("teamPrev");
                var teamNext = document.getElementById("teamNext");

                if (teamPrev) {
                    teamPrev.addEventListener("click", function () {
                        $carousel.trigger("prev.owl.carousel");
                    });
                }
                if (teamNext) {
                    teamNext.addEventListener("click", function () {
                        $carousel.trigger("next.owl.carousel");
                    });
                }
            }

            /* ── Testimonials carousel (si existe) ── */
            if ($(".testimonial-carousel").length) {
                $(".testimonial-carousel").owlCarousel({
                    items:      1,
                    autoplay:   true,
                    smartSpeed: 1000,
                    animateIn:  "fadeIn",
                    animateOut: "fadeOut",
                    dots:       true,
                    loop:       true,
                    nav:        false
                });
            }
        });
    }

});