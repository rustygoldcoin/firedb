<?php

namespace Fire\Db\Collection;

use Fire\FireDbException;

class Config
{
    /**
     * The configuration that sets which properies of incoming ojbects should be indexed.
     * @var array<string>
     */
    public $indexable;

    /**
     * The constructor
     */
    public function __construct($configObj = null)
    {
        //set values based on $configObj
        $config = (!is_null($configObj)) ? $configObj : (object)[];
        if (!is_object($config)) {
            throw new FireDbException('Config must be an object.');
        }

        $this->indexable = (isset($config->indexable) && is_array($config->indexable)) ? $config->indexable : [];
    }
}
