<?php
if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
$plugin = plugin::byId('blescanner');
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());
?>
<style type="text/css">
        .table-responsive {
                width: 100%;
		table-layout: fixed;
        }
</style>

<div id="info_msg" style="width:100%">&nbsp;</div>
<div class="row row-overflow">
  <div class="col-xs-12 eqLogicThumbnailDisplay">
     <legend><i class="fas fa-cog"></i> {{Gestion}}</legend>
     <div class="eqLogicThumbnailContainer">
	<div class="cursor pluginAction success" id="bt_list1_blescanner">
       		<i class="fas fa-list"></i><br/>
       		<span>{{Liste Devices Connus}}</span>
	</div>
	<div class="cursor pluginAction success" id="bt_nwk1_blescanner">
       		<i class="fas fa-sitemap"></i><br/>
       		<span>{{Réseau Devices Connus}}</span>
	</div>
	<div class="cursor pluginAction warning" id="bt_list2_blescanner">
                <i class="fas fa-list"></i><br>
                <span>{{Liste Devices Inconnus}}</span>
	</div>
        <div class="cursor pluginAction warning" id="bt_nwk2_blescanner">
                <i class="fas fa-sitemap"></i><br/>
                <span>{{Réseau Devices Inconnus}}</span>
        </div>

<?php
	$disco = cache::byKey('blescanner::disco')->getValue();
	$color = ($disco) ? 'var(--lb-yellow-color)': '';
        echo '<div class="cursor logoSecondary" style="color:' . $color . '" id="bt_disco_blescanner">';
	echo '<i class="fab fa-searchengin"></i><br>';
	$msg = ($disco)? '{{Auto-découverte en cours}}' : '{{Auto-découverte}}';
        echo '<span id="text_disco_blescanner" style="color:' . $color . '">' . $msg . '</span>';
?>
        </div>
        <div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
                <i class="fas fa-wrench"></i><br>
                <span>{{Configuration}}</span>
        </div>
        <div class="cursor logoSecondary" id="bt_health_blescanner">
                <i class="fas fa-medkit"></i><br>
                <span>{{Santé Antennes}}</span>
        </div>
<!--
        <div class="cursor pluginAction logoSecondary" data-action="openLocation"
            data-location="<?= $plugin->getDocumentation() ?>">
                <i class="fas fa-book"></i><br>
                <span>{{Documentation}}</span>
        </div>
        <div class="cursor pluginAction logoSecondary"
             data-action="openLocation" data-location="https://community.jeedom.com/tag/plugin-<?= $plugin->getId() ?>">
                <i class="fas fa-comments"></i><br>
                <span>{{Community}}</span>
        </div>
-->
    </div>
    <div class="input-group" style="margin:5px;">
            <input class="form-control roundedLeft" placeholder="{{Rechercher}}" id="in_searchEqlogic" />
            <div class="input-group-btn">
                <a id="bt_resetSearch" class="btn roundedRight" style="width:30px"><i class="fas fa-times"></i></a>
            </div>
    </div>
    <?php
	displayEqLogics ($eqLogics, 'Antenna');
	displayEqLogics ($eqLogics, 'Device');
    ?>
</div>

