<?php
/* This file is part of Plugin openzwave for jeedom.
*
* Plugin openzwave for jeedom is free software: you can redistribute it and/or modi>
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
* along with Plugin openzwave for jeedom. If not, see <http://www.gnu.org/licenses/>
*/

if (!isConnect('admin')) {
        throw new Exception('401 - {{Accès non autorisé}}');
}
?>
<style type="text/css">
	.display-mode {
		margin-left: 0px;
		margin-top: 0px;
	}
	.options-select {
		width: 300px;
	}
</style>

<table style="width:100%">
  <tr>
	<th>
	<div class="display-mode">
	    <fieldset id="mode_options">
		<span>
                        <label class="graphLabel">{{Mode d'affichage contextuel}}
                		<sup><i class="fas fa-question-circle tooltips"
                        		title="{{Atténuation (en dB) ou distance (en mètres)}}"></i></sup>
			</label>
		</span>
                <span class="options-select">
<?php
		$mode = cache::byKey('blescanner::display_mode')->getValue();
		if ($mode == 'Distance') {
			$checked1='';
			$checked2 = 'checked';
		} else {
			$checked1='checked';
			$checked2='';
		}
		echo '<span><input type="radio" name="display_mode" id="attenuationBLE" value="Attenuation" '
			. $checked1 .'/>';
                echo '<label class="label" for="attenuationBLE">{{Atténuation}}</label></span>';
		//echo '&nbsp;';
		echo '<span><input type="radio" name="display_mode" id="distanceBLE" value="Distance" '
			. $checked2 .'/>';
		echo '<label class="label" for="distanceBLE">{{Distance}}</label></span>';
?>
		</span>
	   </fieldset>
	</div>
	</th>
	<th>
		<div class="refresh-modal" style="float: right">
        		<label>{{Rafraîchir}}</label>
        		<a class="btn btn-success refreshBtn graphBtn" data-action="refresh">
				<i class="fas fa-sync" style="width:15px!important"></i>
			</a>
		</div>
	</th>
  </tr>
</table>
