<div class="section-<?php echo $pluginName ?>"><!-- $pluginName - задает название секции для разграничения JS скрипта -->

  <!-- Тут начинается верстка видимой части станицы настроек плагина-->
  <div class="widget-table-body">
    <div class="wrapper-entity-setting">

      <!-- Тут начинается  Верстка базовых настроек  плагина (опций из таблицы  setting)-->
      <div class="widget-table-action base-settings">
        <h3>Настройки плагина</h3>

        <ul class="list-option"><!-- список опций из таблицы setting-->
          <li>
            <label>
            <span>Название кнопки:</span> 
            <input type="text" name="button" value="<?php echo $options["button"]; ?>">
            </label>
          </li>  
          <li>
          <label>
              <span>Показивать при навидении:</span>
              <input type="checkbox" name="showbyhover" value="<?php echo $options["showbyhover"]?>" <?php echo ($options["showbyhover"]!='false')?'checked=cheked':''?>>
          </label>
          </li>
        </ul>

       <button class="tool-tip-bottom base-setting-save save-button custom-btn" data-id="" title="<?php echo $lang['SAVE_MODAL']?>">
          <span><?php echo $lang['SAVE_MODAL']?></span> <!-- кнопка применения настроек -->
        </button>
        <div class="clear"></div>
      </div>
      <div class="clear"></div>
    </div>
</div>