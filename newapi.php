<?php
// this file placed in bitrix directory, not locall, if you
// change something in this file, upload it to bitrix folder api

error_reporting(E_ALL);
ini_set('display_errors', 1);
// header('Access-Control-Allow-Origin: *');
require("../bitrix/modules/main/include/prolog_before.php");
require("./kasko.php");

 CModule::IncludeModule('iblock');

class Api {
    protected static $host = 'https://bitrix.a-group.az';
    // protected static $host = 'kir.az';
    public $result = array();
    public $REQUEST = array();

    public function __construct() {
        $action = $_REQUEST['action'];
        $result = [];

        $this->getREQUEST();

        if(method_exists($this, $action)) {
            return $this->$action($_REQUEST);
        }
    }

    public function getREQUEST() {
        foreach ($_REQUEST as $key => $value) {
            if (!is_array($value)) {
                $this->REQUEST[$key] = trim(htmlspecialchars($value));
            } else {
                $this->REQUEST[$key] = $value;
            }
        }
    }

    public function login() {
        // массив параметров для проверки
        $params = array();
        $params['phone'] = array('CODE' => 'phone'/* , 'IS_INT' => true */);
        $params['password'] = array('CODE' => 'password');

        // проверяем параметры
        $this->checkParams($params);

        $this->REQUEST['phone'] = $this->cleanPhone($this->REQUEST['phone']);
        // выбираем данные пользователя чтобы проверить его логин и пароль
        $rsUser = CUser::GetByLogin($this->REQUEST['phone']);

        if ($arUser = $rsUser->Fetch()) {
            if (strlen($arUser["PASSWORD"]) > 32) {
                $salt = substr($arUser["PASSWORD"], 0, strlen($arUser["PASSWORD"]) - 32);
                $db_password = substr($arUser["PASSWORD"], -32);
            }
            else {
                $salt = "";
                $db_password = $arUser["PASSWORD"];
            }
            $user_password = md5($salt . $this->REQUEST['password']);

            if ($user_password == $db_password) {
                if ($arUser['ACTIVE'] == 'N') {
                    $this->throwError('not_active');
                }

                $result['status'] = 'success';
                $result['user_id'] = $arUser['ID'];

                // added @hlib
                $result['username'] = $arUser['NAME'];
                $result['userlastname'] = $arUser['LAST_NAME'];
                $result['usersecondname'] = $arUser['SECOND_NAME'];
                // added @hlib

                if ($arUser['UF_POLICE'] !== null) {
                    $result['policeNumberMed'] = $arUser['UF_POLICE'];
                }
                if ($arUser['UF_POLICE_AUTO'] !== null) {
                    $result['policeNumberAuto'] = $arUser['UF_POLICE_AUTO'];
                }
                if ($arUser['UF_POLICE_TRAVEL'] !== null) {
                    $result['policeNumberTravel'] = $arUser['UF_POLICE_TRAVEL'];
                }
                if ($arUser['UF_POLICE_PROPERTY'] !== null) {
                    $result['policeNumberProperty'] = $arUser['UF_POLICE_PROPERTY'];
                }

                echo json_encode($result);

            }
            else {
                $result['status'] = 'error';
                $result['message'] = 'login error';

                echo json_encode($result);
            }
        }
        else {
            $result['status'] = 'error';
            $result['message'] = 'user not found error';

            echo json_encode($result);
        }
    }

    public function register() {

        $error = null;
        $params = array();
        $params['phone'] = array('CODE' => 'phone');
        $params['password'] = array('CODE' => 'password');

        $this->checkParams($params);

        $this->REQUEST['phone'] = $this->cleanPhone($this->REQUEST['phone']);

        if ($this->isUserExists($this->REQUEST['phone']) > 0) {
            $this->throwError('user_exist');
        }

        $login = $this->REQUEST['phone'];
        $password = $this->REQUEST['password'];
        $police = $this->REQUEST['police'];
        $code = rand(1000, 9999);


        if (isset($police) && (string) $police !== "") {
            # code...
            ini_set("soap.wsdl_cache_enabled", 0);
            $client = new SoapClient("http://78.109.52.103:81/insureazsvc/ClaimService.asmx?WSDL", array('trace' => 1));
            $client->__setLocation('http://78.109.52.103:81/insureazsvc/ClaimService.asmx');
            // SOAP FindInsuredIDByPolicyNumber //
            $params = array("policyNumber" => (string) $police, 'userName' => 'Insureaz', 'password' => 'simsnet');
            $res = $client->__soapCall('FindInsuredIDByPolicyNumber', array($params));
            $xml = get_object_vars(simplexml_load_string($res->FindInsuredIDByPolicyNumberResult)->Result);

            if ($xml['Failed'] == 0) {
                if ($xml['InsuredID'] > 0) {
                    $userId = $xml['InsuredID'];
                    $username = $xml['InsuredName'];
                    $userlastname = $xml['InsuredSurName'];
                    $usersecondname = $xml['InsuredFatherName'];
                    $policeNum = $police;
                }
            }

            if (!isset($userId)) {
                // SOAP FindInsuredIDByCardNumber //
                $params = array("cardNumber" => (string) $police, 'userName' => 'Insureaz', 'password' => 'simsnet');
                $res = $client->__soapCall('FindInsuredIDByCardNumber', array($params));
                $xml = get_object_vars(simplexml_load_string($res->FindInsuredIDByCardNumberResult)->Result);
                if ($xml['Failed'] == 0) {
                    if ($xml['InsuredID'] > 0) {
                        $userId = $xml['InsuredID'];
                        $policeNum = $police;
                        $username = $xml['InsuredName'];
                        $userlastname = $xml['InsuredSurName'];
                        $usersecondname = $xml['InsuredFatherName'];
                    }
                }
            }
  
        }
  
        // SOAP FindInsuredIDByPolicyNumber //

        $user = new CUser;
        $arFields = Array(
            "LOGIN" => $login,
            "ACTIVE" => "N",
            "GROUP_ID" => array(10, 11),
            "PASSWORD" => $password,
            "CONFIRM_PASSWORD" => $password,
            "UF_VCODE" => $code,
            "NAME" => $username,
            "LAST_NAME" => $userlastname,
            "SECOND_NAME" => $usersecondname
        );
        if (isset($userId) && isset($policeNum)) {

            $arFields['UF_POLICE'] = $policeNum;
            $arFields['UF_USER_ID'] = $userId;


        } else {
            if (isset($police) && $police !== null) {
                # code...
                //$arFields['UF_POLICE'] = $police;
                  $arField['UF_POLICE'] = 'testpolice';
            }

        }
        $ID = $user->Add($arFields);

        if ($ID) {

            $this->sendSMS($login, "Secret code: " . $code);
            $email = "noreply@a-group.az";
            $arEventFields = array(
                "EMAIL" => $email,
                "CODE" => $code
            );
            CEvent::SendImmediate("VERIFY_CODE", "s1", $arEventFields);
            $this->result['status'] = 'success';
            $this->result['user_id'] = $ID;
            if ($police) {
                # code...
                $this->result['policenumber'] = $police;
            }
            if (isset($userId)) {
                $this->result['insuredUserId'] = $userId;
            }
            // if (isset($userId) && isset($policeNum)) {
            // }
            $this->result['phone'] = $this->REQUEST['phone'];
            $this->result['username'] = $username;
            $this->result['userlastname'] = $userlastname;
            $this->result['usersecondname'] = $usersecondname;
            echo json_encode($this->result);
        } else {
            $this->throwError('registration_error');
        }
    }

