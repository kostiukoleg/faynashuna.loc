<!DOCTYPE html>
<html lang="ru">
    <head>
        <!--[if lte IE 9]>
        <link  rel="stylesheet" type="text/css" href="<?php echo PATH_SITE_TEMPLATE ?>/css/reject/reject.css" />
        <link  rel="stylesheet" type="text/css" href="<?php echo PATH_SITE_TEMPLATE ?>/css/style-ie9.css" />
        <script  src="https://code.jquery.com/jquery-1.12.4.min.js"
        integrity="sha256-ZosEbRLbNQzLpnKIkEdrPv7lOy9C27hHQ+Xp8a4MxAQ="
        crossorigin="anonymous"></script>
        <![endif]-->
      
		<?php mgMeta("meta","css","jquery"); ?>		
        <meta name="format-detection" content="telephone=no">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
        <?php mgAddMeta('<script src="'.PATH_SITE_TEMPLATE.'/js/owl.carousel.min.js"></script>'); ?>
        <?php mgAddMeta('<script src="'.PATH_SITE_TEMPLATE.'/js/jquery.hoverIntent.js"></script>'); ?>
        <?php mgAddMeta('<script src="'.PATH_SITE_TEMPLATE.'/js/script.js"></script>'); ?>

    </head>
    <body class="l-body <?php MG::addBodyClass('l-'); ?>" <?php backgroundSite(); ?>>
        <?php layout('icons'); ?> <!-- svg иконки -->		
		<?php layout('ie9'); ?>
        <table class="c-login">
        <tr>
            <td>
            <?php 
                $page = (empty(str_replace("/","",URL::getClearUri()))) ? "index" : str_replace("/","",URL::getClearUri());
                $html = MG::get('pages')->getPageByUrl($page);
                echo $html["meta_desc"];
            ?>
            </td>
            <td>
                <?php layout('language_select'); ?> <!-- блок выбора языка -->
                <?php layout('currency_select'); ?> <!-- блок выбора валюты -->
            </td>
            <td>   
                <?php layout('auth'); ?> <!-- авторизация на сайте -->
            </td>  
        </tr>
        </table>
        <header class="l-header">
            <div class="l-header__middle">
                <div class="l-container">
                    <table>
                        <tr>
                            <td><?php layout('contacts'); ?> <!-- контакты --></td>
                            <td>                                    
                                <a  itemprop="logo" class="c-logo" href="<?php echo SITE ?>">
                                <?php echo mgLogo(); ?>
                                </a> <!-- логотип -->  
                                <p class="c-logo-under"><?php echo MG::getSetting('shopName') ?></p>
                            </td>
                            <td>
                            <?php if (MG::getSetting('printCompareButton') == 'true') { ?>
                                <?php layout('compare'); ?> <!-- сравнение товаров -->
                            <?php } ?>
                            </td>
                            <td><?php layout('cart'); ?> <!-- корзина --></td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="l-header__bottom">
                <div class="l-container">
                    <div class="l-row">
                        <div class="l-col min-0--12 min-768--12">
                            <div class="l-header__block">
                                <?php layout('search'); ?> <!-- поиск -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="l-header__top">
                <div class="l-container">
                    <div class="l-row">
                        <div class="l-col min-0--3 min-1025--6">
                            <div class="l-header__block">
                                <?php layout('topmenu'); ?> <!-- меню страниц -->
                            </div>
                        </div>
                        <div class="lcg l-col min-0--9 min-1025--6">
                            <div class="l-header__block group">
                                <?php layout('group'); ?> <!-- "новинки", "хиты продаж", "акции" -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>     
    <?php if (URL::isSection(null)): ?>    
    <?php if (class_exists('SliderAction')): ?>
            [slider-action]
    <?php endif; ?>
    <?php endif ?>
        <main class="l-main">
            <div class="l-container">
                <div class="l-row">
                    <div class="l-col min-12--hide min-1025--3 l-main__left">
                    <?php layout('leftmenu'); ?> <!-- меню каталога -->
                    <?php if (class_exists('dailyProduct')): ?>
                        <div class="daily-wrapper">
                                [daily-product]
                        </div>
                    <?php endif; ?>    
                        <div class="c-filter" id="c-filter" onClick="">
                            <div class="c-filter__content">
                                <?php filterCatalogMoguta(); ?> <!-- фильтр -->
                            </div>
                        </div>
                        <?php if (function_exists(sliderProducts)): ?>
                        <div class="mg-advise">
                                <div class="mg-advise__title"><?php echo lang('recommend'); ?></div>
                                    [slider-products countProduct="4" countPrint="1"]
                                 <!-- cлайдер товаров -->
                            </div>  
                        <?php endif ?>                     
                        <?php if (class_exists('PluginNews')): ?>
                        [news-anons count="3"]
                        <?php endif; ?>  
                    </div>
                    <div class="l-col min-0--12 min-1025--9 l-main__right">
                        <div class="l-row">
                            <div class="l-col min-0--12">
                                <?php if (class_exists('BreadCrumbs')&&MG::get('controller')=="controllers_catalog"): ?>[brcr]<?php endif; ?>
                                <?php layout('content'); ?> <!-- содержимое страниц -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <footer class="l-footer">
            <div class="l-container">
                <div class="l-row">
                    <div class="l-col min-0--12 min-768--5">
                        <div class="c-copyright"><?php echo date('Y').' '.lang('copyright'); ?></div> <!-- копирайт -->
                    </div>
                    <div class="l-col min-0--12 min-768--2 min-0--flex min-0--align-center min-0--justify-center max-767--order-end">
                        <div class="c-widget">
                            <?php layout('widget'); ?> <!-- счетчик -->
                        </div>
                    </div>
                    <div class="l-col min-0--12 min-768--5">
                        <div class="c-copyright c-copyright__moguta"><?php copyrightMoguta(); ?></div> <!-- копирайт -->
                    </div>
                </div>
            </div>
        </footer>
		<div class="wrapper">
            <div class="header">
                <div class="top-bar"><div class="centered clearfix">
                <!--Вывод авторизации-->
                <div class="top-auth-block">
                    <div class="selects">
                                                                    </div> 
                    <div class="work-hours">
                        <a href="javascript:void(0)" class="clock-icon"></a>
                        <div class="hours">
                            Пн-Пт. 9:00 - 21:00 Сб. 10:00 - 18:00
                        </div>
                    </div>

                        <a href="http://dress2.template.moguta.ru/enter" class="enter-link">Войти <span class="text">в кабинет</span></a>
                </div>
                <!--/Вывод авторизации-->

                <div class="top-menu-block">
                    <!--Вывод верхнего меню-->
                    <span class="menu-toggle"></span>
