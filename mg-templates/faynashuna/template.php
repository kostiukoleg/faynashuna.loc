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
        <header>
        <div class="el-top-header"> 
<div class="uk-container uk-container-center el-container"> 
<a href="#mob-menu" class="uk-navbar-toggle uk-hidden-large" data-uk-offcanvas=""></a>
<div id="mob-menu" class="uk-offcanvas">
<div class="uk-offcanvas-bar">
<ul class="uk-navbar-nav mob">
<li><?php layout("auth"); ?></li>
<li>
<a href="<?php echo SITE?>/registration"><span>Регистрация</span></a>
</li>  
</ul> 
   
<ul class="uk-navbar-nav mob">
<li class=""><a href="http://electro.template.moguta.ru/catalog"><span>Каталог</span></a></li>
<li class=""><a href="http://electro.template.moguta.ru/dostavka"><span>Доставка и оплата</span></a></li>
<li class=""><a href="http://electro.template.moguta.ru/feedback"><span>Обратная связь</span></a></li>
<li class=""><a href="http://electro.template.moguta.ru/contacts"><span>Контакты</span></a></li>
 

<li class="uk-parent" data-uk-dropdown="">
<a href="/brand"><span>Бренды</span></a>
<div class="uk-dropdown uk-dropdown-navbar">     
<ul class="uk-nav uk-nav-dropdown">
</ul>
</div>
</li>
 
   
</ul> <ul class="uk-nav  uk-nav-parent-icon left-menu" data-uk-nav="">
 <li class="">
<a href="http://electro.template.moguta.ru/smartfony">
Смартфоны<span class="showCount">(4)</span>
</a>
</li>
<li class="">
<a href="http://electro.template.moguta.ru/obuv-dlya-detey">
Обувь для детей<span class="showCount">(4)</span>
</a>
</li>
<li class="">
<a href="http://electro.template.moguta.ru/jenskaya-obuv">
Женская обувь<span class="showCount">(3)</span>
</a>
</li>
<li class="">
<a href="http://electro.template.moguta.ru/mujskaya-obuv">
Мужская обувь<span class="showCount">(3)</span>
</a>
</li>
<li class="uk-parent" aria-expanded="false">
<a class="link" href="http://electro.template.moguta.ru/aksessuary">
Аксессуары<span class="showCount">(7)</span> 
</a>  
<a class="sub-link" href="#"></a>
 <div style="overflow:hidden;height:0;position:relative;" class="uk-hidden"><ul class="uk-nav-sub">
 <li class="">
<div class="mg-cat-name">
<a href="http://electro.template.moguta.ru/aksessuary/fitnes-braslety">
Фитнес-браслеты<span class="showCount">(3)</span>
</a>
</div>
</li>
 <li class="">
<div class="mg-cat-name">
<a href="http://electro.template.moguta.ru/aksessuary/chehly-dlya-smartfonov">
Чехлы для смартфонов<span class="showCount">(4)</span>
</a>
</div>
</li>
</ul></div>
</li>
<li class="uk-parent" aria-expanded="false">
<a class="link" href="http://electro.template.moguta.ru/jenskaya-odejda">
Женская одежда<span class="showCount">(6)</span> 
</a>  
<a class="sub-link" href="#"></a>
 <div style="overflow:hidden;height:0;position:relative;" class="uk-hidden"><ul class="uk-nav-sub">
 <li class="">
<div class="mg-cat-name">
<a href="http://electro.template.moguta.ru/jenskaya-odejda/legginsy">
Леггинсы<span class="showCount">(3)</span>
</a>
</div>
</li>
 <li class="">
<div class="mg-cat-name">
<a href="http://electro.template.moguta.ru/jenskaya-odejda/mayki">
Майки<span class="showCount">(3)</span>
</a>
</div>
</li>
</ul></div>
</li>
<li class="uk-parent" aria-expanded="false">
<a class="link" href="http://electro.template.moguta.ru/mujskaya-odejda">
Мужская одежда<span class="showCount">(10)</span> 
</a>  
<a class="sub-link" href="#"></a>
 <div style="overflow:hidden;height:0;position:relative;" class="uk-hidden"><ul class="uk-nav-sub">
 <li class="">
<div class="mg-cat-name">
<a href="http://electro.template.moguta.ru/mujskaya-odejda/futbolki">
Футболки<span class="showCount">(3)</span>
</a>
</div>
</li>
 <li class="">
