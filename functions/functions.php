<?
                   


function bbcodes($str) {
		return str_replace(array("[br]","[b]","[i]","[u]","[/b]","[/i]","[/u]"), array("<br>","<b>","<i>","<u>","</b>","</i>","</u>"), $str);
}

////////////////////склонение. пример - plural_form($cr_res[$i]['duration'],['день','дня','дней']) - 1 день 3 дня 10 дней
function plural_form($n, $forms) {
      return $n%10==1&&$n%100!=11?$forms[0]:($n%10>=2&&$n%10<=4&&($n%100<10||$n%100>=20)?$forms[1]:$forms[2]);
 }

//////////////////////отправка email по SMTP
function mailSend($to,$title,$msg) {
  $server = "mail.rosstour.ru";// "mail.rosstour.ru";
  $port = 25;
  $login = "info@rosstour.ru";
  $pass = "jikmjikm";
  $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
  $result = socket_connect($socket, $server, $port);

  if ($result === false) return false;
  smtpCommand($socket, "EHLO 82.151.193.208"); // Посылаем на сервер, что будет аутентификация по логину и паролю  
  smtpCommand($socket, "AUTH LOGIN"); // передаем команду ввода логина и пароля
  smtpCommand($socket, base64_encode($login)); // логин, надо кодировать в BASE64
  smtpCommand($socket, base64_encode($pass)); // пароль, надо кодировать в BASE64
  smtpCommand($socket, "MAIL FROM: $login"); // указываем значение поля "От кого"
  smtpCommand($socket, "RCPT TO: ".$to); // указываем значение поля "Кому"
  smtpCommand($socket, "DATA"); // говорим серверу, что будет сообщение письма
  smtpCommand($socket,'Message-ID: <'.rand(1000000000,9999999999).".".rand(1000000000,9999999999).'@rosstour.ru>
From: =?utf-8?B?'. base64_encode($login) .'?= <'.$login.'>
To: <'.$to.'>
Subject: =?utf-8?B?'. base64_encode($title) .'?=
Date: '.date(DATE_RFC822).'
MIME-Version: 1.0
Content-Type: text/html; charset="utf-8"
Content-Transfer-Encoding: quoted-printable

<HTML><HEAD>
<META content=3D"text/html; charset=3Dutf-8" http-equiv=3DContent-Type>
<META name=3DGENERATOR content=3D"MSHTML 8.00.6001.23067">
<STYLE></STYLE>
</HEAD>
'.str_replace('=','=3D',$msg).'
</HTML>
',false);

  smtpCommand($socket, "");
  smtpCommand($socket, ".");
  smtpCommand($socket, "RSET");
  smtpCommand($socket, "QUIT"); // Собственно отправляем письмо и выходим
  socket_close($socket);
return true;
}

// Функция для отправки запроса серверу
function smtpCommand($socket, $msg, $out=true) {
 socket_write($socket, $msg."\r\n", strlen($msg."\r\n"));
 if($out){
   $r = socket_read($socket, 2048);
  } else sleep(1);
}


//Функции для работы с memcache
define('MEMCACHE_KEY', 'rt.plus');

function memcacheConnect(){
	global $memcache;
	$memcache = new memcache();
	$memcache->connect('127.0.0.1', 11211);
}

function memcacheGet($key){
	global $memcache;
	if (!$memcache) memcacheConnect();
	return $memcache->get(MEMCACHE_KEY . ':' . $key);
}

function memcacheSet($key, $var, $flag, $expire){
	global $memcache;
	if (!$memcache) memcacheConnect();
	return $memcache->set(MEMCACHE_KEY . ':' . $key, $var, $flag, $expire);
}

function memcacheAdd($key, $var, $flag, $expire){
	global $memcache;
	if (!$memcache) memcacheConnect();
	return $memcache->add(MEMCACHE_KEY . ':' . $key, $var, $flag, $expire);
}

function memcacheDelete($key){
	global $memcache;
	if (!$memcache) memcacheConnect();
	return $memcache->delete(MEMCACHE_KEY . ':' . $key);
}

function multiCURL($nodes) {
    //$this->opers[]= array('url' => 'https://'.RT_SERVER.'/avia/'.AVIAVERSION.'/opers/gate.'.$oper.'.php?a=find&nojson', 'data' => $this->req); 
    //foreach(multiCURL($this->opers) as $oper_id => $ret) if ($ret != NULL) 
    
    $mh = curl_multi_init();
    $ch = array();
    $s = '';
    foreach($nodes as $i => $node) {
    $s .= "\r\n".$node['url'].'?'.http_build_query($node['data']);
        $ch[$i] = curl_init($node['url']);
        curl_setopt($ch[$i], CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch[$i], CURLOPT_HEADER, 0);
        curl_setopt($ch[$i], CURLOPT_POST, 1);
        curl_setopt($ch[$i], CURLOPT_POSTFIELDS, http_build_query($node['data']));
        curl_setopt($ch[$i], CURLOPT_TIMEOUT, 180);
        curl_setopt($ch[$i], CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch[$i], CURLOPT_HTTPHEADER, array('SOAPAction: ""'));
        curl_setopt($ch[$i], CURLOPT_ENCODING, 'gzip,deflate');
        curl_multi_add_handle($mh, $ch[$i]);
    }
	
	// print_r($s);exit;
	
    // file_put_contents('multicurl-find.txt',$s);
    do {
        $n = curl_multi_exec($mh, $active);
        //usleep(100);
		while(curl_multi_select($mh) === 0) {}
    }
    while ($active);
    $res = array();
    foreach($nodes as $i => $node) {
        $res[$i] = curl_multi_getcontent($ch[$i]);
        curl_multi_remove_handle($mh, $ch[$i]);
        curl_close($ch[$i]);
    }
    curl_multi_close($mh);
    return $res;
}