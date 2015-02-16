<?php

/*
СоАвтор: gaman.os@yandex.ru
*/

// переменные которые необходимо настроить для синхронизации с 1С
$host="localhost";// $_GET['hst'];   // имя хоста
$base="shopscript5";//$_GET['bs'];   // имя базы

// конец блока настройки переменных, ниже по тексту ничего изменять не нужно

$user='';
$pwd='';
$last_ID='0';
$maxrowscount = 10000;
$key='12v3iool';
ini_set("display_errors", "1"); // режим работы 1 - выводить ошибки, 0 - отключить ошибки


if( isset($_POST['usr']))
{
$user=$_POST['usr'];
$pwd=$_POST['pwd']; // пароль к базе

}

if (isset($_POST['last_ID']))
{
$last_ID = $_POST['last_ID'];
}

if( isset($_POST['maxrowscount']))
{
$maxrowscount=$_POST['maxrowscount'];
$maxrowscount = (int)$maxrowscount;
}



$MySQLHost= mysql_connect($host, $user, $pwd);
if (!$MySQLHost) {

	die("Connection error".$user);

}

mysql_select_db ($base, $MySQLHost) or die ("Не могу соединиться с базой данных. Ошибка: " . mysql_error());

mysql_query("SET NAMES='utf8'");
mysql_query("SET CHARACTER SET utf8");//cp1251
//mysql_query( "set character_set_client='cp1251'" );
//mysql_query( "set character_set_results='cp1251'" );
mysql_query( "set character_set_client='utf8'" );
mysql_query( "set character_set_results='utf8'" );
mysql_query( "set collation_connection='utf-8_general_ci'" );

$submit = ( isset($_POST['submit']) ) ? intval($_POST['submit']) : 0;

if ( !empty($submit) )

{

    //Здесь работаем с содержимым переданного файла.

    $uploadFile = $_FILES['data'];

    $tmp_name = $uploadFile['tmp_name'];

    if ( !is_uploaded_file($tmp_name) )

    {

        die('Ошибка при загрузке файла');

    }

    else

    {

        //Считываем файл в строку

        $data = file_get_contents($tmp_name);
		
		//$data = $iconv("UTF-8","Windows-1251",$data);

        //Декодируем данные

       // $data = base64_decode($data);

        //Теперь нормальный файл можно сохранить на диске

      /*  $data_filename = $_FILES['data']['name'];

        if ( !empty($data) && ($fp = @fopen($data_filename, 'wb')) )

        {

            @fwrite($fp, $data);

            @fclose($fp);

        }

        unset($data);*/

    }

}



// переменные выполнения функций

$run=$_POST['run'];   // префикс выполнения

$seekrow = intval($_POST['seekrow']);   // индекс продолжения выборки;



function GetRealIp()

{

 if (!empty($_SERVER['HTTP_CLIENT_IP']))

 {

   $ip=$_SERVER['HTTP_CLIENT_IP'];

 }

 elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))

 {

  $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];

 }

 else

 {

   $ip=$_SERVER['REMOTE_ADDR'];

 }

 return $ip;

}



// работаем с базой

// очищаем таблицу по заданным параметрам

if ($run == ('clean')) {

	if ($base == '') {

		die ("имя базы не задано - парамтр bs"); }

	if ( !empty($data))

	{

		$data = explode("DELETE",$data);

		for ($i=1;$i<count($data);$i++)

		{

			mysql_query(trim("DELETE ".$data[$i]));

			if (mysql_errno() == 1049) {

				die ('Ошибка обращения к базе '.$base);}

			if (mysql_errno() == 1146) {

				echo (trim("DELETE ".$data[$i]));echo ('<br>');

				die ('Ошибка выполнения команды из фала '.basename($file));}

			if (mysql_error()=='') {echo mysql_affected_rows();echo "\n"; // mysql_num_rows($result);

			} else{die(mysql_error());}

        }

			die ();

	}

	die ("Error file ".$file);

}





// загружаем масив таблиц из файла в базу

if ($run == ('load')) {

	if ($base == '') {

		die ("имя базы не задано - парамтр bs"); }

	if ( !empty($data))

	{

		$data = explode("INSERT INTO",$data);

		for ($i=1;$i<count($data);$i++)

		{

			mysql_query(trim("INSERT INTO ".$data[$i]));

			if (mysql_errno() == 1049) {

				die ('Ошибка обращения к базе '.$base);}

			if (mysql_errno() == 1146) {

				echo (trim("INSERT INTO ".$data[$i]));echo ('<br>');

				die ('Ошибка выполнения команды из файла '.basename($file));}

			if (mysql_error()=='') {
			
			if ($last_ID=='0') 
			{			
			echo mysql_affected_rows()
			;echo "\n";
			}			
			else
			{
			echo mysql_insert_id()
			//}
			;echo "\n"; // mysql_num_rows($result);
			}
			} else{
			//Тим+  Передали 50 инсертов. Из-за того что один не пашет, остальные 49 мучаются
			//die(mysql_error());
			echo mysql_error();
			echo "\n";
			//Тим-	
			}

        }

			die ();

	}

	die ("Error file ".$file);

}



