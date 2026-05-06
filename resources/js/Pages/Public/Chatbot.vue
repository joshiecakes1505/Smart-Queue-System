<template>
    <div class="min-h-screen flex flex-col bg-gradient-to-br from-blue-50 to-indigo-50">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white py-6 px-4 shadow-lg">
            <div class="max-w-6xl mx-auto">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold">Queue Support Chatbot</h1>
                        <p class="text-blue-100 mt-1">Ask me anything about our queue system</p>
                    </div>
                    <Link href="/" class="text-blue-100 hover:text-white transition">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </Link>
                </div>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto">
            <div class="max-w-2xl mx-auto px-4 py-8">
                <!-- Messages Container -->
                <div class="space-y-4 mb-8" ref="messagesContainer">
                    <!-- Welcome Message -->
                    <div v-if="messages.length === 0" class="text-center py-12">
                        <div class="bg-white rounded-lg shadow p-8">
                            <div class="text-5xl mb-4">👋</div>
                            <h2 class="text-2xl font-bold text-gray-800 mb-3">Welcome to Queue Support</h2>
                            <p class="text-gray-600 mb-6">I'm here to help answer your questions about our queue system, services, and more.</p>
                            <p class="text-sm text-gray-500 mb-6">You can:</p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-6">
                                <div class="text-left p-3 bg-blue-50 rounded border border-blue-200">
                                    <p class="font-semibold text-blue-900">💬 Ask a question</p>
                                    <p class="text-xs text-blue-700">Type anything you want to know</p>
                                </div>
                                <div class="text-left p-3 bg-indigo-50 rounded border border-indigo-200">
                                    <p class="font-semibold text-indigo-900">📋 Browse FAQ</p>
                                    <p class="text-xs text-indigo-700">View common questions below</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Messages -->
                    <transition-group name="fade-slide">
                        <div v-for="(message, index) in messages" :key="index" :class="[
                            'flex',
                            message.sender === 'user' ? 'justify-end' : 'justify-start'
                        ]">
                            <div :class="[
                                'max-w-xs md:max-w-md lg:max-w-lg px-4 py-3 rounded-lg whitespace-pre-wrap font-normal',
                                message.sender === 'user'
                                    ? 'bg-blue-600 text-white rounded-br-none'
                                    : 'bg-white text-gray-800 border border-gray-200 rounded-bl-none shadow'
                            ]">
                                {{ message.text }}
                            </div>
                        </div>
                    </transition-group>

                    <!-- Loading Indicator -->
                    <div v-if="isLoading" class="flex justify-start">
                        <div class="bg-white text-gray-800 border border-gray-200 px-4 py-3 rounded-lg rounded-bl-none shadow">
                            <div class="flex gap-2">
                                <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                                <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s;"></div>
                                <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.4s;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- FAQ Topics Section (visible when no messages) -->
                <div v-if="messages.length <= 1 && faqTopics.length > 0" class="mb-8">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Popular Questions</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <button 
                            v-for="topic in faqTopics" 
                            :key="topic.id"
                            @click="askAboutTopic(topic)"
                            class="text-left p-4 bg-white hover:bg-blue-50 border border-gray-200 hover:border-blue-400 rounded-lg shadow-sm transition hover:shadow-md"
                        >
                            <p class="font-medium text-gray-800 hover:text-blue-600">{{ topic.question }}</p>
                            <p class="text-xs text-gray-500 mt-1">Click to learn more →</p>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Input Area -->
        <div class="bg-white border-t border-gray-200 py-4 px-4 shadow-lg">
            <div class="max-w-2xl mx-auto">
                <form @submit.prevent="sendMessage" class="flex gap-3">
                    <input
                        v-model="userInput"
                        :disabled="isLoading"
                        type="text"
                        placeholder="Type your question here..."
                        autocomplete="off"
                        class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed"
                    />
                    <button
                        type="submit"
                        :disabled="isLoading || userInput.trim() === ''"
                        class="bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white px-6 py-3 rounded-lg font-medium transition flex items-center gap-2"
                    >
                        <span>Send</span>
                        <svg v-if="!isLoading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                        <svg v-else class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';

const messages = ref([]);
const userInput = ref('');
const isLoading = ref(false);
const faqTopics = ref([]);
const messagesContainer = ref(null);

onMounted(() => {
    loadFaqTopics();
    // Add initial greeting
    messages.value.push({
        sender: 'bot',
        text: 'Hi! 👋 How can I help you today? Feel free to ask any questions about our queue system.'
    });
});

const loadFaqTopics = async () => {
    try {
        const response = await axios.get(route('api.chatbot.faq-topics'));
        if (response.data.success) {
            faqTopics.value = response.data.topics.slice(0, 6); // Show first 6 topics
        }
    } catch (error) {
        console.error('Failed to load FAQ topics:', error);
    }
};

const sendMessage = async () => {
    if (!userInput.value.trim()) return;

    const message = userInput.value.trim();
    userInput.value = '';

    // Add user message
    messages.value.push({
        sender: 'user',
        text: message
    });

    // Scroll to latest message
    await nextTick();
    scrollToBottom();

    isLoading.value = true;

    try {
        const response = await axios.post(route('api.chatbot.message'), {
            message: message
        });

        if (response.data.success) {
            messages.value.push({
                sender: 'bot',
                text: response.data.response
            });
        }
    } catch (error) {
        messages.value.push({
            sender: 'bot',
            text: 'Sorry, I encountered an error processing your question. Please try again.'
        });
        console.error('Error sending message:', error);
    } finally {
        isLoading.value = false;
        await nextTick();
        scrollToBottom();
    }
};

const askAboutTopic = async (topic) => {
    userInput.value = topic.question;
    await nextTick();
    await sendMessage();
};

const scrollToBottom = () => {
    if (messagesContainer.value) {
        setTimeout(() => {
            messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
        }, 0);
    }
};
</script>

<style scoped>
.fade-slide-enter-active,
.fade-slide-leave-active {
    transition: all 0.3s ease;
}

.fade-slide-enter-from {
    opacity: 0;
    transform: translateY(10px);
}

.fade-slide-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}
</style>
