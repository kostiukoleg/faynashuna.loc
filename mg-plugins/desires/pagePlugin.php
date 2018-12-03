<!--Данная верска выводится на странице плагина в админской части сайта-->
<style>
  		.desires-icon{background:url(../mg-plugins/desires/images/present.png) !important;display:block;}
</style>
<script type="text/javascript">
includeJS('../mg-plugins/desires/js/desires.js');
</script>

<style>
.section-desires .widget-table .actions{width: auto;}
.section-desires .list-option span{display: block;}
.section-desires .list-option li, .section-promo-code .fields-calback li{margin: 0 0 10px 0;}
.section-desires .textarea-text{position: relative;top: -100px;}
.section-desires .property-order-container h2{margin: 0 0 5px 0;}
.section-desires .fields-calback span{display: block;}
.section-desires .fields-calback li input[type="text"],
.section-desires .fields-calback li textarea{width: 250px;}
.section-desires .fields-calback li textarea{height: 150px;}
.section-desires .fields-calback li .textarea-text{top: -145px;}
.section-desires .list-option li textarea{width: 350px;height: 100px;}

.section-desires .spoiler {
	margin: 10px 0px;

}

.section-desires .spoiler .title {
	color: #fff;
	background: #444;
	margin: 0;
	padding-top: .5em;
	padding-bottom: .3em;
	padding-left: .3em;
}
.section-desires .spoiler .content {
	display: none;
}
.section-desires .spoiler .content ul {
	list-style: none;
}
</style>

 <div class="section-desires">
    <!-- Тут начинается Верстка модального окна -->
<div class="reveal-overlay" style="display:none;">
  <div class="reveal xssmall desire-show" style="display:block;">
    <button class="close-button closeModal" type="button"><i class="fa fa-times-circle-o" aria-hidden="true"></i></button>
    <div class="reveal-header">
      <h2><i class="fa fa-plus-circle" aria-hidden="true"></i> <span id="modalTitle">Желание пользователя</span></h2>
    </div>
    <div class="reveal-body">
        <ul class="custom-form-wrapper list-option fields-calback">
                    
					<li>
						<span class="add-text" style="margin-bottom: 10px;">Процент скидки:</span>
						<input name="discount" type="text" value="0">
					</li>
					
					<li>
						<span class="add-text" style="margin-bottom: 10px;">Цена:</span>
						<input name="price" data-price="" type="text" value="0" disabled>
					</li>
					
					<li>
						<span class="add-text" style="margin-bottom: 10px;">Срок действия скидки (-1 - будет взят из настроек):</span>
						<input name="discountTimer" type="text" value="-1">
					</li>
					
					</ul>
    </div>
    <div class="reveal-footer clearfix text-right">
        <button class="save-button button success" data-id=""><span>Сделать скидку</span></button>
		<button class="button primary" data-id=""><span>Отклонить</span></button>
    </div>
  </div>
</div>

