<?php
/* This file is part of Plugin blescanner for jeedom.
*
* Plugin blescanner for jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Plugin blescanner for jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Plugin blescanner for jeedom. If not, see <http://www.gnu.org/licenses/>.
*/
if (!isConnect('admin')) {
	throw new Exception('401 - {{Accès non autorisé}}');
}
?>
<div class="display_options">
   <span>
        <label class="graphLabel" for="display_away">{{Afficher les devices absents}}
                <sup><i class="fas fa-question-circle tooltips"
                        title="{{Afficher les devices connus absents ou non détectés}}"></i></sup>
        </label>
	<?php
	$away = cache::byKey('blescanner::display_away')->getValue();
//	log::add('blescanner', 'debug',  '> list1: display away devices: $$' . $away . '$$');
	$checked = ($away == 'on')? 'checked': '';
        echo '<input type="checkbox" id="display_away" ' . $checked . ' onclick="toggleAway()"/>';
	?>
   </span>
</div>
