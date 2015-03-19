<?php
define("SOAP_CLIENT_BASEDIR", "inc/SalesForceAPI");
require_once(SOAP_CLIENT_BASEDIR . '/SforcePartnerClient.php');

class SalesforceConnection
{

    private $SFConnection;
    private $SFSoapClient;
    private $SFLogin;
    private $log;
    private $applicationTypes;

    public function __construct($SFUSERNAME, $SFPASSWORD, $SFToken, $log)
    {
        // When we create an instance of SalesForceConnector, create a salesforce API connection ($this->SFConnection) so it can be used throughout this class.
        $this->SFConnection = new SforcePartnerClient();
        $this->SFSoapClient = $this->SFConnection->createConnection(SOAP_CLIENT_BASEDIR . '/partner.wsdl.xml');
        $this->SFLogin = $this->SFConnection->login($SFUSERNAME, $SFPASSWORD . $SFToken);
        $this->log = $log;

        $this->applicationTypes = $this->getApplicationTypes();
    }

    public function getApplicationTypes()
    {
        $arr = ['Advertisement',
            'Approval of conditions',
            'Consent under Tree Preservation Orders',
            'Conservation area',
            'Full planning',
            'Householder planning',
            'Lawful Development Certificate (LDC)',
            'Listed building',
            'Notification of proposed works to trees in conservation areas',
            'Outline planning',
            'Pre-Application',
            'Prior notification',
            'Removal/variation of conditions',
            'Reserved matters'];

        foreach ($arr as &$value){
            $applicationTypeIDs[$value] = $this->ReturnApplicationTypeID($value);
        }

        return $applicationTypeIDs;
    }


    private function debugToFile($contents)
    {
        $file = 'debugFile.txt';
        file_put_contents($file, print_r($contents,true));
    }


    private function ReturnApplicationTypeID($name)
    {
        $query = 'SELECT Id from RecordType WHERE Name Like \'' . $name . '\' Limit 1';
        $response = $this->SFConnection->query($query);
        $queryResult = new QueryResult($response);

        for ($queryResult->rewind(); $queryResult->pointer < $queryResult->size; $queryResult->next()) {
            $recordTypeId = $queryResult->current()->Id;
        }

        return $recordTypeId;
    }

    //  public function uploadAttachment($attachmentBody, $attachmentBodyLength, $contentType, $attachmentName) {
    public function CreateAttachment($attachmentBody, $attachmentName, $planningApplicationID)
    {
        $createFields = ['Body' => base64_encode($attachmentBody),
            //    'ContentType' => $contentType,
            'Name' => htmlspecialchars($attachmentName),
            'ParentID' => htmlspecialchars($planningApplicationID),
            'IsPrivate' => 'false'
        ];
        $sObject = new stdclass();
        $sObject->fields = $createFields;
        $sObject->type = 'Attachment';

        $upsertResponse = $this->SFConnection->create(array($sObject));

        if ($upsertResponse[0]->success == 1) {
            echo "Attachment: " . $attachmentName . " uploaded successfully! <br>";
            return $upsertResponse[0]->id;
        } else {

            echo "Attachment Upload Error - Failed to upload " . $attachmentName . " for Planning Application " . $planningApplicationID . "<br>";

            return 'ERROR';
        }
    }

