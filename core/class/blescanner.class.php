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

require_once __DIR__  . '/../../../../core/php/core.inc.php';
if (!class_exists('jeedomtools\MQTTClient'))
	require_once __DIR__ . '/MQTTClient.php';

use jeedomtools\MQTTClient as ble_MQTTClient;

class blescanner extends eqLogic {

  private static $_deamon;

  public static function getDeamon() {
    if (is_null(self::$_deamon)) {
	self::$_deamon =  new ble_MQTTClient(__CLASS__);
    }
    return self::$_deamon;
  }

  public static function initConfig() {
    log::add(__CLASS__, 'debug', __FUNCTION__);
    if (empty(config::byKey('ble_root_topics', __CLASS__)))
	config::save('ble_root_topics','theengs',__CLASS__);

    if (empty(config::byKey('disco_topic',__CLASS__)))
	config::save('disco_topic', 'homeassistant',__CLASS__);

    if (empty(config::byKey('devices_timeout',__CLASS__)))
	config::save('devices_timeout',2,__CLASS__);

    if (empty(config::byKey('disco_duration',__CLASS__)))
	config::save('disco_duration',5,__CLASS__);

    if (empty(config::byKey('auto_create',__CLASS__)))
	config::save('auto_create',false,__CLASS__);

    if (empty(config::byKey('mqttMode',__CLASS__)))
        config::save('mqttMode','local',__CLASS__);

    $mqttSettings = config::byKey('broker',__CLASS__,array());
    if (empty($mqttSettings)) {
	$mqttSettings['port'] = '1883';
	$mqttSettings['host'] = 'localhost';
	$mqttSettings['socket_port'] = '55036';
	config::save('broker',json_encode($mqttSettings), __CLASS__);
    }
  }

  public static function getDiscoTopic() {
    return config::byKey('disco_topic',__CLASS__);
//  log::add(__CLASS__, 'debug', 'topic de discovery: ' . $disco);
    return $disco;
  }

  public static function getRootTopics() {
    $s = config::byKey('ble_root_topics', __CLASS__);
    $s = explode(',',$s);
    // log::add(__CLASS__, 'debug', 'root topics: ' . json_encode($s));
    if (! is_array($s))
	throw new Exception (__FUNCTION__ . ': erreur inattendue sur le décodage des topics');
    return $s;
  }

  public function isAlive () {
    if ($this->getConfiguration('type') == 'Antenna')
	$uid = $this->getConfiguration('antennaUid');
    else
	$uid = $this->getLogicalId();
    return $this->getCmdValue($uid . '-connectivity');
  }

  public static function cron() {
    $disco = cache::byKey('blescanner::disco')->getValue();
    if ($disco < strtotime('now'))
	self::stopDisco();

    $timeout = config::byKey('devices_timeout',__CLASS__);
    if ($timeout == 0)
	return;
    $timeout = $timeout * 60;
    self::cleanKnown($timeout);
    self::cleanUnknown($timeout);
  }

  public static function cleanKnown($timeout) {
    $devs = self::getDevices('Device');
    $antennas = self::getDevices('Antenna');
    foreach ($devs as $dev) {
	$lid = $dev->getLogicalId();
	if (! $dev->isAlive())
		continue;
	$lastUpd = strtotime($dev->getStatus('lastCommunication'));
	// log::add(__CLASS__, 'debug', '['. __FUNCTION__ . '], now = ' . strtotime('now') . ' lastcomm: ' . $lastUpd);
	if ((strtotime('now') - $timeout) > $lastUpd) {
	     	$dev->checkAndUpdateCmd($lid . '-connectivity', false);
	     	$dev->checkAndUpdateCmd($lid . '-rssi', -200);
		 log::add(__CLASS__, 'debug', '['. __FUNCTION__ . '] known device ' . $lid . ' is not reachable anymore');
	}
	foreach ($antennas as $a) {
	     $cmdId = $lid . '-rssi-' . $a->getUid();
	     $cmd = $dev->getCmd('info', $cmdId);
	     // log::add(__CLASS__, 'debug', '['. __FUNCTION__ . '] cmdId=' .$cmdId .  ', now = ' . strtotime('now') . ' lastcomm: ' . $lastUpd);
	     if ((is_object($cmd)) && ((strtotime('now') - $timeout) > strtotime($cmd->getCollectDate()))) {
		$val = $cmd->execCmd();
		if ($val != -200)
			$dev->checkAndUpdateCmd($cmdId, -200);
		$cmdId = $lid . '-distance-' . $a->getUid();
		$cmd = $dev->getCmd('info', $cmdId);
		if ((is_object($cmd)) && ((strtotime('now') - $timeout) > strtotime($cmd->getCollectDate())))
			$dev->checkAndUpdateCmd($cmdId, -1);
	     }
	// log::add(__CLASS__, 'debug', '['. __FUNCTION__ . '], cmd = ' .$cmdId . ' collectdate: ' . $cmd->getCollectDate());
	}
    }
  }

  public static function cleanUnknown($timeout) {
    $devs = cache::byKey('blescanner::unknown_devices')->getValue();
    $antennas = self::getDevices('Antenna');
    foreach ($devs as $key => $dev) {
	$lastUpd = $dev['lastUpdate'];
	// log::add(__CLASS__, 'debug', '['. __FUNCTION__ . '], dev: ' . $key . ' timestamp: ' . $lastUpd);
	if ((strtotime('now') - $timeout) > $lastUpd) {
		if ($dev['discoverable']) { // wait 4 data
			if (! $dev['present'])
				continue;
			log::add(__CLASS__, 'debug', '['. __FUNCTION__ . '] unknown device discoverable' . $key . ' is not reachable anymore');
			$dev['present'] = false;
			$dev['rssi'] = -200;
			foreach ($antennas as $a) {
				$aId = $a->getLogicalId();
				$dev['rssi ' . $aId] = -200;
				$dev['distance ' .  $aId] = -1;
			}
			$devs[$key] = $dev;
		} else {
			unset ($devs[$key]); // remove
			log::add(__CLASS__, 'debug', '['. __FUNCTION__ . '] unknown device not discoverable ' . $key . ' is not reachable anymore');
		}
	}
    }
    cache::set('blescanner::unknown_devices',$devs);
  }

