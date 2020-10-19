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



    }

    public function login($cache = true) {


  
        $sessionFile = 'cache/session.save';

        if($cache) {
   
            if(file_exists($sessionFile)) {
                $this->session = file_get_contents($sessionFile);
                
            }
        }


        if ( !$this->session OR !$cache) {
        

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

                $file = fopen($sessionFile, 'w+');
                fwrite($file, $this->session);
                fclose($file);

                return true;
            }
        } else {
            return true;
        }
        return false;
    }

    public function sendRequest( $method, $params = array()) {
        if ( !$this->url ) {
            throw new Exception("Не указан url API", 1);
        }


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

     

        $json = json_encode($params);
        if ($method == 'get_account_url_and_user_caller_id_for_phone') {
        }
        curl_setopt($curl, CURLOPT_POSTFIELDS, ['method' => $method, 'input_type' => 'JSON', 'response_type' => 'JSON', 'rest_data' => $json]);

        $response = curl_exec($curl);


        if($response === false) {
            throw new Exception(curl_error($curl), 1);
        }

        if (($res = json_decode($response, true)) === NULL) {
            throw new Exception($response, 1);
        }

        if(isset($res['name']) AND $res['name'] == 'Invalid Session ID') {


            
            $this->session = null;
            $this->login(false);

            
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

