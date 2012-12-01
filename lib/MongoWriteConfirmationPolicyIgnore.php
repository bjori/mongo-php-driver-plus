<?php

class MongoWriteConfirmationPolicyIgnore implements MongoWriteConfirmationPolicy {

    function getWriteOptions() {
        /* Temporary overwrite whatever WriteConcern is in use */
        return array("w" => 0);
    }

    function onFailure(MongoWriteConfirmation $writer) {
        return false;
    }
    function onWTimeout(MongoWriteConfirmation $writer, $errorDocument) {
        return false;
    }

    function onSuccess($retval) {
        return $retval;
    }

}

