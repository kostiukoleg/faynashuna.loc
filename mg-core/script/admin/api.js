if(api == undefined) {

	var api = {

		editMode: undefined,
		item: undefined,

		init: function() {
			// Сохранение настроек API
			$('body').on('click', '#tab-api-settings .save-button', function() {
				api.saveApi();
			});

			// Генерация токена API
			$('body').on('click', '#tab-api-settings .createToken', function() {
				api.createToken();
			});

			// Открытие модали для создания токена
			$('body').on('click', '#tab-api-settings .addToken', function() {
				api.editMode = 'add';
				$('#tab-api-settings input').val('');
				$('#tab-api-settings #token-edit-modal input').removeClass('error-input');
				admin.openModal('#token-edit-modal');
			});

			// удаление токена
			$('body').on('click', '#tab-api-settings .fa-trash', function() {
				if(confirm(lang.CONFIRM_TOKEN_DEL)) {
					$(this).parents('.token-item').detach();
					api.saveApi(false);
				}
			});

			// редактирование токена
			$('body').on('click', '#tab-api-settings .fa-pencil', function() {
				api.editMode = 'edit';
				api.item = $(this).parents('.token-item');
				$('#tab-api-settings input').val('');
				$('#tab-api-settings [name=name]').val(api.item.find('.name').html());
				$('#tab-api-settings [name=token]').val(api.item.find('.token').html());
				$('#tab-api-settings [name=key]').val(api.item.find('.key').html());
				admin.openModal('#token-edit-modal');
			});
		},

		saveApi: function(errorCheck) {
			errorCheck = typeof errorCheck !== 'undefined' ? errorCheck : true;
			if(errorCheck) {
				var error = false;
				$('#tab-api-settings #token-edit-modal input').each(function() {
					if($(this).val() == '') {
						$(this).addClass('error-input');
						error = true;
					} else {
						$(this).removeClass('error-input');
					}
				});
				if(error) return false;
			}
		  	var data = {};
		  	if(api.editMode == 'add') {
		  		$('#tab-api-settings .toDel').detach();
		  		api.addRow();
		  	} 
		  	if(api.editMode == 'edit') {
		  		api.item.find('.name').html($('#tab-api-settings [name=name]').val());
		  		api.item.find('.token').html($('#tab-api-settings [name=token]').val());
		  		api.item.find('.key').html($('#tab-api-settings [name=key]').val());
		  	}
		  	$('.tokens-list .token-item').each(function(index) {
		  		if(($(this).find('.name').html() != undefined)&&($(this).find('.name').html() != '')) {
		  			data[index] = {};
		  			data[index]['name'] = $(this).find('.name').html();
		  			data[index]['token'] = $(this).find('.token').html();
		  			data[index]['key'] = $(this).find('.key').html();
		  		}
		  	});
		  	admin.ajaxRequest({
		    	mguniqueurl: "action/saveApi", // действия для выполнения на сервере    
		    	data: data         
		  	},      
		  	function(response) {
		    	admin.indication(response.status, response.msg);  
		    	admin.closeModal('#token-edit-modal');
		    	admin.refreshPanel();     
		  	});
		},

		createToken: function() {
		  	admin.ajaxRequest({
		    	mguniqueurl: "action/createToken", // действия для выполнения на сервере         
		  	},      
		  	function(response) {
		    	$('#tab-api-settings input[name=token]').val(response.data);
		  	});
		},

		addRow: function() {
			$('.tokens-list').prepend('\
				<tr class="token-item">\
				  <td class="name">'+$('#tab-api-settings [name=name]').val()+'</td>\
				  <td class="token">'+$('#tab-api-settings [name=token]').val()+'</td>\
				  <td class="key">'+$('#tab-api-settings [name=key]').val()+'</td>\
				  <td class="text-right">\
				    <a href="javascript:void(0);" class="fa fa-pencil" style="color:#444;margin-right:5px;"></a>\
				    <a href="javascript:void(0);" class="fa fa-trash"></a>\
				  </td>\
				</tr>');
		},

	}

}

api.init();