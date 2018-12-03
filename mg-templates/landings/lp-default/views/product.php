<?php
/**
 *  Файл представления Product - выводит сгенерированную движком информацию на странице личного кабинета.
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
 *   $data['liteFormData'] => Упрощенная форма для карточки товара
 *   $data['meta_title'] => Значение meta тега для страницы,
 *   $data['meta_keywords'] => Значение meta_keywords тега для страницы,
 *   $data['meta_desc'] => Значение meta_desc тега для страницы
 *   $data['landingUTO'] => Значение строки "Уникальное торговое предложение" из карточки товара
 *   $data['landingImage'] => Ссылка на изображение для лендинга
 *   $data['landingSwitch'] => Переключатель формы
 *   </code>
 *
 *   Получить подробную информацию о каждом элементе массива $data, можно вставив следующую строку кода в верстку файла.
 *   <code>
 *    <?php viewData($data); ?>
 *   </code>
 */

// Установка значений в метатеги title, keywords, description.
mgAddMeta('<link href="' . SCRIPT . 'standard/css/layout.related.css" rel="stylesheet" type="text/css" />');
mgAddMeta('<script src="' . SCRIPT . 'standard/js/layout.related.js"></script>');
mgSEO($data);
if (strlen($data['landingImage']) > 0) {
    echo '<style>
        body {position:relative;background:#e3d3b8 url("'.$data['landingImage'].'") no-repeat 100% 100% fixed;-webkit-background-size:cover;-moz-background-size:cover;background-size:cover;color:#333;min-height:100%;background-size: 100% 100%;}
    </style>';
}
?>

<div id="main">
    <div class="row-main">
        <div class="col col-img">
            <div class="mg-product-slides-wrapper">
                <?php mgGalleryProduct($data); ?>
            </div>
        </div>
        <div class="col col-content">
            <div class="in buy-block-inner">
                <p class="read-this"><?php echo $data['title']; ?></p>
                <h1><?php echo $data['landingUTO']; ?></h1>

                <div class="product-status">
                    <div class="buy-block">
                        <div class="product-prices product-status-list clearfix">
                            <div class="old-price" <?php echo (!$data['old_price'])?'style="display:none"':'style="display:inline-block"' ?>>
                                <span><?php echo MG::numberFormat($data['old_price'],'1 234,56')." ".$data['currency']; ?></span>
                            </div>
                            <div class="price"><?php echo $data['price'] ?> <?php echo $data['currency']; ?></div>
                        </div><br>
                        <?php 
                        echo $data['propertyForm']; 

                        if ($data['landingSwitch'] < 0) {
                            $style = '';

                            if ($data['count'] != 0) {
                                $style = 'style="display:none;"';
                            }

                            echo '<a class="depletedLanding" '.$style.' rel="nofollow" href="'.SITE.'/feedback?message='.$data['noneMessage'].'">Сообщить когда будет</a>';
                        }
                        ?>
                    </div>
                </div>

                <?php   
                if ($data['landingSwitch'] > 0) { 
                    if(class_exists('formDesigner')){
                        echo '[contact-form id='.$data['landingSwitch'].']';
                    }
                    else{
                        echo 'Установите плагин "Конструктор форм" или включите кнопку "купить" вместо формы!';
                    }
                } 
                ?>
                <span class="desc"><?php echo $data['description']; ?></span>
            </div>
        </div>
    </div>
</div>