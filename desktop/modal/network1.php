<?php
/* This file is part of Plugin openzwave for jeedom.
*
* Plugin openzwave for jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Plugin openzwave for jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Plugin openzwave for jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

if (!isConnect('admin')) {
	throw new Exception('401 - {{Accès non autorisé}}');
}
$antennas = eqLogic::byType('blescanner');
$devices = array();
if (config::byKey('use_mqttdiscovery','blescanner'))
        $devices = eqLogic::byType('MQTTDiscovery');
if (config::byKey('use_jmqtt','blescanner'))
        $devices = array_merge($devices, eqLogic::byType('jMQTT'));
$away = config::byKey('display_away','blescanner');
$aNodes= blescanner::getAntennaList();
$dNodes = array();

$knownDevices = cache::byKey('blescanner::known_devices')->getValue();
//log::add('blescanner', 'debug',  'known devices: ' . json_encode($knownDevices));
$away = cache::byKey('blescanner::display_away')->getValue();

foreach ($devices as $dev) {
	if (! $dev->getIsEnable())
		continue;
	if ($dev->getEqType_name() == 'jMQTT') {
		if (blescanner::getJmqttType($dev) != 'eqpt')
			continue;
		$uid = blescanner::getJmqttUid($dev);
        } else {
                $uid = $dev->getLogicalId();
        }
	$name = $dev->getName();
        //log::add('blescanner', 'debug', 'device: ' . $name);
	$dNodes[$uid]['name'] = $name;
	$dNodes[$uid]['picture']= $dev->getImage();
	$dNodes[$uid]['rssi'] = -200;
	foreach ($antennas as $a) {
	    if ($dev->getIsEnable()) {
//		$aName = $a->getName();
		$aUid= $a->getLogicalId();
		$distId = 'distance ' . $aUid;
		$dist = $knownDevices[$uid][$distId];
                if (is_null($dist))
                        $dist = -1;
                $dNodes[$uid][$distId] = $dist;

		$rssiId = 'rssi ' . $aUid;
		$rssi = $knownDevices[$uid][$rssiId];
		if ((is_null($rssi)) || (! $aNodes[$aUid]['online']))
			$rssi = -200; // MQTTDiscovery ne fait pas la maj dans ce cas

		// log::add('blescanner', 'debug', 'graph() rssi ' . $aName . ': ' . $rssi);
		$dNodes[$uid][$rssiId] = $rssi;
		$dNodes[$uid]['rssi'] = max ($dNodes[$uid]['rssi'], $rssi);
	   }
	}
	// log::add('blescanner', 'debug', 'rssi:' .  $dNodes[$uid]['rssi']);
	if (($away != 'on') && ($dNodes[$uid]['rssi'] == -200))
		 unset ($dNodes[$uid]);
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
