<?php

interface MongoWriteConfirmation {
    function insert(MongoCollection $collection, $document, array $options = array());
    function retry();
}

    function logWarning($message, $document) {
        echo "=========================\n";
        echo "WARNING: ", $message, "\n";
        var_dump($document);
        echo "=========================\n";
    }
    function logException($message, $document, $exception) {
        echo "=========================\n";
        echo $message, "\n";
        echo "EXCEPTION: ", $exception->getMessage(), "\n";
        var_dump($document);
        echo "=========================\n";
    }
