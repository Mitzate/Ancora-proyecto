(function () {
    "use strict";

    /* ==========================================
       SPINNER
    ========================================== */
    window.addEventListener("load", function () {
        const spinner = document.getElementById("spinner");
        if (spinner) {
            spinner.classList.remove("show");
            spinner.style.opacity = "0";
            setTimeout(() => {
                spinner.style.display = "none";
            }, 600);
        }
    });

    /* ==========================================
       WOW
    ========================================== */
    if (typeof WOW !== "undefined") {
        new WOW().init();
    }

    /* ==========================================
       NAVBAR
    ========================================== */
    const nav = document.getElementById("acNav");
    const backTop = document.getElementById("backTop");

    window.addEventListener("scroll", function () {
        if (nav) {
            nav.classList.toggle("ac-nav-scrolled", window.scrollY > 60);
        }

        if (backTop) {
            backTop.classList.toggle("show", window.scrollY > 300);
        }
    });

    /* ==========================================
       MOBILE MENU
    ========================================== */
    const navToggle = document.getElementById("navToggle");
    const navLinks = document.getElementById("navLinks");

    if (navToggle && navLinks) {
        navToggle.addEventListener("click", function () {
            navLinks.classList.toggle("open");
            navToggle.classList.toggle("open");
        });
    }

    /* ==========================================
       HERO AUTO SCROLL
    ========================================== */
    const hero = document.getElementById("heroSection");
    const nextSection = document.getElementById("seccion360");

    let heroScrolled = false;

    if (hero && nextSection) {
        window.addEventListener("wheel", function (e) {
            if (heroScrolled) return;
            if (window.scrollY > 100) return;
            if (e.deltaY <= 0) return;

            heroScrolled = true;

            window.scrollTo({
                top: nextSection.offsetTop,
                behavior: "smooth"
            });

            setTimeout(() => {
                heroScrolled = false;
            }, 1500);

        }, { passive: true });
    }

    /* ==========================================
       VIDEO SCROLL EFFECT
    ========================================== */
    const videoSection = document.getElementById("videoSection");
    const centerText = document.getElementById("videoCenterText");
    const items = document.querySelectorAll(".ac-vf-item");

    function animateVideoSection() {
        if (!videoSection || !centerText) return;

        const rect = videoSection.getBoundingClientRect();
        const progress = Math.min(
            Math.max((-rect.top) / (rect.height - window.innerHeight), 0),
            1
        );

        centerText.style.transform =
            `translate(-50%, calc(-50% + ${progress * -60}px))`;

        items.forEach((item, i) => {
            if (progress > 0.12 + i * 0.08) {
                item.classList.add("visible");
            }
        });
    }

    window.addEventListener("scroll", animateVideoSection);
    animateVideoSection();

    /* ==========================================
       TEAM CAROUSEL
    ========================================== */
    if ($(".ac-team-carousel").length) {
        $(".ac-team-carousel").owlCarousel({
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

        $(".ac-team-arrow-prev").on("click", function () {
            $(".ac-team-carousel").trigger("prev.owl.carousel");
        });

        $(".ac-team-arrow-next").on("click", function () {
            $(".ac-team-carousel").trigger("next.owl.carousel");
        });
    }

    /* ==========================================
       BACK TO TOP
    ========================================== */
    if (backTop) {
        backTop.addEventListener("click", function (e) {
            e.preventDefault();
            window.scrollTo({
                top: 0,
                behavior: "smooth"
            });
        });
    }

})();