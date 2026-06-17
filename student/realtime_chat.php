<?php
/**
 * Real-time Chat & Notifications
 * WebSocket-based real-time communication
 */

require_once __DIR__ . '/../includes/functions.php';
requireRole('student');

$page_title = 'Real-time Chat & Notifications';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-2">
                <i data-lucide="message-square" class="me-2"></i>
                Real-time Chat & Notifications
            </h1>
            <p class="text-muted">Connect with instructors and peers instantly</p>
        </div>
    </div>

    <div class="row" style="height: 600px;">
        <!-- Conversations List -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Conversations</h5>
                    <button class="btn btn-sm btn-outline-primary" onclick="startNewChat()">
                        <i data-lucide="plus" style="width: 16px;"></i>
                    </button>
                </div>
                <div class="card-body p-0" style="overflow-y: auto;">
                    <div id="conversationsList" class="list-group list-group-flush">
                        <div class="p-3 text-center text-muted">Loading...</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chat Window -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100 d-flex flex-column">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0" id="chatTitle">Select a conversation</h5>
                        <small class="text-muted" id="chatStatus">Offline</small>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary" onclick="openDetails()">
                        <i data-lucide="info" style="width: 16px;"></i>
                    </button>
                </div>
                <div class="card-body" id="messagesContainer" style="overflow-y: auto; flex: 1;">
                    <div class="text-center text-muted py-5">
                        <p>Select a conversation to start chatting</p>
                    </div>
                </div>
                <div class="card-footer bg-light">
                    <div class="input-group">
                        <input type="text" id="messageInput" class="form-control" placeholder="Type a message..." onkeypress="handleKeyPress(event)">
                        <button class="btn btn-primary" onclick="sendMessage()">
                            <i data-lucide="send" style="width: 16px;"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notifications Panel -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Notifications</h5>
                    <button class="btn btn-sm btn-outline-secondary" onclick="clearNotifications()">Clear</button>
                </div>
                <div class="card-body p-0" style="overflow-y: auto;">
                    <div id="notificationsList" class="list-group list-group-flush">
                        <div class="p-3 text-center text-muted">No notifications</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .message-bubble {
        max-width: 70%;
        padding: 10px 15px;
        border-radius: 10px;
        margin-bottom: 10px;
        word-wrap: break-word;
    }

    .message-own {
        background: #5865f2;
        color: white;
        margin-left: auto;
    }

    .message-other {
        background: #f0f0f0;
        color: #333;
        margin-right: auto;
    }

    body[data-theme="dark"] .message-other {
        background: #3a3f47;
        color: #e4e6eb;
    }

    .conversation-item {
        cursor: pointer;
        padding: 12px;
        border-bottom: 1px solid #e9ecef;
        transition: all 0.2s;
    }

    .conversation-item:hover {
        background: #f8f9fa;
    }

    .conversation-item.active {
        background: #5865f2;
        color: white;
        border-bottom-color: #5865f2;
    }

    body[data-theme="dark"] .conversation-item {
        border-bottom-color: #3a3f47;
    }

    body[data-theme="dark"] .conversation-item:hover {
        background: #2a2f37;
    }

    .notification-item {
        padding: 12px;
        border-bottom: 1px solid #e9ecef;
        font-size: 0.85rem;
    }

    .notification-unread {
        background: #e7f3ff;
    }

    body[data-theme="dark"] .notification-unread {
        background: #1a2a3a;
    }

    .status-badge {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #43b581;
        margin-right: 8px;
    }

    .status-badge.offline {
        background: #999;
    }
</style>

<script src="/assets/js/realtime-chat.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
