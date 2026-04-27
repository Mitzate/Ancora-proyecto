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

    /* NAV TOGGLE */
    if (navToggle && navLinks) {
        navToggle.addEventListener("click", function () {
            navLinks.classList.toggle("open");
            navToggle.classList.toggle("open");
        });
    }

     /* SMOOTH SCROLL */
     document.querySelectorAll('a[href^="#"]').forEach(link => {
         link.addEventListener("click", function (e) {
         const href = this.getAttribute("href");

         if (!href) return;

         /* enlaces internos */
         if (href.startsWith("#")) {

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
        }

        /* privacy */
        else if (href === "privacy.html") {
            e.preventDefault();
            window.location.href = "privacy.html";
        }

        /* login */
        else if (href === "login.html") {
            e.preventDefault();
            window.location.href = "login.html";
        }
    });
});

    /* SCROLL */
    window.addEventListener("scroll", function () {
        if (nav) {
            nav.classList.toggle("ac-nav-scrolled", window.scrollY > 60);
        }

        if (backTop) {
            backTop.classList.toggle("show", window.scrollY > 300);
        }
    });

    /* BACK TO TOP */
    if (backTop) {
        backTop.addEventListener("click", function (e) {
            e.preventDefault();
            window.scrollTo({
                top: 0,
                behavior: "smooth"
            });
        });
    }

    /* VIDEO */
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

     /* =========================
      TEAM CAROUSEL
     ========================= */
     if (typeof jQuery !== "undefined" && typeof jQuery.fn.owlCarousel !== "undefined") {

          const $carousel = jQuery("#teamCarousel");

         if ($carousel.length) {

             setTimeout(function () {

             $carousel.owlCarousel({
                loop: true,
                margin: 20,
                nav: false,
                dots: false,
                autoplay: false,
                smartSpeed: 600,
                mouseDrag: true,
                touchDrag: true,
                pullDrag: true,
                responsive: {
                    0: { items: 1 },
                    576: { items: 2 },
                    768: { items: 3 },
                    1200: { items: 4 }
                }
             });

             document.getElementById("teamPrev")?.addEventListener("click", function(e){
                e.preventDefault();
                $carousel.trigger("prev.owl.carousel");
             });

             document.getElementById("teamNext")?.addEventListener("click", function(e){
                e.preventDefault();
                $carousel.trigger("next.owl.carousel");
             });

             console.log("Carrusel funcionando correctamente");

        }, 300);
    }
}

});