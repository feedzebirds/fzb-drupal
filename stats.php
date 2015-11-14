<?php
include_once './includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

global $user;
if($user->uid != 1) drupal_goto("/");

if($_GET['wipe'] == 1) {
  $drop = "DROP TABLE IF EXISTS fzb_statistics;";
  db_query($drop);

  $create = "CREATE TABLE fzb_statistics (sid int(10) NOT NULL AUTO_INCREMENT PRIMARY KEY, cid int(10), fid int(10), bid int(10), uid int(10),
kee varchar(24), value varchar(256));";
}

$maxfed = array();
$campaigns = array();
$query = "SELECT * FROM campaign";
$result = db_query($query);
while($row = db_fetch_object($result)) {
  $campaigns[$row->cid]['created'] = (int) $row->created;
  $campaigns[$row->cid]['uid'] = $row->uid;
  $campaigns[$row->cid]['code'] = $row->code;
}

$query = "SELECT * FROM feeding";
$result = db_query($query);
$feedings = array();
while($row = db_fetch_object($result)) {
  $campaigns[$row->cid]['fed'] += $row->beaks_fed;
  if(empty($maxfed) || $campaigns[$row->cid]['fed'] > $maxfed['fed']) {
    $maxfed = $campaigns[$row->cid];
    $maxfed['cid'] = $row->cid;
  }

  if($campaigns[$row->cid]['fed'] > 100000 && empty($campaigns[$row->cid]['100k_fed'])) {
    $elapsed = $row->created - $campaigns[$row->cid]['created'];
    $campaigns[$row->cid]['100k_fed'] = $elapsed;
    $res = db_query("SELECT cid FROM fzb_statistics WHERE cid=".$row->cid." AND kee='100k_fed'");
    $ro = db_fetch_object($res);
    if(!$ro || empty($ro->cid)) {
      $insert = "INSERT INTO fzb_statistics (cid, fid, bid, uid, kee, value) VALUES(".$row->cid.", 0, 0, ".$campaigns[$row->cid]['uid'].
                ", '100k_fed', '".$elapsed."')";
      db_query($insert);
    }
  }

  if($campaigns[$row->cid]['fed'] > 250000 && empty($campaigns[$row->cid]['250k_fed'])) {
    $elapsed = $row->created - $campaigns[$row->cid]['created'];
    $campaigns[$row->cid]['250k_fed'] = $elapsed;
    $res = db_query("SELECT cid FROM fzb_statistics WHERE cid=".$row->cid." AND kee='250k_fed'");
    $ro = db_fetch_object($res);
    if(!$ro || empty($ro->cid)) {
      $insert = "INSERT INTO fzb_statistics (cid, fid, bid, uid, kee, value) VALUES(".$row->cid.", 0, 0, ".$campaigns[$row->cid]['uid'].
                ", '250k_fed', '".$elapsed."')";
      db_query($insert);
    }
  }

  if($campaigns[$row->cid]['fed'] > 500000 && empty($campaigns[$row->cid]['500k_fed'])) {
    $elapsed = $row->created - $campaigns[$row->cid]['created'];
    $campaigns[$row->cid]['500k_fed'] = $elapsed;
    $res = db_query("SELECT cid FROM fzb_statistics WHERE cid=".$row->cid." AND kee='500k_fed'");
    $ro = db_fetch_object($res);
    if(!$ro || empty($ro->cid)) {
      $insert = "INSERT INTO fzb_statistics (cid, fid, bid, uid, kee, value) VALUES(".$row->cid.", 0, 0, ".$campaigns[$row->cid]['uid'].
                ", '500k_fed', '".$elapsed."')";
      db_query($insert);
    }
  }

  if($campaigns[$row->cid]['fed'] > 1000000 && empty($campaigns[$row->cid]['1m_fed'])) {
    $elapsed = $row->created - $campaigns[$row->cid]['created'];
    $campaigns[$row->cid]['1m_fed'] = $elapsed;
    $res = db_query("SELECT cid FROM fzb_statistics WHERE cid=".$row->cid." AND kee='1m_fed'");
    $ro = db_fetch_object($res);
    if(!$ro || empty($ro->cid)) {
      $insert = "INSERT INTO fzb_statistics (cid, fid, bid, uid, kee, value) VALUES(".$row->cid.", 0, 0, ".$campaigns[$row->cid]['uid'].
                ", '1m_fed', '".$elapsed."')";
      db_query($insert);
    }
  }  
}
$res = db_query("SELECT sid FROM fzb_statistics WHERE kee='max_fed' AND value=".$maxfed['fed']);
$ro = db_fetch_object($res);
if(!$ro) {
  $update = "UPDATE fzb_statistics SET cid=".$maxfed['cid'].", uid=".$maxfed['uid'].
            ", value='".$maxfed['fed']."' WHERE kee='max_fed'";
//  $update = "INSERT INTO fzb_statistics (cid, fid, bid, uid, kee, value) VALUES(".$maxfed['cid'].", 0, 0, ".$maxfed['uid'].
//                ", 'max_fed', '".$maxfed['fed']."')";
  db_query($update);
}

?>
