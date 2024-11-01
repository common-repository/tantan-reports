<?php 
/*
Copyright (C) 2008  Joe Tan

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA

$Revision: 111 $
$Date: 2008-07-21 23:34:25 -0400 (Mon, 21 Jul 2008) $
$Author: joetan54 $
$URL: https://wordpress-reports.googlecode.com/svn/trunk/tantan-reports/wordpress-reports/tantan_reports_config.php $

*/

//$caps = get_usermeta( $userdata->ID, $table_prefix . 'capabilities');
if (!current_user_can('edit_plugins')):?>
Sorry, you need to be logged in as an administrator in order to setup reports.
<?php
exit;
endif;

require_once(dirname(__FILE__).'/lib.googleanalytics.php');
require_once(dirname(__FILE__).'/lib.feedburner.php');

$ga = new tantan_GoogleAnalytics();
$fb = new tantan_FeedBurner();



$message = '';
$error = '';
if ($_POST['service'] == 'googleanalytics') {
    if ($_POST['subaction'] == 'change account') {
        $ga->logout();
        update_option('tantan_gaPassword', '');
        update_option('tantan_gaSession', '');
        update_option('tantan_gaSessionTimeout', 0);
        update_option('tantan_gaSiteProfile', '');
        update_option('tantan_gaInstallTracking', '');
        update_option('tantan_gaTrackingCode', '');
    
    } elseif ($_POST['subaction'] == 'refresh') {
        update_option('tantan_gaProfiles', '');
        $message = 'Refreshed your site profiles list.';
    
    } elseif ($_POST['subaction'] == 'login') {
        update_option('tantan_gaEmail', $_POST['tantan_gaEmail']);
        //$password = $_POST['tantan_gaPassword'];
    
    } elseif ($_POST['action'] == 'save') {
        update_option('tantan_gaEmail',                    $_POST['tantan_gaEmail']);
        //update_option('tantan_gaPassword',                 '');
        update_option('tantan_gaSiteProfile',              $_POST['tantan_gaSiteProfile']);
        update_option('tantan_gaReports',                  $_POST['tantan_gaReports']);
        update_option('tantan_gaInstallTracking',          $_POST['tantan_gaInstallTracking']);
        update_option('tantan_gaInstallTrackingOutbound',  $_POST['tantan_gaInstallTrackingOutbound']);
        update_option('tantan_gaTrackingCode',             '');
        update_option('tantan_gaDontTrackAdmins',          $_POST['tantan_gaDontTrackAdmins']);
        update_option('tantan_gaShowReports',              $_POST['tantan_gaShowReports']);
        

        $message = 'Your settings have been saved. <a href="admin.php?page=tantan-reports/wordpress-reports/class-admin.php">View your reports &gt;</a>';
        if (!$_POST['tantan_gaSiteProfile']) {
            $error = "Please select a site profile.";
        }
    } else {
    }
    
    if ($_POST['tantan_gaEmail'] && $_POST['tantan_gaPassword']) {
        if (!$ga->login($_POST['tantan_gaEmail'], $_POST['tantan_gaPassword'])) {
            $error = "There was a problem logging into your Google Analytics account:<br /><br />".$ga->getError();
            update_option('tantan_gaSession', false);
            update_option('tantan_gaSessionTimeout', 0);
            update_option('tantan_gaTrackingCode', false);
            update_option('tantan_gaPassword', false);
            
        } else { // login ok
            $session = $ga->getSession();
            update_option('tantan_gaSession', $session);
            update_option('tantan_gaSessionTimeout', time());
            update_option('tantan_gaPassword', tantan_GACipher::encrypt($_POST['tantan_gaPassword']));
            
            $accountProfiles = $ga->getAccounts();
            foreach ($accountProfiles as $key => $account) {
                $accountProfiles[$key]['profiles'] = $ga->getSiteProfiles($account['id']);
            }

            update_option('tantan_gaProfiles', $accountProfiles);
        }
    }     
    
    
    
} elseif ($_POST['service'] == 'feedburner') {
    if ($_POST['action'] == 'save') {
        if (is_array($_POST['tantan_fbDelete'])) foreach ($_POST['tantan_fbDelete'] as $del) {
            unset($_POST['tantan_fbURI'][$del]);
        }
        if ($_POST['tantan_fbURINew']) {
            $_POST['tantan_fbURI'][] = $_POST['tantan_fbURINew'];
        }
        update_option('tantan_fbURI', $_POST['tantan_fbURI']);
        $message = 'Your settings have been saved. <a href="admin.php?page=tantan/wordpress-reports/class-admin.php">View your reports &gt;</a>';
    }
}

