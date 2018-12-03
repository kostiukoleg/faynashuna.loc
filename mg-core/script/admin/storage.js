if(storage == undefined) {

	var storage = {

		editMode: undefined,
		item: undefined,

		init: function() {
			// Сохранение настроек storage
			$('body').on('click', '#tab-storage-settings .save-button', function() {
				storage.saveStorage();
			});

			// Сохранение настроек storage
			$('body').on('click', '#tab-storage-settings .on-off-storage', function() {
				storage.onOffStorage();
			});

			// Открытие модали для создания storage
			$('body').on('click', '#tab-storage-settings .addStorage', function() {
				storage.editMode = 'add';
				$('#tab-storage-settings input, #tab-storage-settings textarea').val('');
				$('#tab-storage-settings #storage-edit-modal input').removeClass('error-input');
				admin.openModal('#storage-edit-modal');
			});

			// удаление storage
			$('body').on('click', '#tab-storage-settings .fa-trash', function() {
				if(confirm(lang.ADMIN_LOCALE_3)) {
					$(this).parents('.storage-item').detach();
					storage.saveStorage(false);
				}
			});

			// редактирование storage
			$('body').on('click', '#tab-storage-settings .fa-pencil', function() {
				storage.editMode = 'edit';
				storage.item = $(this).parents('.storage-item');
				$('#tab-storage-settings input').val('');
				$('#tab-storage-settings [name=id]').val(storage.item.find('.id').html());
				$('#tab-storage-settings [name=name]').val(storage.item.find('.name').html());
				$('#tab-storage-settings [name=adress]').val(storage.item.find('.adress').html());
				$('#tab-storage-settings [name=desc]').val(storage.item.find('.desc').html());
				admin.openModal('#storage-edit-modal');
			});
		},

		saveStorage: function(errorCheck) {
			errorCheck = typeof errorCheck !== 'undefined' ? errorCheck : true;
			if(errorCheck) {
				var error = false;
				$('#tab-storage-settings #storage-edit-modal input:not([name=id])').each(function() {
					if($(this).val() == '') {
						$(this).addClass('error-input');
						error = true;
					} else {
						$(this).removeClass('error-input');
					}
				});
				if(error) return false;
			}
			if($('#tab-storage-settings [name=id]').val() == '') {
				$('#tab-storage-settings [name=id]').val(Date.now());
			}
		  	var data = {};
		  	if(storage.editMode == 'add') {
		  		$('#tab-storage-settings .toDel').detach();
		  		storage.addRow();
		  	} 
		  	if(storage.editMode == 'edit') {
		  		storage.item.find('.id').html($('#tab-storage-settings [name=id]').val());
		  		storage.item.find('.name').html($('#tab-storage-settings [name=name]').val());
		  		storage.item.find('.adress').html($('#tab-storage-settings [name=adress]').val());
		  		storage.item.find('.desc').html($('#tab-storage-settings [name=desc]').val());
		  	}
		  	$('.storages-list .storage-item').each(function(index) {
		  		if(($(this).find('.name').html() != undefined)&&($(this).find('.name').html() != '')) {
		  			data[index] = {};
		  			data[index]['id'] = $(this).find('.id').html();
		  			data[index]['name'] = $(this).find('.name').html();
		  			data[index]['adress'] = $(this).find('.adress').html();
		  			data[index]['desc'] = $(this).find('.desc').html();
		  		}
		  	});
		  	admin.ajaxRequest({
		    	mguniqueurl: "action/saveStorage", // действия для выполнения на сервере    
		    	data: data         
		  	},      
		  	function(response) {
		    	admin.indication(response.status, response.msg);  
		    	admin.closeModal('#storage-edit-modal');
		    	admin.refreshPanel();     
		  	});
		},

		addRow: function() {
			$('.storages-list').append('\
				<tr class="storage-item">\
					<td class="id" style="display:none;">'+$('#tab-storage-settings [name=id]').val()+'</td>\
					<td class="desc" style="display:none;">'+$('#tab-storage-settings [name=desc]').val()+'</td>\
				  	<td class="name">'+$('#tab-storage-settings [name=name]').val()+'</td>\
				  	<td class="adress">'+$('#tab-storage-settings [name=adress]').val()+'</td>\
				  	<td class="text-right">\
				  	  	<a href="javascript:void(0);" class="fa fa-pencil" style="color:#444;margin-right:5px;"></a>\
				  	  	<a href="javascript:void(0);" class="fa fa-trash"></a>\
				  	</td>\
				</tr>');
		},

		onOffStorage: function() {
			admin.ajaxRequest({
			  	mguniqueurl: "action/onOffStorage", // действия для выполнения на сервере    
			  	data: $('#tab-storage-settings .on-off-storage').prop('checked')
			},      
			function(response) {
			  	admin.indication(response.status, response.msg);  
			  	location.reload();
			});
		},

	}

}

storage.init();