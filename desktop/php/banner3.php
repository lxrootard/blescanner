<?php
/* This file is part of Plugin openzwave for jeedom.
*
* Plugin openzwave for jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Plugin openzwave for jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Plugin openzwave for jeedom. If not, see <http://www.gnu.org/licenses/>.
*/
if (!isConnect('admin')) {
	throw new Exception('401 - {{Accès non autorisé}}');
}
?>
<style type="text/css">
	.display_options {
//		position: fixed;
//		margin-left: 0px;
		margin-left: 15px;
//		border: 1px solid green;
	}
</style>
<div class="display_options">
   <span>
        <label for="display_away">{{Afficher les devices absents}}&nbsp;
                <sup><i class="fas fa-question-circle tooltips"
                        title="{{Afficher les devices connus absents ou non détectés}}"></i></sup>
        </label>
	<?php
	$away = cache::byKey('blescanner::display_away')->getValue();
//	log::add('blescanner', 'debug',  '> list1: display away devices: $$' . $away . '$$');
	$checked = ($away == 'on')? 'checked': '';
//      log::add('blescanner', 'debug',  'dans le html: checked? $$' . $checked . '$$');
        echo '<input type="checkbox" id="display_away" ' . $checked . ' onclick="toggleAway()"/>';
	?>
   </span>
</div>
