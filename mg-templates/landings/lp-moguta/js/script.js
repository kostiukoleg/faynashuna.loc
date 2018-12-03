
$(document).ready(function(){
  
  	 $('a[href^="#"], *[data-href^="#"]').on('click', function(e){
        e.preventDefault();
        var t = 1000;
        var d = $(this).attr('data-href') ? $(this).attr('data-href') : $(this).attr('href');
        $('html,body').stop().animate({ 'scrollTop': $(d).offset().top }, t);
    });
	
	$('.owl-carousel').owlCarousel({
    items:1,
    margin:10,
    autoHeight:true
    
});

    if ($('.variants-table').length) {
        if ($('.variants-table').find('.active-var').data('count') == 0) {
            $('.depletedLanding').show();
            $('.addToOrderLanding').hide();
        }
        else{
            $('.depletedLanding').hide();
            $('.addToOrderLanding').show();
        }
    }
});