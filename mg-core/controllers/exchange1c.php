<?php

/**
 * Класс Exchange1c - предназначен для обмена данными между "1с - Управление Торговлей" и Moguta.CMS.
 * - Импортирует товары из 1с на сайт.
 *
 * @author Авдеев Марк <mark-avdeev@mail.ru>
 * @package moguta.cms
 * @subpackage Controller
 */
class Controllers_Exchange1c extends BaseController {

  public $startTime = null;
  public $maxExecTime = null;
  public $mode = null;
  public $type = null;
  public $filename = null;
  public $auth = null;
  public $unlinkFile = false;
  public $commerceML = '2.04';

  public function __construct() {

    if (preg_match('/Basic+(.*)$/i', $_SERVER['REDIRECT_HTTP_AUTHORIZATION'], $matches)) {
      list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = explode(':', base64_decode(substr($_SERVER['REDIRECT_HTTP_AUTHORIZATION'], 6)));
    }

    $this->commerceML = '2.04';
    // Проверка версии 1с , для 11+ изменен алгоритм обработки  
    if (strpos($_SERVER['HTTP_USER_AGENT'], '/11.') !== false) {
      $this->commerceML = '2.08';
    }

    if (!empty($files)) {
      file_put_contents('data/' . $filename, $files, FILE_APPEND);
      echo "success\n";
      MG::setOption(array('option' => 'downtime', 'value' => 'false'));
    }

    if (empty($_GET['mode'])) {
      MG::redirect('/');
    };

    if(MG::getSetting('closeSite') == 'true') MG::setOption(array('option' => 'downtime', 'value' => 'true'));

    MG::disableTemplate();
    Storage::$noCache = true;
    $this->unlinkFile = true;
    $this->startTime = microtime(true);
    $this->maxExecTime = min(30, @ini_get("max_execution_time"));
    if (empty($this->maxExecTime)) {
      $this->maxExecTime = 30;
    }

    $mode = (string) $_GET['mode'];
    $this->mode = $mode;
    $this->type = $_GET['type'];
    $this->filename = $_GET['filename'];
    $this->auth = USER::auth($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);

    $fromPlugin = (empty($_SESSION['CML_PLUGIN_ACTIVE'])) ? false : true;

    if ($mode && $this->auth || $mode && $fromPlugin) {
      $this->$mode();
    }
  }

  /**
   * 1 шаг - авторизация 1с клиента.
   */
  public function checkauth() {
    echo "success\n";
    echo session_name() . "\n";
    echo session_id() . "\n";
    exit;
  }

  /**
   * Выгрузка заказов: exchange1c?type=sale&mode=success
   */
  public function success() {
    echo "success\n";
    echo session_name() . "\n";
    echo session_id() . "\n";
    MG::setOption(array('option' => 'downtime', 'value' => 'false'));
    exit;
  }

  /**
   * 2 шаг - сообщаем в 1с клиент о поддержке работы с архивами.
   */
  public function init() {
    $zip = extension_loaded('zip') ? "yes" : "no";
    $fileLimit1c = MG::getSetting('fileLimit1C');
    echo "zip=" . $zip . "\n";
    echo "file_limit=" . intval($fileLimit1c) . "\n";
    exit;
  }