<div class="col-xs-12 eqLogic" style="display: none;">
        <div class="input-group pull-right" style="display:inline-flex">
            <span class="input-group-btn">
                <a class="btn btn-sm btn-default eqLogicAction roundedLeft" data-action="configure"><i class="fas fa-cogs"></i><span class="hidden-xs"> {{Configuration avancée}}</span>
                </a><a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}
                </a><a class="btn btn-sm btn-danger eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i><span class="hidden-xs"> {{Supprimer}}</span></a>
            </span>
        </div>
        <ul class="nav nav-tabs" role="tablist">
            <li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fas fa-arrow-circle-left"></i></a></li>
            <li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer-alt"></i> {{Equipement}}</a></li>
            <li class="device" role="presentation"><a href="#commandstab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fas fa-list-alt"></i> {{Commandes}}</a></li>
	    <li class="antenna" role="presentation"><a href="#bluetoothtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fab fa-bluetooth"></i> {{Bluetooth}}</a></li>
	    <li class="antenna" role="presentation"><a href="#systemtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fab fa-whmcs"></i> {{Système}}</a></li>

	    <li role="presentation"><a href="#customtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fas fa-code"></i> {{Custom}}</a></li>

	    <li role="presentation"><a href="#presencetab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fas fa-wifi"></i> {{Présence}}</a></li>
        </ul>
        <div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
            <div role="tabpanel" class="tab-pane active" id="eqlogictab">
                <br />
                <div class="row">
                    <div class="col-sm-7">
                        <form class="form-horizontal">
                            <fieldset>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">{{Nom de l'équipement}}</label>
                                    <div class="col-sm-3">
                                        <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
                                        <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement}}" />
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">{{Objet parent}}</label>
                                    <div class="col-sm-3">
                                        <select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
                                            <option value="">{{Aucun}}</option>
                                            <?php
                                            $options = '';
                                            foreach ((jeeObject::buildTree(null, false)) as $object) {
                                                $options .= '<option value="' . $object->getId() . '">' . str_repeat('&nbsp;&nbsp;', $object->getConfiguration('parentNumber')) . $object->getName() . '</option>';
                                            }
                                            echo $options;
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">{{Catégorie}}</label>
                                    <div class="col-sm-9">
                                        <?php
                                        foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
                                            echo '<label class="checkbox-inline">';
                                            echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
                                            echo '</label>';
                                        }
                                        ?>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label"></label>
                                    <div class="col-sm-9">
                                        <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" />{{Activer}}</label>
                                        <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" />{{Visible}}</label>
                                    </div>
                                </div>
				<div>&nbsp;</div>
                                <div class="form-group antenna">
                                    <label class="col-sm-3 control-label">{{Administration}}</label>
                                    <div class="col-sm-9" id="webAdmin"/>
				</div>
                            </fieldset>
                        </form>
                    </div>
                    <div class="col-sm-5">
                        <form class="form-horizontal">
                            <fieldset>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">{{Identifiant}}</label>
                                    <div class="col-sm-6">
                                        <span type="text" class="eqLogicAttr label label-default" data-l1key="logicalId"></span>
                                    </div>
                                </div>
				<div class="form-group antenna">
                                    <label class="col-sm-4 control-label">{{Numéro de série}}</label>
                                    <div class="col-sm-4">
                                        <span type="text" class="eqLogicAttr label label-default" data-l1key="configuration" data-l2key="antennaUid"></span>
                                    </div>
				</div>
				<div class="form-group">
				    <label class="col-sm-4 control-label">{{Type}}</label>
				    <div class="col-sm-6">
					 <span type="text" class="eqLogicAttr label label-default" data-l1key="configuration" data-l2key="type"></span>
				    </div>
				</div>
				<div class="form-group">
				    <label class="col-sm-4 control-label">{{Fabricant}}</label>
                                    <div class="col-sm-6">
                                        <span type="text" class="eqLogicAttr label label-default" data-l1key="configuration" data-l2key="manufacturer"></span>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">{{Modèle}}</label>
                                    <div class="col-sm-6">
                                        <span type="text" class="eqLogicAttr label label-default" data-l1key="configuration" data-l2key="model"></span>
                                    </div>
                                </div>

				<span class="hidden eqLogicAttr" data-l1key="configuration" data-l2key="pictureId"></span>

                                <div class="form-group antenna">
                                    <label class="col-sm-4 control-label">{{Version}}</label>
                                    <div class="col-sm-6">
                                        <span type="text" class="eqLogicAttr label label-default" data-l1key="configuration" data-l2key="version"></span>
                                    </div>
                                </div>
                                <div class="form-group antenna">
                                    <label class="col-sm-4 control-label">{{URL Web}}</label>
                                    <div class="col-sm-6">
                                        <span type="text" class="eqLogicAttr label label-default" data-l1key="configuration" data-l2key="antennaWebURL"></span>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">{{Date de création}}</label>
                                    <div class="col-sm-6">
                                        <span type="text" class="eqLogicAttr label label-default" data-l1key="configuration" data-l2key="createtime"></span>
                                    </div>
                                </div>
	                        <div class="form-group">
				 <div class="col-sm-4 control-label">
                                    <label>{{Image}}</label>
				    <div style="margin-top: 20px">
				    	<label class="btn btn-info" for="file-input" id="bt_upload_pic" title="{{Uploader une image}}">
                                        	<i class="fas fa-upload" style="width:15px"></i>
						<input id="file-input" type="file" name="file" accept="image/png" style="display:none"/>
					</label>
                                        <a class="btn btn-info" id="bt_reset_pic" title="{{Réinitialiser}}">
						<i class="fas fa-redo" style="width:15px"></i>
					</a>
                                     </div>
				  </div>
				  <div class="col-sm-6" style="text-align:left;">
					<img id="device_pic" style="height:100px; margin-top:10px"/>
				  </div>
				</div>
                            </fieldset>
                        </form>
                    </div>
                </div>
            </div>

 	    <?php
		displayTabPanel('commands');
		displayTabPanel('presence');
		displayTabPanel('bluetooth');
		displayTabPanel('system');
		displayTabPanel('custom');
	    ?>
        </div>
    </div>
