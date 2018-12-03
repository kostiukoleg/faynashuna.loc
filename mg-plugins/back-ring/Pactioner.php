<?php

/**
 * Класс Pactioner наследник стандарного Actioner
 * Предназначен для выполнения действий,  AJAX запросов плагина 
 *
 * @author Avdeev Mark <mark-avdeev@mail.ru>
 */
class Pactioner extends Actioner {

  private $pluginName = 'back-ring';        

  /**
   * Добавление сущности в таблицу БД
   * @param type $array - массив полей и значений
   * @return array возвращает входящий массив
   */
  public function addEntity($array) {
    unset($array['id']);
    $result = array();
    DB::buildQuery('INSERT INTO `'.PREFIX.$this->pluginName.'` SET ', $array);
    return $result;
  }

  /**
   * Обновление сущности в таблице БД
   * @param type $array - массив полей и значений
   * @return array возвращает входящий массив
   */
  public function updateEntity($array) {
 
	
    $id = $array['id'];
    $result = false;
    if (!empty($id)) {
	  //доступно только модераторам и админам.
      USER::AccessOnly('1,4','exit()');
      if (DB::query('
        UPDATE `'.PREFIX.$this->pluginName.'`
        SET '.DB::buildPartQuery($array).'
        WHERE id = '.DB::quote($id))) {
        $result = true;
      }
    } else {
      $result = $this->addEntity($array);
    }
    return $result;
  }

  /**
   * Удаление сущности
   * @return boolean
   */
  public function deleteEntity() {
    //доступно только модераторам и админам.
    USER::AccessOnly('1,4','exit()');
	
    $this->messageSucces = $this->lang['ENTITY_DEL'];
    $this->messageError = $this->lang['ENTITY_DEL_NOT'];
    if (DB::query('DELETE FROM `'.PREFIX.$this->pluginName.'` WHERE `id`= '.$_POST['id'])) {
      return true;
    }
    return false;
  }

  /**
   * получает сущность
   * @return boolean
   */
  public function getEntity() {
    $res = DB::query('
      SELECT * 
      FROM `'.PREFIX.$this->pluginName.'`
      WHERE `id` = '.DB::quote($_POST['id']));

    if ($row = DB::fetchAssoc($res)) {
      $this->data = $row;
      return true;
    } else {
      return false;
    }

    return false;
  }

  /**
   * Сохраняет и обновляет параметры записи.
   * @return type
   */
  public function saveEntity() {

    $this->messageSucces = $this->lang['ENTITY_SAVE'];
    $this->messageError = $this->lang['ENTITY_SAVE_NOT'];

    unset($_POST['pluginHandler']);
    unset($_POST['email']);  
	
    if (!empty($_POST['id'])) {  // если передан ID, то обновляем
	  $addDatetime = $_POST['add_datetime'];
      unset($_POST['add_datetime']);   
      if ($_POST['status_id']=='3') {
        $_POST['invisible']='0';
      } elseif($_POST['status_id']=='1') {
        $_POST['invisible']='1';
      }
      if (DB::query('
        UPDATE `'.PREFIX.$this->pluginName.'`
        SET '.DB::buildPartQuery($_POST).'
        WHERE id = '.DB::quote($_POST['id']))) {
        $_POST['add_datetime'] = $addDatetime;
        $this->data['row'] = $_POST;    
      } else {
        return false;
      }
    } else {      
      // если  не передан ID, то создаем
      if (DB::buildQuery('INSERT INTO `'.PREFIX.$this->pluginName.'` SET ', $_POST)) {
        $_POST['id'] = DB::insertId();
        //$this->updateEntity(array('id' => $_POST['id'], 'sort'=>$_POST['id'])); 
		DB::query('
        UPDATE `'.PREFIX.$this->pluginName.'`
        SET sort = '.DB::quote($_POST['id']).'
        WHERE id = '.DB::quote($_POST['id']));
		
        $this->data['row'] = $_POST;   
      } else {
        return false;
      }
    }
    return true;
  }

  /**
   * Устанавливает флаг  активности  
   * @return type
   */
  public function visibleEntity() {
    //доступно только модераторам и админам.
    USER::AccessOnly('1,4','exit()');
	
    $this->messageSucces = $this->lang['ACT_V_ENTITY'];
    $this->messageError = $this->lang['ACT_UNV_ENTITY'];

    //обновление
    if (!empty($_POST['id'])) {
      unset($_POST['pluginHandler']);
      $this->updateEntity($_POST);
    }

    if ($_POST['invisible']) {
      return true;
    }

    return false;
  }

  /**
   * Сохраняет  опции плагина
   * @return boolean
   */
  public function saveBaseOption() {
    //доступно только модераторам и админам.
    USER::AccessOnly('1,4','exit()');
	
    $this->messageSucces = $this->lang['SAVE_BASE'];
    $this->messageError = $this->lang['NOT_SAVE_BASE'];
    if (!empty($_POST['data'])) {
      MG::setOption(array('option' => 'back-ring-option', 'value' => addslashes(serialize($_POST['data']))));
    }   
    return true;
  }

   /**
   * Сохраняет заявку на перезвон
   * @return boolean
   */
  public function sendOrderRing() {

    $this->messageSucces = $this->lang['SAVE_BASE'];
    $this->messageError = $this->lang['NOT_SAVE_BASE'];
	
    if (!empty($_POST)) {
	    $section = $_POST['section'];
	    $email = $_POST['email'];
      $_POST['add_datetime'] = date('Y-m-d h:i');
      
      $option = MG::getSetting('back-ring-option');
      $option = stripslashes($option);
      $options = unserialize($option); 
      if($options['caphpa'] == 'true'){ 
        if(strtolower($_POST['capcha'])!= strtolower($_SESSION['capcha'])){ 
          $error = "<span class='error'>Текст с картинки введен неверно!</span>";
          $this->data['msg'] = $error;
          return false;
        }      
      }    
      
      
      if (!empty($_POST['pub'])) {
        unset($_POST['pub']);
        unset($_POST['section']);	
        unset($_POST['capcha']);
        $this->saveEntity();		
      }
	  
	    $sitename = MG::getSetting('sitename');
        $subj = 'Заявка #'.$_POST['id'].' на бесплатный звонок с '.$sitename;   
	 
	    $body = 
		'<H1>Заявка на перезвон #'.$_POST['id'].' от '.$_POST['add_datetime'].'</H1>'.
		'<p></br></br><b>Цель звонка:</b> '.$_POST['mission'].'</p>'.
		'<p></br></br><b>Комментарий:</b> '.$_POST['comment'].'</p>'.
		'<p></br></br><b>Имя:</b> '.$_POST['name'].'</p>'.
		'<p></br></br><b>Телефон:</b> '.$_POST['phone'].'</p>'.
		'<p></br></br><b>Город:</b> '.$_POST['city_id'].'</p>'.		
		'<p></br></br><b>Дата для звонка:</b> '.date('d.m.Y',strtotime($_POST['date_callback'])).'</p>'.
		'<p></br></br><b>Время для звонка:</b> '.$_POST['time_callback'].'</p>'.
		'<p></br></br><b>Добавлено:</b> '.date('d.m.Y H:i',strtotime($_POST['add_datetime'])).'</p>';
		
        //если ответил пользователь то письма отправляются админам
		$mails = explode(',', $options['email']);
		if (!$mails[0]){ 
		  $mails = explode(',', MG::getSetting('adminEmail'));	
		}
		// Отправка заявки админам
		
		foreach($mails as $mail){
      $mail = trim($mail);
		  if(preg_match('/^[A-Za-z0-9._-]+@[A-Za-z0-9_-]+.([A-Za-z0-9_-][A-Za-z0-9_]+)$/', $mail)){
	
			Mailer::sendMimeMail(array(
			  'nameFrom' => MG::getSetting('noReplyEmail'),
			  'emailFrom' => MG::getSetting('noReplyEmail'),
			  'nameTo' => $sitename,
			  'emailTo' => $mail,
			  'subject' => $subj,
			  'body' => $body,
			  'html' => true
			));
		  }
		}
		$msg = "Спасибо! Ваша заявка отправлена. Мы свяжемся с Вами!"; 
   
    }   

    return true;
  }
  
   /**
   * Устанавливает количество отображаемых записей в разделе 
   * @return boolean
   */
  public function setCountPrintRowsEnity(){
    //доступно только модераторам и админам.
    USER::AccessOnly('1,4','exit()');
	
    $count = 20;
    if(is_numeric($_POST['count'])&& !empty($_POST['count'])){
      $count = $_POST['count'];
    }

    MG::setOption(array('option'=>$_POST['option'], 'value'=>$count));
    return true;
  }
}