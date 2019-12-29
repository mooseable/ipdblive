<?php
if (!defined('IPDBL')){
    //todo: log attempts and ban
    die();
}
require_once('passgen.php');
//auth
//require pubkey+hashed (privkey+email+floor(unixseconds/60))
function ipdb_auth($conn, $usr_t){
    if (isset($_SERVER["HTTP_AUTHORIZATION"]) && 0 === stripos($_SERVER["HTTP_AUTHORIZATION"], 'basic ')) {
        $http_auth = explode(':', base64_decode(substr($_SERVER["HTTP_AUTHORIZATION"], 6)), 2);
        if (2 == \count($http_auth)) {
            list($pubk, $privk) = $http_auth;
            list($k, $e) = explode('+', $privk);
            $privkh_e=gen_hash($k,$e);
            //todo: sanitize user input
            $sql = "select id, HEX(privkey) as privkey, email, is_admin FROM $usr_t WHERE pubkey='$pubk' LIMIT 1";
            $res = $conn->query($sql);
            if ($res->num_rows > 0) {
                $row = mysqli_fetch_assoc($res);
                //if we find a matching hash, permit
                if ($privkh_e === $row['privkey']){
                    return array('uid'=>$row['id'],'admin'=>$row['is_admin']);
                }
            }
        }
    }
    //unauthorized
    return false;
}
function gen_key($type = 'pub'){
    if ($type==='priv'){
        return PasswordGenerator::getAlphaNumericPassword(mt_rand(48,64));
    }
    return PasswordGenerator::getAlphaNumericPassword(mt_rand(16,24));
}

function gen_hash($pk, $email){
    return(strtoupper(hash('sha256',$pk.':'.$email, FALSE)));
}