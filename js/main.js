document.addEventListener("DOMContentLoaded", function () {

    /* ── Spinner ── */
    window.addEventListener("load", function () {
        var sp = document.getElementById("spinner");
        if (sp) {
            sp.style.opacity = "0";
            setTimeout(function () {
                sp.style.display = "none";
            }, 700);
        }
    });

    /* ── WOW animations ── */
    if (typeof WOW !== "undefined") {
        new WOW().init();
    }

    /* ── Navbar ── */
    var acNav = document.getElementById("acNav");
    var backTop = document.getElementById("backTop");
    var navToggle = document.getElementById("navToggle");
    var navLinks = document.getElementById("navLinks");

    function getNavHeight() {
        return acNav ? acNav.offsetHeight : 80;
    }

    window.addEventListener("scroll", function () {
        if (acNav) {
            acNav.classList.toggle("ac-nav-scrolled", window.scrollY > 60);
        }

        if (backTop) {
            backTop.classList.toggle("show", window.scrollY > 300);
        }
    }, { passive: true });

    /* ── Mobile menu ── */
    if (navToggle && navLinks) {
        navToggle.addEventListener("click", function () {
            navLinks.classList.toggle("open");
            navToggle.classList.toggle("open");
        });
    }

    /* ── Navbar links ── */
    document.querySelectorAll(".ac-nav-links a").forEach(function (link) {
        link.addEventListener("click", function (e) {
            var href = link.getAttribute("href");

            /* Login → dejar navegación normal */
            if (!href || href.includes("login.html")) {
                return;
            }

            /* Solo manejar anclas internas */
            if (!href.startsWith("#")) {
                return;
            }

            if (href === "#" || href === "#!") {
                e.preventDefault();
                return;
            }

            var target = document.getElementById(href.slice(1));
            if (!target) {
                return;
            }

            e.preventDefault();

            if (navLinks) navLinks.classList.remove("open");
            if (navToggle) navToggle.classList.remove("open");

            var offsetTop = target.getBoundingClientRect().top + window.scrollY - getNavHeight() - 16;

            window.scrollTo({
                top: offsetTop,
                behavior: "smooth"
            });
        });
    });

    /* ── Active link ── */
    var allSections = document.querySelectorAll("section[id]");
    var allNavLinks = document.querySelectorAll(".ac-nav-links a:not(.ac-nav-cta)");

    window.addEventListener("scroll", function () {
        var scrollPos = window.scrollY + getNavHeight() + 40;

        allSections.forEach(function (section) {
            if (
                scrollPos >= section.offsetTop &&
                scrollPos < section.offsetTop + section.offsetHeight
            ) {
                allNavLinks.forEach(function (a) {
                    a.classList.remove("active");

                    if (a.getAttribute("href") === "#" + section.id) {
                        a.classList.add("active");
                    }
                });
            }
        });
    }, { passive: true });

    /* ── Video animation ── */
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

            window.scrollTo({
                top: 0,
                behavior: "smooth"
            });
        });
    }

    /* ── Owl Carousel ── */
    if (typeof jQuery !== "undefined") {
        jQuery(function ($) {
            var $carousel = $(".ac-team-carousel");

            if ($carousel.length) {
                $carousel.owlCarousel({
                    loop: true,
                    margin: 20,
                    nav: false,
                    dots: false,
                    autoplay: false,
                    responsive: {
                        0: { items: 1 },
                        576: { items: 2 },
                        768: { items: 3 },
                        1200: { items: 4 }
                    }
                });

                $("#teamPrev").on("click", function () {
                    $carousel.trigger("prev.owl.carousel");
                });

                $("#teamNext").on("click", function () {
                    $carousel.trigger("next.owl.carousel");
                });
            }

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
        });
    }

});