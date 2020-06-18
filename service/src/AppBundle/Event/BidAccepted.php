<?php

namespace AppBundle\Event;

use AppBundle\Entity\Bid;
use Symfony\Component\EventDispatcher\Event;

class BidAccepted extends Event
{
    const NAME = 'app.bid.accepted';

    /** @var Bid $project */
    private $bid;

    public function __construct(Bid $bid)
    {
        $this->bid = $bid;
    }

    public function getBid(): Bid
    {
        return $this->bid;
    }
}
