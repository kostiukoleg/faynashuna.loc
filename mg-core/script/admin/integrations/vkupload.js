var VKUploadModule = (function() {
	
	return { 
		init: function() { 
			// открытие настроек если пустые
			if ($('.integration-container [name=vkGroupId]').val().length < 1 || 
				$('.integration-container [name=vkAppId]').val().length < 1 || 
				$('.integration-container [name=vkApiKey]').val().length < 1) {
				$('.integration-container .vkStage1 .accordion-title').click();
				$('.integration-container .vkStage1btn').hide();
			}

			//остановка выгрузки/удаления
			$('.integration-container').on('click', '.vkStoptUpload', function() {
				VKUploadModule.uploading = false;
				$(".integration-container .vkStage2 .vkStoptUpload").prop('disabled', true);
			});

			// начало загрузки товаров
			$('.integration-container').on('click', '.vkStartUpload', function() {
				$(".integration-container .vkStage2 button").prop('disabled', true);
				$(".integration-container .vkStage2 .vkStoptUpload").prop('disabled', false);
				$(".integration-container .vkStage2 select").prop('disabled', true);
				$(".integration-container .vkStage2 input").prop('disabled', true);
				$(".integration-container .vkStage2 #allCats").hide();

				$('.integration-container .vkStage2 .echoPercent').html('0%');
				$('.integration-container .vkStage2 .percentWidth').css('width', '0%');

				admin.ajaxRequest({
					mguniqueurl: "action/getNumVKUpload",
					shopCats: $('.integration-container .vkStage2 [name=catsSelect]').val(),
					inactiveToo: $('.integration-container .vkStage2 [name=inactiveToo]').prop('checked'),
					useAdditionalCats: $('.integration-container .vkStage2 [name=useAdditionalCats]').prop('checked')
				},
				function (response) {
					VKUploadModule.productsCount = response.data.productsCount;
					// VKUploadModule.productIDs = response.data.productIDs;
					if (response.data.productsCount > 0) {
						$('.integration-container .vkStage2 .vkLog').append('Выгрузка началась, всего товаров - '+response.data.productsCount+'\n\n');
						VKUploadModule.uploading = true;
						VKUploadModule.upload();
					}
					else{
						$('.integration-container .vkStage2 .vkLog').append('В выбранных категориях нет товаров!\n\n');

						$(".integration-container .vkStage2 button").prop('disabled', false);
						$(".integration-container .vkStage2 .vkStoptUpload").prop('disabled', true);
						$(".integration-container .vkStage2 select").prop('disabled', false);
						$(".integration-container .vkStage2 input").prop('disabled', false);
						$(".integration-container .vkStage2 #allCats").show();

						$(".integration-container .vkStage2 .vkLog").animate({scrollTop:$(".integration-container .vkStage2 .vkLog")[0].scrollHeight - $(".integration-container .vkStage2 .vkLog").height()},1,function(){});
					}
				});
			});

			// начало удаления товаров
			$('.integration-container').on('click', '.vkDeleteAll', function() {

				var confirmText = lang.CONFIRM_DELETE_PROD;

				$('.integration-container .vkStage2 [name=catsSelect] option').each(function() {
					if ($(this).prop('selected')) {
						var catText = $(this).text();
						while (catText.slice(0,6) == '  --  ') {
							catText = catText.substr(6);
						}
						confirmText += catText+',\n';
					}
				});

				if (!confirm(confirmText)) {
					return false;
				}

				$(".integration-container .vkStage2 button").prop('disabled', true);
				$(".integration-container .vkStage2 .vkStoptUpload").prop('disabled', false);
				$(".integration-container .vkStage2 select").prop('disabled', true);
				$(".integration-container .vkStage2 input").prop('disabled', true);
				$(".integration-container .vkStage2 #allCats").hide();

				$('.integration-container .vkStage2 .echoPercent').html('0%');
				$('.integration-container .vkStage2 .percentWidth').css('width', '0%');

				admin.ajaxRequest({
					mguniqueurl: "action/getNumVKUploadDelete",
					shopCats: $('.integration-container .vkStage2 [name=catsSelect]').val(),
					useAdditionalCats: $('.integration-container .vkStage2 [name=useAdditionalCats]').prop('checked')
				},
				function (response) {
					VKUploadModule.productsCount = response.data.productsCount;
					// VKUploadModule.productIDs = response.data.productIDs;
					if (response.data.productsCount > 0) {
						$('.integration-container .vkStage2 .vkLog').append(lang.DELETE_STARTED+response.data.productsCount+'\n\n');
						VKUploadModule.uploading = true;
						VKUploadModule.delete();
					}
					else{
						$('.integration-container .vkStage2 .vkLog').append(lang.ITEMS_NOT_EXIST);

						$(".integration-container .vkStage2 button").prop('disabled', false);
						$(".integration-container .vkStage2 .vkStoptUpload").prop('disabled', true);
						$(".integration-container .vkStage2 select").prop('disabled', false);
						$(".integration-container .vkStage2 input").prop('disabled', false);
						$(".integration-container .vkStage2 #allCats").show();

						$(".integration-container .vkStage2 .vkLog").animate({scrollTop:$(".integration-container .vkStage2 .vkLog")[0].scrollHeight - $(".integration-container .vkStage2 .vkLog").height()},1,function(){});
					}
				});
			});

			// заполнение мультиселекта категорий
			$('.integration-container').on('click', '#allCats', function() {
				$("select[name=catsSelect] option").prop("selected", true);
			});

			// выбор 2го селекта категории VK
			$('.integration-container').on('change', '.vkStage2 .vkMainCat', function() {
				$('.integration-container .vkStage2 .vkMiscCatContainer').hide();
				$('.integration-container .vkStage2 .vkCats [part='+$('.integration-container .vkStage2 .vkCats .vkMainCat').val()+']').parents('.vkMiscCatContainer').show();
			});

			// Сохраняет базовые настроки
			$('.integration-container').on('click', '.saveVKSettings', function() {

				admin.ajaxRequest({
					mguniqueurl: "action/saveVKUpload",
					vkGroupId: $('input[name=vkGroupId]').val(),
					vkAppId: $('input[name=vkAppId]').val(),
					vkApiKey: $('input[name=vkApiKey]').val(),
				},
				function (response) {
					admin.indication(response.status, "Сохранено");
					$('.integration-container .vkStage1btn').show();
					if ($('.integration-container [name=vkGroupId]').val().length < 1 || 
						$('.integration-container [name=vkAppId]').val().length < 1 || 
						$('.integration-container [name=vkApiKey]').val().length < 1) {
						$('.integration-container .vkStage1btn').hide();
					}
				});
			});

			//подключение к VK
			$('.integration-container').on('click', '.connectToVK', function() {

				var url = "https://oauth.vk.com/authorize/?client_id="+$('.integration-container [name=vkAppId]').val()+"&display=page&redirect_uri="+admin.SITE+"/mg-admin&scope=market,groups,photos&response_type=code&v=5.69";
	    
	    		$(location).attr("href",url); 
			});

			VKUploadModule.token = VKUploadModule.getUrlParam('code');

			if (VKUploadModule.token) {
				$('.integration-container .vkStage1btn').hide();
				$('.integration-container .vkStage1').hide();
				window.history.replaceState(null, null, window.location.pathname);
				admin.ajaxRequest({
					mguniqueurl: "action/connectVKUpload",
					token: VKUploadModule.token
				},
				function (response) {
					VKUploadModule.access_token = response.data.access_token;
					$('.integration-container .vkStage1btn').hide();
					$('.integration-container .vkStage1').hide();

					$('.integration-container .vkStage2 [name=catsSelect]').html(response.data.categoriesOptions);
					$('.integration-container .vkStage2 .vkCats').html(response.data.selects);
					$('.integration-container .vkStage2 .vkAlbums').html(response.data.albums);

					$(".integration-container .vkStage2 [name=catsSelect]").val($(".integration-container .vkStage2 [name=catsSelect] option:first").val());
					$(".integration-container .vkStage2 .vkMainCat").val($(".integration-container .vkStage2 .vkMainCat option:first").val()).trigger('change');

					$('.integration-container .vkStage2').show();

					if (response.data.errors.length) {
						$('.integration-container .vkStage2 .vkLog').append(response.data.errors);
						$('.integration-container .vkStage2 .vkStartUpload').prop('disabled', true);
						$('.integration-container .vkStage2 .vkDeleteAll').prop('disabled', true);
					}
				});
			}
		},

		getUrlParam: function(param){
			var results = new RegExp('[\?&]'+param+'=([^&#]*)').exec(window.location.href);
			if (results==null){
				return null;
			}
			else{
				return decodeURI(results[1]) || 0;
			}
		},
		upload: function(){
			admin.ajaxRequest({
				mguniqueurl: "action/uploadVKUpload",
				access_token: VKUploadModule.access_token,
				vkCat: $('.integration-container .vkStage2 .vkCats [part='+$('.integration-container .vkStage2 .vkCats .vkMainCat').val()+']').val(),
				vkAlbum: $('.integration-container .vkStage2 .vkAlbumsSelect').val(),
				// productIDs: VKUploadModule.productIDs,
				useNull: $('.integration-container .vkStage2 [name=useNull]').prop('checked')
			},
			function (response) {

				var percent = (VKUploadModule.productsCount - response.data.remaining) / (VKUploadModule.productsCount / 100);
				percent = Math.round(percent * 100) / 100;

				$('.integration-container .vkStage2 .echoPercent').html(percent+'%');
				$('.integration-container .vkStage2 .percentWidth').css('width', percent+'%');

				$('.integration-container .vkStage2 .vkLog').append(response.data.errors);

				// VKUploadModule.productIDs = response.data.productIDs;
				if (response.data.remaining > 0 && VKUploadModule.uploading) {
					VKUploadModule.upload();
				}
				else{
					if (VKUploadModule.uploading) {
						$('.integration-container .vkStage2 .vkLog').append('Выгрузка завершена!\n\n');
					}
					else{
						$('.integration-container .vkStage2 .vkLog').append('Выгрузка остановлена.\n\n');
					}

					$(".integration-container .vkStage2 button").prop('disabled', false);
					$(".integration-container .vkStage2 .vkStoptUpload").prop('disabled', true);
					$(".integration-container .vkStage2 select").prop('disabled', false);
					$(".integration-container .vkStage2 input").prop('disabled', false);
					$(".integration-container .vkStage2 #allCats").show();
					VKUploadModule.uploading = false;
				}
				$(".integration-container .vkStage2 .vkLog").animate({scrollTop:$(".integration-container .vkStage2 .vkLog")[0].scrollHeight - $(".integration-container .vkStage2 .vkLog").height()},1,function(){});
			});
		},
		delete: function(){
			admin.ajaxRequest({
				mguniqueurl: "action/deleteVKUpload",
				access_token: VKUploadModule.access_token,
				// productIDs: VKUploadModule.productIDs,
			},
			function (response) {

				var percent = (VKUploadModule.productsCount - response.data.remaining) / (VKUploadModule.productsCount / 100);
				percent = Math.round(percent * 100) / 100;

				$('.integration-container .vkStage2 .echoPercent').html(percent+'%');
				$('.integration-container .vkStage2 .percentWidth').css('width', percent+'%');

				$('.integration-container .vkStage2 .vkLog').append(response.data.errors);

				// VKUploadModule.productIDs = response.data.productIDs;

				if (response.data.remaining > 0 && VKUploadModule.uploading) {
					VKUploadModule.delete();
				}
				else{
					if (VKUploadModule.uploading) {
						$('.integration-container .vkStage2 .vkLog').append(lang.DELETE_FINISHED);
					}
					else{
						$('.integration-container .vkStage2 .vkLog').append('Удаление остановлено.\n\n');
					}

					$(".integration-container .vkStage2 button").prop('disabled', false);
					$(".integration-container .vkStage2 .vkStoptUpload").prop('disabled', true);
					$(".integration-container .vkStage2 select").prop('disabled', false);
					$(".integration-container .vkStage2 input").prop('disabled', false);
					$(".integration-container .vkStage2 #allCats").show();
					VKUploadModule.uploading = false;
				}
				$(".integration-container .vkStage2 .vkLog").animate({scrollTop:$(".integration-container .vkStage2 .vkLog")[0].scrollHeight - $(".integration-container .vkStage2 .vkLog").height()},1,function(){});
			});
		},
	};
})();

$(document).ready(function() {
	VKUploadModule.init();
});