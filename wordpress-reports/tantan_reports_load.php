<?php
/*

$Revision: 102 $
$Date: 2008-01-10 17:07:00 -0500 (Thu, 10 Jan 2008) $
$Author: joetan54 $
$URL: https://wordpress-reports.googlecode.com/svn/trunk/tantan-reports/wordpress-reports/tantan_reports_load.php $

*/
$tmpPath = '/../../..';
if (!file_exists(dirname(__FILE__).$tmpPath.'/wp-config.php')) {
$tmpPath = '/../../../..';
    if (!file_exists(dirname(__FILE__).$tmpPath.'/wp-config.php')) {
        echo "Error: wp-config.php not found";
        exit;
    }
}
if (!isset($_GET['module']) ) {
    exit;
}
require_once(dirname(__FILE__).$tmpPath.'/wp-config.php');
//require_once(dirname(__FILE__).$tmpPath.'/wp-admin/admin-functions.php');
//require_once(dirname(__FILE__).$tmpPath.'/wp-admin/admin-db.php');

get_currentuserinfo();

if ( !current_user_can('manage_categories') )
	die('-1');

function get_out_now() { exit; }
add_action('shutdown', 'get_out_now', -1);

require_once(dirname(__FILE__).'/tantan_reports_graphs.php');
function getCache($cacheID) {return false;
    $cacheTimeout = get_option('tantan_gaCacheT_'.$cacheID);
    if ($cacheTimeout && (time() - $cacheTimeout < 3600)) {
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
?>
<html>
<head>
<style type="text/css">
@import url(tantan_reports.css);
<?php if (!$_GET['load']):?>
body {
    background:white url(loading.gif) no-repeat center;
    height:100%;
}
<?php endif; ?>
</style>
<?php if (!$_GET['load']):
$url = "tantan_reports_load.php?load=1&module=".$_GET['module']."&report=".$_GET['report'];
?>
<script type="text/javascript">
function showReport() {
window.location.replace('<?php echo $url;?>');
}
window.onload = function() {
    setTimeout('showReport()', 300);
}
</script>
<?php endif;?>

</head>
<body>
<?php
if ($_GET['load']) {
$start      = date('Ymd', time() - 604800);  // 7 days
$startOlder = date('Ymd', time() - 1209600); // 14 days
$stop       = date('Ymd', time() - 86400);   // yesterday
if ($_GET['module'] == 'analytics') {
    require_once(dirname(__FILE__).'/lib.googleanalytics.php');
    $siteProfile    = get_option('tantan_gaSiteProfile');
    $session        = get_option('tantan_gaSession');
    $ga = new tantan_GoogleAnalytics();
    $ga->setSession($session);
    
    switch ($_GET['report']) {
        case 'gainingcontent':
            if (!($data = getCache('content'))) {
                $data = updateCache('content', $ga->getReport($siteProfile, $start, $stop, 'content'));
            }
            if (is_array($data['Table']['records'])) usort($data['Table']['records'], create_function('$a,$b', 'if ($a[\'% Change\'][\'Unique Pageviews\'] == $b[\'% Change\'][\'Unique Pageviews\']) return 0; return ($a[\'% Change\'][\'Unique Pageviews\'] < $b[\'% Change\'][\'Unique Pageviews\']) ? 1 : -1;'));
            $gainMax = 0;
            for ($i=0;$i<5;$i++) {
                $gainMax = max($data['Table']['records'][$i]['Unique Pageviews'], $gainMax);
            }
            printHorizGraph('Rising Content', $data['Table']['records'], $gainMax, 'Unique Pageviews', 'URL', 'Content with the most net gain of unique views');
        break;
        case 'fallingcontent':
            if (!($data = getCache('content'))) {
                $data = updateCache('content', $ga->getReport($siteProfile, $start, $stop, 'content'));
            }
            if (is_array($data['Table']['records'])) usort($data['Table']['records'], create_function('$a,$b', 'if ($a[\'% Change\'][\'Unique Pageviews\'] == $b[\'% Change\'][\'Unique Pageviews\']) return 0; return ($a[\'% Change\'][\'Unique Pageviews\'] < $b[\'% Change\'][\'Unique Pageviews\']) ? -1 : 1;'));
            $gainMax = 0;
            for ($i=0;$i<5;$i++) {
                $gainMax = max($data['Table']['records'][$i]['Unique Pageviews'], $gainMax);
            }
            printHorizGraph('Falling Content', $data['Table']['records'], $gainMax, 'Unique Pageviews', 'URL', 'Content with the most net loss of unique views');
        
        break;
        case 'pageviews':
	        if (!($data = getCache('pageviews'))) {
	            $data = updateCache('pageviews', $ga->getReport($siteProfile, $start, $stop, 'pageviews'));
	        }
	        printVertGraph('Daily Pageviews', $data['Graph']['records'], $data['Graph']['MaxPageviews'], 'Date', 'Pageviews', 'How many pages were viewed');
		break;
        case 'visits':
			if (!($data = getCache('visits'))) {
		        $data = updateCache('visits', $ga->getReport($siteProfile, $start, $stop, 'visits'));
		    }
		    printVertGraph('Daily Visits', $data['Graph']['records'], $data['Graph']['MaxVisits'], 'Date', 'Visits', 'Number of people that visited your site');
		break;
        case 'avgpageviews':
            if (!($data = getCache('avgpageviews'))) {
                $data = updateCache('avgpageviews', $ga->getReport($siteProfile, $start, $stop, 'avgpageviews'));
            }
            printVertGraph('Avg Pageviews per Visit', $data['Graph']['records'], $data['Graph']['MaxPages/Visit'], 'Date', 'Pages/Visit', 'Average number of pageviews per visit');
        break;
        case 'gaininginbound':
        if (!($data = getCache('referals'))) {
            $data = updateCache('referals', $ga->getReport($siteProfile, $start, $stop, 'referals'));
        }
        if (is_array($data['Table']['records'])) usort($data['Table']['records'], create_function('$a,$b', 'if ($a[\'% Change\'][\'Visits\'] == $b[\'% Change\'][\'Visits\']) return 0; return ($a[\'% Change\'][\'Visits\'] < $b[\'% Change\'][\'Visits\']) ? 1 : -1;'));

        $gainMax = 0;
        for ($i=0;$i<5;$i++) {
            $gainMax = max($data['Table']['records'][$i]['Visits'], $gainMax);
        }
        printHorizGraph('Rising Sources', $data['Table']['records'], $gainMax, 'Visits', 'Source', 'Sources with the most net gain referrals');
        break;
        case 'inbound':
            if (!($data = getCache('referals'))) {
                $data = updateCache('referals', $ga->getReport($siteProfile, $start, $stop, 'referals'));
            }
            printHorizGraph('Inbound Sources', $data['Table']['records'], $data['Table']['MaxVisits'], 'Visits', 'Source', 'Where did people find your site');
        break;
        case 'outbound':
            if (!($data = getCache('outbound'))) {
                $data = updateCache('outbound', $ga->getReport($siteProfile, $start, $stop, 'outbound'));
            }
            printHorizGraph('Outbound Links', $data['Table']['records'], $data['Table']['MaxUnique Pageviews'], 'Unique Pageviews', 'URL', 'External sites people went to from your site');
        break;
        case 'popular':
            if (!($data = getCache('content'))) {
                $data = updateCache('content', $ga->getReport($siteProfile, $start, $stop, 'content'));
            }
            printHorizGraph('Popular Content', $data['Table']['records'], $data['Table']['MaxUnique Pageviews'], 'Unique Pageviews', 'URL', 'Most popular content on your site');
        break;
        case 'entry':
            if (!($data = getCache('entrance'))) {
                $data = updateCache('entrance', $ga->getReport($siteProfile, $start, $stop, 'entrance'));
            }
            printHorizGraph('Entry Pages', $data['Table']['records'], $data['Table']['MaxEntrances'], 'Entrances', 'URL','First page people visit on your site');
        break;
        case 'newreturn':
            if (!($data = getCache('newreturn'))) {
                $data = updateCache('newreturn', $ga->getReport($siteProfile, $start, $stop, 'newreturn'));
            }
            $totalVisits = ((int) $data['Table']['records'][0]['Visits'] + (int) $data['Table']['records'][1]['Visits']);
            if ($totalVisits > 0) {
                $newVisits = round(($data['Table']['records'][0]['Visits'] / $totalVisits) * 100, 2);
                $retVisits = round(($data['Table']['records'][1]['Visits'] / $totalVisits) * 100, 2);
            } else {
                $newVisits = 0;
                $retVisits = 0;
            }
            printFiftyFiftyGraph('New &amp; Returning', $newVisits, $retVisits, 'New', 'Returning', 'Percentage of new and returning visitors');
        break;
        case '7dayreport':
	        if (!($data = getCache('visits'))) {
	            $dataVisits = updateCache('visits-older', $ga->getReport($siteProfile, $startOlder, $stop, 'visits'));
	        }
	        if (!($data = getCache('pageviews'))) {
	            $dataPageviews = updateCache('pageviews-older', $ga->getReport($siteProfile, $startOlder, $stop, 'pageviews'));
	        }

	        $split = strtotime($start);
            
	        $visitsNow = 0;
            $visitsPast = 0;
            if (is_array($dataVisits['Graph']['records'])) foreach ($dataVisits['Graph']['records'] as $visit) {
                if ($visit['UnixTime'] < $split) {
                    if ($visit['Visits']) $visitsPast += $visit['Visits'];
                } else {
                    if ($visit['Visits']) $visitsNow += $visit['Visits'];
                }
                //$visitsNow += $visit['Visits'];
                //if ($visit['% Change']) $visitsPast += $visit['Visits']/$visit['% Change'];
            }
            if ($visitsPast > 0) {
                $visitsPercent = round(($visitsNow / $visitsPast),2);
            } else {
                $visitsPercent = 1;
            }
            if ($visitsPercent >= 1) {
                $visitsPercentLabel = '<span class="plus">(+'.(($visitsPercent * 100)-100).'%)</span> ';
            } else {
                $visitsPercentLabel = '<span class="minus">('.(($visitsPercent * 100)-100).'%)</span> ';
            }
            
            $viewsNow = 0;
            $viewsPast = 0;
            if (is_array($dataPageviews['Graph']['records'])) foreach ($dataPageviews['Graph']['records'] as $view) {
                if ($view['UnixTime'] < $split) {
                    if ($view['Pageviews']) $viewsPast += $view['Pageviews'];
                } else {
                    if ($view['Pageviews']) $viewsNow += $view['Pageviews'];
                }
                //$viewsNow += $view['Pageviews'];
                //if ($view['% Change']) $viewsPast += $view['Pageviews']/$view['% Change'];
            }
            if ($viewsPast > 0) {
                $viewsPercent = round($viewsNow / $viewsPast, 2);
            } else {
                $viewsPercent = 1;
            }
            if ($viewsPercent >= 1) {
                $viewsPercentLabel = '<span class="plus">(+'.(($viewsPercent * 100)-100).'%)</span> ';
            } else {
                $viewsPercentLabel = '<span class="minus">('.(($viewsPercent * 100)-100).'%)</span> ';
            }
    		if ($visitsNow <= 0 && $viewsNow <= 0) {
				echo '<p style="font-size:10px">It looks like either your Google Analytics profile is new and doesn\'t have any reporting data yet, or your session has timed out. If you know you have reporting data, click the <strong>change account</strong> button in the Setup tab to redo the configuration.';
			} else {
	            echo '<p>During the past 7 days, your site received <strong>'.number_format($visitsNow).'</strong> visitors ' .$visitsPercentLabel. ' and <strong>' .number_format($viewsNow). '</strong> pageviews ' .$viewsPercentLabel. '.</p>';
			}

        break;
    }
} elseif ($_GET['module'] == 'feedburner') {
    require_once(dirname(__FILE__).'/lib.feedburner.php');
    $fb    = new tantan_Feedburner();
    $fbURI = get_option('tantan_fbURI');
    $data = $fb->getFeedData($_GET['report'], $start, $stop);
    printVertGraph('Circulation <small>- '.$_GET['report'].'</small>', $data['records'], $data['MaxCirculation'], 'Date Label', 'Circulation', 'Number of subscribers to your content');
} 
} // loading

?>

</body>
</html>