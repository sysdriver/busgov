<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
class Agency {
    const BG_SITE = 'http://bus.gov.ru';
    //static public $strtPage = "/public/agency/quickSearch/searchNew.html?searchString=";
    static public $pages = array (
        'srchPg'	=>	'/public/agency/quickSearch/searchNew.html?searchString=',
        'mainPg'	=>	'/public/agency/agency.html?agency=',
    );
    static public $flgPages = array (
        'mcpZdn'	=>	'http://www.bus.gov.ru/public/agency/agency_tasks.html?agency=',
        'bdtSmt'	=>	'http://www.bus.gov.ru/public/agency/budget.html?agency=',
        'fhd'		=>	'http://www.bus.gov.ru/public/agency/agency_plans.html?agency=',
        'clvSrdst'	=>	'http://www.bus.gov.ru/public/agency/operations.html?agency=',
        //''		=>	'',
    );
	
	static public $cookies = array();
	
    public $agcId;
    public $addr;
    public $contacts;
    public $email;
    public $head;
    public $inn;
    public $agcName;
    public $flags = array();
    public $info;	//array('date' => elements
    public $page;	//full web address
    public $services;	//what service deliver agency

    public function __construct($inn) {
        $this->inn 		=	$inn;
        $this->page = self::BG_SITE.self::$pages['srchPg'].$inn;
        if($this->get_info($this->page)) {	//if parsing was successful
            $this->page = self::BG_SITE.self::$pages['mainPg'].$this->agcId;		//prepare next page
            $this->getFlags($this->page);
        }
    }

