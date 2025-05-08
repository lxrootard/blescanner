# Jeedom Plugin for managing Bluetooth Devices and ESP32 BLE Antennas with MQTT

![Jeedom Logo](../images/jeedom.png)
![Plugin Logo](../images/blescanner_icon.png)
![Plugin Logo](../images/theengs_icon.png)

This plugin discovers and manages Bluetooth devices and [OMG ESP32](https://docs.openmqttgateway.com) BLE antennas. [Theengs](https://gateway.theengs.io) antennas are also recognized, but their parameters are not (yet?) available due to lack of Home Assistant auto-discovery support. The plugin does not replace the [#plugin-theengs](https://mips2648.github.io/jeedom-plugins-docs/tgw/en_US/) to deploy and manage Theengs antennas.

## Documentation
- [Configuration](#configuration)
- [Auto-Discovery](#auto-discovery)
- [Unknown Devices List](#unknown-devices-list)
- [Unknown Devices Network](#unknown-devices-network)
- [Known Devices List](#known-devices-list)
- [Known Devices Network](#known-devices-network)
- [Devices and Antennas](#devices-and-antennas)
- [Antennas Health](#antennas-health)
- [FAQ](#faq)
- [Bugs and Troubleshooting](#bugs-and-troubleshooting)

### Configuration

This plugin requires the [#plugin-mqtt2](https://doc.jeedom.com/fr_FR/plugins/programming/mqtt2).

#### Parameters:

- Auto creation: automatically create BLE devices (disabled by default)
- Auto-discovery duration: detection delay for antennas and devices (in minutes)
- Missing timeout: time after which inactive devices will be deleted (in minutes, 0 to keep them indefinitely)
- Discovery topic: Home Assistant auto-discovery topic. Default: `homeassistant`
- Devices root topics: topics monitored by the plugin (at least one required).

![config](../images/blescanner1.png)

### Auto-Discovery

Auto-discovery is active on startup, and detected antennas are added automatically. You can also stop or restart it using the `Auto-discovery` button.  
If auto-creation is checked in the configuration page, devices will be automatically added at the end of synchronization.

![disco](../images/blescanner2.png)

### Unknown Devices List

Displays all unmanaged devices. The `Discoverable` column indicates whether the devices support auto-discovery. You can filter table rows by clicking on column headers.  
The `Add` button allows adding discovered devices one by one. If the device supports auto-discovery, its commands will be automatically created; otherwise only presence and RSSI will be available.

![list2](../images/blescanner5.png)

### Unknown Devices Network

Contextual display can be shown by signal attenuation or by distance (if supported by the antenna).  
It is activated by selecting a node. The animation can be paused and the graph can be zoomed.

![#network1](../images/blescanner6.png)

### Known Devices List

Displays already added devices. Missing devices can also be displayed, and you can filter table rows by clicking on column headers.

![list1](../images/blescanner3.png)

### Known Devices Network

Contextual display can be shown by attenuation or distance (if supported by the antenna). It is activated by selecting a node. The animation can be paused. Missing devices can also be filtered.

![#network1](../images/blescanner4.png)

### Devices and Antennas

Available commands depend on the type of device or antenna.  
A custom image can be added by device category.

Theengs antennas only support presence detection. ESP32 antennas provide several commands (see `Bluetooth` and `System` tabs).  
The `Web Console` button provides access to the ESP32 admin interface.  
For more info, see the [ESP32 commands reference](https://docs.openmqttgateway.com/use/gateway.html#system-commands-esp-only)

![Equipments](../images/blescanner7.png)  
![Equipments](../images/blescanner8.png)

#### Custom Commands

The `Custom` tab allows adding custom commands found in advertising data but not in auto-discovery (depending on the device). Example: temperature in Fahrenheit (`tempf`) for a reprogrammed Xiaomi `lywsd03mmc`.

![Configuration](../images/blescanner11.png)

Similarly for missing `ESP32` commands like enabling/disabling external decoding or message count:

![Configuration](../images/blescanner10.png)

For actions, the `payload` field must be in the format `"key1":value1 , "key2":value2...`  
Both advertising and auto-discovered data are available in the `Other Data` column of the [Unknown Devices List](#liste-des-devices-inconnus) page.

### Antennas Health

![Configuration](../images/blescanner9.png)

### FAQ

- Antennas are not detected or some devices are not shown as discoverable:  
  Restart auto-discovery on your antennas (`BT: Force scan` or reboot the ESP32 or Theengs antenna)
- ESP32 commands are not created:  
  Enable the `SYS: Auto discovery` parameter
- Advertising data not shown (ESP32):  
  Enable `BT: Publish Advertisement data` parameter
- Distances not shown:  
  Only ESP32 reports this info starting from firmware v1.8 (experimental feature). Enable `BT: Publish HASS presence` for this.

### Bugs and Troubleshooting

See the [Jeedom community forum](https://community.jeedom.com)