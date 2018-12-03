<?php

/**
 * Класс News наследник стандарного Actioner
 * Предназначен для выполнения действий, запрошеных  AJAX функциями
 *
 * @author Avdeev Mark <mark-avdeev@mail.ru>
 */
class News extends Actioner{


  /**
   * Добавляет новость в базу данных.
   *
   * @param array $array массив с данными о новости.
   * @return bool|int в случае успеха возвращает id добавленной новости.
   */
  public function addNews($array){
    // доступно только модераторам и админам.
    USER::AccessOnly('1,4','exit()');
    
    unset($array['id']);
    $result = array();

    $array['url'] = empty($array['url'])?MG::translitIt($array['title']):$array['url'];
    if(strlen($array['url']) > 60){
      $array['url'] = substr($array['url'], 0, 60);
    }

    // Исключает дублирование.
    $dublicatUrl = false;
    $tempArray = $this->getNewsByUrl($array['url']);
    if(!empty($tempArray)){
      $dublicatUrl = true;
    }

    if(DB::buildQuery('INSERT INTO `mpl_news` SET add_date=now(), ', $array)){
      $id = DB::insertId();
      // Если url дублируется, то дописываем к нему id новости.
      if($dublicatUrl){
        $this->updateNews(array('id'=>$id, 'url'=>$array['url'].'_'.$id));
      }

      $array['id'] = $id;
      $result = $array;
    }

    return $result;
  }


