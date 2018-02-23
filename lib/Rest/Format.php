<?php

namespace Rest;

/**
 * Constants used in \Rest\Server Class.
 * As per the requirement we can also add more formats
 */
class Format
{

    const JSON = 'application/json';
    
    /** @var array */
    static public $formats = array(
        'json'  => RestFormat::JSON,
    );

}
