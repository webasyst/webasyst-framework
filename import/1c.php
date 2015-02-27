<?php
//ini_set('error_reporting', E_ALL);
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);


$db1 = mysql_connect("localhost", "m32681", "Jrnr5083LJnh");                //старый shop-script
mysql_select_db("db32681m", $db1);

if (!$db1)
        die(mysql_errno().' '.mysql_error().' <span style="color:red;">Ошибка подключения БД Old Shop-Script</span><br>');
else echo '<span style="color:green;">БД Old Shop-Script подключена</span><br>';

mysql_query("set names cp1251", $db1);

if (isset($_POST['products']))
{
//parent - 665, 669
//    $query = mysql_query("SELECT * FROM SC_categories WHERE parent = 665 OR parent = 669", $db1);
//    while ($row = mysql_fetch_array($query))
//    {	
//	   $category = $category.' "'.$row['categoryID'].'",';
//	}
//
    //$query = mysql_query("SELECT * FROM SC_products", $db1);
    $query = mysql_query("SELECT * FROM SC_products WHERE categoryID IN ('593', '634', '594', '633', '601', '628', '595', '661')", $db1);
    while ($row = mysql_fetch_array($query))
    {

        $i = 0;
        $query_cat = mysql_query("SELECT categoryID FROM SC_category_product WHERE productID  = ".$row['productID'], $db1);
        while ($row_cat = mysql_fetch_array($query_cat))
        {
            $cat[$i] = $row_cat['categoryID'];
            $i++;
        }

        if (count($cat) == 0) $cat = '';
        else $cat = implode(",", $cat);

        $j = 0;
        $query_related = mysql_query("SELECT productID FROM SC_related_items WHERE Owner = ".$row['productID'], $db1);
        while ($row_related = mysql_fetch_array($query_related))
        {
            $related[$j] = $row_related['productID'];
            $j++;
        }

        if (count($related) == 0) $related = '';
        else $related = implode(",", $related);

        $query_default_picture = mysql_query("SELECT filename FROM SC_product_pictures WHERE photoID = ".$row['default_picture']." LIMIT 1", $db1);
        $default_picture = mysql_fetch_array($query_default_picture);

        $query_brend = mysql_query("SELECT name FROM SC_brends WHERE brendID = ".$row['brend']." LIMIT 1", $db1);
        $brend = mysql_fetch_array($query_brend);

        $insert = mysql_query("INSERT INTO 1C_products (id,
                                                        categoryID,
                                                        price,
                                                        in_stock,
						        default_picture,
                                                        slug,
                                                        name_ru,
                                                        brief_description_ru,
							description_ru,
							meta_title_ru,
							meta_description_ru,
							meta_keywords_ru,
							no_dublicate,
							new,
							brend,
							related) VALUES ('".mysql_real_escape_string($row['productID'])."',
                                                                         '".mysql_real_escape_string($row['categoryID']).",".mysql_real_escape_string($cat)."',
                                                                         '".mysql_real_escape_string($row['Price'])."',
                                                                         '".mysql_real_escape_string($row['in_stock'])."',
                                                                         '".mysql_real_escape_string($default_picture['filename'])."',
                                                                         '".mysql_real_escape_string($row['slug'])."',
                                                                         '".mysql_real_escape_string($row['name_ru'])."',
                                                                         '".mysql_real_escape_string($row['brief_description_ru'])."',
                                                                         '".mysql_real_escape_string($row['description_ru'])."',
                                                                         '".mysql_real_escape_string($row['meta_title_ru'])."',
                                                                         '".mysql_real_escape_string($row['meta_description_ru'])."',
                                                                         '".mysql_real_escape_string($row['meta_keywords_ru'])."',
                                                                         '".mysql_real_escape_string($row['no_dublicate'])."',
                                                                         '".mysql_real_escape_string($row['new'])."',
                                                                         '".mysql_real_escape_string($brend['name'])."',
                                                                         '".mysql_real_escape_string($related)."')", $db1);
//        echo mysql_errno() . ": " . mysql_error() . "\n";
//        if (mysql_errno() != 0)
//        {
//            echo '<br>';
//        echo $row['productID'].'<br>';
//        echo $row['categoryID'].",".$cat.'<br>';
//        echo $row['Price'].'<br>';
//        echo $row['in_stock'].'<br>';
//        echo $default_picture['filename'].'<br>';
//        echo $row['slug'].'<br>';
//        echo $row['name_ru'].'<br>';
//        echo '8'.$row['brief_description_ru'].'<br>';
//        echo '9'.$row['description_ru'].'<br>';
//        echo '10'.$row['meta_title_ru'].'<br>';
//        echo '11'.$row['meta_description_ru'].'<br>';
//        echo '12'.$row['meta_keywords_ru'].'<br>';
//        echo '13'.$row['no_dublicate'].'<br>';
//        echo '14'.$row['new'].'<br>';
//        echo '15'.$brend['name'].'<br>';
//        echo '16'.$related.'<br>';
//        echo '<br><br><br>';
//        }  
        if ($insert == true) $products_true++;
        else $products_false++;
    }
}

if (isset($_POST['products_clear']))
{
    mysql_query("TRUNCATE TABLE `1C_products`", $db1);
}


if (isset($_POST['options']))
{
    $query = mysql_query("SELECT `id` FROM 1C_products", $db1);
    while ($row = mysql_fetch_array($query))
    {
        $query_options = mysql_query("SELECT * FROM SC_product_options_set WHERE productID = ".$row['id'], $db1);
        while ($row_options = mysql_fetch_array($query_options))
        {

            $query_variant = mysql_query("SELECT option_value_ru FROM SC_products_opt_val_variants WHERE variantID = ".$row_options['variantID']." LIMIT 1", $db1);
            $variant = mysql_fetch_array($query_variant);

            $insert = mysql_query("INSERT INTO 1C_options (`id`,
                                                           `option`,
							   `value`,
							    `in_stock`) VALUES ('".$row['id']."',
									        'razmer',
									        '".$variant['option_value_ru']."',
										'".$row_options['in_stock']."')", $db1);
        }
        if ($insert == true) $options_true++;
        else $options_false++;
    }
}

