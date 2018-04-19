<?php

namespace App\Type;

class MyDateTime extends \DateTime
{
    public function __toString()
    {
        return $this->format('Y-m-d H:i:s');
    }
}
