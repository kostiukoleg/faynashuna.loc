<?php mgAddMeta('<script src="' . PATH_SITE_TEMPLATE . '/js/layout.cart.js"></script>'); ?>

<?php if (MG::getSetting('popupCart') == 'true') { ?>
    <div class="mg-fake-cart" style="display: none;">
        <a class="mg-close-fake-cart mg-close-popup" href="javascript:void(0);"></a>
        <div class="popup-header"><div class="title"><?php echo lang('cartTitle'); ?></div></div>
        <div class="popup-body">
            <div class="table-wrapper">
                <table class="small-cart-table">

                    <?php if (!empty($data['cartData']['dataCart'])) { ?>

                        <?php foreach ($data['cartData']['dataCart'] as $item): ?>
                            <tr>
                                <td class="small-cart-img">
                                    <a href="<?php echo SITE . "/" . (isset($item['category_url']) ? $item['category_url'] : 'catalog/') . $item['product_url'] ?>">
                                        <img src="<?php echo $item["image_url_new"] ?>" alt="<?php echo $item['title'] ?>"/>
                                    </a>
                                </td>
                                <td class="small-cart-name">
                                    <ul class="small-cart-list">
                                        <li>
                                            <a href="<?php echo SITE . "/" . (isset($item['category_url']) ? $item['category_url'] : 'catalog/') . $item['product_url'] ?>"><?php echo $item['title'] ?></a>
                                            <span class="property"><?php echo $item['property_html'] ?></span>
                                        </li>
                                        <li class="qty">
                                            x<?php echo $item['countInCart'] ?>
                                            <span><?php echo $item['priceInCart'] ?></span>
                                        </li>
                                    </ul>
                                </td>
                                <td class="small-cart-remove">
                                    <a href="#" class="deleteItemFromCart" title="<?php echo lang('delete'); ?>"
                                        data-delete-item-id="<?php echo $item['id'] ?>"
                                        data-property="<?php echo $item['property'] ?>"
                                        data-variant="<?php echo $item['variantId'] ?>">
                                        <div class="icon__cart-remove">
                                            <svg class="icon icon--close"><use xlink:href="#icon--close"></use></svg>
                                        </div>
                                        </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                    <?php } else { ?>

                    <?php } ?>
                </table>
            </div>
        </div>
        <ul class="total sum-list">
            <li class="total-sum">
                <?php echo lang('toPayment')?>:
                <span>
                    <?php echo $data['cartData']['cart_price_wc'] ?>
                </span>
            </li>
        </ul>
        <div class="popup-footer">
            <ul class="total">
                <li class="checkout-buttons">
                    <a class="mg-close-popup" href="javascript:void(0)"><?php echo lang('cartContinue'); ?></a>
                    <a class="default-btn" href="<?php echo SITE ?>/order"><?php echo lang('cartCheckout'); ?></a>
                </li>
            </ul>
        </div>
    </div>
<?php }; ?>


<div class="mg-desktop-cart">
    <div class="cart">
        <div class="cart-inner">
            <a class="" href="<?php echo SITE ?>/cart">
                <span class="small-cart-icon">
                    <span class="countsht"><?php echo $data['cartCount'] ? $data['cartCount'] : 0 ?></span>
                </span>
                <ul class="cart-list">
                    <li class="cart-qty">
                        <span class="cart-qty-text"><?php echo lang('cartCart'); ?>:</span>
                        <span class="pricesht"><?php echo $data['cartPrice'] ? $data['cartPrice'] : 0 ?></span> <?php echo $data['currency']; ?>
                    </li>
                </ul>
            </a>
        </div>
        <div class="small-cart">
            <div class="title"><?php echo lang('cartTitle'); ?></div>
            <div class="table-wrapper">
                <table class="small-cart-table">

                    <?php if (!empty($data['cartData']['dataCart'])) { ?>

                        <?php foreach ($data['cartData']['dataCart'] as $item): ?>
                            <tr>
                                <td class="c-table__img small-cart-img">
                                    <a href="<?php echo SITE . "/" . (isset($item['category_url']) ? $item['category_url'] : 'catalog/') . $item['product_url'] ?>">
                                        <img src="<?php echo $item["image_url_new"] ?>" alt="<?php echo $item['title'] ?>"/>
                                    </a>
                                </td>
                                <td class="c-table__name small-cart-name">
                                    <ul class="small-cart-list">
                                        <li>
                                            <a class="c-table__link" href="<?php echo SITE . "/" . (isset($item['category_url']) ? $item['category_url'] : 'catalog/') . $item['product_url'] ?>"><?php echo $item['title'] ?></a>
                                            <span class="property"><?php echo $item['property_html'] ?> </span>
                                        </li>
                                        <li class="c-table__quantity qty">
                                            x<?php echo $item['countInCart'] ?>
                                            <span><?php echo $item['priceInCart'] ?></span>
                                        </li>
                                    </ul>
                                </td>
                                <td class="c-table__remove small-cart-remove">
                                    <a href="#" class="deleteItemFromCart" title="<?php echo lang('delete'); ?>"
                                    data-delete-item-id="<?php echo $item['id'] ?>"
                                    data-property="<?php echo $item['property'] ?>"
                                    data-variant="<?php echo $item['variantId'] ?>">
                                    <div class="icon__cart-remove">
                                    <svg class="icon icon--remove"><use xlink:href="#icon--remove"></use></svg>
                                    </div>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                    <?php } else { ?>

                    <?php } ?>
                </table>
            </div> <!-- END table-wrapper -->
            <ul class="total">
                <li class="total-sum"><?php echo lang('cartPay'); ?>
                    <span><?php echo $data['cartData']['cart_price_wc'] ?></span>
                </li>
                <li class="checkout-buttons">
                    <a href="<?php echo SITE ?>/cart" class=""><?php echo lang('cartLink'); ?></a>
                    <a href="<?php echo SITE ?>/order" class="default-btn"><?php echo lang('cartCheckout'); ?></a>
                </li>
            </ul>
        </div> <!-- END small-cart -->
    </div>
</div>
