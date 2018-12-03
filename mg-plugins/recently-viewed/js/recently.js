$(document).ready(function(){
	//recent products slider
	$('.mg-recently-viewed-plugin .mg-recently-viewed-slider').bxSlider({
        minSlides: 3,
        maxSlides: 3,
        slideWidth: 200,
        slideMargin: 15,
        moveSlides: 1,
        pager: false,
        auto: false,
        pause: 6000,
        useCSS: false
    });
});