  public function getCmdValue($cmdUid) {
    $cmd = $this->getCmd('info', $cmdUid);
    if (is_object($cmd)) {
	$value = $cmd->execCmd();
	// log::add(__CLASS__, 'debug', '['.__FUNCTION__ . '] antenna: ' . $eqLogic->getName() . ' cmdName: ' .
	// $cmd->getName() . ' cmdUid: ' . $cmd->getLogicalId() . ' value: ' . $value);
        return $value;
    } else
	return null;
	// throw new Exception ('unknown command: ' .  $cmdUid);
  }

  public static function initSettings() {
    self::manageMqtt2();

    $t1 = intval(config::byKey('disco_duration',__CLASS__));
    log::add(__CLASS__, 'info', 'timeout auto-découverte: ' . $t1 . ' mins');
    if (! is_int($t1) || ($t1 < 1))
	throw new Exception("La durée d'auto-découverte doit être un nombre entier de minutes");
    $t2 =  intval(config::byKey('devices_timeout',__CLASS__));
    log::add(__CLASS__, 'info', 'timeout devices: ' . $t2 . ' mins');
    if (! is_int($t2) || $t2 <0)
	throw new Exception("Le délai d'absence doit être un nombre entier de minutes");
    if (config::byKey('ble_root_topics', __CLASS__) == null)
	throw new Exception("Ajoutez au moins un topic à surveiller");
    $mqttSettings = config::byKey('broker',__CLASS__);
    //log::add(__CLASS__, 'debug', 'MQTT settings:' . json_encode($mqttSettings));
    if ($mqttSettings['host'] == '')
	throw new Exception("L'adresse du broker MQTT doit être renseignée");
    if ($mqttSettings['port'] == '')
	throw new Exception("Le port du broker MQTT doit être renseigné");
    if ($mqttSettings['user'] == '')
	throw new Exception("Le login du broker MQTT doit être renseigné");
    if ($mqttSettings['passwd'] == '')
	throw new Exception("Le mot de passe du broker MQTT doit être renseigné");
    if ($mqttSettings['socket_port'] == '')
	throw new Exception("Le port du callback Jeedom doit être renseigné");
    cache::set('blescanner::unknown_devices',array());
    cache::set('blescanner::display_mode','Attenuation');
    cache::set('blescanner::display_away','off');
    cache::set('blescanner::disco', 0);
  }

  public static function startDisco() {
    $disco = cache::byKey('blescanner::disco')->getValue();
    //log::add(__CLASS__, 'debug', '['. __FUNCTION__ .'] disco=' . $disco);
    if ($disco)
	return;
    $timeout = config::byKey('disco_duration', __CLASS__) . ' minute';
    $msg = "*** Début d'auto-découverte, durée: " . $timeout . "(s) ***";
    log::add(__CLASS__, 'info', $msg);
    $topic = self::getDiscoTopic();
    log::add(__CLASS__, 'info', 'topic de discovery: ' . $topic);
    self::getDeamon()->send ('addTopic',$topic);

    event::add('blescanner::discovery', array('message' =>  __($msg, __FILE__), 'type' => 'start'));
    cache::set('blescanner::disco',strtotime('now') + ($timeout * 60));
  }

  public static function stopDisco() {
    $disco = cache::byKey('blescanner::disco')->getValue();
    // log::add(__CLASS__, 'debug', '['. __FUNCTION__ .'] disco=' . $disco);
    if (! $disco)
	return;
    $msg = "*** Fin d'auto-découverte ***";
    log::add(__CLASS__, 'info', $msg);
    self::getDeamon()->send ('removeTopic',self::getDiscoTopic());

    if (config::byKey('auto_create',__CLASS__)) {
	$devs = cache::byKey('blescanner::unknown_devices')->getValue();
	foreach ($devs as $id => $dev)
	   self::addDevice($id);
	cache::set('blescanner::unknown_devices', array());
	event::add('blescanner::newEqLogic', '');
    }
    event::add('blescanner::discovery', array('message' =>  __($msg, __FILE__), 'type' => 'stop'));
    cache::set('blescanner::disco', 0);
  }

  public function updateDevices($message) {
    if (!($this->getIsEnable()))
	return;

//   log::add(__CLASS__, 'debug', '['. __FUNCTION__ .'] antenna: ' . $this->getName() . ' message: ' . json_encode($message));
    foreach ($message as $did => $value) {
	if (is_array($value) && isset($value["id"])) {
		$dLogic = self::byLogicalId($did,__CLASS__);
		if (is_object($dLogic)) {
			if ($dLogic->getIsEnable())
				$dLogic->updateDevice($this,$value);
		} else
			$this->updateUnknownDevice($did,$value);
	}
    }
  }

