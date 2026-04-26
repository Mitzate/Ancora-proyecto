(function () {
    "use strict";

    /* ══ SPINNER ══════════════════════════════════════════ */
    window.addEventListener("load", function () {
        var spinner = document.getElementById("spinner");
        if (spinner) {
            spinner.style.transition = "opacity 0.6s ease";
            spinner.style.opacity = "0";
            setTimeout(function () { spinner.style.display = "none"; }, 700);
        }
    });

    /* ══ WOW ══════════════════════════════════════════════ */
    if (typeof WOW !== "undefined") new WOW().init();

    /* ══ NAVBAR SCROLL ════════════════════════════════════ */
    var nav     = document.getElementById("acNav");
    var backTop = document.getElementById("backTop");

    window.addEventListener("scroll", function () {
        if (nav)     nav.classList.toggle("ac-nav-scrolled", window.scrollY > 60);
        if (backTop) backTop.classList.toggle("show", window.scrollY > 300);
    }, { passive: true });

    /* ══ MOBILE MENU ══════════════════════════════════════ */
    var navToggle = document.getElementById("navToggle");
    var navLinks  = document.getElementById("navLinks");

    if (navToggle && navLinks) {
        navToggle.addEventListener("click", function () {
            navLinks.classList.toggle("open");
            navToggle.classList.toggle("open");
        });
        navLinks.querySelectorAll("a").forEach(function (a) {
            a.addEventListener("click", function () {
                navLinks.classList.remove("open");
                navToggle.classList.remove("open");
            });
        });
    }

    /* ══ SMOOTH SCROLL ════════════════════════════════════ */
    document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
        anchor.addEventListener("click", function (e) {
            var href = this.getAttribute("href");
            if (href === "#!" || href === "#") return;
            var target = document.querySelector(href);
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: "smooth" });
            }
        });
    });

    /* ══ HERO WHEEL → SCROLL A DESCRIPCIÓN ═══════════════ */
    var nextSection  = document.getElementById("seccion360");
    var heroScrolled = false;

    if (nextSection) {
        window.addEventListener("wheel", function (e) {
            if (heroScrolled || window.scrollY > window.innerHeight * 0.5) return;
            if (e.deltaY <= 0) return;
            heroScrolled = true;
            nextSection.scrollIntoView({ behavior: "smooth" });
            setTimeout(function () { heroScrolled = false; }, 1500);
        }, { passive: true });
    }

    /* ══ VIDEO — REVEAL + PARALLAX ═══════════════════════ */
    var videoSection = document.getElementById("videoSection");
    var centerText   = document.getElementById("videoCenterText");
    var vfItems      = document.querySelectorAll(".ac-vf-item");

    function onVideoScroll() {
        if (!videoSection) return;
        var rect     = videoSection.getBoundingClientRect();
        var progress = Math.max(0, Math.min(1, -rect.top / Math.max(rect.height - window.innerHeight, 1)));

        if (centerText) {
            centerText.style.transform = "translate(-50%, calc(-50% + " + (progress * -60) + "px))";
        }

        vfItems.forEach(function (item, i) {
            if (progress > 0.12 + i * 0.08) item.classList.add("visible");
        });
    }
    window.addEventListener("scroll", onVideoScroll, { passive: true });
    onVideoScroll();

    /* ── Video IntersectionObserver para clase in-view ── */
    if (videoSection && "IntersectionObserver" in window) {
        var videoObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    videoSection.classList.add("in-view");
                    videoObserver.unobserve(videoSection);
                }
            });
        }, { threshold: 0.22 });
        videoObserver.observe(videoSection);
    }

    /* ── Video parallax de escala ── */
    var heroVideo = document.getElementById("heroVideo");
    if (heroVideo && videoSection) {
        window.addEventListener("scroll", function () {
            var rect     = videoSection.getBoundingClientRect();
            var progress = Math.max(0, Math.min(1, -rect.top / Math.max(rect.height, 1)));
            heroVideo.style.transform = "scale(" + (1.05 + progress * 0.04) + ")";
        }, { passive: true });
    }

    /* ══ LOGIN MODAL ══════════════════════════════════════ */
    var modal      = document.getElementById("loginModal");
    var openLogin  = document.getElementById("openLogin");
    var modalClose = document.getElementById("modalClose");

    function openModal() {
        if (!modal) return;
        modal.classList.add("open");
        document.body.style.overflow = "hidden";
    }
    function closeModal() {
        if (!modal) return;
        modal.classList.remove("open");
        document.body.style.overflow = "";
    }

    if (openLogin) {
        openLogin.addEventListener("click", function (e) {
            e.preventDefault();
            openModal();
        });
    }
    if (modalClose) modalClose.addEventListener("click", closeModal);
    if (modal) {
        modal.addEventListener("click", function (e) {
            if (e.target === modal) closeModal();
        });
    }
    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") closeModal();
    });

    /* ── Modal tabs ── */
    document.querySelectorAll(".ac-modal-tab").forEach(function (tab) {
        tab.addEventListener("click", function () {
            document.querySelectorAll(".ac-modal-tab").forEach(function (t) { t.classList.remove("active"); });
            document.querySelectorAll(".ac-modal-panel").forEach(function (p) { p.classList.remove("active"); });
            tab.classList.add("active");
            var panel = document.getElementById("panel-" + tab.dataset.tab);
            if (panel) panel.classList.add("active");
        });
    });

    /* ══ BACK TO TOP ══════════════════════════════════════ */
    if (backTop) {
        backTop.addEventListener("click", function (e) {
            e.preventDefault();
            window.scrollTo({ top: 0, behavior: "smooth" });
        });
    }

    /* ══ TEAM CAROUSEL ════════════════════════════════════ */
    $(function () {
        var $carousel = $("#teamCarousel");
        if ($carousel.length === 0) return;

        if ($carousel.hasClass("owl-loaded")) {
            $carousel.trigger("destroy.owl.carousel");
            $carousel.removeClass("owl-carousel owl-loaded");
        }

        $carousel.addClass("owl-carousel").owlCarousel({
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

        $("#teamPrev").on("click", function () {
            $carousel.trigger("prev.owl.carousel");
        });
        $("#teamNext").on("click", function () {
            $carousel.trigger("next.owl.carousel");
        });
    });

})();