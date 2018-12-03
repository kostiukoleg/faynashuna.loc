var RetailCRMModule = (function() {
	
	return { 
		init: function() {

			//табы
			$('.integration-container').on('click', '.template-tabs-menu .template-tabs', function () {
				$(this).parent().find('li').removeClass('is-active');
				$(this).addClass('is-active');
				if ($(this).hasClass('options')) {$('.integration-container #sync, .integration-container #upload').hide(); $('.integration-container #options').show();}
				if ($(this).hasClass('sync')) {$('.integration-container #options, .integration-container #upload').hide(); $('.integration-container #sync').show();}
				if ($(this).hasClass('upload')) {$('.integration-container #sync, .integration-container #options').hide(); $('.integration-container #upload').show();}
			});

			$('.checkbox').each(function(){
				if ($(this).find('input[type=checkbox]').attr('value') == 'true') {
					$(this).find('input[type=checkbox]').click();
				}
			});
			
			//загрузка всех пользователей и заказов
			$('.integration-container').on('click', '.uploadAll', function() {
				
				admin.ajaxRequest({
					mguniqueurl: "action/uploadAllRetailCRM",
					uploadUsers: $('#uploadUsers').prop('checked'),
					uploadOrders: $('#uploadOrders').prop('checked'),
				},
				function (response) {
					admin.indication(response.status, response.msg);
				});
				
			});

			// Сохраняет базовые настроки
			$('.integration-container').on('click', '.saveRetail', function() {

				var retailStatusez = {};
				var retailDeliveryz = {};
				var retailPaymentz = {};
				var retailStorages = {};
				var retailOpFields = {};
				var id = '';
				var txt = '';

				$('.retailStatuses').each(function(){
					if ($(this).val().length > 0) {
						id = $(this).attr('key');
						txt = $(this).val();
						retailStatusez[id.toString()] = txt;
					}
				});

				$('.retailDeliverys').each(function(){
					if ($(this).val().length > 0) {
						id = $(this).attr('key');
						txt = $(this).val();
						retailDeliveryz[id.toString()] = txt;
					}
				});

				$('.retailPayments').each(function(){
					if ($(this).val().length > 0) {
						id = $(this).attr('key');
						txt = $(this).val();
						retailPaymentz[id.toString()] = txt;
					}
				});

				$('.retailStorage').each(function(){
					if ($(this).val().length > 0) {
						id = $(this).attr('key');
						txt = $(this).val();
						retailStorages[id.toString()] = txt;
					}
				});
				$('.retailOpFields').each(function(){
					if ($(this).val().length > 0) {
						id = $(this).attr('key');
						txt = $(this).val();
						retailOpFields[id.toString()] = txt;
					}
				});

				admin.ajaxRequest({
					mguniqueurl: "action/saveRetailCRM",
					url: $('input[name=retailURL]').val(),
					API: $('input[name=retailApiKey]').val(),
					site: $('input[name=retailSite]').val(),
					paid: $('input[name=paid]').val(),
					notPaid: $('input[name=notPaid]').val(),
					warehouseCode: $('input[name=warehouseCode]').val(),
					retailIndividual: $('input[name=retailIndividual]').val(),
					retailLegal: $('input[name=retailLegal]').val(),
					syncUsers: $('#syncUsers').prop('checked'),
					syncOrders: $('#syncOrders').prop('checked'),
					useOrderNumber: $('#useOrderNumber').prop('checked'),
					syncRemains: $('#syncRemains').prop('checked'),
					reportSync: $('#reportSync').prop('checked'),
					retailStatuses: retailStatusez,
					retailDeliverys: retailDeliveryz,
					retailPayments: retailPaymentz,
					retailStorage: retailStorages,
					retailOpFields: retailOpFields
				},
				function (response) {
					admin.indication(response.status, "Сохранено");
				});
			});

			$('.integration-container').on('click', '.syncRetail', function() {

				admin.ajaxRequest({
					mguniqueurl: "action/syncRetailCRM",
				},
				function (response) {
					admin.indication(response.status, response.msg);
				});
			});
		}
	};
})();


$(document).ready(function() {
	RetailCRMModule.init();
});