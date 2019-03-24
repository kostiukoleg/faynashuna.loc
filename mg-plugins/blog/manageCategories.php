<!--
Доступны переменные:
  $pluginName - название плагина
  $lang - массив фраз для выбранной локали движка
  $options - набор данного плагина хранимый в записи таблиц mg_setting
  $arCategories - набор категорий(записи из таблицы mg_blog_categories)
-->

<div class="section-<?php echo $pluginName;?> category" moveCKimagesExists="<?php echo $moveCKimagesExists;?>"><!-- $pluginName - задает название секции для разграничения JS скрипта -->
  <!-- Тут начинается Верстка модального окна -->

  <div class="reveal-overlay" style="display:none;">
    <div class="reveal xssmall " id="add-plug-modal" style="display:block;">
      <button class="close-button closeModal" type="button"><i class="fa fa-times-circle-o" aria-hidden="true"></i></button>
      <div class="reveal-header">
        <h4 class="pages-table-icon" id="modalTitle"><?php echo $lang['CAT_MODAL_TITLE'];?></h4>
      </div>
      <div class="reveal-body">      

        <div class="add-product-form-wrapper">
          <div class="add-img-form">
            <div class="product-text-inputs">
              <label for="title"><span class="custom-text"><?php echo $lang['NAME'];?>:</span><input type="text" name="title" class="product-name-input tool-tip-right" title="<?php echo $lang['T_TIP_CAT_NAME'];?>"><div class="errorField"><?php echo $lang['ERROR_SPEC_SYMBOL'];?></div></label>
              <label for="url"><span class="custom-text"><?php echo $lang['CODE'];?>:</span><input type="text" name="url" class="product-name-input qty tool-tip-right" title="<?php echo $lang['T_TIP_CAT_CODE'];?>"><div class="errorField"><?php echo $lang['ERROR_EMPTY'];?></div></label>
            </div>
              
            <div class="product-upload-img">
              <p class="add-text add-img-text"><?php echo $lang['IMAGE_PRODUCT'];?></p>
              <div class="product-img-prev">
                <div class="img-loader" style="display:none"></div>
                <div class="prev-img"><img src="<?php echo SITE ?>/mg-admin/design/images/no-img.png" alt="" /></div>


                <form id="imageform" method="post" noengine="true" enctype="multipart/form-data">
                  <label for="photoimg" class="button success"><span><i class="fa fa-plus-circle" aria-hidden="true"></i>Загрузить</span></label>
                    <a href="javascript:void(0);" class="add-img-wrapper" style="display: none;">
                        <input type="file" name="photoimg" id="photoimg" class="add-img"/>
                        <input type="hidden" name="photoImgName" value=""/>
                    </a>
                </form>
                  <a href="javascript:void(0);" class="cancel-img-upload tool-tip-top button secondary" title="<?php echo $lang['T_TIP_DEL_IMG_PROD'];?>"><span><i class="fa fa-times" aria-hidden="true"></i>Удалить</span></a>
                <div class="clear"></div>
              </div>
            </div>
            <div class="clear"></div>
            <div class="product-desc-wrapper">
              <ul class="accordion" data-accordion="" data-multi-expand="true" data-allow-all-closed="true" style="margin: 10px 0;">
                <li class="accordion-item" data-accordion-item=""><a class="accordion-title cat_blog_acc" href="javascript:void(0);"><?php echo $lang['CAT_DESCRIPTION'];?></a>
                  <div class="accordion-content">
                    <textarea class="product-desc-field" name="html_content_blog" id="html_category_blog"></textarea>
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
                  <textarea class="product-meta-field  meta-data tool-tip-bottom" name="meta_desc" title="<?php echo $lang['T_TIP_META_DESC'];?>"></textarea>
              </label>
            </div>
          </div>
        </div>
      </div>
      <div class="reveal-footer clearfix">
        <a class="button success fl-right save-button" href="javascript:void(0);"><i class="fa fa-floppy-o" aria-hidden="true"></i> Сохранить</a>
      </div>
    </div>
  </div>
  <!-- Тут заканчивается Верстка модального окна -->
    
  <div class="widget-table-body">
    <div class="widget-table-action">
      <a class="back-to-item-list custom-btn button secondary"><span><?php echo $lang['BACK_TO_ARTICLE'];?></span></a>
      <div class="add-new-button button success"><span><i class="fa fa-plus-circle" aria-hidden="true"></i><?php echo $lang['ADD_CATEGORY'];?></span></div>
      <div class="clear"></div>
    </div>
      
    <div class="wrapper-entity-setting">

      <!-- Тут начинается верстка таблицы сущностей  -->
      <div class="entity-table-wrap">        
        <div class="entity-settings-table-wrapper">
          <table class="widget-table main-table">
            <thead>
              <tr>
                <th style="width:40px;"></th>
                <th style="width:40px" class="text-left">№</th>
                <th style="width:100px;"><?php echo $lang['CATEGORY_NAME'];?></th>
                <th style="width:100px;"><?php echo $lang['CODE'];?></th>
                <th style="width:100px;" class="text-right"><?php echo $lang['ENTITY_ACTIONS'];?></th>
              </tr>
            </thead>
            <tbody class="entity-table-tbody"> 
              <?php if (empty($arCategories)): ?>
                <tr class="no-results">
                  <td colspan="4" align="center"><?php echo $lang['ENTITY_NONE'];?></td>
                </tr>
                <?php else: ?>
                  <?php foreach ($arCategories as $row):?>
                  <tr data-id="<?php echo $row['id'];?>">
                    <td class="mover" style="width:40px;"><i class="fa fa-arrows"></i></td>
                    <td class="text-left"><?php echo $row['id'];?></td>
                    <td>
                      <?php echo $row['title'];?>
                      <a class="link-to-site tool-tip-bottom" title="<?php echo $lang['VIEW_SITE'];?>" href="<?php echo SITE.'/'.$pluginName.'/'.$row['url']?>"  target="_blank" >
                        <img src="<?php echo SITE?>/mg-admin/design/images/icons/link.png" alt="" />
                      </a>
                    </td>
                    <td><?php echo $row['url'];?></td>
                    <td class="actions text-right">
                      <ul class="action-list"><!-- Действия над записями плагина -->
                        <li class="edit-row" 
                            data-id="<?php echo $row['id']?>" 
                            data-type="<?php echo $row['type'];?>">
                          <a class="tool-tip-bottom fa fa-pencil" href="javascript:void(0);" 
                             title="<?php echo $lang['EDIT'];?>"></a>
                        </li>
                        <li class="delete-row" 
                            data-id="<?php echo $row['id']?>">
                          <a class="tool-tip-bottom fa fa-trash" href="javascript:void(0);"  
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
    </div>
  </div>
</div>
<script>
admin.sortable('.entity-table-tbody','<?php echo $pluginName.'_categories'?>');
</script>