<?php

namespace AppBundle\Chatbot\Models;

class Reply
{
    private string $reply;

    private string $label;

    public function __construct(string $reply, string $label)
    {
        $this->reply = $reply;
        $this->label = $label;
    }

    public function getReply(): string
    {
        return $this->reply;
    }

    public function getLabel(): string
    {
        return $this->label;
    }
}