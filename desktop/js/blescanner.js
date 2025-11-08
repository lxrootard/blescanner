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

/* Fonction permettant l'affichage des commandes dans l'équipement */
function buildCmd(_cmd) {
    let tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
    tr += '<td class="hidden-xs">';
    tr += '<span class="cmdAttr" data-l1key="id"></span>';
    tr += '</td>';
    tr += '<td>';
    tr += '<div class="input-group">';
    tr += '<input class="cmdAttr form-control input-sm roundedLeft" data-l1key="name" placeholder="{{Nom de la commande}}">';
    tr += '<span class="input-group-btn"><a class="cmdAction btn btn-sm btn-default" data-l1key="chooseIcon" title="{{Choisir une icône}}"><i class="fas fa-icons"></i></a></span>';
    tr += '<span class="cmdAttr input-group-addon roundedRight" data-l1key="display" data-l2key="icon" style="font-size:19px;padding:0 5px 0 0!important;"></span>';
    tr += '</div>';
    tr += '<select class="cmdAttr form-control input-sm" data-l1key="value" style="display:none;margin-top:5px;" title="{{Commande info liée}}">';
    tr += '<option value="">{{Aucune}}</option>';
    tr += '</select>';
    tr += '</td>';
    tr += '<td>';
    if (_cmd.logicalId)
	tr += '<span class="type" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + '</span>';
    else {
	if (init(_cmd.type) == 'info')
	   tr += '<input class="cmdAttr form-control type input-sm" data-l1key="type" value="Info" disabled style="margin-top:5px;">';
	else
	   tr += '<input class="cmdAttr form-control type input-sm" data-l1key="type" value="Action" disabled style="margin-top:5px;">';
    }
    tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>';
    tr += '</td>';
    tr += '<td>';
    if (!(_cmd.logicalId)|| _cmd.configuration.custom) {
	tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="topic" placeholder="{{Topic à utiliser}}"/>';
	tr += '<div style="margin-top:7px;">';
	if (init(_cmd.type) == 'info') {
	   tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="key" placeholder="{{Clé à utiliser}}"';
	   tr += ' title="{{La clé doit être unique}}"/>';
	} else {
	   tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="command" placeholder="{{Payload à utiliser}}"';
	   tr += ' title="{{Syntaxe: &quot;clé1&quot;=valeur1 [,&quot;clé2&quot;=valeur2]}}"/>';
	}
        tr += '</div>';
    } else {
	tr += '<div class="cmdAttr tooltips" data-l1key="configuration" data-l2key="topic" title="{{Topic MQTT}}"></div>';
	if (init(_cmd.type) == 'info')
	   tr += '<div class="cmdAttr tooltips" data-l1key="configuration" data-l2key="key" title="{{Clé MQTT}}" style="margin-top:5px;"></div>';
	else {
	   let p = _cmd.configuration.payload ? _cmd.configuration.payload.replace(/['{}]+/g, '') : '';
	   tr += '<div class="cmdAttr tooltips" title="{{Payload MQTT}}" style="margin-top:5px;">' + p + '</div>';
	}
    }
    tr += '</td>';
    tr += '<td>';
    tr += '<span class="cmdAttr" data-l1key="htmlstate"></span>';
    tr += '</td>';
    tr += '<td>';
    tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isVisible" checked/>{{Afficher}}</label>';
    tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isHistorized"/>{{Historiser}}</label> ';
    tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="display" data-l2key="invertBinary"/>{{Inverser}}</label>';
    tr += '<div style="margin-top:7px; display: flex">';
    tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min}}" title="{{Min}}">';
    tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max}}" title="{{Max}}">';
    tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="unite" placeholder="Unité" title="{{Unité}}">';
    tr += '</div>';
    tr += '</td>';
    tr += '<td><div>';
    if (is_numeric(_cmd.id)) {
	tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fas fa-cogs"></i></a>';
	tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fas fa-rss"></i> Tester</a>';
    }
    tr += '<i class="fas fa-minus-circle pull-right cmdAction cursor" data-action="remove" title="{{Supprimer la commande}}"></i>';
    tr += '</div></td>';
    tr += '</tr>';
    return tr;
}

