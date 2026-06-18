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

        <!-- Help & Resources Panel -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 help-resources-card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i data-lucide="book-open" style="width: 18px;" class="me-2"></i>Help & Resources
                    </h5>
                </div>
                <div class="card-body p-0 help-resources-scroll" style="overflow-y: auto;">
                    <div class="list-group list-group-flush">
                        <!-- AI Code Assistants -->
                        <div class="resource-section p-3">
                            <small class="resource-category">
                                <i data-lucide="zap" style="width: 12px;"></i>Code AI Assistants
                            </small>
                            <a href="https://chatgpt.com" target="_blank" class="resource-link">
                                <i data-lucide="zap"></i>
                                <span>ChatGPT</span>
                            </a>
                            <a href="https://claude.ai" target="_blank" class="resource-link">
                                <i data-lucide="brain"></i>
                                <span>Claude AI</span>
                            </a>
                            <a href="https://gemini.google.com" target="_blank" class="resource-link">
                                <i data-lucide="sparkles"></i>
                                <span>Google Gemini</span>
                            </a>
                            <a href="https://copilot.microsoft.com" target="_blank" class="resource-link">
                                <i data-lucide="code"></i>
                                <span>MS Copilot</span>
                            </a>
                            <a href="https://www.cohere.com" target="_blank" class="resource-link">
                                <i data-lucide="layers"></i>
                                <span>Cohere</span>
                            </a>
                            <a href="https://perplexity.ai" target="_blank" class="resource-link">
                                <i data-lucide="search"></i>
                                <span>Perplexity</span>
                            </a>
                        </div>

                        <!-- Code-Specific Tools -->
                        <div class="resource-section p-3">
                            <small class="resource-category">
                                <i data-lucide="code" style="width: 12px;"></i>Code Tools
                            </small>
                            <a href="https://github.com/copilot" target="_blank" class="resource-link">
                                <i data-lucide="git-branch"></i>
                                <span>GitHub Copilot</span>
                            </a>
                            <a href="https://www.tabnine.com" target="_blank" class="resource-link">
                                <i data-lucide="zap-off"></i>
                                <span>TabNine</span>
                            </a>
                            <a href="https://www.codeium.com" target="_blank" class="resource-link">
                                <i data-lucide="type"></i>
                                <span>Codeium</span>
                            </a>
                            <a href="https://www.amazon.com/codewhisperer" target="_blank" class="resource-link">
                                <i data-lucide="volume2"></i>
                                <span>CodeWhisperer</span>
                            </a>
                        </div>

                        <!-- Learning & Reference -->
                        <div class="resource-section p-3">
                            <small class="resource-category">
                                <i data-lucide="book" style="width: 12px;"></i>Learning Resources
                            </small>
                            <a href="https://stackoverflow.com" target="_blank" class="resource-link">
                                <i data-lucide="help-circle"></i>
                                <span>Stack Overflow</span>
                            </a>
                            <a href="https://github.com" target="_blank" class="resource-link">
                                <i data-lucide="git-branch"></i>
                                <span>GitHub</span>
                            </a>
                            <a href="https://developer.mozilla.org" target="_blank" class="resource-link">
                                <i data-lucide="globe"></i>
                                <span>MDN Web Docs</span>
                            </a>
                            <a href="https://www.w3schools.com" target="_blank" class="resource-link">
                                <i data-lucide="book"></i>
                                <span>W3Schools</span>
                            </a>
                        </div>

                        <!-- Documentation -->
                        <div class="resource-section p-3">
                            <small class="resource-category">
                                <i data-lucide="file-text" style="width: 12px;"></i>Documentation
                            </small>
                            <a href="https://docs.python.org" target="_blank" class="resource-link">
                                <i data-lucide="file-code"></i>
                                <span>Python</span>
                            </a>
                            <a href="https://laravel.com/docs" target="_blank" class="resource-link">
                                <i data-lucide="feather"></i>
                                <span>Laravel</span>
                            </a>
                            <a href="https://nodejs.org/docs" target="_blank" class="resource-link">
                                <i data-lucide="play-circle"></i>
                                <span>Node.js</span>
                            </a>
                            <a href="https://react.dev" target="_blank" class="resource-link">
                                <i data-lucide="zap"></i>
                                <span>React</span>
                            </a>
                        </div>

                        <!-- Debugging & Testing -->
                        <div class="resource-section p-3">
                            <small class="resource-category">
                                <i data-lucide="wrench" style="width: 12px;"></i>Debugging Tools
                            </small>
                            <a href="https://devtools.tech" target="_blank" class="resource-link">
                                <i data-lucide="wrench"></i>
                                <span>Dev Tools</span>
                            </a>
                            <a href="https://www.postman.com" target="_blank" class="resource-link">
                                <i data-lucide="send"></i>
                                <span>Postman API</span>
                            </a>
                            <a href="https://jsfiddle.net" target="_blank" class="resource-link">
                                <i data-lucide="play"></i>
                                <span>JSFiddle</span>
                            </a>
                            <a href="https://codepen.io" target="_blank" class="resource-link">
                                <i data-lucide="pen-tool"></i>
                                <span>CodePen</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
                            <a href="https://chatgpt.com" target="_blank" class="resource-link">
                                <i data-lucide="zap" style="width: 14px;"></i>
                                <span>ChatGPT</span>
                            </a>
                            <a href="https://claude.ai" target="_blank" class="resource-link">
                                <i data-lucide="brain" style="width: 14px;"></i>
                                <span>Claude AI</span>
                            </a>
                            <a href="https://gemini.google.com" target="_blank" class="resource-link">
                                <i data-lucide="sparkles" style="width: 14px;"></i>
                                <span>Google Gemini</span>
                            </a>
                            <a href="https://copilot.microsoft.com" target="_blank" class="resource-link">
                                <i data-lucide="code" style="width: 14px;"></i>
                                <span>Microsoft Copilot</span>
                            </a>
                            <a href="https://www.cohere.com" target="_blank" class="resource-link">
                                <i data-lucide="layers" style="width: 14px;"></i>
                                <span>Cohere</span>
                            </a>
                            <a href="https://perplexity.ai" target="_blank" class="resource-link">
                                <i data-lucide="search" style="width: 14px;"></i>
                                <span>Perplexity AI</span>
                            </a>
                        </div>

                        <!-- Code-Specific Tools -->
                        <div class="p-3 border-bottom">
                            <small class="text-muted d-block mb-2 text-uppercase font-weight-bold">💻 Code Tools</small>
                            <a href="https://github.com/copilot" target="_blank" class="resource-link">
                                <i data-lucide="git-branch" style="width: 14px;"></i>
                                <span>GitHub Copilot</span>
                            </a>
                            <a href="https://www.tabnine.com" target="_blank" class="resource-link">
                                <i data-lucide="zap-off" style="width: 14px;"></i>
                                <span>TabNine</span>
                            </a>
                            <a href="https://www.codeium.com" target="_blank" class="resource-link">
                                <i data-lucide="type" style="width: 14px;"></i>
                                <span>Codeium</span>
                            </a>
                            <a href="https://www.amazon.com/codewhisperer" target="_blank" class="resource-link">
                                <i data-lucide="whisper" style="width: 14px;"></i>
                                <span>AWS CodeWhisperer</span>
                            </a>
                        </div>

                        <!-- Learning & Reference -->
                        <div class="p-3 border-bottom">
                            <small class="text-muted d-block mb-2 text-uppercase font-weight-bold">📚 Learning Resources</small>
                            <a href="https://stackoverflow.com" target="_blank" class="resource-link">
                                <i data-lucide="help-circle" style="width: 14px;"></i>
                                <span>Stack Overflow</span>
                            </a>
                            <a href="https://github.com" target="_blank" class="resource-link">
                                <i data-lucide="git-branch" style="width: 14px;"></i>
                                <span>GitHub</span>
                            </a>
                            <a href="https://developer.mozilla.org" target="_blank" class="resource-link">
                                <i data-lucide="globe" style="width: 14px;"></i>
                                <span>MDN Web Docs</span>
                            </a>
                            <a href="https://www.w3schools.com" target="_blank" class="resource-link">
                                <i data-lucide="book" style="width: 14px;"></i>
                                <span>W3Schools</span>
                            </a>
                        </div>

                        <!-- Documentation -->
                        <div class="p-3 border-bottom">
                            <small class="text-muted d-block mb-2 text-uppercase font-weight-bold">📖 Documentation</small>
                            <a href="https://docs.python.org" target="_blank" class="resource-link">
                                <i data-lucide="file-code" style="width: 14px;"></i>
                                <span>Python Docs</span>
                            </a>
                            <a href="https://laravel.com/docs" target="_blank" class="resource-link">
                                <i data-lucide="feather" style="width: 14px;"></i>
                                <span>Laravel Docs</span>
                            </a>
                            <a href="https://nodejs.org/docs" target="_blank" class="resource-link">
                                <i data-lucide="node" style="width: 14px;"></i>
                                <span>Node.js Docs</span>
                            </a>
                            <a href="https://react.dev" target="_blank" class="resource-link">
                                <i data-lucide="zap" style="width: 14px;"></i>
                                <span>React Docs</span>
                            </a>
                        </div>

                        <!-- Debugging & Testing -->
                        <div class="p-3">
                            <small class="text-muted d-block mb-2 text-uppercase font-weight-bold">🧪 Debugging Tools</small>
                            <a href="https://devtools.tech" target="_blank" class="resource-link">
                                <i data-lucide="wrench" style="width: 14px;"></i>
                                <span>Dev Tools</span>
                            </a>
                            <a href="https://www.postman.com" target="_blank" class="resource-link">
                                <i data-lucide="send" style="width: 14px;"></i>
                                <span>Postman API</span>
                            </a>
                            <a href="https://jsfiddle.net" target="_blank" class="resource-link">
                                <i data-lucide="play" style="width: 14px;"></i>
                                <span>JSFiddle</span>
                            </a>
                            <a href="https://codepen.io" target="_blank" class="resource-link">
                                <i data-lucide="pen-tool" style="width: 14px;"></i>
                                <span>CodePen</span>
                            </a>
                        </div>
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

    /* Resource Links Styling */
    .resource-link {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 10px;
        color: #007bff;
        text-decoration: none;
        font-size: 0.95rem;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border-radius: 8px;
        margin: 6px 0;
        line-height: 1.4;
    }

    .resource-link:hover {
        color: #0056b3;
        background: rgba(0, 123, 255, 0.12);
        transform: translateX(6px);
        padding-left: 14px;
    }

    .resource-link i {
        flex-shrink: 0;
        opacity: 0.85;
        width: 18px !important;
        height: 18px !important;
        transition: all 0.3s ease;
    }

    .resource-link:hover i {
        opacity: 1;
        transform: scale(1.2);
        color: #0056b3;
    }

    .resource-link span {
        font-weight: 500;
        letter-spacing: 0.3px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Dark Mode Support */
    body[data-theme="dark"] .resource-link {
        color: #66b3ff;
    }

    body[data-theme="dark"] .resource-link:hover {
        color: #99ccff;
        background: rgba(102, 179, 255, 0.15);
    }

    body[data-theme="dark"] .resource-link:hover i {
        color: #99ccff;
    }

    /* Resource Section Category Headers */
    .resource-category {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 14px 8px 10px 8px;
        margin: 12px 0 8px 0;
        border-top: 2px solid #e9ecef;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #6c757d;
    }

    body[data-theme="dark"] .resource-category {
        border-top-color: #3a3f47;
        color: #adb5bd;
    }

    /* Help Panel Scrollbar */
    .help-resources-scroll::-webkit-scrollbar {
        width: 6px;
    }

    .help-resources-scroll::-webkit-scrollbar-track {
        background: transparent;
    }

    .help-resources-scroll::-webkit-scrollbar-thumb {
        background: #ddd;
        border-radius: 3px;
    }

    .help-resources-scroll::-webkit-scrollbar-thumb:hover {
        background: #999;
    }

    body[data-theme="dark"] .help-resources-scroll::-webkit-scrollbar-thumb {
        background: #555;
    }

    body[data-theme="dark"] .help-resources-scroll::-webkit-scrollbar-thumb:hover {
        background: #777;
    }

    /* Help Panel Card Styling */
    .help-resources-card {
        position: relative;
        border: 1px solid #e9ecef;
        background: #f8f9fa;
        transition: all 0.3s ease;
    }

    .help-resources-card:hover {
        border-color: #007bff;
        box-shadow: 0 4px 12px rgba(0, 123, 255, 0.1);
    }

    body[data-theme="dark"] .help-resources-card {
        background: #2a2f37;
        border-color: #3a3f47;
    }

    body[data-theme="dark"] .help-resources-card:hover {
        border-color: #66b3ff;
        box-shadow: 0 4px 12px rgba(102, 179, 255, 0.15);
    }

    .help-resources-card .card-header {
        background: linear-gradient(135deg, rgba(0, 123, 255, 0.05), rgba(0, 123, 255, 0.02));
        border-bottom: 2px solid #e9ecef;
        padding: 16px;
    }

    body[data-theme="dark"] .help-resources-card .card-header {
        background: linear-gradient(135deg, rgba(102, 179, 255, 0.08), rgba(102, 179, 255, 0.03));
        border-bottom-color: #3a3f47;
    }

    .help-resources-card .card-header h5 {
        color: #007bff;
        font-weight: 700;
        font-size: 1.1rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    body[data-theme="dark"] .help-resources-card .card-header h5 {
        color: #66b3ff;
    }

    /* Category Sections */
    .resource-section {
        padding: 12px 0;
    }

    .resource-section:not(:last-child) {
        border-bottom: 1px solid #e9ecef;
    }

    body[data-theme="dark"] .resource-section:not(:last-child) {
        border-bottom-color: #3a3f47;
    }

    /* Animated Background */
    @keyframes resourcePulse {
        0%, 100% {
            box-shadow: 0 0 0 0 rgba(0, 123, 255, 0.1);
        }
        50% {
            box-shadow: 0 0 0 8px rgba(0, 123, 255, 0);
        }
    }

    .resource-link:hover {
        animation: none;
    }
</style>

<script src="/assets/js/realtime-chat.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
