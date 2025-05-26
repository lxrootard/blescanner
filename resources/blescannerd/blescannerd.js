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
var Jeedom = require('./jeedom/jeedom.js')
const fs = require('fs')
var LAST_SEND_TOPIC = {}

const args = Jeedom.getArgs()
if (typeof args.loglevel == 'undefined') {
  args.loglevel = 'debug'
}
Jeedom.log.setLevel(args.loglevel)

Jeedom.log.info('Start blescannerd')
Jeedom.log.info('Log level on  : ' + args.loglevel)
Jeedom.log.info('Socket port : ' + args.socketport)
Jeedom.log.info('MQTT : ' + args.mqtt_server)
Jeedom.log.info('Username : ' + args.username)
Jeedom.log.info('Password : ' + args.password)
Jeedom.log.info('PID file : ' + args.pid)
Jeedom.log.info('Apikey : ' + args.apikey)
Jeedom.log.info('Callback : ' + args.callback)
Jeedom.log.info('Cycle : ' + args.cycle)
/*
Jeedom.log.info('Client key : ' + args.client_key)
Jeedom.log.info('Client crt : ' + args.client_crt)
Jeedom.log.info('CA : ' + args.ca)
*/
Jeedom.write_pid(args.pid)
Jeedom.com.config(args.apikey, args.callback, args.cycle)
//Jeedom.com.test()
var mqtt = require('mqtt')
/*
if (args.ca) {
    var key = fs.readFileSync(args.client_key)
    var crt = fs.readFileSync(args.client_crt)
}
*/
var client = mqtt.connect(args.mqtt_server, {
    clientId: "mqtt-jeedom_"+Math.random().toString(16).substring(0, 8),
    rejectUnauthorized: false,
//    key: key,
//    cert: crt,
    username: args.username,
    password: args.password
})

Jeedom.log.info('Connect to mqtt server')

client.on('error', function(error) {
  Jeedom.log.error('Error on connection to mqtt server : ' + error)
  process.exit()
})

client.on('reconnect', function() {
  Jeedom.log.error('Reconnection to mqtt server')
})

client.on('connect', function() {
  Jeedom.log.info('Connection to mqtt server successfull')
/*
  client.subscribe('$SYS/#', function(err) {
    if (err) {
      Jeedom.log.error('Error on Subscription : ' + err)
      process.exit()
    }
    Jeedom.log.info('Subscription to SYS topic')
  })
*/
})

client.on('message', function(topic, message) {
  Jeedom.log.debug('Received message on topic : ' + topic + ' => ' + message.toString())
  try {
    json = JSON.parse (message.toString())
    Jeedom.com.add_changes(topic.replace(/\//g, '::'), json)
  } catch (e) {
    Jeedom.com.add_changes(topic.replace(/\//g, '::'), message.toString())
  }
})

Jeedom.http.config(args.socketport, args.apikey)

Jeedom.http.app.post('/addTopic', function(req, res) {
  try {
    if (!Jeedom.http.checkApikey(req))
	throw new Exception ('Invalid apikey')
    if(req.body.topic) {
	Jeedom.log.info('Adding topic: ' + req.body.topic)
	client.subscribe(req.body.topic + '/#', function(err) {
	if (err)
		throw new Exception (err)
	})
    }
    res.setHeader('Content-Type', 'application/json')
    res.send({ state: "ok" })
    return
  } catch (error) {
    Jeedom.log.debug('Error on topic addition : ' + error)
    res.setHeader('Content-Type', 'application/json')
    res.send({ state: "nok", result: JSON.stringify(error) })
  }
})

Jeedom.http.app.post('/removeTopic', function(req, res) {
  try {
    if (!Jeedom.http.checkApikey(req))
        throw new Exception ('Invalid apikey')
    if(req.body.topic) {
        Jeedom.log.info('Removing topic: ' + req.body.topic)
        client.unsubscribe(req.body.topic + '/#', function(err) {
        if (err)
                throw new Exception (err)
        })
    }
    res.setHeader('Content-Type', 'application/json')
    res.send({ state: "ok" })
    return
  } catch (error) {
    Jeedom.log.debug('Error on topic removal : ' + error)
    res.setHeader('Content-Type', 'application/json')
    res.send({ state: "nok", result: JSON.stringify(error) })
  }
})

Jeedom.http.app.post('/publish', function(req, res) {
  try {
    if (!Jeedom.http.checkApikey(req))
	throw new Exception ('Invalid apikey')

    if(req.body.topic) {
	Jeedom.log.info('Publish message on topic: ' + req.body.topic + ' => ' + String(req.body.message))
	client.publish(req.body.topic, String(req.body.message), function(err) {
	if (err)
		throw new Exception (err)
	})
    }
    res.setHeader('Content-Type', 'application/json')
    res.send({ state: "ok" })
    return
  } catch (error) {
    Jeedom.log.debug('Error on topic removal : ' + error)
    res.setHeader('Content-Type', 'application/json')
    res.send({ state: "nok", result: JSON.stringify(error) })
  }
})