  public function updateDevice ($aLogic, $msg) {
    $did = $this->getLogicalId();
    // log::add(__CLASS__, 'debug', '['. __FUNCTION__ .'] antenna: ' . $aLogic->getName() . ' device: ' . $did . ' value: ' . json_encode($msg));
    foreach ($msg as $key => $value) {
	$uid = $did . '-' . $key;
	if (strpos($uid, 'rssi')!== false) {
		$cid = $uid . '-' . $aLogic->getConfiguration('antennaUid');
		log::add(__CLASS__, 'debug', '['. __FUNCTION__ .'] antenna: ' . $aLogic->getName() . ' cmd: ' . $cid . ' value: ' . $value);
		$this->checkAndUpdateCmd ($cid, $value);
		$rssi = -200;
		$antennas = self::getDevices('Antenna');
		foreach ($antennas as $a) {
			$cid = $did . '-rssi-' . $a->getUid();
			$val = $this->getCmdValue($cid);
			if ($val > $rssi)
				$rssi = $val;
		}
		$this->checkAndUpdateCmd ($uid, $rssi);
		$this->checkAndUpdateCmd ($did . '-connectivity', true);
	} else if (strpos($uid, 'distance')!== false) {
		$cid = $uid . '-' . $aLogic->getConfiguration('antennaUid');
		$value = round($value,2);
		log::add(__CLASS__, 'debug', '['. __FUNCTION__ .'] antenna: ' . $aLogic->getName() . ' update cmd=' . $cid . ' value:' . $value);
		$this->checkAndUpdateCmd ($cid, $value);
	} else {
		$cmd = $this->getCmd('info',$uid);
		if (! is_object($cmd))
			continue;
		if ($cmd->getGeneric_type() == 'BATTERY')
			$this->batteryStatus($value);
		log::add(__CLASS__, 'debug', '['. __FUNCTION__ .'] antenna: ' . $aLogic->getName() . ' update cmd=' . $uid . ' value: ' . $value);
		$this->checkAndUpdateCmd ($uid, $value);
	}
    }
  }

  public function updateUnknownDevice ($did, $value) {
    $antenna = $this->getLogicalId();
    log::add(__CLASS__, 'debug', '['. __FUNCTION__ .'] antenna: ' . $this->getName() . ' device: ' . $did);
    $unknownDevices = cache::byKey('blescanner::unknown_devices')->getValue();
    $dev = $unknownDevices[$did];
    if (! isset($dev))
	$dev = array();
    $dev['present'] = true;
    if (! isset($dev['id']))
	$dev['id'] = $value['id'];
    if (! $dev['discoverable']) {
	$dev['name'] = $value['name'];
	$dev['model'] = isset($value['model']) ? $value['model'] : $value['model_id'];
	$dev['manufacturer'] = $value['brand'];
    }
    if (!isset($dev['pictureId'])) {
	if (isset($dev['model']))
	    $dev['pictureId'] = md5($dev['model']);
	else if (isset($value['servicedatauuid']))
	    $dev['pictureId'] = md5($value['servicedatauuid']);
	elseif (isset($value['manufacturerdata']))
	    $dev['pictureId'] = md5($value['manufacturerdata']);
    }
    $other = $value;
    $cols = array('id','rssi','name','mac','model','model_id','brand','distance','topic'); //,'manufacturer'
    foreach($cols as $k)
	unset($other[$k]);
    $dev['other'] = $other;
    $dist = is_numeric($value["distance"])? round($value["distance"],2) : -1;
    $dev['rssi ' . $antenna] = $value["rssi"];
    $dev['distance ' . $antenna] = $dist;
    $dev['lastUpdate'] = strtotime('now');

    $unknownDevices[$did] = $dev;
    cache::set('blescanner::unknown_devices',$unknownDevices);
  }

  public static function uploadPicture ($pic, $pictureId) {
    log::add(__CLASS__, 'debug', '[' . __FUNCTION__ . '] pic=' . $pic['name'] . ' modèle=' . $pictureId);
    $dir = __DIR__ . '/../../data/images/tmp/';
    if (!file_exists($dir))
	mkdir ($dir,0755,true);

    $f = file_get_contents($pic['tmp_name']);
    file_put_contents($dir . $pictureId . '.png', $f);
  }

  public static function resetPicture ($pictureId) {
    log::add(__CLASS__, 'debug', '[' . __FUNCTION__ . '] modèle=' . $pictureId);
    $fname = __DIR__ . '/../../data/images/tmp/' . $pictureId . '.png';

    if (file_exists($fname))
	unlink($fname);
  }

  public static function getPicture ($pictureId) {
    $dir = __DIR__ . '/../../';
    $fname = $pictureId . '.png';

    // log::add(__CLASS__, 'debug', '[' . __FUNCTION__ . '] image=' . $fname);
    $f = $dir . '/data/images/tmp/' . $fname;
    if (file_exists($f))
	return 'plugins/blescanner/data/images/tmp/' . $fname . '?ts=' . time(); //. @filemtime($f);
    else
	return 'plugins/blescanner/plugin_info/blescanner_icon.png?ts=' . time();
  }

  public function getImage() {
    return self::getPicture ($this->getConfiguration('pictureId'));
   // return parent::getImage();
  }

  public static function getDevices ($type) {
    //log::add(__CLASS__, 'debug', '[' . __FUNCTION__ . '] of type: ' . $type);
    $list = array();
    $eqLogics = eqLogic::byType(__CLASS__);
    foreach ($eqLogics as $eqLogic) {
    // log::add(__CLASS__, 'debug', '[' . __FUNCTION__ . '] eqLogic Id=' . $eqLogic->getLogicalId() . ' type ' . $eqLogic->getConfiguration('type'));
    if ($eqLogic->getConfiguration('type') == $type)
	array_push($list, $eqLogic);
    }
    return $list;
  }

  public static function addDevice($id) {
    $eqLogic = eqLogic::byLogicalId($id, __CLASS__);
    if (! is_object($eqLogic)) {
	log::add(__CLASS__, 'debug', '[' . __FUNCTION__ . '] id=' . $id);
	$devices = cache::byKey('blescanner::unknown_devices')->getValue();
	$dev = $devices[$id];
	if (is_array($dev)) {
		$eqLogic = self::createDevice($id, $dev);
		$eqLogic->createPresence();
		if ($dev['discoverable'])
		    $eqLogic->createCommands($dev);
	}
    } else
	throw new Exception ($id . ': ce device existe déja');
  }

