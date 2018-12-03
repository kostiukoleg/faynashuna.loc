<div id="mg-gallery"> <!-- в этом блоке будут содержаться картинки -->
  <ul class="mg-gallery-list">

    <?php foreach ($imgList as $img) { ?>
      <li style="width:<?php echo 100/$options['in_line']-2.5;?>%;height:<?php echo $options['height'];?>px;">
        <a class="pic" href="<?php echo $img['image_url']?>" rel="gallery<?php echo $options['id'];?>" title="<?php echo $img['title']?>" target="_blank">
          <img src="<?php echo $img['image_url']?>" title="<?php echo $img['title']?>" alt="<?php echo $img['title']?>"/>
        </a>
      </li>
    <?php } ?>
  </ul>
</div>
