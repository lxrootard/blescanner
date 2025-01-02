/* This file is part of Plugin zwavejs for jeedom.
*
* Plugin zwavejs for jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Plugin zwavejs for jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Plugin zwavejs for jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

var renderer;
var MAX_RSSI = -100;
var NO_RSSI = MAX_RSSI * 2;
var MAX_DIST = 50;
var scale = 0.7;
var scaleX = ((window.innerWidth - 200)/2) * scale;
var scaleY = ((window.innerHeight - 200)/2) * scale;
//alert ("X=" +scaleX + " Y=" +scaleY);
var selectedLink = '#0066cc'; //'blue';
var aliveLink = 'grey';
var selectedText = '#0066cc'; // 'blue';
var aliveText = 'grey';
var deadText = 'red';
var nodeSize = 50; // taille des noeuds
var idealLength = 400; // taille des links
var pause = false;

// -1 ou 1 aleatoirement
function randomNumber(min, max) {
    a = Math.round(Math.random() * (max - min) + min);
    if (a == 0) return randomNumber(min, max);
    return a;
}
/*
function randomNumber(min, max) {
    // Randomly choose between the two intervals
    const intervalChoice = Math.random() < 0.5;
    // Generate a random number in the selected interval
    if (intervalChoice)
        // Interval (-1, -0.5)
        return Math.random() * (-0.5) - 0.5;
    else
        // Interval (0.5, 1)
        return Math.random() * 0.5 + 0.5;
}
*/
function resetGraph() {
	location.reload();
        if (renderer) {
        	renderer.reset();
	}
}

function pauseGraph() {
        if (renderer) {
		let text = document.getElementById("animate_text");
		let icon = document.getElementById("animate_icon");
		icon.classList.add('fas');
//		alert ('text= ' +text.innerHTML +' icon= '+ JSON.stringify(icon.classList));
 		if (! pause) {
			text.empty().append("Reprendre");
			icon.classList.remove('fa-pause');
			icon.classList.add('fa-play');
                	renderer.pause();
			pause = true;
		} else {
			text.empty().append("Pause");
			icon.classList.remove('fa-play');
			icon.classList.add('fa-pause');
			renderer.resume();
			pause = false;
		}
        }
}

