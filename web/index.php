<?php

// Variables used globally - will be set from URL (see above code)
$planningPortalLPA = 'M3645';
$planningPortalLPAPassword = 'ta8ndri3dge';
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

    print_r("DOWNLOAD STARTED FOR " . $planningPortalLPA);

//  For each application on Planning Portal
    try {
        foreach ($PP->GetListOfProposals() as $PlanningPortalApplication) {

            $log->Info("Planning Portal Application Reference: " . $PlanningPortalApplication->RefNum);

            //  Get the whole Proposal
            $fullApplication = $PP->GetFullProposal($PlanningPortalApplication->RefNum);


            //  Get the main XML body with all proposal info (Whole Proposal Less Attachments)
            $applicationInformation = $PP->GetPlanningApplicationInformation($fullApplication);


            // Upload Attachement information to SF (returns SF Object ID)
            $SFPlanningApplication = $SF->CreatePlanningApplication($applicationInformation);

            if ($SFPlanningApplication != "ERROR") {
                //  Get an array of attachments

                $attachmentArray = $PP->GetAttachments($fullApplication);
                foreach ($attachmentArray as $attachment) {
                    // Split the attachment body and content
                    list($rawheaders, $body) = preg_split("/\R\R/", $attachment, 2);
                    // Get the Attahcement Headers
                    $headers = $PP->GetAttachmentHeaders($rawheaders);
                    // Upload the attachment!
                    $attachtmentId = $SF->CreateAttachment($body, $headers['File Name'], $SFPlanningApplication);
                }
                $planningFee = $SF->CreateFee($SFPlanningApplication, $applicationInformation->Body->Proposal->ApplicationHeader->Payment->AmountDue);
            }
        }
    } catch (Exception $e) {
      print_r($e);
        $log->Error("ERROR! Caught Exception: " . $e->getMessage());
    }
    $log->Debug("DOWNLOAD COMPLETED FOR " . $planningPortalLPA);
    
echo "Process Completed";

?>
