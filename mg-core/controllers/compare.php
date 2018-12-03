<?php
/**
 * Контроллер: Сompare
 *
 * Класс Controllers_Сompare создает таблицу сравнения строковых характеристик товаров.
 * - выводит добавленные к сравнению карточки товаров;
 * - в зависимости от настроек разделяет товары на категории;
 *
 * @author Авдеев Марк <mark-avdeev@mail.ru>
 * @package moguta.cms
 * @subpackage Controller
 */
class Controllers_Compare extends BaseController {

  /**
   * Определяет поведение при изменении и удаление данных в корзине,
   * а так же выводит список позиций к заказу.
   * @return void
   */
  public function __construct() {

    $productModel = new Models_Product();
    if (isset($_GET['delCompareProductId'])) {
      foreach ($_SESSION['compareList'] as $key => $category) {
        unset($_SESSION['compareList'][$key][$_GET['delCompareProductId']]);
      }

      foreach ($_SESSION['compareList'] as $key => $category) {
        if (empty($category)) {
          unset($_SESSION['compareList'][$key]);
        }
      }
    }
    if (isset($_GET['delCompare'])) {
      unset($_SESSION['compareList']);
    }

    // viewData($_SESSION);


    if (isset($_GET['inCompareProductId'])) {

      $prodData = $productModel->getProduct($_GET['inCompareProductId']);
      if ($prodData) {
        if ($prodData['cat_id'] >= 0) {
          $_GET['viewCategory'] === $prodData['cat_id'];
          $_SESSION['compareList'][$prodData['cat_id']][$_GET['inCompareProductId']] = $_GET['inCompareProductId'];
          }
        }
      }

    // Если не задана категория, то выводим товары из первой.
    if (!isset($_GET['viewCategory'])) {
      if (!empty($_SESSION['compareList'])) {
        $idCategory = array_keys($_SESSION['compareList']);
        $_GET['viewCategory'] = $idCategory[0];
      }
    }

    $error = '';
    if (MG::getSetting('compareCategory') != 'true') {
      $listCatId[] = $_GET['viewCategory'];
    } else {
      foreach ($_SESSION['compareList'] as $idCat => $idsProd) {
        $listCatId[] = $idCat;
      }
    }

    $arrCategory = MG::get('category')->getArrayCategory();

    $catIds = array(0);
    $arrCategoryTitle = array();
    if (!empty($_SESSION['compareList'])) {
      $catIds = array();
      foreach ($_SESSION['compareList'] as $catId => $v) {

        if ($catId > 0) {
          $arrCategoryTitle[$catId] = $arrCategory[$catId]['title'];
        }
        if ($catId === 0) {
          $arrCategoryTitle[$catId] = 'Каталог';
        }
        $catIds[] = $catId;
      }
    }

    $moreThanThree = '';
    if (count($catalogItems) > 3) {
      $moreThanThree = 'more-than-three';
    }

    $_SESSION['compareCount'] = 0;
    if (!empty($_SESSION['compareList'])) {
      foreach ($_SESSION['compareList'] as $category) {
        $_SESSION['compareCount'] += count($category);
      }
    }

    if (isset($_GET['updateCompare'])) {
      $array = array('count' => $_SESSION['compareCount']);
      echo json_encode($array);
      exit();
    }

    // Получаем все характеристики для текущей категории и вложенных в нее,
    // а также характеристики выводимые для всех категорий.
    foreach ($catIds as $key => $value) {
      if($value == '') unset($catIds[$key]);
    }    
    $catIds = implode(',', $catIds);
    if(!empty($catIds)) $catIds = 'cp.category_id IN ('.$catIds.') and';
    $sql = "
      SELECT * FROM `".PREFIX."property` as pp
      LEFT JOIN `".PREFIX."category_user_property` as cp
         ON  pp.id = cp.property_id
      WHERE ".DB::quote($catIds, true)." pp.filter = 1
        ORDER BY pp.sort DESC
    ";
    $res = DB::query($sql);
    while ($row = DB::fetchAssoc($res)) {
      $property[$row['name']] = $row['description'];
      $propertyList[] = $row;
    }

    $info = $this->getInfoProducts($listCatId, $propertyList);

    if (!empty($info)) {
      $catalogItems = $info['catalogItems'];
    } else {
      // $error = "Нет товаров для сравнения в этой категории";
      $error = MG::restoreMsg('msg__no_compare');
    }

    $this->data = array(
      'error' => $error,
      'compareList' => $_SESSION['compareList'],
      'catalogItems' => $catalogItems,
      'arrCategoryTitle' => $arrCategoryTitle,
      'moreThanThree' => $moreThanThree,
      'meta_title' => 'Список сравнения товаров',
      'meta_keywords' => !empty($model->currentCategory['meta_keywords'])?$model->currentCategory['meta_keywords']:"сравнение,сравнить",
      'meta_desc' => !empty($model->currentCategory['meta_desc'])?$model->currentCategory['meta_desc']:"Список сравнения товаров",
      'property' => $property
    );
    // viewData($this->data);
  }

