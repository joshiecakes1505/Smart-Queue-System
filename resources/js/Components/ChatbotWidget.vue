<script setup>
import { nextTick, onMounted, ref } from 'vue';
import axios from 'axios';

const isOpen = ref(false);
const messages = ref([]);
const userInput = ref('');
const isLoading = ref(false);
const faqTopics = ref([]);
const messagesContainer = ref(null);

onMounted(() => {
    loadFaqTopics();

    messages.value.push({
        sender: 'bot',
        text: "Hi I'm Quenami!👋 How can I help you today? Feel free to ask any questions about our queue system.",
    });
});

const toggleWidget = () => {
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        nextTick(scrollToBottom);
    }
};

const loadFaqTopics = async () => {
    try {
        const response = await axios.get(route('api.chatbot.faq-topics'));

        if (response.data.success) {
            faqTopics.value = response.data.topics.slice(0, 4);
        }
    } catch (error) {
        console.error('Failed to load FAQ topics:', error);
    }
};

const sendMessage = async () => {
    if (!userInput.value.trim()) {
        return;
    }

    const message = userInput.value.trim();
    userInput.value = '';

    messages.value.push({
        sender: 'user',
        text: message,
    });

    await nextTick();
    scrollToBottom();

    isLoading.value = true;

    try {
        const response = await axios.post(route('api.chatbot.message'), {
            message,
        });

        if (response.data.success) {
            messages.value.push({
                sender: 'bot',
                text: response.data.response,
            });
        }
    } catch (error) {
        messages.value.push({
            sender: 'bot',
            text: 'Sorry, I encountered an error processing your question. Please try again.',
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

<template>
    <div class="fixed bottom-4 right-4 z-50">
        <template v-if="isOpen">
            <transition name="chatbot-pop">
                <div class="mb-3 w-[calc(100vw-2rem)] max-w-[24rem] overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/10 sm:w-96">
                    <div class="flex items-center justify-between bg-gradient-to-r from-blue-600 to-indigo-600 px-4 py-3 text-white">
                        <div>
                            <h2 class="text-sm font-semibold">Quenami</h2>
                            <p class="text-xs text-blue-100">Ask me anything about the queue system</p>
                        </div>
                        <button
                            type="button"
                            @click="toggleWidget"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20"
                            aria-label="Minimize chatbot"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14"></path>
                            </svg>
                        </button>
                    </div>

                    <div ref="messagesContainer" class="max-h-[22rem] space-y-3 overflow-y-auto px-4 py-4">
                        <div v-if="messages.length === 0" class="rounded-xl border border-blue-100 bg-blue-50 p-4 text-sm text-blue-900">
                            I can help with queue registration, status checks, and common support questions.
                        </div>

                        <transition-group name="chatbot-fade">
                            <div
                                v-for="(message, index) in messages"
                                :key="index"
                                :class="['flex', message.sender === 'user' ? 'justify-end' : 'justify-start']"
                            >
                                <div
                                    :class="[
                                        'max-w-[85%] whitespace-pre-wrap rounded-2xl px-3 py-2 text-sm',
                                        message.sender === 'user'
                                            ? 'rounded-br-md bg-blue-600 text-white'
                                            : 'rounded-bl-md border border-gray-200 bg-white text-gray-800 shadow-sm',
                                    ]"
                                >
                                    {{ message.text }}
                                </div>
                            </div>
                        </transition-group>

                        <div v-if="isLoading" class="flex justify-start">
                            <div class="rounded-2xl rounded-bl-md border border-gray-200 bg-white px-3 py-2 shadow-sm">
                                <div class="flex gap-1.5">
                                    <div class="h-2 w-2 animate-bounce rounded-full bg-gray-400"></div>
                                    <div class="h-2 w-2 animate-bounce rounded-full bg-gray-400" style="animation-delay: 0.2s;"></div>
                                    <div class="h-2 w-2 animate-bounce rounded-full bg-gray-400" style="animation-delay: 0.4s;"></div>
                                </div>
                            </div>
                        </div>

                        <div v-if="messages.length <= 1 && faqTopics.length > 0" class="space-y-2 pt-1">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Popular Questions</p>
                            <button
                                v-for="topic in faqTopics"
                                :key="topic.id"
                                type="button"
                                @click="askAboutTopic(topic)"
                                class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-left text-sm transition hover:border-blue-300 hover:bg-blue-50"
                            >
                                {{ topic.question }}
                            </button>
                        </div>
                    </div>

                    <div class="border-t border-gray-200 bg-gray-50 px-4 py-3">
                        <form @submit.prevent="sendMessage" class="flex gap-2">
                            <input
                                v-model="userInput"
                                :disabled="isLoading"
                                type="text"
                                placeholder="Type your question..."
                                autocomplete="off"
                                class="min-w-0 flex-1 rounded-xl border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 disabled:cursor-not-allowed disabled:bg-gray-100"
                            />
                            <button
                                type="submit"
                                :disabled="isLoading || userInput.trim() === ''"
                                class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-gray-400"
                            >
                                <svg v-if="!isLoading" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                                </svg>
                                <svg v-else class="h-4 w-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            </transition>
        </template>

        <template v-else>
            <button
                type="button"
                @click="toggleWidget"
                class="inline-flex h-14 w-14 items-center justify-center rounded-full bg-blue-600 text-white shadow-lg shadow-blue-600/30 transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-white"
                aria-label="Open support chatbot"
                title="Open support chatbot"
            >
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                </svg>
            </button>
        </template>
    </div>
</template>

<style scoped>
.chatbot-fade-enter-active,
.chatbot-fade-leave-active {
    transition: all 0.2s ease;
}

.chatbot-fade-enter-from,
.chatbot-fade-leave-to {
    opacity: 0;
    transform: translateY(8px);
}
</style>