<?php

/**
 * Класс Pactioner наследник стандарного Actioner
 * Предназначен для выполнения действий,  AJAX запросов плагина 
 *
 * @author Avdeev Mark <mark-avdeev@mail.ru>
 */
class Pactioner extends Actioner {
 
  private $pluginName = 'preview-photo';
  private $startTime = null;
  private $maxExecTime = null;

  public function start() {
    if(!file_exists('uploads/thumbs/'))
      mkdir ('uploads/thumbs', 0755);
    return $this->process();
  }

  public function process() {
    $this->startTime = microtime(true);
    $this->maxExecTime = min(30, @ini_get("max_execution_time"));
    if (empty($this->maxExecTime)) {
      $this->maxExecTime = 30;
    }
    
    $option = MG::getSetting('preview-photo-option');
    $option = stripslashes($option);
    $options = unserialize($option);
    $realDocumentRoot = str_replace(DIRECTORY_SEPARATOR . 'mg-plugins' . DIRECTORY_SEPARATOR . 'preview-photo', '', dirname(__FILE__));
    $path = $realDocumentRoot . ($options['source'] ? $options['source'] : DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'tempimage');

    $process = false; // флаг запуска процесса
    $count = !empty($_POST['nextItem']) ? $_POST['nextItem'] : 1; // сколько уже обработано файлов
    $imgCount = !empty($_POST['imgCount']) ? $_POST['imgCount'] : 1;
    $model = new Models_Product();
    $log = '';
    
    if($count == 1){
      if($dbRes = DB::query('SELECT COUNT(id) as count FROM `'.PREFIX.'product`')){
        $res = DB::fetchAssoc($dbRes);
        $percent100 = $res['count'];
      }
    }else{
      $percent100 = intval($_POST['total_count']);
    }
    
    $sql = 'SELECT DISTINCT p.id as id, p.image_url as image_url, tmp.images as var_image
      FROM `'.PREFIX.'product` p 
        LEFT JOIN (
          SELECT pv.product_id, GROUP_CONCAT(DISTINCT pv.image ORDER BY pv.image ASC SEPARATOR \'|\') as images 
          FROM `'.PREFIX.'product_variant` pv 
          GROUP BY pv.product_id ) AS tmp ON p.id = tmp.product_id
      GROUP BY p.id
      LIMIT '.($count-1).', 100';
    
    if ($dbRes = DB::query($sql)) {
      
      $options['width70'] = $options['width70'] ? $options['width70'] : 160;
      $options['height70'] = $options['height70'] ? $options['height70'] : 140;
      $options['width30'] = $options['width30'] ? $options['width30'] : 50;
      $options['height30'] = $options['height30'] ? $options['height30'] : 40;
      
      while($product = DB::fetchAssoc($dbRes)){
        $product['image_url'] .= '|'.$product['var_image'];
        $images = explode('|', trim($product['image_url'], '|')); 
				
        foreach($images as $image){		
          $image = URL::parsePageUrl($image);
          // Создаем оригинал
          if(!empty($image) && file_exists($path . DIRECTORY_SEPARATOR . $image)){
            copy($path . DIRECTORY_SEPARATOR . $image, $realDocumentRoot . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $image);

            // Если необходимо, накладываем водяной знак
            if ($options["watter"] == 'true') {
              $this->addWatterMark($realDocumentRoot . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $image);
            }

            // создаем две миниатюры
            $this->reSizeImage('70_' . $image, $realDocumentRoot . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $image, $options['width70'], $options['height70']);
            $this->reSizeImage('30_' . $image, $realDocumentRoot . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $image, $options['width30'], $options['height30']);

            //$item_layout = iconv('windows-1251', 'UTF-8', $image);
            $log .= "\n $imgCount Созданы миниатюры для файла: " . $image;
            $imgCount++;
          }
        }
        
        if($options["new_file_structure"] == 'true'){
          $model->movingProductImage($images, $product['id']);
        }
        
        $count++;
        $execTime = microtime(true) - $this->startTime;
        
        if($execTime + 5 >= $this->maxExecTime){
          $percent = floor(($count * 100) / $percent100);
          $data = array(
            'percent' => $percent,
            'total_count' => $percent100,
            'nextItem' => $count,
            'imgCount' => $imgCount,
            'log' => $log,
          );
					
					if($percent > 100){
            $percent = 100;
          }
          
          $this->messageSucces = "\nОбработано " . $percent . "% товаров";
          $this->data = $data;
          return true;
        }
      }
    }
    
    $percent = floor(($count * 100) / $percent100);
    $data = array(
      'percent' => $percent,
			'total_count' => $percent100,
      'nextItem' => $count,
			'imgCount' => $imgCount,
      'log' => $log,
    );
		
		if($percent > 100){
      $percent = 100;
    }
    
    $this->messageSucces = "\nОбработано " . $percent . "% товаров";
    $this->data = $data;
    
    return true;
  }
  
  function getProductWithImage($image){
    $product = array();
    
    $sql = 'SELECT `id`, `image_url` 
      FROM `'.PREFIX.'product` 
      WHERE `image_url` LIKE \'%'.$image.'%\'';
    
    if($dbRes = DB::query($sql)){
      while($res = DB::fetchAssoc($dbRes)){
        $product[] = $res;
      }
    }
    
    return $product;
  }

  /**
   * Сохраняет  опции плагина
   * @return boolean
   */
  public function saveBaseOption() {
    $this->messageSucces = $this->lang['SAVE_BASE'];
    $this->messageError = $this->lang['NOT_SAVE_BASE'];
    if (!empty($_POST['data'])) {
      MG::setOption(array('option' => 'preview-photo-option', 'value' => addslashes(serialize($_POST['data']))));
    }
    return true;
  }

  /**
   * Функция для ресайза картинки
   * @param string $name имя файла без расширения
   * @param string $tmp исходный временный файл
   * @param int $widthSet заданная ширина изображения
   * @param int $heightSet заданная высота изображения
   * @paramint $koef коэффициент сжатия изображения
   * @return void
   */
  private function reSizeImage($name, $tmp, $widthSet, $heightSet, $dirUpload = 'uploads/thumbs/') {

    $fullName = explode('.', $name);
    $ext = array_pop($fullName);
    $name = implode('.', $fullName);
    list($width_orig, $height_orig) = getimagesize($tmp);

    // $ratio = $heightSet / $height_orig;
    $width = $widthSet;
    $height = $heightSet;

    if ($width_orig > $height_orig) {
      $ratio = $widthSet / $width_orig;
      $width = $widthSet;
      $height = $height_orig * $ratio;
      $height = $height > $heightSet ? $heightSet : $height;
    } else {
      $ratio = $heightSet / $height_orig;
      $width = $width_orig * $ratio;
      $height = $heightSet;
      $width = $width > $widthSet ? $widthSet : $width;
    }

    // ресэмплирование
    $image_p = imagecreatetruecolor($width, $height);

    imageAlphaBlending($image_p, false);
    imageSaveAlpha($image_p, true);

    // вывод
    switch ($ext) {
      case 'png':
        $image = imagecreatefrompng($tmp);
        //делаем фон изображения белым, иначе в png при прозрачных рисунках фон черный
        $black = imagecolorallocate($image, 0, 0, 0);

        // Сделаем фон прозрачным
        imagecolortransparent($image, $black);

        imagealphablending($image_p, false);
        $col = imagecolorallocate($image_p, 0, 0, 0);
        imagefilledrectangle($image_p, 0, 0, $width, $height, $col);
        //imagealphablending( $image_p, true );

        imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
        imagepng($image_p, $dirUpload . $name . '.' . $ext);
        break;

      case 'gif':
        $image = imagecreatefromgif($tmp);
        imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
        imagegif($image_p, $dirUpload . $name . '.' . $ext, 100);
        break;

      default:

        $image = imagecreatefromjpeg($tmp);
        imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
        //imagefilter($image_p, IMG_FILTER_BRIGHTNESS, 15); 
        imagejpeg($image_p, $dirUpload . $name . '.' . $ext, 100);
      // создаём новое изображение
    }
    imagedestroy($image_p);
    imagedestroy($image);
  }

  /**
   * Добавляет водяной знак к картинке
   * @param type $image - путь до картинки на сервере
   * @return boolean
   */
  public function addWatterMark($image) {
    $filename = $image;
    if (!file_exists('uploads/watermark/watermark.png')) {
      return false;
    }
    $size_format = getimagesize($image);
    $format = strtolower(substr($size_format['mime'], strpos($size_format['mime'], '/') + 1));

    // создаём водяной знак
    $watermark = imagecreatefrompng('uploads/watermark/watermark.png');
    imagealphablending($watermark, false);
    imageSaveAlpha($watermark, true);
    // получаем значения высоты и ширины водяного знака
    $watermark_width = imagesx($watermark);
    $watermark_height = imagesy($watermark);

    // создаём jpg из оригинального изображения
    $image_path = $image;



    switch ($format) {
      case 'png':
        $image = imagecreatefrompng($image_path);
        $w = imagesx($image);
        $h = imagesy($image);
        $imageTrans = imagecreatetruecolor($w, $h);
        imagealphablending($imageTrans, false);
        imageSaveAlpha($imageTrans, true);


        $col = imagecolorallocate($imageTrans, 0, 0, 0);
        imagefilledrectangle($imageTrans, 0, 0, $w, $h, $col);
        imagealphablending($imageTrans, true);


        break;
      case 'gif':
        $image = imagecreatefromgif($image_path);
        break;
      default:
        $image = imagecreatefromjpeg($image_path);
    }

    //если что-то пойдёт не так
    if ($image === false) {
      return false;
    }
    $size = getimagesize($image_path);
    // помещаем водяной знак на изображение
    $dest_x = (($size[0]) / 2) - (($watermark_width) / 2);
    $dest_y = (($size[1]) / 2) - (($watermark_height) / 2);

    imagealphablending($image, true);
    imagealphablending($watermark, true);

    imageSaveAlpha($image, true);
    // создаём новое изображение
    imagecopy($image, $watermark, $dest_x, $dest_y, 0, 0, $watermark_width, $watermark_height);

    $imageformat = 'image' . $format;
    if ($format = 'png') {
      $imageformat($image, $filename);
    } else {
      $imageformat($image, $filename, 100);
    }

    // освобождаем память
    imagedestroy($image);
    imagedestroy($watermark);
    return true;
  }

  // сохраняет в папку на сайте оригинальные изображения товаров, собирая их из структуры
  public function getOriginalPhoto(){
    $this->startTime = microtime(true);
    $this->maxExecTime = min(30, @ini_get("max_execution_time"));
    if (empty($this->maxExecTime)) {
      $this->maxExecTime = 30;
    }
    
    $ds = DIRECTORY_SEPARATOR;
    $realDocumentRoot = str_replace($ds . 'mg-plugins' . $ds . 'preview-photo', '', dirname(__FILE__));
    $uploadsProduct =  $realDocumentRoot . $ds . 'uploads' . $ds . 'product';    
    $pathOrig = $realDocumentRoot . $ds . 'uploads' . $ds . 'original';
    
    $count = !empty($_POST['nextItem']) ? $_POST['nextItem'] : 1; // сколько уже обработано файлов    
    
    if(!file_exists($pathOrig)){
      mkdir($pathOrig, 0755);
    }  
    
    if($count == 1){
      if($dbRes = DB::query('SELECT COUNT(id) as count FROM `'.PREFIX.'product`')){
        $res = DB::fetchAssoc($dbRes);
        $percent100 = $res['count'];
      }
    }else{
      $percent100 = intval($_POST['total_count']);
    }
    
    $sql = 'SELECT DISTINCT p.id as id, p.title as title, p.image_url as image_url, tmp.images as var_image
      FROM `'.PREFIX.'product` p 
        LEFT JOIN (
          SELECT pv.product_id, GROUP_CONCAT(DISTINCT pv.image ORDER BY pv.image ASC SEPARATOR \'|\') as images 
          FROM `'.PREFIX.'product_variant` pv 
          GROUP BY pv.product_id ) AS tmp ON p.id = tmp.product_id
      GROUP BY p.id
      LIMIT '.($count-1).', 100';
    
    if($dbRes = DB::query($sql)){
      
      while($product = DB::fetchAssoc($dbRes)){
        $product['image_url'] .= '|'.$product['var_image'];
        $images = explode('|', trim($product['image_url'], '|')); 
        
        foreach($images as $image){
          $dir = floor($product['id']/100).'00'.$ds.$product['id'];
          $image = URL::parsePageUrl($image);
          
          if(file_exists($uploadsProduct.$ds.$dir.$ds.$image)){
            copy($uploadsProduct.$ds.$dir.$ds.$image, $pathOrig.$ds.$image);              
          }
        }
        
        $log .= "\n$count Обработаны изображения товара: " . $product['title'];
        $count++;
        
        $execTime = microtime(true) - $this->startTime;
        
        if($execTime + 5 >= $this->maxExecTime){
          $percent = floor(($count * 100) / $percent100);
          $data = array(
            'percent' => $percent,
            'total_count' => $percent100,
            'nextItem' => $count,            
            'log' => $log,
          );
					
					if($percent > 100){
            $percent = 100;
          }
          
          $this->messageSucces = "\nОбработано " . $percent . "% товаров";
          $this->data = $data;
          return true;
        }
      }
    }
  
    $percent = floor(($count * 100) / $percent100);
    $data = array(
      'percent' => $percent,
      'total_count' => $percent100,
      'nextItem' => $count,            
      'log' => $log,
    );

    if($percent > 100){
      $percent = 100;
    }

    $this->messageSucces = "\nОбработано " . $percent . "% товаров";
    $this->data = $data;
    
    return true;       
  }
}