  public static function createDevice($id, $dev) {
    if (! is_array($dev))
	return null;
    $name = ($dev['name'] != null)? $dev['name'] : $id;
    log::add(__CLASS__, 'debug',  '[' . __FUNCTION__ . '] id: '. $id . ' name: ' . $name);
    $eqLogic = new self();
    $eqLogic->setLogicalId($id);
    $eqLogic->setName($name);
    $eqLogic->setEqType_name(__CLASS__);
    $eqLogic->setIsEnable(1);
    $eqLogic->setIsVisible(1);
    $eqLogic->setConfiguration('type', 'Device');
    if ($dev['manufacturer'] != null)
	$eqLogic->setConfiguration('manufacturer', $dev['manufacturer']);
    if ($dev['version'] != null)
	$eqLogic->setConfiguration('version', $dev['sw']);
    if ($dev['model'] != null)
	$eqLogic->setConfiguration('model', $dev['model']);
    if ($dev['pictureId'] != null)
	$eqLogic->setConfiguration('pictureId', $dev['pictureId']);
    $eqLogic->save();
    //event::add('blescanner::newEqLogic', '');
    log::add(__CLASS__, 'debug', '[' . __FUNCTION__ . '] nouveau device créé id:' .$id);
    return $eqLogic;
  }

  public function preRemove() {
    if ($this->getConfiguration('type') != 'Antenna')
	return;
    $aid = $this->getConfiguration('antennaUid');
    $aList = self::getDevices('Antenna');
    foreach (self::getDevices('Device') as $d) {
	log::add(__CLASS__, 'debug', '[' . __FUNCTION__ . '] removing '.$this->getName().' presence commands for device: ' . $d->getName());
	$did = $d->getLogicalId();
	$cmd = $d->getCmd('info', $did . '-rssi-' . $aid);
       	if (is_object($cmd))
		$cmd->remove();
	$cmd = $d->getCmd('info', $did . '-distance-' . $aid);
	if (is_object($cmd))
		$cmd->remove();
	if (count($aList) == 1)
		$d->checkAndUpdateCmd($did . '-rssi', -200);
    }
  }

  // create device presence
  public function createPresence() {
    if ($this->getConfiguration('type') != 'Device')
	return;
    log::add(__CLASS__, 'debug', '[' . __FUNCTION__ . '] creating presence for: ' . $this->getName());
    $uid = $this->getLogicalId();
    $this->createCmd($uid . '-rssi', 'rssi', null, null, 'numeric','signal_strength','db');
    $this->createCmd($uid . '-connectivity', 'Présent', null, null, 'binary','connectivity');
    $aList = self::getDevices('Antenna');
    foreach ($aList as $a)
	$this->createAntennaPresence($a);
  }

  // create antenna presence for given device
  public function createAntennaPresence ($a) {
//    log::add(__CLASS__, 'debug', '[' . __FUNCTION__ . '] creating '.$a->getName().' presence commands for device: ' . $this->getName());
    $did = $this->getLogicalId();
    $cid = $did . '-rssi-' . $a->getConfiguration('antennaUid');
    $name = 'rssi ' . $a->getName();
    $topic = $this->getConfiguration('root_topic').'+/' . $a->getLogicalId() .'/BTtoMQTT/'.$did;
    $this->createCmd($cid, $name, $topic, 'rssi', 'numeric','signal_strength','db');
    if ($a->getConfiguration('manufacturer') == 'OMG_community') {
	$cid = $did . '-distance-' . $a->getConfiguration('antennaUid');
	$name = 'distance ' . $a->getName();
	$this->createCmd($cid, $name, $topic, 'distance', 'numeric','distance','m');
    }
  }

  public function createCommands ($dev) {
//  log::add(__CLASS__, 'debug', '[' . __FUNCTION__ . '] device: ' . json_encode($dev));
    $id = $this->getLogicalId();
    foreach ($dev as $cid => $config) {
	if (! is_array($config))
		continue;
	$type = $config['type'];
	if (strpos($cid,$id) !== false)
		$this->createInfoCmd ($cid, $type, $config);
    }
  }

  public static function handleMessage($message) {
//  log::add(__CLASS__, 'debug', '[' . __FUNCTION__ . '] message: ' . json_encode($message));
    $root_topics = self::getRootTopics();
    $disco=cache::byKey('blescanner::disco')->getValue();

    foreach ($message as $topic => $content) {
//  	log::add(__CLASS__, 'debug', 'topic: ' . $topic);
    	if (($disco) && ($topic == self::getDiscoTopic()))
	   self::handleDiscovery ($content);
    	else if (in_array($topic, $root_topics))
	   self::handleTopic ($topic, $content);
/*
    	else
	   log::add(__CLASS__, 'error', "Le message reçu n'est pas un message BLE: ". json_encode($message));
*/
    }
  }


  public static function handleDiscovery ($message) {
    // log::add(__CLASS__, 'debug', '[' . __FUNCTION__ . '] message: ' . json_encode($message));
    foreach ($message as $key => $content) {
	self::handleTypeDiscovery ($key, $content);
    }
  }

  // manage attribute type = sensor, number message = {}
  public static function handleTypeDiscovery ($type, $message) {
 //   log::add(__CLASS__, 'debug', '[' . __FUNCTION__ . '] type= '. $type . ' message= ' . json_encode($message));
    foreach ($message as $key => $content) {
	$config = $content['config'];
	if (is_array($config))
		self::handleAttrDiscovery($type, $config);
    }
  }

