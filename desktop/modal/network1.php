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
$antennas = blescanner::getDevices('Antenna');
$devices = blescanner::getDevices('Device');
$away = config::byKey('display_away','blescanner');
$aNodes = blescanner::getAntennaList();
$dNodes = array();

$knownDevices = cache::byKey('blescanner::known_devices')->getValue();
//log::add('blescanner', 'debug',  'known devices: ' . json_encode($knownDevices));
$away = cache::byKey('blescanner::display_away')->getValue();

foreach ($devices as $dev) {
	if (! $dev->getIsEnable())
		continue;
        $uid = $dev->getLogicalId();
	$rssi = $dev->getCmdValue($uid . '-rssi');
	$rssi = isset($rssi)? $rssi: -200;
	if (($away != 'on') && ($rssi == -200))
		continue;

	$dNodes[$uid]['rssi'] = $rssi;
	$dNodes[$uid]['name'] = $dev->getName();
	$dNodes[$uid]['picture']= $dev->getImage();

	foreach ($antennas as $a) {
	    if ($dev->getIsEnable()) {
//		$aName = $a->getName();
		$aUid= $a->getUid();
		$aLid = $a->getLogicalId();
		$distId = 'distance ' . $aLid;
		$dist = $dev->getCmdValue($uid . '-distance-' . $aUid);
                $dNodes[$uid][$distId] =  isset($dist)? $dist: -1;

		$rssiId = 'rssi ' . $aLid;
		$rssi = $dev->getCmdValue($uid . '-rssi-' . $aUid);
		$dNodes[$uid][$rssiId] = isset($rssi)? $rssi: -200;
	   }
	}
	// log::add('blescanner', 'debug', 'rssi:' .  $dNodes[$uid]['rssi']);
}
$mode = cache::byKey('blescanner::display_mode')->getValue();
sendVarToJS('antennas', $aNodes);
sendVarToJS('nodes', $dNodes);
sendVarToJS('mode', $mode);
?>

<script>
function reloadModal() {
  console.log('reload page network1');
  $('#md_modal').dialog('close');
//  $('#md_modal').dialog({title: "{{Réseau BLE devices connus}}"});
  $('#md_modal').load('index.php?v=d&plugin=blescanner&modal=network1').dialog('open');
}
</script>

<?php
include_file('desktop', 'banner1', 'php', 'blescanner');
include_file('desktop', 'banner2', 'php', 'blescanner');
include_file('desktop', 'banner3', 'php', 'blescanner');
include_file('desktop', 'network_layout', 'php', 'blescanner');
include_file('desktop', 'modal', 'js', 'blescanner');
include_file('desktop', 'graph', 'js', 'blescanner'); 
?>
