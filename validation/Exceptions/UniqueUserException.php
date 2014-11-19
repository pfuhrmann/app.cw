<?php

namespace Respect\Validation\Exceptions;

class UniqueUserException extends ValidationException
{
    public static $defaultTemplates = array(
        self::MODE_DEFAULT => array(
            self::STANDARD => '{{name}} must be unique',
        ),
        self::MODE_NEGATIVE => array(
            self::STANDARD => '{{name}} must not be unique',
        )
    );
}
