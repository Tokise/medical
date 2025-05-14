<?php
session_start();
require_once '../../../../../config/config.php';

// Only staff allowed
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'staff') {
    header("Location: /medical/auth/login.php");
    exit;
}

// Fetch user data for greeting
$user_id = $_SESSION['id'];
$userStmt = $conn->prepare("SELECT first_name, last_name FROM users WHERE user_id = ?");
$userStmt->bind_param("i", $user_id);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();

include_once '../../../../../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClinicBot for Staff - School Clinic</title>
    <link rel="stylesheet" href="/medical/src/styles/variables.css">
    <link rel="stylesheet" href="/medical/src/styles/components.css">
    <link rel="stylesheet" href="/medical/src/styles/global.css">
    <link rel="stylesheet" href="/medical/src/modules/dashboard/staff/styles/staff.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
  body {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    margin: 0;
    padding: 20px;
  }

  .staff-dashboard {
    max-width: 800px;
    width: 100%;
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

  .chat-container {
    width: 100%;
    max-width: 600px;
    margin: 0 auto;
    padding: 20px;
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

  /* Bot messages (left) */
  .message.ai {
    justify-content: flex-start;
    color: #007bff;
  }
  .message.ai .message-bubble {
    background-color: #f1f1f1;
    color: #333;
  }

  /* User messages (right) */
  .message.user {
    justify-content: flex-end;
    color: #007bff;
  }
  .message.user .message-bubble {
    background-color: #e3f2fd;
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
  
  .actions-container {
    display: flex;
    gap: 10px;
    margin-bottom: 10px;
    flex-wrap: wrap;
  }
  
  .quick-action {
    padding: 5px 10px;
    background-color: #f1f1f1;
    border: 1px solid #ddd;
    border-radius: 15px;
    cursor: pointer;
    font-size: 0.9rem;
  }
  
  .quick-action:hover {
    background-color: #e3f2fd;
  }
</style>

</head>
<body>
  <div class="staff-dashboard">
    <div class="dashboard-grid">
      <div class="dashboard-column">
        <div class="section-header">
          <h2 class="section-title">Clinic Staff Assistant Bot</h2>
          <p>Welcome <?= htmlspecialchars($user['first_name'] ?? 'Staff Member') . ' ' . htmlspecialchars($user['last_name'] ?? '') ?>, how can I help you manage the school clinic today?</p>
        </div>

        <div class="chat-container">
          <div class="actions-container">
            <div class="quick-action" onclick="insertQuery('How do I update student medical records?')">Update Records</div>
            <div class="quick-action" onclick="insertQuery('What is the protocol for medication administration?')">Medication Protocol</div>
            <div class="quick-action" onclick="insertQuery('How do I document a student visit?')">Document Visit</div>
            <div class="quick-action" onclick="insertQuery('What are the emergency response procedures?')">Emergency Response</div>
          </div>
          <div class="chat-messages" id="chatMessages"></div>
          <div class="chat-input-area">
            <input type="text" id="userInput" placeholder="Type your question..." autocomplete="off" />
            <button id="sendButton"><i class="fas fa-paper-plane"></i></button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    const conversationHistory = [
      {
        role: 'system',
        content: `You are ClinicBot for Clinic Staff, an AI assistant for school health professionals. You handle:
- Student medical record management and updates
- Medication administration protocols and documentation
- Health screening procedures and documentation
- Emergency response procedures in the clinic
- Communication with parents about student health
- Documentation requirements for clinic visits
- Inventory management for medical supplies
- Compliance with school health policies and regulations
- Coordination with school administration on health matters
- Managing chronic conditions and student health plans

Provide specific clinical guidance tailored to school health staff needs. Focus on proper documentation, protocol adherence, and best practices for school-based healthcare. Direct complex medical decisions to physicians when appropriate.`
      },
      {
        role: 'assistant',
        content: `Hello! I'm ClinicBot for Clinic Staff. I can help you with managing student health records, medication protocols, emergency procedures, and documentation requirements. How can I assist you with clinic operations today?`
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

    function insertQuery(queryText) {
        userInput.value = queryText;
        userInput.focus();
    }

    async function handleSend() {
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
                body: JSON.stringify({ model: 'gpt-3.5-turbo', messages: conversationHistory })
            });
            const data = await res.json();
            const reply = data.choices?.[0]?.message?.content || 'Sorry, I didn\'t catch that. Can you rephrase?';
            thinkingBubble.innerHTML = reply;
            conversationHistory.push({ role: 'assistant', content: reply });
        } catch (err) {
            thinkingBubble.innerHTML = 'Error: ' + err.message;
        }
    }

    sendButton.addEventListener('click', handleSend);
    userInput.addEventListener('keydown', e => { if (e.key === 'Enter') handleSend(); });
</script>
</body>

</html>