/**
 * Real-time Chat JavaScript with WebSocket
 * Handles instant messaging and notifications
 */

let currentConversationId = null;
let socket = null;
let conversationsList = [];
let userId = null;

document.addEventListener('DOMContentLoaded', function() {
    initializeWebSocket();
    loadConversations();
    loadNotifications();
});

function initializeWebSocket() {
    // Connect to WebSocket server (in production, use real WebSocket)
    // For demo: simulate with polling
    const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
    const wsUrl = `${protocol}//${window.location.host}/websocket`;
    
    // For now, use polling approach
    setInterval(pollMessages, 2000);
    setInterval(pollNotifications, 3000);
}

async function loadConversations() {
    try {
        const response = await fetch('/api/chat/get_conversations.php');
        const data = await response.json();
        
        if (data.success && data.conversations) {
            conversationsList = data.conversations;
            displayConversations();
        }
    } catch (error) {
        console.error('Error loading conversations:', error);
    }
}

function displayConversations() {
    const container = document.getElementById('conversationsList');
    container.innerHTML = '';
    
    if (conversationsList.length === 0) {
        container.innerHTML = '<div class="p-3 text-center text-muted">No conversations yet</div>';
        return;
    }
    
    conversationsList.forEach(conv => {
        const item = document.createElement('button');
        item.className = `conversation-item text-start w-100 border-0 ${currentConversationId === conv.conversation_id ? 'active' : ''}`;
        
        const statusClass = conv.online ? '' : 'offline';
        item.innerHTML = `
            <div class="d-flex justify-content-between align-items-start">
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center">
                        <span class="status-badge ${statusClass}"></span>
                        <strong>${conv.user_name}</strong>
                    </div>
                    <small class="text-muted d-block mt-1">${conv.last_message}</small>
                </div>
                ${conv.unread > 0 ? `<span class="badge bg-danger">${conv.unread}</span>` : ''}
            </div>
        `;
        
        item.onclick = () => openConversation(conv);
        container.appendChild(item);
    });
}

function openConversation(conversation) {
    currentConversationId = conversation.conversation_id;
    
    document.getElementById('chatTitle').textContent = conversation.user_name;
    document.getElementById('chatStatus').textContent = conversation.online ? '🟢 Online' : '⚪ Offline';
    
    loadMessages();
    displayConversations();
}

async function loadMessages() {
    if (!currentConversationId) return;
    
    try {
        const response = await fetch(`/api/chat/get_messages.php?conversation_id=${currentConversationId}`);
        const data = await response.json();
        
        if (data.success && data.messages) {
            displayMessages(data.messages);
        }
    } catch (error) {
        console.error('Error loading messages:', error);
    }
}

function displayMessages(messages) {
    const container = document.getElementById('messagesContainer');
    container.innerHTML = '';
    
    messages.forEach(msg => {
        const bubble = document.createElement('div');
        bubble.className = `d-flex ${msg.is_own ? 'justify-content-end' : 'justify-content-start'}`;
        
        const messageDiv = document.createElement('div');
        messageDiv.className = `message-bubble ${msg.is_own ? 'message-own' : 'message-other'}`;
        
        const timestamp = new Date(msg.created_at);
        const timeStr = timestamp.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        
        messageDiv.innerHTML = `
            <div>${msg.message}</div>
            <small style="opacity: 0.7; font-size: 0.75rem; margin-top: 4px;">${timeStr}</small>
        `;
        
        bubble.appendChild(messageDiv);
        container.appendChild(bubble);
    });
    
    // Scroll to bottom
    container.scrollTop = container.scrollHeight;
}

async function pollMessages() {
    if (currentConversationId) {
        await loadMessages();
    }
}

function handleKeyPress(event) {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        sendMessage();
    }
}

async function sendMessage() {
    if (!currentConversationId) {
        alert('Please select a conversation');
        return;
    }
    
    const input = document.getElementById('messageInput');
    const message = input.value.trim();
    
    if (!message) return;
    
    try {
        const response = await fetch('/api/chat/send_message.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                conversation_id: currentConversationId,
                message: message
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            input.value = '';
            loadMessages();
        }
    } catch (error) {
        console.error('Error sending message:', error);
    }
}

async function loadNotifications() {
    try {
        const response = await fetch('/api/chat/get_notifications.php');
        const data = await response.json();
        
        if (data.success && data.notifications) {
            displayNotifications(data.notifications);
        }
    } catch (error) {
        console.error('Error loading notifications:', error);
    }
}

function displayNotifications(notifications) {
    const container = document.getElementById('notificationsList');
    container.innerHTML = '';
    
    if (notifications.length === 0) {
        container.innerHTML = '<div class="p-3 text-center text-muted">No notifications</div>';
        return;
    }
    
    notifications.forEach(notif => {
        const item = document.createElement('div');
        item.className = `notification-item ${notif.is_read ? '' : 'notification-unread'}`;
        
        let icon = 'bell';
        if (notif.type === 'message') icon = 'message-square';
        if (notif.type === 'exam') icon = 'file-text';
        if (notif.type === 'material') icon = 'book';
        
        item.innerHTML = `
            <div class="d-flex gap-2">
                <i data-lucide="${icon}" style="width: 16px; flex-shrink: 0;"></i>
                <div class="flex-grow-1">
                    <strong>${notif.title}</strong>
                    <p class="mb-1 text-muted" style="font-size: 0.8rem;">${notif.message}</p>
                    <small class="text-muted">${formatTime(notif.created_at)}</small>
                </div>
            </div>
        `;
        
        container.appendChild(item);
    });
    
    lucide.createIcons();
}

function pollNotifications() {
    loadNotifications();
}

function startNewChat() {
    // Open user selection modal
    const userId = prompt('Enter user ID to start chat:');
    if (userId) {
        fetch('/api/chat/create_conversation.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userId })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                loadConversations();
            }
        });
    }
}

function clearNotifications() {
    fetch('/api/chat/clear_notifications.php', {
        method: 'POST'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            loadNotifications();
        }
    });
}

function openDetails() {
    // Open conversation details panel
    alert('Conversation details coming soon');
}

function formatTime(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diff = now - date;
    
    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(diff / 3600000);
    const days = Math.floor(diff / 86400000);
    
    if (minutes < 1) return 'just now';
    if (minutes < 60) return `${minutes}m ago`;
    if (hours < 24) return `${hours}h ago`;
    if (days < 7) return `${days}d ago`;
    
    return date.toLocaleDateString();
}

// Initialize icons
lucide.createIcons();
