<div class="partners-block">
  <div class="centered clearfix">
      <?php if (!empty($brand)) : ?>
        <ul class="partners-list">
        <?php foreach ($brand as $value) : ?>
          <?php if ($value['url']) { ?>
              <li>
                <a href="<?php echo SITE.'/brand?brand='.$value['brand'] ?>">
                    <img src="<?php echo $value['url'] ?>" alt="<?php echo $value['brand']?>">
                </a>
              </li>
          <?php } ?>
        <?php endforeach; ?>
        </ul>
      <?php endif; ?>
  </div>
</div>

