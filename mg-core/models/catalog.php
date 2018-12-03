<?php

/**
 * Модель: Catalog
 *
 * Класс Models_Catalog реализует логику работы с каталогом товаров.
 *
 * @author Авдеев Марк <mark-avdeev@mail.ru>
 * @package moguta.cms
 * @subpackage Model
 *
 */
class Models_Catalog {

  /**
   * @var array @var mixed Массив с категориями продуктов.
   */
  public $categoryId = array();

  /**
   * @var array @var mixed Массив текущей категории.
   */
  public $currentCategory = array();

  /**
   * @var array @var mixed Фильтр пользователя..
   */
  public $userFilter = array();

  /**
   * Записывает в переменную класса массив содержащий ссылку и название текущей, открытой категории товаров.
   * <code>
   * $catalog = new Models_Catalog;
   * $catalog->getCurrentCategory();
   * </code>
   * @return bool
   */
  public function getCurrentCategory() {
    $result = false;

    $sql = '
      SELECT *
      FROM `' . PREFIX . 'category`
      WHERE id = %d
    ';

    if (end($this->categoryId)) {
      $res = DB::query($sql, end($this->categoryId));
      if ($this->currentCategory = DB::fetchAssoc($res)) {
        $result = true;
      }

    } else {
      $this->currentCategory['url'] = 'catalog';
      $this->currentCategory['title'] = 'Каталог';
      $result = true;
    }

    $args = func_get_args();
    return MG::createHook(__CLASS__ . "_" . __FUNCTION__, $result, $args);
  }

