<?php

namespace AppBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('anoniban', [$this, 'anonymizeIban']),
        ];
    }

    public function anonymizeIban($iban)
    {
        return substr($iban, 0, 5).'****'.substr($iban, -4);
    }
}