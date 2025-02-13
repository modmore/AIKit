class AIKit {
    constructor()
    {
        this.rootElement = null;
        this.assistantOpen = false;
        this.currentConversation = null;
        this.config = {
            assetsUrl: '/assets/components/aikit/',
        };
    }

    // Entry point to initialize the assistant
    initialize(rootElement, config)
    {
        this.rootElement = rootElement;
        this.renderAssistantButton();
        this.config = {...this.config, ...config};
    }

    // Render the button to open the assistant
    renderAssistantButton()
    {
        const button = document.createElement('button');
        button.title = 'Open AI Assistant';
        button.className = 'ai-assistant-open-button';
        button.addEventListener('click', () => this.toggleAssistant());
        button.innerHTML = '<i class="icon icon-robot"></i>';
        this.rootElement.appendChild(button);
    }

    // Toggle the assistant window
    toggleAssistant()
    {
        if (!this.assistantOpen) {
            this.renderAssistant();
            this.fetchConversations();
            this.openChat(1)
        } else {
            this.closeAssistant();
        }
        this.assistantOpen = !this.assistantOpen;
    }

    // Render the assistant UI
    renderAssistant()
    {
        const assistantContainer = document.createElement('div');
        assistantContainer.className = 'ai-assistant-container';

        const header = document.createElement('div');
        header.className = 'ai-assistant-header';

        const chatListButton = document.createElement('button');
        chatListButton.textContent = 'Chats';
        chatListButton.className = 'ai-assistant-chatlist-button';
        chatListButton.addEventListener('click', () => this.toggleChatsList());

        const closeButton = document.createElement('button');
        closeButton.textContent = 'Close';
        closeButton.addEventListener('click', () => this.toggleAssistant());

        header.appendChild(chatListButton);
        header.appendChild(closeButton);

        const mainContent = document.createElement('div');
        mainContent.className = 'ai-assistant-main';

        this.messageContainer = document.createElement('div');
        this.messageContainer.className = 'ai-assistant-message-container';
        mainContent.appendChild(this.messageContainer);
        this.messageRenderer = new MessageRenderer(this.messageContainer);

        // Create the chat list container
        this.chatListContainer = document.createElement('div');
        this.chatListContainer.className = 'chat-list-container';
        mainContent.appendChild(this.chatListContainer);

        // Compose message box
        const footer = document.createElement('div');
        footer.className = 'ai-assistant-footer';

        const textarea = document.createElement('textarea');
        textarea.className = 'ai-assistant-textarea';

        const sendButton = document.createElement('button');
        sendButton.textContent = 'Send';
        sendButton.addEventListener('click', () => this.sendMessage(textarea, messageContainer));

        footer.appendChild(textarea);
        footer.appendChild(sendButton);

        assistantContainer.appendChild(header);
        assistantContainer.appendChild(mainContent);
        assistantContainer.appendChild(footer);
        this.rootElement.appendChild(assistantContainer);
    }

    // Close the assistant UI
    closeAssistant()
    {
        const assistant = this.rootElement.querySelector('.ai-assistant-container');
        if (assistant) {
            this.rootElement.removeChild(assistant);
        }
    }

    // Fetch and render the list of chats
    fetchConversations()
    {
        fetch(this.config.assetsUrl + 'api.php?a=conversations')
            .then(response => response.json())
            .then(data => this.renderChatsList(data.data))
            .catch(error => console.error('Error fetching conversations:', error));
    }

    // Render the sliding chats list
    toggleChatsList()
    {
        if (this.chatListContainer.classList.contains('visible')) {
            this.chatListContainer.classList.remove('visible');
        } else {
            this.chatListContainer.classList.add('visible');
        }
    }

    renderChatsList(conversations = [])
    {
        // (Re-)populate the chat list
        if (Object.keys(conversations).length > 0) {
            this.chatListContainer.innerHTML = '';
            Object.values(conversations).forEach(chat => {
                const chatItem = document.createElement('div');
                chatItem.className = 'chat-item';
                chatItem.textContent = `${chat.title} (By: ${chat.started_by})`;
                chatItem.addEventListener('click', () => {
                    this.openChat(chat.id);
                    this.closeChatList(); // Close the chat list after selection
                });
                this.chatListContainer.appendChild(chatItem);
            });
        }
    }

    // Close the sliding chat list
    closeChatList()
    {
        if (this.chatListContainer) {
            this.chatListContainer.classList.remove('visible');
        }
    }


    // Open chat and fetch messages
    openChat(conversationId)
    {
        this.currentConversation = conversationId;
        fetch(this.config.assetsUrl + 'api.php?a=messages&conversation=' + conversationId)
            .then(response => response.json())
            .then(data => this.renderChatMessages(data.data))
            .catch(error => console.error('Error fetching messages:', error));
    }

    // Render chat messages
    renderChatMessages(messages = [])
    {
        this.messageRenderer.renderMessages(Object.values(messages));
    }

    // Send a message
    sendMessage(textarea, messageContainer)
    {
        if (!this.currentConversation) {
            fetch(this.config.assetsUrl + 'api.php?a=conversations', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({}),
            })
                .then(async response => {
                    const jsonResponse = await response.json();
                    if (response.status === 201) {
                        this.currentConversation = jsonResponse.data.id; // Store the new conversation ID
                        this.sendMessage(textarea, messageContainer);
                        this.fetchConversations();
                    } else {
                        console.error('Error creating a new conversation:', jsonResponse.error || 'Unknown error');
                    }
                })
                .catch(error => {
                    console.error('Error creating a new conversation:', error);
                });
            return;
        }

        const messageContent = textarea.value.trim();
        if (!messageContent) {
            return;
        }

        const loadingIndicator = document.createElement('div');
        loadingIndicator.textContent = 'Sending...';
        messageContainer.appendChild(loadingIndicator);

        fetch(this.config.assetsUrl + 'api.php?a=messages&conversation=' + this.currentConversation, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({content: messageContent}),
        })
            .then(async response => {
                const jsonResponse = await response.json();
                if (response.status === 201) {
                    textarea.value = '';
                    loadingIndicator.remove();
                    this.openChat(this.currentConversation); // Refresh chat messages
                } else if (response.status === 500) {
                    loadingIndicator.textContent = jsonResponse.error || 'An error occurred.';
                }
            })
            .catch(error => {
                loadingIndicator.textContent = 'Error sending message.';
                console.error('Error sending message:', error);
            });
    }
}

