<?php

declare(strict_types=1);

namespace BrutalForce\Network;

interface ClientIpResolverInterface
{
    public function resolve(array $server): string;
}

