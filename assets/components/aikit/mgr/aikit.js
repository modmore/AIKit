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
        chatListButton.textContent = 'Chat History';
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
        // @todo showPrompt
        this.messageRenderer = new MessageRenderer(this.messageContainer, this.config);

        // Create the chat list container
        this.chatListContainer = document.createElement('div');
        this.chatListContainer.className = 'chat-list-container';

        const newChatButton = document.createElement('button');
        newChatButton.textContent = 'New Chat';
        newChatButton.className = 'new-chat-button';
        newChatButton.addEventListener('click', () => {
            this.messageRenderer.reset(); // Reset the message renderer
            this.currentConversation = null;
            this.toggleChatsList();
            const textarea = this.rootElement.querySelector('.ai-assistant-textarea');
            if (textarea) {
                textarea.focus(); // Focus the chat input
            }
        });
        this.chatListContainer.appendChild(newChatButton);
        
        mainContent.appendChild(this.chatListContainer);
        // Compose message box
        const footer = document.createElement('div');
        footer.className = 'ai-assistant-footer';

        // Textarea for typing messages
        const textarea = document.createElement('textarea');
        textarea.className = 'ai-assistant-textarea';
        textarea.placeholder = 'Type your message...';

        // Automatically grow textarea to fit content as the user types
        textarea.addEventListener('input', function () {
            this.style.height = 'auto'; // Reset height first
            this.style.height = `${this.scrollHeight + 2}px`; // Adjust to content's height
        });

        // Container for send button and settings link
        const buttonContainer = document.createElement('div');
        buttonContainer.className = 'ai-assistant-button-container';

        // Send button with Font Awesome icon
        const sendButton = document.createElement('button');
        sendButton.className = 'ai-assistant-send-button'; // Add class for custom styling
        sendButton.innerHTML = '<i class="icon icon-paper-plane"></i>'; // Font Awesome send icon

        sendButton.addEventListener('click', () => this.sendMessage(textarea, this.messageContainer));

        // Settings link/icon below the send button
        const settingsLink = document.createElement('a');
        settingsLink.href = MODx.config.manager_url + '?a=configuration&namespace=aikit'; // Replace with the actual path
        settingsLink.className = 'ai-assistant-settings';
        settingsLink.innerHTML = '<i class="icon icon-cog"></i>'; // Font Awesome settings icon

        buttonContainer.appendChild(sendButton);
        buttonContainer.appendChild(settingsLink);

        footer.appendChild(textarea);
        footer.appendChild(buttonContainer);

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
            const chatItems = this.chatListContainer.querySelectorAll('.chat-item');
            chatItems.forEach(chatItem => chatItem.remove());
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
        this.messageRenderer.reset();
        this.loadChatMessages(conversationId);
    }

    loadChatMessages(conversationId)
    {
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

        this.isLoading = true;
        const messageContent = textarea.value.trim();
        if (!messageContent) {
            return;
        }

        const loadingIndicator = document.createElement('div');
        loadingIndicator.textContent = 'Processing...';
        messageContainer.appendChild(loadingIndicator);

        this.awaitAsyncMessages(this.currentConversation);

        textarea.value = '';
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
                    loadingIndicator.remove();
                    // make sure to load all messages
                    this.loadChatMessages(this.currentConversation);
                } else if (response.status === 500) {
                    loadingIndicator.textContent = jsonResponse.error || 'An error occurred.';
                }
                this.isLoading = false;
            })
            .catch(error => {
                loadingIndicator.textContent = 'Error sending message.';
                console.error('Error sending message:', error);
                this.isLoading = false;
            });
    }

    awaitAsyncMessages(conversationId)
    {
        const lastMessageId = this.messageRenderer.renderedMessages.size > 0 ?
            [...this.messageRenderer.renderedMessages.keys()].pop() : 0;

        fetch(`${this.config.assetsUrl}api.php?a=conversation/await&conversation=${this.currentConversation}&last_message=${lastMessageId}`)
            .then(response => response.json())
            .then(data => {
                const msgs = Object.values(data.data);
                const firstMessage = msgs.length > 0 ? msgs[0] : null;
                if (firstMessage && firstMessage.conversation === this.currentConversation) {
                    this.renderChatMessages(msgs);
                }
                if (this.isLoading && conversationId === this.currentConversation) {
                    this.awaitAsyncMessages(conversationId);
                }
            })
            .catch(error => console.error('Error fetching new messages:', error));
    }
}

class MessageRenderer {
    constructor(messageContainer, config)
    {
        this.messageContainer = messageContainer;
        this.renderedMessages = new Map(); // To track rendered messages by their IDs
        this.config = config;
    }

    reset()
    {
        this.renderedMessages.clear();
        this.messageContainer.innerHTML = '';
    }

    renderMessages(messages)
    {
        // Loop through incoming messages
        messages.forEach(message => {
            const { id, user_role, user, content, status } = message;
            if (!this.config.showSystemPrompt && user_role === 'developer') {
                return;
            }

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
    }

    createMessageElement(msg)
    {
        let { id, user_role, content, status } = msg;

        const md = markdownit({
            linkify: true,
            typographer: true,
            breaks: true,
        });

        const messageEl = document.createElement('div');
        messageEl.className = `message ${user_role}`;
        messageEl.dataset.id = id; // Store ID as a data attribute
        messageEl.dataset.content = content; // Store content to detect updates
        messageEl.dataset.status = status; // Store status for tools to detect updates

        // Render differently based on user_role
        if (user_role === 'developer') {
            content = md.render(content);
            messageEl.innerHTML = `
                <div class="developer-message">
                    <div class="username-bubble">Assistant Instructions</div>
                    <div class="developer-prompt">${content}</div>
                </div>
            `;
        } else if (user_role === 'user') {
            messageEl.innerHTML = `
                <div class="user-message">
                    <div class="username-bubble">${msg.user_username}</div>
                    <div class="user-prompt">${content}</div>
                </div>
            `;
        } else if (user_role === 'assistant') {
            if (Object.values(msg.tool_calls).length > 0) {
                const toolCallsContent = msg.tool_calls.map((toolCall, index) => {
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
                content = md.render(content);

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
        try {
            let json = JSON.parse(content);
            content = JSON.stringify(json, null, 2);
        } catch (e) { }
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
    const contentEl = document.getElementById("message-content-" + messageId);
    if (contentEl) {
        contentEl.classList.toggle('hidden');
    }
}
