<?php

interface MongoWriteConfirmation {
    function insert(MongoCollection $collection, $document, array $options = array());
    function save(MongoCollection $collection, $document, array $options = array());
    function remove(MongoCollection $collection, $document, array $options = array());
    function retry(MongoWriteConfirmationPolicy $oneTimePolicy = null);
    function rollback(MongoWriteConfirmationPolicy $oneTimePolicy = null);
}

