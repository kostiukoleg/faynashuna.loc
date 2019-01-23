<?php if(!empty($data)): ?>
    <ul class="sub-categories">
            <?php foreach($data as $category): ?>
                <li>
                    <a class="cat-image" href="<?php echo SITE.'/'.$category['parent_url'].$category['url']; ?>">
                        <?php if(!empty($category['image_url'])): ?>
                            <img src="<?php echo SITE.$category['image_url']; ?>" alt="<?php echo $category['seo_alt'] ?>" title="<?php echo $category['seo_title'] ?>">
                        <?php else: ?>
                            <img src="<?php echo SITE.'/uploads/thumbs/70_no-img.jpg' ?>" alt="<?php echo $category['title']; ?>" title="<?php echo $category['title']; ?>">
                        <?php endif; ?>
                    </a>
                    <a href="<?php echo SITE.'/'.$category['parent_url'].$category['url']; ?>" class="sub-cat-name"><?php echo $category['title']; ?></a>
                </li>
            <?php endforeach; ?>
    </ul>
<?php endif; ?>