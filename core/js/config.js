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

// Get the tags and input elements from the DOM
var tags = document.getElementById('data-topic-tags');
var input = document.getElementById('topic-input-tag');
var initDataTagsDone = false;

// Add an event listener for keydown on the input element
input.addEventListener('keydown', function(event) {
    // Check if the key pressed is 'Enter'
    if (event.key === 'Enter') {
      // Prevent the default action of the keypress
      // event (submitting the form)
      event.preventDefault();

      const tagContent = input.value.trim();
      input.value = '';
      addDataTag(tagContent);
    }
});

  // Add an event listener for click on the tags list
tags.addEventListener('click', function(event) {
    event.preventDefault();
    const myButton = event.target.closest('.delete-tag-button')
    // If the clicked element has the class 'delete-button'
    if (myButton) {
      // Remove the parent element (the tag)
      myButton.parentNode.remove();
      updateConfigInputTopics();
    }
});

function addDataTag(content) {
    if (content !== '') {
      selectedList = $(`#data-topic-tags li:contains('${content}')`);

      if (selectedList.length) {
        //already in list
        return;
      }

      // Create a new list item element for the tag
      const tag = document.createElement('li');
      tag.innerText = content;
      tag.innerHTML += '<button class="delete-tag-button" title="{{Supprimer ce topic}}"><i class="fas fa-minus-circle"></i></button>';
      tags.appendChild(tag);

      updateConfigInputTopics();
    }
}

function updateConfigInputTopics() {
    const topics = Array.from(tags.children)
    document.querySelector('.configKey[data-l1key="ble_root_topics"]').value = topics.map(x => x.firstChild.textContent).join(',');
  }

  function initDataTopicTagsList() {
    const topics = document.querySelector('.configKey[data-l1key="ble_root_topics"]').value.split(',');
    topics.forEach(t => addDataTag(t));
  }

  $('.configKey[data-l1key="ble_root_topics"]').change(function() {
    if (!initDataTagsDone) {
      initDataTopicTagsList();
    }

    initDataTagsDone = true
});

$('#bt_add').off('click').on('click', function() {
    const tagContent = input.value.trim();
    input.value = '';
    addDataTag(tagContent);
});

$('body').off('blescanner::dependancy_end').on('blescanner::dependancy_end', function(_event, _options) {
  window.location.reload()
})