// отображаем таблицу из базы по выбранным параметрам

if ($run == ('list')) {



	if ($base == '') {

		die ("имя базы не задано - парамтр bs"); }

	if ( !empty($data))

	{

       	// zip var and include
		// start
		//include_once("Ziplib.php");
		//$zip = new Zip();
		//$zip->setComment("DB responce Zip file.\nCreated on " . date('l jS \of F Y H:i:s'));
		//$zip->__construct(true);
		$responce = '';
		$filescount = 0;
		$rowscount = 0;
		// end

		//$data = implode("",file($file));
        $tat = ""; //заголовок
		//$data = explode("SELECT",$data);

		//for ($i=1;$i<count($data);$i++)
		
			
			$data = mysql_query($data);
			if (mysql_errno() == 1049) {

				die ('Ошибка обращения к базе '.$base);}

			if (mysql_errno() == 1146) {

				echo (trim("SELECT ".$data[$i]));echo ('<br>');

				die ('Ошибка выполнения команды из файла ');}

                if (mysql_error()!='') {die(mysql_error());}


				$columns = mysql_num_fields($data);

				for ($i = 0; $i < $columns; $i++) {

    			$meta = mysql_fetch_field($data, $i);

   		 		$tat = ($tat.$meta->name."{;}");

    			}

     			//echo($tat."\n");
	            if($seekrow==1)
	            {
	            $seekrow= $seekrow-1;
	     		$responce .= $tat."\n";
				echo($tat."\n");
	     		}

				$endrow = $seekrow+$maxrowscount;

				//$search = array("\r\n", "\n", "\r");

		  		$num_rows = mysql_num_rows($data);
		      if($num_rows-1<$seekrow)
		 		{
				$seekrow=$num_rows-1;
				if($seekrow<0)
				{
				$seekrow=0;
				}
		 		}
			if($num_rows>0 AND mysql_data_seek($data, $seekrow))
        {
			while($dat=mysql_fetch_array($data))
			//for ($c =0; $c <= $resultrows; $c++)
			{
			 if($rowscount==$maxrowscount)
			 {  //выходим если вывели все строки по плану но еще осталось что выбирать
			    //$responce .= "\n";

			    $responce .= "endrow-".$endrow;
				echo("endrow-".$endrow);
			 	break;
			 }


			// формируем значения csv таблицы

			//echo( $tat."\n");

				for ($i = 0; $i < $columns; $i++)
				{
				$meta = mysql_fetch_field($data, $i);

				//$responce .= str_replace($search," ",$dat[$meta->name])."{;}";
				$responce .= $dat[$meta->name]."{;}";
				echo($dat[$meta->name]."{;}");
				}
				$responce .= "{::}";
				echo("{::}");
           		$responce .= "\n";
				echo("\n");
           		$rowscount=$rowscount+1;
           }

   		}
			//для теста лучше использовать break; а не die(); соответственно закомментить если нужно более одного запроса.
			//break;//только 1 запрос

        

		// zip container START
        $filescount =$filescount+1;
        $strcount = strval($filescount);
		
		//echo("--");
		//$Dlina = count($response);
		//echo($dlina);
		//echo("--");
		//echo($response);
		//echo("--");
		//echo("gagaga");
		die();
		
		
		
		//$zip->addFile($responce, "zipresponce".$strcount.".was");
		//$zip->sendZip("responce.zip");

		// zip container END

		die ();
	}

	die ("Error file ".$file);

}

// отображаем таблицу из базы по выбранным параметрам
if ($run == ('loadu')) {

	if ($base == '') {

		die ("имя базы не задано - парамтр bs"); }

	if ( !empty($data))

	{

		//mysql_query("SET NAMES cp1251");

		//mysql_query("SET CHARACTER SET cp1251");//cp1251

		//$data = implode("",file($file));

		$data = explode("UPDATE",$data);
        $allaffect=0;
		for ($i=1;$i<count($data);$i++)

		{

			//$allaffect=0;

			//mysql_query(trim("UPDATE ".$data[$i]));
			$StrokaZaprosa = trim("UPDATE ".$data[$i]);
			//$StrokaZaprosa  = iconv("UTF-8","Windows-1251",$StrokaZaprosa);
			
			//echo($StrokaZaprosa);
			
			
			//$StrokaZaprosa = htmlentities($StrokaZaprosa);
			mysql_query($StrokaZaprosa);
            //echo (trim($data[$i]));
			if (mysql_errno() == 1049) {

				die ('Ошибка обращения к базе '.$base);}

			if (mysql_errno() == 1146) {

				echo (trim($data[$i]));

				echo ('<br>');

				die ('Ошибка выполнения команды из фала '.basename($file));}

			if (mysql_error()=='') { $allaffect = $allaffect + mysql_affected_rows();// mysql_num_rows($result);

			} else{die(mysql_error());}





		}

		 echo $allaffect;

		 die ();

	}

	die ("Error file ".$file);

}

