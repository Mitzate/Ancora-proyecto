(function ($) {
    "use strict";

    /* ── Spinner ── */
    window.addEventListener("load", function () {
        var sp = document.getElementById("spinner");
        if (sp) {
            sp.style.opacity = "0";
            setTimeout(function () { sp.style.display = "none"; }, 700);
        }
    });

    /* ── WOW ── */
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
       ANCLAS — scroll suave correcto
       Funciona con CUALQUIER link href="#algo"
    ════════════════════════════════════════════ */
     document.querySelectorAll('.ac-nav-links a[href^="#"]').forEach(function (a) {
    a.addEventListener("click", function (e) {
        const href = this.getAttribute("href");

        if (!href || href === "#" || href === "#!") return;

        const target = document.querySelector(href);
        if (!target) return;

        e.preventDefault();

        if (navLinks) navLinks.classList.remove("open");
        if (toggle) toggle.classList.remove("open");

        const navH = nav ? nav.offsetHeight : 90;
        const y = target.getBoundingClientRect().top + window.scrollY - navH - 20;

        window.scrollTo({
            top: y,
            behavior: "smooth"
        });
    });
});

    /* ── Active link en scroll ── */
    var sections = document.querySelectorAll("section[id]");
    var navAs    = document.querySelectorAll(".ac-nav-links a:not(.ac-nav-cta)");

    window.addEventListener("scroll", function () {
        var scrollY = window.scrollY + 120;
        sections.forEach(function (sec) {
            if (scrollY >= sec.offsetTop && scrollY < sec.offsetTop + sec.offsetHeight) {
                navAs.forEach(function (a) {
                    a.classList.remove("active");
                    if (a.getAttribute("href") === "#" + sec.id) {
                        a.classList.add("active");
                    }
                });
            }
        });
    }, { passive: true });

    /* ════════════════════════════════════════════
       LOGIN — redirige directo a login.html
    ════════════════════════════════════════════ */
    /*const openLogin = document.getElementById("openLogin");

    if (openLogin) {
        console.log("✅ Botón Login encontrado correctamente");
    
        openLogin.addEventListener("click", function (e) {
            e.preventDefault();
            console.log("🔄 Redirigiendo a login.html...");
            window.location.href = "login.html";
         });
     } else {
         console.error("❌ No se encontró el botón Login. Revisa que tenga id='openLogin'");
     }*/

    /* ════════════════════════════════════════════
       TEAM CAROUSEL — flechas corregidas
       Se usa jQuery directo porque el IIFE
       ya recibe $ como parámetro
    ════════════════════════════════════════════ */
    var $carousel = $(".ac-team-carousel");

    if ($carousel.length) {
        $carousel.owlCarousel({
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

    /* ── Testimonial carousel ── */
    if ($(".testimonial-carousel").length) {
        $(".testimonial-carousel").owlCarousel({
            items: 1, autoplay: true, smartSpeed: 1000,
            animateIn: "fadeIn", animateOut: "fadeOut",
            dots: true, loop: true, nav: false
        });
    }

    /* ── Video reveal al hacer scroll ── */
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