  // manage device uid=xxxxx-uptime type=switch config = {}
  public static function handleAttrDiscovery($type, $config) {
    $dev = $config['device'];
    // log::add(__CLASS__, 'debug', '[' . __FUNCTION__ . '] name=' . $dev['name'].' mf=' . $dev['mf']);
    if (is_array($dev)) {
	$uid = $config['uniq_id'];
	$uid = str_replace('_', '', $uid);
	$cname = str_replace($dev['mdl'].'-','',$config['name']);
	// log::add(__CLASS__, 'debug', '['. __FUNCTION__ .'] uid: '. $uid. ' name: ' . $cname . ' type: '. $type .' for device: ' . $dev['name']);
	if ($dev['mf'] == 'OMG_community') {
   		$aLogic = eqLogic::byLogicalId($dev['name'], __CLASS__);
		if ((is_object($aLogic)) && ($aLogic->getConfiguration('manufacturer') == 'Unknown')) {
			$aLogic->remove();
			$aLogic = null;
		}
   		if (!is_object($aLogic))
			$aLogic = self::createOMGAntenna($dev);

		$cmd = $aLogic->createInfoCmd($uid, $type, $config);
		if ($type == 'button')
			$aLogic->createActionCmd($uid.'-button', $cname, $type, $config['cmd_t'], $config['pl_prs']);
		else if ($type == 'update')
			$aLogic->createActionCmd($uid, $cname, $type, $config['cmd_t'], $config['payload_install']);
               	else if ($type == 'number')
			$aLogic->createActionCmd($uid.'-set', $cname .' Set', $type, $config['cmd_t'], $config['cmd_tpl'], $cmd);
		else if ($type == 'switch') {
			$aLogic->createActionCmd($uid.'-on', $cname .' On', $type, $config['cmd_t'], $config['pl_on'], $cmd);
			$aLogic->createActionCmd($uid.'-off', $cname .' Off', $type, $config['cmd_t'], $config['pl_off'], $cmd);
		}
   	} else if (is_array($dev['ids'])) {
			$did = $dev['ids'][0];
			if (! isset($did))
				return;
			$dLogic = self::byLogicalId($did,__CLASS__);
			if (is_object($dLogic))
				return;
			$unknownDevices = cache::byKey('blescanner::unknown_devices')->getValue();
			$device = $unknownDevices[$did];
			if (! is_array($device)) {
				log::add(__CLASS__, 'debug', '[' . __FUNCTION__ . '] nouveau device découvrable inconnu: '. $did . ' name: '. $dev['name']);
				$device = array();
				$device['id'] = $did;
				$device['present'] = false;
			}
			if ((! isset($device['discoverable'])) || (! $device['discoverable'])) {
				//unset ($device['other']);
				$device['manufacturer'] = $dev['mf'];
				$device['model'] = $dev['mdl'];
				$device['name'] = $dev['name'];
				$device['discoverable'] = true;
			}
			if (! isset($device['pictureId']))
				$device['pictureId'] = md5($dev['mdl']);

			if (! isset($device['other']))
				$device['other'] = array();

			$t = isset($config['dev_cla']) ? $config['dev_cla'] : $type;
			$device['other'][$cname] = $t;
			unset($config['device']);
			$config['type'] = $type;
			$device[$uid] = $config;
			$unknownDevices[$did] = $device;
			cache::set('blescanner::unknown_devices',$unknownDevices);
		     // log::add(__CLASS__, 'debug', '******* NOUVEAU DEVICE: ' . json_encode($device));

	}
    }
  }

  public function getUid() {
    if ($this->getConfiguration('type') == 'Device')
	return $this->getLogicalId();
    else
	return $this->getConfiguration('antennaUid');
  }

  public function createInfoCmd ($uid, $type, $config) {
    if (($type == 'button') || ($type == 'update'))
	return;
    log::add(__CLASS__, 'debug', '['.__FUNCTION__ . '] uid: ' . $uid . ' type: ' . $type);

    $calc = null;
    $key = str_replace($this->getUid().'-', '', $uid);
    if (isset($config['val_tpl'])) {
	$calc = str_replace('value_json.', '', $config['val_tpl']);
	$calc = preg_replace ('/[{ }]/','', $calc);
	$calc = preg_split('/\|/', $calc)[0];
	if (preg_match('/^([^\/\*\+\-\(\)]+)/', $calc, $matches)) {
	   $key = $matches[0];
	   if ($key != $calc)
		$calc = str_replace($key,'',$calc);
	    else
                unset ($calc);
	} else
	   $key = $calc;
    }
    // uid remplacé par key pour tempc
    $lid = $this->getUid() . '-' . $key;
    $cmd = $this->getCmd('info',$lid);
    if (is_object($cmd))
	return $cmd;

    if ($type == 'binary_sensor') {
	$calc = "== '" . $config['pl_on'] . "'";
	$key = null;
    }

    $unit = null;
    $stype = self::getSubTypeFromHA($type,$config);
    if ($stype == 'numeric')
	$unit = $config['unit_of_meas'];

    $cmd = $this->newCmd ($lid, $config['name'], $config['stat_t'], $key, $stype, $config['dev_cla'],$unit);
    if (trim($calc) != '') {
	$calc = '#value# ' . $calc;
	$cmd->setConfiguration('calculValueOffset', $calc);
	$cmd->save();
    }
    $this->initPresence($lid);
    return $cmd;
  }

  public function initPresence($lid) {
    if (strpos($lid,'connectivity') !== false) {
	if ($this->getConfiguration('type') == 'Antenna')
	    $this->checkAndUpdateCmd($lid,'online');
	else
	    $this->checkAndUpdateCmd($lid, false);
    } else if (strpos($lid,'rssi') !== false)
	$this->checkAndUpdateCmd($lid, -200);
    else if (strpos($lid,'distance') !== false)
	$this->checkAndUpdateCmd($lid, -1);
  }

  public function createActionCmd ($uid, $name , $type, $topic, $payload, $info=null) {
    $cmd = $this->getCmd('action',$uid);
    if (is_object($cmd))
	return $cmd;

    $cmd = $this->newCmd ($uid, $name, $topic, $payload, 'other');
    if (isset($info))
	$cmd->setValue($info->getId());

    if ($type == 'switch') {
	$cmd->setTemplate("dashboard",'core::binarySwitch');
	$cmd->setTemplate("mobile",'core::binarySwitch');
	$info->setIsVisible(0); // on masque l'info
	$info->save();
    } else if ($type == 'number')
	$cmd->setSubType('slider');

    $cmd->save();
    return $cmd;
  }

