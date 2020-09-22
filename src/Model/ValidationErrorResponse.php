<?php


namespace Seegurke13\ApiBundle\Model;


use Symfony\Component\Validator\ConstraintViolationListInterface;

class ValidationErrorResponse extends ErrorResponse
{
    public function __construct(ConstraintViolationListInterface $violationList)
    {
        parent::__construct(json_encode(array_map(function ($violation) {
            return $violation->getMessage();
        }, iterator_to_array($violationList))));
    }
}