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

/* * ***************************Includes********************************* */
require_once __DIR__  . '/../../../../core/php/core.inc.php';

class blescanner extends eqLogic {

  public static function dependancy_end() {
    log::add(__CLASS__, 'info', "Fin de configuration des dépendances");
    if (shell_exec(system::getCmdSudo() . ' which mosquitto_sub | wc -l') == 0)
                throw new Exception(__('commande mosquitto_sub non installée', __FILE__));
    if (config::byKey('ble_root_topics', __CLASS__) == null)
	config::save('ble_root_topics','theengs',__CLASS__);

    if (config::byKey('disco_topic',__CLASS__) == null)
        config::save('disco_topic', 'homeassistant',__CLASS__);

    if (config::byKey('devices_timeout',__CLASS__) == null)
	config::save('devices_timeout',2,__CLASS__);

    if (config::byKey('disco_timeout',__CLASS__) == null)
        config::save('disco_timeout',2,__CLASS__);

    if (config::byKey('use_mqttdiscovery',__CLASS__) == null)
	config::save('use_mqttdiscovery',true,__CLASS__);

    if (config::byKey('use_jmqtt',__CLASS__) == null)
	config::save('use_jmqtt',false,__CLASS__);
  }

  public static function getDiscoTopic() {
    return config::byKey('disco_topic',__CLASS__);
//    log::add(__CLASS__, 'debug', 'topic de discovery: ' . $disco);
    return $disco;
  }

  public static function getRootTopics() {
	$s = config::byKey('ble_root_topics', __CLASS__);
	// log::add(__CLASS__, 'debug', 'root topics: ' . $s);
	return explode(',',$s);
  }

  public function isAlive () {
	return self::getCmdValue($this, $this->getConfiguration('antennaUid') . '-connectivity');
  }

  public static function cron() {
	$timeout = config::byKey('devices_timeout',__CLASS__);
	if ($timeout == 0)
		return;
	$timeout = $timeout * 60;
	$knownDevices = cache::byKey('blescanner::known_devices')->getValue();
 	$unknownDevices = cache::byKey('blescanner::unknown_devices')->getValue();
	cache::set('blescanner::known_devices',$knownDevices = self::clean($knownDevices, $timeout));
	cache::set('blescanner::unknown_devices',$unknownDevices = self::clean($unknownDevices, $timeout));
  }

  public static function clean($devs, $timeout) {
	foreach ($devs as $key => $value) {
		$lastUpd = $value['lastUpdate'];
	//	log::add(__CLASS__, 'debug', 'clean, dev: ' . $key . ' timestamp: ' . $lastUpd);
		if ((strtotime('now') - $timeout) > $lastUpd) {
			// log::add(__CLASS__, 'debug', '['.__FUNCTION__.'] removing: ' . $key);
			$value['present'] = false;
			// unset ($devs[$key]);
		}
	}
	return $devs;
  }

  public static function getCmdValue($eqLogic, $cmdUid) {
	$cmd = $eqLogic->getCmd('info', $cmdUid);
        if (is_object($cmd)) {
		$value = $cmd->execCmd();
		// log::add('blescanner', 'debug', '['.__FUNCTION__ . '] antenna: ' . $eqLogic->getName() . ' cmdName: ' .
		// $cmd->getName() . ' cmdUid: ' . $cmd->getLogicalId() . ' value: ' . $value);
                return $value;
	}
	else
		return null;
  }

  public static function getJmqttUid($eqLogic) {
//	log::add(__CLASS__, 'debug', '['.__FUNCTION__ . '] dev id: ' . $eqLogic->getId());
	$conf = $eqLogic->getConfiguration();
        if (is_array($conf)) {
                $value = $conf['auto_add_topic'];
		if (isset($value)) {
			$tokens = explode('/', $value);
			array_pop($tokens);
			$value = array_pop($tokens);
                	// log::add(__CLASS__, 'debug', '['.__FUNCTION__ . '] l id du dev jMQTT: ' . $value);
                	return $value;
		}
        }
        else
                return null;
  }

