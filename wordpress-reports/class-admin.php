<?php
/*

$Revision: 111 $
$Date: 2008-07-21 23:34:25 -0400 (Mon, 21 Jul 2008) $
$Author: joetan54 $
$URL: https://wordpress-reports.googlecode.com/svn/trunk/tantan-reports/wordpress-reports/class-admin.php $

*/
class TanTanReportsPlugin {
    function _module($m, $r, $s=0) {
        return array('module' => $m, 'report' => $r, 'sort' => $s);
    }
    function show_reports() {
        $session        = get_option('tantan_gaSession');
        $sessionTimeout = get_option('tantan_gaSessionTimeout');
        $siteProfile    = get_option('tantan_gaSiteProfile');
        $fbURI          = get_option('tantan_fbURI');
        $gaShowReports  = get_option('tantan_gaShowReports');
        
        if (!$gaShowReports['saved']) { // havnt saved preference yet
            $showAllReports = true;
        }
        if (!$siteProfile) { // don't show GA reports if there is no registered profile
            $showAllReports = false;
        }
        if (!$siteProfile && !$fbURI) {
            $html = '<div class="wrap"><h2>Reports</h2>'.
                '<p>Reporting has not yet been configured for this site. </p>'.
                '<a href="admin.php?page=tantan-reports/wordpress-reports/tantan_reports_config.php">Enter Setup &gt;</a>'.
                '</div>';
            print $html;
            return;
        } else {
            if ($siteProfile && ((time() - $sessionTimeout) > 86400)) {
                $pw = get_option('tantan_gaPassword');
                require_once(dirname(__FILE__).'/lib.googleanalytics.php');
                $ga = new tantan_GoogleAnalytics();
                if ($pw) $pw = tantan_GACipher::decrypt($pw);
                if ($pw && $ga->login(get_option('tantan_gaEmail'), $pw)) {
                    $session = $ga->getSession();
                    update_option('tantan_gaSession', $session);
                    update_option('tantan_gaSessionTimeout', time());
                    //echo 'updated login session!';
                } else {
                    $siteProfile = false;
                    update_option('tantan_gaSession', '');
                    update_option('tantan_gaPassword', '');
                    update_option('tantan_gaSessionTimeout', 0);
                    
                }
            }
            if ($siteProfile) { // found analytics account
                $modules[] = $this->_module('analytics', '7dayreport');
                
                if ($gaShowReports['visits'])       $modules[] = $this->_module('analytics', 'visits');
                if ($gaShowReports['pageviews'])    $modules[] = $this->_module('analytics', 'pageviews');
                if ($gaShowReports['avgpageviews']) $modules[] = $this->_module('analytics', 'avgpageviews');
                if ($gaShowReports['inbound'])      $modules[] = $this->_module('analytics', 'inbound');
                if ($gaShowReports['gaininginbound'])      $modules[] = $this->_module('analytics', 'gaininginbound');
                if (get_option('tantan_gaInstallTracking') && get_option('tantan_gaInstallTrackingOutbound'))
                    if ($gaShowReports['outbound'])     $modules[] = $this->_module('analytics', 'outbound');
                if ($gaShowReports['popular'])      $modules[] = $this->_module('analytics', 'popular');
                if ($gaShowReports['gainingcontent']) $modules[] = $this->_module('analytics', 'gainingcontent');
                if ($gaShowReports['fallingcontent']) $modules[] = $this->_module('analytics', 'fallingcontent');
                
                if ($gaShowReports['entry'])        $modules[] = $this->_module('analytics', 'entry');
                if ($gaShowReports['newreturn'])    $modules[] = $this->_module('analytics', 'newreturn');
                
            }
            if ($fbURI) { // load feed burner reports
                if (is_array($fbURI)) {
                    foreach ($fbURI as $uri) {
                        $modules[] = $this->_module('feedburner', $uri);
                    }
                } else {
                    $modules[] = $this->_module('feedburner', $fbURI);
                }
            }
            include(dirname(__FILE__).'/tantan_reports.html');
        }
    }

