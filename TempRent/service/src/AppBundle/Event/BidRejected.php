<?php

namespace AppBundle\Event;

use AppBundle\Entity\Bid;
use Symfony\Component\EventDispatcher\Event;

class BidRejected extends Event
{
    const NAME = 'app.bid.rejected';

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