    public function activateUser() {
        global $USER;

        $phone = $this->cleanPhone($this->REQUEST['phone']);
        $key = $this->REQUEST['key'];
        $user_info = $USER->GetByLogin($phone)->Fetch();

        if ($user_info['UF_VCODE'] === $key) {
            $update_status = $USER->Update($user_info['ID'], array('ACTIVE' => 'Y'));

            $this->result['status'] = 'success';
            $this->result['user_id'] = $user_info['ID'];
        } else {
            $this->result['status'] = 'error_code';
        }

        echo json_encode($this->result);
    }

    protected function isUserExists($phone) {
        global $USER;
        $res = $USER->GetByLogin($phone);
        return $res->SelectedRowsCount();
    }

    protected function sendSMS($number, $text) {
        // $number = $_GET['number'];
        // $text = $_GET['text'];
        $text = self::transliterate($text);
        $text = $text . " | " . date('d.m.Y H:i:s');
        $xml_data = "<?xml version='1.0' encoding='UTF-8'?>
                        <request>
                        <head>
                        <operation>submit</operation>
                        <login>aqroupsms</login>
                        <password>OU#207-521-078#723</password>
                        <title>A-QROUP</title>
                        <isbulk>false</isbulk>
                        <controlid>" . time() . "</controlid>
                        <scheduled>now</scheduled>
                        </head>
                        <body>
                        <msisdn>994$number</msisdn>
                        <message>$text</message>
                        </body>
                        </request>";


        $URL = "https://sms.atatexnologiya.az/bulksms/api";

        $ch = curl_init($URL);
        curl_setopt($ch, CURLOPT_MUTE, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, "$xml_data");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    static function transliterate($st) {
        $st = strtr($st, array(
            'ş' => "sh",
            'ə' => "e",
            'ı' => "i",
            'ğ' => "q",
            'ö' => "o",
            'ü' => "u",
            'ç' => "c",
            'Ş' => "SH",
            'Ə' => "E",
            'I' => "I",
            'İ' => "I",
            'Ğ' => "Q",
            'Ö' => "O",
            'Ü' => "U",
            'Ç' => "C",
        ));
        return $st;
    }

    protected function cleanPhone($phone) {
        $phone = str_replace('-', '', $phone);
        $phone = str_replace('_', '', $phone);
        $phone = str_replace('(', '', $phone);
        $phone = str_replace(')', '', $phone);
        $phone = str_replace('.', '', $phone);
        $phone = str_replace(' ', '', $phone);
        $phone = str_replace('+994', '', $phone);
        return $phone;
    }

    // проверка массива нужных параметров
    public function checkParams($params = array()) {
        foreach ($params as $param) {
            if (!$this->checkEmpty($_REQUEST[$param['CODE']], $param['IS_INT'])) {
                $this->throwError('Params error, An error occured while the objects were fetching ');
            }
        }
    }

    // проверка параметра на пустоту или цифру
    public function checkEmpty($var, $int = false) {
        if ($int == false) {
            if (!isset($var) || trim($var) == '') {
                return false;
            }
        } else {
            if (!isset($var) || ctype_digit($var) != 1) {
                return false;
            }
        }
        return true;
    }

//----------------------------------------------------------------------------------------------------------------------------------
    // страница: НАЙТИ ВРАЧА; Заполнение комбобокса с типа врачей  
    public function getDoctorsTypes() {
        
        $res = CIBlockPropertyEnum::GetList(
            $arOrder = array('VALUE' => 'ASC'), $arFilter = array('IBLOCK_ID' => 6, 'CODE' => 'CATEGORIES')
        );
        while ($ef = $res->GetNext()) {
            $CATEGORIES[] = array('NAME' => text($ef["VALUE"]), 'ID' => $ef["ID"]);
        }

        $data['status'] = 'success';
        $data['category_list'] = $CATEGORIES;

        echo json_encode($data);
    }
//----------------------------------------------------------------------------------------------------------------------------------

    // страница: НАЙТИ ВРАЧА; Остальная информация о врачах
    public function getDoctors() {
        cmodule::IncludeModule('iblock');

        $params['doctor'] = array('CODE' => 'ID');
        $params['types'] = array('CODE' => 'TYPE');
        $id = $this->REQUEST['doctor'];
        $userID = $this->REQUEST['userID'];
        $types = $this->REQUEST['types'];
        $lang = $this->REQUEST['lang'];

        switch ($lang) {
            case 'az':
                $lang_id = 35;
                break;
            case 'ru':
                $lang_id = 36;
                break;
            case 'en':
                $lang_id = 37;
                break;

            default:
                $lang_id = 35;
                break;
        }
        // d($types, "TYPES");
        $category_filter = explode(',', $types);
        // d($category_filter, "CAT");
        //d($id, 'id');
        $arSelect = Array("ID", "IBLOCK_ID", 'PREVIEW_TEXT', "NAME", 'PROPERTY_SURNAME', 'PROPERTY_CLINIC', 'PROPERTY_CLINIC.NAME', 'PROPERTY_CLINIC.PROPERTY_ADDRESS', 'PROPERTY_MOBILE', 'PROPERTY_SCHEDULE', 'PROPERTY_WEBSITE', 'DETAIL_PICTURE', "PROPERTY_CATEGORIES", "PROPERTY_EXPERIENCE", "PROPERTY_EDUCATION", "PROPERTY_TRAINING", "PROPERTY_UNIQUE_ID");
        $arFilter = Array("IBLOCK_ID" => 6, "ACTIVE_DATE" => "Y", "ACTIVE" => "Y", "PROPERTY_CATEGORIES" => $category_filter, "PROPERTY_LANG" => $lang_id);
        $res = CIBlockElement::GetList(Array("NAME" => "ASC"), $arFilter, false, false, $arSelect);
        if ($res) {
            $doctors = array();
            $clinic_id = array();
            $letters = array('A', 'B', 'C', 'D', 'H', 'W', 'K', 'I', 'M', 'N');
            while ($ob = $res->Fetch()) {
                $doctors['ID'] = $ob['ID'];
                $doctors['NAME'] = $ob['NAME'];
                $doctors['CLINIC_TITLE'] = $ob['PROPERTY_CLINIC_VALUE'];
                $doctors['CLINIC_ADDRESS'] = $ob['PROPERTY_CLINIC_PROPERTY_ADDRESS_VALUE'];
                $doctors['NUMBER'] = $ob['PROPERTY_MOBILE_VALUE'];
                $doctors['RATING'] = $this->getDoctorRating($ob['ID']);
                $doctors['USER_RATE'] = $this->getUserDoctorRating($ob['ID'], $userID);
                $doctors['SCHEDULE'] = $ob['PROPERTY_SCHEDULE_VALUE'];
                $doctors['TYPE'] = $ob['PROPERTY_CATEGORIES_VALUE'];
                $doctors['WEBSITE'] = $ob['PROPERTY_WEBSITE_VALUE'];
                $doctors['TRAINING'] = $ob['PROPERTY_TRAINING_VALUE'];
                $doctors['EXPERIENCE'] = $ob['PROPERTY_EXPERIENCE_VALUE'];
                $doctors['EDUCATION'] = $ob['PROPERTY_EDUCATION_VALUE'];
                $doctors['UNIQUE_ID'] = $ob['PROPERTY_UNIQUE_ID_VALUE'];
                $doctors['NOTE'] = $ob['PREVIEW_TEXT'];
                $doctors['PICTURE'] = self::$host . CFile::GetPath($ob['DETAIL_PICTURE']);

                $dc[] = $doctors;
            }
            ksort($dc);


            // $this->result['status'] = 'success';
            // $this->result['doctors_list'] = $dc;

            $data['status'] = 'success';
            $data['doctors_list'] = $dc;
    
            echo json_encode($data);
        } else {
            $this->throwError('An error occured while the objects were fetching ');
        }
    }

//----------------------------------------------------------------------------------------------------------------------------------

    public function getDoctorById() {
        cmodule::IncludeModule('iblock');

        $params['doctor'] = array('CODE' => 'ID');
        $params['types'] = array('CODE' => 'TYPE');
        $id = $this->REQUEST['doctor'];
        $userID = $this->REQUEST['userID'];
        $types = $this->REQUEST['types'];
        $lang = $this->REQUEST['lang'];
        switch ($lang) {
            case 'az':
                $lang_id = 35;
                break;
            case 'ru':
                $lang_id = 36;
                break;
            case 'en':
                $lang_id = 37;
                break;

            default:
                $lang_id = 35;
                break;
        }
        // d($types, "TYPES");
        $category_filter = explode(',', $types);
        // d($category_filter, "CAT");
        //d($id, 'id');
        $arSelect = Array("ID", "IBLOCK_ID", 'PREVIEW_TEXT', "NAME", 'PROPERTY_SURNAME', 'PROPERTY_CLINIC', 'PROPERTY_CLINIC.NAME', 'PROPERTY_CLINIC.PROPERTY_ADDRESS', 'PROPERTY_MOBILE', 'PROPERTY_SCHEDULE', 'PROPERTY_WEBSITE', 'DETAIL_PICTURE', "PROPERTY_CATEGORIES", "PROPERTY_EXPERIENCE", "PROPERTY_EDUCATION", "PROPERTY_TRAINING", "PROPERTY_UNIQUE_ID");
        $arFilter = Array("IBLOCK_ID" => 6, "ACTIVE_DATE" => "Y", "ACTIVE" => "Y", "ID" => $id, "PROPERTY_LANG" => $lang_id);
        $res = CIBlockElement::GetList(Array("NAME" => "ASC"), $arFilter, false, false, $arSelect);
        if ($res) {
            $doctors = array();
            $clinic_id = array();
            $letters = array('A', 'B', 'C', 'D', 'H', 'W', 'K', 'I', 'M', 'N');
            while ($ob = $res->Fetch()) {
                $doctors['ID'] = $ob['ID'];
                $doctors['NAME'] = $ob['NAME'];
                $doctors['CLINIC_TITLE'] = $ob['PROPERTY_CLINIC_VALUE'];
                $doctors['CLINIC_ADDRESS'] = $ob['PROPERTY_CLINIC_PROPERTY_ADDRESS_VALUE'];
                $doctors['NUMBER'] = $ob['PROPERTY_MOBILE_VALUE'];
                $doctors['RATING'] = $this->getDoctorRating($ob['ID']);
                $doctors['USER_RATE'] = $this->getUserDoctorRating($ob['ID'], $userID);
                $doctors['SCHEDULE'] = $ob['PROPERTY_SCHEDULE_VALUE'];
                $doctors['TYPE'] = $ob['PROPERTY_CATEGORIES_VALUE'];
                $doctors['WEBSITE'] = $ob['PROPERTY_WEBSITE_VALUE'];
                $doctors['TRAINING'] = $ob['PROPERTY_TRAINING_VALUE'];
                $doctors['EXPERIENCE'] = $ob['PROPERTY_EXPERIENCE_VALUE'];
                $doctors['EDUCATION'] = $ob['PROPERTY_EDUCATION_VALUE'];
                $doctors['UNIQUE_ID'] = $ob['PROPERTY_UNIQUE_ID_VALUE'];
                $doctors['NOTE'] = $ob['PREVIEW_TEXT'];
                $doctors['PICTURE'] = self::$host . CFile::GetPath($ob['DETAIL_PICTURE']);
            }

            $data['status'] = 'success';
            $data['info'] = $doctors;
    
            echo json_encode($data);

        } else {
            $this->throwError('An error occured while the objects were fetching ');
        }
    }

//----------------------------------------------------------------------------------------------------------------------------------
    public function getDoctorRating($doctorID) {
        $arSelect = Array("ID", "PROPERTY_RATING", "PROPERTY_DOCTOR");
        $arFilter = Array("IBLOCK_ID" => 18, "PROPERTY_DOCTOR" => $doctorID);
        $total_count = 0;
        $res = CIBlockElement::GetList(Array("NAME" => "ASC"), $arFilter, false, false, $arSelect);
        while ($row = $res->Fetch()) {
            $total_count = $total_count + $row['PROPERTY_RATING_VALUE'];
        }

        $rating = round($total_count / $res->SelectedRowsCount(), 1);
        return $rating;
    }

//----------------------------------------------------------------------------------------------------------------------------------
    public function getUserDoctorRating($doctorID, $userID) {
        $arSelect = Array("PROPERTY_RATING");
        $arFilter = Array("IBLOCK_ID" => 18, "PROPERTY_DOCTOR" => $doctorID, "PROPERTY_USER" => $userID);
        $total_count = 0;
        $res = CIBlockElement::GetList(Array("NAME" => "ASC"), $arFilter, false, false, $arSelect)->Fetch();
        return $res['PROPERTY_RATING_VALUE'];
    }

//----------------------------------------------------------------------------------------------------------------------------------
    public function getObjects() {
        // $params['page'] = array('CODE' => 'page');
        // $params['limit'] = array('CODE' => 'limit');
        // $this->checkParams($params);
        $params['types'] = array('CODE' => 'TYPE');
        $types = $this->REQUEST['types'];
        $category_filter = explode(',', $types);
        // $page=$this->REQUEST['page'];

        $curlatitude = $this->REQUEST['latitude'];
        $curlongitude = $this->REQUEST['longitude'];

        $arSelect = Array("ID", "NAME", "PREVIEW_PICTURE", "PROPERTY_LAT", 'PROPERTY_LNG', 'PROPERTY_ADDRESS', 'PREVIEW_TEXT', "PROPERTY_SCHEDULE", "PROPERTY_WEBSITE", "PROPERTY_NUMBER", "PROPERTY_MOBILE", "PROPERTY_CATEGORIES", "PROPERTY_CATEGORIES.XML_ID");
        $arFilter = Array("IBLOCK_ID" => 5, "ACTIVE_DATE" => "Y", "ACTIVE" => "Y", "PROPERTY_CATEGORIES" => $category_filter);
        $res = CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);
        $clinic = Array();
        if ($res) {
            while ($object = $res->Fetch()) {
                $clinic[$object['ID']]['ID'] = $object['ID'];
                $clinic[$object['ID']]['NAME'] = $object['NAME'];
                $clinic[$object['ID']]['LAT'] = $object['PROPERTY_LAT_VALUE'];
                $clinic[$object['ID']]['LNG'] = $object['PROPERTY_LNG_VALUE'];
                $clinic[$object['ID']]['ADDRESS'] = $object['PROPERTY_ADDRESS_VALUE'];
                $clinic[$object['ID']]['PREVIEW_TEXT'] = $object['PREVIEW_TEXT'];
                $clinic[$object['ID']]['SCHEDULE'] = $object['PROPERTY_SCHEDULE_VALUE'];
                $clinic[$object['ID']]['WEBSITE'] = $object['PROPERTY_WEBSITE_VALUE'];
                // $clinic[$object['ID']]['RATING'] = $this->getClinicRating($object['ID']);
                // $clinic[$object['ID']]['USER_RATE'] = $this->getUserClinicRating($object['ID'], $userID);
                $clinic[$object['ID']]['NUMBER'] = $object['PROPERTY_NUMBER_VALUE'];
                $clinic[$object['ID']]['MOBILE'] = $object['PROPERTY_MOBILE_VALUE'];
                $clinic[$object['ID']]['TYPE'] = $object['PROPERTY_CATEGORIES_VALUE'];
                $clinic[$object['ID']]['TYPE_CODE'] = $object['PROPERTY_CATEGORIES_ENUM_ID'];
                $clinic[$object['ID']]['PICTURE'] = $this->host . CFile::GetPath($object['PREVIEW_PICTURE']);
                // $clinic[$object['ID']]['DISTANCE'] = round(self::getDistance($curlatitude, $curlongitude, $object['PROPERTY_LAT_VALUE'], $object['PROPERTY_LNG_VALUE'], 'K'), 2);
            }


            //получаем порядок сортировки от пользователя
            $sorttype = $this->REQUEST['idname'];


            switch ($sorttype) :
                case 'sortname': //по названию
                    usort($clinic, function($a, $b) {
                        if ($a['NAME'] == $b['NAME']) {
                            return 0;
                        }
                        return ($a['NAME'] < $b['NAME']) ? -1 : 1;
                    });
                    break;
                case 'sortrate': //по рейтингу
                    usort($clinic, function($a, $b) {
                        if ($a['RATING'] == $b['RATING']) {
                            return 0;
                        }
                        return ($a['RATING'] < $b['RATING']) ? 1 : -1;
                    });
                    break;
                case 'sortdist': //по расстаянию
                    usort($clinic, function($a, $b) {
                        if ($a['DISTANCE'] == $b['DISTANCE']) {
                            return 0;
                        }
                        return ($a['DISTANCE'] < $b['DISTANCE']) ? -1 : 1;
                    });
                    break;

                default: //по умолчанию
                    usort($clinic, function($a, $b) {
                        if ($a['DISTANCE'] == $b['DISTANCE']) {
                            return 0;
                        }
                        return ($a['DISTANCE'] < $b['DISTANCE']) ? -1 : 1;
                    });
                    break;

            endswitch;

            $result['status'] = 'success';
            $result['list'] = $clinic;

            echo json_encode($result);
        } else {
            $this->throwError('An error occured while the objects were fetching ');
        }
    }

//----------------------------------------------------------------------------------------------------------------------------------
    public function getDrugs() {
        include('../libs/phpQuery-onefile.php');

        $medicine = $this->REQUEST['medicine'];

        $data = array();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://pharma.az/index.php?lang=1&ind=medicals_search');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "name=$medicine");
        $out = curl_exec($ch);

        $doc = phpQuery::newDocument($out);
        $drugsTable = $doc->find("th:eq(4)")->remove();
        $drugsTable = $doc->find("td:eq(4)")->remove();

        // $num = 4;
        // foreach ($doc->find('tr') as $data) {
        //     $num = $num + 5;
        //     $drugsTable = $doc->find("td:eq($num)")->remove();
        // }

        $drugsTable = $doc->find("table")->html();
        $result['status'] = 'success';
        $result['data'] = $drugsTable;

        echo json_encode($result);
    }

//----------------------------------------------------------------------------------------------------------------------------------
    public function getDrugsInfo() {

        function mb_ucfirst($text) {
            return mb_strtoupper(mb_substr($text, 0, 1)) . mb_substr($text, 1);
        }

        $search = mb_ucfirst($this->REQUEST['medicine']);
        //$cure = $this->REQUEST['cure'];
        include('../libs/phpQuery-onefile.php');
        if ($search == null) {
            $this->result['status'] = 'error';
        } else {

            $data = array();
            //include('../libs/phpQuery-onefile.php');
            $url = "http://www.piluli.kharkov.ua/search/?search=" . urlencode($search);
            // var_dump($url);
            // $content = mb_convert_encoding(trim(file_get_contents("http://www.piluli.kharkov.ua/search/?search=$search&f[0]=type%3Adrug")), "UTF-8");
            //$content = @iconv('UTF-8', '', file_get_contents("http://www.piluli.kharkov.ua/search/?search=$search&f[0]=type%3Adrug"));
            // $content = mb_convert_encoding('utf-8', file_get_contents("http://www.piluli.kharkov.ua/search/?search=$search&f[0]=type%3Adrug"));
            // print($content);
            $content = file_get_contents($url);


            //$content = iconv('cp1251','UTF-8', $content);
            //print "<textarea>";print($content);print "</textarea>";
            // echo $content;
            $doc = phpQuery::newDocumentHTML($content, 'utf8');
            // $drugs = $doc->find("div#publication")->html();

            $cure = $doc->find("div.view-content .field-content")->eq(0);
            $content_url = $cure->find('a')->attr('href');


            $content = file_get_contents($content_url);

            $doc = phpQuery::newDocumentHTML($content, 'utf8');

            $cure = $doc->find("#farm-deistvie'")->remove();
            $cure = $doc->find("div.content div.field-name-field-farm-deistvie");


            $style = 'style="text-align:left;padding:0 15px;font-size:1em;border:1px solid #ddd;"';

            $cure = "<div $style>"
            . $doc->find("div.content div.field-name-field-farm-deistvie")->html()
            . "</div>";


            if(!empty($cure)) {
                $this->result['status'] = 'success';
                $this->result['data'] = $cure;
            } else {
                $this->result['status'] = 'error';
            }
            
        }
    }
//----------------------------------------------------------------------------------------------------------------------------------

// calculate and send insurance


public function calcIns() {
    // global $USER;

    $PROPERTIES = $this->REQUEST['USER'][1]['PROPERTIES'];
    $FIELDS = $this->REQUEST['FIELDS'];

    // $userID = $this->REQUEST['userID'];
    // $signature = $this->REQUEST['signature'];
    // $params['userID'] = array('CODE' => 'userID', "IS_INT" => true);
    // $params['signature'] = array('CODE' => 'signature');
    // $this->checkParams($params);
    // $this->checkSignature($userID);
    $el = new CIBlockElement;

    if(!isset($PROPERTIES['FIO']) || $PROPERTIES['FIO'] == 'null' || empty($PROPERTIES['FIO'])) {
        $name = 'Медицинский полис (Ozel - Cайт) ' . date('Y-m-d h:i');
    } else {
        $name = $PROPERTIES['FIO'] . ' (Ozel - Сайт)';
    }

    $PROP = array();
    $arLoadProductArray = Array(
        // "MODIFIED_BY" => $USER->GetID(), // элемент изменен текущим пользователем
        "IBLOCK_SECTION_ID" => false, // элемент лежит в корне раздела
        "IBLOCK_ID" => 10,
        "NAME" => $name,
        "ACTIVE" => "Y", // активен
    );

    foreach ($PROPERTIES as $code => $value) {
        $arLoadProductArray['PROPERTY_VALUES'][$code] = trim(htmlspecialchars($value));
    }
    foreach ($FIELDS as $code => $value) {
        $arLoadProductArray[$code] = trim(htmlspecialchars($value));
    }

    // $arLoadProductArray['PROPERTY_VALUES']['USER'] = $userID;

    if($this->REQUEST['insurance_status'] == 'order') {
        $PRODUCT_ID = $el->Add($arLoadProductArray);
    }

    $PROPERTIES['PREVIEW_TEXT'] = $FIELDS['PREVIEW_TEXT'];
    $PROPERTIES['INSURANCE_STATUS'] = $this->REQUEST['insurance_status'];
    $PROPERTIES['INSURANCE_TOTAL_SUM'] = $this->REQUEST['insurance_total_sum'];

    $this->result['status'] = 'success';

    if($this->result['status'] == 'success') {
        $this->medInsuranceMail($PROPERTIES);

        $data['status'] = 1;
        $data['message'] = 'success';
    
        echo json_encode($data);
    }
}

public function calcInsFamily() {
	// global $USER;
	// $userID = $this->REQUEST['userID'];
	// $signature = $this->REQUEST['signature'];
	// $params['userID'] = array('CODE' => 'userID', "IS_INT" => true);
	// $params['signature'] = array('CODE' => 'signature');
	// $this->checkParams($params);
	// $this->checkSignature($userID);

	$el = new CIBlockElement;

    // данные пользователя в запросе
	$user = $this->REQUEST['USER'][1];
    $PROPERTIES = $user['PROPERTIES'];
    $FIELDS = $user['FIELDS'];

	// общие данные, храним в первом пользователя получающим страховку
	$info = [];
	$info['INSURANCE_STATUS'] = $this->REQUEST['insurance_status'];
	$info['INSURANCE_TOTAL_SUM'] = $this->REQUEST['insurance_total_sum'];

    if(!isset($PROPERTIES['FIO']) || $PROPERTIES['FIO'] == 'null' || empty($PROPERTIES['FIO'])) {
        $name = 'Медицинский полис (Aile - Сайт) ' . date('Y-m-d h:i');
    } else {
        $name = $PROPERTIES['FIO'] . ' (Aile - Сайт)';
    }


    $PROP = array();
    $arLoadProductArray = Array(
        // "MODIFIED_BY" => $USER->GetID(), // элемент изменен текущим пользователем
        "IBLOCK_SECTION_ID" => false, // элемент лежит в корне раздела
        "IBLOCK_ID" => 28,
        "NAME" => $name,
        "ACTIVE" => "Y", // активен
    );


    foreach ($PROPERTIES as $code => $value) {
        $arLoadProductArray['PROPERTY_VALUES'][$code] = trim(htmlspecialchars($value));
    }
    foreach ($FIELDS as $code => $value) {
        $arLoadProductArray[$code] = trim(htmlspecialchars($value));
    }

    $arLoadProductArray['PROPERTY_VALUES']['USER'] = $userID;


    if($info['INSURANCE_STATUS'] == 'order') {
        $PRODUCT_ID = $el->Add($arLoadProductArray);
    }

    $this->result['status'] = 'success';

	if($this->result['status'] == 'success') {

        $this->medInsuranceMailFamily($user, $info);
        
        $data['status'] = 1;
        $data['message'] = 'success';
    
        echo json_encode($data);
	}

}

public function calcInsFerqli() {
    // global $USER;

    $PROPERTIES = $this->REQUEST['USER'][1]['PROPERTIES'];
    $FIELDS = $this->REQUEST['FIELDS'];

    // $userID = $this->REQUEST['userID'];
    // $signature = $this->REQUEST['signature'];
    // $params['userID'] = array('CODE' => 'userID', "IS_INT" => true);
    // $params['signature'] = array('CODE' => 'signature');
    // $this->checkParams($params);
    // $this->checkSignature($userID);
    $el = new CIBlockElement;

    if(!isset($PROPERTIES['FIO']) || $PROPERTIES['FIO'] == 'null' || empty($PROPERTIES['FIO'])) {
        $name = 'Медицинский полис (Ferqli - Cайт) ' . date('Y-m-d h:i');
    } else {
        $name = $PROPERTIES['FIO'] . ' (Ferqli - Сайт)';
    }

    $PROP = array();
    $arLoadProductArray = Array(
        // "MODIFIED_BY" => $USER->GetID(), // элемент изменен текущим пользователем
        "IBLOCK_SECTION_ID" => false, // элемент лежит в корне раздела
        "IBLOCK_ID" => 29,
        "NAME" => $name,
        "ACTIVE" => "Y", // активен
    );

    foreach ($PROPERTIES as $code => $value) {

        $arLoadProductArray['PROPERTY_VALUES'][$code] = trim(htmlspecialchars($value));
    }
    foreach ($FIELDS as $code => $value) {
        $arLoadProductArray[$code] = trim(htmlspecialchars($value));
    }

    // $arLoadProductArray['PROPERTY_VALUES']['USER'] = $userID;

    if($this->REQUEST['insurance_status'] == 'order') {
        $PRODUCT_ID = $el->Add($arLoadProductArray);
    }

    $PROPERTIES['PREVIEW_TEXT'] = $FIELDS['PREVIEW_TEXT'];
    $PROPERTIES['INSURANCE_STATUS'] = $this->REQUEST['insurance_status'];
    $PROPERTIES['INSURANCE_TOTAL_SUM'] = $this->REQUEST['insurance_total_sum'];

    $this->result['status'] = 'success';

    if($this->result['status'] == 'success') {
        $this->medInsuranceMailFerqli($PROPERTIES);

        $data['status'] = 1;
        $data['message'] = 'success';
    
        echo json_encode($data);
    }
}

public function getKaskoRepairShopName() {
    $items = ApiKasko::getRepairShops();
    foreach($items as $item) {
        if($item->ID == $this->REQUEST['repairShop']) {
            return $item->Name;
        } 
    }

    return null;
}

public function getKaskoBrandName() {
    $items = ApiKasko::getBrandList();
    foreach($items as $item) {
        if($item->ID == $this->REQUEST['brand']) {
            return $item->Name;
        } 
    }

    return null;
}

public function getKaskoDriverName() {
    $items = ApiKasko::getDrivers();
    foreach($items as $item) {
        if($item->ID == $this->REQUEST['driver']) {
            return $item->Name;
        } 
    }

    return null;
}

public function getKaskoBonusName() {
    $items = ApiKasko::getBonuses();
    foreach($items as $item) {
        if($item->ID == $this->REQUEST['bonus']) {
            return $item->Name;
        } 
    }

    return null;
}

public function calcKasko() {
    $calculate = ApiKasko::calculate([
        'brand' => $this->REQUEST['brand'],
        'year' => $this->REQUEST['year'],
        'price' => $this->REQUEST['price'],
        'repairShop' => $this->REQUEST['repairShop'],
        'franchise' => $this->REQUEST['franchise'],
        'driver' => $this->REQUEST['driver'],
        'bonus' => $this->REQUEST['bonus'],
    ]);

    $hasException = (string) $calculate->HasException;
    if($hasException == 0) {
        $data = [
            'type' => $this->REQUEST['type'],
            'year' => $this->REQUEST['year'],
            'price' => $this->REQUEST['price'],
            'franchise' => $this->REQUEST['franchise'],
            'brandName'      => $this->getKaskoBrandName(),
            'repairShopName' => $this->getKaskoRepairShopName(),
            'driverName'     => $this->getKaskoDriverName(),
            'bonusName'      => $this->getKaskoBonusName(),
            'status' => 1,
            'message' => 'success',
            'premium' => (string) $calculate->BruttoPremium
        ];

        $this->kaskoMail($data);
    } else {
        $data['status'] = 0;
        $data['message'] = 'error';
        $data['exception'] = (string) $calculate->ExceptionMsg;
    }

    echo json_encode($data);
}

public function kaskoMail($data) {
    $to = 'anacafov@a-group.az, insurance@a-group.az, nqasimova@a-group.az';
    // $to = 'halilov.lib@gmail.com';
    $from = 'no-reply@a-group.az';

    // To send HTML mail, the Content-type header must be set
    $headers  = 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";

    // Create email headers
    $headers .= 'From: '.$from."\r\n".
    'Reply-To: '.$from."\r\n" .
    'X-Mailer: PHP/' . phpversion();

    // Compose a simple HTML email message
    $message = '<!doctype html><html><head><meta charset="UTF-8"></head><body>';

    // Subject
    if($data['type'] == 'calculate') {
        $subject = 'Расчет каско страхования (через сайт)';
        $message .= '<h1 style="color:black !important;">Пользователь расчитал страховку (каско) через сайт</h1>';
    } else {
        $subject = 'Покупка каско страхования (через сайт)';
        $message .= '<h1 style="color:black !important;">Пользователь хочет купить страховку (каско) через сайт</h1>';
    }

    $message .= '<h3 style="color:black !important;"> ФИО: ' . 'Данные клиента' . '</h3>';
    $message .= '<h4 style="color:black !important;"> ФИО: ' . $this->REQUEST['fio'] . '</h4>';
    $message .= '<h4 style="color:black !important;"> Номер: ' . $this->REQUEST['phone'] . '</h4>';
    $message .= '<h4 style="color:black !important;"> E-mail: ' . $this->REQUEST['email'] . '</h4>';

    $message .= '<h3 style="color:black !important;"> ФИО: ' . 'Данные авто' . '</h3>';
    $message .= '<h4 style="color:black !important;"> Год выпуска: ' . $data['year'] . '</h4>';
    $message .= '<h4 style="color:black !important;"> Цена: ' . $data['price'] . '</h4>';
    $message .= '<h4 style="color:black !important;"> Франшиза: ' . $data['franchise'] . '</h4>';
    $message .= '<h4 style="color:black !important;"> Брэнд: ' . $data['brandName'] . '</h4>';
    $message .= '<h4 style="color:black !important;"> Тип обслуживания: ' . $data['repairShopName'] . '</h4>';
    $message .= '<h4 style="color:black !important;"> Тип водителя: ' . $data['driverName'] . '</h4>';
    $message .= '<h4 style="color:black !important;"> Тип бонуса: ' . $data['bonusName'] . '</h4>';

    $message .= '<p style="color:black !important;"> Сумма страхования: ' . $data['premium'] . ' AZN</p>';
    $message .= '</body></html>';

    // Sending email
    mail($to, $subject, $message, $headers);
}


public function complaintsForm() {
    $name = $this->REQUEST['name'];
    $surname = $this->REQUEST['surname'];
    $lastname = $this->REQUEST['lastname'];
    $phone = $this->REQUEST['phone'];
    $email = $this->REQUEST['email'];
    $content = $this->REQUEST['content'];

    // $to = 'akhalilov@beylitech.az';
    $to = 'complaints@a-group.az, dakershteyn@a-group.az';
    $from = 'form-mail@a-group.az';

    // To send HTML mail, the Content-type header must be set
    $headers  = 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";

    // Create email headers
    $headers .= 'From: '.$from."\r\n".
    'Reply-To: '.$from."\r\n" .
    'X-Mailer: PHP/' . phpversion();

    // Subject
    $subject = 'Жалоба (через сайт)';

    $fio = $name . " " . $surname . " " . $lastname;

    // Compose a simple HTML email message
    $message = '<!doctype html><html><head><meta charset="UTF-8"></head><body>';
    $message .= '<h1 style="color:black !important;">Пользователь отправил жалобу через сайт</h1>';
    $message .= '<h4 style="color:black !important;"> ФИО: ' . $fio . '</h4>';
    $message .= '<h4 style="color:black !important;"> Номер: ' . $phone . '</h4>';
    $message .= '<h4 style="color:black !important;"> E-mail: ' . $email . '</h4>';
    $message .= '<p style="color:black !important;"> Сообщение: ' . $content . '</p>';
    $message .= '</body></html>';

    // Sending email
    mail($to, $subject, $message, $headers);

    $data['status'] = 1;
    $data['message'] = 'success';

    echo json_encode($data);
}

public function contactForm() {
    $fullname = $this->REQUEST['fullname'];
    $phone = $this->REQUEST['phone'];
    $content = $this->REQUEST['content'];

    // $to = 'halilov.lib@gmail.com';
   // $to = 'amiskarli@a-group.az, insurance@a-group.az';
   $to ='dakershteyn@a-group.az';
    $from = 'contact@a-group.az';

    // To send HTML mail, the Content-type header must be set
    $headers  = 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";

    // Create email headers
    $headers .= 'From: '.$from."\r\n".
    'Reply-To: '.$from."\r\n" .
    'X-Mailer: PHP/' . phpversion();

    // Subject
    $subject = 'Пользователь захотел связаться с ним (через сайт)';

    // Compose a simple HTML email message
    $message = '<!doctype html><html><head><meta charset="UTF-8"></head><body>';
    $message .= '<h1 style="color:black !important;">Пользователь запросил связаться с ним через сайт</h1>';
    $message .= '<h4 style="color:black !important;"> ФИО: ' . $fullname . '</h4>';
    $message .= '<h4 style="color:black !important;"> Номер: ' . $phone . '</h4>';
    $message .= '<p style="color:black !important;"> Сообщение: ' . $content . '</p>';
    $message .= '</body></html>';

    // Sending email
    mail($to, $subject, $message, $headers);

    $data['status'] = 1;
    $data['message'] = 'success';

    echo json_encode($data);
}

public function medInsuranceMail($data) {
    // $to = 'halilov.lib@gmail.com';
    $to = 'nqasimova@a-group.az, insurance@a-group.az, anacafov@a-group.az';
    $from = 'no-reply@a-group.az';

    // To send HTML mail, the Content-type header must be set
    $headers  = 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";

    // Create email headers
    $headers .= 'From: '.$from."\r\n".
    'Reply-To: '.$from."\r\n" .
    'X-Mailer: PHP/' . phpversion();

    // Compose a simple HTML email message
    $message = '<!doctype html><html><head><meta charset="UTF-8"></head><body>';


    if($data['INSURANCE_STATUS'] == 'calculate') {
        $subject = 'Расчет мед страховки (через сайт) ÖZƏL';
        $message .= '<h1 style="color:black !important;">Пользователь расчитал цену на мед страховку</h1>';
    } elseif($data['INSURANCE_STATUS'] == 'order') {
        $subject = 'Заказ мед страхования (через сайт) ÖZƏL';
        $message .= '<h1 style="color:green !important;">Пользователь заказал мед страховку</h1>';
    }

    $message .= '<h4 style="color:black !important;"> ФИО: ' . $data['FIO'] . '</h4>';
    $message .= '<h4 style="color:black !important;"> Дата рождения: ' . $data['BIRTHDAY'] . '</h4>';
    $message .= '<h4 style="color:black !important;"> Вес: ' . $data['WEIGHT'] . '</h4>';
    $message .= '<h4 style="color:black !important;"> Рост: ' . $data['HEIGHT'] . '</h4>';
    $message .= '<h4 style="color:black !important;"> Номер: ' . $data['PHONE'] . '</h4>';
    $message .= '<h4 style="color:black !important;"> Адрес: ' . $data['ADDRESS'] . '</h4>';
    $message .= '<h4 style="color:black !important;"> Место работы: ' . $data['WORKPLACE'] . '</h4>';
    $message .= '<h4 style="color:green !important;"> Цена страховки: ' . $data['INSURANCE_TOTAL_SUM'] . ' AZN</h4>';


    $message .= '<table style="border:1px solid lightgray; color:black !important; width:100%;">';

    $message .= '<tr>';
    $message .= '<th style="border:1px solid lightgray; padding:5px;">Вопрос</th>';
    $message .= '<th style="border:1px solid lightgray; padding:5px;">Ответ</th>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Обследовались ли вы в последние 2 года?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $data['Q1'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Госпитализировались ли вы когда-нибудь?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $data['Q2'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Принимаете ли Вы сейчас какое-либо лечение или лекарство?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $data['Q3'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Заболевания пищевода/желудка/кишечника?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $data['Q4'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Астма/аллергия/легочные заболевания?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $data['Q5'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Заболевания почек/ мочевого тракта?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $data['Q6'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Врожденные и наследственные заболевания?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $data['Q7'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Головная боль/ мигрень/ головокружения?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $data['Q8'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Повышение артериального давления (выше 140/90)?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $data['Q9'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Ревматизм/заболевания мышц, суставов, костей?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $data['Q10'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Варикозное расширение вен и другие заболевания сосудов?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $data['Q11'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Нарушения ритма, проводимости, болезни сердца?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $data['Q12'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Психические заболевания/нервные расстройства, эпилепсия?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $data['Q13'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Травмы, повреждения/дефекты, их последствия?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $data['Q14'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Проблемы со спиной или с позвоночником?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $data['Q15'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Болезни печени/селезёнки/поджелудочной железы?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $data['Q16'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Заболевания крови?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $data['Q17'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Сахарный диабет ?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $data['Q18'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Другие Эндокринные Заболевания?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $data['Q19'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Опухоли: доброкачественные/злокачественные?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $data['Q20'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Имеются ли у Вас нарушения здоровья, не указанные выше?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $data['Q21'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Являетесь ли Вы ныне или были ли когда-либо застрахованным в другой страховой компании?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $data['Q22'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Дополнительная информация</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $data['PREVIEW_TEXT'] . '</td>';
    $message .= '</tr>';

    $message .= '</table>';
    $message .= '</body></html>';

    // Sending email
    mail($to, $subject, $message, $headers);

    return "success";
}

public function medInsuranceMailFamily($user, $info) {
    // $to = 'RSafarov@a-group.az';
    $to = 'nqasimova@a-group.az, insurance@a-group.az, anacafov@a-group.az';
    // $to = 'akhalilov@beylitech.az';
    $from = 'no-reply@a-group.az';
    // To send HTML mail, the Content-type header must be set
    $headers  = 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
    // Create email headers
    $headers .= 'From: '.$from."\r\n".
    'Reply-To: '.$from."\r\n" .
    'X-Mailer: PHP/' . phpversion();

    if($info['INSURANCE_STATUS'] == 'calculate') {
        $subject = "Расчет мед страхования (через сайт) AİLƏ";
        // Compose a simple HTML email message
        $message = '<!doctype html><html><head><meta charset="UTF-8"></head><body>';
        $message .= '<h1 style="color:black !important;">Пользователь расчитал цену на мед страховку</h1>';
    } elseif($info['INSURANCE_STATUS'] == 'order') {
        $subject = "Заказ мед страхования (через сайт) AİLƏ";
        // Compose a simple HTML email message
        $message = '<html><body>';
        $message .= '<h1 style="color:green !important;">Пользователь заказал мед страховку</h1>';
    }

    $message .= '<h4 style="color:green !important;">Общая цена страховки: ' . $info['INSURANCE_TOTAL_SUM'] . ' AZN</h4>';


    $PROPERTIES = $user['PROPERTIES'];
    $FIELDS = $user['FIELDS'];


    $message .= '<hr />';
    $message .= '<h4 style="color:black !important;"> ФИО: ' . $PROPERTIES['FIO'] . '</h4>';
    $message .= '<h4 style="color:black !important;"> Дата рождения: ' . $PROPERTIES['BIRTHDAY'] . '</h4>';
    $message .= '<h4 style="color:black !important;"> Вес: ' . $PROPERTIES['WEIGHT'] . '</h4>';
    $message .= '<h4 style="color:black !important;"> Рост: ' . $PROPERTIES['HEIGHT'] . '</h4>';
    $message .= '<h4 style="color:black !important;"> Номер: ' . $PROPERTIES['PHONE'] . '</h4>';
    $message .= '<h4 style="color:black !important;"> Адрес: ' . $PROPERTIES['ADDRESS'] . '</h4>';
    $message .= '<h4 style="color:black !important;"> Место работы: ' . $PROPERTIES['WORKPLACE'] . '</h4>';

    $message .= '<table style="border:1px solid lightgray; color:black !important; width:100%;">';

    $message .= '<tr>';
    $message .= '<th style="border:1px solid lightgray; padding:5px;">Вопрос</th>';
    $message .= '<th style="border:1px solid lightgray; padding:5px;">Ответ</th>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Обследовались ли вы в последние 2 года?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $PROPERTIES['Q1'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Госпитализировались ли вы когда-нибудь?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $PROPERTIES['Q2'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Принимаете ли Вы сейчас какое-либо лечение или лекарство?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $PROPERTIES['Q3'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Заболевания пищевода/желудка/кишечника?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $PROPERTIES['Q4'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Астма/аллергия/легочные заболевания?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $PROPERTIES['Q5'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Заболевания почек/ мочевого тракта?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $PROPERTIES['Q6'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Врожденные и наследственные заболевания?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $PROPERTIES['Q7'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Головная боль/ мигрень/ головокружения?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $PROPERTIES['Q8'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Повышение артериального давления (выше 140/90)?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $PROPERTIES['Q9'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Ревматизм/заболевания мышц, суставов, костей?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $PROPERTIES['Q10'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Варикозное расширение вен и другие заболевания сосудов?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $PROPERTIES['Q11'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Нарушения ритма, проводимости, болезни сердца?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $PROPERTIES['Q12'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Психические заболевания/нервные расстройства, эпилепсия?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $PROPERTIES['Q13'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Травмы, повреждения/дефекты, их последствия?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $PROPERTIES['Q14'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Проблемы со спиной или с позвоночником?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $PROPERTIES['Q15'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Болезни печени/селезёнки/поджелудочной железы?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $PROPERTIES['Q16'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Заболевания крови?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $PROPERTIES['Q17'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Сахарный диабет ?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $PROPERTIES['Q18'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Другие Эндокринные Заболевания?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $PROPERTIES['Q19'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Опухоли: доброкачественные/злокачественные?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $PROPERTIES['Q20'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Имеются ли у Вас нарушения здоровья, не указанные выше?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $PROPERTIES['Q21'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Являетесь ли Вы ныне или были ли когда-либо застрахованным в другой страховой компании?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $PROPERTIES['Q22'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Дополнительная информация</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $FIELDS['PREVIEW_TEXT'] . '</td>';
    $message .= '</tr>';
    $message .= '</table>';
    $message .= '</body></html>';

    // Sending email
    mail($to, $subject, $message, $headers);

    return "success";
}

public function medInsuranceMailFerqli($data) {

    // $to = 'halilov.lib@gmail.com';
    $to = 'nqasimova@a-group.az, insurance@a-group.az, anacafov@a-group.az';
    $from = 'no-reply@a-group.az';

    // To send HTML mail, the Content-type header must be set
    $headers  = 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";

    // Create email headers
    $headers .= 'From: '.$from."\r\n".
    'Reply-To: '.$from."\r\n" .
    'X-Mailer: PHP/' . phpversion();

    // Compose a simple HTML email message
    $message = '<!doctype html><html><head><meta charset="UTF-8"></head><body>';

    $subject = 'Заказ мед страхования (через сайт) FƏRGLİ';
    $message .= '<h1 style="color:green !important;">Пользователь заказал мед страховку</h1>';

    $message .= '<h4 style="color:black !important;"> ФИО: ' . $data['FIO'] . '</h4>';
    $message .= '<h4 style="color:black !important;"> Дата рождения: ' . $data['BIRTHDAY'] . '</h4>';
    $message .= '<h4 style="color:black !important;"> Вес: ' . $data['WEIGHT'] . '</h4>';
    $message .= '<h4 style="color:black !important;"> Рост: ' . $data['HEIGHT'] . '</h4>';
    $message .= '<h4 style="color:black !important;"> Номер: ' . $data['PHONE'] . '</h4>';
    $message .= '<h4 style="color:black !important;"> Адрес: ' . $data['ADDRESS'] . '</h4>';
    $message .= '<h4 style="color:black !important;"> Место работы: ' . $data['WORKPLACE'] . '</h4>';


    $message .= '<table style="border:1px solid lightgray; color:black !important; width:100%;">';

    $message .= '<tr>';
    $message .= '<th style="border:1px solid lightgray; padding:5px;">Вопрос</th>';
    $message .= '<th style="border:1px solid lightgray; padding:5px;">Ответ</th>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Обследовались ли вы в последние 2 года?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $data['Q1'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Госпитализировались ли вы когда-нибудь?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $data['Q2'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Принимаете ли Вы сейчас какое-либо лечение или лекарство?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $data['Q3'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Заболевания пищевода/желудка/кишечника?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $data['Q4'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Астма/аллергия/легочные заболевания?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $data['Q5'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Заболевания почек/ мочевого тракта?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $data['Q6'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Врожденные и наследственные заболевания?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $data['Q7'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Головная боль/ мигрень/ головокружения?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $data['Q8'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Повышение артериального давления (выше 140/90)?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $data['Q9'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Ревматизм/заболевания мышц, суставов, костей?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $data['Q10'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Варикозное расширение вен и другие заболевания сосудов?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $data['Q11'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Нарушения ритма, проводимости, болезни сердца?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $data['Q12'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Психические заболевания/нервные расстройства, эпилепсия?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $data['Q13'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Травмы, повреждения/дефекты, их последствия?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $data['Q14'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Проблемы со спиной или с позвоночником?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $data['Q15'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Болезни печени/селезёнки/поджелудочной железы?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $data['Q16'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Заболевания крови?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $data['Q17'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Сахарный диабет ?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $data['Q18'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Другие Эндокринные Заболевания?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $data['Q19'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Опухоли: доброкачественные/злокачественные?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $data['Q20'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Имеются ли у Вас нарушения здоровья, не указанные выше?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $data['Q21'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Являетесь ли Вы ныне или были ли когда-либо застрахованным в другой страховой компании?</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $data['Q22'] . '</td>';
    $message .= '</tr>';

    $message .= '<tr>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">Дополнительная информация</td>';
    $message .= '<td style="border:1px solid lightgray; padding:5px;">' . $data['PREVIEW_TEXT'] . '</td>';
    $message .= '</tr>';

    $message .= '</table>';
    $message .= '</body></html>';

    // Sending email
    mail($to, $subject, $message, $headers);

    return "success";
}

}

$obj = new Api;
