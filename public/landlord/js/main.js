(function($) {
    "use strict";

    //Detect device mobile
    var isMobile = false;
    if (/Android|webOS|iPhone|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) || ($(window).width() < 990)) {
        $('body').addClass('mobile');
        isMobile = true;
    } else {
        isMobile = false;
    }


    if (!isMobile) {
        $(window).scroll(function() {
            var sc = $(window).scrollTop();
            var menu = $('#main-menu').clone();
            if (sc > 300) {
                $("#header-middle").addClass("fixed");
                $("#header-middle .middle-column .fixed-menu").html(menu);
            } else {
                $("#header-middle").removeClass("fixed");
                $("#header-middle .middle-column .fixed-menu").html('');
            }
        });

        $(document).ready(function(){
            $(".sticker").sticky({ topSpacing: 70 });
        });

        // $(window).on('scroll', function() {
        //     if ($(window).scrollTop() >= $(
        //       '.middle').offset().top + $('.middle').
        //         outerHeight() - window.innerHeight) {
        //         $(".sticker").css('position','relative');
        //     }
        // });
    }

    if (isMobile) {
        
        $(window).scroll(function() {
            var sc = $(window).scrollTop();
            var menu = $('#main-menu').clone();
            if (sc > 100) {
                $("#header-middle").addClass("fixed");
                $("#header-middle .middle-column .fixed-menu").html(menu);
            } else {
                $("#header-middle").removeClass("fixed");
                $("#header-middle .middle-column .fixed-menu").html('');
            }
        });

        var menu = $('#main-menu').clone();
        // $(".header-middle .middle-column").html('');
        $("#mobile-nav .container").html(menu);

        $(".mobile-menu-icon").on("click", function() {
            $('.body__overlay').addClass('is-visible');
            $("#mobile-nav").toggleClass("show");
        });
        
        $("#mobile-nav ul li a,.mobile-menu-close-icon,.body__overlay").on("click", function() {
            $('.body__overlay').removeClass('is-visible');
            $("#mobile-nav").toggleClass("show");
        });

    }


    /*------------------------------------------
            Open filter menu mobile
      --------------------------------------------*/
    $('.filter-collection-left > a').on('click', function() {
        $('.wrappage').addClass('show-filter');
    });

    $('.close-sidebar-collection').on('click', function() {
        $('.wrappage').removeClass('show-filter');
    });


    /*------------------------------------------
                     Tooltip
    --------------------------------------------*/
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl)
    })


    /*------------------------------------------
                      Scroll to top
    --------------------------------------------*/
    $(window).scroll(function() {
        if ($(this).scrollTop() >= 200) { // If page is scrolled more than 50px
            $('#scrolltotop').fadeIn(500); // Fade in the arrow
        } else {
            $('#scrolltotop').fadeOut(500); // Else fade out the arrow
        }
    });
    $('#scrolltotop').click(function() { // When arrow is clicked
        $('body,html').animate({
            scrollTop: 0 // Scroll to top of body
        }, 700);
    });

    $(document).ready(function() {
        
        if (isMobile) {
            var swiper = new Swiper(".mySwiper", {
                slidesPerView: 1,
                spaceBetween: 50,
                autoHeight: true,
                loop: true,
                navigation: {
                    nextEl: ".swiper-nav-next",
                    prevEl: ".swiper-nav-prev",
                }
            });
        } else {
            var swiper = new Swiper(".mySwiper", {
                slidesPerView: 3,
                spaceBetween: 50,
                autoHeight: true,
                loop: true,
                navigation: {
                    nextEl: ".swiper-nav-next",
                    prevEl: ".swiper-nav-prev",
                },
                breakpoints: {
                    // when window width is <= 420
                    650: {
                        slidesPerView: 1,
                        spaceBetween: 30
                    },
                    // when window width is <= 991
                    991: {
                        slidesPerView: 2,
                        spaceBetween: 30
                    }
                }
            });
        }


        /*------------------------------------
             left sidebar menu
        --------------------------------------*/

        $('.category__menu').on('click', function() {
            $('.category__list').toggleClass('list__open');
            if(!isMobile) {
                $('.right-pane').toggleClass('w-100');
            }
        });

        /*------------------------------------
             Shopping Cart
        --------------------------------------*/

        $('.panel_setting').on('click', function() {
            $('.setting-panel').addClass('setting-panel-on');
            $('.body__overlay').addClass('is-visible');

        });
        $('.offsetmenu__close__btn').on('click', function() {
            $('.shopping__cart').removeClass('shopping__cart__on');
            $('.body__overlay').removeClass('is-visible');
        });
        $('.offsetmenu__close__btn').on('click', function() {
            $('.setting-panel').removeClass('setting-panel-on');
            $('.body__overlay').removeClass('is-visible');
        });


        //Close body Overlay
        $('.body__overlay').on('click', function() {
            $(this).removeClass('is-visible');
            $('.offsetmenu').removeClass('offsetmenu__on');
            $('.user__meta').removeClass('user__meta__on');
            $("#mobile-nav").removeClass("show");
        });


        /*------------------------
           Category menu Activation
        --------------------------*/
        $('.category-sub-menu li.has-sub').on('click', function() {
            var element = $(this);
            if (element.hasClass('open')) {
                element.removeClass('open');
                element.find('li').removeClass('open');
                element.find('ul').slideUp('fast');
            } else {
                element.addClass('open');
                element.children('ul').slideDown('fast');
                element.siblings('li').children('ul').slideUp('fast');
                element.siblings('li').removeClass('open');
                element.siblings('li').find('li').removeClass('open');
                element.siblings('li').find('ul').slideUp('fast');
            }
        });

    });

}(jQuery));