class MessageRenderer {
    constructor(messageContainer)
    {
        this.messageContainer = messageContainer;
        this.renderedMessages = new Map(); // To track rendered messages by their IDs
    }

    renderMessages(messages)
    {
        // Loop through incoming messages
        messages.forEach(message => {
            const { id, user_role, user, content, status } = message;

            // Check if the message is already rendered and hasn't changed
            const existingMessageEl = this.renderedMessages.get(id);
            if (existingMessageEl) {
                if (existingMessageEl.dataset.content === content && existingMessageEl.dataset.status === status) {
                    return; // Skip re-rendering unchanged messages
                }

                // Update existing message's content if it's updated
                this.updateMessageElement(existingMessageEl, message);
            } else {
                // Create new element for the message
                const messageEl = this.createMessageElement(message);
                this.messageContainer.appendChild(messageEl);
                this.renderedMessages.set(id, messageEl);
            }
        });

        // Clean up messages that are no longer in the input
        const messageIds = messages.map(m => m.id);
        [...this.renderedMessages.keys()].forEach(renderedId => {
            if (!messageIds.includes(renderedId)) {
                const messageEl = this.renderedMessages.get(renderedId);
                this.messageContainer.removeChild(messageEl);
                this.renderedMessages.delete(renderedId);
            }
        });
    }

