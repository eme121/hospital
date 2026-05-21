document.addEventListener('DOMContentLoaded', () => {
    const chatIcon = document.getElementById('hope-chat-icon');
    const chatWindow = document.getElementById('hope-chat-window');
    const chatLabel = document.getElementById('hope-chat-label');
    const chatMessages = document.getElementById('chat-messages');
    const chatInput = document.getElementById('chat-input');
    const sendBtn = document.getElementById('send-btn');
    const typingIndicator = document.querySelector('.typing');
    const quickBtns = document.querySelector('.quick-btns');

    // Entrance Delay for Premium Feel
    setTimeout(() => {
        if (chatLabel && !chatWindow.classList.contains('active')) {
            chatLabel.style.opacity = '1';
            chatLabel.style.transform = 'translateY(0) scale(1)';
        }
    }, 2500);

    // Toggle Chat Window
    chatIcon.addEventListener('click', () => {
        const isActive = chatWindow.classList.toggle('active');
        
        if (chatLabel) {
            chatLabel.style.opacity = isActive ? '0' : '1';
            chatLabel.style.pointerEvents = isActive ? 'none' : 'auto';
        }
        
        if (isActive) {
            // Focus input on open
            setTimeout(() => chatInput.focus(), 400);
            
            // Initial Welcome Message if empty
            if (chatMessages.children.length === 0) {
                setTimeout(() => {
                    addMessage("Hello! I am <strong>HOPE</strong>, your premium health assistant. How can I assist your wellness journey today?", 'bot');
                    if (quickBtns) quickBtns.style.display = 'flex';
                }, 500);
            }
        }
    });

    // Send Message
    const sendMessage = async () => {
        const text = chatInput.value.trim();
        if (!text) return;

        // Hide quick buttons when chatting starts
        if (quickBtns) quickBtns.style.display = 'none';

        addMessage(text, 'user');
        chatInput.value = '';

        // Show Typing
        typingIndicator.style.display = 'block';
        chatMessages.scrollTop = chatMessages.scrollHeight;

        try {
            const response = await fetch('ai_assistant/chat_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: text })
            });
            const data = await response.json();
            
            setTimeout(() => {
                typingIndicator.style.display = 'none';
                addMessage(data.reply, 'bot');
            }, 800);
        } catch (error) {
            typingIndicator.style.display = 'none';
            addMessage("I'm having trouble connecting right now. Please call us at +234 123 456 7890.", 'bot');
        }
    };

    sendBtn.addEventListener('click', sendMessage);
    chatInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') sendMessage();
    });

    // Helper: Add Message Bubble
    function addMessage(text, sender) {
        const msgDiv = document.createElement('div');
        msgDiv.className = `message ${sender}`;
        msgDiv.innerHTML = text; // Use innerHTML for clickable links
        chatMessages.appendChild(msgDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;

        // Hide quick buttons if this isn't the first bot message
        if (chatMessages.children.length > 1 && quickBtns) {
            quickBtns.style.setProperty('display', 'none', 'important');
        }
    }

    // Quick Action Buttons
    window.handleQuickAction = (action) => {
        // Hide buttons immediately on any action
        if (quickBtns) quickBtns.style.setProperty('display', 'none', 'important');

        if (action === 'Book Appointment') {
            window.location.href = 'appointment.php';
            return;
        }
        if (action === 'Telemedicine') {
            window.location.href = 'telemedicine.php';
            return;
        }
        if (action === 'Emergency') {
            addMessage("🚨 <strong>EMERGENCY CONTACT</strong><br>Please call our 24/7 Emergency Line immediately at:<br><a href='tel:+2341234567890' style='color:#dc2626; font-weight:bold; font-size:1.2em;'>+234 123 456 7890</a><br>Or visit our emergency unit at Emmause Road, Plateau.", 'bot');
            return;
        }
        if (action === 'Hospital Services') {
            window.location.href = 'services.php';
            return;
        }
        chatInput.value = action;
        sendMessage();
    };

    // Hide buttons when user starts typing or focuses
    const hideBtns = () => {
        if (quickBtns) quickBtns.style.setProperty('display', 'none', 'important');
    };

    chatInput.addEventListener('input', hideBtns);
    chatInput.addEventListener('focus', hideBtns);
});
