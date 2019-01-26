<?php
/**
 *  Файл представления Catalog - выводит сгенерированную движком информацию на странице сайта с каталогом товаров.
 *  В этом  файле доступны следующие данные:
 *   <code>
 *    $data['items'] => Массив товаров,
 *    $data['titeCategory'] => Название открытой категории,
 *    $data['cat_desc'] => Описание открытой категории,
 *    $data['pager'] => html верстка  для навигации страниц,
 *    $data['searchData'] => Результат поисковой выдачи,
 *    $data['meta_title'] => Значение meta тега для страницы,
 *    $data['meta_keywords'] => Значение meta_keywords тега для страницы,
 *    $data['meta_desc'] => Значение meta_desc тега для страницы,
 *    $data['currency'] => Текущая валюта магазина,
 *    $data['actionButton'] => Тип кнопки в мини карточке товара,
 *    $data['cat_desc_seo'] => SEO описание каталога,
 *    $data['seo_alt'] => Алтернативное подпись изображение категории,
 *    $data['seo_title'] => Title изображения категории
 *   </code>
 *
 *   Получить подробную информацию о каждом элементе массива $data, можно вставив следующую строку кода в верстку файла.
 *   <code>
 *    <?php viewData($data['items']); ?>
 *   </code>
 *
 *   Вывести содержание элементов массива $data, можно вставив следующую строку кода в верстку файла.
 *   <code>
 *    <?php echo $data['items']; ?>
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

<?php if (empty($data['searchData'])): ?>

    <?php if (class_exists('BreadCrumbs')): ?>[brcr]<?php endif; ?>
        <?php if ($cd = str_replace("&nbsp;", "", $data['cat_desc'])): ?>
            <div class="l-col min-0--12 c-description_category">
                <div class="c-description c-description__top">
                    <?php if ($data['cat_img']): ?>
                        <img src="<?php echo SITE . $data['cat_img'] ?>" alt="<?php echo $data['seo_alt'] ?>" title="<?php echo $data['seo_title'] ?>">
                    <?php endif; ?>
                    <?php if (URL::isSection('catalog')||(((MG::getSetting('catalogIndex')=='true') && (URL::isSection('index') || URL::isSection(''))))): ?>
            <!-- Здесь можно добавить описание каталога - информация для пользователей (выводится только на странице каталог (не в категории)) -->
        <?php else :?>
            <?php echo $data['cat_desc'] ?>
        <?php endif;?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (MG::getSetting('picturesCategory') == 'true'): ?>
            <?php echo mgSubCategory($data['cat_id']); ?>
        <?php endif; ?>

        <h1 class="new-products-title"><?php echo $data['titeCategory'] ?></h1>

        <?php layout("apply_filter", $data['applyFilter']); ?>

        <div class="products-wrapper catalog grid">
            <div class="form-group">

                <div class="view-switcher">
                    <span class="form-title">Вид каталога:</span>
                    <div class="btn-group" data-toggle="buttons-radio">
                        <button class="view-btn grid" title="Вид сеткой" data-type="grid"></button>
                        <button class="view-btn list" title="Вид списком" data-type="list"></button>
                    </div>
                </div>
                <div class="count-viewed"></div>
                <div class="clear"></div>

            </div>
            <div class="products-holder clearfix">
                <?php foreach ($data['items'] as $item) {
                    $data['item'] = $item; ?>
                        <?php layout('mini_product', $data); ?>
                <?php } ?>
            </div>

                <div class="clear"></div>
                <?php echo $data['pager']; ?>
                <div class="clear"></div>
 
        </div>

        <?php if($data['cat_desc_seo']){ ?>
        <div class="cat-desc-text">
            <?php echo $data['cat_desc_seo'] ?>
        </div>
        <?php } ?>

    <?php else: ?>

        <style>
            .daily-wrapper{
                display: none;
            }
        </style>

        <h1 class="new-products-title"><?php echo lang('search1'); ?><b class="c-title__search">"<?php echo $data['searchData']['keyword'] ?>"</b><?php echo lang('search2'); ?><b class="c-title__search"><?php echo mgDeclensionNum($data['searchData']['count'], array(lang('search3-1'), lang('search3-2'), lang('search3-3'))); ?></b></h1>

        <div class="products-wrapper catalog list">
            <div class="products-holder clearfix">
                <?php foreach ($data['items'] as $item) {
                    $data['item'] = $item; ?>
                    <?php layout('mini_product', $data); ?>
                <?php } ?>
            </div>
            <div class="clear"></div>
        </div>

        <div class="mg-pager">
            <?php echo $data['pager']; ?>
        </div>
        <div class="clear"></div>

<?php endif;?>