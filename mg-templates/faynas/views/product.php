<?php
/**
 *  Файл представления Product - выводит сгенерированную движком информацию на странице карточки товара.
 *  В этом файле доступны следующие данные:
 *   <code>
 *   $data['category_url'] => URL категории в которой находится продукт
 *   $data['product_url'] => Полный URL продукта
 *   $data['id'] => id продукта
 *   $data['sort'] => порядок сортировки в каталоге
 *   $data['cat_id'] => id категории
 *   $data['title'] => Наименование товара
 *   $data['description'] => Описание товара
 *   $data['price'] => Стоимость
 *   $data['url'] => URL продукта
 *   $data['image_url'] => Главная картинка товара
 *   $data['code'] => Артикул товара
 *   $data['count'] => Количество товара на складе
 *   $data['activity'] => Флаг активности товара
 *   $data['old_price'] => Старая цена товара
 *   $data['recommend'] => Флаг рекомендуемого товара
 *   $data['new'] => Флаг новинок
 *   $data['thisUserFields'] => Пользовательские характеристики товара
 *   $data['images_product'] => Все изображения товара
 *   $data['currency'] => Валюта магазина.
 *   $data['propertyForm'] => Форма для карточки товара
 *     $data['liteFormData'] => Упрощенная форма для карточки товара
 *   $data['meta_title'] => Значение meta тега для страницы,
 *   $data['meta_keywords'] => Значение meta_keywords тега для страницы,
 *   $data['meta_desc'] => Значение meta_desc тега для страницы,
 *   $data['wholesalesData'] => Информация об оптовых скидках,
 *   $data['storages'] => Информация о складах,
 *   $data['remInfo'] => Информация при отсутсвии товара,
 *   </code>
 *
 *   Получить подробную информацию о каждом элементе массива $data, можно вставив следующую строку кода в верстку файла.
 *   <code>
 *    <?php viewData($data['thisUserFields']); ?>
 *   </code>
 *
 *   Вывести содержание элементов массива $data, можно вставив следующую строку кода в верстку файла.
 *   <code>
 *    <?php echo $data['thisUserFields']; ?>
 *   </code>
 *
 *   <b>Внимание!</b> Файл предназначен только для форматированного вывода данных на страницу магазина. Категорически не рекомендуется выполнять в нем запросы к БД сайта или реализовывать сложную программную логику логику.
 * @author Авдеев Марк <mark-avdeev@mail.ru>
 * @package moguta.cms
 * @subpackage Views
 */
// Установка значений в метатеги title, keywords, description.
mgSEO($data);
?>