 public static function createOMGAntenna($dev) {
    $id = $dev['ids'][0];
    log::add(__CLASS__, 'debug', '[' . __FUNCTION__ .'] id: '. $id . ' name: ' . $dev['name'] . ' type: ' . $dev['mf']);
    $aLogic = new self();
    $aLogic->setLogicalId($dev['name']);
    $aLogic->setName($dev['name']);
    $aLogic->setEqType_name(__CLASS__);
    $aLogic->setIsEnable(1);
    $aLogic->setIsVisible(1);
    $aLogic->setConfiguration('antennaUid', $id);
    $aLogic->setConfiguration('type', 'Antenna');
    $aLogic->setConfiguration('manufacturer', $dev['mf']);
    $aLogic->setConfiguration('version', $dev['sw']);
    $aLogic->setConfiguration('model', $dev['mdl']);
    $aLogic->setConfiguration('pictureId', md5($dev['mdl']));

    $aLogic->setConfiguration('antennaWebURL',$dev['cu']);
    $aLogic->save();

    $dList = self::getDevices('Device');

    foreach ($dList as $d)
	$d->createAntennaPresence($aLogic);

    event::add('blescanner::newEqLogic', '');
    return $aLogic;
  }

  public function createCmd($uid, $name, $topic, $key, $stype, $class=null, $unit=null) {
    $cmd = $this->newCmd($uid, $name, $topic, $key, $stype, $class, $unit);
    $this->initPresence($uid);
    return $cmd;
  }

  public function newCmd($uid, $name, $topic, $key, $stype, $class=null, $unit=null)
  {
    // log::add(__CLASS__, 'info', '['. __FUNCTION__ . '] uid: '. $uid .' name: ' . $name . ' type: ' . $stype . ' class: ' . $class . ' unit: ' . $unit);
    $cmd = $this->getCmd(null, $uid);
    if (!is_object($cmd)) {
	// log::add(__CLASS__, 'info', 'creating the command: '. $uid . ' / ' . $name . ' / ' . $stype);
	$cmd = new blescannerCmd();
	$cmd->setLogicalId($uid);
	$cmd->setEqLogic_id($this->getId());
	$cmd->setName($name);
	$cmd->setIsHistorized(0);
	$cmd->setIsVisible(1);
	if ($stype == "other") {
		$cmd->setType('action');
		$cmd->setGeneric_type('GENERIC_ACTION');
		if (isset($key))
			$cmd->setConfiguration('payload',"'" .$key . "'");
	} else {
		$cmd->setType('info');
		$cmd->setGeneric_type(self::getGenericFromHA($class));
		if (isset($key))
			$cmd->setConfiguration('key',$key);
	}
	$cmd->setSubType($stype);
	if (isset($topic))
		$cmd->setConfiguration('topic',$topic);
	if (isset($unit)) {
		$cmd->setUnite($unit);
		if ($unit == 's')
			$cmd->setConfiguration('historizeRound','2');
	}
	$cmd->save();
    }
    return $cmd;
  }

  public static function getGenericFromHA($class) {
    if (! isset($class))
	return 'GENERIC_INFO';
    switch ($class) {
	case 'duration':
		return 'TIMER';
	case 'temperature':
		return 'TEMPERATURE';
	case 'illuminance':
		return 'BRIGHTNESS';
	case 'humidity':
		return 'HUMIDITY';
	case 'battery':
		return 'BATTERY';
	case 'voltage':
		return 'VOLTAGE';
	case 'distance':
		return 'DISTANCE';
	case 'connectivity':
		return 'PRESENCE';
	case 'signal_strength':
		return 'NOISE';
	default:
		return 'GENERIC_INFO';
    }
  }

  public static function getSubTypeFromHA ($type,$config) {
    // log::add(__CLASS__, 'debug', __FUNCTION__ . ' $type: ' . $type);
    switch ($type) {
	case 'sensor':
		if (isset($config['unit_of_meas']))
			return 'numeric';
		else
			return 'string';
	case 'binary_sensor':
	case 'device_tracker':
	case 'switch': return 'binary';
	case 'button':
	case 'update': return 'other'; // action
	case 'number': return 'numeric';
	default:
		throw new Exception('Discovery error, unknown type: '. $type);
    }
  }

  // SYS or BT config messages
  public function updateAntennaConfig($topic, $msg) {
    //log::add(__CLASS__, 'debug', '>>> update Antenna config topic= ' . $topic); // . ' msg= ' . json_encode($msg));
    //log::add(__CLASS__, 'debug', 'eqLogic ID=' . $this->getId(). ' '.$this->getName());
    if ($this->getIsEnable())
      foreach ($msg as $key => $value) {
	 if (!is_array($value)) {
		$cmdUid = $this->getConfiguration('antennaUid') . '-' . $key;
		$cmd = $this->getCmd('info', $cmdUid);
                //$cmd = cmd::byEqLogicIdAndLogicalId ($this->getId(),$cmdUid);
                if ($cmd != null) {
                        log::add(__CLASS__, 'debug', '[' . __FUNCTION__ . '] ' . $cmdUid .' / '
				. $cmd->getName() .' / value: '. $value);
                        if(isset($value))
                                $this->checkAndUpdateCmd($cmdUid, $value);
		}
	 }
      }
  }

  public function updateBtConfig($topic, $msg) {
    // log::add(__CLASS__, 'debug', '[' . __FUNCTION__ . '] msg= ' . json_encode($msg));
    $this->updateDevices($msg);
    $this->updateAntennaConfig($topic, $msg);
  }

  public static function updateLWT ($key, $alive) {
    $eqLogic = self::byLogicalId($key, __CLASS__);
    if (is_object($eqLogic)) {
	log::add(__CLASS__, 'debug', '[' . __FUNCTION__ . '] antenna: ' . $key . ' LWT: ' . $alive);
	$cmdUid = $eqLogic->getConfiguration('antennaUid') . '-connectivity';
	$eqLogic->checkAndUpdateCmd($cmdUid, trim($alive));
    }
  }

