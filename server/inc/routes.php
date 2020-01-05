<?php
if (!defined('IPDBL')){
    //todo: log attempts and ban
    die();
}
//$db, $method, $data, $userip, $config['db']['t'], $config['defs']
function route($conn, $method, $data, $auth, $userip, $config){
    $tbl = $config['db']['t'];
    $defs = $config['defs'];
    if (array_key_exists('ip', $data)) {
        $ip = filter_var($data['ip'], FILTER_VALIDATE_IP);
    } else {
        $ip=FALSE;
    }
    switch($method) {
        case "GET":
            if ($data['job'] === 'cron' && $auth['admin']){
                require_once('./inc/cron.php');
                return do_cron($conn, $config);
            }
            switch($ip){
                case FALSE:
                    return get_all($conn, $tbl, $defs);
                    break;
                default:
                    return get_single($conn, $ip, $tbl, $defs);
            }
            break;
        case "POST":
            return post_single($conn, $ip, $data, $userip, $tbl);
            break;
        case "PUT":
            if ($auth['admin']){
                return put_single($conn, $data, $userip, $tbl['acc']);
            }
            return(array('code'=>405, 'response'=>null));
            break;
        case "DELETE":
            if ($auth['admin']){
                return delete_single($conn, $data, $userip, $tbl['acc']);
            }
            return(array('code'=>405, 'response'=>null));
            break;
        default:
            //return method not allowed
            return(array('code'=>405, 'response'=>null));
    }

    
}

function get_all($conn, $t, $d) {
    $sql = "SELECT INET6_NTOA(UNHEX(ip)) as ip, score from ".$t['ip']." WHERE score >= ".$d['warn']." AND updated > DATE_SUB(now(), INTERVAL 30 DAY)";
    $res = $conn->query($sql);
    $warn = array();
    $crit = array();
    $ban  = array();
    if ($res->num_rows > 0) {
        while ($row = mysqli_fetch_assoc($res)){
            if ($row['score'] >= $d['ban']){
                $ban[] = $row['ip'];
            } elseif ($row['score'] >= $d['crit']) {
                $crit[] = $row['ip'];
            } else {
                $warn[] = $row['ip'];
            }
        }
        return(array('code'=>200, 'response'=>array('ban'=>$ban,'crit'=>$crit,'warn'=>$warn)));
    }
    return(array('code'=>404, 'response'=>null));
}
function get_single($conn, $ip, $t, $d){
    $sql = "SELECT score from ".$t['ip']." WHERE ip=HEX(INET6_ATON($ip)) updated > DATE_SUB(now(), INTERVAL 30 DAY)";
    $res = $conn->query($sql);
    if ($res->num_rows > 0) {
        return(array('code'=>200, 'response'=>array('score'=>$row['score'])));
    }
    return(array('code'=>404, 'response'=>null));
}
function post_single($conn, $data, $userip){
    return(array('code'=>501, 'response'=>null));
}
function set_single($conn, $ip, $score, $source, $reason, $ip_t, $rep_t){
    $sql = "INSERT INTO $ip_t (ip, score) VALUES INET6_ATON('$ip'), $score ON DUPLICATE KEY UPDATE score=$score";
    if ($conn->query($sql) === TRUE){
        return true;
    }
    return false;
}
function set_multiple($conn, $ips, $score, $source, $reason, $ip_t, $rep_t){
    $values = array();
    $repvalues = array();
    foreach ($ips as $ip){
        $ip = trim($ip);
        if(filter_var($ip, FILTER_VALIDATE_IP)){
            $values[] = "(HEX(INET6_ATON('$ip')),$score)";
            $repvalues[] = "(0,HEX(INET6_ATON('$ip')),'$reason',$score)";
        }
    }
    if (count($values)>0){
        $sql = "INSERT INTO $ip_t (ip, score) VALUES ".implode(",\r\n",$values)." ON DUPLICATE KEY UPDATE score=$score";
        if ($conn->query($sql) !== TRUE){
            echo 'sqlerr:';
            return $conn->error;
        }
    }
    if (count($values)>0){
        $sql = "INSERT INTO $rep_t (acc_id, src_ip, reason, rating) VALUES ".implode(",\r\n",$repvalues)." ON DUPLICATE KEY UPDATE rating=$score";
        if ($conn->query($sql) === TRUE){
            return true;
        }
        echo 'sqlerr2:';
        return $conn->error;
    }
    return $ips;
}
function put_single($conn, $data, $userip, $acc_t){
    //call me paranoid
    $email=preg_replace("/[^A-Za-z0-9\-_\+@]/", '', $data['email']);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return(array('code'=>400, 'response'=>null));
    }
    $sql = "SELECT 1 FROM $acc_t WHERE email='$email'";
    $res = $conn->query($sql);
    $pubk=gen_key();
    $prik=gen_key('priv');
    $prik_e=gen_hash($prik,$email); //32cha binary
    if ($res->num_rows === 1){
        //update
        $sql = "UPDATE $acc_t SET pubkey='$pubk' privkey='$prik_e' WHERE email='$email'";
    } else {
        //create
        $sql = "INSERT INTO $acc_t (pubkey, privkey, email) VALUES ('$pubk', '$prik_e', '$email'";
    }
    if ($conn->query($sql) === TRUE){
        //key sent to submitter only once. sha hash stored in db
        return(array('code'=>200, 'response'=> array( 'pubkey' => $pubk, 'privkey' => $prik )));
    }
    return(array('code'=>500, 'response'=>null));
}
function delete_single($conn, $data, $userip, $acc_t){
    //call me paranoid
    $email=preg_replace("/[^A-Za-z0-9\-_\+@]/", '', $data['email']);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return(array('code'=>400, 'response'=>null));
    }
    $sql = "SELECT 1 FROM $acc_t WHERE email='$email'";
    $res = $conn->query($sql);
    if ($res->num_rows === 1){
        //update
        $sql = "DELETE FROM $acc_t WHERE email='$email'";
        if ($conn->query($sql) === TRUE){
            //key sent to submitter only once. sha hash stored in db
            return(array('code'=>200, 'response'=> array( 'result' => 'success' )));
        }
    } else {
        return(array('code'=>404, 'response'=>null));
    }
    return(array('code'=>500, 'response'=>null));
}