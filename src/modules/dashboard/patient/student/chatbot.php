<?php
session_start();
require_once '../../../../../config/config.php';

// Only students allowed
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'student') {
    header("Location: /medical/auth/login.php");
    exit;
}


// Fetch user data for greeting or further use if needed
$user_id = $_SESSION['id'];
$userStmt = $conn->prepare("SELECT first_name FROM users WHERE user_id = ?");
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
    <title>ClinicBot Chat - School Clinic</title>
    <link rel="stylesheet" href="/medical/src/styles/variables.css">
    <link rel="stylesheet" href="/medical/src/styles/components.css">
    <link rel="stylesheet" href="/medical/src/styles/global.css">
    <link rel="stylesheet" href="/medical/src/modules/dashboard/patient/student/styles/student.css">
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

  .student-dashboard {
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
          <h2 class="section-title">ClinicBot Assistant</h2>
          <p>Hello <?= htmlspecialchars($user['first_name'] ?? 'Student') ?>, how can I help you with clinic services today?</p>
        </div>

        <div class="chat-container">
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
        content: `You are ClinicBot, an AI assistant for a school-based clinic. You only handle:
- Appointment booking, rescheduling, cancellation, reminders
- Clinic hours is 8:00 AM to 5:00 PM, location, holiday schedule
- Available services, required forms, fees
- Health-education FAQs: first-aid tips, when to visit clinic vs. doctor, hygiene, nutrition, stress
- Insurance & billing info
- Medication & pharmacy pickup details
- Policy & parental consent guidelines

Do NOT diagnose serious conditions, prescribe meds beyond OTC guidance, or provide legal/psychological counseling. Defer complex queries to human staff. Keep responses concise (1–2 short paragraphs).`
      },
      {
        role: 'assistant',
        content: `Hello! I'm ClinicBot, your school clinic helper. What can I assist you with today?`
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
                    'Authorization': 'Bearer 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  ',
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
