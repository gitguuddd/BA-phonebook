<?php


namespace App\Service;


use Symfony\Component\Validator\ConstraintViolationListInterface;

class ViolationTransformer
{
    public function transformViolationsList(ConstraintViolationListInterface $violationList): array
    {
        $formatedViolationList = [];
        foreach ($violationList as $violation) {
            $formatedViolationList[] = [$violation->getPropertyPath() => $violation->getMessage()];
        }
        return $formatedViolationList;
    }
}