<?php

error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 'On');

header('Content-Type: text/html; charset=utf-8');
$db = mysql_connect("localhost", "shopscript5", "k3eukm8y");               //новый shop-script
mysql_select_db("shopscript5", $db);
mysql_query("set names utf8", $db);

class Api {

    const TOKEN = 'a599af483c1386481f14f22385573468';

    public static function request($method, $url, $data = array()) {
        $result = null;
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_HTTPHEADER     => array('Token:'.self::TOKEN),
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_POSTFIELDS     => empty($data) ? '' : json_encode($data),
        ));
        $answer = curl_exec($ch);
        if (curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200)
        {
            $result = json_decode($answer, true);
        }

        curl_close($ch);
        return $result;
    }

}

$result = Api::request('GET', 'http://www.berito.ru/api/v1/orders/?status=2');

//$order = array();
//$order['buyer']['name'] = 'Валера';
//$order['buyer']['email'] = 'valera@mail.ru';
//$order['buyer']['phone'] = '+792111223344';
//  foreach ($result['result'] as $order)
//  {
//  print_r ($order);
//  echo '<br><br>';
//  } 
foreach ($result["result"] as $order)
{
//    Api::request('PUT', 'http://www.berito.ru/api/v1/orders/'.$order['id'].'/', array('status' => 2));
//    $new_order = Api::request('GET', 'http://www.berito.ru/api/v1/orders/'.$order['id'].'/');
//    $order = $new_order['result'];
//
    $price = 0;
    foreach ($order['products'] as $product)
    {
        $price = $price + $product['price'];
    }

    $order['total'] = $order['delivery']['price'] + $price;
//-----------------------------------CUSTOMER---------------------------------//


    if (isset($order['buyer']['email']))
    {
        $sql = mysql_query("SELECT * FROM wa_contact_emails WHERE email = '".$order['buyer']['email']."'");
        if (mysql_num_rows($sql))
        {
            $row = mysql_fetch_array($sql);
            $user['id'] = $row['contact_id'];
        }
    } else
    {
        $phone = $order['buyer']['phone'];
        $phone = preg_replace('~[^0-9]+~', '', $phone);
        $phone = substr($phone, 1);
        $phone1 = '8'.$phone;
        $phone2 = '+7'.$phone;

        $sql = mysql_query("SELECT * FROM wa_contact_data WHERE field = 'phone' AND value IN ('".$phone1."', '".$phone2."')");
        if (mysql_num_rows($sql))
        {
            $row = mysql_fetch_array($sql);
            $user['id'] = $row['contact_id'];
        }
    }

    if (!isset($user['id']))
    {

        if (isset($order['buyer']['name']))
            $user['name'] = $order['buyer']['name'];
        else
            $user['name'] = 'berito';

        $datetime = date("Y-m-d H:i:s");

        mysql_query("INSERT INTO wa_contact (name,
                                             firstname,
                                             is_user,
                                             create_datetime,
                                             create_app_id,
                                             create_method,
                                             create_contact_id,
                                             locale) VALUES ('".$user['name']."',
                                                             '".$user['name']."',
                                                             '0',    
                                                             '".$datetime."',
                                                             'shop',
                                                             'berito',
                                                             '1',
                                                             'ru_RU')");
        $user['id'] = mysql_insert_id();

        mysql_query("INSERT INTO wa_contact_categories (category_id, contact_id) VALUES ('2', '".$user['id']."')");

        if (isset($order['buyer']['email']))
            mysql_query("INSERT INTO wa_contact_emails (contact_id,
                                                        email,
                                                        ext,
                                                        sort,
                                                        status) VALUES ('".$user['id']."',
                                                                        '".$order['buyer']['email']."',
                                                                        '',
                                                                        '0',
                                                                        'unknown')");

        if (isset($order['buyer']['phone']))
            mysql_query("INSERT INTO wa_contact_data (contact_id, field, ext, value, sort) VALUES ('".$user['id']."', 'phone', '', '".$order['buyer']['phone']."', '0')");

        if (isset($order['buyer']['address']))
            mysql_query("INSERT INTO wa_contact_data (contact_id, field, ext, value, sort)
                                                        VALUES ('".$user['id']."', 'address:street', '', '".$order['buyer']['address']."', '0')");

        mysql_query("INSERT INTO shop_customer (contact_id) VALUES ('".$user['id']."')");
    }


//---------------------------------END CUSTOMER-------------------------------//
//------------------------------------ ORDER ---------------------------------//

    if (isset($order['comment']))
        $comment = $order['comment'];
    else
        $comment = '';


    mysql_query("INSERT INTO shop_order (contact_id,
                                         create_datetime,
                                         state_id,
                                         total,
                                         currency,
                                         rate,
                                         tax,
                                         shipping,
                                         discount,
                                         comment) VALUES ('".$user['id']."',                                                       
                                                          '".$order['created']."',
                                                          'new',
                                                          '".$order['total']."',
                                                          'RUB',
                                                          '1.0',
                                                          '0',
                                                          '0',
                                                          '0',
                                                          '".$comment."')");

    $id = mysql_insert_id();

    mysql_query("INSERT INTO shop_order_params (order_id, name, value) VALUES ('".$id."', 'storefront', 'www.berito.ru')");
    mysql_query("INSERT INTO shop_order_params (order_id, name, value) VALUES ('".$id."', 'berito', '".$order['id']."')");
    mysql_query("INSERT INTO shop_order_params (order_id, name, value) VALUES ('".$id."', 'shipping_name', '".$order['delivery']['description']."')");
    mysql_query("INSERT INTO shop_order_params (order_id, name, value) VALUES ('".$id."', 'payment_name', '".$order['payment']['description']."')");

    if (isset($order['buyer']['address']))
    {
        mysql_query("INSERT INTO shop_order_params (order_id, name, value) VALUES ('".$id."', 'shipping_address.street', '".$order['buyer']['address']."')");
        mysql_query("INSERT INTO shop_order_params (order_id, name, value) VALUES ('".$id."', 'billing_address.street', '".$order['buyer']['address']."')");
    }


    if (isset($order['buyer']['email']))
    {
        $email = $order['buyer']['email'];
    } else
    {
        $sql = mysql_query("SELECT * FROM wa_contact_emails WHERE id = '".$user['id']."'");
        if (mysql_num_rows($sql))
        {
            $row = mysql_fetch_array($sql);
            $email = $row['email'];
        }
    }

    if (isset($email) && !empty($email))
        mysql_query("INSERT INTO shop_order_params (order_id, name, value) VALUES ('".$id."', 'contact_email', '".$email."')");

    if (isset($order['buyer']['name']))
    {
        $name = $order['buyer']['name'];
    } else
    {
        $sql = mysql_query("SELECT * FROM wa_contact WHERE id = '".$user['id']."'");
        if (mysql_num_rows($sql))
        {
            $row = mysql_fetch_array($sql);
            $name = $row['shipping_firstname'].' '.$row['shipping_lastname'];
        }
    }

    if (isset($name) && !empty($name))
    {
        mysql_query("INSERT INTO shop_order_params (order_id, name, value) VALUES ('".$id."', 'shipping_contact_name', '".$name."')");
        mysql_query("INSERT INTO shop_order_params (order_id, name, value) VALUES ('".$id."', 'contact_name', '".$name."')");
        mysql_query("INSERT INTO shop_order_params (order_id, name, value) VALUES ('".$id."', 'billing_contact_name', '".$name."')");
    }
//----------------------------------END ORDER --------------------------------//
//----------------------------------ORDER ITEMS-------------------------------//

    $sql_stock = mysql_query("SELECT * FROM shop_stock LIMIT 1");
    if (mysql_num_rows($sql_stock))
    {
        $row_stock = mysql_fetch_array($sql_stock);
        $stock_id = $row_stock['id'];
    }

    foreach ($order['products'] as $product)
    {
        $sql_product = mysql_query("SELECT * FROM shop_product WHERE id = '".$product['import']['id']."'");
        if (mysql_num_rows($sql_product))
        {
            $row_product = mysql_fetch_array($sql_product);
        }

        $sql_skus = mysql_query("SELECT * FROM shop_product_skus WHERE name = '".$product['import']['size']."'");
        if (mysql_num_rows($sql_skus))
        {
            $row_skus = mysql_fetch_array($sql_skus);
        }

        mysql_query("INSERT INTO shop_order_items (order_id,
                                                   name,
                                                   product_id,
                                                   sku_id,
                                                   sku_code,
                                                   type,
                                                   price,
                                                   quantity,
                                                   stock_id,
                                                   purchase_price) VALUES ('".$id."',
                                                                           '".$row_product['name']."',
                                                                           '".$product['import']['id']."',
                                                                           '".$row_skus['id']."',
                                                                           '',    
                                                                           'product',
                                                                           '".(float) $row_product['Price']."',
                                                                           '1'.
                                                                           '".$stock_id."',
                                                                           '0')");

        mysql_query("UPDATE shop_product_skus SET count=count-1 WHERE id = '".$row_skus['id']."'");
        mysql_query("UPDATE shop_product SET count=count-1 WHERE id = '".$product['import']['id']."'");
    }

//--------------------------------END ORDER ITEMS-----------------------------//
//----------------------------------ORDER LOG---------------------------------//

    mysql_query("INSERT INTO shop_order_log (order_id,
                                             contact_id,
					     action_id,
                                             datetime,
                                             after_state_id,
                                             text) VALUES (".$id.",
						           '1',
						           'action',
                                                           '".$datetime."',                                                                         
                                                           'new',
                                                           'Заказ оформлен покупателем c Берито')");

//---------------------------------END ORDER LOG-------------------------------//    
//-------------------------------CUSTOMER UPDATE------------------------------//    

    mysql_query("UPDATE shop_customer SET total_spent = total_spent+'".$order['total']."',
                                          number_of_orders = number_of_orders +1,
                                          last_order_id = '".$id."'
                                          WHERE contact_id = '".$user['id']."'");
    exit;
//----------------------------END CUSTOMER UPDATE-----------------------------//      
}
//
//
//
//
//
//foreach ($result["result"] as $order)
//{

//    foreach ($order['products'] as $product)
//    {
//        $sql = mysql_query("SELECT * FROM  `SC_product_options_set` WHERE `productID` ='".$product['import']['id']."'");
//        while ($row = mysql_fetch_array($sql))
//        {
//            $sql_var = mysql_query("SELECT * FROM  `SC_products_opt_val_variants` WHERE  `variantID` ='".$row['variantID']."'");
//            while ($row_var = mysql_fetch_array($sql_var))
//            {
//                if ($row_var['option_value_ru'] == $product['import']['size'])
//                {
//                    $product['variantID'] = $row['variantID'];
//                    $product['name_size'] = $row_var['option_value_ru'];
//                }
//            }
//        }
//
//        $sql_product = mysql_query("SELECT * FROM  `SC_products` WHERE `productID` ='".$product['import']['id']."'");
//        while ($row_product = mysql_fetch_array($sql_product))
//        {
//            $product['name'] = $row_product['name_ru'];
//        }
//
//        //print_r ($product);
//        //echo '<br><br>';
//
//        $sql_id = mysql_query("SELECT MAX(`itemID`) FROM `SC_ordered_carts`");
//        $row_id = mysql_fetch_array($sql_id);
//        $itemID = $row_id[0] + 1;
//
//        $sql = mysql_query("INSERT INTO `SC_ordered_carts`(`itemID`,
//		                                                   `orderID`, 
//		                                                   `name`, 
//                                                                   `Price`,
//                                                                   `Quantity`,
//                                                                   `tax`,
//                                                                   `variantID`, 
//                                                                   `productID`) VALUES ('".$itemID."',
//                                                                                        '".$order['orderID']."',
//                                                                                        '".$product['name']." (".$product['name_size'].")',
//                                                                                                                '".(float) $product['price']."',
//                                                                                                                '1',
//                                                                                                                '0',
//                                                                                                                '".$product['variantID']."',
//                                                                                                                '".$product['import']['id']."')");
//
//        $sql = mysql_query("INSERT INTO `SC_shopping_carts` (`customerID`,
//		                                                     `itemID`,		                                                    
//														     `Quantity`) VALUES ('".$user['customerID']."',
//															                     '".$itemID."',
//																				 '1')");
//
//        $sql = mysql_query("INSERT INTO `SC_shopping_cart_items` (`itemID`,		                                                    
//														          `productID`) VALUES ('".$itemID."',
//																				       '".$product['import']['id']."')");
//
//        $sql = mysql_query("INSERT INTO `SC_shopping_cart_items_content` (`itemID`,		                                                    
//														                  `variantID`) VALUES ('".$itemID."',
//																				               '".$product['variantID']."')");
//
//        mysql_query("UPDATE `SC_product_options_set` SET in_stock = (in_stock-1) WHERE   `productID` = ".$product['import']['id']." AND `variantID` = ".$product['variantID']);
//        mysql_query("UPDATE `SC_products` SET in_stock = (in_stock-1) WHERE   `productID` = ".$product['import']['id']);
//        //var_dump ($sql);
//    }
//} 
