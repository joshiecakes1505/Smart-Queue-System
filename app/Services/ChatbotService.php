<?php

namespace App\Services;

class ChatbotService
{
    /**
     * FAQ data structure
     */
    private const FAQ_DATA = [
        'name' => [
            'question' => 'What\'s your name?',
            'answer' => 'I am Quenami, your friendly queue management chatbot. I\'m here to help you with any questions about our queue system and services!',
            'keywords' => ['name', 'what', 'who'],
        ],
        'queue-system' => [
            'question' => 'How does the queue system work?',
            'answer' => 'Our smart queue system allows you to join queues for various services. You can take a queue number for your preferred service category and monitor your position in real-time. The system estimates your waiting time based on the average service duration.',
            'keywords' => ['queue', 'how', 'system', 'work'],
        ],
        'take-queue' => [
            'question' => 'How do I take a queue number?',
            'answer' => 'Visit the FrontDesk section of our application, select your desired service category from the list, and provide your details (name, contact, client type). Once submitted, you\'ll receive a queue number and can track your position in real-time.',
            'keywords' => ['take', 'queue', 'number', 'get', 'register'],
        ],
        'check-status' => [
            'question' => 'How can I check my queue status?',
            'answer' => 'You can check your queue status in the Public Queue Status section. Enter your queue number to view your current position, estimated waiting time, and service category information.',
            'keywords' => ['check', 'status', 'position', 'waiting', 'time'],
        ],
        'skip-queue' => [
            'question' => 'What happens if I skip my queue?',
            'answer' => 'If you skip your turn when called, the system will count it and you\'ll be moved to the end of the queue. Multiple skips may affect your priority. You can also reinstate yourself if needed.',
            'keywords' => ['skip', 'skip count', 'reinstate', 'turn', 'called'],
        ],
        'priority' => [
            'question' => 'Are there priority services?',
            'answer' => 'Yes! We offer priority service for senior citizens and individuals with high priority needs. When you register, you can select your client type, and the system will automatically prioritize you according to our service category rules.',
            'keywords' => ['priority', 'senior', 'citizen', 'high priority', 'service'],
        ],
        'multiple-services' => [
            'question' => 'Can I register for multiple services at once?',
            'answer' => 'Each queue registration is for a single service at a time. However, you can register for additional services after completing your current transaction. The system supports multiple services in a single transaction for eligible service categories.',
            'keywords' => ['multiple', 'services', 'transaction', 'register'],
        ],
        'eta' => [
            'question' => 'How is the estimated waiting time calculated?',
            'answer' => 'The ETA is calculated based on the average service duration per service category and the number of people ahead of you in the queue. The system updates this in real-time as people are served.',
            'keywords' => ['eta', 'estimated', 'time', 'waiting', 'calculate'],
        ],
        'mobile' => [
            'question' => 'Is there a mobile version?',
            'answer' => 'Yes! Our system is fully responsive and works on mobile devices. You can access all features including queue registration, status checking, and support through your smartphone.',
            'keywords' => ['mobile', 'version', 'phone', 'responsive'],
        ],
        'support' => [
            'question' => 'How do I get support?',
            'answer' => 'For questions or issues, please speak with our FrontDesk staff who can assist you. You can also use this chatbot to find answers to common questions. For urgent matters, ask to speak with a supervisor.',
            'keywords' => ['support', 'help', 'contact', 'issue', 'problem'],
        ],
        'category-info' => [
            'question' => 'What service categories are available?',
            'answer' => 'Available service categories vary by institution. Common categories include admission, registration, billing, and general services. You can see all available categories when you register for a queue.',
            'keywords' => ['service', 'category', 'categories', 'available', 'type'],
        ],
    ];

    /**
     * Process user message and return an appropriate response
     */
    public function respond(string $userMessage): string
    {
        $userMessage = strtolower(trim($userMessage));

        // Check for exact keyword match or similarity
        $bestMatch = $this->findBestMatch($userMessage);

        if ($bestMatch) {
            return self::FAQ_DATA[$bestMatch]['answer'];
        }

        // Default response if no match found
        return $this->getDefaultResponse($userMessage);
    }

    /**
     * Find the best matching FAQ entry based on keywords
     */
    private function findBestMatch(string $userMessage): ?string
    {
        $userWords = $this->tokenize($userMessage);
        $bestMatch = null;
        $bestScore = 0;

        foreach (self::FAQ_DATA as $key => $faq) {
            $score = 0;
            
            // Check keywords
            foreach ($faq['keywords'] as $keyword) {
                if (str_contains($userMessage, $keyword)) {
                    $score += 2;
                }
            }

            // Check question similarity
            $questionWords = $this->tokenize($faq['question']);
            foreach ($userWords as $word) {
                if (in_array($word, $questionWords)) {
                    $score += 1;
                }
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $key;
            }
        }

        return $bestScore > 0 ? $bestMatch : null;
    }

    /**
     * Tokenize a message into individual words
     */
    private function tokenize(string $message): array
    {
        return array_filter(
            preg_split('/[\s\-\?\!\.]+/', strtolower($message)),
            fn($word) => strlen($word) > 2
        );
    }

    /**
     * Get default response when no FAQ match is found
     */
    private function getDefaultResponse(string $userMessage): string
    {
        $suggestions = [
            'I didn\'t quite understand that. Here are some things I can help with:',
            '• How does the queue system work?',
            '• How do I take a queue number?',
            '• How can I check my queue status?',
            '• Are there priority services?',
            '• Is there a mobile version?',
            '• How do I get support?',
            '',
            'Feel free to ask any of these questions, or type your own question!',
        ];

        return implode("\n", $suggestions);
    }

    /**
     * Get all FAQ topics for menu display
     */
    public function getFaqTopics(): array
    {
        return array_map(
            fn($key, $faq) => [
                'id' => $key,
                'question' => $faq['question'],
            ],
            array_keys(self::FAQ_DATA),
            array_values(self::FAQ_DATA)
        );
    }

    /**
     * Get FAQ answer by topic ID
     */
    public function getFaqByTopic(string $topicId): ?array
    {
        return self::FAQ_DATA[$topicId] ?? null;
    }
}
