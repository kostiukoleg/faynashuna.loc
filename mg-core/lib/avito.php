<?php
/**
 * Класс Avito используется для создания и редактирования выгрузок на Avito
 *
 * @package moguta.cms
 * @subpackage Libraries
 */
class Avito {
   /**
	* Подготавливает данные для страницы интеграции
	*/
	static function createPage() {

		$rows = DB::query("SELECT COUNT(id) FROM `".PREFIX."avito_cats`;");
		$res = DB::fetchAssoc($rows);
		//MG::loger($res['COUNT(id)']);
		$databaseError = false;

		if ($res['COUNT(id)'] != 577) {
			$databaseError = true;
		}

		$rows = DB::query("SELECT COUNT(id) FROM `".PREFIX."avito_locations`;");
		$res = DB::fetchAssoc($rows);
		//MG::loger($res['COUNT(id)']);

		if ($res['COUNT(id)'] != 4016) {
			$databaseError = true;
		}

		$rows = DB::query("SELECT `name` FROM `".PREFIX."avito_settings` ORDER BY `edited` DESC");
		while ($row = DB::fetchAssoc($rows)) {
			$names[] = $row['name'];
		}

		$array = array();
		$res = DB::query('SELECT DISTINCT * FROM '.PREFIX.'category WHERE parent = 0 GROUP BY sort ASC');
		while($row = DB::fetchAssoc($res)) {
			$array[] = $row;
		}

		// необходимо для корректного вывода таблицы
		$_SESSION['categoryCountToAdmin'] = 0;
		$categoryList = Category::getPagesSimple($array, 0, 0);
		if($categoryList == '') {
			$categoryList = '<tr><td colspan="6" style="text-align:center;">Категории не найдены</td></tr>';
		}
		unset($_SESSION['categoryCountToAdmin']);

		$rows = DB::query("SELECT `title` FROM `".PREFIX."product` WHERE 1=1 LIMIT 0,1");
		while ($row = DB::fetchAssoc($rows)) {
			$exampleName = $row['title'];
		}

		$regionOptions = '<option value="-5">Введите или выберите регион</option>';
		$rows = DB::query("SELECT `id`, `name` FROM `".PREFIX."avito_locations` WHERE `type` = 1");
		while ($row = DB::fetchAssoc($rows)) {
			$regionOptions .= '<option value="'.$row['id'].'">'.$row['name'].'</option>';
		}
		// echo '<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css" />';
		echo '<script type="text/javascript">
			includeJS("'.SITE.'/mg-core/script/admin/category.js")
			</script>';
		echo '<script src="'.SITE.'/mg-core/script/admin/integrations/'.pathinfo(__FILE__, PATHINFO_FILENAME).'.js"></script>';
		include('mg-admin/section/views/integrations/'.basename(__FILE__));
	}
   /**
	* Получение списка городов Avito
	* @return bool
	*/
	static function getCitys($region){
		$cityOptions = '<option value="-5">Введите или выберите город</option>';

		$rows = DB::query("SELECT `id`, `name` FROM `".PREFIX."avito_locations` WHERE `type` = 2 AND `parent_id` = ".DB::quoteInt($region));
		while ($row = DB::fetchAssoc($rows)) {
			$cityOptions .= '<option value="'.$row['id'].'">'.$row['name'].'</option>';
		}
		return $cityOptions;
	}
   /**
	* Получение списка метро и районов Avito
	* @return bool
	*/
	static function getSubways($city){
		$subwayOptions = '';
		$districtOptions = '';
		$rows = DB::query("SELECT `id`, `name`, `type` FROM `".PREFIX."avito_locations` WHERE `type` IN (3,4) AND `parent_id` = ".DB::quoteInt($city));
		while ($row = DB::fetchAssoc($rows)) {
			if ($row['type'] == 3) {
				$subwayOptions .= '<option value="'.$row['id'].'">'.$row['name'].'</option>';
			}
			if ($row['type'] == 4) {
				$districtOptions .= '<option value="'.$row['id'].'">'.$row['name'].'</option>';
			}
		}
		return array('subways' => $subwayOptions, 'districts' => $districtOptions);
	}
   /**
	* Записывает категории и локации avito в базу данных
	* @return bool
	*/
	static function updateDB(){
		$ds = DIRECTORY_SEPARATOR;
		$dir = URL::getDocumentRoot();

		DB::query("TRUNCATE TABLE `".PREFIX."avito_cats`;");

		$values = file_get_contents('zip://'.$dir.'mg-admin'.$ds.'section'.$ds.'views'.$ds.'integrations'.$ds.'avito_cats.zip#cats.txt');
		DB::query("INSERT INTO `".PREFIX."avito_cats` (`id`, `name`, `parent_id`) VALUES ".$values.";");

		DB::query("TRUNCATE TABLE `".PREFIX."avito_locations`;");

		for ($i=1; $i < 5; $i++) { 
			$values = file_get_contents('zip://'.$dir.'mg-admin'.$ds.'section'.$ds.'views'.$ds.'integrations'.$ds.'avito_cats.zip#locations_'.$i.'.txt');
			DB::query("INSERT INTO `".PREFIX."avito_locations` (`id`, `name`, `type`, `parent_id`) VALUES ".$values.";");
		}

		return true;
	}
   /**
	* Возвращает название avito категории по ID.
	* @param string $id ID avito категории
	* @return string название avito категории
	*/
	static function getCatName($id) {
		
		$res = DB::query("SELECT `name` FROM `".PREFIX."avito_cats` WHERE `id` = ".DB::quoteInt($id));
		$row = DB::fetchAssoc($res);
		return $row['name'];
	}
   /**
	* Возвращает верстку для выбора avito категорий по ID родительской категории.
	* @param int $id ID avito категории
	* @param int $shopCatId ID категории магазина
	* @param string $uploadName название выгрузки
	* @return array массив с версткой селектов и ID возможных выборов
	*/
	static function buildSelects($id, $shopCatId, $uploadName){

		$res = DB::query("SELECT `parent_id`, `name` FROM `".PREFIX."avito_cats` WHERE `id` = ".DB::quote((int)$id));
		$row = DB::fetchAssoc($res);
		$parentId = $row['parent_id'];
		$parentsArray = array();
		$parentsArrayWords = array();
		$html = '';

		if ($parentId != 0) {
			array_push($parentsArray, $parentId);
			array_push($parentsArrayWords, $row['name']);
		}

		while ($parentId != 0) {//иерархия выборов

			$res = DB::query("SELECT `parent_id`, `name` FROM `".PREFIX."avito_cats` WHERE `id` = ".DB::quote((int)$parentId));
			$row = DB::fetchAssoc($res);
			$parentId = $row['parent_id'];

			if ($parentId != 0) {
				array_unshift($parentsArray, $parentId);
				array_unshift($parentsArrayWords, $row['name']);
			}
		}

		//базовый селект
		$res = DB::query("SELECT `id`, `name`
			FROM `".PREFIX."avito_cats` 
			WHERE `parent_id` = 0");
		$html .= '<select class="customCatSelect">';
		$html .= '<option value="-5">Не выбрано</option>';
		while ($row = DB::fetchAssoc($res)) {
			if ($row['id'] > 0) {
				$html .= '<option value="'.$row['id'].'">'.$row['name'].'</option>';
			}
		}

		$html .= '</select>';

		foreach ($parentsArray as $key => $value) {//вторичные селекты

			$res = DB::query("SELECT `id`, `name`
				FROM `".PREFIX."avito_cats` 
				WHERE `parent_id` = ".DB::quote((int)$value));
			$html .= '<select class="customCatSelect">';
			$html .= '<option value="-5">Не выбрано</option>';

			while ($row = DB::fetchAssoc($res)) {
				$html .= '<option value="'.$row['id'].'">'.$row['name'].'</option>';
			}
			$html .= '</select>';
			
		}

		if ($id > 0) {//следующий селект
			$res = DB::query("SELECT `id`, `name`
				FROM `".PREFIX."avito_cats` 
				WHERE `parent_id` = ".DB::quote((int)$id));

			if ($res->num_rows > 0) {
				$html .= '<select class="customCatSelect">';
				$html .= '<option value="-5">Не выбрано</option>';

				while ($row = DB::fetchAssoc($res)) {
					$html .= '<option value="'.$row['id'].'">'.$row['name'].'</option>';
				}
				$html .= '</select>';
			}
			
		}

		array_push($parentsArray, $id);
		
		$additional = self::buildSelectsAdditional($parentsArray, $parentsArrayWords, $shopCatId, $uploadName);

		$data = array('html' => $html.$additional, 'choices' => $parentsArray);

		return $data;
	}
	/**
	* Возвращает верстку для выбора avito категорий.
	* @param array $parentsArray массив выборов (id)
	* @param array $parentsArray массив выборов (названия)
	* @param int $shopCatId id категории магазина
	* @param string $uploadName название выгрузки
	* @return string
	*/
	static function buildSelectsAdditional($parentsArray, $parentsArrayWords, $shopCatId, $uploadName){
		$res = DB::query("SELECT `id`, `name`
			FROM `".PREFIX."avito_cats` 
			WHERE `id` = ".DB::quoteInt($parentsArray[0]));
		$rows = DB::fetchArray($res);
		array_unshift($parentsArrayWords, $rows['name']);
		
		$selects = array();

		if ($parentsArrayWords[0] == 'Хобби и отдых' && 
			($parentsArrayWords[1] == 'Охота и рыбалка' || ($parentsArrayWords[1] != 'Охота и рыбалка' && strlen($parentsArrayWords[2])))
			) {
			$selects = array(
				'header' => 'Вид объявления',
				'name' => 'AdType',
				'options' => array(
					'Товар приобретен на продажу' => 'Товар приобретен на продажу',
					'Товар от производителя' => 'Товар от производителя',
				)
			);
		}

		if ($parentsArrayWords[0] == 'Для дома и дачи' && $parentsArrayWords[1] == 'Бытовая техника') {
			$selects = array(
				'header' => 'Вид объявления',
				'name' => 'AdType',
				'options' => array(
					'Товар приобретен на продажу' => 'Товар приобретен на продажу',
					'Товар от производителя' => 'Товар от производителя',
				)
			);
		}

		if ($parentsArrayWords[0] == 'Транспорт' && $parentsArrayWords[1] == 'Мотоциклы и мототехника' && $parentsArrayWords[2] == 'Мотоциклы') {
			$selects = array(
				'header' => '',
				'name' => 'MotoType',
				'options' => array(
					'Дорожные' => 'Дорожные',
					'Кастом-байки' => 'Кастом-байки',
					'Кросс и эндуро' => 'Кросс и эндуро',
					'Спортивные' => 'Спортивные',
					'Чопперы' => 'Чопперы',
				)
			);
		}

		if ($parentsArrayWords[3] != '' && $parentsArrayWords[3] != 'Другое' && $parentsArrayWords[3] != 'Пиджаки и костюмы' && $parentsArrayWords[3] != 'Шапки, варежки, шарфы' &&
			($parentsArrayWords[2] == 'Женская одежда' || $parentsArrayWords[2] == 'Мужская одежда'	|| $parentsArrayWords[2] == 'Для девочек' || $parentsArrayWords[2] == 'Для мальчиков')
			) {
			$props = array();
			$rows = DB::query("SELECT `id`, `name` FROM `".PREFIX."property` WHERE `type` IN ('assortmentCheckBox', 'string', 'size') ORDER BY `sort` asc");
			while ($row = DB::fetchAssoc($rows)) {
				$props[$row['id']] = $row['name'];
			}
			$selects = array(
				'header' => 'Выберите характеристику с размером одежды или обуви',
				'name' => 'Size',
				'options' => $props
			);
		}

		if ($parentsArrayWords[0] == 'Транспорт' && $parentsArrayWords[1] == 'Запчасти и аксессуары') {
			$selects = array(
				'header' => '',
				'name' => 'TypeId',
				'options' => array(
					'11-618' => 'Запчасти / Для автомобилей / Автосвет',
					'11-619' => 'Запчасти / Для автомобилей / Аккумуляторы',
					'16-827' => 'Запчасти / Для автомобилей / Двигатель / Блок цилиндров, головка, картер',
					'16-828' => 'Запчасти / Для автомобилей / Двигатель / Вакуумная система',
					'16-829' => 'Запчасти / Для автомобилей / Двигатель / Генераторы, стартеры',
					'16-830' => 'Запчасти / Для автомобилей / Двигатель / Двигатель в сборе',
					'16-831' => 'Запчасти / Для автомобилей / Двигатель / Катушка зажигания, свечи, электрика',
					'16-832' => 'Запчасти / Для автомобилей / Двигатель / Клапанная крышка',
					'16-833' => 'Запчасти / Для автомобилей / Двигатель / Коленвал, маховик',
					'16-834' => 'Запчасти / Для автомобилей / Двигатель / Коллекторы',
					'16-835' => 'Запчасти / Для автомобилей / Двигатель / Крепление двигателя',
					'16-836' => 'Запчасти / Для автомобилей / Двигатель / Масляный насос, система смазки',
					'16-837' => 'Запчасти / Для автомобилей / Двигатель / Патрубки вентиляции',
					'16-838' => 'Запчасти / Для автомобилей / Двигатель / Поршни, шатуны, кольца',
					'16-839' => 'Запчасти / Для автомобилей / Двигатель / Приводные ремни, натяжители',
					'16-840' => 'Запчасти / Для автомобилей / Двигатель / Прокладки и ремкомплекты',
					'16-841' => 'Запчасти / Для автомобилей / Двигатель / Ремни, цепи, элементы ГРМ',
					'16-842' => 'Запчасти / Для автомобилей / Двигатель / Турбины, компрессоры',
					'16-843' => 'Запчасти / Для автомобилей / Двигатель / Электродвигатели и компоненты',
					'11-621' => 'Запчасти / Для автомобилей / Запчасти для ТО',
					'16-805' => 'Запчасти / Для автомобилей / Кузов / Балки, лонжероны',
					'16-806' => 'Запчасти / Для автомобилей / Кузов / Бамперы',
					'16-807' => 'Запчасти / Для автомобилей / Кузов / Брызговики',
					'16-808' => 'Запчасти / Для автомобилей / Кузов / Двери',
					'16-809' => 'Запчасти / Для автомобилей / Кузов / Заглушки',
					'16-810' => 'Запчасти / Для автомобилей / Кузов / Замки',
					'16-811' => 'Запчасти / Для автомобилей / Кузов / Защита',
					'16-812' => 'Запчасти / Для автомобилей / Кузов / Зеркала',
					'16-813' => 'Запчасти / Для автомобилей / Кузов / Кабина',
					'16-814' => 'Запчасти / Для автомобилей / Кузов / Капот',
					'16-815' => 'Запчасти / Для автомобилей / Кузов / Крепления',
					'16-816' => 'Запчасти / Для автомобилей / Кузов / Крылья',
					'16-817' => 'Запчасти / Для автомобилей / Кузов / Крыша',
					'16-818' => 'Запчасти / Для автомобилей / Кузов / Крышка, дверь багажника',
					'16-819' => 'Запчасти / Для автомобилей / Кузов / Кузов по частям',
					'16-820' => 'Запчасти / Для автомобилей / Кузов / Кузов целиком',
					'16-821' => 'Запчасти / Для автомобилей / Кузов / Лючок бензобака',
					'16-822' => 'Запчасти / Для автомобилей / Кузов / Молдинги, накладки',
					'16-823' => 'Запчасти / Для автомобилей / Кузов / Пороги',
					'16-824' => 'Запчасти / Для автомобилей / Кузов / Рама',
					'16-825' => 'Запчасти / Для автомобилей / Кузов / Решетка радиатора',
					'16-826' => 'Запчасти / Для автомобилей / Кузов / Стойка кузова',
					'11-623' => 'Запчасти / Для автомобилей / Подвеска',
					'11-624' => 'Запчасти / Для автомобилей / Рулевое управление',
					'11-625' => 'Запчасти / Для автомобилей / Салон',
					'16-521' => 'Запчасти / Для автомобилей / Система охлаждения',
					'11-626' => 'Запчасти / Для автомобилей / Стекла',
					'11-627' => 'Запчасти / Для автомобилей / Топливная и выхлопная системы',
					'11-628' => 'Запчасти / Для автомобилей / Тормозная система',
					'11-629' => 'Запчасти / Для автомобилей / Трансмиссия и привод',
					'11-630' => 'Запчасти / Для автомобилей / Электрооборудование',
					'6-401' => 'Запчасти / Для мототехники',
					'6-406' => 'Запчасти / Для спецтехники',
					'6-411' => 'Запчасти / Для водного транспорта',
					'4-943' => 'Аксессуары',
					'21' => 'GPS-навигаторы',
					'4-942' => 'Автокосметика и автохимия',
					'20' => 'Аудио- и видеотехника',
					'4-964' => 'Багажники и фаркопы',
					'4-963' => 'Инструменты',
					'4-965' => 'Прицепы',
					'11-631' => 'Противоугонные устройства / Автосигнализации',
					'11-632' => 'Противоугонные устройства / Иммобилайзеры',
					'11-633' => 'Противоугонные устройства / Механические блокираторы',
					'11-634' => 'Противоугонные устройства / Спутниковые системы',
					'22' => 'Тюнинг',
					'10-048' => 'Шины, диски и колёса / Шины',
					'10-047' => 'Шины, диски и колёса / Мотошины',
					'10-046' => 'Шины, диски и колёса / Диски',
					'10-045' => 'Шины, диски и колёса / Колёса',
					'10-044' => 'Шины, диски и колёса / Колпаки',
					'6-416' => 'Экипировка',
				)
			);
		}

		if (empty($selects)) {
			return '';
		}
		else{
			$res = DB::query("SELECT `additional`
				FROM `".PREFIX."avito_settings` 
				WHERE `name` = ".DB::quote($uploadName));
			$row = DB::fetchArray($res);
			$additional = unserialize(stripslashes($row['additional']));

			$html = '';
			if ($selects['header'] != '') {
				$html = '<p>'.$selects['header'].':</p>';
			}
			$html .= '<select class="additionalCatSelect" paramName="'.$selects['name'].'">';

			foreach ($selects['options'] as $key => $value) {
				$selected = '';
				if ($key == $additional[$shopCatId][$selects['name']]) {
					$selected = ' selected';
				}
				$html .= '<option value="'.$key.'"'.$selected.'>'.$value.'</option>';
			}
			$html .= '</select>';
			return $html;
		}

	}
   /**
	* Возвращает список соответствий avito категорий и категорий магазина по названию выгрузки.
	* @param string $name название выгрузки
	* @return array массив соответствий ID категорий
	*/
	static function getCats($name){
		$rows = DB::query("SELECT `cats` FROM `".PREFIX."avito_settings` WHERE `name` = ".DB::quote($name));
		while ($row = DB::fetchAssoc($rows)) {
			$res = $row['cats'];
		}

		$cats = unserialize(stripslashes($res));

		return $cats;
	}
   /**
	* Применяет соответствующую avito категорию ко всем вложенным категориям магазина.
	* @param string $shopId ID категории магазина
	* @param string $avitoId ID категории avito
	* @param string $name название выгрузки
	* @return bool
	*/
	static function updateCatsRecurs($shopId, $avitoId, $name){
		$rows = DB::query("SELECT `cats`, `additional` FROM `".PREFIX."avito_settings` WHERE `name` = ".DB::quote($name));
		while ($row = DB::fetchAssoc($rows)) {
			$res = $row;
		}

		$cats = unserialize(stripslashes($res['cats']));
		$additional = unserialize(stripslashes($res['additional']));
		$model = new Category;
		$catIds = $model->getCategoryList($shopId);

		foreach ($catIds as $key => $value) {
			$cats[$value] = $avitoId;
			if (empty($additional[$shopId])) {
				unset($additional[$value]);
			}
			else{
				$additional[$value] = $additional[$shopId];
			}
		}
		$cats = addslashes(serialize($cats));
		$additional = addslashes(serialize($additional));

		DB::query("UPDATE `".PREFIX."avito_settings` SET `cats`=".DB::quote($cats).", `additional`=".DB::quote($additional)." WHERE `name`=".DB::quote($name));

		return true;
	}
   /**
	* Сохраняет соответствие avito категории и категории магазина.
	* @param int $shopId ID категории магазина
	* @param int $avitoId ID категории avito
	* @param string $name название выгрузки
	* @param array $addArr массив дополнительных параметров
	* @return bool
	*/
	static function saveCat($shopId, $avitoId, $name, $addArr){
		$rows = DB::query("SELECT `cats`, `additional` FROM `".PREFIX."avito_settings` WHERE `name` = ".DB::quote($name));
		while ($row = DB::fetchAssoc($rows)) {
			$res = $row;
		}
		$cats = unserialize(stripslashes($res['cats']));
		$additional = unserialize(stripslashes($res['additional']));
		$cats[$shopId] = $avitoId;

		if (empty($addArr)) {
			unset($additional[$shopId]);
		}
		else{
			foreach ($addArr as $value) {
				if (!$value['paramName'] || !$value['val']) {continue;}
				$additional[$shopId][$value['paramName']] = $value['val'];
			}
		}
		
		$cats = addslashes(serialize($cats));
		$additional = addslashes(serialize($additional));

		DB::query("UPDATE `".PREFIX."avito_settings` SET `cats`=".DB::quote($cats).", `additional`=".DB::quote($additional)." WHERE `name`=".DB::quote($name));

		return true;
	}
   /**
	* Создает новую выгрузку.
	* @param string $name название выгрузки
	* @return string|bool название выгрузки, если оно уникально или false если повторяется
	*/
	static function newTab($name) {
		$dbRes = DB::query("SELECT `name` FROM `".PREFIX."avito_settings` WHERE `name` = ".DB::quote($name));

		if(!$row = DB::fetchArray($dbRes)) {
			DB::query("INSERT IGNORE INTO  `".PREFIX."avito_settings` (`name`) VALUES (".DB::quote($name).")");
			return $name;
		}

		return false;
	}
   /**
	* Сохраняет настройки выгрузки.
	* @param string $name название выгрузки
	* @param array $data массив с данными для сохранения
	* @return bool
	*/
	static function saveTab($name, $data) {

		$data['manager'] = mb_substr($data['manager'], 0, 39);
		if ($data['subway'] < 0) {unset($data['subway']);}
		if ($data['district'] < 0) {unset($data['district']);}

		$data = addslashes(serialize($data));

		DB::query("UPDATE `".PREFIX."avito_settings` SET `settings`=".DB::quote($data)." WHERE `name`=".DB::quote($name));

		return true;
	}
   /**
	* Получает настройки выгрузки.
	* @param string $name название выгрузки
	* @return array массив с данными выгрузки
	*/
	static function getTab($name) {
		$rows = DB::query("SELECT `settings` FROM `".PREFIX."avito_settings` WHERE `name` = ".DB::quote($name));
		while ($row = DB::fetchAssoc($rows)) {
			$res = $row['settings'];
		}

		$options = unserialize(stripslashes($res));
		
		$options['ignoreProducts'] = self::getRelated($options['ignoreProducts']);

		$options['cityOptions'] = '<option value="-5">Введите или выберите город</option>';
		$options['subwayOptions'] = '';
		$options['districtOptions'] = '';
		$rows = DB::query("SELECT `id`, `name`, `type` FROM `".PREFIX."avito_locations` WHERE 
			(`type` = 2 AND `parent_id` = ".DB::quoteInt($options['region']).") OR 
			(`type` IN (3,4) AND `parent_id` = ".DB::quoteInt($options['city']).")");
		while ($row = DB::fetchAssoc($rows)) {
			if ($row['type'] == 2) {
				$options['cityOptions'] .= '<option value="'.$row['id'].'">'.$row['name'].'</option>';
			}
			if ($row['type'] == 3) {
				$options['subwayOptions'] .= '<option value="'.$row['id'].'">'.$row['name'].'</option>';
			}
			if ($row['type'] == 4) {
				$options['districtOptions'] .= '<option value="'.$row['id'].'">'.$row['name'].'</option>';
			}
		}
		if ($options['cityOptions'] == '<option value="-5">Введите или выберите город</option>') {
			$options['cityOptions'] = '<option value="-5">Для выбора города выберите регион</option>';
		}
		
		return $options;
	}
   /**
	* Удаляет выгрузку.
	* @param string $name название выгрузки
	* @return bool
	*/
	static function deleteTab($name) {
		DB::query("DELETE FROM `".PREFIX."avito_settings` WHERE `name`=".DB::quote($name));
		
		return true;
	}
   /**
	* Возвращает данные игнорируемых товаров.
	* @param string $option артикулы товаров
	* @return array массив с данными игнорируемых товаров
	*/
	static function getRelated($option) {
		$stringRelated = ' null';
	    $sortRelated = array();
	    if (!empty($option)) {
	      foreach (explode(',', $option) as $item) {
	        $stringRelated .= ','.DB::quote($item);
	        if (!empty($item)) {
	          $sortRelated[$item] = $item;
	        }
	      }
	      $stringRelated = substr($stringRelated, 1);
	    }

	    $res = DB::query('
	      SELECT  CONCAT(c.parent_url,c.url) as category_url,
	        p.url as product_url, p.id, p.image_url,p.price_course as price,p.title,p.code
	      FROM `'.PREFIX.'product` p
	        LEFT JOIN `'.PREFIX.'category` c
	        ON c.id = p.cat_id
	        LEFT JOIN `'.PREFIX.'product_variant` AS pv
	        ON pv.product_id = p.id
	      WHERE p.code IN ('.$stringRelated.') OR pv.code IN ('.$stringRelated.')');

	    while ($row = DB::fetchAssoc($res)) {
	      $img = explode('|', $row['image_url']);
	      $row['image_url'] = $img[0];
	      $sortRelated[$row['code']] = $row;
	    }
	    $productsRelated = array();

	    if (!empty($sortRelated)) {
	      foreach ($sortRelated as $item) {
	        if (is_array($item)) {
	          $item['image_url'] = mgImageProductPath($item['image_url'], $item['id'], 'small');
	          $productsRelated[] = $item;
	        }
	      }
	    }
	    
	    return $productsRelated;
	}
	/**
	* Приводит характеристики к нормальному виду
	* @param array $thisUserFields массив с характеристиками
	* @return array массив с характеристиками
	*/
	static function convertProps($thisUserFields) {
		$result = array();
		if (!empty($thisUserFields)) {
			foreach ($thisUserFields as $key => $value) {
				$tmp = array();

				$name = explode('[', $value['name']);
				$name = $name[0];

				if (strlen($name) < 1) {continue;}

				switch ($value['type']) {
					case 'string':
						if (strlen($value['data'][0]['name']) < 1) {break;}
						$result['string'][$value['prop_id']] = array(
							'id' => $value['prop_id'],
							'name' => $name,
							'unit' => $value['unit'],
							'active' => $value['activity'],
							'value' => $value['data'][0]['name']
						);
						break;
					
					case 'assortmentCheckBox':
						foreach ($value['data'] as $k => $v) {
							if ($v['active'] > 0 && strlen($v['name']) > 0) {
								$tmp[] = $v['name'];
							}
						}
						if (count($tmp) > 0) {
							$result['string'][$value['prop_id']] = array(
								'id' => $value['prop_id'],
								'name' => $name,
								'unit' => $value['unit'],
								'active' => $value['activity'],
								'value' => implode(', ', $tmp)
							);
						}
						break;
					
					case 'color':
						foreach ($value['data'] as $k => $v) {
							if ($v['active'] > 0 && strlen($v['name']) > 0) {
								$tmp[$v['prop_data_id']] = array(
									'id' => $value['prop_id'],
									'name' => $name,
									'active' => $value['activity'],
									'value' => $v['name']
								);
							}
						}
						if (count($tmp) > 0) {
							$result['color'] = $tmp;
						}
						break;
					
					case 'size':
						foreach ($value['data'] as $k => $v) {
							if ($v['active'] > 0 && strlen($v['name']) > 0) {
								$tmp[$v['prop_data_id']] = array(
									'id' => $value['prop_id'],
									'name' => $name,
									'value' => $v['name'],
									'unit' => $value['unit'],
									'active' => $value['activity']
								);
							}
						}
						if (count($tmp) > 0) {
							$result['size'] = $tmp;
						}
						break;
					default:
						# code...
						break;
				}
			}
			// mg::loger($result);
			return $result;
		}
		return false;
	}
	/**
	* Конвертирует цену в рубли.
	* @param array $rates массив соотношений валют
	* @param float $price цена товара
	* @param string $currency валюта товара
	* @return float цена товара в рублях
	*/
	static function convertToRub($rates, $price, $currency) {
		$iso = false;
		if (array_key_exists('RUB', $rates)) {
			$iso = 'RUB';
		}
		if (array_key_exists('RUR', $rates)) {
			$iso = 'RUR';
		}
		if ($iso && array_key_exists($currency, $rates)) {
			return (float)round($price*$rates[$currency]/$rates[$iso],2);
		}
 		return $price;
	}
	/**
	* Возвращает массив названий категорий.
	* @param array $cats массив соотношения категорий магазина и авито
	* @param int $catId id категории товара
	* @param array $allCatsArr массив со всеми категориями авито
	* @return array
	*/
	static function fixCatNames($cats, $catId, $allCatsArr) {

		$parent = $allCatsArr[$cats[$catId]]['parent'];
		$catnames = array($allCatsArr[$cats[$catId]]['name']);
		
		while ($parent != 0) {
			array_unshift($catnames, $allCatsArr[$parent]['name']);
			$parent = $allCatsArr[$parent]['parent'];
		}

		$result = array();

		if ($catnames[0] == 'Для дома и дачи' && $catnames[1] == 'Бытовая техника') {
			$result['Category'] = $catnames[1];
			$result['GoodsType'] = $catnames[3];
		}
		elseif ($catnames[0] == 'Личные вещи' && ($catnames[1] == 'Одежда, обувь, аксессуары' || $catnames[1] == 'Детская одежда и обувь')) {
			$result['Category'] = $catnames[1];
			$result['GoodsType'] = $catnames[2];
			$result['Apparel'] = $catnames[3];
		}
		elseif ($catnames[0] == 'Транспорт' && ($catnames[1] == 'Мотоциклы и мототехника' || $catnames[1] == 'Водный транспорт')) {
			$result['Category'] = $catnames[1];
			$result['VehicleType'] = $catnames[2];
		}
		elseif ($catnames[0] == 'Предложение услуг') {
			$result['ServiceType'] = $catnames[1];
			$result['ServiceSubype'] = $catnames[2];
		}
		elseif ($catnames[0] == 'Хобби и отдых' && $catnames[1] == 'Велосипеды') {
			$result['Category'] = $catnames[1];
			$result['VehicleType'] = $catnames[2];
		}
		elseif ($catnames[0] == 'Животные' && ($catnames[1] == 'Собаки' || $catnames[1] == 'Кошки')) {
			$result['Category'] = $catnames[1];
			$result['Breed'] = $catnames[2];
		}
		else {
			$result['Category'] = $catnames[1];
			$result['GoodsType'] = $catnames[2];
		}

		return $result;
	}
   /**
	* Создает результат выгрузки.
	* @param string $name название выгрузки
	* @return string результат выгрузки
	*/
	static function constructXML($name){

		$rows = DB::query("SELECT `settings` FROM `".PREFIX."avito_settings` WHERE `name` = ".DB::quote($name));
		while ($row = DB::fetchAssoc($rows)) {
			$res = $row['settings'];
		}

		if (strlen($res) > 1) {

			$ds = DIRECTORY_SEPARATOR;
			$options = unserialize(stripslashes($res));
			$rows = DB::query("SELECT `cats`, `additional` FROM `".PREFIX."avito_settings` WHERE `name` = ".DB::quote($name));
			while ($row = DB::fetchAssoc($rows)) {
				$res = $row;
			}
			$cats = unserialize(stripslashes($res['cats']));
			$cats = array_filter($cats);
			$additional = unserialize(stripslashes($res['additional']));

			$tmpLocArr = array($options['region'], $options['city']);

			if ($options['subway']) {
				$tmpLocArr[] = $options['subway'];
			}

			if ($options['district']) {
				$tmpLocArr[] = $options['district'];
			}

			$rows = DB::query("SELECT `name`, `type` FROM `".PREFIX."avito_locations` WHERE `id` IN (".DB::quoteIN($tmpLocArr).")");
			while ($row = DB::fetchAssoc($rows)) {
				if ($row['type'] == 1) {
					$options['region'] = $row['name'];
				}
				if ($row['type'] == 2) {
					$options['city'] = $row['name'];
				}
				if ($row['type'] == 3) {
					$options['subway'] = $row['name'];
				}
				if ($row['type'] == 4) {
					$options['district'] = $row['name'];
				}
			}

			if ($options['city'] == 'Москва' || $options['city'] == 'Санкт-Петербург') {
				unset($options['city']);
			}

			$rates = MG::getSetting('dbCurrRates');
			if (empty($rates)) {
				$rates = MG::getSetting('currencyRate');
			}

		 	$RSS = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;

			$xml = new XMLWriter();
			$xml->openMemory();
			$xml->setIndent(true);
			$xml->startElement('Ads');///////////////////////////////////////////////////////////////////////////////////////// start xml
			$xml->writeAttribute('target', 'Avito.ru');
			$xml->writeAttribute('formatVersion', '3');
			
			$filter = 'WHERE `cat_id` IN (';

			foreach ($cats as $key => $value) {
				$filter .= DB::quote((int)$key).', ';
			}
			$filter = substr($filter, 0, -2);
			$filter .= ') ';

			if (!empty($options['ignoreProducts'])) {
				$filter .= 'AND (';
				$ignored = explode(',', $options['ignoreProducts']);

				foreach ($ignored as $key => $value) {
					$filter .=" (`code` != ".DB::quote($value).") AND";
				}

				$filter = substr($filter, 0, -3);
				$filter .= ')';
			}

			if ($options['inactiveToo'] == "false") {
				$filter .= ' AND `activity` = 1';
			}
			$allCatsArr = array();
			$res = DB::query("SELECT `id`, `parent_id`, `name` FROM `".PREFIX."avito_cats`");
			while ($row = DB::fetchAssoc($res)) {
				$allCatsArr[$row['id']] = array('name' => $row['name'], 'parent' => $row['parent_id']);
			}

			$model = new Models_Product;

			$res = DB::query("SELECT `id`, `code`, `count` FROM `".PREFIX."product` ".$filter);

			while ($row = DB::fetchAssoc($res)) {

				set_time_limit(30);

				$product = $model->getProduct($row['id'], false, true);
				
				if ($options['useNull'] == 'false') {
					$variants = $model->getVariants($row['id']);
					if (is_array($variants) && !empty($variants)) {
						$empty = true;
						foreach ($variants as $variant) {
							if ($variant['count'] != 0) {
								$empty = false;
							}
						}
						if ($empty) {
							continue;
						}
					}
					else{
						if ((int)$product['count'] == 0) {
							continue;
						}
					}
				}

				$product['description'] = str_replace('&nbsp;', ' ', $product['description']);
				$product['description'] = strip_tags($product['description']);
				$product['description'] = html_entity_decode(htmlspecialchars_decode($product['description']));
				$product['description'] = preg_replace('/&(?!#?[a-z0-9]+;)/', '&amp;', $product['description']);
				$product['description'] = preg_replace('/[[:cntrl:]]/', '', $product['description']);
				$product['description'] = str_replace(array("~", "<", ">"), "", $product['description']);
				$product['description'] = strip_tags($product['description']);
				$product['description'] = preg_replace('/\s+/', ' ',$product['description']);
				$product['description'] = mb_substr(trim($product['description']), 0,2975, 'utf-8');

				$product['title'] = str_replace('&nbsp;', ' ', $product['title']);
				$product['title'] = strip_tags($product['title']);
				$product['title'] = html_entity_decode(htmlspecialchars_decode($product['title']));
				$product['title'] = preg_replace('/&(?!#?[a-z0-9]+;)/', '&amp;', $product['title']);
				$product['title'] = preg_replace('/[[:cntrl:]]/', '', $product['title']);
				$product['title'] = str_replace(array("~", "<", ">"), "", $product['title']);
				$product['title'] = strip_tags($product['title']);
				$product['title'] = preg_replace('/\s+/', ' ',$product['title']);
				$product['title'] = mb_substr(trim($product['title']), 0,49, 'utf-8');

				$xml->startElement('Ad');/////////////////////////////////////////////////////////////////////////////////////// start product

				$xml->startElement('Id');
				$xml->text($row['id']);
				$xml->endElement();

				$xml->startElement('Title');
				$xml->text($product['title']);
				$xml->endElement();

				$product['price'] = self::convertToRub($rates, $product['price'], $$product['currency_iso']);
				$xml->startElement('Price');
				$xml->text($product['price']);
				$xml->endElement();

				$xml->startElement('Description');
				$xml->text('<![CDATA[ '.$product['description'].' ]]>');
				$xml->endElement();

				$catArr = self::fixCatNames($cats, $product['cat_id'], $allCatsArr);

				foreach ($catArr as $key => $value) {
					if ($value) {
						$xml->startElement($key);
						$xml->text($value);
						$xml->endElement();
					}
				}
				
				foreach ($additional[$product['cat_id']] as $key => $value) {
					if ($key == 'Size') {
						$product = $model->getProduct($row['id'], true, true);
						$product['thisUserFields'] = self::convertProps($product['thisUserFields']);							
						$xml->startElement('Size');
						$xml->text($product['thisUserFields']['string'][$value]['value']);
						$xml->endElement();
					}
					else{
						$xml->startElement($key);
						$xml->text($value);
						$xml->endElement();
					}
				}

				if ($options['manager']) {
					$xml->startElement('ManagerName');
					$xml->text($options['manager']);
					$xml->endElement();
				}
				if ($options['phone']) {
					$xml->startElement('ContactPhone');
					$xml->text($options['phone']);
					$xml->endElement();
				}

				$xml->startElement('Region');
				$xml->text($options['region']);
				$xml->endElement();

				if ($options['city']) {
					$xml->startElement('City');
					$xml->text($options['city']);
					$xml->endElement();
				}

				if ($options['subway']) {
					$xml->startElement('Subway');
					$xml->text($options['subway']);
					$xml->endElement();
				}

				if ($options['district']) {
					$xml->startElement('District');
					$xml->text($options['district']);
					$xml->endElement();
				}

				if (!empty($product['images_product'])) {
					$xml->startElement('Images');
					$i=0;
					foreach ($product['images_product'] as $key => $value) {
						if ($i==0 || $i < 10) {
							$xml->startElement('Image');
							$xml->writeAttribute('url', SITE.$ds.'uploads'.$ds.$value);
							$xml->endElement();
						}
						$i++;
					}
					$xml->endElement();
				}
				$xml->endElement();/////////////////////////////////////////////////////  end product
			}
			$xml->endElement();/////////////////////////////////////////////////////////  end xml
			$RSS .= $xml->outputMemory();    
			return html_entity_decode($RSS);
		}
	}
}