    private static function curl_init($page) {
		//7.12.2013@todo: add check on existing of page: if page doesn't exist then do not ask for other pages and don't save results
		//if page not exist then save message about this fact into log
        $ch = curl_init();
        $proxy = '213.141.146.146:80';      //109.232.190.180:80
        curl_setopt($ch, CURLOPT_URL, $page);
        //curl_setopt($ch, CURLOPT_PROXY, $proxy);
        //curl_setopt($ch, CURLOPT_HEADER, TRUE);		//вывод заголовков в ответе
        $headers = array(	//taken from firebug
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Encoding: gzip, deflate',
            'Accept-Language: ru-RU,ru;q=0.8,en-US;q=0.5,en;q=0.3',
            'Connection: keep-alive',
			//'Cookie: GMU_FRONT-Insert=R1811833858; path=/; JSESSIONID=00007tJzirq8dU2jo33rLT_6zcF:1731lnamn; _ym_visorc=w; gmuportal=1',
            'Host: bus.gov.ru',
            'User-Agent: Mozilla/5.0 (Windows NT 6.1; rv:23.0) Gecko/20100101 Firefox/23.0',
        );
        if(!empty(self::$cookies)) {
            $tmp = 'Cookie: ';
            foreach(self::$cookies as $val) {
                    $tmp .= $val['val'].'; ';
            }
            $headers[] = $tmp;			//Set cookies
            $tmp = null;
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_ENCODING , "gzip");	//un gzip
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);		//output results into var instead of output in browser
		//if(empty($cookies))
        curl_setopt($ch,CURLOPT_HEADER,true);		//enable http headers in answer
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 1000);
        //curl_setopt($ch, CURLOPT_TIMEOUT,1000%1000);

        $return = curl_exec($ch);
        $blocks = explode("\r\n\r\n",$return);
        //we need header - $blocks[0]
        $strs = explode("\r\n",$blocks[0]);
        $blocks = null;
        $str = null;
        $tmp = explode(" ",$strs[0]);
        $answ_code = $tmp[1];
        $strs = null;
        $tmp = null;
		
        $info = curl_errno($ch);
        curl_close($ch);

        unset($ch);
        if($info||strlen($return)==0||($answ_code != 200)) 
            return 0;
            //die('There are no answer from server.');
        return $return;
    }

    private function get_info($page) {
        if($str = self::curl_init($page)) {
            //print_r($str);
            //die;
            $html = str_get_html($str);		//receive html object
            if(count($html->find('div.result-element'))) {
                foreach($html->find('div.result-element') as $div) {
                    //echo $div->innertext;
                    if(count($div->find('a.result-title'))) {
                        foreach($div->find('a.result-title') as $a) {
                            //echo $a->href;
                            $name = $a->innertext;
                            $link = $a->href;
                        }
                    }

                    if(count($div->find('div.address'))) {
                        foreach($div->find('div.address span') as $adr) {
                            $address = $adr->innertext;
                        }
                    }

                    if(count($div->find('div.services'))) {
                        foreach($div->find('div.services') as $srv) {
                            if($srv->find('a.result-service')) {
                                foreach($srv->find('a.result-service') as $res_srv) {
                                        $services = $res_srv->innertext;
                                }
                            }
                        }
                    }

                }
            }
            $str = explode("                                                     ", trim($address));		//53 spaces
            //print_r($str);
            //die;

            $this->addr = trim($str[0]);
            $this->contacts = trim($str[1]);
            $this->services = trim($services);
            $this->agcName = trim($name);            
//*********************************
//error due small length agcId string, was increased by 1 from 5 to 6
            $this->agcId = mb_substr($link,34,6);   
            
            $html->clear();		//get away from memory leaks
            return 1;
        }
        else
            return 0;
    }
	
    public function getFlags($page) {
		
        if($str = self::curl_init($page)) {
            $html = str_get_html($str);		//receive html object
            if(count($html->find('div[class=wrapTable_2 agencyDocumentTab]'))) {
                $div = $html->find('div[class=wrapTable_2 agencyDocumentTab]',0);
                if(count($div->find('table tbody'))) {
                    $tbody = $div->find('tbody',0);
                    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
                    //http://stackoverflow.com/questions/2340952/tbody-glitch-in-php-simple-html-dom-parser
                    //in simple_html_dom.php file comment or remove line #396
                    // if ($m[1]==='tbody') continue;
                    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
                    //$tbody = $tbody->find('tbody',0);
                    //print_r($tbody->innertext);
                    //die;
                    $table = array();
                    $tblHeaders = array(
                        'flName',
                        'stName',
                        'ppO',
                        'polnomochiya',
                        'grbs',
                        'codGlavyGrbs',
                        'rbsName',
                        'ofcType',
                        'ofcKind',
                        'okved',
                        'okato',
                        'oktmo',
                        'pptType',      //property type
                        'ofcTypeOkpf',  
                        'address',
                        'head',
                        'phone',
                        'email',
                    );
                    foreach($tbody->find('tr') as $row => $tr) {
                        $td = $tr->find('td',1);		//receive only values without headers
                        //foreach($tr->find('td') as $td)
                        if(!empty($td->plaintext))
                            $table[$tblHeaders[$row]] = trim($td->plaintext);
                    }
            //print_r($table);
            //die;
                    foreach($div->find('a.result-title') as $a) {
                        //echo $a->href;
                        $name = $a->innertext;
                        $link = $a->href;
                    }
                    if(!empty($table['email']))
                        $this->email = $table['email'];
					if(!empty($table['head']))
						$this->head = $table['head'];
                }
            }
            $html->clear();		//get away from memory leaks
            //print_r($str);            //die;
        }
    }
	
    public function getAllFlags($flag,$agcId) {
        $page = self::$flgPages[$flag].$agcId;
        if($str = self::curl_init($page)) {
            $html = str_get_html($str);		//receive html object
            if(count($html->find('div[class=wrapTable_2]'))) {
                $div = $html->find('div[class=wrapTable_2]',0);
                if(count($div->find('table tbody'))) {
                    $tbody = $div->find('tbody',0);
                    foreach($tbody->find('tr') as $row => $tr) {
                        $td = $tr->find('td',1);
                        //$td = $tr->find('td',2);	//receive publication date
                        //в данном случае может быть инф. за 2012, 2013 год, но выведется сюда
                        //только за 2013, т.к. foreach закончится на последнем элементе
//**********************************************
//error with old years - any data will be accepted as actual - fixed
                        //print_r($flag.' : '.$td->plaintext.'<br>');
                        if(!empty($td->plaintext) && (stripos($td->plaintext,'2014') !== FALSE)) {
                            //@todo: add date $this->flags[date('d.m.Y')][$flag] = 1;
                            $this->flags[$flag] = 1;
                            break;
                        }
                        else {
                            $this->flags[$flag] = 0;
                        }
                    }
                }
            }
            //print_r($this->flags); ///die;
            $html->clear();		//get away from memory leaks
            return 1;
        }
        else
            return 0;
    }

    public function doLog() {

    }
    
    public function save2db(&$db) {
        //@todo: form 2 arrays ( headers and values)
        //mysqli_real_escape_string()
        //@todo: add check on duplicate values and renew values
        if(!$db->query("INSERT INTO `busgov`.`agencies` (`agcid`, `inn`, `agcname`, `mcpzdn`, `bdtsmt`, `fhd`, `clvsrdst`) VALUES ('"
        .$this->agcId."', '".$this->inn."', '".$this->agcName."', '".$this->flags['mcpZdn']
        ."', '".$this->flags['bdtSmt']."', '".$this->flags['fhd']."', '".$this->flags['clvSrdst']."');")) {
            //05.12.13@todo: write to log
            $db->query("INSERT INTO `busgov`.`log` (`id`, `agcid`, `text`, `date`) 
            VALUES ('', '".$this->agcId."', '".$db->error."', '');");
            //echo $db->error;
            //die("\nCan\'t write to db");
        }
        
    }
	
    //http://www.php.net/manual/ru/mysqli.quickstart.dual-interface.php
    public static function get_db($srv,$user,$pass,$database) {
        //'localhost','root','',$database
        $mysqli = new mysqli($srv,$user,$pass,$database);
        if ($mysqli->connect_errno) {
            //echo "Не удалось подключиться к MySQL: " . $mysqli->connect_error;
            return FALSE;
        }
        
        $mysqli->set_charset('utf8');
        /*
        $res = $mysqli->query("SELECT * FROM AGENCIES");
        $row = $res->fetch_assoc();
        foreach($row as $key => $field)
            echo $key." = ".$field."; \n";
         * 
         */
        return $mysqli;    
    }
    
	public static function getFromDb(&$db,$inn) {
            if($res = $db->query("SELECT `agcId` from `agencies` WHERE `inn` = ".trim($inn).";")) {
                if(mysqli_num_rows($res) > 0) {
                    //echo "Agency with inn=".$inn." was already added to DB<br>";
                    return 1;
                }
                else
                    return 0;	//if 0 then we will seek for this agc on bus.gov.ru

            }
            echo $db->error;
            return 1;
	}
	
    public static function makeDb() {
        /*
         * 

         * 
         */
    }
	
	public static function setCookies($page) {
            if($str = self::curl_init($page)) {
                $blocks = explode("\r\n\r\n",$str);
                //we need header - $blocks[0]
                $strs = explode("\r\n",$blocks[0]);
                /*
                        [0] => HTTP/1.1 301 Moved Permanently
                        [1] => Set-Cookie: GMU_FRONT-Insert=R1811833858; path=/; expires=Tue, 10-Dec-2013 16:52:27 GMT
                        [2] => Server: nginx
                        [3] => Date: Tue, 10 Dec 2013 16:47:37 GMT
                        [4] => Content-Type: text/html
                        [5] => Content-Length: 178
                        [6] => Location: http://bus.gov.ru/public/home.html
                        [7] => Connection: keep-alive
                */
                $blocks = null;
                $str = null;
                $tmp = explode(" ",$strs[0]);
                $answ_code = $tmp[1];

                $tmp = explode(": ",$strs[1]);
                //    [0] => Set-Cookie
                //    [1] => GMU_FRONT-Insert=R1811869795; path=/; expires=Tue, 10-Dec-2013 17:14:21 GMT
                $tmp = explode("; ",$tmp[1]);
                self::$cookies[0]['val'] = trim($tmp[0]);

                $tmp = explode(": ",$strs[6]);
                $page = trim($tmp[1]);

                if($str = self::curl_init($page)) {
                    //cookies gmuportal = 1 - is set up in http://bus.gov.ru/public/loadInfoGraphics.html (XHR in Firebug), _ym_visorc = w already exist in browser
                    $blocks = explode("\r\n\r\n",$str);
                    //we need header - $blocks[0]
                    $strs = explode("\r\n",$blocks[0]);
                    /*
                            [0] => HTTP/1.1 200 OK
                            [1] => Set-Cookie: GMU_FRONT-Insert=R1811869795; path=/; expires=Tue, 10-Dec-2013 19:11:29 GMT
                            [2] => Server: nginx
                            [3] => Date: Tue, 10 Dec 2013 18:53:49 GMT
                            [4] => Content-Type: text/html; charset=UTF-8
                            [5] => Transfer-Encoding: chunked
                            [6] => Connection: keep-alive
                            [7] => Vary: Accept-Encoding
                            [8] => Content-Language: ru
                            [9] => Set-Cookie: JSESSIONID=0000VSmuz3waM32PmqkfrVDJSAs:1731lncf6; Path=/
                            [10] => Set-Cookie: districtName=""; Expires=Thu, 01-Dec-94 16:00:00 GMT; Path=/
                            [11] => Set-Cookie: districtId=""; Expires=Thu, 01-Dec-94 16:00:00 GMT; Path=/
                            [12] => Set-Cookie: regionName=""; Expires=Thu, 01-Dec-94 16:00:00 GMT; Path=/
                            [13] => Set-Cookie: regionId=""; Expires=Thu, 01-Dec-94 16:00:00 GMT; Path=/
                            [14] => Expires: Thu, 01 Dec 1994 16:00:00 GMT
                            [15] => Cache-Control: no-cache="set-cookie, set-cookie2"
                            [16] => Content-Encoding: gzip
                    */
                    $blocks = null;
                    $str = null;

                    $tmp = explode(" ",$strs[0]);
                    $answ_code = $tmp[1];
                    if($answ_code <> '200')
                        return 0;

                    foreach($strs as $k => $v) {
                        if(strpos(trim($v),'Set-Cookie: JSESSIONID') === false) {
                            //do nothing if there are no matches
                        }
                        else {
                            $tmp = substr($v,12);
                            $tmp = explode("; ",$tmp);
                            self::$cookies[1]['val'] = trim($tmp[0]);
                        }
                    }
                    self::$cookies[2]['val'] = '_ym_visorc=w';
                    self::$cookies[3]['val'] = 'gmuportal=1';
                }
            }
	}
	
	public static function show(&$db) {
            if($res = $db->query("SELECT * from `agencies`;")) {
                if(mysqli_num_rows($res) > 0) {
                    echo "Agency with inn=".$inn." was already added to DB<br>";
                    return 1;
                }
                else
                    return 0;	//if 0 then we will seek for this agc on bus.gov.ru

            }
            echo $db->error;
            return 1;
	}
        
        /*
         * Show all rows from agencies table
         */
        public static function printAgcs(&$db,$action) {
            if($res = $db->query("SELECT * from `agencies` ;")) {
                $i = 0;
                $html = '<html><head> <meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>';
                $html .= '<body>';
                if(mysqli_num_rows($res) > 0) {
                    $html .= '<table border ="1" id="agc" class="agencies">';
                    $html .= "<tr><td>0. NPP</td><td>1. Bus.gov ID</td><td>2. ИНН</td><td>3. Название</td><td>4. Мун. зад.</td><td>5.  ФХД</td><td>6. Целев. ср-ва</td><td>7. Бюд. смета</td></tr>"; 
                    while($data = mysqli_fetch_array($res)) {
                        $html .= '<tr>';
                        $html .= '<td>' . $i . '</td>';
                        $html .= '<td>' . $data['agcid'] . '</td>';
                        $html .= '<td>' . $data['inn'] . '</td>';
                        $html .= '<td>' . $data['agcname'] . '</td>';
                        $html .= '<td><a href="http://www.bus.gov.ru/public/agency/agency_tasks.html?agency='.$data['agcid'].'">' . $data['mcpzdn'] . '</a></td>';

                        $html .= '<td><a href="http://www.bus.gov.ru/public/agency/agency_plans.html?agency='.$data['agcid'].'">' . $data['fhd'] . '</a></td>';
                        $html .= '<td><a href="http://www.bus.gov.ru/public/agency/operations.html?agency='.$data['agcid'].'">' . $data['clvsrdst'] . '</a></td>';
                        $html .= '<td><a href="http://www.bus.gov.ru/public/agency/budget.html?agency='.$data['agcid'].'">' . $data['bdtsmt'] . '</a></td>';
                        $html .= '</tr>';
                        $i++;
                    }
                    
                    $html .= "</table></body></html>";
                    if($action == 'download') {
                        $path = $_SERVER['DOCUMENT_ROOT']."/";
                        $filename = "file.xls";
                        $fullpath = $path.$filename;
                        $handle = fopen($fullpath, "w");
                        fwrite($handle, $html);
                        fclose($handle);

                        //$link = '<div><a href="'.$_SERVER['REQUEST_URI'].$filename.'">';
                        $link = '<div><a mimetype="application/vnd.ms-excel; charset=UTF-8" href="/'.$filename.'">';
                        $link .= 'Скачать файл</a></div>';
                        $out = array('html' => $link);
                    }
                    else {
                        $out = array('html' => $html);
                    }
                    echo json_encode($out);
                }
                else {
                    $html .= "В БД нет данных.";
                    $out = array('html' => $html);
                    echo json_encode($out);	
                }

            }
            echo $db->error;
            return 1;
	}
        
        /*
         * delete all rows from agencies table
         */
        public static function clearDb(&$db) {
            if($res = $db->query("DELETE from `agencies`;")) {
                $html = ($db->affected_rows > 0) ? "База успешно очищена! Удалено "
                    .$db->affected_rows." записей." : "База пуста.";
                $out = array('html' => $html);
                echo json_encode($out);
            }
         }
//END Agency class	
}