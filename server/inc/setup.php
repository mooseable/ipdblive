<?php
if (!defined('IPDBL')){
    //todo: log attempts and ban
    die();
}
function run_setup($conn, $t, $email){
    $acc_t=$t['acc'];
    $ip_t=$t['ip'];
    $log_t=$t['log'];
    $rep_t=$t['rep'];
    foreach ($t as $k=>$v){
        $sql = "DROP TABLE $v";
        if ($conn->query($sql) !== TRUE){
            echo('error dropping table '.$v.'
            ');
        }
    }

    $sql="CREATE TABLE $acc_t (
        id INT(8) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        pubkey VARCHAR(24) NOT NULL,
        privkey BINARY(32) NOT NULL,
        email VARCHAR(128) NOT NULL UNIQUE,
        is_admin BOOLEAN DEFAULT FALSE,
        created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
        ";
    if ($conn->query($sql) !== TRUE){
        die('error creating table '.$acc_t);
    }
    $sql="CREATE TABLE $ip_t (
        ip VARBINARY(16) PRIMARY KEY,
        score TINYINT SIGNED,
        created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
        ";
    if ($conn->query($sql) !== TRUE){
        die('error creating table '.$ip_t);
    }
    $sql="CREATE TABLE $rep_t (
        id int(8) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        acc_id int(8) UNSIGNED NOT NULL,
        src_ip VARBINARY(16) NOT NULL,
        svc_name VARCHAR(64),
        reason VARCHAR(256) NOT NULL,
        rating TINYINT UNSIGNED NOT NULL,
        created TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
        ";
    if ($conn->query($sql) !== TRUE){
        die('error creating table '.$rep_t);
    }
    $sql="CREATE TABLE $log_t (
        id INT(8) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        pubkey VARCHAR(32) NOT NULL,
        privkey BINARY(20) NOT NULL,
        email VARCHAR(128) NOT NULL UNIQUE,
        is_admin BOOLEAN DEFAULT FALSE,
        created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
        ";
    if ($conn->query($sql) !== TRUE){
        die('error creating table '.$log_t);
    }

    $pubk=gen_key();
    $prik=gen_key('priv');
    $prik_e=gen_hash($prik,$email); //32cha binary
    $sql = "INSERT INTO $acc_t (pubkey, privkey, email, is_admin) VALUES ('$pubk', UNHEX('$prik_e'), '$email', true)";
    if ($conn->query($sql) === TRUE){
        //key sent to submitter only once. sha hash stored in db
        return(array('code'=>200, 'response'=> array( 'pubkey' => $pubk, 'privkey' => $prik, 'hash'=> $prik_e )));
    }
    return(array('code'=>500, 'response'=> array( 'error' => $conn->error )));
}