  /**
   * Запрос заказов
   */
  public function query() {

    $orderModel = new Models_Order();
    $ordersArr = $orderModel->getOrder('`updata_date` > IFNULL(`1c_last_export`, 0)');
    $listModifyOrderId = '0';
    $nXML = '<?xml version="1.0" encoding="utf-8"?>
<КоммерческаяИнформация ВерсияСхемы="2.04" ДатаФормирования="' . date('Y-m-d') . '">
';
    $xml = new XMLWriter();

    $xml->openMemory();
    $xml->setIndent(true);
    foreach ($ordersArr as $order) {

      $yurInfo = unserialize(stripslashes($data['order']['yur_info']));


      $xml->startElement("Документ");
      $xml->writeElement("Ид", $order['number']);
      $listModifyOrderId.=',' . $order['id'];
      $xml->writeElement("Номер", $order['number']);
      $xml->writeElement("Дата", date('Y-m-d', strtotime($order['add_date'])));
      $xml->writeElement("ХозОперация", 'Заказ товара');
      $xml->writeElement("Роль", 'Продавец');
      $xml->writeElement("Валюта", MG::getSetting('currencyShopIso'));
      $xml->writeElement("Курс", 1);
      $xml->writeElement("Сумма", $order['summ']);

      $xml->startElement("Контрагенты");
      $xml->startElement("Контрагент");
      $xml->writeElement("Ид", $order['user_email']);
      $xml->writeElement("Наименование", $yurInfo['nameyur']?$yurInfo['nameyur']:$order['name_buyer']);
      $xml->writeElement("Роль", "Покупатель");
      $xml->writeElement("ПолноеНаименование", $yurInfo['nameyur']?$yurInfo['nameyur']:$order['name_buyer']);
      $xml->writeElement("Имя", $order['name_buyer']);
      $xml->startElement("АдресРегистрации");
      $xml->writeElement("Представление", $order['address']);
      $xml->startElement("АдресноеПоле");
      $xml->writeElement("Тип", 'Страна');
      $xml->writeElement("Значение", 'RU');
      $xml->endElement();  //АдресноеПоле
      $xml->endElement(); //АдресРегистрации

      $xml->startElement("Контакты");
      $xml->startElement("Контакт");
      $xml->writeElement("Тип", 'Телефон');
      $xml->writeElement("Значение", $order['phone']);
      $xml->endElement(); //Контакт
      $xml->startElement("Контакт");
      $xml->writeElement("Тип", 'Почта');
      $xml->writeElement("Значение", $order['user_email']);
      $xml->endElement(); //Контакт
      $xml->endElement(); //Контакты


      $xml->endElement(); //Контрагент
      $xml->endElement(); //Контрагенты
      $xml->writeElement("Время", date('H:i:s', strtotime($order['add_date'])));

      $xml->startElement("Товары");


	   //Информация о доставке
		
		if($order['delivery_cost']>0){
			$xml->startElement("Товар");
	 		$xml->writeElement("Ид", 'ORDER_DELIVERY');
	        $xml->writeElement("Наименование", 'Доставка заказа');
	        $xml->writeElement("БазоваяЕдиница", "шт");
	        $xml->writeAttribute("Код", "796");
	        $xml->writeAttribute("НаименованиеПолное", "Штука");
	        $xml->writeAttribute("МеждународноеСокращение", "PCE");
	      
	        $xml->writeElement("ЦенаЗаЕдиницу", $order['delivery_cost']);
	        $xml->writeElement("Количество", 1);
	        $xml->writeElement("Сумма", $order['delivery_cost']);

	        $xml->startElement("ЗначенияРеквизитов");
	        $xml->startElement("ЗначениеРеквизита");
	        $xml->writeElement("Наименование", 'ВидНоменклатуры');
	        $xml->writeElement("Значение", 'Услуга');
	        $xml->endElement(); //ЗначениеРеквизита
	        $xml->startElement("ЗначениеРеквизита");
	        $xml->writeElement("Наименование", 'ТипНоменклатуры');
	        $xml->writeElement("Значение", 'Услуга');
	        $xml->endElement(); //ЗначениеРеквизита
	        $xml->endElement(); //ЗначенияРеквизитов
			$xml->endElement(); //Товар
		}
		//Конец - Информация о доставке

      $products = unserialize(stripslashes($order['order_content']));
      foreach ($products as $product) {
        $xml->startElement("Товар");
        $extendId = $product['id'];

        if (!empty($product['variant_id'])) {
          $sql = "
            SELECT CONCAT_WS('#', p.`1c_id`, pv.`1c_id`) as 1c_id 
            FROM `" . PREFIX . "product_variant` pv
              LEFT JOIN `" . PREFIX . "product` p
                ON p.id = pv.product_id
            WHERE pv.`id` = " . DB::quote($product['variant_id']) . " 
              AND pv.`product_id` = " . DB::quote($product['id']);
        } else {
          $sql = "
            SELECT `1c_id`
            FROM  `" . PREFIX . "product`
            WHERE `id` = " . DB::quote($product['id']) . "
          ";
        }

        $res = DB::query($sql);

        if ($row = DB::fetchAssoc($res)) {
          $extendId = $row['1c_id'] ? $row['1c_id'] : $product['id'];
        }

        $xml->writeElement("Ид", $extendId);
        $xml->writeElement("Наименование", htmlspecialchars_decode($product['name']));
        $xml->writeElement("ЦенаЗаЕдиницу", $product['price']);
        $xml->writeElement("БазоваяЕдиница", "шт");
        $xml->writeElement("Артикул", $product['code']);
        $xml->writeElement("Код", $product['code']);

        $xml->writeElement("Количество", $product['count']);
        $xml->writeElement("Сумма", $product['price'] * $product['count']);
        $xml->startElement("ЗначенияРеквизитов");
        $xml->startElement("ЗначениеРеквизита");
        $xml->writeElement("Наименование", 'ВидНоменклатуры');
        $xml->writeElement("Значение", 'Товар');
        $xml->endElement(); //ЗначениеРеквизита
        $xml->startElement("ЗначениеРеквизита");
        $xml->writeElement("Наименование", 'ТипНоменклатуры');
        $xml->writeElement("Значение", 'Товар');
        $xml->endElement(); //ЗначениеРеквизита
        $xml->endElement(); //ЗначенияРеквизитов

        $xml->endElement(); //Товар
      }

      $xml->endElement(); //Товары        

      $xml->startElement("ЗначенияРеквизитов");

      $arrayStatus = array(
        1 => 'Подтвержден',
        2 => 'Собран',
        6 => 'Собран',
        3 => 'Отгружен',
        4 => 'Отменен',
        5 => '[F] Доставлен',
        0 => '[N] Принят',
      );

      if ($order['status_id']) {
        $xml->startElement("ЗначениеРеквизита");

        $xml->writeElement("Наименование", 'Статус заказа');
        $xml->writeElement("Значение", $arrayStatus[$order['status_id']]);

        $xml->endElement(); //ЗначениеРеквизита      

        if ($order['status_id'] == 4) {
          $xml->startElement("ЗначениеРеквизита");
          $xml->writeElement("Наименование", 'Отменен');
          $xml->writeElement("Значение", 'true');
          $xml->endElement();
        }
      }

      $xml->endElement(); //ЗначенияРеквизитов
      $xml->endElement();  // Документ 
    }

    $nXML .= $xml->outputMemory();
    //$nXML = mb_convert_encoding($nXML, "WINDOWS-1251", "UTF-8");
    $nXML .= '</КоммерческаяИнформация>';

  
    if ($listModifyOrderId != '0') {
      DB::query('UPDATE ' . PREFIX . 'order SET `1c_last_export` = now() WHERE id IN(' . DB::quote($listModifyOrderId, 1) . ')');
    }

    header("Content-type: text/xml; charset=utf-8");
    echo "\xEF\xBB\xBF";
    echo $nXML;
  
  }
  /**
   * Обновление заказов
   * @param string $filename
   */
  public function ordersUpdate($filename) {

    // вычисляем какой из имеющихся файлов в папке обмена относится к заказам.
    $sep = DIRECTORY_SEPARATOR;
    $dirname = dirname(__FILE__);
    $realDocumentRoot = str_replace($sep . 'mg-core' . $sep . 'controllers', '', $dirname);
    $files = scandir($realDocumentRoot . '/tempcml/');

    foreach ($files as $name) {
      if (end(explode(".", $name)) == 'xml' && $name != "import.xml" && $name != "offers.xml" && $name != "import0_1.xml" && $name != "offers0_1.xml") {
        $filename = $name;
      }
    }

    $orderModel = new Models_Order();
    $arrayStatus = array(
      'Новый' => 0,
      'Подтвержден' => 1,
      'Собран' => 6,
      'Отгружен' => 3,
      'Доставлен' => 5,
      'Возврат' => 5,
      'Отменен' => 4,
      '[F] Доставлен' => 5,
      '[N] Принят' => 0,
    );

    $xml = simplexml_load_file('tempcml/' . $filename);

    foreach ($xml->Документ as $order) {
      $orderId = 0;
      $orderNumber = $order->Номер;
      $orderStatusId = '';

      foreach ($order->ЗначенияРеквизитов->ЗначениеРеквизита as $item) {
        //if ($item->Наименование == "Номер по 1С") {
        //  $orderNumber = $item->Значение;    
        //}
        $res = DB::query("SELECT id FROM " . PREFIX . "order WHERE number = " . DB::quote($orderNumber));

        if ($row = DB::fetchAssoc($res)) {
          $orderId = $row['id'];
        }

        if ($item->Наименование == "Дата оплаты по 1С" && !empty($item->Значение)) {
          $orderStatusId = 2;
        }

        if ($item->Наименование == "Статус заказа") {
          $orderStatus = $item->Значение;
          $orderStatusId = $arrayStatus[(string) $orderStatus];
        }
        //if ($item->Наименование == "Проведен") {
        //  $passed = $item->Значение == "true"?1:0;
        //}
        if ($item->Наименование == "ПометкаУдаления") {
          $delete = ($item->Значение == "true") ? 1 : 0;
          if ($delete) {
            $orderModel->deleteOrder($orderId);
          }
        }
      }

      if (empty($orderId) || empty($orderStatusId)) {
        continue;
      }

      //echo "<br>".$orderId.'['.$orderNumber.']['.$orderId1c.']='.$orderStatus.'['.$orderStatusId.']';

      $arrayOrder = array(
        'id' => $orderId,
        'status_id' => $orderStatusId,
      );

      $orderModel->updateOrder($arrayOrder);
    }

    unlink($realDocumentRoot.'/tempcml/'.$filename);
    $upload = new Upload(false);
    $upload->removeDirectory($realDocumentRoot . '/tempcml');
  }

