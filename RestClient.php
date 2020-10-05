<?php
if(!defined('sugarEntry'))define('sugarEntry', true);

require_once('include/entryPoint.php');

class RestClient {

    public $url;
    public $username;
    public $password;

    public $apache_username;
    public $apache_password;

    private $session = null;

    function __construct () {
        $this->url = 'http://u424.local/custom/service/v4_1_4/rest.php';


        $this->username = 'admin';
        $this->password = md5('password');

        //$this->apache_username = 'moedelo';
        //s$this->apache_password = '<tpGfhjkz';


        //$this->url = 'http://u424.local/custom/service/v4_1_calls_entries/rest.php';
        //$this->username = 'admin';
        //$this->password = md5('password');



    }

    public function login($cache = true) {


        // Файл с названием сессии
        $sessionFile = 'cache/session.save';

        if($cache) {
            // Если указан параметр испльзования кеша
            // Пытаемся соединиться по старому подключению
            //echo ('указан параметр испльзования кеша' . "<BR>");
            //echo ('Пытаемся соединиться по старому подключению' . "<BR>");
            if(file_exists($sessionFile)) {
                $this->session = file_get_contents($sessionFile);
                //echo '$this->session = ' . $this->session . "<BR>";
            }
        }


        if ( !$this->session OR !$cache) {
            //echo 'Сессия не указана' . "<BR>";

            $login_parameters = [
                'user_auth' => array(
                    'user_name' => $this->username,
                    'password' => $this->password,
                ),
                '1',
            ];
            $result = $this->sendRequest('login', $login_parameters);
            if ( isset($result['id']) ) {
                $this->session = $result['id'];

                // Записываем сессию в файл
                //echo 'Записываем сессию '.$this->session.' в файл' . "<BR>";
                $file = fopen($sessionFile, 'w+');
                fwrite($file, $this->session);
                fclose($file);

                return true;
            }
        } else {
            //echo 'Сессия уже указана' . "<BR>";
            return true;
        }
        return false;
    }

    public function sendRequest( $method, $params = array()) {
        if ( !$this->url ) {
            throw new Exception("Не указан url API", 1);
        }

        //echo '$method = ' . $method . ' $this->session = ' . $this->session . "<BR>";
        //echo '$params:' . "<BR><pre>";
        //var_export($params);
        //echo '</pre>';
        if ($method !== 'login' && empty($this->session)) {

            if (!$this->login(false)) {
                throw new Exception("Ошибка соединения с REST сервисом", 1);
            }
        }

        $curl = curl_init($this->url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_BINARYTRANSFER, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        //curl_setopt($curl, CURLOPT_USERPWD, $this->apache_username . ':' . $this->apache_password);

        $json = json_encode($params);
        if ($method == 'get_account_url_and_user_caller_id_for_phone') {
        }
        curl_setopt($curl, CURLOPT_POSTFIELDS, ['method' => $method, 'input_type' => 'JSON', 'response_type' => 'JSON', 'rest_data' => $json]);

        $response = curl_exec($curl);

        //echo '$response = ' . $response . "<BR>";
        //var_export($response);
        if($response === false) {
            throw new Exception(curl_error($curl), 1);
        }

        if (($res = json_decode($response, true)) === NULL) {
            throw new Exception($response, 1);
        }

        if(isset($res['name']) AND $res['name'] == 'Invalid Session ID') {
            // Закончилась авторизация
            //echo 'Закончилась авторизация!' . "<BR>";

            // Заново авторизуемся
            $this->session = null;
            $this->login(false);

            // Меняем в параметрах номер сессии
            $params[0] = $this->session;
            return $this->sendRequest($method, $params);
        }


        return $res;
    }

    public function __call( $name, $arguments ) {
        if ($name !== 'login') array_unshift($arguments[0], $this->session);
        return $this->sendRequest($name, $arguments[0]);
    }
}

$phone = '+79109082123';

$rest = new RestClient;

if (!$rest->login()) {
    echo "Ошибка соединения с REST сервисом\n";
    return;
}
echo "Успешно соединились с REST сервисом\n";

$params = [
    'phone' => $phone,
];


$res = $rest->get_account_url_and_user_caller_id_for_phone($params);
//print_array('$res: ' . var_export($res,1));

