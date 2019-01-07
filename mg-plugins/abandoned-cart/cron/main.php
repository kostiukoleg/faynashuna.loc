<?php

/* 
 * класс main формирует из базы данных письма пользователям, кто оставили корзины
 * и отправляет им, если прошло указанное количество часов\дней
 */
class main{
  static private $_instance = null;
  static private $dataCharset = 'UTF-8';
  static private $sendCharset = 'KOI8-R';
  static private $endString = "\r\n";
  static private $addHeaders = null;
  private function __construct(){
  }
  private function __clone(){
  }
  private function __wakeup(){
  }
  /**
   * Инициализирует данный класс Main.
   * @return void
   */
  public static function init(){
    self::getInstance();
    // начинает подготовку к отправле письма
    self::prepareSend();
  }
   /**
   * Возвращет единственный экземпляр данного класса.
   * @return object - объект класса Mailer
   */
  static public function getInstance(){
    if(is_null(self::$_instance)){
      self::$_instance = new self;
    }
    return self::$_instance;
  }
  // подключение всех необходимых классов
  static public function prepareSend(){
    
    if (file_exists('config.ini')) {
      $config = parse_ini_file('config.ini', true);
      define('HOST', $config['DB']['HOST']);
      define('USER', $config['DB']['USER']);
      define('PASSWORD', $config['DB']['PASSWORD']);
      define('NAME_BD', $config['DB']['NAME_BD']);
      define('PREFIX', $config['DB']['TABLE_PREFIX']);
	define('SQL_BIG_SELECTS', $config['SETTINGS']['SQL_BIG_SELECTS']);
      define('PROTOCOL', $config['SETTINGS']['PROTOCOL']);
	define('DEBUG_SQL', 0);      
      DB::init();
      include('mg-plugins/abandoned-cart/cron/prepare.php');
      PREPARE::init();      
  }
  }
  public function sendMail($dataMail){
    $to = self::mimeHeaderEncode($dataMail['nameTo']).' <'.$dataMail['emailTo'].'>';
    $subject = self::mimeHeaderEncode($dataMail['subject']);
    $from = self::mimeHeaderEncode($dataMail['nameFrom']).' <'.$dataMail['emailFrom'].'>';

    if(self::$dataCharset != self::$sendCharset){
      $body = iconv(self::$dataCharset, self::$sendCharset, $dataMail['body']);
    }

    $headers = "From: ".$from.self::$endString;
    $type = ($dataMail['html']) ? 'html' : 'plain';
    $headers .= "Content-type: text/$type; charset=".self::$sendCharset.self::$endString;
    $headers .= "Mime-Version: 1.0".self::$endString;
    $headers .= self::$addHeaders;

    // Сбрасываем заголовки, чтобы они не попали в следующее письмо.
    self::$addHeaders = null;

    // Отправляем письмо
    return @mail($to, $subject, $body, $headers);
  }
  /**
   * Фунция получает массив с  заголовками и их значениями,
   * преобразует все в верную кодировку, и сохраняет в переменную класса.
   * @param array $headers - массив заголовков, ключ значение.
   * @return void
   */
  public static function addHeaders($headers){
    if(!empty($headers)){
      foreach($headers as $key => $value){
        self::$addHeaders.=$key.": ".$value.self::$endString;
      }
    }
  }

  /**
   * Функция для формирования корректных заголовков в письме,
   * @param type $str - значение заголовка.
   * @return string
   */
  public static function mimeHeaderEncode($header){
    if(self::$dataCharset != self::$sendCharset){
      $header = iconv(self::$dataCharset, self::$sendCharset, $header);
    }
    return '=?'.self::$sendCharset.'?B?'.base64_encode($header).'?=';
  }

}