    private function CalculateApplicationType($applicationScenario)
    {

        switch ($applicationScenario) {

            case "1":  //Householder Application for planning permission for works or extension to a dwelling
                $SFApplicationType = $this->applicationTypes['Householder planning'];
                break;
            case "2":  //Householder Application for planning permission for works or extension to a dwelling and demolition of an unlisted building in a conservation area
                $SFApplicationType = $this->applicationTypes['Householder planning'];
                break;
            case "3":  //Householder application for planning permission for works or extension to a dwelling and listed building consent for alterations, extension or demolition of a listed building
                $SFApplicationType = $this->applicationTypes['Householder planning'];
                break;
            case "4":  //Application for planning permission
                $SFApplicationType = $this->applicationTypes['Full planning'];
                break;
            case "5":  //Application for outline planning permission with some matters reserved
                $SFApplicationType = $this->applicationTypes['Outline planning'];
                break;
            case "6":  //Application for outline planning permission with all matters reserved
                $SFApplicationType = $this->applicationTypes['Outline planning'];
                break;
            case "7":  //Application for planning permission for demolition of an unlisted building in a conservation area
                $SFApplicationType = $this->applicationTypes['Full planning'];
                break;
            case "8":  //Application for planning permission and listed building consent for alterations, extension or demolition of a listed building
                $SFApplicationType = $this->applicationTypes['Full planning'];
                break;
            case "9":  //Application for planning permission and consent to display an advertisement(s)
                $SFApplicationType = $this->applicationTypes['Full planning'];
                break;
            case "10":  //Application for planning permission for demolition in a conservation area
                $SFApplicationType = $this->applicationTypes['Conservation area'];
                break;
            case "11":  //Application for listed building consent for alterations, extension or demolition of a listed building
                $SFApplicationType = $this->applicationTypes['Listed building'];
                break;
            case "12":  //Application for consent to display an advertisement(s)
                $SFApplicationType = $this->applicationTypes['Advertisement'];
                break;
            case "14":  //Application for a Lawful Development Certificate for an Existing use or operation or activity including those in breach of a planning condition
                $SFApplicationType = $this->applicationTypes['Lawful Development Certificate (LDC)'];
                break;
            case "15":  //Application for a Lawful Development Certificate for a Proposed use or development
                $SFApplicationType = $this->applicationTypes['Lawful Development Certificate (LDC)'];
                break;
            case "16":  //Application for prior notification of proposed agricultural or forestry development - proposed building
                $SFApplicationType = $this->applicationTypes['Prior notification'];
                break;
            case "17":  //Application for prior notification of proposed agricultural or forestry development - proposed road
                $SFApplicationType = $this->applicationTypes['Advertisement'];
                break;
            case "18":  //Application for prior notification of proposed agricultural or forestry development - excavation/waste material
                $SFApplicationType = $this->applicationTypes['Advertisement'];
                break;
            case "19":  //Application for prior notification of proposed agricultural or forestry development - proposed fish tank (cage)
                $SFApplicationType = $this->applicationTypes['Advertisement'];
                break;
            case "20":  //Application form for prior notification of proposed development in respect of permitted development by telecommunications code systems operators
                $SFApplicationType = $this->applicationTypes['Advertisement'];
                break;
            case "21":  //Application for Hedgerow Removal Notice
                $SFApplicationType = $this->applicationTypes['Advertisement'];
                break;
            case "22":  //Application for prior notification of proposed demolition
                $SFApplicationType = $this->applicationTypes['Advertisement'];
                break;
            case "23":  //Application for approval of reserved matters following outline approval
                $SFApplicationType = $this->applicationTypes['Reserved matters'];
                break;
            case "25":  // and 26   Application for removal or variation of a condition following grant of planning permission
                $SFApplicationType = $this->applicationTypes['Removal/variation of conditions'];
                break;
            case "26":  // and 26   Application for removal or variation of a condition following grant of planning permission
                $SFApplicationType = $this->applicationTypes['Removal/variation of conditions'];
                break;
            case "27":  //Application for approval of details reserved by condition Town and Country Planning Act 1990
                $SFApplicationType = $this->applicationTypes['Approval of conditions'];
                break;
            case "31":  //Application for tree works: Works to Trees Subject to a Tree Preservation Order (TPO) and/or Notification of Proposed Works to Trees in Conservation Areas (CA)
                $SFApplicationType = $this->applicationTypes['Consent under Tree Preservation Orders'];
                break;
        }

        return $SFApplicationType;
    }

