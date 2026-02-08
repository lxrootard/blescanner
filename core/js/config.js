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

function createTopic() {
    const re = /^[A-Za-z0-9_-]+$/;
    const input = document.getElementById('new-topic');
    const topic = newTopic.value.trim();
    const val = input.value.trim();
    if (topic === "") return;
    if (!re.test(val)) {
      input.focus();
      input.setCustomValidity('Seuls les caractères alphanumériques (A–Z, a–z, 0–9), tiret et sous-tiret sont autorisés.');
      input.reportValidity();
      setTimeout(() => input.setCustomValidity(''), 1000);
      return;
    }
    addTopic(topic)
}

function addTopic(topic) {
    const existingTopics = Array.from(topicsList.querySelectorAll("li")).map(li => li.dataset.topic);
    if (!existingTopics.includes(topic)) {
	createTopicElement(topic);
	updateHiddenInput();
	newTopic.value = "";
    }
}

function removeTopic(topic) {
    const t = topicsList.querySelector(`li[data-topic="${topic}"]`);
    if (t) {
        t.remove();
        updateHiddenInput();
    }
}

addBtn.addEventListener("click", createTopic);

newTopic.addEventListener("keydown", function (e) {
    if (e.key === "Enter") {
	e.preventDefault();
	createTopic();
    }
});

function initializeTopics() {
    topicsList.innerHTML = "";
    if (hiddenInput.value != "")
	hiddenInput.value.split(',').forEach(t => createTopicElement(t));
}

$('.configKey[data-l1key="ble_root_topics"]').change(function() {
    if (! initDone)
	initializeTopics();
    initDone = true;
});


$('.configKey[data-l1key=mqttMode]').off('change').on('change', function() {
    $('.mqttMode').hide()
    if ((! $(this).value()) || ($(this).value() == 'local'))
	$('.remote').hide()
    else
	$('.remote').show()
})

$('.configKey[data-l1key=use_plugin_tgw]').off('change').on('change', function() {
    if ($(this).value() == 1)
	addTopic('home');
    else
	removeTopic('home');
})
