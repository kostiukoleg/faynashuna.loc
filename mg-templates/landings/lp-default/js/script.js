$(document).ready(function() {
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