    public function CreatePlanningApplication($applicationInformation)
    {
        $applicant = $this->CreateContact($applicationInformation->Applicant);
        echo "Applicant: " . $applicant . "<br>";

        $UPRN = $this->CreateUPRN($applicationInformation->SiteLocation);
        echo "UPRN: " . $UPRN . "<br>";


        //  Some application are bloody fussy, and require different descriptions, set the description for the fussy types, else put instructions for council staff to complete.

        // Adverts
        if (isset($applicationInformation->ApplicationData->Advert->AdvertDescription)) {
            $advertDescriptions = $applicationSalesForceDescription = $applicationInformation->ApplicationData->Advert->AdvertDescription;

            foreach ($advertDescriptions as $advert) {
                $applicationSalesForceDescription = "";
                $applicationSalesForceDescription .= $advert->DescriptionText . ". ";
            }

        } // LDC
        else if (isset($applicationInformation->ApplicationData->CertificateLawfulness->ExistingUseApplication->DescriptionCEU)) {
            $applicationSalesForceDescription = $applicationInformation->ApplicationData->CertificateLawfulness->ExistingUseApplication->DescriptionCEU;
        } //  Tree preservation order :)
        else if (isset($applicationInformation->ApplicationData->Trees->TreeDetails)) {
            $preservationOrder = $applicationInformation->ApplicationData->Trees->TreeDetails;

            foreach ($preservationOrder as $tpo) {
                $applicationSalesForceDescription = "";
                $applicationSalesForceDescription .= $tpo->TreesProposedWorksDescription . ". ";
            }
        }
        //  For Planning Portal Application type 'Approval of details reserved by condition' the path for description is
        elseif (isset($applicationInformation->ApplicationData->Conditions->ConditionsDescription->DescriptionText))
        {
          $applicationSalesForceDescription = $applicationInformation->ApplicationData->Conditions->ConditionsDescription->DescriptionText;
        }
        //  General Application
        else if (isset($applicationInformation->ApplicationData->ProposalDescription->DescriptionText)) {
            $applicationSalesForceDescription = $applicationInformation->ApplicationData->ProposalDescription->DescriptionText;
        } else {
            $applicationSalesForceDescription = "Application description not found, please update from application document";
        }

        $applicationFields = [
            'Applicant__c' => htmlspecialchars($applicant),
            'UPRN__c' => htmlspecialchars($UPRN),
            'Proposal__c' => htmlspecialchars($applicationSalesForceDescription),
            'CreatedDate__c' => htmlspecialchars($applicationInformation->ApplicationHeader->DateSubmitted),
            'Planning_Portal_Reference__c' => htmlspecialchars($applicationInformation->ApplicationHeader->FormattedRefNum),
            'RecordTypeId' => htmlspecialchars($this->CalculateApplicationType($applicationInformation->ApplicationScenario->ScenarioNumber))
        ];

        if (strlen($applicationInformation->Agent->PersonName->PersonFamilyName) > 0) {
            $applicationFields['Agent__c'] = $this->CreateContact($applicationInformation->Agent);
            echo "Agent: " . $agent . "<br>";

        }

        $sObject = new stdclass();
        $sObject->fields = $applicationFields;
        $sObject->type = 'PApplication__c';


        //  Upsert UPRN into BasicLandProperyUnit__c - if sucessfull return SF ID, ELSE return ERROR.
        $upsertResponse = $this->SFConnection->create(array($sObject));


        if ($upsertResponse[0]->success == 1) {
            return $upsertResponse[0]->id;
        } else {
            echo "Error - Could not insert Planning Application: " . $applicationInformation->ApplicationHeader->FormattedRefNum;
            return 'ERROR';
        }
    }

    public function CreateFee($SFApplicationID, $PPFeeAmount)
    {
        $feeFields = ['Description__c' => htmlspecialchars("PlanningPortal.gov Calculated Fee"),
            'Net_Fee__c' => $PPFeeAmount,
            'Planning_Application__c' => htmlspecialchars($SFApplicationID)];

        $sObject = new stdclass();
        $sObject->fields = $feeFields;
        $sObject->type = 'Planning_Fee__c';

        $SFResponce = $this->SFConnection->create(array($sObject));

        //  If we have a contact ID return it, else return error
        if ($SFResponce[0]->success == 1) {

          $this->debugToFile($SFResponce);

            return $SFResponce[0]->id;
        } else {
            return 'ERROR';
        }
    }

    private function CreateContact($Contact)
    {

        $contactFields = ['FirstName' => htmlspecialchars($Contact->PersonName->PersonGivenName),
        //    'LastName' => htmlspecialchars($Contact->PersonName->PersonFamilyName),
            'Email' => htmlspecialchars($Contact->ContactDetails->Email->EmailAddress),
            'Title' => htmlspecialchars($Contact->PersonName->PersonNameTitle),
            'Preferred_Contact_Method__c' => htmlspecialchars($Contact->ContactDetails['PreferredContactMedium']),
            'MailingStreet' => htmlspecialchars($Contact->ExternalAddress->InternationalAddress->IntAddressLine[0]) . ', ' . htmlspecialchars($Contact->ExternalAddress->InternationalAddress->IntAddressLine[1]),
            'MailingCity' => htmlspecialchars($Contact->ExternalAddress->InternationalAddress->IntAddressLine[3]),
            'Mailingstate' => htmlspecialchars($Contact->ExternalAddress->InternationalAddress->IntAddressLine[4]),
            'MailingPostalCode' => htmlspecialchars($Contact->ExternalAddress->InternationalAddress->InternationalPostCode),
            'Phone' => htmlspecialchars($Contact->ContactDetails->Telephone[0]->TelNationalNumber),
            'MobilePhone' => htmlspecialchars($Contact->ContactDetails->Telephone[1]->TelNationalNumber)
        ];


        if (strlen($Contact->PersonName->PersonFamilyName) == 0) {
          $contactFields['LastName'] = "Unknown";
        }
        elseif(strlen($Contact->PersonName->PersonFamilyName) > 0)
        {
          $contactFields['LastName'] = $Contact->PersonName->PersonFamilyName;
        }


        if (strlen($Contact->OrgName) > 0) {
          $contactFields['AccountId'] = htmlspecialchars($this->CreateAccount($Contact->OrgName));
        }

        $sObject = new stdclass();
        $sObject->fields = $contactFields;
        $sObject->type = 'Contact';

        if (strlen($contactFields['Email']) == 0) {
            $SFResponce = $this->SFConnection->create(array($sObject));
        } else {
            $SFResponce = $this->SFConnection->upsert("Email", array($sObject));
        }


        //  If we have a contact ID return it, else return error
        if ($SFResponce[0]->success == 1) {
            return $SFResponce[0]->id;
        }
        else {
            //  FIX: We might have tried an upsert as we had an email, however sometimes there are duplicate accounts in Salesforce with the same email address,
            //  to fix this, we just need to do a basic insert - someone will clean up the duplicates later on.
            $SFResponce = $this->SFConnection->create(array($sObject));

            if ($SFResponce[0]->success == 1) {

                return $SFResponce[0]->id;

            } else {
              $this->debugToFile($sObject);
                return 'ERROR';
            }

            echo $SFResponce;

            return 'ERROR';
        }
    }

