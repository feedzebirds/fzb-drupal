<?php

/**
 * @file
 * Handles incoming requests to fire off regularly-scheduled tasks (cron jobs).
 */

include_once './includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
drupal_cron_run();

require_once("./cypay/cypay_API_functions.php");
require_once("./cypay/cypay_config.php");
require_once("./cypay/cypay_util.php");
require_once("./cypay/cypay_error.php");

module_load_include('inc', 'twitter');
$gtwit = twitter_account_load(406121111);

$result = db_query("SELECT COUNT(bid) AS count FROM bitcoin_address WHERE cid=0");
$resulto = db_fetch_object($result);
$acount = $resulto->count;
echo $acount;
if($acount < 10) {
  $result = getNewAddress(0, 10-$acount, "", "");
  if(get_class($result) != "cyError" && property_exists($result, "address0")) {
    foreach($result as $key => $value) {
      $insert = "INSERT INTO bitcoin_address (cid, uid, address, received, is_public, server) VALUES(0".
            ", 0, '".$value."', 0, 0, 2)";
      db_query($insert);
    }
  }
}

$result = db_query("SELECT DISTINCT(uid) FROM feeding");
while($resultu = db_fetch_object($result)) {
  $tmpuser = user_load($resultu->uid);
  if(empty($tmpuser->profile_payment_address)) continue;
echo $tmpuser->profile_payment_address."<br>";
  $balance = intToFloat(getUserBalance($tmpuser->uid));
echo $balance."<br>";
  if($balance >= 0.01 && $tmpuser->uid != 380) {
    $toaddy = $tmpuser->profile_payment_address;
    if(strpbrk($tmpuser->profile_payment_address, "@.") != false) {
      $toaddy = getCoinapultAddy($tmpuser->profile_payment_address);
    }
    $cysult = sendBitcoin($balance, $toaddy);
    if(get_class($cysult) != "cyError" &&
       property_exists($cysult, "txid")) {
      $r = db_query("SELECT fid FROM feeding WHERE sent=0 AND uid=".$tmpuser->uid);
      while($feeding = db_fetch_object($r)) {
        $update = "UPDATE feeding SET sent=1 WHERE fid=".$feeding->fid;
        db_query($update);
      }
    } else {
      watchdog("error", "error sending Bitcoin from CyPay: ".json_encode($cysult));
    }
  }
}

$result = db_query("SELECT cid FROM campaign WHERE active=1");
while($row = db_fetch_object($result)) {
  $res = db_query("SELECT MAX(f.created) AS maxc FROM feeding WHERE cid=".$row->cid);
  while($ro = db_fetch_object($res)) {
    $cutoff = time()-86400*7;
    if($ro->maxc < $cutoff) {
      echo $row->cid."<br>";
    }
  }
}

$result = db_query("SELECT DISTINCT(uid) FROM refund");
while($row = db_fetch_object($result)) {
  $tmpuser = user_load($row->uid);
  if(empty($tmpuser->profile_payment_address)) continue;
  $balance = intToFloat(getUserRefundBal($tmpuser->uid));
  if($balance >= 0.01 && $tmpuser->uid != 380) {
    $toaddy = $tmpuser->profile_payment_address;
    if(strpbrk($tmpuser->profile_payment_address, "@.") != false) {
      $toaddy = getCoinapultAddy($tmpuser->profile_payment_address);
    }
    $cysult = sendBitcoin($balance, $toaddy);
    if(get_class($cysult) != "cyError" &&
       property_exists($cysult, "txid")) {
      $r = db_query("SELECT rid FROM refund WHERE sent=0 AND uid=".$tmpuser->uid);
      while($refund = db_fetch_object($r)) {
        $update = "UPDATE refund SET sent=1 WHERE rid=".$refund->rid;
        db_query($update);
      }
    } else {
      watchdog("error", "error sending Bitcoin from CyPay: ".json_encode($cysult));
    }
  }
}

function getCoinapultAddy($email) {
  $params = new stdClass();

  $params->method = "email";
  $params->to = $email;
  $params->from = "feedzebirds@gmail.com";
  $params->message = "FeedZeBirds payment";
  $params->raw = "1";

  //add HMAC
  $hmac = strongHMACEncode($params, "");
  if(!$hmac) return false;
  $params->hmac = $hmac;

  $post_string = http_build_query($params, '', '&');
  $curl = curl_init("http://coinapult.com/payload/send");
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
  curl_setopt($curl, CURLOPT_FORBID_REUSE, TRUE);
  curl_setopt($curl, CURLOPT_FRESH_CONNECT, TRUE);
  curl_setopt($curl, CURLOPT_POST, TRUE);
  curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
  curl_setopt($curl, CURLOPT_POSTFIELDS, $post_string);

  $rawresponse = curl_exec($curl);

  return $rawresponse;
}

/**
 * Generate HMAC by json encoding parameter array then running through SHA512.
 * 
 * Parameters:
 * params           The parameters to authenticate, as an associative array
 * secret           Your Paysius application secret
 *
 * Return (if valid): HMAC (String)
 * if invalid: false
 */
function strongHMACEncode($params, $secret = "") {
  //Replace API credentials with defined values, if not provided.
  if(empty($secret)) $secret = SECRET;

  //Check for bad values
  if(empty($params) || empty($secret) || !is_object($params))
    return false;

  //json_encode params
  $jMessage = json_encode($params);

  //generate hmac
  $hmac = hash_hmac("sha512", $jMessage, $secret);
  return $hmac;
}

?>
