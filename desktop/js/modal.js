jeedom.blescanner = function() {
}

jeedom.blescanner.setMode = function(_params) {
  const paramsRequired = ['mode']
  const paramsSpecifics = {}
  try {
	jeedom.private.checkParamsRequired(_params || {}, paramsRequired)
  } catch (e) {
	(_params.error || paramsSpecifics.error || jeedom.private.default_params.error)(e)
	return
  }
  const params = $.extend({}, jeedom.private.default_params, paramsSpecifics, _params || {})
  const paramsAJAX = jeedom.private.getParamsAJAX(params)
  paramsAJAX.url = 'plugins/blescanner/core/ajax/blescanner.ajax.php'
  paramsAJAX.data = {
	action: 'setMode',
	mode: _params.mode
  }
  $.ajax(paramsAJAX)
}


jeedom.blescanner.setAway = function(_params) {
  const paramsRequired = ['away']
  const paramsSpecifics = {}
  try {
        jeedom.private.checkParamsRequired(_params || {}, paramsRequired)
  } catch (e) {
        (_params.error || paramsSpecifics.error || jeedom.private.default_params.error)(e)
        return
  }
  const params = $.extend({}, jeedom.private.default_params, paramsSpecifics, _params || {})
  const paramsAJAX = jeedom.private.getParamsAJAX(params)
  paramsAJAX.url = 'plugins/blescanner/core/ajax/blescanner.ajax.php'
  paramsAJAX.data = {
        action: 'setAway',
        away: _params.away
  }
  $.ajax(paramsAJAX)
}

jeedom.blescanner.addDevice = function(_params) {
  // alert("*** jeedom.blescanner.addDevice : " +  JSON.stringify(_params));
  const paramsRequired = ['id']
  const paramsSpecifics = {}
  try {
        jeedom.private.checkParamsRequired(_params || {}, paramsRequired);
  	const params = $.extend({}, jeedom.private.default_params, paramsSpecifics, _params || {});
  	const paramsAJAX = jeedom.private.getParamsAJAX(params);
  	paramsAJAX.url = 'plugins/blescanner/core/ajax/blescanner.ajax.php';
  	paramsAJAX.data = {
        	action: 'addDevice',
        	id: _params.id
  	}
  	$.ajax(paramsAJAX);
  	setTimeout(() => {
  		reloadModal();
  	}, "1000");
	setTimeout(() => {
  		$('#modal_msg').showAlert({ message: '{{Device ' +  _params.id + ' ajouté}}', level: 'success' });
  	}, "1000");
  } catch (e) {
	(_params.error || paramsSpecifics.error || jeedom.private.default_params.error)(e);
  }
}

$('#mode_options input').change(function() {
  jeedom.blescanner.setMode({
	mode: $(this).val(),
	global: false
  })
  // alert("*** display_mode: " + $(this).val());
  reloadModal();
});

$('#display_options input').change(function() {
  //alert("*** onchange display options: " + $(this).val());
  jeedom.blescanner.setAway({
        away: $(this).val(),
        global: false
  })
  reloadModal();
});

$('#table_list2').off().on('click', '.addDevice', function() {
//  alert("*** onclick addDevice: " + $(this).attr('data-id'));
  jeedom.blescanner.addDevice({
	id: $(this).attr('data-id'),
	global: false
  })
});

function toggleAway() {
  const checkBox = document.getElementById("display_away");
  const text = document.getElementById("text");
  if (checkBox.checked == true) {
      jeedom.blescanner.setAway({
        away: 'on',
        global: false })
  } else {
        jeedom.blescanner.setAway({
        away: 'off',
        global: false })
  }
  reloadModal();
}

$('.refreshBtn[data-action=refresh]').on('click',function() {
  reloadModal();
});

$('.pauseGraph[data-action=pause]').on('click',function() {
  pauseGraph();
});
