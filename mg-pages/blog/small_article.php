<?php
mgSEO($data);
?>
<div class="product-item">
  <div class="title"><?php echo $data['title']; ?></div>

  <div class="text">
      <?php echo MG::inlineEditor(PREFIX.'blog_items' , 'description', $data['id'], $data['previewText'], 'blog');?>
  </div>
  <ul class="features-list">
            
      <?php foreach($data['tags'] as $cell=>$tag){
          echo '<li>'.$tag['value'].'</li>';
      }?>
  </ul>
  <div class="text-center action">
      <a href="/blog/index/<?php echo $data['url']; ?>" class="default-btn">Подробнее</a>
  </div>
</div>