<?php if (class_exists('BreadCrumbs')): ?>[brcr]<?php endif; ?>
<div class="c-product product-details-block" itemscope itemtype="http://schema.org/Product">
        <div class="product-status clearfix">
            <div class="mg-product-slides margin">
                <?php mgGalleryProduct($data); ?>
            </div>
            <div class="buy-block">
                <div class="buy-block-inner">
                    <div class="product-bar">
                        <h1 class="product-title" itemprop="name"><?php echo $data['title'] ?></h1>
                        <div>

                            <div class="product-code">
                                <span>
                                <?php echo lang('productCode'); ?> <span class="c-product__code--span label-article code" itemprop="productID"> <?php echo $data['code'] ?></span>
                                </span>
                                <span class="price">
                                    <span content="<?php echo str_replace(' ', '', $data['price'])?>"></span>
                                    <span content="<?php echo $data['currency']; ?>"></span>
                                </span>
                            </div>

                            <div class="product-price">
                                <ul itemprop="offers" itemscope itemtype="http://schema.org/Offer" class="product-status-list">
                                    <li>
                                        <div class="c-product__price c-product__price--current normal-price">
                                            <div class="c-product__price--title">
                                                <?php echo lang('productPrice'); ?>
                                            </div>
                                            <span class="c-product__price--value price">
                                                <span itemprop="price" content="<?php echo MG::numberDeFormat($data['price']);?>"><?php echo $data['price'] ?></span> <span itemprop="priceCurrency"><?php echo $data['currency']; ?></span>
                                            </span>
                                        </div>
                                    </li>
                                    <li <?php echo (!$data['old_price']) ? 'style="display:none"' : 'style="display:block"' ?>>
                                        <div class="c-product__price c-product__price--old old">
                                            <div class="c-product__price--title">
                                                <?php echo lang('productOldPrice'); ?>
                                            </div>
                                            <s class="c-product__price--value old-price">
                                                <?php echo MG::numberFormat($data['old_price']) . " " . $data['currency']; ?>
                                            </s>
                                        </div>
                                    </li>
                                </ul>
                            </div>

                            <div>
                                <?php if (class_exists('Rating')): ?>
                                    <div class="c-product__row">[rating id = "<?php echo $data['id'] ?>"]</div>
                                <?php endif; ?>                                                    
                            </div>

                            <div class="available">
                                <?php layout('count_product', $data); ?>
                                <?php if (class_exists('NonAvailable')): ?>
                                    <div>[non-available id="<?php echo $data['id']?>"]</div>
                                <?php endif; ?>
                                <ul class="product-status-list">
                                    <li <?php echo (!$data['weight']) ? 'style="display:none"' : 'style="display:block"' ?>>
                                        <?php echo lang('productWeight1'); ?> <span class="label-black weight"><?php echo $data['weight'] ?></span> <?php echo lang('productWeight2'); ?>
                                    </li>
                                </ul>                                                                                                           
                            </div>

                        </div>
                        <?php echo MG::layoutManager('layout_wholesales_info', $data['wholesalesData']); ?>
                        <?php echo MG::layoutManager('layout_storage_info', $data); ?>
                        <?php echo $data['propertyForm'] ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="product-details-wrapper">

            <ul class="product-tabs">
                <li><a class="active" href="#c-tab__tab1"><?php echo lang('productDescription'); ?></a></li>
                
                <?php if(!empty($data['stringsProperties'])): ?>
                    <li><a href="#c-tab__property"><?php echo lang('productCharacteristics'); ?></a></li>
                <?php endif; ?>

                <?php if (class_exists('mgTreelikeComments')): ?>
                    <li><a href="#c-tab__tree-comments"><?php echo lang('productComments'); ?></a></li>
                <?php endif; ?>
                
                <?php if (class_exists('CommentsToMoguta')): ?>
                    <li><a href="#c-tab__comments-mg"><?php echo lang('productComments'); ?></a></li>
                <?php endif; ?>

                <?php foreach ($data['thisUserFields'] as $key => $value) {
                    if ($value['type']=='textarea'&&$value['value']) {?>
                        <li><a href="#c-tab__tab<?php echo $key?>"><?php echo $value['name']?></a></li>
                    <?php   }
                }?>
            </ul>
            <div class="product-tabs-container">
                <div id="c-tab__tab1" itemprop="description">
                    <?php echo $data['description'] ?>
                </div>

                <?php if(class_exists('mgTreelikeComments')): ?>
                    <div id="c-tab__tree-comments" itemscope itemtype="http://schema.org/Review">
                        <span style="display: none;" itemprop="itemReviewed" content="<?php echo $data['product_title'] ?>"></span>
                        [mg-treelike-comments type="product"]
                    </div>
                <?php endif; ?>

                <?php if(class_exists('CommentsToMoguta')): ?>
                    <div id="c-tab__comments-mg" itemscope itemtype="http://schema.org/Review">
                        <span style="display: none;" itemprop="itemReviewed" content="<?php echo $data['product_title'] ?>"></span>
                        [comments]
                    </div>
                <?php endif; ?>
                
                <?php if(!empty($data['stringsProperties'])): ?>
                    <div id="c-tab__property">
                        <?php layout('property',$data);?>							
                    </div>
                <?php endif; ?>

                <?php foreach ($data['thisUserFields'] as $key => $value) {
                    if ($value['type']=='textarea') { ?>
                        <div id="c-tab__tab<?php echo $key?>">
                            <?php echo preg_replace('/\<br(\s*)?\/?\>/i', "\n", $value['value'])?>
                        </div>
                    <?php  }
                }?>
            </div>
        </div>
        <div class="l-col min-0--12">
            <?php echo $data['related'] ?> 
        </div>
        <?php if(class_exists('RecentlyViewed')) {?>
        <div class="l-col min-0--12">
            <div class="c-carousel__title">
                <span class="c-carousel__title--span">
                    <?php echo lang('RecentlyViewed'); ?>
                </span>
            </div>
            [recently-viewed countPrint=4 count=5 random=1]
        </div>
        <?php }?>
        <div class="l-col min-0--12">
            <?php if(class_exists('SetGoods')): ?>[set-goods id="<?php echo $data['id']?>"]<?php endif; ?>
        </div>
</div>