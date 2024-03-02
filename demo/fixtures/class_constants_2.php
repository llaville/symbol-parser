<?php

namespace App\Domain;

abstract class Some_Class
{
    const
        CONSTANT_ONE   = 101,
        CONSTANT_TWO   = 102,
        CONSTANT_THREE = 103
    ;
}
final class Status extends Some_Class
{
    public const DELETED = 'deleted';

    const
        CONSTANT_ONE   = 101,
        CONSTANT_TWO   = 102,
        CONSTANT_THREE = 103;

    public function getDeletedStatus(): string
    {
        return self::DELETED;
    }
}

// access from FQCN
echo Status::DELETED, PHP_EOL;  // output deleted

$status = new Status;
// access from instance
echo $status::DELETED, PHP_EOL; // output deleted

// access from the class itself
echo $status->getDeletedStatus(), PHP_EOL; // output deleted
