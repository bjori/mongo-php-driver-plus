<?php

interface MongoWriteConfirmationPolicy {
    function getWriteOptions();

    function onWTimeout(MongoWriteConfirmation $writer, $errorDocument);
    function onFailure(MongoWriteConfirmation $writer);
    function onSuccess($retval);
}

