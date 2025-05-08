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

var addBtn = document.getElementById("bt_add_topic");
var newTopic = document.getElementById("new-topic");
var topicsList = document.getElementById("topics-list");
var hiddenInput = document.querySelector('.configKey[data-l1key="ble_root_topics"]');
var initDone = false;

function updateHiddenInput() {
    const topics = Array.from(topicsList.querySelectorAll("li")).map(li => li.dataset.topic);
    hiddenInput.value = topics.join(',');
}

function createTopicElement(topic) {
    const li = document.createElement("li");
    li.dataset.topic = topic;
    li.textContent = topic + " ";
    const removeBtn = document.createElement("a");
    removeBtn.innerHTML += '<button class="bt_remove_topic" title="{{Supprimer ce topic}}"><i class="fas fa-minus-circle"></i></button>';
    removeBtn.addEventListener("click", function (e) {
	e.preventDefault();
	li.remove();
	updateHiddenInput();
    });
    li.appendChild(removeBtn);
    topicsList.appendChild(li);
}

function addTopic() {
    const topic = newTopic.value.trim();
    if (topic === "") return;

    const existingTopics = Array.from(topicsList.querySelectorAll("li")).map(li => li.dataset.topic);
    if (!existingTopics.includes(topic)) {
	createTopicElement(topic);
	updateHiddenInput();
	newTopic.value = "";
    }
}

addBtn.addEventListener("click", addTopic);

newTopic.addEventListener("keydown", function (e) {
    if (e.key === "Enter") {
	e.preventDefault();
	addTopic();
    }
});

function initializeTopics() {
    topicsList.innerHTML = "";
    hiddenInput.value.split(',').forEach(t => createTopicElement(t));
}

$('.configKey[data-l1key="ble_root_topics"]').change(function() {
    if (! initDone)
	initializeTopics();
    initDone = true;
});

$('body').off('blescanner::dependancy_end').on('blescanner::dependancy_end', function(_event, _options) {
    window.location.reload();
})
