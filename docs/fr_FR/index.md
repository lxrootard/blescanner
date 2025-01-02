# Plugin Jeedom pour scanner les devices Bluetooth et superviser les antennes BLE via MQTT

![Logo Jeedom](../images/jeedom.png)
![Logo Plugin](../images/blescanner_icon.png)
![Logo Plugin](../images/theengs_icon.png)

Ce plugin permet de scanner les devices bluetooth et de découvrir/gérer les antennes BLE [OMG ESP32](https://docs.openmqttgateway.com) ou [Theengs gateway](https://gateway.theengs.io) via MQTT. 
Il est possible de surveiller et de gérer les paramètres des antennes ESP32 mais pas les antennes Theengs qui ne supportent pas (encore?) l'auto-découverte Home Assistant. 

Le plugin est complémentaire du [#plugin-mqttdiscovery](https://mips2648.github.io/jeedom-plugins-docs/MQTTDiscovery/fr_FR) et il peut aussi fonctionner sans. 
Dans ce dernier cas seuls des devices inconnus seront affichés. Avec quelques modifications il serait aussi possible de détecter certains devices du [#plugin-jmqtt](https://domotruc.github.io/jMQTT/fr_FR) (à vérifier)

## Documentation
- [Configuration](#configuration)
- [Auto-découverte](#auto-découverte)
- [Liste des devices connus](#liste-des-devices-connus)
- [Réseau des devices connus](#réseau-des-devices-connus)
- [Liste des devices inconnus](#liste-des-devices-inconnus)
- [Réseau des devices inconnus](#réseau-des-devices-inconnus)
- [Santé](#santé)
- [Antennes](#antennes)
- [FAQ](#faq)
- [Bugs et dépannage](#bugs-et-dépannage)

### Configuration
![config](../images/blescanner1.png)
Le plugin requiert le [#plugin-mqtt2](https://doc.jeedom.com/fr_FR/plugins/programming/mqtt2).

#### Paramètres:

- Utiliser MQTTDiscovery: C'est le mode par défaut qui permet de lister et d'afficher le réseau des devices connus cad déjà ajoutés au `#plugin-mqttdiscovery`
- Utiliser jMQTT: lister et afficher les devices du `#plugin-jmqtt` (sous réserve)
- Durée d'auto-découverte: Délai de détection des antennes et des devices (en minutes)
- Délai d'absence: délai après lequel les devices inactifs seront supprimés (em minutes, 0 pour les garder indéfiniment)
- Topic de découverte: topic d'auto-découverte Home Assistant. défaut: `homeassistant`
- Topics racines des equipements: topics surveillés par le plugin (au moins un). 

### Auto-découverte
![disco](../images/blescanner2.png)

L'auto-découverte est active au démarrage, les antennes détectées sont ajoutées automatiquement. Vous pouvez également l'interrompre ou la relancer avec le bouton auto-découverte.

### Liste des devices connus
![list1](../images/blescanner3.png)

Seront affichés les devices déjà ajoutés aux plugins `#plugin-mqttdiscovery` et/ou `#plugin-jmqtt`
<br>Il est également possible de filter les devices absents.

### Réseau des devices connus
![#network1](../images/blescanner4.png)

L'affichage contextuel peut se faire par atténuation ou par distance (si supporté). 
Il s'active en sélectionnant un noeud. L'animation peut être mise en pause.
<br>Il est également possible de filter les devices absents.

### Liste des devices inconnus
![list2](../images/blescanner5.png)

Affiche tous les devices non ajoutés dans les `#plugin-mqttdiscovery` ou `#plugin-jmqtt`.
 
Le bouton `Ajouter` permet de les ajouter au `#plugin-mqttdiscovery`.
<br>Si le device est auto-découvrable ses commandes seront automatiquement ajoutées, sinon seuls la présence et le RSSI seront disponibles.

### Réseau des devices inconnus
![#network1](../images/blescanner6.png)

### Santé
![Configuration](../images/blescanner9.png)

### Antennes
![Equipments](../images/blescanner7.png)

![Equipments](../images/blescanner8.png)

Les commandes disponibles dépendent du type d'antenne.
<br>Le bouton `Console web` permet d'accéder à l'interface d'administration des ESP32. 
<br>Pour plus d'informations voir la doc des [commandes ESP32](https://docs.openmqttgateway.com/use/gateway.html#system-commands-esp-only)

### FAQ

- Les antennes ne sont pas détectées ou certains devices ne s'affichent pas comme découvrables:
<br>Relancez l'auto-découverte sur vos antennes (`BT: Force scan` ou reboot de l'ESP32)
- Les commandes ESP32 ne sont pas créées
<br>Activez le paramètre `SYS: Auto discovery`
- Les distances ne s'affichent pas:
<br>Seuls les ESP32 remontent cette information (expérimental). Pour cela activez le paramètre `BT: Publish HASS presence`

### Bugs et dépannage
Voir le forum [Jeedom community](https://community.jeedom.com)
