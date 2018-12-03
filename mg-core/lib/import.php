<?php
/**
 * Класс Import - предназначен для импорта товаров в каталог магазина. Поддерживает две структуры файлов  в формате CSV. Упрощенная - с артикулами и ценами, а также полная со всей информацией о каждом товаре.
 *
 * @author Авдеев Марк <mark-avdeev@mail.ru>
 * @package moguta.cms
 * @subpackage Libraries
 */
class Import {
  private $typeCatalog = 'MogutaCMS';
  private $currentRowId = null;
  private $validError = null; 
  public static $iteration = 1; 
  public static $downloadLink = false;
  public static $complianceArray = array();
  public static $fullProduct = array();
  private static $notUpdate = array();
  public static $maskArray = array(
    'MogutaCMS' => array(
      0 => 'ID товара',              // 0
      1 => 'Артикул',                // 1
      2 => 'Категория',              // 2
      3 => 'URL категории',          // 3
      4 => 'Товар',                  // 4
      5 => 'Вариант',                // 5
      6 => 'Краткое описание',       // 6
      7 => 'Описание',               // 7
      8 => 'Цена',                   // 8
      9 => 'Старая цена',            // 9
      10 => 'URL товара',             // 10
      11 => 'Изображение',            // 11
      12 => 'Количество',             // 12
      13 => 'Активность',             // 13
      14 => 'Заголовок [SEO]',        // 14
      15 => 'Ключевые слова [SEO]',   // 15
      16 => 'Описание [SEO]',         // 16
      17 => 'Рекомендуемый',          // 17
      18 => 'Новый',                  // 18
      19 => 'Сортировка',             // 19
      20 => 'Вес',                    // 20
      21 => 'Связанные артикулы',     // 21
      22 => 'Смежные категории',      // 22
      23 => 'Ссылка на товар',        // 23
      24 => 'Валюта',                 // 24
      25 => 'Единицы измерения',      // 25

      26 => 'Оптовые цены',           // 26
      27 => 'Склады',                 // 27

      28 => 'Свойства начинаются с',  // 28
      29 => 'Сложные характеристики', // 29
    ),
    'Category' => array(
      'Название категории',
      'URL категории',
      'id родительской категории',
      'URL родительской категории',
      'Описание категории',
      'Изображение',
      'Заголовок [SEO]',
      'Ключевые слова [SEO]',
      'Описание [SEO]',
      'SEO Описание',
      'Наценка',
      'Не выводить в меню',
      'Активность',
      'Не выгружать в YML',
      'Сортировка',
      'Внешний идентификатор',
      'ID категории',
      'title изображенния',
      'alt изображения',
    ),
  );
  public static $fields = array(
    'MogutaCMS' => array(
      0 => 'id',               // 0
      1 => 'code',             // 1
      2 => 'cat_id',           // 2
      3 => 'cat_url',          // 3
      4 => 'title',            // 4
      5 => 'variant',          // 5
      6 => 'short_description',// 6
      7 => 'description',      // 7
      8 => 'price',            // 8
      9 => 'old_price',        // 9
      10 => 'url',              // 10
      11 => 'image_url',        // 11
      12 => 'count',            // 12
      13 => 'activity',         // 13
      14 => 'meta_title',       // 14
      15 => 'meta_keywords',    // 15
      16 => 'meta_desc',        // 16
      17 => 'recommend',        // 17
      18 => 'new',              // 18
      19 => 'sort',             // 19
      20 => 'weight',           // 20
      21 => 'related',          // 21
      22 => 'inside_cat',       // 22
      23 => 'link_electro',     // 23
      24 => 'currency_iso',     // 24
      25 => 'category_unit',    // 25

      26 => 'wholesales',       // 26
      27 => 'storages',         // 27

      28 => 'property',         // 28
      29 => 'hard_prop',        // 29
    ),
  );
  public static $fieldsInfo = array(
    'MogutaCMS' => array(
      0 => 'Необязательное поле. Идентификатор товара в системе',
      1 => 'Необязательное поле.',
      2 => 'Пример: Аксессуары/Головные уборы
Обязательное поле при загрузке новых товаров, при обновлении не обязательно. Нужно для создания к товару категории.',
      3 => 'Пример: aksessuary/golovnye-ubory
Необязательное поле. Содержит URL категории с учетом вложенностей',
      4 => 'Пример: Бейсболка мужская Demix
Название товара. Обязательное поле',
      5 => 'Пример: Черный[:param:][src=241.jpg]
Необязательное поле. Нужно для создания вариантов товара. В конструкции [src=241.jpg] позволяет задать имя картинки, которая будет прикреплена к данному варианту товара',
      6 => 'Необязательное поле. Содержит карткое описание товара, которое выводиться в мини карточке товара (весь текст должен быть в одну строку!)',
      7 => 'Необязательное поле. Содержит полное описание товара (весь текст должен быть в одну строку!)',
      8 => 'Обязательное поле. Содерит цену товара или его варианта, если указан вариант',
      9 => 'Необязательное поле. Позволяет установить старую цену для товаров, обычно используеться для установки скидки у товара',
      10 => 'Необязательное поле.',
      11 => 'Пример простой: no-img.jpg|no-img2.jpg
Пример с заполнением описаний к картинке: no-img.jpg|no-img2.jpg[:param:][alt=prodtmpimg/321.jpg][title=Картинка]
Необязательное поле. Позволяет указать у товара изображения',
      12 => 'Необязательное поле. Остаток товара в магазине',
      13 => 'Необязательное поле. 1 - товар будет отображаться в каталоге. 0 - не будет отображаться в каталоге',
      14 => 'Пример: Бейсболка мужская Demix
Необязательное поле. ',
      15 => 'Пример: Бейсболка мужская Demix купить, CN32, Бейсболка, мужская, Demix
Необязательное поле. ',
      16 => 'Пример: ГИБКОСТЬ Специальные канавкиFlex Groovesпозволяют подошве легко сгибаться.
Необязательное поле. ',
      17 => 'Необязательное поле. 1/0 - Выводить или не выводить товар в блоке рекомендуемых товаров',
      18 => 'Необязательное поле. 1/0 - Выводить или не выводить товар в блоке новых товаров',
      19 => 'Необязательное поле. Устанавливает один из возможных порядков вывода товаров',
      20 => 'Необязательное поле. Позволяет указать вес товара',
      21 => 'Пример: CN17,CN18
Необязательное поле. Позволяет привязать к товару другие товары в блоке рекомендаций ниже',
      22 => 'Пример: 13,16,67
Необязательное поле. Позволяет включить товар к выводу в других категориях',
      23 => 'Необязательное поле. Ссылка на скачивание цифрового товара',
      24 => 'Пример: RUR
Необязательное поле. Позволяет установить товару валюту отличную от других товаров',
      25 => 'Пример: шт.
Необязательное поле. Устанавливает товару единицу измерения',

      26 => 'Пример записи заголовка: Количество от 10 [оптовая цена]
[оптовая цена] - являеться ключом для чтения, числовое значение являеться обязательным параметром в названии, устанавливает от какого количества товара добавленного в корзину, будет применяться оптовая цена
Пример записи значения: 999
Просто числовое значение, которое будет установлено вместо стандартной цены при соблюдении условия описанного выше
Не являеться обязательным полем. Можно записывать несколько подобных столбцов друг за другом',
      27 => 'Пример записи заголовка: Склад №1  [склад=Sklad-№1]
Склад №1 - являеться видимом отображением названия склада. [склад=Sklad-№1] после знака = указывает внутренний идентификатор склада, запрещена кириллица, пробелы и спец. символы
Пример записи значения: 9
Указывает остаток товара на текущем складе
Не являеться обязательным параметром. Можно записывать несколько подобных столбцов друг за другом',

      28 => 'Многостолбцовая структура. В заголовках вы указываете название свойства товара, а в самом поле указываете значение свойства
Если свойство являеться цветом или размером то нужно после указания названия свойства при писать еще [color] или [size] соответсвенно
Если поле являеться текстовым, то аналогичным способом нужно приписать [textarea]
Пример записи заголовка:
1) Производитель
2) Цвет [color]
3) Размер [size]
4) Описание производства [textarea]
В поле для записания свойства нужно просто вписать параметр, в случае если это цвет, можно сразу указать и сам цвет товара в виде хэша [#4caf50]
Пример записи свойства:
1) Просто значение
2) Белый [#ffffff]',
      29 => 'Необязательное поле.',
    ),
  );

  public static $requiredFields = array(
      'MogutaCMS' => array(
        4, 8
      ),
    );

  public function __construct($typeCatalog = "MogutaCMS") {
    $this->typeCatalog = $typeCatalog;  
    self::$notUpdate = explode(',', MG::getSetting('csvImport-'.$typeCatalog.'-notUpdateCol'));
  }
  
  /**
   * Устанавливает тип импорта.
   * @param string тип
   */
  public function setTypeCatalog($type) {
    $this->typeCatalog = $type;
  }

  /**
   * Устанавливает поля для игнорирования в импорте.
   * @param array поля для игнора
   */
  public function setNotUpdateFields($notUpdate) {
    self::$notUpdate = $notUpdate;
  }
  
  /**
   * Возвращает ошибку при импорте.
   * @return string ошибка
   */
  public function getValidError() {
    return $this->validError;
  }
  
  /**
   * Получает заголовки столбцов из CSV файла.
   * @return array
   */
  public static function getTitleList() {
    $titleList = array();
    if($_SESSION['importType'] != 'excel') {
      $file = new SplFileObject("uploads/importCatalog.csv");
      if(!$file->eof()) {
        $data = $file->fgetcsv(";");
        
        foreach($data as $cell=>$value) {
          $value = str_replace(' ',' ',iconv("WINDOWS-1251", "UTF-8", $value));
          $titleList[$cell] = $value;
        } 
      }
    } else {
      include_once CORE_DIR.'script/excel/PHPExcel/IOFactory.php';
      include_once CORE_DIR.'script/excel/chunkReadFilter.php';  

      $file = "uploads/importCatalog.xlsx";

      $chunkFilter = new chunkReadFilter();    
      $chunkFilter->setRows(0,1);
      $objReader = PHPExcel_IOFactory::createReaderForFile($file);    
      $objReader->setReadFilter($chunkFilter);
      $objReader->setReadDataOnly(true);    
      $objPHPExcel = $objReader->load($file);
      $sheet = $objPHPExcel->getActiveSheet();
      $colNumber = PHPExcel_Cell::columnIndexFromString($sheet->getHighestColumn());

      for($i=0; $i<$colNumber; $i++) {
        $titleList[$i] = $sheet->getCellByColumnAndRow($i, 1)->getValue();
      }

      unset($objReader); 
      unset($objPHPExcel); 
    }
    return $titleList;
  }
  
  /**
   * Запускает загрузку товаров с заданной строки.
   * @param int $rowId - id строки для старта
   * @return array
   */
  public function startCategoryUpload($rowId = false) {
    if(!$rowId) {
      $rowId = 1;
    }
    
    if(empty($_SESSION['stopProcessImportCsv'])) {
      $data = $this->importFromCsv($rowId, "default");

      if($data === false) {
        $msg = 'Ошибка в CSV файле! '.$this->validError.' line:'.((int)$this->currentRowId+1);
        
        return array(
          'status' => 'error',
          'msg' => $msg
        );
      }
      
      return array(
        'percent' => $data['percent'],
        'status' => 'run',
        'rowId' => $data['rowId']       
      );
    } else {
      unset($_SESSION['stopProcessImportCsv']);
      
      return array(
        'percent' => 0,
        'status' => 'canseled',
        'rowId' => $rowId
      );
    }
  }

  /**
   * Запускает загрузку товаров с заданной строки.
   * @param int $rowId - id строки для старта
   * @return array
   */
  public function startUpload($rowId = false, $schemeType = 'default', $downloadLink = false, $iteration = 1) {    
    if(!$rowId) {
      $rowId = 1;
    }

    self::$iteration = $iteration;

    self::$downloadLink = ($downloadLink == "false")?false:true;

    if(empty($_SESSION['stopProcessImportCsv'])) {
      $data = $this->importFromCsv($rowId, $schemeType);

      if($data===false) {
        $msg = 'Ошибка в CSV файле! '.$this->validError.' line:'.((int)$this->currentRowId+1).'<br />Попробуйте использовать свою схему импорта данных.';
        return
        array(
          'status' => 'error',
          'msg' => $msg
        );
      }
      
      return
        array(
          'percent' => $data['percent'],
          'status' => 'run',
          'startGenerationImage' => ($data['percent']>=100 && $this->autoStartImageGen())?true:false,
          'downloadLink' => self::$downloadLink,
          'rowId' => $data['rowId'],
          'iteration' => ++self::$iteration   
        );
    } else {
      unset($_SESSION['stopProcessImportCsv']);
      return
        array(
          'percent' => 0,
          'status' => 'canseled',          
          'rowId' => $rowId,
          'iteration' => ++self::$iteration
      );
    }
  }

  /**
   * Останавливает процесс импорта.
   */
  public function stopProcess() {
    $_SESSION['stopProcessImportCsv'] = true;
  }

  /**
   * Основной метод импорта из CSV.
   * @param int $rowId - id строки для старта
   * @param string $schemeType - тип импорта
   * @return array
   */
  public function importFromCsv($rowId, $schemeType) {
    $this->maxExecTime = min(30, @ini_get("max_execution_time"));
    
    if(empty($this->maxExecTime)) {
      $this->maxExecTime = 30;
    }
    
    $startTimeSql = microtime(true);
    $infile = false;

    if($_SESSION['importType'] != 'excel') {
      $fileCSVcheck = new SplFileObject("uploads/importCatalog.csv");
      // сразу считаем количество строк в файле
      $percent100 = -1;
      $fileCSVcheck->seek(0);
      while(!$fileCSVcheck->eof()) {   
        $dataTmp = $fileCSVcheck->fgetcsv(";"); 
        if((count($data) == 1)||($dataTmp == '')) break;
        $percent100++;
      }
      
      if($rowId === 1 || empty($rowId)) {
        $rowId = 0;
      }
    } else {
      include_once CORE_DIR.'script/excel/PHPExcel/IOFactory.php';
      include_once CORE_DIR.'script/excel/chunkReadFilter.php';  

      $file = 'uploads/importCatalog.xlsx';

      $objReader = PHPExcel_IOFactory::createReader("Excel2007");

      $worksheetData = $objReader->listWorksheetInfo($file);
      $percent100 = $worksheetData[0]['totalRows'];
      $totalColumns = $worksheetData[0]['totalColumns'];
      if($rowId === 1 || empty($rowId)) {
        $rowId = 0;
      }
    }
  
    while(($rowId < $percent100)&&!((microtime(true) - $startTimeSql) > $this->maxExecTime - 5)) {
      $data = array();

      if($_SESSION['importType'] != 'excel') {
        $file = new SplFileObject("uploads/importCatalog.csv");
        $file->seek($rowId);
        $line = $file->current();
        file_put_contents("uploads/tmp.csv", htmlspecialchars_decode($line)."\n"); 

        $fileCSV = new SplFileObject("uploads/tmp.csv");
        $fileCSV->seek(0);

        $this->currentRowId = $rowId;
        $validFormat = true;
        $infile = true;
        $data = $fileCSV->fgetcsv(";");
      } else {
        $infile = true;
        $validFormat = true;
        $chunkFilter = new chunkReadFilter();    
        $chunkFilter->setRows($rowId+1, 1); 
        $objReader->setReadFilter($chunkFilter);
        $objReader->setReadDataOnly(true);    
        $objPHPExcel = $objReader->load($file);
        $sheet = $objPHPExcel->getActiveSheet();
        $colNumber = PHPExcel_Cell::columnIndexFromString($sheet->getHighestColumn());
        for($c = 0; $c < $colNumber; $c++) {
          $data[] = $sheet->getCellByColumnAndRow($c, $rowId+1)->getValue();
        }
      }
      
      if($rowId === 0) {
        if($schemeType == 'default') {
          $validFormat = $this->validateFormate(
            $data,
            self::$maskArray[$this->typeCatalog]
          );
          if(!$validFormat) {
            break;          
          }
        }        
        $rowId = 1;
        continue;
      }     
      
      $cData = array(); 
      if(empty(self::$complianceArray)) {
        self::$complianceArray = self::getCompliance($this->typeCatalog, $schemeType);  
      }
      
      $usedArray = array();
      foreach(self::$maskArray[$this->typeCatalog] as $key=>$title) {
        // фикс двойного использования одинаковых полей
        if(in_array(self::$complianceArray[$key], $usedArray)) {
          $cData[$key] = '';
          continue;
        }
        $usedArray[] = self::$complianceArray[$key];
        if (empty(self::$complianceArray)) {
          $v = trim($data[$key]);
        } else {
          $v = trim($data[self::$complianceArray[$key]]);
        }

        if(!empty($v) || $v == 0) {    
          if($_SESSION['importType'] != 'excel') {      
            $cData[$key] = str_replace(' ',' ',iconv("WINDOWS-1251", "UTF-8", $v));
          } else {
            $cData[$key] = $v;
          }
        } else {
          $cData[$key] = '';
        }
      }

      // $complianceArray - массив с установленными соответствиями столбцов (28 - свойства товара)
      // $cData - массив с прочитанными строками
      
      // собираем тайтлы столбцов, если их нет уже, для работы с характеристиками
      if(empty($_SESSION['import']['columnsTitles'])) {
        if($_SESSION['importType'] != 'excel') {
          $file2 = new SplFileObject("uploads/importCatalog.csv");
          $file2->seek(0);
          while(!$file2->eof()) {
            $data1 = $file2->fgetcsv(";");    
            for($i = 0; $i < count($data1); $i++) {
              $_SESSION['import']['columnsTitles'][] = str_replace(' ',' ',iconv("WINDOWS-1251", "UTF-8", $data1[$i]));
            }
            break;
          }
          unset($file2);
        } else {
          $chunkFilter = new chunkReadFilter();    
          $chunkFilter->setRows(1); 
          $objReader->setReadFilter($chunkFilter);
          $objReader->setReadDataOnly(true);    
          $objPHPExcel = $objReader->load($file);
          $sheet = $objPHPExcel->getActiveSheet();
          $colNumber = PHPExcel_Cell::columnIndexFromString($sheet->getHighestColumn());
          for($c = 0; $c < $colNumber; $c++) {
            // $data1[] = $sheet->getCellByColumnAndRow($c, 1)->getValue();
            $_SESSION['import']['columnsTitles'][] = $sheet->getCellByColumnAndRow($c, 1)->getValue();
          }
        }
      }
      
      if($_SESSION['importType'] != 'excel') {
        foreach ($data as &$value) {
          $value = str_replace(' ',' ',iconv("WINDOWS-1251", "UTF-8", $value));
        }
      }

      // if(count($_SESSION['import']['columnsTitles']) == count($data) || count($data) == 1) {
        // если полная загрузка, то считываем все
        if($this->typeCatalog == 'MogutaCMS') {
          // читаем характеристики
          if(self::$complianceArray[28] != 'none') {
            unset($property);
            for($i = self::$complianceArray[28]; $i < count($data); $i++) {
              if(substr_count($_SESSION['import']['columnsTitles'][$i], 'Сложные характеристики') == 0) {
                $property[$_SESSION['import']['columnsTitles'][$i]] = $data[$i];
              }
            }
          }

          // читаем склады
          if(self::$complianceArray[27] != 'none') {
            unset($storage);
            for($i = /*0*/self::$complianceArray[27]; $i < count($data); $i++) {
              if(substr_count($_SESSION['import']['columnsTitles'][$i], '[склад=') != 0) {
                $storages[$_SESSION['import']['columnsTitles'][$i]] = $data[$i];
              }
            }
          }

          // читаем цены оптовые
          if(self::$complianceArray[26] != 'none') {
            unset($storage);
            for($i = /*0*/self::$complianceArray[26]; $i < count($data); $i++) {
              if(substr_count($_SESSION['import']['columnsTitles'][$i], '[оптовая цена]') != 0) {
                $wholesales[$_SESSION['import']['columnsTitles'][$i]] = $data[$i];
              }
            }
          }

          $cData['storages'] = array();
          $cData['storages'] = $storages;

          $cData['wholesales'] = array();
          $cData['wholesales'] = $wholesales;

          $cData['property'] = array();
          $cData['property'] = $property;
        }
        
        $data = $cData;
        self::$fullProduct = $cData;
        $this->currentRowId = $rowId;
        switch($this->typeCatalog) {
          case "MogutaCMS":
            if(!$this->formateMogutaCMS($data)) {
              return false;
            }
            break;
          case "Category":
            if(!$this->formateCategoryMogutaCMS($data)) {
              return false;
            }
            break;
          default:
            if(!$this->formateMogutaCMS($data)) {
              return false;
            }
        }
        $rowId++;
      // } else {
      //   self::log('Ошибка форматирования файла на '.$rowId.' строке');
      //   $this->validError = 'Нарушен порядок столбцов или кодировка! Импорт товаров прерван!';
      //   return false;
      // }
    } 
    unset($fileCSV);

    if(!$validFormat) {
      $this->validError = 'Нарушен порядок столбцов или кодировка!';
      return false;
    }
    
    $fileCSV = null;    
    
    $percent = $rowId;
    $percent = $percent * 100 / $percent100;

    if(!$infile) {
      $percent = 100;
    }

    if($percent >= 100) {
      self::log('----------------------------------');
      self::log('Импорт завершен');
      self::log('Обработано '.$percent100.' строк');
      self::log('Товары были импортированы/обновлены за '.date('i:s', microtime(true) - $_SESSION['startImportTime']));
    } else {
      self::log('-  -  -  -  -  -  -  -  -  -  -  -');
      self::log('Результат импорта на шаге '.self::$iteration);
      self::log('Начало обработки со строки '.$_SESSION['iterationStartRow']);
      self::log('Обработано '.($rowId-$_SESSION['iterationStartRow']).' строк');
      self::log('Время выполнения шага '.date('i:s', microtime(true) - $_SESSION['iterationImportTime']));
      self::log('----------------------------------');
    }

    $data = array(
      'rowId' => $rowId,
      'percent' => floor($percent)
    );

    Storage::clear();

    return $data;
  }

  /**
   * Сопостовляет прочитанные стобцы из файла с настройками импорта.
   * @param string $importType - тип импорта
   * @param string $scheme - схема
   * @return array
   */
  function getCompliance($importType, $scheme) {
    $data = array();
    
    if($scheme != 'default') {
      $data = MG::getOption('csvImport-last'.$importType.'ColComp');
      $data = unserialize(stripslashes($data));
    } else {
      foreach(Import::$maskArray[$importType] as $id=>$title) {
        $data[$id] = $id;
      }
    }
    
    return $data;
  } 
  
  /**
   * Проверка валидности файла.
   * @param array $data массив считанных данных
   * @param array $maskArray формат построения данных
   * @return bool
   */
  public function validateFormate($data,$maskArray) {
    $result = true;
    if(!empty($maskArray[24])) {
      unset($maskArray[24]);
      }
    // Проверим на соответствие заголовки столбцов.
    foreach($data as $k => $v) {
      $v = str_replace(' ',' ',iconv("WINDOWS-1251", "UTF-8", $v));
      
      if(isset($maskArray[$k])) {
        if($maskArray[$k]!=$v) {
          $result = false;      
          $this->validError = 'Столбец "'.$maskArray[$k].'" не обнаружен!';
          break;
        }      
      }
    }    
    return $result;    
  }
  
  /**
   * Импорт или обновление категории.
   * @param array $data массив считанных данных
   * @return bool true
   */
  public function formateCategoryMogutaCMS($data) {
    $arFields = array(
      'title',
      'url',      
      'parent',
      'parent_url',
      'html_content',
      'image_url',
      'meta_title',
      'meta_keywords',
      'meta_desc',
      'seo_content',
      'rate',
      'invisible',
      'activity',
      'export',
      'sort',
      '1c_id',
      'id',
      'seo_title',
      'seo_alt',
    );
    $itemsIn = array();
    
    foreach ($arFields as $key => $field) {
      $itemsIn[$field] = $data[$key];
    }
    
    $category = new Category();
    $itemsIn['csv'] = 1;
    $category->updateCategory($itemsIn);
    
    return true;
  }
  
  /**
   * Полная выгрузка по формату Moguta.CMS.
   * @param array $data массив считанных данных
   * @param bool $new флаг о начале импорта
   * @return bool true
   */    
  public function formateMogutaCMS($data, $new = false) { 

    // выдераем характеристики, потом обратно засунем
    $property = $data['property'];
    unset($data['property']);

    // выдераем склады, потом обратно засунем
    $storages = $data['storages'];
    unset($data['storages']);
    // выдераем оптовые цены, потом обратно засунем
    $wholesales = $data['wholesales'];
    unset($data['wholesales']);

    unset($itemsIn['property']);

    foreach($data as $cell => $value) {
      if(!$new && $_POST['schemeType'] != 'default' && self::$complianceArray[$cell] == 'none') {
        continue;
      }
      
      $itemsIn[self::$fields[$this->typeCatalog][$cell]] = trim($value);
    }  
    
    // костыль
    $itemsIn['cat_id'] = $data[2];

    // суем характеристики обратно
    if(self::$complianceArray[28] != 'none') $itemsIn['property'] = $property;

    // суем склады обратно
    if(self::$complianceArray[27] != 'none') $itemsIn['storages'] = $storages;
    // суем оптовые цены обратно
    if(self::$complianceArray[26] != 'none') $itemsIn['wholesales'] = $wholesales;

    if(!empty($data[5])) {
      if(strpos($data[5], '[:param:]')!==false) {
        $variant = explode('[:param:]', $data[5]);
        $itemsIn['variant'] = $variant[0];
        $itemsIn['image'] = str_replace(array('[src=', ']'),'', $variant[1]);
      } else {
        $itemsIn['variant'] = $data[5];
      }     
    }  

    if(self::isEndFile()) return true;

    if(empty($itemsIn['cat_id'])) {
      $itemsIn['cat_id'] = -1;
    }
    
    $itemsIn['price'] = str_replace(',','.',$itemsIn['price']); 
    $itemsIn['old_price'] = str_replace(',','.',$itemsIn['old_price']);

    // создаем категорию если надо
    if(empty($_SESSION['import']['category'][$itemsIn['cat_id'].$itemsIn['cat_url']])) {
      if($itemsIn['cat_url'] != '') {
        $categories = $this->parseCategoryPath($itemsIn['cat_url']);
        $tmp = array();
        $tmp = explode('/', $itemsIn['cat_id']);
        $i = 0;
        foreach ($categories as $key => $value) {
          $categories[$key]['title'] = $tmp[$i];
          $i++;
        }
        $this->createCategory($categories);
      } else {
        $categories = $this->parseCategoryPath($itemsIn['cat_id']);
        $this->createCategory($categories);
      }
      $lastElem = array_pop($categories);
      // находим сам урл
      $res = DB::query('SELECT id FROM '.PREFIX.'category WHERE url = '.DB::quote($lastElem['url']).' 
        AND parent_url = '.DB::quote($lastElem['parent_url']=='/'?'':$lastElem['parent_url']));
      while($row = DB::fetchAssoc($res)) {
        $_SESSION['import']['category'][$itemsIn['cat_id'].$itemsIn['cat_url']] = $row['id'];
      }
      DB::query('UPDATE '.PREFIX.'category SET unit = '.DB::quote($itemsIn['category_unit']).'
        WHERE id = '.DB::quoteInt($_SESSION['import']['category'][$itemsIn['cat_id'].$itemsIn['cat_url']]));
    }
    $itemsIn['cat_id'] = $_SESSION['import']['category'][$itemsIn['cat_id'].$itemsIn['cat_url']];

    // конвентируем старую цену
    $curSetting = MG::getSetting('currencyRate');     
    if($itemsIn['currency_iso']) {
      if($itemsIn['currency_iso'] != 'RUR' && $itemsIn['old_price'] != 0) {
        $itemsIn['old_price'] *= $curSetting[$itemsIn['currency_iso']];
      }   
    }

    if($itemsIn['currency_iso'] != 'RUR') {
      foreach ($itemsIn['wholesales'] as $key => $value) {
        $itemsIn['wholesales'][$key] = round($value * $curSetting[$itemsIn['currency_iso']], 2);
      }
    }   

    if($itemsIn['cat_id'] == '' && $itemsIn['title'] == '' && $itemsIn['variant'] == '') {
      $this->updateProduct($itemsIn);
    } else {
      $this->createProduct($itemsIn, $itemsIn['cat_id']);
      if($itemsIn['cat_id'] == '') {
        self::log('У товара отсутсвует категория, строка '.$this->currentRowId);
      }
      // проерка того, что товар есть
      $res = DB::query('SELECT id FROM '.PREFIX.'product WHERE cat_id = '.DB::quoteInt(htmlspecialchars($itemsIn['cat_id'])).' AND title = '.DB::quote(htmlspecialchars($itemsIn['title'])));
      if(!$row = DB::fetchAssoc($res)) {
        self::log('Товар с именем "'.$itemsIn['title'].'" не создан (id созданной для товара категории "'.$itemsIn['cat_id'].'")'.DB::quote($itemsIn['title']));
      }
    }

    return true;
  }

  /**
   * Создает продукт в БД если его не было.
   * @param array $product - массив с данными о продукте.
   * @param int|null $catId - категория к которой относится продукт.
   * @return bool|void
   */
  public function createProduct($product, $catId = null) {
    $model = new Models_Product();
    $variant = $product['variant'];
    $img_var = $product['image'];
    $property = $product['property'];
    $hardProp = $product['hard_prop'];
    $storages = $product['storages'];
    $wholesales = $product['wholesales'];
    $color = $product['color'];
    $size = $product['size'];
    $product['price'] = MG::numberDeFormat($product['price']);
    $product['old_price'] = MG::numberDeFormat($product['old_price']);
    $product['unit'] = $product['category_unit'];
    unset($product['cat_url']);
    unset($product['variant']);
    unset($product['image']);
    unset($product['property']);
    unset($product['hard_prop']);
    unset($product['storages']);
    unset($product['wholesales']);
    unset($product['category_unit']);
    unset($product['color']);
    unset($product['size']);

    if($product['activity'] == '') {
      if($_POST['defaultActive'] === "true") {
        $product['activity'] = 1;
      } else {
        $product['activity'] = 0;
      }
    }

    // если в строке содержится ссылка
  	if (strpos($product['image_url'], "http://") !== false|| strpos($product['image_url'], "https://") !== false) {
      self::$downloadLink = true;
	    $this->downloadImgFromSite($product['image_url']);
      $product['image_url'] = basename($product['image_url']);  
	  } elseif(strpos($product['image_url'], '[:param:]')!==false) {
	    // Парсим изображение, его alt и title.
      $images = $this->parseImgSeo($product['image_url']);
      $product['image_url'] = $images[0];  
      $product['image_alt'] = $images[1];
      $product['image_title'] = $images[2];   
    }

    if($catId === null) {
      // 1 находим ID категории по заданному пути.
      $product['cat_id'] = MG::translitIt($product['cat_id'], 1);
      $product['cat_id'] = URL::prepareUrl($product['cat_id']);

      if($product['cat_id']) {
        $product['cat_id'] = (empty($product['cat_id'])) ? $product['cat_url'] : $product['cat_id'];
        
        $url = URL::parsePageUrl($product['cat_id']);
        $parentUrl = URL::parseParentUrl($product['cat_id']);
        $parentUrl = $parentUrl != '/' ? $parentUrl : '';                
        
        $cat = MG::get('category')->getCategoryByUrl($url, $parentUrl);     
        $product['cat_id'] = $cat['id'];
      }
    } else {
      $product['cat_id'] = $catId;
    }

    if($catId == -1) {
      unset($product['cat_id']);
    } else {
      $product['cat_id'] = !empty($product['cat_id']) ? $product['cat_id'] : 0;
    }    

    if(!empty($product['id']) && is_numeric($product['id'])) {   
      $dbRes = DB::query('SELECT `id`, `url`, `title` FROM `'.PREFIX.'product` WHERE `id` = '.DB::quoteInt($product['id']));
      
      if($res = DB::fetchArray($dbRes)) {        
        if($res['title'] == $product['title']) {
          $product['url'] = $res['url'];
        }       
        // unset($product['id']);
      } else {
        if(empty($_SESSION['csv_import_full'])) { 
          $_SESSION['csv_import_full'] = 'y';
          $this->formateMogutaCMS(self::$fullProduct, true); 
          return;
        } else {
          unset($_SESSION['csv_import_full']);
        }   
        $arrProd = $model->addProduct($product);               
      }             
    }
    
    if(empty($arrProd)) {
      // 2 если URL не задан в файле, то транслитирируем его из названия товара.
      $product['url'] = !empty($product['url'])?$product['url']:preg_replace('~-+~','-',MG::translitIt($product['title'], 1));
      $product['url'] = str_replace(array(':', '/'),array('', '-'),$product['url']);
      $product['url'] = URL::prepareUrl($product['url'], true);  
      
      // сначала поиск по артикулу
      if(!empty($product['code'])) {
        $res = DB::query('
          SELECT id, url
          FROM `'.PREFIX.'product`
          WHERE code = '.DB::quote($product['code'])
        );
        
        $alreadyProduct = DB::fetchAssoc($res);
        
        if(empty($alreadyProduct['id'])) {
          $res = DB::query('
            SELECT p.id, p.url
            FROM `'.PREFIX.'product` p
              LEFT JOIN `'.PREFIX.'product_variant` pv
                ON pv.product_id = p.id
            WHERE pv.code = '.DB::quote($product['code'])
          );
          
          $alreadyProduct = DB::fetchAssoc($res);
        } 
      }

      // если не нашли товар по артикулу, то тогда ищем по названию
      if(empty($alreadyProduct['id'])) {
        if(empty($product['cat_id']) || $product['cat_id'] == 0) {
          $alreadyProduct = $model->getProductByUrl($product['url']);
        } else {
          $alreadyProduct = $model->getProductByUrl($product['url'], $product['cat_id']);
        }
      }

      // поиск по урлу
      if(empty($alreadyProduct['id'])) {
        if($product['cat_id'] == 0) {
          $alreadyProduct = $model->getProductByUrl($product['url']);
        } else {
          $alreadyProduct = $model->getProductByUrl($product['url'], $product['cat_id']);
        }
      }

      // Если в базе найден этот продукт, то при обновлении будет сохранен ID и URL. 
      if(!empty($alreadyProduct['id'])) {
        $product['id'] = $alreadyProduct['id'];
        $product['url'] = $alreadyProduct['url'];
      }

      if(((empty($alreadyProduct['id']) || !empty($variant))) && empty($product['id'])) {
        $res = DB::query('SELECT MAX(id) FROM '.PREFIX.'product');
        while($row = DB::fetchAssoc($res)) {
          $productId = ++$row['MAX(id)'];
        }

        $product['id'] = $productId;
        $model->addProduct($product);
      }
      
      // обновляем товар, если его не было то метод вернет массив с параметрами вновь созданного товара, в том числе и ID. Иначе  вернет true       
      $arrProd = $model->updateProduct($product);
    }
       
    $product_id = $product['id']?$product['id']:$arrProd['id'];   
    $categoryId = $product['cat_id'];
    $productId = $product_id;
    $listProperty = $property;


    // удаляем характеристики // TODO
    if(self::$complianceArray[28] != 'none') {
      DB::query('DELETE pupd FROM '.PREFIX.'product_user_property_data AS pupd
        INNER JOIN '.PREFIX.'property AS p ON p.id = pupd.prop_id 
        WHERE pupd.product_id = '.DB::quoteInt($product['id']).' AND p.type NOT IN (\'color\', \'size\')');
    }
    // добавляем характеристики к товарам
    foreach ($property as $key => $value) {
      if(!empty($value)) {
        // проверяем не являеться ли характеристика размерной сеткой, если да, то обрабатываем ее подругому
        if((substr_count($key, '[size]') == 1)||(substr_count($key, '[color]') == 1)) {
          Property::createSizeMapPropFromCsv($key, $value, $product_id, $variant, $product['cat_id']);
          // continue;
        } else {
          // создаем характеристики и получаем их id 
          if(empty($_SESSION['import']['property'][$key])) {
            $_SESSION['import']['property'][$key] = Property::createProp($key);
          }
          // связываем характеристику с категорией
          Property::createPropToCatLink($_SESSION['import']['property'][$key], $product['cat_id']);
          // записываем содержимое характеристики для товара
          Property::createProductStringProp($value, $product['id'], $_SESSION['import']['property'][$key]);
        }
      }
    }
    Property::createHardPropFromCsv($hardProp, $product_id, $product['cat_id']);

    // добавляем оптовые цены только для одиночного товара
    if($wholesales != NULL) {
      DB::query('DELETE FROM '.PREFIX.'wholesales_sys WHERE 
              product_id = '.DB::quoteInt($product_id).' AND variant_id = 0');
      foreach ($wholesales as $key => $value) {
        if(!empty($value)) {
          // $count = preg_replace("/[^0-9]/", '', $key);
          
          $tmp = explode('Количество от ', $key);
          $tmpCount = $tmp[1];
          $tmpCount = explode(' для цены ', $tmpCount);
          $count = $tmpCount[0];
          $tmpGroup = explode(' [оптовая цена]', $tmpCount[1]);
          $group = $tmpGroup[0];

          DB::query('INSERT INTO '.PREFIX.'wholesales_sys (product_id, variant_id, count, price, `group`) VALUES
            ('.DB::quoteInt($product_id).', 0, 
            '.DB::quoteInt($count).', '.DB::quote((float)$value, true).', '.DB::quoteInt($group).')');
        }
      }
    }

    // добавляем склады
    if($storages !== NULL) {
      if(empty($_SESSION['import']['storageArray'])) {
        $storageArray = array();
      } else {
        $storageArray = $_SESSION['import']['storageArray'];
      }

      $storageArray = unserialize(stripcslashes(MG::getSetting('storages')));
      foreach($storages as $key => $value) {
        // дробим ключ, чтобы все узнать
        $key = explode('[', str_replace(']', '', $key));
        $storageId = explode('=', $key[1]);
        $storageItem['id'] = $storageId[1];
        $storageItem['name'] = $key[0];
        if($storageItem['id'] == '') {
          $storageItem['id'] = substr(md5($storageItem['name']), 0, 10);
        }
        $findStorage = false;
        foreach ($storageArray as $item) {
          if($item['id'] == $storageItem['id']) {
            $findStorage = true;
          }
        }
        if(!$findStorage) {
          $storageArray[] = $storageItem;
        }
        // заполняем склад
        if(empty($variantId)) $variantId = 0;
        DB::query('DELETE FROM '.PREFIX.'product_on_storage WHERE 
          product_id = '.DB::quoteInt($product_id).' AND variant_id = 0
          AND storage = '.DB::quote($storageItem['id']));
        DB::query('INSERT INTO '.PREFIX.'product_on_storage (product_id, variant_id, storage, count) VALUES 
          ('.DB::quoteInt($product_id).', 0, 
          '.DB::quote($storageItem['id']).', '.DB::quoteInt($value).')');
      }
      if(empty($_SESSION['import']['storageArray'])) {
        MG::setOption('storages', addslashes(serialize($storageArray)));
        $_SESSION['import']['storageArray'] = $storageArray;
      }
    }

    // viewData($variant);
    
    if(!$variant) {
      return true;
    }
    
    $var = $model->getVariants($product['id']);

    $varUpdate = null;
    
    if(!empty($var)) {
      foreach($var as $k => $v) {
        if($v['code'] == $product['code'] && $v['product_id'] == $product_id) {
          $varUpdate = $v['id'];
          break;
        }
        if($v['title_variant'] == $variant && $v['product_id'] == $product_id) {
          $varUpdate = $v['id'];
        }
      }
    }

    // Иначе обновляем существующую запись в таблице вариантов.
    $varFields = array(      
      'price',
      'old_price',
      'count',
      'code',  
      'weight',
      'activity',
      'currency_iso'
    );
    
    $newVariant = array(
      'product_id' => $product_id,
      'title_variant' => $variant,
    );
    
    if($img_var) {
      $newVariant['image'] = $img_var;
    }
    
    foreach($varFields as $field) {
      if(isset($product[$field])) {
        $newVariant[$field] = $product[$field];   
      }
    }

    $model->importUpdateProductVariant($varUpdate, $newVariant, $product_id);

    // Обновляем продукт по первому варианту.
    $res = DB::query('
      SELECT  pv.*
      FROM `'.PREFIX.'product_variant` pv    
      WHERE pv.product_id = '.DB::quote($product_id).'
      ORDER BY sort');
    if($row = DB::fetchAssoc($res)) {

      if(!empty($row)) {
        if($product['title']) {
          $row['title'] = $product['title'];
        }        
        
        $row['id'] = $row['product_id'];
        unset($row['image']);
        unset($row['sort']);
        unset($row['title_variant']);
        unset($row['product_id']);
        $model->updateProduct($row);
      }
    }

    // добавляем оптовые цены
    if($wholesales != NULL) {
      $res = DB::query('SELECT id FROM '.PREFIX.'product_variant WHERE product_id = '.DB::quoteInt($product_id).'
        AND title_variant = '.DB::quote($variant));
      while ($row = DB::fetchAssoc($res)) {
        $variantId = $row['id'];
      }
      DB::query('DELETE FROM '.PREFIX.'wholesales_sys WHERE 
              product_id = '.DB::quoteInt($product_id).' AND variant_id = '.DB::quoteInt($variantId));
      foreach ($wholesales as $key => $value) {
        if(!empty($value)) {
          // $count = preg_replace("/[^0-9]/", '', $key);
          
          $tmp = explode('Количество от ', $key);
          $tmpCount = $tmp[1];
          $tmpCount = explode(' для цены ', $tmpCount);
          $count = $tmpCount[0];
          $tmpGroup = explode(' [оптовая цена]', $tmpCount[1]);
          $group = $tmpGroup[0];

          DB::query('INSERT INTO '.PREFIX.'wholesales_sys (product_id, variant_id, count, price, `group`) VALUES
            ('.DB::quoteInt($product_id).', '.DB::quoteInt($variantId).', 
            '.DB::quoteInt($count).', '.DB::quote((float)$value, true).', '.DB::quoteInt($group).')');
        }
      }
    }

    // добавляем склады
    if($storages !== NULL) {
      if(empty($variantId)) {
        $res = DB::query('SELECT id FROM '.PREFIX.'product_variant WHERE product_id = '.DB::quoteInt($product_id).'
          AND title_variant = '.DB::quote($variant));
        while ($row = DB::fetchAssoc($res)) {
          $variantId = $row['id'];
        }
      }
      if(empty($_SESSION['import']['storageArray'])) {
        $storageArray = array();
      } else {
        $storageArray = $_SESSION['import']['storageArray'];
      }

      $storageArray = unserialize(stripcslashes(MG::getSetting('storages')));
      foreach($storages as $key => $value) {
        // дробим ключ, чтобы все узнать
        $key = explode('[', str_replace(']', '', $key));
        $storageId = explode('=', $key[1]);
        $storageItem['id'] = $storageId[1];
        $storageItem['name'] = $key[0];
        if($storageItem['id'] == '') {
          $storageItem['id'] = substr(md5($storageItem['name']), 0, 10);
        }
        $findStorage = false;
        foreach ($storageArray as $item) {
          if($item['id'] == $storageItem['id']) {
            $findStorage = true;
          }
        }
        if(!$findStorage) {
          $storageArray[] = $storageItem;
        }
        // заполняем склад
        if(empty($variantId)) $variantId = 0;
        DB::query('DELETE FROM '.PREFIX.'product_on_storage WHERE 
          product_id = '.DB::quoteInt($product_id).' AND variant_id = '.DB::quoteInt($variantId).'
          AND storage = '.DB::quote($storageItem['id']));
        DB::query('INSERT INTO '.PREFIX.'product_on_storage (product_id, variant_id, storage, count) VALUES 
          ('.DB::quoteInt($product_id).', '.DB::quoteInt($variantId).', 
          '.DB::quote($storageItem['id']).', '.DB::quoteInt($value).')');
      }
      if(empty($_SESSION['import']['storageArray'])) {
        MG::setOption('storages', addslashes(serialize($storageArray)));
        $_SESSION['import']['storageArray'] = $storageArray;
      }
    }

    // добавляем характеристики к товарам // TODO 
    foreach ($property as $key => $value) {
      if(!empty($value)) {
        // проверяем не являеться ли характеристика размерной сеткой, если да, то обрабатываем ее подругому
        if((substr_count($key, '[size]') == 1)||(substr_count($key, '[color]') == 1)) {
          Property::createSizeMapPropFromCsv($key, $value, $product_id, $variant, $product['cat_id']);
          // continue;
        }
      }
    }
  }

  /**
   * Создает категории в БД если их небыло.
   * @param array $categories - массив категорий полученный из записи вида категория/субкатегория/субкатегория2.
   */
  public function createCategory($categories) {
    foreach($categories as $category) {
      $category['parent_url'] = $category['parent_url'] != '/'?$category['parent_url']:'';

      if($category['parent_url']) {
        $pUrl = URL::parsePageUrl($category['parent_url']);
        $parentUrl = URL::parseParentUrl($category['parent_url']);
        $parentUrl = $parentUrl != '/'?$parentUrl:'';
      } else {
        $pUrl = $category['url'];
        $parentUrl = $category['parent_url'];
      }

      $res = DB::query('SELECT COUNT(id) FROM '.PREFIX.'category WHERE url = '.DB::quote($category['url']).' 
        AND parent_url = '.DB::quote($category['parent_url']=='/'?'':$category['parent_url']));
      while ($catRow = DB::fetchAssoc($res)) {
        if($catRow['COUNT(id)'] == 0) {
          $parentId = 0;
          if($category['parent_url'] != '') {
            $sections = explode('/', $category['parent_url']);
            $lastSection = $sections[count($sections)-2];
            $parentIdRes = DB::query('SELECT id FROM '.PREFIX.'category WHERE url = '.DB::quote($lastSection));
            while ($row = DB::fetchAssoc($parentIdRes)) {
              $parentId = $row['id'];
            }
          }
          DB::query('INSERT INTO '.PREFIX.'category (title, url, parent_url, parent, sort) VALUES
            ('.DB::quote($category['title']).', '.DB::quote($category['url']).', '.DB::quote($category['parent_url']).',
            '.DB::quoteInt($parentId).', '.DB::quoteInt(++$_SESSION['import']['categoryCounter']).')');
        }
      }
    }
  }

  /**
   * Парсит путь категории возвращает набор категорий.
   * @param string $path список категорий через / слэш.
   * @return array массив с данными о категории
   */
  public function parseCategoryPath($path) {

    $i = 1;

    $categories = array();
    if(!$path || $path == -1) {
      return $categories;
    }

    $parent = $path;
    $parentForUrl = str_replace(array('«', '»'), '', $parent);    
    $parentTranslit = MG::translitIt($parentForUrl, 1);
    $parentTranslit = URL::prepareUrl($parentTranslit);

    $categories[$parent]['title'] = URL::parsePageUrl($parent);
    $categories[$parent]['url'] = URL::parsePageUrl($parentTranslit);
    $categories[$parent]['parent_url'] = URL::parseParentUrl($parentTranslit);
    $categories[$parent]['parent'] = 0;

    while($parent != '/') {
      $parent = URL::parseParentUrl($parent);
      $parentForUrl = str_replace(array('«', '»'), '', $parent);
      $parentTranslit = MG::translitIt($parentForUrl, 1);
      $parentTranslit = URL::prepareUrl($parentTranslit);
      if($parent != '/') {
        $categories[$parent]['title'] = URL::parsePageUrl($parent);
        $categories[$parent]['url'] = URL::parsePageUrl($parentTranslit);
        $categories[$parent]['parent_url'] = URL::parseParentUrl($parentTranslit);
        $categories[$parent]['parent_url'] = $categories[$parent]['parent_url'] != '/'?$categories[$parent]['parent_url']:'';
        $categories[$parent]['parent'] = 0;
      }
    }

    $categories = array_reverse($categories);

    return $categories;
  }

  /**
   * Сравнивает создаваемую категорию, с имеющимися ранее.
   * Если обнаруживает, что аналогичная категория раньше существовала,то возвращает ее старый ID.   
   * @param string $title название товара.
   * @param string $path путь.
   * @return int|null id категории.
   */
  public function getCategoryId($title, $path) {
    $path = trim($path, '/');

    $sql = '
      SELECT cat_id
      FROM `'.PREFIX.'import_cat`
      WHERE `title` ='.DB::quote($title)." AND `parent` = ".DB::quote($path);

    $res = DB::query($sql);
    if($row = DB::fetchAssoc($res)) {
      return $row['cat_id'];
    }
    return null;
  }

  /**
   * Возвращает старый ID для товара.
   * то возвращает ее старый ID.
   * @param string $title - название товара.
   * @param int $cat_id - id категории.
   * @return int|null id товара.
   */
  public function getProductId($title, $cat_id) {
    $path = trim($path, '/');

    $sql = '
      SELECT product_id
      FROM `'.PREFIX.'import_prod`
      WHERE `title` ='.DB::quote($title)." AND `category_id` = ".DB::quote($cat_id);

    $res = DB::query($sql);
    if($row = DB::fetchAssoc($res)) {
      return $row['product_id'];
    }
    return null;
  }
  /**
   * Возвращает массив из изображений и seo-настройки к ним - alt и title
   * @param string $listImg пример $listImg = 'noutbuk.png[:param:][alt=ноутбук][title=ноутбук]|noutbuk-Dell-Inspiron-N411Z-oneside.png[:param:][alt=ноутбук черного цвета][title=ноутбук черного цвета]';
   * @return array
   */
  function parseImgSeo($listImg) {  	
    $images_alt = '';
    $images_title = '';
    $images = explode('|', $listImg);
    foreach ($images as $value) {
      $item = explode('[:param:]', $value);
      $images_url .= $item[0].'|';
      $seo = explode(']', $item[1]);
      $images_alt .= str_replace('[alt=','', $seo[0]).'|';
      $images_title .= str_replace('[title=','', $seo[1]).'|';  
    }
    $result = array(substr($images_url, 0, -1), substr($images_alt, 0, -1), substr($images_title, 0, -1));
    return $result;
  }

  /**
   * Загружает изображения с сайтов по ссылке.
   * @param string $url - местонахождение изображения в сети
   * @return bool|void
   */
  function downloadImgFromSite($url) {

    if(!$this->autoStartImageGen()) {
      return false;
    } 

    $realDocumentRoot = str_replace(DIRECTORY_SEPARATOR.'mg-core'.DIRECTORY_SEPARATOR.'lib', '', dirname(__FILE__));
    $path = $realDocumentRoot.'/uploads/tempimage/';
    if (!file_exists($path)) {
      chdir($realDocumentRoot."/uploads/");
      mkdir("tempimage", 0777);
    }

  	$ch = curl_init($url);  
    $path = $realDocumentRoot.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'tempimage';

  	$fp = fopen($path.'/'.basename($url), 'wb');

  	curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
  	curl_setopt($ch, CURLOPT_HEADER, 0);
  	curl_exec($ch);
  	curl_close($ch);
  	fclose($fp);
  }

  /**
   * Определяет нужно ли производить загрузку изображений.
   * @return bool
   */
  function autoStartImageGen() {
    if(self::$downloadLink == true) {
      return true;
    }
    return false;
  }

  /**
   * Записывает лог импорта в отдельный файл.
   * @param string $text текст для записи
   * @param bool $new начинать ли новый файл
   */
  function log($text, $new = false) {
    if($new) {
      unlink('import_csv_log.txt');
      $string = 'Лог для импорта каталога из CSV'."\r\n";
      $string .= 'Начало импорта - '.date('d.m.Y H:i:s')."\r\n";
      $string .= '----------------------------------'."\r\n";
    }
    $fileName = 'import_csv_log.txt';
    if($text != '') {
      $string .= print_r($text, true)."\r\n";
    }
    $f = fopen($fileName, 'a+');
    fwrite($f, $string);
    fclose($f);
  }

  /**
   * Определяет при чтении CSV конец файла, для прерывания процесса импорта.
   * @return bool
   */
  function isEndFile() {
    foreach (self::$fullProduct as $item) {
      if(!is_array($item) && !empty($item)) {
        return false;
      }
    }
    return true;
  }

  /**
   * Упрощенный метод импорта товаров, обновляет только цены и остатки.
   * @param array $data текст для записи
   * @return bool
   */
  public function updateProduct($data) { 
    $storages = $data['storages'];
    $wholesales = $data['wholesales'];
    $arrayToUpdateFilter = array('code', 'price', 'old_price', 'count');
    foreach ($data as $key => $value) {
      if(in_array($key, $arrayToUpdateFilter)) {
        $itemsIn[$key] = $value;
      }
    }

    foreach ($itemsIn as $key => $item) {
      if($item == '') unset($itemsIn[$key]); 
    }

    DB::query('
      UPDATE `'.PREFIX.'product`
      SET '.DB::buildPartQuery($itemsIn).'
      WHERE code = '.DB::quote($data['code'])
    );
    
    DB::query('
      UPDATE `'.PREFIX.'product_variant`
      SET '.DB::buildPartQuery($itemsIn).'
      WHERE code = '.DB::quote($data['code'])
    );
    
    $model = new Models_Product();
    $currencyShopIso = MG::getSetting('currencyShopIso');    
    
    $res = DB::query('
      SELECT id
      FROM `'.PREFIX.'product`
      WHERE code = '.DB::quote($data['code'])
    );
    
    if($row = DB::fetchAssoc($res)) {     
      $model->updatePriceCourse($currencyShopIso, array($row['id']));    
      $productId = $row['id'];
      $variantId = 0;
    } else {
      $res = DB::query('
        SELECT product_id
        FROM `'.PREFIX.'product_variant`
        WHERE code = '.DB::quote($data['code'])
      );
      
      if($row = DB::fetchAssoc($res)) {     
        $model->updatePriceCourse($currencyShopIso, array($row['product_id']));    
      }
    }

    // пытаемся достать id варианта, если раньше не получилось
    $res = DB::query('
      SELECT product_id, id
      FROM `'.PREFIX.'product_variant`
      WHERE code = '.DB::quote($data['code'])
    );
    if($row = DB::fetchAssoc($res)) {     
      $model->updatePriceCourse($currencyShopIso, array($row['product_id']));    
      $productId = $row['product_id'];
      $variantId = $row['id'];
    }

    // добавляем оптовые цены
    if($wholesales != NULL) {
      
      DB::query('DELETE FROM '.PREFIX.'wholesales_sys WHERE 
              product_id = '.DB::quoteInt($productId).' AND variant_id = '.DB::quoteInt($variantId));
      foreach ($wholesales as $key => $value) {
        if(!empty($value)) {
          // $count = preg_replace("/[^0-9]/", '', $key);
          $tmp = explode('Количество от ', $key);
          $tmpCount = $tmp[1];
          $tmpCount = explode(' для цены ', $tmpCount);
          $count = $tmpCount[0];
          $tmpGroup = explode(' [оптовая цена]', $tmpCount[1]);
          $group = $tmpGroup[0];

          DB::query('INSERT INTO '.PREFIX.'wholesales_sys (product_id, variant_id, count, price, `group`) VALUES
            ('.DB::quoteInt($productId).', '.DB::quoteInt($variantId).', 
            '.DB::quoteInt($count).', '.DB::quoteInt($value).', '.DB::QuoteInt($group).')');
        }
      }
    }

    // добавляем склады
    if($storages != NULL) {
      if(empty($_SESSION['import']['storageArray'])) {
        $storageArray = array();
      } else {
        $storageArray = $_SESSION['import']['storageArray'];
      }

      $storageArray = unserialize(stripcslashes(MG::getSetting('storages')));
      // viewData($storages);
      foreach($storages as $key => $value) {
        // дробим ключ, чтобы все узнать
        $key = explode('[', str_replace(']', '', $key));
        $storageId = explode('=', $key[1]);
        $storageItem['id'] = $storageId[1];
        $storageItem['name'] = $key[0];
        if($storageItem['id'] == '') {
          $storageItem['id'] = substr(md5($storageItem['name']), 0, 10);
        }
        $findStorage = false;
        foreach ($storageArray as $item) {
          if($item['id'] == $storageItem['id']) {
            $findStorage = true;
          }
        }
        if(!$findStorage) {
          $storageArray[] = $storageItem;
        }
        // заполняем склад
        DB::query('DELETE FROM '.PREFIX.'product_on_storage WHERE 
          product_id = '.DB::quoteInt($productId).' AND variant_id = '.DB::quoteInt($variantId).'
          AND storage = '.DB::quote($storageItem['id']));
        DB::query('INSERT INTO '.PREFIX.'product_on_storage (product_id, variant_id, storage, count) VALUES 
          ('.DB::quoteInt($productId).', '.DB::quoteInt($variantId).', 
          '.DB::quote($storageItem['id']).', '.DB::quoteInt($value).')');
      }
      if(empty($_SESSION['import']['storageArray'])) {
        MG::setOption('storages', addslashes(serialize($storageArray)));
        $_SESSION['import']['storageArray'] = $storageArray;
      }
    }
    
    return true;    
  }
}