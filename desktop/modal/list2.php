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
$useMqttDisco = config::byKey('use_mqttdiscovery','blescanner');

?>
<div id="modal_msg" style="display: none"></div>
<div class="refresh-modal" style="float: right">
        <label>{{Rafraîchir }}&nbsp;</label>
        <a class="btn btn-success refreshGraph" data-action="refresh"><i class="fas fa-sync"></i></a>
</div>
<table class="table table-condensed tablesorter" id="table_listblescanner">
	<thead>
     		<tr>
			<th>{{N° de série}}</th>
			<th>{{Nom}}</th>
			<th>{{Modèle}}</th>
			<th>{{Marque}}</th>
			<th>{{RSSI}}</th>
			<th>{{Distance}}</th>
			<th>{{Découvrable}}</th>
			<th>{{Autres données}}</th>
		</tr>
	</thead>
	<tbody>
<?php
foreach ($devices as $uid => $dev) {
	if (! $dev['present'])
		continue;
	echo '<tr>';
	echo '<td><span class="label label-info">' . $dev['id'] . '</span></td>';
	echo '<td><span class="label label-info">' . $dev['name'] . '</span></td>';
	echo '<td><span class="label label-info">' . $dev['model'] . '</span></td>';
	echo '<td><span class="label label-info">' . $dev['brand'] . '</span></td>';
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

        $status = '<span class="label label-danger" style="font-size : 1em;cursor:default;">{{Non}}</span>';
        if ($dev['discoverable'])
                $status = '<span class="label label-success" style="font-size : 1em;cursor:default;">{{Oui}}</span>';
        echo '<td>' . $status . '</td>';

        $payload = $dev['other'];
        echo '<td>';
        foreach ($payload as $key => $value) {
                echo '<span style="color:#0066cc">' . $key . '</span>'
			. ': <span style="font-style:normal">' . $value . '</span>';
                if ($key != array_key_last($payload))
                        echo ', ';
        }
        echo '</td>';
	if ($useMqttDisco) {
		echo '<td>';
		echo '<a class="btn btn-primary btn-sm cursor roundedLeft addDevice" data-id="' . $uid . '">';
		echo '<i class="fas fa-check-circle"></i> {{Ajouter}}</a>';
		echo '</td>';
	}
	echo '</tr>';
}
?>
	</tbody>
</table>

<script>
function reloadModal() {
  // console.log('reload page list1');
  $('#md_modal').dialog('close');
//  $('#md_modal').dialog({title: "{{Liste devices BLE incconnus}}"});
  $('#md_modal').load('index.php?v=d&plugin=blescanner&modal=list2').dialog('open');
}
</script>

<?php
include_file('desktop', 'modal', 'js', 'blescanner');
?>
