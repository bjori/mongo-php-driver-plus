<?php
require __DIR__ . "/lib/MongoWriteConfirmation.php";
require __DIR__ . "/lib/MongoWriteConfirmationPolicy.php";
require __DIR__ . "/lib/MongoWriteConfirmationPolicyCleanup.php";
require __DIR__ . "/lib/MongoWriteConfirmationPolicyIgnore.php";
require __DIR__ . "/lib/MongoWriter.php";

$opts = array(
    "readPreference"     => MongoClient::RP_PRIMARY_PREFERRED,
    "readPreferenceTags" => array("dc:ny", "dc:sf", ""),
    "w"                  => "default",
    "wtimeout"           => 500,
);

$mc = new MongoClient("primary,secondary", $opts);
$articles = $mc->selectCollection("blogs", "articles");

do {
    // Get a new ID on each run so we don't update the same document
    $article = array(
        "author"  => "Jane Doe",
        "title"   => "How to deal with failovers",
        "content" => "Dealing with failovers can be tricky, but it is very"
                    ."important to understand what happens and be prepared.",
        "tags"    => array("mongodb", "replicaset", "failover"),
    );

    $mw = new MongoWriter(new MongoWriteConfirmationPolicyCleanup);
    $success = $mw->insert($articles, $article);
    if ($success) {
        echo "Data Written successfully\n";
    } else {
        echo "Write failed, there should be no traces of it\n";
    }
    echo "\n";
    sleep(1);
} while(1);