update_option('tantan_gaCacheTimeout', false);

$email                   = get_option('tantan_gaEmail');
$siteProfile             = get_option('tantan_gaSiteProfile');
$reports                 = get_option('tantan_gaReports');
$installTracking         = get_option('tantan_gaInstallTracking');
$installTrackingOutbound = get_option('tantan_gaInstallTrackingOutbound');
$tantan_gaDontTrackAdmins= get_option('tantan_gaDontTrackAdmins');
$tantan_gaShowReports    = get_option('tantan_gaShowReports');

$session                 = get_option('tantan_gaSession');
$sessionTimeout          = get_option('tantan_gaSessionTimeout');

$feedburnerURI           = get_option('tantan_fbURI');

$showReports = array(
    'visits' => 'Daily Visits',
    'pageviews' => 'Daily Pageviews',
    'avgpageviews' => 'Ave Pageviews per Visit',
    'inbound' => 'Inbound Sources',
    'gaininginbound' => 'Rising Sources',
    'outbound' => 'Outbound Links <small>(require\'s outbound link tracking)</small>',
    'popular' => 'Popular Content',
    'gainingcontent' => 'Rising Content',
    'fallingcontent' => 'Falling Content',
    'entry' => 'Entry Pages',
    'newreturn' => 'New and Returning',
);
if (!$tantan_gaShowReports['saved']) { // havnt saved preference yet
    $showAllReports = true;
}

if ($session) { // google session is available
    if ($ga->setSession($session)) {
        $accountProfiles = get_option('tantan_gaProfiles');

        if (!is_array($accountProfiles) || !$accountProfiles) {
            $accountProfiles = $ga->getAccounts();
            if (count($accountProfiles) > 0) foreach ($accountProfiles as $key => $account) {
                $accountProfiles[$key]['profiles'] = $ga->getSiteProfiles($account['id']);
            } else {
                $accountProfiles = array(array('id' => 0,
                    'name' => 'Default profile',
                    'profiles' => $ga->getSiteProfiles(''),
                    ));
            }

            update_option('tantan_gaProfiles', $accountProfiles);
        }
        
        if ($_POST['action'] == 'save' && $installTracking && $siteProfile) {
            foreach ($accountProfiles as $account) {
                if (array_key_exists($siteProfile, $account['profiles'])) {
                    break;
                }
            }
			
			if ($code = $ga->getTrackingCode($account['id'], $siteProfile)) {
				update_option('tantan_gaTrackingCode', $code);
			} else {
				//update_option('tantan_gaInstallTracking', false);
				//$installTracking = false;
				$error = "Unable to install the Google Analytics tracking code. Please make sure you have administrative access to the Analytics profile.";
			}
            
        }

    } else {
        $error = "Google Analytics Error: Please re-enter your Google Analytics login information.";
    }
}

if ($feedburnerURI) {
    if (is_array($feedburnerURI)) foreach ($feedburnerURI as $fbURI) {
        $data = $fb->getFeedData($fbURI);
        if ($data['error']) {
            $error = 'Feedburner Setup Error for '.$fbURI.': '.$data['error'];
        }
    }
}


if (!function_exists('curl_init')) {
    $error .= "You don't appear to have <a href='http://us3.php.net/manual/en/ref.curl.php'>libcurl</a> installed on your server. ".
    "You will need to have this PHP library installed on your server in order for this plugin to work. ".
    "Please see your server administrator for more information.";
}
?>

