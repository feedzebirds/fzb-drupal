<?php

function retweeter() {
  global $user;
  $user = user_load($user->uid);

  if(!$user->status) {
    echo t('You have been banned.');
    return;
  }

  $campaign = NULL;
  $cselect = "SELECT * FROM campaign WHERE code='".$_POST['title']."' AND uid!=".$user->uid;
  $cresult = db_query($cselect);
  $campaign = db_fetch_object($cresult);
  if(empty($campaign)) {
    echo "campaign not found";
    return;
  }

  $fselect = "SELECT fid FROM feeding WHERE cid=".$campaign->cid." AND uid=".$user->uid;
  $fresult = db_query($fselect);
  $feeding = db_fetch_object($fresult);
  if(!empty($feeding)) {
    echo t('You already retweeted this campaign.');
    return;
  }

  $remaining = fundsRemaining($campaign);
  if($remaining <= 0) {
    echo t('Campaign is out of funds.');
    return;
  }

  $twaccount = twitter_twitter_accounts($user);

  if(empty($twaccount) || $twaccount[0]->id == 0) {
    echo t('Account required');
    drupal_goto('twitter/redirect');
  }
  $twaccount = twitter_account_load($twaccount[0]->id);

  $topay = 0;
  $feedme = getRetweetValue($twaccount, $campaign);
  $topay = (feedme >= $remaining ? $remaining : $feedme);

  $status = $campaign->body." #FZB #spon feedzebirds.com/".$campaign->code;
  if(strlen($status) > 140) {
    echo "Sorry, somehow the status message was too long. We logged this error and are looking into it.";
    return;
  }

  $select = "SELECT created FROM feeding WHERE uid=".$user->uid." ORDER BY created DESC LIMIT 10";
  $result = db_query($select);
  $i = 1;
  $left = 0;
  while ($row = db_fetch_object($result)) {
    $i++;
    if($i != 2 && $i!=3 && $i!=10) {
      continue;
    }
    $now = time();
    $ago = ($now-$row->created);
    if($i == 2 && $ago < 60) {
      $left = 60-$ago;
    } elseif($i == 3 && $ago < 600) {
      $left = 600-$ago;
    } elseif($i == 10 && $ago < 3600) {
      $left = 3600-$ago;
    } else continue;
  }
  if($left < 60 && $left > 0) {
    echo "The birds are full; please wait ".$left." seconds.";
    return;
  } elseif($left > 60) {
    $min = round($left/60);
    echo "The birds are full; please wait ".$min." minutes.";
    return;
  }

  $fed = 0;
  try {
    $result = twitter_set_status($twaccount, $status);
    echo "<span class=\"highlighter\">Fed me ".sprintf("%f", intToFloat($topay)."btc</span>");
  } catch (TwitterException $e) {
    echo t('An error occurred when posting to twitter: %code %error',
                       array('%code' => $result->code, '%error' => $result->error));
    return;
  }

  $insert = sprintf("INSERT INTO feeding (cid, uid, beaks_fed, amount, fee, sent, created, updated) ".
            "VALUES(%d, %d, %d, %d, %d, %d, %d, %d)", $campaign->cid, $user->uid, $twaccount->followers_count,
            $topay, $topay/9, 0, time(), time());
  db_query($insert);

  fundsRemaining($campaign);

//  twitter_fetch_user_timeline($twaccount->id);
//  drupal_goto("/".$node->title);
}

/**
 * Settings form callback
 */
function twitter_post_admin_settings() {
  $node_types = node_get_types('names');
  foreach ($node_types as $type => &$name) {
    $name .= ' ' . l('Customize', 'admin/content/node-type/'.$type, array('fragment' => 'twitter'));
  }
  
  $form['twitter_post_types'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Node types'),
    '#options' => $node_types,
    '#default_value' => variable_get('twitter_post_types', array('story' => 'story', 'blog' => 'blog')),
  );

  $form['twitter_post_default_format'] = array(
    '#type' => 'textfield',
    '#title' => t('Default format string'),
    '#maxlength' => 140,
    '#description' => t('The given text will be posted to twitter.com. You can use !url, !url-alias, !tinyurl, !title, and !user as replacement text.  If the token module is enabled, you may also choose from the tokens listed in the replacement patterns section.'),
    '#default_value' => variable_get('twitter_post_default_format', 'New post: !title !tinyurl'),
  );
  twitter_include_token_fields($form);

  $form['posting']['twitter_post_default_state'] = array(
    '#type' => 'radios',
    '#title' => t('Post announcement default'),
    '#maxlength' => 140,
    '#description' => t('Choose the default state for post announcements.'),
    '#options' => array(
      FALSE => t('Disabled by default'),
      'all' => t('Enabled by default'),
      'create' => t('Enabled by default on create only'),
      'update' => t('Enabled by default on update only'),
      ),
    '#default_value' => variable_get('twitter_post_default_state', FALSE),
  );

  return system_settings_form($form);  
}
