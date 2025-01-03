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
$devices = array();
if (config::byKey('use_mqttdiscovery','blescanner'))
	$devices = eqLogic::byType('MQTTDiscovery');
if (config::byKey('use_jmqtt','blescanner'))
	$devices = array_merge($devices, eqLogic::byType('jMQTT'));
$antennas = eqLogic::byType('blescanner');
$knownDevices = cache::byKey('blescanner::known_devices')->getValue();
$away = cache::byKey('blescanner::display_away')->getValue();
// log::add('blescanner', 'debug',  'known devices: ' . json_encode($knownDevices));
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
	.refresh-modal {
		float: right;
	}
</style>
<div class="refresh-modal">
	<label>{{Rafraîchir }}&nbsp;</label>
	<a class="btn btn-success refreshGraph" data-action="refresh"><i class="fas fa-sync"></i></a>
</div>

<?php
include_file('desktop', 'banner3', 'php', 'blescanner');
?>


<table class="table table-condensed tablesorter" id="table_listblescanner">
	<thead>
		<tr>
			<th>{{Image}}</th>
			<th>{{Equipement}}</th>
			<th>{{ID}}</th>
                        <th>{{N° de série}}</th>
			<th>{{Marque}}</th>
			<th>{{Type/Modèle}}</th>
			<th>{{Présent}}</th>
			<th>{{Batterie}}</th>
			<th>{{RSSI}}</th>
			<th>{{Distance}}</th>
			<th>{{Dernière communication}}</th>
		</tr>
	</thead>
	<tbody>
<?php
foreach ($devices as $dev) {
	if (! $dev->getIsEnable())
		continue;
	if ($dev->getEqType_name() == 'jMQTT') {
		if (blescanner::getJmqttType($dev) != 'eqpt')
			continue;
		$uid = blescanner::getJmqttUid($dev);
		//$img = '<i class="fas ' . blescanner::getJmqttIcon($dev) . '"></i>';
	 	// log::add('blescanner', 'debug',  'img: ' . $img);
	} else {
		$uid = $dev->getLogicalId();
	}
	$d = $knownDevices[$uid];
	// $d = blescanner::getCmdValue ($dev,'present'); ko pour jMQTT
	if (($away != 'on') && ((!isset($d)) || (!$d['present'])))
		continue;
	$img = '<img src=' . $dev->getImage() . ' style="height:35px; width:35px; ' . $opacity . '" class="' . $opacity . '"/>';
 	$opacity = ($dev->getIsEnable()) ? '' : 'disableCard';
	echo '<tr><td class="' . $opacity . '" >' . $img . '</td><td><a href="' . $dev->getLinkToConfiguration() . '" style="text-decoration: none;">'
		. $dev->getHumanName(true) . '</a></td>';
	echo '<td><span class="label label-info" style="font-size : 1em; cursor : default;">' . $dev->getId() . '</span></td>';
	echo '<td><span class="label label-info" style="font-size : 1em; cursor : default;">' . $uid . '</span></td>';
        echo '<td><span class="label label-info" style="font-size : 1em; cursor : default;">' . $dev->getConfiguration('manufacturer') . '</span></td>';
        echo '<td><span class="label label-info" style="font-size : 1em; cursor : default;">' . $dev->getConfiguration('model') . '</span></td>';
	$status = '<span class="label label-danger" style="font-size : 1em;cursor:default;">{{NOK}}</span>';
        if ((isset($d)) && ($d['present']))
		$status = '<span class="label label-success" style="font-size : 1em;cursor:default;">{{OK}}</span>';
	echo '<td>' . $status . '</td>';
	$bat =  blescanner::getCmdValue($dev,$uid.'-batt'); // a remplacer par generic
	if ($bat < 20 && $bat != '')
                $battery_status = '<span class="label label-danger" style="font-size : 1em;">' . $bat . '%</span>';
        elseif ($bat < 60 && $bat != '')
                $battery_status = '<span class="label label-warning" style="font-size : 1em;">' . $bat . '%</span>';
        elseif ($bat > 60 && $bat != '')
                $battery_status = '<span class="label label-success" style="font-size : 1em;">' . $bat . '%</span>';
        else
                $battery_status = '<span class="label label-primary"></span>';
        echo '<td>' . $battery_status . '</td>';

	echo '<td>';
        foreach ($antennas as $a) {
	    if ($a->getIsEnable()) { // && $a->isAlive()) {
		$aUid= $a->getLogicalId();
		$aName = $a->getName();
		$rssiId = 'rssi ' . $aUid;
		$rssi = $d[$rssiId];
                // $rssi = blescanner::getCmdValue ($dev, $uid. '-rssi-' . $aUid);
                // log::add('blescanner', 'debug',  $aUid . ' / ' . $dev->getName() . ' rssi= ' . $rssi);
		$signal = 'success';
                if ($rssi <= -150)
                	$signal = 'none';
                elseif ($rssi <= -90)
                	$signal = 'danger';
                elseif ($rssi <= -80)
                	$signal = 'warning';
                if ($signal != 'none' && $rssi != '')
                        echo '<span class="label label-' . $signal . '" style="font-size : 0.9em;cursor:default;padding:0px 5px;">' . $rssi .'dBm (' . $aName .')</span><br>';
            }
	}
	echo '</td>';

	echo '<td>';
	$knownDevices = cache::byKey('blescanner::known_devices')->getValue();
	foreach ($antennas as $a) {
	   if ($a->getIsEnable() && $a->isAlive()) {
		$aUid= $a->getLogicalId();
                $aName = $a->getName();
		$distId = 'distance '. $aUid;
		if ($a->getIsEnable()) {
			$dist = $d[$distId];
			if (isset($dist) && ($dist!= -1))
				echo '<span class="label label-info" style="font-size : 0.9em;cursor:default;padding:0px 5px;">'
				. $dist . 'm (' . $aName .')</span><br>';
		}
	   }
	}
	echo '</td>';

	echo '<td><span class="label label-info" style="font-size : 1em;cursor:default;">' . $dev->getStatus('lastCommunication') . '</span></td>';
	echo '</tr>';
}
?>
	</tbody>
</table>

<script>
function reloadModal() {
//  console.log('reload page list1');
  $('#md_modal').dialog('close');
//  $('#md_modal').dialog({title: "{{Liste devices BLE connus}}"});
  $('#md_modal').load('index.php?v=d&plugin=blescanner&modal=list1').dialog('open');
}
</script>


<?php
include_file('desktop', 'modal', 'js', 'blescanner');
?>
