
 <div class="section-news">
    <!-- Тут начинается Верстка модального окна -->

      <div class="b-modal hidden-form" id="add-news-wrapper">
        <div class="product-table-wrapper">
          <div class="widget-table-title">
            <h4 class="pages-table-icon" id="modalTitle">Создание галереи</h4>
            <div class="b-modal_close tool-tip-bottom" title="Создание галереи"></div>
          </div>
          <div class="widget-table-body">
            

              <div class="base-settings">
                <div class="add-img-form">
                  <div class="product-text-inputs">
                    <h4>Настройки галереи</h4>
                    <br>
                    <label for="title"><div class="add-text">Название галереи</div><input style="width:250px;" type="text" name="title" class="product-name-input tool-tip-right" id="gal-name" title="Служит для определения галлереи"></label>
                    <h4>Настройки</h4>
                    <br>
                    <label><div class="add-text">Высота</div><input style="width:250px;" type="text" name="title" class="product-name-input tool-tip-right" id="gal-height" title="Высота выводимых изображений"></label>
                    <label><div class="add-text">Изображений в ряд</div><input style="width:250px;" type="text" name="url" class="product-name-input qty tool-tip-right" id="gal-in-line" title="Количество изображений в ряд"></label>
                  </div>
                </div>
                
                <div class="clear"></div>
              </div>

              <div class="img-settings">
                <div class="add-img-form">
                  <div class="product-text-inputs">
                    <h4>Настройки изображения</h4>
                    <br>
                    <p>Для выбора изображения, просто кликните по нему ниже</p>
                    <br>
                    <h4>Настройки</h4>
                    <br>
                    <label><div class="add-text">Title</div><input style="width:250px;" type="text" name="title" class="product-name-input tool-tip-right" id="img-title" title="Title изображения"></label>
                    <label><div class="add-text">Alt</div><input style="width:250px;" type="text" name="url" class="product-name-input qty tool-tip-right" id="img-alt" title="Alt изображения"></label>
                  </div>
                </div>
                <button class="save-button tool-tip-bottom img-save" title="Применить изменения"><span>Применить</span></button>
                <div class="clear"></div>
              </div>
              <div class="add-product-form-wrapper">
		            <div class="clear"></div>
                <button class="save-button" id="browseImage" style="margin-top:0;" title="Выбрать ихображение"><span style="padding: 4px 10px 4px 10px!important; background:none;">Выбрать картинку</span></button>
                <div class="clear"></div>
                <div id="mg-gallery"></div>
                <div class="clear"></div>
              </div>
              <br>
              <button class="save-button tool-tip-bottom gallery-save" title="Сохранить настройки"><span>Сохранить</span></button>
              <div class="clear"></div>
            </div>
          </div>
        </div>
      </div>

    <!-- Тут заканчивается Верстка модального окна -->


    <!-- Тут начинается  Верстка таблицы товаров --> 
    <div class="widget-table-body">
      <div class="widget-table-action">
        <div class="add-new-button tool-tip-bottom" id="add-new-gallery"><span>Создать галерею</span></div>
        
        <div class="clear"></div>
      </div>

      <div class="main-settings-container">
        <table class="widget-table product-table">
          <thead>
            <tr>
              <th class="shortcode">шорткод</th>
              <th class="gal-name">название галлереи</th>
              <th></th>
            </tr>
          </thead>
          <tbody class="gallery-tbody">
          <!-- вывод списка галерей -->
          <?php 
            $res = DB::query('SELECT id, gal_name FROM `'.PREFIX.'all_galleries` ORDER BY id DESC');

            while ($row = DB::fetchAssoc($res)) {
              if($row['gal_name'] == "Новая галерея") $row['gal_name'] = '<b>'.$row['gal_name'].'</b>';
              echo '<tr>';
              echo '<td>[gallery id='.$row['id'].']</td>';
              echo '<td style="cursor:pointer;" class="gallery-edit" data-id="'.$row['id'].'">'.$row['gal_name'].'</td>';
              echo '<td><ul class="edit">
                          <li class="edit-row gallery-edit" data-id="'.$row['id'].'">
                            <a class="tool-tip-bottom" href="#" title="Редактировать галерею">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</a>
                          </li>
                          <li style="list-style-type: none;" class="delete-order">
                            <a class="tool-tip-bottom delete-gallery" data-id="'.$row['id'].'" href="#" title="Удалить галерею">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</a>
                          </li></ul></td>';
              echo '</tr>';
            }
           ?>
          </tbody>
        </table>
      </div>
      <div class="clear"></div>
   </div>
 </div>