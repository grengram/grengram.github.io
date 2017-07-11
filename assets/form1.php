<?php 
/* 	
If you see this text in your browser, PHP is not configured correctly on this webhost. 
Contact your hosting provider regarding PHP configuration for your site.
*/
require_once('form_throttle.php');
// CRM server conection data
define('CRM_HOST', 'cwt.bitrix24.ru'); // your CRM domain name
define('CRM_PORT', '443'); // CRM server port
define('CRM_PATH', '/crm/configs/import/lead.php'); // CRM server REST service path

// CRM server authorization data
// OR you can send special authorization hash which is sent by server after first successful connection with login and password
define('CRM_AUTH', 'f331c0056e93a1f719d3349de7ac70d1'); // authorization hash

/********************************************************************************************/

// POST processing
if ($_SERVER['REQUEST_METHOD'] == 'POST')
{


//pbx.php начало


$strHost = "pbxcwt.dynru.org";
$strUser = "webcall_emkost";
$strSecret = "xzCOoPOspzOpSz";
$strChannel = "Local/s@from-script-emkost";
$strContext = "from-script";
$strWaitTime = "30";
$strPriority = "1";
$strExten = $_REQUEST["custom_phone"];
$strCallerId = "emkost <$strExten>";
$length = strlen($strExten);

if ($length == 11 && is_numeric($strExten))
{
$oSocket = fsockopen($strHost, 5038, $errnum, $errdesc) or die("Connection to host failed");
fputs($oSocket, "Action: login\r\n");
fputs($oSocket, "Events: off\r\n");
fputs($oSocket, "Username: $strUser\r\n");
fputs($oSocket, "Secret: $strSecret\r\n\r\n");
fputs($oSocket, "Action: originate\r\n");
fputs($oSocket, "Channel: $strChannel\r\n");
fputs($oSocket, "WaitTime: $strWaitTime\r\n");
fputs($oSocket, "CallerId: $strCallerId\r\n");
fputs($oSocket, "Exten: $strExten\r\n");
fputs($oSocket, "Context: $strContext\r\n");
fputs($oSocket, "Priority: $strPriority\r\n\r\n");
fputs($oSocket, "Action: Logoff\r\n\r\n");
sleep (1);
fclose($oSocket,128);
}


//pbx.php конец


$postData = array(
'TITLE' => 'Емкости: Заказ с мобильного',		
'NAME' => $_REQUEST["custom_name"],		
'EMAIL_WORK' => $_REQUEST["Email"] ,
'PHONE_WORK' => $_REQUEST["custom_phone"],
'COMMENTS' => $_REQUEST["custom_comment"],		
'SOURCE_ID' => 'Заявка' , // Источник
'UF_CRM_1413182177'	=> 'Емкости' , // Продукт
'UF_CRM_1409138449' => $_COOKIE['refferer'],  // Площадка	
'UF_CRM_1409138465' => $_COOKIE['utm_term_coc'], // Поисковый запрос
);

// append authorization data
	if (defined('CRM_AUTH'))
	{
		$postData['AUTH'] = CRM_AUTH;
	}
	else
	{
		$postData['LOGIN'] = CRM_LOGIN;
		$postData['PASSWORD'] = CRM_PASSWORD;
	}

	// open socket to CRM
	$fp = fsockopen("ssl://".CRM_HOST, CRM_PORT, $errno, $errstr, 30);
	if ($fp)
	{
		// prepare POST data
		$strPostData = '';
		foreach ($postData as $key => $value)
			$strPostData .= ($strPostData == '' ? '' : '&').$key.'='.urlencode($value);

		// prepare POST headers
		$str = "POST ".CRM_PATH." HTTP/1.0\r\n";
		$str .= "Host: ".CRM_HOST."\r\n";
		$str .= "Content-Type: application/x-www-form-urlencoded\r\n";
		$str .= "Content-Length: ".strlen($strPostData)."\r\n";
		$str .= "Connection: close\r\n\r\n";

		$str .= $strPostData;

		// send POST to CRM
		fwrite($fp, $str);

		// get CRM headers
		$result = '';
		while (!feof($fp))
		{
			$result .= fgets($fp, 128);
		}
		fclose($fp);

		// cut response headers
		$response = explode("\r\n\r\n", $result);

		$output = '<pre>'.print_r($response[1], 1).'</pre>';
		echo '{"FormResponse": { "success": true,"redirect":"zakaz.html"}}';
	}
	else
	{
		echo 'Connection Failed! '.$errstr.' ('.$errno.')';
		echo '{"MusePHPFormResponse": { "success": false,"error": "Failed to send email"}}';
	}
}
else
{
	$output = '';
}
?>