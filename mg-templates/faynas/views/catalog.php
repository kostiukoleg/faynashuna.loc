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
<?php if (class_exists('BreadCrumbs')): ?>[brcr]<?php endif; ?>
<?php if (empty($data['searchData'])): ?>

    <!-- catalog - start -->
    <div class="center">
        <!-- c-sub - start -->
        <?php if (MG::getSetting('picturesCategory') == 'true'): ?>
            <?php echo mgSubCategory($data['cat_id']); ?>
        <?php endif; ?>
        <!-- c-sub - end -->

        <!-- c-title - start -->
        <h1 class="new-products-title"><?php echo $data['titeCategory'] ?></h1>
        <!-- c-title - end -->

        <!-- c-goods - start -->
        <div class="products-wrapper catalog list">
            <div class="form-group">
                <!-- c-switcher - start -->
                <div class="view-switcher">
                    <span class="form-title">Вид каталога:</span>
                    <div class="btn-group" data-toggle="buttons-radio">
                        <button class="view-btn grid" title="Вид сеткой" data-type="grid"></button>
                        <button class="view-btn list active" title="Вид списком" data-type="list"></button>
                    </div>
                </div>
                <div class="count-viewed"></div>
                <div class="clear"></div>
                <!-- c-switcher - end -->
            </div>
            <div class="products-holder clearfix">
                <?php foreach ($data['items'] as $item) {
                    $data['item'] = $item; ?>
                        <?php layout('mini_product', $data); ?>
                <?php } ?>

                <!-- pager - start -->
                <div class="clear"></div>
                <?php echo $data['pager']; ?>
                <div class="clear"></div>
                <!-- pager - end -->    
            </div>
        </div>
        <!-- c-goods - end -->

        <!-- seo - start -->
        <?php if($data['cat_desc_seo']){ ?>
        <div class="cat-desc-text">
            <?php echo $data['cat_desc_seo'] ?>
        </div>
        <?php } ?>
        <!-- seo - end -->

    </div>
    <!-- catalog - end -->


    <?php else: ?>


    <!-- search - start -->
    <div class="l-row">
        <style>
            .daily-wrapper{
                display: none;
            }
        </style>
        <!-- c-title - start -->
        <div class="l-col min-0--12">
            <h1 class="c-title"><?php echo lang('search1'); ?><b class="c-title__search">"<?php echo $data['searchData']['keyword'] ?>"</b><?php echo lang('search2'); ?><b class="c-title__search"><?php echo mgDeclensionNum($data['searchData']['count'], array(lang('search3-1'), lang('search3-2'), lang('search3-3'))); ?></b></h1>
        </div>
        <!-- c-title - end -->

        <!-- c-goods - start -->
        <div class="l-col min-0--12">
            <div class="c-goods products-wrapper catalog">
                <div class="l-row">
                    <?php foreach ($data['items'] as $item) {
                        $data['item'] = $item; ?>
                        <div class="l-col min-0--6 min-768--4 min-990--3 min-1025--4 c-goods__trigger">
                            <?php layout('mini_product', $data); ?>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
        <!-- c-goods - end -->

        <!-- pager - start -->
        <div class="l-col min-0--12">
            <div class="c-pagination">
                <?php echo $data['pager']; ?>
            </div>
        </div>
        <!-- pager - end -->

    </div>
    <!-- search - end -->

<?php endif;?>