  /**
   * 3 шаг - сохраняем файл выгрузки полученный из 1с.
   */
  public function file() {
    $filename = $this->filename;

    if (isset($filename) && ($filename) > 0) {
      $filename = trim(str_replace("\\", "/", trim($filename)), "/");
    }

    if (function_exists("file_get_contents")) {
      if (end(explode(".", $filename)) == 'zip') {
        $data = file_get_contents("php://input");
        file_put_contents($filename, $data, FILE_APPEND);
        $_SESSION['fileName'] = $filename;

        if ($this->type == "sale") {
          $this->extractZip($filename);
          $this->ordersUpdate();
        }

        echo "success\n";
      } else {
        $data = file_get_contents("php://input");
        $sep = DIRECTORY_SEPARATOR;
        $dirname = dirname(__FILE__);
        $realDocumentRoot = str_replace($sep . 'mg-core' . $sep . 'controllers', '', $dirname);
        chdir($realDocumentRoot);
        mkdir("tempcml", 0777);
        file_put_contents($realDocumentRoot . '/tempcml/' . $filename, $data, FILE_APPEND);

        if ($this->type == "sale") {
          $this->ordersUpdate($filename);
        }

        echo "success\n";
      }
    } else {
      echo "failure\n";
      echo "Error of get data!\n";
    }

    exit;
  }
  /**
   * Получение файлов из архива
   * @param string $filename путь к файлу архива
   */
  private function getFilesFromZip($filename) {
    if (end(explode(".", $filename)) == 'zip') {
      if ($this->extractZip($filename)) {

        if ($this->type == "catalog") {
          $_SESSION['lastCountOffer1cImport'] = 0;
          $_SESSION['lastCountProduct1cImport'] = 0;
          unset($_SESSION['fileName']);
        }
      } else {
        echo "failure\n";
        echo "Error unzip data!\n";
        exit();
      }
    }
  }

  /**
   * 4 шаг - запуск процесса импорта файла выгрузки.
   */
  public function import() {
    if (isset($_SESSION['fileName'])) {
      $this->getFilesFromZip($_SESSION['fileName']);
    }

    $log = $this->processImportXml($this->filename);
    echo "success\n";
    echo session_name() . "\n";
    echo session_id() . "\n";
    echo $log;
    exit;
  }

  /**
   * 5 шаг - распаковывает архив с данными по выгрузкам заказов и товаров.
   * @param string $file - путь к файлу архива с данными.
   * @return bool
   */
  public static function extractZip($file) {

    if (file_exists($file)) {
      $zip = new ZipArchive;
      $res = $zip->open($file, ZIPARCHIVE::CREATE);

      if ($res === TRUE) {
        $sep = DIRECTORY_SEPARATOR;
        $dirname = dirname(__FILE__);
        $realDocumentRoot = str_replace($sep . 'mg-core' . $sep . 'controllers', '', $dirname);
        $zip->extractTo($realDocumentRoot . '/tempcml/');
        $zip->close();
        unlink($file);
        return true;
      } else {
        return false;
      }
    }
    return false;
  }

