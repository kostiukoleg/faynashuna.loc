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
                      

                <div class="custom-form-wrapper block-for-form fields-order" >
                    <div class="info-text">Информация о пользователе:</div>
                    <table class="plugin-table">
                        <tr>
                            <td>Имя:</td>
                            <td><span class="name"></span></td>
                        </tr>
                        <tr>
                            <td>Телефон:</td>
                            <td><span class="phone"></span></td>
                        </tr>
                        <tr>
                            <td>Email:</td>
                            <td><span class="email" ></span></td>
                        </tr>
                        <tr>
                            <td>Адрес:</td>
                            <td><span class="address" ></span></td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <div class="info-text">Информация о заказе:</div>
                            </td>
                        </tr>
                        <tr>
                            <td>id товара:</td>
                            <td><span  class="product_id" ></span></td>
                        </tr>
                        <tr>
                            <td>Артикул:</td>
                            <td><span class="code" ></span></td>
                        </tr>
                        <tr>
                            <td>Наименование:</td>
                            <td><span class="title" ></span></td>
                        </tr>
                        <tr>
                            <td></td>
                            <td><span class="description"></span></td>
                        </tr>
                        <tr>
                            <td>Количество:</td>
                            <td><span class="count" ></span></td>
                        </tr>
                        <tr>
                            <td>Комментарий пользователя:</td>
                            <td><span class="comment" ></span></td>
                        </tr>
                        <tr>
                            <td>Дата оформления заказа:</td>
                            <td><span class='add_datetime'></span></td>
                        </tr>
                        <tr>
                            <td>Статус</td>
                            <td>
                                <select name="status_id">
                                    <?php
                                    foreach ($status as $id => $item) {
                                        echo "<option value='".$id."'>".$item."</option>";
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td>Комментарий менеджера:</td>
                            <td><textarea name="comment_admin">  </textarea></td>
                        </tr>
                    </table>
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
            <a href="javascript:void(0);" class="show-filters tool-tip-top" title="<?php echo $lang['T_TIP_SHOW_FILTER']; ?>"><span><?php echo $lang['FILTER']; ?></span></a>
            <a href="javascript:void(0);" class="show-property-order tool-tip-top" title="<?php echo $lang['T_TIP_SHOW_PROPERTY']; ?>"><span><?php echo $lang['SHOW_PROPERTY']; ?></span></a>

            <div class="filter">
                <span class="last-items"><?php echo $lang['SHOW_COUNT_ORDER']; ?></span>
                <select class="last-items-dropdown countPrintRowsEntity">
                    <?php
                    foreach (array(10, 20, 30, 50, 100) as $value) {
                      $selected = '';
                      if ($value == $countPrintNonAvailable) {
                        $selected = 'selected="selected"';
                      }
                      echo '<option value="'.$value.'" '.$selected.' >'.$value.'</option>';
                    }
                    ?>
                </select>
            </div>
            <div class="clear"></div>
        </div>

        <div class="filter-container" <?php if ($displayFilter) {
                      echo "style='display:block'";
                    } ?>>
<?php echo $filter ?>
            <div class="clear"></div>
        </div>

        <div class="property-order-container">    
            <h2>Поля в форме :</h2>
            <form  class="base-setting" name="base-setting" method="POST">       
                <ul class="list-option">
                    <li><label><span>Имя:</span> <input type="checkbox" name="name" value="<?php echo $options["name"] ?>" <?php echo ($options["name"] && $options["name"] != 'false') ? 'checked=cheked' : '' ?>></label></li>
                    <li><label><span>Телефон:</span> <input type="checkbox" name="phone" value="<?php echo $options["phone"] ?>" <?php echo ($options["phone"] && $options["phone"] != 'false') ? 'checked=cheked' : '' ?>></label></li>
                    <li><label><span>Email:</span> <input type="checkbox" name="email" value="<?php echo $options["email"] ?>" <?php echo ($options["email"] && $options["email"] != 'false') ? 'checked=cheked' : '' ?>></label></li>
                    <li><label><span>Адрес:</span> <input type="checkbox" name="address" value="<?php echo $options["address"] ?>" <?php echo ($options["address"] && $options["address"] != 'false') ? 'checked=cheked' : '' ?>></label></li>
                    <li><label><span>Количество:</span> <input type="checkbox" name="count" value="<?php echo $options["count"] ?>" <?php echo ($options["count"] && $options["count"] != 'false') ? 'checked=cheked' : '' ?>></label></li>
                    <li><label><span>Комментарий:</span> <input type="checkbox" name="comment" value="<?php echo $options["comment"] ?>" <?php echo ($options["comment"] && $options["comment"] != 'false') ? 'checked=cheked' : '' ?>></label></li>
                    <li><label><span>Капча:</span> <input type="checkbox" name="capcha" value="<?php echo $options["capcha"] ?>" <?php echo ($options["capcha"] && $options["capcha"] != 'false') ? 'checked=cheked' : '' ?>></label></li>
                    <li><span>Заголовок окна заказа:</span><input type="text" name="header" value="<?php echo $options["header"] ?>"/></li>
                    <li><span>Название кпонпки:</span><input type="text" name="button" value="<?php echo $options["button"] ?>"/></li>
                    
                    <li><span>E-mail для получения заявок</span> <input type="text" name="email_order" value="<?php echo $options["email_order"] ?>"/> Если не указан, то будет использоваться администраторский e-mail</li>
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
                                    <a href="javascript:void(0);" class="field-sorter <?php echo ($sorterData[0] == "add_datetime") ? 'sort-dir-'.$sorterData[3] : 'sort-dir-asc' ?>" data-sort="<?php echo ($sorterData[0] == "add_datetime") ? $sorterData[1] * (-1) : 1 ?>" data-field="add_datetime">Добавлено</a>
                                </th>              
                                <th>
                                    <a href="javascript:void(0);" class="field-sorter <?php echo ($sorterData[0] == "name") ? 'sort-dir-'.$sorterData[3] : 'sort-dir-asc' ?>" data-sort="<?php echo ($sorterData[0] == "name") ? $sorterData[1] * (-1) : 1 ?>" data-field="name">Имя</a>
                                </th>
                                <th>
                                    <a href="javascript:void(0);" class="field-sorter <?php echo ($sorterData[0] == "phone") ? 'sort-dir-'.$sorterData[3] : 'sort-dir-asc' ?>" data-sort="<?php echo ($sorterData[0] == "phone") ? $sorterData[1] * (-1) : 1 ?>" data-field="phone">Телефон</a>
                                </th>
                                <th>
                                    <a href="javascript:void(0);" class="field-sorter <?php echo ($sorterData[0] == "code") ? 'sort-dir-'.$sorterData[3] : 'sort-dir-asc' ?>" data-sort="<?php echo ($sorterData[0] == "code") ? $sorterData[1] * (-1) : 1 ?>" data-field="code">Артикул товара</a>
                                </th>
                                <th>
                                    <a href="javascript:void(0);" class="field-sorter <?php echo ($sorterData[0] == "title") ? 'sort-dir-'.$sorterData[3] : 'sort-dir-asc' ?>" data-sort="<?php echo ($sorterData[0] == "title") ? $sorterData[1] * (-1) : 1 ?>" data-field="title">Наименование</a>
                                </th>              
                                <th>
                                    <a href="javascript:void(0);" class="field-sorter <?php echo ($sorterData[0] == "status_id") ? 'sort-dir-'.$sorterData[3] : 'sort-dir-asc' ?>" data-sort="<?php echo ($sorterData[0] == "status_id") ? $sorterData[1] * (-1) : 1 ?>" data-field="status_id">Статус</a>
                                </th>
                                <th class="actions"><?php echo $lang['ACTIONS']; ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="entity-table-tbody"> 
                            <?php if (empty($entity)): ?>
                              <tr class="no-results">
                                  <td colspan="8" align="center"><?php echo $lang['ENTITY_NONE']; ?></td>
                              </tr>
<?php else: ?>
  <?php foreach ($entity as $row): ?>
                                <tr data-id="<?php echo $row['id']; ?>">
                                    <td><?php echo $row['id']; ?></td>   

                                    <td class="add_datetime"> 
                                        <?php echo MG::dateConvert($row['add_datetime']).' ['.date('H:i', strtotime($row['add_datetime'])).']'; ?>   
                                    </td>
                                    <td class="name">                                  
                                        <?php echo $row['name'] ?>                    
                                    </td>

                                    <td class="phone">                                  
                                        <?php echo $row['phone'] ?>                    
                                    </td>

                                    <td class="code">                                  
                                        <?php echo $row['code'] ?>                    
                                    </td>

                                    <td class="title">                                  
                                        <?php echo $row['title'] ?>                      
                                    </td>

                                    <td class="status_id">                                  
                                        <?php
                                        $class = 'get-paid';
                                        if ($row['status_id'] == 1) {
                                          $class = 'get-paid';
                                        }
                                        if ($row['status_id'] == 2) {
                                          $class = 'dont-paid';
                                        }
                                        if ($row['status_id'] == 3) {
                                          $class = 'activity-product-true';
                                        }
                                        echo "<span class='".$class."'> ".($status[$row['status_id']] ? $status[$row['status_id']] : 'Без статуса')."</span>";
                                        ?>                    
                                    </td>                 

                                    <td class="actions">
                                        <ul class="action-list"><!-- Действия над записями плагина -->
                                            <li class="edit-row" 
                                                data-id="<?php echo $row['id'] ?>">
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
