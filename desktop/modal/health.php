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

if (!isConnect('admin')) {
	throw new Exception('401 - Accès non autorisé');
}
$plugin = plugin::byId('blescanner');
$eqLogics = blescanner::getDevices('Antenna');
?>

<style type="text/css">
        .refresh-modal {
                float: right;
		margin-bottom: 20px;
        }
</style>
<div class="refresh-modal">
        <label>{{Rafraîchir }}&nbsp;</label>
        <a class="btn btn-success refreshBtn" data-action="refresh"><i class="fas fa-sync"></i></a>
</div>

<table class="table table-condensed tablesorter" id="table_healthblescanner">
	<thead>
		<tr>
			<th>{{Image}}</th>
			<th>{{Antenne}}</th>
			<th>{{ID}}</th>
                        <th>{{Identifiant}}</th>
			<th>{{Type}}</th>
			<th>{{Modèle}}</th>
			<th>{{Version}}</th>
			<th>{{Présent}}</th>
			<th>{{Dernière communication}}</th>
			<th>{{Date création}}</th>
		</tr>
	</thead>
	<tbody>
<?php
foreach ($eqLogics as $eqLogic) {
   if (! $eqLogic->getIsEnable())
	continue;
   $image = '<img src="' . $eqLogic->getImage() . '" height="55" width="55" />';
   echo '<tr><td>' . $image . '</td><td><a href="' . $eqLogic->getLinkToConfiguration() . '" style="text-decoration: none;">' . $eqLogic->getHumanName(true) . '</a></td>';
   echo '<td><span class="label label-info" style="font-size : 1em; cursor : default;">' . $eqLogic->getId() . '</span></td>';
   echo '<td><span class="label label-info" style="font-size : 1em; cursor : default;">' . $eqLogic->getConfiguration('antennaUid') . '</span></td>';
   echo '<td><span class="label label-info" style="font-size : 1em; cursor : default;">' . $eqLogic->getConfiguration('manufacturer') . '</span></td>';
   echo '<td><span class="label label-info" style="font-size : 1em; cursor : default;">' . $eqLogic->getConfiguration('model') . '</span></td>';
   echo '<td><span class="label label-info" style="font-size : 1em; cursor : default;">' . $eqLogic->getConfiguration('version') . '</span></td>';
   $alive = $eqLogic->isAlive();
   $status = '<span class="label label-danger" style="font-size : 1em; cursor : default;">{{NOK}}</span>';
   if ($alive)
	$status = '<span class="label label-success" style="font-size : 1em; cursor : default;">{{OK}}</span>';
   echo '<td>' . $status . '</td>';

   echo '<td><span class="label label-info" style="font-size : 1em; cursor : default;">' . $eqLogic->getStatus('lastCommunication') . '</span></td>';
   echo '<td><span class="label label-info" style="font-size : 1em; cursor : default;">' . $eqLogic->getConfiguration('createtime') . '</span></td>';
}
?>
	</tbody>
</table>
<script>
function reloadModal() {
   $('#md_modal').dialog('close');
   // $('#md_modal').dialog({title: "{{Liste devices BLE connus}}"});
   $('#md_modal').load('index.php?v=d&plugin=blescanner&modal=health').dialog('open');
}
</script>

<?php
include_file('desktop', 'modal', 'js', 'blescanner');
?>
