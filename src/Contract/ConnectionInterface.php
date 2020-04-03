<?php

namespace Src\MQ\Contract;

interface ConnectionInterface
{
    public function create_receiver();
}