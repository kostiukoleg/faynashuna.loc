var MailChimpModule = (function() {
	
	return { 
		init: function() {  

			if ($('#uploadNew').attr('value') == 'true') {$('#uploadNew').click()}    
			
			//загрузка всех пользователей
			$('.integration-container').on('click', '.uploadChimpList', function() {
				
				admin.ajaxRequest({
					mguniqueurl: "action/uploadAllMailChimp",
					API: $('input[name=chimpApiKey]').val(),
					listId: $('input[name=chimpList]').val(),
					perm: $('select[name=permission]').val(),
				},
				function (response) {
					admin.indication(response.status, response.msg);
					if (response.status == 'success') {
						$('.chimpSend').text(lang.MAILCHIMP_MESSAGE_1).show();
					}
					else{
						$('.chimpSend').text(lang.MAILCHIMP_MESSAGE_2).show();
					}
					
				});
				
			});

			// Сохраняет базовые настроки
			$('.integration-container').on('click', '.saveChimpList', function() {

				admin.ajaxRequest({
					mguniqueurl: "action/saveMailChimp",
					API: $('input[name=chimpApiKey]').val(),
					listId: $('input[name=chimpList]').val(),
					perm: $('select[name=permission]').val(),
					uploadNew: $('#uploadNew').prop('checked'),
				},
				function (response) {
					admin.indication(response.status, lang.SAVED);
				});
			});
		}
	}
})();



$(document).ready(function() {
	MailChimpModule.init();
});