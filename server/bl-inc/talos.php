<?php
if (!defined('IPDBL')){
    //todo: log attempts and ban
    die();
}
function talos_bl_check($ip){
    $request = new HttpRequest();
    $request->setUrl('https://talosintelligence.com/sb_api/blacklist_lookup');
    $request->setMethod(HTTP_METH_GET);

    $request->setQueryData(array(
    'query_type' => 'ipaddr',
    'query_entry' => '$ip'
    ));

    $request->setHeaders(array(
    'cache-control' => 'no-cache',
    'Accept-Encoding' => 'gzip, deflate',
    'Host' => 'talosintelligence.com',
    'Cache-Control' => 'no-cache',
    'TE' => 'Trailers',
    'Connection' => 'keep-alive',
    'Referer' => 'https://talosintelligence.com/reputation_center/lookup?search=$ip',
    'Accept-Language' => 'en-US,en;q=0.5',
    'Accept' => 'application/json, text/javascript, */*; q=0.01',
    'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64; rv:68.0) Gecko/20100101 Firefox/68.0'
    ));

    try {
        $response = $request->send();
        $objres = json_decode($response->getBody());
        if (array_key_exists('entry', $objres)){
            if (array_key_exists('status', $objres['entry'])){
                if ($objres['entry']['status'] === "ACTIVE"){
                    $classifications='';
                    if (array_key_exists('classification', $objres['entry'])){
                        $classifications = implode(', ',$objres['entry']['classification']);
                    }
                    return array('bad'=>true,'source'=>'talos', 'reason'=>$classifications);
                }
            }
        }
    } catch (HttpException $ex) {
        return false;
    }
    return false;
}

function talos_bl_download(){
    $curl = curl_init();

    curl_setopt_array($curl, array(
    CURLOPT_URL => "https://talosintelligence.com/documents/ip-blacklist",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_FOLLOWLOCATION, true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_HTTPHEADER => array(
        "Accept: */*",
        "Accept-Encoding: gzip, deflate",
        "Cache-Control: no-cache",
        "Connection: keep-alive",
        "Referer: https://talosintelligence.com/documents/ip-blacklist",
        "User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:68.0) Gecko/20100101 Firefox/68.0",
        "cache-control: no-cache"
    ),
    ));
    $response = curl_follow_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);
    if ($err) {
        return false;
    } else {
        $badips = explode("\n",$response);
        return array('source'=>'talos', 'ips'=>$badips);
    }
    
}