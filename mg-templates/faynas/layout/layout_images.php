<?php mgAddMeta('<link type="text/css" href="'.SCRIPT.'standard/css/jquery.fancybox.css" rel="stylesheet"/>'); ?>
<?php mgAddMeta('<script src="'.SCRIPT.'jquery.fancybox.pack.js"></script>'); ?>
<?php mgAddMeta('<script src="'.SCRIPT.'jquery.bxslider.min.js"></script>'); ?>
<?php mgAddMeta('<script src="' . PATH_SITE_TEMPLATE . '/js/layout.images.js"></script>'); ?>
<?php mgAddMeta('<script src="'.SCRIPT.'zoomsl-3.0.js"></script>'); ?>

<div class="mg-product-slides margin">
    <?php if(count($data["images_product"])>1){?>

    <div class="thumbs-holder">
        <div class="slides-slider">
            <div class="slides-inner">
                <?php foreach ($data["images_product"] as $key=>$image){
                $src = mgImageProductPath($image, $data["id"], 'small');
                $data["images_alt"][$key] = $imagesData["image_alt"]?$imagesData["image_alt"]:$data["title"].'_'.$key;
                $data["images_title"][$key] = $imagesData["images_title"]?$imagesData["images_title"]:$data["title"].'_'.$key;
                ?>
                <a class="slides-item" data-slide-index="<?php echo $key?>">
                    <img class="mg-peview-foto"  src="<?php echo $src ?>" alt="<?php echo $data["images_alt"][$key];?>"/>
                </a>
                <?php }?>
            </div>
        </div>    
        <div class="thumb-controls clearfix"></div>        
    </div>
    <?php }?>
    <div class="main-image">
    <div class="product-stickers"></div>
        <ul class="main-product-slide">
            <?php foreach ($data["images_product"] as $key=>$image){?>
            <li class="product-details-image">
                <a class="fancy-modal" href="<?php echo mgImageProductPath($image, $data["id"]) ?>" data-fancybox="mainProduct" rel="gallery">
                    <?php
                        $item["image_url"] = $image;
                        $item["id"] = $data["id"];
                        $item["title"] = $data["title"];
                        $item["image_alt"] = $data["images_alt"][$key];
                        $item["image_title"] = $data["images_title"][$key];  
                        echo mgImageProduct($item,false,'MID',true); 
                    ?>
                </a>
                <a class="zoom" href="javascript:void(0);"></a>
            </li>
            <?php }?>
        </ul>
    </div>
</div>