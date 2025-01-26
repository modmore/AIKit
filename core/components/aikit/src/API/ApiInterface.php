<?php

namespace modmore\AIKit\API;

use MODX\Revolution\modX;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface ApiInterface
{
    public function __construct(modX $modx);
    public function handleRequest(ServerRequestInterface $request): ResponseInterface;
}
