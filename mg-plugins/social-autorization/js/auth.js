/* 
 * name : auth_m.js
 * platform: PC/Mobile
 * package : social_autorization
 * version : 1.0
 * author : Alexandr Shamarin <alexsandrshamarin@ynadex.ru>
 */
var auth_social_autorization = (function () {
	return {
		init: function() {
		
			// Поворачиваем элемент и задаем title параметр для разделителя
			auth_social_autorization.rotateElement($('.soc-login-block a[id="partly"]'), 0);
			$('.soc-login-block a[id="partly"]').attr('title', 'Показать остальные');
			
			// Скрываем блок
			$('.soc-login-block #partly a[id="auth"]').each(function(index){
				$(this).css('margin-top', - $(this).width() * 2);
			});
			
			// Обрабатывает нажатие на одну из соц сетей
			$(document).on('click', '.soc-login-block a[id="auth"]', function(){
				auth_social_autorization.authController($(this), 'location');
			});
			
			// Обрабатывает нажатие на кнопку "показать остальные"
			$(document).on('click', '.soc-login-block a[id="partly"]', function(){
				
				var anim_speed = 100;
				
				if($('.soc-login-block div#partly').hasClass("visible")) {
					$('.soc-login-block div#partly').removeClass("visible");
					auth_social_autorization.rotateElement($('.soc-login-block a[id="partly"]'), 0);
					$(this).attr('title', 'Показать остальные');
					
					anim_speed = 100;
					
					// Анимируе скрытие блока авторизации
					$($('.soc-login-block #partly a[id="auth"]').get().reverse()).each(function(index){
						$(this).stop().animate({
							'marginTop': - $(this).width() * 2
						}, anim_speed += 200);
					});
				}
				else {
					$('.soc-login-block div#partly').addClass("visible");
					auth_social_autorization.rotateElement($('.soc-login-block a[id="partly"]'), 180);
					$(this).attr('title', 'Скрыть');
					
					anim_speed = 100;
					
					// Анимируе появление блока авторизации
					$('.soc-login-block #partly a[id="auth"]').each(function(index){
						$(this).stop().animate({
							'marginTop': 0
						}, anim_speed += 100);
					});
				}
			});			
		},
				
		/*
		 * Поворачивает элемент
		 *
		 * element - елемент страницы
		 * angle - угол поворота в градусах
		 */
		rotateElement: function(element, angle) {
			var val = 'rotate('+angle+'deg)';
			element.css({'-moz-transform': val, '-ms-transform': val, '-webkit-transform': val, '-o-transform': val, 'transform': val});
		},
		
		/*
		 * Отвечает за обработку нажатий на ярлыки сервисов авторизации
		 *
		 * label - ярлык сервиса
		 * metod - метод авторизации
		 */
		authController: function(label, metod) {
			var str = '&metod='+metod+'&location='+window.location.href;
		    location.href='/sociallogin?auth='+label.data('name')+str;
		},
			
	}
})();

$(document).ready(function() {
	auth_social_autorization.init();
});