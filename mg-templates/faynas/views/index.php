<?php
/**
 *  Файл представления Index - выводит сгенерированную движком информацию на главной странице магазина.
 *  В этом файле доступны следующие данные:
 *   <code>
 *    $data['recommendProducts'] => Массив рекомендуемых товаров
 *    $data['newProducts'] => Массив товаров новинок
 *    $data['saleProducts'] => Массив товаров распродажи
 *    $data['titeCategory'] => Название категории
 *    $data['cat_desc'] => Описание категории
 *    $data['meta_title'] => Значение meta тега для страницы
 *    $data['meta_keywords'] => Значение meta_keywords тега для страницы
 *    $data['meta_desc'] => Значение meta_desc тега для страницы
 *    $data['currency'] => Текущая валюта магазина
 *    $data['actionButton'] => тип кнопки в мини карточке товара
 *   </code>
 *
 *   Получить подробную информацию о каждом элементе массива $data, можно вставив следующую строку кода в верстку файла.
 *   <code>
 *    <?php viewData($data['saleProducts']); ?>
 *   </code>
 *
 *   Вывести содержание элементов массива $data, можно вставив следующую строку кода в верстку файла.
 *   <code>
 *    <?php echo $data['saleProducts']; ?>
 *   </code>
 *
 *   <b>Внимание!</b> Файл предназначен только для форматированного вывода данных на страницу магазина. Категорически не рекомендуется выполнять в нем запросы к БД сайта или реализовывать сложную программную логику логику.
 * @author Авдеев Марк <mark-avdeev@mail.ru>
 * @package moguta.cms
 * @subpackage Views
 */
// Установка значений в метатеги title, keywords, description.
mgSEO($data);
//viewData($data['newProducts']);
?>

<div class="l-row">
    <?php if (class_exists('trigger')): ?>
    [trigger-guarantee id="1"]
    <?php endif ?>
    <!-- new - start -->
    <?php if (!empty($data['newProducts'])): ?>
        <div class="m-p-products sale">
            <div class="title">
                <a href="<?php echo SITE; ?>/group?type=latest">
                    <?php echo lang('indexNew'); ?>
                </a>
            </div>
            <div class="m-p-products-slider">
                <div class="<?php echo count($data['newProducts']) > 0 ? "m-p-products-slider-start" : "" ?>">
                    <?php foreach ($data['newProducts'] as $item) {
                        $data['item'] = $item;
                        layout('mini_product', $data);
                    } ?>
                </div>
            </div>            
            <div class="clear"></div>
        </div>
    <?php endif; ?>
    <!-- new - end -->
    <div class="middle-banner" style="background: url('<?php echo PATH_SITE_TEMPLATE ?>/images/banners.jpg') center center no-repeat"><!-- фоновая картинка разрешением 1920х400px -->
        <div class="centered clearfix">
            [blog-index id=1] 
        </div>
    </div>
    <!-- hit - start -->
    <?php if (!empty($data['recommendProducts'])): ?>
        <div class="m-p-products sale">
            <div class="title">
                <a href="<?php echo SITE; ?>/group?type=recommend">
                    <?php echo lang('indexHit'); ?>
                </a>
            </div>
            <div class="m-p-products-slider">
                <div class="<?php echo count($data['recommendProducts']) > 0 ? "m-p-products-slider-start" : "" ?>">
                    <?php foreach ($data['recommendProducts'] as $item) {
                        $data['item'] = $item;
                        layout('mini_product', $data);
                    } ?>
                </div>
            </div>            
            <div class="clear"></div>
        </div>
    <?php endif; ?>
    <!-- hit - end -->
    <!-- sales - start -->
    <?php if (!empty($data['saleProducts'])): ?>
        <div class="m-p-products sale">
            <div class="title">
                <a href="<?php echo SITE; ?>/group?type=sale">
                    <?php echo lang('indexSale'); ?>
                </a>
            </div>
            <div class="m-p-products-slider">
                <div class="<?php echo count($data['saleProducts']) > 0 ? "m-p-products-slider-start" : "" ?>">
                    <?php foreach ($data['saleProducts'] as $item) {
                        $data['item'] = $item;
                        layout('mini_product', $data);
                    } ?>
                </div>
            </div>
            <div class="clear"></div>
        </div>
    <?php endif; ?>
    <!-- sales - end -->
    <!-- seo - start -->
        <div class="cat-desc">
            <?php echo $data['cat_desc'] ?>
        </div>
    <!-- seo - end -->

   <!--  blok editor start -->
   <?php if (class_exists('SiteBlockEditor')): ?>
    <div class="two-banners">
        [site-block id=1]
        [site-block id=2]
        [site-block id=3]
    </div>
   <?php endif ?>
   <!--  blok editor end -->  
</div>