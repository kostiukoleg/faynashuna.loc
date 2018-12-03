<?php
/*
  Plugin Name: Новостная лента
  Description: Позволяет вести новостную ленту добавляя и редактируя тексты новостей. После подключения плагина становится доступной страница [sitename]/news.html , на которой отображается список анонсов всех новостей. Чтобы вывести анонсы новостей в любом месте сайта нужно указать шорткод [news-anons count="3"], где count - число анонсов. А также появляется возможность подписаться на RSS рассылку по адресу [sitename]/news/feed
  Author: Avdeev Mark
  Version: 3.0.3
 */

/**
 * При активации плагина, создает таблицу для новостей
 * также создает файл news.php , который будет генерироватьодноименную страницу сайта
 * [sitename]/news.html, при необходимости его можно изменять.
 * На данной странице будут выведены анонсы новостей.
 */
new PluginNews();
mgAddMeta('<link href="'.SITE.'/mg-plugins/news/css/style.css" rel="stylesheet" type="text/css">');

class PluginNews {

  public function __construct() {
    mgActivateThisPlugin(__FILE__, array(__CLASS__, 'createDateBaseNews'));
    mgAddAction(__FILE__, array(__CLASS__, 'pagePluginNews'));
    mgAddAction('mg_gethtmlcontent', array(__CLASS__, 'printNews'), 1);
    mgAddAction('mg_start', array(__CLASS__, 'newsFeed'));
    mgAddShortcode('news-anons', array(__CLASS__, 'anonsNews'));
  }

