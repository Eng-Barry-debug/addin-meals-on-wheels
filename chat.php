<?php
// Include configuration and check login
require_once __DIR__ . '/admin/includes/config.php';
requireLogin();

// Check if user is admin (for admin dashboard access)
$isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

$pageTitle = $isAdmin ? 'Admin Communication Center' : 'Chat Support';
include 'includes/header.php';

// Add a test customer message if admin and no messages exist (for testing)
if ($isAdmin) {
    try {
        $testStmt = $pdo->query("SELECT COUNT(*) as count FROM customer_messages");
        $messageCount = $testStmt->fetch(PDO::FETCH_ASSOC)['count'];

        if ($messageCount == 0) {
            // Add a test customer message
            $pdo->query("
                INSERT INTO customer_messages (customer_name, customer_email, subject, message, status)
                VALUES ('Test Customer', 'test@example.com', 'Test Message', 'Hello! This is a test message from a customer.', 'unread')
            ");

            // Log the test message activity
            require_once '../includes/ActivityLogger.php';
            $activityLogger = new ActivityLogger($pdo);
            $activityLogger->logActivity("New message from Test Customer (test@example.com): Hello! This is a test message from a customer.", null, 'message');
        }
    } catch (PDOException $e) {
        // Silent fail for test data
    }
}

// Get user info for chat
$sender_name = $_SESSION['username'] ?? $_SESSION['user_name'] ?? 'Anonymous';
$sender_type = $isAdmin ? 'admin' : 'customer';
$conversation_id = $_GET['conversation'] ?? '';
$customer_email = $_GET['email'] ?? '';

// Set default conversation ID if not set
if (empty($conversation_id)) {
    // For standard users, use their email as conversation ID
    if (!$isAdmin) {
        $conversation_id = $_SESSION['user_email'] ?? ($_SESSION['username'] ?? $_SESSION['user_name'] ?? 'customer') . '@example.com';
    } else {
        $conversation_id = 'default';
    }
}

?>

<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Conversations Sidebar -->
            <div class="w-full lg:w-1/3 xl:w-1/4">
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="p-4 bg-primary text-white">
                        <h2 class="text-lg font-semibold"><?php echo $isAdmin ? 'Conversations' : 'Support Inbox'; ?></h2>
                        <p class="text-sm opacity-90"><?php echo $isAdmin ? 'Manage customer conversations' : "We're here to help"; ?></p>
                    </div>

                    <!-- Conversations List -->
                    <div id="conversationsList" class="divide-y divide-gray-200 max-h-[calc(100vh-300px)] overflow-y-auto">
                        <!-- Conversations will be loaded here -->
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="mt-4 bg-white rounded-lg shadow-md p-4">
                    <h3 class="text-sm font-medium text-gray-900 mb-2">Quick Actions</h3>
                    <?php if ($isAdmin): ?>
                        <div class="space-y-2">
                            <a href="/admin/customer_support.php" class="block w-full text-center bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                                <i class="fas fa-cog mr-2"></i>
                                Support Dashboard
                            </a>
                            <button onclick="createNewConversation()" class="w-full bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                                <i class="fas fa-plus mr-2"></i>
                                New Conversation
                            </button>
                        </div>
                    <?php else: ?>
                        <p class="text-sm text-gray-600 mb-3">Need help? Our support team is online and ready to assist you.</p>
                        <div class="text-xs text-gray-500">
                            <p><i class="far fa-clock mr-2"></i> Support Hours: 8 AM - 8 PM</p>
                            <p><i class="far fa-envelope mr-2"></i> support@addinmeals.com</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Chat Area -->
            <div class="flex-1 flex flex-col bg-white rounded-lg shadow-md overflow-hidden">
                <!-- Chat Header -->
                <div class="p-4 border-b flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-10 w-10 rounded-full bg-primary text-white flex items-center justify-center">
                            <i class="fas fa-comments"></i>
                        </div>
                        <div class="ml-3">
                            <h2 class="text-lg font-medium text-gray-900" id="chatHeader">Loading...</h2>
                            <p class="text-sm text-gray-500" id="chatStatus">Connecting...</p>
                        </div>
                    </div>
                    <div class="flex space-x-2">
                        <?php if ($isAdmin): ?>
                            <button onclick="closeConversation()" class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg" title="Close Conversation">
                                <i class="fas fa-times"></i>
                            </button>
                        <?php endif; ?>
                        <button onclick="refreshChat()" class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-50 rounded-lg" title="Refresh">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </div>

                <!-- Messages Container -->
                <div id="messagesContainer" class="flex-1 p-4 overflow-y-auto bg-gray-50" style="max-height: 60vh;">
                    <div class="space-y-4">
                        <!-- Loading indicator -->
                        <div id="loadingMessages" class="text-center py-8">
                            <div class="inline-flex items-center">
                                <i class="fas fa-spinner fa-spin text-primary mr-2"></i>
                                <span class="text-gray-600">Loading messages...</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Message Input -->
                <div class="border-t p-4 bg-white">
                    <form id="messageForm" class="flex items-end space-x-2">
                        <div class="flex-1">
                            <textarea id="messageInput"
                                      placeholder="Type your message..."
                                      rows="1"
                                      class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent resize-none"
                                      style="min-height: 40px; max-height: 120px;"
                                      onkeydown="handleKeyPress(event)"></textarea>
                        </div>
                        <button type="submit"
                                class="bg-primary hover:bg-primary-dark text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center justify-center min-w-[44px] h-[44px]"
                                disabled id="sendButton">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                    <div class="text-xs text-gray-500 mt-2">
                        Press Enter to send, Shift+Enter for new line
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Connection Status Indicator -->
<div id="connectionStatus" class="fixed top-4 right-4 z-50">
    <div class="bg-white rounded-lg shadow-lg p-3 border-l-4 border-green-500">
        <div class="flex items-center">
            <div class="w-3 h-3 rounded-full bg-green-500 mr-2 animate-pulse"></div>
            <span class="text-sm font-medium text-gray-700">Connected</span>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Global variables
    window.currentConversationId = '<?php echo $conversation_id; ?>';
    window.senderName = '<?php echo addslashes($sender_name); ?>';
    window.senderType = '<?php echo $sender_type; ?>';
    window.isAdmin = <?php echo $isAdmin ? 'true' : 'false'; ?>;
    window.lastMessageTime = null;

    // Initialize chat
    initializeChat();

    // Auto-refresh every 3 seconds for real-time updates
    setInterval(refreshMessages, 3000);

    // Load conversations
    loadConversations();

    // If admin, also refresh conversations every 5 seconds to catch new customer messages
    if (window.isAdmin) {
        setInterval(loadConversations, 5000);
    }
});