<div class="reveal-overlay" style="display:none;">
  <div class="reveal xssmall modal-settings" style="display:block;">
    <button class="close-button closeModal" type="button"><i class="fa fa-times-circle-o" aria-hidden="true"></i></button>
    <div class="reveal-header">
      <h2><i class="fa fa-plus-circle" aria-hidden="true"></i> <span id="modalTitle">Настройки</span></h2>
    </div>
    <div class="reveal-body">
        <div class="controlBlock">
                  <div class="product-desc-wrapper">
				  	<div class="spoiler">
					<div class="title"><div class="group-property"><h3>Основные настройки</h3></div></div>
					<div class="content">
				  <ul class="custom-form-wrapper list-option fields-calback">
				  <li>
                    <label><span class="add-text" style="margin-bottom: 10px;">Использовать ссылки:</span>
                    <select name="useLinks">
                    	<option value="1">Длинные</option>
                    	<option value="2">Короткие</option>
                    </select></label>
					</li>
				  <li>
                    <label><span class="add-text" style="margin-bottom: 10px;">Уведомлять на почту о новом желании (можно несколько адресов через запятую):</span>
                    <input name="sendEmail" type="text" value=""></label>
					</li>
				  <li>
                    <label><span class="add-text" style="margin-bottom: 10px;"><input name="defaultUse" type="checkbox"> Использовать скидку по умолчанию</span>
                    </label>
					</li>
				  <li>
                    <label><span class="add-text" style="margin-bottom: 10px;">Процент скидки по умолчанию</span>
                    <input name="defaultDiscount" type="text" value=""></label>
					</li>
				  <li>
                    <label><span class="add-text" style="margin-bottom: 10px;">Применять скидку по умолчанию через (дней)</span>
                    <input name="defaultPeriod" type="text" value=""></label>
					</li>
				  <li>
                    <label><span class="add-text" style="margin-bottom: 10px;"><input name="roundResult" type="checkbox"> Окгруглять цену после скидки</span>
                    </label>
					</li>

				  <li>
                    <label><span class="add-text" style="margin-bottom: 10px;">Название кнопки "В мои желания":</span>
                    <input name="buttonTitle" type="text" value=""></label>
					</li>
				
				  <li>
                    <label><span class="add-text" style="margin-bottom: 10px;">Количество желаний на странице в личном кабинене:</span>
                    <input name="desiresLcPerPage" type="text" value=""></label>
					</li>

				  <li>
                    <label><span class="add-text" style="margin-bottom: 10px;">Время действия скидки на товар в днях (0 - не ограничено):</span>
                    <input name="timerValue" type="text" value=""></label>
					</li>
					
				  <li>
                    <label><span class="add-text" style="margin-bottom: 10px;">Включить таймер для товаров в личном кабинете: </span>
						<select name="enableLcTimer">
							<option value="1">Да</option>
							<option value="0">Нет</option>
						</select>
					</label>
					</li>
					
				  <li>
                    <label><span class="add-text" style="margin-bottom: 10px;">Выводить счётчик добавления товара в мои желания:</span>
                    <select name="enableCounter">
						<option value="1">Да</option>
						<option value="0">Нет</option>
					</select>
					</label>
					</li>
					
					
				  <li>
					<label>
						<span class="add-text" style="margin-bottom: 10px;">Разрешить добавлять товар в список желаний больше одного раза:</span>
						<select name="enableManyDesires">
							<option value="1">Да</option>
							<option value="0">Нет</option>
						</select>
					</label>
				</li>
				
									<li>
                     <label><span class="add-text" style="margin-bottom: 10px;">Текст письма к пользователю (можно использовать HTML):<br></span></label>
					 <div class="spoiler">
					 <div class="title"><h4>Параметры для текста письма</h4></div>
					 <div class="content">
	{USER_NAME} - Имя пользователя<br>
	{USER_SURNAME} - Фамилия пользователя<br>
	{DISCOUNT_PRODUCT_URL} - URL товара, для которого делается скидка<br>
	{DISCOUNT_PRODUCT_TITLE} - Название товара, для которого делается скидка<br>
	{DISCOUNT_PERCENT} - Процент скидки<br>
	{DISCOUNT_ACTIVATE_URL} - URL для активации скидки<br>
	{PRODUCT_SMALL_IMAGE_URL} - URL маленького изображения товара<br>
	{PRODUCT_BIG_IMAGE_URL} - URL большого изображения товара<br>
	{SHOP_LOGO} - URL лого магазина</br>
	{DISCOUNT_MAX_DATE} - срок действия ссылки
	</div>
					 </div>
                    <textarea name="emailText" style="width: 85%; height: 300px;"></textarea>
					</li>
					
					<li>
                    <label> <span class="add-text" style="margin-bottom: 10px;">Шаблон письма (можно создавать свои шаблоны в папке tpl плагина):</span></label>
                    <select name="emailTemplate">
					<?php
					$files = scandir(dirname(__FILE__).'/tpl/');
					unset($files[0], $files[1]);
					
					foreach ($files as $file) {
						echo '<option value="'.$file.'">'.$file.'</option>';
					}
					?>
					</select>
					</li>
					
			</ul>
			</div>
			</div>
				

					 <div class="spoiler">
					 <div class="title"><div class="group-property"><h3>Настройка вывода шорткодов</h3></div></div>
					 <div class="content">
					 
                    <div>
					Для добавления виджета (кнопки) для страницы catalog.php и index.php используйте шорт-код:<br>
					<div style="background:#eee;border:1px solid #ccc;padding:5px 10px;"><code>[</code>addtowishlist product=<code><</code>?php echo $item['id']; ?>]
					</div><br>
					для страницы product.php:<br>
					<div style="background:#eee;border:1px solid #ccc;padding:5px 10px;"><code>[</code>addtowishlist product=<code><</code>?php echo $data['id']; ?>]
					</div><br>
					Для добавления вкладки "Мои желания" на странице views/personal.php Вашего шаблона после строки:<br>           
                   <div style="background:#eee;border:1px solid #ccc;padding:5px 10px;"><code><</code>li><code><</code>a href="#orders-history">История заказов<code><</code>/a><code><</code>/li>
				   </div><br>
                   добавить:<br>
				   <div style="background:#eee;border:1px solid #ccc;padding:5px 10px;"> <code><</code>li><code><</code>a href="#my-desires">Мои желания<code><</code>/a><code><</code>/li>
				   </div><br>
				   и после строки:<br>
				   <div style="background:#eee;border:1px solid #ccc;padding:5px 10px;"><code><</code>br><</code>span>У вас нет заказов<code><</code>/span><br>
                       <code><</code>?php endif ?> <code><</code>!-- if($data['orderInfo']) --><br>
                   <code><</code>/div>
				   </div><br>
				   Вставить шорт-код:<br>
				   <div style="background:#eee;border:1px solid #ccc;padding:5px 10px;"><code>[</code>wish-list]
				   </div>
			        </div>		
					
					</div></div>

					
					 <div class="spoiler">
					 <div class="title"><div class="group-property"><h3>Шаблон письма</h3></div></div>
					 <div class="content">
					<iframe src="<?php echo SITE.'/desiretemplate'; ?>" style="width: 100%; height: 400px;" align="left"></iframe>
					
					</div>
					</div>
					</div>
					</div>
    </div>
    <div class="reveal-footer clearfix text-right">
        <button class="save-button button success" data-id=""><span>Сохранить</span></button>
    </div>
  </div>
