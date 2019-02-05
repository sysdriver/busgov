<?php

error_reporting(0);
@ini_set('display_errors', 0);
// Отвечаем только на Ajax
//if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest') {die('exit');}

//link parser
include_once('classes/simplehtmldom/simple_html_dom.php');
include_once('classes/agency/Agency.php');

if(!$db = Agency::get_db('localhost','root','','busgov'))
    die();

$action = $_POST['action'];
if($action == 'show' || $action == 'download') {
    Agency::printAgcs($db,$action);
    //return 0;
}
elseif($action == 'delete') {
    //echo json_encode(array('html' => 'test'));
    Agency::clearDb($db);
}
else {
    $url = $_POST['url']; if (empty($url)) return;

    //$json = json_decode(file_get_contents('data.json'), true);
    $json = json_decode(file_get_contents($url), true);
    $inns = $json['inns'];


    $count = count($inns);
    //$count = 30;
    $step = 1;

    // Получаем от клиента номер итерации

    $offset = $_POST['offset'];
    $inn = $inns[$offset];

    Agency::setCookies(Agency::BG_SITE);		//set cookies; 13.12.13@todo: save cookies in db

    if(!Agency::getFromDb($db,$inn)) {	//check if there is already this inn in Db
        $agc = new Agency($inn);

        //ask 4 flags by every agc

        foreach(Agency::$flgPages as $flag => $page) {
            if($flag == 'fhd' && in_array($inn,array('6652012289','6652011623')))   //6652012289 (mkdou 54), 6652011623 (mkou sosh 9) - don't have plan fhd
                continue;
            $agc->getAllFlags($flag,$agc->agcId);          
        }
        $agc->save2db($db);
            $arAgc = array('agcid' => $agc->agcId, 'inn' => $agc->inn, 'agcname' => $agc->agcName, 'mcpzdn' => $agc->flags['mcpZdn'], 
            'bdtsmt' => $agc->flags['bdtSmt'], 'fhd' => $agc->flags['fhd'], 'clvsrdst' => $agc->flags['clvSrdst']);

        //$agc = null;  //destroy object to release memory
    }
    sleep(1);

    // Проверяем, все ли строки обработаны
    $offset = $offset + $step;
    if ($offset >= $count) {
      $sucsess = 1;
    } else {
      $sucsess = round($offset / $count, 2);
    }

    // И возвращаем клиенту данные (номер итерации и сообщение об окончании работы скрипта)
    $output = Array('offset' => $offset, 'sucsess' => $sucsess, 'agc' => $arAgc);
    echo json_encode($output);
}