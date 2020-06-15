<?php

namespace AppBundle\Chatbot;

use AppBundle\Chatbot\Models\Hashtag;
use AppBundle\Chatbot\Models\Reply;
use AppBundle\Entity\ConversationLine;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class Chatbot
{
    private const CHATBOT_API = 'http://13.95.10.108:5001/analyze';

    public const GET_HASHTAGS_JOB = 'GET_HASHTAGS';
    public const INFER_CHATBOT_JOB = 'INFER_CHATBOT';
    public const START_CONVERSATION_JOB = 'START_CONVERSATION';

    private Client $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    public function getHashtags()
    {
        $chatbotResponse = $this->client->request('POST', self::CHATBOT_API, [
            RequestOptions::JSON => [
                'JOB' => self::GET_HASHTAGS_JOB,
            ]
        ]);

        $content = json_decode($chatbotResponse->getBody()->getContents(), 1);

        $tags = [];
        foreach ($content['HASHTAGS'] as $HASHTAG => $HASHTAG_description) {
            $tags[] = (new Hashtag())
                ->setHastag($HASHTAG)
                ->setDescription($HASHTAG_description);
        }

        return $tags;
    }

    public function startConversation()
    {
        $chatbotResponse = $this->client->request('POST', self::CHATBOT_API, [
            RequestOptions::JSON => [
                'JOB' => self::START_CONVERSATION_JOB
            ]
        ]);

        $content = json_decode($chatbotResponse->getBody()->getContents());

        return $content->INTRO;
    }

    public function sendReply(ConversationLine $reply)
    {
        $chatbotResponse = $this->client->request('POST', self::CHATBOT_API, [
            RequestOptions::JSON => [
                'JOB' => 'INFER_CHATBOT',
                'CONVERSATION' => $reply->getProject()->getTranscriptAsArray(),
            ]
        ]);

        $content = json_decode($chatbotResponse->getBody()->getContents());

        if ($content->NEXT_UTTERANCE === '') {
            return false;
        }

        return new Reply($content->NEXT_UTTERANCE, $content->USER_LABEL);
    }
}