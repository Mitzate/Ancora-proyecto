/* =========================================================
   ANCORA — main.js limpio y funcional
========================================================= */

console.log("[Ancora] main.js cargado");

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

    const nav = document.getElementById("acNav");
    const navToggle = document.getElementById("navToggle");
    const navLinks = document.getElementById("navLinks");
    const backTop = document.getElementById("backTop");

    /* =========================
       NAV TOGGLE MOBILE
    ========================= */
    if (navToggle && navLinks) {
        navToggle.addEventListener("click", function () {
            navLinks.classList.toggle("open");
            navToggle.classList.toggle("open");
        });
    }

    /* =========================
       SMOOTH SCROLL SOLO INTERNOS
    ========================= */
    document.querySelectorAll('a[href^="#"]').forEach(link => {
        link.addEventListener("click", function (e) {
            const href = this.getAttribute("href");

            if (href === "#" || href === "#!") {
                e.preventDefault();
                return;
            }

            const target = document.querySelector(href);

            if (target) {
                e.preventDefault();

                const navHeight = nav ? nav.offsetHeight : 80;
                const top = target.offsetTop - navHeight;

                window.scrollTo({
                    top: top,
                    behavior: "smooth"
                });

                if (navLinks) navLinks.classList.remove("open");
                if (navToggle) navToggle.classList.remove("open");
            }
        });
    });

    /* =========================
       SCROLL EFFECTS
    ========================= */
    window.addEventListener("scroll", function () {
        if (nav) {
            nav.classList.toggle("ac-nav-scrolled", window.scrollY > 60);
        }

        if (backTop) {
            backTop.classList.toggle("show", window.scrollY > 300);
        }
    });

    /* =========================
       BACK TO TOP
    ========================= */
    if (backTop) {
        backTop.addEventListener("click", function (e) {
            e.preventDefault();
            window.scrollTo({
                top: 0,
                behavior: "smooth"
            });
        });
    }

    /* =========================
       VIDEO REVEAL
    ========================= */
    const videoSection = document.getElementById("videoSection");

    if (videoSection && "IntersectionObserver" in window) {
        const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    videoSection.classList.add("in-view");
                }
            });
        }, {
            threshold: 0.2
        });

        observer.observe(videoSection);
    }
});

/* =========================
   TEAM CAROUSEL
========================= */
window.addEventListener("load", function () {

    if (typeof jQuery === "undefined") {
        console.error("jQuery no cargó");
        return;
    }

    if (typeof jQuery.fn.owlCarousel === "undefined") {
        console.error("Owl Carousel no cargó");
        return;
    }

    const $carousel = jQuery("#teamCarousel");

    if (!$carousel.length) {
        console.error("No existe #teamCarousel");
        return;
    }

    $carousel.owlCarousel({
        loop: true,
        margin: 20,
        nav: false,
        dots: false,
        autoplay: false,
        smartSpeed: 500,
        responsive: {
            0: {
                items: 1
            },
            576: {
                items: 2
            },
            768: {
                items: 3
            },
            1200: {
                items: 4
            }
        }
    });

    const prevBtn = document.getElementById("teamPrev");
    const nextBtn = document.getElementById("teamNext");

    if (prevBtn) {
        prevBtn.addEventListener("click", function () {
            $carousel.trigger("prev.owl.carousel");
        });
    }

    if (nextBtn) {
        nextBtn.addEventListener("click", function () {
            $carousel.trigger("next.owl.carousel");
        });
    }

    console.log("Carrusel inicializado correctamente");
});