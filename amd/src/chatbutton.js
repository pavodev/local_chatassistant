// local_openai_assistant/amd/src/chatbutton.js
define(['jquery'], function($) {
  return {
      init: function() {
          // Create the chat button.
          var $chatButton = $('<button id="openai-chat-button">Chat</button>').css({
              position: 'fixed',
              bottom: '20px',
              right: '20px',
              'z-index': 9999
          });
          // Create the chat window container.
          var $chatWindow = $('<div id="openai-chat-window" style="display: none;"></div>').css({
              position: 'fixed',
              bottom: '70px',
              right: '20px',
              width: '300px',
              height: '400px',
              'background-color': '#fff',
              border: '1px solid #ccc',
              'z-index': 9999,
              'box-shadow': '0 0 10px rgba(0,0,0,0.5)'
          });
          // Append elements to the page.
          $('body').append($chatButton).append($chatWindow);
          // Toggle the chat window when the button is clicked.
          $chatButton.on('click', function() {
              $chatWindow.toggle();
          });
          // Add a close button inside the chat window.
          var $closeButton = $('<button id="openai-chat-close">Close</button>').css({
              position: 'absolute',
              top: '5px',
              right: '5px'
          });
          $chatWindow.append($closeButton);
          $closeButton.on('click', function() {
              $chatWindow.hide();
          });
          // Add a container for chat content.
          var $chatContent = $('<div id="openai-chat-content"></div>').css({
              margin: '40px 10px 10px 10px',
              height: '340px',
              overflow: 'auto'
          });
          $chatWindow.append($chatContent);
          // Add an input form for chat messages.
          var $form = $('<form id="openai-chat-form"></form>').css({
              position: 'absolute',
              bottom: '10px',
              left: '10px',
              right: '10px'
          });
          var $input = $('<input type="text" id="openai-chat-input" placeholder="Type your message..." required />').css({
              width: '70%',
              padding: '5px'
          });
          var $send = $('<button type="submit">Send</button>').css({
              padding: '5px 10px'
          });
          $form.append($input).append($send);
          $chatWindow.append($form);
          
          // Handle the form submission using AJAX.
          $form.on('submit', function(e) {
              e.preventDefault();
              var message = $input.val();
              if (message.trim() === '') {
                  return;
              }
              // Append the user's message to the chat.
              $chatContent.append('<div class="chat-message user"><strong>You:</strong> ' + message + '</div>');
              $input.val('');
              
              // Make the AJAX call to chat.php.
              $.ajax({
                  method: 'POST',
                  url: M.cfg.wwwroot + '/local/openai_assistant/chat.php',
                  data: { input: message },
                  dataType: 'json'
              }).done(function(response) {
                  var reply = response.choices && response.choices[0].text ? response.choices[0].text.trim() : 'No reply';
                  $chatContent.append('<div class="chat-message assistant"><strong>Assistant:</strong> ' + reply + '</div>');
                  // Scroll to the bottom.
                  $chatContent.scrollTop($chatContent[0].scrollHeight);
              }).fail(function() {
                  $chatContent.append('<div class="chat-message error"><strong>Error:</strong> Failed to get response.</div>');
              });
          });
      }
  };
});