<ul class="top-menu-list clearfix">
                                        <li class="">
                <a href="http://dress2.template.moguta.ru/catalog">
                    <span>Каталог</span>
                </a>
            </li>
                                                <li class="">
                <a href="http://dress2.template.moguta.ru/dostavka">
                    <span>Доставка и оплата</span>
                </a>
            </li>
                                                <li class="">
                <a href="http://dress2.template.moguta.ru/feedback">
                    <span>Обратная связь</span>
                </a>
            </li>
                                                <li class="">
                <a href="http://dress2.template.moguta.ru/contacts">
                    <span>Контакты</span>
                </a>
            </li>
                    </ul>                    <!--/Вывод верхнего меню-->
                </div>
            </div>
            </div>  
                <div class="bottom-bar"></div>                   
            </div>
            <div class="container"></div>
        </div>
        <div class="footer">
            <div class="footer-top"></div>
            <div class="footer-bottom"></div>
        </div>
        <?php if (class_exists('BackRing')): ?>[back-ring]<?php endif; ?> <!-- обратный звонок -->		

		<?php mgMeta("js"); ?>
    </body>
</html>
<!DOCTYPE html>
<html lang="ru">
    <head>
        <!--[if lte IE 9]>
        <link  rel="stylesheet" type="text/css" href="<?php echo PATH_SITE_TEMPLATE ?>/css/reject/reject.css" />
        <link  rel="stylesheet" type="text/css" href="<?php echo PATH_SITE_TEMPLATE ?>/css/style-ie9.css" />
        <script  src="https://code.jquery.com/jquery-1.12.4.min.js"
        integrity="sha256-ZosEbRLbNQzLpnKIkEdrPv7lOy9C27hHQ+Xp8a4MxAQ="
        crossorigin="anonymous"></script>
        <![endif]-->
      
		<?php mgMeta("meta","css","jquery"); ?>		
        <meta name="format-detection" content="telephone=no">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
        <?php mgAddMeta('<script src="'.PATH_SITE_TEMPLATE.'/js/owl.carousel.min.js"></script>'); ?>
        <?php mgAddMeta('<script src="'.PATH_SITE_TEMPLATE.'/js/jquery.hoverIntent.js"></script>'); ?>
        <?php mgAddMeta('<script src="'.PATH_SITE_TEMPLATE.'/js/script.js"></script>'); ?>

    </head>
    <body class="l-body <?php MG::addBodyClass('l-'); ?>" <?php backgroundSite(); ?>>
        <?php layout('icons'); ?> <!-- svg иконки -->		
		<?php layout('ie9'); ?>
        <table class="c-login">
        <tr>
            <td>
            <?php 
                $page = (empty(str_replace("/","",URL::getClearUri()))) ? "index" : str_replace("/","",URL::getClearUri());
                $html = MG::get('pages')->getPageByUrl($page);
                echo $html["meta_desc"];
            ?>
            </td>
            <td>
                <?php layout('language_select'); ?> <!-- блок выбора языка -->
                <?php layout('currency_select'); ?> <!-- блок выбора валюты -->
            </td>
            <td>   
                <?php layout('auth'); ?> <!-- авторизация на сайте -->
            </td>  
        </tr>
        </table>
        <header class="l-header">
            <div class="l-header__middle">
                <div class="l-container">
                    <table>
                        <tr>
                            <td><?php layout('contacts'); ?> <!-- контакты --></td>
                            <td>                                    
                                <a  itemprop="logo" class="c-logo" href="<?php echo SITE ?>">
                                <?php echo mgLogo(); ?>
                                </a> <!-- логотип -->  
                                <p class="c-logo-under"><?php echo MG::getSetting('shopName') ?></p>
                            </td>
                            <td>
                            <?php if (MG::getSetting('printCompareButton') == 'true') { ?>
                                <?php layout('compare'); ?> <!-- сравнение товаров -->
                            <?php } ?>
                            </td>
                            <td><?php layout('cart'); ?> <!-- корзина --></td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="l-header__bottom">
                <div class="l-container">
                    <div class="l-row">
                        <div class="l-col min-0--12 min-768--12">
                            <div class="l-header__block">
                                <?php layout('search'); ?> <!-- поиск -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="l-header__top">
                <div class="l-container">
                    <div class="l-row">
                        <div class="l-col min-0--3 min-1025--6">
                            <div class="l-header__block">
                                <?php layout('topmenu'); ?> <!-- меню страниц -->
                            </div>
                        </div>
                        <div class="lcg l-col min-0--9 min-1025--6">
                            <div class="l-header__block group">
                                <?php layout('group'); ?> <!-- "новинки", "хиты продаж", "акции" -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>     
    <?php if (URL::isSection(null)): ?>    
    <?php if (class_exists('SliderAction')): ?>
            [slider-action]
    <?php endif; ?>
    <?php endif ?>
        <main class="l-main">
            <div class="l-container">
                <div class="l-row">
                    <div class="l-col min-12--hide min-1025--3 l-main__left">
                    <?php layout('leftmenu'); ?> <!-- меню каталога -->
                    <?php if (class_exists('dailyProduct')): ?>
                        <div class="daily-wrapper">
                                [daily-product]
                        </div>
                    <?php endif; ?>    
                        <div class="c-filter" id="c-filter" onClick="">
                            <div class="c-filter__content">
                                <?php filterCatalogMoguta(); ?> <!-- фильтр -->
                            </div>
                        </div>
                        <?php if (function_exists(sliderProducts)): ?>
                        <div class="mg-advise">
                                <div class="mg-advise__title"><?php echo lang('recommend'); ?></div>
                                    [slider-products countProduct="4" countPrint="1"]
                                 <!-- cлайдер товаров -->
                            </div>  
                        <?php endif ?>                     
                        <?php if (class_exists('PluginNews')): ?>
                        [news-anons count="3"]
                        <?php endif; ?>  
                    </div>
                    <div class="l-col min-0--12 min-1025--9 l-main__right">
                        <div class="l-row">
                            <div class="l-col min-0--12">
                                <?php if (class_exists('BreadCrumbs')&&MG::get('controller')=="controllers_catalog"): ?>[brcr]<?php endif; ?>
                                <?php layout('content'); ?> <!-- содержимое страниц -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <footer class="l-footer">
            <div class="l-container">
                <div class="l-row">
                    <div class="l-col min-0--12 min-768--5">
                        <div class="c-copyright"><?php echo date('Y').' '.lang('copyright'); ?></div> <!-- копирайт -->
                    </div>
                    <div class="l-col min-0--12 min-768--2 min-0--flex min-0--align-center min-0--justify-center max-767--order-end">
                        <div class="c-widget">
                            <?php layout('widget'); ?> <!-- счетчик -->
                        </div>
                    </div>
                    <div class="l-col min-0--12 min-768--5">
                        <div class="c-copyright c-copyright__moguta"><?php copyrightMoguta(); ?></div> <!-- копирайт -->
                    </div>
                </div>
            </div>
        </footer>
        <?php if (class_exists('BackRing')): ?>[back-ring]<?php endif; ?> <!-- обратный звонок -->		

		<?php mgMeta("js"); ?>
    </body>
</html>