  public static function createDateBaseNews() {
    DB::query("
     CREATE TABLE IF NOT EXISTS  `mpl_news` (
     `id` INT( 11 ) NOT NULL AUTO_INCREMENT ,
     `title` VARCHAR( 255 ) NOT NULL ,
     `description` TEXT NOT NULL ,
     `add_date` DATETIME NOT NULL ,
     `url` VARCHAR( 255 ) NOT NULL ,
     `image_url` VARCHAR( 255 ) NOT NULL ,
     `meta_title` varchar(255) NOT NULL,
     `meta_keywords` varchar(512) NOT NULL,
     `meta_desc` text NOT NULL,
     PRIMARY KEY ( `id` )
     ) ENGINE = MYISAM DEFAULT CHARSET=utf8;
   ");

    $file = PLUGIN_DIR.'news/viewnews.php';
    $newfile = 'news.php';
    if (!file_exists(PAGE_DIR.$newfile)) {
      copy($file, PAGE_DIR.$newfile);
    }

    setOption('countPrintRowsNews', 10);

    $realDocumentRoot = str_replace(DIRECTORY_SEPARATOR.'mg-plugins'.DIRECTORY_SEPARATOR.'news', '', dirname(__FILE__));
    $path = $realDocumentRoot.'/uploads/news/';
    if (!file_exists($path)) {
      chdir($realDocumentRoot."/uploads/");
      mkdir("news", 0777);
      chdir($realDocumentRoot."/uploads/news/");
      mkdir("thumbs", 0777);
    }
  }

  //Выводит полную новость на странице news/[название_новости]
  public static function printNews($arg) {
    $result = $arg['result'];  
    if (URL::isSection('news')) {
      $news = self::getNewsByUrl(URL::getLastSection());
      if (empty($news)) {
        MG::redirect('/404.html');
      }
      MG::titlePage($news['title']);
      MG::seoMeta($news);   

      if(file_exists($realDocumentRoot.'/'.PAGE_DIR.'news/news.item.php')){
         
        $template = $realDocumentRoot.'/'.PAGE_DIR.'news/news.item.php';
        ob_start();
        include($template);
        $result .= ob_get_contents();
        ob_end_clean();
      }else{
        $img = $news['image_url'] ? 
          '<img src="'.SITE.'/uploads/news/'.$news['image_url'].'" alt="'.$news['title'].'" title="'.$news['title'].'">' : '';
        $result ='       
        <h1 class="big-title">Новости компании</h1>
        <div class="main-news-block">
         <a href="'.SITE.'/news" class="go-back-link">&larr; Вернуться назад</a>
           <div class="main-news-item">             
              <h2 class="news-title">'.$news['title'].'</h2>
              <span class="news-date">'.date('d.m.Y', strtotime($news['add_date'])).'</span>
              <div class="clear"></div>
              <div class="main-news-img">
                '.$img.'  
              </div>'.MG::inlineEditor("mpl_news","description",$news['id'], $news['description']).'                 
            </div>
          </div>

        <div class="rss-block">
          <p>Чтобы ничего не пропустить, Вы можете подписаться на рассылку новостей, для этого просто кликнете по кнопке ниже!</p>
          <a class="rss" href="'.SITE.'/news/feed"><span class="rss-icon"></span>RSS лента новостей</a>
        </div>';
      }
    }
    return $result;
  }

// Формирует и выводит RSS ленту.
  public static function newsFeed() {
    if (URL::getClearUri()=='/news/feed') {
      MG::disableTemplate();
      include 'feed.php';
      $rss = new Feed(SITE, 'RSS подписка на новости', 'Все о moguta.CMS');
      $data = self::getListNews();
      $listNews = $data['listNews'];
      foreach ($listNews as $news) {
        $rss->AddItem(
          htmlentities(SITE.'/news/'.$news['url']), $news['title'], $news['description'], $news['add_date']
        );
      }

      # публикуем рузельтирующий RSS 2.0
      $rss->Publish();
      exit;
    }
  }

//выводит страницу плагина в админке
  public static function pagePluginNews() {
    $lang = PM::plugLocales('news');
    if ($_POST["page"])
      $page = $_POST["page"]; //если был произведен запрос другой страницы, то присваиваем переменной новый индекс

    $countPrintRowsNews = MG::getOption('countPrintRowsNews');

    $navigator = new Navigator("SELECT  *  FROM `mpl_news` ORDER BY `add_date` DESC", $page, $countPrintRowsNews); //определяем класс
    $news = $navigator->getRowsSql();
    $pagination = $navigator->getPager('forAjax');

    // подключаем view для страницы плагина
    include 'pagePlugin.php';
  }

  /**
   * Печатает на экран анонс заданной новости
   * @param type $news - массив с данными о новости (полностью запись из БД)
   */
  public static function printAnonsNews($news) {
    ?>
    <div class="main-news-item">      
      <h2 class="news-title"><a href="<?php echo SITE?>/news/<?php echo $news['url']; ?>"><?php echo $news['title']; ?></a></h2>
      <div class="news-date"><?php echo date('d.m.Y', strtotime($news['add_date'])); ?></div>
      <div class="clear"></div>
      <a href="<?php echo SITE?>/news/<?php echo $news['url']; ?>" class="main-news-img">
        <?php if ($news['image_url']) : ?>
         <img src="<?php echo SITE ?>/uploads/news/<?php echo $news['image_url'] ?>" alt="<?php echo $news['title'] ?>" title="<?php echo $news['title'] ?>">
        <?php endif; ?>
      </a>
      <p class="news-main-desc"> <?php echo mb_substr(strip_tags(PM::stripShortcodes($news['description'])), 0, 240, 'utf-8')."..."; ?></p>
      <a href="<?php echo SITE?>/news/<?php echo $news['url']; ?>" class="read-more">Читать всю новость &rarr;</a>
      <div class="clear"></div>
    </div>

    <?php
  }

   /**
   * Печатает на экран анонс заданной новости
   * @param type $news - массив с данными о новости (полностью запись из БД)
   */
	  public static function anonsNews($args) {
	  $args['count'] = $args['count']?$args['count']:3;
		$data = self::getListNews($args['count'], false);
		$listNews = $data['listNews'];
		$html = '
    <div class="news-block">
      <div class="news-header">
        <h2>Новости</h2>
      </div>

      <div class="news-body">';
    
		if (!empty($listNews)) {
		  foreach ($listNews as $news) {
        $img = $news['image_url'] ? '<a href="'.SITE.'/news/'.$news['url'].'" class="news-img">
          <img src="'.SITE.'/uploads/news/thumbs/70_'.$news['image_url'].'" alt="'.$news['title'].'" title="'.$news['title'].'">
        </a>' : '';
        $html .= '

      <div class="news-item">
        '.$img.'
        <div class="news-details">
          <div class="news-date">'.date('d.m.Y', strtotime($news['add_date'])).'</div>
          <a href="'.SITE.'/news/'.$news['url'].'" class="news-text">
            '.$news['title'].'
          </a>
        </div>
      </div> ';
		  }
		}
    
    $html .= '	</div>
	   <div class="news-footer"> 
       <a href="'.SITE.'/news" class="show-all">Все новости</a>  	
   	</div>  
    </div>
    ';
		return $html;
	  }

  /**
   * Запускает механизм вывода анонсов для новостей
   * @param type $count - количество выводимых анонсов
   */
  public static function runNews($count = 3) {
    $data = self::getListNews($count);

    $listNews = $data['listNews'];
    if (!empty($listNews)) {
      echo '<div class="main-news-block">';
      foreach ($listNews as $news) {
        echo self::printAnonsNews($news);
      }
      echo $data['pagination'].'</div>';
    } else {
      echo "Пока новостей нет!";
    }
  }

//Возвращает список новостей
  public static function getListNews($count = 100, $usepager = true) {

    if($usepager){
      //Получаем список новостей
      if ($_GET["page"])
        $page = $_GET["page"]; //если был произведен запрос другой страницы, то присваиваем переменной новый индекс

      $navigator = new Navigator("SELECT  *  FROM `mpl_news` WHERE `add_date` <= now() ORDER BY `add_date` DESC ", $page, $count); //определяем класс
      $news = $navigator->getRowsSql();
      $pagination = $navigator->getPager();
    }else{
      $navigator = new Navigator("SELECT  *  FROM `mpl_news` WHERE `add_date` <= now() ORDER BY `add_date` DESC", 1, $count); //определяем класс
      $news = $navigator->getRowsSql();
      $pagination = '';
    }

    return array('listNews' => $news, 'pagination' => $pagination);
  }

// Возвращает данные о запрошенной новости.
  public static function getNewsByUrl($url) {
    $result = array();
    $res = DB::query('
    SELECT  *
    FROM `mpl_news`  
    WHERE url="'.DB::quote($url, true).'.html" OR url="'.DB::quote($url, true).'"'
    );
    if ($result = DB::fetchAssoc($res)) {
      return $result;
    }
    return $result;
  }

}