</div>
 
       
    <!-- Тут заканчивается Верстка модального окна -->


    <!-- Тут начинается  Верстка таблицы-->
    <div class="widget-table-body">

	<div class="widget-table-action">
		<a href="#" class="show-property-order show-settings button primary"><span>Настройки</span></a>
		<a href="#" class="get-csv button primary"><span>Выгрузить в CSV</span></a>
		
      	<div class="filter" style="text-align: right">
          <span class="last-items">Выводить желаний</span>
          <select class="last-items-dropdown countPrintRowsPage" style="max-width: 70px">
            <?php
            foreach(array(5, 10, 15, 20, 25, 30, 100, 150) as $value){
              $selected = '';
              if($value == $countPrintRowsDesires){
                $selected = 'selected="selected"';
              }
              echo '<option value="'.$value.'" '.$selected.' >'.$value.'</option>';
            }
            ?>
          </select>
        </div>
        <div class="clear"></div>
	</div>
	
      <div class="main-settings-container">
        <table class="widget-table product-table main-table">
          <thead>
            <tr>
              <th class="checkbox-cell" style="width: 30px"><input type="checkbox" class="desire-allcheck"></th>
              <th class="add_date">Дата</th>
              <th class="user_id">Покупатель</th>
			  <th class="user_id">Группа</th>
			  <th class="user_register_date">Дата регистрации</th>
              <th class="text">Продукт</th>
              <th class="status">Статус</th>
              <th class="actions text-right">Действия</th>
            </tr>
          </thead>
          <tbody class="desires-tbody">

          <?php
          if(!empty($desires)){
          foreach($desires as $data){ ?>
              <tr data-id="<?php echo $data['id'] ?>">

                <td class="check-align"><input type="checkbox" data-id="<?php echo $data['id']; ?>" name="desire-check" class="desire-check" value=""></td>
                <td class="add_date"><?php echo date('d.m.y H:i', strtotime($data['add_date'])).' '; ?></td>
                <td class="user_id"><?php echo $data['name']." ".$data['sname'].' ('.$data['email'].')'?></td>
				<td class="user_group"><?php echo MyDesiresPlugin::$listRoles[$data['user_role']]; ?></td>
				<td class="user_register_date"><?php echo date('d.m.Y', strtotime($data['user_register_date'])); ?></td>
                <td class="product">
                <?php if($config['useLinks'] == 1) { ?>
                <a href="<?php echo SITE.'/'.$data["parent_url"].$data["url"].'/'.$data['product_url']; ?>"><?php echo $data['title'] ?></a>
                <?php }
                else
                { ?>
                <a href="<?php echo SITE.'/'.$data['product_url']; ?>"><?php echo $data['title'] ?></a>
                <?php } ?>
                </td>
                <td class="closed"><?php echo MyDesiresPlugin::getStatus($data['status'], $data['discount_percent']); ?></td>
                <td class="actions text-right">
                  <ul class="action-list">
					<li class="edit-row tool-tip-bottom fa fa-pencil <?php echo ($data['status'] == 1) ? 'active' : ''; ?>" title="Просмотр и подтверждение желания" data-id="<?php echo $data['id'] ?>"><a  href="javascript:void(0);"></a></li>
					<!--li class="edit-row" data-id="<?php echo $data['id'] ?>"><a class="tool-tip-bottom" href="#" title="Просмотр"></a></li-->
					<li class="delete-row fa fa-trash" data-id="<?php echo $data['id'] ?>"><a class="tool-tip-bottom" href="javascript:void(0);" title="Отправить промокод"></a></li>
                  </ul>
                </td>

              </tr>
           <?php }
          }else{
          ?>
           <tr class="noneRows"><td colspan="8" style="padding:10px 0;">Нет желаний</td></tr>
          <?php }?>

          </tbody>
        </table>
      </div>

		  <br><select name="operation" class="desire-operation" style="max-width: 200px">       
			<option value="delete">Удалить</option> 
		  </select>
		<a href="javascript:void(0);" class="desire-run-operation button primary"><span>Выполнить</span></a>
	  
      <?php echo $pagination ?>
      <div class="clear"></div>
   </div>
 </div>