  /**
   * Изменяет данные о новости
   *
   * @param array $array массив с данными о новости.
   * @param int $id  id изменяемой новости.
   * @return bool
   */
  public function updateNews($array){
    // доступно только модераторам и админам.
    USER::AccessOnly('1,4','exit()');
    
    $id = $array['id'];
    $result = false;
    if(!empty($id)){
      if(DB::query('
        UPDATE `mpl_news`
        SET '.DB::buildPartQuery($array).'
        WHERE id = '.DB::quote($id))){
        $result = true;
      }
    } else{
     $result = $this->addNews($array);
    }

    return $result;
  }



   //Удаляем новость
  public function deleteNews(){
    // доступно только модераторам и админам.
    USER::AccessOnly('1,4','exit()');
    
    $this->messageSucces = 'Удалена новость  №'.$_POST['id'];
    $this->messageError = 'Не удалось удалить новость!';
    if(DB::query('DELETE FROM `mpl_news` WHERE `id`= '.DB::quote($_POST['id']))){
      return true;
    }
    return false;
  }
  /**
   * Получает новость по его URL.
   *
   * @param string $url запрашиваемой страницы.
   * @return array массив с данными о запрашиваемой страницы.
   */
  public function getNewsByUrl($url){
    $result = array();
    $res = DB::query('
      SELECT *
      FROM `mpl_news`  
      WHERE url="'.DB::quote(URL::getRoute(),1).'.html" OR url="'.DB::quote(URL::getRoute(),1).'"
    ', $url);

    if(!empty($res)){
      if($news = DB::fetchArray($res)){
        $result = $news;
      }
    }

    return $result;
  }
  /**
   * Сохраняет и обновляет параметры страницы.
   * @return type
   */
  public function saveNews(){
    // доступно только модераторам и админам.
    USER::AccessOnly('1,4','exit()');
    
    $this->messageSucces = $this->lang['ACT_SAVE_PAGE'];
    $this->messageError = $this->lang['ACT_NOT_SAVE_PAGE'];

   // if(!empty($_POST['url'])){$_POST['url'].=".html";}

    unset($_POST['pluginHandler']);
    unset($_POST['actionerClass']);
    unset($_POST['action']);

    $_POST['image_url'] = basename($_POST['image_url']);

    if($_POST['image_url']=='no-img.png'){
      $_POST['image_url'] = '';
    }

    //обновление
    if(!empty($_POST['id'])){
   
      $this->updateNews($_POST);
      $this->data = $_POST;
    }else{
      // добавление
      $this->data = $this->updateNews($_POST);
    }
    return true;
  }


   /**
   * Добавляет картинку товара.
   * @return boolean
   */
  public function addImageNews(){

    $path = 'uploads/news/';

    $validFormats = array('jpeg', 'jpg', 'png', 'gif');
    if(isset($_POST) && 'POST' == $_SERVER['REQUEST_METHOD']){

      if(!empty($_FILES['photoimg'])){
        $file_array = $_FILES['photoimg'];
      }else{
        $file_array = $_FILES['edit_photoimg'];
      }

      $name = $file_array['name'];
      $size = $file_array['size'];

      if(strlen($name)){
        //list($txt, $ext) = explode('.', $name);
        $fullName = explode('.', $name);
        $ext = array_pop($fullName);
        $name = implode('.', $fullName);
        if(in_array($ext, $validFormats)){
          if($size < (1024 * 1024)){
            $name = time();
            $actualImageName = $name.'.'.$ext;
            $tmp = $file_array['tmp_name'];
            //подготовка миниатюр 70% и 30% от оригинала
            $this->reSizeImage($name, $ext, $tmp, 0.3);
            $this->reSizeImage($name, $ext, $tmp, 0.7);

            if(move_uploaded_file($tmp, $path.$actualImageName)){

              $this->data = array('img' => $actualImageName);
              $this->messageSucces = $this->lang['ACT_IMG_UPLOAD'];
              return true;
            }else{
              $this->messageError = $this->lang['ACT_IMG_NOT_UPLOAD'];
              return false;
            }
          }else{
            $this->messageError = $this->lang['ACT_IMG_NOT_UPLOAD1'];
            return false;
          }
        }else{
          $this->messageError = $this->lang['ACT_IMG_NOT_UPLOAD2'];
          return false;
        }
      }else{
        $this->messageError = $this->lang['ACT_IMG_NOT_UPLOAD3'];
        return false;
      }
    }
    return false;
  }

  /**
   * Удаляет изображение новости из папки  uploads/news/
   * @return bool
   */
  public function deleteImageNews(){
    if(empty($_POST['msgImg'])){
      $this->messageSucces = $this->lang['ACT_IMG_DEL'];
    }
    $this->messageError = 'Изображение удалено';

    // удаление ссылки на картинку из БД
    $array['id'] = $_POST['id'];
    $array['image_url'] = '';
    if(!empty($array['id'])){
     $this->updateNews($array);
    }

    // удаление картинки с сервера

    $documentroot = str_replace(DIRECTORY_SEPARATOR.'mg-plugins'.DIRECTORY_SEPARATOR.'news','',dirname(__FILE__));

    if(is_file($documentroot."uploads/news/".basename($_POST['imgFile']))){
      unlink($documentroot."uploads/news/".basename($_POST['imgFile']));
      if(is_file($documentroot."uploads/news/thumbs/30_".basename($_POST['imgFile'])))
        unlink($documentroot."uploads/news/thumbs/30_".basename($_POST['imgFile']));
      if(is_file($documentroot."uploads/news/thumbs/70_".basename($_POST['imgFile'])))
        unlink($documentroot."uploads/news/thumbs/70_".basename($_POST['imgFile']));
      return true;
    }


    return false;
  }


  //Получаем параметры новости
  public function getNews(){
    $result = DB::query('
      SELECT *
      FROM `mpl_news`
      WHERE `id` = '.DB::quote($_POST['id'])    
    );

    if($news = DB::fetchAssoc($result)){
      //$news['url'] = str_replace('.html', '', $news['url']);
      $this->data = $news;
      return true;
    }
    return false;
  }

 /**
   * Функция для ресайза картинки
   * @param string $name имя файла без расширения
   * @param string $ext расшерения файла
   * @param string $tmp исходный временный файл
   * @paramint $koef коэффициент сжатия изображения
   * @return void
   */
  public function reSizeImage($name, $ext, $tmp, $koef, $dirUpload='uploads/news/thumbs/'){
      $percent = $koef * 100;
      // получение новых размеров
      list($width_orig, $height_orig) = getimagesize($tmp);
      $width = $koef * $width_orig;
      $height = $koef * $height_orig;
      // ресэмплирование
      $image_p = imagecreatetruecolor($width, $height);
      // вывод
      switch($ext){
        case 'png':
          $image = imagecreatefrompng($tmp);
          //делаем фон изображения белым, иначе в png при прозрачных рисунках фон черный
          $color = imagecolorallocate($image_p, 255, 255, 255);
          imagefill($image_p, 0, 0, $color);

          imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
          imagepng($image_p, $dirUpload.$percent.'_'.$name.'.'.$ext);
          break;

        case 'gif':
          $image = imagecreatefromgif($tmp);
          imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
          imagegif($image_p, $dirUpload.$percent.'_'.$name.'.'.$ext, 100);
          break;

        default:
          $image = imagecreatefromjpeg($tmp);
          imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
          imagejpeg($image_p, $dirUpload.$percent.'_'.$name.'.'.$ext, 100);
      }
      imagedestroy($image_p);
      imagedestroy($image);
  }

   /**
   * Устанавливает количество отображаемых записей в разделе новостей
   * @return boolean
   */
  public function setCountPrintRowsNews(){

    $count = 20;
    if(is_numeric($_POST['count'])&& !empty($_POST['count'])){
      $count = $_POST['count'];
    }


    MG::setOption(array('option'=>'countPrintRowsNews ', 'value'=>$count));
    return true;
  }

}
