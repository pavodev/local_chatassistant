/// local_chatassistant/amd/src/chatbutton.js

define([
  "jquery",
  "https://cdn.jsdelivr.net/npm/marked/marked.min.js",
], function ($, marked) {
  return {
    init: function () {
      // Chat button
      var $chatButton = $('<button id="openai-chat-button">💬</button>').css({
        position: "fixed",
        bottom: "20px",
        left: "20px",
        "z-index": 9999,
        "background-color": "#0078d7",
        color: "#fff",
        border: "none",
        "border-radius": "50%",
        width: "50px",
        height: "50px",
        "font-size": "20px",
        "box-shadow": "0 2px 6px rgba(0,0,0,0.3)",
        cursor: "pointer",
      });

      // Chat window
      var $chatWindow = $(
        '<div id="openai-chat-window" style="display: none;"></div>'
      ).css({
        position: "fixed",
        bottom: "80px",
        left: "20px",
        width: "320px",
        height: "450px",
        "background-color": "#ffffff",
        border: "1px solid #ddd",
        "border-radius": "10px",
        "z-index": 9999,
        "box-shadow": "0 4px 12px rgba(0,0,0,0.2)",
        display: "flex",
        "flex-direction": "column",
        overflow: "hidden",
        "font-family": "Arial, sans-serif",
      });

      // Close button
      var $closeButton = $('<button id="openai-chat-close">×</button>').css({
        position: "absolute",
        top: "10px",
        right: "10px",
        border: "none",
        background: "transparent",
        "font-size": "18px",
        cursor: "pointer",
      });

      // Chat content area
      var $chatContent = $('<div id="openai-chat-content"></div>').css({
        flex: "1",
        padding: "50px 10px 10px 10px",
        overflowY: "auto",
      });

      // Chat form
      var $form = $('<form id="openai-chat-form"></form>').css({
        display: "flex",
        padding: "10px",
        borderTop: "1px solid #eee",
        gap: "8px",
        background: "#fafafa",
      });

      var $input = $(
        '<input type="text" id="openai-chat-input" placeholder="Type your message..." required />'
      ).css({
        flex: "1",
        padding: "8px 10px",
        border: "1px solid #ccc",
        "border-radius": "20px",
        outline: "none",
      });

      var $send = $('<button type="submit">Send</button>').css({
        padding: "8px 15px",
        background: "#0078d7",
        color: "#fff",
        border: "none",
        "border-radius": "20px",
        cursor: "pointer",
      });

      $form.append($input).append($send);
      $chatWindow.append($closeButton, $chatContent, $form);
      $("body").append($chatButton, $chatWindow);

      // Show/hide chat window
      $chatButton.on("click", function () {
        $chatWindow.toggle();
      });

      $closeButton.on("click", function () {
        $chatWindow.hide();
      });

      // Handle chat submission
      $form.on("submit", function (e) {
        e.preventDefault();
        var message = $input.val();
        if (message.trim() === "") return;

        const userMessage = $('<div class="chat-message user"></div>')
          .css({
            margin: "10px 0",
            padding: "8px 12px",
            "background-color": "#e0f7fa",
            "border-radius": "10px",
            "max-width": "85%",
            "align-self": "flex-end",
            "margin-left": "auto",
          })
          .html(`<strong>You:</strong> ${message}`);

        $chatContent.append(userMessage);
        $input.val("");
        $chatContent.scrollTop($chatContent[0].scrollHeight);

        // Assistant reply container
        const $responseContainer = $(`
            <div class="chat-message assistant">
              <strong>Assistant:</strong> <span class="assistant-reply"></span>
            </div>
          `);
        $chatContent.append($responseContainer);
        const $replySpan = $responseContainer.find(".assistant-reply");

        // Start streaming from PHP endpoint
        fetch(M.cfg.wwwroot + "/local/chatassistant/chatstream.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded",
          },
          body: new URLSearchParams({ input: message }),
        }).then((response) => {
          const reader = response.body.getReader();
          const decoder = new TextDecoder("utf-8");
          let buffer = "";

          function read() {
            reader.read().then(({ done, value }) => {
              if (done) return;

              buffer += decoder.decode(value, { stream: true });

              const sseMessages = buffer.split("\n\n");
              buffer = sseMessages.pop(); // Save incomplete message

              for (const msg of sseMessages) {
                const lines = msg.split("\n");
                for (const line of lines) {
                  if (line.startsWith("data: ")) {
                    const jsonPart = line.slice(6);
                    if (jsonPart === "[DONE]") return;

                    try {
                      const chunk = JSON.parse(jsonPart);
                      const newText = chunk.content?.[0]?.text?.value || "";
                      $replySpan.append(document.createTextNode(newText));
                      $chatContent.scrollTop($chatContent[0].scrollHeight);
                    } catch (e) {
                      console.error("Failed to parse stream chunk", e);
                    }
                  }
                }
              }

              read();
            });
          }

          read();
        });
      });
    },
  };
});

// AJAX call to backend
//   $.ajax({
//     method: 'POST',
//     url: M.cfg.wwwroot + '/local/chatassistant/chat.php',
//     data: { input: message },
//     dataType: 'json'
//   }).done(function(response) {
//     $loader.remove();

//     console.log('ASSISTANT:', response.message.content[0].text.value);

//     const reply = response.message.content[0] && response.message.content[0].text.value
//       ? marked.parse(response.message.content[0].text.value.trim())
//       : 'No reply';

//     const assistantMessage = $('<div class="chat-message assistant"></div>').css({
//       'margin': '10px 0',
//       'padding': '8px 12px',
//       'background-color': '#f1f1f1',
//       'border-radius': '10px',
//       'max-width': '85%',
//       'align-self': 'flex-start',
//       'margin-right': 'auto'
//     }).html(`<strong>Assistant:</strong> ${reply}`);

//     $chatContent.append(assistantMessage);
//     $chatContent.scrollTop($chatContent[0].scrollHeight);
//   }).fail(function() {
//     $loader.remove();

//     const errorMessage = $('<div class="chat-message error"></div>').css({
//       'margin': '10px 0',
//       'padding': '8px 12px',
//       'background-color': '#ffcdd2',
//       'border-radius': '10px',
//       'color': '#b71c1c'
//     }).text('Error: Failed to get response.');
//     $chatContent.append(errorMessage);
//     $chatContent.scrollTop($chatContent[0].scrollHeight);
//   });