  /**
   * Возвращает список товаров и пейджер для постраничной навигации.
   * <code>
   * $catalog = new Models_Catalog;
   * $items = $catalog->getList(6, false, true);
   * viewData($items);
   * </code>
   * @param int $countRows количество возвращаемых записей для одной страницы.
   * @param bool $mgadmin откуда вызван метод, из публичной части или панели управления.
   * @param bool $onlyActive учитывать только активные продукты.
   * @return array
   */
  public function getList($countRows = 20, $mgadmin = false, $onlyActive = false) {
    // Если не удалось получить текущую категорию.
    if (!$this->getCurrentCategory()) {
      echo 'Ошибка получения данных!';
      exit;
    }

    // только для публичной части строим html для фильтров, а если уже пришел запрос с нее, то получаем результат
    if (!$mgadmin) {

      $onlyInCount = false; // ищем все товары
      if(MG::getSetting('printProdNullRem') == "true") {
        $onlyInCount = true; // ищем только среди тех которые есть в наличии
      }
      $filterProduct = $this->filterPublic(true, $onlyInCount);
      
      MG::set('catalogfilter',$filterProduct['filterBarHtml']);

      // return array('catalogItems'=>null, 'pager'=>null, 'filterBarHtml'=>$filter->getHtmlFilter(true), 'userFilter' => $userFilter);
      // если пришел запрос с фильтра со страницы каталога и не используется плагин фильтров
      if (isset($_REQUEST['applyFilter'])) {

        $result = array();
        if (!empty($filterProduct['userFilter'])) {
          // если при генерации фильтров был построен запрос
          // по входящим свойствам товара из  get запроса
          // то получим все товары  именно по данному запросу, учитывая фильтрацию по характеристикам

          $result = $this->getListByUserFilter($countRows, $filterProduct['userFilter']);

          $result['filterBarHtml'] = $filterProduct['filterBarHtml'];
          $result['htmlProp'] = $filterProduct['htmlProp'];
          $result['applyFilterList'] = $filterProduct['applyFilterList'];
        }

        $args = func_get_args();
        return MG::createHook(__CLASS__ . "_" . __FUNCTION__, $result, $args);
      }
    }

    // Страница.
    $page = URL::get("page");

    $sql .= 'SELECT p.id, CONCAT(c.parent_url,c.url) as category_url, c.unit as category_unit, p.unit as product_unit,
          p.url as product_url, p.*, pv.product_id as variant_exist, rate,
          (p.price_course + p.price_course * (IFNULL(rate,0))) as `price_course`,
          IF(p.count <0, 1000000, 
            IF(varcount, 
              IF(p.count<varcount, varcount, p.count), 
            p.count)
          ) AS  `count_sort`, p.currency_iso
        FROM `' . PREFIX . 'product` AS p
        LEFT JOIN `' . PREFIX . 'category` AS c
          ON c.id = p.cat_id
        LEFT JOIN `' . PREFIX . 'product_variant` AS pv
          ON p.id = pv.product_id
        LEFT JOIN (
          SELECT pv.product_id, SUM(IF(pv.count <0, 1000000, pv.count)) AS varcount
          FROM  `' . PREFIX . 'product_variant` AS pv
          GROUP BY pv.product_id
        ) AS temp ON p.id = temp.product_id';

      // FIND_IN_SET - учитывает товары, в настройках которых,
      // указано в каких категориях следует их показывать.
      $this->currentCategory['id'] = $this->currentCategory['id']?$this->currentCategory['id']:0;
      
        if (MG::getSetting('productInSubcat')=='true') {       
          $filter = '((p.cat_id IN (' .DB::quote( implode(',', $this->categoryId),1) . ') '
          . 'or FIND_IN_SET(' . DB::quote($this->currentCategory['id'],1) . ',p.`inside_cat`)))';
        } else {
          $filter = '((c.id IN (' . DB::quote($this->currentCategory['id'],1) . ') '
          . 'or FIND_IN_SET(' .  DB::quote($this->currentCategory['id'],1)  . ',p.`inside_cat`)))';
        }  
      
        if ($mgadmin) {           
          $filter = ' (p.cat_id IN (' .DB::quote( implode(',', $this->categoryId),1) . ') '
          . 'or FIND_IN_SET(' .  DB::quote($this->currentCategory['id'],1)  . ',p.`inside_cat`))';
          
          if($this->currentCategory['id'] == 0) {
            $filter = ' 1=1 ';
          }
        }  
      // Запрос вернет общее кол-во продуктов в выбранной категории.
      if ($onlyActive) {
        $filter .= ' AND p.activity = 1';
      }
      if (MG::getSetting('printProdNullRem') == "true" && !$mgadmin) {

        if(MG::enabledStorage()) {
          $filter .= ' AND ((SELECT SUM(ABS(count)) FROM '.PREFIX.'product_on_storage WHERE product_id = p.id) != 0)';
        } else {

          $filter .= " AND (temp.`varcount` > 0 OR temp.`varcount` < 0"
            . " OR p.count>0 OR p.count<0)";
        }
      }
      $sql .=' WHERE  ' . $filter;
   
    $orderBy = ' ORDER BY `sort` DESC ';
    if(MG::getSetting('filterSort') && !$mgadmin ) {
      $parts = !empty($_SESSION['filters']) ? explode('|',$_SESSION['filters']) : explode('|',MG::getSetting('filterSort'));     
      if (!empty($_SESSION['filters'])) {
        $parts[1] = intval($parts[1]) > 0 ? "DESC" : "ASC"; 
      }      
      $parts[0] = $parts[0]=='count' ? 'count_sort' : $parts[0];
      $orderBy = ' ORDER BY `'.DB::quote($parts[0],1).'` '.DB::quote($parts[1],1);      
    }
    $sql .= ' GROUP BY p.id '.$orderBy;

    // в админке не используем кэш
    if (!$mgadmin) {
      $result = Storage::get(md5($sql.$page.LANG.$_SESSION['userCurrency']));
    }
    
    if ($result == null) {
      // узнаем количество товаров для построения навигатора
      $res = DB::query("SELECT count(distinct p.id) AS count
        FROM ".PREFIX."product p
        LEFT JOIN ".PREFIX."category c
          ON c.id = p.cat_id
        LEFT JOIN ".PREFIX."product_variant pv
          ON p.id = pv.product_id
        LEFT JOIN (
          SELECT pv.product_id, SUM(IF(pv.count <0, 1000000, pv.count)) AS varcount
          FROM  ".PREFIX."product_variant AS pv
          GROUP BY pv.product_id
        ) AS temp ON p.id = temp.product_id WHERE ". $filter);
      $maxCount = DB::fetchAssoc($res);
      //определяем класс  
      $navigator = new Navigator($sql, $page, $countRows, 6, false, 'page', $maxCount['count']); 
      
      $this->products = $navigator->getRowsSql();

      // добавим к полученным товарам их свойства
      $this->products = $this->addPropertyToProduct($this->products, $mgadmin);   
      
      foreach ($this->products as &$item) {
        MG::loadLocaleData($item['id'], LANG, 'product', $item);
        if (!isset($item['category_unit'])) {
          $item['category_unit'] = 'шт.';
        }
        if (isset($item['product_unit']) && $item['product_unit'] != null && strlen($item['product_unit']) > 0) {
          $item['category_unit'] = $item['product_unit'];
        }
      }

      if ($mgadmin) {
        $this->pager = $navigator->getPager('forAjax');
      } else {
        $this->pager = $navigator->getPager();
      }

      $result = array('catalogItems' => $this->products, 'pager' => $this->pager, 'totalCountItems' => $navigator->getNumRowsSql());
      // в админке не используем кэш
      if (!$mgadmin) {
        Storage::save(md5($sql.$page.LANG.$_SESSION['userCurrency']), array('catalogItems' => $this->products, 'pager' => $this->pager, 'totalCountItems' => $navigator->getNumRowsSql()));
      }
    }

    if (!empty($filterProduct['filterBarHtml'])) {
      $result['filterBarHtml'] = $filterProduct['filterBarHtml'];
    }

    $args = func_get_args();

    return MG::createHook(__CLASS__ . "_" . __FUNCTION__, $result, $args);
  }

  /**
   * Получает список продуктов в соответствии с выбранными параметрами фильтра.
   * <code>
   * $catalog = new Models_Catalog;
   * $result = $catalog->getListByUserFilter(20, ' p.cat_id IN  (1,2,3)');
   * viewData($result);
   * </code>
   * @param int $countRows количество записей.
   * @param string $userfilter пользовательская составляющая для запроса.
   * @param bool $mgadmin админка.
   * @param bool $noCache не использовать кэш.
   * @return array
   */
  public function getListByUserFilter($countRows = 20, $userfilter, $mgadmin = false, $noCache = false) {
    if(!URL::isSection('mg-admin') || $noCache) $cache = Storage::get('catalog-'.md5(URL::getUri()));
    if(!$cache) {
      // Вычисляет общее количество продуктов.
      $page = URL::get("page");
      // в запросе меняем условие по количеству товаров в таблице product
      // затем добавляем условие по количеству вариантов и товаров
      $having = '';
      if (stristr($userfilter, 'AND (p.count>0 OR p.count<0)')!==FALSE) {
        $userfilter = str_replace('AND (p.count>0 OR p.count<0)', ' ', $userfilter);

        if(MG::enabledStorage()) {
          $having = 'HAVING (SUM(IFNULL(ABS((SELECT SUM(count) FROM '.PREFIX.'product_on_storage WHERE product_id = pv.id)), 0)
           + ABS((SELECT SUM(count) FROM '.PREFIX.'product_on_storage WHERE product_id = p.id))) > 0)';
        } else {
          $having = 'HAVING (SUM( IFNULL( ABS( pv.`count` ) , 0 ) + ABS( p.`count` ) ) >0)';

        }
      }   
      if($_REQUEST['sale']) {
        $userfilter = ' p.old_price != 0 AND '.$userfilter;
      }
      // Запрос вернет общее кол-во продуктов в выбранной категории.
      $sql = '
        SELECT DISTINCT p.id, CONCAT(c.parent_url,c.url) AS category_url, c.unit AS category_unit, p.unit AS product_unit,
          p.url AS product_url, p.*, pv.product_id AS variant_exist, rate,
          (p.price_course + p.price_course * (IFNULL(rate,0))) AS `price_course`,
          IF(p.count <0, 1000000, 
            IF(varcount, 
              IF(p.count<varcount, varcount, p.count), 
            p.count)
          ) AS  `count_sort`, p.currency_iso,
          IF(IFNULL(c.url, "") = "" AND p.cat_id <> 0, -10, p.cat_id) AS cat_id
        FROM `' . PREFIX . 'product` p
        LEFT JOIN `' . PREFIX . 'category` c
          ON c.id = p.cat_id
        LEFT JOIN `' . PREFIX . 'product_variant` pv
          ON p.id = pv.product_id
        LEFT JOIN (
          SELECT pv.product_id, SUM(IF(pv.count <0, 1000000, pv.count)) AS varcount
          FROM  `' . PREFIX . 'product_variant` AS pv
          GROUP BY pv.product_id
        ) AS temp ON p.id = temp.product_id 
       WHERE  '.str_replace('ORDER BY', ' GROUP BY p.id '.$having.' ORDER BY', $userfilter);

      $sql = str_replace('ORDER BY `count`', 'ORDER BY `count_sort`', $sql);

      $userfilterCount = explode('ORDER BY', $userfilter);
      $userfilterCount = $userfilterCount[0];

      $res = DB::query('SELECT COUNT(DISTINCT p.id) AS count
        FROM `' . PREFIX . 'product` p
        LEFT JOIN `' . PREFIX . 'category` c
          ON c.id = p.cat_id
        LEFT JOIN `' . PREFIX . 'product_variant` pv
          ON p.id = pv.product_id
        LEFT JOIN (
          SELECT pv.product_id, SUM(IF(pv.count <0, 1000000, pv.count)) AS varcount
          FROM  `' . PREFIX . 'product_variant` AS pv
          GROUP BY pv.product_id
        ) AS temp ON p.id = temp.product_id 
       WHERE '.str_replace('ORDER BY', ' '.$having.' ORDER BY', $userfilterCount));
      $count = DB::fetchAssoc($res);

      $navigator = new Navigator($sql, $page, $countRows, 6, false, 'page', $count['count']); //определяем класс.
      $this->products = $navigator->getRowsSql();
      // 
      if ($mgadmin) {
        $this->pager = $navigator->getPager('forAjax');
      } else {
        $this->pager = $navigator->getPager();
      }
      // 
      // добавим к полученным товарам их свойства
      $this->products = $this->addPropertyToProduct($this->products, $mgadmin);

      foreach ($this->products as &$item) {
        MG::loadLocaleData($item['id'], LANG, 'product', $item);
        if (!isset($item['category_unit'])) {
          $item['category_unit'] = 'шт.';
        }
        if (isset($item['product_unit']) && $item['product_unit'] != null && strlen($item['product_unit']) > 0) {
          $item['category_unit'] = $item['product_unit'];
        }
      }
      // 
      $data['products'] = $this->products;
      $data['count'] = $productCount = $navigator->getNumRowsSql();
      $data['pager'] = $this->pager;
      if(!URL::isSection('mg-admin') && MG::get('controller')!="controllers_compare" && $noCache) Storage::save('catalog-'.md5(URL::getUri()), $data);
    } else {
      $this->products = $cache['products'];
      $productCount = $cache['count'];
      $this->pager = $cache['pager'];
    }  

    // добавляем к товарам со складов инфу, если надо
    $ids = array();
    foreach ($this->products as $value) {
      $ids[] = $value['id'];
    }
    if(MG::enabledStorage()) {
      $res = DB::query('SELECT SUM(count), product_id FROM '.PREFIX.'product_on_storage WHERE product_id IN ('.DB::quoteIN($ids).')');
      while($row = DB::fetchAssoc($res)) {
        $data[$row['product_id']] = $row['SUM(count)'];
      }
    } else {
      $res = DB::query('SELECT SUM(IFNULL(pv.`count`,0) + p.`count`) AS count, p.id FROM '.PREFIX.'product AS p
        LEFT JOIN '.PREFIX.'product_variant AS pv ON pv.product_id = p.id WHERE p.id IN ('.DB::quoteIN($ids).') GROUP BY p.id');
      while($row = DB::fetchAssoc($res)) {
        $data[$row['id']] = $row['count'];
      }
    }
    foreach ($this->products as $key => $value) {
      $this->products[$key]['count'] = empty($data[$value['id']])?0:$data[$value['id']];
    }

    $result = array('catalogItems' => $this->products, 'pager' => $this->pager, 'totalCountItems' => $productCount);

    $args = func_get_args();
    return MG::createHook(__CLASS__ . "_" . __FUNCTION__, $result, $args);
  }

  /**
   * Возвращает список найденных продуктов соответствующих поисковой фразе.
   * <code>
   * $catalog = new Models_Catalog();
   * $items = $catalog->getListProductByKeyWord('Nike', true, true);
   * viewData($items);
   * </code>
   * @param string $keyword поисковая фраза.
   * @param string $allRows получить сразу все записи.
   * @param string $onlyActive учитывать только активные продукты.
   * @param bool $adminPanel запрос из публичной части или админки.
   * @param bool $mode (не используеться)
   * @param bool|int $forcedPage номер страницы использующийся вместо url
   * @param int $searchCats поиск в категории (оставить пустым если не надо искать)
   * @return array
   */
  public function getListProductByKeyWord($keyword, $allRows = false, $onlyActive = false, $adminPanel = false, $mode = false, $forcedPage = false, $searchCats = -1) {

    $result = array(
      'catalogItems' => array(),
      'pager' => null,
      'numRows' => null
    );

    $keyword = htmlspecialchars($keyword);
    $keywordUnTrim = $keyword;
    $keyword = trim($keyword);

    //if (empty($keyword) || mb_strlen($keyword, 'UTF-8') <= 2) {
    //  return $result;
   // }
    $currencyRate = MG::getSetting('currencyRate');
    $currencyShopIso = MG::getSetting('currencyShopIso');
    // Поиск по точному соответствию.
    // Пример $keyword = " 'красный',   зеленый "
    // Убираем начальные пробелы и конечные.
    $keyword = trim($keyword); //$keyword = "'красный',   зеленый"

		
	if (MG::getSetting('searchType') == 'sphinx') {
		// подключаем библиотеку для поискового движка
		require_once ( "sphinxapi.php" );		
	    $cl = new SphinxClient();
	    $cl->SetServer( MG::getSetting('searchSphinxHost'), MG::getSetting('searchSphinxPort') );
	    $cl->SetConnectTimeout(1); 
	    $cl->SetMaxQueryTime(1000);
	    $cl->SetMatchMode(SPH_MATCH_ALL);

		$matches = array();
		// поиск по индексам товаров и вариантов
	    $resultSphinx = $cl->Query($keyword, 'product');
        $matches = isset($resultSphinx['matches'])?$resultSphinx['matches']:array();
		// поиск по индексам характеристик
		$resultSphinx2 = $cl->Query($keyword, 'property');
		$matches = isset($resultSphinx2['matches'])? ($matches+$resultSphinx2['matches']):$matches;

	    if ( $resultSphinx === false ) {
	     if( $cl->GetLastWarning() ) { 
	      echo 'WARNING: '.$cl->GetLastWarning();
	      exit;
	     }
	     exit('Невозможно установить соединение с поисковым движком Shinx, пожалуйста, обратитесь к администратору.');
	    }

	    foreach ($matches AS $key => $row) {
        	$idsArr[] = intval($key);
    	}

        $idsProductSphinx = join(',', $idsArr);


	} else {

	    if (MG::getSetting('searchType') == 'fulltext') {
	      // Вырезаем спец символы из поисковой фразы.
	      $keyword = preg_replace('/[`~!#$%^*()=+\\\\|\\/\\[\\]{};:"\',<>?]+/', '', $keyword); //$keyword = "красный   зеленый"
	      // Замена повторяющихся пробелов на на один.
	      $keyword = preg_replace('/ +/', ' ', $keyword); //$keyword = "красный зеленый"
	      // Обрамляем каждое слово в звездочки, для расширенного поиска.
	      $keyword = str_replace(' ', '* +', $keyword); //$keyword = "красный* *зеленый"
	      // Добавляем по краям звездочки.
	      $keyword = '+' . $keyword . '*'; //$keyword = "*красный* *зеленый*"

	      $sql = " 
	      SELECT distinct p.code, CONCAT(c.parent_url,c.url) AS category_url, c.unit as category_unit, p.unit as product_unit,
	        p.url AS product_url, p.*, pv.product_id as variant_exist, pv.id as variant_id, rate,(p.price_course + p.price_course * (IFNULL(rate,0))) as `price_course`
	      FROM  `" . PREFIX . "product` AS p
	      LEFT JOIN  `" . PREFIX . "category` AS c ON c.id = p.cat_id
	      LEFT JOIN  `" . PREFIX . "product_variant` AS pv ON p.id = pv.product_id";

	      if (!$adminPanel) {
	        $sql .=" LEFT JOIN (
	        SELECT pv.product_id, SUM( pv.count ) AS varcount
	        FROM  `" . PREFIX . "product_variant` AS pv
	        GROUP BY pv.product_id
	      ) AS temp ON p.id = temp.product_id";
	      }

	      $prod = new Models_Product();
	      $fulltext = "";
	      $sql .= " WHERE ";
	      $match =
	      " MATCH (
	      p.`title` , p.`code`, p.`description` " . $fulltextInVar . " " . $fulltext . "
	      )
	      AGAINST (
	      '" . $keyword . "'
	      IN BOOLEAN
	      MODE
	      ) ";

	      DB::query("SELECT id FROM `" . PREFIX . "product_variant` LIMIT 1");

	      //Если есть варианты товаров то будет искать и в них.
	      if (DB::numRows(DB::query("SELECT id FROM `" . PREFIX . "product_variant` LIMIT 1"))) {
	        $fulltextInVar = ', pv.`title_variant`, pv.`code` ';

	      $match = "(".$match.
	        " OR MATCH (pv.`title_variant`, pv.`code`)
	        AGAINST (
	        '" . $keyword . "'
	        IN BOOLEAN
	        MODE
	        )) ";
	      }

	    $sql .= $match;
	      // Проверяем чтобы в вариантах была хотябы одна единица.
	      if (!$adminPanel) {
	      if (MG::getSetting('printProdNullRem') == "true") {
	          $sql .=" AND (temp.`varcount` > 0 OR temp.`varcount` < 0 OR p.count>0 OR p.count<0)";
	    }
	    if(MG::getSetting('showVariantNull')=='false') {
	        $sql .= ' AND (pv.`count` != 0 OR pv.`count` IS NULL) '; 
	      }
	      }

	      if ($onlyActive) {
	        $sql .= ' AND p.`activity` = 1';
	      }
        if ($searchCats > -1) {
          $sql .= ' AND c.`id` = '.DB::quoteInt($searchCats);
        }
	    } else {

	      $sql = "
	       SELECT distinct p.id, CONCAT(c.parent_url,c.url) AS category_url, c.unit as category_unit, p.unit as product_unit,
	         p.url AS product_url, p.*, pv.product_id as variant_exist, pv.id as variant_id, rate,(p.price_course + p.price_course * (IFNULL(rate,0))) as `price_course`, 
	         p.currency_iso
	       FROM  `" . PREFIX . "product` AS p
	       LEFT JOIN  `" . PREFIX . "category` AS c ON c.id = p.cat_id
	       LEFT JOIN  `" . PREFIX . "product_variant` AS pv ON p.id = pv.product_id";

	      if (!$adminPanel) {
	        $sql .=" LEFT JOIN (
	         SELECT pv.product_id, SUM( pv.count ) AS varcount
	         FROM  `" . PREFIX . "product_variant` AS pv
	         GROUP BY pv.product_id
	       ) AS temp ON p.id = temp.product_id";
	      }

	      $prod = new Models_Product();
	      $fulltext = "";

	      $keywords = explode(" ", $keyword);
	      // foreach($keywords as $key=>$s) {
	      //   if(strlen($s)<3) unset($keywords[$key]);
	      // }
	      $keyword = "%".implode('%%', $keywords)."%";

