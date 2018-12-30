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

<body class="l-body <?php MG::addBodyClass('l-'); ?>j-index" <?php backgroundSite(); ?>>
    <?php layout('icons'); ?>
        <!-- svg иконки -->
        <?php layout('ie9'); ?>

            <div class="wrapper main-page">

                <!--Шапка сайта-->
                <div class="header">
                    <div class="top-bar">
                        <div class="centered clearfix">
                            <!--Вывод авторизации-->
                            <div class="top-auth-block">
                                <div class="selects"></div>
                                
                                <?php if($thisUser = $data['thisUser']): ?>

                                    <a class="enter-link" href="<?php echo SITE?>/personal">
                                        <span class="text"><?php echo lang('authAccount'); ?></span>
                                    </a>

                                    <?php else: ?>

                                        <a class="enter-link" href="<?php echo SITE?>/enter">
                                            <span class="text"><?php echo lang('authAccount'); ?></span>
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
                                            <?php echo lang('mon-fri'); ?>
                                                </span>
                                                <?php echo trim($workTime[0]); ?>
                                                    <?php echo lang('sat-sun'); ?>
                                                        </span>
                                                        <?php echo trim($workTime[1]); ?>
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
                <div class="centered clearfix">    
                <div class="side-block">
                <?php layout('leftmenu'); ?>            
                </div>
                    <!-- показывать на главной -->
                    <!-- плагин слайдер акций -->
                    <?php if (URL::isSection(null)): ?>    
                    <?php if (class_exists('SliderAction')): ?>
                            [slider-action]
                    <?php endif; ?>
                    <?php endif ?>
                    <!-- /плагин слайдер акций -->

                    <!-- плагин триггеры гарантий -->
                    <!-- /плагин триггеры гарантий -->

                        <div class="center">
                            <!-- плагин хлебных крошек -->
                            <div class="bread-crumbs"><?php if (class_exists('BreadCrumbs')&&MG::get('controller')=="controllers_catalog"): ?>[brcr]<?php endif; ?></div>
                            <!-- /плагин хлебных крошек -->
                            <?php layout('content'); ?> <!-- содержимое страниц -->
                            <?php if (URL::isSection(null)): ?> 
                            <!-- баннер с товаром -->
                            <div class="middle-banner" style="background: url(&#39;http://dress2.template.moguta.ru/mg-templates/mg-boutique/images/banners/childrens.jpg&#39;) center center no-repeat">
                                <!-- фоновая картинка разрешением 1920х400px -->
                                <div class="centered clearfix">
                                    <div class="product-item">
                                        <div class="title">
                                            Детская коллекция
                                            <br> от Евгения Зайцева
                                        </div>
                                        <div class="text">
                                            Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis
                                        </div>
                                        <ul class="features-list">
                                            <li>Весна-Лето 2016</li>
                                            <li>24 позиции в каталоге</li>
                                        </ul>
                                        <div class="text-center action">
                                            <a href="javascript:void(0);" class="default-btn">Подробнее</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- баннер с товаром -->
                            <!-- показывать на главной -->
                            <!-- два баннера -->
                            <div class="two-banners">
                                <a href="javascript:void(0);">
                                    <img src="./Главная _ localhost_files/b1_1.png" alt="">
                                </a>
                                <a href="javascript:void(0);">
                                    <img src="./Главная _ localhost_files/b2_2.png" alt="">
                                </a>
                            </div>
                            <!-- /два баннера -->
                            <?php endif; ?> 
                        </div>
                    </div>

                    <!-- показывать на главной -->

                </div>

            <!--Подвал сайта-->
            <div class="footer">
                <div class="footer-top">
                    <div class="centered clearfix">
                        <div class="col">
                            <h2>Сайт</h2>
                            <ul class="footer-column">
                                <li><a href="http://dress2.template.moguta.ru/catalog"><span>Каталог</span></a></li>
                                <li><a href="http://dress2.template.moguta.ru/dostavka"><span>Доставка и оплата</span></a></li>
                            </ul>
                            <ul class="footer-column">
                                <li><a href="http://dress2.template.moguta.ru/feedback"><span>Обратная связь</span></a></li>
                                <li><a href="http://dress2.template.moguta.ru/contacts"><span>Контакты</span></a></li>
                            </ul>
                        </div>
                        <div class="col">
                            <h2>Продукция</h2>
                            <ul class="footer-column">
                                <li><a href="http://dress2.template.moguta.ru/mujskaya-odejda"><span>Мужская одежда</span></a></li>
                                <li><a href="http://dress2.template.moguta.ru/jenskaya-odejda"><span>Женская одежда</span></a></li>
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
<?php mgMeta("js"); ?>
</body>
</html>