  public static function handleTopic($rootTopic, $msg) {
//  log::add(__CLASS__, 'debug', '[' . __FUNCTION__ . '] topic: ' . $rootTopic . ' msg=' . json_encode($msg));
    $disco=cache::byKey('blescanner::disco')->getValue();
    foreach ($msg as $key => $value) {
	$alive = $value["LWT"];
	if (isset($alive))
		self::updateLWT($key,$alive);

	$val = $value["BTtoMQTT"];
	$eqLogic = self::byLogicalId($key, __CLASS__);
	if (isset($val)) {
		if ($disco)
			$eqLogic = self::createUnknownAntenna($key, $rootTopic);
		$topic = $rootTopic . '/' . $key . '/BTtoMQTT';
		if (is_object($eqLogic))
			$eqLogic->updateBtConfig($topic, $val);
	}
	$val = $value["SYStoMQTT"];
	$topic = $rootTopic . '/' . $key . '/SYStoMQTT';
	if (isset($val) && is_object($eqLogic))
		$eqLogic->updateAntennaConfig($topic, $val);
    }
  }

  public static function createUnknownAntenna($key,$topic)
  {
    $aLogic = eqLogic::byLogicalId($key, __CLASS__);
    if (!is_object($aLogic)) {
	log::add(__CLASS__, 'info','[' . __FUNCTION__ .  '] new antenna: '. $key . ' topic: ' . $topic);
	$aLogic = new self();
	$aLogic->setLogicalId($key);
	$aLogic->setName($key);
	$aLogic->setEqType_name(__CLASS__);
	$aLogic->setIsEnable(1);
	$aLogic->setIsVisible(1);
	$aLogic->setConfiguration('type', 'Antenna');
	$aLogic->setConfiguration('manufacturer', 'Unknown');
	$aLogic->setConfiguration('model', 'Unknown');
	$aLogic->setConfiguration('pictureId', md5($dev['mdl']));
	$aLogic->setConfiguration('antennaUid',$key);
   	$aLogic->save();
	$topic = $topic.'/'.$key.'/LWT';
	$uid = 'connectivity';
	$lwt = $aLogic->newCmd($key.'-'.$uid, 'Connectivity', $topic, $uid, 'binary',$uid);
	$lwt->setConfiguration('calculValueOffset', "#value#  == 'online'");
	$lwt->save();
	$aLogic->checkAndUpdateCmd($key.'-'.$uid, 'online');

	$dList = self::getDevices('Device');
	foreach ($dList as $d)
	   $d->createAntennaPresence($aLogic);

	event::add('blescanner::newEqLogic', '');
    }
    return $aLogic;
  }

  public static function send_alert($msg) {
    log::add(__CLASS__, 'error', __($msg, __FILE__), 'unableStartDeamon');
    event::add('jeedom::alert', array('level' => 'warning', 'page' => 'blescanner',
	'message' => $msg));
  }

  public static function deamon_info() {
    $rc = array();
    $rc['log'] = __CLASS__;
    $rc['state'] = 'nok';
    $rc['launchable'] = 'ok';
    $deamon = self::getDeamon();
    if (($rc['launchable'] == 'ok') && (!is_null($deamon)) && ($deamon->isRunning()))
	$rc['state'] = 'ok';

    // log::add(__CLASS__, 'debug', '['.__FUNCTION__ . '] state= ' . $rc['state']);
    return $rc;
  }

  public static function manageMqtt2() {
    $mode = config::byKey('mqttMode',__CLASS__);
    log::add(__CLASS__, 'debug', '['.__FUNCTION__ . '] mode: ' . $mode);
    if ($mode != 'local')
	return;

    $mqtt2 = plugin::byId('mqtt2');
    if ((! $mqtt2) || (!$mqtt2->isActive()))
        throw new Exception("Le plugin MQTT2 n'est pas installé ou pas activé");
    if (class_exists('mqtt2')) {
	$mqtt2Infos = mqtt2::getFormatedInfos();
	// log::add(__CLASS__, 'debug', '['.__FUNCTION__ . '] config' . json_encode($mqtt2Infos));
	$mqttSettings = config::byKey('broker',__CLASS__,array());
	$mqttSettings['host'] = $mqtt2Infos['ip'];
	$mqttSettings['port'] = $mqtt2Infos['port'];
	$mqttSettings['user'] = $mqtt2Infos['user'];
	$mqttSettings['passwd'] = $mqtt2Infos['password'];
	config::save('broker',json_encode($mqttSettings), __CLASS__);
	if (mqtt2::deamon_info()['state'] != 'ok') {
	     log::add(__CLASS__, 'debug', '['.__FUNCTION__ . '] Démarrage du démon MQTT2');
	     mqtt2::deamon_start();
	}
    }
  }

  public static function deamon_start() {
    self::deamon_stop();
    try {
	$rc = self::deamon_info();
	if ($rc['launchable'] != 'ok')
	   throw new Exception('Veuillez vérifier la configuration');

	log::add(__CLASS__, 'info', '*** Starting blescanner deamon ***');
	self::initSettings();
	$mqttSettings = config::byKey('broker',__CLASS__);
	$mqttSettings['cbclass'] = 'jeeblescanner';
	config::save('broker', json_encode($mqttSettings), __CLASS__);
	log::add(__CLASS__, 'debug', '[' . __FUNCTION__ . '] settings MQTT: ' . json_encode($mqttSettings));
	$deamon = self::getDeamon();
	$deamon->start ($mqttSettings);
	sleep(3);
	if (! ($deamon->isRunning()))
	    throw new Exception('Démon MQTT non démarré ou mauvais paramétrage, vérifier les logs');
	$i = 0;
	while ($i < 3) {
	   $rc = self::deamon_info();
	   if ($rc['state'] == 'ok')
		break;
	   sleep(1);
	   $i++;
	}
	if ($i >=3)
	   throw new Exception('Impossible de démarrer le démon blescanner, consultez les logs');

	$topics = self::getRootTopics();
	log::add(__CLASS__, 'info', 'root topics: ' . json_encode($topics));
	foreach ($topics as $t)
	   $deamon->send('addTopic',$t);
	self::startDisco();
	log::add(__CLASS__, 'info', '*** blescanner deamon started ***');
	//    message::removeAll(__CLASS__, 'unableStartDeamon');
	return true;
    }  catch (Exception $e) {
	self::send_alert ($e->getMessage());
	return false;
    }
  }


