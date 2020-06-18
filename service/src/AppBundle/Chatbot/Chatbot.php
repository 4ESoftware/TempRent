<?php

namespace AppBundle\Chatbot;

use AppBundle\Chatbot\Models\Hashtag;
use AppBundle\Chatbot\Models\Reply;
use AppBundle\Entity\ConversationLine;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class Chatbot
{
    public const GET_HASHTAGS_JOB = 'GET_HASHTAGS';
    public const INFER_CHATBOT_JOB = 'INFER_CHATBOT';
    public const START_CONVERSATION_JOB = 'START_CONVERSATION';

    private $apiUrl = null;

    /** @var Client $client */
    private $client;

    public function __construct($apiUrl, $apiPort)
    {
        $this->client = new Client();
        $this->apiUrl = 'http://'.$apiUrl.':'.$apiPort.'/analyze';
    }

    public function getHashtags()
    {
        $chatbotResponse = $this->client->request('POST', $this->apiUrl, [
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
        $chatbotResponse = $this->client->request('POST', $this->apiUrl, [
            RequestOptions::JSON => [
                'JOB' => self::START_CONVERSATION_JOB
            ]
        ]);

        $content = json_decode($chatbotResponse->getBody()->getContents());

        $id = null;
        if (isset($content->CONVERSATION_ID)) {
            $id = $content->CONVERSATION_ID;
        }

        return ['intro' => $content->INTRO, 'id' => $id];
    }

    public function sendReply(ConversationLine $reply)
    {
        $payload = [
            'JOB' => 'INFER_CHATBOT',
            'CONVERSATION' => $reply->getProject()->getTranscriptAsArray(),
        ];

        if ($reply->getProject()->getConversationId() !== null) {
            $payload['CONVERSATION_ID'] = $reply->getProject()->getConversationId();
        }

        $chatbotResponse = $this->client->request('POST', $this->apiUrl, [
            RequestOptions::JSON => $payload,
        ]);

        $content = json_decode($chatbotResponse->getBody()->getContents());

        if ($content->NEXT_UTTERANCE === '') {
            return false;
        }

        return new Reply($content->NEXT_UTTERANCE, $content->USER_LABEL);
    }
}
