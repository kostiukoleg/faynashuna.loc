<!--
Доступны переменные:
  $pluginName - название плагина
  $lang - массив фраз для выбранной локали движка
  $options - набор данного плагина хранимый в записи таблиц mg_setting
 
-->

<div class="section-<?php echo $pluginName ?>"><!-- $pluginName - задает название секции для разграничения JS скрипта -->


  <!-- Тут начинается верстка видимой части станицы настроек плагина-->
  <div class="widget-table-body">
    
     <div class="widget-table-action">    
        <a href="javascript:void(0);" class="show-property-order tool-tip-top" title="<?php echo $lang['T_TIP_SHOW_PROPERTY'];?>"><span><?php echo $lang['SHOW_PROPERTY'];?></span></a>
        <a href="javascript:void(0);" class="start-processs custom-btn"><span>Начать процесс генерации миниатюр</span></a>       
        <br>
        <a href="javascript:void(0);" class="processs-get-photo custom-btn"><span>Собрать все 100%-ные изображения в папку /uploads/original</span></a>
              
        <div class="clear"></div>
      </div>
           
      <div class="property-order-container">    
        <h2>Настройки процесса генерации изображений:</h2>       
              <ul class="list-option">
                  <li><label><span>Ширина 30_:</span> <input type="text" name="width30" value="<?php echo ($options["width30"])?$options["width30"]:'50'?>"></label></li>
                  <li><label><span>Высота 30_:</span> <input type="text" name="height30" value="<?php echo ($options["height30"])?$options["height30"]:'50'?>"></label></li>
                  <li><label><span>Ширина 70_ :</span> <input type="text" name="width70" value="<?php echo ($options["width70"])?$options["width70"]:'300'?>"></label></li>
                  <li><label><span>Высота 70_:</span> <input type="text" name="height70" value="<?php echo ($options["height70"])?$options["height70"]:'225'?>"></label></li>
                  <li><label><span>Папка с оригинальными изображенияеми:</span> <input type="text" name="source" value="<?php echo ($options["source"])?$options["source"]:'/uploads/tempimage'?>"></label></li>
                  <li><label><span>Наложить водяной знак:</span> <input type="checkbox" name="watter" value="<?php echo $options["watter"]?>" <?php echo ($options["watter"]&&$options["watter"]!='false')?'checked=cheked':''?>></label></li>
                  <li><label><span>Использовать новую структуру файлов:</span> 
                      <input type="checkbox" name="new_file_structure" 
                             value="<?php echo $options["new_file_structure"]?>" <?php echo ($options["new_file_structure"]&&$options["new_file_structure"]!='false')?'checked=cheked':''?>
                             title="Переход на новую структуру хранения изображений с версии системы 5.7.0" />
                    </label></li>
              </ul>
              <div class="clear"></div>          
          <div class="clear"></div>
        <a href="javascript:void(0);" class="base-setting-save custom-btn"><span>Сохранить</span></a>
        <div class="clear"></div>
      </div>   
     
      <div class="generation" style="display:none;"></div>
      <div class="progress" style="display:none;">Выполнено 0%</div>
      <div class="loger">
      <textarea class="log widget-table-action" style="width:98%; height:300px;"></textarea>
      </div>
      <div class="clear"></div>
      <!-- Тут начинается верстка таблицы сущностей  -->
    
  
  </div>
  
