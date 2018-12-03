<?php

/**
 * Модель: Product
 *
 * Класс Models_Product реализует логику взаимодействия с товарами магазина.
 * - Добавляет товар в базу данных;
 * - Изменяет данные о товаре;
 * - Удаляет товар из базы данных;
 * - Получает информацию о запрашиваемом товаре;
 * - Получает продукт по его URL;
 * - Получает цену запрашиваемого товара по его id.
 *
 * @author Авдеев Марк <mark-avdeev@mail.ru>
 * @package moguta.cms
 * @subpackage Model
 * 
 */
class Models_Product {

  public static $storage = 'all';

  /**
   * Добавляет товар в базу данных. 
   * <code>
   * $array = array(
   *  'title' => 'title', // название товара
   *  'url' => 'link', // последняя часть ссылки на товар
   *  'code' => 'CN230', // артикул товара
   *  'price' => 100, // цена товара
   *  'old_price' => 200, // старая цена товара
   *  'image_url' => 1434653074061713.jpg, // последняя часть ссылки на изображение товара
   *  'image_title' => '', // title изображения товара
   *  'image_alt' => '', // alt изображения товара
   *  'count' => 77, // остаток товара
   *  'weight' => 5, // вес товара
   *  'cat_id' => 4, // ID основной категории товара
   *  'inside_cat' => '1,2', // дополнительные категории товаров
   *  'description' => 'descr', // описание товара
   *  'short_description' => 'short descr', // краткое описание товара
   *  'meta_title' => 'title', // seo название товара
   *  'meta_keywords' => 'title купить, CN230, title', // seo ключевые слова
   *  'meta_desc' => 'meta descr', // seo описание товара
   *  'currency_iso' => 'RUR', // код валюты товара
   *  'recommend' => 0, // выводить товар в блоке рекомендуемых
   *  'activity' => 1, // выводить товар
   *  'unit' => 'шт.', // единица измерения товара (если null, то используется единица измерения основной категории товара)
   *  'new' => 0, // выводить товар в блоке новинок
   *  'userProperty' => Array, // массив с характеристиками товара
   *  'related' => 'В-500-1', // артикулы связанных товаров
   *  'variants' => Array, // массив с вариантами товаров
   *  'related_cat' => null, // ID связанных категорий
   *  'lang' => 'default', // язык для сохранения
   *  'landingTemplate' => 'noLandingTemplate', // шаблон для лэндинга товара
   *  'ytp' => '', // строка с торговым предложением для лэндинга
   *  'landingImage' => 'no-img.jpg', // изображение для лэндинга
   *  'storage' => 'all' // склад товара
   * );
   * $model = new Models_Product();
   * $id = $model->addProduct($product);
   * echo $id;
   * </code>
   * @param array $array массив с данными о товаре.
   * @param bool $clone происходит ли клонирование или обычное добавление товара
   * @return int|bool в случае успеха возвращает id добавленного товара.
   */
  public function addProduct($array, $clone = false) {
    if(empty($array['title'])) {
      return false;
    }

    $userProperty = $array['userProperty'];
    $variants = !empty($array['variants']) ? $array['variants'] : array(); // варианты товара
    unset($array['userProperty']);
    unset($array['variants']);
    unset($array['count_sort']);
    unset($array['lang']);
    if(empty($array['id'])) {
      unset($array['id']);
    }

    if($array['code'] == '') {
      $res = DB::query('SELECT max(id) FROM '.PREFIX.'product');
      $id = DB::fetchAssoc($res);
      $array['code'] = MG::getSetting('prefixCode').($id['max(id)']+1);
    }

    $result = array();

    $array['url'] = empty($array['url']) ? MG::translitIt($array['title']) : $array['url'];

    $maskField = array('title','meta_title','meta_keywords','meta_desc','image_title','image_alt');

    foreach ($array as $k => $v) {
      if(in_array($k, $maskField)) {
        $v = htmlspecialchars_decode($v);
        $array[$k] = htmlspecialchars($v);       
      }
    }

    if (!empty($array['url'])) {
      $array['url'] = URL::prepareUrl($array['url']);
    }

    // Исключает дублирование.
    $dublicatUrl = false;
    $tempArray = $this->getProductByUrl($array['url']);
    if (!empty($tempArray)) {
      $dublicatUrl = true;
    }

    if($array['weight']) {
     $array['weight'] = (double)str_replace(array(',',' '), array('.',''), $array['weight']);
    }else {
      $array['weight'] = 0;
    }

    if($array['price']) {
      $array['price'] = (double)str_replace(array(',',' '), array('.',''), $array['price']);
    }

    $array['sort'] = 0;
    $array['system_set'] = 1;

    //сохранение настроек лендинга
    $landArr['landingTemplate'] = $array['landingTemplate'];
    $landArr['landingColor'] = $array['landingColor'];
    $landArr['ytp'] = $array['ytp'];
    $landArr['landingImage'] = $array['landingImage'];
    $landArr['landingSwitch'] = $array['landingSwitch'];

    unset($array['landingTemplate']);
    unset($array['landingColor']);
    unset($array['ytp']);
    unset($array['landingImage']);
    unset($array['landingSwitch']);

    unset($array['storage']);

    unset($array['color']);
    unset($array['size']);

    if (DB::buildQuery('INSERT INTO `'.PREFIX.'product` SET ', $array)) {
      $id = DB::insertId();

      // Если url дублируется, то дописываем к нему id продукта.
      if ($dublicatUrl) {
        $url_explode = explode('_', $array['url']);
        if (count($url_explode) > 1) {
          $array['url'] = str_replace('_'.array_pop($url_explode), '', $array['url']);
        }
        $updateArray = array(
          'id' => $id, 
          'url' => $array['url'].'_'.$id, 
          'sort' => $id, 
          'landingTemplate' => $landArr['landingTemplate'], 
          'landingColor' => $landArr['landingColor'], 
          'ytp' => $landArr['ytp'], 
          'landingImage' => $landArr['landingImage'], 
          'landingSwitch' => $landArr['landingSwitch']
        );
        if ($clone) {
          $updateArray['code'] = MG::getSetting('prefixCode').$id;          
          $array['code'] = MG::getSetting('prefixCode').$id;
        }
        $this->updateProduct($updateArray);
      } else {
        $updateArray = array(
          'id' => $id, 
          'url' => $array['url'], 
          'sort' => $id, 
          'landingTemplate' => $landArr['landingTemplate'], 
          'landingColor' => $landArr['landingColor'], 
          'ytp' => $landArr['ytp'], 
          'landingImage' => $landArr['landingImage'], 
          'landingSwitch' => $landArr['landingSwitch']
        );
        if ($clone) {
          $updateArray['code'] = MG::getSetting('prefixCode').$id;
          $array['code'] = MG::getSetting('prefixCode').$id;
        }
        $this->updateProduct($updateArray);
      }
      unset($landArr);
      
      $array['id'] = $id;
      $array['sort'] = (int)$id;
      $array['userProperty'] = $userProperty;
      $userProp = array();

      if ($clone) {
        if (!empty($userProperty)) {
          foreach ($userProperty as $property) {
            $userProp[$property['property_id']] = $property['value'];
            if (!empty($property['product_margin'])) {
              $userProp[("margin_".$property['property_id'])] = $property['product_margin'];
            }
          }
          $userProperty = $userProp;
        }
      }

      if (!empty($userProperty)) {
        $this->saveUserProperty($userProperty, $id);
      }

      // Обновляем и добавляем варианты продукта.      
      $this->saveVariants($variants, $id);
      $variants = $this->getVariants($id);
      foreach ($variants as $variant) {
        $array['variants'][] = $variant;
      }

      $tempProd = $this->getProduct($id);
      $array['category_url'] = $tempProd['category_url'];
      $array['product_url'] = $tempProd['product_url'];

      $result = $array;
    }
    
    $this->updatePriceCourse($currencyShopIso, array($result['id']));  

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Изменяет данные о товаре.
   * <code>
   * $array = array(
   *  'id' => 23, // ID товара
   *  'title' => 'title', // название товара
   *  'url' => 'link', // последняя часть ссылки на товар
   *  'code' => 'CN230', // артикул товара
   *  'price' => 100, // цена товара
   *  'old_price' => 200, // старая цена товара
   *  'image_url' => 1434653074061713.jpg, // последняя часть ссылки на изображение товара
   *  'image_title' => '', // title изображения товара
   *  'image_alt' => '', // alt изображения товара
   *  'count' => 77, // остаток товара
   *  'weight' => 5, // вес товара
   *  'cat_id' => 4, // ID основной категории товара
   *  'inside_cat' => '1,2', // дополнительные категории товаров
   *  'description' => 'descr', // описание товара
   *  'short_description' => 'short descr', // краткое описание товара
   *  'meta_title' => 'title', // seo название товара
   *  'meta_keywords' => 'title купить, CN230, title', // seo ключевые слова
   *  'meta_desc' => 'meta descr', // seo описание товара
   *  'currency_iso' => 'RUR', // код валюты товара
   *  'recommend' => 0, // выводить товар в блоке рекомендуемых
   *  'activity' => 1, // выводить товар
   *  'unit' => 'шт.', // единица измерения товара (если null, то используется единица измерения основной категории товара)
   *  'new' => 0, // выводить товар в блоке новинок
   *  'userProperty' => Array, // массив с характеристиками товара
   *  'related' => 'В-500-1', // артикулы связанных товаров
   *  'variants' => Array, // массив с вариантами товаров
   *  'related_cat' => null, // ID связанных категорий
   *  'lang' => 'default', // язык для сохранения
   *  'landingTemplate' => 'noLandingTemplate', // шаблон для лэндинга товара
   *  'ytp' => '', // строка с торговым предложением для лэндинга
   *  'landingImage' => 'no-img.jpg', // изображение для лэндинга
   *  'storage' => 'all' // склад товара
   * );
   * $model = new Models_Product();
   * $model->updateProduct($array);
   * </code>
   * @param array $array массив с данными о товаре.
   * @return bool
   */
  public function updateProduct($array) {
    $id = $array['id'];
    $userProperty = !empty($array['userProperty']) ? $array['userProperty'] : null; //свойства товара
    $variants = !empty($array['variants']) ? $array['variants'] : array(); // варианты товара
    $updateFromModal = !empty($array['updateFromModal']) ? true : false; // варианты товара

    unset($array['userProperty']);
    unset($array['variants']);
    unset($array['updateFromModal']);

    if (!empty($array['url'])) {
      $array['url'] = URL::prepareUrl($array['url']);
    }

    // перехватываем данные для записи, если выбран другой язык
    $lang = $array['lang'];
    define(LANG, $lang);
    unset($array['lang']);

    $filter = array('title','meta_title','meta_keywords','meta_desc','description','short_description','unit');
    $localeData = MG::prepareLangData($array, $filter, $lang);

    $filterLanding = array('ytp');
    $localeDataLanding = MG::prepareLangData($array, $filterLanding, $lang);

    // фильтрация данных
    $maskField = array('title','meta_title','meta_keywords','meta_desc','image_title','image_alt');
    foreach ($array as $k => $v) {
      if(in_array($k, $maskField)) {
        $v = htmlspecialchars_decode($v);
        $array[$k] = htmlspecialchars($v);       
      }
    }
	
    $result = false;

    // Если происходит обновление параметров.
    if (!empty($id)) {
      unset($array['delete_image']);

      if($array['weight']) {
        $array['weight'] = (double)str_replace(array(',',' '), array('.',''), $array['weight']);
      }

      if($array['price']) {
        $array['price'] = (double)str_replace(array(',',' '), array('.',''), $array['price']);
      }
      if($array['price_course']) {
        $array['price_course'] = (double)str_replace(array(',',' '), array('.',''), $array['price_course']);
      }
      if(empty($array['price_course'])) {
        unset($array['price_course']);
      } 

      //сохранение настроек лендинга
      $tmp = explode('/', $array['landingImage']);
      $tmp = array_slice($tmp, -1);
      if ($tmp[0] == 'no-img.jpg') {
        unset($array['landingImage']);
      }

      if ($array['landingTemplate'] == 'noLandingTemplate' && $array['ytp'] == '' && !isset($array['landingImage']) && $array['landingSwitch'] == -1 || $array['landingTemplate'] == '') {
        DB::query("DELETE FROM `".PREFIX."landings` where id = ".DB::quoteInt($id));
      }
      else{
        DB::query("INSERT INTO `".PREFIX."landings` (id, template, templateColor, ytp, image, buySwitch) 
        VALUES(".DB::quoteInt($id).", ".DB::quote($array['landingTemplate']).", ".DB::quote($array['landingColor']).", ".DB::quote($array['ytp']).", ".DB::quote($array['landingImage']).", ".DB::quote($array['landingSwitch']).") 
        ON DUPLICATE KEY UPDATE template = ".DB::quote($array['landingTemplate']).", templateColor = ".DB::quote($array['landingColor']).", ytp = ".DB::quote($array['ytp']).", image = ".DB::quote($array['landingImage']).", buySwitch = ".DB::quote($array['landingSwitch']));
      }

      unset($array['landingTemplate']);
      unset($array['landingColor']);
      unset($array['ytp']);
      unset($array['landingImage']);
      unset($array['landingSwitch']);

      // фикс для размерной сетки, чтобы сюда не шло то, что не надо
      unset($array['color']);
      unset($array['size']);

      // если есть склады, 
      if(MG::enabledStorage()) {
        $count = $array['count'];
        unset($array['count']);
      }
      $storage = $array['storage'];

      unset($array['storage']);

      foreach ($array as $key => $value) {
        if($key == '') unset($array[$key]);
      }

      // Обновляем стандартные  свойства продукта.
      if (DB::query('
          UPDATE `'.PREFIX.'product`
          SET '.DB::buildPartQuery($array).'
          WHERE id = '.DB::quote($id))) {

        // сохраняем локализацию
        MG::saveLocaleData($id, $lang, 'product', $localeData);
        MG::saveLocaleData($id, $lang, 'landings', $localeDataLanding);

        // Обновляем пользовательские свойства продукта.
        if (!empty($userProperty)) {
          $this->saveUserProperty($userProperty, $id);
        }

        // сохраняем количество товара на определенном складе
        if(MG::enabledStorage()) {
          if($storage != 'all') {
            $res = DB::query('SELECT id FROM '.PREFIX.'product_on_storage WHERE product_id = '.DB::quote($id).' 
              AND storage = '.DB::quote($storage).' AND variant_id = 0');
            if($row = DB::fetchassoc($res)) {
              DB::query('UPDATE '.PREFIX.'product_on_storage SET count = '.DB::quote($count).' WHERE id = '.DB::quoteInt($row['id']));
            } else {
              DB::query('INSERT INTO '.PREFIX.'product_on_storage (product_id, storage, count) VALUES
                ('.DB::quoteInt($id).', '.DB::quote($storage).', '.DB::quoteInt($count).')');
            }
          }
        }

        // Эта проверка нужна только для того, чтобы исключить удаление 
        //вариантов при обновлении продуктов не из карточки товара в админке, 
        //например по нажатию на "лампочку".
        if (!empty($variants) || $updateFromModal) {

          // обновляем и добавляем варианты продукта.
          if ($variants === null) {
            $variants = array();
          }

          $filterVar = array('title_variant');
          foreach ($variants as &$item) {
            $localeDataVariants = MG::prepareLangData($item, $filterVar, $lang);
            MG::saveLocaleData($item['id'], $lang, 'product_variant', $localeDataVariants);
          }
          // оключаем сохранение вариантов, когда выбран другой язык, чтобы все не поломать
          if(empty($localeDataVariants)) {
            $this->saveVariants($variants, $id);
          }
        }

        $result = true;
      }
    } else {
      $result = $this->addProduct($array);
    }
    
    $currencyShopIso = MG::getSetting('currencyShopIso');  
    
    $this->updatePriceCourse($currencyShopIso, array($id));   

    Storage::clear('product-'.$id);
    Storage::clear('indexGroup-%');

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Обновляет поле в варианте и синхронизирует привязку первого варианта с продуктом.
   * <code>
   * $array = array(
   * 'price' => 200, // цена
   * 'count' => 50 // количество
   * );
   * $model = new Models_Product();
   * $model->fastUpdateProductVariant(5, $array, 2);
   * </code>
   * @param int $id id варианта.
   * @param array $array ассоциативный массив поле=>значение.
   * @param int $product_id id продукта.
   * @return bool
   */
  public function fastUpdateProductVariant($id, $array, $product_id) {
    if (!DB::query('
       UPDATE `'.PREFIX.'product_variant`
       SET '.DB::buildPartQuery($array).'
       WHERE id = '.DB::quote($id))) {
      return false;
    };
  
    // Следующие действия выполняются для синхронизации  значений первого 
    // варианта со значениями записи продукта из таблицы product.
    // Перезаписываем в $array новое значение от первого в списке варианта,
    // и получаем id продукта от этого варианта
    $variants = $this->getVariants($product_id);
   
    $field = array_keys($array);
    foreach ($variants as $key => $value) {
      $array[$field[0]] = $value[$field[0]];
      break;
    }

    // Обновляем продукт в соответствии с первым вариантом.
    $this->fastUpdateProduct($product_id, $array);
    return true;
  }

  /**
   * Аналогичная fastUpdateProductVariant функция, но с поправками для
   * процесса импорта вариантов.
   * <code>
   *   $model = new Models_Product();
   *   $model->importUpdateProductVariant(5, $array, 2);
   * </code>
   * @param int $id id варианта.
   * @param array $array массив поле = значение.
   * @param int $product_id id продукта.
   * @return bool
   */
  public function importUpdateProductVariant($id, $array, $product_id) {
    if($array['weight']) {
     $array['weight'] = (double)str_replace(array(',',' '), array('.',''), $array['weight']);
    }

    if($array['price']) {
      $array['price'] = (double)str_replace(array(',',' '), array('.',''), $array['price']);
    }

    if($array['price_course']) {
      $array['price_course'] = (double)str_replace(array(',',' '), array('.',''), $array['price_course']);
    }
    if(empty($array['price_course'])) {
      unset($array['price_course']);
    }
    
    if (!$id || !DB::query('
       UPDATE `'.PREFIX.'product_variant`
       SET '.DB::buildPartQuery($array).'
       WHERE id = %d
     ', $id)) {
      $res = DB::query('SELECT MAX(id) FROM '.PREFIX.'product_variant');
      while($row = DB::fetchAssoc($res)) {
        $array['sort'] = $row['MAX(id)']+1;
      }
      DB::query('
       INSERT INTO `'.PREFIX.'product_variant`
       SET '.DB::buildPartQuery($array)
      );
    };

    return true;
  }

  /**
   * Обновление заданного поля продукта.
   * <code>
   * $array = array(
   * 'price' => 200, // цена
   * 'sort' => 5, // номер сортировки
   * 'count' => 50 // количество
   * );
   * $model = new Models_Product();
   * $model->fastUpdateProduct(5, $array);
   * </code>
   * @param int $id - id продукта.
   * @param array $array - параметры для обновления.
   * @return bool
   */
  public function fastUpdateProduct($id, $array) {
    if($array['price']) {
      $array['price'] = (double)str_replace(array(',',' '), array('.',''), $array['price']);
    }
    if($array['sort']) {
      $array['sort'] = (int)str_replace(array(',',' '), array('.',''), $array['sort']);
    }
    if($array['count']) {
      $array['count'] = (int)str_replace(array(',',' '), array('.',''), $array['count']);
    }
    
    if (!DB::query('
      UPDATE `'.PREFIX.'product`
      SET '.DB::buildPartQuery($array).'
      WHERE id = %d
    ', $id)) {
      return false;
    };
    
    $currencyShopIso = MG::getSetting('currencyShopIso');  
    $this->updatePriceCourse($currencyShopIso, array($id));   
    
    return true;
  }

  /**
   * Сохраняет пользовательские характеристики для товара 
   * (внутренний метод, используется только при сохранении товара).
   * @param array $userProperty набор характеристик.
   * @param int $id - id товара.
   * @return bool
   */
  public function saveUserProperty($userProperty, $id) {
    foreach ($userProperty as $key => $value) {
      $propertyId = (int)$key;

      switch ($value['type']) {
        case 'select':
        case 'checkbox':
          unset($value['type']);
          foreach ($value as $keyIn => $item) {
            $data = explode('#', $item['val']);
            if(substr_count($keyIn, 'temp') == 1) {
              DB::query("
                INSERT INTO `".PREFIX."product_user_property_data`
                (prop_id, product_id, margin, active, prop_data_id, type_view)
                VALUES (".DB::quote($propertyId).", ".DB::quote($id).", ".DB::quote($data[1]).", 
                ".DB::quote($item['active']).", ".DB::quote($item['prop-data-id']).", ".DB::quote($item['type-view']).")");
            } else {
              DB::query("
                UPDATE `".PREFIX."product_user_property_data`
                SET margin = ".DB::quote($data[1]).", active = ".DB::quote($item['active']).", type_view = ".DB::quote($item['type-view'])."
                WHERE id = ".DB::quote($keyIn));
            }
          }
          break;
        case 'input':
        case 'textarea':
          unset($value['type']);
          foreach ($value as $keyIn => $item) {
            if(substr_count($keyIn, 'temp') == 1) {
              DB::query("
                INSERT INTO `".PREFIX."product_user_property_data`
                (prop_id, product_id, name, margin, active, prop_data_id)
                VALUES (".DB::quote($propertyId).", ".DB::quote($id).", ".DB::quote($item['val']).",
                '', ".DB::quote($item['active']).", ".DB::quote($item['prop-data-id']).")");
            } else {
              // сохраняем локализацию
              $filterProp = array('val');
              $localeDataVariants = MG::prepareLangData($item, $filterProp, LANG);
              if(!empty($localeDataVariants['val'])) {
                MG::saveLocaleData($keyIn, LANG, 'product_user_property_data', array('name' => $localeDataVariants['val']));
              }
              // сохранение самой характеристики
              if(empty($item['val'])) {
                if((LANG != 'LANG') && (LANG != 'default')) {
                  $val = '';
                } else {
                  $val = 'name = \'\',';
                }
              } else {
                $val = 'name = '.DB::quote($item['val']).',';
              }
              DB::query("
                UPDATE `".PREFIX."product_user_property_data`
                SET ".$val." active = ".DB::quote($item['active'])."
                WHERE id = ".DB::quote($keyIn));
            }
          }
          break;
      }

    }
  }

  /**
   * Сохраняет варианты товара.
   * <code>
   * $variants = Array(
   *  0 => Array(
   *     'color' => 19, // id цвета варианта
   *     'size' => 11, // id размера варианта
   *     'title_variant' => '22 Голубой', // название
   *     'code' => 'SKU241', // артикул
   *     'price' => 2599, // цена
   *     'old_price' => 3000, // старая цена
   *     'weight' => 1, // вес
   *     'count' => 50, // количество
   *     'activity' => 1, // активность
   *     'id' => 1249, // id варианта
   *     'currency_iso' => 'RUR', // код валюты
   *     'image' => '13140250299.jpg' // название картинки варианта
   *  )
   * );
   * $model = new Models_Product();
   * $model->saveVariants($variants, 51);
   * </code>
   * @param array $variants набор вариантов
   * @param int $id id товара
   * @return bool
   */
  public function saveVariants($variants = array(), $id) {
    $existsVariant = array();
    
    $dbRes = DB::query('SHOW COLUMNS FROM `'.PREFIX.'product` WHERE FIELD = \'system_set\'');
    if(!$row = DB::fetchArray($dbRes)) {
      return false;
    }
    
    $dbRes = DB::query("SELECT * FROM `".PREFIX."product_variant` WHERE product_id = ".DB::quote($id));
    
    while ($arRes = DB::fetchAssoc($dbRes)) {
      $existsVariant[$arRes['id']] = $arRes;
    }

    foreach ($variants as $item) {
      $res = DB::query('SELECT count FROM '.PREFIX.'product_variant WHERE id = '.DB::quoteInt($item['id']));
      while ($row = DB::fetchAssoc($res)) {
        $countArray[$item['id']] = $row['count'];
      }
    }
    
    // Удаляем все имеющиеся товары.
    $res = DB::query("DELETE FROM `".PREFIX."product_variant` WHERE product_id = ".DB::quote($id));

    if(!empty($variants)) {
      DB::query("DELETE FROM `".PREFIX."product_on_storage` WHERE product_id = ".DB::quote($id).' 
        AND storage = '.DB::quote($_POST['storage']));
    }

    // Если вариантов как минимум два.
   // if (count($variants) > 1) {
      // Сохраняем все отредактированные варианты.
    $i = 1;
    foreach ($variants as $variant) { 
      if (!empty($existsVariant[$variant['id']]['1c_id'])) {
        $variant['1c_id'] = $existsVariant[$variant['id']]['1c_id'];
      }
      if (empty($variant['code'])) {
        $variant['code'] = MG::getSetting('prefixCode').$id.'_'.$i;
      }
      $variant['sort'] = $i++;
      unset($variant['product_id']);
      unset($variant['rate']);
      unset($variant['count_sort']);
      if(!empty($variant['id'])) {

        if(MG::enabledStorage()) {
          $count = $variant['count'];
          $variant['count'] = $countArray[$variant['id']];
        }
      }

      $varId = $variant['id'];
      if($this->clone) {
        unset($variant['id']);
      }
      DB::query(' 
        INSERT  INTO `'.PREFIX.'product_variant` 
        SET product_id= '.DB::quote($id).", ".DB::buildPartQuery($variant)
      );

      if($this->clone) {
        MG::cloneLocaleData($varId, DB::insertId(), 'product_variant');
      }

      // сохраняем количество товара на определенном складе
      if(!empty($varId)) {
        if(MG::enabledStorage()) {
          if(!empty($_POST['storage'])) {
            $this->storage = $_POST['storage'];
          }
          if($this->storage != 'all') {
            // $res = DB::query('SELECT id FROM '.PREFIX.'product_on_storage WHERE product_id = '.DB::quote($id).' 
            //   AND storage = '.DB::quote($this->storage).' AND variant_id = '.$variant['id']);
            // if($row = DB::fetchassoc($res)) {
            //   DB::query('UPDATE '.PREFIX.'product_on_storage SET count = '.DB::quote($count).' WHERE id = '.DB::quoteInt($row['id']));
            // } else {
              DB::query('INSERT INTO '.PREFIX.'product_on_storage (product_id, variant_id, storage, count) VALUES
                ('.DB::quoteInt($id).', '.DB::quoteInt($varId).', '.DB::quote($this->storage).', '.DB::quoteInt($count).')');
            // }
          }
        }
      }
    }
   // }
  }

  /**
   * Клонирует товар.
   * <code>
   * $productId = 25;
   * $model = new Models_Product;
   * $model->cloneProduct($productId);
   * </code>
   * @param int $id id клонируемого товара.
   * @return array
   */
  public function cloneProduct($id) {
    $result = false;

    $arr = $this->getProduct($id, true, true);
    $arr['unit'] = $arr['product_unit'];
    $arr['title'] = htmlspecialchars_decode($arr['title']);
    $image_url = basename($arr['image_url']);         
    
    foreach ($arr['images_product'] as $k=>$image) {
      $arr['images_product'][$k] = basename($image);
    }   
    $arr['image_url'] = implode("|", $arr['images_product']);
    $imagesArray = $arr['images_product'];
    
    $userProperty = $arr['thisUserFields'];

    unset($arr['product_unit']);
    unset($arr['category_unit']);
    unset($arr['real_category_unit']);
    unset($arr['category_name']);
    unset($arr['thisUserFields']);
    unset($arr['category_url']);
    unset($arr['product_url']);
    unset($arr['images_product']);
    unset($arr['images_title']);
    unset($arr['images_alt']);
    unset($arr['rate']);    
    unset($arr['plugin_message']);
    unset($arr['id']);
    unset($arr['count_buy']);
    $arr['code'] = '';
    $arr['userProperty'] = $userProperty;
    $variants = $this->getVariants($id);
    
    foreach ($variants as &$item) {
      // unset($item['id']);
      unset($item['product_id']);
      unset($item['rate']); 
      $item['code'] = '';
      $imagesArray[] = $item['image'];
      $item['image'] = $item['image'];      
    }
    
    $arr['variants'] = $variants;   
    
    // перед клонированием создадим копии изображений, 
    // чтобы в будущем можно было без проблемно удалять их вместе с удалением продукта       
    $result = $this->addProduct($arr, true);
    
    $this->cloneImagesProduct($imagesArray, $id, $result['id']); 

    // клонирование характеристик характеристик
    foreach ($userProperty as $item) {
      if(empty($item['data'])) {
        DB::query('INSERT INTO '.PREFIX.'product_user_property_data (prop_id, product_id, name) VALUES
          ('.DB::quote($item['prop_id']).', '.DB::quote($result['id']).', '.DB::quote($item['value']).')');
        MG::cloneLocaleData($item['prop_id'], DB::insertId(), 'product_user_property_data'); 
      } else {
        foreach ($item['data'] as $val) {
          DB::query('INSERT INTO '.PREFIX.'product_user_property_data (prop_id, product_id, name, margin, active, prop_data_id) VALUES
            ('.DB::quote($item['prop_id']).', '.DB::quote($result['id']).', '.DB::quote($val['name']).', 
            '.DB::quote($val['margin']).', '.DB::quote($val['active']).', '.DB::quote($val['prop_data_id']).')');
          MG::cloneLocaleData($item['prop_id'], DB::insertId(), 'product_user_property_data'); 
        }
      }
    }

    // клонирование локализаций
    MG::cloneLocaleData($id, $result['id'], 'product'); 
    
    $result['image_url'] = $image_url;
    $result['currency'] = MG::getSetting('currency');

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }
  
   /**
     * Клонирует изображения продукта.
     * <code>
     *   $imagesArray = array(
     *     '40Untitled-1.jpg',
     *     '41Untitled-1.jpg',
     *     '42Untitled-1.jpg'
     *   );
     *   $oldId = 40;
     *   $newId = 130;
     *   $model = new Models_Product;
     *   $model->deleteProduct($imagesArray, $oldId, $newId);
     * </code>
     * @param array $imagesArray массив url изображений, которые надо клонировать.
     * @param int $oldId старый ID товара.
     * @param int $newId новый ID товара.
     * @return bool
     */
  public function cloneImagesProduct($imagesArray = array(), $oldId = 0, $newId = 0) { 
    if(!$oldId && !$newId) return false;
    $ds = DIRECTORY_SEPARATOR;
    $documentroot = str_replace($ds.'mg-core'.$ds.'models','',dirname(__FILE__)).$ds;     
    $dir = floor($oldId/100).'00'.$ds.$oldId;        
    $this->movingProductImage($imagesArray, $newId, 'uploads'.$ds.'product'.$ds.$dir, false);
 
    return true;
  }

  /**
   * Удаляет товар, его свойства, варианты, локализации, оптовые цены из базы данных.
   * <code>
   * $productId = 25;
   * $model = new Models_Product;
   * $model->deleteProduct($productId);
   * </code>
   * @param int $id id удаляемого товара
   * @return bool
   */
  public function deleteProduct($id) {
    $result = false;
    $prodInfo = $this->getProduct($id);  
       
    $this->deleteImagesProduct($prodInfo['images_product'], $id); 
    $this->deleteImagesVariant($id); 
    $this->deleteImagesFolder($id);

    // Удаляем продукт из базы.
    DB::query('
      DELETE
      FROM `'.PREFIX.'product`
      WHERE id = %d
    ', $id);

    // Удаляем все значения пользовательских характеристик данного продукта.
    $res = DB::query('SELECT id FROM '.PREFIX.'product_user_property_data WHERE product_id = '.DB::quoteInt($id));
    while($row = DB::fetchAssoc($res)) {
      MG::removeLocaleDataByEntity($row['id'], 'product_user_property_data');
    }
    DB::query('
      DELETE
      FROM `'.PREFIX.'product_user_property_data`
      WHERE product_id = %d
    ', $id);

    // Удаляем все варианты данного продукта.
    DB::query('
      DELETE
      FROM `'.PREFIX.'product_variant`
      WHERE product_id = %d
    ', $id);

    // удаляем склады
    DB::query('DELETE FROM '.PREFIX.'product_on_storage WHERE product_id = '.DB::quoteInt($id));

    // Удаляем лендинг
    DB::query("DELETE FROM `".PREFIX."landings` where id = ".DB::quoteInt($id));

    // удаляем локализацию
    MG::removeLocaleDataByEntity($id, 'product');
    MG::removeLocaleDataByEntity($id, 'landings');

    $result = true;
    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

   /**
   * Удаляет папки из структуры папок изображений относящиеся к заданному продукту.
   * <code>
   * $productId = 25;
   * $model = new Models_Product;
   * $model->deleteImagesFolder($productId);
   * </code>
   * @param int $id id товара.
   */
  public function deleteImagesFolder($id) {
    if(!empty($id)) {
      $ds = DIRECTORY_SEPARATOR;
      $path = 'uploads'.$ds.'product'.$ds.floor($id/100).'00'.$ds.$id;
      if(file_exists($path)) {
        if(file_exists($path.$ds.'thumbs')) {
          rmdir($path.$ds.'thumbs');
        }
        rmdir($path);
      }
    }
  }
  /**
   * Удаляет все картинки привязанные к продукту.
   * <code>
   *   $array = array(
   *    'product/100/105/120.jpg',
   *    'product/100/105/122.jpg',
   *    'product/100/105/121.jpg'
   *  );
   *  $model = new Models_Product();
   *  $model->deleteImagesProduct($array);
   * </code>
   * @param array $arrayImages массив с названиями картинок
   * @param int $productId ID товара
   */
   public function deleteImagesProduct($arrayImages = array(), $productId = false) {
     if(empty($arrayImages)) {       
       return true;
     }     
     // удаление картинки с сервера
    $uploader = new Upload(false);   
    foreach ($arrayImages as $key => $imageName) {
      $pos = strpos($imageName, 'no-img');
      if(!$pos && $pos !== 0) {
        $uploader->deleteImageProduct($imageName, $productId);     
      }
    }
  }
  /**
   * Получает информацию о запрашиваемом товаре.
   * <code>
   * $where = '`cat_id` IN (5,6)';
   * $model = new Models_Product;
   * $result = $model->deleteImagesFolder($where);
   * viewData($result);
   * </code>
   * @param string $where необязательный параметр, формирующий условия поиска, например: id = 1
   * @return array массив товаров
   */
  public function getProductByUserFilter($where = '') {
    $result = array();

    if ($where) {
      $where = ' WHERE '.$where;
    }
    
    $res = DB::query('
     SELECT  CONCAT(c.parent_url,c.url) as category_url,
       p.url as product_url, p.*, rate,(p.price_course + p.price_course * (IFNULL(rate,0))) as `price_course`, 
       p.`currency_iso`
     FROM `'.PREFIX.'product` p
       LEFT JOIN `'.PREFIX.'category` c
       ON c.id = p.cat_id
     '.$where);
    
    while ($order = DB::fetchAssoc($res)) {
      $result[$order['id']] = $order;
    }
    return $result;
  }

  /**
   * Получает информацию о запрашиваемом товаре по его ID.
   * <code>
   * $productId = 25;
   * $model = new Models_Product;
   * $product = $model->getProduct($productId);
   * viewData($product);
   * </code>
   * @param int $id id запрашиваемого товара.
   * @param bool $getProps возвращать ли характеристики.
   * @param bool $disableCashe отключить ли кэш.
   * @return array массив с данными о товаре.
   */
  public function getProduct($id, $getProps = true, $disableCashe = false) {    
    if(!$disableCashe && $getProps) $prodCash = Storage::get('product-'.$id.LANG.MG::getSetting('currencyShopIso'));

    if(!$prodCash) {
      $id =  intval($id);
      $result = array();
      $res = DB::query('
        SELECT  CONCAT(c.parent_url,c.url) as category_url, c.title as category_name, c.unit as category_unit, p.unit as product_unit,
          p.url as product_url, p.*, rate, (p.price_course + p.price_course * (IFNULL(rate,0))) as `price_course`, 
          p.`currency_iso` 
        FROM `'.PREFIX.'product` p
          LEFT JOIN `'.PREFIX.'category` c
          ON c.id = p.cat_id
        WHERE p.id = '.DB::quote($id, true));
     
      if (!empty($res)) {
        if ($product = DB::fetchAssoc($res)) {
          $result = $product;

          // подгражем количество товара на складах
          if(empty($_POST['storage'])) $_POST['storage'] = 'all';
          if(!empty($_POST['storage'])) $this->storage = $_POST['storage'];
          $result['count'] = MG::getProductCountOnStorage($result['count'], $id, 0, $this->storage);
          
          if ($getProps) {
            // Запрос делает следующее 
            // 1. Вычисляет список пользовательских характеристик для категории товара, 
            // 2. Присваивает всем параметрам значения по умолчанию, 
            // 3. Находит заполненные характеристики товара, заменяет ими значения по умолчанию.
            // В результате получаем набор всех пользовательских характеристик включая те, что небыли определены явно.

            $res = DB::query("
              SELECT pup.prop_id, pup.type_view, prop.*
              FROM `".PREFIX."product_user_property_data` as pup
              LEFT JOIN `".PREFIX."property` as prop
                ON pup.prop_id = prop.id
              LEFT JOIN  `".PREFIX."category_user_property` as cup 
                ON cup.property_id = prop.id
              WHERE pup.`product_id` = ".DB::quote($id)." AND cup.category_id = ".DB::quote($result['cat_id'])."
                
              ORDER BY `sort` DESC;
            ");

            while ($userFields = DB::fetchAssoc($res)) {
              // Заполняет каждый товар его характеристиками.
              $result['thisUserFields'][$userFields['prop_id']] = $userFields;
            }

            // получаем содержимое сложных настроек для пользовательских характеристик
            Property::addDataToProp($result['thisUserFields'], $id);
          }

          $imagesConctructions = $this->imagesConctruction($result['image_url'],$result['image_title'],$result['image_alt'], $result['id']);
          $result['images_product'] = $imagesConctructions['images_product']; 
          $result['images_title'] = $imagesConctructions['images_title']; 
          $result['images_alt'] = $imagesConctructions['images_alt']; 
          $result['image_url'] = $imagesConctructions['image_url']; 
          $result['image_title'] = $imagesConctructions['image_title']; 
          $result['image_alt'] = $imagesConctructions['image_alt'];  

          $result['price'] = MG::convertPrice($result['price']);
          $result['price_course'] = MG::convertPrice($result['price_course']);
          $result['old_price'] = MG::convertPrice($result['old_price']);

          $result['unit'] = $result['product_unit'];
        }
      }
      
      if (!isset($result['category_unit'])) {
        $result['category_unit'] = 'шт.';
      }

      $cat = array('unit'=>$result['category_unit']);
      MG::loadLocaleData($id, LANG, 'product', $result);
      MG::loadLocaleData($id, LANG, 'category', $cat);
      $result['product_unit'] = $result['unit'];
      $result['real_category_unit'] = $result['category_unit'];
      $result['real_category_unit'] = $cat['unit'];;
      if (isset($result['product_unit']) && $result['product_unit'] != null && strlen($result['product_unit']) > 0) {
        $result['category_unit'] = $result['product_unit'];
      }
      if ($getProps) {
        Storage::save('product-'.$id.LANG.MG::getSetting('currencyShopIso'), $result);
      }
    } else {
      $result = $prodCash;

      if(MG::enabledStorage()) {
        // подгражем количество товара на складах
        if(empty($_POST['storage'])) $_POST['storage'] = 'all';
        if(!empty($_POST['storage'])) $this->storage = $_POST['storage'];
        $result['count'] = MG::getProductCountOnStorage($result['count'], $id, 0, $this->storage);
      } else {
        $res = DB::query('SELECT IF(COUNT(pv.id) = 0, p.count, SUM(pv.count)) AS count 
          FROM '.PREFIX.'product AS p
          LEFT JOIN '.PREFIX.'product_variant AS pv ON p.id = pv.product_id
          WHERE p.id = '.DB::quoteInt($id));
        while ($row = DB::fetchAssoc($res)) {
          $result['count'] = $row['count'];
        }
      }
      
    }

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }
  
  /**
   * Создает массивы данных для картинок товара, возвращает три массива со ссылками, заголовками и альт, текстами.
   * <code>
   *   $model = new Models_Product();
   *   $imageUrl = '120.jpg|121.jpg';
   *   $imageTitle = 'Каритинка товара';
   *   $imageAlt = 'Альтернативная подпись картинки';
   *   $res = $model->imagesConctruction($imageUrl, $imageTitle, $imageAlt);
   *   viewData($res);
   * </code>
   * @param string $imageUrl строка с разделителями | между ссылок.
   * @param string $imageTitle строка с разделителями | между заголовков.
   * @param string $imageAlt строка с разделителями | между тестов.
   * @param string $id ID товара.
   * @return array
   */
  public function imagesConctruction($imageUrl, $imageTitle, $imageAlt, $id = 0) {
    $result = array(
      'images_product'=>array(),
      'images_title'=>array(),
      'images_alt'=>array()
    );
    
    // Получаем массив картинок для продукта, при этом первую в наборе делаем основной.
    $arrayImages = explode("|", $imageUrl);
    
    foreach($arrayImages as $cell=>$image) {
      $arrayImages[$cell] = str_replace(SITE.'/uploads/', '', mgImageProductPath($image, $id));
    }
    
    if (!empty($arrayImages)) {
      $result['image_url'] = $arrayImages[0];
    }

    $result['images_product'] = $arrayImages;  
    // Получаем массив title для картинок продукта, при этом первый в наборе делаем основной.
    $arrayTitles = explode("|", $imageTitle);
    if (!empty($arrayTitles)) {
      $result['image_title'] = $arrayTitles[0];
    }

    $result['images_title'] = $arrayTitles;  

    // Получаем массив alt для картинок продукта, при этом первый в наборе делаем основной.
    $arrayAlt = explode("|", $imageAlt);
    if (!empty($arrayAlt)) {
      $result['image_alt'] = $arrayAlt[0];
    }

    $result['images_alt'] = $arrayAlt;  
    
    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }
    
  /**
   * Обновляет остатки продукта, увеличивая их на заданное количество.
   * <code>
   * Models_Product::increaseCountProduct(37, 'SKU348', 2);
   * </code>
   * @param int $id номер продукта.
   * @param string $code артикул.
   * @param int $count прибавляемое значение к остатку.
   */
  public function increaseCountProduct($id, $code, $count) {

    $sql = "
      UPDATE `".PREFIX."product_variant` as pv 
      SET pv.`count`= pv.`count`+".DB::quote($count)." 
      WHERE pv.`product_id`=".DB::quote($id)." 
        AND pv.`code`=".DB::quote($code)." 
        AND pv.`count`>=0
    ";

    DB::query($sql);

    $sql = "
      UPDATE `".PREFIX."product` as p 
      SET p.`count`= p.`count`+".DB::quote($count)." 
      WHERE p.`id`=".DB::quote($id)." 
        AND p.`code`=".DB::quote($code)." 
        AND  p.`count`>=0
    ";

    DB::query($sql);
  }

  /**
   * Обновляет остатки продукта, уменьшая их количество,
   * при смене статуса заказа с "отменен" на любой другой.
   * <code>
   * Models_Product::decreaseCountProduct(37, 'SKU348', 2);
   * </code>
   * @param int $id ID продукта.
   * @param string $code Артикул.
   * @param int $count Прибавляемое значение к остатку.
   */
  public function decreaseCountProduct($id, $code, $count) {

    $product = $this->getProduct($id);
    $variants = $this->getVariants($product['id']);
    foreach ($variants as $idVar => $variant) {
      if ($variant['code'] == $code) {
        $variantCount = ($variant['count'] * 1 - $count * 1) >= 0 ? $variant['count'] - $count : 0;
        $sql = "
          UPDATE `".PREFIX."product_variant` as pv 
          SET pv.`count`= ".DB::quote($variantCount, true)." 
          WHERE pv.`id`=".DB::quote($idVar)." 
            AND pv.`code`=".DB::quote($code)." 
            AND  pv.`count`>0";
        DB::query($sql);
      }
    }

    $product['count'] = ($product['count'] * 1 - $count * 1) >= 0 ? $product['count'] - $count : 0;
    $sql = "
      UPDATE `".PREFIX."product` as p 
      SET p.`count`= ".DB::quote($product['count'], true)." 
      WHERE p.`id`=".DB::quote($id)." 
        AND p.`code`=".DB::quote($code)."
        AND  p.`count`>0";
    DB::query($sql);
  }

  /**
   * Удаляет все миниатюры и оригинал изображения товара из папки upload.
   * @param array $arrayDelImages массив с изображениями для удаления
   * @return bool
   * @deprecated
   */
  public function deleteImageProduct($arrayDelImages) {
    if (!empty($arrayDelImages)) {
      foreach ($arrayDelImages as $value) {
        if (!empty($value)) {
          // Удаление картинки с сервера.
          $documentroot = str_replace('mg-core'.$ds.'models', '', __DIR__);
          if (is_file($documentroot."uploads/".basename($value))) {
            unlink($documentroot."uploads/".basename($value));
            if (is_file($documentroot."uploads/thumbs/30_".basename($value))) {
              unlink($documentroot."uploads/thumbs/30_".basename($value));
            }
            if (is_file($documentroot."uploads/thumbs/70_".basename($value))) {
              unlink($documentroot."uploads/thumbs/70_".basename($value));
            }
          }
        }
      }
    }
    return true;
  }

  /**
   * Возвращает общее количество продуктов каталога.
   * <code>
   * $result = Models_Product::getProductsCount();
   * viewData($result);
   * </code>
   * @return int количество товаров.
   */
  public function getProductsCount() {
    $result = 0;
    $res = DB::query('
      SELECT count(id) as count
      FROM `'.PREFIX.'product`
    ');

    if ($product = DB::fetchAssoc($res)) {
      $result = $product['count'];
    }

    return $result;
  }

  /**
   * Получает продукт по его URL.
   * <code>
   * $url = 'nike-air-versitile_102';
   * $result = Models_Product::getProductByUrl($url);
   * viewData($result);
   * </code>
   * @param string $url запрашиваемого товара.
   * @param int $catId id-категории, т.к. в разных категориях могут быть одинаковые url.
   * @return array массив с данными о товаре.
   */
  public function getProductByUrl($url, $catId = false) {
    $result = array();
    if ($catId !== false) {
      $where = ' and cat_id='.DB::quote($catId);
    }

    $res = DB::query('
      SELECT *
      FROM `'.PREFIX.'product`
      WHERE url = '.DB::quote($url).' 
    '.$where);
   
    if (!empty($res)) {
      if ($product = DB::fetchAssoc($res)) {
        $result = $product;
      }
    }

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Получает цену запрашиваемого товара по его id.
   * <code>
   * $result = Models_Product::getProductPrice(5);
   * viewData($result);
   * </code>
   * @param int $id id изменяемого товара.
   * @return bool|float $error в случаи ошибочного запроса.
   */
  public function getProductPrice($id) {
    $result = false;
    $res = DB::query('
      SELECT price
      FROM `'.PREFIX.'product`
      WHERE id = %d
    ', $id);

    if ($row = DB::fetchObject($res)) {
      $result = $row->price;
    }

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Создает форму пользовательских характеристик для товара.
   * В качестве входящего параметра получает массив:
   * <code>
   * $param = array(
   *   'id' => null, // id товара.
   *   'maxCount' => null, // максимальное количество товара на складе.
   *   'productUserFields' => null, // массив пользовательских полей для данного продукта.
   *   'action' => "/catalog", // ссылка для метода формы.
   *   'method' => "POST", // тип отправки данных на сервер.
   *   'ajax' => true, // использовать ajax для пересчета стоимости товаров.
   *   'blockedProp' => array(), // массив из ID свойств, которые ненужно выводить в форме.
   *   'noneAmount' => false, // не выводить  input для количества.
   *   'titleBtn' => "В корзину", // название кнопки.
   *   'blockVariants' => '', // блок вариантов.
   *   'classForButton' => 'addToCart buy-product buy', // классы для кнопки.
   *   'noneButton' => false, // не выводить кнопку отправки.
   *   'addHtml' => '' // добавить HTML в содержимое формы.
   *   'currency_iso' => '', // обозначение валюты в которой сохранен товар
   *   'printStrProp' => 'true', // выводить строковые характеристики
   *   'printCompareButton' => 'true', // выводить кнопку сравнения
   *   'buyButton' => 'true', // показывать кнопку 'купить' в миникарточках (если false - показывается кнопка 'подробнее')
   *   'productData' => 'Array', // массив с данными о товаре
   *   'showCount' => 'true' // показывать блок с количеством
   * );
   * $model = new Models_Product;
   * $result = $model->getProduct($param);
   * echo $result;
   * </code>
   * @param array $param массив параметров.
   * @param string $adminOrder заказ для админки или нет (по умолчанию - нет).
   * @return string html форма.
   */
  public function createPropertyForm(
  $param = array(
    'id' => null,
    'maxCount' => null,
    'productUserFields' => null,
    'action' => "/catalog",
    'method' => "POST",
    'ajax' => true,
    'blockedProp' => array(),
    'noneAmount' => false,
    'titleBtn' => "В корзину",
    'blockVariants' => '',
    'classForButton' => 'addToCart buy-product buy',
    'noneButton' => false,
    'addHtml' => '',   
    'printStrProp' => null,
    'printCompareButton' => null,
    'buyButton' => '',
    'currency_iso' => '',
    'productData' => null,
    'showCount' => true,
  ), $adminOrder = 'nope'
  ) {
    extract($param);
    if (empty($classForButton)) {
      $classForButton = 'addToCart buy-product buy';
    }
    if ($id === null || $maxCount === null) {
      return "error param!";
    }
    if (empty($printStrProp)) {
      $printStrProp = MG::getSetting('printStrProp');    
    }
    if ($printCompareButton===null) {
      $printCompareButton = MG::getSetting('printCompareButton');    
    }
	
	if($this->groupProperty==null){
	  $this->groupProperty = Property::getPropertyGroup(true);
	}
	
    $catalogAction = MG::getSetting('actionInCatalog') === "true" ? 'actionBuy' : 'actionView';
    // если используется аяксовый метод выбора, то подключаем доп класс для работы с формой. 
    $marginPrice = 0; // добавочная цена, в зависимости от выбранных автоматом характеристик
    $secctionCartNoDummy = array(); //Не подставной массив характеристик, все характеристики с настоящими #ценами#
    //в сессию записать реальные значения, в паблик подмену, с привязкой в конце #№
    $html = '';
   //if ($ajax) {
    //  mgAddMeta("<script type=\"text/javascript\" src=\"".SITE."/mg-core/script/jquery.form.js\"></script>");
    //}

    $currencyRate = MG::getSetting('currencyRate');
    $currencyShort = MG::getSetting('currencyShort');
    $currencyRate = $currencyRate[$currency_iso];
    $currencyShort = $currencyShort[$currency_iso];
    $propPieces = array();
    $htmlProperty = '';

    if (!empty($productUserFields)) {
      $defaultSet = array(); // набор характеристик предоставленных по умолчанию.  
      // массив со строковыми характеристиками
      $stringsProperties = array();      
      foreach ($productUserFields as $property) {
    
        if (in_array($property['id'], $blockedProp)) {
          continue;
        }

        // подгрузка локализаций для сложных характеристик
        if(($item['type'] != 'string')&&($item['type'] != 'textarea')) {
          foreach ($data as &$val) {
            $res = DB::query("SELECT * FROM ".PREFIX."property_data WHERE `id` = ".DB::quote($val['prop_data_id']));
            while ($userFieldsData = DB::fetchAssoc($res)) {
              MG::loadLocaleData($userFieldsData['id'], LANG, 'prop_data', $userFieldsData);
              $val['name'] = $userFieldsData['name'];
            }
          }
        }
        MG::loadLocaleData($property['id'], LANG, 'property', $property);
        $collectionParse = array();
        $collectionAccess = array();
        /*
          'select' - набор значений, можно интерпретировать как  выпадающий список либо набор радиокнопок
          'assortment' - мультиселект
          'string' - пара ключ-значение
          'assortmentCheckBox' - набор чекбоксов
         */

        switch ($property['type']) {
          case 'assortmentCheckBox': {
              $marginStoper = $marginPrice;
              $htmladd = '';
              // $htmladd .= '<p><span class="property-title">'.$property['name'].'</span><span class="property-delimiter">:</span> <span class="label-black">';    
              $htmladdIn = '';

              foreach ($property['data'] as $item) {
                if ($item['active'] == 1) {
                  $htmladdIn .= ''.$item['name'].', ';
                }
              }

              $htmladdIn = substr($htmladdIn, 0, -2);
              // сохраняем в массив строковых переменных
              $tmp['name'] = $htmladdIn;
              $tmp['group_prop'] = $this->groupProperty[$property['group_id']];
              $tmp['unit'] = $property['unit'];
              $stringsProperties[$property['name']][] = $tmp;
              
              // не выводим если ненужно
              if($printStrProp=='true') {
                if (strlen($htmladdIn)) {
                  $propPieces[] = array('type' => 'assortment', 'name' => $property['name'], 'additional' => $htmladdIn);
                }
              }
              
              break;
            }

          case 'assortment': {

              if ((empty($property['type_view']) || $property['type_view'] == '' || $property['type_view'] == 'type_view') && !empty($property['data'][0]) && !empty($property['data'][0]['type_view'])) {
                $property['type_view'] = $property['data'][0]['type_view'];
              }

              $marginStoper = $marginPrice;
          
              $property['value'] = $property['value']?$property['value']:$property['default'];               
              $property['property_id'] = $property['property_id']?$property['property_id']:$property['id'];                
              $collection = explode('|', $property['value']);
  
              $i = 0;               
              $firstLiMargin = 0;
              $isExistSelected = false;
             
              foreach ($collection as $value) {   
                $tempVar = $this->parseMarginToProp($value);
                if($tempVar['name']) {
                  $collectionParse[$tempVar['name']] = $tempVar['margin'];                      
                } else {
                  $collectionParse[$value] = 0;                          
                }
              }                 
          
              $collectionAccess = array(); // допустимый актуальный перечень
              foreach (explode('|', $property['product_margin']) as $value) {   
                $tempVar = $this->parseMarginToProp($value);
                if($tempVar['name']) {
                  $collectionAccess[] = $tempVar['name'];                      
                } else {
                  $collectionAccess[] = $value;                          
                }
              }
                            
              // для типа вывода select :
              if ($property['type_view'] == 'select' || empty($property['type_view']) || $property['type_view'] == 'type_view') {
                // для типа вывода select :
                if ($property['value'] == 'null') {
                  break;
                }
                $selectPieces = array();               
                // $htmlSelect = '<p class="select-type"><span class="property-title">'.$property['name'].'<span class="property-delimiter">:</span> </span><select name="'.$property['name'].'" class="last-items-dropdown">';            

                foreach ($property['data'] as $item) {   
                  if($item['active'] == 0) {
                    continue; 
                  }
                 
                  $value = '';
                  $value = $item['name']."#".$item['margin']."#";
                                    
                  if (empty($item)) {
                    $item = array('name' => $value, 'margin' => 0);
                  }
                              
                  $plus = $this->addMarginToProp($item['margin'], $currencyRate, $currencyShort);
                  $plus = MG::getSetting('outputMargin')=='false' ? '' : $plus;
                  $selected = "";

                  if ($marginStoper == $marginPrice) {

                    // только один раз добавляем цену и выделяем пункт
                    //  (т.к. не исключена возможнось нескольких выделанных пунктов)                   
                    if ($i == 0) {
                     
                      $selected = ' selected="selected" ';
                      $marginPrice += $item['margin'];
                     
                      // запоминаем дефолтное значение
                      $defaultSet[$property['property_id'].'#'.$i] = $value;
                      $isExistSelected = true;
                       
                    };
                  }
              
                  // $htmlSelect .= '<option value="'.$property['property_id'].'#'.
                  //   $i.'" '.$selected.'>'.$item['name'].$plus.'</option>';
                  $secctionCartNoDummy[$property['property_id']][$i++] = array(
                    'value' => $value,
                    'name' => $property['name']);
                  $selectPieces[] = array('value' => $property['property_id'].'#'.($i-1), 'selected' => $selected, 'itemName' => $item['name'], 'price' => $plus);
                }

                // $htmlSelect .= '</select></p>';
                
                if($isExistSelected) {
                  // $html .= $htmlSelect;
                  if (!empty($selectPieces)) {
                    $propPieces[] = array('type' => 'assortment-select', 'name' => $property['name'], 'additional' => $selectPieces);
                  }
                }                  
     
                break;
              }

              // Для типа вывода radiobutton :              
              if ($property['type_view'] == 'radiobutton') {
                if ($property['data'] == 'null') {
                  break;
                }
                $radioPieces = array();
                // $htmlRadiobutton = '<span class="property-title">'.$property['name'].'</span><span class="property-delimiter">:</span><br/>';
                $collection = explode('|', $property['value']);    
                $i = 0;
                $htmlButtonList = '';
                
                foreach ($property['data'] as $item) {
                  if($item['active'] == 0) {
                    continue; 
                  }

                  $value = '';
                  $value = $item['name']."#".$item['margin']."#";
                                    
                  if (empty($item)) {
                    $item = array('name' => $value, 'margin' => 0);
                  }
                  
                  $plus = $this->addMarginToProp($item['margin'], $currencyRate, $currencyShort);
                  $plus = MG::getSetting('outputMargin')=='false' ? '' : $plus;

                  $checked = '';
                  if ($i == 0) {
                    $checked = ' checked="checked" ';                    
                    
                    // запоминаем дефолтное значение
                    $defaultSet[$property['property_id'].'#'.$i] = $value;
                    
                    if ($marginStoper == $marginPrice) {
                      $marginPrice += $item['margin'];
                      $isExistSelected = true;
                    }
                  }

                  $htmlButtonList .= '<label '.($checked ? 'class="active"': '').'><input type="radio" name="'.
                    $property['property_id'].'#'.$i.'" value="'.$value.'" '.$checked.'>
                     <span class="label-black">'.$item['name'].$plus.'</span></label><br>';
                  
                  $secctionCartNoDummy[$property['property_id']][$i++] = array(
                    'value' => $value,
                    'name' => $property['name']);
                  $radioPieces[] = array('name' => $property['property_id'].'#'.($i-1), 'checked' => $checked, 'value' => $value, 'itemName' => $item['name'], 'price' => $plus);
                }

                if($htmlButtonList) {
                  // $html .= '<p>'.$htmlRadiobutton.$htmlButtonList."</p>";
                  if (!empty($radioPieces)) {
                    $propPieces[] = array('type' => 'assortment-radio', 'name' => $property['name'], 'additional' => $radioPieces);
                  }
                }
                break;
              }

              // Для типа вывода checkbox:                    
              if ($property['type_view'] == 'checkbox') {
               
                if ($property['data'] == 'null') {
                  break;
                }
                $checkBoxPieces = array();
                // $html .= '<p><span class="property-title">'.$property['name'].'</span><span class="property-delimiter">:</span><br/>';
  
                $i = 0;
                foreach ($property['data'] as $item) {
                  
                  if($item['active'] == 0) {
                    continue; 
                  }
                  $value = $item['name']."#".$item['margin']."#";
                  
                  if (empty($value)) {
                    $value = array('name' => $value, 'margin' => 0);
                  }
                  $plus = $this->addMarginToProp($item['margin'], $currencyRate, $currencyShort);
                  $plus = MG::getSetting('outputMargin')=='false' ? '' : $plus;

                  // $html .= '<label><input type="checkbox" name="'.
                  //   $property['property_id'].'#'.$i.'" value="+'.
                  //   $value['margin'].' '.MG::getSetting('currency').'">
                  //   <span class="label-black">'.$item['name'].$plus.' </span></label><br>';
                  $secctionCartNoDummy[$property['property_id']][$i++] = array(
                    'value' => $value,
                    'name' => $property['name']
                  );
                  $value = '+'.$value['margin'].' '.MG::getSetting('currency');
                  $checkBoxPieces[] = array('name' => $property['property_id'].'#'.($i-1), 'value' => $value, 'itemName' => $item['name'], 'price' => $plus);
                }

                // $html .= "</p>";
                if (!empty($checkBoxPieces)) {
                  $propPieces[] = array('type' => 'assortment-checkBox', 'name' => $property['name'], 'additional' => $checkBoxPieces);
                }
                break;
              }
            }

          case 'string': {
              $marginStoper = $marginPrice;
              if (!empty($property['data'][0]['name'])) {
		// viewData($value);
        				$property['data'][0]['group_prop'] = $this->groupProperty[$property['group_id']];
        				$property['data'][0]['priority'] = $property['sort'];
        				$property['data'][0]['unit'] = $property['unit'];
                $value = !empty($property['value']) ? $property['value'] : $property['data'];
                $stringsProperties[$property['name']] = $value;
                if($printStrProp=='true') {
                  // $html .= '<p><span class="property-title">'.$property['name'].'</span><span class="property-delimiter">:</span> <span class="label-black">'.
                  //   htmlspecialchars_decode($property['data'][0]['name']).
                  // '</span><span class="unit"> '.$property['unit'].'</span></p>';
                  $propPieces[] = array('type' => 'string', 'name' => $property['name'], 'text' => htmlspecialchars_decode($property['data'][0]['name']), 'unit' => $property['unit']);
                }
              }
              break;
            }

          default:
            if($property['type'] != 'textarea' && $property['type'] != 'color' && $property['type'] != 'size') {
              if (!empty($property['data'])) {
                // $html .= ''.$property['name'].': <span class="label-black">'.$property['data'][0]['name'].'</span>';
                $text='';
                if(isset($property['data'][0]['name'])){
                  $text=$property['data'][0]['name'];
                }
                $propPieces[] = array('type' => 'other', 'name' => $property['name'], 'text' => $text);
              }
            }
            break;
        }
      }
    }

    if ($adminOrder == 'yep') {
      $htmlProperty = $propPieces;
    }
    else{
      if(MG::getSetting('outputMargin') == 'false') {
        foreach ($propPieces as $key => $value) {
          if(is_array($value['additional'])) {
            foreach ($value['additional'] as $key1 => $value1) {
              unset($propPieces[$key]['additional'][$key1]['price']);
            }
          }
        }
      }
      $htmlProperty = MG::layoutManager('layout_htmlproperty', $propPieces);
    }
    // $htmlProperty = $html;

    $data = array(
     'maxCount' => $maxCount,
     'noneAmount' => $noneAmount,
     'noneButton' => $noneButton,
     'printCompareButton' => $printCompareButton,
     'ajax' => $ajax,
     'buyButton' => $buyButton,
     'classForButton' => $classForButton,
     'titleBtn' => $titleBtn,
     'id' => $id,
     'blockVariants' => $blockVariants,
     'addHtml' => $addHtml,
     'price' => ($productData ? $productData['price_course']: ''),
     'old_price' => ($productData ? $productData['old_price'] : ''),
     'activity' => $productData['activity'],
	   'parentData' => $param,
	   'htmlProperty' => $htmlProperty,
     'showCount' => $showCount,
     'action' => $action,
     'method' => $method,
     'catalogAction' => $catalogAction,
    );

    if ($adminOrder == 'yep') {
      $data['stringsProperties'] = Property::sortPropertyToGroup(array('stringsProperties' => $stringsProperties),true);
      $adminOrderFile = str_replace('mg-core'.DIRECTORY_SEPARATOR.'models', '', dirname(__FILE__)).'mg-admin'.DIRECTORY_SEPARATOR.'section'.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'layout'.DIRECTORY_SEPARATOR.'adminOrder.php';
      ob_start();
      include $adminOrderFile;
      $htmlLayout = ob_get_contents();
      ob_end_clean();
    }
    else{
      $htmlLayout = MG::layoutManager('layout_property', $data);
    }

    if (strpos($htmlLayout, '<form') === false ||
        strpos($htmlLayout, $action) === false ||
        strpos($htmlLayout, $method) === false ||
        strpos($htmlLayout, $catalogAction) === false ||
        strpos($htmlLayout, '</form>') === false
        ) {
      $htmlForm = '<form action="'.SITE.$action.'" method="'.$method.'" class="property-form '.$catalogAction.'" data-product-id='.$id.'>';
      $htmlForm .= $htmlLayout;
      $htmlForm .= '</form>';
    }
    else{
      $htmlForm = $htmlLayout;
    }

    $result = array(
        'html' => $htmlForm,    
        'marginPrice' => $marginPrice * $currencyRate, 
        'defaultSet' => $defaultSet,  // набор характеристик, которые были бы выбраны по умолчанию при открытии карточки товара.
        'propertyNodummy' => $secctionCartNoDummy, 
        'stringsProperties' => $stringsProperties
        );
    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Формирует блок вариантов товара.
   * <code>
   * $model = new Models_Product;
   * $result = $model->getBlockVariants(5);
   * echo $result;
   * </code>
   * @param int $id id товара
   * @param int $cat_id id категории
   * @param string $adminOrder заказ из админки или нет (по умолчанию - нет)
   * @return string|array (array - для админки)
   */
  public function getBlockVariants($id, $cat_id = 0, $adminOrder = 'nope') {
    $arr = $this->getVariants($id, false, true);   

    foreach ($arr as $key => $value) {
      if($value['count'] == 0) {
        $tmp = $value;
        unset($arr[$key]);
        $arr[$tmp['id']] = $tmp;
      }
    }

    foreach ($arr as &$var) {
      $var['price'] = MG::priceCourse($var['price_course']);
    }
    if ($adminOrder == 'yep') {
      $html = $arr;
    }
    else{
      $html = MG::layoutManager('layout_variant', array('blockVariants'=>$arr, 'type'=>'product'));
    }
    return $html;
  }

  /**
   * Формирует массив блоков вариантов товаров на странице каталога.
   * Метод создан для сокращения количества запросов к БД.
   * <code>
   * $model = new Models_Product;
   * $result = $model->getBlocksVariantsToCatalog(array(2,3,4));
   * echo $result;
   * </code>
   * @param int $array массив id товаров
   * @param array $returnArray если true то вернет просто массив без html блоков
   * @param bool $mgadmin если true то вернет данные для админки
   * @return string|array
   */
  public function getBlocksVariantsToCatalog($array, $returnArray = false, $mgadmin = false) {
    if (!empty($array)) {
      $in = implode(',', $array);
    }
    $orderBy = 'ORDER BY sort, id';
    $where = '';
    if(MG::getSetting('filterSortVariant') && !$mgadmin) {
      $parts = explode('|',MG::getSetting('filterSortVariant'));
      $parts[0] = $parts[0] == 'count' ? 'count_sort' : $parts[0];
      $orderBy = ' ORDER BY `'.DB::quote($parts[0],1).'` '.DB::quote($parts[1],1).', id';      
    }
    if(MG::getSetting('showVariantNull')=='false' && !$mgadmin) {
      if(MG::enabledStorage()) {
        $orderBy = ' AND (SELECT SUM(ABS(count)) FROM '.PREFIX.'product_on_storage WHERE product_id = p.id AND variant_id = pv.id) > 0 '.$orderBy; 
      } else {
        $orderBy = ' AND (pv.`count` != 0 OR pv.`count` IS NULL) '.$orderBy; 
      }
      
    }
    if(MG::enabledStorage()) {
      $storageCheck = ',(SELECT SUM(ABS(count)) FROM '.PREFIX.'product_on_storage WHERE product_id = p.id AND variant_id = pv.id) AS count';
    }
    // Получаем все варианты для передранного массива продуктов.
    if ($in) {
      $res = DB::query('
       SELECT pv.*, c.rate,(pv.price_course + pv.price_course * (IFNULL(c.rate,0))) as `price_course`,
       IF( pv.count<0,  1000000, pv.count ) AS  `count_sort`
       '.$storageCheck.'
       FROM `'.PREFIX.'product_variant` pv    
         LEFT JOIN `'.PREFIX.'product` as p ON 
           p.id = pv.product_id
         LEFT JOIN `'.PREFIX.'category` as c ON 
           c.id = p.cat_id  
       WHERE pv.product_id  in ('.$in.')
       '.$orderBy);

      if (!empty($res)) {
        while ($variant = DB::fetchAssoc($res)) {      
          if (!$returnArray) {

            $variant['old_price'] = MG::convertPrice($variant['old_price']);
            $variant['price_course'] = MG::convertPrice($variant['price_course']);

            $variant['price'] = MG::priceCourse($variant['price_course']);
          }
          $results[$variant['product_id']][] = $variant;
        }
      }
    }
    $productCount = 0;

    if(!$mgadmin) {
      foreach ($results as &$blockVariants) {
        for($i = 0; $i < count($blockVariants); $i++) {
          $productCount += $blockVariants[$i]['count'];
          if($blockVariants[$i]['count'] == 0) {
            $blockVariants[] = $blockVariants[$i];
            unset($blockVariants[$i]);
          }
        }
        $blockVariants = array_values($blockVariants);
      }
    }
    if ($returnArray) {
      return $results;
    }

    sort($array);
    
    $cash = Storage::get('getBlocksVariantsToCatalog-'.md5(json_encode($array).$productCount.@LANG));
    if(!$cash) {
      if (!empty($results)) {
        // Для каждого продукта создаем HTML верстку вариантов.
        foreach ($results as &$blockVariants) {       
          $html = MG::layoutManager('layout_variant', array('blockVariants'=>$blockVariants, 'type'=>'catalog'));
          $blockVariants = $html;
        }
      }
      Storage::save('getBlocksVariantsToCatalog-'.md5(json_encode($array).$productCount.@LANG), $results);
      return $results;
    } else {
      return $cash;
    }
  }

  /**
   * Формирует добавочную строку к названию характеристики,
   * в зависимости от наличия наценки и стоимости.
   * <code>
   * $model = new Models_Product;
   * $result = $model->addMarginToProp(250);
   * echo $result;
   * </code>
   * @param float $margin наценка
   * @param float $rate множитель цены
   * @param string $currency валюта
   * @return string
   */
  public function addMarginToProp($margin, $rate = 1, $currency = false) {
    $currency = $currency ? $currency : MG::getSetting('currencyShopIso');
    $symbol = '+';
    if (!empty($margin)) {
      if ($margin < 0) {
        $symbol = '-';
        $margin = $margin * -1;
      }
    }
    return (!empty($margin) || $margin === 0) ? ' '.$symbol.' '.MG::numberFormat($margin * $rate).' '.MG::getSetting('currency') : '';
  }

  /**
   * Отделяет название характеристики от цены название_пункта#стоимость#.
   * Пример входящей строки: "Красный#300#"
   * <code>
   * $model = new Models_Product;
   * $result = $model->parseMarginToProp('Красный#300#');
   * echo $result;
   * </code>
   * @param string $value строка, которую надо распарсить
   * @return array $array массив с разделенными данными, название пункта и стоимость.
   */
  public function parseMarginToProp($value) {
    $array = array();
    $pattern = "/^(.*)#([\d\.\,-]*)#$/";
    preg_match($pattern, $value, $matches);
    if (isset($matches[1]) && isset($matches[2])) {
      $array = array('name' => $matches[1], 'margin' => $matches[2]);
    }
    return $array;
  }

  /**
   * Обновление состояния корзины.
   * Используеться для пересчета корзины и обновления цены в карточке товара ajax'ом
   * <code>
   *   $model = new Models_Product;
   *   $model->calcPrice();
   * </code>
   */
  public function calcPrice() {
    $product = $this->getProduct($_POST['inCartProductId']);
    $currencyRate = MG::getSetting('currencyRate');      
    $currencyShopIso = MG::getSetting('currencyShopIso'); 
    $variantId = 0;    
    if (isset($_POST['variant'])) {
      $variants = $this->getVariants($_POST['inCartProductId']);

      $variant = $variants[$_POST['variant']];
      $variantId = $_POST['variant'];
      $product['price'] = $variant['price'];           
      $product['code'] = $variant['code'];
      $product['count'] = $variant['count'];
      $product['old_price'] = $variant['old_price'];
      $product['weight'] = $variant['weight'];
      $product['price_course'] = $variant['price_course'];   
      $product['variant'] = $variant['id'];
    }

    $cart = new Models_Cart;
    $property = $cart->createProperty($_POST);
    $product['currency_iso'] = $product['currency_iso']?$product['currency_iso']:$currencyShopIso;
    $product['price'] = $product['price_course']; 

    $tmpPrice = $product['price'];

    $product['price'] = MG::setWholePrice($product['price'], $product['id'], $_POST['amount_input'], $variantId);

    if ($tmpPrice != $product['price']) {
      $product['price'] = MG::convertPrice($product['price']);
    }
    
    $product['price'] = SmalCart::plusPropertyMargin($product['price'], $property['propertyReal'], $currencyRate[$product['currency_iso']]);

    $product['real_price'] = $product['price'];
    
    // $product['old_price'] *= $currencyRate[$product['currency_iso']];
    $product['remInfo'] = !empty($_POST['remInfo']) ? $_POST['remInfo'] : '';

    // для склада
    if(MG::enabledStorage()) {
      $storages = unserialize(stripcslashes(MG::getSetting('storages')));
      foreach ($storages as $item) {
        $count = MG::getProductCountOnStorage(0, $product['id'], $_POST['variant'], $item['id']);
        if($count == -1) {
          $storage[$item['id']] = lang('countMany');
          break;
        }
        $storage[$item['id']] += $count;
      }
    }

    if(MG::get('controller') == 'controllers_product' && USER::access('wholesales') == 1) {
      $res = DB::query('SELECT count, price FROM '.PREFIX.'wholesales_sys WHERE product_id = '.DB::quoteInt($product['id']).' 
        AND variant_id = '.DB::quoteInt($product['variant']).' ORDER BY count ASC');
      while ($row = DB::fetchAssoc($res)) {
        $row['price'] = MG::numberFormat(MG::convertPrice($row['price'])).' '.MG::getSetting('currency');
        $data['wholesalesData']['data'][] = $row;
      }
      $data['wholesalesData']['type'] = MG::getSetting('wholesalesType');
      $data['wholesalesData']['unit'] = $product['unit']?$product['unit']:$product['category_unit'];
      $wholesalesTable = MG::layoutManager('layout_wholesales_info', $data['wholesalesData']);
    }

    $response = array(
      'status' => 'success',
      'data' => array(
        'title' => $product['title'],
        'price' => MG::numberFormat($product['price']).' '.MG::getSetting('currency'),
        'old_price' => MG::numberFormat($product['old_price']).' '.MG::getSetting('currency'),
        'code' => $product['code'],
        'count' => $product['count'],
        'price_wc' => $product['price'],
        'real_price' => $product['real_price'],
        'weight' => $product['weight'],
        'count_layout' => MG::layoutManager('layout_count_product', $product),
        'actionInCatalog' => MG::getSetting('actionInCatalog'),
        'storage' => $storage,
        'wholesalesTable' => $wholesalesTable,
      )
    );

    echo json_encode($response);
    exit;
  }

  /**
   * Возвращает набор вариантов товара.
   * <code>
   * $productId = 25;
   * $model = new Models_Product;
   * $variants = $model->getVariants($productId);
   * viewData($variants);
   * </code>
   * @param int $id id продукта для поиска его вариантов
   * @param string|bool $title_variants название варианта продукта для поиска его вариантов
   * @param bool $sort использовать ли сортировку результатов (из настройки 'filterSortVariant')
   * @return array $array массив с параметрами варианта.
   */
  public function getVariants($id, $title_variants = false, $sort = false) {
    $results = array();
    $orderBy = 'ORDER BY sort';
    if(MG::getSetting('filterSortVariant')&& $sort) {
      $parts = explode('|',MG::getSetting('filterSortVariant'));
      $parts[0] = $parts[0] == 'count' ? 'count_sort' : $parts[0];
      $orderBy = ' ORDER BY `'.DB::quote($parts[0],1).'` '.DB::quote($parts[1],1).', id';       
    }
    // if(MG::getSetting('showVariantNull')=='false' && $sort) {
    //   $orderBy = ' AND pv.`count` != 0 '.$orderBy; 
    // }
    if (!$title_variants) {
      $res = DB::query('
      SELECT  pv.*, c.rate,(pv.price_course + pv.price_course *(IFNULL(c.rate,0))) as `price_course`,
      p.currency_iso, IF( pv.count<0,  1000000, pv.count ) AS  `count_sort`
      FROM `'.PREFIX.'product_variant` pv   
        LEFT JOIN `'.PREFIX.'product` as p ON 
          p.id = pv.product_id
        LEFT JOIN `'.PREFIX.'category` as c ON 
          c.id = p.cat_id       
      WHERE pv.product_id = '.DB::quote($id).' '.$orderBy);   
    } else {    
      $res = DB::query('
        SELECT  pv.*
        FROM `'.PREFIX.'product_variant` pv    
        WHERE pv.product_id = '.DB::quote($id).'  and pv.title_variant = '.DB::quote($title_variants).' '.$orderBy);
    }

    if (!empty($res)) {  
      while ($variant = DB::fetchAssoc($res)) {
        // MG::loadLocaleData($variant['id'], LANG, 'product_variant', $variant);
        // подгражем количество товара на складах
        if(!empty($_POST['storage'])) $this->storage = $_POST['storage'];
        $variant['price_course'] = MG::convertPrice($variant['price_course']);
        $variant['old_price'] = MG::convertPrice($variant['old_price']);
        $results[$variant['id']] = $variant;
      }
    }

    if(MG::enabledStorage()) {
      foreach ($results as $key => $value) {
        $ids[] = $key;
        $results[$key]['count'] = 0;
      }
      $ids = array_unique($ids);
      if(!empty($_POST['storage'])) $this->storage = $_POST['storage'];
      if($this->storage == 'all') {
        $storageWhere = '';
      } else {
        $storageWhere = 'AND storage = '.DB::quote($this->storage);
      }
      // $storage = ',(SELECT IF(SUM(count) != 0, SUM(count), 0) FROM '.PREFIX.'product_on_storage WHERE product_id = '.DB::quoteInt($id).' 
      //   AND variant_id = pv.id '.$storageWhere.') AS count ';
      $res = DB::query('SELECT count, variant_id FROM '.PREFIX.'product_on_storage WHERE variant_id IN ('.DB::quoteIN($ids).') '.$storageWhere);
      while($row = DB::fetchAssoc($res)) {
        $tmpData[$row['variant_id']][] = $row['count'];
      }
      foreach ($results as $key => $value) {
        // $tmpData[$key];
        foreach ($tmpData[$key] as $value) {
          if($results[$key]['count'] == -1) break;
          if($value == -1) {
            $results[$key]['count'] = -1;
            break;
          }
          $results[$key]['count'] += $value;
        }
      }
    }

    if(MG::getSetting('showVariantNull')=='false' && $sort) {
      foreach ($results as $key => $value) {
        if($results[$key]['count'] == 0) {
          unset($results[$key]);
        }
      }
    }

    // загрузка локалей для вариантов
    if((LANG != '')&&(LANG != 'LANG')&&(LANG != 'default')) {
      if(!empty($results)) {
        $idsVar = array();
        foreach ($results as $key => $value) {
          $idsVar[] = $key;
        }
        $res = DB::query('SELECT `id_ent`, `field`, `text` FROM '.PREFIX.'locales WHERE 
          `id_ent` IN ('.implode(',', $idsVar).') AND `table` = \'product_variant\' AND locale = '.DB::quote(LANG));
        while($row = DB::fetchAssoc($res)) {
          $localeData[$row['id_ent']][$row['field']] = $row['text'];
        }
        foreach ($results as $key => $value) {
          foreach ($value as $key2 => $item) {
            if(!empty($localeData[$key][$key2])) $results[$key][$key2] = $localeData[$key][$key2];
          }
        }
      }
    }

    // for($i = 0; $i < count($item['variants']); $i++) {
    //       if($item['variants'][$i]['count'] == 0) {
    //         $item['variants'][] = $item['variants'][$i];
    //         unset($item['variants'][$i]);
    //       }
    //     }
    //     $items['catalogItems'][$k]['variants'] = array_values($item['variants']);
    return $results;
  }

  /**
   * Возвращает массив id характеристик товара, которые ненужно выводить в карточке.
   * <code>
   * $result = Models_Product::noPrintProperty($productId);
   * viewData($result);
   * </code>
   * @return array $array - массив с id.
   */
  public function noPrintProperty() {
    $results = array();
   
    $res = DB::query('
      SELECT  `id`
      FROM `'.PREFIX.'property`     
      WHERE `activity` = 0');
    
    while ($row = DB::fetchAssoc($res)) {
      $results[] = $row['id'];
    }
 
    return $results;
  }
  
  /**
   * Возвращает HTML блок связанных товаров.
   * <code>
   * $args = array(
   *  'product' => 'CN182,В-500-1', // артикулы связанных товаров
   *  'category' => '2,4' // ID связанных категорий
   * );
   * $model = new Models_Product;
   * $result = $model->createRelatedForm($args);
   * echo $result;
   * </code>
   * @param array $args массив с данными о товарах
   * @param string $title заголовок блока
   * @param string $layout используемый лэйаут
   * @return string
   */
  public function createRelatedForm($args,$title='С этим товаром покупают', $layout = 'layout_related') {
    if($args) {
      $data['title'] = $title;
      
      $stringRelated = ' null';
      $sortRelated = array();
      if (!empty($args['product'])) {
        foreach (explode(',',$args['product']) as $item) {
          $stringRelated .= ','.DB::quote($item);
          $sortRelated[$item] = $item;
        }
        $stringRelated = substr($stringRelated, 1);
      }

      // выводить ли товар если его нет в наличии
      if(MG::getOption('printSameProdNullRem') == "true") {
        $forSameProdFilter = ' and count <> 0';
      } else {
        $forSameProdFilter = '';
      }

      $data['products'] = $this->getProductByUserFilter(' p.code IN ('.$stringRelated.') and p.activity = 1'.$forSameProdFilter);
      
      $datarelatedCat = array();
      if (!empty($args['category'])) {
        $stringRelatedCat = ' null';
        foreach (explode(',',$args['category']) as $item) {
          $stringRelatedCat .= ','.DB::quote($item);
        }
        $stringRelatedCat = substr($stringRelatedCat, 1);
        $relatedCat = $this->getProductByUserFilter(' p.`cat_id` IN ('.$stringRelatedCat.') and p.activity = 1'.$forSameProdFilter);
        shuffle($relatedCat);        
      }
      if (!empty($relatedCat)) {
        foreach ($relatedCat as $key => $prod) {
          if ($key > 10) {
            break;
          }
          $data['products'][] = $prod;
          $sortRelated[$prod['code']] = $prod;
        }
      }
      if(!empty($data['products'])) {
        $data['currency'] = MG::getSetting('currency');
        foreach ($data['products'] as $item) {            
          $img = explode('|',$item['image_url']);
          $item['img'] = $img[0];
          $item['category_url'] = (MG::getSetting('shortLink') == 'true' ? '' : $item['category_url'].'/');
          $item['category_url'] = ($item['category_url'] == '/' ? 'catalog/' : $item['category_url']);
          $item['url'] = (MG::getSetting('shortLink') == 'true' ? SITE .'/'.$item["product_url"] : SITE .'/'.(isset($item["category_url"])&&$item["category_url"]!='' ? $item["category_url"] : 'catalog/').$item["product_url"]);

          $item['price_course'] = MG::convertPrice($item['price_course']);

          $item['price'] = MG::priceCourse($item['price_course']);
          
          $item['old_price'] = MG::convertPrice($item['old_price']);

          $sortRelated[$item['code']] = $item;
        }
        $data['products'] = array();
        //сортируем связанные товары в том порядке, в котором они идут в строке артикулов
        foreach ($sortRelated as $item) {
          if(!empty($item['id']) && is_array($item)) {
            $data['products'][$item['id']] = $item;
          }
        }      
        $result = '';
        $result = MG::layoutManager($layout, $data);
      }
      
    };
    
    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }
  
  
  /**
   * Конвертирование стоимости товаров по заданному курсу.
   * <code>
   * $model = new Models_Product;
   * $model->convertToIso('USD', array(2, 3, 4));
   * </code>
   * @param string $iso валюта в которую будет производиться конвертация.
   * @param array $productsId массив с id продуктов.
   */
  public function convertToIso($iso,$productsId=array()) {
    
    $productsId = implode(',', $productsId);
    if(empty($productsId)) {$productsId = 0;};
    
    // вычислим соотношение валют имеющихся в базе товаров к выбранной для замены
    // вычисление производится на основе имеющихся данных по отношению в  валюте магазина
    $currencyShort = MG::getSetting('currencyShort');     
    $currencyRate = MG::getSetting('currencyRate');
    $currencyShopIso = MG::getSetting('currencyShopIso'); 
       
    // если есть непривязанные к валютам товары, то  назначаем им текущую валюту магазина
    DB::query('
      UPDATE `'.PREFIX.'product` SET 
            `currency_iso` = '.DB::quote($currencyShopIso).'
      WHERE `currency_iso` =  "" AND `id` IN ('.DB::quote($productsId, true).')');
    DB::query('
      UPDATE `'.PREFIX.'product_variant` SET 
            `currency_iso` = '.DB::quote($currencyShopIso).'
      WHERE `currency_iso` =  "" AND `id` IN ('.DB::quote($productsId, true).')');

    // запоминаем базовое соотношение курсов к валюте магазина
    $rateBaseArray = $currencyRate;  
    $rateBase = $currencyRate[$iso];  
    // создаем новое соотношение валют по отношению в выбранной для конвертации
    foreach ($currencyRate as $key => $value) {     
        if(!empty($rateBase)) {    
          $currencyRate[$key] = $value / $rateBase;                 
        }        
    }
    $currencyRate[$iso] = 1;
  
    // пересчитываем цену, старую цену и цену по курсу для выбранных товаров
    foreach ($currencyRate as $key => $rate) { 
      DB::query('
      UPDATE `'.PREFIX.'product`
      SET `price`= ROUND(`price`*'.DB::quote($rate,TRUE).',2),
          `price_course`= ROUND(`price`*'.DB::quote(($rateBaseArray[$iso]?$rateBaseArray[$iso]:1),TRUE).',2)
      WHERE currency_iso = '.DB::quote($key).' AND `id` IN ('.DB::quote($productsId, true).')');
      
      // также и в вариантах
      DB::query('
      UPDATE `'.PREFIX.'product_variant`
       SET `price`= ROUND(`price`*'.DB::quote($rate,TRUE).',2),
          `price_course`= ROUND(`price`*'.DB::quote(($rateBaseArray[$iso]?$rateBaseArray[$iso]:1),TRUE).',2)
      WHERE currency_iso = '.DB::quote($key).' AND `product_id` IN ('.DB::quote($productsId, true).')');
    }
    
    // всем выбранным продуктам изменяем ISO
     DB::query('
      UPDATE `'.PREFIX.'product`
      SET `currency_iso` = '.DB::quote($iso).'
      WHERE `id` IN ('.DB::quote($productsId, true).')');
     
     DB::query('
      UPDATE `'.PREFIX.'product_variant`
      SET `currency_iso` = '.DB::quote($iso).'
      WHERE `product_id` IN ('.DB::quote($productsId, true).')');

  }

   /**
   * Обновления цены выдранных товаров в соответствии с курсом валюты.
   * <code>
   * $model = new Models_Product;
   * $model->updatePriceCourse('USD', array(2, 3, 4));
   * </code>
   * @param string $iso валюта в которую будет производиться конвертация.
   * @param array $listId массив с id продуктов.
   */
  public function updatePriceCourse($iso,$listId = array()) {
    
     if(empty($listId)) {$listId = 0;}
     else{
       foreach ($listId as $key => $value) {
         $listId[$key] = intval($value);
       }
       $listId = implode(',', $listId);     
     }
    
    // вычислим соотношение валют имеющихся в базе товаров к выбранной для замены
    // вычисление производится на основе имеющихся данных по отношению в  валюте магазина
    $currencyShort = MG::getSetting('currencyShort');     
    $currencyRate = unserialize(stripcslashes(MG::getOption('currencyRate')));
    $currencyShopIso = MG::getOption('currencyShopIso');

    $rate = $currencyRate[$iso];
    
    if($rate != 0) {
      DB::query('
        UPDATE `'.PREFIX.'wholesales_sys` 
          SET `price`= ROUND(`price`/'.DB::quote((float)$rate,TRUE).',2) 
          WHERE `product_id` IN ('.DB::quote($listId, true).')');
    }
    
    $where = '';
    if(!empty($listId)) {
      $where =' AND `id` IN ('.DB::quote($listId, true).')';
    }
    
    $whereVariant = '';
    if(!empty($listId)) {
      $whereVariant =' AND `product_id` IN ('.DB::quote($listId, true).')';
    }
    
    DB::query('
     UPDATE `'.PREFIX.'product` SET 
           `currency_iso` = '.DB::quote($currencyShopIso).'
     WHERE `currency_iso` = "" '.$where);
  
    
    $rate = $currencyRate[$iso];  
    foreach ($currencyRate as $key => $value) {     
        if(!empty($rate)) {
          $currencyRate[$key] = $value / $rate;                 
        }        
    }
    $currencyRate[$iso] = 1;

    foreach ($currencyRate as $key => $rate) {
   
      DB::query('
      UPDATE `'.PREFIX.'product` 
        SET `price_course`= ROUND(`price`*'.DB::quote((float)$rate,TRUE).',2)          
      WHERE currency_iso = '.DB::quote($key).' '.$where);
     
      DB::query('
      UPDATE `'.PREFIX.'product_variant` 
        SET `price_course`= ROUND(`price`*'.DB::quote((float)$rate,TRUE).',2)         
      WHERE currency_iso = '.DB::quote($key).' '.$whereVariant);
    }
    
  }
  
   /**
   * Удаляет картинки вариантов товара.
   * <code>
   * $model = new Models_Product;
   * $model->deleteImagesVariant(4);
   * </code>
   * @param int $productId ID товара
   * @return bool
   */
  public function deleteImagesVariant($productId) { 
    $imagesArray = array();
    // Удаляем картинки продукта из базы.
    $res = DB::query('
      SELECT image
      FROM `'.PREFIX.'product_variant` 
      WHERE product_id = '.DB::quote($productId) );
    while($row = DB::fetchAssoc($res)) {
      $imagesArray[] = $row['image'];
    }    
    $this->deleteImagesProduct($imagesArray, $productId); 
    return true;
  }
  
  /**
   * Подготавливает названия изображений товара.
   * <code>
   *   $model = new Models_Product;
   *   $res = $model->prepareImageName($product);
   *   viewData($res);
   * </code>
   * @param array $product массив с товаром
   * @return array
   */
  public function prepareImageName($product) {   
    $result = $product;
    
    $images = explode("|", $result['image_url']);
    foreach($images as $cell=>$image) {      
      $pos = strpos($image, 'no-img');
      if($pos || $pos === 0) {
        unset($images[$cell]);        
      } else {
        $images[$cell] = basename($image);
      }      
    }
    $result['image_url'] = implode('|', $images);
    
    foreach($result['variants'] as $cell=>$variant) {
      if(empty($variant['image'])) {
        continue;
      }
      
      $pos = strpos($variant['image'], 'no-img');
      if($pos || $pos === 0) {
        unset($result['variants'][$cell]['image']);
      } else {
        $result['variants'][$cell]['image'] = str_replace(array('30_', '70_'), '', basename($variant['image']));      
        $images[] = $result['variants'][$cell]['image'];
      }
    }
    
    return $result;
  }
  
  /**
   * Копирует изображения товара в новую структуру хранения.
   * 
   * @param array $images - массив изображений
   * @param int $productId - id товара
   * @param string $path - папка в которой лежат исходные изображения
   * @param bool $removeOld - флаг удаления изображений из папки $path после копирования в новое место
   * @return void
   */
  public function movingProductImage($images, $productId, $path='uploads', $removeOld = true) {
    if(empty($images)) {
      return false;
    }
    
    $ds = DIRECTORY_SEPARATOR;
    $dir = floor($productId/100).'00';
    $curdir = getcwd();
    
    if(!file_exists('uploads'.$ds.'product'.$ds.$dir.$ds.$productId.$ds.'thumbs')) {
      
      if(!file_exists('uploads'.$ds.'product'.$ds.$dir.$ds.$productId)) {

        if(!file_exists('uploads'.$ds.'product'.$ds.$dir)) {

          if(!file_exists('uploads'.$ds.'product')) {
            if(chdir('uploads'.$ds)) {
              mkdir('product', 0755);
              chdir($curdir);
            }             
          }

          if(chdir('uploads'.$ds.'product'.$ds)) {
            mkdir($dir, 0755);
            chdir($curdir);
          }           
        }

        if(chdir('uploads'.$ds.'product'.$ds.$dir.$ds)) {
          mkdir($productId, 0755);
          chdir($curdir);
        }        
      }
      
      if(chdir('uploads'.$ds.'product'.$ds.$dir.$ds.$productId.$ds)) {
        mkdir('thumbs', 0755);
        chdir($curdir);
      }      
    }
    
    foreach($images as $cell=>$image) {
      $pos = strpos($image, '_-_time_-_');

      if ($pos) {
        if (MG::getSetting('addDateToImg') == 'true') {
          $tmp1 = explode('_-_time_-_', $image);
          $tmp2 = strrpos($tmp1[1], '.');
          $tmp1[0] = date("_Y-m-d_H-i-s", substr_replace($tmp1[0], '.', 10, 0));
          $imageClear = substr($tmp1[1], 0, $tmp2).$tmp1[0].substr($tmp1[1], $tmp2);
        }
        else{
          $imageClear = substr($image, ($pos+10));
        }
      }
      else{
        $imageClear = $image;
      }

      if(copy($path.$ds.$image, 'uploads'.$ds.'product'.$ds.$dir.$ds.$productId.$ds.$imageClear)) {
        
        if(copy($path.$ds.'thumbs'.$ds.'30_'.$image, 'uploads'.$ds.'product'.$ds.$dir.$ds.$productId.$ds.'thumbs'.$ds.'30_'.$imageClear) && $removeOld) {
          unlink($path.$ds.'thumbs'.$ds.'30_'.$image);
        }
        
        if(copy($path.$ds.'thumbs'.$ds.'70_'.$image, 'uploads'.$ds.'product'.$ds.$dir.$ds.$productId.$ds.'thumbs'.$ds.'70_'.$imageClear) && $removeOld) {
          unlink($path.$ds.'thumbs'.$ds.'70_'.$image);
        }
        
        if($removeOld) {
          unlink($path.$ds.$image);
        }
      }elseif(copy('uploads'.$ds.$image, 'uploads'.$ds.'product'.$ds.$dir.$ds.$productId.$ds.$imageClear)) {
        
        if(copy('uploads'.$ds.'thumbs'.$ds.'30_'.$image, 'uploads'.$ds.'product'.$ds.$dir.$ds.$productId.$ds.'thumbs'.$ds.'30_'.$imageClear) && $removeOld) {
          unlink('uploads'.$ds.'thumbs'.$ds.'30_'.$image);
        }
        
        if(copy('uploads'.$ds.'thumbs'.$ds.'70_'.$image, 'uploads'.$ds.'product'.$ds.$dir.$ds.$productId.$ds.'thumbs'.$ds.'70_'.$imageClear) && $removeOld) {
          unlink('uploads'.$ds.'thumbs'.$ds.'70_'.$image);
        }
        
        if($removeOld) {
          unlink('uploads'.$ds.$image);
        }
      }
    }
  }
  
}