<div class="mg-cat-name">
<a href="http://electro.template.moguta.ru/mujskaya-odejda/kurtki">
Куртки<span class="showCount">(3)</span>
</a>
</div>
</li>
 <li class="">
<div class="mg-cat-name">
<a href="http://electro.template.moguta.ru/mujskaya-odejda/svitshoty">
Свитшоты<span class="showCount">(4)</span>
</a>
</div>
</li>
</ul></div>
</li>
<li class="">
<a href="http://electro.template.moguta.ru/snapback">
Бейсболки<span class="showCount">(3)</span>
</a>
</li>
</ul>  
</div>  
</div>    
<div class="uk-float-left">
 <div class="mg-contacts-block" itemscope="" itemtype="http://schema.org/Organization">
<div class="phone uk-float-left">
<div class="phone-item" itemprop="telephone">
<i class="uk-icon-phone"></i><a href="tel:8 (555) 555-55-55"> 8 (555) 555-55-55</a>
</div>
</div>
<div class="address uk-float-left uk-hidden-small" itemprop="address" itemscope="" itemtype="http://schema.org/PostalAddress">
<div class="address-item" itemprop="streetAddress">
<i class="uk-icon-map-marker"></i> г. Москва, ул. Тверская, 1. </div>
</div>
<div class="opening uk-float-left uk-visible-large" itemscope="" itemtype="http://schema.org/Store">
<div class="hours-item" itemprop="openingHours"><i class="uk-icon-clock-o"></i>с 10 до 19, без выходных</div>
</div>
</div></div>
<div class="uk-float-right uk-hidden-small"><div class="auth">
<div class="enter-on uk-float-left" data-uk-dropdown="{mode:'click',delay: 1000}">
<a href="#enter"><i class="uk-icon-unlock-alt"></i>Вход</a>
<div class="uk-dropdown">
<form action="http://electro.template.moguta.ru/enter" method="POST">
<ul class="form-list">
<li><input type="text" name="email" placeholder="Email" value=""></li>
<li><input type="password" name="pass" placeholder="Пароль"></li>
</ul>
<a href="http://electro.template.moguta.ru/forgotpass" class="forgot-link">Забыли пароль?</a>
<button type="submit" class="enter-btn default-btn">Войти</button>
</form>
</div>
</div>
<a class="registr" href="http://electro.template.moguta.ru/registration"><i class="uk-icon-user-plus"></i>Регистрация</a>
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
                            <?php layout('language_select'); ?> <!-- блок выбора языка -->
                            <?php layout('currency_select'); ?> <!-- блок выбора валюты -->
                            <div class="l-header__block group">
                                <?php layout('group'); ?> <!-- "новинки", "хиты продаж", "акции" -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="l-header__middle">
                <div class="l-container">
                    <div class="l-row min-0--align-center">
                        <div class="l-col min-0--12 min-768--3">
                            <a  itemprop="logo" class="c-logo" href="<?php echo SITE ?>"><?php echo mgLogo(); ?></a> <!-- логотип -->
                          <p><?php echo MG::getSetting('shopName') ?></p>
                        </div>
                        <div class="l-col min-0--12 min-768--9">
                            <div class="min-0--flex min-0--justify-center min-768--justify-end">
                                <div class="l-header__block">
                                    <?php layout('contacts'); ?> <!-- контакты -->
                                </div>

                                <?php if (MG::getSetting('printCompareButton') == 'true') { ?>
                                    <div class="l-header__block max-767--hide">
                                        <?php layout('compare'); ?> <!-- сравнение товаров -->
                                    </div>
                                <?php } ?>

                                <div class="l-header__block">
                                    <?php layout('auth'); ?> <!-- авторизация на сайте -->
                                </div>
                                <div class="l-header__block">
                                    <?php layout('cart'); ?> <!-- корзина -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="l-header__bottom">
                <div class="l-container">
                    <div class="l-row">
                        <div class="l-col min-0--5 min-768--3">
                            <div class="l-header__block">
                                <?php layout('leftmenu'); ?> <!-- меню каталога -->
                            </div>
                        </div>
                        <div class="l-col min-0--7 min-768--9">
                            <div class="l-header__block">
                                <?php layout('search'); ?> <!-- поиск -->
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