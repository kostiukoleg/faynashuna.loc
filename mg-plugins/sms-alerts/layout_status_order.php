<?php echo 
'Статус вашего заказа #'.($data['orderInfo']['number'] != '' ? $data['orderInfo']['number'] : $data['orderInfo']['id']).' в магазине '.MG::getSetting('shopName').' "'.$data['statusName'].'"';


  