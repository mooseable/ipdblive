<?php
if (!defined('IPDBL')){
    //todo: log attempts and ban
    die();
}

function ipabuse_check_ip($apikey, $ip){
    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => "https://api.abuseipdb.com/api/v2/check?maxAgeInDays=30&ipAddress=$ip",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => array(
        "Accept: application/json",
        "Key: $apikey",
        "cache-control: no-cache"
      ),
    ));
    
    $response = curl_exec($curl);
    $err = curl_error($curl);
    
    curl_close($curl);
    
    if ($err) {
      return false;
    } else {
        $objres = json_decode($response->getBody());
        if (array_key_exists('data', $objres)){
            if (array_key_exists('abuseConfidenceScore', $objres['data'])){
                if ($objres['data']['abuseConfidenceScore'] >= 75){
                    return array('bad'=>true,'source'=>'ipabusedb', 'reason'=>'abuse confidence');
                }
            }
        }
        return array('bad'=>true,'source'=>'ipabusedb', 'reason'=>$classifications);
    }
}

function ipabuse_bl_download($apikey){
    $curl = curl_init();

    curl_setopt_array($curl, array(
    CURLOPT_URL => "https://api.abuseipdb.com/api/v2/blacklist?confidenceMinimum=90&plaintext",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_POSTFIELDS => "confidenceMinimum=90",
    CURLOPT_HTTPHEADER => array(
        "Accept: application/json",
        "Accept-Encoding: gzip, deflate",
        "Cache-Control: no-cache",
        "Connection: keep-alive",
        "Content-Type: application/x-www-form-urlencoded",
        "Host: api.abuseipdb.com",
        "Key: $apikey",
        "User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:68.0) Gecko/20100101 Firefox/68.0",
        "cache-control: no-cache"
    ),
    ));
    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
        return false;
    } else {
        $badips = explode("\n",$response);
        return array('source'=>'ipabusedb', 'ips'=>$badips);
    }
}