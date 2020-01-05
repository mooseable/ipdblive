<?php
if (!defined('IPDBL')){
    //todo: log attempts and ban
    die();
}
function do_cron($conn, $config){
    require_once('./bl-inc/ipabusedb.php');
    require_once('./bl-inc/talos.php');
    $ipabusebl = ipabuse_bl_download($config['abusedbs']['abuseipdb']['apikey']);
    //var_dump($ipabusebl);
    if ($ipabusebl){
        $ins=set_multiple($conn, $ipabusebl['ips'], 100, $ipabusebl['source'], 'high abuse score', $config['db']['t']['ip'], $config['db']['t']['rep']);
        if (!$ins===true){
            die(print_r($ins));
        }
    }
    $talosbl = talos_bl_download();
    if ($talosbl){
        $ins = set_multiple($conn, $talosbl['ips'], 100, $talosbl['source'], 'talos blacklist', $config['db']['t']['ip'], $config['db']['t']['rep']);
        if (!$ins===true){
            die($ins);
        }
    }
    return(array('code'=>200,'response'=>array('result'=>'ok')));
}


function curl_follow_exec($ch)
{
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $data = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($http_code == 301 || $http_code == 302) {
        preg_match('/(Location:|URI:)(.*?)\n/', $data, $matches);
        if (isset($matches[2])) {
            $redirect_url = trim($matches[2]);
            if ($redirect_url !== '') {
                curl_setopt($ch, CURLOPT_URL, $redirect_url);
                return curl_follow_exec($ch);
            }
        }
    }
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($data, 0, $header_size);
    $body = substr($data, $header_size);
    return $body;
}