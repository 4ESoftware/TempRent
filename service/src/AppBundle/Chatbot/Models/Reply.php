<?php

namespace AppBundle\Chatbot\Models;

class Reply
{
    /** @var string $reply */
    private $reply;

    /** @var string $label */
    private $label;

    public function __construct(string $reply, string $label)
    {
        $this->reply = $reply;
        $this->label = $label;
    }

    /**
     * @return string
     */
    public function getReply(): string
    {
        return $this->reply;
    }

    /**
     * @param string $reply
     * @return Reply
     */
    public function setReply(string $reply): Reply
    {
        $this->reply = $reply;
        return $this;
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @param string $label
     * @return Reply
     */
    public function setLabel(string $label): Reply
    {
        $this->label = $label;
        return $this;
    }
}