<?php
/*

$Revision: 102 $
$Date: 2008-01-10 17:07:00 -0500 (Thu, 10 Jan 2008) $
$Author: joetan54 $
$URL: https://wordpress-reports.googlecode.com/svn/trunk/tantan-reports/wordpress-reports/tantan_reports_graphs.php $

*/
function formatBigNum($num, $log) {
    if ($log >= 3) {
        return number_format(round($num / pow(10, $log), 2), 2);
    } elseif (ereg('.', $num)) {
		return round($num, 2);
	} else {
        return $num;
    }
}
function formatDate($str) {
    return ereg_replace(" ", "<br />", $str);
}

function printVertGraph($title, $records, $max, $x, $y, $msg) {
//$max = $max * 3154;
$cols = count($records);
$log = floor(log($max) / log(10));
?>
<fieldset class="graph">
<legend><?php echo $title?></legend>
<table class="vertGraph" cellspacing="0" cellpadding="0">
<thead>
    <td class="label" colspan="<?php echo $cols?>"><?php echo $msg?>
    <?php if ($log >= 3):?>
    (in <?php echo number_format(pow(10, $log));?>s)
    <?php endif;?>
    </td>
</thead>
<tbody><tr>
<?php if (is_array($records)) foreach ($records as $k=>$rec):
//$rec[$y] = $rec[$y] * 3154;
?>
    <?php if (!$max || ($max <= 1)) { $height = 10; }
    else { $height = max(floor(($rec[$y] / $max)*95),10); }?>
    <td class="col col<?php echo $k?>"><div class="bwrap"><div class="bar" style="height:<?php echo $height?>%;"><span title="<?php echo (($log >= 3) ? 'actual: '.$rec[$y] : '');?>"><?php echo formatBigNum($rec[$y], $log); ?></span></div></div></td>
<?php endforeach;?>
</tr></tbody>
<tfoot>
<?php if (is_array($records)) foreach ($records as $k=>$rec):?>
<th class="label"><?php echo formatDate($rec[$x])?></th>
<?php endforeach;?>
</tfoot>
</table>
</fieldset>
<?php
} // printVertGraph

function printHorizGraph($title, $records, $max, $x, $y, $msg) {
?>
<fieldset class="graph">
<legend><?php echo $title;?></legend>
<table class="horizGraph" cellspacing="0" cellpadding="0">
<thead>
<tr><td colspan="2" class="label"><?php echo $msg?></td></tr>
<tr><th align="left" class="label"><?php echo $y?></th>
    <th align="right" width="100%" class="label"><?php echo $x?></th>
    </tr>
</thead>
<tbody>
<?php $i=0;?>
<?php if (is_array($records)) foreach ($records as $k=>$rec):?>
<?php
/*
    if (isset($rec[$x . ' (2)'])) $cmpKey = $x . ' (2)';
    else $cmpKey = $x . '(2)';
    if ($rec[$cmpKey] > 0) {
        $p = round(($rec[$x] / $rec[$cmpKey]),2);
        if ($p >= 1) {
            $percent = '<span class="plus">+'.(($p-1) * 100).'%</span> '; // wtf -100?
        } else {
            $percent = '<span class="minus">-'.(100 - ($p * 100)).'%</span> ';
        }
    } else {
        $percent = '<span class="plus">new!</span>';
    }
*/
	if (is_array($rec['% Change']) && $rec['% Change'][$x]) {
		if ($rec['% Change'][$x] > 0) {
			$percent = '<span class="plus">+'.round($rec['% Change'][$x]*100).'%</span>';
		} else {
			$percent = '<span class="minus">'.round($rec['% Change'][$x]*100).'%</span>';
		}
	} elseif ($rec['% Change']) {
		if ($rec['% Change'] > 0) {
			$percent = '<span class="plus">+'.round($rec['% Change']*100).'%</span>';
		} else {
			$percent = '<span class="minus">'.round($rec['% Change']*100).'%</span>';
		}
	}
$max = $max * 1.05;
?>
<tr class="row">
   <td class="start"><div class="bar" title="<?php echo $rec[$y]?>"><span><?php echo $rec[$y]?></span></div></td>
   <td class="end"><div class="bar" style="width:<?php echo ($max > 0 ? floor(($rec[$x]/$max) * 95) : '10')?>%"><span title="<?php echo (($rec[$cmpKey]>=0) ? 'was: '.$rec[$cmpKey]: '')?>"><?php echo $rec[$x].$percent?></span></div></td>
</tr>
<?php
if ($i++ >= 4) break;
endforeach;?>
</tbody>
</table>
</fieldset>
<?php
} // printHorizGraph


function printFiftyFiftyGraph($title, $left, $right, $leftLabel, $rightLabel, $msg) {
?>
<fieldset class="graph graphwide">
<legend><?php echo $title;?></legend>
<table class="horizGraph" cellspacing="0" cellpadding="5">
<thead>
    <tr><td colspan="2" class="label"><?php echo $msg;?></td></tr>
    <tr><th width="<?php echo floor($left)?>%" align="right" class="label"><?php echo $leftLabel;?></th>
        <th width="<?php echo floor($right)?>%" align="left" class="label"><?php echo $rightLabel;?></th></tr>
</thead>
<tbody>
    <tr><td width="<?php echo floor($left)?>%" class="newVisits"><?php echo $left?>%</td>
    <td width="<?php echo floor($right)?>%" class="retVisits"><?php echo $right?>%</td></tr>
</tbody>
</table>
</fieldset>
<?php    
} // printFiftyFiftyGraph
?>