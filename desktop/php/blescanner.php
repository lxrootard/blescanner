<?php
if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
$plugin = plugin::byId('blescanner');
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());
$useMqttDisco = config::byKey('use_mqttdiscovery','blescanner');
?>
<div id="info_msg" style="width:100%">&nbsp;</div>
<div class="row row-overflow">
  <div class="col-xs-12 eqLogicThumbnailDisplay">
	<legend><i class="fas fa-cog"></i> {{Gestion}}</legend>
        <div class="eqLogicThumbnailContainer">
	<?php
	     if ($useMqttDisco) {
		echo '<div class="cursor pluginAction success" id="bt_list1_blescanner">';
        	echo '<i class="fas fa-list"></i><br/>';
        	echo '<span>{{Liste Devices Connus}}</span></div>';

        	echo '<div class="cursor pluginAction success" id="bt_nwk1_blescanner">';
        	echo '<i class="fas fa-sitemap"></i><br/>';
        	echo '<span>{{Réseau Devices Connus}}</span></div>';
	    }
	?>
        <div class="cursor pluginAction warning" id="bt_list2_blescanner">
                <i class="fas fa-list"></i>
                <br>
                <span>{{Liste Devices Inconnus}}</span>
        </div>
        <div class="cursor pluginAction warning" id="bt_nwk2_blescanner">
                <i class="fas fa-sitemap"></i>
                <br/>
                <span>{{Réseau Devices Inconnus}}</span>
        </div>
        <div class="cursor logoSecondary" id="bt_disco_blescanner">
                <i class="fab fa-searchengin"></i>
                <br>
                <span>{{Auto-découverte}}</span>
        </div>
        <div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
                <i class="fas fa-wrench"></i>
                <br>
                <span>{{Configuration}}</span>
        </div>
        <div class="cursor logoSecondary" id="bt_health_blescanner">
                <i class="fas fa-medkit"></i>
                <br>
                <span>{{Santé Antennes}}</span>
        </div>
<!--
        <div class="cursor pluginAction logoSecondary" data-action="openLocation"
            data-location="<?= $plugin->getDocumentation() ?>">
                <i class="fas fa-book"></i>
                <br>
                <span>{{Documentation}}</span>
        </div>
        <div class="cursor pluginAction logoSecondary"
             data-action="openLocation" data-location="https://community.jeedom.com/tag/plugin-<?= $plugin->getId() ?>">
                <i class="fas fa-comments"></i>
                <br>
                <span>{{Community}}</span>
        </div>
-->
</div>
<legend><i class="fas fa-wifi"></i> {{Mes Antennes BLE}}</legend>
        <div class="input-group" style="margin:5px;">
            <input class="form-control roundedLeft" placeholder="{{Rechercher}}" id="in_searchEqlogic" />
            <div class="input-group-btn">
                <a id="bt_resetSearch" class="btn roundedRight" style="width:30px"><i class="fas fa-times"></i></a>
            </div>
        </div>
        <div class="eqLogicThumbnailContainer">
            <?php
            foreach ($eqLogics as $eqLogic) {
                $opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
                echo '<div class="eqLogicDisplayCard cursor ' . $opacity . '" data-eqLogic_id="' . $eqLogic->getId() . '">';
                echo '<img src="' . $eqLogic->getImage() . '"/>';
                echo "<br>";
                echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
                echo '</div>';
            }
            ?>
        </div>
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
            <li role="presentation"><a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fas fa-list-alt"></i> {{Commandes}}</a></li>
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
                                <div class="form-group">
                                    <label class="col-sm-3 control-label"></label>
                                    <div class="col-sm-9" id="webAdmin"/>
				</div>
                            </fieldset>
                        </form>
                    </div>
                    <div class="col-sm-5">
                        <form class="form-horizontal">
                            <fieldset>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">{{Identifiant}}</label>
                                    <div class="col-sm-6">
                                        <span type="text" class="eqLogicAttr label label-default" data-l1key="logicalId"></span>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">{{Numéro de série}}</label>
                                    <div class="col-sm-6">
                                        <span type="text" class="eqLogicAttr label label-default" data-l1key="configuration" data-l2key="antennaUid"></span>
                                    </div>
                               </div>
                               <div class="form-group">
                                    <label class="col-sm-3 control-label">{{Type}}</label>
                                    <div class="col-sm-6">
                                        <span type="text" class="eqLogicAttr label label-default" data-l1key="configuration" data-l2key="antennaType"></span>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">{{Modèle}}</label>
                                    <div class="col-sm-6">
                                        <span type="text" class="eqLogicAttr label label-default" data-l1key="configuration" data-l2key="antennaModel"></span>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">{{Version}}</label>
                                    <div class="col-sm-6">
                                        <span type="text" class="eqLogicAttr label label-default" data-l1key="configuration" data-l2key="antennaVersion"></span>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">{{URL Web}}</label>
                                    <div class="col-sm-6">
                                        <span type="text" class="eqLogicAttr label label-default" data-l1key="configuration" data-l2key="antennaWebURL"></span>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">{{Date de création}}</label>
                                    <div class="col-sm-6">
                                        <span type="text" class="eqLogicAttr label label-default" data-l1key="configuration" data-l2key="createtime"></span>
                                    </div>
                                </div>
                            </fieldset>
                        </form>
                    </div>
                </div>
            </div>
            <div role="tabpanel" class="tab-pane" id="commandtab">
                <div class="table-responsive">
                    <table id="table_cmd" class="table table-bordered table-condensed tablesorter">
                        <thead>
                            <tr>
				<th class="hidden-xs" style="min-width:50px;width:70px">{{Id}}</th>
                                <th data-sortable="true" data-sorter="inputs" style="min-width:120px;width:250px">{{Nom}}</th>
                                <th data-sorter="select-text" style="width:130px;">{{Type}}</th>
				<th data-sorter="false" style="min-width:120px;width:250px">{{Topic et clé}}</th>
				<th data-sorter="false">{{Valeur}}</th>
                                <th data-sorter="false" style="min-width:150px;width:300px">{{Options}}</th>
                                <th data-sorter="false" style="min-width:80px;width:140px">{{Actions}}</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_file('desktop', 'blescanner', 'js', 'blescanner'); ?>
<?php include_file('core', 'plugin.template', 'js'); ?>
