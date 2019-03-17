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
    <?php mgAddMeta('<link type="text/css" href="'.PATH_SITE_TEMPLATE.'/css/layout.fake.cart.css" rel="stylesheet"/>');?>
    <?php mgAddMeta('<link type="text/css" href="'.PATH_SITE_TEMPLATE.'/css/layout.cart.css" rel="stylesheet"/>');?>
    <?php mgAddMeta('<link type="text/css" href="'.PATH_SITE_TEMPLATE.'/css/layout.search.css" rel="stylesheet"/>');?>
    <?php mgAddMeta('<script src="'.PATH_SITE_TEMPLATE.'/js/owl.carousel.js"></script>'); ?>
    <?php mgAddMeta('<script src="'.PATH_SITE_TEMPLATE.'/js/jquery.hoverIntent.js"></script>'); ?>
    <?php mgAddMeta('<script src="'.PATH_SITE_TEMPLATE.'/js/script.js"></script>'); ?>
</head>

<body class="l-body <?php MG::addBodyClass('l-'); ?>j-index" <?php backgroundSite(); ?>>
    <?php layout('icons'); ?>
    <!-- svg иконки -->
    <?php layout('ie9'); ?>
    <div class="wrapper">
        <!--Шапка сайта-->
        <div class="header">
            <div class="top-bar">
                <div class="centered clearfix">
                    <!--Вывод авторизации-->
                    <div class="top-auth-block">
                        <div class="selects"></div>
                        
                        <?php if($thisUser = $data['thisUser']): ?>

                        <a class="exit-btn" href="<?php echo SITE?>/enter?logout=1">
                            <span class="text"><?php echo lang('authLogout'); ?></span>
                        </a>

                        <a class="enter-link" href="<?php echo SITE?>/personal">
                            <span class="text"><?php echo $thisUser->name; ?></span>
                        </a>

                        <?php else: ?>

                        <a class="enter-link" href="<?php echo SITE?>/enter">
                            <span class="text"><?php echo lang('authAccountLogin'); ?></span>
                        </a>

                        <?php endif; ?>
                    </div>
                    <div class="top-menu-block">            
                    <?php 
                        $page = (empty(str_replace("/","",URL::getClearUri()))) ? "index" : str_replace("/","",URL::getClearUri());
                        $html = MG::get('pages')->getPageByUrl($page);
                        echo $html["meta_desc"];
                    ?>
                    </div>
                    <!--/Вывод авторизации-->
                </div>
            </div>

            <div class="bottom-bar">
                <div class="centered clearfix">
                    <!--Вывод реквизитов сайта-->
                    <div class="mg-contacts-block" itemscope="" itemtype="http://schema.org/Organization">
                        <div class="address" itemprop="address" itemscope="" itemtype="http://schema.org/PostalAddress">
                            <div class="bold" itemprop="streetAddress">
                                <?php echo MG::getSetting('shopAddress'); ?>
                            </div>
                        </div>
                        <div class="phone">
                            <?php $phones = explode(', ', MG::getSetting('shopPhone')); foreach ($phones as $phone) {?>
                                <div class="bold" itemprop="telephone">
                                    <?php echo $phone; ?>
                                </div>
                            <?php } ?>
                            <?php if (class_exists('BackRing')): ?>
                            [back-ring]
                            <?php endif; ?>
                        </div>
                    </div>
                    <!--/Вывод реквизитов сайта-->

                    <!--Вывод логотипа сайта-->
                    <a href="<?php echo SITE ?>" class="logo">
                        <?php echo mgLogo(); ?>
                    <h2 class="underlogo">
                        <?php echo MG::getSetting('shopName') ?>
                    </h2>
                    </a>

                    <div class="work-hours">
                        <a href="javascript:void(0)" class="clock-icon"></a>
                        <div class="hours">
                            <?php $workTime = explode(',', MG::getSetting('timeWork')); ?>
                            <span>
                                <?php echo lang('mon-fri'); ?>
                                <?php echo trim($workTime[0]); ?>
                                <?php echo lang('sat-sun'); ?>
                                <?php echo trim($workTime[1]); ?>
                            </span>
                            <?php layout('language_select'); ?> <!-- блок выбора языка -->
                            <?php layout('currency_select'); ?> 
                        </div>
                    </div>
                    <!--/Вывод логотипа сайта-->

                    <div class="bar-right clearfix">
                        <!--Вывод аякс поиска-->
                        <?php layout('search'); ?>
                        <!--/Вывод аякс поиска-->

                        <div class="icons-wrapper clearfix">
                            <!--Индикатор сравнения товаров-->
                            <?php layout('compare'); ?>
                            <!--/Индикатор сравнения товаров-->
                            <!--Вывод корзины-->
                            <div class="mg-layer" style="display: none"></div>
                            <?php layout('cart'); ?>
                            <!--/Вывод корзины-->
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--/Шапка сайта-->
        <?php layout('topmenu'); ?>
        <div class="container">
                <!-- плагин слайдер акций -->
                <?php if (URL::isSection(null)): ?> 
                <?php if (class_exists('SliderAction')): ?>
                    [slider-action]
                <?php endif; ?>
                <?php endif; ?>
                <!-- /плагин слайдер акций -->
            <div class="centered clearfix">   
                <?php if (isCatalog() && !isSearch()) : ?> 
                <div class="side-block">
                    <?php layout('leftmenu'); ?>     
                    <div class="filter-block ">
                        <?php filterCatalog(); ?>
                    </div>    
                </div>
                <?php endif; ?>
                <div class="center">
                    <?php layout('content'); ?> <!-- содержимое страниц -->
                </div>
            </div>
        </div>
        <?php if (class_exists('brand') && URL::isSection(null)): ?>
            [brand] 
        <?php endif; ?>
        <!--Подвал сайта-->
        <div class="footer">
            <div class="footer-top">
                <div class="centered clearfix">
                    <div class="col">
                        <h2>Сайт</h2>
                        <?php echo MG::get('pages')->getFooterPagesUl(); ?>
                    </div>
                    <div class="col">
                        <h2>Продукция</h2>
                        <ul class="footer-column">
                            <?php echo MG::get('category')->getCategoryListUl(0, 'public', false); ?>
                        </ul>
                    </div>
                    <div class="col">
                        <h2>Мы принимаем оплату</h2>
                        <img src="<?php echo PATH_SITE_TEMPLATE ?>/images/payments.png" title="Мы принимаем оплату" alt="Мы принимаем оплату">
                    </div>
                    <div class="col">
                        <h2>Мы в соцсетях</h2>
                        <ul class="social-media">
                            <li>
                                <a href="javascript:void(0);" class="vk-icon" title="VKontakte"></a>
                            </li>
                            <li>
                                <a href="javascript:void(0);" class="fb-icon" title="Facebook"></a>
                            </li>
                            <li>
                                <a href="javascript:void(0);" class="tw-icon" title="Twitter"></a>
                            </li>
                        </ul>
                        <div class="widget">
                        <?php layout('widget'); ?> <!-- счетчик -->
                        </div>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <div class="centered clearfix">
                    <div class="copyright"><?php echo date('Y').' '.lang('copyright'); ?></div>

                    <!--Вывод копирайта-->
                    <div class="powered"><?php copyrightMoguta(); ?></div>
                    <!--Вывод копирайта-->
                </div>
            </div>
        </div>
        <!--/Подвал сайта-->	
    </div>	
<?php mgMeta("js"); ?>
</body>
</html>