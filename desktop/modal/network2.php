<?php
/* This file is part of Plugin blescanner for jeedom.
*
* Plugin blescanner for jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Plugin blescanner for jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Plugin blescanner for jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

if (!isConnect('admin')) {
	throw new Exception('401 - {{Accès non autorisé}}');
}
$aNodes= blescanner::getAntennaList();
// log::add('blescanner', 'debug',  '*** antennas: ' . json_encode($aNodes));
$antennas = eqLogic::byType('blescanner');
$picture = '/plugins/blescanner/data/images/unknown.png';
$devices = cache::byKey('blescanner::unknown_devices')->getValue();
//log::add('blescanner', 'debug',  '*** unknown devices: ' . json_encode($devices));

$dNodes = array();
foreach ($devices as $uid => $dev) {
	if (! $dev['present'])
		continue;
	$name = $dev['name'];
//        log::add('blescanner', 'debug', 'device: ' . $name);
	$dNodes[$uid]['name'] = $name;
	$dNodes[$uid]['picture']= $picture;
	$dNodes[$uid]['rssi'] = -200;
	foreach ($antennas as $a) {
		$aName=  $a->getName();
		$aUid= $a->getLogicalId();
		$rssi = $dev['rssi ' . $aUid];
		$dist = $dev['distance ' . $aUid];
		if (is_null($dist))
			$dist = -1;
		$dNodes[$uid]['distance ' . $aUid] = $dist;
		if ((is_null($rssi)) || (! $aNodes[$aUid]['online']))
			$rssi = -200;
		$dNodes[$uid]['rssi ' . $aUid] = $rssi;
		$dNodes[$uid]['rssi'] = max ($dNodes[$uid]['rssi'], $rssi);
	}
//	log::add('blescanner', 'debug', 'rssi:' .  $dNodes[$name]['rssi']);
}

$mode = cache::byKey('blescanner::display_mode')->getValue();
// log::add('blescanner', 'debug', '*** nodes :' .  json_encode($dNodes));
sendVarToJS('antennas', $aNodes);
sendVarToJS('nodes', $dNodes);
sendVarToJS('mode', $mode);
?>

<script>
function reloadModal() {
  $('#md_modal').dialog('close');
//  $('#md_modal').dialog({title: "{{Réseau BLE devices inconnus}}"});
  $('#md_modal').load('index.php?v=d&plugin=blescanner&modal=network2').dialog('open');
}
</script>

<?php
include_file('desktop', 'banner1', 'php', 'blescanner');
include_file('desktop', 'banner2', 'php', 'blescanner');
include_file('desktop', 'network_layout', 'php', 'blescanner');
include_file('desktop', 'modal', 'js', 'blescanner');
include_file('desktop', 'graph', 'js', 'blescanner');
?>