function displayCmd(_cmd, tr) {
    jeedom.eqLogic.buildSelectCmd ({
	id: $('.eqLogicAttr[data-l1key=id]').value(),
	filter: {type: 'info'},
	error: function (error) {
		$('#div_alert').showAlert({message: error.message, level: 'danger'});
	},
	success: function (result) {
		tr.find('.cmdAttr[data-l1key=value]').append(result);
		tr.setValues(_cmd, '.cmdAttr');
		jeedom.cmd.changeType(tr, init(_cmd.subType));
	}
    });
}

function addCmdToTable(_cmd) {
    if (!isset(_cmd))
	var _cmd = { configuration: {} }
    if (!isset(_cmd.configuration))
	_cmd.configuration = {}

    const cmdId = _cmd.logicalId;
    const eqId = _cmd.eqLogic_id;
    if (! cmdId) {
	$('#custom_table tbody').append(buildCmd(_cmd))
	var tr = $('#custom_table tbody tr').last();
	displayCmd(_cmd, tr);

    } else if (eqId) {
	jeedom.eqLogic.byId({ id: eqId,
	    success: function (eqLogic) {
		const eqType = eqLogic.configuration.type;
		const topic = _cmd.configuration.topic;
		if (cmdId.includes('rssi') || cmdId.includes('distance') || cmdId.includes('connectivity')) {
			$('#presence_table tbody').append(buildCmd(_cmd));
			var tr = $('#presence_table tbody tr').last();
		} else if  (_cmd.configuration.custom) {
			$('#custom_table tbody').append(buildCmd(_cmd));
			var tr = $('#custom_table tbody tr').last();
                } else {
		    if (eqType == 'Antenna') {
			if ((topic != null) && (topic.includes('BTtoMQTT') || (topic.includes('MQTTtoBT')))) {
				$('#bluetooth_table tbody').append(buildCmd(_cmd));
				var tr = $('#bluetooth_table tbody tr').last();
			} else if ((topic != null) && (topic.includes('SYStoMQTT') || (topic.includes('MQTTtoSYS')))) {
			$('#system_table tbody').append(buildCmd(_cmd));
				var tr = $('#system_table tbody tr').last();
			}
		    } else { // Device
                        $('#commands_table tbody').append(buildCmd(_cmd));
                        var tr = $('#commands_table tbody tr').last();
		    }
		}
		displayCmd(_cmd,tr);
	    },
	    error: function (error) {  alert('message: ' + error.message); }
	});
   }
}

$('.pluginAction[data-action=openLocation]').on('click', function () {
  window.open($(this).attr("data-location"), "_blank", null);
});


$("#bt_addCustomInfo").on('click', function(event) {
    addCmdToTable({ type: 'info' });
    modifyWithoutSave = true;
})

$("#bt_addCustomAction").on('click', function(event) {
    addCmdToTable({ type: 'action' });
    modifyWithoutSave = true;
})

function updatePicture (pictureId) {
    $.ajax({
	type: "POST",
	url: "plugins/blescanner/core/ajax/blescanner.ajax.php",
	data: {
           action: "getPicture",
	   pictureId : pictureId
	},
      dataType: 'json',
      error: function (request, status, error) {
          handleAjaxError(request, status, error);
      },
      success: function (data) {
	  if (data.state != 'ok') {
		$('#div_alert').showAlert({ message: data.result, level: 'danger' });
		return;
          }
	  $('#device_pic').attr("src", data.result);
      }
    });
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
		return;
	   }
	}
    });
});

$('#bt_upload_pic').on('click', function () {
    var pictureId = $(this).closest('.form-horizontal').find('.eqLogicAttr[data-l2key="pictureId"]').value();
    $(this).fileupload({
	dataType: 'json',
	url: 'plugins/blescanner/core/ajax/blescanner.ajax.php?action=uploadPicture&pictureId=' + pictureId,
	replaceFileInput: false,
	error: function (request, status, error) {
	    handleAjaxError(request, status, error);
	},
	done: function (e,data) {
	    if (data.result.state != 'ok') {
		$('#div_alert').showAlert({ message: data.result.result, level: 'danger' });
	    	return;
	    }
	    updatePicture(pictureId);
	}
    });
});

