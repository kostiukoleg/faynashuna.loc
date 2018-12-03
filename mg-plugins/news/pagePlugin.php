<script type="text/javascript">
includeJS('../mg-plugins/news/js/news.js');
</script>
<link rel="stylesheet" href="../mg-plugins/news/css/style.css" type="text/css" />

 <div class="section-news">
    <!-- Тут начинается Верстка модального окна -->

      <div class="b-modal hidden-form" id="add-news-wrapper">
        <div class="product-table-wrapper add-news-form">
          <div class="widget-table-title">
            <h4 class="pages-table-icon" id="modalTitle"><?php echo $lang['NEWS_MODAL_TITLE'];?></h4>
            <div class="b-modal_close tool-tip-bottom" title="<?php echo $lang['T_TIP_CLOSE_MODAL'];?>"></div>
          </div>
          <div class="widget-table-body">
            <div class="add-product-form-wrapper">

              <div class="add-img-form">
                <div class="product-text-inputs">
                  <label for="title"><span class="add-text"><?php echo $lang['NEWS_NAME'];?>:</span><input type="text" name="title" class="product-name-input tool-tip-right" title="<?php echo $lang['T_TIP_NEWS_NAME'];?>"><div class="errorField"><?php echo $lang['ERROR_SPEC_SYMBOL'];?></div></label>
                  <label for="url"><span class="add-text"><?php echo $lang['NEWS_URL'];?>:</span><input style="width:220px;" type="text" name="url" class="product-name-input qty tool-tip-right" title="<?php echo $lang['T_TIP_NEWS_URL'];?>"><div class="errorField"><?php echo $lang['ERROR_EMPTY'];?></div></label>
                </div>

                <div class="product-upload-img">
                  <p class="add-text add-img-text"><?php echo $lang['IMAGE_PRODUCT'];?></p>
                  <div class="product-img-prev">
                    <div class="img-loader" style="display:none"></div>
                    <div class="prev-img"><img src="<?php echo SITE ?>/mg-admin/design/images/no-img.png" alt="" /></div>

                    <form id="imageform" method="post" noengine="true" enctype="multipart/form-data">
                        <a href="javascript:void(0);" class="add-img-wrapper">
                            <span>Загрузить</span>
                            <input type="file" name="photoimg" id="photoimg" class="add-img"/>
                            <input type="hidden" name="action" value="addImageNews"/>
                        </a>
                    </form>
                      <a href="javascript:void(0);" class="cancel-img-upload tool-tip-top" title="<?php echo $lang['T_TIP_DEL_IMG_PROD'];?>"><span>Удалить</span></a>
                    <div class="clear"></div>
                  </div>
                </div>
                <div class="clear"></div>

                <div class="product-desc-wrapper">
                  <span class="add-text" style="margin-bottom: 10px;"><?php echo $lang['NEWS_CONTENT'];?>:</span>
                  <div style="background:#FFF">
                    <textarea class="product-desc-field" name="html_content"  style="width:785px;"></textarea>
                  </div>
                </div>
                <div><span><?php echo $lang['DATE'] ?> :</span> <input type="date" name="add_date" value=""></div>
		<span class="seo-title"><?php echo $lang['SEO_BLOCK']?></span>
                <div class="seo-wrapper">
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
		<div class="clear"></div>

                <form action="<?php echo SITE ?>/previewer" id="previewer" noengine="true" method="post" target="_blank" style="display:none">
                  <input id="previewContent" type="hidden" name="content" value=""/>
                </form>

		<button class="previewPage tool-tip-bottom" title="<?php echo $lang['T_TIP_PREVIEW_NEWS'];?>"><span><?php echo $lang['PREVIEW'];?></span></button>
                <button class="save-button tool-tip-bottom" title="<?php echo $lang['T_TIP_SAVE_NEWS'];?>"><span><?php echo $lang['SAVE'];?></span></button>

                <div class="clear"></div>
              </div>
            </div>
          </div>
        </div>
      </div>

    <!-- Тут заканчивается Верстка модального окна -->


    <!-- Тут начинается  Верстка таблицы товаров -->
    <div class="widget-table-body">
      <div class="widget-table-action">
        <div class="add-new-button tool-tip-bottom" title="<?php echo $lang['T_TIP_ADD_PAGE'];?>"><span><?php echo $lang['NEWS_MODAL_TITLE'];?></span></div>
        <div class="filter">
          <span class="last-items"><?php echo $lang['NEWS_COUNT'];?></span>
          <select class="last-items-dropdown countPrintRowsPage">
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
        <table class="widget-table product-table">
          <thead>
            <tr>
              <th class="product-picture"><?php echo $lang['IMAGE'];?></th>
              <th class="news-name"><?php echo $lang['NEWS_NAME'];?></th>
              <th><?php echo $lang['NEWS_URL'];?></th>
              <th><?php echo $lang['PUBLISH_AFTER'];?></th>
              <th class="actions"><?php echo $lang['ACTIONS'];?></th>
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
                <td class="actions">
                  <ul class="action-list">
                    <li class="edit-row" id="<?php echo $data['id'] ?>"><a class="tool-tip-bottom" href="#" title="<?php echo $lang['EDIT'];?>"></a></li>
                    <li class="delete-order" id="<?php echo $data['id'] ?>"><a class="tool-tip-bottom" href="#"  title="<?php echo $lang['DELETE'];?>"></a></li>
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
