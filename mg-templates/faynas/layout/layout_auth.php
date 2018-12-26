<?php if($thisUser = $data['thisUser']): ?>
<a href="<?php echo SITE?>/personal" id="loginbtn" class="login"><?php echo lang('authAccount'); ?></a>
<?php else: ?>
<a href="<?php echo SITE?>/enter" id="loginbtn" class="login"><?php echo lang('authAccountLogin'); ?></a>
<?php endif; ?>