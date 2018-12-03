var interface = (function () {
  return {
  	schemes: ['colorMain', 'colorLink', 'colorSave', 'colorBorder', 'colorSecondary'],

  	init: function() {
  		// для повторной инициализации
  		$('.colorpicker-style').detach();
  		$('body').append('<div class="colorpicker-style"></div>');
  		$('.colorpicker-style').append('<link rel="stylesheet" type="text/css" href="' + admin.SITE + '/mg-core/script/colorPicker/css/colorpicker.css" />\
  		                  <link rel="stylesheet" media="screen" type="text/css" href="' + admin.SITE + '/mg-core/script/colorPicker/css/layout.css" />');
  		$('.colorpicker').detach();
  		// цветовая схема
		$('#colorMain').ColorPicker({
		  color: admin.rgb2hex($('#colorMain div').css('backgroundColor')),
		  onShow: function (colpkr) {
		    $(colpkr).fadeIn(0);
		    return false;
		  },
		  onHide: function (colpkr) {
		    $(colpkr).fadeOut(0);
		    return false;
		  },
		  onChange: function (hsb, hex, rgb) {
		    $('#colorMain div').css('backgroundColor', '#' + hex);
		  }
		});
		// цвет ссылок
		$('#colorLink').ColorPicker({
		  color: admin.rgb2hex($('#colorLink div').css('backgroundColor')),
		  onShow: function (colpkr) {
		    $(colpkr).fadeIn(0);
		    return false;
		  },
		  onHide: function (colpkr) {
		    $(colpkr).fadeOut(0);
		    return false;
		  },
		  onChange: function (hsb, hex, rgb) {
		    $('#colorLink div').css('backgroundColor', '#' + hex);
		  }
		});
		// цвет кнопок сохранения
		$('#colorSave').ColorPicker({
		  color: admin.rgb2hex($('#colorSave div').css('backgroundColor')),
		  onShow: function (colpkr) {
		    $(colpkr).fadeIn(0);
		    return false;
		  },
		  onHide: function (colpkr) {
		    $(colpkr).fadeOut(0);
		    return false;
		  },
		  onChange: function (hsb, hex, rgb) {
		    $('#colorSave div').css('backgroundColor', '#' + hex);
		  }
		});
		// цвет secondary
		$('#colorSecondary').ColorPicker({
		  color: admin.rgb2hex($('#colorSecondary div').css('backgroundColor')),
		  onShow: function (colpkr) {
		    $(colpkr).fadeIn(0);
		    return false;
		  },
		  onHide: function (colpkr) {
		    $(colpkr).fadeOut(0);
		    return false;
		  },
		  onChange: function (hsb, hex, rgb) {
		    $('#colorSecondary div').css('backgroundColor', '#' + hex);
		  }
		});
		// цвет рамок
		$('#colorBorder').ColorPicker({
		  color: admin.rgb2hex($('#colorBorder div').css('backgroundColor')),
		  onShow: function (colpkr) {
		    $(colpkr).fadeIn(0);
		    return false;
		  },
		  onHide: function (colpkr) {
		    $(colpkr).fadeOut(0);
		    return false;
		  },
		  onChange: function (hsb, hex, rgb) {
		    $('#colorBorder div').css('backgroundColor', '#' + hex);
		  }
		});

  		// сохранение и применение стилей
  		$('.admin-center').on('click', '.section-settings .save-interface', function() {  
  			interface.save();
  		});

  		$('.admin-center').on('click', '.section-settings .default-interface', function() {  
  			interface.default();
  		});

  		//загрузка фона
		$('body').on('change', '.section-settings input[name="customBackground"]', function() {
			var img_container = $(this).parents('.upload-img-block');
			        
			if($(this).val()) {          
			  img_container.find('.imageform').ajaxForm({
			    type:"POST",
			    url: "ajax",
			    data: {
			      mguniqueurl:"action/updateCustomAdmin"
			    },
			    cache: false,
			    dataType: 'json',
			    success: function(response) {
			      if (response.status=='error') {
			        admin.indication(response.status, response.msg);
			      } 
			      else {
			        var src = admin.SITE+'/uploads/'+response.data.img;
			        var img = response.data.img.substring(12);

			        img_container.find('#customBackground').attr('src', src).attr('fileName', img);
			        $('.customBackground').css('backgroundImage', 'url("'+src+'")').attr('img', src).show();
			      }
			     }
			  }).submit();
			}
		});

		//загрузка логотипа
		$('body').on('change', '.section-settings input[name="customAdminLogo"]', function() {
			var img_container = $(this).parents('.upload-img-block');
			        
			if($(this).val()) {          
			  img_container.find('.imageform').ajaxForm({
			    type:"POST",
			    url: "ajax",
			    data: {
			      mguniqueurl:"action/updateCustomAdmin"
			    },
			    cache: false,
			    dataType: 'json',
			    success: function(response) {
			      if (response.status=='error') {
			        admin.indication(response.status, response.msg);
			      } 
			      else {
			        var src = admin.SITE+'/uploads/'+response.data.img;
			        var img = response.data.img.substring(12);

			        img_container.find('#customAdminLogo').attr('src', src).attr('fileName', img);
			        $('.customAdminLogoTrash').show();
			        
			      }
			     }
			  }).submit();
			}
		});

		//сброс логотипа
		$('body').on('click', '.section-settings .customAdminLogoTrash', function() {
			$('.customAdminLogoTrash').hide();
			$('#customAdminLogo').attr('src', admin.SITE+'/mg-admin/design/images/logo-normal.png').attr('fileName', '');
		});
  	},

  	save: function() {
  		var data = {};
  		for(i = 0; i < interface.schemes.length; i++) {
  			data[interface.schemes[i]] = $('#'+interface.schemes[i]).find('div').css('background-color');
  		}

  		admin.ajaxRequest({
  		  mguniqueurl:"action/saveInterface",
  		  data: data,
  		  bg: $('#bg').val(),
  		  customBG: $('#customBackground').attr('fileName'),
  		  customLogo: $('#customAdminLogo').attr('fileName'),
  		  fullscreen: $('#bgfullscreen').prop('checked')
  		},
  		function(response) {
  		  location.reload();
  		});
  	},

  	default: function() {
  		admin.ajaxRequest({
  		  mguniqueurl:"action/defaultInterface",
  		},
  		function(response) {
  		  location.reload();
  		});
  	},

  }
})();