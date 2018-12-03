<div class="section-<?php echo $pluginName ?>"><!-- $pluginName - задает название секции для разграничения JS скрипта -->
    <!-- Тут начинается Верстка модального окна -->
    <div class="b-modal hidden-form" id="add-tag-wrapper">
	    <div class="tag-table-wrapper">
        <div class="widget-table-title">
          <h4 class="add-tag-table-icon"></h4>
          <div class="b-modal_close tool-tip-bottom" title="Закрыть окно"></div>
        </div>
        <div class="widget-table-body" style="background: rgb(248, 248, 248);">
          <div class="add-tag-form-wrapper">
            <div class="add-tag-form">
              <label>
                <span class="errorField" name="blocked_reason" style="display: none;">Поле должно быть заполнено !</span>
                <span class="custom-text">Причина блокировки входа:</span>
                <input type="text" name="blocked_reason" class="tag-name-input tool-tip-right" title="Введите причину блокировки" value="">
              </label>
              <label>
                <!-- Кнопка блокировки -->
                <a href="javascript:void(0);" class="custom-btn save-button tool-tip-top" style="margin: 0px 0px 0px 10px;" title="Заблокировать вход данному пользователю">
                  <span>Заблокировать</span>
                </a>
              </label>
            </div>
          </div>
        </div>
      </div>
    </div>  

  <!-- Табы настроек -->
  <div id="settings-tabs">
    <!-- Заголовки -->
    <ul class="tabs-list">
            <li class="ui-state-active">
              <a href="javascript:void(0);" class="tool-tip-top" id="sign-services-main" title="Главная страница"><span>Главная</span></a>
            </li>
            <li>
              <a href="javascript:void(0);" class="tool-tip-top" id="sign-services-block" title="Настройки для сервисов авторизации"><span>Сервисы</span></a>
            </li>
            <!--
            <li>
              <a href="javascript:void(0);" class="tool-tip-top" id="sign-services-style-editor" title="Редактирование стиля оформления плагина"><span>Редактор стилей</span></a>
            </li> 
            -->
            <li>
              <a href="javascript:void(0);" class="tool-tip-top" id="sign-services-users" title="Список авторизовавшихся пользователей"><span>Пользователи</span></a>
            </li>
            <li>
              <a href="javascript:void(0);" class="tool-tip-top" id="sign-services-statistic" title="Статистика авторизаций"><span>Статистика</span></a>
            </li>          
            <li>
              <a href="javascript:void(0);" class="tool-tip-top" id="sign-services-statistic-faq" title="Ваш помощник по плагину"><span>Помощник</span></a>
            </li>           
    </ul>
  </div>
  <!-- Блоки настроек -->
  <div class="plugin-pages">
      <!-- Блок настроек плагина + новостная лента -->
    <div class="sign-services-main" style="display: block;">
      <h4>Главная страница</h4>
      <table>
        <thead>
          <th></th>
          <th></th>
        </thead>
        <tbody>
          <td>
            <ul class="sign-service-main-setting-list">
              <li id="read">
                <span>Подтверждение email:</span>
                <?php
 echo MG::getOption('checkSocialEmail') == 'true' ? '<input type="checkbox" value="true" checked="checked" id="checkSocialEmail">' : '<input type="checkbox" value="false" id="checkSocialEmail">'; ?>
                <a href="javascript:void(0);" class="tool-tip-right desc-property" title="Включить подтверждение email при авторизации">?</a>
              </li>
              <li id="read">
                <span>Перенаправить пользователя:</span>
                  <select id="socialLoginRedirect">
                    <?php
 $rd = array ( array('name' => 'personal', 'text' => 'В личный кабинет'), array('name' => 'catalog', 'text' => 'В каталог'), array('name' => 'main', 'text' => 'На главную'), array('name' => 'cart', 'text' => 'В корзину'), array('name' => 'old', 'text' => 'На последнюю страницу'), ); for($i = 0, $c = count($rd), $sel = MG::getOption('socialLoginRedirect'); $i < $c; $i++) { if($rd[$i]['name'] == $sel) echo '<option name="'.$rd[$i]['name'].'" selected="selected">'.$rd[$i]['text'].'</option>'; else echo '<option name="'.$rd[$i]['name'].'">'.$rd[$i]['text'].'</option>'; } ?>
                  </select>
                <a href="javascript:void(0);" class="tool-tip-right desc-property" title="Куда перенаправить пользователя после успешной авторизации">?</a>
              </li>              
              <li id="read">
                <span>Ключ активации плагина:</span>
                <input type="text" id="SocialLoginKey" value="<?php echo MG::getOption('SocialLoginKey'); ?>">
                <a href="javascript:void(0);" class="tool-tip-right desc-property" title="Введите полученный вами ключ активации пакета для плагина">?</a>
              </li>
              <li>
                <span>Текущая версия плагина:</span>
                <input type="text" id="SocialLoginVersion" readonly="" value="<?php echo $ver; ?>">
                <a href="javascript:void(0);" class="tool-tip-right desc-property" title="Версия плагина">?</a>
              </li>
              <li>
                <span>Код промо-акции:</span>
                <input type="text" id="SocialLoginPromo" value="">
                <a href="javascript:void(0);" class="tool-tip-right desc-property" title="Введите полученный вами код промо-акции">?</a>
              </li>
              <li>
                <button type="button" class="button tool-tip-bottom" name="socialLoginUpdate" id="startUpdate" title="Обновиться до последней версии"><p>Обновиться</p></button>                
              </li>
              <li>
                <button type="button" class="button tool-tip-bottom" name="socialLoginPromo" id="startPromo" title="Активировать код промо акции"><p>Активировать промо-код</p></button>                
              </li>
              <li>
                <button type="button" class="button tool-tip-bottom" name="socialLoginMessager" id="startMessage" title="Отправить сообщение в тех поддержку"><p>Отправить</p></button>  
                <textarea id="startMessageTextArea">Если вы нашли ошибку, хотите предложить что-то новенькое, добавить новый сервис авторизации, приобрести полную версию или пакет из определенных сервисов, напишите это здесь и нажмите кнопку отправить, также в сообщении укажите свои контактные данные, чтобы мы могли связаться с вами в кротчайшие сроки!. По вопросам сотрудничества обращайтесь alexsandrshamarin@yandex.ru - Александр</textarea>          
              </li>
            </ul>
          </td>
          <td>
            <div class="news">
              <h1>Новости</h1>
              <div class="news-list">
                <?php echo MG::getOption('updateNews'); ?>
              </div>
            </div>
          </td>
        </tbody>
      </table>
    </div>
    <!-- Блок настроек для сервисов -->
    <div class="sign-services-block" style="display: none;">
      <h4>Сервисы авторизации</h4>
      <ul>
        <!-- Весь список соц сетей для авторизации -->
        <?php
 $socCon = 0; foreach($socials as $social) { if(empty($social['abbreviation']) || !$social['handler']) continue; $socCon++; if($social['active'] == 1) echo '<li class="sign-service-item active" id="'.$social['abbreviation'].'">'; else echo '<li class="sign-service-item" id="'.$social['abbreviation'].'">'; echo '<h3>'.$social['name'].'</h3>
                  <ul class="sign-service-setting-list" style="display: none">
                    <!-- Весь список настроек авторизации для соц сети -->
                    <li id="def-setting">
                      <span>Активировать авторизацию:</span>'; echo $social['active'] == 1 ? '<input type="checkbox" value="true" checked="checked" name='.$social['abbreviation'].'>' : '<input type="checkbox" value="false" name='.$social['abbreviation'].'>'; echo '</li>'; for($r = 0, $c = count($social['setting']); $r <= $c; $r++) { if(empty($social['setting'][$r]['name'])) continue; echo '<li id="soc-setting" type="'.$social['setting'][$r]['type'].'">
                      <span>'.$social['setting'][$r]['name'].'</span>'; switch ($social['setting'][$r]['type']) { case 'text': echo '<input type="text" id="'.$social['abbreviation'].'" value="'.$social['setting'][$r]['value'].'">'; break; case 'email': echo '<input type="email" id="'.$social['abbreviation'].'" value="'.$social['setting'][$r]['value'].'">'; break; case 'password': echo '<input type="password" id="'.$social['abbreviation'].'" value="'.$social['setting'][$r]['value'].'">'; break; case 'number': echo '<input type="text" id="'.$social['abbreviation'].'" value="'.$social['setting'][$r]['value'].'">'; break; case 'info': echo '<input type="text" id="'.$social['abbreviation'].'" readonly="" value="'.$social['setting'][$r]['value'].'">'; break; case 'checkbox': $t = ($social['setting'][$r]['value'] == 'true') ? 'value="true" checked="checked"' : 'value="false"'; echo '<input type="checkbox" '.$t.' id="'.$social['abbreviation'].'">'; break; case 'select': $s = explode("#", $social['setting'][$r]['value']); echo '<select id="'.$social['abbreviation'].'">'; for($e = 0, $c2 = count($s); $e <= $c2; $e++) { if(empty($s[$e])) continue; if(substr($s[$e], -1) == "^") echo '<option id="'.$e.'" value="'.substr($s[$e], 0, -1).'" selected="selected">'.substr($s[$e], 0, -1).'</option>'; else echo '<option id="'.$e.'" value="'.$s[$e].'">'.$s[$e].'</option>'; } echo '</select>'; break; case 'multiple': $s = explode("#", $social['setting'][$r]['value']); echo '<select multiple="multiple" id="'.$social['abbreviation'].'">'; for($e = 0, $c3 = count($s); $e <= $c3; $e++) { if(empty($s[$e])) continue; if(substr($s[$e], -1) == "^") echo '<option id="'.$e.'" value="'.substr($s[$e], 0, -1).'" selected="selected">'.substr($s[$e], 0, -1).'</option>'; else echo '<option id="'.$e.'" value="'.$s[$e].'">'.$s[$e].'</option>'; } echo '</select>'; break; default: echo '<span class="type-info">ERROR ! : unknow type</span>'; break; } echo '  <a href="javascript:void(0);" class="tool-tip-right desc-property" title="'.$social['setting'][$r]['title'].'">?</a>                      
                    </li>'; } echo '<li>
                    <span>Инструкция по настройке</span>
                    <span id="instruction"><h4>Развернуть</h4><div class="instruction-body" style="display: none;">'.$social['instruction'].'</div></span>                    
                  </li>
                  <li class="version">
                    <span>API версия</span>
                    <span>'.$social['version'].'</span>
                  </li>
                  <li class="author">
                    <span>Разработал</span>
                    <span>'.$social['author'].'</span>
                  </li>
                  <li>
                    <button type="button" class="button tool-tip-bottom" name="'.$social['abbreviation'].'" id="startTest" title="Протестировать данный сервис"><span>Протестировать</span></button>
                  </li>'; echo '</ul>
                </li>'; } if($socCon == 0) echo '<li><p class="u-info">Пока нет доступных обработчиков</p></li>'; ?>
      </ul>
    </div>
    <!-- Блок авторизовавшихся пользователей -->
    <div class="sign-services-users" style="display: none;">
      <table class="plugin-users-table">
        <!-- Шапка таблицы -->
        <thead>
          <tr>
            <th>Полное имя</th>
            <th>Email</th>
            <th>Сервис</th>
            <th>Комбинированный режим</th>
            <th>Блокировка</th>
            <th>Действия</th>
          </tr>
        </thead>
        <tbody class="plugin-users-tbody">
          <?php
 $users_count = 0; $soc_array[] = array(); if(count($users['social']) == 0) { echo '<tr><td class="u-info" colspan="6">Пока нет авторизовавшихся пользователей</td></tr>'; } else { foreach($users['social'] as $id => $user) { $users_count ++; $val = $soc_array[$user['service']]['count']++; $val ++; $soc_array[$user['service']] = array('service' => $user['service'], 'count' => $val); $sv = isset($socials[$user['service']]['name']) ? $socials[$user['service']]['name'] : $user['service']; $blocked = $user['blocked'] == '0' ? '0' : '1'; echo '<tr id="'.$user['id'].'">
                      <td class="name">'.$user['full_name'].'</td>
                      <td class="email">
                        <input type="text" id="'.$user['id'].'" name="email" class="settings-input option" size="29" maxlength="29" value="'.$user['email'].'"></td>
                      <td class="service">'.$sv.'</td>
                      <td class="combined" active="'.$user['combined'].'"</td>
                      <td class="blocked" active="'. $blocked .'"></td>
                      <td class="delete">
                        <a class="tool-tip-bottom" id="'.$user['id'].'" href="javascript:void(0);" title="Удалить"></a>
                      </td>
                    </tr>'; } } ?>
          <div class="clear"></div>
        </tbody>
      </table>
    </div>
    <!-- Статистика входов -->
    <div class="sign-services-statistic" style="display: none;">
      <h4>Статистика авторизаций</h4>
      <div class="plugin-users-statistic">
        <ul>
          <li>
            Всего авторизовалось: 
            <span><?php echo $users_count; ?></span>
          </li>
         
        <?php foreach($soc_array as $_id => $_service) { if (empty($_service['service'])) continue; echo '<li>
                  Авторизовалось через '.$_service['service'].': <span>'.$_service['count'].'</span>
                </li>'; }?>
        </ul>
       </div>
    </div>
    <!-- Информация для управления -->
    <div class="sign-services-statistic-faq" style="display: none;">
      <div class="info">
        <ul>
          <li>
            Термины:
            <ul>
              <li>
                * прикрепленный покупатель - покапатель, email которого совпадает с email у авторизованного пользователя.
              </li>
            </ul>
          </li>
          <li>
            Действия:
            <ul>
              <li>
                * При удалении пользователя, не забудьте удалить прикрепленного покупателя.
              </li>              
              <li>
                * Если возникли проблемы со входом (email), вы можете пересоздать аккаут прикрепленного покупателя, и изменить email авторизующегося клиента на новый.
              </li>
              <li>
                * Если авторизующийся ввел не правильно email при авторизации, вы изменяете email у прикрепленного покупателя и email авторизовавшегося, при следующем входе,
                пользователь получает уведомление на новый email.
              </li>
              <li>
                * Если вы заметили, что авторизующийся нарушил правила, вы можете блокировать ему вход, указав причину.
              </li>
              <li>
                * Если вы хотите, чтобы авторизующийся снова подтвердил логин и пароль, отключите комбинированный режим.
              </li>          
              <li>
                * Если вы хотите, чтобы авторизующийся пересоздал покапателя, удалите прикрепленного покупателя,
                удалите email у авторизующегося и отключите комбинированный режим.
              </li>
              <li>
                * Если авторизующийся забыл пароль вам необходимо изменить пароль у прикрепленного покупателя.
              </li>
            </ul>
          </li>
        </ul>
      </div>    
    </div>
  </div>
</div>