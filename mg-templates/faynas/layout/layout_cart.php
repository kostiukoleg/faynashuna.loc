<?php mgAddMeta('<script src="' . PATH_SITE_TEMPLATE . '/js/layout.cart.js"></script>'); ?>
<a class="c-cart__small cart" href="<?php echo SITE ?>/cart">
<div class="vmCartModule cart">
    <div class="cartmodno">
        <img src="<?php echo PATH_SITE_TEMPLATE ?>/images/cartmod.gif" alt="">
        <?php echo lang('cartCart'); ?> (<span class="countsht"><?php echo $data['cartCount'] ? $data['cartCount'] : 0 ?></span>)
        <span class="pricesht"><?php echo $data['cartPrice'] ? $data['cartPrice'] : 0 ?></span> <?php echo $data['currency']; ?>
    </div>
</div>
</a>
