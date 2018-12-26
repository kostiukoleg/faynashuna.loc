<?php $workTime = explode(',', MG::getSetting('timeWork')); ?>
<div class="c-contact" itemscope itemtype="http://schema.org/Store">
<?php $phones = explode(', ', MG::getSetting('shopPhone'));
        foreach ($phones as $phone) {?>
        <span class="phone-top"><?php echo $phone; ?></span>
        <?php } ?>
        <?php if (class_exists('BackRing')): ?>
            <div class="c-contact__row">
                <div class="wrapper-back-ring"><button type="submit" class="back-ring-button default-btn">Проблемы с заказом?</button></div>
            </div>
        <?php endif; ?>
</div>
