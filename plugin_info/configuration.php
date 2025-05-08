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
  .input-group {
   width: 65%;
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
</style>

<form class="form-horizontal">
  <fieldset>
     <div class="form-group">
        <label class="col-xs-2 control-label">{{Création automatique}}
                <sup><i class="fas fa-question-circle tooltips" title="{{Créer automatiquement les devices auto-découverts}}"></i></sup>
        </label>
        <div class="col-md-1">
                <input type="checkbox" class="configKey form-control" data-l1key="auto_create" unchecked>
        </div>
     </div>
     <div class="form-group">
        <label class="col-xs-2 control-label">{{Durée d'auto-découverte}}
	    <sup><i class="fas fa-question-circle tooltips" title="{{Durée de l'auto-découverte (en minutes)}}"></i></sup>
        </label>
	<div class="col-xs-1">
	    <input class="configKey form-control" data-l1key="disco_duration"/>
	</div>
	<span>{{mins}}</span>
     </div>
     <div class="form-group">
	<label class="col-xs-2 control-label">{{Délai d'absence}}
	    <sup><i class="fas fa-question-circle tooltips"
	    title="{{Délai pour considérer les devices comme absents (en minutes). 0 pour les garder sans limite}}"></i></sup>
	</label>
        <div class="col-xs-1">
            <input class="configKey form-control" data-l1key="devices_timeout"/>
        </div>
	<span>{{mins}}</span>
     </div>
     <div class="form-group">
        <label class="col-xs-2 control-label">{{Topic de découverte}}
            <sup><i class="fas fa-question-circle tooltips"
            title="{{Topic d'auto-découverte. Défaut: homeassistant}}"></i></sup>
        </label>
        <div class="col-xs-2">
            <input class="configKey form-control" data-l1key="disco_topic"/>
        </div>
     </div>
     <div class="form-group">
	<label class="col-xs-2 control-label">{{Topics racines des équipements}}
	    <sup><i class="fas fa-question-circle tooltips" title="{{Topics racines des antennes BLE à monitorer}}"></i></sup>
        </label>
        <div class="col-xs-3">
          <input class="configKey form-control" data-l1key="ble_root_topics" type="hidden"/>
          <div class="root-topics">
            <div class="input-group">
              <input class="form-control roundedLeft" id="new-topic" placeholder="{{Ajouter un topic}}"/>
              <span class="input-group-btn">
                <a class="btn btn-default form-control roundedRight" id="bt_add_topic" title="{{Ajouter}}"><i class="fas fa-plus-square"></i></a>
              </span>
            </div>
	    <p>
	    <ul id="topics-list"></ul>
          </div>
        </div>
     </div>
  </fieldset>
</form>

<?php
include_file('core', 'config', 'js', 'blescanner');
?>
