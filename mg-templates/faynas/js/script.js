$(document).ready(function(){
  $('.mg-filter-item .color-filter').parent('ul').addClass('color-filter-ul');
  $('input.mg-filter-prop-checkbox:checked').parent('label').addClass('active');
  $('input.mg-filter-prop-checkbox').on('click', function() {
    if ($(this).is(':checked')) {
      $(this).parent('label').addClass('active');
    } else {
      $(this).parent('label').removeClass('active');
    }
  });
  /*Mobile menu*/
  $(".top-menu-list li .slider_btn").on("click", function(){
    $(this).parent("li").toggleClass("open");
  });

  $("body").on("click", ".toggle", function(){
    $(this).parents("li").toggleClass("open");
  });

  var toggleOpenClass = function(button, parent){
    $(button).on("click", function () {
      $(this).parents(parent).toggleClass("open");
    });
  }

  toggleOpenClass(".menu-toggle", ".top-menu-block");
  toggleOpenClass(".clock-icon", ".work-hours");
  toggleOpenClass(".main-menu-toggle", ".mg-main-menu-holder");
  toggleOpenClass(".filter-toggle", ".filter-block");

  $(".addToCompare").on("click", function () {
    $(this).addClass("active");
  });

  $("table").wrap("<div class='table-wrapper'/>");

  $(".products-wrapper .product-wrapper").each(function(){
    
      var variants = $(this).find(".block-variants");
      if(variants.length){
        $(this).find(".product-image").prepend("<span class='variants-text'><i class='fa fa-bookmark-o'></i> Есть варианты</span>");
        $('.variants-text').show();
      }
  });

    $(".enter-on .open-link").on("click", function(e){
        e.preventDefault();
        $(this).parent().toggleClass("open");
    });

    $('html').click(function( event ){

        var target = $( event.target ).parents(".enter-on");

        if( !target.length ){
            $(".enter-on").removeClass("open");
        }
    });

  $(".zoom").on("click", function(){
    $(this).prev().trigger("click");
  });

  function productTabs(){
    var tabContainers = $('.product-tabs-container > div');
    tabContainers.hide().filter(':first').show();

    $('.product-tabs li a').click(function(){
      tabContainers.hide();
      tabContainers.filter(this.hash).fadeIn("fast");
      $('.product-tabs li').removeClass('active');
      $(this).parent().addClass('active');
      return false;
    }).filter(':first').click();
  }

  productTabs();

  function rememberView(){
    var className = localStorage["class"];
    //localStorage.clear();

    if(className === undefined){
      $(".btn-group .view-btn:first-child").addClass("active");
      localStorage.setItem('class', 'grid');
    }

    else{
      $('.btn-group .view-btn[data-type="' + className + '"]').addClass("active");
      $('.products-wrapper.catalog').addClass(className);
    }

    $(".btn-group .view-btn").on("click", function(e){
      e.preventDefault();
      var currentView = $(this).data('type');
      var product = $('.products-wrapper.catalog');
      product.removeClass("list grid");
      product.addClass(currentView);
      $('.btn-group .view-btn').removeClass("active");
      $(this).addClass("active");
      localStorage.setItem('class', $(this).data('type'));
      return false;
    });
  }

  rememberView();

  $(".show-hide-filters").on("click", function(){
    $(this).parent(".filter-block").toggleClass("show");
  });

  $(".close-icon").on("click", function(){
    $("body").removeClass("locked");
    $(this).parents(".menu-block").removeClass("open");
  });

  $(".mobile-toggle").on("click", function(){
    $("body").toggleClass("locked");
    $(this).parent(".menu-block").toggleClass("open");
  });



  var owl = $(".m-p-products-slider-start");

  owl.owlCarousel({
    items: 4, //10 items above 1000px browser width
    itemsDesktop: [1100, 3], //5 items between 1000px and 901px
    itemsDesktopSmall: [900, 2], // betweem 900px and 601px
    itemsTablet: [600, 2], //2 items between 600 and 0
    itemsMobile: [550, 1], // itemsMobile disabled - inherit from itemsTablet option
    pagination: false,
    navigation: true
  });

  
  var slider_width = $('.menu-block').width() + 2;
  var deviceWidth = $(window).width();

  /*Fix mobile top menu position if login admin*/
  if($("body").hasClass("admin-on-site")){
    $("body").find(".mobile-top-panel").addClass("position-fix");
  }
  //Leftmenu
  $('nav').find('ul').parent('li').addClass('parent');
    $('nav a').each(function() {
        var location = window.location.href;
        var link = this.href;
        if (location == link) {
            $(this).parent('li').addClass('active');
        }
    });
  

    // Add active class
    $('.j-accordion-menu a').each(function() {

        var location = window.location.href;

        var link = this.href;

        if (location == link) {

            $(this).parent('li').addClass('active');

            $(this).parents('.j-accordion-menu li').addClass('open').children('ul').show();

            $(this).parents('.j-accordion-menu ul').show();

        }

    });


  // Accordion menu
  $('.j-accordion-menu__parent').on('click', function() {

        var AccordionMenu = $(this).parent('li');

        if (AccordionMenu.hasClass('open')) {

            AccordionMenu.removeClass('open');

            AccordionMenu.children().find('li').removeClass('open');

            AccordionMenu.find('ul').slideUp();

        } else {

            AccordionMenu.addClass('open');

            AccordionMenu.children('ul').slideDown();

            AccordionMenu.siblings('li').children('ul').slideUp();

            AccordionMenu.siblings('li').removeClass('open');

            AccordionMenu.siblings('li').find('li').removeClass('open');

            AccordionMenu.siblings('li').find('ul').slideUp();
        }
  });

  $('#j-catalog__button').click(function(){
    $('.j-catalog__nav.j-offcanvas').addClass('j-offcanvas--open');
  });

  $('.mg-buy-click-button, .close-mg-buy-button').on('click', function (){
    $('body').toggleClass('scrollBlock');
  });

  // Personal Tabs 
  $( "#tabs" ).tabs({
        active: localStorage.getItem("currentIdx"),
        activate: function (event, ui) {
            localStorage.setItem("currentIdx", $(this).tabs('option','active'));
        }
  });

  $( "#ui-id-5" ).parent().attr("aria-controls","");
});