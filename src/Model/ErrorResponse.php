<?php


namespace Seegurke13\ApiBundle\Model;


use Symfony\Component\HttpFoundation\Response;

class ErrorResponse extends Response
{
    public function __construct(?string $content = '')
    {
        parent::__construct($content, 200, []);
    }
}