if ($run == ('get_database_tables')) {
echo get_database_tables();
die();
}

if ($run == ('get_columns')){
get_columns($data);
die();
}

if ($run == ('get_files')){
new RidDir($key);
 
 }
 
 if ($run ==('add_column')){
 add_column($data);
 die("OK");
 }
 
 if ($run == ('searchIndex'))
{
$method=(string)$_REQUEST['method'];
$path=dirname(__FILE__).'/wa-config/SystemConfig.class.php';

    require_once($path);

    waSystem::getInstance(NULL,new SystemConfig('backend'));

    $app='shop';
    $app_system=waSystem::getInstance($app,NULL,TRUE);

    $search=new shopIndexSearch();

    $ids=(string)$_REQUEST['ids'];

    foreach(explode(',',$ids) as $v)
    if(is_numeric($v))
    $search->$method((int)$v);

die("OK");
}
 
 
 


/////////////////////////////////////////////////Timoha edition+
function get_database_tables()
{
$responce = '';

	$r = mysql_query("SHOW TABLES");
	if (mysql_num_rows($r)>0)
	{
		while($row = mysql_fetch_array($r, MYSQL_NUM))
		{
			//$ret[] = $row[0];
			$responce .= $row[0]."\n";
			
		}
	}
	return $responce;
}

function get_columns($table_name)
{
$responce = '';


$result = mysql_query("SHOW COLUMNS FROM ".$table_name);
//echo($Tek);
  while($col = mysql_fetch_row($result)){
  //while($col = mysql_fetch_field($result)){

    //print_r($col); print "<br>\n";
	//echo(gettype($col));
	//echo($col);
	echo("\n");
	
	
	for ($ii=0;$ii<count($col);$ii++){
	$znachenie = $col[$ii];
	//if (gettype($znachenie)=="NULL"){
	//echo("NULL"."{;}");
	//}
	//else	
	//{
	echo($znachenie."{;}");
	//}
	
	}
	
	
	//for ($i=1;$i<count($col);$i++)
	//	{
	//	$tekZnach = $col[i];
	//	//echo($tekZnach);
	//	echo(gettype($tekZnach));
	//	}
	
	
	$responce .= $col."\n";
	}
return $responce;
}

function add_column($query){
//$responce = 'OK';
$result = mysql_query($query);
return "OK";
}

function getArrayOfFTPFiles($ftp_server,$ftp_user_name,$ftp_user_pass,$ftp_directory)
{
$conn_id = ftp_connect($ftp_server);

// проверка имени пользователя и пароля
$login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass);

// получение списка файлов директории /
$buff = ftp_rawlist($conn_id, $ftp_directory,true);

// закрытие соединения
ftp_close($conn_id);

// вывод буфера
//var_dump($buff);
return $buff;
}








class RidDir
{

  /**
   *
   */
  public function __construct($key)
  {
  
  //var_dump($_REQUEST);
    if
    (
      isset
      (
	$_REQUEST['key'],
	$_REQUEST['path']
      )
      &&
      $_REQUEST['key']==$key
    )
    {
      $path=dirname(__FILE__).(string)$_REQUEST['path'];
      
      if(file_exists($path))
      {
	$str='';
	
	$this->rriddir($path,$str);
	
	exit($str);
      }
    }
  }
  
  /**
   *
   */
  private function rriddir($path,&$str)
  {
    if($handle=opendir($path))
    {
      while(FALSE!==($file=readdir($handle)))
      { 
	if($file!='.' && $file!='..')
	{ 
	  $f=$path.$file;
	  
	  $arr=array();
	  
	  $arr['path']=$f;
	  
	  if(($is_dir=is_dir($f)))
	  {
	    $arr['type']='d';
	    $arr['md5']='';
	    $arr['size']='';
		//echo "d";
		//echo "\n";
	  }
	  else
	  {
	    $arr['type']='f';
	    $arr['md5']=md5_file($f);
	    $arr['size']=filesize($f);
		//echo "f";
		//echo "\n";
	  }
	  
	  foreach($arr as $k=>$v)
	  {
	    $arr[$k]=$this->csv_string($v);
	  }
	  
	  $str.=($str==''?'':"\n").join(';',$arr);
	    
	  if($is_dir)
	  {
	    $fns=__FUNCTION__;
	    $this->$fns($f.'/',$str);
	  }
	} 
      }
      
      closedir($handle); 
    }
  }
  
  /**
   *
   */
  private function csv_string($str)
  {
    return '"'.str_replace('"','""',$str).'"';
  }
}

/////////////////////////////////////////////////Timoha edition-




// закрываем базу

mysql_close();

//echo ("OK");

?>