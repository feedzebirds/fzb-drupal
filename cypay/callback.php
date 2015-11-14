<?php
/**
 * @file
 * Handles incoming requests to fire off regularly-scheduled tasks (cron jobs).
 */
require_once("./cypay_API_functions.php");
require_once("./cypay_config.php");
require_once("./cypay_util.php");
require_once("./cypay_error.php");

chdir("..");
include_once './includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

module_load_include('inc', 'twitter');
$gtwit = twitter_account_load(4001111111);

$call = parseCallback(SECRET);
if(!$call) {
  watchdog("ERROR", "errors parsing callback: ".json_encode($call));
} else if($call['received'] > 0) {
  $result = getAddressInfo($call['address']);
  if(get_class($result) != "cyError" &&
     property_exists($result, "received") &&
     $result->received > 0) {

    $aselect = sprintf("SELECT * FROM bitcoin_address WHERE address='%s'", $call['address']);
    $aresult = db_query($aselect);
    $address = db_fetch_object($aresult);
    if(empty($address)) {
      exit;
    }
    db_query("UPDATE bitcoin_address SET received=%d WHERE bid=%d", $result->received*1e8, $address->bid);

    $select = "SELECT * FROM campaign WHERE bid=".$address->bid;
    $result = db_query($select);
    while($campaign = db_fetch_object($result)) {
      if(!$campaign->active) {
        $status = $campaign->body." #FZB #spon feedzebirds.com/".$campaign->code;
        try {
          $result = twitter_set_status($gtwit, $status);
        } catch (TwitterException $e) {
          watchdog('twitter', 'An error occurred when posting to twitter: '.$e.getMessage());
        }
      }
      db_query("UPDATE campaign SET active=1 WHERE cid=%d", $campaign->cid);
    }
  }
}
?>