function initializeChat() {
    // Load messages for current conversation
    loadMessages();

    // Setup message form
    const messageForm = document.getElementById('messageForm');
    const messageInput = document.getElementById('messageInput');
    const sendButton = document.getElementById('sendButton');

    messageForm.addEventListener('submit', function(e) {
        e.preventDefault();
        sendMessage();
    });

    // Enable/disable send button based on input
    messageInput.addEventListener('input', function() {
        sendButton.disabled = this.value.trim() === '';
    });

    // Auto-resize textarea
    messageInput.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 120) + 'px';
    });
}

function handleKeyPress(event) {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        sendMessage();
    }
}

async function loadConversations() {
    try {
        console.log('Loading conversations...');
        const response = await fetch('/api/chat_api.php?action=get_conversations');
        const data = await response.json();

        console.log('Conversations response:', data);

        if (data.success) {
            displayConversations(data.conversations);

            // If admin and no conversations, show helpful message
            if (window.isAdmin && data.conversations.length === 0) {
                console.log('Admin: No conversations found, will show sample data');
            }
        } else {
            console.error('Error loading conversations:', data.error);
            // Show error in UI
            const container = document.getElementById('conversationsList');
            container.innerHTML = `
                <div class="p-4 text-center text-red-500">
                    <i class="fas fa-exclamation-triangle text-2xl mb-2"></i>
                    <p>Error loading conversations</p>
                    <p class="text-sm">${data.error}</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error fetching conversations:', error);
        // Show error in UI
        const container = document.getElementById('conversationsList');
        container.innerHTML = `
            <div class="p-4 text-center text-red-500">
                <i class="fas fa-exclamation-triangle text-2xl mb-2"></i>
                <p>Connection error</p>
                <p class="text-sm">Unable to load conversations</p>
            </div>
        `;
    }
}

function displayConversations(conversations) {
    const container = document.getElementById('conversationsList');

    if (conversations.length === 0) {
        container.innerHTML = `
            <div class="p-4 text-center text-gray-500">
                <i class="fas fa-comments text-2xl mb-2"></i>
                <p>No conversations yet</p>
                <?php if ($isAdmin): ?>
                    <p class="text-sm mt-1">Customer messages will appear here</p>
                <?php else: ?>
                    <p class="text-sm mt-1">Start a conversation with support</p>
                <?php endif; ?>
            </div>
        `;
        return;
    }

    container.innerHTML = conversations.map(conv => `
        <div class="p-4 hover:bg-gray-50 cursor-pointer ${conv.id === window.currentConversationId ? 'bg-primary/5 border-l-4 border-primary' : ''}"
             onclick="switchConversation('${conv.id}', '${conv.customer_name}', '${conv.customer_email}')">
            <div class="flex items-center justify-between">
                <div class="flex items-center flex-1 min-w-0">
                    <div class="flex-shrink-0 h-10 w-10 rounded-full bg-primary text-white flex items-center justify-center">
                        ${conv.customer_name.charAt(0).toUpperCase()}
                    </div>
                    <div class="ml-3 flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">${conv.customer_name}</p>
                        <p class="text-sm text-gray-500 truncate">${conv.customer_email || 'No email'}</p>
                    </div>
                </div>
                <div class="flex flex-col items-end">
                    ${conv.unread_count > 0 ? `<span class="bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center mb-1">${conv.unread_count}</span>` : ''}
                    <span class="text-xs text-gray-500">${formatTime(conv.last_message_at)}</span>
                </div>
            </div>
            <div class="mt-2">
                <span class="px-2 py-1 text-xs rounded-full ${getStatusBadgeClass(conv.status)}">
                    ${conv.status.charAt(0).toUpperCase() + conv.status.slice(1)}
                </span>
            </div>
        </div>
    `).join('');
}

async function loadMessages() {
    try {
        const response = await fetch(`/api/chat_api.php?action=get_messages&conversation_id=${window.currentConversationId}`);
        const data = await response.json();

        if (data.success) {
            displayMessages(data.messages);
            updateChatHeader();
        } else {
            console.error('Error loading messages:', data.error);
        }
    } catch (error) {
        console.error('Error fetching messages:', error);
    }
}

function displayMessages(messages) {
    const container = document.getElementById('messagesContainer');
    const loadingIndicator = document.getElementById('loadingMessages');

    if (messages.length === 0) {
        loadingIndicator.innerHTML = `
            <div class="text-center py-8">
                <i class="fas fa-comments text-4xl text-gray-300 mb-4"></i>
                <p class="text-gray-500">No messages yet. Start the conversation!</p>
            </div>
        `;
        return;
    }

    loadingIndicator.remove();

    const messagesHtml = messages.map(message => {
        // Determine if this is a message sent by the current user
        const isOwn = (message.sender_type === 'admin' && window.isAdmin) ||
                     (message.sender_type === 'customer' && !window.isAdmin);

        // For admin users, show customer messages as received (left side)
        // For customers, show admin messages as received (left side)
        const isReceived = (window.isAdmin && message.sender_type === 'customer') ||
                          (!window.isAdmin && message.sender_type === 'admin');

        return `
            <div class="flex ${isReceived ? 'justify-start' : 'justify-end'} mb-4">
                <div class="max-w-xs lg:max-w-md ${isReceived ? 'order-1' : 'order-2'}">
                    <div class="flex items-center mb-1 ${isReceived ? 'justify-start' : 'justify-end'}">
                        <span class="text-xs text-gray-500 mr-2">${message.sender_name}</span>
                        <span class="text-xs text-gray-400">${formatTime(message.created_at)}</span>
                    </div>
                    <div class="bg-${isReceived ? 'gray-200' : 'primary'} text-${isReceived ? 'gray-800' : 'white'} rounded-lg px-4 py-2 shadow-sm">
                        <p class="text-sm whitespace-pre-wrap">${escapeHtml(message.message)}</p>
                    </div>
                    ${!message.is_read && isReceived ? '<div class="w-2 h-2 bg-blue-500 rounded-full mt-1"></div>' : ''}
                </div>
            </div>
        `;
    }).join('');

    container.innerHTML = messagesHtml;
    scrollToBottom();
}

function updateChatHeader() {
    const header = document.getElementById('chatHeader');
    const status = document.getElementById('chatStatus');

    // For demo purposes, show current conversation
    header.textContent = window.isAdmin ? 'Customer Support Chat' : 'Support Team';
    status.textContent = 'Online - Ready to help';
}

async function sendMessage() {
    const messageInput = document.getElementById('messageInput');
    const sendButton = document.getElementById('sendButton');
    const message = messageInput.value.trim();

    if (!message) return;

    // Disable input while sending
    messageInput.disabled = true;
    sendButton.disabled = true;
    sendButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    try {
        const formData = new FormData();
        formData.append('conversation_id', window.currentConversationId);
        formData.append('message', message);
        formData.append('sender_name', window.senderName);
        formData.append('sender_type', window.senderType);
        formData.append('subject', 'Chat Message');
        formData.append('customer_email', window.currentConversationId);

        const response = await fetch('/api/chat_api.php?action=send_message', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            messageInput.value = '';
            messageInput.style.height = 'auto';

            // Immediately refresh messages to show the new message
            await loadMessages();
            loadConversations(); // Refresh conversations list

            // Trigger notification update event for header
            document.dispatchEvent(new CustomEvent('notificationsUpdated'));

            console.log('Message sent successfully:', data);
        } else {
            alert('Error sending message: ' + data.error);
            console.error('Send message error:', data.error);
        }
    } catch (error) {
        console.error('Error sending message:', error);
        alert('Error sending message. Please try again.');
    } finally {
        // Re-enable input
        messageInput.disabled = false;
        sendButton.disabled = false;
        sendButton.innerHTML = '<i class="fas fa-paper-plane"></i>';
        messageInput.focus();
    }
}

async function refreshMessages() {
    if (document.hidden) return; // Don't refresh if tab is not active

    try {
        const response = await fetch(`/api/chat_api.php?action=get_messages&conversation_id=${window.currentConversationId}&since=${window.lastMessageTime || ''}`);
        const data = await response.json();

        if (data.success && data.messages.length > 0) {
            // Check if there are new messages
            const latestMessage = data.messages[data.messages.length - 1];
            if (!window.lastMessageTime || new Date(latestMessage.created_at) > new Date(window.lastMessageTime)) {
                displayMessages(data.messages);
                window.lastMessageTime = latestMessage.created_at;

                // Show notification for new messages (if not from current user)
                if (latestMessage.sender_type !== window.senderType) {
                    showNotification(`New message from ${latestMessage.sender_name}`);
                }
            }

            // Trigger notification update event for header
            document.dispatchEvent(new CustomEvent('notificationsUpdated'));
        }
    } catch (error) {
        console.error('Error refreshing messages:', error);
    }
}

function switchConversation(conversationId, customerName, customerEmail) {
    window.currentConversationId = conversationId;
    window.lastMessageTime = null; // Reset for new conversation

    // Update active conversation in sidebar
    document.querySelectorAll('#conversationsList > div').forEach(div => {
        div.classList.remove('bg-primary/5', 'border-l-4', 'border-primary');
        if (div.onclick.toString().includes(conversationId)) {
            div.classList.add('bg-primary/5', 'border-l-4', 'border-primary');
        }
    });

    // Update chat header
    const header = document.getElementById('chatHeader');
    const status = document.getElementById('chatStatus');

    if (window.isAdmin && customerName) {
        header.textContent = `Chat with ${customerName}`;
        status.textContent = `${customerEmail} - Active`;
    } else {
        header.textContent = 'Customer Support Chat';
        status.textContent = 'Connected';
    }

    // Load messages for new conversation
    loadMessages();
}

function createNewConversation() {
    // For demo, just switch to a new conversation ID
    const newId = 'new_' + Date.now();
    const customerName = 'New Customer';
    const customerEmail = `customer${Date.now()}@example.com`;

    // Add to conversations list
    const conversationsList = document.getElementById('conversationsList');
    const newConvHTML = `
        <div class="p-4 hover:bg-gray-50 cursor-pointer bg-primary/5 border-l-4 border-primary"
             onclick="switchConversation('${newId}', '${customerName}', '${customerEmail}')">
            <div class="flex items-center justify-between">
                <div class="flex items-center flex-1 min-w-0">
                    <div class="flex-shrink-0 h-10 w-10 rounded-full bg-primary text-white flex items-center justify-center">
                        ${customerName.charAt(0).toUpperCase()}
                    </div>
                    <div class="ml-3 flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">${customerName}</p>
                        <p class="text-sm text-gray-500 truncate">${customerEmail}</p>
                    </div>
                </div>
                <div class="flex flex-col items-end">
                    <span class="text-xs text-gray-500">Just now</span>
                </div>
            </div>
            <div class="mt-2">
                <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">
                    Active
                </span>
            </div>
        </div>
    `;

    conversationsList.insertAdjacentHTML('afterbegin', newConvHTML);
    switchConversation(newId, customerName, customerEmail);
}

function closeConversation() {
    if (confirm('Are you sure you want to close this conversation?')) {
        // Update conversation status to closed
        fetch('/api/chat_api.php?action=close_conversation', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `conversation_id=${window.currentConversationId}`
        }).then(() => {
            loadConversations();
            switchConversation('conv_001'); // Switch back to default
        });
    }
}

function refreshChat() {
    loadMessages();
    loadConversations();
}

function scrollToBottom() {
    const container = document.getElementById('messagesContainer');
    container.scrollTop = container.scrollHeight;
}

function showNotification(message) {
    // Simple notification - in production, use a proper notification library
    if ('Notification' in window && Notification.permission === 'granted') {
        new Notification(message);
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatTime(timestamp) {
    const date = new Date(timestamp);
    const now = new Date();
    const diff = now - date;

    if (diff < 60000) { // Less than 1 minute
        return 'Just now';
    } else if (diff < 3600000) { // Less than 1 hour
        return Math.floor(diff / 60000) + 'm ago';
    } else if (diff < 86400000) { // Less than 1 day
        return Math.floor(diff / 3600000) + 'h ago';
    } else {
        return date.toLocaleDateString();
    }
}

function getStatusBadgeClass(status) {
    const classes = {
        'active': 'bg-green-100 text-green-800',
        'waiting': 'bg-yellow-100 text-yellow-800',
        'closed': 'bg-gray-100 text-gray-800'
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
}

// Request notification permission
if ('Notification' in window && Notification.permission === 'default') {
    Notification.requestPermission();
}

// Handle page visibility changes
document.addEventListener('visibilitychange', function() {
    if (!document.hidden) {
        // Page became visible, refresh data
        refreshMessages();
        loadConversations();
    }
});
</script>

<style>
/* Chat-specific styles */
#messagesContainer {
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 #f1f5f9;
}

#messagesContainer::-webkit-scrollbar {
    width: 6px;
}

#messagesContainer::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 3px;
}

#messagesContainer::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

#messagesContainer::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Message bubble animations */
@keyframes messageSlideIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.message-animation {
    animation: messageSlideIn 0.3s ease-out;
}

/* Connection status animation */
@keyframes connectionPulse {
    0%, 100% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: 0.7;
        transform: scale(0.95);
    }
}

.connection-pulse {
    animation: connectionPulse 2s ease-in-out infinite;
}

/* Responsive adjustments */
@media (max-width: 1024px) {
    #connectionStatus {
        top: 1rem;
        right: 1rem;
    }
}

/* Typing indicator */
.typing-indicator {
    display: flex;
    align-items: center;
    padding: 0.5rem;
    background: #f3f4f6;
    border-radius: 0.5rem;
    margin: 0.5rem 0;
}

.typing-indicator span {
    height: 4px;
    width: 4px;
    background: #9ca3af;
    border-radius: 50%;
    margin: 0 2px;
    animation: typing 1.4s infinite ease-in-out;
}

.typing-indicator span:nth-child(2) {
    animation-delay: 0.2s;
}

.typing-indicator span:nth-child(3) {
    animation-delay: 0.4s;
}

@keyframes typing {
    0%, 60%, 100% {
        transform: translateY(0);
        opacity: 0.4;
    }
    30% {
        transform: translateY(-10px);
        opacity: 1;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
