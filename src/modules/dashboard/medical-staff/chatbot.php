<?php
session_start();
require_once '../../../../config/config.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['doctor', 'nurse'])) {
    header('Location: /medical/login.php');
    exit();
}

// Fetch user data for greeting
$user_id = $_SESSION['user_id'];
$userStmt = $conn->prepare("SELECT first_name FROM users WHERE user_id = ?");
$userStmt->bind_param("i", $user_id);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();

$user_role = $_SESSION['role'] ?? 'provider';

include_once '../../../../includes/header.php';

// Fetch conversation history for the current user
$conversation_history = [];
$sql = "SELECT query, response FROM ai_consultations WHERE user_id = ? ORDER BY consultation_date ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $conversation_history[] = $row;
}
$stmt->close();

// Debug: Output conversation history
// echo '<!-- Conversation History: '; print_r($conversation_history); echo ' -->';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedAssist Chatbot - School Clinic</title>
    <link rel="stylesheet" href="/medical/src/styles/variables.css">
    <link rel="stylesheet" href="/medical/src/styles/components.css">
    <link rel="stylesheet" href="/medical/src/styles/global.css">
    <link rel="stylesheet" href="/medical/src/modules/dashboard/patient/user/styles/student.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
      body {
        min-height: 100vh;
        margin: 0;
        padding: 0;
        background: var(--bg-light);
      }
      .student-dashboard {
        max-width: 800px;
        width: 100%;
        margin: 40px auto 0 auto;
        padding: 0;
        background: none;
        border-radius: 0;
        box-shadow: none;
      }
      .dashboard-grid {
        display: flex;
        justify-content: center;
      }
      .dashboard-column {
        flex: 1;
        max-width: 600px;
        margin: 0;
        margin-top: -50px;
      }
      .section-header {
        text-align: left;
        margin-bottom: 0;
        padding: 18px 0 10px 0;
        border-bottom: 1px solid #eee;
      }
      .section-title {
        font-size: 1.3rem;
        font-weight: 700;
        margin: 0 0 2px 0;
        color: #222;
      }
      .section-header p {
        margin: 0;
        font-size: 1rem;
        color: #444;
      }
      .chatbot-page {
        width: 100%;
        display: flex;
        justify-content: center;
      }
      .chat-container {
        width: 100%;
        max-width: 400px;
        margin: 0 auto;
        padding: 0;
        max-height: 900px;
        position: relative;
        background: #fff;
        border-top-left-radius: 16px;
        border-top-right-radius: 16px;
        border-bottom-left-radius: 0;
        border-bottom-right-radius: 0;
        border: 1.5px solid #eee;
        border-bottom: none;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        overflow: hidden;
        
      }
      .chat-header-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        z-index: 2;
        background: #fff;
        padding: 12px 0 0 0;
        border-radius: 16px 16px 0 0;
        font-weight: 700;
        font-size: 1.1rem;
        color: #007bff;
        text-align: center;
        padding-bottom: 10px;
        box-shadow: none;
        margin-top: 0;
        margin-bottom: 5px;
        border-bottom: 3px solid #eee;
      }
      .chat-messages {
        display: flex;
        flex-direction: column;
        gap: 2px;
        height: 65vh;
        overflow-y: auto;
        margin-bottom: 0;
        padding: 54px 0 90px 0;
        background-color: #fff;
        border-radius: 0;
        position: relative;
        border: none;
        margin-top: 20px;
      }
      .chat-input-area-overlay {
        position: absolute;
        left: 0;
        right: 0;
        bottom: 0;
        background: #fff;
        border-radius: 0 0 0 0;
        padding: 12px 12px 12px 12px;
        display: flex;
        gap: 10px;
        box-shadow: none;
        z-index: 2;
        border-top: 1.5px solid #eee;
        align-items: flex-end;
      }
      .message {
        display: flex;
        width: 100%;
        margin-bottom: 10px;
        flex-direction: column;
      }
      .sender-name {
        font-size: 0.85rem;
        font-weight: 600;
        margin-bottom: 2px;
        margin-left: 12px;
        color: #888;
       
      }
      .message.user .sender-name {
        text-align: right;
        margin-right: 18px;
        margin-left: 0;
      }
      .message.ai .sender-name {
        text-align: left;
        margin-left: 18px;
        margin-right: 0;
      }
      .message.ai {
        justify-content: flex-start;
       
      }
      .message.user {
        justify-content: flex-end;
      }
      .message-bubble {
        padding: 10px 15px;
        border-radius: 15px;
        max-width: 80%;
        white-space: pre-wrap;
      }
      .message.ai .message-bubble {
        background-color: #007bff;
        color: #fff;
        border-bottom-left-radius: 15px;
        border: 1px solid #eee;
        padding: 10px 15px;
        margin-left: 10px;
        margin-right: 0;
        margin-bottom: 10px;
        margin-top: 10px;
        max-width: 80%;
        font-size: 14px;
      }
      .message.user .message-bubble {
        background-color: #007bff;
        color: #fff;
        border-bottom-right-radius: 15px;
        margin-right: 10px;
        margin-left: auto;
        margin-bottom: 10px;
        margin-top: 10px;
        max-width: 80%;
      }
      
      .chat-input-area {
        display: flex;
        gap: 10px;
        justify-content: center;
      }
      #userInput {
        flex-grow: 1;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 8px;
        resize: none;
        min-height: 50px;
        max-height: 120px;
        font-size: 1rem;
        line-height: 1.4;
        overflow-y: auto;
      }
      #sendButton {
        height: 40px;
        padding: 0 18px;
        background-color: #007bff;
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-left: 8px;
      }
      #sendButton:hover {
        background-color: #0056b3;
      }
    </style>