    function version_check() {
        global $TanTanVersionCheck;
        if (is_object($TanTanVersionCheck)) {
            $data = get_plugin_data(dirname(__FILE__).'/../tantan_reports.php');
            $TanTanVersionCheck->versionCheck(145, $data['Version']);
        }
    }
    
    function admin_options() {
        global $userdata, $table_prefix;
        add_menu_page('Reports', 'Reports', 2, __FILE__, array(&$this, 'show_reports'));
        $caps = get_usermeta( $userdata->ID, $table_prefix . 'capabilities');
        if (isset($caps['administrator']) && $caps['administrator']) {
    	   add_submenu_page(__FILE__, 'Setup', 'Setup', 10, 'tantan-reports/wordpress-reports/tantan_reports_config.php');
        }
        $this->version_check();
    }
    function activate() {
        wp_redirect('plugins.php?tantanActivate=wordpress-reports');
        exit;
    }
    function showConfigNotice() {
        add_action('admin_notices', create_function('', 'echo \'<div id="message" class="updated fade"><p>The WordPress Reports plugin has been <strong>activated</strong>. <a href="admin.php?page=tantan/wordpress-reports/tantan_reports_config.php">Configure the plugin &gt;</a></p></div>\';'));
    }
    function TanTanReportsPlugin() {
        add_action('admin_menu', array(&$this, 'admin_options'));
        add_action('activate_tantan/tantan_reports.php', array(&$this, 'activate'));
        if ($_GET['tantanActivate'] == 'wordpress-reports') {
            $this->showConfigNotice();
        }
    }
}

function tantan_reports_autoupdate($old, $new) {
	remove_action( 'add_option_update_plugins', 'tantan_reports_autoupdate', 10, 2);
	remove_action( 'update_option_update_plugins', 'tantan_reports_autoupdate', 10, 2);
	if (is_string($new)) $new = @unserialize($new);
	if (!is_object($new))$new = new stdClass();
	
	$http_request  = "GET /tantan-reports.serialized HTTP/1.0\r\n";
	$http_request .= "Host: updates.tantannoodles.com\r\n";
	$http_request .= 'User-Agent: WordPress/' . $wp_version . '; ' . get_bloginfo('url') . "\r\n";
	$http_request .= "\r\n";
	$http_request .= $request;
	$response = '';
	if( false != ( $fs = @fsockopen( 'updates.tantannoodles.com', 80, $errno, $errstr, 3) ) && is_resource($fs) ) {
		fwrite($fs, $http_request);
		while ( !feof($fs) ) $response .= fgets($fs, 1160); // One TCP-IP packet
		fclose($fs);
		$response = explode("\r\n\r\n", $response, 2);
	}
	$update = unserialize( $response[1] );
	if (is_object($update)) {
		$thisPlugin = get_plugin_data(dirname(__FILE__).'/../tantan_reports.php');
		if (version_compare($thisPlugin['Version'], $update->new_version, '<')) {
			$new->response['tantan-reports/tantan_reports.php'] = $update;
			update_option('update_plugins', $new);
		}
	}
}
function tantan_reports_after_plugin_row($file) {
    if (strpos('tantan-reports/tantan_reports.php',$file)!==false ) {
	    $current = get_option( 'update_plugins' );
	    if ( !isset( $current->response[ $file ] ) ) return false;
	    $r = $current->response[ $file ];
	    echo "<tr><td colspan='5' style='text-align:center;'>";
		echo "<a href='http://tantannoodles.com/category/toolkit/wordpress-reports/'>View the latest updates for this plugin &gt;</a>";
		echo "</td></tr>";
		
	}
}
if (TANTAN_AUTOUPDATE_NOTIFY && version_compare(get_bloginfo('version'), '2.3', '>=')) {
	//add_action( 'add_option_update_plugins', 'tantan_reports_autoupdate', 10, 2);
	//add_action( 'update_option_update_plugins', 'tantan_reports_autoupdate', 10, 2);
	add_action( 'after_plugin_row', 'tantan_reports_after_plugin_row' );

}

?>