  public static function getJmqttType($eqLogic) {
//      log::add(__CLASS__, 'debug', '['.__FUNCTION__ . '] dev id: ' . $eqLogic->getId());
        $conf = $eqLogic->getConfiguration();
        if (is_array($conf))
                return $conf['type'];
        else
                return null;
  }

/*
  public static function getJmqttIcon($eqLogic) {
//      log::add(__CLASS__, 'debug', '['.__FUNCTION__ . '] dev id: ' . $eqLogic->getId());
        $conf = $eqLogic->getConfiguration();
        if (is_array($conf))
                return $conf['icone'];
        else
                return null;
  }
*/

  public static function initSettings() {
    if (mqtt2::deamon_info()['state'] != 'ok') {
	self::send_alert("Le démon MQTT Manager n'est pas démarré", __FILE__);
	return false;
    }
    $useMqttDisco = config::byKey('use_mqttdiscovery', __CLASS__);
    if ($useMqttDisco) {
	if (!class_exists('MQTTDiscovery')) {
		self::send_alert("Le plugin MQTT Discovery n'est pas installé", __FILE__);
		return false;
	}
        if (MQTTDiscovery::deamon_info()['state'] != 'ok') {
		self::send_alert("Le démon MQTT Discovery n'est pas démarré", __FILE__);
		return false;
	}
    }
    $useJmqtt = config::byKey('use_jmqtt', __CLASS__);
    if ($useJmqtt) {
        if (!class_exists('jMQTT')) {
		self::send_alert("Le plugin jMQTT n'est pas installé", __FILE__);
		return false;
	}
        if (jMQTT::deamon_info()['state'] != 'ok') {
                self::send_alert("Le démon jMQTT n'est pas démarré", __FILE__);
		return false;
	}
    }

    $t1 =  intval(config::byKey('disco_timeout',__CLASS__));
    log::add(__CLASS__, 'debug', '>>> timeout auto-découverte: ' . $t1 . ' mins');
    if (! is_int($t1) || ($t1 < 1)) {
	self::send_alert("La durée d'auto-découverte doit être un nombre entier de minutes", __FILE__);
	return false;
    }
    $t2 =  intval(config::byKey('devices_timeout',__CLASS__));
    log::add(__CLASS__, 'debug', '>>> timeout devices: ' . $t2 . ' mins');
    if (! is_int($t2) || $t2 <0) {
	self::send_alert("Le délai d'absence doit être un nombre entier de minutes", __FILE__);
	return false;
    }
    if (config::byKey('ble_root_topics', __CLASS__) == null) {
	self::send_alert("Ajoutez au moins un topic à surveiller", __FILE__);
	return false;
    }

    $mqttSettings = mqtt2::getFormatedInfos();
    log::add(__CLASS__, 'debug', 'MQTT settings:' . json_encode($mqttSettings));
    cache::set('blescanner::unknown_devices',array());
    cache::set('blescanner::known_devices',array());
    cache::set('blescanner::display_mode','Attenuation');
    cache::set('blescanner::display_away','off');
    cache::set('blescanner::disco', false);
    return true;
  }

  public static function startDisco() {
    $disco = cache::byKey('blescanner::disco')->getValue();
    //log::add(__CLASS__, 'debug', '['. __FUNCTION__ .'] disco=' . $disco);
    if ($disco)
	return;
    $timeout = config::byKey('disco_timeout', __CLASS__) . ' minute';
    $msg = "*** Début d'auto-découverte, durée: " . $timeout . "(s)";
    log::add(__CLASS__, 'debug', $msg);
    cache::set('blescanner::disco', true);
    $topic = self::getDiscoTopic();
    log::add(__CLASS__, 'debug', 'topic de discovery: ' . $topic);
    mqtt2::removePluginTopic($topic);
    mqtt2::addPluginTopic(__CLASS__, $topic);

    event::add('blescanner::discovery', array('message' =>  __($msg, __FILE__), 'type' => 'start'));
    self::executeAsync ('stopDisco', $timeout);
  }

