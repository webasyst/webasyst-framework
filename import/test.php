<?php
$db1 = mysql_connect("localhost", "m32681", "Jrnr5083LJnh");                //старый shop-script
mysql_select_db("db32681m", $db1);

$db2 = mysql_connect("localhost", "test", "test");               //новый shop-script
mysql_select_db("test", $db2);

if (!$db1)
    die(mysql_errno().' '.mysql_error().' <span style="color:red;">Ошибка подключения БД Old Shop-Script</span><br>');
else
    echo '<span style="color:green;">БД Old Shop-Script подключена</span><br>';

if (!$db2)
    die(mysql_errno().' '.mysql_error().' <span style="color:red;">Ошибка подключения БД Shop-Script 5</span><br>');
else
    echo '<span style="color:green;">БД Shop-Script 5 подключена</span><br>';

//mysql_query("set names utf8", $db1);
//mysql_query("set names utf8", $db2);

         $sql = mysql_query("SELECT * FROM SC_category_product WHERE categoryID IN ('663', '709', '708', '737', '707', '736', '738')", $db1);
         while($row = mysql_fetch_array($sql))
         {
             
             $sql_products = mysql_query("SELECT productID, list_price FROM SC_products WHERE productID = ".$row['productID'], $db1);
             $row_products = mysql_fetch_array($sql_products);

             if ($row_products['list_price'] == 0)
             {
                $sql_price = mysql_query("SELECT list_price, Price FROM SC_products WHERE productID = ".$row_products['productID'], $db2);
                $row_price = mysql_fetch_array($sql_price);
//var_dump ($row_price);
        var_dump (mysql_query("UPDATE SC_products SET Price = '".$row_price['Price']."', list_price = '".$row_price['list_price']."'  WHERE productID = ".$row_products['productID'], $db1)); 
             }
         }
         
         
         
         
//$sql = mysql_query("SELECT productID FROM SC_products WHERE Price = 0", $db1);
//while($row = mysql_fetch_array($sql))
//{
//
//    $sql_products = mysql_query("SELECT productID, Price FROM SC_products WHERE productID = ".$row['productID'], $db2);
//    $row_products = mysql_fetch_array($sql_products);
//    if ($row_products['Price'] > 0)
//        var_dump (mysql_query("UPDATE SC_products SET Price = '".$row_products['Price']."' WHERE productID = ".$row['productID'], $db1));
//}

?>