	      //Если есть варианты товаров то будеи искать и в них.
	      if (DB::numRows(DB::query("SELECT id FROM `" . PREFIX . "product_variant` LIMIT 1"))) {

	        $fulltextInVar = " OR
	             pv.`title_variant` LIKE '%" . DB::quote($keyword, true) . "%'
	           OR
	             pv.`code` LIKE '%" . DB::quote($keyword, true) . "%'";
	      }


	      $sql .=
	        " WHERE (
	             p.`title` LIKE '%" . DB::quote($keyword, true) . "%'
	           OR
	             p.`code` LIKE '%" . DB::quote($keyword, true) . "%'
	        " . $fulltextInVar .')';


	      // Проверяем чтобы в вариантах была хотябы одна единица.
	      if (!$adminPanel) {
  	      if (MG::getSetting('printProdNullRem') == "true") {
            if(MG::enabledStorage()) {
              $sql .= ' AND ((SELECT SUM(count) FROM '.PREFIX.'product_on_storage WHERE product_id = p.id) > 0)';
            } else {
              $sql .=" AND (temp.`varcount` > 0 OR temp.`varcount` < 0 OR p.count>0 OR p.count<0)";
            }
  	      }
  	      if(MG::getSetting('showVariantNull')=='false') {
  	        $sql .= ' AND (pv.`count` != 0 OR pv.`count` IS NULL)'; 
  	      }
	      }

	      if ($onlyActive) {
          $sql .= ' AND p.`activity` = 1';
        }

