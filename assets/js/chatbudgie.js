(function($) {
    'use strict';

    var conversationHistory = [];
    var $widget, $container, $toggle, $messages, $input, $sendBtn;

    $(document).ready(function() {
        $widget = $('#chatbudgie-widget');
        $container = $widget.find('.chatbudgie-container');
        $toggle = $widget.find('.chatbudgie-toggle');
        $messages = $widget.find('.chatbudgie-messages');
        $input = $widget.find('.chatbudgie-input');
        $sendBtn = $widget.find('.chatbudgie-send');

        $toggle.on('click', toggleChat);
        $widget.find('.chatbudgie-close').on('click', closeChat);
        $sendBtn.on('click', sendMessage);
        $input.on('keypress', function(e) {
            if (e.which === 13 && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        addInitialMessage();
    });

    function toggleChat() {
        $container.toggleClass('active');
        if ($container.hasClass('active')) {
            $input.focus();
        }
    }

    function closeChat() {
        $container.removeClass('active');
    }

    function addInitialMessage() {
        var initialHtml = '<div class="chatbudgie-initial-message">' +
            '<h4>Hello! I\'m ChatBudgie</h4>' +
            '<p>How can I help you today?</p>' +
            '</div>';
        $messages.html(initialHtml);
    }

    function sendMessage() {
        var message = $.trim($input.val());
        if (!message) return;

        addMessage(message, 'user');
        $input.val('');
        $sendBtn.prop('disabled', true).text(chatbudgie_params.strings.sending);

        conversationHistory.push({ role: 'user', content: message });

        var $loadingMsg = addMessage('', 'loading');

        var formData = new FormData();
        formData.append('action', 'chatbudgie_send_message_sse');
        formData.append('nonce', chatbudgie_params.nonce);
        formData.append('message', message);
        formData.append('conversation_history', JSON.stringify(conversationHistory));

        var xhr = new XMLHttpRequest();
        xhr.open('POST', chatbudgie_params.sse_url, true);

        var buffer = '';

        xhr.onreadystatechange = function() {
            if (xhr.readyState === XMLHttpRequest.LOADING || xhr.readyState === XMLHttpRequest.DONE) {
                if (xhr.readyState === XMLHttpRequest.LOADING) {
                    // Process incoming SSE data
                    //console.log('SSE response text: ' + this.responseText);
                    parseSSEResponse(this.responseText);
                    $loadingMsg.remove();
                } else if (xhr.readyState === XMLHttpRequest.DONE) {
                    //console.log("state is done: " + this.responseText)
                    if (xhr.status === 200) {
                        // Stream complete, add to conversation history
                        var accumulatedReply = parseSSEResponse(this.responseText);
                        conversationHistory.push({ role: 'assistant', content: accumulatedReply });
                        $sendBtn.prop('disabled', false).text('Send');
                        scrollToBottom();
                    } else {
                        $loadingMsg.remove();
                        conversationHistory.pop();
                        addMessage(chatbudgie_params.strings.error, 'error');
                        $sendBtn.prop('disabled', false).text('Send');
                    }
                }
            }
        };

        xhr.onerror = function() {
            $loadingMsg.remove();
            conversationHistory.pop();
            addMessage(chatbudgie_params.strings.error, 'error');
            $sendBtn.prop('disabled', false).text('Send');
        };

        xhr.send(formData);
    }

    function updateOrAddMessage(content, type) {
        var $lastMsg = $messages.find('.chatbudgie-message').last();

        if ($lastMsg.hasClass(type)) {
            $lastMsg.text(content);
        } else {
            addMessage(content, type);
        }
        scrollToBottom();
    }

    function addMessage(content, type) {
        var $msg = $('<div class="chatbudgie-message ' + type + '"></div>');

        if (type === 'loading') {
            $msg.html('<div class="typing-indicator"><span></span><span></span><span></span></div>');
        } else {
            $msg.text(content);
        }

        var $initial = $messages.find('.chatbudgie-initial-message');
        if ($initial.length) {
            $initial.remove();
        }

        $messages.append($msg);
        scrollToBottom();

        return $msg;
    }

    function scrollToBottom() {
        $messages.scrollTop($messages[0].scrollHeight);
    }

    function parseSSEResponse(response) {
        var accumulatedReply = '';
        var lines = response.split('\n');
        for (var i = 0; i < lines.length; i++) {
            var line = lines[i];
            if (line.indexOf('data:') === 0) {
                accumulatedReply += line.substring(5); // Remove "data:" prefix
            }
        }
        // Update the response content
        updateOrAddMessage(accumulatedReply, 'assistant');
        return accumulatedReply;
    }

})(jQuery);