  public static function stopDisco() {
     $disco = cache::byKey('blescanner::disco')->getValue();
     // log::add(__CLASS__, 'debug', '['. __FUNCTION__ .'] disco=' . $disco);
     if (! $disco)
	return;
     $msg = "*** Fin d'auto-découverte";
     log::add(__CLASS__, 'debug', $msg);
     mqtt2::removePluginTopic(self::getDiscoTopic());
     event::add('blescanner::discovery', array('message' =>  __($msg, __FILE__), 'type' => 'stop'));
     cache::set('blescanner::disco', false);
  }

  public function updateDevices($message) {
     if (!($this->getIsEnable()))
	return;
     $useMqttDisco = config::byKey('use_mqttdiscovery',__CLASS__);
     $useJmqtt = config::byKey('use_jmqtt',__CLASS__);

//     log::add(__CLASS__, 'debug', '['. __FUNCTION__ .'] antenna: ' . $this->getName() . ' message: ' . json_encode($message));
     foreach ($message as $key => $value) {
	if (is_array($value) && isset($value["id"])) {
		$mLogic = self::byLogicalId($key,'MQTTDiscovery');
		$jLogic = self::byLogicalId($key,'jMQTT');
		if ($useMqttDisco && is_object($mLogic))
			$this->updateDevice($key,$value,$mLogic);
		else if ($useJmqtt && is_object($jLogic))
			$this->updateDevice($key,$value,$jLogic);
		else
			$this->updateDevice($key,$value,null);
	}
     }
  }

  public function updateDevice ($key, $value, $dLogic) {
	$antenna = $this->getLogicalId();
	// log::add(__CLASS__, 'debug', '['. __FUNCTION__ .'] antenna: ' . $this->getName() . ' device: ' . json_encode($value));
	$unknownDevices = cache::byKey('blescanner::unknown_devices')->getValue();
	$knownDevices = cache::byKey('blescanner::known_devices')->getValue();
	$known = is_object($dLogic);

	$dev = ($known)? $knownDevices[$key] : $unknownDevices[$key];
	if (! isset($dev))
		$dev = array();
	$dev['id'] = $value["id"];
	$dist = is_numeric($value["distance"])? round($value["distance"],2) : -1;
	$dev['rssi ' . $antenna] = $value["rssi"];
	$dev['distance ' . $antenna] = $dist;
	$dev['lastUpdate'] = strtotime('now');
	$dev['present'] = true;

	if ($known) {
		$knownDevices[$key] = $dev;
		cache::set('blescanner::known_devices',$knownDevices);
	} else {
		$dev['name'] = $value["name"];
		$dev['model'] = $value["model"];
		$dev['brand'] = $value["brand"];
		$other = $value;
		$cols = array('id','rssi','name','model','brand','distance');
		foreach($cols as $k)
		unset($other[$k]);
		$dev['other'] = $other;
                if (! isset($dev['discoverable']))
			$dev['discoverable'] = false;
		$unknownDevices[$key] = $dev;
		cache::set('blescanner::unknown_devices',$unknownDevices);
	}
  }

  public static function addDevice($id) {
   if (! $useMqttDisco = config::byKey('use_mqttdiscovery',__CLASS__))
	return;
   $eqLogic = self::byLogicalId($id,'MQTTDiscovery');
   if (is_object($eqLogic))
	return;
   $devices = MQTTDiscovery::getDiscoveredDevices();
   $dev = $devices[$id];
   if (is_array($dev)) {
   	$eqLogic = self::createDevice($id, $dev);
	$eqLogic->setConfiguration('addCommandsFromDiscoveryConfig', '1')->save(true);
   } else {
	$devices = cache::byKey('blescanner::unknown_devices')->getValue();
	$dev = $devices[$id];
	if (is_array($dev))
		$eqLogic = self::createDevice($id, $dev);
   }
   if (! is_object($eqLogic))
	return;
   log::add(__CLASS__, 'debug', '[' . __FUNCTION__ . '] device ajouté:' . $eqLogio);
   $eqLogic->preInsert();
   $eqLogic->postInsert();
   log::add(__CLASS__, 'debug', '[' . __FUNCTION__ . '] fin de création ' . $id);
  }