  /**
   * Парсинг XML и импорт в БД товаров.
   * @param string $filename - путь к файлу архива с данными.
   * @return string|void
   */
  public function processImportXml($filename) {

    if ($this->commerceML != '2.04' && $filename == 'import.xml') {
      $filename = 'import0_1.xml';
    }

    if ($this->commerceML != '2.04' && $filename == 'offers.xml') {
      $filename = 'offers0_1.xml';
    }

    $resizeType = MG::getSetting("imageResizeType");

    if (empty($resizeType)) {
      $resizeType = 'PROPORTIONAL';
    }

    $importOnlyNew = false;
    $sep = DIRECTORY_SEPARATOR;
    $dirname = dirname(__FILE__);
    $realDocumentRoot = str_replace($sep.'mg-core'.$sep.'controllers', '', $dirname);
    $upload = new Upload(false);

    $lastPositionProduct = $_SESSION['lastCountProduct1cImport'];
    $lastPositionOffer = $_SESSION['lastCountOffer1cImport'];
    $xml = $this->getImportXml($filename);

    if ($xml && ($filename == 'import.xml' || $filename == 'import0_1.xml')) {

      foreach ($xml->Каталог->attributes() as $key => $val) {
        if ($key == 'СодержитТолькоИзменения' && $val == "true") {
          $importOnlyNew = true;
        }
      }

      if (isset($xml->Каталог->СодержитТолькоИзменения)) {
        $importOnlyNew = $xml->Каталог->СодержитТолькоИзменения[0] == 'true' ? true : false;
      }

      if (empty($lastPositionProduct) && $importOnlyNew == false) {
        // если установлена директива CLEAR_CATALOG = 1 в config.ini, то удаляем товары перед синхронизацией с 1с
        $clearCatalog1c = MG::getSetting('clearCatalog1C');

        if ($clearCatalog1c && $clearCatalog1c != "false") {
          DB::query('DELETE FROM `' . PREFIX . 'product` WHERE 1');
          DB::query('DELETE FROM `' . PREFIX . 'category` WHERE 1');
          DB::query('DELETE FROM `' . PREFIX . 'product_variant` WHERE 1');
          DB::query('DELETE FROM `' . PREFIX . 'product_on_storage` WHERE 1');
          DB::query('DELETE FROM `' . PREFIX . 'product_user_property_data` WHERE 1');
		  
		  MG::rrmdir(URL::getDocumentRoot().DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'product');
		  @mkdir('product', 0755);
		  
        }
      }

	  
	  
      $category = $this->groupsGreate($xml->Классификатор, $category, 0);
      $this->propertyСreate($xml->Классификатор->Свойства);
	  
	  
	  // После того как созданы харатеристики из 1с-свойств, получаем внутренний 
	  // 1c_id для характеристики Вес. По указанному названию в настройказ 1с, на стороне CMS.
	  // Например: 'Вес брутто, кг'
	  // ля того чтобы записать значения характеристики в системное поле Вес, для дальнейшего расчета в плагинах доставки.
	  
	  $weightPropertyName = MG::getSetting('weightPropertyName1с')?MG::getSetting('weightPropertyName1с'):'Вес';
	  $weightProperty1cId = 'none';	  
	  $res = DB::query(
	    'SELECT 1c_id 
        FROM `' . PREFIX . 'property` 
        WHERE `name`=' . DB::quote($weightPropertyName). ' and 1c_id <> "" '
      );
	  if ($row = DB::fetchAssoc($res)) {	     
		  $weightProperty1cId = $row['1c_id'];
	  }
	  
	  
	  
      $model = new Models_Product;
      $currentPosition = 0;
      
      $widthPreview = MG::getSetting('widthPreview') ? MG::getSetting('widthPreview') : 200;
      $widthSmallPreview = MG::getSetting('widthSmallPreview') ? MG::getSetting('widthSmallPreview') : 50;
      $heightPreview = MG::getSetting('heightPreview') ? MG::getSetting('heightPreview') : 100;
      $heightSmallPreview = MG::getSetting('heightSmallPreview') ? MG::getSetting('heightSmallPreview') : 50;

      $log = "";

      foreach ($xml->Каталог->Товары[0] as $item) {

        $currentPosition++;
        if ($currentPosition <= $lastPositionProduct) {
          continue;
        }

        // Добавляем изображение товара в папку uploads 
        $imageUrl = array();
        $realImgPath = array();
        if (isset($item->Картинка)) {
          foreach ($item->Картинка as $img) {
            $path = 'tempcml' . $sep . $img;
            $realImgPath[] = $path;
            $image = basename($img);
            $imageUrl[] = $image;
          }
        }


        $imageUrl = implode("|", $imageUrl);
        $id = (string) $item->Группы->Ид[0];
        $name = (string) $item->Наименование[0];
        $description = '';
        $desExist = false;
        if (isset($item->Описание)) {
          $description = MG::nl2br((string) $item->Описание[0], true);
          $desExist = true;
        }

        foreach ($item->ЗначенияРеквизитов->ЗначениеРеквизита as $row) {
          if ($row->Наименование == 'Полное наименование') {

            // если в файле нет специального тега с описанием, то берем из полного наименования
            if (!$desExist) {
              $description = (string) $row->Значение ? (string) $row->Значение : $description;
              $description = MG::nl2br($description, true);
            } else {
              // иначе полное наименование подставляем в title товара 
              $name = (string) $row->Значение ? (string) $row->Значение : $name;
            }
          }
        }

        $code = !empty($item->Артикул[0]) ? $item->Артикул[0] : $item->ШтрихКод[0];
		$weight = !empty($item->Вес[0])? $item->Вес[0]:0;
        $newProduct = false;
        $id_1c = (string) $item->Ид[0];
        $ids1c = explode("#", $id_1c);
		
		// Привязываем свойство веса к системному полю "вес".
		$weight = '';
		if (isset($item->ЗначенияСвойств)) {
			foreach ($item->ЗначенияСвойств->ЗначенияСвойства as $prop) {	
				$propId = '' . $prop->Ид[0];
				$propVal = '' . $prop->Значение[0];							
				if($propId==$weightProperty1cId){
				 $weight = $propVal;
				}
			}
		}
		
		
        $id_1c = $ids1c[0];
        $dataProd = array(
          'title' => $name,
          'url' => str_replace('\\', '-', URL::prepareUrl(MG::translitIt($name), true)),
          'code' => $code,
          'description' => $description,
          'image_url' => $imageUrl,
          'cat_id' => $category[$id]['category_id'],
          'activity' => 1,
          '1c_id' => $id_1c,
          'weight' => $weight
        );

	
        if($dataProd['code'] == '') unset($dataProd['code']);
		if($dataProd['weight'] == '') {		    
           unset($dataProd['weight']);
        }

        if ($importOnlyNew) {
          unset($dataProd['description']);
          unset($dataProd['image_url']);
          unset($dataProd['meta_title']);
          unset($dataProd['meta_keywords']);
          unset($dataProd['recommend']);
          unset($dataProd['activity']);
          unset($dataProd['new']);
          unset($dataProd['related']);
          unset($dataProd['inside_cat']);
        }
    
		
        $res = DB::query('SELECT * 
          FROM ' . PREFIX . 'product WHERE `1c_id`=' . DB::quote($id_1c));

        if ($row = DB::fetchAssoc($res)) {
          $dataProd['cat_id'] = $dataProd['cat_id'] ? $dataProd['cat_id'] : $row['cat_id'];

          $updateDescription1c = MG::getSetting('notUpdateDescription1C');

          if ($updateDescription1c && $updateDescription1c != "false") {
            // При импорте данных из 1с, не перезаписывать описание товара из 1с
            unset($dataProd['description']);
          }

          $updateImage1c = MG::getSetting('notUpdateImage1C');

          if ($updateImage1c && $updateImage1c != "false") {
            // При импорте данных из 1с, не перезаписывать картинки товара из 1с
            unset($dataProd['image_url']);
          }

          DB::query('
           UPDATE `' . PREFIX . 'product`
           SET ' . DB::buildPartQuery($dataProd) . '
           WHERE `1c_id`=' . DB::quote($id_1c)
          );
          $productId = $row['id'];
        } else {

          // проверим возможно id продукта совпадает с внешним id 
          // это может случиться если изначально товары магазина, не имеющие внешнего кода,
          // были выгружены из заказов с действующим id вместо внешнего кода
          $res = DB::query('SELECT * 
          FROM ' . PREFIX . 'product WHERE `id` LIKE ' . DB::quote($id_1c));
          if ($row = DB::fetchAssoc($res)) {
            $dataProd['cat_id'] = $dataProd['cat_id'] ? $dataProd['cat_id'] : $row['cat_id'];
             $updateDescription1c = MG::getSetting('notUpdateDescription1C');

          if ($updateDescription1c && $updateDescription1c != "false") {
            // При импорте данных из 1с, не перезаписывать описание товара из 1с
            unset($dataProd['description']);
          }

          $updateImage1c = MG::getSetting('notUpdateImage1C');

          if ($updateImage1c && $updateImage1c != "false") {
            // При импорте данных из 1с, не перезаписывать картинки товара из 1с
            unset($dataProd['image_url']);
          }
            DB::query('
         UPDATE `' . PREFIX . 'product`
         SET ' . DB::buildPartQuery($dataProd) . '
         WHERE `id`=' . DB::quote($id_1c)
            );
            $productId = $row['id'];
          } else {
            // если внешний код не совпал ни с внешним кодом товара ни с его id,
            // значит переданный товар является новым
            $newProd = $model->addProduct($dataProd);
            $newProduct = true;
            $productId = $newProd['id'];
          }
        }
        $updateImage1c = MG::getSetting('notUpdateImage1C');
           
        $arImgPath = explode('/', $realImgPath[0]);
        array_pop($arImgPath);
        $path = implode($sep, $arImgPath);
        $imageUrl = explode('|', $imageUrl);

        $dir = floor($productId / 100) . '00';

        if (!empty($realImgPath) && ($updateImage1c == "false" || $newProduct)) {
          if (!file_exists($path . $sep . 'thumbs')) {
            mkdir($path . $sep . 'thumbs');
          }

          foreach ($realImgPath as $cell => $image) {
            if (!empty($image) && is_file($image)) {
              if (MG::getSetting("waterMark") == "true") {
                $upload->addWatterMark($image);
              }

              $bigImg = $upload->_reSizeImage('70_' . $imageUrl[$cell], $realDocumentRoot . $sep . $image, $widthPreview, $heightPreview, $resizeType, $path . $sep . 'thumbs' . $sep);
              $smallImg = $upload->_reSizeImage('30_' . $imageUrl[$cell], $realDocumentRoot . $sep . $image, $widthSmallPreview, $heightSmallPreview, $resizeType, $path . $sep . 'thumbs' . $sep);

              if (!$bigImg || !$smallImg) {
                $log .= "Изображение " . $imageUrl[$cell] . " не обработано. Слишком большое разрешение.\n";
              }
            }
          }

          $model->movingProductImage($imageUrl, $productId, $path);
          rmdir($path . $sep . 'thumbs');
        }



        // Привязываем свойства.
        if (isset($item->ЗначенияСвойств)) {
          foreach ($item->ЗначенияСвойств->ЗначенияСвойства as $prop) {
            $propVal = '';
            $tempProp = '' . $prop->Значение[0];

            if (!empty($_SESSION['variant_value'][$tempProp])) {
              $propVal = $_SESSION['variant_value'][$tempProp];
            } else {
              if (!empty($tempProp)) {
                $propVal = '' . $prop->Значение[0];
              }
            }

            if (empty($propVal)) {
              $propVal = '';
              $idVal = '' . $prop->ИдЗначения;
              if (!empty($_SESSION['variant_value'][$idVal])) {
                $propVal = $_SESSION['variant_value'][$idVal];
              }
            }
            $this->propertyConnect($id_1c, $prop->Ид, $propVal, $category[$id]['category_id']);
          }
        }

        $execTime = microtime(true) - $this->startTime;
        if ($execTime + 5 >= $this->maxExecTime) {
          header("Content-type: text/xml; charset=utf-8");
          echo "\xEF\xBB\xBF";
          echo "progress\r\n";
          echo "Выгружено товаров: $currentPosition\n";
          echo $log;
          $_SESSION['lastCountProduct1cImport'] = $currentPosition;
          exit();
        }
      }

      if ($this->unlinkFile) {
        unlink($realDocumentRoot . '/tempcml/' . $filename);
      }

      $_SESSION['lastCountProduct1cImport'] = 0;
    } elseif ($xml && ($filename == 'offers.xml' || $filename == 'offers0_1.xml')) {

      $currentPosition = 0;
      $model = new Models_Product;
	  
	  
	  // Ищем новые склады в файле импорта из 1c, если есть новые добавляем, старые оставляем в движке.
	  $storagesCMS = MG::getSetting('storages');
	  $storagesCMS = unserialize(stripslashes($storagesCMS));	  
	  $storages1c = array();	  
	  if(isset($xml->ПакетПредложений[0]->Склады[0])){
	  	$storages1c = $xml->ПакетПредложений[0]->Склады[0];
	    foreach ($storages1c as $store) {
	      $storageId = (string) $store->Ид[0];
		  $storageName = (string) $store->Наименование[0];

		  if(!$this->storageExist($storagesCMS,$storageId)){
		 	$storagesCMS[]=array(
			'id' => $storageId,
			'name' => $storageName,
			'adress' => '',
            'desc'=>''
			);
		  };
	    }
	  }
	 
	  
	  MG::setOption(array('option' => 'storages', 'value' => addslashes(serialize($storagesCMS))));
	  
	  
      $currencyRate = MG::getSetting('currencyRate');
      $currencyShort = MG::getSetting('currencyShort');

      foreach ($xml->ПакетПредложений[0]->Предложения[0] as $item) {

        $currentPosition++;
        if ($currentPosition <= $lastPositionOffer) {
          continue;
        }

        $id = (string) $item->Ид[0];
        $ids1c = explode('#', (string) $item->Ид[0]);
        
        $price = (string) $item->Цены->Цена->ЦенаЗаЕдиницу[0];
		$price_course = 0;
		
		/* Вернуть если будут проблемы
        if(empty($price)|| $price == ""){
           $dbRes1 = DB::query('
            SELECT `price`,`price_course` FROM `' . PREFIX . 'product`          
            WHERE 1c_id = ' . DB::quote($ids1c[0]) . '
          ');

          if ($row = DB::fetchArray($dbRes1)) {
            $price = $row['price']?$row['price']:0;
            $price_course = $row['price_course']&& $row['price_course']!=0 ?$row['price_course']:$price;
          }

        }*/

        $iso = $this->getIsoByCode((string) $item->Цены->Цена->Валюта[0]);
        if ($iso == 'NULL') {
          $iso = substr(MG::translitIt((string) $item->Цены->Цена->Валюта[0]), 0, 3);
        }

        $count = (string) $item->Количество[0];

        // если валюта товара не задана ранее в магазине, то добавим ее. (Курс нужно будет установить вручную в настройках)

        $currency = array();

        if (empty($currencyRate[$iso])) {
          $currency['iso'] = htmlspecialchars($iso);
          $tmp = trim($currency['iso']);
          if (!empty($tmp)) {
            $currency['short'] = $currency['iso'];
            $currency['rate'] = 1;
            $currencyRate[$currency['iso']] = $currency['rate'];
            $currencyShort[$currency['iso']] = $currency['short'];

            MG::setOption(array('option' => 'currencyRate', 'value' => addslashes(serialize($currencyRate))));
            MG::setOption(array('option' => 'currencyShort', 'value' => addslashes(serialize($currencyShort))));
          }
        }


        $partProd = array(
          'price' => $price,
          'count' => $count < 0 ? 0 : $count,
          // 'price_course' => $price*$currencyRate[$currency['iso']], 
          'currency_iso' => $iso
        );


        // проверяем, вдруг это предложение является вариантом для товара       
        $variantId = '';
        // если id варианта не найден
        if (empty($ids1c[1])) {
          // просто товар, не вариант  
          $ptemp = isset($price_course)&&$price_course!=0?$price_course:($price * $currencyRate[$iso]);
          DB::query('
             UPDATE `' . PREFIX . 'product`
             SET ' . DB::buildPartQuery($partProd) . ', `price_course` = ROUND(' . DB::quote($ptemp, TRUE) . ',2) 
             WHERE 1c_id = ' . DB::quote($ids1c[0]) . '
          ');		  
			
		   	foreach ($item->Склад as $store){	
		  	  $storageId = (string)$store->attributes()->ИдСклада;
			  $storageCount = (string)$store->attributes()->КоличествоНаСкладе;		
			}	
	        
		   // если есть склад в импортируемом файле, то обновляем информацию о количестве на этом складе
		   if(isset($item->Склад)){
		   
			  $this->updateStorage($item->Склад,$ids1c[0]);
			 
		  }
		  
        } else {
          // если товарное предложение является вариантом для продукта
          $productId = '';
          $variantId = $ids1c[1];
        
          $variant = array();

          $dbRes = DB::query('
            SELECT `id`, `cat_id` FROM `' . PREFIX . 'product`          
            WHERE 1c_id = ' . DB::quote($ids1c[0]) . '
          ');

          if ($row = DB::fetchArray($dbRes)) {
            $productId = $row['id'];
            $name = array();
            foreach ($item->ХарактеристикиТовара->ХарактеристикаТовара as $prop) {
              $name[] = $prop->Значение;
            }

            if (empty($name)) {
              $name = $item->Наименование;
            } else {
              $name = implode(', ', $name);
            }

			//$weight = !empty($item->Вес[0])? $item->Вес[0]:0;
			
			// Привязываем свойство веса к системному полю "вес".
			$weight = '';
			if (isset($item->ЗначенияСвойств)) {
				foreach ($item->ЗначенияСвойств->ЗначенияСвойства as $prop) {	
					$propId = '' . $prop->Ид[0];
					$propVal = '' . $prop->Значение[0];							
					if($propId==$weightProperty1cId){
					 $weight = $propVal;
					}
				}
			}			
            $sizeId = 0;
            if (MG::getSetting('variantToWeight1c') == 'true') {
              $sizeId = $this->getSizeId($row['cat_id'], $name, $productId);
            }
			
            $titleVariant = $name;
            $variant = array(
              'title_variant' => $titleVariant,
              'code' => $item->Артикул[0],
              'price' => $price,  
              // 'image' => '',
              'count' => $count < 0 ? 0 : $count,
              '1c_id' => $variantId,
              'weight' => $weight,
              'activity' => 1,
              'currency_iso' => $iso
            );

            if ($sizeId > 0) {
              $variant['size'] = $sizeId;
            }

            if($variant['code'] == '') {
              unset($variant['code']);
            }
			if($variant['weight'] == '') {
              unset($variant['weight']);
            }

            // ******
            //  ищем варианты для этого товара
            $dbRes = DB::query('
              SELECT id FROM `' . PREFIX . 'product_variant`           
              WHERE product_id = ' . DB::quote($productId) . '
            ');
            // если еще ни одного небыло, то создаем и обновляем в таблице product значения по первому варианту
            if (!$row = DB::fetchArray($dbRes)) {
              DB::query('
               UPDATE `' . PREFIX . 'product`
               SET ' . DB::buildPartQuery($partProd) . ' , `price_course` = ROUND(' . DB::quote($price * $currencyRate[$iso], TRUE) . ',2) 
               WHERE 1c_id = ' . DB::quote($ids1c[0]) . '
              ');
            }

            // если для данного товара загружается первый вариант из списка, обновляем mg_product
            if($_SESSION['temp_var_pid_1c'] != $ids1c[0]){
              DB::query('
               UPDATE `' . PREFIX . 'product`
               SET ' . DB::buildPartQuery($partProd) . ' , `price_course` = ROUND(' . DB::quote($price * $currencyRate[$iso], TRUE) . ',2) 
               WHERE 1c_id = ' . DB::quote($ids1c[0]) . '
              ');
            }
            // обновляем 1c_id в памяти, чтобы в следующий раз предыдущее условие не сработало, т.к. это уже будет второй вариант
              $_SESSION['temp_var_pid_1c'] = $ids1c[0];

            // ******
            // проверяем, импортирован ли ранее этот вариант
            $dbRes = DB::query('
              SELECT id FROM `' . PREFIX . 'product_variant`           
              WHERE 1c_id = ' . DB::quote($ids1c[1]) . ' 
                AND product_id = ' . DB::quote($productId) . '
            ');

            // если еще нет, то получаем массив всех имеющихся вариантов по этому продукту, 
            // добавляем к нему новый вариант и обновляем массив вариантов стандартными средствами
            if (!$row = DB::fetchArray($dbRes)) {
              $arrVariants = array();
              $arVarCodes = array();
              $res = DB::query('
                  SELECT  pv.*
                  FROM `' . PREFIX . 'product_variant` pv    
                  WHERE pv.product_id = ' . DB::quote($productId) . '
                  ORDER BY sort
                ');

              if (!empty($res)) {
                while ($var = DB::fetchAssoc($res)) {
                  $arrVariants[$var['id']] = $var;
                  $arVarCodes[] = $var['code'];
                }
              }

              $variant['sort'] = count($arrVariants);

              if (count($arrVariants) > 0 &&
                      in_array($variant['code'], $arVarCodes)) {
                $variant['code'] .= '-' . count($arrVariants);
              }

              $variant['price_course'] = round($price * $currencyRate[$iso], 2);
              $arrVariants[] = $variant;

              $model->saveVariants($arrVariants, $productId);
            } else {
              // обновить вариант
                DB::query('
               UPDATE `' . PREFIX . 'product_variant`
               SET ' . DB::buildPartQuery($variant) . ',`price_course` = ROUND(' . DB::quote($price * $currencyRate[$iso], TRUE) . ',2)
               WHERE 1c_id = ' . DB::quote($ids1c[1]) . ' 
                 AND product_id = ' . DB::quoteInt($productId) . '
              ');
            }
			
		   // если есть склад в импортируемом файле, то обновляем информацию о количестве варианта товара на этом складе
		   if(isset($item->Склад)){		   
		     $dbRes = DB::query('
				SELECT id FROM `' . PREFIX . 'product_variant`          
				WHERE 1c_id = ' . DB::quote($ids1c[1]) . ' AND  product_id = ' . DB::quoteInt($productId) . '
			  ');
			  if ($rowVar = DB::fetchArray($dbRes)) {
			    $this->updateStorage($item->Склад,$ids1c[0],$rowVar['id']);
			  }			 
		  }
          }		  		   
		  
        }
        $execTime = microtime(true) - $this->startTime;

        if ($execTime + 1 >= $this->maxExecTime) {
          header("Content-type: text/xml; charset=utf-8");
          echo "\xEF\xBB\xBF";
          echo "progress\r\n";
          echo "Выгружено предложений: $currentPosition";
          echo $log;
          $_SESSION['lastCountOffer1cImport'] = $currentPosition;
          exit();
        }
      }

      if ($this->unlinkFile) {
        unlink($realDocumentRoot . '/tempcml/' . $filename);
        $upload->removeDirectory($realDocumentRoot . '/ ');
      }

      $_SESSION['lastCountOffer1cImport'] = 0;
      $_SESSION['sizePropId1c'] = 0;
      $_SESSION['sizePropCatsBound1c'] = array();
      $_SESSION['sizePropVarsBound1c'] = array();
      Storage::clear();
    } else {
      echo "Ошибка загрузки XML\n";
      foreach (libxml_get_errors() as $error) {
        echo "\t", $error->message;
        exit;
      }
    }

    return $log;
  }
  /**
   * сохранение и получение данных для сохранения размера
   * @param int $catId id категории 
   * @param string $value значение характеристики
   */
  function getSizeId($catId, $value, $productId) {
    if (!$_SESSION['sizePropId1c']) {
      $res = DB::query("SELECT `id` FROM `".PREFIX."property` WHERE `1c_id` = 'size1c'");
      if ($row = DB::fetchArray($res)) {
        $_SESSION['sizePropId1c'] = $row['id'];
        $res = DB::query("SELECT `category_id` FROM `".PREFIX."category_user_property` WHERE `property_id` = ".DB::quoteInt($_SESSION['sizePropId1c']));
        while ($row = DB::fetchArray($res)) {
          $_SESSION['sizePropCatsBound1c'][] = $row['category_id'];
        }
        $res = DB::query("SELECT `id`, `name` FROM `".PREFIX."property_data` WHERE `prop_id` = ".DB::quoteInt($_SESSION['sizePropId1c']));
        while ($row = DB::fetchArray($res)) {
          $_SESSION['sizePropVarsBound1c'][$row['name']] = $row['id'];
        }
      }
      else{
        DB::query("INSERT INTO `".PREFIX."property`
          (`name`,`type`,`activity`,`filter`,`type_filter`,`1c_id`,`plugin`,`unit`) VALUES
          ('Размер[prop attr=1C]', 'size', '1', '1', 'checkbox', 'size1c', '', '')");
        $_SESSION['sizePropId1c'] = DB::insertId();
        $_SESSION['sizePropCatsBound1c'] = array();
        $_SESSION['sizePropVarsBound1c'] = array();
      }
    }

    if (!in_array($catId, $_SESSION['sizePropCatsBound1c'])) {
      DB::query("INSERT INTO `".PREFIX."category_user_property` (`category_id`,`property_id`) VALUES (".DB::quoteInt($catId).", ".DB::quoteInt($_SESSION['sizePropId1c']).")");
      $_SESSION['sizePropCatsBound1c'][] = $catId;
    }

    if (!array_key_exists($value, $_SESSION['sizePropVarsBound1c']) ) {
      DB::query("INSERT INTO `".PREFIX."property_data` (`prop_id`,`name`) VALUES (".DB::quoteInt($_SESSION['sizePropId1c']).", ".DB::quote($value).")");
      $lastId = DB::insertId();
      $_SESSION['sizePropVarsBound1c'][$value] = $lastId;
    }

    $res = DB::query("SELECT `id` FROM `".PREFIX."product_user_property_data` WHERE `prop_id` = ".DB::quoteInt($_SESSION['sizePropId1c'])." AND `prop_data_id` = ".DB::quote($_SESSION['sizePropVarsBound1c'][$value])." AND `product_id` = ".DB::quoteInt($productId));
    if (!DB::fetchArray($res)) {
      DB::query("INSERT INTO `".PREFIX."product_user_property_data` (`prop_id`,`prop_data_id`,`product_id`, `active`) VALUES (".DB::quoteInt($_SESSION['sizePropId1c']).", ".DB::quote($_SESSION['sizePropVarsBound1c'][$value]).", ".DB::quoteInt($productId).", '1')");
    }

    return $_SESSION['sizePropVarsBound1c'][$value];
  }

  /**
   * Обход дерева групп полученных из 1С.
   * @param object $xml дерево с данными.
   * @param array $category категория. 
   * @param int $parent родительская категория.
   * @return array
   */
  function groupsGreate($xml, $category, $parent) {

    if (!$parent) {
      $parent = array('category_id' => 0, 'name' => '');
    }

    if (!isset($xml->Группы)) {
      return $category;
    }

    foreach ($xml->Группы->Группа as $category_data) {


      $name = (string) $category_data->Наименование;
      // костыль для МойСклад (Ни при каких условиях не создавать категорию 'Товары интернет-магазинов')
      if ($name == 'Товары интернет-магазинов') {
        continue;
      }
      $cnt = (string) $category_data->Ид;
      $category[$cnt]['1c_id'] = $cnt;
      $category[$cnt]['name'] = $name;
      $category[$cnt]['parent_id'] = $parent['category_id'];
      $category[$cnt]['parentname'] = $parent['name'];
      $category[$cnt]['description'] = "";
      $category[$cnt]['category_id'] = $this->newCategory($category[$cnt]);
      $category = $this->groupsGreate($category_data, $category, $category[$cnt]);
    }

    return $category;
  }

  /**
   * Создание новой категории.
   * @param array $category категория.
   * @return int
   */
  function newCategory($category) {

    $url = URL::prepareUrl(MG::translitIt($category['name'], 1));
    $parent_url = MG::get('category')->getParentUrl($category['parent_id']);
    $parent = URL::prepareUrl(MG::translitIt($category['parentname'], 1));

    $data = array(
      'title' => $category['name'],
      'url' => str_replace(array('/', '\\'), '-', $url),
      'parent' => $category['parent_id'],
      //'html_content' => $category['description'],
      //'meta_title' => $category['name'],
      //'meta_keywords' => $category['name'],
      //'meta_desc' => MG::textMore($category['description'], 157),
      'invisible' => 0,
      'parent_url' => $parent_url,
      '1c_id' => $category['1c_id'],
    );

    $res = DB::query('SELECT *
      FROM `' . PREFIX . 'category`
      WHERE `1c_id`=' . DB::quote($category['1c_id']));
    if ($row = DB::fetchAssoc($res)) {

      DB::query('
        UPDATE `' . PREFIX . 'category`
        SET ' . DB::buildPartQuery($data) . '
        WHERE `1c_id`=' . DB::quote($category['1c_id'])
      );

      return $row['id'];
    } else {
      $data = MG::get('category')->addCategory($data);
      return $data['id'];
    }

    return 0;
  }

  /**
   * Создание свойств для товаров.
   * @param object $xml дерево с данными
   */
  function propertyСreate($xml) {


    foreach ($xml->Свойство as $property_data) {
      foreach ($property_data->ТипыЗначений as $typesValue) {
        foreach ($typesValue->ТипЗначений as $typeValue) {
          foreach ($typeValue->ВариантыЗначений as $variantsVal) {
            foreach ($variantsVal->ВариантЗначения as $variantVal) {
              $tId = '' . $variantVal->Ид;
              $_SESSION['variant_value'][$tId] = '' . $variantVal->Значение;
            }
          }
        }
      }

      foreach ($property_data->ВариантыЗначений as $variantsVal) {
        foreach ($variantsVal->Справочник as $variantVal) {
          $tId = '' . $variantVal->ИдЗначения;
          $_SESSION['variant_value'][$tId] = '' . $variantVal->Значение;
        }
      }

      $this->propertyСreateProcess($property_data);
    }


    foreach ($xml->СвойствоНоменклатуры as $property_data) {
      $this->propertyСreateProcess($property_data);
    }
  }

  /**
   * Процесс создания характеристик
   * @param object $property_data - объект с характеристиками
   */
  function propertyСreateProcess($property_data) {

    $id = (string) $property_data->Ид;
    $name = (string) $property_data->Наименование;

    $property['1c_id'] = $id;
    $property['name'] = $name;

    $res = DB::query('SELECT * 
        FROM `' . PREFIX . 'property` 
        WHERE `1c_id`=' . DB::quote($property['1c_id']));
    if ($row = DB::fetchAssoc($res)) {
      DB::query('
          UPDATE `' . PREFIX . 'property`
          SET `name` =' . DB::quote($property['name']) . '
          WHERE `1c_id`=' . DB::quote($property['1c_id'])
      );
    } else {
      /**/
      DB::query("
          INSERT INTO `" . PREFIX . "property` 
            (`name`,`type`,`all_category`,`activity`,`filter`,`type_filter`,`1c_id`)        
          VALUES (" . DB::quote($property['name']) . ",'string','1','1','1','checkbox'," . DB::quote($property['1c_id']) . ")"
      );
      if ($lastId = DB::insertId()) {
        DB::query("
         UPDATE `" . PREFIX . "property`
         SET `sort`=`id` WHERE `id` = " . DB::quote($lastId)
        );
      }
    }
  }

  /**
   * Привязка свойств к товару, категории и установка значений
   * @param int $productId1c - id товара из 1с в бзе сайта.
   * @param int $propId1c - id обрабатываемого товара из 1с.
   * @param string $propValue - значение свойства.
   * @param int $categoryId - id категории.
   * @return bool
   */
  function propertyConnect($productId1c, $propId1c, $propValue, $categoryId) {
    // Получаем реальные id для товара и свойства из базы данных.
    $res = DB::query('SELECT id FROM `' . PREFIX . 'product` WHERE `1c_id`=' . DB::quote($productId1c));
    if ($row = DB::fetchAssoc($res)) {
      $productId = $row['id'];
    } else {
      return false;
    }

    $res = DB::query('SELECT id FROM `' . PREFIX . 'property` WHERE `1c_id`=' . DB::quote($propId1c));
    if ($row = DB::fetchAssoc($res)) {
      $propertyId = $row['id'];
    } else {
      return false;
    }

    // Проверим, если такой привязки еще нет между категориями и свойствами, то создадим ее для категории.
    $res = DB::query('
      SELECT category_id      
      FROM `' . PREFIX . 'category_user_property` 
      WHERE `property_id`=' . DB::quote($propertyId) . '
         and `category_id` = ' . DB::quote($categoryId));

    if (!DB::numRows($res)) {
      DB::query("
        INSERT INTO `" . PREFIX . "category_user_property` (`category_id`, `property_id`)
        VALUES (" . DB::quote($categoryId) . ", " . DB::quote($propertyId) . ")");
    }

    // Проверим, если такой привязки еще нет между продуктом и свойством ,
    //  то создадим ее для продукта.
    $res = DB::query('
     SELECT product_id
     FROM `' . PREFIX . 'product_user_property_data`
     WHERE `product_id`=' . DB::quote($productId) . '
       and `prop_id` = ' . DB::quote($propertyId));
    if (!DB::numRows($res)) {
      DB::query("
        INSERT INTO `" . PREFIX . "product_user_property_data` 
          (`product_id`, `prop_id`, `name`, `margin`, `type_view`)
        VALUES (" . DB::quote($productId) . ", " . DB::quote($propertyId) . ", " . DB::quote($propValue) . ", '', 'select')");
    } else {
      // если привязка есть, то обновим данные
      DB::query('
        UPDATE `' . PREFIX . 'product_user_property_data`
        SET `name` =' . DB::quote($propValue) . '
        WHERE `product_id`=' . DB::quote($productId) . ' and `prop_id` = ' . DB::quote($propertyId)
      );
    }

    return true;
  }

  /**
   * Парсинг XML.
   * @param string $filename исходный файл.
   * @return object
   */
  public function getImportXml($filename) {
    $xml = simplexml_load_file('tempcml/'.$filename);
    return $xml;
  }

  /**
   * Возвращает ISO коды валют по ID валюты.
   * @param string $id ID валюты.
   * @return string
   */
  public function getIsoByCode($id) {
    $arrayRub = array('RUB','руб','RUR');
    if (in_array($id, $arrayRub)) {
      return 'RUR';
    }
    $arr = array(
      '643' => 'RUB',
      '980' => 'UAH',
      '974' => 'BYR',
      '398' => 'KZT',
      '860' => 'UZS',
      '972' => 'TJS',
      '795' => 'TMM',
      '417' => 'KGS',
      '498' => 'MDL',
      '051' => 'AMD',
      '031' => 'AZM',
      '981' => 'GEL',
      '428' => 'LVL',
      '233' => 'EEK',
      '440' => 'LTL',
      '840' => 'USD',
      '826' => 'GBP',
      '756' => 'CHF',
      '752' => 'SEK',
      '578' => 'NOK',
      '208' => 'DKK',
      '124' => 'CAD',
      '368' => 'IQD',
      '392' => 'JPY',
      '036' => 'AUD',
      '978' => 'EUR',
      '414' => 'KWD',
      '586' => 'PKR',
      '422' => 'LBP',
      '352' => 'ISK',
      '702' => 'SGD',
      '400' => 'JOD',
      '736' => 'SDD',
      '949' => 'TRY',
      '682' => 'SAR',
      '032' => 'ARS',
      '818' => 'EGP',
      '986' => 'BRL',
      '364' => 'IRR',
      '356' => 'INR',
      '524' => 'NPR',
      '004' => 'AFA',
      '360' => 'IDR',
      '710' => 'ZAR',
      '196' => 'CYP',
      '901' => 'TWD',
      '152' => 'CLP',
      '012' => 'DZD',
      '376' => 'ILS',
      '348' => 'HUF',
      '203' => 'CZK',
      '642' => 'ROL',
      '496' => 'MNT',
      '975' => 'BGN',
      '704' => 'VND',
      '985' => 'PLN',
      '192' => 'CUP',
      '703' => 'SKK',
      '960' => 'XDR',
      '008' => 'ALL',
      '784' => 'AED',
      '404' => 'KES',
      '156' => 'CNY',
      '170' => 'COP',
      '418' => 'LAK',
      '434' => 'LYD',
      '504' => 'MAD',
      '484' => 'MXN',
      '566' => 'NGN',
      '554' => 'NZD',
      '604' => 'PEN',
      '760' => 'SYP',
      '705' => 'SIT',
      '764' => 'THB',
      '788' => 'TND',
      '858' => 'UYU',
      '608' => 'PHP',
      '144' => 'LKR',
      '230' => 'ETB',
      '891' => 'YUM',
      '410' => 'KRW',
    );

    return $arr[$id] ? $arr[$id] : 'NULL';
  }
  /**
   * Проверка на существование ID склада в базе движка
   * @param array $storagesCMS массив складов.
   * @param int $id ID искомого склада.
   * @return bool
   */
  public function storageExist($storagesCMS,$id) {
		$result = false;
		
		foreach ($storagesCMS as $storeCms) {
		  if($storeCms['id'] == $id){
		    // Если нашли, то записываем флаг nextStorage для перехода к следующему объекту.
		    $result=true;
		    break;
		  }
		}		

		return $result;		
  }
	
	/**
   * Проверка на существование ID склада в базе движка
   * @param array $store1c массив складов.
   * @param int $id1c 1c ID склада товара.
   * @param int $variantId ID варианта товара.
   */
	public function updateStorage($store1c,$id1c,$variantId=''){
		foreach ($store1c as $store){
		  $storageId = (string)$store->attributes()->ИдСклада;
		  $storageCount = (string)$store->attributes()->КоличествоНаСкладе;		  
		
		  // получаем id предложения в CMS для обновления остатков на складе
		  $dbRes = DB::query('
			SELECT id FROM `' . PREFIX . 'product`
			WHERE 1c_id = '.DB::quote($id1c).'
		  ');
		 
		  if ($row = DB::fetchAssoc($dbRes)) {
		  
			 if($variantId==''){
			   DB::query('DELETE FROM '.PREFIX.'product_on_storage WHERE 
			      product_id = '.DB::quoteInt($row['id']).' AND storage = '.DB::quote($storageId));
			 }else{
			   DB::query('DELETE FROM '.PREFIX.'product_on_storage WHERE 
			      product_id = '.DB::quoteInt($row['id']).' AND storage = '.DB::quote($storageId).' AND variant_id = '.DB::quoteInt($variantId));
			 }
			   
			 DB::query('INSERT INTO '.PREFIX.'product_on_storage (product_id, variant_id, storage, count) VALUES 
			  ('.DB::quoteInt($row['id']).', '.DB::quoteInt($variantId).', 
			  '.DB::quote($storageId).', '.DB::quoteInt($storageCount).')');
		  }
		}			  
	}
}