if (isset($_POST['options_clear']))
{
    mysql_query("TRUNCATE TABLE `1C_options`", $db1);
}

if (isset($_POST['categories']))
{
    //$query = mysql_query("SELECT * FROM SC_categories WHERE categoryID != 1", $db1);
	$query = mysql_query("SELECT * FROM SC_categories WHERE categoryID = 593 OR parent = 593", $db1);
	
    while ($row = mysql_fetch_array($query))
    {
        $insert = mysql_query("INSERT INTO 1C_categories (id,
                                                          parent,
							  slug,
                                                          name_ru,
                                                          description_ru,
                                                          description_second_ru, 
                                                          meta_title_ru,
                                                          meta_description_ru,
                                                          meta_keywords_ru,
                                                          no_dublicate) VALUES ('".$row['categoryID']."',
                                                                                '".$row['parent']."',
                                                                                '".mysql_real_escape_string($row['slug'])."',
                                                                                '".mysql_real_escape_string($row['name_ru'])."',
                                                                                '".mysql_real_escape_string($row['description_ru'])."',
                                                                                '".mysql_real_escape_string($row['description_second_ru'])."',
                                                                                '".mysql_real_escape_string($row['meta_title_ru'])."',
                                                                                '".mysql_real_escape_string($row['meta_description_ru'])."',
                                                                                '".mysql_real_escape_string($row['meta_keywords_ru'])."',
                                                                                '".mysql_real_escape_string($row['no_dublicate'])."')", $db1);
        if ($insert == true) $categories_true++;
        else $categories_false++;
    }
}

if (isset($_POST['categories_clear']))
{
    mysql_query("TRUNCATE TABLE `1C_categories`", $db1);
}

if (isset($_POST['pictures']))
{
    $query = mysql_query("SELECT * FROM SC_product_pictures", $db1);
    //$query = mysql_query("SELECT * FROM SC_product_pictures LEFT JOIN SC_products ON SC_product_pictures.productID=SC_products.productID  WHERE SC_products.categoryID IN ('726', '675', '667', '677', '676', '666', '727', '672', '670', '674', '673', '671')", $db1);
    while ($row = mysql_fetch_array($query))
    {
        $insert = mysql_query("INSERT INTO 1C_pictures (productID,
                                                        filename,
							thumbnail,
                                                        enlarged) VALUES ('".$row['productID']."',
														                  '".$row['filename']."',
																		  '".$row['thumbnail']."',
																		  '".$row['enlarged']."')", $db1);
        if ($insert == true) $pictures_true++;
        else $pictures_false++;
    }
}

if (isset($_POST['pictures_clear']))
{
    mysql_query("TRUNCATE TABLE `1C_pictures`", $db1);
}
?>


<body>
    <form action="" method="POST">
        <div style="margin-top: 20px;" >
            <b>Таблицы для 1С:</b>
            <table>
                <tr>
                    <td width="310px">Заполнение таблицы 1C_products:</td>
                    <td><span style=" color:green;">Удачно:</span><input type="text" id="wa_contact" style="margin-left: 5px; width: 100px;" value="<?= $products_true ?>" /></td>
                    <td><span style=" color:red;">Неудачно:</span><input type="text" id="wa_contact" style="margin-left: 5px; width: 100px;" value="<?= $products_false ?>" /></td>
                    <td><button type="submit" name="products">Заполнить</button></td>
                    <td><button type="submit" style="" name="products_clear">Очистить таблицу</button></td>
                </tr>
                <tr>
                    <td width="310px">Заполнение таблицы 1C_options:</td>
                    <td><span style=" color:green;">Удачно:</span><input type="text" id="wa_contact" style="margin-left: 5px; width: 100px;" value="<?= $options_true ?>" /></td>
                    <td><span style=" color:red;">Неудачно:</span><input type="text" id="wa_contact" style="margin-left: 5px; width: 100px;" value="<?= $options_false ?>" /></td>
                    <td><button type="submit" name="options">Заполнить</button></td>
                    <td><button type="submit" style="" name="options_clear">Очистить таблицу</button></td>
                </tr>
                <tr>
                    <td width="310px">Заполнение таблицы 1C_pictures:</td>
                    <td><span style=" color:green;">Удачно:</span><input type="text" id="wa_contact" style="margin-left: 5px; width: 100px;" value="<?= $pictures_true ?>" /></td>
                    <td><span style=" color:red;">Неудачно:</span><input type="text" id="wa_contact" style="margin-left: 5px; width: 100px;" value="<?= $pictures_false ?>" /></td>
                    <td><button type="submit" name="pictures">Заполнить</button></td>
                    <td><button type="submit" style="" name="pictures_clear">Очистить таблицу</button></td>
                </tr>
                <tr>
                    <td width="310px">Заполнение таблицы 1C_categories:</td>
                    <td><span style=" color:green;">Удачно:</span><input type="text" id="wa_contact" style="margin-left: 5px; width: 100px;" value="<?= $categories_true ?>" /></td>
                    <td><span style=" color:red;">Неудачно:</span><input type="text" id="wa_contact" style="margin-left: 5px; width: 100px;" value="<?= $categories_false ?>" /></td>
                    <td><button type="submit" name="categories">Заполнить</button></td>
                    <td><button type="submit" style="" name="categories_clear">Очистить таблицу</button></td>
                </tr>
            </table>
        </div>
    </form>
</body>		