    private function CreateAccount($OrgName)
    {

        if (empty($OrgName)) {
            $OrgName = '-';
        }

        $query = 'SELECT Id,Name,BillingStreet,BillingCity,BillingState,Phone,Fax from Account WHERE Name Like \'' . $OrgName . '\' Limit 1';
        $response = $this->SFConnection->query($query);
        $queryResult = new QueryResult($response);

        for ($queryResult->rewind(); $queryResult->pointer < $queryResult->size; $queryResult->next()) {
            $accountId = $queryResult->current()->Id;
        }
        if ($accountId == NULL) {
            $accountFields = [
                'Name' => htmlspecialchars($OrgName),
                'Description' => "Account Created During PlanningPortal.gov Application Import"
            ];
            $sObject = new stdclass();
            $sObject->fields = $accountFields;
            $sObject->type = 'Account';

            $SFResponce = $this->SFConnection->create(array($sObject));

            if ($SFResponce[0]->success == 1) {
                echo "Account ID " . $SFResponce[0]->id . "<br>";
                return $SFResponce[0]->id;
            } else {
                echo "Failed to Create Account for " . $OrgName . "<br>";

            }
        }
        else
        {
            return $accountId;
        }
        return 'ERROR';
    }

    private function CreateUPRN($SiteLocation)
    {

        try{


            $UPRN = $SiteLocation->BS7666Address->UniquePropertyReferenceNumber;


            echo $SiteLocation  . "<br>";

            //check if UPRN exists in Salesforce
            $query = "SELECT Id from BasicLandPropertyUnit__c WHERE UPRN__c = '" . $UPRN ."'";
            $response = $this->SFConnection->query($query);
            $queryResult = new QueryResult($response);

            for ($queryResult->rewind(); $queryResult->pointer < $queryResult->size; $queryResult->next()) {
                $uprnId = $queryResult->current()->Id;
            }

            if ($uprnId == NULL) {

                $createFields = ['PostCode__c' => htmlspecialchars($SiteLocation->BS7666Address->PostCode),
                'Street__c' => htmlspecialchars($SiteLocation->BS7666Address->StreetDescription),
                'UPRN__c' => htmlspecialchars($SiteLocation->BS7666Address->UniquePropertyReferenceNumber),
                'X_COORDINATE__c' => htmlspecialchars($SiteLocation->SiteGridRefence->X),
                'Y_COORDINATE__c' => htmlspecialchars($SiteLocation->SiteGridRefence->Y),
                'Temporal__c' => true
                ];

                $sObject = new stdclass();
                $sObject->fields = $createFields;
                $sObject->type = 'BasicLandPropertyUnit__c';

                $SFResponce = $this->SFConnection->create(array($sObject));

                if ($SFResponce[0]->success == 1) {

                echo "TEMP UPRN ID " . $SFResponce[0]->id . "<br>";
                return $SFResponce[0]->id;

                } else {
                    echo "Failed to Create Temp UPRN for " . $SiteLocation . "<br>";
                }
            } 
            else {
                return $uprnId;
            }


        }catch(Exception $ex){

            echo "UPRN error : " . $ex;
        }
    }

    public function CheckCreation($Planning_Portal_Reference__c)
    {
        // Query Salesforce to see if the Planning Application has been created sucessfully.
        $query = 'SELECT Id from PApplication__c WHERE Planning_Portal_Reference__c Like \'' . $Planning_Portal_Reference__c . '\' Limit 1';
        $response = $this->SFConnection->query($query);
        $queryResult = new QueryResult($response);

        for ($queryResult->rewind(); $queryResult->pointer < $queryResult->size; $queryResult->next()) {
            $applicationId = $queryResult->current()->Id;
        }
        If (strlen($applicationId) > 0) {
            return True;
        } else {
            // Yes! Its on salesforce, now delete it from the planning portal website.
            return False;
        }
    }

}