<style>
fieldset.options {
    clear:both;
    border:1px solid #ccc;
}
fieldset.options legend {
    font-family: Georgia,"Times New Roman",Times,serif;
    font-size: 22px;
}
</style>

<?php if ($error):?>
<div id="message" class="error fade"><p><strong><?php echo $error?></strong></p></div>
<?php elseif ($message):?>
<div id="message" class="updated fade"><p><strong><?php echo $message?></strong></p></div>
<?php endif;?>

<div class="wrap">
<h2>Reports Setup</h2>

<h3>General Notes</h3>
<?php if (is_object($TanTanVersionCheck)):

?>
<div style="width:200px; border:1px solid #ccc;padding:10px; float:right; margin:0 0 10px 10px;">
<strong>Plugin Updates:</strong><br />
<a href="plugins.php?page=tantan/version-check.php">Check for updates to this plugin &gt;</a>
</div>
<?php endif;?>
<p>
This plugin adds a <strong>Reports</strong> tab to your WordPress administration, which gives you a quick overview of what's going on with your site. 
This plugin retrieves any available data for your site from Google Analytics and Feedburner 
(and possibly some others in the future), and then formats that data into nice charts and graphs for you to look at.
</p>
<p>
As you might imagine, you <strong>will</strong> need to have either a <a href="http://www.feedburner.com">Feedburner</a> account
or a <a href="http://www.google.com/analytics/">Google Analytics</a> account for this plugin to be useful.
</p>

