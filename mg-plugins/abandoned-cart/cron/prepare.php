<?php
class prepare {
  static private $_instance = null;
  private function __construct(){
    define('SITE', PROTOCOL.'://'.$_SERVER['SERVER_NAME'].str_replace('/index.php', '', $_SERVER['SCRIPT_NAME']));
  }
  private function __clone(){
  }
  private function __wakeup(){
  }
  /**
   * Инициализирует данный класс Mailer.
   * @return void
   */
  public static function init(){
    self::getInstance();    
    self::checkCartAndTrigg();
  }
   /**
   * Возвращет единственный экземпляр данного класса.
   * @return object - объект класса Mailer
   */
  static public function getInstance(){
    if(is_null(self::$_instance)){
      self::$_instance = new self;
    }
    return self::$_instance;
  }

private function sendLetter($let, $cart) {
  // подготовка текста письма
  $option = DB::query('SELECT `value` FROM `'.PREFIX.'setting` WHERE `option` ="abandonedCartOption"');
  $rezCart = DB::fetchArray($option);
  $option = stripslashes($rezCart['value']);
  $options = unserialize($option); 
  if (time() - (strtotime($cart['date_update'])&&strtotime($cart['date_update']) >0 ? strtotime($cart['date_update']) : 0) > $options['diff']*60*60) {
  $id = $cart['id'];
  $text = $let['text'];
  if (empty($cart['hash'])){      
    $cart['hash'] = htmlspecialchars(crypt($cart['email']));
  }
  $link = '<a href="'.SITE.'/basket?cart='.$cart['hash'].'&id='.$id.'" target="blank">'.$options['return'].'</a>';
  $linkCan = '<a href="'.SITE.'/basket?cancel='.$cart['hash'].'&id='.$id.'" target="blank">'.$options['cancel'].'</a>';
  $content = self::getHtmlCart($cart['content']);
  ob_start();
  include ('mg-plugins/abandoned-cart/layout_cart.php');
  $html = ob_get_contents();
  ob_clean();
  $text = str_replace('{name}', $cart['name'], $text);
  $text = str_replace('{linkOrder}', $link, $text);
  $text = str_replace('{linkCancel}', $linkCan, $text);
  $text = str_replace('{cartContent}', $html, $text);
  $recv= DB::query('SELECT `value`, `option` FROM `'.PREFIX.'setting` WHERE `option` ="sitename" OR `option` ="noReplyEmail"');
  while ($replay = db::fetchArray($recv)) {
    $replaySet[$replay['option']] = $replay['value'];
  }
  $sitename = $replaySet['sitename'];
  if(preg_match('/^[-._a-zA-Z0-9]+@(?:[a-zA-Z0-9][-a-zA-Z0-9]+\.)+[a-zA-Z]{2,6}$/', $cart['email'])){
  Main::sendMail(array(
			  'nameFrom' => $replaySet['noReplyEmail'],
			  'emailFrom' => $replaySet['noReplyEmail'],
			  'nameTo' => $sitename,
			  'emailTo' => $cart['email'],
			  'subject' => $let['subject'] ? $let['subject'] : '[FROM '.$sitename.']',
			  'body' => $text,
			  'html' => true
			));
  DB::query('UPDATE `'.PREFIX.'abandoned-cart` SET '
          . '`date_update`=now(), `id_letter`=CONCAT(`id_letter`,\'|'.DB::quote(intval($let['id']), true).'\'), `hash` ='.DB::quote($cart['hash']).' 
          WHERE `id`='.DB::quote(intval($id)));
  }
  return true;
  }
  return false;
}
// html таблица корзины
  private function getHtmlCart($cart){
  $productPositions = array();
      $totalSumm = 0;
      $cartArray = unserialize(stripslashes($cart));
    if (!empty($cartArray['cart'])) {
      $settings = array();
      $set = DB::query('SELECT `value`, `option` FROM `'.PREFIX.'setting` WHERE `option`  IN ("currencyRate","currencyShopIso","priceFormat","currency")');
      while($rowSet=DB::fetchArray($set)) {
        $settings[$rowSet['option']]=$rowSet['value'];
      }
      $currencyRate = unserialize(stripslashes($settings['currencyRate']));
      $currencyShopIso = $settings['currencyShopIso'];
      $variantsId = array();
      $productsId = array();
      foreach ($cartArray['cart'] as $key => $item) {
        if (!empty($item['variantId'])) {
          $variantsId[] = intval($item['variantId']);
        }
        $productsId[] = intval($item['id']);        
      }
      $products_all = array();
      $variants_all = array();
      if (!empty($variantsId)) {
          $ids = implode(',', $variantsId);
          $variants_res = DB::query('SELECT  pv.*, c.rate,(pv.price_course + pv.price_course *(IFNULL(c.rate,0))) as `price_course`,
          p.currency_iso
          FROM `'.PREFIX.'product_variant` pv   
          LEFT JOIN `'.PREFIX.'product` as p ON 
            p.id = pv.product_id
          LEFT JOIN `'.PREFIX.'category` as c ON 
            c.id = p.cat_id       
          WHERE pv.id IN ('.trim(DB::quote($ids, true)).')');
          while ($variant_row = DB::fetchAssoc($variants_res)) {
            $variants_all[$variant_row['id']] = $variant_row;
           }
        }
        if (!empty($productsId)) {
          $ids = implode(',', array_unique($productsId));
          $product_res = DB::query('
            SELECT  CONCAT(c.parent_url,c.url) as category_url,
            p.url as product_url, p.*, rate, (p.price_course + p.price_course * (IFNULL(rate,0))) as `price_course`, 
            p.`currency_iso` 
            FROM `'.PREFIX.'product` p
            LEFT JOIN `'.PREFIX.'category` c
            ON c.id = p.cat_id
            WHERE p.id IN ('.DB::quote($ids, true).')');
   
          if (!empty($product_res)) {
            while ($result = DB::fetchAssoc($product_res)) {
              $products_all[$result['id']] = $result;
            }          
          }
        }
      foreach ($cartArray['cart'] as $key => $item) {
        $variant = '';
        if (!empty($item['variantId'])) {
        //  $variants = $itemPosition->getVariants($item['id']);
          $variant = $variants_all[$item['variantId']];
        }
        // Заполняет массив информацией о каждом продукте по id из куков.
        // Если куки не актуальны, пропускает товар.
        $product = $products_all[$item['id']];
        if (!empty($product)) {
          $product['property'] = $cartArray['cart'][$key]['propertySetId'];
          $product['property_html'] = htmlspecialchars_decode(str_replace('&amp;', '&', $cartArray['cart'][$key]['property']));
          $product['propertySetId'] = $cartArray['cart'][$key]['propertySetId'];

          if (!empty($variant)) {           
            $product['price'] = $variant['price'];
            $product['code'] = $variant['code'];        
            $product['title'] .= " ".$variant['title_variant'];
            $product['variantId'] = $variant['id'];
          	$product['price_course']  = $variant['price_course'];
          }
          // если установлен формат без копеек то округлим стоимость.
          $priceFormat = $settings['priceFormat'];          
          if(in_array($priceFormat, array('1234','1 234','1,234',''))){               
            $price = round($product['price_course']);          
          }else{
            $price = $product['price_course'];     
          }          
          if ($item['id'] == $product['id']) {
            $count = $item['count'];
            $price = self::plusPropertyMargin($price, $item['propertyReal'], $currencyRate[$product['currency_iso']]);
            $product['price'] = $price;            
            $product['priceInCart'] = self::priceCourse($product['price'] * $count)." ".$settings['currency'];          
            $arrayImages = explode("|", $product['image_url']);            
            if (!empty($arrayImages)) {
              $product['image_url'] = mgImageProductPath($arrayImages[0], $item['id'], $size = 'small');
            }
          }
          $product['link'] = SITE.'/'.(isset($product["category_url"]) ? $product["category_url"] : 'catalog'.'/').$product["product_url"];
          $product['countInCart'] = $item['count'];
          if ($product['countInCart'] > 0) {
            $productPositions[] = $product;
          }
          $totalSumm += $product['price'] * $item['count'];          
         
        }
      }
    }
    $cartData = $productPositions;
    $totalSumm = self::numberFormat($totalSumm).' '.$settings['currency'];
    return array('summCart'=>$totalSumm, 'cartdata'=>$cartData);
}
public function checkCartAndTrigg() {
    $res = DB::query('SELECT * FROM `'.PREFIX.'abandoned-cart-letters` WHERE `auto`=1');    
    $arrLet= array();
    while ($row = DB::fetchArray($res)) {
      $arrLet[] = $row;
    } 
    if (!empty($arrLet)) {
    foreach ($arrLet as $let) {   
      $res = DB::query('SELECT c.*, u.name FROM `'.PREFIX.'abandoned-cart` c '
              . 'LEFT JOIN `'.PREFIX.'user` u ON c.`id_user`= u.`id` '
              . 'WHERE `status` = 1 AND c.`id_letter` NOT LIKE \'%'.DB::quote($let['id'], true).'%\' AND c.`email`<> \'\' LIMIT 100');
      $arrCart= array();
      while ($row = DB::fetchArray($res)) {
        $arrCart[] = $row;
      }
      if (!empty($arrCart)) {
        // проверка времени прошедшее после последнего действия корзины        
          foreach ($arrCart as $cart) {
            if (stristr($cart['id_letter'], $let['id'])===FALSE) {              
              $days = $let['time']=='1' ? 24 : 1;
              $timeSend = $let['period'] * 3600 * $days;
              if (time() - (strtotime($cart['date_act'])&&strtotime($cart['date_act'])>0 ? strtotime($cart['date_act']) : 0) >= $timeSend) {
                self::sendLetter($let, $cart);
              }
           }
          }          
        }
      }
    }
}
 public static function plusPropertyMargin($price, $propertyHtml, $rate) {
    $m = array();
    preg_match_all("/#([\d\.\,-]*)#</i", $propertyHtml, $m);
    $rate = $rate ? $rate : 1;
    if (!empty($m[1])) {
      //находим все составляющие цены характеристик и прибавляем их к общей стоимости позиции
      foreach ($m[1] as $partPrice) {
        $price+=is_numeric($partPrice * 1) ? $partPrice * 1 * $rate : 0;
        
      }
    }
    return $price;
  }
   public static function priceCourse($price, $format = true, $useFloat = null) {  

    if ($useFloat === false) {
      $price = round($price);
    }

    if ($format) {
      $price = self::numberFormat($price);
    }

    return $price;
  }
  /**
   * Форматирует цену в читаемый вид
   * @param type $str - строковое значение цены
   * @param type $type - тип вывода
   * @return string - форматированная строка с ценой.
   */
  public static function numberFormat($str, $type = null) {
    $result = $str;
    $set = DB::query('SELECT `value` FROM `'.PREFIX.'setting` WHERE `option`="priceFormat"');
    $rez = DB::fetchArray($set);
    $priceFormat =$rez['value'];
    if ($type) {
      $priceFormat = $type;
    }

    //без форматирования
    if ($priceFormat == '1234.56') {
      $result = $str;
    } else

    //разделять тысячи пробелами, а копейки запятыми
    if ($priceFormat === '1 234,56') {
      $result = number_format($str, 2, ',', ' ');
    } else

    //разделять тысячи запятыми, а копейки точками
    if ($priceFormat === '1,234.56') {
      $result = number_format($str, 2, '.', ',');
    } else

    //без копеек, без форматирования
    if ($priceFormat == '1234') {
      $result = round($str);
    } else

    //без копеек, разделять тысячи пробелами, а копейки запятыми
    if ($priceFormat == '1 234') {
      $result = number_format(round($str), 0, ',', ' ');
    } else

    //без копеек, разделять тысячи запятыми, а копейки точками
    if ($priceFormat == '1,234') {
      $result = number_format(round($str), 0, '.', ',');
    } else {
      $result = number_format(round($str), 0, ',', ' ');
    }

    $cent = substr($result, -3);

    if ($cent === '.00' || $cent === ',00') {
      $result = substr($result, 0, -3);
    }

    return $result;
  }
}

