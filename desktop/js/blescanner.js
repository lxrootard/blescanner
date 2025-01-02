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

/* Permet la réorganisation des commandes dans l'équipement */

/* Fonction permettant l'affichage des commandes dans l'équipement */
function addCmdToTable(_cmd) {
  if (!isset(_cmd)) {
    var _cmd = {configuration: {}}
  }
  if (!isset(_cmd.configuration)) {
    _cmd.configuration = {}
  }
  var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">'
  tr += '<td class="hidden-xs">'
  tr += '<span class="cmdAttr" data-l1key="id"></span>'
  tr += '</td>'
  tr += '<td>'
  tr += '<div class="input-group">'
  tr += '<input class="cmdAttr form-control input-sm roundedLeft" data-l1key="name" placeholder="{{Nom de la commande}}">'
  tr += '<span class="input-group-btn"><a class="cmdAction btn btn-sm btn-default" data-l1key="chooseIcon" title="{{Choisir une icône}}"><i class="fas fa-icons"></i></a></span>'
  tr += '<span class="cmdAttr input-group-addon roundedRight" data-l1key="display" data-l2key="icon" style="font-size:19px;padding:0 5px 0 0!important;"></span>'
  tr += '</div>'
  tr += '<select class="cmdAttr form-control input-sm" data-l1key="value" style="display:none;margin-top:5px;" title="{{Commande info liée}}">'
  tr += '<option value="">{{Aucune}}</option>'
  tr += '</select>'
  tr += '</td>'

  tr += '<td>'
  tr += '<span class="type" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + '</span>'
  tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>'
  tr += '</td>'

  tr += '<td>';
  tr += '<span class="cmdAttr" data-l1key="configuration" data-l2key="topic"></span>';
  tr += '<div style="margin-top:7px;">'
  if (init(_cmd.type) == 'info')
    tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="key" placeholder="{{Clé}}" title="{{Clé MQTT}}">'
  else
    tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="payload" placeholder="{{Payload}}" title="{{Payload MQTT}}">'
  tr += '</div>'
  tr += '</td>'

  tr += '<td>'
  tr += '<span class="cmdAttr" data-l1key="htmlstate"></span>'
  tr += '</td>'

  tr += '<td>'
  tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isVisible" checked/>{{Afficher}}</label> '
  tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isHistorized" checked/>{{Historiser}}</label> '
  tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="display" data-l2key="invertBinary"/>{{Inverser}}</label> '
  tr += '<div style="margin-top:7px;">'
  tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min}}" title="{{Min}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">'
  tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max}}" title="{{Max}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">'
  tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="unite" placeholder="Unité" title="{{Unité}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">'
  tr += '</div>'
  tr += '</td>'
  tr += '<td>'
  if (is_numeric(_cmd.id)) {
    tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fas fa-cogs"></i></a> '
    tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fas fa-rss"></i> Tester</a>'
  }
  tr += '<i class="fas fa-minus-circle pull-right cmdAction cursor" data-action="remove" title="{{Supprimer la commande}}"></i></td>'
  tr += '</tr>'
  $('#table_cmd tbody').append(tr)
  var tr = $('#table_cmd tbody tr').last()
  jeedom.eqLogic.buildSelectCmd({
    id:  $('.eqLogicAttr[data-l1key=id]').value(),
    filter: {type: 'info'},
    error: function (error) {
      $('#div_alert').showAlert({message: error.message, level: 'danger'})
    },
    success: function (result) {
      tr.find('.cmdAttr[data-l1key=value]').append(result)
      tr.setValues(_cmd, '.cmdAttr')
      jeedom.cmd.changeType(tr, init(_cmd.subType))
    }
  })
}

$('#bt_disco_blescanner').on('click', function () {
  $.ajax({
      type: "POST",
      url: "plugins/blescanner/core/ajax/blescanner.ajax.php",
      data: {
          action: "disco",
      },
      dataType: 'json',
      error: function (request, status, error) {
          handleAjaxError(request, status, error);
      },
      success: function (data) {
          if (data.state != 'ok') {
              $('#div_alert').showAlert({ message: data.result, level: 'danger' });
          }
      }
  });
});

$('#bt_health_blescanner').on('click', function () {
  $('#md_modal').dialog({title: "{{Santé des antennes BLE}}"});
  $('#md_modal').load('index.php?v=d&plugin=blescanner&modal=health').dialog('open');
});


$('#bt_list1_blescanner').on('click', function () {
  $('#md_modal').dialog({title: "{{Liste des devices BLE connus}}"});
  $('#md_modal').load('index.php?v=d&plugin=blescanner&modal=list1').dialog('open');
});

$('#bt_list2_blescanner').on('click', function () {
  $('#md_modal').dialog({title: "{{Liste des devices BLE inconnus}}"});
  $('#md_modal').load('index.php?v=d&plugin=blescanner&modal=list2').dialog('open');
});

$('#bt_nwk1_blescanner').on('click', function () {
  $('#md_modal').dialog({title: "{{Graphique des devices connus}}"});
  $('#md_modal').load('index.php?v=d&plugin=blescanner&modal=network1').dialog('open');
});

$('#bt_nwk2_blescanner').on('click', function () {
  $('#md_modal').dialog({title: "{{Graphique des devices inconnus}}"});
  $('#md_modal').load('index.php?v=d&plugin=blescanner&modal=network2').dialog('open');
});

$('body').off('blescanner::discovery').on('blescanner::discovery', function(_event, _options) {
  $('#div_alert').showAlert({
    message: _options.message,
    level: 'info'
  })
  if (_options.type == 'start')
	$('#info_msg').empty().append('<span class="alert alert-info" role="alert"> {{Auto-découverte en cours. Cliquez sur l\'icone pour arrêter}} </span>');
  else
	$('#info_msg').empty().append('<span>&nbsp;</span>');;
})

$('body').off('blescanner::newAntenna').on('blescanner::newAntenna', function(_event, _options) {
  window.location.reload();
})

$("#table_cmd").sortable({
  axis: "y",
  cursor: "move",
  items: ".cmd",
  placeholder: "ui-state-highlight",
  tolerance: "intersect",
  forcePlaceholderSize: true
})

setTimeout(() => {
  $('.eqLogicAction[data-action=returnToThumbnailDisplay]').removeAttr('href').off('click').on('click', function(event) {
    // contournement du plugin.template du core
    // force un load page lors du click sur returnToThumbnailDisplay
    event.preventDefault()
    jeedomUtils.loadPage('index.php?v=d&m=blescanner&p=blescanner', false)
  })
}, "500");

function printEqLogic(_eqLogic) {
  if (_eqLogic.configuration.antennaWebURL != null) {
  	$('#webAdmin').append ('<a class="btn btn-primary" target="_blank" href="' + _eqLogic.configuration.antennaWebURL
		+ '"/> <i class="fas fa-external-link-square-alt"></i> {{Console Web}}</a>');
  }
  // lance une tempo pour laisser le temps au core d'executer tous les addCmdToTable
  setTimeout(() => {
    $('table.tablesorter').trigger('update') // update de tablesorter
  }, "1000");
}