<p><strong>Supported Services:</strong> (You'll need at least one of these)
<ul>
    <li><a href="http://www.feedburner.com/">Feedburner</a> account, with the <strong>Awareness API</strong> turned on.</li>
    <li><a href="http://www.google.com/analytics/">Google Analytics</a> account.</li>
</ul>

<p><strong>Setup:</strong>
Just enter your Feedburner and Google Analytics details. Make sure the details you enter for each service is actually for this site.
</p>

<p style="border:1px solid red; padding:10px;"><strong>Technical Support:</strong>
If you are having trouble getting this plugin to work, please post your questions to this 
<a href="http://groups.google.com/group/tantannoodles">Google Group</a>. This will make things much easier to 
manage and keep track of different issues. Questions posted to the main
distribution page on tantannoodles.com will almost certainly not be answered. It's just too hard to manage!
<br>
<br>
<strong>Bug Reports and Feature Requests:</strong> If you are encountering a bug with the plugin, or have a feature suggestion, please
post them to the <a href="http://code.google.com/p/wordpress-reports/">Google Project page</a>. 
</p>

<div  style="float:right;width:250px;background:#eee;padding:10px;font-size:0.9em;">
If you find this plugin helpful, please consider donating a few dollars to help support this plugin's development. Thanks!

<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
<input type="hidden" name="cmd" value="_donations" />
<input type="hidden" name="business" value="joetan54@gmail.com" />
<input type="hidden" name="item_name" value="TanTanNoodles Plugin Donation" />
<input type="hidden" name="item_number" value="WordPress Reports" />
<input type="hidden" name="page_style" value="Primary" />

<input type="hidden" name="no_shipping" value="1" />
<input type="hidden" name="return" value="http://tantannoodles.com/donation-thankyou/" />
<input type="hidden" name="cancel_return" value="http://tantannoodles.com/" />
<input type="hidden" name="currency_code" value="USD" />
<input type="hidden" name="tax" value="0" />
<input type="hidden" name="cn" value="Message / Note" />
<input type="hidden" name="lc" value="US" />
<input type="hidden" name="bn" value="PP-DonationsBF" />
<div style="float:left;width:150px;padding-top:10px">
Amount: $<input type="text" name="amount" value="" style="width:50px;" /> <br />
</div>
<div style="float:right;width:100px">
<input type="image" src="https://www.paypal.com/en_US/i/btn/x-click-butcc-donate.gif" border="0" name="submit" alt="Make payments with PayPal - it's fast, free and secure!" />
</div>
<img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1" style="clear:both;" />
</form>
<br />
<strong><a href="http://www.dreamhost.com/r.cgi?156998" target="_blank">Switch to a better web host!</a></strong><br />
Signup for Dreamhost and use the coupon code <b>TANTAN50COUPON</b> to get a $50 discount.
</div>



<p>
Check for the latest updates to this plugin here: <br />
<a href="http://www.tantannoodles.com/toolkit/wordpress-reports/">http://www.tantannoodles.com/toolkit/wordpress-reports/</a>
</p>
<p>
<strong>RSS Updates:</strong> Subscribe to the <a href="http://feeds.feedburner.com/TanTanToolkit">TanTanToolkit feed</a> and get notified when there's an update to this plugin.
</p>

<p>
Please send any comments / suggestions / flames to:<br />
Joe Tan (<a href="mailto:joetan54@gmail.com">joetan54@gmail.com</a>)<br />
<a href="http://www.tantannoodles.com">tantannoodles.com</a>
</p>




<form method="post">
<input type="hidden" name="service" value="feedburner" />
<input type="hidden" name="action" value="save" />
<fieldset class="options">
<legend>Feedburner</legend>
<a href="http://www.feedburner.com">Feedburner</a> tells you approximately how many subscribers have requested and are reading your 
syndicated content.<br />

<table width="100%" cellspacing="2" cellpadding="5" class="editform"> 
<?php 
if ($feedburnerURI && !is_array($feedburnerURI)) {
    $feedburnerURI = array($feedburnerURI);
}
?>
<?php if (is_array($feedburnerURI)) foreach ($feedburnerURI as $k => $fbURI):?>
<tr> 
<th width="33%" scope="row">Feedburner URI:</th> 
<td>http://feeds.feedburner.com/<input type="text" name="tantan_fbURI[<?php echo $k?>]" value="<?php echo $fbURI;?>" />
&nbsp; <input type="checkbox" name="tantan_fbDelete[]" value="<?php echo $k?>" id="fb-<?php echo $k?>" /><label for="fb-<?php echo $k?>"> delete</label>
</td></tr>
<?php endforeach;?>
<tr> 
<th width="33%" scope="row">Add a Feedburner URI:</th> 
<td>http://feeds.feedburner.com/<input type="text" name="tantan_fbURINew" value="" />
</td></tr>

<tr><td>&nbsp;</td><td>
<small>Note: Ensure you have turned on <strong>Awareness API</strong> for your feeds in Feedburner</small>
</td>
</tr>
</table>
<p class="submit"> 
	<input type="submit" name="Submit" value="Save Feedburner Settings &raquo;" />
</p>
</fieldset>
</form>


<form method="post">
<input type="hidden" name="service" value="googleanalytics" />
<input type="hidden" name="action" value="save" />
<fieldset class="options">
<legend>Google Analytics</legend>
<a href="http://www.google.com/analytics/">Google Analytics</a> tells you how people
found your site, and how they interact with your site.<br/>

<table width="100%" cellspacing="2" cellpadding="5" class="editform"> 
<?php if ($ga->isLoggedIn()):?>
<input name="tantan_gaEmail" type="hidden" value="<?php echo $email?>" />
<tr> 
<th width="33%" scope="row">Google Analytics Email:</th> 
<td><?php echo $email?> <input type="submit" name="subaction" value="change account" /></td>
</tr>
<tr> 
<th width="33%" scope="row">Password:</th> 
<td>****************</td>
</tr>
<?php else:?>
<input type="hidden" name="subaction" value="login" />
<tr valign="top"> 
<th width="33%" scope="row">Google Analytics Email:</th> 
<td><input name="tantan_gaEmail" type="text" id="tantan_gaEmail" value="<?php echo $email?>" size="40" /></td> 
</tr> 
<tr valign="top"> 
<th width="33%" scope="row">Password:</th> 
<td><input name="tantan_gaPassword" type="password" id="tantan_gaPassword" value="" size="40" /><br />
<small>Note: Your Google Analytics password will be <?php if (constant('TANTAN_GA_ENCRYPT_PWD')): ?><strong>encrypted</strong> and <?php endif;?> <strong>saved</strong> in order to authenticate with and retrieve data from Google Analytics.</small></td> 
</tr> 
<?php endif;?>
</table>

<?php if (!$ga->isLoggedIn()):?>
<p class="submit"> 
	<input type="submit" name="Submit" value="Continue &raquo;" />
</p>
<?php else:?>

<table width="100%" cellspacing="2" cellpadding="5" class="editform"> 
<tr valign="top"> 
<th width="33%" scope="row">Site Profile:</th> 
<td><select size="1" name="tantan_gaSiteProfile">
<option value="">Select profile...</option>
<?php foreach ($accountProfiles as $account):?>
<optgroup label="<?php echo $account['name']; ?>">
    <?php foreach($account['profiles'] as $profile): ?>
        <option value="<?php echo $profile['id']?>" <?php if ($profile['id'] == $siteProfile):?>selected="selected"<?php endif;?>>&nbsp;&nbsp;<?php echo $profile['name']?></option>
    <?php endforeach; ?>
</optgroup>
<?php endforeach;?>
</select> <input type="submit" name="subaction" value="refresh" /><br>
<small>Select the Google Analytics site profile used for this site.</small>
</td> 
</tr> 

<tr valign="top"> 
<th width="33%" scope="row" valign="top">Show Reports:</th> 
<td><table width="100%" cellspacing="0" cellpadding="0"><tr><td valign="top">
<input type="hidden" name="tantan_gaShowReports[saved]" value="1" />
<?php $count = count($showReports);
$midpoint = floor($count / 2);
$i = 1;
?>
<?php foreach ($showReports as $rid => $report):?>
<input type="checkbox" name="tantan_gaShowReports[<?php echo $rid?>]" value="1" id="sr-<?php echo $rid?>" <?php echo ($showAllReports || $tantan_gaShowReports[$rid]? 'checked="checked"' : '')?>/><label for="sr-<?php echo $rid?>"> <?php echo $report?></label><br />
<?php  if ($i++ == $midpoint):?></td><td valign="top"><?php  endif;?>
<?php endforeach;?>
</td></tr></table>
</td>
</tr>

<tr valign="top"> 
<th width="33%" scope="row">Tracking Code:</th> 
<td><input type="checkbox" name="tantan_gaInstallTracking" id="tantan_gaInstallTracking" value="1" <?php if ($installTracking):?>checked="checked"<?php endif;?> onclick="if (this.checked) {document.getElementById('tantan_gaTrackingOutbound').style.visibility='visible'} else {document.getElementById('tantan_gaTrackingOutbound').style.visibility='hidden'} "/> 
<label for="tantan_gaInstallTracking">Install the Google Analytics tracking code for me.</label><br>
<small>Check this if you have not already installed Google Analytics onto your site.</small>
<div id="tantan_gaTrackingOutbound" style="margin:10px 0 0 30px;<?php if (!$installTracking):?>visibility:hidden<?php endif;?>">
    <input type="checkbox" name="tantan_gaInstallTrackingOutbound" id="tantan_gaInstallTrackingOutbound" value="1" <?php if ($installTrackingOutbound):?>checked="checked"<?php endif;?> /> 
    <label for="tantan_gaInstallTrackingOutbound">Track outbound links.</label><br>
    <small>Use Google Analytics to track links to other external websites.</small><br>
   <br>
    <input type="checkbox" name="tantan_gaDontTrackAdmins" id="tantan_gaDontTrackAdmins" value="1" <?php if ($tantan_gaDontTrackAdmins):?>checked="checked"<?php endif;?> /> 
    <label for="tantan_gaDontTrackAdmins">Don't track administrator users.</label><br>
    <small>Turns off the tracking code for logged in administrator users.</small><br>

</div>
</td>
</tr>

</table>

<p class="submit"> 
	<input type="submit" name="Submit" value="Save Google Analytics Settings &raquo;" />
</p>


<?php endif;?>

</fieldset>
</form>

</div>