function loadGraph() {
        // Create a graph instance:
	var graph = Viva.Graph.graph();

	// make sure you have the layout object for nodes positioning
	var layout = Viva.Graph.Layout.forceDirected(graph, {
		springLength: idealLength,
		springCoeff : 0.0008,
        	gravity : -10,
	// This is the main part of this example. We are telling force directed
	// layout, that we want to change length of each physical spring
	// by overriding `springTransform` method:
                springTransform: function (link, spring) {
		spring.length = idealLength * link.data.connectionStrength;
               	}
	});
        // Set up the graphics and the renderer:
        var graphics = Viva.Graph.View.svgGraphics();

	// highlight des noeuds sélectionnés
        highlightRelatedNodes = function (nodeId, isOn) {
                graph.forEachLinkedNode(nodeId, function (node2, link) {
                    var linkUI = graphics.getLinkUI(link.id);
                    if (linkUI) {
			linkUI.attr('stroke',  isOn ? selectedLink : aliveLink)
                        linkUI.attr('stroke-width', isOn ? '2.2px' : '0.6px');
	                svgText = Viva.Graph.svg('text')
                                .text(node2.id)
                                .attr('fill', node2.data.txtColor);
			let l1 = document.getElementById(nodeId);
			let n1 = graph.getNode(nodeId);
			l1.text(isOn? nodeId : n1.data.label)
				.attr('fill', isOn? selectedText : aliveText);
			let l2 = document.getElementById('label_'+ linkUI.attr('id'));
			if (l2 != null) {
				l2.text(linkUI.link.data.label);
				l2.attr('fill', isOn? selectedText : 'none')
                                        .attr('x', linkUI.attr('x'))
                                        .attr('y', linkUI.attr('y'));
			//console.log('l2= %o',l2);
			}
			// alert(linkUI.link.data.label); // JSON.stringify(linkUI));
		    }
                });
        };

	// Noeuds avec texte et image
        graphics.node(function(node) {
	//alert (JSON.stringify(node));
         	var ui = Viva.Graph.svg('g'),
		svgText = Viva.Graph.svg('text')
				.text(node.data.label)
				.attr('id',  node.data.id)
				.attr('fill', node.data.txtColor),
		img = Viva.Graph.svg('image')
				.attr('width', nodeSize)
				.attr('height', nodeSize)
				.link(node.data.url);
		ui.attr('alignment-baseline','middle');
		ui.append(svgText);
		ui.append(img);
                $(ui).hover(function () {
                    highlightRelatedNodes(node.id, true);
                }, function () {
                    highlightRelatedNodes(node.id, false);
                });
        	return ui;
        }).placeNode(function(nodeUI, pos) { // position image au centre du noeud
                // 'g' element doesn't have convenient (x,y) attributes, instead
                // we have to deal with transforms: http://www.w3.org/TR/SVG/coords.html#SVGGlobalTransformAttribute
                nodeUI.attr('transform',
                            'translate(' +
                                  (pos.x - nodeSize/2) + ',' + (pos.y - nodeSize/2) +
                            ')');
            	});

	// liens avec labels
	var geom = Viva.Graph.geom();

	graphics.link(function(link) {
	// alert (JSON.stringify(link));
		var label = Viva.Graph.svg('text').attr('id','label_'+ link.data.id); // .text(link.data.label);
		graphics.getSvgRoot().childNodes[0].append(label);
		return Viva.Graph.svg('path')
			.attr('stroke', aliveLink)
			.attr('id', link.data.id);
			}).placeLink(function(linkUI, fromPos, toPos) {
				var toNodeSize = nodeSize,fromNodeSize = nodeSize;
				var from = geom.intersectRect(
					fromPos.x - fromNodeSize / 2, // left
					fromPos.y - fromNodeSize / 2, // top
					fromPos.x + fromNodeSize / 2, // right
					fromPos.y + fromNodeSize / 2, // bottom
					fromPos.x, fromPos.y, toPos.x, toPos.y) // segment
				|| fromPos;

				var to = geom.intersectRect(
					toPos.x - toNodeSize / 2, // left
					toPos.y - toNodeSize / 2, // top
					toPos.x + toNodeSize / 2, // right
					toPos.y + toNodeSize / 2, // bottom
					toPos.x, toPos.y, fromPos.x, fromPos.y)
				|| toPos;

				var data = 'M' + from.x + ',' + from.y + 'L' + to.x + ',' + to.y;
				linkUI.attr("d", data);
				linkUI.attr("x",(from.x + to.x) / 2);
				linkUI.attr("y", (from.y + to.y) / 2);
	});

	// alert ('antennas:' + JSON.stringify(antennas));
	// alert ('nodes:' + JSON.stringify(nodes));

        // Populate the graph with some nodes and links:
	for (a in antennas) {
		let dead = (antennas[a]['online'] == false);
		let color = (dead)? deadText : aliveText;
		let name = antennas[a]['name'];
		let picture = antennas[a]['picture'];
		graph.addNode(a, { id: a, label: name, url: picture, isPinned: dead, txtColor: color });
	}
        for (n in nodes) {
		let dead = (nodes[n]['rssi'] == NO_RSSI);
		let color = (dead)? deadText : aliveText;
		let name = nodes[n]['name'];
		let picture =  nodes[n]['picture'];
		graph.addNode(n,{ id: n, label: name, url: picture, isPinned: dead, txtColor: color });

		if (dead) { // dead
			var x = Math.floor(Math.random() * randomNumber(-1,1) * scaleX);
			var y = Math.floor(Math.random() * randomNumber(-1,1) * scaleY);
			//var y = Math.floor(Math.random() * scaleX) * randomNumber(-1,1);
			//var x = Math.floor(Math.sqrt(Math.pow(scaleY,2) - Math.pow(y,2))) * randomNumber(-1,1);
			layout.setNodePosition(n, x, y);
		}

		for (a in antennas) {
			if (mode == 'Distance') {
				dist = parseInt(nodes[n]['distance ' + a]);
				if ((!isNaN(dist)) && (dist != -1)) {
					graph.addLink(n, a, { id: n + '-' + a,
						connectionStrength: dist/MAX_DIST, label: dist + 'm'});
				}
			} else {
				rssi = parseInt(nodes[n]['rssi ' + a]);
				if ((rssi != 0) && (rssi != NO_RSSI)) {
					// alert ('link:' + n +' > ' + a);
					graph.addLink(n, a, { id: n + '-' + a,
						connectionStrength: rssi *(1/MAX_RSSI), label: rssi + 'db'});
				}
			}
		}

	}

        // Render the graph:
        renderer = Viva.Graph.View.renderer(graph, {
        	graphics: graphics,
		layout: layout,
		container: document.getElementById('network_graph')
        });
        renderer.run();
}
loadGraph();