  /**
   * Получает информацию о каждом товаре.
   * @param array $viewCategoryId - массив id категорий.
   * @param array $property - массив с характеристиками.
   * @return array
   */
  public function getInfoProducts($viewCategoryId, $property) {
    DB::query('SELECT `add_security` FROM `'.PREFIX.'payment` WHERE `add_security`='.DB::quote(md5(MG::getSetting('sitename'))));
    if (empty($viewCategoryId)) {
      return false;
    }

    $listProductsArray = array();
    $countProduct = 0;
    
    foreach ($viewCategoryId as $k => $id) {
      $listProductsIdTemp = $_SESSION['compareList'][$id];
      $countProduct += count($_SESSION['compareList'][$id]);
      $listProductsArray = array_merge($listProductsArray, $listProductsIdTemp);
    }
    foreach ($listProductsArray as &$value) {
      $value = intval($value);
    }
    $listProductsId = implode(',', $listProductsArray);
    $catalogModel = new Models_Catalog();
    $productModel = new Models_Product();

    if (!empty($listProductsId)) {
      $arrProduct = $catalogModel->getListByUserFilter(
        $countProduct, ' p.id IN ('.DB::quote($listProductsId, true).')'
      );
    }

    $arrProduct['catalogItems'] = MG::loadWholeSalesToCatalog($arrProduct['catalogItems']);

    $currencyRate = MG::getSetting('currencyRate');
    $currencyShopIso = MG::getSetting('currencyShopIso');

    foreach ($arrProduct['catalogItems'] as &$product) {
      $product['thisUserFields'] = array();
      $propToProd = $property;
      Property::addDataToProp($propToProd, $product['id']);
      foreach ($propToProd as $item) {
        if(empty($item['data'])) continue;
        $item['value'] = $item['data'][0]['name'];
        $product['thisUserFields'][] = $item;
      }

      $blockVariants = $productModel->getBlockVariants($product['id']);
      $blockedProp = $productModel->noPrintProperty();
       $buyButton = '';   
      if ($product['count'] == 0) {
        $buyButton = '<a href="'.SITE.'/'.((MG::getSetting('shortLink') != 'true')&&($product["category_url"]=='') ? 'catalog/' : $product["category_url"]) . $product["product_url"] . '" class="product-info">' . MG::getSetting('buttonMoreName') . '</a>';;
        if (!empty($blockVariants)) {
          $buyButton .= '<a style="display:none" href="' . SITE . '/catalog?inCartProductId=' . $product["id"] . '" rel="nofollow" class="addToCart buy-product buy" data-item-id="' . $product["id"] . '">' . MG::getSetting('buttonBuyName') . '</a>';
        }
      } else {
        $actionButton = MG::getSetting('actionInCatalog') === "true" ? 'actionBuy' : 'actionView';
        $buyButton = '<a href="' . SITE . '/catalog?inCartProductId=' . $product["id"] . '" rel="nofollow" class="addToCart addToCart buy-product buy" data-item-id="' . $product["id"] . '">' . MG::getSetting('buttonBuyName') . '</a>';
        if (!empty($blockVariants)) {
          $buyButton .= '<a style="display:none" href="'.SITE.'/'.((MG::getSetting('shortLink') != 'true')&&($product["category_url"]=='') ? 'catalog/' : $product["category_url"]) . $product["product_url"] . '" rel="nofollow" class="product-info action_buy_variant">' . MG::getSetting('buttonMoreName') . '</a>';
        }        
      }
      
      $propertyFormData = $productModel->createPropertyForm($param = array(
        'id' => $product['id'],
        'maxCount' => $product['count'],
        'productUserFields' => $product['thisUserFields'],
        'action' => "/catalog",
        'method' => "POST",
        'ajax' => true,
        'blockedProp' => $blockedProp,
        'noneAmount' => false,
        'titleBtn' => MG::getSetting('buttonBuyName'),
        'blockVariants' => $blockVariants,
        'printStrProp' => 'false',
        'printCompareButton' => 'false',
        'currency_iso' => $product['currency_iso'],
        'buyButton'=> $buyButton
      ));

      if ($product['count'] < 0) {
        $product['count'] = "много";
      };

      if (!empty($product['variants'])) {
        $product["price"] = MG::numberFormat($product['variants'][0]["price_course"]);
        $product["old_price"] = $product['variants'][0]["old_price"];
        $product["count"] = $product['variants'][0]["count"];
        $product["code"] = $product['variants'][0]["code"];
        $product["weight"] = $product['variants'][0]["weight"];
        $product["price_course"] = $product['variants'][0]["price_course"];
        $product["variant_exist"] = $product['variants'][0]["id"];
      }

      $product['price']+=$propertyFormData['marginPrice'];
      $product['currency_iso'] = $product['currency_iso']?$product['currency_iso']:$currencyShopIso;
      $product['currency'] = MG::getSetting('currency');   
      $product['price'] = MG::priceCourse($product['price_course'], true, true);
      $product['propertyForm'] = $propertyFormData['html'];
      $product['propertyNodummy'] = $propertyFormData['propertyNodummy'];
      foreach ($propertyFormData['stringsProperties'] as $key => $value) {
        if(is_array($value)) {
          $propertyFormData['stringsProperties'][$key] = $value[0]['name'];
        }
      }
      $product['stringsProperties'] = $propertyFormData['stringsProperties'];
      $product['image_url'] = explode('|', $product['image_url']);
      $product['image_url'] = $product['image_url'][0];
    }

    return array('catalogItems' => $arrProduct['catalogItems']);
  }

}
