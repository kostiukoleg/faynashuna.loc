var GoogleMerchantModule = (function() {
	
	return { 
		init: function() {

			if ($('.integration-container .template-tabs-menu .template-tabs').length > 1) {
				$('.integration-container .template-tabs-menu .template-tabs').show();
			}
			else{
				$('.integration-container .template-tabs-menu .template-tabs').hide();
			}

			 // Добавляет вкладку
			$('.integration-container').on('click', '.newNameSave', function() {

				//удаление хлама
				var nname = $(".integration-container input[name=newName]").val();
				//nname = nname.replace( /\s/g, "");
				nname = nname.toLowerCase();
				nname = nname.replace(/[^0-9a-z]/g, '');

				if (nname == '') {
					admin.indication('error', lang.UPLOAD_NAME);
				}
				else{
					admin.ajaxRequest({
						mguniqueurl: "action/newTabGoogleMerchant",
						name: nname,
					},
					function (response) {
						if (response.data == false && nname != 0) {
							admin.indication('error', lang.NAME_ALREADY_EXISTS);
						} else {
							$('.integration-container .template-tabs-menu .template-tabs').show();
							$(".integration-container input[name=newName]").val('');
							admin.indication('success', lang.NEW_UPLOAD_CREATED);
							$('<li class="template-tabs button primary clickMe" name="'+response.data+'"><a href="javascript:void(0);" ><span>'+response.data+'</span></a></li>').insertAfter(".creator");
							$('<li style="display:inline-block;width:4px;"></li>').insertAfter(".creator");
							$('.clickMe').click().removeClass('clickMe');
						}
					});
				}
			});

			//преключение табов
			$('.integration-container').on('click', '.template-tabs', function() {

				$(this).parent().find('li').removeClass('is-active');
				$(this).addClass('is-active');
				var nname = $(this).attr('name');
				GoogleMerchantModule.resetTable();
				GoogleMerchantModule.updateLink(nname);
				if (nname.length > 0) {
					$('.newName').hide();
					$('.editOld').show();
					$('.editOldSave').attr('name', nname);
					$('.editOldDelete').attr('name', nname);

					admin.ajaxRequest({
						mguniqueurl: "action/getTabGoogleMerchant",
						name: nname,  
					},

					function(response) {
						if ($.map(response.data, function() { return 1; }).length > 1) {
							$('.bottomBorder').show();
							$('#downloadLink').show();
						}
						else{
							$('#downloadLink').hide();
							$('.bottomBorder').hide();
						}
						console.log(response);
						GoogleMerchantModule.updateText();
						$(".editOld input[name=rssName]").val(response.data.rssName);
						$(".editOld input[name=rssDesc]").val(response.data.rssDesc);
						GoogleMerchantModule.drawCheckbox(response.data.useVariants, 'useVariants');
						GoogleMerchantModule.drawCheckbox(response.data.useNull, 'useNull');
						GoogleMerchantModule.drawCheckbox(response.data.inactiveToo, 'inactiveToo');
						GoogleMerchantModule.drawCheckbox(response.data.useOldPrice, 'useOldPrice');
						$(".editOld select[name=condition]").val(response.data.condition);
						GoogleMerchantModule.drawRelatedProduct(response.data.ignoreProducts);
						GoogleMerchantModule.hideTrash();
					});
				}
				else{
					$('.newName').show();
					$('.editOld').hide();
				}
			});

			//сохранение таба
			$('.integration-container').on('click', '.editOldSave', function() {

				var nname = $(this).attr('name');

				admin.ajaxRequest({
					mguniqueurl: "action/saveTabGoogleMerchant",
					name: nname,  
					data: {
						rssName : $(".editOld input[name=rssName]").val(),
						rssDesc : $(".editOld input[name=rssDesc]").val(),
						condition : $(".editOld select[name=condition]").val(),
						useVariants : $(".editOld input[name=useVariants]").prop('checked'),
						useNull : $(".editOld input[name=useNull]").prop('checked'),
						inactiveToo : $(".editOld input[name=inactiveToo]").prop('checked'),
						useOldPrice : $(".editOld input[name=useOldPrice]").prop('checked'),
						ignoreProducts : GoogleMerchantModule.getRelatedProducts(),
					},
				},

				function(response) {
					$('.bottomBorder').show();
					$('#downloadLink').show();
					admin.indication(response.status, lang.SAVED);
					GoogleMerchantModule.clearTrash(nname);
				});
				
			});

			//удаление таба
			$('.integration-container').on('click', '.editOldDelete', function() {

				var nname = $(this).attr('name');

				admin.ajaxRequest({
					mguniqueurl: "action/deleteTabGoogleMerchant",
					name: nname,  
				},

				function(response) {
					$('.tabs-list').children('li[name="'+nname+'"]').remove();
					$('.tabs-list').children('li[name=""]').click();
					admin.indication(response.status, lang.DELETED);
				});
				
			});

			//Заполнение базы
			$('.integration-container').on('click', '.updateDB', function() {

				admin.ajaxRequest({
					mguniqueurl: "action/updateDBGoogleMerchant", 
				},

				function(response) {
					admin.indication(response.status, lang.FILLED);
					window.location.reload(true);
				});
				
			});

			//изменение типа тегов
			$('.integration-container').on('click', '.customTagsContainer .changeCustomTagType', function () {
				if ($(this).attr('tagType') == 'prop') {
					$(this).attr('tagType', 'text');
					$(this).parent().parent().find('.customProp').hide();
					$(this).parent().parent().find('.customTagText').show();
				}
				else if ($(this).attr('tagType') == 'text') {
					$(this).attr('tagType', 'prop');
					$(this).parent().parent().find('.customProp').show();
					$(this).parent().parent().find('.customTagText').hide();
				}
			});

			// Разворачивание подпунктов по клику в интеграции
	      $('.integration-container').on('click', '.integraion-category .show_sub_menu', function() {
	        var object = $(this).parents('tr');
	        var id = $(this).parents('tr').data('id');
	        var level = $(this).parents('tr').data('level');
	        var group = 'group-'+$(this).parents('tr').data('id');
	        level++;

	        thisSortNumber = 0;
	        isFindeSorte = false;
	        $('.integraion-category .main-table tbody tr').each(function() {
	          if($(this).data('id') == id) {
	            isFindeSorte = true;
	          }
	          if(!isFindeSorte) {
	            thisSortNumber++;
	          }
	        });

	        if ($(this).hasClass('opened')) {

	          category.group = $(this).parents('tr').data('group');

	          var trCount = $('.integraion-category .main-table tbody tr').length;

	          var startDel = false;
	          $('.integraion-category .main-table tbody tr').each(function() {
	            if($(this).data('level') >= level) {
	              if($(this).data('group') == group) {
	                startDel = true;
	              }
	            }
	            if(startDel) {
	              if($(this).data('level') >= level) {
	                $(this).detach();
	              } else {
	                startDel = false;
	              }
	            }
	          });

	          $(this).removeClass('opened');
	        } else {
	          object.after('\
	            <tr id="loader-'+id+'">\
	              <td style="padding-left:40px;"><img src="'+admin.SITE+'/mg-admin/design/images/loader-small.gif"></td>\
	              <td></td>\
	            </tr>');
	          admin.ajaxRequest({
	            mguniqueurl: "action/showSubCategorySimple",
	            id: id,
	            level: level
	          },
	          function(response) {      
	            $('#loader-'+id).detach();
	            object.after(response.data);
	            category.hidePageRows(level+1);
	            GoogleMerchantModule.updateText();
	          });

	          $(this).addClass('opened');
	          
	        }
	      });
	      
			document.cookie = 'openedCategoryAdmin' + '=; expires=Thu, 01-Jan-70 00:00:01 GMT;';

			//игнор товаров
			// показывает сроку поиска для связанных товаров
			$('.integration-container').on('click', '.add-related-product', function() {
				$('.select-product-block').show();
			});

			// Удаляет связанный товар из списка связанных
			$('.integration-container').on('click', '.add-related-product-block .remove-added-product', function() {
				$(this).parents('.product-unit').remove();
				GoogleMerchantModule.widthRelatedUpdate();
				GoogleMerchantModule.msgRelated();
			});

			// Закрывает выпадающий блок выбора связанных товаров
			$('.integration-container').on('click', '.add-related-product-block .cancel-add-related', function() {
				$('.select-product-block').hide();
			});

			// Поиск товара при создании связанного товара.
			// Обработка ввода поисковой фразы в поле поиска.
			$('.integration-container').on('keyup', '.search-block input[name=searchcat]', function() {
				admin.searchProduct($(this).val(),'.integration-container .search-block .fastResult', -1, 'nope', false);
			});

			// Подстановка товара из примера в строку поиска связанного товара.
			$('.integration-container').on('click', '.search-block  .example-find', function() {
				$('.section-catalog .search-block input[name=searchcat]').val($(this).text());
				admin.searchProduct($(this).text(),'.integration-container .search-block .fastResult', -1, 'nope', false);
			});

			// Клик по найденым товарам поиска в форме добавления связанного товара.
			$('.integration-container').on('click', '.fast-result-list a', function() {
				GoogleMerchantModule.addrelatedProduct($(this).data('element-index'));
			});
			//игнор товаров/////////////

			//модалка
			$('.integration-container').on('click', '.integraion-category .upload-cat-text', function() {
				admin.openModal('#add-google-category-modal');
				GoogleMerchantModule.fillModal($(this).attr('upload-cat-name'));
				$('.integration-container #add-google-category-modal .save-button').attr('shopId', $(this).attr('data-cat-id'));
			});

			$('.integration-container').on('change', '#add-google-category-modal .reveal-body .customCatSelect', function() {
				var tmp = $(this).val();
				if (tmp != -5) {
					GoogleMerchantModule.fillModal(tmp);
				}
				else{
					var last = GoogleMerchantModule.findLast();
					GoogleMerchantModule.fillModal(last);
				}
			});

			$('.integration-container').on('click', '#add-google-category-modal .save-button', function() {
				shopCatId = $(this).attr('shopId');
				var googleCatId = GoogleMerchantModule.findLast();
				nname = $('.template-tabs-menu').find('.is-active').attr('name');

				admin.ajaxRequest({
					mguniqueurl: "action/saveCatGoogleMerchant",
					shopId: shopCatId,
					googleId: googleCatId,
					name: nname
				},

				function(response) {
					GoogleMerchantModule.updateText();
					admin.indication(response.status, lang.SAVED);
					admin.closeModal('#add-google-category-modal');
				});
			});
			//модалка///////////////////
			//таблица с категориями
			$('.integration-container').on('click', '.cat-apply-follow', function() {
				var shopCatId = $(this).parent().parent().find('.upload-cat-text').attr('data-cat-id');
				var googleCatId = $(this).parent().parent().find('.upload-cat-text').attr('upload-cat-name');
				nname = $('.template-tabs-menu').find('.is-active').attr('name');

				admin.ajaxRequest({
					mguniqueurl: "action/updateCatsRecursGoogleMerchant",
					shopId: shopCatId,
					googleId: googleCatId,
					name: nname
				},

				function(response) {
					GoogleMerchantModule.updateText();
					admin.indication(response.status, lang.SAVED);
				});
			});

			$('.integration-container').on('click', '.cat-cansel', function() {
				var shopCatId = $(this).parent().parent().find('.upload-cat-text').attr('data-cat-id');
				var googleCatId = 0;
				nname = $('.template-tabs-menu').find('.is-active').attr('name');

				admin.ajaxRequest({
					mguniqueurl: "action/saveCatGoogleMerchant",
					shopId: shopCatId,
					googleId: googleCatId,
					name: nname
				},

				function(response) {
					GoogleMerchantModule.updateText();
					admin.indication(response.status, lang.SAVED);
				});
			});
			//таблица с категориями//////
		},

		fillModal: function(catId) {
			admin.ajaxRequest({
				mguniqueurl: "action/buildSelectsGoogleMerchant",
				id: catId,
			},

			function(response) {
				$('#add-google-category-modal .reveal-body').html('');
				$('#add-google-category-modal .reveal-body').html(response.data.html);
				$('#add-google-category-modal .reveal-body .customCatSelect').val(-5);

				for (var j=0; j<response.data.choices.length; j++) {
					if (response.data.choices[j]>0) {
						$('#add-google-category-modal .reveal-body .customCatSelect:eq('+j+')').val(response.data.choices[j]);
					}
				}

			});
		},

		findLast: function(catId) {
			var count = $('.customCatSelect').length;
			var res = 0;
			var tmp = '';

			for (var j=0; j<count; j++) {
				tmp = $('#add-google-category-modal .reveal-body .customCatSelect:eq('+j+')').val();
				if (tmp != -5) {
					res = tmp;
				}
				else{
					return res;
				}
			}
			return res;
		},

		clearTrash: function(nname) {
			admin.ajaxRequest({
				mguniqueurl: "action/clearTrashGoogleMerchant",
				name: nname,
			});
		},

		resetTable: function() {
			$('.integration-container .category-tree').find('.sticker-menu').addClass('alert');
			$('.integration-container .category-tree').find('.sticker-menu').removeClass('success');
			$('.integration-container .category-tree').find('.upload-cat-text').attr('upload-cat-name', 0);
			$('.integration-container .category-tree').find('.upload-cat-text').text('Привязать категорию');
		},

		updateText: function() {

			nname = $('.template-tabs-menu').find('.is-active').attr('name');

			admin.ajaxRequest({
				mguniqueurl: "action/getCatsGoogleMerchant",
				name: nname
			},

			function(response) {
				$.each( response.data, function( index, value ){
					if ($('.integration-container .category-tree [data-id='+index+']').length >0) {

						if (value > 0) {
							$('.integration-container .category-tree [data-id='+index+']').find('.sticker-menu').removeClass('alert');
							$('.integration-container .category-tree [data-id='+index+']').find('.sticker-menu').addClass('success');
							var objj = $('.integration-container .category-tree [data-id='+index+']').find('.upload-cat-text');
							objj.attr('upload-cat-name', value);

							admin.ajaxRequest({
								mguniqueurl: "action/getCatNameGoogleMerchant",
								id: value,
							},

							function(response) {
								objj.text(response.data);
							});
						}

						else{
							$('.integration-container .category-tree [data-id='+index+']').find('.sticker-menu').addClass('alert');
							$('.integration-container .category-tree [data-id='+index+']').find('.sticker-menu').removeClass('success');
							$('.integration-container .category-tree [data-id='+index+']').find('.upload-cat-text').attr('upload-cat-name', value);
							$('.integration-container .category-tree [data-id='+index+']').find('.upload-cat-text').text('Привязать категорию');
						}
					}
				});

				$('.integration-container .category-tree tr').each(function() {
					if ($(this).find('.show_sub_menu').length > 0) {
						$(this).find('.cat-apply-follow').show();
					}
					else{
						$(this).find('.cat-apply-follow').hide();
					}
				});
			});
			
		},

		widthRelatedUpdate: function() {
			var prodWidth = $('.product-unit').length * ($('.product-unit').width() + 30);
			$('.related-block').width(prodWidth);
			if($('.product-unit').length == 0) {
				$('.added-related-product-block').css('display','none');
			} else {
				$('.added-related-product-block').css('display','');
			}
			if($('.category-unit').length == 0) {
				$('.added-related-category-block').css('display','none');
			} else {
				$('.added-related-category-block').css('display','');
			}
		},

		addrelatedProduct: function(elementIndex, product) {
			$('.search-block .errorField').css('display', 'none');
			$('.search-block input.search-field').removeClass('error-input');
			if(!product) {
				var product = admin.searcharray[elementIndex];
			}

			if (product.category_url.charAt(product.category_url.length-1) == '/') {
				product.category_url = product.category_url.slice(0,-1);
			}

			var html = GoogleMerchantModule.rowRelatedProduct(product);
			$('.added-related-product-block .product-unit[data-id='+product.id+']').remove();
			$('.related-wrapper .added-related-product-block').prepend(html);
			GoogleMerchantModule.widthRelatedUpdate();
			GoogleMerchantModule.msgRelated();
			$('input[name=searchcat]').val('');
			$('.select-product-block').hide();
			$('.fastResult').hide();
		},

		rowRelatedProduct: function(product) {
			var price = (product.real_price) ? product.real_price : product.price;

			var html = '\
			<div class="product-unit" data-id='+ product.id +' data-code="'+ product.code +'">\
				<div class="product-img" style="text-align:center;height:50px;">\
					<a href="javascript:void(0);"><img src="' + product.image_url + '" style="height:50px;"></a>\
					<a class="remove-img fa fa-trash tip remove-added-product" href="javascript:void(0);" aria-hidden="true" data-hasqtip="88" oldtitle="'+lang.DELETE+'" title="" aria-describedby="qtip-88"></a>\
				</div>\
				<a href="' + mgBaseDir + '/' + product.category_url + "/" + product.product_url +
					'" data-url="' + product.category_url +
					"/" + product.product_url + '" class="product-name" target="_blank" title="' +
					product.title + '">' +
					product.title + '</a>\
				<span>' + price +' '+ admin.CURRENCY+'</span>\
			</div>\
			';
			return html;
		},

		msgRelated: function() {
			if($('.added-related-product-block .product-unit').length==0&&$('.added-related-category-block .category-unit').length==0) {
				if ($('a.add-related-product.in-block-message').length==0) {
				$('.related-wrapper .added-related-product-block').append('\
				 <a class="add-related-product in-block-message" href="javascript:void(0);"><span></span></a>\
			 ');
				}
				$('.added-related-product-block').width('800px');
			}else {
				$('.added-related-product-block .add-related-product').remove();
			};
			if ($('.added-related-category-block .category-unit').length==0) {
				$('.add-related-product-block .add-related-category.in-block-message').hide();
			} else {
				$('.add-related-product-block .add-related-category.in-block-message').show();
			}
		},

		getRelatedProducts: function() {
			var result = '';
			$('.add-related-product-block .product-unit').each(function() {
				result += $(this).data('code') + ',';
			});
			result = result.slice(0, -1);

			return result;
		},

		drawRelatedProduct: function(relatedArr) {
			$('.related-block').html('');
			$('.related-block').hide();
			relatedArr.forEach(function (product, index, array) {
				var html = GoogleMerchantModule.rowRelatedProduct(product);
				$('.related-wrapper .added-related-product-block').append(html);
				GoogleMerchantModule.widthRelatedUpdate();
			});
			GoogleMerchantModule.msgRelated();
		},

		hideTrash: function() {
			if ($(".editOld select[name=condition]").val() == null) {
				$(".editOld select[name=condition]").val('new');
			}
		},

		drawCheckbox: function(resp, name) {
			if (resp == 'true') {
				$(".editOld input[name="+name+"]").prop('checked', true);
			}
			else {
				$(".editOld input[name="+name+"]").prop('checked', false);
			}
		},

		updateLink: function(name) {
			$('#ymlLink').attr('href', $('#ymlLink').attr('defaul')+name);
			$('#ymlLink').text($('#ymlLink').attr('defaul')+name);
			$('#downloadLink').attr('href', $('#downloadLink').attr('defaul')+name);
		},
	}
})();

$(document).ready(function() {
	GoogleMerchantModule.init();
});