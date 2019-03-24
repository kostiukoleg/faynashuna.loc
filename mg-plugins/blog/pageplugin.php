<!--
Доступны переменные:
  $pluginName - название плагина
  $lang - массив фраз для выбранной локали движка
  $options - набор данного плагина хранимый в записи таблиц mg_setting
  $entity - набор записей сущностей плагина из его таблицы
  $pagination - блок навигациицам 
-->

<div class="section-<?php echo $pluginName;?>" moveCKimagesExists="<?php echo $moveCKimagesExists;?>"><!-- $pluginName - задает название секции для разграничения JS скрипта -->
    
  <!-- Тут начинается Верстка модального окна -->
  <div class="reveal-overlay" style="display:none;">
    <div class="reveal xssmall " id="add-plug-modal" style="display:block;">
      <button class="close-button closeModal" type="button"><i class="fa fa-times-circle-o" aria-hidden="true"></i></button>
      <div class="reveal-header">
        <h4 class="pages-table-icon" id="modalTitle"><?php echo $lang['MODAL_TITLE'];?></h4>
      </div>
      <div class="reveal-body">

        <div class="add-product-form-wrapper">
          <div class="add-img-form">
            <div class="product-text-inputs">
              <label for="title"><span class="custom-text"><?php echo $lang['NAME'];?>:</span><input type="text" name="title" class="product-name-input tool-tip-right" title="<?php echo $lang['T_TIP_NAME'];?>"><div class="errorField"><?php echo $lang['ERROR_SPEC_SYMBOL'];?></div></label>
              <label for="url"><span class="custom-text"><?php echo $lang['CODE'];?>:</span><input type="text" name="url" class="product-name-input qty tool-tip-right" title="<?php echo $lang['T_TIP_CODE'];?>"><div class="errorField"><?php echo $lang['ERROR_EMPTY'];?></div></label>
              <label><a href="javascript:void(0);" class="set-visible-period note" data-change-text="<?php echo $lang['HIDE_PERIOD_PARAMS'];?>"><span><?php echo $lang['VISIBLE_PERIOD_PARAMS'];?></span></a></label>
              <div class="period-params" style="display: none;">
                <label for="date_active_from"><span class="custom-text"><?php echo $lang['ACTIVE_FROM'];?>:</span><input style="width:250px;" type="text" name="date_active_from" class="date-from-input tool-tip-right" title="<?php echo $lang['T_TIP_DATE_ACTIVE_FROM'];?>"></label>
                <label for="date_active_to"><span class="custom-text"><?php echo $lang['ACTIVE_TO'];?>:</span><input style="width:250px;" type="text" name="date_active_to" class="date-to-input tool-tip-right" title="<?php echo $lang['T_TIP_DATE_ACTIVE_TO'];?>"></label>
              </div>
              <div class="category-filter">
                <span class="custom-text"><?php echo $lang['CAT_PRODUCT'];?>:<a class="add-category" href="javascript:void(0);"><span><i class="fa fa-plus-circle" aria-hidden="true"></i></span></a></span>
                <select style="width:270px;" class="last-items-dropdown custom-dropdown tool-tip-right" title="<?php echo $lang['T_TIP_CAT_PROD'];?>" id="productCategorySelect" name="category_id">
                  <option selected="selected" value="0"><?php echo $lang['ALL'];?></option>
                  <?php foreach($itemCategories as $category):?>
                  <option value="<?php echo $category["id"];?>"><?php echo $category["title"];?></option>
                  <?php endforeach;?>
                </select>
                <span style="display:none;"><input type="text" name="new_category" class="product-name-input new-category tool-tip-right" placeholder="Введите название" title="<?php echo $lang['T_TIP_NEW_CATEGORY'];?>"><a href="javascript:void(0);" class="addNewCat"><span>Добавить</span></span>
                <br /><a href="javascript:void(0);" class="add-new-cat-change note" data-change-text="Выбрать существующую категорию"><span>Добавить новую категорию</span></a>
              </div>
              <label for="tags"><span class="custom-text"><?php echo $lang['LABEL_TAGS'];?>:</span><input type="text" name="tags" class="product-name-input tool-tip-right" title="<?php echo $lang['T_TIP_TAGS'];?>" /></label>
              <label for="author"><span class="custom-text">Автор:</span><input type="text" name="author" class="product-name-input"><div class="errorField"><?php echo $lang['ERROR_EMPTY'];?></div></label>
            </div>
            <div class="product-upload-img">
              <p class="add-text add-img-text"><?php echo $lang['IMAGE_PRODUCT'];?></p>
              <div class="product-img-prev">
                <div class="img-loader" style="display:none"></div>
                <div class="prev-img" style="margin-bottom: 10px;"><img style="max-height: 100%;" src="<?php echo SITE?>/mg-admin/design/images/no-img.png" alt="" /></div>
                <form id="imageform" method="post" noengine="true" enctype="multipart/form-data">
                    <label for="photoimg" class="button success"><span><i class="fa fa-plus-circle" aria-hidden="true"></i>Загрузить</span></label>
                    <a href="javascript:void(0);" class="add-img-wrapper" style="display: none;">
                        <input type="file" name="photoimg" id="photoimg" class="add-img"/>
                        <input type="hidden" name="photoImgName" value=""/>
                        <input type="hidden" name="action" value="addImageNews"/>
                    </a>
                </form>
                  <a href="javascript:void(0);" class="cancel-img-upload tool-tip-top button secondary" title="<?php echo $lang['T_TIP_DEL_IMG_PROD'];?>"><span><i class="fa fa-times" aria-hidden="true"></i>Удалить</span></a>
                <div class="clear"></div>
              </div>
            </div>
            <div class="clear"></div>
            <div class="product-desc-wrapper">
              <ul class="accordion" data-accordion="" data-multi-expand="true" data-allow-all-closed="true" style="margin: 10px 0;">
                <li class="accordion-item" data-accordion-item=""><a class="accordion-title content_blog_acc" href="javascript:void(0);"><?php echo $lang['CONTENT'];?></a>
                  <div class="accordion-content" id="html-content-wrapper">
                    <textarea class="product-desc-field" id="blog_content" name="html_content_blog"></textarea>
                  </div>
                </li>
              </ul>
            </div>
            <a href="javascript:void(0);"><span class="seo-title"><?php echo $lang['SEO_BLOCK']?></span></a>
            <div class="seo-wrapper" style="display: none;">
              <label><span class="add-text"><?php echo $lang['META_TITLE'];?>:</span><input type="text" name="meta_title" class="product-name-input meta-data tool-tip-bottom" title="<?php echo $lang['T_TIP_META_TITLE'];?>"></label>
              <label><span class="add-text"><?php echo $lang['META_KEYWORDS'];?>:</span><input type="text" name="meta_keywords" class="product-name-input meta-data tool-tip-bottom" title="<?php echo $lang['T_TIP_META_KEYWORDS'];?>"></label>
              <label>
                <ul class="meta-list">
                  <li><span class="add-text"><?php echo $lang['META_DESC'];?>:</span></li>
                  <li><span class="symbol-left"><?php echo $lang['LENGTH_META_DESC'];?>: <span class="symbol-count"></span></li>
                </ul>
                <textarea class="meta-data tool-tip-bottom" name="meta_desc" title="<?php echo $lang['T_TIP_META_DESC'];?>"></textarea>
              </label>
            </div>
          </div>
        </div>

      </div>
      <div class="reveal-footer clearfix">
        <form action="<?php echo SITE ?>/previewer" id="previewer" noengine="true" method="post" target="_blank" style="display:none">
          <input id="previewContent" type="hidden" name="content" value=""/>
        </form>
        <a class="button fl-left previewPage" href="javascript:void(0);"><i class="fa fa-eye" aria-hidden="true"></i> Предпросмотр</a>
        <a class="button success fl-right save-button" href="javascript:void(0);"><i class="fa fa-floppy-o" aria-hidden="true"></i> Сохранить</a>
      </div>
    </div>
  </div>
  <!-- Тут заканчивается Верстка модального окна -->

  <!-- Тут начинается верстка видимой части станицы настроек плагина-->

  <div class="widget-table-body">
    <div class="widget-table-action">
      <a href="javascript:void(0);" class="show-property-order tool-tip-top button info" title="<?php echo $lang['T_TIP_SHOW_PROPERTY'];?>"><i class="fa fa-cogs" aria-hidden="true"></i><span> <?php echo $lang['SETTINGS'];?></span></a>
      <a href="javascript:void(0);" class="manage-categories custom-btn button secondary"><span><?php echo $lang['MANAGE_CATEGORIES'];?></span></a>
      <a href="javascript:void(0);" class="custom-btn add-new-button button success"><i class="fa fa-plus-circle" aria-hidden="true"></i><span> <?php echo $lang['ADD_MODAL'];?></span></a>
      <div class="select">
        <span class="label-field"> <?php echo $lang['CAT_PRODUCT']?> :</span>
        <select class="category" name="category">
            <option value="0"><?php echo $lang['ALL'];?></option>
            <?php foreach($itemCategories as $category):?>
            <option value="<?php echo $category["id"];?>" <?php echo ($category['active'])?'selected':''?>><?php echo $category["title"];?></option>
            <?php endforeach;?>
        </select>
      </div>
      <div class="filter"><div class="filter2">
        <span class="last-items"><?php echo $lang['COUNT'];?></span>
        <select class="last-items-dropdown countPrintRowsPage">
          <?php
          foreach(array(5, 10, 15, 20, 25, 30) as $value){
            $selected = '';
            if($value == $countPrintRows){
              $selected = 'selected="selected"';
            }
            echo '<option value="'.$value.'" '.$selected.' >'.$value.'</option>';
          }
          ?>
        </select>
      </div></div>
      <div class="clear"></div>
    </div>
    <div class="property-order-container" style="display:none;">    
      <h2><?php echo $lang['PLUGIN_SETTINGS'];?>:</h2>
        <form  class="base-setting" name="base-setting" method="POST">       
          <ul class="list-option">
            <li><label><span><?php echo $lang['ROOT_CATEGORY_TITLE'];?>:</span><input style="width:300px;" type="text" name="root_category_title" value="<?php echo $options['root_category_title']?>"></label></li>
            <li><label><span><?php echo $lang['ROOT_CATEGORY_META_DESC'];?>:</span><textarea style="width:300px;" type="text" name="root_category_description"><?php echo $options['root_category_description']?></textarea></label></li>
            <li><label><span><?php echo $lang['ROOT_CATEGORY_META_KEYWORDS'];?>:</span><input style="width:300px;" type="text" name="root_category_keywords" value="<?php echo $options['root_category_keywords']?>"></label></li>
            <li><label><span><?php echo $lang['PAGE_COUNT'];?>:</span><input style="width:300px;" type="text" name="page_count" value="<?=$options["page_count"]?>"></label></li>
            <li><label><span><?php echo $lang['PREVIEW_LENGTH'];?>:</span><input style="width:300px;" type="text" name="preview_length" value="<?=$options["preview_length"]?>"></label></li>
            <li><label><span><?php echo $lang['ONLY_ACTIVE'];?>:</span><input type="checkbox" name="show_active" value="<?php echo $options["show_active"];?>" <?php echo ($options["show_active"]!='false')?'checked=cheked':''?>></label></li>
            <li><label><span><?php echo $lang['CHECK_ACTIVE_PERIOD'];?>:</span><input type="checkbox" name="check_active_period" value="<?php echo $options["check_active_period"];?>" <?php echo ($options["check_active_period"]!='false')?'checked=cheked':''?>></label></li>
            <li><label><span><?php echo $lang['SHOW_CATEGORY_COUNT'];?>:</span><input type="checkbox" name="show_category_count" value="<?php echo $options["show_category_count"];?>" <?php echo ($options["show_category_count"]!='false')?'checked=cheked':''?>></label></li>
            <li><label><span><?php echo $lang['SHOW_EMPTY_CATEGORY'];?>:</span><input type="checkbox" name="show_empty_categories" value="<?php echo $options["show_empty_categories"];?>" <?php echo ($options["show_empty_categories"]!='false')?'checked=cheked':''?>></label></li>
          </ul>
          <div class="clear"></div>
        </form>
        <div class="clear"></div>
      <a href="javascript:void(0);" class="base-setting-save custom-btn button success"><span><i class="fa fa-floppy-o" aria-hidden="true"></i>Сохранить</span></a>
      <div class="clear"></div>
    </div>
    
    <div class="wrapper-entity-setting">
      <div class="clear"></div>
      <!-- Тут начинается верстка таблицы сущностей  -->
      <div class="entity-table-wrap">        
        <div class="entity-settings-table-wrapper">
          <table class="widget-table main-table">
            <thead>
              <tr>
                <th style="width:40px"  class="text-left">№</th>
                <th style="width:100px; text-align: center;"><?php echo $lang['IMAGE'];?></th>
                <th style="width:100px;"><?php echo $lang['NAME'];?></th>
                <th style="width:100px;"><?php echo $lang['CAT_NAME_TITLE'];?></th>
                <th style="width:100px;"><?php echo $lang['DATE_ACTIVE_FROM'];?></th>
                <th style="width:100px;" class="text-right"><?php echo $lang['ENTITY_ACTIONS'];?></th>
              </tr>
            </thead>
            <tbody class="entity-table-tbody"> 
              <?php if(empty($entity)):?>
                <tr class="no-results">
                  <td colspan="6" align="center"><?php echo $lang['ENTITY_NONE'];?></td>
                </tr>
                  <?php else:?>
                    <?php foreach ($entity as $row):?>
                    <tr data-id="<?php echo $row['id'];?>">
                      <td class="text-left"><?php echo $row['id'];?></td>
                      <td class="product-picture">                                  
                        <?php
                        $src = SITE.'/mg-admin/design/images/no-img.png';
                        if($row['image_url']){
                          $src = SITE.'/uploads/'.$pluginName.'/'.$row['image_url'];
                        }
                        ?>
                        <img class="uploads" src="<?php echo $src?>"/>   
                      </td>
                      <td>
                        <?php echo $row['title'];?>
                        <a class="link-to-site tool-tip-bottom" title="<?php echo $lang['VIEW_SITE'];?>" href="<?php echo SITE.$row['path']?>"  target="_blank" >
                          <img src="<?php echo SITE?>/mg-admin/design/images/icons/link.png" alt="" />
                        </a>
                      </td>
                      <td class="cat_name"><?php echo $row['cat_name']?></td>
                      <td class="date-create"><?php echo $row['date_active_from'];?></td>
                      <td class="actions text-right">
                        <ul class="action-list"><!-- Действия над записями плагина -->
                          <li class="edit-row" 
                              data-id="<?php echo $row['id']?>" 
                              data-type="<?php echo $row['type'];?>">
                            <a class="tool-tip-bottom fa fa-pencil" href="javascript:void(0);" 
                               title="<?php echo $lang['EDIT'];?>"></a>
                          </li>
                          <li class="visible tool-tip-bottom  <?php echo ($row['activity']) ? 'active' : ''?>" 
                              data-id="<?php echo $row['id']?>" 
                              title="<?php echo ($row['invisible']) ? $lang['ACT_V_ENTITY'] : $lang['ACT_UNV_ENTITY'];?>">
                            <a class="fa fa-lightbulb-o <?php echo ($row['activity']) ? 'active' : '' ?>" href="javascript:void(0);"></a>
                          </li>
                          <li class="delete-row" 
                              data-id="<?php echo $row['id']?>">
                            <a class="tool-tip-bottom  fa fa-trash" href="javascript:void(0);"  
                               title="<?php echo $lang['DELETE'];?>"></a>
                          </li>
                        </ul>
                      </td>
                    </tr>
                  <?php endforeach;?>
                <?php endif;?>
            </tbody>
          </table>
        </div>
      </div>
      <div class="clear"></div>
      <div class="table-pagination clearfix">
        <?php echo $pagination?>  <!-- Вывод навигации -->
      </div>
      <div class="clear"></div>
    </div>
  </div>
</div>