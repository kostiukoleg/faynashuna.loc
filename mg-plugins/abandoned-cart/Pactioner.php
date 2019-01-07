<?php

/**
 * Класс Pactioner наследник стандарного Actioner
 * Предназначен для выполнения действий,  AJAX запросов плагина 
 *
 * @author Avdeev Mark <mark-avdeev@mail.ru>
 */
class Pactioner extends Actioner {

  private $pluginName = 'abandoned-cart';

  /**
   * Проверяет правильно ли введены email, телефон и капча 
   * @return boolean
   */
  public function getLetter() {
    //доступно только модераторам и админам.
    USER::AccessOnly('1,4','exit()');
    $this->messageError = $this->lang['ERROR_LETTER'];
    if ($_POST['id']) {
      $rez = DB::query('SELECT * FROM `'.PREFIX.$this->pluginName.'-letters` WHERE `id`='.DB::quote(intval($_POST['id'])));
      if ($row = DB::fetchArray($rez)) {        
        $this->data = $row;
        $this->data['special'] = unserialize(stripslashes($row['special']));
        return true;
      }
    }
    return false;
  }
  /**
   * Сохраняет и обновляет параметры письма.
   * @return type
   */
  public function saveLetter() {   
    //доступно только модераторам и админам.
    USER::AccessOnly('1,4','exit()');
    $this->messageSucces = $this->lang['ENTITY_SAVE'];
    $this->messageError = $this->lang['ENTITY_SAVE_NOT'];
    if (!empty($_POST['id'])) {  
      if (!empty($_POST['data']['special'])) {
        $_POST['data']['special'] = addslashes(serialize($_POST['data']['special']));
      }
      if (DB::query('
        UPDATE `'.PREFIX.$this->pluginName.'-letters`
        SET '.DB::buildPartQuery($_POST['data']).'
        WHERE id = '.DB::quote(intval($_POST['id'])))) {
        $this->data['title'] = $_POST['data']['title'];
        $this->data['auto'] = $_POST['data']['auto']=='1' ? $this->lang['ON'] : $this->lang['OFF'];
        $this->data['autoclass'] = $_POST['data']['auto']=='1' ? 'paid' : 'dont-paid';
        return true;
      } 
    }
    return false;
  }
/**
   * Устанавливает количество отображаемых записей в разделе 
   * @return boolean
   */
  public function setCountPrintRowsEnity(){
    //доступно только модераторам и админам.
    USER::AccessOnly('1,4','exit()');	
    $this->messageSucces = $this->lang['ENTITY_DEL_CART'];
    $this->messageError = $this->lang['ENTITY_DEL_NOT_CART'];
    $count = 20;
    if(is_numeric($_POST['count'])&& !empty($_POST['count'])){
      $count = $_POST['count'];
    }
    MG::setOption(array('option'=>$_POST['option'], 'value'=>$count));
    return true;
  }
  /*
   * удаляет коризину из бд
   */
public function deleteCart($id=''){
  USER::AccessOnly('1,4','exit()');	
  $id = $id ? $id : $_POST['id'];
  if ($id) {
    DB::query('DELETE FROM `'.PREFIX.$this->pluginName.(isset($_POST['cart']) ? '-owncart' : '').'` 
               WHERE `id`='.DB::quote(intval($id)));
    return true;
  }
  return false;
}
public function previewLetter($id='', $let=''){
  USER::AccessOnly('1,4','exit()');	
  $id = $id ? $id : $_POST['id'];
  $let = $let ? $let : $_POST['let'];
  $res = DB::query('SELECT c.*, u.name FROM `'.PREFIX.$this->pluginName.'` c '
          . 'LEFT JOIN `'.PREFIX.'user` u ON u.email=c.email '
          . 'WHERE c.`id`='.DB::quote(intval($id)));
  if ($row = DB::fetchArray($res)) {
    $cart = $row;
    $res = DB::query('SELECT `text`,`subject` FROM `'.PREFIX.$this->pluginName.'-letters` '
            . 'WHERE `id`='.DB::quote(intval($let)));
    if ($let = DB::fetchArray($res)) {
      $text = $let['text'];
      if (empty($cart['hash'])){      
        $cart['hash'] = htmlspecialchars(crypt($cart['email']));
        DB::query('UPDATE `'.PREFIX.$this->pluginName.'` SET  `hash` ='.DB::quote($cart['hash'])
              . ' WHERE `id`='.DB::quote(intval($id)));
      }
      $option = MG::getSetting('abandonedCartOption');
      $option = stripslashes($option);
      $options = unserialize($option);
      $link = '<a href="'.SITE.'/basket?cart='.$cart['hash'].'&id='.$id.'" target="blank">'.$options['return'].'</a>';
      $linkCan = '<a href="'.SITE.'/basket?cancel='.$cart['hash'].'&id='.$id.'" target="blank">'.$options['cancel'].'</a>';
      $content = $this->getHtmlCart($cart['content']);
      ob_start();
      include ('layout_cart.php');
      $html = ob_get_contents();
      ob_clean();
      $text = str_replace('{name}', $cart['name'], $text);
      $text = str_replace('{linkOrder}', $link, $text);
      $text = str_replace('{linkCancel}', $linkCan, $text);
      $text = str_replace('{cartContent}', $html, $text);
      $this->data['html']= $text;
      $this->data['email']= $cart['email'];
      $this->data['subject']=$let['subject'];
      return TRUE;
    }
  }
}
static public function getHtmlCart($cart){
  $productPositions = array();
      $totalSumm = 0;
      $cartArray = unserialize(stripslashes($cart));
    if (!empty($cartArray['cart'])) {
      $currencyRate = MG::getSetting('currencyRate');   
      $currencyShopIso = MG::getSetting('currencyShopIso');
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
            $product['count'] = $variant['count'];
            $product['weight'] = $variant['weight'];        
            $product['image_url'] = $variant['image']?$variant['image']:$product['image_url'];
            $product['title'] .= " ".$variant['title_variant'];
            $product['variantId'] = $variant['id'];
          	$product['price_course']  = $variant['price_course'];
          }
          // если установлен формат без копеек то округлим стоимость.
          $priceFormat = MG::getSetting('priceFormat');          
          if(in_array($priceFormat, array('1234','1 234','1,234',''))){               
            $price = round($product['price_course']);          
          }else{
            $price = $product['price_course'];     
          }          
          if ($item['id'] == $product['id']) {
            $count = $item['count'];
            $price = SmalCart::plusPropertyMargin($price, $item['propertyReal'], $currencyRate[$product['currency_iso']]);
            $product['price'] = $price;            
            $product['priceInCart'] = MG::priceCourse($product['price'] * $count)." ".MG::getSetting('currency');          
            $arrayImages = explode("|", $product['image_url']);            
            if (!empty($arrayImages)) {
              $product['image_url'] = mgImageProductPath($arrayImages[0], $item['id'], $size = 'small');
            }
          }
          $product['category_url'] = (SHORT_LINK == '1' ? '' : $product['category_url'].'/');
          $row['category_url'] = ($row['category_url'] == '/' ? '' : $row['category_url']);
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
   $totalSumm = MG::numberFormat($totalSumm).' '.MG::getSetting('currency');
    return array('summCart'=>$totalSumm, 'cartdata'=>$cartData);
}
public function sendLetter($id='', $let='', $text='', $email='', $subject=''){
  USER::AccessOnly('1,4','exit()');	
  $this->messageSucces = $this->lang['LETTER_SENDED'];
  $this->messageError = $this->lang['LETTER_NOT_SENDED'];
  $text = !empty($_POST['text']) ? $_POST['text'] : $text;
  $id = $id ? $id : $_POST['id'];
  $let = $let ? $let : $_POST['let'];
  if (!$text){
    $this->previewLetter($id, $let);
    $text = $this->data['html'];
    $email = $this->data['email'];
    $sub = $this->data['subject'];
  } else {
    $text = $_POST['text'];
    $email = $_POST['email'];
    $sql = DB::query('SELECT `subject` FROM `'.PREFIX.$this->pluginName.'-letters` '
            . 'WHERE `id`='.DB::quote(intval($_POST['let'])));
    if ($row = DB::fetchArray($sql)) {
      $sub = $row['subject'];
    }
  }  
  $option = MG::getSetting('abandonedCartOption');
  $option = stripslashes($option);
  $options = unserialize($option);
  $rez = DB::query('SELECT `date_update` FROM`'.PREFIX.$this->pluginName.'` '
          . 'WHERE `id`='.DB::quote(intval($id)));
  if ($row = DB::fetchArray($rez)) {
    $lasttime = strtotime($row['date_update']);
    if ($lasttime&&$lasttime>0
            &&time()- $lasttime <= $options['diff']) {
      $this->messageError .= $this->lang['ERROR_SEND1'].' '.$options['diff'].' '.$this->lang['ERROR_SEND2'];
      return false;
    }       
  }   
  $sitename = MG::getSetting('sitename');
  if(preg_match('/^[-._a-zA-Z0-9]+@(?:[a-zA-Z0-9][-a-zA-Z0-9]+\.)+[a-zA-Z]{2,6}$/', $email)){
  Mailer::sendMimeMail(array(
			  'nameFrom' => MG::getSetting('noReplyEmail'),
			  'emailFrom' => MG::getSetting('noReplyEmail'),
			  'nameTo' => $sitename,
			  'emailTo' => $email,
			  'subject' => $sub ? $sub : $this->lang['SUBJECT_DEFAULT'],
			  'body' => $text,
			  'html' => true
			));
  DB::query('UPDATE `'.PREFIX.$this->pluginName.'` SET '
          . '`date_update`=now(), `id_letter`=CONCAT(`id_letter`,\'|'.DB::quote($_POST['let'], true).'\') WHERE `id`='.DB::quote(intval($_POST['id'])));
  $this->data['date']=date('d.m.Y H:i');
  }
  return true;
}
// добавляет email к корзине, если не авторизрван
  public function addEmailCart(){
    if (!USER::isAuth()) {
      $email = $_POST['email'];
      $id = $_POST['id'];
      $res = DB::query('SELECT `email` FROM `'.PREFIX.$this->pluginName.'` WHERE `id`='.DB::quote(intval($id)));
      if ($row = DB::fetchArray($res)) {
          DB::query('UPDATE `'.PREFIX.$this->pluginName.'` SET '
          . '`email`='.DB::quote($email).' WHERE `id`='.DB::quote(intval($id)));  
      }
    }
    return true;
  }
  /**
   * Сохраняет  опции плагина
   * @return boolean
   */
  public function saveBaseOption() {
    //доступно только модераторам и админам.
    USER::AccessOnly('1,4','exit()');	
    $this->messageSucces = $this->lang['SAVE_BASE'];
    $this->messageError = $this->lang['NOT_SAVE_BASE'];
    if (!empty($_POST['data'])) {
      MG::setOption(array('option' => 'abandonedCartOption', 'value' => addslashes(serialize($_POST['data']))));
    }   
    return true;
  }
    /**
   * Выполняет операцию над отмеченными товарами в админке.
   * @return boolean
   */
  public function operationCart() {
     USER::AccessOnly('1,4','exit()');
    $this->messageSucces = $this->lang['DONE'];
    $operation = $_POST['operation'];
    if (empty($_POST['carts_id'])) {
      $this->messageError = $this->lang['NEED_CHOOSE'];
      return false;
    }
    if ($operation == 'delete') {
      foreach ($_POST['carts_id'] as $cartId) {
        $this->deleteCart($cartId);
      }
    } elseif (strpos($operation, 'send') === 0 && !empty($_POST['carts_id'])) {
      if (empty($_POST['let'])||($_POST['let'])=='0') {
        $this->messageError = $this->lang['NEED_CHOOSE_LET'];
        return false;
      }
      foreach ($_POST['carts_id'] as $id) {
        $this-> sendLetter($id, $_POST['let']);
      }
    } 
    return true;
  }
  // новый id для корзины
  public function getNextIdCart(){
     USER::AccessOnly('1,4','exit()');
     $next = DB::query('SELECT max(`id`) as id FROM `'.PREFIX.$this->pluginName.'-owncart`');
     if ($id = DB::fetchArray($next)) {
       $this->data['id'] = $id['id']+1;
     } else {
       $this->data['id'] = 1;
     }
     return true;     
  }
  // сохраняет корзину
  public function addOwnCart(){
    USER::AccessOnly('1,4','exit()');
    $this->messageSucces = $this->lang['SAVE_CART'];
    $this->messageError = $this->lang['NOT_SAVE_CART'];
    if (!empty($_POST)){
      $array['title'] =  $_POST['title'];
      $array['url']= $_POST['url'];
      $array['date_create'] = date('Y-m-d H:i:s');
      $array['id']=$_POST['id'];
      $cart = new Models_Cart;
      $result = array();
      foreach ($_POST['data'] as $itempost) {
        $item = array();
        foreach (explode('&', $itempost) as $item2) {
          $items = explode('=', $item2);
          $item[urldecode($items[0])]=urldecode($items[1]);
        }
        $variantId = null;
    if (!empty($item["variant"])) {
      $variantId = $item["variant"];
      unset($item["variant"]);
    }
        $id = $item['inCartProductId'];
        $count = $item['amount_input'];
        $property = array('property' => '', 'propertyReal' => ''); 
        $property = $cart->createProperty($item);
    $propertyReal = $property['propertyReal'];
    $property = $property['property'];
    if (empty($count) || !is_numeric($count)) {
      $count = 1;
    }
    $property = str_replace('%', '&#37;', $property);
    $property = str_replace('&', '&amp;', htmlspecialchars($property));
    $result['propertySetArray'][] = $property;
      $lastKey = array_keys($result['propertySetArray']);
      $lastKey = end($lastKey);
      if ($variant) {
        $id = $variant;
      }
      if(count($result['cart']) < MAX_COUNT_CART){

        $result['cart'][] = array(
          'id' => $id, 
          'count' => $count, 
          'property' => $property, 
          'propertyReal' => $propertyReal, 
          'propertySetId' => $lastKey, 
          'variantId' => $variantId
        );
      
      }
    }     
    MG::loger(print_r($result, true));
      $result['couponCode'] = $_POST['coupone'];
      $array['content'] = addslashes(serialize($result));      
      if (DB::buildQuery('INSERT INTO `'.PREFIX.$this->pluginName.'-owncart` SET ',$array )) {
        return true;
      } else{
        unset($array['id']);
        if (DB::buildQuery('INSERT INTO `'.PREFIX.$this->pluginName.'-owncart` SET ',$array )) {
          return true;
        }
      }      
    }
    return false;
  }
}
