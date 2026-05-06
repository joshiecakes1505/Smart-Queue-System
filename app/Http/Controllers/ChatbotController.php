<?php

namespace App\Http\Controllers;

use App\Services\ChatbotService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ChatbotController extends Controller
{
    private ChatbotService $chatbotService;

    public function __construct(ChatbotService $chatbotService)
    {
        $this->chatbotService = $chatbotService;
    }

    /**
     * Display the chatbot page
     */
    public function index()
    {
        $faqTopics = $this->chatbotService->getFaqTopics();

        return Inertia::render('Public/Chatbot', [
            'faqTopics' => $faqTopics,
        ]);
    }

    /**
     * Handle chatbot message and return AI response
     */
    public function sendMessage(Request $request)
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'min:1', 'max:500'],
        ]);

        $userMessage = $validated['message'];
        $response = $this->chatbotService->respond($userMessage);

        return response()->json([
            'success' => true,
            'response' => $response,
            'timestamp' => now(),
        ]);
    }

    /**
     * Get FAQ topics for menu
     */
    public function getFaqTopics()
    {
        $topics = $this->chatbotService->getFaqTopics();

        return response()->json([
            'success' => true,
            'topics' => $topics,
        ]);
    }

    /**
     * Get specific FAQ by topic ID
     */
    public function getFaqTopic(string $topicId)
    {
        $topic = $this->chatbotService->getFaqByTopic($topicId);

        if (!$topic) {
            return response()->json([
                'success' => false,
                'message' => 'Topic not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'topic' => [
                'id' => $topicId,
                'question' => $topic['question'],
                'answer' => $topic['answer'],
            ],
        ]);
    }
}