$('#bt_reset_pic').on('click', function () {
  var pictureId = $(this).closest('.form-horizontal').find('.eqLogicAttr[data-l2key="pictureId"]').value();
    $.ajax({
	type: "POST",
	url: "plugins/blescanner/core/ajax/blescanner.ajax.php",
	data: {
	    action: "resetPicture",
	    pictureId: pictureId
	},
	dataType: 'json',
	error: function (request, status, error) {
	    handleAjaxError(request, status, error);
	},
	success: function (data) {
	    if (data.state != 'ok') {
		$('#div_alert').showAlert({ message: data.result, level: 'danger' });
		return;
	    }
	    updatePicture(pictureId);
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
    $('#md_modal').dialog({title: "{{Liste des devices BLE inconnus}}",
	close: function(rc) {
	   if (manualClose)
		window.location.reload();
	}
    });
    $('#md_modal').load('index.php?v=d&plugin=blescanner&modal=list2').dialog('open');
});


$('#bt_nwk1_blescanner').on('click', function () {
    $('#md_modal').dialog({title: "{{Réseau des devices connus}}"});
    $('#md_modal').load('index.php?v=d&plugin=blescanner&modal=network1').dialog('open');
});

$('#bt_nwk2_blescanner').on('click', function () {
    $('#md_modal').dialog({title: "{{Réseau des devices inconnus}}"});
    $('#md_modal').load('index.php?v=d&plugin=blescanner&modal=network2').dialog('open');
});

$('body').off('blescanner::discovery').on('blescanner::discovery', function(_event, _options) {
    $('#div_alert').showAlert({
	message: _options.message,
	level: 'info'
    })
    const col = 'var(--lb-yellow-color)';
    if (_options.type == 'start') {
	$('#bt_disco_blescanner').attr("style", "color: " + col);
	$('#text_disco_blescanner').empty().append('{{Auto-découverte en cours}}');
	$('#text_disco_blescanner').attr("style", "color: " + col);
	$('#info_msg').empty().append('<span class="alert alert-info" role="alert"> {{Auto-découverte en cours. Cliquez sur l\'icone pour arrêter}} </span>');
    } else {
	$('#bt_disco_blescanner').removeAttr('style');
	$('#text_disco_blescanner').empty().append('{{Auto-découverte}}');
	$('#text_disco_blescanner').removeAttr('style');
	$('#info_msg').empty().append('<span>&nbsp;</span>');
    }
});

$('body').off('blescanner::newEqLogic').on('blescanner::newEqLogic', function(_event, _options) {
    window.location.reload();
})

$(".eqLogicAttr[data-l1key='configuration'][data-l2key='type']").change(function () {
    if ($(this).value() == 'Antenna')
        $('.antenna').show();
    else
        $('.antenna').hide();
    if ($(this).value() == 'Device')
        $('.device').show();
    else
        $('.device').hide();
});


$(".eqLogicAttr[data-l1key='configuration'][data-l2key='manufacturer']").change(function () {
  if ($(this).value() == 'Unknown')
	$('.antenna').hide();
  else if ($(this).value() == 'OMG_community')
	$('.antenna').show();
});
/*
function sortRows(table) {
  table.sortable({
    axis: "y",
    cursor: "move",
    items: ".cmd",
    placeholder: "ui-state-highlight",
    tolerance: "intersect",
    forcePlaceholderSize: true
  });
}

sortRows($("#commands_table"));
sortRows($("#commands_table"));
*/

setTimeout(() => {
    $('.eqLogicAction[data-action=returnToThumbnailDisplay]').removeAttr('href').off('click').on('click', function(event) {
	// contournement du plugin.template du core
	// force un load page lors du click sur returnToThumbnailDisplay
	event.preventDefault();
	jeedomUtils.loadPage('index.php?v=d&m=blescanner&p=blescanner', false);
    })
}, "500");

function printEqLogic(_eqLogic) {
    if (_eqLogic.configuration.antennaWebURL != null) {
	$('#webAdmin').append ('<a class="btn btn-primary" target="_blank" href="' + _eqLogic.configuration.antennaWebURL
	+ '"/> <i class="fas fa-external-link-square-alt"></i> {{Console Web}}</a>');
    }
    updatePicture (_eqLogic.configuration.pictureId);
    // lance une tempo pour laisser le temps au core d'executer tous les addCmdToTable
    setTimeout(() => {
	$('table.tablesorter').trigger('update'); // update de tablesorter
    }, "1000");
}
