<?php
/*

$Revision: 102 $
$Date: 2008-01-10 17:07:00 -0500 (Thu, 10 Jan 2008) $
$Author: joetan54 $
$URL: https://wordpress-reports.googlecode.com/svn/trunk/tantan-reports/wordpress-reports/class-ga-tracker.php $

*/
class TanTanReportsGATracker {
    function TanTanReportsGATracker() {
        add_action('wp_footer', array(&$this, 'install_tracking'));
    }
    
    function install_tracking() {
    global $userdata, $table_prefix;
        $dontTrack = false;
        if (get_option('tantan_gaDontTrackAdmins')) {
            $caps = get_usermeta( $userdata->ID, $table_prefix . 'capabilities');
            if (isset($caps['administrator']) && $caps['administrator']) {
                $dontTrack = true;
            }
        }
        if (!$dontTrack) {
            print get_option('tantan_gaTrackingCode');
            if (get_option('tantan_gaInstallTrackingOutbound')) {
                $this->install_tracking_outbound();
            } 
        }
    }
    
    function install_tracking_outbound() {
    ?>
    <script type="text/javascript">
    //<![CDATA[ 
    if (document.getElementsByTagName) {
        var ahrefs = document.getElementsByTagName('a');
        for (var i=0; i<ahrefs.length;i++) {
            if (ahrefs[i].href.indexOf('<?php bloginfo('url')?>') == -1 && !ahrefs[i].onclick) {
                ahrefs[i].onclick = function () { var track = this.href + ''; urchinTracker ('/outgoing/'+track.substring(7)); }
            }
        }
    }
    //]]>
    </script>
    <?php
    }
}
?>