</head>
<body>
<div class="student-dashboard">
    <div class="dashboard-grid">
      <div class="dashboard-column">
       

        <div class="chatbot-page">
          <div class="chat-container">
            <div class="chat-header-overlay">MsChat</div>
            <div class="chat-messages" id="chatMessages"></div>
            <div class="chat-input-area-overlay">
              <textarea id="userInput" placeholder="Type your message..." autocomplete="off" rows="1"></textarea>
              <button id="sendButton"><i class="fas fa-paper-plane"></i></button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <script>
    const conversationHistory = [
      {
        role: 'system',
        content: `You are MsChat, an AI assistant for healthcare providers in a school-based clinic. You can help with clinical questions, documentation, medication info, and protocols. Use medical terminology and be concise.`
      },
      {
        role: 'assistant',
        content: `Hello! I'm MsChat, your clinical support chatbot. How can I assist you today?`
      }
    ];
    const chatMessages = document.getElementById('chatMessages');
    const userInput = document.getElementById('userInput');
    const sendButton = document.getElementById('sendButton');
    const OPENROUTER_API_KEY = '<?php echo OPENROUTER_API_KEY; ?>';

    const loadedHistory = <?php echo json_encode($conversation_history); ?>;

    function appendMessage(role, text) {
        const msgDiv = document.createElement('div');
        msgDiv.classList.add('message');
        let senderName = document.createElement('div');
        senderName.classList.add('sender-name');
        if (role === 'ai' || role === 'assistant') {
            msgDiv.classList.add('ai');
            senderName.textContent = 'MsChat';
        } else {
            msgDiv.classList.add('user');
            senderName.textContent = '<?= htmlspecialchars($user['first_name'] ?? 'You') ?>';
        }
        msgDiv.appendChild(senderName);
        const bubble = document.createElement('div');
        bubble.classList.add('message-bubble');
        bubble.innerHTML = text;
        msgDiv.appendChild(bubble);
        chatMessages.appendChild(msgDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    window.addEventListener('load', () => {
        // appendMessage('ai', conversationHistory[1].content);
        // Display loaded history
        loadedHistory.forEach(entry => {
            appendMessage('user', entry.query);
            appendMessage('ai', entry.response);
        });
    });

    async function handleSend(e) {
        e.preventDefault();
        const text = userInput.value.trim();
        if (!text) return;
        appendMessage('user', text);
        conversationHistory.push({ role: 'user', content: text });
        userInput.value = '';
        appendMessage('ai', '<em>Thinking...</em>');
        const thinkingBubble = chatMessages.lastElementChild.querySelector('.message-bubble');
        try {
            const res = await fetch('https://openrouter.ai/api/v1/chat/completions', {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + OPENROUTER_API_KEY,
                    'HTTP-Referer': window.location.origin,
                    'X-Title': 'Medical Management System',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ 
                    model: 'mistralai/mistral-small', 
                    messages: conversationHistory,
                    temperature: 0.7,
                    max_tokens: 1000
                })
            });
            if (!res.ok) {
                throw new Error(`API Error: ${res.status}`);
            }
            const data = await res.json();
            const reply = data.choices?.[0]?.message?.content || 'Sorry, I didn\'t catch that. Can you rephrase?';
            thinkingBubble.innerHTML = reply;
            conversationHistory.push({ role: 'assistant', content: reply });

            // Save conversation to database
            fetch('../../chatbot/save_conversation.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'query=' + encodeURIComponent(text) + '&response=' + encodeURIComponent(reply)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Conversation saved successfully', data.message);
                } else {
                    console.error('Error saving conversation', data.message, data.error);
                }
            })
            .catch(error => {
                console.error('Error sending conversation to save script', error);
            });

        } catch (err) {
            console.error('Error:', err);
            thinkingBubble.innerHTML = 'Error connecting to the chatbot service. Please try again later.';
        }
    }
    sendButton.addEventListener('click', handleSend);
    userInput.addEventListener('keydown', function(e) {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        handleSend(e);
      }
      // Auto-expand textarea
      setTimeout(() => {
        userInput.style.height = 'auto';
        userInput.style.height = userInput.scrollHeight + 'px';
      }, 0);
    });
  </script>
</body>
</html>
