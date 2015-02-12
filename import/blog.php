<META content="text/html; charset=windows-1251" http-equiv="Content-Type">
<?php
$db1 = mysql_connect("localhost", "m32681", "Jrnr5083LJnh");                //старый shop-script
mysql_select_db("db32681m", $db1);

$db2 = mysql_connect("localhost", "shopscript5", "k3eukm8y");               //новый shop-script
mysql_select_db("shopscript5", $db2);


if (!$db1)
    die(mysql_errno().' '.mysql_error().' <span style="color:red;">Ошибка подключения БД Old Shop-Script</span><br>');
else
    echo '<span style="color:green;">БД Old Shop-Script подключена</span><br>';

if (!$db2)
    die(mysql_errno().' '.mysql_error().' <span style="color:red;">Ошибка подключения БД Shop-Script 5</span><br>');
else
    echo '<span style="color:green;">БД Shop-Script 5 подключена</span><br>';

mysql_query("set names cp1251", $db1);
mysql_query("set names cp1251", $db2);

if (isset($_POST['news']))
{
    $query = mysql_query("SELECT * FROM  `SC_news_table` ORDER BY  `SC_news_table`.`NID` ASC", $db1);
    while ($row = mysql_fetch_array($query))
    {
        $date = date("Y-m-d H:i:s", $row['add_stamp']);
        $insert = mysql_query("INSERT INTO blog_post (blog_id,
                                                      contact_id,
                                                      datetime,
                                                      title,
                                                      status,
                                                      text,
                                                      url,
                                                      comments_allowed) VALUES (1,
                                                                                1,
                                                                                '".$date."',
                                                                                '".$row['title']."',
                                                                                'published',    
                                                                                '".$row['textToPublication']."',
                                                                                '".$row['NID']."',
                                                                                0)", $db2);
        if ($insert == true)
            $news_true++;
        else
            $news_false++;
    }
}


if (isset($_POST['news_clear']))
{
    mysql_query("DELETE FROM `blog_post` WHERE `blog_id` = 1", $db2);
}

if (isset($_POST['blog']))
{
    $query = mysql_query("SELECT * FROM  `SC_blog` WHERE aux_page_text_ru != '' ORDER BY aux_page_ID ASC", $db1);
    while ($row = mysql_fetch_array($query))
    {
        //$date = date("Y-m-d H:i:s", $row['add_stamp']);
        $insert = mysql_query("INSERT INTO blog_post (blog_id,
                                                      contact_id,
                                                      datetime,
                                                      title,
                                                      status,
                                                      text,
                                                      url,
                                                      comments_allowed) VALUES (2,
                                                                                1,
                                                                                '".$row['aux_page_date_start']."',
                                                                                '".$row['aux_page_name_ru']."',
                                                                                'published',    
                                                                                '".$row['aux_page_text_ru']."',
                                                                                '".$row['aux_page_slug']."',
                                                                                0)", $db2);
        if ($insert == true)
            $blog_true++;
        else
            $blog_false++;
    }
}


if (isset($_POST['blog_clear']))
{
    mysql_query("DELETE FROM `blog_post` WHERE `blog_id` = 2", $db2);
}

if (isset($_POST['dbf']))
{
    $query = mysql_query("SELECT * FROM  `SC_loaddbf`", $db1);
    while ($row = mysql_fetch_array($query))
    {
        if ($row['status'] != 'bad')
        {
            $text = explode('"/', $row['text']);
            $href = explode(';">', $text[1]);
            $order = explode('</a>', $href[1]);
            $end = explode('1000', $order[0]);

            $query_id = mysql_query("SELECT id FROM  `shop_order` WHERE old_order =".$end[1]." LIMIT 1", $db2);
            while ($row_id = mysql_fetch_array($query_id))
            {
                $id = $row_id['id'];
            }        
                $text_str = $text[0].'"?action=orders#/orders/id='.$id.'/">100'.$id.'</a> '.$order[1];
        }
        $insert = mysql_query("INSERT INTO shop_dbf (id,
                                                     dbf,
                                                     date,
                                                     text,
                                                     amount,
                                                     status) VALUES ('".$row['id']."',
                                                                      '".$row['dbf']."',
                                                                      '".$row['date']."',
                                                                      '".$text_str."',
                                                                      '".$row['amount']."',
                                                                      '".$row['status']."')", $db2);
        if ($insert == true)
            $dbf_true++;
        else
            $dbf_false++;
    }
}


if (isset($_POST['dbf_clear']))
{
    mysql_query("TRUNCATE TABLE `shop_dbf`", $db2);
}

if (isset($_POST['redirect']))
{
    $query = mysql_query("SELECT * FROM  `SC_blog` WHERE aux_page_text_ru != '' ORDER BY aux_page_ID ASC", $db1);
    while ($row = mysql_fetch_array($query))
    {         
         echo 'RewriteRule ^auxpage_'.$row['aux_page_slug'].'(.*)/$ /post/'.$row['aux_page_slug'].'/ [NC,R=301,L]'.'<br>';
    }
} 

?>


<body>
    <form action="" method="POST">
        <div style="margin-top: 20px;" >
            <b>Блог:</b>
            <table>
                <tr>
                    <td width="310px">Импорт новостей:</td>
                    <td><span style=" color:green;">Удачно:</span><input type="text" id="wa_contact" style="margin-left: 5px; width: 100px;" value="<?= $news_true ?>" /></td>
                    <td><span style=" color:red;">Неудачно:</span><input type="text" id="wa_contact" style="margin-left: 5px; width: 100px;" value="<?= $news_false ?>" /></td>
                    <td><button type="submit" name="news">Заполнить</button></td>
                    <td><button type="submit" style="" name="news_clear">Очистить таблицу</button></td>
                </tr>
                <tr>
                    <td width="310px">Импорт блога:</td>
                    <td><span style=" color:green;">Удачно:</span><input type="text" id="wa_contact" style="margin-left: 5px; width: 100px;" value="<?= $blog_true ?>" /></td>
                    <td><span style=" color:red;">Неудачно:</span><input type="text" id="wa_contact" style="margin-left: 5px; width: 100px;" value="<?= $blog_false ?>" /></td>
                    <td><button type="submit" name="blog">Заполнить</button></td>
                    <td><button type="submit" style="" name="blog_clear">Очистить таблицу</button></td>
                </tr>
                <tr>
                    <td width="310px">Загрузить и исправить dbf:</td>
                    <td><span style=" color:green;">Удачно:</span><input type="text" id="wa_contact" style="margin-left: 5px; width: 100px;" value="<?= $dbf_true ?>" /></td>
                    <td><span style=" color:red;">Неудачно:</span><input type="text" id="wa_contact" style="margin-left: 5px; width: 100px;" value="<?= $dbf_false ?>" /></td>
                    <td><button type="submit" name="dbf">Заполнить</button></td>
                    <td><button type="submit" style="" name="dbf_clear">Очистить таблицу</button></td>
                </tr>
                <tr>
                    <td width="310px">Получить правила редиректа:</td>
                    <td><button type="submit" name="redirect">Показать</button></td>
                </tr>
            </table>
        </div>
    </form>
</body>	