<div class="product-wrapper clearfix" <?php if(MG::get('controller')!=="controllers_product"): ?>
itemscope itemtype="http://schema.org/Product"<?php endif; ?>>
    <div class="product-stickers">
    <?php
        $price = intval(MG::numberDeFormat($data['item']['price'])) ;
        $oldprice = intval(MG::numberDeFormat($data['item']['old_price']));
        $calculate = ($oldprice-$price)/($oldprice/100);
        $result = "" .round($calculate). " %";
        if(!empty($data['item']['old_price'])&&$oldprice>$price){
            if(MG::get('controller')!=="controllers_product"){
                echo '<span class="sticker-sale" itemprop="offers" itemscope itemtype="http://schema.org/Offer"> -' . $result . ' </span>';
            } else {
                echo '<span class="sticker-sale"> -' . $result . ' </span>';
            }
        }
        echo $data['item']['new']?'       <span class="sticker-new">'.lang('stickerNew').'</span>':'';
        echo $data['item']['recommend']?' <span class="sticker-recommend">'.lang('stickerHit').'</span>':'';
    ?>
    </div>
    <div class="product-image">
    <?php 
        if(MODE_MINI_IMAGE!='MODE_MINI_IMAGE'){
            echo mgImageProduct($data['item'],false,'MIN',true); 
        }else{			  
            echo mgImageProduct($data['item'],false,'MID',true); 
        }
    ?>
    </div>
    <div class="info-holder clearfix">
        <?php if (class_exists('MyDesiresPlugin')): ?>[addtowishlist product=<?php echo $item['id']; ?>]<?php endif; ?>

        <div class="product-name">
            <a href="<?php echo $data['item']["link"] ?>" <?php if(MG::get('controller')!=="controllers_product"): ?>  <?php endif; ?>><span itemprop="name"><?php echo $data['item']["title"] ?></span></a>
        </div>
        <div class="product-description">
        <?php 
        if ($data['item']["short_description"]) {
            echo MG::textMore($data['item']["short_description"], 80);
        }
        else{
            echo MG::textMore($data['item']["description"], 80);
        }
        ?>
        </div>
        <div class="product-footer">
            <div class="clearfix">
                <?php if($data['item']["old_price"]!=""): ?>
                <div class="product-old-price" <?php echo (!$data['item']['old_price'])?'style="display:none"':'' ?>>
                    <?php echo $data['item']['old_price']; ?> <?php echo $data['currency']; ?>
                </div>
                <?php endif; ?>
                <div class="product-price"><span class="product-default-price" <?php if(MG::get('controller')!=="controllers_product"): ?> itemprop="price" content="<?php echo MG::numberDeFormat($data['item']["price"]);?>"<?php endif; ?>><?php echo priceFormat($data['item']["price"]) ?></span> <span <?php if(MG::get('controller')!=="controllers_product"): ?> itemprop="priceCurrency"<?php endif; ?>><?php echo $data['currency']; ?></span></div>
                <?php if (class_exists('Rating') && MG::get('controller')!=="controllers_product"): ?>          
                    <div class="mg-rating">[rating id = "<?php echo $data['item']['id'] ?>"]</div>  
                <?php endif; ?>
            </div>
            <div class="product-buttons clearfix">
                <?php 
                if (isset($data['item']['buyButton'])) {
                    if (class_exists('BuyClick')){echo '[buy-click id="'.$data['item']['id'].']';}
                    echo $data['item']['buyButton']; 
                }
                elseif(isset($data['item'][$data['actionButton']]) || isset($data['item']['actionCompare'])){
                    echo $data['item'][$data['actionButton']];
                    echo $data['item']['actionCompare'];
                    if (class_exists('BuyClick')){echo '[buy-click id="'.$data['item']['id'].']';}
                }
                else{ ?>
                    <!-- Плагин купить одним кликом-->
                    <?php if (class_exists('BuyClick')): ?>
                        [buy-click id="<?php echo $data['item']['id'] ?>"]
                    <?php endif; ?>
                    <!--/ Плагин купить одним кликом-->

                    <a class="addToCart product-buy" href="<?php echo SITE ?>/catalog?inCartProductId=<?php echo $data['item']['id']; ?>" data-item-id="<?php echo $data['item']['id']; ?>">
                        <?php echo lang('relatedAddButton'); ?>
                    </a>
                <?php } ?>
            </div>
        </div>
    </div>
</div>
