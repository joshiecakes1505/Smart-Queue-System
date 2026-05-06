<?php

namespace Tests\Unit;

use App\Services\ChatbotService;
use Tests\TestCase;

class ChatbotServiceTest extends TestCase
{
    private ChatbotService $chatbotService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->chatbotService = new ChatbotService();
    }

    public function test_responds_to_queue_system_question()
    {
        $response = $this->chatbotService->respond('How does the queue system work?');
        $this->assertNotEmpty($response);
        $this->assertStringContainsString('queue', strtolower($response));
    }

    public function test_responds_to_priority_service_question()
    {
        $response = $this->chatbotService->respond('Are there priority services?');
        $this->assertNotEmpty($response);
        $this->assertStringContainsString('priority', strtolower($response));
    }

    public function test_provides_suggestions_for_unknown_question()
    {
        $response = $this->chatbotService->respond('xyz random gibberish');
        $this->assertNotEmpty($response);
        $this->assertStringContainsString('help', strtolower($response));
    }

    public function test_returns_faq_topics()
    {
        $topics = $this->chatbotService->getFaqTopics();
        $this->assertNotEmpty($topics);
        $this->assertGreaterThan(0, count($topics));
        $this->assertArrayHasKey('question', $topics[0]);
    }

    public function test_retrieves_faq_by_topic_id()
    {
        $topic = $this->chatbotService->getFaqByTopic('queue-system');
        $this->assertNotNull($topic);
        $this->assertArrayHasKey('question', $topic);
        $this->assertArrayHasKey('answer', $topic);
        $this->assertArrayHasKey('keywords', $topic);
    }

    public function test_returns_null_for_unknown_topic()
    {
        $topic = $this->chatbotService->getFaqByTopic('non-existent-topic');
        $this->assertNull($topic);
    }
}
