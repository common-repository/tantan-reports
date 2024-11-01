<?php
/*

$Revision: 102 $
$Date: 2008-01-10 17:07:00 -0500 (Thu, 10 Jan 2008) $
$Author: joetan54 $
$URL: https://wordpress-reports.googlecode.com/svn/trunk/tantan-reports/wordpress-reports/widget-mostactive.php $

*/
class TanTanReportsMostActive {
    
    function TanTanReportsMostActive() {
        if (function_exists('register_sidebar_widget')) { 
            register_sidebar_widget('Most Active Content', array(&$this, 'display'));
            register_widget_control('Most Active Content', array(&$this, 'control'));
        }
    }
    
    function control() {
        $options = get_option('tantan_reports_mostactive');
        if ( $_POST['tantan-reports-mostactive'] && is_array($_POST['tantan-reports'])) {
            $options = array();
            foreach ($_POST['tantan-reports'] as $key => $value) {
                $options[$key] = strip_tags(stripslashes($value));
            }
            update_option('tantan_reports_mostactive', $options);
		}
        if (!isset($options['title'])) $options['title'] = "Top Posts"; 
        if (!isset($options['pattern'])) $options['pattern'] = "(.*)$"; 
        if (!isset($options['max'])) $options['max'] = 10; 
       
        include(dirname(__FILE__).'/widget-mostactive-control.html');
    }
    
    function display($args=false) {
        $options = get_option('tantan_reports_mostactive');
        if (is_array($options)) extract($options);
        if (is_array($args)) extract($args);
        elseif (is_string($args)) parse_str($args);
            
        if (!isset($title)) $title = 'Top Posts';
        if (!isset($pattern)) $pattern = "(.*)$"; 
        if (!isset($max)) $max = 10; 
        if (!($popular = $this->getCache('content'))) {
            $sessionTimeout = get_option('tantan_gaSessionTimeout');
            
            require_once(dirname(__FILE__).'/lib.googleanalytics.php');
            $ga = new tantan_GoogleAnalytics();
            
            if ((time() - $sessionTimeout) > 86400) {
                $pw = get_option('tantan_gaPassword');
                if ($pw && $ga->login(get_option('tantan_gaEmail'), trim(@mcrypt_decrypt(MCRYPT_RIJNDAEL_256, DB_PASSWORD, $pw, MCRYPT_MODE_ECB)))) {
                    $session = $ga->getSession();
                    update_option('tantan_gaSession', $session);
                    update_option('tantan_gaSessionTimeout', time());
                } else {
                    echo "<!-- Unable to authenticate with Google Analytics -->\n";
                    return; // session reset failed!
                }
            } else {
                $ga->setSession(get_option('tantan_gaSession'));
            }
            $popular = $this->updateCache('content', $ga->getReport(get_option('tantan_gaSiteProfile'), date('Ymd', time() - 604800), date('Ymd', time() - 86400), 'content'));//1349430
        }
        if (file_exists(TEMPLATEPATH.'/wordpress-reports-widget-mostactive.html')) {
            include(TEMPLATEPATH.'/wordpress-reports-widget-mostactive.html');
        } else {
            include(dirname(__FILE__).'/widget-mostactive.html');
        }
    }
    
    // borrowed from tantan_reports_load.php
    function getCache($cacheID) {
        $cacheTimeout = get_option('tantan_gaCacheT_'.$cacheID);
        if ($cacheTimeout && (time() - $cacheTimeout < 86400)) {
            return get_option('tantan_gaCacheD_'.$cacheID);
        } else {
            return false;
        }    
    }
    function updateCache($cacheID, $value) {
        update_option('tantan_gaCacheT_'.$cacheID, time());
        update_option('tantan_gaCacheD_'.$cacheID, $value);
        return $value;
    }    
}
?>
