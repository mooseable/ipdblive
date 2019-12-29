<?php
if (!defined('IPDBL')){
    //todo: log attempts and ban
    die();
}
$config = array();
$config['db']['host']="localhost";
$config['db']['user']="dbuser";
$config['db']['pass']="dbpass";
$config['db']['name']="dbname";

$config['db']['t']['ip']="ips";
$config['db']['t']['rep']="ipreports";
$config['db']['t']['acc']="accounts";
$config['db']['t']['log']="audit";

$config['defs']['warn']=15;
$config['defs']['crit']=15;
$config['defs']['ban']=15;

//delete this line from config once your setup is complete
$config['setup']['email']="MY@EMAIL.ADDR";

$config['abusedbs']['abuseipdb']['apikey']="APIKEY";