<?php

namespace App\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\DateTimeType;

class MyDateTimeType extends DateTimeType
{
    public function getName()
    {
        return 'mydatetime';
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        $dateTime = parent::convertToPHPValue($value, $platform);

        if ( ! $dateTime) {
            return $dateTime;
        }

        return new MyDateTime($dateTime->format('Y-m-d H:i:s'));
    }

}
