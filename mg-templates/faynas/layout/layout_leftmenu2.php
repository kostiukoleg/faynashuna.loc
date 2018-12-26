<?php 
$data['categories'] = array();
foreach(MG::get('category')->getCategoryList() as $id){
    array_push($data['categories'], MG::get('category')->getCategoryById($id));
}
?>
<div id="leftbot">
    <div class="modulediv">
        <?php

        ?>
        <?php foreach ($data['categories'] as $category): ?>
        <?php if ($category['invisible'] == "1") { continue;} ?>
        <?php if (SITE.URL::getClearUri() === $category['link']) { $active = 'active'; } else { $active = ''; } ?>
        <?php if ($category['parent'] > 0): ?>
        <?php $slider = 'slider'; $noUl = 1; foreach($category['child'] as $categoryLevel1){$noUl *= $categoryLevel1['invisible']; } if($noUl){$slider='';}?>
        <span class="leftmenutitle"><?php echo MG::contextEditor('category', $category['title'], $category["id"], "category"); ?></span>
 

            <?php if($noUl){$slider=''; continue;} ?>
                <ul class="leftmenu">
                <?php foreach ($category['child'] as $categoryLevel1): ?> <?php if ($categoryLevel1['invisible'] == "1") { continue; } ?>
                <?php if (SITE.URL::getClearUri() === $categoryLevel1['link']) { $active = 'active'; } else { $active = ''; } ?>
                <?php if (isset($categoryLevel1['child'])): ?>
                <?php $slider = 'slider'; $noUl = 1; foreach($categoryLevel1['child'] as $categoryLevel2){$noUl *= $categoryLevel2['invisible']; } if($noUl){$slider='';}?>
                <li>
                    <a class="leftmenucat" href="<?php echo $categoryLevel1['link']; ?>">Шины   
                        <span>
                            <?php echo MG::contextEditor('category', $categoryLevel1['title'], $categoryLevel1["id"], "category"); ?>
                        </span>
                    </a>
                </li>
                <?php else: ?>

                <li>
                    <a class="leftmenucat" href="<?php echo $categoryLevel1['link']; ?>">Шины   
                        <span>
                            <?php echo MG::contextEditor('category', $categoryLevel1['title'], $categoryLevel1["id"], "category"); ?>
                        </span>
                    </a>
                </li>
                <?php endif; ?>
                <?php endforeach; ?>
                </ul><br>
        <?php else: ?>                       
            <span class="leftmenutitle">
                <?php echo MG::contextEditor('category', $category['title'], $category["id"], "category"); ?>
            </span>
        <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div> 