<?php
/*
Plugin Name: WordPress Reports
Plugin URI: http://tantannoodles.com/toolkit/wordpress-reports/
Description: Generates reports from Google Analytics and Feedburner data
Version: 0.89
Author: Joe Tan
Author URI: http://tantannoodles.com/

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

Release Page:
http://tantannoodles.com/toolkit/wordpress-reports/

Project Page:
http://code.google.com/p/wordpress-reports/

Changlog:
http://code.google.com/p/wordpress-reports/wiki/ChangeLog

$Revision: 113 $
$Date: 2008-10-22 14:33:07 -0400 (Wed, 22 Oct 2008) $

*/

if (!defined('TANTAN_GA_ENCRYPT_PWD')) define('TANTAN_GA_ENCRYPT_PWD', true);

// auto update notification
if (!defined('TANTAN_AUTOUPDATE_NOTIFY')) define('TANTAN_AUTOUPDATE_NOTIFY', true);


if (ereg('/wp-admin/', $_SERVER['REQUEST_URI'])) { // just load in admin
    require_once(dirname(__FILE__).'/wordpress-reports/class-admin.php');
    $TanTanReportsPlugin =& new TanTanReportsPlugin();
} elseif (get_option('tantan_gaInstallTracking')) {
    require_once(dirname(__FILE__).'/wordpress-reports/class-ga-tracker.php');
    $TanTanReportsGATracker =& new TanTanReportsGATracker();
}
?>