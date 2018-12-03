<?php

/**
 * Класс Pactioner наследник стандарного Actioner
 * Предназначен для выполнения действий,  AJAX запросов плагина 
 *
 * @author Avdeev Mark <mark-avdeev@mail.ru>
 */
class Pactioner extends Actioner {

  private $pluginName = 'non-available';

  /**
   * Сохраняет  опции плагина
   * @return boolean
   */
  public function saveBaseOption() {
    //доступно только модераторам и админам.
    USER::AccessOnly('1,4', 'exit()');

    $this->messageSucces = $this->lang['SAVE_BASE'];
    $this->messageError = $this->lang['NOT_SAVE_BASE'];
    if (!empty($_POST['data'])) {
      MG::setOption(array('option' => 'nonAvailableOption', 'value' => addslashes(serialize($_POST['data']))));
    }
    return true;
  }

  /**
   * Проверяет правильно ли введены email, телефон и капча, сохраняет заявку в бд и отпраяляет письмо администратору
   * @return boolean
   */
  public function orderNonAvailable() {

    $option = MG::getSetting('nonAvailableOption');
    $option = stripslashes($option);
    $options = unserialize($option);
    if ($options['email'] == 'true') {
      if (!preg_match('/^[-._a-zA-Z0-9]+@(?:[a-zA-Z0-9][-a-zA-Z0-9]{0,61}+\.)+[a-zA-Z]{2,6}$/', $_POST['email'])) {
        $error = "<span class='error'>E-mail введен некорректно!</span>";
        $this->data['msg'] = $error;
        return false;
      }
    }
    if ($options['phone'] == 'true') {
      if (empty($_POST['phone'])) {
        $error = "<span class='error'>Введите номер телефона!</span>";
        $this->data['msg'] = $error;
        return false;
      }
    }
    if ($options['capcha'] == 'true') {
      if (strtolower($_POST['capcha']) != strtolower($_SESSION['capcha'])) {
        $error = "<span class='error'>Текст с картинки введен неверно!</span>";
        $this->data['msg'] = $error;
        return false;
      }
    }
    unset($_POST['capcha']);
    $_POST['add_datetime'] = date('Y-m-d H:i:s');

    $cart = new Models_Cart;
    $property = $cart->createProperty($_GET);
    $_POST['description'] = $property["propertyReal"];
    
    unset($_POST['mguniqueurl']);
    unset($_POST['pluginHandler']);

    $this->saveEntity();
    $this->sendMail($_POST);

    $msg = "Спасибо! Ваша заявка отправлена.  Наши менеджеры свяжутся с Вами!";
    $this->data['msg'] = $msg;

    return true;
  }

  /**
   * получает сущность
   * @return boolean
   */
  public function getEntity() {
    //доступно только модераторам и админам.
    USER::AccessOnly('1,4', 'exit()');
    $res = DB::query('
      SELECT * 
      FROM `'.PREFIX.$this->pluginName.'`
      WHERE `id` = '.DB::quote($_POST['id']));

    
    if ($row = DB::fetchAssoc($res)) {
      $row['add_datetime'] = MG::dateConvert($row['add_datetime']).' ['.date('H:i', strtotime($row['add_datetime'])).']';
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

    if (!empty($_POST['id'])) {  // если передан ID, то обновляем
      if (DB::query('
        UPDATE `'.PREFIX.$this->pluginName.'`
        SET '.DB::buildPartQuery($_POST).'
        WHERE id = '.DB::quote($_POST['id']))) {
        $this->data['row'] = $_POST;
      } else {
        return false;
      }
    } else {
      // если  не передан ID, то создаем
      if (DB::buildQuery('INSERT INTO `'.PREFIX.$this->pluginName.'` SET ', $_POST)) {
        $_POST['id'] = DB::insertId();
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
   * Удаление сущности
   * @return boolean
   */
  public function deleteEntity() {
    //доступно только модераторам и админам.
    USER::AccessOnly('1,4', 'exit()');

    $this->messageSucces = $this->lang['ENTITY_DEL'];
    $this->messageError = $this->lang['ENTITY_DEL_NOT'];
    if (DB::query('DELETE FROM `'.PREFIX.$this->pluginName.'` WHERE `id`= '.$_POST['id'])) {
      return true;
    }
    return false;
  }

  /**
   * Устанавливает флаг  активности  
   * @return type
   */
  public function visibleEntity() {
    //доступно только модераторам и админам.
    USER::AccessOnly('1,4', 'exit()');

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
   * Обновление сущности в таблице БД
   * @param type $array - массив полей и значений
   * @return array возвращает входящий массив
   */
  public function updateEntity($array) {
    //доступно только модераторам и админам.
    USER::AccessOnly('1,4', 'exit()');

    $id = $array['id'];
    $result = false;
    if (!empty($id)) {
      //доступно только модераторам и админам.
      USER::AccessOnly('1,4', 'exit()');
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
   * Устанавливает количество отображаемых записей в разделе 
   * @return boolean
   */
  public function setCountPrintRowsEnity() {
    //доступно только модераторам и админам.
    USER::AccessOnly('1,4', 'exit()');

    $count = 20;
    if (is_numeric($_POST['count']) && !empty($_POST['count'])) {
      $count = $_POST['count'];
    }

    MG::setOption(array('option' => $_POST['option'], 'value' => $count));
    return true;
  }

  /**
   * Отправляет письмо администратору об оставленной заявке
   * @return type
   */
  public function sendMail($array) {
    $option = MG::getSetting('nonAvailableOption');
    $option = stripslashes($option);
    $options = unserialize($option);
    $subj = 'Заявка №'.$array['id'].' на отсутствующий товар';

    $body = '<p>Отправлена заявка №'.$array['id'].' от '.$array['add_datetime'].' на товар '.$array['title'].' с артикулом '.$array['code'].'. '
      .'Зайдите в кабинет администратора для получения подробной информации.</p>';
    
    $mails = explode(',', $options['email_order']);

    if (!$mails[0]) {
      $mails = explode(',', MG::getSetting('adminEmail'));
    }
    // Отправка заявки админам

    foreach ($mails as $mail) {
      $mail = trim($mail);
      if (preg_match('/^[A-Za-z0-9._-]+@[A-Za-z0-9_-]+.([A-Za-z0-9_-][A-Za-z0-9_]+)$/', $mail)) {

        Mailer::sendMimeMail(array(
          'nameFrom' => MG::getSetting('noReplyEmail'),
          'emailFrom' => MG::getSetting('noReplyEmail'),
          'nameTo' => MG::getSetting('sitename'),
          'emailTo' => $mail,
          'subject' => $subj,
          'body' => $body,
          'html' => true
        ));
      }
    }

    return true;
  }
  // формирование окна для закза 
  public function buildOrderForm() {
    $id = $_POST['id'];
    $productModel = new Models_Product();
    $prodData = $productModel->getProduct($_POST['id']);
    $option = MG::getSetting('nonAvailableOption');
    $option = stripslashes($option);
    $options = unserialize($option);    
    if (isset($_SESSION['user'])) {
      $this->data['user'] = $_SESSION['user'];
    }     
    $this->data['user'] = '';
    $this->data['options'] = $options;
    $this->data['link'] = ($prodData['category_url']?$prodData['category_url'] : 'catalog').'/'.$prodData['product_url'];
    return true;
  }

}
