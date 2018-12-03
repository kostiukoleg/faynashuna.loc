var wholesale = (function () {

  	return {

	  	search: '',
	  	selectedProductId: '',
	  	selectedVariantId: 0,
	  	page: 1,
	  	categoryId: 0,
	  	selectedGroup: 1,
	  	toVariants: 1,

	  	init: function() {

	  		wholesale.loadList();

	  		// alert($('#tab-wholesale-settings .wholesalesGroup .template-tabs.success').data('id'));
	  		wholesale.selectedGroup = $('#tab-wholesale-settings .wholesalesGroup .template-tabs.success').data('id');

	  		$('.admin-center').on('click', '#tab-wholesale-settings .wholesalesGroup .template-tabs', function() {
	  			$('#tab-wholesale-settings .wholesalesGroup .template-tabs').removeClass('success');
	  			$(this).addClass('success');
	  			wholesale.selectedGroup = $(this).data('id');
	  			wholesale.loadRule();
	  		});

	  		$('.admin-center').on('click', '#tab-wholesale-settings .editWholeRule', function() {
	  			wholesale.selectedProductId = $(this).parents('.product').data('product-id');
	  			wholesale.selectedVariantId = $(this).parents('.product').data('variant-id');
	  			wholesale.selectedGroup = $(this).data('group');
	  			$('#tab-wholesale-settings .product').removeClass('selected');
	  			$(this).addClass('selected');
	  			admin.openModal('#editWholePriceModal');
	  			wholesale.loadRule();
	  			$('#tab-wholesale-settings .edit-field').show();
	  		});

	  		$('.admin-center').on('click', '#tab-wholesale-settings .addField', function() {
	  			wholesale.addField();
	  		});

	  		$('.admin-center').on('click', '#tab-wholesale-settings .save-button', function() {
	  			wholesale.saveRule();
	  		});

	  		$('.admin-center').on('click', '#tab-wholesale-settings .deleteLineRule', function() {
	  			if(confirm('Удалить?')) {
	  				$(this).parents('.rule').detach();
	  			}
	  		});

	  		$('.admin-center').on('click', '#tab-wholesale-settings .wholesale-onlyNotSetPrice', function() {
	  			wholesale.loadList();
	  		});

	  		$('.admin-center').on('click', '#tab-wholesale-settings .newNavigator', function() {
	  			wholesale.page = admin.getIdByPrefixClass($(this), 'page');
	  			wholesale.loadList();
	  		});

	  		$('.admin-center').on('change', '#tab-wholesale-settings .category-select', function() {
	  			wholesale.categoryId = $(this).val();
	  			wholesale.page = 1;
	  			wholesale.loadList();
	  		});

	  		$('.admin-center').on('change', '#tab-wholesale-settings [name=sale-type]', function() {
	  			$(this).data('type', $(this).val());
	  			$('#tab-wholesale-settings .text-type').text($('#tab-wholesale-settings [name=sale-type] option[value='+$(this).val()+']').text());
	  			wholesale.saveType();
	  		});

	  		$('.admin-center').on('change', '#tab-wholesale-settings #setVariantsToo', function() {
	  			if($(this).prop('checked')) {
	  				wholesale.toVariants = 1;
	  			} else {
	  				wholesale.toVariants = 0;
	  			}
	  			wholesale.loadList();
	  		});

	  		$('.admin-center').on('click', '#tab-wholesale-settings .addWholesalesGroup', function() {
	  			wholesale.addGroup();
	  			// $('wholesalesGroup').append('<div class="button primary template-tabs" data-id="'.$value.'">Цена '.$value.'</div>');
	  		});

	  		$('.admin-center').on('click', '#tab-wholesale-settings .deleteWholePrice', function() {
	  			if(!confirm('Удалить цену?')) return false;
	  			id = $(this).data('id');
	  			$(this).parents('tr').detach();
	  			wholesale.deleteGroup(id);
	  		});

	  		$('.admin-center').on('change', '#tab-wholesale-settings .setToWholesaleGroup', function() {
	  			id = $(this).parents('tr').data('id');
	  			group = $(this).val();
	  			wholesale.setToWholesaleGroup(id, group);
	  		});

	  		setInterval(function() {
	  			if(wholesale.search != $('#tab-wholesale-settings .wholesale-search').val()) {
	  				wholesale.search = $('#tab-wholesale-settings .wholesale-search').val();
	  				wholesale.page = 1;
	  				wholesale.loadList();
	  			}
	  		}, 1000);
	  	},

	  	setToWholesaleGroup: function(id, group) {
	  		admin.ajaxRequest({
	  			mguniqueurl: "action/setToWholesaleGroup", // действия для выполнения на сервере  
	  			id: id,
	  			group: group,
	  		},      
	  		function(response) {
	  			admin.indication(response.status, response.msg);
	  		});
	  	},

	  	deleteGroup: function(id) {
	  		admin.ajaxRequest({
	  			mguniqueurl: "action/deleteWholesaleGroup", // действия для выполнения на сервере  
	  			id: id  
	  		},      
	  		function(response) {
	  			$('.userList').html(response.data.htmlUser);
	  			$('.priceList').html(response.data.htmlPrice);
	  			$('.productsHead').html('<tr>'+response.data.productsHead+'</tr>');
	  			wholesale.loadList();
	  		});
	  	},

	  	addGroup: function() {
	  		admin.ajaxRequest({
	  			mguniqueurl: "action/addWholesaleGroup", // действия для выполнения на сервере    
	  		},      
	  		function(response) {
	  		  	$('.userList').html(response.data.htmlUser);
	  		  	$('.priceList').html(response.data.htmlPrice);
	  		  	$('.productsHead').html('<tr>'+response.data.productsHead+'</tr>');
	  		  	wholesale.loadList();
	  		});
	  	},

	  	loadList: function() {
	  		admin.ajaxRequest({
	  			mguniqueurl: "action/loadWholesaleList", // действия для выполнения на сервере    
	  			search: wholesale.search,
	  			only: $('#tab-wholesale-settings .wholesale-onlyNotSetPrice').prop('checked'),
	  			page: wholesale.page,
	  			category: wholesale.categoryId,
	  			group: wholesale.selectedGroup,
	  			variants: wholesale.toVariants,
	  		},      
	  		function(response) {
	  		  	$('#tab-wholesale-settings .product-list').html(response.data.html);
	  		  	$('#tab-wholesale-settings .table-pagination').html(response.data.pager);
	  		  	$('#tab-wholesale-settings .linkPage').addClass('newNavigator').removeClass('linkPage');
	  		});
	  	},

	  	loadRule: function() {
	  		var data = {};
	  		data['productId'] = wholesale.selectedProductId;
	  		data['variantId'] = wholesale.selectedVariantId;
	  		admin.ajaxRequest({
	  			mguniqueurl: "action/loadWholesaleRule", // действия для выполнения на сервере    
	  			data: data,
	  			group: wholesale.selectedGroup,     
	  		},      
	  		function(response) {
	  		  	$('#tab-wholesale-settings .rule-list').html(response.data);
	  		  	$('#tab-wholesale-settings [name=sale-type]').val($('#tab-wholesale-settings [name=sale-type]').data('type'));
	  		  	$('#tab-wholesale-settings .text-type').text($('#tab-wholesale-settings [name=sale-type] option[value='+$('#tab-wholesale-settings [name=sale-type]').val()+']').text());
	  		});
	  	},

	  	addField: function() {
	  		$('.toDel').detach();
	  		$('#tab-wholesale-settings .rule-list').append('\
	  			<tr class="rule">\
	  				<td><input type="text" name="count" placeholder="'+lang.EXAMPLE_2+'"></td>\
	  				<td><input type="text" name="price" placeholder="'+lang.EXAMPLE_4+'"></td>\
	  				<td class="text-right"><i class="fa fa-trash"></i></td>\
	  			</tr>');
	  	},

	  	saveRule: function() {
	  		var data = {};
	  		$('#tab-wholesale-settings .rule').each(function(index) {
	  			data[index] = {};
	  			data[index]['count'] = $(this).find('[name=count]').val();
	  			data[index]['price'] = $(this).find('[name=price]').val();
	  		});
	  		admin.ajaxRequest({
	  			mguniqueurl: "action/saveWholesaleRule", // действия для выполнения на сервере    
	  			data: data,
	  			product: wholesale.selectedProductId,
	  			variant: wholesale.selectedVariantId,
	  			group: wholesale.selectedGroup,
	  			variants: wholesale.toVariants,
	  		},      
	  		function(response) {
	  		  	admin.indication(response.status, response.msg);
	  		  	admin.closeModal('#editWholePriceModal');
	  		});
	  	},

	  	saveType: function() {
	  		admin.ajaxRequest({
	  			mguniqueurl: "action/saveWholesaleType", // действия для выполнения на сервере    
	  			type: $('#tab-wholesale-settings [name=sale-type]').val()
	  		},      
	  		function(response) {
	  		  	admin.indication(response.status, response.msg);
	  		});
	  	},

  	}

})();

wholesale.init();