  public static function createDevice($id, $dev) {
   if (! is_array($dev))
	return null;
   $brand = isset($dev['manufacturer']) ? $dev['manufacturer'] : $dev['brand'];
   $payload = array ('logicalId' => $id,
	'name' => $dev['name'],
	'manufacturer' => $brand,
	'model' => $dev['model'],
	'sw_version' => $dev['sw_version'],
	'configuration_url' => ''
   );
   $eqLogic = MQTTDiscovery::createDevice($payload);
   log::add(__CLASS__, 'debug', '[' . __FUNCTION__ . '] nouveau device créé id:' .$id . ' payload: ' . json_encode($payload));
   return $eqLogic;
  }

  public static function handleMqttMessage($message) {
   // log::add(__CLASS__, 'debug', '[' . __FUNCTION__ . '] message: ' . json_encode($message));
   $root_topics = self::getRootTopics();
   $disco=cache::byKey('blescanner::disco')->getValue();

   foreach ($message as $topic => $content) {
//      log::add(__CLASS__, 'debug', 'topic: ' . $topic);
	if (($disco) && ($topic == self::getDiscoTopic()))
		self::handleDiscovery ($content);
        else if (in_array($topic, $root_topics))
		self::handleMqttTopic ($topic, $content);
//      else
//              log::add(__CLASS__, 'debug', "Le message reçu n'est pas un message ". json_encode($root_topics));
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
   // log::add(__CLASS__, 'debug', '[' . __FUNCTION__ . '] type= '. $type . ' message= ' . json_encode($message));
   foreach ($message as $key => $content) {
	$config = $content['config'];
	if (isset($config))
		self::handleAttrDiscovery($key, $type, $config);
    }
  }

  // manage device uid=48E729A19AB8-uptime type=switch config = {}
  public static function handleAttrDiscovery($uid, $type, $config) {
   $dev = $config['device'];
   // log::add(__CLASS__, 'debug', 'name=' . $dev['name'].' mf=' . $dev['mf']);
   if (is_array($dev)) {
	// log::add(__CLASS__, 'debug',  __FUNCTION__ .' uid: '. $uid. ' name: ' . $config['name'] . ' type: '. $type .' for device: ' . $dev['name']);
	if ($dev['mf'] == 'OMG_community') {
   		$aLogic = eqLogic::byLogicalId($dev['name'], __CLASS__);

		if ((is_object($aLogic)) && ($aLogic->getConfiguration('antennaType') == 'Unknown')) {
			$aLogic->remove();
			$aLogic = null;
		}
   		if (!is_object($aLogic))
			$aLogic = self::createOMGAntenna($dev);
		$uid = $config['uniq_id'];
		$uid = preg_replace('/_/', '', $uid);
		// log::add(__CLASS__, 'debug', '>>> UID modifié: ' . $uid);

		if (($type != 'button') && ($type != 'update'))
				$cmd = $aLogic->createInfoCmd($uid, $config, $type);
		if ($type == 'button')
			$aLogic->createActionCmd($uid.'-button', $config['name'], $type, $config['cmd_t'], $config['pl_prs']);
		else if ($type == 'update')
			$aLogic->createActionCmd($uid, $config['name'], $type, $config['cmd_t'], $config['payload_install']);
               	else if ($type == 'number')
			$aLogic->createActionCmd($uid.'-set', ($config['name']).' Set', $type, $config['cmd_t'], $config['cmd_tpl'], $cmd);
		else if ($type == 'switch') {
			$aLogic->createActionCmd($uid.'-on', ($config['name']).' On', $type, $config['cmd_t'], $config['pl_on'], $cmd);
			$aLogic->createActionCmd($uid.'-off', ($config['name']).' Off', $type, $config['cmd_t'], $config['pl_off'], $cmd);
		}
   	} else if (is_array($dev['ids'])) {
			$uid = $dev['ids'][0];
			if (! isset($uid))
				return;
			$mLogic = self::byLogicalId($uid,'MQTTDiscovery');
			$useMqttDisco = config::byKey('use_mqttdiscovery',__CLASS__);
			if ($useMqttDisco && (! is_object($mLogic))) {
			//	log::add(__CLASS__, 'debug', '>> nouveau device découvrable inconnu: '. $uid . ' name: '. $config['name']);
				$unknownDevices = cache::byKey('blescanner::unknown_devices')->getValue();
				$device = $unknownDevices[$uid];
				if (!isset($device))
					$device = array();
				$device['discoverable'] = true;
				if (!isset ($device['present']))
					$device['present'] = false;
				$unknownDevices[$uid] = $device;
				cache::set('blescanner::unknown_devices',$unknownDevices);
				// log::add(__CLASS__, 'debug', '>> la liste apres= ' . json_encode($unknownDevices));
			}
	}
   }
  }

  public function createInfoCmd ($uid, $config, $type) {
    $cmd = $this->getCmd('info',$uid);
    if (is_object($cmd))
	return $cmd;

    $unit = null;
    $stype = self::getSubTypeFromHA($type,$config);
    // log::add(__CLASS__, 'debug', '$stype= ' . $stype);
    if ($stype == 'numeric')
	$unit = $config['unit_of_meas'];
    $key = preg_replace('/' . $this->getConfiguration('antennaUid') . '-/', '', $uid);
    if (isset($config['val_tpl'])) {
        $key = preg_replace('/value_json./', '', $config['val_tpl']);
        $key = preg_replace('/[{}\/|]/', ' ', $key);
        $key = strtok ($key, ' ');
    }
    $cmd = $this->createCmd($uid, $config['name'], $config['stat_t'], $key, $stype, $unit);
    $calc = preg_replace('/\|.*/','',$config['val_tpl']);
    $calc = preg_replace('/[} ]/', '', $calc);
    $calc = preg_replace('/.*'.$key.'/', '', $calc);
    if ($calc != '')
	$calc = '#value# ' . $calc;
    else
	$calc = null;
    if ($key == 'connectivity')
	self::checkAndUpdateCmd($uid, true);
    if (isset($calc)) {
	$cmd->setConfiguration('calculValueOffset', $calc);
	$cmd->save();
    }
    return $cmd;
  }

  public function createActionCmd ($uid, $name , $type, $topic, $payload, $info=null) {
    $cmd = $this->getCmd('action',$uid);
    if (is_object($cmd))
        return $cmd;

     $key = preg_replace('/' . $this->getConfiguration('antennaUid') . '-/', '', $uid);
     $cmd = $this->createCmd ($uid, $name, $topic, $key, 'other', null);
//     $payload = '{' . preg_replace('/[{}]/', '', $payload) . '}';
     log::add(__CLASS__, 'debug', '>>> createActionCmd uid=' . $uid . ': ' . $payload);
     $cmd->setConfiguration ('payload', $payload);
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
   log::add(__CLASS__, 'debug', '*** createOMGAntenna id: '. $id . ' name: ' . $dev['name'] . ' type: ' . $dev['mf']);
   $eqLogic = new self();
   $eqLogic->setLogicalId($dev['name']);
   $eqLogic->setName($dev['name']);
   $eqLogic->setEqType_name(__CLASS__);
 //	$eqLogic->setObject_id(19); // temp
   $eqLogic->setIsEnable(1);
   $eqLogic->setIsVisible(1);
 //  $eqLogic->setCategory('monitoring', 1);
   $eqLogic->setConfiguration('antennaUid', $id);
   $eqLogic->setConfiguration('antennaType', $dev['mf']);
   $eqLogic->setConfiguration('antennaVersion', $dev['sw']);
   $eqLogic->setConfiguration('antennaModel', $dev['mdl']);
   $eqLogic->setConfiguration('antennaWebURL',$dev['cu']);
   $eqLogic->save();
   event::add('blescanner::newAntenna', '');
   return $eqLogic;
  }

  public function createCmd($uid, $name, $topic, $key, $stype, $unit=null)
  {
   log::add(__CLASS__, 'info', '['. __FUNCTION__ . '] uid: '. $uid .' name: ' . $name . ' type: ' . $stype . ' unit: ' . $unit);
   $cmd = $this->getCmd(null, $name);
   if (!is_object($cmd)) {
	// log::add(__CLASS__, 'info', 'creating the command: '. $uid . ' / ' . $name . ' / ' . $stype);
        $cmd = new blescannerCmd();
        $cmd->setLogicalId($uid);
        $cmd->setEqLogic_id($this->getId());
        $cmd->setName($name);
        $cmd->setIsHistorized(0);
        $cmd->setIsVisible(1);
	if ($stype == "other")
		$cmd->setType("action");
	else
        	$cmd->setType("info");
        $cmd->setSubType($stype);
	$cmd->setConfiguration('topic',$topic);
	$cmd->setConfiguration('key',$key); // sert a rien pour l instant
        $cmd->setGeneric_type("GENERIC_INFO");
        if (isset($unit)) {
                $cmd->setUnite($unit);
    		if ($unit == 's')
        		$cmd->setConfiguration('historizeRound','2');
	}
	$cmd->save();
        return $cmd;
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
                                $this->checkAndUpdateCmd($cmdUid, $value); // difference avec  $cmd->event($value) ???
		}
	}
      }
  }

  public function updateBtConfig($topic, $msg) {
    // log::add(__CLASS__, 'debug', '[' . __FUNCTION__ . '] msg= ' . json_encode($msg));
    $this->updateDevices($msg);
    $this->updateAntennaConfig($topic, $msg);
  }

  public static function  updateLWT ($key, $alive, $rootTopic) {
    $eqLogic = self::byLogicalId($key, __CLASS__);
    if (is_object($eqLogic)) {
	//log::add(__CLASS__, 'debug', '[' . __FUNCTION__ . '] antenna: ' . $key . ' LWT: ' . $alive);
	//log::add(__CLASS__, 'debug', 'eqLogic id: ' . $eqLogic->getId());
	$b = (strpos($alive,'online')!== false) ? true : false;
	//log::add(__CLASS__, 'debug', '*** $b=' . $b);
	$cmdUid = $eqLogic->getConfiguration('antennaUid') . '-connectivity';
	//log::add(__CLASS__, 'debug', '*** cde LWT= ' . $cmdUid);
	$eqLogic->checkAndUpdateCmd($cmdUid, $b);
//		$cmd->event($alive);
    } else {
/*
	$disco=cache::byKey('blescanner::disco')->getValue();
	if ($disco)
		self::createUnknownAntenna($key, $rootTopic);
*/
    }
  }

  public static function handleMqttTopic($rootTopic, $msg) {
  //  log::add(__CLASS__, 'debug', '[' . __FUNCTION__ . '] topic: ' . $rootTopic . ' msg=' . json_encode($msg));
    $disco=cache::byKey('blescanner::disco')->getValue();
    foreach ($msg as $key => $value) {
	$alive = $value["LWT"];
	if (isset($alive))
		self::updateLWT($key,$alive,$rootTopic);

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

 public function checkAntenna () {
   $mqttInfos = mqtt2::getFormatedInfos();
   $host = $mqttInfos['ip'];
   $port = $mqttInfos['port'];
   $user = $mqttInfos['user'];
   $passwd = $mqttInfos['password'];
   $cmdUid = $this->getConfiguration('antennaUid') . '-connectivity';
   //log::add(__CLASS__, 'debug', '[' .__FUNCTION__ . '] LWT command uid: ' . $cmdUid);
   $cmd = $this->getCmd('info', $cmdUid);

   //log::add(__CLASS__, 'debug', ' LWT command name: ' . $cmd->getName());
 
   $topic = $cmd->getConfiguration('topic');
   //log::add(__CLASS__, 'debug', __FUNCTION__ . ' LWT topic: ' . $topic);

   $cmdline = ' mosquitto_sub -W 1 -C 1 -L mqtt://' . $user . ':' . $passwd . '@'
        . $host . ':' . $port . '/' . $topic; //. '/' . $this->getLogicalId() . '/+';
   //log::add(__CLASS__, 'debug', '>>> checkAntenna command: '. $cmdline);
   $rc = shell_exec(system::getCmdSudo() . $cmdline);
   log::add(__CLASS__, 'debug', '>>> check antenna '. $this->getLogicalId() . ' status: ' . $rc);
   $b = (strpos($rc,'online')!== false)? true : false;
   $this->checkAndUpdateCmd($cmdUid, $b);
 }

 public static function createUnknownAntenna($key,$topic)
 {
   $eqLogic = eqLogic::byLogicalId($key, __CLASS__);
   if (!is_object($eqLogic)) {
	log::add(__CLASS__, 'info','[' . __FUNCTION__ .  '] new antenna: '. $key . ' topic: ' . $topic);
        $eqLogic = new self();
        $eqLogic->setLogicalId($key);
        $eqLogic->setName($key);
        $eqLogic->setEqType_name(__CLASS__);
//      $eqLogic->setObject_id(19); // temp
   	$eqLogic->setIsEnable(1);
   	$eqLogic->setIsVisible(1);
	$eqLogic->setConfiguration('antennaType', 'Unknown');
	$eqLogic->setConfiguration('antennaUid',$key);
 //  $eqLogic->setCategory('monitoring', 1);
   	$eqLogic->save();
	$topic = $topic.'/'.$key.'/LWT';
	$uid = 'connectivity';
	$lwt = $eqLogic->createCmd($key.'-'.$uid, 'Connectivity', $topic, $uid, 'binary');
 	$eqLogic->checkAndUpdateCmd($key.'-'.$uid, true);
	event::add('blescanner::newAntenna', '');
    }
    return $eqLogic;
 }

 public function createCommand($name, $type)
 {
  //log::add(__CLASS__, 'info', '*** createCommand: '. $name . ' ' . $type);
   $cmd = $this->getCmd(null, $name);
   if (!is_object($cmd)) {
       log::add(__CLASS__, 'info', 'creating the command: '. $name . ' ' . $type);
        $cmd = new blescannerCmd();
        $cmd->setLogicalId($name);
        $cmd->setEqLogic_id($this->getId());
        $cmd->setName($name);
        $cmd->setIsHistorized(false);
        $cmd->setIsVisible(true);
        $cmd->setType("info");
        $cmd->setSubType($type);
        $cmd->setGeneric_type("GENERIC_INFO");
        $cmd->save();
        return $cmd;
   }
 }

 // @Mips
 public static function executeAsync(string $_method, $_date = 'now') {
	if (!method_exists(__CLASS__, $_method))
		throw new InvalidArgumentException(
			"Method provided for executeAsync does not exist: " . $_method);

	$cron = new cron();
        $cron->setClass(__CLASS__);
        $cron->setFunction($_method);

 	$cron->setOnce(1);
        $scheduleTime = strtotime($_date);
        $cron->setSchedule(cron::convertDateToCron($scheduleTime));
        $cron->save();
        if ($scheduleTime <= strtotime('now')) {
        	$cron->run();
        	log::add(__CLASS__, 'debug', "Task '{$_method}' executed now");
        } else
                log::add(__CLASS__, 'debug', "Task '{$_method}' scheduled at {$_date}");
 }

  public static function send_alert($msg) {
    log::add(__CLASS__, 'error', __($msg, __FILE__), 'unableStartDeamon');
    event::add('jeedom::alert', array('level' => 'error', 'page' => 'plugin',
		'message' => $msg));
    // throw new Exception(__($msg, __FILE__));
  }

  public static function deamon_info() {
    $rc = array();
    $rc['log'] = __CLASS__;
    $rc['state'] = 'nok';
    $rc['launchable'] = 'ok';
    if (config::byKey('deamonStatus', __CLASS__,'') == "running")
            $rc['state'] = 'ok';

    if (!class_exists('mqtt2')) {
        $rc['launchablemessage'] = __("Le plugin MQTT Manager n'est pas installé", __FILE__);
	$rc['launchable'] = 'nok';
    }

    if ($rc['launchable'] == 'nok')
	log::add(__CLASS__, 'debug', '['.__FUNCTION__ . '] msg: ' . $rc['launchablemessage']);

    return $rc;
  }

  public static function deamon_start() {
    self::deamon_stop();
    $rc = self::deamon_info();
    if ($rc['launchable'] != 'ok')
	throw new Exception(__('Veuillez vérifier la configuration', __FILE__), 'unableStartDeamon');

    if (! self::initSettings())
	return false;

    $antennas = eqLogic::byType('blescanner');
    foreach ($antennas as $a)
        if ($a->getIsEnable())
                $a->checkAntenna();

    self::startDisco();
    $topics = self::getRootTopics();
    log::add(__CLASS__, 'info', 'root topics: ' . json_encode($topics));

    foreach ($topics as $t) {
	log::add(__CLASS__, 'debug', 'ajout du topic: ' . $t);
	mqtt2::addPluginTopic(__CLASS__,$t);
    }

    $deamon_info = self::deamon_info();
    if ($deamon_info['launchable'] != 'ok') {
        throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
    }

    log::add(__CLASS__, 'info', 'Lancement démon');
    // $result = exec($cmd . ' >> ' . log::getPathToLog('blescanner_daemon') . ' 2>&1 &'); // nommer votre log en commençant par le plugin>
    $i = 0;
    config::save('deamonStatus', 'running' ,__CLASS__);

    while ($i < 3) {
        $deamon_info = self::deamon_info();
        if ($deamon_info['state'] == 'ok')
            break;
        sleep(1);
        $i++;
    }
    if ($i >= 3) {
        log::add(__CLASS__, 'error', __('Impossible de lancer le démon, vérifiez la log', __FILE__), 'unableStartDeamon');
        return false;
    }
    message::removeAll(__CLASS__, 'unableStartDeamon');
    log::add(__CLASS__, 'info', 'Démon blescanner lancé');
    return true;
  }


  public static function deamon_stop() {
    log::add(__CLASS__, 'info', 'Arrêt démon');
    $topics = self::getRootTopics();
    foreach ($topics as $t) {
        log::add(__CLASS__, 'debug', 'suppression du topic: ' . $t);
        mqtt2::removePluginTopic($t);
    }
    mqtt2::removePluginTopic(self::getDiscoTopic());
    $antennas = eqLogic::byType('blescanner');
    foreach ($antennas as $a) {
	$cmdUid = $a->getConfiguration('antennaUid') . '-connectivity';
	$a->checkAndUpdateCmd($cmdUid, false);
    }
    config::save('deamonStatus', 'stopped' ,__CLASS__);
    cache::delete('blescanner::known_devices');
    cache::delete('blescanner::unknown_devices');
    cache::set('blescanner::disco', false);
    sleep(1);
  }

  public static function getAntennaList() {
    $aNodes = array();
    $antennas = eqLogic::byType(__CLASS__);
    foreach ($antennas as $a) {
      if ($a->getIsEnable()) {
        $aUid= $a->getLogicalId();
        $aName = $a->getName();
        $cmdUid = $a->getConfiguration('antennaUid') . '-connectivity';
        //log::add('blescanner', 'debug', '[graph2] antenna: ' . $aName. ' LWT:' . $cmdUid);
        $b = self::getCmdValue($a, $cmdUid);
        //log::add('blescanner', 'debug', '$b: ' . $b);
        $online = $b ? 'on':'off';
        $aNodes[$aUid]['name'] = $aName; // new
        $aNodes[$aUid]['online'] = $b ;
        $aNodes[$aUid]['picture'] = '/plugins/blescanner/data/images/bluetooth_' . $online . '.png';
      }
   }
   return $aNodes;
  }

}

class blescannerCmd extends cmd {

 /* helper */
  public static function getCommandsFileContent(string $filePath) {
        if (!file_exists($filePath)) {
                throw new RuntimeException("Fichier de configuration non trouvé: {$filePath}");
        }
        $content = file_get_contents($filePath);
        if (!is_json($content)) {
                throw new RuntimeException("Fichier de configuration incorrect: {$filePath}");
        }
        return json_decode($content, true);
  }

  public function evalPayload($value=null) {
    $expr = [];
    $payload = $this->getConfiguration('payload');
//    log::add('blescanner', 'debug', '[' . __FUNCTION__ . '] payload initial: '  .  $payload);
    if (preg_match('/\{\{(.*)\}\}/i', $payload, $expr) ===1) {
//	log::add('blescanner', 'debug', 'expr 2 eval: ' . $expr[1]);
    	$result = jeedom::evaluateExpression(str_replace('value', $value, $expr[1]));
//	log::add('blescanner', 'debug', 'result: ' . $result);
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

    log::add('blescanner', 'debug', '>>> topic: ' . $topic . ' payload: ' . $payload);
    mqtt2::publish($topic, $payload);

  }

}

