<script type="text/javascript">
includeJS('../mg-plugins/news/js/news.js');
</script> 
<link rel="stylesheet" href="../mg-plugins/news/css/style.css" type="text/css" />
<?php mgAddMeta('<link href="'.SCRIPT.'standard/css/datepicker.css" rel="stylesheet" type="text/css">'); ?>
 <div class="section-news">
    <!-- Тут начинается Верстка модального окна -->

  <div class="reveal-overlay" id="add-news-wrapper" style="display:none;">
      <div class="reveal xssmall" id="add-plug-modal" style="display:block;">
        <button class="close-button closeModal" type="button"><i class="fa fa-times-circle-o" aria-hidden="true"></i></button>
        <div class="reveal-header">
          <h4 class="pages-table-icon" id="modalTitle"><i class="fa fa-plus-circle" aria-hidden="true"></i> <?php echo $lang['NEWS_MODAL_TITLE'];?></h4>
        </div>
        <div class="reveal-body">      

        <div class="add-product-form-wrapper">

          <div class="add-img-form">
            <div class="product-text-inputs fl-left">
              <label for="title"><span class="add-text"><?php echo $lang['NEWS_NAME'];?>:</span><input type="text" name="title" class="product-name-input tool-tip-right large" title="<?php echo $lang['T_TIP_NEWS_NAME'];?>"><div class="errorField"><?php echo $lang['ERROR_SPEC_SYMBOL'];?></div></label>
              <label for="url"><span class="add-text"><?php echo $lang['NEWS_URL'];?>:</span><input type="text" name="url" class="product-name-input qty tool-tip-right large" title="<?php echo $lang['T_TIP_NEWS_URL'];?>"><div class="errorField"><?php echo $lang['ERROR_EMPTY'];?></div></label>
              <label for="author"><span class="add-text">Автор:</span><input type="text" name="author" class="product-name-input large"><div class="errorField"><?php echo $lang['ERROR_EMPTY'];?></div></label>
            </div>

            <div class="product-upload-img fl-right">
              <p class="add-text add-img-text" style="display: none;"><?php echo $lang['IMAGE_PRODUCT'];?></p>
              <div class="product-img-prev">
                <div class="img-loader" style="display:none"></div>
                <div class="prev-img"><img src="<?php echo SITE ?>/mg-admin/design/images/no-img.png" alt="" /></div>

                <form id="imageform" method="post" noengine="true" enctype="multipart/form-data">
                  <label for="photoimg" class="button success"><span><i class="fa fa-plus-circle" aria-hidden="true"></i>Загрузить</span></label>
                    <a href="javascript:void(0);" class="add-img-wrapper" style="display: none;">
                        <input type="file" name="photoimg" id="photoimg" class="add-img"/>
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
                <li class="accordion-item" data-accordion-item=""><a class="accordion-title" href="javascript:void(0);"><?php echo $lang['NEWS_CONTENT'];?></a>
                  <div class="accordion-content">
                    <textarea class="product-desc-field" name="html_content" id="news_content"></textarea>
                  </div>
                </li>
              </ul>
            </div>
            <div><span class="add-text"><?php echo $lang['DATE'] ?> :</span> <input type="text" class="medium" name="add_date" value=""></div>
            <span class="seo-title"><?php echo $lang['SEO_BLOCK']?></span>
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
        <form action="<?php echo SITE ?>/previewer" id="previewer" noengine="true" method="post" target="_blank" style="display:none">
          <input id="previewContent" type="hidden" name="content" value=""/>
        </form>
        <a class="button fl-left previewPage" href="javascript:void(0);"><i class="fa fa-eye" aria-hidden="true"></i> <?php echo $lang['PREVIEW'];?></a>
        <a class="button success fl-right save-button" href="javascript:void(0);"><i class="fa fa-floppy-o" aria-hidden="true"></i> <?php echo $lang['SAVE'];?></a>
      </div>
    </div>
  </div>

    <!-- Тут заканчивается Верстка модального окна -->


    <!-- Тут начинается  Верстка таблицы товаров -->
    <div class="widget-table-body">
      <div class="widget-table-action">
        <div class="add-new-button tool-tip-bottom button success" title="<?php echo $lang['T_TIP_ADD_PAGE'];?>"><span><i class="fa fa-plus-circle" aria-hidden="true"></i> <?php echo $lang['NEWS_MODAL_TITLE'];?></span></div>
        <div class="filter fl-right">
          <span class="last-items"><?php echo $lang['NEWS_COUNT'];?></span>
          <select class="last-items-dropdown countPrintRowsPage small">
            <?php
            foreach(array(5, 10, 15, 20, 25, 30) as $value){
              $selected = '';
              if($value == $countPrintRowsNews){
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
              <th class="product-picture"><?php echo $lang['IMAGE'];?></th>
              <th class="news-name"><?php echo $lang['NEWS_NAME'];?></th>
              <th><?php echo $lang['NEWS_URL'];?></th>
              <th><?php echo $lang['PUBLISH_AFTER'];?></th>
              <th class="actions text-right"><?php echo $lang['ACTIONS'];?></th>
            </tr>
          </thead>
          <tbody class="news-tbody">

          <?php
          if(!empty($news)){
          foreach($news as $data){ ?>
              <tr id="<?php echo $data['id'] ?>">
                <td class="product-picture image_url">
                  <?php
                  $src = SITE.'/mg-admin/design/images/no-img.png';
                  if($data['image_url']){
                    $src = SITE.'/uploads/news/'.$data['image_url'];
                  }
                  ?>
                  <img class="uploads" src="<?php echo $src ?>"/>
                </td>
                <td class="title"><?php echo $data['title'] ?></td>
                <td class="url"><a class="tool-tip-bottom" href="<?php echo SITE."/news/".$data['url'];?>" title="<?php echo $lang['T_TIP_GOTO_PAGE'];?>" target="_blank"><?php echo $data['url'] ?></a></td>
               <?php $diff = round((strtotime($data['add_date']) - time())/(3600*24));?>
                <td><?php echo date('d.m.Y',  strtotime($data['add_date'])) ?>
                  <?php if ($diff > 0) {  ?>
                  <span class="future-public"> <?php echo 'Публикация через '.$diff.' дн.'; ?> </span> 
                  <?php  } ?>
                </td>                
                <td class="actions text-right">
                  <ul class="action-list">
                    <li class="edit-row" id="<?php echo $data['id'] ?>"><a class="tool-tip-bottom fa fa-pencil" href="#" title="<?php echo $lang['EDIT'];?>"></a></li>
                    <li class="delete-order" id="<?php echo $data['id'] ?>"><a class="tool-tip-bottom fa fa-trash" href="#"  title="<?php echo $lang['DELETE'];?>"></a></li>
                  </ul>
                </td>
              </tr>
           <?php }
          }else{
          ?>

           <tr class="noneNews"><td colspan="4"><?php echo $lang['NEWS_NONE']?></td></tr>

         <?php }?>

          </tbody>
        </table>
      </div>

      <?php echo $pagination ?>
      <div class="clear"></div>
   </div>
 </div>

<script>  
     $('.section-news #add-plug-modal .add-product-form-wrapper input[name="add_date"]').datepicker({dateFormat: "dd.mm.yy"});  
  </script>