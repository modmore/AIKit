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
        chatListButton.textContent = 'Chats';
        chatListButton.className = 'ai-assistant-chatlist-button';
        chatListButton.addEventListener('click', () => this.renderChatsList());

        const closeButton = document.createElement('button');
        closeButton.textContent = 'Close';
        closeButton.addEventListener('click', () => this.toggleAssistant());

        header.appendChild(chatListButton);
        header.appendChild(closeButton);

        const mainContent = document.createElement('div');
        mainContent.className = 'ai-assistant-main';

        const messageContainer = document.createElement('div');
        mainContent.appendChild(messageContainer);

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

    // Render chats list
    renderChatsList(conversations = [])
    {
        const mainContent = this.rootElement.querySelector('.ai-assistant-main');
        if (!mainContent) {
            return;
        }

        mainContent.innerHTML = ''; // Clear main content

        Object.values(conversations).forEach(chat => {
            const chatItem = document.createElement('div');
            chatItem.className = 'chat-item';
            chatItem.textContent = `${chat.title} (By: ${chat.started_by})`;
            chatItem.addEventListener('click', () => this.openChat(chat.id));
            mainContent.appendChild(chatItem);
        });
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
        console.log('messages', messages);
        const mainContent = this.rootElement.querySelector('.ai-assistant-main');
        if (!mainContent) {
            return;
        }

        mainContent.innerHTML = ''; // Clear chat view

        const messagesContainer = document.createElement('div');
        messagesContainer.className = 'messages-container';

        Object.values(messages).forEach(message => {
            const messageEl = document.createElement('div');
            messageEl.className = `message ${message.user_role}`;
            messageEl.textContent = `${message.user || 'System'}: ${message.content}`;
            messagesContainer.appendChild(messageEl);
        });

        mainContent.appendChild(messagesContainer);
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
                        this.sendMessage(textarea, messageContainer)
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
