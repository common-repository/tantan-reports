<?php
/*
// disabled widget plugin for now, seems to be conflicting with WordPress.org's plugin distribution system

$Revision: 110 $
$Date: 2008-07-15 11:13:45 -0400 (Tue, 15 Jul 2008) $
$Author: joetan54 $
$URL: https://wordpress-reports.googlecode.com/svn/trunk/tantan-reports/tantan_reports_widget.php $

*/

global $TanTanReportsMostActive;
require_once(dirname(__FILE__)."/wordpress-reports/widget-mostactive.php");
add_action('plugins_loaded', create_function('', 'global $TanTanReportsMostActive; $TanTanReportsMostActive =& new TanTanReportsMostActive();'));
?>