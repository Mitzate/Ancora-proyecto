(function () {
    "use strict";

    /* ── Spinner ── */
    window.addEventListener("load", function () {
        var sp = document.getElementById("spinner");
        if (sp) {
            sp.style.opacity = "0";
            setTimeout(function () { sp.style.display = "none"; }, 700);
        }
    });

    /* ── WOW Animations ── */
    if (typeof WOW !== "undefined") new WOW().init();

    /* ── Navbar scroll ── */
    var nav     = document.getElementById("acNav");
    var backTop = document.getElementById("backTop");

    window.addEventListener("scroll", function () {
        if (nav)     nav.classList.toggle("ac-nav-scrolled", window.scrollY > 60);
        if (backTop) backTop.classList.toggle("show", window.scrollY > 300);
    }, { passive: true });

    /* ── Mobile toggle ── */
    var toggle   = document.getElementById("navToggle");
    var navLinks = document.getElementById("navLinks");

    if (toggle && navLinks) {
        toggle.addEventListener("click", function () {
            navLinks.classList.toggle("open");
            toggle.classList.toggle("open");
        });
    }

    /* ════════════════════════════════════════════
       ANCLAS — scroll suave a cada sección
       Calcula offset con altura del navbar
    ════════════════════════════════════════════ */
    document.querySelectorAll('a[href^="#"]').forEach(function (a) {
        a.addEventListener("click", function (e) {
            var href = this.getAttribute("href");
            if (!href || href === "#" || href === "#!") return;

            var target = document.querySelector(href);
            if (!target) return;

            e.preventDefault();

            // Cierra menú mobile si está abierto
            if (navLinks) navLinks.classList.remove("open");
            if (toggle)   toggle.classList.remove("open");

            var navH = nav ? nav.offsetHeight : 80;
            var y    = target.getBoundingClientRect().top + window.scrollY - navH - 8;
            window.scrollTo({ top: y, behavior: "smooth" });
        });
    });

    /* ── Active link highlight al hacer scroll ── */
    var sections = document.querySelectorAll("section[id]");
    var navAs    = document.querySelectorAll(".ac-nav-links a[data-nav]");

    window.addEventListener("scroll", function () {
        var scrollY = window.scrollY + 120;
        sections.forEach(function (sec) {
            if (scrollY >= sec.offsetTop && scrollY < sec.offsetTop + sec.offsetHeight) {
                navAs.forEach(function (a) {
                    a.classList.remove("active");
                    if (a.getAttribute("href") === "#" + sec.id) a.classList.add("active");
                });
            }
        });
    }, { passive: true });

    /* ════════════════════════════════════════════
       LOGIN — redirige a login.html
       (el modal ya no se usa — login.js maneja
       loginForm, loginEmail, etc. en login.html)
    ════════════════════════════════════════════ */
    var openLogin = document.getElementById("openLogin");
    var modal     = document.getElementById("loginModal");

    if (openLogin) {
        openLogin.addEventListener("click", function (e) {
            e.preventDefault();
            window.location.href = "login.html";
        });
    }

    /* Escape cierra el modal por si acaso queda abierto */
    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape" && modal) {
            modal.classList.remove("open");
            document.body.style.overflow = "";
        }
    });

    /* ════════════════════════════════════════════
       TEAM CAROUSEL — flechas funcionando
    ════════════════════════════════════════════ */
    $(document).ready(function () {
        var $c = $(".ac-team-carousel");
        if (!$c.length) return;

        $c.owlCarousel({
            loop: true,
            margin: 20,
            nav: false,
            dots: false,
            autoplay: false,
            responsive: {
                0:    { items: 1 },
                576:  { items: 2 },
                768:  { items: 3 },
                1200: { items: 4 }
            }
        });

        var prev = document.getElementById("teamPrev");
        var next = document.getElementById("teamNext");

        if (prev) prev.addEventListener("click", function () { $c.trigger("prev.owl.carousel"); });
        if (next) next.addEventListener("click", function () { $c.trigger("next.owl.carousel"); });
    });

    /* ── Testimonial carousel (si existe) ── */
    if (typeof $ !== "undefined" && $(".testimonial-carousel").length) {
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

    /* ── Video section Apple reveal ── */
    var videoSection = document.getElementById("videoSection");
    if (videoSection && "IntersectionObserver" in window) {
        new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    videoSection.classList.add("in-view");
                }
            });
        }, { threshold: 0.22 }).observe(videoSection);
    }

    /* ── Back to top ── */
    if (backTop) {
        backTop.addEventListener("click", function (e) {
            e.preventDefault();
            window.scrollTo({ top: 0, behavior: "smooth" });
        });
    }

})(jQuery);