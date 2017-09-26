<?php

namespace Fire\Db\Collection;

use Fire\FireDbException;

class Config
{
    /**
     * The configuration that sets which properies of incoming ojbects should be indexed.
     * @var array<string>
     */
    private $_indexable;

    public function setIndexable($indexable)
    {
        if (!is_array($indexable)) {
            throw new FireDbException('Indexable values should be configured as an array of indexable properties.');
        }
        $this->_indexable = $indexable;
    }

    public function getIndexable()
    {
        return $this->_indexable;
    }
}
