<?php
/**
 * Created by PhpStorm.
 * User: michaelntambazi
 * Date: 07/05/16
 * Time: 10:25 AM
 */

/*
 * This index.php file will act as a front controller for the API,
 *  so all requests to the API server will be made through this file.
 *
 * DUTIES
 * 1 - Accept an API call with any number of parameters
 * 2 - Extract the Controller and Action for the API call
 * 3 - Make the necessary checks to ensure that the Controller and Action exist
 * 4 - Execute the API call
 * 5 - Catch errors, if any
 * 6 - Send back a result to the caller
 */

// Define path to data folder
define('DATA_PATH', realpath(dirname(__FILE__).'/data'));

//Define our id-key pairs
$applications = array(
    'APP001' => '28e336ac6c9423d946ba02d19c6a2632', //randomly generated app key
);

//include our models
include_once 'models/TodoItem.php';

//wrap the whole thing in a try-catch block to catch any wayward exceptions!
try {
    //*UPDATED*
    //get the encrypted request
    $enc_request = $_REQUEST['enc_request'];

    //get the provided app id
    $app_id = $_REQUEST['app_id'];

    //check first if the app id exists in the list of applications
    if( !isset($applications[$app_id]) ) {
        throw new Exception('Application does not exist!');
    }

    //decrypt the request
    $params = json_decode(trim(mcrypt_decrypt( MCRYPT_RIJNDAEL_256, $applications[$app_id], base64_decode($enc_request), MCRYPT_MODE_ECB )));

    //check if the request is valid by checking if it's an array and looking for the controller and action
    if( $params == false || isset($params->controller) == false || isset($params->action) == false ) {
        throw new Exception('Request is not valid');
    }

    //cast it into an array
    $params = (array) $params;

    //get the controller and format it correctly so the first
    //letter is always capitalized
    $controller = ucfirst(strtolower($params['controller']));

    //get the action and format it correctly so all the
    //letters are not capitalized, and append 'Action'
    $action = strtolower($params['action']).'Action';

    //check if the controller exists. if not, throw an exception
    if( file_exists("controllers/{$controller}.php") ) {
        include_once "controllers/{$controller}.php";
    } else {
        throw new Exception('Controller is invalid.');
    }

    //create a new instance of the controller, and pass
    //it the parameters from the request
    $controller = new $controller($params);

    //check if the action exists in the controller. if not, throw an exception.
    if( method_exists($controller, $action) === false ) {
        throw new Exception('Action is invalid.');
    }

    //execute the action
    $result['data'] = $controller->$action();
    $result['success'] = true;

} catch (Exception $e) {
    //catch any exceptions and report the problem
    $result = array();
    $result['success'] = false;
    $result['errormsg'] = $e->getMessage();
}

//echo the result of the API call
echo json_encode($result);
exit();