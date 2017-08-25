<?php

namespace firedb\collection\logic;

use firedb\collection\helper\filesystem;
use FireDbException;

class indexing {

    const INDEX_SIZE_LIMIT = 2500;

    private $_filesystem;

    public function __construct(filesystem $filesystem) {
        $this->_filesystem = $filesystem;

        $metaData = $this->_filesystem->getCollectionMetaData();
        if (!isset($metaData->indexable)) {
            $metaData->indexable = [];
            $this->_filesystem->writeCollectionMetaData($metaData);
        }
        if (!isset($metaData->registry)) {
            $metaData->registry = $this->_generateIndexId();
            $this->_filesystem->writeCollectionMetaData($metaData);
        }
    }

    public function setIndexable($indexable) {
        if (!is_array($indexable)) {
            throw new FireDbException('Indexable values should be configured as an array of indexable properties.');
        }
        $metaData = $this->_filesystem->getCollectionMetaData();
        $metaData->indexable = $indexable;
        $this->_filesystem->writeCollectionMetaData($metaData);
    }

    public function getIndexable() {
        $metaData = $this->_filesystem->getCollectionMetaData();
        return $metaData->indexable;
    }

    public function buildIndexByLookupId($indexLookupId) {
        $indexIdsList = $this->_filesystem->getIndex($indexLookupId);
        $indexIds = (!empty($indexIdsList)) ? $this->_convertIndexToArray($indexIdsList) : [];
        $indexes = array_map([$this->_filesystem, 'getIndex'], $indexIds);
        $documentIdsList = implode('', $indexes);
        $documentIds = $this->_convertIndexToArray($documentIdsList);
        return $documentIds;
    }

    public function getCollectionIndexedRegistry($offset, $length, $reverseOrder) {
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

    public function indexDocument($document) {
        $metaData = $this->_filesystem->getCollectionMetaData();
        $indexable = $metaData->indexable;
        $registry = $metaData->registry;
        foreach (get_object_vars($document) as $property => $value) {
            if (
                $this->_isPropertyIndexable($property, $indexable)
                && $this->_isValueIndexable($value)
            ) {
                $indexLookupId = $this->generateIndexLookupId($property, $value);
                $this->_addDocumentToIndex($indexLookupId, $document->__id);
            }
        }

        if (!$this->_filesystem->documentExists($document->__id)) {
            $this->_addDocumentToIndex($registry, $document->__id);
        }
    }

    public function removeDocumentIndex($document) {
        //@todo add logic to remove a document from indexes it belongs to
    }

    public function generateIndexLookupId($property, $value) {
        $propHash = md5($property);
        $valHash = md5($value);
        $combinedHash = $propHash . $valHash;

        return md5($combinedHash);
    }

    private function _addDocumentToIndex($indexLookupId, $documentId) {
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

    private function _isValueIndexable($value) {
        return (
            is_string($value)
            || is_null($value)
            || is_bool($value)
            || is_integer($value)
        );
    }

    private function _isPropertyIndexable($property, $indexable) {
        $indexBlacklist = ['__id', '__revision', '__timestamp'];
        $indexableList = is_array($indexable) ? $indexable : [];
        return !in_array($property, $indexBlacklist) && in_array($property, $indexableList);
    }

    private function _convertIndexToArray($index) {
        return array_filter(explode(',', $index));
    }

    private function _convertIndexToString($index) {
        return implode(',', $index) . ',';
    }

    private function _generateIndexId() {
        $id = $this->_filesystem->generateUniqueId();
        return md5($id);
    }

}
