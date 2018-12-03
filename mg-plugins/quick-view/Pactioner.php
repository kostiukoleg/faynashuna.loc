<?php

/**
 * Класс Pactioner наследник стандарного Actioner
 * Предназначен для выполнения действий,  AJAX запросов плагина 
 *
 * @author Avdeev Mark <mark-avdeev@mail.ru>
 */
class Pactioner extends Actioner {

  static $pluginName = 'quickView';

  /**
   * Сохраняет  опции плагина
   * @return boolean
   */
  public function saveBaseOption() {
    $this->messageSucces = $this->lang['SAVE_BASE'];
    $this->messageError = $this->lang['NOT_SAVE_BASE'];
    if (!empty($_POST['data'])) {
      MG::setOption(array('option' => self::$pluginName.'Option', 'value' => addslashes(serialize($_POST['data']))));
    }   
    return true;
  }
  /**
   *  Функция создания модального окна для быстрого просмотра товара
   */
  public function buildProductCard() {     
    $productModel = new Models_Product();
    $product = $productModel->getProduct(intval($_POST['id']));    
    $blockVariants = $productModel->getBlockVariants($product['id']);
      $blockedProp = $productModel->noPrintProperty();
      $propertyFormData = $productModel->createPropertyForm($param = array(
        'id' => $product['id'],
        'maxCount' => $product['count'],
        'productUserFields' => $product['thisUserFields'],
        'action' => "/catalog",
        'method' => "POST",
        'ajax' => true,
        'blockedProp' => $blockedProp,
        'noneAmount' => false,
        'noneButton' => $product['count']?false:true,
        'titleBtn' => MG::getSetting('buttonBuyName'),
        'blockVariants' => $blockVariants,
        'currency_iso' => $product['currency_iso'],
        'productData' => $product,
      ));
      $currencyRate = MG::getSetting('currencyRate');      
      $currencyShopIso = MG::getSetting('currencyShopIso');  
        $product['price_course']+=$propertyFormData['marginPrice']; 
        $product['currency_iso'] = $product['currency_iso']?$product['currency_iso']:$currencyShopIso;
      $product['old_price'] = $product['old_price']*$currencyRate[$product['currency_iso']];
      $product['old_price'] = $product['old_price']? $product['old_price']:0;
      $product['price'] = MG::priceCourse($product['price_course']); 
      $product['currency'] = MG::getSetting('currency');
      $product['propertyForm'] = $propertyFormData['html'];
    $data = $product;
    
    
    ob_start();
    include ('layout.php');
    $html = ob_get_contents();
    ob_clean();
    $this->data = (PM::doShortcode($html)); 
    return true;    
  }

}
