<?php
$data = stripslashes(MG::getSetting('desiresPluginSettings'));
$data = unserialize($data);

$emailText = $data['emailText'];

include SITE_DIR.'mg-plugins/desires/tpl/'.$data['emailTemplate'];
?>