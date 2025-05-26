<?php
/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */
if (!isConnect('admin')) {
	throw new Exception('401 - Accès non autorisé');
}

$plugin = plugin::byId('blescanner');
$devices = cache::byKey('blescanner::unknown_devices')->getValue();
$antennas = eqLogic::byType('blescanner');
$away = cache::byKey('blescanner::display_away')->getValue();
?>
<style type="text/css">
        html, body, svg {
                width: 100%;
                height: 100%;
                margin: 0;
                padding: 0;
                font-family: Arial;
        }
        .table {
                margin-left: 0px;
                margin-top: 20px;
        }
	.table-responsive {
		width: 100%;
	}
	.refresh-modal {
                float: right;
		margin-right: 5px;
        }
</style>

<div id="modal_msg" style="display: none"></div>
<div class="refresh-modal">
        <label>{{Rafraîchir }}&nbsp;</label>
        <a class="btn btn-success refreshBtn" data-action="refresh"><i class="fas fa-sync"></i></a>
</div>

<?php
include_file('desktop', 'banner4', 'php', 'blescanner');
?>
<div class="table-responsive">
  <table class="table table-condensed tablesorter" id="table_list2">
	<thead>
     		<tr>
			<th data-sortable="true" data-sorter="inputs">{{N° de série}}</th>
			<th data-sorter="select-text">{{Nom}}</th>
			<th data-sorter="select-text">{{Marque}}</th>
			<th data-sorter="select-text">{{Modèle}}</th>
			<th data-sorter="select-text">{{Découvrable}}</th>
			<th data-sorter="false">{{RSSI}}</th>
			<th data-sorter="false">{{Distance}}</th>
			<th data-sorter="false">{{Autres données}}</th>
		</tr>
	</thead>
	<tbody>
<?php
uasort($devices, function($a, $b) {
    return strcmp($a['discoverable'], $b['discoverable']);
});

foreach ($devices as $uid => $dev) {
//	log::add('blescanner', 'debug', 'device: ' . $uid . ' value: ' . json_encode($dev));
	if (($away == 'off') && (! $dev['present']))
		continue;
	echo '<tr>';
	echo '<td><span class="label label-info">' . $dev['id'] . '</span></td>';
	echo '<td><span class="label label-info">' . $dev['name'] . '</span></td>';
	echo '<td><span class="label label-info">' . $dev['manufacturer'] . '</span></td>';
	echo '<td><span class="label label-info">' . $dev['model'] . '</span></td>';

        $status = '<span class="label label-danger" style="font-size : 1em;cursor:default;">{{Non}}</span>';
        if ($dev['discoverable'])
                $status = '<span class="label label-success" style="font-size : 1em;cursor:default;">{{Oui}}</span>';
        echo '<td>' . $status . '</td>';

	echo '<td>';
	foreach ($antennas as $a) {
	   if ($a->getIsEnable() && $a->isAlive()) {
		$aUid= $a->getLogicalId();
		$aName=  $a->getName();
               	$rssi = $dev['rssi ' . $aUid];
//             	log::add('blescanner', 'debug', 'rssi ' . $aName . ': ' . $rssi);
		$signal = 'success';
               	if ($rssi <= -150)
               		$signal = 'none';
               	elseif ($rssi <= -90)
               		$signal = 'danger';
               	elseif ($rssi <= -80)
               		$signal = 'warning';
               	if ($signal != 'none' && $rssi != '')
                       	echo '<span class="label label-' . $signal . '" style="font-size : 0.9em;cursor:default;padding:0px 5px;">'
			. $rssi .'dBm (' . $aName .')</span><br>';
	   }
        }
	echo '</td>';

	echo '<td>';
	foreach ($antennas as $a) {
	   if ($a->getIsEnable() && $a->isAlive()) {
		$aUid= $a->getLogicalId();
		$aName = $a->getName();
		$dist = $dev['distance '. $aUid];
		if (isset($dist) && ($dist!= -1))
 			echo '<span class="label label-info" style="font-size : 0.9em;cursor:default;padding:0px 5px;">' . $dist
			. 'm (' . $aName . ')</span><br>';
	   }
	}
	echo '</td>';

        $payload = $dev['other'];
        echo '<td>';
        foreach ($payload as $key => $value) {
                echo '<span style="color:#0066cc">' . $key . '</span>'
			. ': <span style="font-style:normal">' . $value . '</span>';
                if ($key != array_key_last($payload))
                        echo ', ';
        }
        echo '</td>';
	echo '<td>';
	echo '<a class="btn btn-primary btn-sm cursor roundedLeft addDevice" data-id="' . $uid . '">';
	echo '<i class="fas fa-check-circle"></i> {{Ajouter}}</a>';
	echo '</td>';
	echo '</tr>';
}
?>
	</tbody>
  </table>
</div>

<script>
var manualClose = true; // assume user closes unless overridden
function reloadModal() {
  manualClose = false; // suppress reload during refresh
  // console.log('reload page list1');
  $('#md_modal').dialog('close');
//  $('#md_modal').dialog({title: "{{Liste devices BLE incconnus}}"});
  $('#md_modal').load('index.php?v=d&plugin=blescanner&modal=list2').dialog('open');
  manualClose = true;
}

$(function() {
  $("#table_list2").tablesorter();
});

</script>

<?php
include_file('desktop', 'modal', 'js', 'blescanner');
?>
