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

try {
    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
    include_file('core', 'authentification', 'php');

    if (!isConnect('admin')) {
        throw new Exception(__('401 - Accès non autorisé', __FILE__));
    }

  /* Fonction permettant l'envoi de l'entête 'Content-Type: application/json'
    En V3 : indiquer l'argument 'true' pour contrôler le token d'accès Jeedom
    En V4 : autoriser l'exécution d'une méthode 'action' en GET en indiquant le(s) nom(s) de(s) action(s) dans un tableau en argument
  */
    ajax::init(array('uploadPicture'));

    if (init('action') == 'disco') {
	$deamon_info = blescanner::deamon_info();
        if ($deamon_info['state'] != 'ok') {
		event::add('jeedom::alert', array('level' => 'warning', 'page' => 'blescanner',
			'message' => __("Le deamon n'est pas démarré", __FILE__)));
	// ajax::error("Le deamon n'est pas démarré");
	} else {
		$disco = cache::byKey('blescanner::disco')->getValue();
		$command = $disco ? 'start' : 'stop';
		// log::add('blescanner', 'debug', $command . ' discovery');
		if ($disco)
			blescanner::stopDisco();
		else
			blescanner::startDisco();
	}
    	ajax::success();
    }

    if (init('action') == 'setMode') {
        //log::add('blescanner', 'debug', 'ajax: set mode to: '. init('mode'));
	cache::set('blescanner::display_mode',init('mode'));
        ajax::success();
    }

    if (init('action') == 'setAway') {
        //log::add('blescanner', 'debug', 'ajax: set away to: '. init('away'));
        cache::set('blescanner::display_away',init('away'));
        ajax::success();
    }

    if (init('action') == 'uploadPicture') {
	$file = $_FILES['file'];
        log::add('blescanner', 'debug', 'ajax: uploadPicture for: '. init('pictureId') . ' icon: ' . $file['name']);
	blescanner::uploadPicture ($file,init('pictureId'));
        ajax::success();
    }

   if (init('action') == 'getPicture') {
	$pictureId = init('pictureId');
        //log::add('blescanner', 'debug', 'ajax: getPicture for: '. $model);
        $i = blescanner::getPicture($pictureId);
        ajax::success($i);
    }


    if (init('action') == 'resetPicture') {
	// log::add('blescanner', 'debug', 'ajax: resetPicture for: '. init('pictureId'));
	blescanner::resetPicture (init('pictureId'));
        ajax::success();
    }

    if (init('action') == 'addDevice') {
	$key = init('id');
        // log::add('blescanner', 'debug', 'ajax: addDevice id: '. $key);
        blescanner::addDevice ($key);
	$unknownDevices = cache::byKey('blescanner::unknown_devices')->getValue();
	unset ($unknownDevices[$key]);
        cache::set('blescanner::unknown_devices',$unknownDevices);
	// event::add('blescanner::newEqLogic', '');
        ajax::success();
    }

    throw new Exception(__('Aucune méthode correspondante à', __FILE__) . ' : ' . init('action'));
}
catch (Exception $e) {
    ajax::error('blescanner.ajax.php: ' . displayException($e), $e->getCode());
}
?>
