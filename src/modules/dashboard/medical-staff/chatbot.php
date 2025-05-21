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
        margin: 50px auto 0 auto;
        padding: 20px;
        background: var(--bg-white);
        border-radius: 16px;
        box-shadow: 0 4px 16px rgba(0,0,0,0.06);
      }
      .dashboard-grid {
        display: flex;
        justify-content: center;
      }
      .dashboard-column {
        flex: 1;
        max-width: 600px;
        margin: 20px;
      }
      .section-header {
        text-align: center;
        margin-bottom: 30px;
      }
      .chatbot-page {
        width: 100%;
        display: flex;
        justify-content: center;
      }
      .chat-container {
        width: 100%;
        max-width: 600px;
        margin: 0 auto;
        padding: 20px 0 0 0;
      }
      .chat-messages {
        display: flex;
        flex-direction: column;
        gap: 8px;
        height: 60vh;
        overflow-y: auto;
        margin-bottom: 20px;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 8px;
        background: #fff;
      }
      .message {
        display: flex;
        width: 100%;
      }
      .message.ai {
        justify-content: flex-start;
        color: #007bff;
      }
      .message.ai .message-bubble {
        background-color: #f1f1f1;
        color: #333;
      }
      .message.user {
        justify-content: flex-end;
        color: #d4f8c4;
      }
      .message.user .message-bubble {
        background-color: #d4f8c4;
        color: #000;
      }
      .message-bubble {
        padding: 10px;
        border-radius: 8px;
        max-width: 70%;
        white-space: pre-wrap;
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
      }
      #sendButton {
        padding: 10px 20px;
        background-color: #007bff;
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
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
        <div class="section-header">
          <h2 class="section-title">MedAssist Chatbot</h2>
          <p>Hello <?= htmlspecialchars($user['first_name'] ?? 'Provider') ?>, how can I help you with clinic services today?</p>
        </div>
        <div class="chatbot-page">
          <div class="chat-container">
            <div class="chat-messages" id="chatMessages"></div>
            <div class="chat-input-area">
              <input type="text" id="userInput" placeholder="Type your message..." autocomplete="off" />
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
        content: `You are MedAssist, an AI assistant for healthcare providers in a school-based clinic. You can help with clinical questions, documentation, medication info, and protocols. Use medical terminology and be concise.`
      },
      {
        role: 'assistant',
        content: `Hello! I'm MedAssist, your clinical support chatbot. How can I assist you today?`
      }
    ];
    const chatMessages = document.getElementById('chatMessages');
    const userInput = document.getElementById('userInput');
    const sendButton = document.getElementById('sendButton');
    function appendMessage(role, text) {
        const msgDiv = document.createElement('div');
        msgDiv.classList.add('message', role === 'assistant' ? 'ai' : 'user');
        const bubble = document.createElement('div');
        bubble.classList.add('message-bubble');
        bubble.innerHTML = text;
        msgDiv.appendChild(bubble);
        chatMessages.appendChild(msgDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    window.addEventListener('load', () => {
        appendMessage('ai', conversationHistory[1].content);
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
                    'Authorization': 'Bearer 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',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ 
                    model: 'gpt-3.5-turbo', 
                    messages: conversationHistory 
                })
            });
            if (!res.ok) {
                throw new Error(`API Error: ${res.status}`);
            }
            const data = await res.json();
            const reply = data.choices?.[0]?.message?.content || 'Sorry, I didn\'t catch that. Can you rephrase?';
            thinkingBubble.innerHTML = reply;
            conversationHistory.push({ role: 'assistant', content: reply });
        } catch (err) {
            console.error('Error:', err);
            thinkingBubble.innerHTML = 'Error connecting to the chatbot service. Please try again later.';
        }
    }
    sendButton.addEventListener('click', handleSend);
    userInput.addEventListener('keydown', e => { if (e.key === 'Enter') handleSend(e); });
  </script>
</body>
</html>
