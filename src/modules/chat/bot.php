<?php
session_start();
require_once '../../../config/config.php';

// Only doctors and nurses allowed
if (!isset($_SESSION['id']) || ($_SESSION['role'] !== 'doctor' && $_SESSION['role'] !== 'nurse')) {
    header("Location: /medical/auth/login.php");
    exit;
}

// Fetch user data for greeting
$user_id = $_SESSION['id'];
$userStmt = $conn->prepare("SELECT first_name, last_name FROM users WHERE user_id = ?");
$userStmt->bind_param("i", $user_id);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();

// Get role from session instead of database
$user_role = $_SESSION['role'] ?? 'provider';

include_once '../../../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedAssist for Healthcare Providers - School Clinic</title>
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
    background-color: #f8f9fa;
  }

  .provider-dashboard {
    max-width: 900px;
    width: 100%;
    margin-top: 50px;
  }

  .dashboard-grid {
    display: flex;
    justify-content: center;
  }

  .dashboard-column {
    flex: 1;
    max-width: 700px;
    margin: 20px;
  }

  .section-header {
    text-align: center;
    margin-bottom: 30px;
  }

  .chat-container {
    width: 100%;
    max-width: 700px;
    margin: 0 auto;
    padding: 20px;
    background-color: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
  }

  .chat-messages {
    display: flex;
    flex-direction: column;
    gap: 12px;
    height: 55vh;
    overflow-y: auto;
    margin-bottom: 20px;
    padding: 15px;
    border: 1px solid #e0e0e0;
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
  }
  .message.ai .message-bubble {
    background-color: #f0f4f8;
    color: #2c3e50;
    border-left: 3px solid #3498db;
  }

  /* User messages (right) */
  .message.user {
    justify-content: flex-end;
  }
  .message.user .message-bubble {
    background-color: #e8f4fd;
    color: #34495e;
    border-right: 3px solid #2980b9;
  }

  .message-bubble {
    padding: 12px 15px;
    border-radius: 8px;
    max-width: 75%;
    white-space: pre-wrap;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
  }

  .chat-input-area {
    display: flex;
    gap: 10px;
    justify-content: center;
    margin-top: 15px;
  }

  #userInput {
    flex-grow: 1;
    padding: 12px 15px;
    border: 1px solid #cfd8dc;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.2s;
  }

  #userInput:focus {
    outline: none;
    border-color: #3498db;
    box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
  }

  #sendButton {
    padding: 10px 20px;
    background-color: #3498db;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: background-color 0.2s;
  }

  #sendButton:hover {
    background-color: #2980b9;
  }
  
  .category-heading {
    font-size: 0.9rem;
    font-weight: bold;
    margin: 5px 0;
    color: #34495e;
  }
  
  .actions-container {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-bottom: 15px;
  }
  
  .quick-actions-row {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
  }
  
  .quick-action {
    padding: 6px 12px;
    background-color: #edf2f7;
    border: 1px solid #cfd8dc;
    border-radius: 15px;
    cursor: pointer;
    font-size: 0.9rem;
    transition: all 0.2s;
  }
  
  .quick-action:hover {
    background-color: #d6e4ff;
    border-color: #a9c1fd;
  }
  
  .role-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: bold;
    margin-left: 8px;
    text-transform: capitalize;
  }
  
  .role-doctor {
    background-color: #d4edda;
    color: #155724;
  }
  
  .role-nurse {
    background-color: #cce5ff;
    color: #004085;
  }
</style>

</head>
<body>
  <div class="provider-dashboard">
    <div class="dashboard-grid">
      <div class="dashboard-column">
        <div class="section-header">
          <h2 class="section-title">MedAssist for Healthcare Providers
            <span class="role-badge <?= 'role-' . htmlspecialchars($user_role) ?>">
              <?= htmlspecialchars($user_role) ?>
            </span>
          </h2>
          <p>Welcome <?= htmlspecialchars($user['last_name'] ?? 'Provider') ?>, how can I assist you with patient care today?</p>
        </div>

        <div class="chat-container">
          <div class="actions-container">
            <div class="category-heading">Clinical Resources</div>
            <div class="quick-actions-row">
              <div class="quick-action" onclick="insertQuery('What are the pediatric dosing guidelines for common medications?')">Pediatric Dosing</div>
              <div class="quick-action" onclick="insertQuery('What are the current asthma treatment protocols for school-age children?')">Asthma Protocols</div>
              <div class="quick-action" onclick="insertQuery('What is the standard protocol for concussion evaluation?')">Concussion Assessment</div>
            </div>
            
            <div class="category-heading">Documentation</div>
            <div class="quick-actions-row">
              <div class="quick-action" onclick="insertQuery('What documentation is required for administering emergency medications?')">Emergency Med Docs</div>
              <div class="quick-action" onclick="insertQuery('How should I document a sports-related injury assessment?')">Injury Assessment</div>
              <div class="quick-action" onclick="insertQuery('What consent forms are needed for telehealth consultations?')">Telehealth Consent</div>
            </div>
          </div>
          <div class="chat-messages" id="chatMessages"></div>
          <div class="chat-input-area">
            <input type="text" id="userInput" placeholder="Type your clinical question..." autocomplete="off" />
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
        content: `You are MedAssist, a clinical decision support assistant for doctors and nurses in a school-based health clinic. You provide:

1. Evidence-based clinical guidance for pediatric and adolescent patients (ages 5-18)
2. Medication dosing information and drug interaction checks for common pediatric medications
3. Treatment protocols for common conditions seen in school settings:
   - Respiratory conditions (asthma, allergies, infections)
   - Musculoskeletal injuries (sprains, strains, fractures)
   - Infectious diseases (flu, strep, conjunctivitis, skin infections)
   - Chronic condition management (diabetes, seizures, ADHD)
   - Mental health concerns (anxiety, depression, self-harm risk assessment)
4. Clinical documentation best practices and templates
5. Relevant school health regulations and reporting requirements
6. Emergency response protocols and triage guidelines
7. Information on parental consent requirements for treatments

Important guidelines:
- Always note when recommendations would require physician approval if speaking to nursing staff
- Reference current pediatric clinical guidelines when appropriate
- Recognize the limited resources of a school clinic and adapt recommendations accordingly
- Emphasize when emergency services/hospital transfer would be indicated
- Note when parent/guardian notification is legally required
- Remind providers about documentation requirements for interventions

Use medical terminology appropriate for healthcare professionals. Be concise but thorough in your responses.`
      },
      {
        role: 'assistant',
        content: `Hello! I'm MedAssist, your clinical decision support tool for school-based healthcare. I can help with evidence-based treatment protocols, medication information, documentation requirements, and emergency guidance for pediatric and adolescent patients. How can I support your clinical practice today?`
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
        appendMessage('ai', '<em>Processing clinical query...</em>');
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
                    messages: conversationHistory,
                    temperature: 0.3 // Lower temperature for more precise clinical information
                })
            });
            
            if (!res.ok) {
                throw new Error(`API Error: ${res.status}`);
            }
            
            const data = await res.json();
            const reply = data.choices?.[0]?.message?.content || 'Sorry, I\'m unable to process that clinical query. Please rephrase or try again.';
            thinkingBubble.innerHTML = reply;
            conversationHistory.push({ role: 'assistant', content: reply });
        } catch (err) {
            console.error('Error:', err);
            thinkingBubble.innerHTML = 'Error connecting to the clinical support service. Please try again later.';
        }
    }

    sendButton.addEventListener('click', handleSend);
    userInput.addEventListener('keydown', e => { if (e.key === 'Enter') handleSend(); });
</script>
</body>

</html>
