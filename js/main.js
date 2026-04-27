document.addEventListener("DOMContentLoaded", function () {

    /* ── Spinner ── */
    window.addEventListener("load", function () {
        const spinner = document.getElementById("spinner");
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
    const acNav = document.getElementById("acNav");
    const navLinks = document.getElementById("navLinks");
    const navToggle = document.getElementById("navToggle");
    const backTop = document.getElementById("backTop");

    function getNavHeight() {
        return acNav ? acNav.offsetHeight : 80;
    }

    /* ── Scroll navbar ── */
    window.addEventListener("scroll", function () {
        if (acNav) {
            acNav.classList.toggle("ac-nav-scrolled", window.scrollY > 60);
        }

        if (backTop) {
            backTop.classList.toggle("show", window.scrollY > 300);
        }

        updateActiveLink();
    }, { passive: true });

    /* ── Menú móvil ── */
    if (navToggle && navLinks) {
        navToggle.addEventListener("click", function () {
            navLinks.classList.toggle("open");
            navToggle.classList.toggle("open");
        });
    }

    /* ── Links del navbar ── */
    document.querySelectorAll(".ac-nav-links a").forEach(function (link) {
        link.addEventListener("click", function (e) {
            const href = this.getAttribute("href");

            /* LOGIN */
            if (href === "login.html") {
                if (navLinks) navLinks.classList.remove("open");
                if (navToggle) navToggle.classList.remove("open");

                window.location.href = "login.html";
                return;
            }

            /* Solo enlaces internos */
            if (!href || !href.startsWith("#")) {
                return;
            }

            const target = document.querySelector(href);

            if (!target) {
                console.warn("No existe sección:", href);
                return;
            }

            e.preventDefault();

            if (navLinks) navLinks.classList.remove("open");
            if (navToggle) navToggle.classList.remove("open");

            const offsetTop =
                target.getBoundingClientRect().top +
                window.pageYOffset -
                getNavHeight();

            window.scrollTo({
                top: offsetTop,
                behavior: "smooth"
            });
        });
    });

    /* ── Active links ── */
    const sections = document.querySelectorAll("section[id]");
    const menuLinks = document.querySelectorAll(".ac-nav-links a:not(.ac-nav-cta)");

    function updateActiveLink() {
        const scrollPos = window.scrollY + getNavHeight() + 50;

        sections.forEach(function (section) {
            const top = section.offsetTop;
            const bottom = top + section.offsetHeight;

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

    /* ── Video reveal ── */
    const videoSection = document.getElementById("videoSection");

    if (videoSection && "IntersectionObserver" in window) {
        const observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    videoSection.classList.add("in-view");
                }
            });
        }, {
            threshold: 0.2
        });

        observer.observe(videoSection);
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
            const $carousel = $(".ac-team-carousel");

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