    createMessageElement(msg)
    {
        let { id, user_role, user, content, status } = msg;

        const messageEl = document.createElement('div');
        messageEl.className = `message ${user_role}`;
        messageEl.dataset.id = id; // Store ID as a data attribute
        messageEl.dataset.content = content; // Store content to detect updates
        messageEl.dataset.status = status; // Store status for tools to detect updates

        // Render differently based on user_role
        if (user_role === 'developer') {
            messageEl.innerHTML = `
                <div class="developer-pill" onclick="toggleExpand('${id}')">
                    Assistant Instructions
                </div>
                <div class="developer-content hidden" id="message-content-${id}">
                    ${content}
                </div>
            `;
        } else if (user_role === 'user') {
            messageEl.innerHTML = `
                <div class="user-message">
                    <div class="username-bubble">${user}</div>
                    <div class="user-prompt">${content}</div>
                </div>
            `;
        } else if (user_role === 'assistant') {
            if (Object.values(msg.tool_calls).length > 0) {
                const toolCallsContent = msg.tool_calls.map((toolCall, index) => {
                    console.log(toolCall);
                    const args = toolCall.function.arguments;
                    return `
    <div class="tool-pill processing" id="tool-${toolCall.id}" data-index="${index}">
        <div class="tool-title" >
            ${toolCall.function.name}
        </div>
        <div class="tool-arguments arguments-${index}" style="display: none;">
            <pre>${args}</pre>
        </div>
    </div>
    `;
                }).join('');
                messageEl.innerHTML += `
    <div class="assistant-tool-calls">
        ${toolCallsContent}
    </div>
`;

                messageEl.addEventListener('click', (e) => {
                    const toolPill = e.target.closest('.tool-pill');
                    if (toolPill) {
                        const argumentsElement = toolPill.querySelector('.tool-arguments');
                        if (argumentsElement) {
                            argumentsElement.style.display = (argumentsElement.style.display === 'none') ? 'block' : 'none';
                        }

                        const contentElement = toolPill.querySelector('.tool-content');
                        if (contentElement) {
                            contentElement.style.display = (contentElement.style.display === 'none') ? 'block' : 'none';
                        }
                    }
                });
            }
            else {
                messageEl.innerHTML = `
                    <div class="assistant-message">
                        ${content}
                    </div>
                `;
            }
        } else if (user_role === 'tool') {
            let toolCallEl = this.messageContainer.querySelector('#tool-' + msg.tool_call_id);
            if (toolCallEl) {
                content = this.parseToolContent(content);

                toolCallEl.classList.add('done');
                toolCallEl.innerHTML += `
                    <pre class="tool-content" style="display: none;">${content}</pre>
                `;
                return messageEl;
            }

            messageEl.innerHTML = `
                <div class="tool-pill finished">
                    <span class="tool-title">${user_role}</span>
                    <div class="tool-content">${this.parseToolContent(content)}</div>
                </div>
            `;
        }


        return messageEl;
    }

    parseToolContent(content)
    {
        content = JSON.stringify(JSON.parse(content),null,2);
        return content.replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    updateMessageElement(existingMessageEl, { content, status })
    {
        // Update content and status if changed
        existingMessageEl.dataset.content = content;
        existingMessageEl.dataset.status = status;

        // Update rendering logic if necessary
        if (existingMessageEl.classList.contains('tool-pill')) {
            // For tools, we might want to update the status or include the content if it's done
            const toolContentEl = existingMessageEl.querySelector('.tool-content');
            if (status === 'done' && !toolContentEl) {
                const contentEl = document.createElement('div');
                contentEl.className = 'tool-content';
                contentEl.textContent = content;
                existingMessageEl.appendChild(contentEl);
            }
        }
    }
}

// Example toggle logic for developer pills
function toggleExpand(messageId)
{
    const contentEl = document.getElementById(`message - content - ${messageId}`);
    if (contentEl) {
        contentEl.classList.toggle('hidden');
    }
}
