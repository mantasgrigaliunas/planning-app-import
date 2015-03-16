<?php

class PlanningPortalConnection {

    private $PPURL;
    private $PPLPA;
    private $PPPAS;
    private $log;

    public function __construct($PPLPA, $PPPAS,$log) {
        $this->PPURL = "https://www.planningportal.gov.uk/oneapp/servlet/messagerouter/";
        $this->PPLPA = $PPLPA;
        $this->PPPAS = $PPPAS;
        $this->log = $log;
    }

    private function InteractWithPlanningPortal($xml) {
        // send XML to the planning portal API and return the responce.
        $headers = array(
            "Content-type: text/xml;charset=UTF-8",
            "Content-length: " . strlen($xml),
            "Accept-Encoding: gzip,deflate",
            "SOAPAction" . $this->PPURL,
            "User-Agent: Jakarta Commons-HttpClient/3.1"
        );

        $ch = curl_init($this->PPURL);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "$xml");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_ENCODING, 'identity');


        $serversResponce = curl_exec($ch);

        if (curl_errno($ch)) {
            print curl_error($ch);
        } else {
            curl_close($ch);
        }

        return $serversResponce;
    }

    private function GenerateMD5($input) {
        // Protection against falsified (invalid) and repeat messages will be provided through the use of an MD-5 hash.
        // A unique request number must be supplied with each GetProposal request. The use of an MD-5 hash and an auto incrementing message number will prevent repeat messages i.e. relay attacks, being accepted by the Portal.
        //The format of the input string for the digest is:
        //Proposal Reference + LPA Code + Unique Request Number + Private key
        //e.g. The signature should be:
        //proposalRef + lpaCode + requestId + privateKey
        return base64_encode(md5(utf8_encode($input), true));
    }

    private function GenerateRequestID() {
        return date('dmis');
    }

    public function GetListOfProposals() {
        // create an XML request for a list of planning applications.
        $xmlRequest = '<?xml version="1.0"?>' . '<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">' . '<SOAP-ENV:Body>' . '<getProposalList version="2.0" xmlns="http://www.planningportal.gov.uk/schema/ProposalWebservice" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.planningportal.gov.uk/schema/ProposalWebservice ProposalWebservices-2.0.xsd">' . '<lpaCode>' . $this->PPLPA . '</lpaCode>' . '</getProposalList>' . '</SOAP-ENV:Body>' . '</SOAP-ENV:Envelope>';

        $responceObject = $this->XMLResponseToObject($this->InteractWithPlanningPortal($xmlRequest));
        // $numberOfApplications = sizeof($responceObject->Body->ProposalList->ProposalHeader);

        return $responceObject->Body->ProposalList->ProposalHeader;
    }

    private function XMLResponseToObject($input) {

        $SOAPXML = str_ireplace([
            'SOAP-ENV:',
            'SOAP:'
                ], '', $input);

        // alex's code
        $find = array('common:', 'pdt:', 'apd:', 'bs7666:');
        $replace = '';

        $SOAPXML= str_ireplace($find, $replace, $SOAPXML);

        $find = array(chr(39).chr(34), chr(34).chr(39));
        $replace = chr(34);
        $SOAPXML= str_ireplace($find, $replace, $SOAPXML);


       return simplexml_load_string($SOAPXML);

    }

    private function RemoveLinesFromString($str, $lines = 4) {
        return implode("\n", array_slice(explode("\n", $str), $lines));
    }

    public function GetFullProposal($proposalReference) {
        //  Generate a RequestID for this event (used twice in XML Request)
        $currentRequest = $this->GenerateRequestID();
        //  Generate the request to send..
        $xmlRequest = '<?xml version="1.0"?>' . '<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">' . '<SOAP-ENV:Body>' . '<getProposal version="0.9" xmlns="http://www.planningportal.gov.uk/schema/ProposalWebservice" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.planningportal.gov.uk/schema/ProposalWebservice ProposalWebservices-0.9.xsd">' . '<proposalRef>' . $proposalReference . '</proposalRef>' . '<lpaCode>' . $this->PPLPA . '</lpaCode>' . '<requestId>' . $currentRequest . '</requestId>' . '<signature>' . $this->GenerateMD5($proposalReference . $this->PPLPA . $currentRequest . $this->PPPAS) . '</signature>' . '</getProposal>' . '</SOAP-ENV:Body>' . '</SOAP-ENV:Envelope>';
        //  Send Request to Server
        $serverResponse = $this->InteractWithPlanningPortal($xmlRequest);
        //  Take the first line of the server reponse - this line seperates the XML and each attachement (e.g PART---52975623792)
        $breakString = strtok($serverResponse, "\n");
        //  At each point of the $breakString occurance, seperate it and add to array of strings
        $proposalParts = explode($breakString, $serverResponse);
        //  The first element of the explode result array is blank, so discard it to save space
        unset($proposalParts[0]);

        return $proposalParts;
    }

    public function GetPlanningApplicationInformation($rawFullProposal) {
        // we have a full application downloaded, now lets seperate the application information and determin the amount of attachements on the document.
        $applicationXML = $this->RemoveLinesFromString($rawFullProposal[1], 6);

        return $this->XMLResponseToObject($applicationXML);
    }

    public function GetAttachments($fullApplication) {
        // we have already added all the information for the application to SF. Delete Application information leaving just the attachements.
        // echo 'Was = ' . sizeof($fullApplication);
        //  done with the planning information, so ditch it (save 3000 lines of a string!).
        unset($fullApplication[1]);
        //  echo 'Now = ' . sizeof($fullApplication);
        return $fullApplication;
    }

    public function GetAttachmentHeaders($raw_headers) {
        $headers = array();
        $key = '';

        foreach (explode("\n", $raw_headers) as $i => $h) {
            $h = explode(':', $h, 2);

            if (isset($h[1])) {
                if (!isset($headers[$h[0]])) {
                    $headers[$h[0]] = trim($h[1]);
                } elseif (is_array($headers[$h[0]])) {
                    $headers[$h[0]] = array_merge($headers[$h[0]], array(trim($h[1])));
                } else {
                    $headers[$h[0]] = array_merge(array($headers[$h[0]]), array(trim($h[1])));
                }

                $key = $h[0];
            } else {
                if (substr($h[0], 0, 1) == "\t") {
                    $headers[$key] .= "\r\n\t" . trim($h[0]);
                } elseif (!$key) {
                    $headers[0] = trim($h[0]);
                }
            }
        }

        $headers['File Name'] = str_replace('attachment; filename=', '', $headers['Content-Disposition']);
        $headers['File Name'] = preg_replace("([^\w\s\d\-_~,;:\[\]\(\).])", '', $headers['File Name']);
        // Remove any runs of periods (thanks falstro!)
        $headers['File Name'] = preg_replace("([\.]{2,})", '', $headers['File Name']);

        return $headers;
    }

    public function AcknowledgePlanningApplication($PPReference) {
//  Once an application has successfully been imported into Salesforce, remove it from the Planning Portal..
        $currentRequest = $this->GenerateRequestID();
        $values = $PPReference . $this->PPLPA . $currentRequest . $this->PPPAS;
        $xmlRequest = '<?xml version = "1.0"?>' . '<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">' .
                '<SOAP-ENV:Body>'
                .'<setProposalReceived version="0.9" xmlns="http://www.planningportal.gov.uk/schema/ProposalWebservice" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.planningportal.gov.uk/schema/ProposalWebservice ProposalWebservices-0.9.xsd">'
                .'<proposalRef>' . $PPReference . '</proposalRef>'
                . '<lpaCode>' . $this->PPLPA . '</lpaCode>'
                . '<requestId>' . $currentRequest . '</requestId>'
                . '<signature>' . $this->GenerateMD5($values) . '</signature>'
                . ' </setProposalReceived>'
                . '</SOAP-ENV:Body>'
                .'</SOAP-ENV:Envelope>';



        $serverResponse = $this->InteractWithPlanningPortal($xmlRequest);



          $this->log->Info("Sent Acknowledgement/Delete for  Application: " . $PPReference);

    }

}
