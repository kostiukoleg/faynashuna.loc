<!--
Доступны переменные:
  $pluginName - название плагина
  $lang - массив фраз для выбранной локали движка
  $options - набор данного плагина хранимый в записи таблиц mg_setting
  $entity - набор записей сущностей плагина из его таблицы
  $pagination - блок навигациицам 
-->

<div class="section-<?php echo $pluginName ?>"><!-- $pluginName - задает название секции для разграничения JS скрипта -->

  <!-- Тут начинается Верстка модального окна -->
  <div class="b-modal hidden-form">
    <div class="custom-table-wrapper"><!-- блок для контента модального окна -->

      <div class="widget-table-title"><!-- Заголовок модального окна -->
        <h4 class="pages-table-icon" id="modalTitle">
          <?php echo $lang['HEADER_MODAL_ADD']; ?>
        </h4><!-- Иконка + Заголовок модального окна -->
        <div class="b-modal_close tool-tip-bottom" title="<?php echo $lang['CLOSE_MODAL']; ?>"></div><!-- Кнопка для закрытия окнаа -->
      </div>

      <div class="widget-table-body slide-editor"><!-- Содержимое окна, управляющие элементы -->
       
        <div class="block-for-form" >
          <ul class="custom-form-wrapper fields-calback">
            <li>
              <span>Имя</span> <input type="text" name="name" value=""/>              
            </li>
            <li>
              <span>Телефон</span> <input type="text" name="phone" value=""/>              
            </li>
            <li>
              <span>Город</span> <input type="text" name="city_id" value=""/>              
            </li>
            <li>
              <span>Цель звонка</span>   
              <select name="mission">
                <?php echo $selectMissions;?>            
              </select>              
            </li>
            <li>
              <span>Дата</span> <input type="text" name="date_callback" value=""/>              
            </li>
            <li>
              <span>Время</span>       
              C
              <select name="from">
                <?php echo $selectHour;?>            
              </select> 
              До
              <select name="to">
               <?php echo $selectHour;?>            
              </select> 
            </li>
            <li>
              <span>Статус</span>
              <select name="status_id">
                <?php foreach($status as $id => $item){
                  echo "<option value='".$id."'>".$item."</option>";
                }?>            
              </select>                      
            </li>
            <li>
              <span class="textarea-text">Комментарий </span>
              <textarea name="comment">  </textarea>
            </li>			
          </ul>        
        </div>
                
                
        <button class="save-button tool-tip-bottom" data-id="" title="<?php echo $lang['SAVE_MODAL'] ?>"><!-- Кнопка действия -->
          <span><?php echo $lang['SAVE_MODAL'] ?></span>
        </button>
        <div class="clear"></div>
      </div>
    </div>
  </div>
  <!-- Тут заканчивается Верстка модального окна -->

  <!-- Тут начинается верстка видимой части станицы настроек плагина-->
  <div class="widget-table-body">
    
     <div class="widget-table-action">
       <a href="javascript:void(0);" class="show-filters tool-tip-top" title="<?php echo $lang['T_TIP_SHOW_FILTER'];?>"><span><?php echo $lang['FILTER'];?></span></a>
        <a href="javascript:void(0);" class="show-property-order tool-tip-top" title="<?php echo $lang['T_TIP_SHOW_PROPERTY'];?>"><span><?php echo $lang['SHOW_PROPERTY'];?></span></a>
               
        <div class="filter">
          <span class="last-items"><?php echo $lang['SHOW_COUNT_ORDER'];?></span>
          <select class="last-items-dropdown countPrintRowsEntity">
            <?php
            foreach(array(10, 20, 30, 50, 100) as $value){
              $selected = '';
              if($value == $countPrintRowsBackRing){
                $selected = 'selected="selected"';
              }
              echo '<option value="'.$value.'" '.$selected.' >'.$value.'</option>';
            }
            ?>
          </select>
        </div>
        <div class="clear"></div>
      </div>
      
      <div class="filter-container" <?php if($displayFilter){echo "style='display:block'";} ?>>
        <?php echo $filter ?>
        
        <div class="clear"></div>
      </div>
      
      <div class="property-order-container">    
        <h2>Поля в форме звонка:</h2>
          <form  class="base-setting" name="base-setting" method="POST">       
              <ul class="list-option">
                  <li><label><span>Имя:</span> <input type="checkbox" name="name" value="<?php echo $options["name"]?>" <?php echo ($options["name"]&&$options["name"]!='false')?'checked=cheked':''?>></label></li>
                  <li><label><span>Город:</span> <input type="checkbox" name="city" value="<?php echo $options["city"]?>" <?php echo ($options["city"]&&$options["city"]!='false')?'checked=cheked':''?>></label></li>
                  <li><label><span>Комментарий:</span> <input type="checkbox" name="comment" value="<?php echo $options["comment"]?>" <?php echo ($options["comment"]&&$options["comment"]!='false')?'checked=cheked':''?>></label></li>
                  <li><label><span>Звонок:</span> <input type="checkbox" name="period" value="<?php echo $options["period"]?>" <?php echo ($options["period"]&&$options["period"]!='false')?'checked=cheked':''?>></label></li>
                  <li><label><span>Цель:</span> <input type="checkbox" name="mission" value="<?php echo $options["mission"]?>" <?php echo ($options["mission"]&&$options["mission"]!='false')?'checked=cheked':''?>></label></li>
                  <li><label><span>Капча:</span> <input type="checkbox" name="caphpa" value="<?php echo $options["caphpa"]?>" <?php echo ($options["caphpa"]&&$options["caphpa"]!='false')?'checked=cheked':''?>></label></li>
                  <li><label><span>Период времени:</span> <input type="checkbox" name="period" value="<?php echo $options["period"]?>" <?php echo ($options["period"]&&$options["period"]!='false')?'checked=cheked':''?>></label></li>
                  <li><label><span>Дата :</span> <input type="checkbox" name="date" value="<?php echo $options["date"]?>" <?php echo ($options["date"]&&$options["date"]!='false')?'checked=cheked':''?>></label></li>
                
                  <li><span>Доступный период для заказа звонка:</span> С
                    <select name="from">
                       <?php
                       $hours = array('00','01','02','03','04','05','06','07','08','09','10','11','12','13','14','15','16','17','18','19','20','21','22','23','24');
                        foreach ($hours as $value) {
                          $selected = '';
                          if($value == $options["from"]){
                            $selected = 'selected="selected"';
                          }
                          echo '<option value="'.$value.'" '.$selected.' >'.$value.'</option>';
                        }
                        ?>
                    </select>
                    По
                    <select name="to">
                       <?php
                       $hours = array('00','01','02','03','04','05','06','07','08','09','10','11','12','13','14','15','16','17','18','19','20','21','22','23','24');
                        foreach ($hours as $value) {
                          $selected = '';
                          if($value == $options["to"]){
                            $selected = 'selected="selected"';
                          }
                          echo '<option value="'.$value.'" '.$selected.' >'.$value.'</option>';
                        }
                        ?>
                    </select>
                  </li>
				
                  <li><span class="textarea-text">Список городов:</span><textarea type="text" name="city_list"><?php echo $options["city_list"]?></textarea></li>
                  <li><span class="textarea-text">Список целей:</span><textarea type="text" name="mission_list"><?php echo $options["mission_list"]?></textarea></li>
				  <li><span>E-mail для получения заявок</span> <input type="text" name="email" value="<?php echo $options["email"]?>"/>Если не указан, то будет использоваться админский e-mail</li>
              </ul>
              <div class="clear"></div>
          </form>
          <div class="clear"></div>
        <a href="javascript:void(0);" class="base-setting-save custom-btn"><span>Сохранить</span></a>
        <div class="clear"></div>
      </div>
    <div class="wrapper-entity-setting">

      
      <div class="clear"></div>
      <!-- Тут начинается верстка таблицы сущностей  -->
      <div class="entity-table-wrap">                
        <div class="clear"></div>
        <div class="entity-settings-table-wrapper">
          <table class="widget-table">          
            <thead>
            <tr>
              <th class="id-width">№</th>       
              <th>                
               <a href="javascript:void(0);" class="field-sorter <?php echo ($sorterData[0]=="add_datetime") ? 'sort-dir-'.$sorterData[3]:'sort-dir-asc' ?>" data-sort="<?php echo ($sorterData[0]=="add_datetime") ? $sorterData[1]*(-1) : 1 ?>" data-field="add_datetime">Добавлено</a>
              </th>              
              <th>
               <a href="javascript:void(0);" class="field-sorter <?php echo ($sorterData[0]=="name") ? 'sort-dir-'.$sorterData[3]:'sort-dir-asc' ?>" data-sort="<?php echo ($sorterData[0]=="name") ? $sorterData[1]*(-1) : 1 ?>" data-field="name">Имя</a>
              </th>
              <th>
               <a href="javascript:void(0);" class="field-sorter <?php echo ($sorterData[0]=="phone") ? 'sort-dir-'.$sorterData[3]:'sort-dir-asc' ?>" data-sort="<?php echo ($sorterData[0]=="phone") ? $sorterData[1]*(-1) : 1 ?>" data-field="phone">Телефон</a>
              </th>
              <th>
               <a href="javascript:void(0);" class="field-sorter <?php echo ($sorterData[0]=="city_id") ? 'sort-dir-'.$sorterData[3]:'sort-dir-asc' ?>" data-sort="<?php echo ($sorterData[0]=="city_id") ? $sorterData[1]*(-1) : 1 ?>" data-field="city_id">Город</a>
              </th>
              <th>
                 <a href="javascript:void(0);" class="field-sorter <?php echo ($sorterData[0]=="mission") ? 'sort-dir-'.$sorterData[3]:'sort-dir-asc' ?>" data-sort="<?php echo ($sorterData[0]=="mission") ? $sorterData[1]*(-1) : 1 ?>" data-field="mission">Цель звонка</a>
              </th>              
              <th>
                 <a href="javascript:void(0);" class="field-sorter <?php echo ($sorterData[0]=="date_callback") ? 'sort-dir-'.$sorterData[3]:'sort-dir-asc' ?>" data-sort="<?php echo ($sorterData[0]=="date_callback") ? $sorterData[1]*(-1) : 1 ?>" data-field="date_callback">Дата звонка</a>
              </th>
               <th>
                 <a href="javascript:void(0);" class="field-sorter <?php echo ($sorterData[0]=="time_callback") ? 'sort-dir-'.$sorterData[3]:'sort-dir-asc' ?>" data-sort="<?php echo ($sorterData[0]=="time_callback") ? $sorterData[1]*(-1) : 1 ?>" data-field="time_callback">Время звонка</a>
              </th>
               <th>
                 <a href="javascript:void(0);" class="field-sorter <?php echo ($sorterData[0]=="status_id") ? 'sort-dir-'.$sorterData[3]:'sort-dir-asc' ?>" data-sort="<?php echo ($sorterData[0]=="status_id") ? $sorterData[1]*(-1) : 1 ?>" data-field="status_id">Статус</a>
              </th>
              <th class="actions"><?php echo $lang['ACTIONS'];?>
              </th>
            </tr>
          </thead>
            <tbody class="entity-table-tbody"> 
              <?php 
              setlocale(LC_ALL, 'ru_RU', 'rus_RUS', 'Russian_Russia');
              if (empty($entity)): ?>
                <tr class="no-results">
                  <td colspan="10" align="center"><?php echo $lang['ENTITY_NONE']; ?></td>
                </tr>
                  <?php else: ?>
                    <?php foreach ($entity as $row): ?>
                    <tr data-id="<?php echo $row['id']; ?>">
                      <td><?php echo $row['id']; ?></td>   
                            
                      <td class="add_datetime"> 
                        <?php echo MG::dateConvert($row['add_datetime']).' ['.date('H:i',strtotime($row['add_datetime'])).']'; ?>   
                      </td>
                      <td class="name">                                  
                        <?php echo $row['name'] ?>                    
                      </td>
                      
                      <td class="phone">                                  
                        <?php echo $row['phone'] ?>                    
                      </td>
                      
                      <td class="city_id">                                  
                        <?php echo $row['city_id'] ?>                    
                      </td>
                      
                      <td class="mission">                                  
                        <?php echo $row['mission'] ?>                      
                      </td>
                      
                      <td class="date_callback">                        
                        <?php if($row['date_callback']!='0000-00-00'){echo MG::dateConvert($row['date_callback']);} ?>   
                      </td>
                      
                      <td class="time_callback">                                  
                        <?php echo $row['time_callback'] ?>         
                      </td>
                      
                      <td class="status_id">                                  
                        <?php 
                        $class = 'get-paid';
                        if($row['status_id'] == 1){        
                         $class = 'get-paid';
                        }
                        if($row['status_id'] == 2){        
                         $class = 'dont-paid';
                        }
                        if($row['status_id'] == 3){        
                         $class = 'activity-product-true';
                        }
                        echo "<span class='".$class."'> ".$status[$row['status_id']]."</span>";
                        ?>                    
                      </td>                 
                  
                      <td class="actions">
                        <ul class="action-list"><!-- Действия над записями плагина -->
                          <li class="edit-row" 
                              data-id="<?php echo $row['id'] ?>" 
                              data-type="<?php echo $row['type']; ?>">
                            <a class="tool-tip-bottom" href="javascript:void(0);" 
                               title="<?php echo $lang['EDIT']; ?>"></a>
                          </li>
                          <li class="visible tool-tip-bottom  <?php echo ($row['invisible']) ? 'active' : '' ?>" 
                              data-id="<?php echo $row['id'] ?>" 
                              title="<?php echo ($row['invisible']) ? $lang['ACT_V_ENTITY'] : $lang['ACT_UNV_ENTITY']; ?>">
                            <a href="javascript:void(0);"></a>
                          </li>
                          <li class="delete-row" 
                              data-id="<?php echo $row['id'] ?>">
                            <a class="tool-tip-bottom" href="javascript:void(0);"  
                               title="<?php echo $lang['DELETE']; ?>"></a>
                          </li>
                        </ul>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
      <div class="clear"></div>
    
      <?php echo $pagination ?>  <!-- Вывод навигации -->
      <div class="clear"></div>
    </div>
  </div>
  
  <script>
     $( ".section-back-ring  .b-modal .fields-calback input[name=city_id]" ).autocomplete({
          source: availableTags
     });
     $( ".ui-autocomplete" ).css('z-index','1000'); 
     $('.section-back-ring  .b-modal .fields-calback input[name="date_callback"]').datepicker({ dateFormat: "yy-mm-dd" }); 
     $(".section-back-ring  .b-modal .fields-calback input[name=phone]").mask("+7 (999) 999-99-99");
  </script>