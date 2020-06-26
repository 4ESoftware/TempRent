<?php

namespace AppBundle\Chatbot\Models;

class Hashtag
{
    /** @var string $hastag */
    private $hastag;

    /** @var string $description */
    private $description;

    /**
     * @return string
     */
    public function getHastag(): string
    {
        return $this->hastag;
    }

    /**
     * @param string $hastag
     * @return Hashtag
     */
    public function setHastag(string $hastag): Hashtag
    {
        $this->hastag = $hastag;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return Hashtag
     */
    public function setDescription(string $description): Hashtag
    {
        $this->description = $description;
        return $this;
    }
}