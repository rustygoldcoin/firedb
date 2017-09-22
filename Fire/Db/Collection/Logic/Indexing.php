<?php

namespace Fire\Db\Collection\Logic;

use Fire\Db\Collection\Helper\FileSystem;
use Fire\FireDbException;

/**
 * This helper class contains the logic required for indexing.
 */

class Indexing
{

    /**
     * Constants
     */
    const INDEX_SIZE_LIMIT = 2500;

    /**
     * @var Fire\Db\Collection\Helper\FileSystem
     */
    private $_filesystem;

    /**
     * The Constructor
     * @param Fire\Db\Collection\Helper\FileSystem $filesystem
     */
    public function __construct(FileSystem $filesystem)
    {
        $this->_filesystem = $filesystem;
    }

    /**
     * Returns an index by the lookup id.
     * @param String $indexLookupId
     * @return Array
     */
    public function buildIndexByLookupId($indexLookupId)
    {
        $indexIdsList = $this->_filesystem->getIndex($indexLookupId);
        $indexIds = (!empty($indexIdsList)) ? $this->_convertIndexToArray($indexIdsList) : [];
        $indexes = array_map([$this->_filesystem, 'getIndex'], $indexIds);
        $documentIdsList = implode('', $indexes);
        $documentIds = $this->_convertIndexToArray($documentIdsList);
        return $documentIds;
    }

    /**
     * Returns a subset of a collection registry index.
     * @param Interger $offset
     * @param Interger $length
     * @param Boolean $reverseOrder
     * @return Array<String>
     */
    public function getCollectionIndexedRegistry($offset, $length, $reverseOrder)
    {
        $metaData = $this->_filesystem->getCollectionMetaData();
        $registryLookupId = $metaData->registry;
        $indexLookupList = $this->_filesystem->getIndex($registryLookupId);
        $indexIds = (!empty($indexLookupList)) ? $this->_convertIndexToArray($indexLookupList) : [];
        //reverse the order in which we get the ids
        if ($reverseOrder) {
            array_reverse($indexIds, SORT_STRING);
        }
        //determine which indexes we need to load in
        $startIndex = floor($offset/self::INDEX_SIZE_LIMIT);
        $endIndex = ceil(($offset + $length)/self::INDEX_SIZE_LIMIT);
        $indexLength = $endIndex - $startIndex;
        $indexIds = array_slice($indexIds, $startIndex, $indexLength);
        $indexes = array_map([$this->_filesystem, 'getIndex'], $indexIds);
        $documentIdsList = implode('', $indexes);
        $documentIds = $this->_convertIndexToArray($documentIdsList);
        if ($reverseOrder) {
            rsort($documentIds, SORT_STRING);
        } else {
            sort($documentIds, SORT_STRING);
        }
        $offset = $offset - ($startIndex * self::INDEX_SIZE_LIMIT);
        $documentIds = array_slice($documentIds, $offset, $length);
        return $documentIds;
    }

    /**
     * Adds the document to applicable indexes.
     * @param object $document
     * @return void
     */
    public function indexDocument($document)
    {
        foreach (get_object_vars($document) as $property => $value) {
            if (
                $this->_isPropertyIndexable($property)
                && $this->_isValueIndexable($value)
            ) {
                $indexLookupId = $this->generateIndexLookupId($property, $value);
                $this->_addDocumentToIndex($indexLookupId, $document->__id);
            }
        }

        $metaData = $this->_filesystem->getCollectionMetaData();
        $registry = $metaData->registry;
        if (!$this->_filesystem->documentExists($document->__id)) {
            $this->_addDocumentToIndex($registry, $document->__id);
        }
    }

    /**
     * Removes a document from indexes that are applicable.
     * @param  object $document
     * @return void
     */
    public function removeDocumentIndex($document)
    {
        //@todo add logic to remove a document from indexes it belongs to
    }

    /**
     * Generates a index lookup id from a hash of the property and value.
     * @param  string $property [description]
     * @param  mixed $value    [description]
     * @return string
     */
    public function generateIndexLookupId($property, $value)
    {
        $propHash = md5($property);
        $valHash = md5($value);
        $combinedHash = $propHash . $valHash;

        return md5($combinedHash);
    }

    /**
     * Logic to add a document id to a specific index.
     * @param string $indexLookupId
     * @param string $documentId
     */
    private function _addDocumentToIndex($indexLookupId, $documentId)
    {
        $indexLookup = $this->_filesystem->getIndex($indexLookupId);
        //if the indexLookup is empty we need to create one
        if (empty($indexLookup)) {
            $indexLookup .= $this->_generateIndexId() . ',';
            $this->_filesystem->writeIndex($indexLookupId, $indexLookup);
        }
        $indexLookup = $this->_convertIndexToArray($indexLookup);
        //if we get the current index and it is too big, start a new one
        $indexId = $indexLookup[count($indexLookup) - 1];
        $index = $this->_filesystem->getIndex($indexId);
        if (substr_count($index, ',') >= self::INDEX_SIZE_LIMIT) {
            $indexLookup[] = $this->_generateIndexId();
            $this->_filesystem->writeIndex($indexLookupId, $this->_convertIndexToString($indexLookup));
            $indexId = $indexLookup[count($indexLookup) - 1];
            $index = $this->_filesystem->getIndex($indexId);
        }
        //index the documentId
        $index .= $documentId . ',';
        $this->_filesystem->writeIndex($indexId, $index);
    }

    /**
     * Determines if a document's value can be indexed.
     * @param mixed $value
     * @return boolean
     */
    private function _isValueIndexable($value)
    {
        return (
            is_string($value)
            || is_null($value)
            || is_bool($value)
            || is_integer($value)
        );
    }

    /**
     * Determines if a document's property can be indexed.
     * @param string $property
     * @return boolean
     */
    private function _isPropertyIndexable($property)
    {
        $metaData = $this->_filesystem->getCollectionMetaData();
        $indexable = $metaData->config->getIndexable();
        $indexBlacklist = ['__id', '__revision', '__timestamp'];
        $indexableList = is_array($indexable) ? $indexable : [];
        return !in_array($property, $indexBlacklist) && in_array($property, $indexableList);
    }

    /**
     * Helper method to turn an index string into an array.
     * @param string $index
     * @return array
     */
    private function _convertIndexToArray($index)
    {
        return array_filter(explode(',', $index));
    }

    /**
     * Helper method to turn a index array to a string.
     * @param  array $index
     * @return string
     */
    private function _convertIndexToString($index)
    {
        return implode(',', $index) . ',';
    }

    /**
     * Generates a unique id for an index.
     * @return string
     */
    private function _generateIndexId()
    {
        $id = $this->_filesystem->generateUniqueId();
        return md5($id);
    }

}