        if ($searchCats > -1) {
	        $sql .= ' AND c.`id` = '.DB::quoteInt($searchCats);
	      }

	    }

	}


	if(!empty($idsProductSphinx)) {
		$sql = "SELECT distinct p.id, CONCAT(c.parent_url,c.url) AS category_url,
	         p.url AS product_url, p.*, pv.product_id as variant_exist, pv.id as variant_id, rate,(p.price_course + p.price_course * (IFNULL(rate,0))) as `price_course`, 
	         p.currency_iso
	       FROM  `" . PREFIX . "product` AS p
	       LEFT JOIN  `" . PREFIX . "category` AS c ON c.id = p.cat_id
	       LEFT JOIN  `" . PREFIX . "product_variant` AS pv ON p.id = pv.product_id
	       WHERE p.id IN(".$idsProductSphinx.")";

	}
	if(empty($sql)) {return  $result;}

    $page = URL::get("page");
    $settings = MG::get('settings');

    if ($forcedPage) {
      $page = $forcedPage;
    }

    //if ($mode=='groupBy') {
      $sql .= ' GROUP BY p.id' ;
    //}
    if ($allRows) {
      $sql .= ' LIMIT 15' ;
    }

    if ($adminPanel) {
      // $allRows = true;
      $settings['countСatalogProduct'] = $settings['countPrintRowsProduct'];
    }

    if(!$settings['countСatalogProduct']) {
       $settings['countСatalogProduct'] = 10;
    }

    $navigator = new Navigator($sql, $page, $settings['countСatalogProduct'], $linkCount = 6, $allRows); // Определяем класс.

    $this->products = $navigator->getRowsSql();

    // добавим к полученым товарам их свойства
    $this->products = $this->addPropertyToProduct($this->products, $adminPanel, false);

    $useStorages = MG::enabledStorage();

    foreach ($this->products as &$pitem) {
      MG::loadLocaleData($pitem['id'], LANG, 'product', $pitem);

      if (!isset($pitem['category_unit'])) {
        $pitem['category_unit'] = 'шт.';
      }
      if (isset($pitem['product_unit']) && $pitem['product_unit'] != null && strlen($pitem['product_unit']) > 0) {
        $pitem['category_unit'] = $pitem['product_unit'];
      }

      if ($useStorages) {
        if (!empty($pitem['variants'])) {
          foreach ($pitem['variants'] as $pkey => $pvalue) {
            $pitem['variants'][$pkey]['count'] = MG::getProductCountOnStorage(0, $pitem['id'], $pitem['variants'][$pkey]['id'], 'all');
          }
        }
        else{
          $pitem['count'] = MG::getProductCountOnStorage(0, $pitem['id'], 0, 'all');
        }
      }
    }

    $this->pager = $navigator->getPager();
 
    $result = array(
      'catalogItems' => $this->products,
      'pager' => $this->pager,
      'numRows' => $navigator->getNumRowsSql()
    );

    if (count($result['catalogItems']) > 0) {

      // упорядочивание списка найденных  продуктов
      // первыми в списке будут стоять те товары, у которых полностью совпала поисковая фраза
      // затем будут слова в начале которых встретилось совпадение
      // в конце слова в середине которых встретилось совпадение
      $keyword = str_replace('*', '', $keyword);
      $resultTemp = $result['catalogItems'];
      $prioritet0 = array();
      $prioritet1 = array();
      $prioritet2 = array();
      foreach ($resultTemp as $key => $item) {
        $title = mb_convert_case($item['title'], MB_CASE_LOWER, "UTF-8");
        $keyword = mb_convert_case($keyword, MB_CASE_LOWER, "UTF-8");
        $item['image_url'] = mgImageProductPath($item["image_url"], $item['id']);
        
        if (trim($title) == $keyword) {
        $prioritet0[] = $item;
          continue;
        }

        if (strpos($title, $keyword) === 0) {
            $prioritet1[] = $item;
          } else {
            $prioritet2[] = $item;
          }
        }

      $result['catalogItems'] = array_merge($prioritet0,  $prioritet1,$prioritet2);
    }

    $args = func_get_args();
    return MG::createHook(__CLASS__ . "_" . __FUNCTION__, $result, $args);
  }

  /**
   * Записывает построчно CSV выгрузку в файл data_csv_m_d_Y.csv в корневую папку сайта.
   * <code>
   * $model = new Models_Product;
   * $product = $model->getProduct(5);
   * $line1 = Models_Catalog::addToCsvLine($product);
   * $product = $model->getProduct(6);
   * $line2 = Models_Catalog::addToCsvLine($product);
   * $csvText = array($line1, $line2);
   * Models_Catalog::rowCsvPrintToFile($csvText);
   * <code>
   * @param array $csvText массив с csv строками.
   * @param bool $new записывать в конец файла.
   * @return void
   */
  public function rowCsvPrintToFile($csvText, $new = false) {
    foreach ($csvText as &$item) {
      $item = mb_convert_encoding($item, "WINDOWS-1251", "UTF-8");
    }
    // 
    $date = date('m_d_Y');

    if($new) {      
      $fp = fopen('data_csv_'.$date.'.csv', 'w');
    } else {      
      $fp = fopen('data_csv_'.$date.'.csv', 'a');
    }

    //fputs($fp, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM
    fputcsv($fp, $csvText, ';');
    fclose($fp);
  }

   /**
   * Выгружает содержание всего каталога в CSV файл.
   * <code>
   * $listProductId = array(1, 2, 5, 25);
   * $catalog = new Models_Catalog();
   * $result = $catalog->exportToCsv($listProductId);
   * viewData($result);
   * </code>
   * @param array $listProductId массив id товаров
   * @return array
   */
  public function exportToCsv($listProductId = array()) {
    if(@set_time_limit(100)) {
      $maxExecTime = 100;
      $items2page = 100;
      $timeMargin = 20;
    } else {
      $maxExecTime = min(30, @ini_get("max_execution_time"));
      $items2page = 10;
      $timeMargin = 10;
    }     
        
    $startTime = microtime(true);
    $startPage = (URL::getQueryParametr('page')) ? URL::getQueryParametr('page') : 1;
    
    header("Content-Type: application/force-download");
    header("Content-Type: application/octet-stream;");
    header("Content-Type: application/download");
    header("Content-Disposition: attachment;filename=data.csv");
    header("Content-Transfer-Encoding: binary ");

    $propNameToCsv = Property::getEasyPropNameToCsv($listProductId);

    $csvText = array();        
    
    if($startPage == 1) {
      $csvText = array("ID товара","Артикул","Категория","URL категории","Товар","Вариант","Краткое описание","Описание","Цена","Старая цена","URL товара","Изображение","Количество","Активность","Заголовок [SEO]","Ключевые слова [SEO]","Описание [SEO]","Рекомендуемый","Новый","Сортировка","Вес","Связанные артикулы","Смежные категории","Ссылка на товар","Валюта","Единицы измерения");
      
      // добавляем заголовки для оптовых цен
      $wholesalesGroup = unserialize(stripslashes(MG::getSetting('wholesalesGroup')));
      foreach ($wholesalesGroup as $key => $value) {
        $res = DB::query('SELECT DISTINCT count FROM '.PREFIX.'wholesales_sys WHERE `group` = '.DB::quoteInt($value));
        while ($row = DB::fetchAssoc($res)) {
          $_SESSION['export']['wholeColumns'][] = $row['count'].'/'.$value;
          $csvText[] = 'Количество от '.$row['count'].' для цены '.$value.' [оптовая цена]';
        }
      }

      if(MG::enabledStorage()) {
        unset($_SESSION['export']['storageColumns']);
        $storages = unserialize(stripcslashes(MG::getSetting('storages')));
        foreach ($storages as $item) {
          $csvText[] = $item['name'].' [склад='.$item['id'].']';
          $_SESSION['export']['storageColumns'][] = $item['id'];
        } 
      }

      foreach ($propNameToCsv as $item) {
        $csvText[] = $item;
      }
      $csvText[] = "Сложные характеристики";
      $this->rowCsvPrintToFile($csvText, true);
    }
        
    $product = new Models_Product();
    $catalog = new Models_Catalog();

    Storage::$noCache = true;
    $page = 1;
    // получаем максимальное количество заказов, если выгрузка всего ассортимента
    if(empty($listProductId)) {
      $maxCountPage = ceil($product->getProductsCount() / $items2page);    
    } else {
      $maxCountPage = ceil(count($listProductId) / $items2page);    
    }    
    $catalog->categoryId = MG::get('category')->getCategoryList(0);
    $catalog->categoryId[] = 0;
    $listId = implode(',', $listProductId);
    
    for ($page = $startPage; $page <= $maxCountPage; $page++) { 
      URL::setQueryParametr("page", $page);            
      
      if(empty($listProductId)) {    
        $catalog->getList($items2page, true);
      } else {   
        $catalog->getListByUserFilter($items2page, ' p.id IN  ('.DB::quote($listId,1).')');        
      }      
           
      $rowCount = (empty($_POST['rowCount'])) ? 0 : $_POST['rowCount'];
      unset($_POST['rowCount']);                  
      
      foreach ($catalog->products as $cell=>$row) {  
        if($cell < $rowCount) {
          continue;
        }
        
        $csvText = array();
        $parent = $row['category_url'];

        // Подставляем всесто URL названия разделов.
        $resultPath = '';
        $resultPathUrl = '';
        while ($parent) {
          $url = URL::parsePageUrl($parent);
          $parent = URL::parseParentUrl($parent);
          $parent = $parent != '/' ? $parent : '';
          $alreadyParentCat = MG::get('category')->getCategoryByUrl(
            $url, $parent
          );

          $resultPath = $alreadyParentCat['title'] . '/' . $resultPath;
          $resultPathUrl = $alreadyParentCat['url'] . '/' . $resultPathUrl;
        }

        $resultPath = trim($resultPath, '/');
        $resultPathUrl = trim($resultPathUrl, '/');

        $variants = $product->getVariants($row['id']);

        if(!empty($variants)) {
          foreach ($variants as $key => $variant) {
            foreach ($variant as $k => $v) {
              if($k != 'sort' && $k != 'id') {
                $row[$k] = $v;
              }
            }
            $row['image'] = $variant['image'];
            $row['category_url'] = $resultPath;
            $row['category_full_url'] = $resultPathUrl;
            $row['real_price'] = $row['price'];
            $csvText = $this->addToCsvLine($row, 1);
            $this->rowCsvPrintToFile($csvText); 
          }
        } else {
          $row['category_url'] = $resultPath;
          $row['category_full_url'] = $resultPathUrl;
          $csvText = $this->addToCsvLine($row);
          $this->rowCsvPrintToFile($csvText); 
        }

        $rowCount++;        
        $execTime = microtime(true) - $startTime;        

        if($execTime+$timeMargin >= $maxExecTime) {                  
          $data = array(
            'success' => false,
            'nextPage' => $page,
            'rowCount' =>$rowCount,
            'percent' => round(($page / $maxCountPage) * 100)
          );
          echo json_encode($data);
          exit();
        }                
      }      
    }    
    
    $date = date('m_d_Y');

    unset($_SESSION['export']);
    
    if(empty($listProductId)) {
      $data = array(
        'success' => true,
        'file' => 'data_csv_'.$date.'.csv'
      );
      echo json_encode($data);
      exit();
    }
    
    return 'data_csv_'.$date.'.csv';
  }

  /**
   * Добавляет продукт в CSV выгрузку.
   * <code>
   * $model = new Models_Product;
   * $product = $model->getProduct(5);
   * echo Models_Catalog::addToCsvLine($product);
   * </code>
   * @param array $row - продукт.
   * @param bool $variant - есть ли варианты этого продукта.
   * @return string
   */
  public function addToCsvLine($row, $variant = false) {    
    // конвертируем старую цену
    $curSetting = MG::getSetting('currencyRate');     
    if($row['currency_iso'] != 'RUR') {
      $row['old_price'] /= $curSetting[$row['currency_iso']];
    }    
    
    $row['price'] = str_replace(".", "," ,$row['price']);

    $row['image_url'] = '';
    if(!empty($row['images_product'])) {
      foreach ($row['images_product'] as $key => $url ) {
        $param = '';
        if (!empty($row['images_alt'][$key])||!empty($row['images_title'][$key])) {
          $param = '[:param:][alt='.(!empty($row['images_alt'][$key]) ? $row['images_alt'][$key] : '').'][title='.(!empty($row['images_title'][$key]) ? $row['images_title'][$key] : '').']';
        }
        $row['image_url'] .= basename($url).$param.'|';
      }
      $row['image_url'] = substr($row['image_url'], 0, -1);
     //   $row['image_url'] = implode('|',$row['images_product']);
    }

    $row['meta_title'] = htmlspecialchars_decode($row['meta_title']);
    $row['meta_keywords'] = htmlspecialchars_decode($row['meta_keywords']);
    $row['meta_desc'] = htmlspecialchars_decode($row['meta_desc']);
    $row['old_price'] = ($row['old_price']!='"0"')?str_replace(".", "," ,$row['old_price']):'';
    $row['description'] = str_replace("\r", "", $row['description']);
    $row['description'] = str_replace("\n", "", $row['description']);
    $row['meta_desc'] = str_replace("\r", "", $row['meta_desc']);
    $row['meta_desc'] = str_replace("\n", "", $row['meta_desc']);
    $row['weight'] = str_replace(".", "," ,$row['weight']);
    $row['image_url'] = $row['image_url'];
    // получаем строку со связанными продуктами
    // формируем строку с характеристиками
    $row['property'] = Property::getHardPropToCsv($row['id']);
    $row['property'] = str_replace("\r", "", $row['property']);
    $row['property'] = str_replace("\n", "", $row['property']);

    foreach ($row as $key => $value) {
      $row[$key] = str_replace("\n", "", $value);
    }

    if(MG::enabledStorage()) {
      foreach ($_SESSION['export']['storageColumns'] as $item) {
        $variant = 0;
        foreach ($row['variants'] as $var) {
          if($var['title_variant'] == $row['title_variant']) {
            $variant = $var['id'];
          }
        }
        $res = DB::query('SELECT count, storage FROM '.PREFIX.'product_on_storage WHERE 
          product_id = '.DB::quoteInt($row['id']).' AND variant_id = '.DB::quoteInt($variant).'
          AND storage = '.DB::quote($item));
        while ($subRow = DB::fetchAssoc($res)) {
          $result = $subRow['count'];
        }
        if(empty($result)) $result = 0;
        $row['storages'][] = $result;
      }
    }

    $row['wholesales'] = MG::getWholesalesToCSV($row['id'], $row);

    $row['easy_prop'] = Property::getEasyPropToCsv($row['id'], $row['color'], $row['size']);

    if ($variant) { 
      $var_image = '[:param:][src='.$row['image'].']';
      $row['title_variant'] .= $var_image;
      $variantsCol = str_replace("\"", "\"\"", htmlspecialchars_decode($row['title_variant']));
    } else {
      $variantsCol = "";
    }

    $csvText = array($row['id'], $row['code'], $row['category_url'], $row['category_full_url'], $row['title'], $variantsCol, $row['short_description'], 
      $row['description'], $row['price'], $row['old_price'], $row['url'], $row['image_url'], $row['count'], $row['activity'], $row['meta_title'], 
      $row['meta_keywords'], $row['meta_desc'], $row['recommend'], $row['new'], $row['sort'], $row['weight'], $row['related'], $row['inside_cat'], 
      $row['link_electro'], $row['currency_iso'], $row['category_unit']);

    foreach ($row['wholesales'] as $item) {
      $csvText[] = $item;
    }
    foreach ($row['storages'] as $item) {
      $csvText[] = $item;
    }

    foreach ($row['easy_prop'] as $item) {
      $csvText[] = $item;
    }
    $csvText[] = $row['property'];

    return $csvText;
  }

  /**
   * Получает массив всех категорий магазина.
   * <code>
   * $catalog = new Models_Catalog();
   * $categoryArray = $catalog->getCategoryArray();
   * viewData($categoryArray);
   * </code>
   * @return array - ассоциативный массив id => категория.
   */
  public function getCategoryArray() {
    $res = DB::query('
      SELECT *
      FROM `' . PREFIX . 'category`');
    while ($row = DB::fetchAssoc($res)) {
      $result[$row['id']] = $row;
    }
    return $result;
  }

  /**
   * Получает минимальную цену из всех стоимостей товаров (варианты тоаров не учитываются).
   * <code>
   * echo Models_Catalog::getMinPrice();
   * </code>
   * @return float
   */
  public function getMinPrice() {
    $res = DB::query('SELECT MIN(`price_course`) as price FROM `' . PREFIX . 'product`');
    if ($row = DB::fetchObject($res)) {
      $result = $row->price;
    }
    return $result;
  }

  /**
   * Получает максимальную цену из всех стоимостей товаров (варианты тоаров не учитываются).
   * <code>
   * echo Models_Catalog::getMaxPrice();
   * </code>
   * @return float
   */
  public function getMaxPrice() {
    $res = DB::query('SELECT MAX(`price_course`) as price FROM `' . PREFIX . 'product`');
    if ($row = DB::fetchObject($res)) {
      $result = $row->price;
    }
    return $result;
  }
  /**
   * Возвращает пример загружаемого файла, содержащего информацию о категориях.
   * <code>
   * Models_Catalog::getExampleCategoryCSV();
   * </code>
   */
  public function getExampleCategoryCSV() {
    header("Content-Type: application/force-download");
    header("Content-Type: application/octet-stream;");
    header("Content-Type: application/download");
    header("Content-Disposition: attachment;filename=data.csv");
    header("Content-Transfer-Encoding: binary ");
    
    $csvText = '"Название категории";"URL категории";"id родительской категории";"URL родительской категории";"Описание категории";"Изображение";"Заголовок [SEO]";"Ключевые слова [SEO]";"Описание [SEO]";"SEO Описание";"Наценка";"Не выводить в меню";"Активность";"Не выгружать в YML";"Сортировка";"Внешний идентификатор";"ID категории";
"Программное обеспечение";"programmnoe-obespechenie";0;"";"";"";"";"";"";;0;0;1;1;"1";;1;
"Комплектующие";"komplektuyuschie";0;"";"";"";"";"";"";;0;0;1;1;"2";;2;
"HDD";"hdd";2;"komplektuyuschie/";"";"";"";"";"";;0;0;1;1;"3";;3;
"Процессоры";"protsessory";2;"komplektuyuschie/";"";"";"";"";"";;0;0;1;1;"12";;12;
"Видеокарты";"videokarty";2;"komplektuyuschie/";"";"";"";"";"";;0;0;1;1;"23";;23;
"Сетевое оборудование";"setevoe-oborudovanie";0;"";"";"";"";"";"";;0;0;1;1;"4";;4;
"Wifi и Bluetooth";"wifi-i-bluetooth";4;"setevoe-oborudovanie/";"";"";"";"";"";;0;0;1;1;"5";;5;
"Оргтехника";"orgtehnika";0;"";"";"";"";"";"";;0;0;1;1;"6";;6;
"Сканеры";"skanery";6;"orgtehnika/";"";"";"";"";"";;0;0;1;1;"7";;7;
"Принтеры и МФУ";"printery-i-mfu";6;"orgtehnika/";"";"";"";"";"";;0;0;1;1;"10";;10;
"3D принтеры";"3d-printery";6;"orgtehnika/";"";"";"";"";"";;0;0;1;1;"11";;11;
"Периферийные устройства";"periferiynye-ustroystva";0;"";"";"";"";"";"";;0;0;1;1;"8";;8;
"Комп. акустика";"komp.-akustika";8;"periferiynye-ustroystva/";"";"";"";"";"";;0;0;1;1;"9";;9;
"Мониторы";"monitory";8;"periferiynye-ustroystva/";"";"";"";"";"";;0;0;1;1;"13";;13;
"Устройства ввода";"ustroystva-vvoda";8;"periferiynye-ustroystva/";"";"";"";"";"";;0;0;1;1;"17";;17;
"Компьютерные мыши";"kompyuternye-myshi";17;"periferiynye-ustroystva/ustroystva-vvoda/";"";"";"";"";"";;0;0;1;1;"18";;18;
"Клавиатуры";"klaviatury";17;"periferiynye-ustroystva/ustroystva-vvoda/";"";"";"";"";"";;0;0;1;1;"19";;19;
"Накопители";"nakopiteli";0;"";"";"";"";"";"";;0;0;1;1;"14";;14;
"Карты памяти";"karty-pamyati";14;"nakopiteli/";"";"";"";"";"";;0;0;1;1;"15";;15;
"USB Flash drive";"usb-flash-drive";14;"nakopiteli/";"";"";"";"";"";;0;0;1;1;"16";;16;
"Компьютеры";"kompyutery";0;"";"";"";"";"";"";;0;0;1;1;"20";;20;
"Ноутбуки";"noutbuki";20;"kompyutery/";"";"";"";"";"";;0;0;1;1;"21";;21;
"Планшеты";"planshety";20;"kompyutery/";"";"";"";"";"";;0;0;1;1;"22";;22;
"Настольные";"nastolnye";20;"kompyutery/";"";"";"";"";"";;0;0;1;1;"24";;24;';
    
    echo iconv("UTF-8", "WINDOWS-1251", $csvText);
    exit;
  }

  /**
   * Возвращает пример загружаемого каталога.
   * <code>
   * Models_Catalog::getExampleCSV();
   * </code>
   */
  public function getExampleCSV() {

    header("Content-Type: application/force-download");
    header("Content-Type: application/octet-stream;");
    header("Content-Type: application/download");
    header("Content-Disposition: attachment;filename=data.csv");
    header("Content-Transfer-Encoding: binary ");

   $csvText .='"ID товара";Категория;"URL категории";Товар;Вариант;"Краткое описание";Описание;Цена;URL;Изображение;Артикул;Количество;Активность;"Заголовок [SEO]";"Ключевые слова [SEO]";"Описание [SEO]";"Старая цена";Рекомендуемый;Новый;Сортировка;Вес;"Связанные артикулы";"Смежные категории";"Ссылка на товар";Валюта;"Единицы измерения";"Количество от 10 для цены 1 [оптовая цена]";"Количество от 20 для цены 1 [оптовая цена]";"Количество от 50 для цены 1 [оптовая цена]";"Слад №1  [склад=Slad-№1]";"Склад №2  [склад=Sklad-№2]";"Пункт самомвывоза  [склад=Punkt-samomvyvoza]";"Цвет [color]";"Страна производства   ";"Производитель   ";"Пол   ";"Сезон   ";"Размер [size]";"Возраст   ";"Сложные характеристики"
51;"Аксессуары/Головные уборы";aksessuary/golovnye-ubory;"Бейсболка мужская Demix";"50 Голубой [:param:][src=]";"<p>  &nbsp;Непревзойдённое сочетание цены и качества говорят сами за себя, что значительно упрощает решение при выборе товара.</p>";"<strong>Demix</strong> — великолепный пример воплощения американской мечты. Компания, начинавшаяся с желания студента найти достойную спортивную обувь, превратилась в один из сильнейших брендов мира, который существенно повлиял на развитие спорта и вирусного маркетинга. Кроме того, именно Demix сделала спорт не только великолепным зрелищем, но и прибыльным бизнесом.";199;beysbolka-mujskaya-demix_51;no-img.jpg;CN51_1;0;1;"Бейсболка мужская Demix";"Бейсболка мужская Demix купить, CN32, Бейсболка, мужская, Demix";;;0;0;51;0;;;;RUR;шт.;159;119;79;10;11;12;"Голубой [#2832f0]";Китай;Demix;Унисекс;Лето;50;;
51;"Аксессуары/Головные уборы";aksessuary/golovnye-ubory;"Бейсболка мужская Demix";"50 Красный [:param:][src=]";"<p>  &nbsp;Непревзойдённое сочетание цены и качества говорят сами за себя, что значительно упрощает решение при выборе товара.</p>";"<strong>Demix</strong> — великолепный пример воплощения американской мечты. Компания, начинавшаяся с желания студента найти достойную спортивную обувь, превратилась в один из сильнейших брендов мира, который существенно повлиял на развитие спорта и вирусного маркетинга. Кроме того, именно Demix сделала спорт не только великолепным зрелищем, но и прибыльным бизнесом.";199;beysbolka-mujskaya-demix_51;no-img.jpg;CN51_2;0;1;"Бейсболка мужская Demix";"Бейсболка мужская Demix купить, CN32, Бейсболка, мужская, Demix";;;0;0;51;0;;;;RUR;шт.;159;119;79;11;12;13;"Красный [#d90707]";Китай;Demix;Унисекс;Лето;50;;
44;"Мужская обувь";mujskaya-obuv;"Кроссовки мужские Demix Beast";"36 Голубой[:param:][src=]";"<p> &nbsp;Непревзойдённое сочетание цены и качества говорят сами за себя, что значительно упрощает решение при выборе товара.</p>";"<ul>  <li>    <b>ГИБКОСТЬ</b><br />   Специальные канавки&nbsp;Flex Grooves&nbsp;позволяют подошве легко сгибаться. </li> <li>    <b>СТАБИЛИЗАЦИЯ И ПОДДЕРЖКА СТОПЫ</b><br />   Высокая шнуровка надежно фиксирует голеностоп.  </li> <li>    <b>НИЗКОПРОФИЛЬНАЯ АМОРТИЗАЦИЯ</b><br />    Промежуточная подошва из ЭВА смягчает ударную нагрузки при прыжках, защищая суставы от преждевременного износа. </li> <li>    <b>СВОБОДА ДВИЖЕНИЙ</b><br />   Кроссовки с низким резом позволяют добиться более динамичного ускорения и резких маневров на высокой скорости.  </li></ul>";1199;krossovki-mujskie-demix-beast;no-img.jpg|no-img.jpg[:param:][alt=prodtmpimg/321.jpg][title=]|no-img.jpg[:param:][alt=prodtmpimg/322.jpg][title=]|no-img.jpg[:param:][alt=prodtmpimg/323.jpg][title=];CN44;-1;1;"Кроссовки мужские Demix Beast";"Кроссовки мужские Demix Beast купить, CN44, Кроссовки, мужские, Demix, Beast";"ГИБКОСТЬ Специальные канавкиFlex Groovesпозволяют подошве легко сгибаться. СТАБИЛИЗАЦИЯ И ПОДДЕРЖКА СТОПЫ Высокая шнуровка надежно фиксирует голеностоп";;0;0;44;0;;;;RUR;шт.;959;719;479;12;13;14;"Голубой [#2832f0]";Китай;Demix;Мужской;Лето;;Взрослые;
44;"Мужская обувь";mujskaya-obuv;"Кроссовки мужские Demix Beast";"39 Голубой[:param:][src=]";"<p> &nbsp;Непревзойдённое сочетание цены и качества говорят сами за себя, что значительно упрощает решение при выборе товара.</p>";"<ul>  <li>    <b>ГИБКОСТЬ</b><br />   Специальные канавки&nbsp;Flex Grooves&nbsp;позволяют подошве легко сгибаться. </li> <li>    <b>СТАБИЛИЗАЦИЯ И ПОДДЕРЖКА СТОПЫ</b><br />   Высокая шнуровка надежно фиксирует голеностоп.  </li> <li>    <b>НИЗКОПРОФИЛЬНАЯ АМОРТИЗАЦИЯ</b><br />    Промежуточная подошва из ЭВА смягчает ударную нагрузки при прыжках, защищая суставы от преждевременного износа. </li> <li>    <b>СВОБОДА ДВИЖЕНИЙ</b><br />   Кроссовки с низким резом позволяют добиться более динамичного ускорения и резких маневров на высокой скорости.  </li></ul>";1199;krossovki-mujskie-demix-beast;no-img.jpg|no-img.jpg[:param:][alt=prodtmpimg/321.jpg][title=]|no-img.jpg[:param:][alt=prodtmpimg/322.jpg][title=]|no-img.jpg[:param:][alt=prodtmpimg/323.jpg][title=];CN44-2;-1;1;"Кроссовки мужские Demix Beast";"Кроссовки мужские Demix Beast купить, CN44, Кроссовки, мужские, Demix, Beast";"ГИБКОСТЬ Специальные канавкиFlex Groovesпозволяют подошве легко сгибаться. СТАБИЛИЗАЦИЯ И ПОДДЕРЖКА СТОПЫ Высокая шнуровка надежно фиксирует голеностоп";;0;0;44;0;;;;RUR;шт.;959;719;479;13;14;15;"Голубой [#2832f0]";Китай;Demix;Мужской;Лето;;Взрослые;
44;"Мужская обувь";mujskaya-obuv;"Кроссовки мужские Demix Beast";"40 Голубой[:param:][src=]";"<p> &nbsp;Непревзойдённое сочетание цены и качества говорят сами за себя, что значительно упрощает решение при выборе товара.</p>";"<ul>  <li>    <b>ГИБКОСТЬ</b><br />   Специальные канавки&nbsp;Flex Grooves&nbsp;позволяют подошве легко сгибаться. </li> <li>    <b>СТАБИЛИЗАЦИЯ И ПОДДЕРЖКА СТОПЫ</b><br />   Высокая шнуровка надежно фиксирует голеностоп.  </li> <li>    <b>НИЗКОПРОФИЛЬНАЯ АМОРТИЗАЦИЯ</b><br />    Промежуточная подошва из ЭВА смягчает ударную нагрузки при прыжках, защищая суставы от преждевременного износа. </li> <li>    <b>СВОБОДА ДВИЖЕНИЙ</b><br />   Кроссовки с низким резом позволяют добиться более динамичного ускорения и резких маневров на высокой скорости.  </li></ul>";1199;krossovki-mujskie-demix-beast;no-img.jpg|no-img.jpg[:param:][alt=prodtmpimg/321.jpg][title=]|no-img.jpg[:param:][alt=prodtmpimg/322.jpg][title=]|no-img.jpg[:param:][alt=prodtmpimg/323.jpg][title=];CN44-3;-1;1;"Кроссовки мужские Demix Beast";"Кроссовки мужские Demix Beast купить, CN44, Кроссовки, мужские, Demix, Beast";"ГИБКОСТЬ Специальные канавкиFlex Groovesпозволяют подошве легко сгибаться. СТАБИЛИЗАЦИЯ И ПОДДЕРЖКА СТОПЫ Высокая шнуровка надежно фиксирует голеностоп";;0;0;44;0;;;;RUR;шт.;959;719;479;14;15;16;"Голубой [#2832f0]";Китай;Demix;Мужской;Лето;40;Взрослые;
44;"Мужская обувь";mujskaya-obuv;"Кроссовки мужские Demix Beast";"41 Голубой[:param:][src=]";"<p> &nbsp;Непревзойдённое сочетание цены и качества говорят сами за себя, что значительно упрощает решение при выборе товара.</p>";"<ul>  <li>    <b>ГИБКОСТЬ</b><br />   Специальные канавки&nbsp;Flex Grooves&nbsp;позволяют подошве легко сгибаться. </li> <li>    <b>СТАБИЛИЗАЦИЯ И ПОДДЕРЖКА СТОПЫ</b><br />   Высокая шнуровка надежно фиксирует голеностоп.  </li> <li>    <b>НИЗКОПРОФИЛЬНАЯ АМОРТИЗАЦИЯ</b><br />    Промежуточная подошва из ЭВА смягчает ударную нагрузки при прыжках, защищая суставы от преждевременного износа. </li> <li>    <b>СВОБОДА ДВИЖЕНИЙ</b><br />   Кроссовки с низким резом позволяют добиться более динамичного ускорения и резких маневров на высокой скорости.  </li></ul>";1199;krossovki-mujskie-demix-beast;no-img.jpg|no-img.jpg[:param:][alt=prodtmpimg/321.jpg][title=]|no-img.jpg[:param:][alt=prodtmpimg/322.jpg][title=]|no-img.jpg[:param:][alt=prodtmpimg/323.jpg][title=];CN44-4;-1;1;"Кроссовки мужские Demix Beast";"Кроссовки мужские Demix Beast купить, CN44, Кроссовки, мужские, Demix, Beast";"ГИБКОСТЬ Специальные канавкиFlex Groovesпозволяют подошве легко сгибаться. СТАБИЛИЗАЦИЯ И ПОДДЕРЖКА СТОПЫ Высокая шнуровка надежно фиксирует голеностоп";;0;0;44;0;;;;RUR;шт.;959;719;479;15;16;17;"Голубой [#2832f0]";Китай;Demix;Мужской;Лето;;Взрослые;
44;"Мужская обувь";mujskaya-obuv;"Кроссовки мужские Demix Beast";"42 Голубой[:param:][src=]";"<p> &nbsp;Непревзойдённое сочетание цены и качества говорят сами за себя, что значительно упрощает решение при выборе товара.</p>";"<ul>  <li>    <b>ГИБКОСТЬ</b><br />   Специальные канавки&nbsp;Flex Grooves&nbsp;позволяют подошве легко сгибаться. </li> <li>    <b>СТАБИЛИЗАЦИЯ И ПОДДЕРЖКА СТОПЫ</b><br />   Высокая шнуровка надежно фиксирует голеностоп.  </li> <li>    <b>НИЗКОПРОФИЛЬНАЯ АМОРТИЗАЦИЯ</b><br />    Промежуточная подошва из ЭВА смягчает ударную нагрузки при прыжках, защищая суставы от преждевременного износа. </li> <li>    <b>СВОБОДА ДВИЖЕНИЙ</b><br />   Кроссовки с низким резом позволяют добиться более динамичного ускорения и резких маневров на высокой скорости.  </li></ul>";1199;krossovki-mujskie-demix-beast;no-img.jpg|no-img.jpg[:param:][alt=prodtmpimg/321.jpg][title=]|no-img.jpg[:param:][alt=prodtmpimg/322.jpg][title=]|no-img.jpg[:param:][alt=prodtmpimg/323.jpg][title=];CN44-5;-1;1;"Кроссовки мужские Demix Beast";"Кроссовки мужские Demix Beast купить, CN44, Кроссовки, мужские, Demix, Beast";"ГИБКОСТЬ Специальные канавкиFlex Groovesпозволяют подошве легко сгибаться. СТАБИЛИЗАЦИЯ И ПОДДЕРЖКА СТОПЫ Высокая шнуровка надежно фиксирует голеностоп";;0;0;44;0;;;;RUR;шт.;959;719;479;16;17;18;"Голубой [#2832f0]";Китай;Demix;Мужской;Лето;;Взрослые;
44;"Мужская обувь";mujskaya-obuv;"Кроссовки мужские Demix Beast";"43 Голубой[:param:][src=]";"<p> &nbsp;Непревзойдённое сочетание цены и качества говорят сами за себя, что значительно упрощает решение при выборе товара.</p>";"<ul>  <li>    <b>ГИБКОСТЬ</b><br />   Специальные канавки&nbsp;Flex Grooves&nbsp;позволяют подошве легко сгибаться. </li> <li>    <b>СТАБИЛИЗАЦИЯ И ПОДДЕРЖКА СТОПЫ</b><br />   Высокая шнуровка надежно фиксирует голеностоп.  </li> <li>    <b>НИЗКОПРОФИЛЬНАЯ АМОРТИЗАЦИЯ</b><br />    Промежуточная подошва из ЭВА смягчает ударную нагрузки при прыжках, защищая суставы от преждевременного износа. </li> <li>    <b>СВОБОДА ДВИЖЕНИЙ</b><br />   Кроссовки с низким резом позволяют добиться более динамичного ускорения и резких маневров на высокой скорости.  </li></ul>";1199;krossovki-mujskie-demix-beast;no-img.jpg|no-img.jpg[:param:][alt=prodtmpimg/321.jpg][title=]|no-img.jpg[:param:][alt=prodtmpimg/322.jpg][title=]|no-img.jpg[:param:][alt=prodtmpimg/323.jpg][title=];CN44-6;-1;1;"Кроссовки мужские Demix Beast";"Кроссовки мужские Demix Beast купить, CN44, Кроссовки, мужские, Demix, Beast";"ГИБКОСТЬ Специальные канавкиFlex Groovesпозволяют подошве легко сгибаться. СТАБИЛИЗАЦИЯ И ПОДДЕРЖКА СТОПЫ Высокая шнуровка надежно фиксирует голеностоп";;0;0;44;0;;;;RUR;шт.;959;719;479;17;18;19;"Голубой [#2832f0]";Китай;Demix;Мужской;Лето;43;Взрослые;
40;"Аксессуары/Чехлы для смартфонов";aksessuary/chehly-dlya-smartfonov;"Чехол на руку для смартфона Demix+";Черный[:param:][src=241.jpg];"<p> &nbsp;Непревзойдённое сочетание цены и качества говорят сами за себя, что значительно упрощает решение при выборе товара.</p>";"Удобный чехол для смартфона крепится на руку и позволяет во время тренировки оставаться на связи, пользоваться приложениями для более эффективных занятий и слушать музыку. Влагоотводящая сетка на задней части и легкая в обращении система регулировки размера сделает занятия спортом еще комфортнее и приятнее.";299;chehol-na-ruku-dlya-smartfona-demix;no-img.jpg|no-img.jpg|no-img.jpg;CN40_1;-1;1;"Чехол на руку для смартфона Demix";"Чехол на руку для смартфона Demix купить, CN39, Чехол, на руку, для смартфона, Demix";;;0;0;40;0;;;;RUR;шт.;239;179;119;18;19;20;"Черный [#1a191a]";Китай;Demix;Унисекс;;;;
40;"Аксессуары/Чехлы для смартфонов";aksessuary/chehly-dlya-smartfonov;"Чехол на руку для смартфона Demix+";Зелёный[:param:][src=];"<p> &nbsp;Непревзойдённое сочетание цены и качества говорят сами за себя, что значительно упрощает решение при выборе товара.</p>";"Удобный чехол для смартфона крепится на руку и позволяет во время тренировки оставаться на связи, пользоваться приложениями для более эффективных занятий и слушать музыку. Влагоотводящая сетка на задней части и легкая в обращении система регулировки размера сделает занятия спортом еще комфортнее и приятнее.";299;chehol-na-ruku-dlya-smartfona-demix;no-img.jpg|no-img.jpg|no-img.jpg;CN40_2;-1;1;"Чехол на руку для смартфона Demix";"Чехол на руку для смартфона Demix купить, CN39, Чехол, на руку, для смартфона, Demix";;;0;0;40;0;;;;RUR;шт.;239;179;119;19;20;21;"Зелёный [#a1e63a]";Китай;Demix;Унисекс;;;;
40;"Аксессуары/Чехлы для смартфонов";aksessuary/chehly-dlya-smartfonov;"Чехол на руку для смартфона Demix+";Голубой[:param:][src=242.jpg];"<p>  &nbsp;Непревзойдённое сочетание цены и качества говорят сами за себя, что значительно упрощает решение при выборе товара.</p>";"Удобный чехол для смартфона крепится на руку и позволяет во время тренировки оставаться на связи, пользоваться приложениями для более эффективных занятий и слушать музыку. Влагоотводящая сетка на задней части и легкая в обращении система регулировки размера сделает занятия спортом еще комфортнее и приятнее.";299;chehol-na-ruku-dlya-smartfona-demix;no-img.jpg|no-img.jpg|no-img.jpg;CN40_3;-1;1;"Чехол на руку для смартфона Demix";"Чехол на руку для смартфона Demix купить, CN39, Чехол, на руку, для смартфона, Demix";;;0;0;40;0;;;;RUR;шт.;239;179;119;20;21;22;"Голубой [#2832f0]";Китай;Demix;Унисекс;;;;
40;"Аксессуары/Чехлы для смартфонов";aksessuary/chehly-dlya-smartfonov;"Чехол на руку для смартфона Demix+";Розовый[:param:][src=243..jpg];"<p> &nbsp;Непревзойдённое сочетание цены и качества говорят сами за себя, что значительно упрощает решение при выборе товара.</p>";"Удобный чехол для смартфона крепится на руку и позволяет во время тренировки оставаться на связи, пользоваться приложениями для более эффективных занятий и слушать музыку. Влагоотводящая сетка на задней части и легкая в обращении система регулировки размера сделает занятия спортом еще комфортнее и приятнее.";299;chehol-na-ruku-dlya-smartfona-demix;no-img.jpg|no-img.jpg|no-img.jpg;CN40_4;-1;1;"Чехол на руку для смартфона Demix";"Чехол на руку для смартфона Demix купить, CN39, Чехол, на руку, для смартфона, Demix";;;0;0;40;0;;;;RUR;шт.;239;179;119;21;22;23;"Розовый [#ff0040]";Китай;Demix;Унисекс;;;;
36;"Аксессуары/Чехлы для смартфонов";aksessuary/chehly-dlya-smartfonov;"Чехол для смартфона iPhone Nike Waffle";;"<p> &nbsp;Непревзойдённое сочетание цены и качества говорят сами за себя, что значительно упрощает решение при выборе товара.</p>";"<ul>  <li>    надежная защита от сколов и царапин;  </li> <li>    дополнительная защита для камеры; </li> <li>    вырезы для удобства доступа к элементам управления. </li></ul>";1199;chehol-dlya-smartfona-iphone-nike-waffle;no-img.jpg;CN36;-1;1;"Чехол для смартфона iPhone Nike Waffle";"Чехол для смартфона iPhone Nike Waffle  купить, CN36, Чехол, для смартфона, iPhone, Nike, Waffle,";"надежная защита от сколов и царапин; дополнительная защита для камеры; вырезы для удобства доступа к элементам управления.";;0;0;36;0;;;;RUR;шт.;959;719;479;0;0;0;;Китай;NIke;Унисекс;;;;
35;Фитнес-браслеты;fitnes-braslety;"Кардиодатчик Kettler Cardio Pulse";;"&nbsp;Непревзойдённое сочетание цены и качества говорят сами за себя, что значительно упрощает решение при выборе товара.";"Показания снимаются в околосердечной зоне груди. Совместимость со всеми кардиотренажерами поддерживающими протокол Bluetooth. Данные выводятся на дисплей тренировочного компьютера. Батарейка: CR2032 3V, время работы: 600-800 часов. Длина ремня: 65-95 см. Передача данных осуществляется через протоколы&nbsp;Bluetooth low energy&nbsp;(радиус до 10 метров) или&nbsp;ANT+&nbsp;(до 6 метров).";1199;kardiodatchik-kettler-cardio-pulse;no-img.jpg|no-img.jpg;CN35;-1;1;"Кардиодатчик Kettler Cardio Pulse";"Кардиодатчик Kettler Cardio Pulse купить, CN35, Кардиодатчик, Kettler, Cardio, Pulse";;;0;0;35;0;;;;RUR;шт.;959;719;479;0;0;0;;Китай;Kettler;Унисекс;;;;

';

    echo iconv("UTF-8", "WINDOWS-1251", $csvText);
    exit;
  }


    /**
   * Возвращает пример CSV файла для обновления цен товаров.
   * <code>
   * Models_Catalog::getExampleCsvUpdate();
   * </code>
   */
  public function getExampleCsvUpdate() {

    header("Content-Type: application/force-download");
    header("Content-Type: application/octet-stream;");
    header("Content-Type: application/download");
    header("Content-Disposition: attachment;filename=data.csv");
    header("Content-Transfer-Encoding: binary ");

    $csvText .='"Артикул";"Цена";"Старая цена";"Количество";"Активность"
1000A;39000;;9;1
1001A;27900;;0;1
1003A;33500;;-1;1
10034B;14990;19000;-1;1
10034Bb;14990;;0;1
10024Bw;13000;;-1;1
10024Bbl;13000;;-1;1
10024Bbr;13000;;-1;1
10105A;44500;;1;1
340К;1390;;0;1
1004C;17990;;-1;1
1054C;12550;;-1;1
1005A;27990;30000;6;1
10002A;21900;25000;2;1
1006A;39990;;-1;1
1007D;25000;28990;7;1
390K;1090;1390;-1;1
2060B;19990;;-1;1
1004С;1090;1190;-1;1';
    echo iconv("UTF-8", "WINDOWS-1251", $csvText);
    exit;
  }


  /**
   * Метод для обработки фильтрации товаров в каталоге.
   * <code>
   * $catalog = new Models_Catalog();
   * $result = $catalog->filterPublic();
   * viewData($result);
   * </code>
   * @param bool $noneAjax построение HTML для использования AJAX запросов. 
   * @param bool $onlyInCount учитывать только товары в наличии, 
   * @param bool $onlyActive учитывать только активные товары, 
   * @param array $sortFields массив доступных сортировок товаров.
   * @param string $baseSort сортировка по умолчанию, 
   * @return array возвращает array('filterBarHtml' => $filter->getHtmlFilter($noneAjax), 'userFilter' => $userFilter, 'applyFilterList' => $applyFilterList);
   */
  public function filterPublic($noneAjax = true, $onlyInCount = false, $onlyActive=true, $sortFields = array(
      'price_course|-1'=>'цене, сначала недорогие',
      'price_course|1'=>'цене, сначала дорогие',
      'id|1'=>'новизне',
      'count_buy|1'=>'популярности',
      'recommend|1'=>'сначала рекомендуемые',
      'new|1'=>'сначала новинки',
      'old_price|1'=>'сначала распродажа',
      'sort|-1'=>'порядку',
      'count|1'=>'наличию',
      'count|-1' => 'возрастанию количества',    
      'title|-1' => 'наименованию А-Я',
      'title|1' => 'наименованию Я-А',
       ),$baseSort = 'sort|-1') {

    if (MG::enabledStorage()) {
      unset($sortFields['count|1']);
      unset($sortFields['count|-1']);
    }
      
    
    $orderBy = strtolower(MG::getSetting('filterSort'));   
    
    $compareArray = array(
      "sort|desc" => 'sort|1',
      "sort|asc" => 'sort|-1',
      "price_course|asc" => 'price_course|-1',
      "price_course|desc" => 'price_course|1',
      "id|desc" => 'id|1',
      "count_buy|desc" => 'count_buy|1',
      "recommend|desc" => 'recommend|1',
      "new|desc" => 'new|1',
      "old_price|desc" => 'old_price|1',
      "count|desc" =>'count|1'
    );

    if (MG::enabledStorage()) {
      unset($compareArray["count_buy|desc"]);
    }
    
    if(URL::isSection('mg-admin')) {
      $sortFieldsAdmin = array(
        'id|-1' => 'сначала старые',
        'count|-1' => 'по возрастанию количества',
        'cat_id|1' => 'Категория Я-А',
        'cat_id|-1' => 'Категория А-Я',
        'title|-1' => 'Название А-Я',
        'title|1' => 'Название Я-А',
        'activity|1' => 'Сначала активные',
        'activity|-1' => 'Сначала неактивные',
      );

      if (MG::enabledStorage()) {
        unset($sortFieldsAdmin['count|-1']);
      }

      $sortFields = array_merge($sortFields, $sortFieldsAdmin);

      $compareArrayAdmin = array(
        "id|asc" => 'id|-1', 
        "count|asc" => 'count|-1',
        "cat_id|asc" => 'cat_id|-1',
        "cat_id|desc" => 'cat_id|1',
        "title|asc" => 'title|-1',
        "title|desc" => 'title|1',
      );  

      if (MG::enabledStorage()) {
        unset($compareArrayAdmin["count|asc"]);
      }   
       
      $compareArray = array_merge($compareArray, $compareArrayAdmin);
    }
    
    $baseSort = $compareArray[$orderBy]?$compareArray[$orderBy]:'sort|1';
    
    $newSortFields[$baseSort] = $sortFields[$baseSort];
    unset($sortFields[$baseSort]);      
    $sortFields = array_merge($newSortFields,$sortFields);
    $lang = MG::get('lang');
    $model = new Models_Catalog;
    $catalog = array();

    foreach ($this->categoryId as $key => $value) {
      $this->categoryId[$key] = intval($value);
    }

    if(!empty($_REQUEST['insideCat']) && $_REQUEST['insideCat']==="false") {
      $this->categoryId = array(end($this->categoryId));
    }
    
    $currentCategoryId = $this->currentCategory['id'] ? $this->currentCategory['id'] : 0;
    $where = '';
    
    if(!URL::isSection('mg-admin')) {
      $where .= ' p.activity = 1 ';   
    
      if(MG::getSetting('printProdNullRem') == "true") {

        if(MG::enabledStorage()) {
          $where .= ' AND ((SELECT SUM(count) FROM '.PREFIX.'product_on_storage WHERE product_id = p.id) > 0)';
        } else {
          $where .= ' AND count != 0 ';
        }
      }
    }
    $catIds = implode(',', $this->categoryId);
        
    if (!empty($catIds)||$catIds === 0) {             
      $where1 = ' (p.cat_id IN (' . DB::quote($catIds,1) . ') or FIND_IN_SET(' . DB::quote($currentCategoryId,1) . ',p.`inside_cat`))';
      $rule1 = ' (cat_id IN (' . DB::quote($catIds,1) . ') or FIND_IN_SET(' . DB::quote($currentCategoryId,1) . ',p.`inside_cat`)) ';
      if($currentCategoryId==0) {
        $where1 = ' 1=1 or FIND_IN_SET(' . DB::quote($currentCategoryId,1) . ',p.`inside_cat`)';
        $rule1 = ' 1=1 or FIND_IN_SET(' . DB::quote($currentCategoryId,1) . ',p.`inside_cat`) ';
      } 
    } else {
      $catIds = 0;
    }
    
    if(!empty($where) || !empty($where1)) {
      $where = 'WHERE '.$where;
      if(!empty($where1)) {
        $where .= (URL::isSection('mg-admin')) ? $where1 : ' AND '.$where1;
      }
    }
    
    $prices = DB::fetchAssoc(
        DB::query('
         SELECT
          CEILING(MAX((p.price_course + p.price_course * (IFNULL(c.rate,0))))) as `max_price`,
          FLOOR(MIN((p.price_course + p.price_course * (IFNULL(c.rate,0))))) as min_price
        FROM `' . PREFIX . 'product` as p
          LEFT JOIN `' . PREFIX . 'category` as c ON
          c.id = p.cat_id '. $where));    
    $where = str_replace('AND count != 0', 'AND (pv.count != 0 OR pv.count IS NULL)', $where);
    if(MG::getSetting('showVariantNull') == "false") {
        $where = str_replace('p.activity = 1', 'p.activity = 1 AND (pv.count != 0 OR pv.count IS NULL)', $where);
      }
    $pricesVariant = DB::fetchAssoc(
        DB::query('
         SELECT
          CEILING(MAX((pv.price_course + pv.price_course * (IFNULL(c.rate,0))))) as `max_price`, 
          FLOOR(MIN((pv.price_course + pv.price_course * (IFNULL(c.rate,0))))) as `min_price`
        FROM `' . PREFIX . 'product` as p
          LEFT JOIN `' . PREFIX . 'category` as c ON
          c.id = p.cat_id 
          LEFT JOIN `'.PREFIX.'product_variant` pv ON pv.`product_id`=p.id '.$where
    ));  
    $maxPrice = max($prices['max_price']||$prices['max_price']=="0" ? $prices['max_price'] : $pricesVariant['max_price'], $pricesVariant['max_price']||$pricesVariant['max_price']=="0" ? $pricesVariant['max_price'] : $prices['max_price']);
    $minPrice = min($prices['min_price']||$prices['min_price']=="0" ? $prices['min_price'] : $pricesVariant['min_price'], $pricesVariant['min_price']||$pricesVariant['min_price']=="0" ? $pricesVariant['min_price'] : $prices['min_price']);
    $property = array(
      'cat_id' => array(
        'type' => 'hidden',
        'value' => $_REQUEST['cat_id'],
      ),

      'sorter' => array(
        'type' => 'select', //текстовый инпут
        'label' => 'Сортировать по',
      'option' => $sortFields,
        'selected' => !empty($_REQUEST['sorter']) ? $_REQUEST['sorter'] : 'null', // Выбранный пункт (сравнивается по значению)
        'value' => !empty($_REQUEST['sorter'])?$_REQUEST['sorter']:null,
      ),

      'price_course' => array(
        'type' => 'beetwen', //Два текстовых инпута
        'label1' => $lang['PRICE_FROM'],
        'label2' => $lang['PRICE_TO'],
        'min' => !empty($_REQUEST['price_course'][0]) ? $_REQUEST['price_course'][0] : $minPrice,
        'max' => !empty($_REQUEST['price_course'][1]) ? $_REQUEST['price_course'][1] : $maxPrice,
        'factMin' => $minPrice,
        'factMax' => $maxPrice,
        'class' => 'price numericProtection'
      ),

      'applyFilter' => array(
        'type' => 'hidden', //текстовый инпут
        'label' => 'флаг примения фильтров',
        'value' => 1,
      )
    );
    
    if (URL::isSection('mg-admin')) {
      $property['title'] = array(
        'type' => 'text',
        'special' => 'like',
        'label' => $lang['NAME_PRODUCT'],
        'value' => !empty($_POST['title'][0]) ? $_POST['title'][0] : null,
        );
      $property['code'] = array(
        'type' => 'text',
        'special' => 'like',
        'label' => $lang['CODE_PRODUCT'],
        'value' => !empty($_POST['code'][0]) ? $_POST['code'][0] : null,
        );
    }

    $filter = new Filter($property);
          
    $arr = array(
      'dual_condition' => array (
           array(
                !empty($_REQUEST['price_course'][0]) ? $_REQUEST['price_course'][0] : $minPrice, 
                !empty($_REQUEST['price_course'][1]) ? $_REQUEST['price_course'][1] : $maxPrice,
                '(p.price_course + p.price_course * (IFNULL(rate,0)))'
              ),
              array(
                !empty($_REQUEST['price_course'][0]) ? $_REQUEST['price_course'][0] : $minPrice, 
                !empty($_REQUEST['price_course'][1]) ? $_REQUEST['price_course'][1] : $maxPrice,
                '(pv.price_course + pv.price_course * (IFNULL(rate,0)))'
              ),
          'operator' => 'OR'
        ),
      'p.new' => (isset($_REQUEST['new'])) ? $_REQUEST['new'] : 'null',
      'p.recommend' => (isset($_REQUEST['recommend'])) ? $_REQUEST['recommend'] : 'null',
      'rule1' => $rule1,

    );    
    if (URL::isSection('mg-admin')) {
      if (isset($_REQUEST['code'])&&!empty($_REQUEST['code'][0])) {
        $rule2 = 'p.`code` LIKE ("%'.DB::quote($_REQUEST['code'][0],1).'%") or pv.`code` LIKE ("%'.DB::quote($_REQUEST['code'][0],1).'%")  ';
        $arr['rule2'] = $rule2;
      }     
      if (isset($_REQUEST['title'])&&!empty($_REQUEST['title'][0])) {
        $rule3 = 'p.`title` LIKE ("%'.DB::quote($_REQUEST['title'][0],1).'%") or pv.`title_variant` LIKE ("%'.DB::quote($_REQUEST['title'][0],1).'%")  ';
        $arr['rule3'] = $rule3;
      }  
    }
    $userFilter = $filter->getFilterSql($arr, array(), $_REQUEST['insideCat']);

    // отсеивание фильтра ползунка, если его не настраивали
    foreach ($_REQUEST['prop'] as $id => $property) {
      if(in_array($property[0], array('slider|easy', 'slider|hard'))) {
        if($property[1] == '') {
          unset($_REQUEST['prop'][$id]);
          continue;
        }
        if($property[2] == '') {
          unset($_REQUEST['prop'][$id]);
          continue;
        }
        // проверка значений на дефолтность
        $type = explode('|', $property[0]);
        $type = $type[1];
        if($type == 'easy') {
          unset($tmp);
          $res = DB::query('SELECT DISTINCT name FROM '.PREFIX.'product_user_property_data WHERE prop_id = '.DB::quoteInt($id));
          while($row = DB::fetchAssoc($res)) {
            $tmp[] = (float)$row['name'];
          }
          if(($property[1] == min($tmp))&&($property[2] == max($tmp))) {
            unset($_REQUEST['prop'][$id]);
            continue;
          }
        } else {
          unset($tmp);
          $res = DB::query('SELECT DISTINCT name FROM '.PREFIX.'property_data WHERE prop_id = '.DB::quoteInt($prop['id']));
          while($row = DB::fetchAssoc($res)) {
            $tmp[] = (float)$row['name'];
          }
          if(($property[1] == min($tmp))&&($property[2] == max($tmp))) {
            unset($_REQUEST['prop'][$id]);
            continue;
          }
        }
      }
    }

    // проерка значений фильтра на их наличие
    $propFilterCounter = 0;
    foreach ($_REQUEST['prop'] as $id => $property) {
      foreach ($property as $cnt=>$value) {
        if($value != '') {
          $propFilterCounter++;
        }
      }
    }

    if(!empty($_REQUEST['prop']) && ($propFilterCounter != 0)) {
      if (!empty($_REQUEST['insideCat'])&&$_REQUEST['insideCat']=='true') {
        $catIdsFilter = $this->categoryId;
      } else {
        $catIdsFilter = $this->currentCategory['id'];
      }
      $arrayIdsProd = $filter->getProductIdByFilter($_REQUEST['prop'], str_replace('AND count != 0', 'AND ABS(IFNULL( pv.`count` , 0 ) ) + ABS( p.`count` ) >0', $where)) ;
      $listIdsProd = implode(',',$arrayIdsProd);
      if($listIdsProd != '') {
        if(strlen($userFilter) > 0) {
          $userFilter .= ' AND ';
        }
        $userFilter .= ' p.id IN ('.$listIdsProd.') ';
      } else {
        // добавляем заведомо неверное  условие к запросу,
        // чтобы ничего не попало в выдачу, т.к. товаров отвечающих заданым характеристикам ненайдено
        $userFilter = ' 0 = 1 ';
      }
    }

    $keys = array_keys($sortFields);
    if(empty($_REQUEST['sorter'])) {
      $_REQUEST['sorter'] = $keys[0];
    } elseif(!URL::isSection('mg-admin') && !in_array($_REQUEST['sorter'], $keys)) {
      $_REQUEST['sorter'] = $keys[0];
    }

    if(!empty($_REQUEST['sorter']) && !empty($userFilter)) {
      $sorterData = explode('|', $_REQUEST['sorter']);
      $field = $sorterData[0];
      if ($sorterData[1] > 0) {
        $dir = 'desc';
      } else {
        $dir = 'asc';
      }

      if ($onlyInCount) {
        $userFilter .= ' AND (p.count>0 OR p.count<0)';
      }

      if ($onlyActive) {
        $userFilter .= ' AND p.`activity` = 1';
      }

      if(!empty($userFilter)) {
        $userFilter .= " ORDER BY `".DB::quote($field, true)."`  ".$dir;
      }
    }

    $applyFilterList = $filter->getApplyFilterList();
    if(MG::isAdmin()) {
      return array('filterBarHtml' => $filter->getHtmlFilterAdmin($noneAjax), 'userFilter' => $userFilter, 'applyFilterList' => $applyFilterList);
    } else {
      return array('filterBarHtml' => $filter->getHtmlFilter($noneAjax), 'userFilter' => $userFilter, 'applyFilterList' => $applyFilterList,
        'htmlProp' => $filter->getHtmlPropertyFilter());
    }
  }



  /**
   * Метод добавляет к массиву продуктов информацию о характеристиках
   * для каждого продукта.
   * <code>
   * $catalog = new Models_Catalog;
   * $products = $catalog->addPropertyToProduct($products);
   * </code>
   * @param array $arrayProducts массив с продуктами
   * @param bool $mgadmin если из админки
   * @param bool $changePic заменять изображение
   * @return array
   */
  public function addPropertyToProduct($arrayProducts, $mgadmin = false, $changePic = true) {
    if(empty($arrayProducts)) {
      return $arrayProducts;
    }    
    
    $categoryIds = array();
    $whereCat = '';
    $idsProduct = array();
    $currency = MG::getSetting("currency");
    $currencyRate = MG::getSetting('currencyRate');
    $currencyShopIso = MG::getSetting('currencyShopIso');
    $prod = new Models_Product();
    $idsVariantProduct = array();
   
    foreach ($arrayProducts as $key => $product) {
      $change = true;
      $arrayProducts[$key]['category_url'] = (MG::getSetting('shortLink') == 'true'&&(!URL::isSection('mg-admin')&&!URL::isSection('mgadmin')) ? '' : $arrayProducts[$key]['category_url'].'/');
      $arrayProducts[$key]['category_url'] = ($arrayProducts[$key]['category_url'] == '/' ? '' : $arrayProducts[$key]['category_url']);
      $product['category_url'] = (MG::getSetting('shortLink') == 'true' ? '' : $product['category_url'].'/');
      $product['category_url'] = ($product['category_url'] == '/' ? '' : $product['category_url']);
      if($product['variant_exist']&&$product['variant_id']) {

        $variants = $prod->getVariants($product['id']);
        $variantsKey = array_keys($variants);
        $product['variant_id'] = $variantsKey[0];
        $idsVariantProduct[$product['id']][] = $key;
        $variant = $variants[$product['variant_id']];

        $arrayProducts[$key]['price_course'] =  $variant['price_course'];
        $arrayProducts[$key]['price'] =  $variant['price'];
        $change = false;
        if ($changePic) {
          $arrayProducts[$key]['image_url'] =  $variant['image']?$variant['image']:$arrayProducts[$key]['image_url'];
        }
        
      }
      $idsProduct[$product['id']] = $key;
      $categoryIds[] = $product['cat_id'];
      // Назначаем для продукта пользовательские
      // характеристики по умолчанию, заданные категорией.
   
      $arrayProducts[$key]['thisUserFields'] = MG::get('category')->getUserPropertyCategoryById($product['cat_id']);
      Property::addDataToProp($arrayProducts[$key]['thisUserFields'], $product['id']);
      $arrayProducts[$key]['propertyIdsForCat'] =  MG::get('category')->getPropertyForCategoryById($product['cat_id']);

      $arrayProducts[$key]['currency'] = $currency;
      // Формируем ссылки подробнее и в корзину.		
      $arrayProducts[$key]['actionBuy'] = MG::layoutManager('layout_btn_buy', $product);	 
      $arrayProducts[$key]['actionCompare'] =  MG::layoutManager('layout_btn_compare', $product);
      $arrayProducts[$key]['actionView'] =  MG::layoutManager('layout_btn_more', $product);
	  
	  
      $arrayProducts[$key]['link'] = (MG::getSetting('shortLink') == 'true' ? SITE.'/'.$product["product_url"] : SITE.'/'.(isset($product["category_url"])&&($product["category_url"]!='') ? $product["category_url"] : 'catalog/').$product["product_url"]);
      if (empty($arrayProducts[$key]['currency_iso'])) {
        $arrayProducts[$key]['currency_iso'] = $currencyShopIso;
      }
	  
	  
      $arrayProducts[$key]['real_old_price'] = $arrayProducts[$key]['old_price'];

      $arrayProducts[$key]['old_price'] = MG::convertPrice($arrayProducts[$key]['old_price']);

      // $arrayProducts[$key]['old_price'] = round($arrayProducts[$key]['old_price'],2);
      $arrayProducts[$key]['real_price'] = $arrayProducts[$key]['price'];

      if ($change) {
        $arrayProducts[$key]['price_course'] = MG::convertPrice($arrayProducts[$key]['price_course']);
      }

      $arrayProducts[$key]['price'] = MG::priceCourse($arrayProducts[$key]['price_course']);
      
      $imagesConctructions = $prod->imagesConctruction($arrayProducts[$key]['image_url'],$arrayProducts[$key]['image_title'],$arrayProducts[$key]['image_alt'], $product['id']);
      $arrayProducts[$key]['images_product'] = $imagesConctructions['images_product'];
      $arrayProducts[$key]['images_title'] = $imagesConctructions['images_title'];
      $arrayProducts[$key]['images_alt'] = $imagesConctructions['images_alt'];
      $arrayProducts[$key]['image_url'] = $imagesConctructions['image_url'];
      $arrayProducts[$key]['image_title'] = $imagesConctructions['image_title'];
      $arrayProducts[$key]['image_alt'] = $imagesConctructions['image_alt'];

      $imagesUrl = explode("|", $arrayProducts[$key]['image_url']);
      $arrayProducts[$key]["image_url"] = "";
      if (!empty($imagesUrl[0])) {
        $arrayProducts[$key]["image_url"] = $imagesUrl[0];
      }

    }

    $model = new Models_Product();
    $arrayVariants = $model->getBlocksVariantsToCatalog(array_keys($idsProduct), true, $mgadmin);

    foreach (array_keys($idsProduct) as $id) {
      $arrayProducts[$idsProduct[$id]]['variants'] = $arrayVariants[$id];
    }

    foreach ($arrayProducts as $key => $value) {
      if (!empty($arrayProducts[$key]['variant_exist'])) {
        $arrayProducts[$key]['real_old_price'] = $arrayProducts[$key]['old_price'];
        $arrayProducts[$key]['real_price'] = MG::priceCourse($arrayProducts[$key]['price_course']);
        if ($arrayProducts[$key]['count'] == 0) {
          $arrayProducts[$key]['actionBuy'] = $arrayProducts[$key]['actionView'];
        }
        if (!empty($value['variants'])) {
          foreach ($value['variants'] as $key2 => $value2) {
            
            $arrayProducts[$key]['variants'][$key2]['price_course'] = MG::convertPrice($arrayProducts[$key]['variants'][$key2]['price_course']);
            $arrayProducts[$key]['variants'][$key2]['old_price'] = MG::convertPrice($arrayProducts[$key]['variants'][$key2]['old_price']);

            $arrayProducts[$key]['variants'][$key2]['price'] = MG::priceCourse($arrayProducts[$key]['variants'][$key2]['price']);
          }
        }
      }    }

    // Собираем все ID продуктов в один запрос.
    if ($prodSet = trim(DB::quote(implode(',', array_keys($idsProduct))), "'")) {
      // Формируем список id продуктов, к которым нужно найти пользовательские характеристики.
      $where = ' IN (' . $prodSet . ') ';
    } else {
      $where = ' IN (0) ';
    }

    //Определяем id категории, в которой находимся
    $catCode = URL::getLastSection();

    // $sql = '
    //   SELECT pup.property_id, pup.value, pup.product_id, prop.*, pup.type_view, pup.product_margin
    //   FROM `'.PREFIX.'product_user_property` as pup
    //   LEFT JOIN `'.PREFIX.'property` as prop
    //     ON pup.property_id = prop.id ';


    if((int)MG::getSetting('catalogProp') > 0) {
      $sql = '
        SELECT DISTINCT pup.prop_id, pup.product_id, prop.*, pup.type_view, pup.name AS value
            FROM `'.PREFIX.'product_user_property_data` as pup
            LEFT JOIN `'.PREFIX.'property` as prop
              ON pup.prop_id = prop.id ';
      
      if($catSet = trim(DB::quote(implode(',', $categoryIds)), "'")) {
        $categoryIds = array_unique($categoryIds);
        $sql .= '
          LEFT JOIN  `'.PREFIX.'category_user_property` as cup
          ON cup.property_id = prop.id ';
        $whereCat = ' AND cup.category_id IN ('.$catSet.') ';
      }

      $sql .= 'WHERE pup.`product_id` '.$where.$whereCat;
      $sql .= 'ORDER BY `sort` DESC';

      $res = DB::query($sql);

      while ($userFields = DB::fetchAssoc($res)) {
       // Обновляет данные по значениям характеристик, только для тех хар. которые  назначены для категории текущего товара.
       // Это не работает в фильтрах и сравнениях.
        $userFields['value'] = $userFields['value'];
        if((int)MG::getSetting('catalogProp') > 1) {
          if(($userFields['type'] != 'string') && ($userFields['type'] != 'textarea') && ($userFields['type'] != 'select')) {
            $resIn = DB::query('SELECT GROUP_CONCAT(ipd.name,\'#\',ipupd.margin,\'#|\') AS value
              FROM '.PREFIX.'product_user_property_data AS ipupd
              LEFT JOIN '.PREFIX.'property_data AS ipd ON ipupd.prop_data_id = ipd.id 
              WHERE ipupd.product_id = '.$userFields['product_id'].' AND ipupd.prop_id = '.$userFields['prop_id'].' AND ipupd.active = 1');
            while($rowIn = DB::fetchAssoc($resIn)) {
              $userFields['value'] = str_replace(',', '', mb_substr($rowIn['value'], 0, -1));
            }
          }
        }
        // дописываем в массив пользовательских характеристик,
        // все переопределенные для каждого товара, оставляя при
        // этом не измененные характеристики по умолчанию
        $arrayProducts[$idsProduct[$userFields['product_id']]]['thisUserFields'][$userFields['prop_id']] = $userFields;
        // добавляем польз характеристики ко всем вариантам продукта
        if(!empty($idsVariantProduct[$userFields['product_id']])) {
          foreach ($idsVariantProduct[$userFields['product_id']]  as $keyPages ) {
             $arrayProducts[$keyPages]['thisUserFields'][$userFields['prop_id']] = $userFields;
          }
        }
      }
    }
    

    return $arrayProducts;
  }

}