/**
 * Модуль для  раздела "галерея".
 */

var gallery = (function () {
  return {
   	pluginName: 'mg-gallery',
   	selectedGallery: undefined,
   	selectedImg: undefined,
    /**
     * Инициализирует обработчики для кнопок и элементов раздела.
     */
    init: function() {
      // gallery.loadGalleries();

      // добавление новой галереи
      $('.admin-center').on('click', '#add-new-gallery', function(){
      	gallery.addGallery();
      });

      // вызов модального окна для редактирования галереи
      $('.admin-center').on('click', '.gallery-edit', function(){
        gallery.loadGalleryInfo($(this).data('id'));
      });

      // сохранение настроек галереи
      $('.admin-center').on('click', '.gallery-save', function(){
        gallery.saveGallery(
        	$('#gal-name').val(),
        	$('#gal-height').val(),
        	$('#gal-in-line').val()
        	);
      });

      // сохранение настроек изображения
      $('.admin-center').on('click', '.img-save', function(){
        gallery.saveImg(
        	$('#img-title').val(),
        	$('#img-alt').val()
        	);
      });

      // Выбор картинки слайдера
      $('.admin-center').on('click', '#browseImage', function() {
      	admin.openUploader('gallery.getFile');
      });    

      // Удаление картинки из списка, не сам файл
      $('.admin-center').on('click', '.remove-img', function() {
      	gallery.removeImg($(this).data('id'));
      });   

      // Удаление галереи
      $('.admin-center').on('click', '.delete-gallery', function() {
      	if(confirm('Удалить?')) {
      		gallery.deleteGallery($(this).data('id'));
      	}
      });  

      // Выбор изображения для редактирования
      $('.admin-center').on('click', '.edit-img', function() {
      	gallery.selectedImg = $(this).data('id');
      	$('#img-title').val($(this).data('title'));
      	$('#img-alt').val($(this).data('alt'));
      });  

    },

    addGallery: function() {
      admin.ajaxRequest({
        	pluginHandler: "mg-gallery", // имя папки в которой лежит данный плагин
          actionerClass: "Pactioner", 
          action: "addGallery", // название действия в пользовательском классе       
        },
        function(response) {
        	gallery.loadGalleries();
        }
      );   
    },

    loadGalleries: function() {
      admin.ajaxRequest({
        	pluginHandler: "mg-gallery", // имя папки в которой лежит данный плагин
          actionerClass: "Pactioner", 
          action: "loadGalleries", // название действия в пользовательском классе       
        },
        function(response) {
        	var table = '';
        	for(i = 0; i < response.data.length; i++) {
        		if(response.data[i].gal_name == 'Новая галерея') {
        			response.data[i].gal_name = '<b>'+response.data[i].gal_name+'</b>';
        		}
        		table += '<tr>';
        		table += '<td>[gallery id='+response.data[i].id+']</td>';
        		table += '<td style="cursor:pointer;" class="gallery-edit" data-id="'+response.data[i].id+'">'+response.data[i].gal_name+'</td>';
        		table += '<td><ul class="edit">\
                      <li class="edit-row gallery-edit" data-id="'+response.data[i].id+'">\
                        <a class="tool-tip-bottom" href="#" title="Редактировать галерею">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</a>\
                      </li>\
                      <li style="list-style-type: none;" class="delete-order" id="">\
        								<a class="tool-tip-bottom delete-gallery" data-id="'+response.data[i].id+'" href="#">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</a>\
        							</li></td>';
        		table += '</tr>';
        	}
        	$('.gallery-tbody').html(table);
        }
      );   
    },

    loadGalleryInfo: function(id) {
    	gallery.selectedGallery = id;
      admin.ajaxRequest({
        	pluginHandler: "mg-gallery", // имя папки в которой лежит данный плагин
          actionerClass: "Pactioner", 
          action: "loadGalleryInfo", // название действия в пользовательском классе  
          id: id,     
        },
        function(response) {
        	$('#gal-name').val(response.data.gal_name);
        	$('#gal-height').val(response.data.height);
        	$('#gal-in-line').val(response.data.in_line);
        	// получение списка изображений галереи
        	$('#mg-gallery').html('');
        	gallery.getImgList();
        	// Вызов модального окна.
      		admin.openModal($('.b-modal'));
        }
      );   
    },

    saveGallery: function(galName, height, inLine) {
    	var data = {
    		'gal_name': galName,
    		'height': height,
    		'in_line': inLine
    	}
      admin.ajaxRequest({
        	pluginHandler: "mg-gallery", // имя папки в которой лежит данный плагин
          actionerClass: "Pactioner", 
          action: "saveGallery", // название действия в пользовательском классе  
          id: gallery.selectedGallery,   
          data: data,
        },
        function(response) {
        	admin.indication(response.status, response.msg);
        	gallery.loadGalleries();
        }
      );   
    },

    saveImg: function(title, alt) {
    	var data = {
    		'title': title,
    		'alt': alt
    	}
      admin.ajaxRequest({
        	pluginHandler: "mg-gallery", // имя папки в которой лежит данный плагин
          actionerClass: "Pactioner", 
          action: "saveImg", // название действия в пользовательском классе  
          id: gallery.selectedImg,   
          data: data,
        },
        function(response) {
        	admin.indication(response.status, response.msg);
        	gallery.getImgList();
        }
      );   
    },

    /**
    * функция для приема файла из аплоадера
    */         
    getFile: function(file) {      
      console.log(file.url);
      gallery.addNewImg(file.url);
    },

    addNewImg: function(url) {
      admin.ajaxRequest({
        	pluginHandler: "mg-gallery", // имя папки в которой лежит данный плагин
          actionerClass: "Pactioner", 
          action: "addNewImg", // название действия в пользовательском классе  
          id: gallery.selectedGallery,   
          url: url,
        },
        function(response) {
        	gallery.getImgList();
        }
      );   
    },

    getImgList: function() {
      admin.ajaxRequest({
        	pluginHandler: "mg-gallery", // имя папки в которой лежит данный плагин
          actionerClass: "Pactioner", 
          action: "getImgList", // название действия в пользовательском классе  
          id: gallery.selectedGallery
        },
        function(response) {
        	if(response.data != null) {
          	var imgList = '<ul class="mg-gallery-list">';

          	for(i = 0; i < response.data.length; i++) {
          		imgList += '<li style="width:17.5%;height:150px;" class="edit-img" data-id="'+response.data[i].id+'" data-title="'+response.data[i].title+'" data-alt="'+response.data[i].alt+'">\
            				<img src="'+response.data[i].image_url+'"/>\
            				<div class="remove-img" data-id="'+response.data[i].id+'"></div>\
        				</li>';
        		}

          	imgList += '</ul>';
          	
          	$('#mg-gallery').html(imgList);		
        	}
        }
      );   
    },

    removeImg: function(id) {
    	admin.ajaxRequest({
        	pluginHandler: "mg-gallery", // имя папки в которой лежит данный плагин
          actionerClass: "Pactioner", 
          action: "removeImg", // название действия в пользовательском классе  
          id: id,
        },
        function(response) {
        	gallery.getImgList();
        }
      );   
    },

    deleteGallery: function(id) {
    	admin.ajaxRequest({
        	pluginHandler: "mg-gallery", // имя папки в которой лежит данный плагин
          actionerClass: "Pactioner", 
          action: "deleteGallery", // название действия в пользовательском классе  
          id: id,
        },
        function(response) {
        	gallery.loadGalleries();
        }
      );   
    }                         	

  }
})();

// инициализациямодуля при подключении
gallery.init();