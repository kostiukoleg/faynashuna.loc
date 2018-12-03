<!--
Доступны переменные:
  $pluginName - название плагина
  $lang - массив фраз для выбранной локали движка
  $options - набор данного плагина хранимый в записи таблиц mg_setting
  $entity - набор записей сущностей плагина из его таблицы
  $pagination - блок навигациицам 
-->
<script>
$(document).ready(function(){
  var services = new Array(<?php echo '"'.implode('","', explode(',', $options['services'])).'"';?>);
  for(var i=0; i<services.length; i++){
    console.log(services[i]);
    $('.section-<?php echo $pluginName?> .list-option select[name="services"] option[value="'+services[i]+'"]').attr("selected","selected");
  }
  $('.section-<?php echo $pluginName?> .list-option .pluso input[value="<?php echo $options['theme']?>"]').attr("checked", "checked");
});
</script>
<div class="section-<?php echo $pluginName?>"><!-- $pluginName - задает название секции для разграничения JS скрипта -->

  <!-- Тут начинается верстка видимой части станицы настроек плагина-->
  <div class="widget-table-body">
    <div class="wrapper-entity-setting">

      <!-- Тут начинается  Верстка базовых настроек  плагина (опций из таблицы  setting)-->
      <div class="widget-table-action base-settings">
        <h3>Настройки плагина</h3>
       
        <ul class="list-option"><!-- список опций из таблицы setting-->
          <li><label>
            <span><?php echo $lang['SELECT_THEME']?>:</span>
            <div style="display:inline-block">
              <div class="pluso pluso-skip horizontal">
                <div class="theme">
                  <div class="horizontal line big square t<?php echo $options['theme']?> exampleTheme">
                    <input type="hidden" name="curTheme" value="t<?php echo $options['theme']?>">
                    <label class="pluso-wrap">
                      <a class="pluso-vkontakte"></a>
                      <a class="pluso-odnoklassniki"></a>
                      <a class="pluso-facebook"></a>
                      <a class="pluso-twitter"></a>
                      <a class="pluso-google"></a>
                      <a class="pluso-moimir"></a>
                      <a class="pluso-more"></a>
                    </label>
                  </div>
                </div>
              </div>
              <div class="clear"></div>
              <div class="pluso pluso-skip horizontal pluso-select">
              <div class="theme">
                <div class="horizontal line big square t01">
                  <label class="pluso-wrap">
                    <input checked="checked" name="theme" value="01" class="t01" type="radio">
                    <a class="pluso-vkontakte"></a>
                    <a class="pluso-odnoklassniki"></a>
                    <a class="pluso-facebook"></a>
                    <a class="pluso-twitter"></a>
                    <a class="pluso-google"></a>
                    <a class="pluso-moimir"></a>
                    <a class="pluso-more"></a>
                  </label>
                </div>
              </div>
              <div class="theme">
                <div class="horizontal line big square t02">
                  <label class="pluso-wrap">
                    <input name="theme" value="02" class="t02" type="radio">
                    <a class="pluso-vkontakte"></a>
                    <a class="pluso-odnoklassniki"></a>
                    <a class="pluso-facebook"></a>
                    <a class="pluso-twitter"></a>
                    <a class="pluso-google"></a>
                    <a class="pluso-moimir"></a>
                    <a class="pluso-more"></a>
                  </label>
                </div>
              </div>
              <div class="theme">
                <div class="horizontal line big square t03">
                  <label class="pluso-wrap">
                    <input name="theme" value="03" class="t03" type="radio">
                    <a class="pluso-vkontakte"></a>
                    <a class="pluso-odnoklassniki"></a>
                    <a class="pluso-facebook"></a>
                    <a class="pluso-twitter"></a>
                    <a class="pluso-google"></a>
                    <a class="pluso-moimir"></a>
                    <a class="pluso-more"></a>
                  </label>
                </div>
              </div>
              <div class="theme">
                <div class="horizontal line big square t04">
                  <label class="pluso-wrap">
                    <input name="theme" class="t04" value="04" type="radio">
                    <a class="pluso-vkontakte"></a>
                    <a class="pluso-odnoklassniki"></a>
                    <a class="pluso-facebook"></a>
                    <a class="pluso-twitter"></a>
                    <a class="pluso-google"></a>
                    <a class="pluso-moimir"></a>
                    <a class="pluso-more"></a>
                  </label>
                </div>
              </div>
              <div class="theme">
                <div class="horizontal line big square t05">
                  <label class="pluso-wrap">
                    <input name="theme" value="05" class="t05" type="radio">
                    <a class="pluso-vkontakte"></a>
                    <a class="pluso-odnoklassniki"></a>
                    <a class="pluso-facebook"></a>
                    <a class="pluso-twitter"></a>
                    <a class="pluso-google"></a>
                    <a class="pluso-moimir"></a>
                    <a class="pluso-more"></a>
                  </label>
                </div>
              </div>
              <div class="theme">
                <div class="horizontal line big square t06">
                  <label class="pluso-wrap">
                    <input name="theme" value="06" class="t06" type="radio">
                    <a class="pluso-vkontakte"></a>
                    <a class="pluso-odnoklassniki"></a>
                    <a class="pluso-facebook"></a>
                    <a class="pluso-twitter"></a>
                    <a class="pluso-google"></a>
                    <a class="pluso-moimir"></a>
                    <a class="pluso-more"></a>
                  </label>
                </div>
              </div>
              <div class="theme">
                <div class="horizontal line big square t07">
                  <label class="pluso-wrap">
                    <input name="theme" value="07" class="t07" type="radio">
                    <a class="pluso-vkontakte"></a>
                    <a class="pluso-odnoklassniki"></a>
                    <a class="pluso-facebook"></a>
                    <a class="pluso-twitter"></a>
                    <a class="pluso-google"></a>
                    <a class="pluso-moimir"></a>
                    <a class="pluso-more"></a>
                  </label>
                </div>
              </div>
              <div class="theme">
                <div class="horizontal line big square t08">
                  <label class="pluso-wrap">
                    <input name="theme" value="08" class="t08" type="radio">
                    <a class="pluso-vkontakte"></a>
                    <a class="pluso-odnoklassniki"></a>
                    <a class="pluso-facebook"></a>
                    <a class="pluso-twitter"></a>
                    <a class="pluso-google"></a>
                    <a class="pluso-moimir"></a>
                    <a class="pluso-more"></a>
                  </label>
                </div>
              </div>
              <div class="theme">
                <div class="horizontal line big square t14">
                  <label class="pluso-wrap">
                    <input name="theme" value="14" class="t14" type="radio">
                    <span class="vkontakte"><a class="pluso-vkontakte"></a><b title="1385">1385</b></span>
                    <span class="odnoklassniki"><a class="pluso-odnoklassniki"></a><b title="749">749</b></span>
                    <span class="facebook"><a class="pluso-facebook"></a><b title="851">851</b></span>
                    <a class="pluso-more"></a>
                  </label>
                </div>
              </div>
            </div>
            </div>
            <a href="javascript:void(0);" class="showTheme" data-alt-text="<?php echo $lang['HIDE_THEME_LIST']?>"><?php echo $lang['CHANGE_THEME']?></a>
            <div style="clear:both;"></div>
          </label></li>
          <li><label>
            <span><?php echo $lang['USE_SERVICES']?>:</span> 
            <select name="services" multiple="multiple" size="5">
              <option value="vkontakte">ВКонтакте</option>
              <option value="odnoklassniki">Одноклассники</option>
              <option value="facebook">Facebook</option>
              <option value="twitter">Twitter</option>
              <option value="google">Google+</option>
              <option value="vkrugu">В Кругу Друзей</option>
              <option value="moikrug">МойКруг</option>
              <option value="moimir">Мой Мир@Mail.Ru</option>
              <option value="bookmark">В закладки</option>
              <option value="email">Отправить на email</option>
              <option value="print">Печатать</option>
              <option value="blogger">Blogger</option>
              <option value="delicious">Delicious</option>
              <option value="digg">Digg</option>
              <option value="evernote">Evernote</option>
              <option value="formspring">Formspring.me</option>
              <option value="googlebookmark">Google закладки</option>
              <option value="instapaper">Instapaper</option>
              <option value="juick">Juick</option>
              <option value="linkedin">LinkedIn</option>
              <option value="liveinternet">LiveInternet</option>
              <option value="livejournal">LiveJournal</option>
              <option value="memori">Memori.ru</option>
              <option value="pinme">Pinme</option>
              <option value="pinterest">Pinterest</option>
              <option value="readability">Readability</option>
              <option value="springpad">Springpad</option>
              <option value="stumbleupon">StumbleUpon</option>
              <option value="surfingbird">Surfingbird</option>
              <option value="tumblr">Tumblr</option>
              <option value="webdiscover">WebDiscover</option>
              <option value="yahoo">Yahoo закладки</option>
              <option value="myspace">mySpace</option>
              <option value="bobrdobr">БобрДобр</option>
              <option value="moemesto">МоёМесто</option>
              <option value="yandex">Я.ру</option>
              <option value="yazakladki">Яндекс.Закладки</option>
              <option value="webmoney">Webmoney события</option>
              <option value="misterwong">Мистер Вонг</option>
              <option value="friendfeed">Friend Feed</option>
            </select>
            <a href="javascript:void(0);" class="unwrap" data-alt-text="<?php echo $lang['HIDE_THEME_LIST']?>"><?php echo $lang['SERVICES_LIST_UNWRAP']?></a>
            <div style="clear:both;"></div>
          </label></li>
          <li><label>
            <span><?php echo $lang['BUTTON_SIZE']?>:</span> 
            <select name="size">
              <option value="big" <?php echo ($options['size']=="big")?'selected="selected"':''?>><?php echo $lang['BUTTON_SIZE_BIG']?></option>
              <option value="medium" <?php echo ($options['size']=="medium")?'selected="selected"':''?>><?php echo $lang['BUTTON_SIZE_MEDIUM']?></option>
              <option value="small" <?php echo ($options['size']=="small")?'selected="selected"':''?>><?php echo $lang['BUTTON_SIZE_SMALL']?></option>
            </select>
          </label></li>
          <li><label>
            <span><?php echo $lang['BUTTON_SHAPE']?>:</span> 
            <select name="shape">
              <option value="square" <?php echo ($options['shape']=="square")?'selected="selected"':''?>><?php echo $lang['BUTTON_SHAPE_SQUARE']?></option>
              <option value="round" <?php echo ($options['shape']=="round")?'selected="selected"':''?>><?php echo $lang['BUTTON_SHAPE_ROUND']?></option>
            </select>
          </label></li>
          <li><label>
            <span><?php echo $lang['BUTTONS_ORIENTATION']?>:</span> 
            <select name="orientation">
              <option value="horizontal" <?php echo ($options['orientation']=="horizontal")?'selected="selected"':''?>><?php echo $lang['BUTTONS_ORIENTATION_HORIZONTAL']?></option>
              <option value="vertical" <?php echo ($options['orientation']=="vertical")?'selected="selected"':''?>><?php echo $lang['BUTTONS_ORIENTATION_VERTICAL']?></option>
            </select>
          </label></li>
          <li><label>
              <span><?php echo $lang['BUTTONS_MULTILINE'];?>:</span>
              <input type="checkbox" name="multiline" value="<?php echo $options["multiline"]?>" <?php echo ($options["multiline"]!='false')?'checked=cheked':''?>>
          </label></li>
          <li><label>
              <span><?php echo $lang['BUTTONS_COUNTER'];?>:</span>
              <input type="checkbox" name="counter" value="<?php echo $options["counter"]?>" <?php echo ($options["counter"]!='false')?'checked=cheked':''?>>
          </label></li>
          <li><label>
              <span><?php echo $lang['BUTTONS_USE_BACKGROUND'];?>:</span>
              <input type="checkbox" name="use-background" value="<?php echo $options["use-background"]?>" <?php echo ($options["use-background"]!='false')?'checked=cheked':''?>>
          </label></li>
          <li><label>
              <span><?php echo $lang['BUTTONS_BACKGROUND'];?>:</span>
              <input type="text" name="background" value="<?php echo $options["background"]?>" <?php echo ($options["use-background"]!='false')?'':'disabled="disabled"'?>>
          </label></li>
        </ul>

        <button class="tool-tip-bottom base-setting-save save-button custom-btn" data-id="" title="<?php echo $lang['SAVE_MODAL']?>">
          <span><?php echo $lang['SAVE_MODAL']?></span> <!-- кнопка применения настроек -->
        </button>
        <div class="clear"></div>
      </div>
      <div class="clear"></div>
    </div>
  </div>