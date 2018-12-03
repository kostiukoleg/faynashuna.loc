<?php

/*
 * Plugin Name: Недавно просмотренные товары
 * Description: Выводит товары, которые пользователь недавно просматривал. Для вывода необходим шорткод и аргументы: count - количество выводимых товаров, random = 1 или 0 вывод в случайном порядке или по очередности просмотра. Шорткод [recently-viewed count=5 random=1]
 * Author: Чуркина Дарья
 * Version: 1.0.5
 */

new RecentlyViewed;

class RecentlyViewed {

  private static $pluginName = ''; // Название плагина (соответствует названию папки).
  private static $path = ''; // Путь до файлов плагина.

  public function __construct() {
    mgAddAction('mg_start', array(__CLASS__, 'checkPage'));
    mgAddShortcode('recently-viewed', array(__CLASS__, 'recentlyViewed'));
    self::$path = PLUGIN_DIR.self::$pluginName;
    self::$pluginName = PM::getFolderPlugin(__FILE__);
  }

  /**
   * 
   * Обработчик хука при загрузке страницы, идет проверка открыт какой-либо продукт или нет. Добавление в куки id, если открыт товар
   * 
   */
  static function checkPage() {   
      $product = URL::parsePageUrl();
      $res = DB::query('SELECT `code` FROM `'.PREFIX.'product` WHERE `url`='.DB::quote($product));
      if ($item = DB::fetchArray($res)) {
        if (isset($_COOKIE['recently-viewed'])) {
          $array_id = json_decode($_COOKIE['recently-viewed']);
          if (count($array_id) > 100) {
            array_shift($array_id);
          }
        }
        $array_id[] = $item['code'];
        $array_id = array_unique($array_id);
        $json = json_encode($array_id);
        // сохранение истории на 2 дня
        setcookie('recently-viewed', $json, time() + 172800, '/');
      }    
  }

  static function recentlyViewed($arg) {
    $count = $arg['count'] ? $arg['count'] : 10;
    if (isset($_COOKIE['recently-viewed'])) {
      $array_id = json_decode($_COOKIE['recently-viewed']);
      $codes = array_reverse($array_id);
      if ($arg['random']) {
        shuffle($codes);
      }
      foreach($codes as $code) {
        if ($count > 0 ){
          $products[] = $code;
        }
        $count--;
      }
     // $products = implode(',', $products);
      $related = self::getInfoRecentProduct($products);
      return $related;
    }
  }

  static function getInfoRecentProduct($args) {
    $model = new Models_Product;
    $stringRelated = ' null';
    $sortRelated = array();
    foreach ($args as $item) {
      $stringRelated .= ','.DB::quote($item);
      $sortRelated[$item] = $item;
    }
    $stringRelated = substr($stringRelated, 1);

    $data['products'] = $model->getProductByUserFilter(' p.code IN ('.$stringRelated.') and p.activity = 1 ');

    if (!empty($data['products'])) {
      $data['currency'] = MG::getSetting('currency');
      foreach ($data['products'] as $item) {
        $img = explode('|', $item['image_url']);
        $item['img'] = $img[0];
        if (SHORT_LINK == 1||MG::getSetting('shortLink')=='true') {
          $item['url'] = SITE.'/'.$item["product_url"];
        } else {
          $item['url'] = SITE.'/'.(isset($item["category_url"]) ? $item["category_url"] : 'catalog').'/'.$item["product_url"];
        }        
        $item['price'] = MG::priceCourse($item['price_course']);
        $sortRelated[$item['code']] = $item;
      }
      $data['products'] = array();
      //сортируем связанные товары в том порядке, в котором они идут в строке артикулов
      foreach ($sortRelated as $item) {
        if (!empty($item['id']) && is_array($item)) {
          $data['products'][$item['id']] = $item;
        }
      }
    }
    return self::htmlRecentlyProducts($data);
  }

  /**
   * функция фозвращает html код для вывода блока с недавно просмотренными товарами
   * @param type $args
   * @return string
   */
  static function htmlRecentlyProducts($data) {
    //viewData($data);
    $block = '';
    foreach ($data["products"] as $item) {
      $block .= '
         <div class="product-wrapper " >
        <div class="mg-recently-viewed">
        <div class="mg-recently-product-wrapper " >
        <div class="mg-recently-product-image">
        <a href="'.$item['url'].'">';
      $item["image_url"] = $item['img'];
      $block .= mgImageProduct($item).'</a>
				</div>
        <div class="mg-recently-product-name">
        <a href="'.$item['url'].'">'.$item["title"].'</a>
        </div>
        <span class="mg-recently-product-price">'.$item["price"].' '.$data['currency'].'</span>
				<div ></div>';
      if ($item['count']==0) {
         $block .= '<a href="'.$item['url'].'" class="product-info default-btn">Подробнее</a>';
      }
      else {
        $block .= '<a class="addToCart default-btn product-buy" href="'.SITE.'/catalog?inCartProductId='.$item['id'].'" data-item-id="'.$item['id'].'">Добавить</a>';
      }
        $block .='</div>
        </div></div>';
    }
    $html = '<link href="'.SITE.'/'.self::$path.self::$pluginName.'/css/recently.css" rel="stylesheet" type="text/css" />
      <script src="'.SCRIPT.'jquery.bxslider.min.js"></script>
      <script src="'.SITE.'/'.self::$path.self::$pluginName.'/js/recently.js"></script>
      <div class="mg-recently-viewed-plugin">
      <h2 class="mg-recently-title">Недавно просмотренные товары <span class="custom-arrow"></span></h2>
      <div class="mg-recently-viewed-slider">'.$block.'</div></div>';
    return $html;
  }

}
