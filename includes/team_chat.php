<?php
if (!function_exists('h')) {
    function h($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

$teamChatUserName = $_SESSION['name'] ?? 'Team Member';
$teamChatUserRole = $_SESSION['role'] ?? 'user';
?>

<style>
.team-chat-launcher{
    position:fixed;
    right:24px;
    bottom:24px;
    z-index:9999;
    display:flex;
    align-items:center;
    gap:10px;
    padding:10px 14px 10px 10px;
    border:1px solid #dbe4ef;
    border-radius:999px;
    background:#fff;
    color:#0f172a;
    box-shadow:0 18px 40px rgba(15,23,42,.18);
    cursor:pointer;
    font-weight:800;
}

.team-chat-launcher img{
    width:42px;
    height:42px;
    object-fit:cover;
    border-radius:50%;
}

.team-chat-window{
    position:fixed;
    right:24px;
    bottom:88px;
    z-index:9999;
    width:340px;
    max-width:calc(100vw - 32px);
    background:#fff;
    border:1px solid #dbe4ef;
    border-radius:12px;
    box-shadow:0 24px 54px rgba(15,23,42,.24);
    overflow:hidden;
    display:none;
}

.team-chat-window.open{
    display:block;
}

.team-chat-head{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    padding:14px;
    background:#0f172a;
    color:#fff;
}

.team-chat-title{
    display:flex;
    align-items:center;
    gap:10px;
    font-weight:800;
}

.team-chat-title img{
    width:34px;
    height:34px;
    object-fit:cover;
    border-radius:50%;
}

.team-chat-close{
    border:none;
    background:rgba(255,255,255,.12);
    color:#fff;
    width:32px;
    height:32px;
    border-radius:8px;
    cursor:pointer;
    font-size:20px;
}

.team-chat-messages{
    height:260px;
    overflow:auto;
    padding:14px;
    background:#f8fafc;
    display:flex;
    flex-direction:column;
    gap:10px;
}

.team-chat-message{
    width:82%;
    padding:10px;
    border-radius:12px;
    background:#fff;
    border:1px solid #e2e8f0;
    align-self:flex-start;
    position:relative;
}

.team-chat-message.mine{
    background:#dcfce7;
    border-color:#bbf7d0;
    align-self:flex-end;
}

.team-chat-message strong{
    display:block;
    font-size:12px;
    color:#334155;
    margin-bottom:4px;
}

.team-chat-message.mine strong{
    color:#166534;
}

.team-chat-message span{
    display:block;
    color:#0f172a;
    line-height:1.4;
    word-break:break-word;
}

.team-chat-message small{
    display:block;
    margin-top:6px;
    color:#64748b;
    font-size:11px;
}

.team-chat-delete{
    position:absolute;
    right:8px;
    top:8px;
    border:none;
    background:rgba(15,23,42,.08);
    color:#334155;
    width:24px;
    height:24px;
    border-radius:50%;
    cursor:pointer;
    font-size:16px;
    line-height:1;
}

.team-chat-delete:hover{
    background:#fee2e2;
    color:#991b1b;
}

.team-chat-form{
    display:flex;
    gap:8px;
    padding:12px;
    border-top:1px solid #e2e8f0;
    background:#fff;
}

.team-chat-form input{
    flex:1;
    border:1px solid #d9e2ec;
    border-radius:8px;
    padding:10px;
    outline:none;
}

.team-chat-form input:focus{
    border-color:#2563eb;
    box-shadow:0 0 0 3px rgba(37,99,235,.12);
}

.team-chat-form button{
    border:none;
    background:#2563eb;
    color:#fff;
    padding:10px 14px;
    border-radius:8px;
    cursor:pointer;
    font-weight:800;
}

@media(max-width:640px){
    .team-chat-launcher{
        right:14px;
        bottom:14px;
    }

    .team-chat-window{
        right:14px;
        bottom:78px;
    }
}
</style>

<button type="button" class="team-chat-launcher" id="teamChatLauncher">
    <img src="../assets/images/chaticon.png" alt="">
    <span>Team Chat</span>
</button>

<div class="team-chat-window" id="teamChatWindow">
    <div class="team-chat-head">
        <div class="team-chat-title">
            <img src="../assets/images/chaticon.png" alt="">
            <div>
                <div>Team Chat</div>
                <small><?php echo h($teamChatUserName . ' - ' . ucfirst($teamChatUserRole)); ?></small>
            </div>
        </div>
        <button type="button" class="team-chat-close" id="teamChatClose" aria-label="Close Team Chat">&times;</button>
    </div>

    <div class="team-chat-messages" id="teamChatMessages"></div>

    <form class="team-chat-form" id="teamChatForm">
        <input type="text" id="teamChatInput" placeholder="Message team..." autocomplete="off" required>
        <button type="submit">Send</button>
    </form>
</div>

<script>
(function(){
    const launcher = document.getElementById('teamChatLauncher');
    const chatWindow = document.getElementById('teamChatWindow');
    const closeButton = document.getElementById('teamChatClose');
    const form = document.getElementById('teamChatForm');
    const input = document.getElementById('teamChatInput');
    const messagesBox = document.getElementById('teamChatMessages');
    const currentUserId = <?php echo (int)($_SESSION['user_id'] ?? 0); ?>;
    const apiUrl = '../includes/team_chat_api.php';
    let refreshTimer = null;

    function escapeText(value){
        return String(value).replace(/[&<>"']/g, function(char){
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char];
        });
    }

    function formatTime(value){
        if (!value) {
            return '';
        }

        return new Date(value.replace(' ', 'T')).toLocaleString();
    }

    function renderMessages(messages){

        if (messages.length === 0) {
            messagesBox.innerHTML = '<div class="team-chat-message"><strong>Team Chat</strong><span>No messages yet.</span><small>Messages older than 24 hours are removed automatically.</small></div>';
            return;
        }

        messagesBox.innerHTML = messages.map(function(message){
            const isMine = Number(message.user_id) === currentUserId;
            const mineClass = isMine ? ' mine' : '';
            const senderName = isMine ? 'You' : escapeText(message.user_name) + ' - ' + escapeText(message.user_role);
            const deleteButton = Number(message.can_delete) === 1
                ? '<button type="button" class="team-chat-delete" data-message-id="' + Number(message.id) + '" aria-label="Delete message">&times;</button>'
                : '';

            return '<div class="team-chat-message' + mineClass + '">' +
                deleteButton +
                '<strong>' + senderName + '</strong>' +
                '<span>' + escapeText(message.message) + '</span>' +
                '<small>' + escapeText(formatTime(message.created_at)) + '</small>' +
            '</div>';
        }).join('');

        messagesBox.scrollTop = messagesBox.scrollHeight;
    }

    async function loadMessages(){
        try {
            const response = await fetch(apiUrl + '?action=list', { credentials: 'same-origin' });
            const data = await response.json();

            if (data.success) {
                renderMessages(data.messages || []);
            }
        } catch (error) {
            messagesBox.innerHTML = '<div class="team-chat-message"><strong>Team Chat</strong><span>Chat is not available right now.</span><small>Please try again.</small></div>';
        }
    }

    async function deleteMessage(messageId){
        try {
            const response = await fetch(apiUrl + '?action=delete', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({id: messageId})
            });
            const data = await response.json();

            if (data.success) {
                loadMessages();
            } else {
                alert(data.message || 'Delete time expired.');
                loadMessages();
            }
        } catch (error) {
            alert('Message could not be deleted.');
        }
    }

    launcher.addEventListener('click', function(){
        chatWindow.classList.toggle('open');

        if (chatWindow.classList.contains('open')) {
            loadMessages();
            refreshTimer = setInterval(loadMessages, 5000);
            input.focus();
        } else if (refreshTimer) {
            clearInterval(refreshTimer);
            refreshTimer = null;
        }
    });

    closeButton.addEventListener('click', function(){
        chatWindow.classList.remove('open');
        if (refreshTimer) {
            clearInterval(refreshTimer);
            refreshTimer = null;
        }
    });

    form.addEventListener('submit', async function(event){
        event.preventDefault();
        const text = input.value.trim();

        if (text === '') {
            return;
        }

        try {
            const response = await fetch(apiUrl + '?action=send', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({message: text})
            });
            const data = await response.json();

            if (data.success) {
                input.value = '';
                loadMessages();
            }
        } catch (error) {
            messagesBox.innerHTML = '<div class="team-chat-message"><strong>Team Chat</strong><span>Message could not be sent.</span><small>Please try again.</small></div>';
        }
    });

    messagesBox.addEventListener('click', function(event){
        const button = event.target.closest('.team-chat-delete');

        if (!button) {
            return;
        }

        deleteMessage(button.getAttribute('data-message-id'));
    });
})();
</script>
