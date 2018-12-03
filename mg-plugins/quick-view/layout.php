<!--
Доступны переменные:
  $pluginName - название плагина
  $lang - массив фраз для выбранной локали движка
  $options - набор данного плагина хранимый в записи таблиц mg_setting
  $entity - набор записей сущностей плагина из его таблицы
  $pagination - блок навигациицам 
-->
<div class="wrapper-modal-mg-quick-view" style="display: block; z-index: 111; margin-top: 600px;" itemscope="" itemtype="http://schema.org/Product">
    <!--Здесь начинается верстка мини-карточки. Классы не удалять! по ним js подгружает информацию о товаре-->
     <span class="close-mg-quickview-button"><a href="javascript:void(0);"></a></span>
  <h1 class="product-title title-modal-mg-quick-view" itemprop="name"><?php echo $data['title']; ?></h1>
<div class="mg-product-slides">
    <?php
    echo $data['recommend']?'<span class="sticker-recommend"></span>':'';
    echo $data['new']?'<span class="sticker-new"></span>':'';
    ?>

  
    <ul class="main-product-slide">
        <?php         
        foreach ($data["images_product"] as $key=>$image){?>
            <li  class="product-details-image"><a href="<?php echo mgImageProductPath($image, $data["id"]) ?>" rel="gallery" class="fancy-modal">
            <?php
            $item["image_url"] = $image;
            $item["id"] = $data["id"];
            $item["title"] = $data["title"];
            $item["image_alt"] = $data["images_alt"][$key];
            $item["image_title"] = $data["images_title"][$key];
            echo mgImageProduct($item);
            ?></a>
            <a class="zoom" href="javascript:void(0);"></a>
	
            </li>
        <?php 
		
		}?>
    </ul>


    <?php if(count($data["images_product"])>1){?>
    <div class="slides-slider">
        <div class="slides-inner">
            <?php foreach ($data["images_product"] as $key=>$image){
              $src = mgImageProductPath($image, $data["id"], 'big');
            ?>
              <a data-slide-index="<?php echo $key?>" class="slides-item" href="javascript:void(0);">
                <img class="mg-peview-foto"  src="<?php echo $src ?>" alt="<?php echo $data["images_alt"][$key];?>" title="<?php echo $data["images_title"][$key];?>"/>
              </a>
            <?php }?>
        </div>
    </div>
    <?php }?>
</div>
  <div class="product-status">
    <div class="buy-block">
      <div class="buy-block-inner">
        <div class="product-code">
          Артикул: <span class="label-article code" itemprop="productID"><?php echo $data['code']; ?></span>
        </div>
        <div class="product-price" itemprop="offers">
          <ul class="product-status-list">
            <li <?php echo (!$data['old_price'])?'style="display:none"':'style="display:block"' ?>>
              <div class="old">
                <s><span class="old-price"><?php echo MG::numberFormat($data['old_price'])." ".$data['currency']; ?></span></s>
              </div>
            </li>
            <li>
              <div class="normal-price">
                <span id="count_price" class="price" itemprop="price"><?php echo $data['price'] ?></span> <span class="price"><?php echo $data['currency']; ?></span>
              </div>
            </li>
          </ul>
        </div>

        <ul class="product-status-list">
          <!--если не установлен параметр - старая цена, то не выводим его-->
<li class="count-product-info">
              <?php layout('count_product', $data); ?>
          </li>
          
          <li <?php echo (!$data['weight'])?'style="display:none"':'style="display:block"' ?>>Вес: <span class="label-black weight"><?php echo $data['weight'] ?></span> кг. </li>
        </ul>
        <!--Кнопка, кототорая меняет свое значение с "В корзину" на "Подробнее"-->
        <?php echo $data['propertyForm'] ?>
      </div>
    </div>   
  </div><!-- End product-status--> 
 <div class="clear"></div>    
  <h2>Описание:</h2>
  <div class="description" itemprop="description"></div>
  <a href="<?php echo SITE.'/'.$data['category_url'].'/'.$data['product_url']; ?>" data-item-id="<?php echo $data['id']; ?>" class="toProductWrapper">Подробнее</a>
</div>

