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
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOS,E. See the
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
        html, body, svg {
		width: 100%;
		height: 100%;
            	margin: 0;
            	padding: 0;
		font-family: Arial;
		// overflow: hidden;
	}
        #network_graph {
                height: 90%;
                width: 100%;
                vertical-align: bottom;
                background-color: rgba(var(--panel-bg-color), var(--opacity)) !important;
        }
	.pauseGraph {
    		float: right;
	}
	.graphBtn {
		margin-left:10px;
	}
/*
	.separator {
		width: 10px;
	}
*/
        .legend {
		width: 200px;
                position: fixed;
		margin-left: 20px;
		bottom: 75px;
		width: 250px;
        }
	.alivenode-color { color: grey; width: 35px }
	.aliveant-color { color: #a9f50e; width: 35px }
	.deadnode-color { color: red; width: 35px }
        .selectednode-color { color: #0066cc; width: 35px }
	.alivelink-color { color: grey; }
	.selectedlink-color { color: #0066cc }
	.fas { margin-right: 5px }
</style>

<div id="network_graph">
</div>
<div>
	<table class=legend table table-bordered table-condensed">
		<thead>
			<tr>
				<th colspan="2">{{Légende}}</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td class="alivenode-color"><i class="fas fa-circle"></i></td>
				<td class="label">{{Device actif}}</td>
			</tr>
                        <tr>
                                <td class="aliveant-color"><i class="fas fa-circle"></i></td>
                                <td class="label">{{Antenne active}}</td>
                        </tr>
			<tr>
				<td class="deadnode-color"><i class="fas fa-circle"></i></td>
				<td class="label">{{Device ou antenne mort ou injoignable}}</td>
			</tr>
                        <tr>
                                <td class="selectednode-color"><i class="fas fa-circle"></i></td>
                                <td class="label">{{Device sélectionné}}</td>
                        </tr>
                        <tr>
				<td class="alivelink-color"><i class="fas fa-minus"></i></td>
				<td class="label">{{Lien actif}}</td>
			</tr>
			<tr>
				<td class="selectedlink-color"><i class="fas fa-minus"></i></td>
				<td class="label">{{Lien sélectionné}}</td>
			<tr>
		</tbody>
	</table>
</div>

<script type="text/javascript" src="plugins/blescanner/3rdparty/vivagraph/vivagraph.js">
</script>
