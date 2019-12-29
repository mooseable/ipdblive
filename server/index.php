<?php
header('Content-Type: application/json');
//include requirement
define('IPDBL', true);
//load config
require_once( 'config.php' );
require_once( './inc/userip.php' );
require_once( './inc/auth.php' );
//debug override (make postman life easier)
$config['auth']['window']=86400;

$userip=getUserIp();
if (!filter_var($userip, FILTER_VALIDATE_IP)){
    //visitor must have a real ip
    http_response_code(400);
    die();
}

//db connect
$db = new mysqli($config['db']['host'],$config['db']['user'],$config['db']['pass'],$config['db']['name']);
if ($db -> connect_errno) {
    //db error, return 500
    http_response_code(500);
    die();
}
if (array_key_exists('setup',$config)){
    require_once( './inc/setup.php' );
    $setup_ret = run_setup($db, $config['db']['t'], $config['setup']['email']);
    die(json_encode($setup_ret));
}
$auth = ipdb_auth($db,$config['db']['t']['acc']);
if ($auth === false){
    //unauthorized. todo: log fail attempt and ban
    http_response_code(401);
    $db->close();
    die();
}
//todo: log success attempt. ban connections from too many ips or ban too many connections in a window

require_once ( './inc/routes.php' );
$method = $_SERVER['REQUEST_METHOD'];
switch($method){
    case "POST":
        $data=$_POST;
        break;
    case "GET":
        $data=$_GET;
        break;
    default:
        $data = parse_str(file_get_contents("php://input"), $data);
}
$ret = route($db, $method, $data, $auth, $userip, $config);
http_response_code($ret['code']);
if ($ret['response']){
    echo json_encode($ret['response']);
}

$db->close();