(function ($) {
    "use strict";

    var spinner = function () {
        setTimeout(function () {
            if ($('#spinner').length > 0) {
                $('#spinner').removeClass('show');
            }
        }, 1);
    };
    spinner();

    /*  Animations*/
    new WOW().init();
   
    /* o Navbar Sticky */
    $(window).scroll(function () {
        if ($(this).scrollTop() > 300) {
            $('.sticky-top')
                .addClass('bg-white shadow-sm')
                .css('top', '0px');
        } else {
            $('.sticky-top')
                .removeClass('bg-white shadow-sm')
                .css('top', '-150px');
        }
    });

    /* Back To Top */
    $(window).scroll(function () {
        if ($(this).scrollTop() > 100) {
            $('.back-to-top').fadeIn('slow');
        } else {
            $('.back-to-top').fadeOut('slow');
        }
    });

    $('.back-to-top').click(function () {
        $('html, body').animate({
            scrollTop: 0
        }, 1500, 'easeInOutExpo');
        return false;
    });  

    /* Hero Scroll */
    $(window).scroll(function () {
        let scrollTop = $(this).scrollTop();

        $('.hero-modern').css({
            transform: 'translateY(' + (scrollTop * 0.05) + 'px)'
        });
    });

    /* Scroll suave flecha */
    $('.scroll-down').click(function (e) {
        e.preventDefault();

        $('html, body').animate({
            scrollTop: $('#seccion360').offset().top
        }, 1200, 'easeInOutExpo');
    });

    $(".testimonial-carousel").owlCarousel({
        items: 1,
        autoplay: true,
        smartSpeed: 1000,
        animateIn: 'fadeIn',
        animateOut: 'fadeOut',
        dots: true,
        loop: true,
        nav: false
    });

})(jQuery);