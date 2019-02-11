<form action="<?php echo SITE.$data['action'] ?>" method="<?php echo $data['method'] ?>" class="property-form <?php echo $data['catalogAction'] ?>" data-product-id='<?php echo $data['id'] ?>'>
    <div class="buy-container <?php echo (MG::get('controller') == 'controllers_product') ? 'product' : '' ?>"
        <?php if (MG::get('controller') == 'controllers_product') {
            echo($data['maxCount'] == "0" || !$data['activity'] ? 'style="display:none"' : '');
        } ?> >

        <div class="hidder-element" <?php echo($data['noneButton'] ? 'style="display:none"' : '') ?> >
            <p class="qty-text">Количество:</p>
            <div class="cart_form" <?php if ((MG::getSetting('printQuantityInMini') == 'false') && MG::get('controller') != "controllers_product") {echo 'style="display: none;"';} ?>>
                <input type="text" class="amount_input" name="amount_input" data-max-count="<?php echo $data['maxCount'] ?>" value="1"/>
                <div class="amount_change">
                    <a href="#" class="up">
                        +
                    </a>                   
                    <a href="#" class="down">
                        -
                    </a>
                </div>
            </div>
        </div>
        <div class="hidder-element">
            <input type="hidden" name="inCartProductId" value="<?php echo $data['id'] ?>">
            <?php if (!$data['noneButton'] || (MG::getProductCountOnStorage(0, $data['id'], 0, 'all') != 0)) { ?>
                <?php if ($data['ajax']) {
                    if ($data['buyButton']) {
                        ?>
                        <?php echo $data['buyButton']; ?>
                    <?php } else { ?>                     
                        <?php echo  MG::layoutManager('layout_btn_buy', $data); ?>
                        <input type="submit" name="buyWithProp" onclick="return false;" style="display:none">
                        <?php
                    }
                } else {
                    ?>

                    <input type="submit" name="buyWithProp">

                <?php } ?>
                <?php if ($data['printCompareButton'] == 'true') { ?>
                    <?php echo  MG::layoutManager('layout_btn_compare', $data); ?>  
                <?php } ?>
            <?php } ?>
            <?php if (class_exists('BuyClick')): ?>
                [buy-click id="<?php echo $data['id'] ?>"]
            <?php endif; ?>
        </div>
    </div>
    <div class="c-form">
        <?php echo $data['blockVariants']; ?>
        <?php echo $data['htmlProperty']; ?>
        <?php echo $data['addHtml'];?>    
    </div>
</form>