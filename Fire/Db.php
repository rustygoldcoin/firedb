<?php
namespace Fire;

use Fire\Db\Collection;
use Fire\FireDbException;

class Db
{
    /**
     * The database directory
     * @var String
     */
    private $_dbDir;

    /**
     * An array of collection objects.
     * @var Array<Fire\Db\Collection>
     */
    private $_collections;

    /**
     * The constructor
     * @param String $dir The directory of the database
     * @throws Fire\FireDbException If we cannot write to the database directory
     */
    public function __construct($dir)
    {
        $this->_dbDir = $dir;
        $this->_collections = [];

        if (!file_exists($this->_dbDir) || !is_dir($this->_dbDir) || !is_writable($this->_dbDir)) {
            throw new FireDbException('FireDB cannot write to directory "' . $this->_dbDir . '"');
        }
    }

    /**
     * Provides access to a collection.
     * @param String $collectionName The collection name
     * @return Fire\Db\Collection
     */
    public function collection($collectionName)
    {
        $collectionDir = $this->_getCollectionDir($collectionName);
        if (!$this->has($collectionName)) {
            mkdir($collectionDir);
        }
        if (!isset($this->_collections[$collectionName])) {
            $this->_collections[$collectionName] = new Collection($collectionDir);
        }

        return $this->_collections[$collectionName];
    }

    /**
     * Deteremines if this database has this collection.
     * @param String $collectionName The collection name
     * @return Boolean
     */
    public function has($collectionName)
    {
        $collectionDir = $this->_getCollectionDir($collectionName);
        return file_exists($collectionDir) && is_dir($collectionDir);
    }

    /**
     * Returns the collection directory based on the collection name.
     * @param String $collectionName The collection name
     * @return String
     */
    private function _getCollectionDir($collectionName)
    {
        return $this->_dbDir . '/' . $collectionName;
    }

}
