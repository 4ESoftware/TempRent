<?php

namespace AppBundle\Chatbot\Models;

class Hashtag
{
    private string $hastag;

    private string $description;

    public function getHastag(): string
    {
        return $this->hastag;
    }

    public function setHastag(string $hastag): Hashtag
    {
        $this->hastag = $hastag;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): Hashtag
    {
        $this->description = $description;
        return $this;
    }
}