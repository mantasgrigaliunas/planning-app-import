<?php



// Variables used globally - will be set from URL (see above code)
//$planningPortalLPA = 'M3645';
//$planningPortalLPAPassword = 'ta8ndri3dge';
$path = 'app/Application.xml';
$salesforceUsername = "mantas.grigaliunas@arcus.built.mantas";
$salesforceToken = "9rktHA2u0NWJX6Jh4PTh6lbX";
$salesforcePassword = "#arcus123";

ini_set('max_execution_time', 300);  //300 seconds = 5 minutes
//  Requires
//require_once 'inc/PlanningPortalConnector.php';
require_once 'inc/SalesForceConnector.php';
require_once 'inc/LogEntriesAPI/logentries.php';
require_once 'inc/functions.php';

// MAIN ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//  If we have some stuff in $_GET then let begin..

    //$PP = new PlanningPortalConnection($planningPortalLPA, $planningPortalLPAPassword, $log);
    $SF = new SalesforceConnection($salesforceUsername, $salesforcePassword, $salesforceToken, $log);

    foreach(glob("app/*xml") as $filename) {
        $xml_file = file_get_contents($filename, FILE_TEXT);
        print_r($filename);
    }


    print_r('Connected');



?>