  public static function deamon_stop() {
    $antennas = self::getDevices('Antenna');
    foreach ($antennas as $a) {
	$cmdUid = $a->getConfiguration('antennaUid') . '-connectivity';
	$a->checkAndUpdateCmd($cmdUid, 'offline');
    }
    self::cleanKnown(0);
    cache::delete('blescanner::unknown_devices');
    cache::set('blescanner::disco', 0);

    $rc = self::deamon_info();
    if ($rc['state'] != 'ok')
        return;

    log::add(__CLASS__, 'info', '*** Stopping blescanner deamon ***');
    $deamon = self::getDeamon();
    $deamon->send('removeTopic',self::getDiscoTopic());
    $topics = self::getRootTopics();
    foreach ($topics as $t) {
//	log::add(__CLASS__, 'debug', 'suppression du topic: ' . $t);
	$deamon->send('removeTopic',$t);
    }
    $deamon->stop();
    log::add(__CLASS__, 'info', '*** blescanner deamon stopped ***');
  }

  public static function getAntennaList() {
    $aNodes = array();
    $antennas = self::getDevices('Antenna');
    foreach ($antennas as $a) {
      if ($a->getIsEnable()) {
	$aUid= $a->getLogicalId();
	$aName = $a->getName();
	$cmdUid = $a->getConfiguration('antennaUid') . '-connectivity';
	//log::add(__CLASS__, 'debug', '[graph2] antenna: ' . $aName. ' LWT:' . $cmdUid);
	$b = $a->getCmdValue($cmdUid);
	$online = $b ? 'on':'off';
	$aNodes[$aUid]['name'] = $aName; // new
	$aNodes[$aUid]['online'] = $b ;
	$aNodes[$aUid]['picture'] = '/plugins/blescanner/data/images/bluetooth_' . $online . '.png';
      }
    }
    return $aNodes;
  }
/*
  public function postAjax() {
    log::add(__CLASS__, 'debug', '['. __FUNCTION__ . '] eqLogic= ' . $this->getName());
  }
*/
}

class blescannerCmd extends cmd {

  public function preSave() {
    $lid = $this->getLogicalId();
    $topic = $this->getConfiguration('topic');
    if (is_null($lid) || $this->getConfiguration('custom')) {
	$eqLogic = $this->getEqLogic();
        // log::add('blescanner', 'debug', '['. __FUNCTION__ . '] cmd ' . $this->getName() . ' logicalId: ' . $lid);
	$topic = $this->getConfiguration('topic');
	if ($topic == '')
		throw new Exception('Commande ' . $this->getName() .': le topic doit être renseigné: ' .$topic);
	if (strpos($topic,$eqLogic->getLogicalId()) === false)
		throw new Exception('Commande ' . $this->getName() .": le topic doit contenir l'ID du device");
	if ($this->getType() == 'action') {
		$payload = array();
		$str= '{' . $this->getConfiguration('command') . '}';
		$payload = json_decode($str,true);
		if (! $payload)
			throw new Exception('Commande ' . $this->getName() .': payload incorrect');
		$uid = array_keys($payload)[0] . rand(0,999);
		$this->setConfiguration('payload', "'". json_encode($payload) . "'");
	} else
		$uid = $this->getConfiguration('key');

	$uid = $eqLogic->getUid() . '-' . $uid;
	if ((is_null($lid)) || ($lid != $uid)) {
	    if (is_object($eqLogic->getCmd(null,$uid)))
		throw new Exception('Commande ' . $this->getName() .': cette clé existe déjà: '. $uid);
	   // log::add(__CLASS__, 'debug', 'PRESAVE: update command: '. $this->getName() . ' logicalId: ' . $uid);
	    $this->setLogicalId($uid);
	    $this->setConfiguration('custom', true);
        }
    }
  }

  public function evalPayload($val=null) {
    $payload = trim($this->getConfiguration('payload'),"'");
//  log::add('blescanner', 'debug', '[' . __FUNCTION__ . '] payload initial: '  .  $payload . ' value: '. $val);
    if (preg_match('/\{\{(.*)\}\}/', $payload, $expr)) {
	$expr2 = str_replace('value', $val, $expr[1]);
	$result = jeedom::evaluateExpression($expr2);
	$payload = str_replace($expr[0], $result, $payload);
//	log::add('blescanner', 'debug', 'payload final: ' . $payload);
    }
    return $payload;
  }

  // Exécution d'une commande
  public function execute($_options = array()) {
    log::add('blescanner', 'debug', '[' . __FUNCTION__ . '] $options: ' . json_encode($_options));
    $topic = $this->getConfiguration('topic');

    switch ($this->getSubType()) {
	case 'slider':
		$payload = $this->evalPayload($_options['slider']);
		break;
	case 'message':
		$payload = $this->evalPayload($_options['message']);
		break;
	case 'select':
		$payload = $this->evalPayload($_options['select']);
		break;
	default:
		$payload = $this->evalPayload();
    }

    log::add('blescanner', 'debug', '[' . __FUNCTION__ . '] topic: ' . $topic . ' payload: ' . $payload);
    blescanner::getDeamon()->send('publish', $topic, $payload);
  }
}

