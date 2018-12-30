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
                                        <div class="c-contact__row">
                                            <div class="wrapper-back-ring">
                                                <button type="submit" class="back-ring-button default-btn">
                                                    <?php echo lang('backring'); ?>
                                                </button>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <!--/Вывод реквизитов сайта-->

                            <!--Вывод логотипа сайта-->
                            <a href="<?php echo SITE ?>" class="logo">
                                <?php echo mgLogo(); ?>
                            </a>
                            <!-- <h2 class="underlogo">
                                <?php echo MG::getSetting('shopName') ?>
                            </h2> -->
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

                    <!-- показывать на главной -->
                    <!-- плагин слайдер акций -->
                    <div class="m-p-slider-wrapper" style="width:100%; height:500px; text-align: center;">
                        <div class="bx-wrapper" style="max-width: 100%;">
                            <div class="bx-viewport" style="width: 100%; overflow: hidden; position: relative; height: 355.297px;">
                                <div class="m-p-slider-contain" style="width: auto; position: relative;">
                                    <div class="m-p-slide-unit" style="float: none; list-style: none; position: absolute; width: 1349px; z-index: 0; display: none;">
                                        <a href="http://dress2.template.moguta.ru/catalog"><img src="./Главная _ localhost_files/s1.jpg" alt="" title=""></a>
                                    </div>
                                    <div class="m-p-slide-unit" style="float: none; list-style: none; position: absolute; width: 1349px; z-index: 50;">
                                        <a href="http://dress2.template.moguta.ru/catalog"><img src="./Главная _ localhost_files/s2.jpg" alt="" title=" title=""></a>
                                    </div>
                                </div>
                            </div>
                            <div class="bx-controls bx-has-pager bx-has-controls-direction">
                                <div class="bx-pager bx-default-pager">
                                    <div class="bx-pager-item"><a href="http://dress2.template.moguta.ru/" data-slide-index="0" class="bx-pager-link">1</a></div>
                                    <div class="bx-pager-item"><a href="http://dress2.template.moguta.ru/" data-slide-index="1" class="bx-pager-link active">2</a></div>
                                </div>
                                <div class="bx-controls-direction"><a class="bx-prev" href="http://dress2.template.moguta.ru/">Prev</a><a class="bx-next" href="http://dress2.template.moguta.ru/">Next</a></div>
                            </div>
                        </div>
                    </div>
                    <div class="clear fix-slider-block"></div>

                    <script type="text/javascript">
                        $(document).ready(function() {
                            var sliderItems = $(".m-p-slider-contain .m-p-slide-unit"),
                                checkItemsCount = (sliderItems.length > 1) ? true : false;

                            $(".m-p-slider-contain").bxSlider({
                                minSlides: 1,
                                maxSlides: 1,
                                //pager:true,
                                adaptiveHeight: true,
                                //auto:true,
                                auto: checkItemsCount,
                                pager: checkItemsCount,
                                pause: 1500,
                                useCSS: false,
                                speed: 3000,
                                mode: "fade"
                            });
                        });
                    </script>
                    <div class="after-slider-content"></div>
                    <!-- /плагин слайдер акций -->

                    <!-- плагин триггеры гарантий -->
                    <!-- /плагин триггеры гарантий -->

                    <div class="centered clearfix">

                        <!-- показывать на главной -->
                        <div class="main-banners">
                            <a href="javascript:void(0);">
                                <img src="./Главная _ localhost_files/b1.jpg" alt="">
                            </a>
                            <a href="javascript:void(0);">
                                <img src="./Главная _ localhost_files/b2.jpg" alt="">
                            </a>
                            <a href="javascript:void(0);">
                                <img src="./Главная _ localhost_files/b3.jpg" alt="">
                            </a>
                            <a href="javascript:void(0);">
                                <img src="./Главная _ localhost_files/b4.jpg" alt="">
                            </a>
                        </div>

                        <div class="center">
                            <!-- плагин хлебных крошек -->
                            <div class="bread-crumbs"><a href="http://dress2.template.moguta.ru/catalog">Каталог</a></div>
                            <!-- /плагин хлебных крошек -->

                            <div class="m-p-products latest">
                                <div class="title"><a href="http://dress2.template.moguta.ru/group?type=latest">Новинки</a></div>
                                <div class="m-p-products-slider">
                                    <div class="m-p-products-slider-start owl-carousel owl-theme" style="opacity: 1; display: block;">
                                        <div class="owl-wrapper-outer">
                                            <div class="owl-wrapper" style="width: 2960px; left: 0px; display: block;">
                                                <div class="owl-item" style="width: 296px;">
                                                    <div class="product-wrapper">
                                                        <div class="product-stickers">
                                                            <span class="sticker-new">Новинка</span> </div>
                                                        <div class="product-image">
                                                            <a href="http://dress2.template.moguta.ru/jenskaya-odejda/djempery/djemper-tom-tailor">
                                                                <img class="mg-product-image" itemprop="image" data-transfer="true" data-product-id="11" src="./Главная _ localhost_files/70_zhenskiy-seriy-dzhemper-tom-tailor-tt-30194740970-2528.jpg" alt="Джемпер TOM TAILOR" title="Джемпер TOM TAILOR" data-magnify-src="http://dress2.template.moguta.ru/uploads/product/000/11/zhenskiy-seriy-dzhemper-tom-tailor-tt-30194740970-2528.jpg"> </a>
                                                        </div>

                                                        <div class="product-name">
                                                            <a href="http://dress2.template.moguta.ru/jenskaya-odejda/djempery/djemper-tom-tailor">Джемпер TOM TAILOR</a>
                                                        </div>
                                                        <div class="product-footer clearfix">
                                                            <div class="clearfix">
                                                                <span class="product-price">1 099 руб.</span>
                                                                <div class="mg-rating">
                                                                    <div class="rating-wrapper" itemprop="aggregateRating" itemscope="" itemtype="http://schema.org/AggregateRating">
                                                                        <div class="rating-action" data-rating-id="11">
                                                                            <div class="rateit" data-plugin="stars" data-rateit-value="3.5" data-productid="11" data-rateit-readonly="0">
                                                                                <button id="rateit-reset-2" type="button" data-role="none" class="rateit-reset" aria-label="reset rating" aria-controls="rateit-range-2" style="display: none;"></button>
                                                                                <div id="rateit-range-2" class="rateit-range" tabindex="0" role="slider" aria-label="rating" aria-owns="rateit-reset-2" aria-valuemin="0" aria-valuemax="5" aria-valuenow="3.5" aria-readonly="0" style="width: 80px; height: 16px;">
                                                                                    <div class="rateit-selected" style="height: 16px; width: 56px;"></div>
                                                                                    <div class="rateit-hover" style="height:16px"></div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <span class="info" data-id="11"><span class="mg-rating-count" data-count="11">(<span itemprop="ratingValue">3.5</span>/<span itemprop="ratingCount">17</span>)</span>
                                                                        </span>
                                                                    </div>

                                                                </div>
                                                            </div>

                                                            <div class="product-buttons clearfix">
                                                                <!--Кнопка, кототорая меняет свое значение с "В корзину" на "Подробнее"-->
                                                                <form action="http://dress2.template.moguta.ru/catalog" method="POST" class="property-form actionBuy" data-product-id="11">
                                                                    <div class="buy-container ">

                                                                        <div class="hidder-element">
                                                                            <input type="hidden" name="inCartProductId" value="11">

                                                                            <a href="http://dress2.template.moguta.ru/catalog?inCartProductId=11" rel="nofollow" class="addToCart product-buy" data-item-id="11">Купить</a>

                                                                            <a href="http://dress2.template.moguta.ru/compare?inCompareProductId=11" data-item-id="11" class="addToCompare">
        Сравнить        </a>

                                                                        </div>
                                                                    </div>
                                                                </form>
                                                            </div>

                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="owl-item" style="width: 296px;">
                                                    <div class="product-wrapper">
                                                        <div class="product-stickers">
                                                            <span class="sticker-new">Новинка</span> </div>
                                                        <div class="product-image">
                                                            <a href="http://dress2.template.moguta.ru/jenskaya-odejda/futbolki/futbolka-tom-tailor-denim">
                                                                <img class="mg-product-image" itemprop="image" data-transfer="true" data-product-id="18" src="./Главная _ localhost_files/70_zhenskaya-rozovaya-futbolka-tom-tailor-denim-tt-10323780171-4678.jpg" alt="Футболка TOM TAILOR Denim" title="Футболка TOM TAILOR Denim" data-magnify-src="http://dress2.template.moguta.ru/uploads/product/000/18/zhenskaya-rozovaya-futbolka-tom-tailor-denim-tt-10323780171-4678.jpg"> </a>
                                                        </div>

                                                        <div class="product-name">
                                                            <a href="http://dress2.template.moguta.ru/jenskaya-odejda/futbolki/futbolka-tom-tailor-denim">Футболка TOM TAILOR Denim</a>
                                                        </div>
                                                        <div class="product-footer clearfix">
                                                            <div class="clearfix">
                                                                <span class="product-price">359 руб.</span>
                                                                <div class="mg-rating">
                                                                    <div class="rating-wrapper" itemprop="aggregateRating" itemscope="" itemtype="http://schema.org/AggregateRating">
                                                                        <div class="rating-action" data-rating-id="18">
                                                                            <div class="rateit" data-plugin="stars" data-rateit-value="3.9" data-productid="18" data-rateit-readonly="0">
                                                                                <button id="rateit-reset-3" type="button" data-role="none" class="rateit-reset" aria-label="reset rating" aria-controls="rateit-range-3" style="display: none;"></button>
                                                                                <div id="rateit-range-3" class="rateit-range" tabindex="0" role="slider" aria-label="rating" aria-owns="rateit-reset-3" aria-valuemin="0" aria-valuemax="5" aria-valuenow="3.9" aria-readonly="0" style="width: 80px; height: 16px;">
                                                                                    <div class="rateit-selected" style="height: 16px; width: 62.4px;"></div>
                                                                                    <div class="rateit-hover" style="height:16px"></div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <span class="info" data-id="18"><span class="mg-rating-count" data-count="18">(<span itemprop="ratingValue">3.9</span>/<span itemprop="ratingCount">18</span>)</span>
                                                                        </span>
                                                                    </div>

                                                                </div>
                                                            </div>

                                                            <div class="product-buttons clearfix">
                                                                <!--Кнопка, кототорая меняет свое значение с "В корзину" на "Подробнее"-->
                                                                <form action="http://dress2.template.moguta.ru/catalog" method="POST" class="property-form actionBuy" data-product-id="18">
                                                                    <div class="buy-container ">

                                                                        <div class="hidder-element">
                                                                            <input type="hidden" name="inCartProductId" value="18">

                                                                            <a href="http://dress2.template.moguta.ru/catalog?inCartProductId=18" rel="nofollow" class="addToCart product-buy" data-item-id="18">Купить</a>

                                                                            <a href="http://dress2.template.moguta.ru/compare?inCompareProductId=18" data-item-id="18" class="addToCompare">
        Сравнить        </a>

                                                                        </div>
                                                                    </div>
                                                                </form>
                                                            </div>

                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="owl-item" style="width: 296px;">
                                                    <div class="product-wrapper">
                                                        <div class="product-stickers">
                                                            <span class="sticker-recommend">Хит</span><span class="sticker-new">Новинка</span> </div>
                                                        <div class="product-image">
                                                            <a href="http://dress2.template.moguta.ru/jenskaya-odejda/futbolki/futbolka-tom-tailor">
                                                                <img class="mg-product-image" itemprop="image" data-transfer="true" data-product-id="19" src="./Главная _ localhost_files/70_zhenskaya-krasnaya-futbolka-tom-tailor-tt-10317420070-5533.jpg" alt="Футболка TOM TAILOR" title="Футболка TOM TAILOR" data-magnify-src="http://dress2.template.moguta.ru/uploads/product/000/19/zhenskaya-krasnaya-futbolka-tom-tailor-tt-10317420070-5533.jpg"> </a>
                                                        </div>

                                                        <div class="product-name">
                                                            <a href="http://dress2.template.moguta.ru/jenskaya-odejda/futbolki/futbolka-tom-tailor">Футболка TOM TAILOR</a>
                                                        </div>
                                                        <div class="product-footer clearfix">
                                                            <div class="clearfix">
                                                                <span class="product-price">359 руб.</span>
                                                                <div class="mg-rating">
                                                                    <div class="rating-wrapper" itemprop="aggregateRating" itemscope="" itemtype="http://schema.org/AggregateRating">
                                                                        <div class="rating-action" data-rating-id="19">
                                                                            <div class="rateit" data-plugin="stars" data-rateit-value="3.5" data-productid="19" data-rateit-readonly="0">
                                                                                <button id="rateit-reset-4" type="button" data-role="none" class="rateit-reset" aria-label="reset rating" aria-controls="rateit-range-4" style="display: none;"></button>
                                                                                <div id="rateit-range-4" class="rateit-range" tabindex="0" role="slider" aria-label="rating" aria-owns="rateit-reset-4" aria-valuemin="0" aria-valuemax="5" aria-valuenow="3.5" aria-readonly="0" style="width: 80px; height: 16px;">
                                                                                    <div class="rateit-selected" style="height: 16px; width: 56px;"></div>
                                                                                    <div class="rateit-hover" style="height:16px"></div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <span class="info" data-id="19"><span class="mg-rating-count" data-count="19">(<span itemprop="ratingValue">3.5</span>/<span itemprop="ratingCount">23</span>)</span>
                                                                        </span>
                                                                    </div>

                                                                </div>
                                                            </div>

                                                            <div class="product-buttons clearfix">
                                                                <!--Кнопка, кототорая меняет свое значение с "В корзину" на "Подробнее"-->
                                                                <form action="http://dress2.template.moguta.ru/catalog" method="POST" class="property-form actionBuy" data-product-id="19">
                                                                    <div class="buy-container ">

                                                                        <div class="hidder-element">
                                                                            <input type="hidden" name="inCartProductId" value="19">

                                                                            <a href="http://dress2.template.moguta.ru/catalog?inCartProductId=19" rel="nofollow" class="addToCart product-buy" data-item-id="19">Купить</a>

                                                                            <a href="http://dress2.template.moguta.ru/compare?inCompareProductId=19" data-item-id="19" class="addToCompare">
        Сравнить        </a>

                                                                        </div>
                                                                    </div>
                                                                </form>
                                                            </div>

                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="owl-item" style="width: 296px;">
                                                    <div class="product-wrapper">
                                                        <div class="product-stickers">
                                                            <span class="sticker-new">Новинка</span> </div>
                                                        <div class="product-image">
                                                            <a href="http://dress2.template.moguta.ru/jenskaya-odejda/futbolki/futbolka-tom-tailor-denim_23">
                                                                <img class="mg-product-image" itemprop="image" data-transfer="true" data-product-id="23" src="./Главная _ localhost_files/70_zhenskaya-sinyaya-futbolka-tom-tailor-denim-tt-10287580071-6902.jpg" alt="Футболка TOM TAILOR Denim" title="Футболка TOM TAILOR Denim" data-magnify-src="http://dress2.template.moguta.ru/uploads/product/000/23/zhenskaya-sinyaya-futbolka-tom-tailor-denim-tt-10287580071-6902.jpg"> </a>
                                                        </div>

                                                        <div class="product-name">
                                                            <a href="http://dress2.template.moguta.ru/jenskaya-odejda/futbolki/futbolka-tom-tailor-denim_23">Футболка TOM TAILOR Denim</a>
                                                        </div>
                                                        <div class="product-footer clearfix">
                                                            <div class="clearfix">
                                                                <span class="product-price">299 руб.</span>
                                                                <div class="mg-rating">
                                                                    <div class="rating-wrapper" itemprop="aggregateRating" itemscope="" itemtype="http://schema.org/AggregateRating">
                                                                        <div class="rating-action" data-rating-id="23">
                                                                            <div class="rateit" data-plugin="stars" data-rateit-value="3.4" data-productid="23" data-rateit-readonly="0">
                                                                                <button id="rateit-reset-5" type="button" data-role="none" class="rateit-reset" aria-label="reset rating" aria-controls="rateit-range-5" style="display: none;"></button>
                                                                                <div id="rateit-range-5" class="rateit-range" tabindex="0" role="slider" aria-label="rating" aria-owns="rateit-reset-5" aria-valuemin="0" aria-valuemax="5" aria-valuenow="3.4" aria-readonly="0" style="width: 80px; height: 16px;">
                                                                                    <div class="rateit-selected" style="height: 16px; width: 54.4px;"></div>
                                                                                    <div class="rateit-hover" style="height:16px"></div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <span class="info" data-id="23"><span class="mg-rating-count" data-count="23">(<span itemprop="ratingValue">3.4</span>/<span itemprop="ratingCount">17</span>)</span>
                                                                        </span>
                                                                    </div>

                                                                </div>
                                                            </div>

                                                            <div class="product-buttons clearfix">
                                                                <!--Кнопка, кототорая меняет свое значение с "В корзину" на "Подробнее"-->
                                                                <form action="http://dress2.template.moguta.ru/catalog" method="POST" class="property-form actionBuy" data-product-id="23">
                                                                    <div class="buy-container ">

                                                                        <div class="hidder-element">
                                                                            <input type="hidden" name="inCartProductId" value="23">

                                                                            <a href="http://dress2.template.moguta.ru/catalog?inCartProductId=23" rel="nofollow" class="addToCart product-buy" data-item-id="23">Купить</a>

                                                                            <a href="http://dress2.template.moguta.ru/compare?inCompareProductId=23" data-item-id="23" class="addToCompare">
        Сравнить        </a>

                                                                        </div>
                                                                    </div>
                                                                </form>
                                                            </div>

                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="owl-item" style="width: 296px;">
                                                    <div class="product-wrapper">
                                                        <div class="product-stickers">
                                                            <span class="sticker-new">Новинка</span> </div>
                                                        <div class="product-image">
                                                            <a href="http://dress2.template.moguta.ru/mujskaya-odejda/jilety/jilet-tom-tailor_42">
                                                                <img class="mg-product-image" itemprop="image" data-transfer="true" data-product-id="42" src="./Главная _ localhost_files/70_muzhskoy-zeleniy-zhilet-tom-tailor-tt-35215640010-7528.jpg" alt="Жилет TOM TAILOR" title="Жилет TOM TAILOR" data-magnify-src="http://dress2.template.moguta.ru/uploads/product/000/42/muzhskoy-zeleniy-zhilet-tom-tailor-tt-35215640010-7528.jpg"> </a>
                                                        </div>

                                                        <div class="product-name">
                                                            <a href="http://dress2.template.moguta.ru/mujskaya-odejda/jilety/jilet-tom-tailor_42">Жилет TOM TAILOR</a>
                                                        </div>
                                                        <div class="product-footer clearfix">
                                                            <div class="clearfix">
                                                                <span class="product-price">1 199 руб.</span>
                                                                <div class="mg-rating">
                                                                    <div class="rating-wrapper" itemprop="aggregateRating" itemscope="" itemtype="http://schema.org/AggregateRating">
                                                                        <div class="rating-action" data-rating-id="42">
                                                                            <div class="rateit" data-plugin="stars" data-rateit-value="0" data-productid="42" data-rateit-readonly="0">
                                                                                <button id="rateit-reset-6" type="button" data-role="none" class="rateit-reset" aria-label="reset rating" aria-controls="rateit-range-6" style="display: none;"></button>
                                                                                <div id="rateit-range-6" class="rateit-range" tabindex="0" role="slider" aria-label="rating" aria-owns="rateit-reset-6" aria-valuemin="0" aria-valuemax="5" aria-valuenow="0" aria-readonly="0" style="width: 80px; height: 16px;">
                                                                                    <div class="rateit-selected" style="height: 16px; width: 0px;"></div>
                                                                                    <div class="rateit-hover" style="height:16px"></div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <span class="info" data-id="42"><span class="mg-rating-count" data-count="42">(<span itemprop="ratingValue">0</span>/<span itemprop="ratingCount">0</span>)</span>
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="product-buttons clearfix">
                                                                <!--Кнопка, кототорая меняет свое значение с "В корзину" на "Подробнее"-->
                                                                <form action="http://dress2.template.moguta.ru/catalog" method="POST" class="property-form actionBuy" data-product-id="42">
                                                                    <div class="buy-container ">

                                                                        <div class="hidder-element">
                                                                            <input type="hidden" name="inCartProductId" value="42">

                                                                            <a href="http://dress2.template.moguta.ru/catalog?inCartProductId=42" rel="nofollow" class="addToCart product-buy" data-item-id="42">Купить</a>

                                                                            <a href="http://dress2.template.moguta.ru/compare?inCompareProductId=42" data-item-id="42" class="addToCompare">
        Сравнить        </a>

                                                                        </div>
                                                                    </div>
                                                                </form>
                                                            </div>

                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="owl-controls clickable">
                                            <div class="owl-buttons">
                                                <div class="owl-prev">prev</div>
                                                <div class="owl-next">next</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="clear"></div>
                            </div>

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

                            <div class="m-p-products recommend">
                                <div class="title"><a href="http://dress2.template.moguta.ru/group?type=recommend">Хиты продаж</a></div>
                                <div class="m-p-products-slider">
                                    <div class="m-p-products-slider-start owl-carousel owl-theme" style="opacity: 1; display: block;">
                                        <div class="owl-wrapper-outer">
                                            <div class="owl-wrapper" style="width: 2368px; left: 0px; display: block;">
                                                <div class="owl-item" style="width: 296px;">
                                                    <div class="product-wrapper">
                                                        <div class="product-stickers">
                                                            <span class="sticker-recommend">Хит</span> </div>
                                                        <div class="product-image">
                                                            <a href="http://dress2.template.moguta.ru/jenskaya-odejda/platya/plate-fracomina">
                                                                <img class="mg-product-image" itemprop="image" data-transfer="true" data-product-id="2" src="./Главная _ localhost_files/70_zhenskoe-sinee-plate-fracomina-fra-fr15fw8024-117.jpg" alt="Платье Fracomina" title="Платье Fracomina" data-magnify-src="http://dress2.template.moguta.ru/uploads/product/000/2/zhenskoe-sinee-plate-fracomina-fra-fr15fw8024-117.jpg"> </a>
                                                        </div>

                                                        <div class="product-name">
                                                            <a href="http://dress2.template.moguta.ru/jenskaya-odejda/platya/plate-fracomina">Платье Fracomina</a>
                                                        </div>
                                                        <div class="product-footer clearfix">
                                                            <div class="clearfix">
                                                                <span class="product-price">1 299 руб.</span>
                                                                <div class="mg-rating">
                                                                    <div class="rating-wrapper" itemprop="aggregateRating" itemscope="" itemtype="http://schema.org/AggregateRating">
                                                                        <div class="rating-action" data-rating-id="2">
                                                                            <div class="rateit" data-plugin="stars" data-rateit-value="3.7" data-productid="2" data-rateit-readonly="0">
                                                                                <button id="rateit-reset-7" type="button" data-role="none" class="rateit-reset" aria-label="reset rating" aria-controls="rateit-range-7" style="display: none;"></button>
                                                                                <div id="rateit-range-7" class="rateit-range" tabindex="0" role="slider" aria-label="rating" aria-owns="rateit-reset-7" aria-valuemin="0" aria-valuemax="5" aria-valuenow="3.7" aria-readonly="0" style="width: 80px; height: 16px;">
                                                                                    <div class="rateit-selected" style="height: 16px; width: 59.2px;"></div>
                                                                                    <div class="rateit-hover" style="height:16px"></div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <span class="info" data-id="2"><span class="mg-rating-count" data-count="2">(<span itemprop="ratingValue">3.7</span>/<span itemprop="ratingCount">9</span>)</span>
                                                                        </span>
                                                                    </div>

                                                                </div>
                                                            </div>

                                                            <div class="product-buttons clearfix">
                                                                <!--Кнопка, кототорая меняет свое значение с "В корзину" на "Подробнее"-->
                                                                <form action="http://dress2.template.moguta.ru/catalog" method="POST" class="property-form actionBuy" data-product-id="2">
                                                                    <div class="buy-container ">

                                                                        <div class="hidder-element">
                                                                            <input type="hidden" name="inCartProductId" value="2">

                                                                            <a href="http://dress2.template.moguta.ru/catalog?inCartProductId=2" rel="nofollow" class="addToCart product-buy" data-item-id="2">Купить</a>

                                                                            <a href="http://dress2.template.moguta.ru/compare?inCompareProductId=2" data-item-id="2" class="addToCompare">
        Сравнить        </a>

                                                                        </div>
                                                                    </div>
                                                                </form>
                                                            </div>

                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="owl-item" style="width: 296px;">
                                                    <div class="product-wrapper">
                                                        <div class="product-stickers">
                                                            <span class="sticker-recommend">Хит</span> </div>
                                                        <div class="product-image">
                                                            <a href="http://dress2.template.moguta.ru/jenskaya-odejda/djempery/djemper-fracomina">
                                                                <img class="mg-product-image" itemprop="image" data-transfer="true" data-product-id="13" src="./Главная _ localhost_files/70_zhenskiy-siniy-dzhemper-fracomina-fra-fr15fw8021-941.jpg" alt="Джемпер Fracomina" title="Джемпер Fracomina" data-magnify-src="http://dress2.template.moguta.ru/uploads/product/000/13/zhenskiy-siniy-dzhemper-fracomina-fra-fr15fw8021-941.jpg"> </a>
                                                        </div>

                                                        <div class="product-name">
                                                            <a href="http://dress2.template.moguta.ru/jenskaya-odejda/djempery/djemper-fracomina">Джемпер Fracomina</a>
                                                        </div>
                                                        <div class="product-footer clearfix">
                                                            <div class="clearfix">
                                                                <span class="product-price">1 299 руб.</span>
                                                                <div class="mg-rating">
                                                                    <div class="rating-wrapper" itemprop="aggregateRating" itemscope="" itemtype="http://schema.org/AggregateRating">
                                                                        <div class="rating-action" data-rating-id="13">
                                                                            <div class="rateit" data-plugin="stars" data-rateit-value="3.2" data-productid="13" data-rateit-readonly="0">
                                                                                <button id="rateit-reset-8" type="button" data-role="none" class="rateit-reset" aria-label="reset rating" aria-controls="rateit-range-8" style="display: none;"></button>
                                                                                <div id="rateit-range-8" class="rateit-range" tabindex="0" role="slider" aria-label="rating" aria-owns="rateit-reset-8" aria-valuemin="0" aria-valuemax="5" aria-valuenow="3.2" aria-readonly="0" style="width: 80px; height: 16px;">
                                                                                    <div class="rateit-selected" style="height: 16px; width: 51.2px;"></div>
                                                                                    <div class="rateit-hover" style="height:16px"></div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <span class="info" data-id="13"><span class="mg-rating-count" data-count="13">(<span itemprop="ratingValue">3.2</span>/<span itemprop="ratingCount">7</span>)</span>
                                                                        </span>
                                                                    </div>

                                                                </div>
                                                            </div>

                                                            <div class="product-buttons clearfix">
                                                                <!--Кнопка, кототорая меняет свое значение с "В корзину" на "Подробнее"-->
                                                                <form action="http://dress2.template.moguta.ru/catalog" method="POST" class="property-form actionBuy" data-product-id="13">
                                                                    <div class="buy-container ">

                                                                        <div class="hidder-element">
                                                                            <input type="hidden" name="inCartProductId" value="13">

                                                                            <a href="http://dress2.template.moguta.ru/catalog?inCartProductId=13" rel="nofollow" class="addToCart product-buy" data-item-id="13">Купить</a>

                                                                            <a href="http://dress2.template.moguta.ru/compare?inCompareProductId=13" data-item-id="13" class="addToCompare">
        Сравнить        </a>

                                                                        </div>
                                                                    </div>
                                                                </form>
                                                            </div>

                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="owl-item" style="width: 296px;">
                                                    <div class="product-wrapper">
                                                        <div class="product-stickers">
                                                            <span class="sticker-recommend">Хит</span><span class="sticker-new">Новинка</span> </div>
                                                        <div class="product-image">
                                                            <a href="http://dress2.template.moguta.ru/jenskaya-odejda/futbolki/futbolka-tom-tailor">
                                                                <img class="mg-product-image" itemprop="image" data-transfer="true" data-product-id="19" src="./Главная _ localhost_files/70_zhenskaya-krasnaya-futbolka-tom-tailor-tt-10317420070-5533.jpg" alt="Футболка TOM TAILOR" title="Футболка TOM TAILOR" data-magnify-src="http://dress2.template.moguta.ru/uploads/product/000/19/zhenskaya-krasnaya-futbolka-tom-tailor-tt-10317420070-5533.jpg"> </a>
                                                        </div>

                                                        <div class="product-name">
                                                            <a href="http://dress2.template.moguta.ru/jenskaya-odejda/futbolki/futbolka-tom-tailor">Футболка TOM TAILOR</a>
                                                        </div>
                                                        <div class="product-footer clearfix">
                                                            <div class="clearfix">
                                                                <span class="product-price">359 руб.</span>
                                                                <div class="mg-rating">
                                                                    <div class="rating-wrapper" itemprop="aggregateRating" itemscope="" itemtype="http://schema.org/AggregateRating">
                                                                        <div class="rating-action" data-rating-id="19">
                                                                            <div class="rateit" data-plugin="stars" data-rateit-value="3.5" data-productid="19" data-rateit-readonly="0">
                                                                                <button id="rateit-reset-9" type="button" data-role="none" class="rateit-reset" aria-label="reset rating" aria-controls="rateit-range-9" style="display: none;"></button>
                                                                                <div id="rateit-range-9" class="rateit-range" tabindex="0" role="slider" aria-label="rating" aria-owns="rateit-reset-9" aria-valuemin="0" aria-valuemax="5" aria-valuenow="3.5" aria-readonly="0" style="width: 80px; height: 16px;">
                                                                                    <div class="rateit-selected" style="height: 16px; width: 56px;"></div>
                                                                                    <div class="rateit-hover" style="height:16px"></div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <span class="info" data-id="19"><span class="mg-rating-count" data-count="19">(<span itemprop="ratingValue">3.5</span>/<span itemprop="ratingCount">23</span>)</span>
                                                                        </span>
                                                                    </div>

                                                                </div>
                                                            </div>

                                                            <div class="product-buttons clearfix">
                                                                <!--Кнопка, кототорая меняет свое значение с "В корзину" на "Подробнее"-->
                                                                <form action="http://dress2.template.moguta.ru/catalog" method="POST" class="property-form actionBuy" data-product-id="19">
                                                                    <div class="buy-container ">

                                                                        <div class="hidder-element">
                                                                            <input type="hidden" name="inCartProductId" value="19">

                                                                            <a href="http://dress2.template.moguta.ru/catalog?inCartProductId=19" rel="nofollow" class="addToCart product-buy" data-item-id="19">Купить</a>

                                                                            <a href="http://dress2.template.moguta.ru/compare?inCompareProductId=19" data-item-id="19" class="addToCompare">
        Сравнить        </a>

                                                                        </div>
                                                                    </div>
                                                                </form>
                                                            </div>

                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="owl-item" style="width: 296px;">
                                                    <div class="product-wrapper">
                                                        <div class="product-stickers">
                                                            <span class="sticker-recommend">Хит</span> </div>
                                                        <div class="product-image">
                                                            <a href="http://dress2.template.moguta.ru/mujskaya-odejda/jilety/jilet-tom-tailor_43">
                                                                <img class="mg-product-image" itemprop="image" data-transfer="true" data-product-id="43" src="./Главная _ localhost_files/70_muzhskoy-siniy-zhilet-tom-tailor-tt-35208680010-6000.jpg" alt="Жилет TOM TAILOR" title="Жилет TOM TAILOR" data-magnify-src="http://dress2.template.moguta.ru/uploads/product/000/43/muzhskoy-siniy-zhilet-tom-tailor-tt-35208680010-6000.jpg"> </a>
                                                        </div>

                                                        <div class="product-name">
                                                            <a href="http://dress2.template.moguta.ru/mujskaya-odejda/jilety/jilet-tom-tailor_43">Жилет TOM TAILOR</a>
                                                        </div>
                                                        <div class="product-footer clearfix">
                                                            <div class="clearfix">
                                                                <span class="product-price">1 199 руб.</span>
                                                                <div class="mg-rating">
                                                                    <div class="rating-wrapper" itemprop="aggregateRating" itemscope="" itemtype="http://schema.org/AggregateRating">
                                                                        <div class="rating-action" data-rating-id="43">
                                                                            <div class="rateit" data-plugin="stars" data-rateit-value="3.5" data-productid="43" data-rateit-readonly="0">
                                                                                <button id="rateit-reset-10" type="button" data-role="none" class="rateit-reset" aria-label="reset rating" aria-controls="rateit-range-10" style="display: none;"></button>
                                                                                <div id="rateit-range-10" class="rateit-range" tabindex="0" role="slider" aria-label="rating" aria-owns="rateit-reset-10" aria-valuemin="0" aria-valuemax="5" aria-valuenow="3.5" aria-readonly="0" style="width: 80px; height: 16px;">
                                                                                    <div class="rateit-selected" style="height: 16px; width: 56px;"></div>
                                                                                    <div class="rateit-hover" style="height:16px"></div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <span class="info" data-id="43"><span class="mg-rating-count" data-count="43">(<span itemprop="ratingValue">3.5</span>/<span itemprop="ratingCount">4</span>)</span>
                                                                        </span>
                                                                    </div>

                                                                </div>
                                                            </div>

                                                            <div class="product-buttons clearfix">
                                                                <!--Кнопка, кототорая меняет свое значение с "В корзину" на "Подробнее"-->
                                                                <form action="http://dress2.template.moguta.ru/catalog" method="POST" class="property-form actionBuy" data-product-id="43">
                                                                    <div class="buy-container ">

                                                                        <div class="hidder-element">
                                                                            <input type="hidden" name="inCartProductId" value="43">

                                                                            <a href="http://dress2.template.moguta.ru/catalog?inCartProductId=43" rel="nofollow" class="addToCart product-buy" data-item-id="43">Купить</a>

                                                                            <a href="http://dress2.template.moguta.ru/compare?inCompareProductId=43" data-item-id="43" class="addToCompare">
        Сравнить        </a>

                                                                        </div>
                                                                    </div>
                                                                </form>
                                                            </div>

                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="owl-controls clickable" style="display: none;">
                                            <div class="owl-buttons">
                                                <div class="owl-prev">prev</div>
                                                <div class="owl-next">next</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="clear"></div>
                            </div>

                            <div class="m-p-products sale">
                                <div class="title"><a href="http://dress2.template.moguta.ru/group?type=sale">Распродажа</a></div>
                                <div class="m-p-products-slider">
                                    <div class="m-p-products-slider-start owl-carousel owl-theme" style="opacity: 1; display: block;">

                                        <div class="owl-wrapper-outer">
                                            <div class="owl-wrapper" style="width: 2960px; left: 0px; display: block;">
                                                <div class="owl-item" style="width: 296px;">
                                                    <div class="product-wrapper">
                                                        <div class="product-stickers">
                                                        </div>
                                                        <div class="product-image">
                                                            <a href="http://dress2.template.moguta.ru/jenskaya-odejda/platya/plate-mr520">
                                                                <img class="mg-product-image" itemprop="image" data-transfer="true" data-product-id="4" src="./Главная _ localhost_files/70_zhenskoe-sinee-plate-mr520-mr-229-2029-0815-dark-blue.jpg" alt="Платье MR520" title="Платье MR520" data-magnify-src="http://dress2.template.moguta.ru/uploads/product/000/4/zhenskoe-sinee-plate-mr520-mr-229-2029-0815-dark-blue.jpg"> </a>
                                                        </div>

                                                        <div class="product-name">
                                                            <a href="http://dress2.template.moguta.ru/jenskaya-odejda/platya/plate-mr520">Платье MR520</a>
                                                        </div>
                                                        <div class="product-footer clearfix">
                                                            <div class="clearfix">
                                                                <span class="product-price">999 руб.</span>
                                                                <div class="mg-rating">
                                                                    <div class="rating-wrapper" itemprop="aggregateRating" itemscope="" itemtype="http://schema.org/AggregateRating">
                                                                        <div class="rating-action" data-rating-id="4">
                                                                            <div class="rateit" data-plugin="stars" data-rateit-value="2.8" data-productid="4" data-rateit-readonly="0">
                                                                                <button id="rateit-reset-11" type="button" data-role="none" class="rateit-reset" aria-label="reset rating" aria-controls="rateit-range-11" style="display: none;"></button>
                                                                                <div id="rateit-range-11" class="rateit-range" tabindex="0" role="slider" aria-label="rating" aria-owns="rateit-reset-11" aria-valuemin="0" aria-valuemax="5" aria-valuenow="2.8" aria-readonly="0" style="width: 80px; height: 16px;">
                                                                                    <div class="rateit-selected" style="height: 16px; width: 44.8px;"></div>
                                                                                    <div class="rateit-hover" style="height:16px"></div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <span class="info" data-id="4"><span class="mg-rating-count" data-count="4">(<span itemprop="ratingValue">2.8</span>/<span itemprop="ratingCount">9</span>)</span>
                                                                        </span>
                                                                    </div>

                                                                </div>
                                                            </div>

                                                            <div class="product-buttons clearfix">
                                                                <!--Кнопка, кототорая меняет свое значение с "В корзину" на "Подробнее"-->
                                                                <form action="http://dress2.template.moguta.ru/catalog" method="POST" class="property-form actionBuy" data-product-id="4">
                                                                    <div class="buy-container ">

                                                                        <div class="hidder-element">
                                                                            <input type="hidden" name="inCartProductId" value="4">

                                                                            <a href="http://dress2.template.moguta.ru/catalog?inCartProductId=4" rel="nofollow" class="addToCart product-buy" data-item-id="4">Купить</a>

                                                                            <a href="http://dress2.template.moguta.ru/compare?inCompareProductId=4" data-item-id="4" class="addToCompare">
        Сравнить        </a>

                                                                        </div>
                                                                    </div>
                                                                </form>
                                                            </div>

                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="owl-item" style="width: 296px;">
                                                    <div class="product-wrapper">
                                                        <div class="product-stickers">
                                                        </div>
                                                        <div class="product-image">
                                                            <a href="http://dress2.template.moguta.ru/jenskaya-odejda/djempery/djemper-only_17">
                                                                <img class="mg-product-image" itemprop="image" data-transfer="true" data-product-id="17" src="./Главная _ localhost_files/70_zhenskiy-cherniy-dzhemper-only-on-15103626-black.jpg" alt="Джемпер Only" title="Джемпер Only" data-magnify-src="http://dress2.template.moguta.ru/uploads/product/000/17/zhenskiy-cherniy-dzhemper-only-on-15103626-black.jpg"> </a>
                                                        </div>

                                                        <div class="product-name">
                                                            <a href="http://dress2.template.moguta.ru/jenskaya-odejda/djempery/djemper-only_17">Джемпер Only</a>
                                                        </div>
                                                        <div class="product-footer clearfix">
                                                            <div class="clearfix">
                                                                <span class="product-price">859 руб.</span>
                                                                <div class="mg-rating">
                                                                    <div class="rating-wrapper" itemprop="aggregateRating" itemscope="" itemtype="http://schema.org/AggregateRating">
                                                                        <div class="rating-action" data-rating-id="17">
                                                                            <div class="rateit" data-plugin="stars" data-rateit-value="3.5" data-productid="17" data-rateit-readonly="0">
                                                                                <button id="rateit-reset-12" type="button" data-role="none" class="rateit-reset" aria-label="reset rating" aria-controls="rateit-range-12" style="display: none;"></button>
                                                                                <div id="rateit-range-12" class="rateit-range" tabindex="0" role="slider" aria-label="rating" aria-owns="rateit-reset-12" aria-valuemin="0" aria-valuemax="5" aria-valuenow="3.5" aria-readonly="0" style="width: 80px; height: 16px;">
                                                                                    <div class="rateit-selected" style="height: 16px; width: 56px;"></div>
                                                                                    <div class="rateit-hover" style="height:16px"></div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <span class="info" data-id="17"><span class="mg-rating-count" data-count="17">(<span itemprop="ratingValue">3.5</span>/<span itemprop="ratingCount">7</span>)</span>
                                                                        </span>
                                                                    </div>

                                                                </div>
                                                            </div>

                                                            <div class="product-buttons clearfix">
                                                                <!--Кнопка, кототорая меняет свое значение с "В корзину" на "Подробнее"-->
                                                                <form action="http://dress2.template.moguta.ru/catalog" method="POST" class="property-form actionBuy" data-product-id="17">
                                                                    <div class="buy-container ">

                                                                        <div class="hidder-element">
                                                                            <input type="hidden" name="inCartProductId" value="17">

                                                                            <a href="http://dress2.template.moguta.ru/catalog?inCartProductId=17" rel="nofollow" class="addToCart product-buy" data-item-id="17">Купить</a>

                                                                            <a href="http://dress2.template.moguta.ru/compare?inCompareProductId=17" data-item-id="17" class="addToCompare">
        Сравнить        </a>

                                                                        </div>
                                                                    </div>
                                                                </form>
                                                            </div>

                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="owl-item" style="width: 296px;">
                                                    <div class="product-wrapper">
                                                        <div class="product-stickers">
                                                        </div>
                                                        <div class="product-image">
                                                            <a href="http://dress2.template.moguta.ru/mujskaya-odejda/jilety/jilet-tom-tailor_40">
                                                                <img class="mg-product-image" itemprop="image" data-transfer="true" data-product-id="40" src="./Главная _ localhost_files/70_muzhskoy-goluboy-zhilet-tom-tailor-tt-35215690010-6753.jpg" alt="Жилет TOM TAILOR" title="Жилет TOM TAILOR" data-magnify-src="http://dress2.template.moguta.ru/uploads/product/000/40/muzhskoy-goluboy-zhilet-tom-tailor-tt-35215690010-6753.jpg"> </a>
                                                        </div>

                                                        <div class="product-name">
                                                            <a href="http://dress2.template.moguta.ru/mujskaya-odejda/jilety/jilet-tom-tailor_40">Жилет TOM TAILOR</a>
                                                        </div>
                                                        <div class="product-footer clearfix">
                                                            <div class="clearfix">
                                                                <span class="product-price">1 199 руб.</span>
                                                                <div class="mg-rating">
                                                                    <div class="rating-wrapper" itemprop="aggregateRating" itemscope="" itemtype="http://schema.org/AggregateRating">
                                                                        <div class="rating-action" data-rating-id="40">
                                                                            <div class="rateit" data-plugin="stars" data-rateit-value="3" data-productid="40" data-rateit-readonly="0">
                                                                                <button id="rateit-reset-13" type="button" data-role="none" class="rateit-reset" aria-label="reset rating" aria-controls="rateit-range-13" style="display: none;"></button>
                                                                                <div id="rateit-range-13" class="rateit-range" tabindex="0" role="slider" aria-label="rating" aria-owns="rateit-reset-13" aria-valuemin="0" aria-valuemax="5" aria-valuenow="3" aria-readonly="0" style="width: 80px; height: 16px;">
                                                                                    <div class="rateit-selected" style="height: 16px; width: 48px;"></div>
                                                                                    <div class="rateit-hover" style="height: 16px; width: 0px; display: none;"></div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <span class="info" data-id="40"><span class="mg-rating-count" data-count="40">(<span itemprop="ratingValue">3</span>/<span itemprop="ratingCount">5</span>)</span>
                                                                        </span>
                                                                    </div>

                                                                </div>
                                                            </div>

                                                            <div class="product-buttons clearfix">
                                                                <!--Кнопка, кототорая меняет свое значение с "В корзину" на "Подробнее"-->
                                                                <form action="http://dress2.template.moguta.ru/catalog" method="POST" class="property-form actionBuy" data-product-id="40">
                                                                    <div class="buy-container ">

                                                                        <div class="hidder-element">
                                                                            <input type="hidden" name="inCartProductId" value="40">

                                                                            <a href="http://dress2.template.moguta.ru/catalog?inCartProductId=40" rel="nofollow" class="addToCart product-buy" data-item-id="40">Купить</a>

                                                                            <a href="http://dress2.template.moguta.ru/compare?inCompareProductId=40" data-item-id="40" class="addToCompare">
        Сравнить        </a>

                                                                        </div>
                                                                    </div>
                                                                </form>
                                                            </div>

                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="owl-item" style="width: 296px;">
                                                    <div class="product-wrapper">
                                                        <div class="product-stickers">
                                                        </div>
                                                        <div class="product-image">
                                                            <a href="http://dress2.template.moguta.ru/mujskaya-odejda/shorty/shorty-tom-tailor">
                                                                <img class="mg-product-image" itemprop="image" data-transfer="true" data-product-id="55" src="./Главная _ localhost_files/70_muzhskie-zelenie-shorti-tom-tailor-tt-64029340010-7585.jpg" alt="Шорты TOM TAILOR" title="Шорты TOM TAILOR" data-magnify-src="http://dress2.template.moguta.ru/uploads/product/000/55/muzhskie-zelenie-shorti-tom-tailor-tt-64029340010-7585.jpg"> </a>
                                                        </div>

                                                        <div class="product-name">
                                                            <a href="http://dress2.template.moguta.ru/mujskaya-odejda/shorty/shorty-tom-tailor">Шорты TOM TAILOR</a>
                                                        </div>
                                                        <div class="product-footer clearfix">
                                                            <div class="clearfix">
                                                                <span class="product-price">499 руб.</span>
                                                                <div class="mg-rating">
                                                                    <div class="rating-wrapper" itemprop="aggregateRating" itemscope="" itemtype="http://schema.org/AggregateRating">
                                                                        <div class="rating-action" data-rating-id="55">
                                                                            <div class="rateit" data-plugin="stars" data-rateit-value="4.1" data-productid="55" data-rateit-readonly="0">
                                                                                <button id="rateit-reset-14" type="button" data-role="none" class="rateit-reset" aria-label="reset rating" aria-controls="rateit-range-14" style="display: none;"></button>
                                                                                <div id="rateit-range-14" class="rateit-range" tabindex="0" role="slider" aria-label="rating" aria-owns="rateit-reset-14" aria-valuemin="0" aria-valuemax="5" aria-valuenow="4.1" aria-readonly="0" style="width: 80px; height: 16px;">
                                                                                    <div class="rateit-selected" style="height: 16px; width: 65.6px;"></div>
                                                                                    <div class="rateit-hover" style="height:16px"></div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <span class="info" data-id="55"><span class="mg-rating-count" data-count="55">(<span itemprop="ratingValue">4.1</span>/<span itemprop="ratingCount">4</span>)</span>
                                                                        </span>
                                                                    </div>

                                                                </div>
                                                            </div>

                                                            <div class="product-buttons clearfix">
                                                                <!--Кнопка, кототорая меняет свое значение с "В корзину" на "Подробнее"-->
                                                                <form action="http://dress2.template.moguta.ru/catalog" method="POST" class="property-form actionBuy" data-product-id="55">
                                                                    <div class="buy-container ">

                                                                        <div class="hidder-element">
                                                                            <input type="hidden" name="inCartProductId" value="55">

                                                                            <a href="http://dress2.template.moguta.ru/catalog?inCartProductId=55" rel="nofollow" class="addToCart product-buy" data-item-id="55">Купить</a>

                                                                            <a href="http://dress2.template.moguta.ru/compare?inCompareProductId=55" data-item-id="55" class="addToCompare">
        Сравнить        </a>

                                                                        </div>
                                                                    </div>
                                                                </form>
                                                            </div>

                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="owl-item" style="width: 296px;">
                                                    <div class="product-wrapper">
                                                        <div class="product-stickers">
                                                        </div>
                                                        <div class="product-image">
                                                            <a href="http://dress2.template.moguta.ru/mujskaya-odejda/shorty/shorty-no-excess">
                                                                <img class="mg-product-image" itemprop="image" data-transfer="true" data-product-id="56" src="./Главная _ localhost_files/70_muzhskie-serie-shorti-no-excess-nx-728110301-224.jpg" alt="Шорты No Excess" title="Шорты No Excess" data-magnify-src="http://dress2.template.moguta.ru/uploads/product/000/56/muzhskie-serie-shorti-no-excess-nx-728110301-224.jpg"> </a>
                                                        </div>

                                                        <div class="product-name">
                                                            <a href="http://dress2.template.moguta.ru/mujskaya-odejda/shorty/shorty-no-excess">Шорты No Excess</a>
                                                        </div>
                                                        <div class="product-footer clearfix">
                                                            <div class="clearfix">
                                                                <span class="product-price">699 руб.</span>
                                                                <div class="mg-rating">
                                                                    <div class="rating-wrapper" itemprop="aggregateRating" itemscope="" itemtype="http://schema.org/AggregateRating">
                                                                        <div class="rating-action" data-rating-id="56">
                                                                            <div class="rateit" data-plugin="stars" data-rateit-value="4" data-productid="56" data-rateit-readonly="0">
                                                                                <button id="rateit-reset-15" type="button" data-role="none" class="rateit-reset" aria-label="reset rating" aria-controls="rateit-range-15" style="display: none;"></button>
                                                                                <div id="rateit-range-15" class="rateit-range" tabindex="0" role="slider" aria-label="rating" aria-owns="rateit-reset-15" aria-valuemin="0" aria-valuemax="5" aria-valuenow="4" aria-readonly="0" style="width: 80px; height: 16px;">
                                                                                    <div class="rateit-selected" style="height: 16px; width: 64px;"></div>
                                                                                    <div class="rateit-hover" style="height:16px"></div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <span class="info" data-id="56"><span class="mg-rating-count" data-count="56">(<span itemprop="ratingValue">4</span>/<span itemprop="ratingCount">2</span>)</span>
                                                                        </span>
                                                                    </div>

                                                                </div>
                                                            </div>

                                                            <div class="product-buttons clearfix">
                                                                <!--Кнопка, кототорая меняет свое значение с "В корзину" на "Подробнее"-->
                                                                <form action="http://dress2.template.moguta.ru/catalog" method="POST" class="property-form actionBuy" data-product-id="56">
                                                                    <div class="buy-container ">

                                                                        <div class="hidder-element">
                                                                            <input type="hidden" name="inCartProductId" value="56">

                                                                            <a href="http://dress2.template.moguta.ru/catalog?inCartProductId=56" rel="nofollow" class="addToCart product-buy" data-item-id="56">Купить</a>

                                                                            <a href="http://dress2.template.moguta.ru/compare?inCompareProductId=56" data-item-id="56" class="addToCompare">
        Сравнить        </a>

                                                                        </div>
                                                                    </div>
                                                                </form>
                                                            </div>

                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="owl-controls clickable">
                                            <div class="owl-buttons">
                                                <div class="owl-prev">prev</div>
                                                <div class="owl-next">next</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="clear"></div>
                            </div>

                            <div class="cat-desc">
                                <h3 style="text-align: center;">Добро пожаловать в наш интернет-магазин!</h3>
                                <div>
                                    <p>Мы стабильная и надежная компания, с каждым днем наращиваем свой потенциал. Имеем огромный опыт в сфере корпоративных продаж, наши менеджеры готовы предложить Вам высокий уровень сервиса, грамотную консультацию, выгодные условия работы и широкий спектр цветовых решений. В число наших постоянных клиентов входят крупные компании.</p>
                                    <p>Наши товары производятся только из самых качественных материалов!</p>
                                    <p>Отдел корпоративных продаж готов предложить Вам персонального менеджера, грамотную консультацию, доставку на следующий день после оплаты, сертификаты на всю продукцию, индивидуальный метод работы.</p>
                                    <p>Отдельным направлением является работа с частными лицами с оперативной доставкой, низкими ценами и высоким качеством обслуживания.</p>
                                    <p>Главное для нас — своевременно удовлетворять потребности наших клиентов всеми силами и доступными нам средствами. Работая с нами, Вы гарантированно приобретаете только оригинальный товар подлинного качества.</p>
                                    <p>Мы работаем по всем видам оплат. Только приобретая товар у официального дилера, Вы застрахованы от подделок. Будем рады нашему долгосрочному сотрудничеству.</p>
                                    <p>** Информация представленная на сайте является демонстрационной для ознакомления с Moguta.CMS. <a data-cke-saved-href="http://moguta.ru/" href="http://moguta.ru/">Moguta.CMS - простая cms для интернет-магазина.</a></p>
                                </div>
                            </div>
                            <!-- вывод основного контента -->
                        </div>

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

                    </div>
                </div>

                <!-- показывать на главной -->
                <div class="partners-block">
                    <div class="centered clearfix">
                        <ul class="partners-list">
                            <li><img src="./Главная _ localhost_files/armani.png" alt=""></li>
                            <li><img src="./Главная _ localhost_files/mango.png" alt=""></li>
                            <li><img src="./Главная _ localhost_files/boss.png" alt=""></li>
                            <li><img src="./Главная _ localhost_files/mexx.png" alt=""></li>
                            <li><img src="./Главная _ localhost_files/franchi.png" alt=""></li>
                            <li><img src="./Главная _ localhost_files/dg.png" alt=""></li>
                        </ul>
                    </div>
                </div>

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
                            <img src="./Главная _ localhost_files/payments.png" title="Мы принимаем оплату" alt="Мы принимаем оплату">
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
                                <!--Коды счетчиков-->
                                <!-- В это поле необходимо прописать код счетчика посещаемости Вашего сайта. Например, Яндекс.Метрика или Google analytics -->
                                <!--/Коды счетчиков-->
                            </div>
                        </div>
                    </div>
                </div>
                <div class="footer-bottom">
                    <div class="centered clearfix">
                        <div class="copyright"> 2018 год. Все права защищены.</div>

                        <!--Вывод копирайта-->
                        <div class="powered"> Сайт работает на движке:
                            <a href="https://moguta.ru/" target="_blank">
      Moguta<span class="red">CMS</span></a></div>
                        <!--Вывод копирайта-->
                    </div>
                </div>
            </div>
            <!--/Подвал сайта-->
            <link href="./Главная _ localhost_files/css" rel="stylesheet">
            <div class="demo-sidebar">
                <div class="demo-template demo-template-active" data-tmpl_name="http://dress2.template.moguta.ru/">

                    <div class="img-templ clearfix" style="background-image: url(http://dress2.template.moguta.ru/mg-templates/mg-boutique/images/mg-preview.png)">
                        <a class="demoBuyButton" href="https://moguta.ru/catalog?inCartProductId=326">Купить шаблон</a> </div>

                    <div class="demo-template-colors">
                        <span>Шаблон BOUTIQUE</span>
                        <br>
                        <span class="demo-template-color-text">Выберите цветовую схему:</span>

                        <div class="demo-template-color" style="background-color:#009688" data-tmpl_color="009688"></div>

                        <div class="demo-template-color" style="background-color:#00BCD4" data-tmpl_color="00BCD4"></div>

                        <div class="demo-template-color" style="background-color:#1A4D82" data-tmpl_color="1A4D82"></div>

                        <div class="demo-template-color" style="background-color:#673AB7" data-tmpl_color="673AB7"></div>

                        <div class="demo-template-color" style="background-color:#75c11f" data-tmpl_color="75c11f"></div>

                        <div class="demo-template-color" style="background-color:#9C27B0" data-tmpl_color="9C27B0"></div>

                        <div class="demo-template-color demo-template-color-active" style="background-color:#f92517" data-tmpl_color="f92517"></div>

                        <div class="demo-template-color" style="background-color:#feae34" data-tmpl_color="feae34"></div>

                    </div>
                </div>
                <div class="demo-template" data-tmpl_name="http://victoria.template.moguta.ru/">

                    <div class="img-templ clearfix" style="background-image: url(http://victoria.template.moguta.ru/mg-templates/mg-victoria/images/mg-preview.png)">
                        <a class="demoBuyButton" href="https://moguta.ru/catalog?inCartProductId=332">Купить шаблон</a> </div>

                    <div class="demo-template-colors">
                        <span>Шаблон VICTORIA</span>
                        <br>
                        <span class="demo-template-color-text">Выберите цветовую схему:</span>

                        <div class="demo-template-color" style="background-color:#3D396B" data-tmpl_color="3D396B"></div>

                        <div class="demo-template-color" style="background-color:#4192BD" data-tmpl_color="4192BD"></div>

                        <div class="demo-template-color" style="background-color:#4573B9" data-tmpl_color="4573B9"></div>

                        <div class="demo-template-color" style="background-color:#4A4A4C" data-tmpl_color="4A4A4C"></div>

                        <div class="demo-template-color" style="background-color:#696259" data-tmpl_color="696259"></div>

                        <div class="demo-template-color" style="background-color:#699D38" data-tmpl_color="699D38"></div>

                        <div class="demo-template-color" style="background-color:#73b1d6" data-tmpl_color="73b1d6"></div>

                        <div class="demo-template-color" style="background-color:#A470BC" data-tmpl_color="A470BC"></div>

                        <div class="demo-template-color" style="background-color:#C2C2C2" data-tmpl_color="C2C2C2"></div>

                        <div class="demo-template-color" style="background-color:#CC0000" data-tmpl_color="CC0000"></div>

                        <div class="demo-template-color" style="background-color:#D45684" data-tmpl_color="D45684"></div>

                        <div class="demo-template-color" style="background-color:#E05A00" data-tmpl_color="E05A00"></div>

                        <div class="demo-template-color" style="background-color:#FFDD00" data-tmpl_color="FFDD00"></div>

                        <div class="demo-template-color" style="background-color:#a0b58d" data-tmpl_color="a0b58d"></div>

                        <div class="demo-template-color" style="background-color:#b0a196" data-tmpl_color="b0a196"></div>

                        <div class="demo-template-color" style="background-color:#be97c7" data-tmpl_color="be97c7"></div>

                    </div>
                </div>
                <div class="demo-template" data-tmpl_name="http://amelia.template.moguta.ru/">

                    <div class="img-templ clearfix" style="background-image: url(http://amelia.template.moguta.ru/mg-templates/mg-amelia/images/mg-preview.png)">
                        <a class="demoBuyButton" href="https://moguta.ru/catalog?inCartProductId=432">Купить шаблон</a> </div>

                    <div class="demo-template-colors">
                        <span>Шаблон AMELIA</span>
                        <br>
                        <span class="demo-template-color-text">Выберите цветовую схему:</span>

                        <div class="demo-template-color" style="background-color:#333333" data-tmpl_color="333333"></div>

                        <div class="demo-template-color" style="background-color:#406ca9" data-tmpl_color="406ca9"></div>

                        <div class="demo-template-color" style="background-color:#41a4dd" data-tmpl_color="41a4dd"></div>

                        <div class="demo-template-color" style="background-color:#78bf57" data-tmpl_color="78bf57"></div>

                        <div class="demo-template-color" style="background-color:#88776f" data-tmpl_color="88776f"></div>

                        <div class="demo-template-color" style="background-color:#a958bc" data-tmpl_color="a958bc"></div>

                        <div class="demo-template-color" style="background-color:#d45684" data-tmpl_color="d45684"></div>

                        <div class="demo-template-color" style="background-color:#e93f11" data-tmpl_color="e93f11"></div>

                        <div class="demo-template-color" style="background-color:#ef8b2e" data-tmpl_color="ef8b2e"></div>

                    </div>
                </div>
                <div class="demo-template" data-tmpl_name="http://valery.template.moguta.ru/">

                    <div class="img-templ clearfix" style="background-image: url(http://valery.template.moguta.ru/mg-templates/mg-valery/images/mg-preview.png)">
                        <a class="demoBuyButton" href="https://moguta.ru/catalog?inCartProductId=431">Купить шаблон</a> </div>

                    <div class="demo-template-colors">
                        <span>Шаблон VALERY</span>
                        <br>
                        <span class="demo-template-color-text">Выберите цветовую схему:</span>

                        <div class="demo-template-color" style="background-color:#3D396B" data-tmpl_color="3D396B"></div>

                        <div class="demo-template-color" style="background-color:#4192BD" data-tmpl_color="4192BD"></div>

                        <div class="demo-template-color" style="background-color:#4573B9" data-tmpl_color="4573B9"></div>

                        <div class="demo-template-color" style="background-color:#4A4A4C" data-tmpl_color="4A4A4C"></div>

                        <div class="demo-template-color" style="background-color:#696259" data-tmpl_color="696259"></div>

                        <div class="demo-template-color" style="background-color:#699D38" data-tmpl_color="699D38"></div>

                        <div class="demo-template-color" style="background-color:#A470BC" data-tmpl_color="A470BC"></div>

                        <div class="demo-template-color" style="background-color:#C2C2C2" data-tmpl_color="C2C2C2"></div>

                        <div class="demo-template-color" style="background-color:#CC0000" data-tmpl_color="CC0000"></div>

                        <div class="demo-template-color" style="background-color:#E05A00" data-tmpl_color="E05A00"></div>

                        <div class="demo-template-color" style="background-color:#E69EB8" data-tmpl_color="E69EB8"></div>

                        <div class="demo-template-color" style="background-color:#FFDD00" data-tmpl_color="FFDD00"></div>

                    </div>
                </div>
                <div class="demo-template" data-tmpl_name="http://grace.template.moguta.ru/">

                    <div class="img-templ clearfix" style="background-image: url(http://grace.template.moguta.ru/mg-templates/mg-grace/images/mg-preview.png)">
                        <a class="demoBuyButton" href="https://moguta.ru/catalog?inCartProductId=247">Купить шаблон</a> </div>

                    <div class="demo-template-colors">
                        <span>Шаблон GRACE</span>
                        <br>
                        <span class="demo-template-color-text">Выберите цветовую схему:</span>

                        <div class="demo-template-color" style="background-color:#222222" data-tmpl_color="222222"></div>

                        <div class="demo-template-color" style="background-color:#466183" data-tmpl_color="466183"></div>

                        <div class="demo-template-color" style="background-color:#5F5352" data-tmpl_color="5F5352"></div>

                        <div class="demo-template-color" style="background-color:#7E8891" data-tmpl_color="7E8891"></div>

                        <div class="demo-template-color" style="background-color:#986FAC" data-tmpl_color="986FAC"></div>

                        <div class="demo-template-color" style="background-color:#D27480" data-tmpl_color="D27480"></div>

                    </div>
                </div>
                <div class="demo-template" data-tmpl_name="http://lucy.template.moguta.ru/">

                    <div class="img-templ clearfix" style="background-image: url(http://lucy.template.moguta.ru/mg-templates/mg-lucy/images/mg-preview.png)">
                        <a class="demoBuyButton" href="https://moguta.ru/catalog?inCartProductId=222">Купить шаблон</a> </div>

                    <div class="demo-template-colors">
                        <span>Шаблон LUCY</span>
                        <br>
                        <span class="demo-template-color-text">Выберите цветовую схему:</span>

                        <div class="demo-template-color" style="background-color:#264566" data-tmpl_color="264566"></div>

                        <div class="demo-template-color" style="background-color:#282c2f" data-tmpl_color="282c2f"></div>

                        <div class="demo-template-color" style="background-color:#2A6F66" data-tmpl_color="2A6F66"></div>

                        <div class="demo-template-color" style="background-color:#34233F" data-tmpl_color="34233F"></div>

                        <div class="demo-template-color" style="background-color:#3E94B2" data-tmpl_color="3E94B2"></div>

                        <div class="demo-template-color" style="background-color:#3F4B6E" data-tmpl_color="3F4B6E"></div>

                        <div class="demo-template-color" style="background-color:#564C6E" data-tmpl_color="564C6E"></div>

                        <div class="demo-template-color" style="background-color:#577038" data-tmpl_color="577038"></div>

                        <div class="demo-template-color" style="background-color:#685A44" data-tmpl_color="685A44"></div>

                        <div class="demo-template-color" style="background-color:#77758D" data-tmpl_color="77758D"></div>

                        <div class="demo-template-color" style="background-color:#7C5469" data-tmpl_color="7C5469"></div>

                        <div class="demo-template-color" style="background-color:#809D75" data-tmpl_color="809D75"></div>

                        <div class="demo-template-color" style="background-color:#858585" data-tmpl_color="858585"></div>

                        <div class="demo-template-color" style="background-color:#B23831" data-tmpl_color="B23831"></div>

                        <div class="demo-template-color" style="background-color:#B24D1B" data-tmpl_color="B24D1B"></div>

                        <div class="demo-template-color" style="background-color:#B5654B" data-tmpl_color="B5654B"></div>

                    </div>
                </div>
                <div class="demo-template" data-tmpl_name="http://mobiles.template.moguta.ru/">

                    <div class="img-templ clearfix" style="background-image: url(http://mobiles.template.moguta.ru/mg-templates/mg-selling/images/mg-preview.png)">
                        <a class="demoBuyButton" href="https://moguta.ru/catalog?inCartProductId=298">Купить шаблон</a> </div>

                    <div class="demo-template-colors">
                        <span>Шаблон SELLING</span>
                        <br>
                        <span class="demo-template-color-text">Выберите цветовую схему:</span>

                        <div class="demo-template-color" style="background-color:#00A3A3" data-tmpl_color="00A3A3"></div>

                        <div class="demo-template-color" style="background-color:#00C13F" data-tmpl_color="00C13F"></div>

                        <div class="demo-template-color" style="background-color:#4617B4" data-tmpl_color="4617B4"></div>

                        <div class="demo-template-color" style="background-color:#632F00" data-tmpl_color="632F00"></div>

                        <div class="demo-template-color" style="background-color:#66CC99" data-tmpl_color="66CC99"></div>

                        <div class="demo-template-color" style="background-color:#AA40FF" data-tmpl_color="AA40FF"></div>

                        <div class="demo-template-color" style="background-color:#B01E00" data-tmpl_color="B01E00"></div>

                        <div class="demo-template-color" style="background-color:#F3B200" data-tmpl_color="F3B200"></div>

                        <div class="demo-template-color" style="background-color:#FE7C22" data-tmpl_color="FE7C22"></div>

                        <div class="demo-template-color" style="background-color:#FF1D77" data-tmpl_color="FF1D77"></div>

                        <div class="demo-template-color" style="background-color:#FF2E12" data-tmpl_color="FF2E12"></div>

                    </div>
                </div>
                <div class="demo-template" data-tmpl_name="http://olddefault.template.moguta.ru/">

                    <div class="img-templ clearfix" style="background-image: url(http://olddefault.template.moguta.ru/mg-templates/mg-old-default/images/mg-preview.png)">
                        <a class="demoBuyButton" href="https://moguta.ru/catalog?inCartProductId=449">Купить шаблон</a> </div>

                    <div class="demo-template-colors">
                        <span>Шаблон CLASSIC</span>
                        <br>
                        <span class="demo-template-color-text">Выберите цветовую схему:</span>

                        <div class="demo-template-color" style="background-color:#7abcff" data-tmpl_color="7abcff"></div>

                        <div class="demo-template-color" style="background-color:#9eea4d" data-tmpl_color="9eea4d"></div>

                        <div class="demo-template-color" style="background-color:#DE0101" data-tmpl_color="DE0101"></div>

                        <div class="demo-template-color" style="background-color:#a90329" data-tmpl_color="a90329"></div>

                        <div class="demo-template-color" style="background-color:#e570e7" data-tmpl_color="e570e7"></div>

                        <div class="demo-template-color" style="background-color:#ff0084" data-tmpl_color="ff0084"></div>

                        <div class="demo-template-color" style="background-color:#ffaf4b" data-tmpl_color="ffaf4b"></div>

                    </div>
                </div>
                <div class="demo-template" data-tmpl_name="http://megastore.template.moguta.ru/">

                    <div class="img-templ clearfix" style="background-image: url(http://megastore.template.moguta.ru/mg-templates/mg-megastore/images/mg-preview.png)">
                        <a class="demoBuyButton" href="https://moguta.ru/catalog?inCartProductId=451">Купить шаблон</a> </div>

                    <div class="demo-template-colors">
                        <span>Шаблон MEGASTORE</span>
                        <br>
                        <span class="demo-template-color-text">Выберите цветовую схему:</span>

                        <div class="demo-template-color" style="background-color:#0A93AC" data-tmpl_color="0A93AC"></div>

                        <div class="demo-template-color" style="background-color:#2A991D" data-tmpl_color="2A991D"></div>

                        <div class="demo-template-color" style="background-color:#AC0A5D" data-tmpl_color="AC0A5D"></div>

                        <div class="demo-template-color" style="background-color:#ED0101" data-tmpl_color="ED0101"></div>

                        <div class="demo-template-color" style="background-color:#EF008B" data-tmpl_color="EF008B"></div>

                        <div class="demo-template-color" style="background-color:#FFAF4B" data-tmpl_color="FFAF4B"></div>

                    </div>
                </div>
                <div class="demo-template" data-tmpl_name="http://dress.template.moguta.ru/">

                    <div class="img-templ clearfix" style="background-image: url(http://dress.template.moguta.ru/mg-templates/mg-flatty/images/mg-preview.png)">
                        <a class="demoBuyButton" href="https://moguta.ru/catalog?inCartProductId=192">Купить шаблон</a> </div>

                    <div class="demo-template-colors">
                        <span>Шаблон FLATTY</span>
                        <br>
                        <span class="demo-template-color-text">Выберите цветовую схему:</span>

                        <div class="demo-template-color" style="background-color:#00A9A8" data-tmpl_color="00A9A8"></div>

                        <div class="demo-template-color" style="background-color:#2E79D8" data-tmpl_color="2E79D8"></div>

                        <div class="demo-template-color" style="background-color:#40AF64" data-tmpl_color="40AF64"></div>

                        <div class="demo-template-color" style="background-color:#535C69" data-tmpl_color="535C69"></div>

                        <div class="demo-template-color" style="background-color:#A00024" data-tmpl_color="A00024"></div>

                        <div class="demo-template-color" style="background-color:#A900FE" data-tmpl_color="A900FE"></div>

                        <div class="demo-template-color" style="background-color:#EA1700" data-tmpl_color="EA1700"></div>

                        <div class="demo-template-color" style="background-color:#FF0084" data-tmpl_color="FF0084"></div>

                        <div class="demo-template-color" style="background-color:#FF670F" data-tmpl_color="FF670F"></div>

                    </div>
                </div>
                <div class="demo-template" data-tmpl_name="http://mobile.template.moguta.ru/">

                    <div class="img-templ clearfix" style="background-image: url(http://mobile.template.moguta.ru/mg-templates/mg-coleos/images/mg-preview.png)">
                        <a class="demoBuyButton" href="https://moguta.ru/catalog?inCartProductId=281">Купить шаблон</a> </div>

                    <div class="demo-template-colors">
                        <span>Шаблон COLEOS</span>
                        <br>
                        <span class="demo-template-color-text">Выберите цветовую схему:</span>

                        <div class="demo-template-color" style="background-color:#0088CC" data-tmpl_color="0088CC"></div>

                        <div class="demo-template-color" style="background-color:#009688" data-tmpl_color="009688"></div>

                        <div class="demo-template-color" style="background-color:#00BCD4" data-tmpl_color="00BCD4"></div>

                        <div class="demo-template-color" style="background-color:#3F51B5" data-tmpl_color="3F51B5"></div>

                        <div class="demo-template-color" style="background-color:#4CAF50" data-tmpl_color="4CAF50"></div>

                        <div class="demo-template-color" style="background-color:#607D8B" data-tmpl_color="607D8B"></div>

                        <div class="demo-template-color" style="background-color:#673AB7" data-tmpl_color="673AB7"></div>

                        <div class="demo-template-color" style="background-color:#795548" data-tmpl_color="795548"></div>

                        <div class="demo-template-color" style="background-color:#8BC34A" data-tmpl_color="8BC34A"></div>

                        <div class="demo-template-color" style="background-color:#9C27B0" data-tmpl_color="9C27B0"></div>

                        <div class="demo-template-color" style="background-color:#E91E63" data-tmpl_color="E91E63"></div>

                        <div class="demo-template-color" style="background-color:#FF5722" data-tmpl_color="FF5722"></div>

                    </div>
                </div>
                <div class="demo-template" data-tmpl_name="http://sport.template.moguta.ru/">

                    <div class="img-templ clearfix" style="background-image: url(http://sport.template.moguta.ru/mg-templates/mg-store/images/mg-preview.png)">
                        <a class="demoBuyButton" href="https://moguta.ru/catalog?inCartProductId=202">Купить шаблон</a> </div>

                    <div class="demo-template-colors">
                        <span>Шаблон STORE</span>
                        <br>
                        <span class="demo-template-color-text">Выберите цветовую схему:</span>

                        <div class="demo-template-color" style="background-color:#2FB991" data-tmpl_color="2FB991"></div>

                        <div class="demo-template-color" style="background-color:#6DBCDB" data-tmpl_color="6DBCDB"></div>

                        <div class="demo-template-color" style="background-color:#87BA0A" data-tmpl_color="87BA0A"></div>

                        <div class="demo-template-color" style="background-color:#B660C0" data-tmpl_color="B660C0"></div>

                        <div class="demo-template-color" style="background-color:#BA0A0A" data-tmpl_color="BA0A0A"></div>

                        <div class="demo-template-color" style="background-color:#E4C31D" data-tmpl_color="E4C31D"></div>

                        <div class="demo-template-color" style="background-color:#FA1010" data-tmpl_color="FA1010"></div>

                    </div>
                </div>
                <div class="demo-template" data-tmpl_name="http://velo.template.moguta.ru/">

                    <div class="img-templ clearfix" style="background-image: url(http://velo.template.moguta.ru/mg-templates/mg-style/images/mg-preview.png)">
                        <a class="demoBuyButton" href="https://moguta.ru/catalog?inCartProductId=206">Купить шаблон</a> </div>

                    <div class="demo-template-colors">
                        <span>Шаблон STYLE</span>
                        <br>
                        <span class="demo-template-color-text">Выберите цветовую схему:</span>

                        <div class="demo-template-color" style="background-color:#2FB991" data-tmpl_color="2FB991"></div>

                        <div class="demo-template-color" style="background-color:#87BA0A" data-tmpl_color="87BA0A"></div>

                        <div class="demo-template-color" style="background-color:#B660C0" data-tmpl_color="B660C0"></div>

                        <div class="demo-template-color" style="background-color:#BA0A0A" data-tmpl_color="BA0A0A"></div>

                        <div class="demo-template-color" style="background-color:#E4C31D" data-tmpl_color="E4C31D"></div>

                        <div class="demo-template-color" style="background-color:#FA1010" data-tmpl_color="FA1010"></div>

                    </div>
                </div>
                <div class="demo-template" data-tmpl_name="http://tools.template.moguta.ru/">

                    <div class="img-templ clearfix" style="background-image: url(http://tools.template.moguta.ru/mg-templates/mg-market/images/mg-preview.png)">
                        <a class="demoBuyButton" href="https://moguta.ru/catalog?inCartProductId=213">Купить шаблон</a> </div>

                    <div class="demo-template-colors">
                        <span>Шаблон MARKET</span>
                        <br>
                        <span class="demo-template-color-text">Выберите цветовую схему:</span>

                        <div class="demo-template-color" style="background-color:#129EDD" data-tmpl_color="129EDD"></div>

                        <div class="demo-template-color" style="background-color:#8FE57D" data-tmpl_color="8FE57D"></div>

                        <div class="demo-template-color" style="background-color:#9157C5" data-tmpl_color="9157C5"></div>

                        <div class="demo-template-color" style="background-color:#DD2B76" data-tmpl_color="DD2B76"></div>

                        <div class="demo-template-color" style="background-color:#EAD123" data-tmpl_color="EAD123"></div>

                        <div class="demo-template-color" style="background-color:#ED1C2A" data-tmpl_color="ED1C2A"></div>

                        <div class="demo-template-color" style="background-color:#EF8B00" data-tmpl_color="EF8B00"></div>

                    </div>
                </div>
                <div class="demo-template" data-tmpl_name="http://auto.template.moguta.ru/">

                    <div class="img-templ clearfix" style="background-image: url(http://auto.template.moguta.ru/mg-templates/mg-automobiles/images/mg-preview.png)">
                        <a class="demoBuyButton" href="https://moguta.ru/catalog?inCartProductId=163">Купить шаблон</a> </div>

                    <div class="demo-template-colors">
                        <span>Шаблон AUTOMOBILES</span>
                        <br>
                        <span class="demo-template-color-text">Выберите цветовую схему:</span>

                        <div class="demo-template-color" style="background-color:#2587C4" data-tmpl_color="2587C4"></div>

                        <div class="demo-template-color" style="background-color:#516789" data-tmpl_color="516789"></div>

                        <div class="demo-template-color" style="background-color:#72AF00" data-tmpl_color="72AF00"></div>

                        <div class="demo-template-color" style="background-color:#9D4D23" data-tmpl_color="9D4D23"></div>

                        <div class="demo-template-color" style="background-color:#E1150C" data-tmpl_color="E1150C"></div>

                        <div class="demo-template-color" style="background-color:#F62D95" data-tmpl_color="F62D95"></div>

                        <div class="demo-template-color" style="background-color:#FF822C" data-tmpl_color="FF822C"></div>

                    </div>
                </div>
                <div class="demo-template" data-tmpl_name="http://sleep.template.moguta.ru/">

                    <div class="img-templ clearfix" style="background-image: url(http://sleep.template.moguta.ru/mg-templates/mg-porto/images/mg-preview.png)">
                        <a class="demoBuyButton" href="https://moguta.ru/catalog?inCartProductId=242">Купить шаблон</a> </div>

                    <div class="demo-template-colors">
                        <span>Шаблон PORTO</span>
                        <br>
                        <span class="demo-template-color-text">Выберите цветовую схему:</span>

                        <div class="demo-template-color" style="background-color:#175659" data-tmpl_color="175659"></div>

                        <div class="demo-template-color" style="background-color:#292560" data-tmpl_color="292560"></div>

                        <div class="demo-template-color" style="background-color:#8B0360" data-tmpl_color="8B0360"></div>

                        <div class="demo-template-color" style="background-color:#8E2727" data-tmpl_color="8E2727"></div>

                        <div class="demo-template-color" style="background-color:#9E8B05" data-tmpl_color="9E8B05"></div>

                    </div>
                </div>
                <div class="demo-template" data-tmpl_name="http://door.template.moguta.ru/">

                    <div class="img-templ clearfix" style="background-image: url(http://door.template.moguta.ru/mg-templates/mg-wooden/images/mg-preview.png)">
                        <a class="demoBuyButton" href="https://moguta.ru/catalog?inCartProductId=137">Купить шаблон</a> </div>

                    <div class="demo-template-colors">
                        <span>Шаблон WOOD</span>
                        <br>
                        <span class="demo-template-color-text">Выберите цветовую схему:</span>

                        <div class="demo-template-color" style="background-color:#00C8C0" data-tmpl_color="00C8C0"></div>

                        <div class="demo-template-color" style="background-color:#16C339" data-tmpl_color="16C339"></div>

                        <div class="demo-template-color" style="background-color:#269AFC" data-tmpl_color="269AFC"></div>

                        <div class="demo-template-color" style="background-color:#3B5388" data-tmpl_color="3B5388"></div>

                        <div class="demo-template-color" style="background-color:#C83500" data-tmpl_color="C83500"></div>

                        <div class="demo-template-color" style="background-color:#EC2AA0" data-tmpl_color="EC2AA0"></div>

                        <div class="demo-template-color" style="background-color:#ECAA2A" data-tmpl_color="ECAA2A"></div>

                        <div class="demo-template-color" style="background-color:#FC2626" data-tmpl_color="FC2626"></div>

                    </div>
                </div>
            </div>
            <div class="demoArrow"><span class="demo-template-button">Выбрать дизайн</span></div>

            <script>
                if (window.sessionStorage.demoTemplateState == 'opened') {
                    $('.demo-sidebar').attr('style', 'transition: unset;').addClass('demoOpen').attr('style', '');
                    $('.demoArrow').attr('style', 'transition: unset;').addClass('demoOpen').attr('style', '');
                }
                $(document).ready(function() {
                    setInterval(function() {
                        $('.demoArrow>span').addClass('blink_on');
                        setTimeout(function() {
                            $('.demoArrow>span').removeClass('blink_on')
                        }, 1500);
                    }, 6000);

                    $('body').on('click', function(e) {
                        var sidebar = $('.demo-sidebar');
                        var sidebarArrow = $('.demoArrow');

                        if (!sidebar.is(e.target) && sidebar.has(e.target).length === 0 && !sidebarArrow.is(e.target) && sidebarArrow.has(e.target).length === 0) {
                            sidebar.removeClass('demoOpen');
                            sidebarArrow.removeClass('demoOpen');
                            window.sessionStorage.demoTemplateState = 'closed';
                        }
                    });

                    $('.demoArrow').on('click', function() {
                        $(this).toggleClass('demoOpen');
                        $('.demo-sidebar').toggleClass('demoOpen');
                        if ($(this).hasClass('demoOpen')) {
                            window.sessionStorage.demoTemplateState = 'opened';
                        } else {
                            window.sessionStorage.demoTemplateState = 'closed';
                        }
                    });

                    $('.demo-sidebar').on('click', '.demo-template', function() {
                        var tmpl = $(this).data('tmpl_name');
                        if (tmpl != 'msg') {
                            var clr = $(this).find('.demo-template-color:first').data('tmpl_color');
                            if (clr) {
                                clr = '?tpl&color=' + clr;
                            } else {
                                clr = '';
                            }
                            window.location = tmpl + clr;
                        }
                    });

                    $('.demo-sidebar').on('click', '.demo-template-color', function(e) {
                        e.stopPropagation();
                        var tmpl = $(this).parent().parent().data('tmpl_name');
                        var clr = $(this).data('tmpl_color');
                        if (clr) {
                            clr = '?tpl&color=' + clr;
                        } else {
                            clr = '';
                        }
                        window.location = tmpl + clr;
                    });
                });
            </script>
</body>
<div class="XTranslate">
    <div class="popup-content resolved"></div>
</div>

</html>