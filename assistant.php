<?php
require_once 'config.php';
require_once 'AICategorizer.php';

$qa = new SmartQA();
$popular_faqs = $qa->getPopularFAQs(5);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Assistant - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    
    <?php include 'includes/header.php'; ?>

    <main class="max-w-4xl mx-auto px-4 py-8">
        <div class="bg-white rounded-xl shadow-lg overflow-hidden flex flex-col" style="height: 70vh;" x-data="chatAssistant()">
            
            <!-- Header -->
            <div class="bg-gradient-to-r from-green-600 to-teal-600 p-6 text-white">
                <h2 class="text-2xl font-bold flex items-center">
                    <i class="fas fa-robot mr-3 text-3xl"></i>
                    Smart Q&A Assistant
                </h2>
                <p class="text-green-100 mt-1">Ask about permits, certificates, requirements, and services</p>
            </div>

            <!-- Chat Messages -->
            <div class="flex-1 overflow-y-auto p-6 space-y-4 bg-gray-50" id="chatMessages">
                <div class="flex justify-start">
                    <div class="max-w-xs md:max-w-md px-4 py-3 rounded-lg bg-white text-gray-800 shadow">
                        <p class="font-medium mb-1"><i class="fas fa-robot text-green-600 mr-2"></i>Assistant</p>
                        <p>Hello! I'm your LGU Smart Assistant. Ask me about permits, certificates, schedules, or LGU services.</p>
                    </div>
                </div>

                <template x-for="(message, index) in messages" :key="index">
                    <div :class="message.type === 'user' ? 'flex justify-end' : 'flex justify-start'">
                        <div :class="message.type === 'user' ? 
                            'max-w-xs md:max-w-md px-4 py-3 rounded-lg bg-indigo-600 text-white' : 
                            'max-w-xs md:max-w-md px-4 py-3 rounded-lg bg-white text-gray-800 shadow'">
                            <p x-show="message.type === 'bot'" class="font-medium mb-1">
                                <i class="fas fa-robot text-green-600 mr-2"></i>Assistant
                            </p>
                            <p x-html="message.text" class="whitespace-pre-wrap"></p>
                            <template x-if="message.departments && message.departments.length > 0">
                                <div class="mt-2 pt-2 border-t border-gray-200">
                                    <p class="text-xs font-medium mb-1">Related Departments:</p>
                                    <div class="flex flex-wrap gap-1">
                                        <template x-for="dept in message.departments" :key="dept">
                                            <span class="px-2 py-0.5 bg-green-100 text-green-700 rounded text-xs">
                                                <span x-text="dept"></span>
                                            </span>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                <div x-show="loading" class="flex justify-start">
                    <div class="max-w-xs px-4 py-3 rounded-lg bg-white text-gray-800 shadow">
                        <p class="font-medium mb-1"><i class="fas fa-robot text-green-600 mr-2"></i>Assistant</p>
                        <p><i class="fas fa-spinner fa-spin mr-2"></i>Thinking...</p>
                    </div>
                </div>
            </div>

            <!-- Input Area -->
            <div class="p-4 bg-white border-t border-gray-200">
                <form @submit.prevent="sendMessage" class="flex space-x-2">
                    <input 
                        type="text" 
                        x-model="inputText"
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                        placeholder="Ask about business permits, certificates, schedules..."
                        :disabled="loading"
                    >
                    <button 
                        type="submit"
                        :disabled="loading || !inputText.trim()"
                        class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:bg-gray-400 transition flex items-center"
                    >
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
                <div class="mt-2 text-xs text-gray-500">
                    <i class="fas fa-info-circle mr-1"></i>
                    Try: "business permit requirements", "birth certificate", "garbage schedule", "cedula"
                </div>
            </div>
        </div>

        <!-- Popular FAQs -->
        <?php if (!empty($popular_faqs)): ?>
        <div class="mt-6 bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">
                <i class="fas fa-fire text-orange-500 mr-2"></i>
                Popular Questions
            </h3>
            <div class="space-y-3">
                <?php foreach ($popular_faqs as $faq): ?>
                <button 
                    onclick="askQuestion('<?php echo addslashes($faq['question']); ?>')"
                    class="w-full text-left p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition"
                >
                    <p class="font-medium text-gray-800 text-sm flex items-center">
                        <i class="fas fa-question-circle text-green-600 mr-2"></i>
                        <?php echo htmlspecialchars($faq['question']); ?>
                    </p>
                    <p class="text-xs text-gray-500 mt-1">
                        <i class="fas fa-eye mr-1"></i><?php echo number_format($faq['view_count']); ?> views
                    </p>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script>
        function chatAssistant() {
            return {
                messages: [],
                inputText: '',
                loading: false,
                
                async sendMessage() {
                    if (!this.inputText.trim() || this.loading) return;
                    
                    const userMessage = this.inputText;
                    this.messages.push({
                        type: 'user',
                        text: userMessage
                    });
                    this.inputText = '';
                    this.loading = true;
                    this.scrollToBottom();
                    
                    try {
                        const response = await fetch('api/chat.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                query: userMessage
                            })
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            this.messages.push({
                                type: 'bot',
                                text: data.data.answer,
                                departments: data.data.departments || []
                            });
                        } else {
                            this.messages.push({
                                type: 'bot',
                                text: 'I apologize, but I encountered an error. Please try again or submit a formal concern ticket.'
                            });
                        }
                    } catch (error) {
                        console.error('Chat error:', error);
                        this.messages.push({
                            type: 'bot',
                            text: 'I\'m having trouble connecting. Please check your internet connection and try again.'
                        });
                    } finally {
                        this.loading = false;
                        this.scrollToBottom();
                    }
                },
                
                scrollToBottom() {
                    setTimeout(() => {
                        const chatDiv = document.getElementById('chatMessages');
                        chatDiv.scrollTop = chatDiv.scrollHeight;
                    }, 100);
                }
            }
        }
        
        function askQuestion(question) {
            // Trigger the chat with pre-filled question
            const event = new CustomEvent('ask-question', { detail: question });
            window.dispatchEvent(event);
        }
        
        window.addEventListener('ask-question', (e) => {
            // Find the Alpine component and set the input
            const input = document.querySelector('input[type="text"]');
            if (input) {
                input.value = e.detail;
                input.dispatchEvent(new Event('input'));
                // Trigger form submission
                const form = input.closest('form');
                if (form) {
                    form.dispatchEvent(new Event('submit'));
                }
            }
        });
    </script>
</body>
</html>