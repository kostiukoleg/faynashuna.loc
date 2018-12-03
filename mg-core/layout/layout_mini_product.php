<div class="c-goods__item product-wrapper" itemscope itemtype="http://schema.org/Product">
    <div class="c-goods__left">
        <a class="c-goods__img" href="<?php echo $data['item']["link"] ?>">
            <div class="c-ribbon">
                <?php
                    $price = intval(MG::numberDeFormat($data['item']['price'])) ;
                    $oldprice = intval(MG::numberDeFormat($data['item']['old_price']));
                    $calculate = ($oldprice-$price)/($oldprice/100);
                    $result = "" .round($calculate). " %";
                    if(!empty($data['item']['old_price'])&&$oldprice>$price){
                        echo '<div class="c-ribbon__sale"> -' . $result . ' </div>' ;
                    }
                    echo $data['item']['new']?'       <div class="c-ribbon__new">'.lang('stickerNew').'</div>':'';
                    echo $data['item']['recommend']?' <div class="c-ribbon__hit">'.lang('stickerHit').'</div>':'';
                ?>

            </div>
            <?php echo mgImageProduct($data['item']); ?>
        </a>        
        <?php if (class_exists('Rating')): ?>
            [rating id = "<?php echo $data['item']['id'] ?>"]
        <?php endif; ?>
    </div>
    <div class="c-goods__right">
        <div class="c-goods__price">
            <?php if($data['item']["old_price"]!=""): ?>
            <s class="c-goods__price--old product-old-price old-price" <?php echo (!$data['item']['old_price'])?'style="display:none"':'' ?>>
                <?php echo $data['item']['old_price']; ?> <?php echo $data['currency']; ?>
            </s>
            <?php endif; ?>
            <div class="c-goods__price--current product-price">
               <?php echo priceFormat($data['item']["price"]) ?> <?php echo $data['currency']; ?>
            </div>
        </div>
        <a class="c-goods__title" href="<?php echo $data['item']["link"] ?>" itemprop="name">
            <?php echo $data['item']["title"] ?>
        </a>
        <div class="c-goods__description">
            <?php 
            if ($data['item']["short_description"]) {
                echo MG::textMore($data['item']["short_description"], 80);
            }
            else{
                echo MG::textMore($data['item']["description"], 80);
            }
            ?>
        </div>
        <div class="c-goods__footer">
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

                <a class="default-btn buy-product" href="<?php echo SITE ?>/catalog?inCartProductId=<?php echo $data['item']['id']; ?>" data-item-id="<?php echo $data['item']['id']; ?>">
                    <?php echo lang('relatedAddButton'); ?>
                </a>
            <?php } ?>
        </div>
    </div>
</div>