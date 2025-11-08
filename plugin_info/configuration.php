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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');

if (!isConnect()) {
  include_file('desktop', '404', 'php');
  die();
}
?>

<style>
  .topics-list {
   width: 400px;
  }
  .root-topics ul {
    list-style: none;
    padding: 0;
  }
  .root-topics li {
    display: inline-block;
    background-color:  var(--al-info-color);
    color: white;
    border: 1px solid lightgrey;
    padding: 0px 4px 0px 4px;
    margin-right: 5px;
    margin-top: 5px;
  }
  .bt_remove_topic {
    background-color: transparent;
    border: none;
    color: white;
    margin-left: 5px;
  }
  .warning-tooltip {
    color: var(--al-warning-color);
  }

</style>

<form class="form-horizontal">
  <fieldset>
     <div class="col-md-6">
	<legend><i class="fab fa-hubspot"></i>&nbsp;{{Broker MQTT}}</legend>
	<div class="form-group">
	   <label class="col-lg-4 control-label">{{Adresse du broker}}&nbsp;
	     <sup><i class="fa fa-question-circle tooltips" title="{{URL du service MQTT}}"></i></sup>
	   </label>
	   <div class="col-lg-6 input-group">
	     <span class="input-group-addon">mqtt://</span>
	     <input class="configKey form-control tooltips" data-l1key="broker" data-l2key="host" placeholder="localhost"
		title="{{Adresse IP du Broker. Défaut: localhost}}"/>
	     <span class="input-group-addon">:</span>
	     <input class="configKey form-control tooltips" data-l1key="broker" data-l2key="port" placeholder="1883"
		type="number" min="1" max="65535" title="{{Port  du Broker. Défaut: 1883}}"/>
	   </div>
	</div>
	<div class="form-group">
	   <label class="col-lg-4 control-label">{{Authentification}}&nbsp;
	     <sup><i class="fa fa-question-circle tooltips" title="{{Utilisateur et mot de passe}}"></i></sup>
	   </label>
	   <div class="col-lg-6 input-group">
	     <input class="configKey form-control" data-l1key="broker" data-l2key="user"  placeholder="utilisateur mqtt"/>
             <span class="input-group-addon">:</span>
             <input class="configKey form-control" data-l1key="broker" data-l2key="passwd" type="password" placeholder="mot de passe mqtt"/>
	   </div>
	</div>
	<div class="form-group">
	   <label class="col-lg-4 control-label">{{Topic de découverte}}&nbsp;
	     <sup><i class="fas fa-question-circle tooltips" title="{{Topic d'auto-découverte. Défaut: homeassistant}}"></i></sup>
	   </label>
	   <div class="col-lg-3">
	     <input class="configKey form-control" data-l1key="disco_topic" placeholder="homeassistant"/>
	   </div>
	</div>
	<div class="form-group">
	   <label class="col-lg-4 control-label">{{Topics racines}}&nbsp;
	     <sup><i class="fas fa-question-circle tooltips" title="{{Topics racines des antennes BLE à monitorer. Pas de sous-topics}}"></i></sup>
	   </label>
	   <div class="col-lg-3">
	     <input class="configKey form-control" data-l1key="ble_root_topics" type="hidden"/>
	     <div class="root-topics">
		<div class="input-group">
		  <input class="form-control" id="new-topic" placeholder="{{Ajouter un topic}}"/>
		  <span class="input-group-btn">
			<a class="btn btn-default form-control" id="bt_add_topic" title="{{Ajouter}}">
				<i class="fas fa-plus-square"></i></a>
		  </span>
		</div>
		<p>
		<ul id="topics-list" class="topics-list"></ul>
	     </div>
	   </div>
	</div>
     </div>

     <div class="col-md-6">
	<legend><i class="fas fa-wifi"></i>&nbsp;{{Devices et Antennes}}</legend>
	<div class="form-group">
	   <label class="col-lg-4 control-label">{{Création automatique}}&nbsp;
	     <sup><i class="fas fa-question-circle tooltips" title="{{Créer automatiquement les devices auto-découverts}}"></i></sup>
	   </label>
	   <div class="col-lg-1">
	     <input type="checkbox" class="configKey form-control" data-l1key="auto_create" unchecked>
	   </div>
	</div>
	<div class="form-group">
	   <label class="col-lg-4 control-label">{{Durée d'auto-découverte}}&nbsp;
	     <sup><i class="fas fa-question-circle tooltips" title="{{Durée de l'auto-découverte (en minutes)}}"></i></sup>
	   </label>
	   <div class="col-lg-2">
	     <input class="configKey form-control" data-l1key="disco_duration" type="number" min="1" max="30" placeholder="5"/>
	   </div>
	   <span>{{mins}}</span>
	</div>
	<div class="form-group">
	   <label class="col-lg-4 control-label">{{Délai d'absence}}&nbsp;
	     <sup><i class="fas fa-question-circle tooltips"
	        title="{{Délai pour considérer les devices comme absents (en minutes). 0 pour les garder sans limite}}"></i></sup>
	   </label>
	   <div class="col-lg-2">
	      <input class="configKey form-control" data-l1key="devices_timeout" type="number" min="0" max="30" placeholder="2"/>
	   </div>
	   <span>{{mins}}</span>
	</div>

	<legend><i class="fab fa-whmcs"></i>&nbsp;{{Système}}</legend>
	<div class="form-group">
	   <label class="col-lg-4 control-label">{{Port socket MQTT}}&nbsp;
	     <sup><i class="fas fa-exclamation-triangle tooltips warning-tooltip"
		title="{{Port du deamon blescannerd. Ne pas modifier sauf en cas de conflit}}"></i></sup>
	   </label>
	   <div class="col-lg-2">
	     <input class="configKey form-control" data-l1key="broker" data-l2key="socket_port" type="number" min="1" max="65535" placeholder="55036"/>
	   </div>
	</div>
     </div>
  </fieldset>
</form>
<?php
include_file('core', 'config', 'js', 'blescanner');
?>
