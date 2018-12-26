<?php if($thisUser = $data['thisUser']): ?>

<a href="<?php echo SITE?>/personal"><span><?php echo lang('authAccount'); ?></span></a>

<?php else: ?>

<a href="<?php echo SITE?>/enter"><span><?php echo lang('authAccount'); ?></span></a>

<?php endif; ?>