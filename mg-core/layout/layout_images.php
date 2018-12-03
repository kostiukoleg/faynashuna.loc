<?php mgAddMeta('<link type="text/css" href="'.SCRIPT.'standard/css/jquery.fancybox.css" rel="stylesheet"/>'); ?>
<?php mgAddMeta('<script src="'.SCRIPT.'jquery.fancybox.pack.js"></script>'); ?>
<?php mgAddMeta('<script src="'.SCRIPT.'jquery.bxslider.min.js"></script>'); ?>
<?php mgAddMeta('<script src="' . PATH_SITE_TEMPLATE . '/js/layout.images.js"></script>'); ?>
<?php mgAddMeta('<script src="'.SCRIPT.'zoomsl-3.0.js"></script>'); ?>

<div class="c-images mg-product-slides">
    <ul class="main-product-slide">
        <?php foreach ($data["images_product"] as $key=>$image){?>
        <li class="c-images__big product-details-image">
            <a class="fancy-modal" href="<?php echo mgImageProductPath($image, $data["id"]) ?>" data-fancybox="mainProduct">
                <?php
                    $item["image_url"] = $image;
                    $item["id"] = $data["id"];
                    $item["title"] = $data["title"];
                    $item["image_alt"] = $data["images_alt"][$key];
                    $item["image_title"] = $data["images_title"][$key];
                    echo mgImageProduct($item);
                ?>
            </a>
        </li>
        <?php }?>
    </ul>

    <?php if(count($data["images_product"])>1){?>

    <div class="c-carousel slides-slider">
        <div class="c-carousel__images slides-inner">
            <?php foreach ($data["images_product"] as $key=>$image){
            $src = mgImageProductPath($image, $data["id"], 'big');
            ?>
            <a class="c-images__slider__item   slides-item" data-slide-index="<?php echo $key?>">
                <img class="c-images__slider__img   mg-peview-foto"  src="<?php echo $src ?>" alt="<?php echo $data["images_alt"][$key];?>" title="<?php echo $data["images_title"][$key];?>"/>
            </a>
            <?php }?>
        </div>
    </div>
    <?php }?>
</div>