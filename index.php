<?php
define("DS", DIRECTORY_SEPARATOR);
require "Exceptions/IdTagException.php";
require "classes/IdTag.php";
require "classes/Worker.php";
require "classes/Rule.php";
if ( isset($argv[1]) and is_dir(realpath($argv[1]))) {
    $target_dir = realpath($argv[1]) . DS;
    printf("%5s","-----");
    printf("%20s","AutoTagger");
    printf("%5s\n","-----");
    printf("%5s","R = ");
    printf("%25s\n","Tag V1 Removed");
    printf("%5s","W = ");
    printf("%25s\n","Tag V2.4 Written");
    echo "------------------------------\n";

    try {
        $idTag  = new IdTag();
        $idTag->setV1TagHandling("remove");

        $worker = new Worker($idTag);
        /**
         * Choose your directory where your rules are located.
         */
        $worker->loadRules(__DIR__ . DS . "rules" . DS);
        $worker->scanFolder($target_dir);
        echo "\n";
    } catch (Exception $exc) {
        echo $exc->getMessage();
    }

} else {
    echo "\nSorry, folder was not found\n";
}
