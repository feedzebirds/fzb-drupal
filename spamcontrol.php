<?php

include_once './includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

session_save_session(FALSE);

module_load_include('inc', 'twitter');

$commalist = "";
$select = "SELECT uid FROM users ORDER BY uid";
$result = db_query($select);
$hand = fopen("fzb_user_categorization", "w");
fwrite($hand, "name;kscore;followers;number of tweets;list\n");
while($row = db_fetch_object($result)) {
  if($row->uid == 0) continue;
  $user = user_load((int) $row->uid);
  if($user->profile_restricted == 0) continue;

  $twaccount = twitter_twitter_accounts($user);
  if(!empty($twaccount) && $twaccount[0]->id != 0) {
    $twaccount = twitter_account_load($twaccount[0]->id);
  }
  if($user->data['status'] != "a") {
    fwrite($hand, 
$twaccount->screen_name.";".$user->profile_kscore.";".
$twaccount->followers_count.";".$twaccount->statuses_count.";grey\n");
    if(!$user->profile_restricted) {
      $user->profile_restricted = 1;
      $edit = array("profile_restricted"=>1);
      profile_save_profile($edit, $user, "Spam Control");
    }
    $user = user_load(0);
    continue;
  }

  $followers = (empty($twaccount->followers_count) ? 0 : $twaccount->followers_count);
  if(isSpammer($user->profile_kscore, $followers) == "white") {
    fwrite($hand, 
$twaccount->screen_name.";".$user->profile_kscore.";".
$twaccount->followers_count.";".$twaccount->statuses_count.";white\n");    
    if($user->profile_restricted) {
      $user->profile_restricted = 0;
      $edit = array("profile_restricted"=>0);
      profile_save_profile($edit, $user, "Spam Control");
    }
    $user = user_load(0);
    continue;
  }
  sleep(1);
  $toget = "http://api.klout.com/1/klout.json?key=...&users=".urlencode($twaccount->screen_name);
  $request = drupal_http_request($toget);
  $data = json_decode($request->data, true);
  if(empty($data['users'])) {
    $user->profile_kscore = 0;
    $edit = array("profile_kscore"=>0);
    profile_save_profile($edit, $user, "Spam Control");

    $spammer = isSpammer($user->profile_kscore, $followers);
    fwrite($hand, 
$twaccount->screen_name.";".$user->profile_kscore.";".
$followers.";".$twaccount->statuses_count.";".
$spammer."\n");
    if($spammer == "white") {
      $user->profile_restricted = 0;
      $edit = array("profile_restricted"=>0);
      profile_save_profile($edit, $user, "Spam Control");
    } else if($spammer == "grey") {
      $user->profile_restricted = 1;
      $edit = array("profile_restricted"=>1);
      profile_save_profile($edit, $user, "Spam Control");
    }
    $user = user_load(0);
    continue;
  }

  $userdata = $data['users'];
  for($i = 0; $i < sizeof($userdata); $i++) {
    if($user->profile_kscore > 0 &&
       $user->profile_kscore != $userdata[$i]['kscore']) {
      $update = "UPDATE profile_values SET value='".$userdata[$i]['kscore'].
                "' WHERE uid=".$user->uid." AND fid=5)";
      db_query($update);
    } elseif(empty($user->profile_kscore)) {
      $insert = "INSERT INTO profile_values VALUES (5, ".
                 $user->uid.", '".$userdata[$i]['kscore']."')";
      db_query($insert);
    }
    $spammer = isSpammer($user->profile_kscore, $followers);
    fwrite($hand, 
$twaccount->screen_name.";".$user->profile_kscore.";".
$followers.";".$twaccount->statuses_count.";".
$spammer."\n");
    if($spammer == "white") {
      $user->profile_restricted = 0;
      $edit = array("profile_restricted"=>0);
      profile_save_profile($edit, $user, "Spam Control");
    } else if($spammer == "grey") {
      $user->profile_restricted = 1;
      $edit = array("profile_restricted"=>1);
      profile_save_profile($edit, $user, "Spam Control");
    }
  }
  $user=user_load(0);
}
fclose($hand);

function isSpammer($kscore, $followers) {
  if(empty($kscore) || ($followers > 10 && $kscore < 10.5)) {
    return "grey";
  }
  return "white";
}
?>