</div>

<?php
function displayTabPanel ($id) {
	echo '<div role="tabpanel" class="tab-pane" id="' . $id . 'tab">';

	if ($id == 'custom') {
		echo '<div role="tabpanel" class="tab-pane" id="commandtab">';
		echo '<div class="input-group pull-right" style="display:inline-flex;margin-top:5px;">';
		echo '<span class="input-group-btn">';
		echo '<a class="btn btn-info btn-xs roundedLeft" id="bt_addCustomInfo"><i class="fas fa-plus-circle"></i> {{Ajouter une info custom}}</a>';
		echo '<a class="btn btn-warning btn-xs roundedRight" id="bt_addCustomAction"><i class="fas fa-plus-circle"></i> {{Ajouter une action custom}}</a>';

		echo '</span></div><br><br>';
	}

	echo '<div class="table-responsive">';
	echo '<table id="' . $id . '_table" class="table table-bordered table-condensed tablesorter">';
	echo '<thead><tr>';
	echo '<th class="hidden-xs">{{Id}}</th>';
	echo '<th data-sortable="true" data-sorter="inputs">{{Nom}}</th>';
	echo '<th data-sorter="true" data-sorter="select-text">{{Type}}</th>';
	echo '<th data-sorter="false">{{Topic et clé ou payload MQTT}}</th>';
	echo '<th data-sorter="false">{{Valeur}}</th>';
	echo '<th data-sorter="false">{{Options}}</th>';
	echo '<th data-sorter="false" >{{Actions}}</th>';
	echo '</tr></thead>';
	echo '<tbody></tbody>';
	echo '</table></div></div>';
}

function displayEqLogics ($eqLogics, $type) {
	if ($type == 'Antenna')
		echo '<legend><i class="mdi mdi-access-point" style="font-size:1.5em"></i> {{Mes Antennes BLE}}</legend>';
	else
		echo '<legend><i class="fas fa-wifi"></i> {{Mes Devices BLE}}</legend>';
	echo '<div class="eqLogicThumbnailContainer">';
        foreach ($eqLogics as $eqLogic) {
                if ($eqLogic->getConfiguration('type') != $type)
			continue;
                $opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
                echo '<div class="eqLogicDisplayCard cursor ' . $opacity . '" data-eqLogic_id="' . $eqLogic->getId() . '">';
		echo '<img src="' . $eqLogic->getImage() . '" style="height:100px"/>';
                echo "<br>";
                echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
                echo '</div>';
        }
	echo '</div>';
}
?>
<?php include_file('desktop', 'blescanner', 'js', 'blescanner'); ?>
<?php include_file('core', 'plugin.template', 'js'); ?>
