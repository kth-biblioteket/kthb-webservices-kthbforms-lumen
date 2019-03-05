<?php
namespace App\Classes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class Alma
{
    /**
     * 
     * Funktion som anropar Almas API
     */
    public static function callAlmaApi($endpoint, $requesttype, $object, $lang='en', $override='false')
    {
        try {
            $api_key = env("ALMA_API_KEY", "missing");
            $ch = curl_init();
            $url = env("ALMA_API_URL", "missing") . $endpoint;
            if($lang='swedish' ) {
                $lang='sv';
            } else {
                $lang='en';
            }
            $queryParams = '?' . urlencode('apikey') . '=' . urlencode($api_key) . '&format=json'. '&lang=sv';
            if($override) {
                $queryParams .= '&override_blocks=' . urlencode($override);
            }
            curl_setopt($ch, CURLOPT_URL, $url . $queryParams);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_HEADER, FALSE);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $requesttype);
            if($requesttype == 'POST') {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $object);
	            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($object)));
            }
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            $response = curl_exec($ch);
            curl_close($ch);
            $jsonalmacheckresponse = json_decode(self::checkifAlmaerror($response));
            if ($jsonalmacheckresponse->status == "Error") {
                return json_encode($jsonalmacheckresponse);
            } else {
                return $response;
            }
        } catch(\Exception $e) {
            $responseobject = array(
                "status"  => "Error",
                "message" => $e->getMessage()
            );
            return response()->json($responseobject, 400);
        }
    }

    /**
     * 
     * Funktion som skapar användare i Alma
     * 
     * Genererar ett JSON-object som skickas till Almas API
     */
    public static function createUser($requestInput){
        $requestInputkey='form';
        $usergroup = '30';
        if (!empty($requestInput[$requestInputkey]['otherinfo'])) {
            if ($requestInput[$requestInputkey]['otherinfo'] == 'scania') {
                $usergroup = '43';
            }
        }
        $jsonuser = '
			{
                "password": "' . (!empty($requestInput[$requestInputkey]['password']) ? $requestInput[$requestInputkey]['password'] : "") .'",
                "status": {
                "value": "ACTIVE"
                },
                "record_type": {
                "value": "PUBLIC"
                },
                "primary_id": "' . (!empty($requestInput[$requestInputkey]['email']) ? $requestInput[$requestInputkey]['email'] : "") .'",
                "first_name": "' . (!empty($requestInput[$requestInputkey]['firstname']) ? $requestInput[$requestInputkey]['firstname'] : "") .'",
                "middle_name": "",
                "last_name": "' . (!empty($requestInput[$requestInputkey]['lastname']) ? $requestInput[$requestInputkey]['lastname'] : "") .'",
                "pin_number": "' . (!empty($requestInput[$requestInputkey]['pin']) ? $requestInput[$requestInputkey]['pin'] : "") .'",
                "job_category": {
                "value": ""
                },
                "job_description": "",
                "user_group": {
                    "value": "' . $usergroup . '"
                },
                "campus_code": {
                "value": ""
                },
                "web_site_url": "",
                "preferred_language": {
                "value": "sv"
                },
                "expiry_date": "'. date('Y-m-d', strtotime('+2 years')) .'Z",';
                $jsonuser .= '"account_type": {
                "value": "INTERNAL"
                },
                "external_id": "",
                "force_password_change": "",
                "contact_info": {
                "address": [
                    {
                    "preferred": true,
                    "line1": "' . (!empty($requestInput[$requestInputkey]['streetadress']) ? $requestInput[$requestInputkey]['streetadress'] : "") .'",
                    "line2": "",
                    "city": "' . (!empty($requestInput[$requestInputkey]['city']) ? $requestInput[$requestInputkey]['city'] : "") .'",
                    "state_province": "",
                    "postal_code": "' . (!empty($requestInput[$requestInputkey]['zipcode']) ? $requestInput[$requestInputkey]['zipcode'] : "") .'",
                    "address_note": "",
                    "start_date": "'. date('Y-m-d') . 'Z",
                    "address_type": [
                        {
                        "value": "home"
                        }
                    ]
                    }';
                $jsonuser .= '],
                "email": [
                    {
                    "description": null,
                    "preferred": true,
                    "email_address": "' . (!empty($requestInput[$requestInputkey]['email']) ? $requestInput[$requestInputkey]['email'] : "") .'",
                    "email_type": [
                        {
                        "value": "personal"
                        }
                    ]
                    }
                ],
                "phone": [
                    {
                    "preferred": true,
                    "phone_number": "' . (!empty($requestInput[$requestInputkey]['phone1']) ? $requestInput[$requestInputkey]['phone1'] : "") .'",
                    "preferred_sms": null,
                    "phone_type": [
                        {
                        "value": "home"
                        }
                    ]
                    }';
                if (!empty($requestInput[$requestInputkey]['phone2'])) {
                    if ($requestInput[$requestInputkey]['phone2'] != '') {
                        $jsonuser .= ',
                        {
                        "preferred": true,
                        "phone_number": "' . (!empty($requestInput[$requestInputkey]['phone2']) ? $requestInput[$requestInputkey]['phone2'] : "") .'",
                        "preferred_sms": null,
                        "phone_type": [
                            {
                            "value": "home"
                            }
                        ]
                        }';
                    }
                }
                $jsonuser .= ']
                },';
                $jsonuser .= ' 
                "user_identifier": [
                {
                    "value": "' . (!empty($requestInput[$requestInputkey]['personalnumber']) ? $requestInput[$requestInputkey]['personalnumber'] : "") .'",
                    "note": "Personal Number",
                    "status": "ACTIVE",
                    "id_type": {
                    "value": "PERSONAL_NUMBER"
                    },
                    "segment_type":"Internal"
                }
                ],';
                $jsonuser .= '
                "user_role": [
                {
                    "status": {
                    "value": "INACTIVE",
                    "desc": "Inactive"
                    },
                    "scope": {
                    "value": "46KTH_INST",
                    "desc": "Royal Institute of Technology"
                    },
                    "role_type": {
                    "value": "200",
                    "desc": "Patron"
                    }
                }
                ]';
            if (!empty($requestInput[$requestInputkey]['otherinfo'])) {
                if ($requestInput[$requestInputkey]['otherinfo'] == '16_18') {
                    $jsonuser .= ',
                    "user_block": [
                        {
                            "block_type": {
                            "value": "USER"
                            },
                            "block_description": {
                            "value": "UNDER18"
                            },
                            "block_status": "ACTIVE"
                        }
                        ]';
                }
            }
        $jsonuser .= '}';

        $almaresponse = self::callAlmaApi('users', 'POST', $jsonuser);
        return $almaresponse;
    }

    /**
     * 
     * Funktion som aktiverar patronrollen på en användare
     * 
     * Förutsättningen är att det är en KTH-användare
     */
    public static function activatePatronRole($kthid) {
        //Hämta användare
        $almauser = self::callAlmaApi('users', 'GET', '');
        $almauserobject = json_decode($almauser);
        if ($almauserobject->status == 'Error') {
            return $almauser;
        }

        $user_primary_id = $almauserobject['primary_id'];

        foreach ($almauserobject['contact_info']['email'] as $value) {
            if($value['preferred'] == "1") {
                $emailaddress = $value['email_address'];
            }
        }

        $almauserobject['user_role'][0]['status']['value'] = "ACTIVE";
        $almauserobject['user_role'][0]['status']['desc'] = "Active";
        $almauserobject['user_role'][0]['scope']['value'] = "46KTH_INST";
        $almauserobject['user_role'][0]['scope']['desc'] = "KTH Library";
        $almauserobject['user_role'][0]['role_type']['value'] = "200";
        $almauserobject['user_role'][0]['role_type']['desc'] = "Patron";
        
        //Sätt pinkod om det angetts
        $pinmessage = "";
        if(!empty($pin)) {
            if($pin != "") {
                $almauserobject['pin_number'] = $pin;
            }
        }
        
        $usergroup = $almauserobject['user_group']['value'];
        $fullname = $almauserobject['full_name'];
        
        $note_text = "Låntagarrollen aktiverades, via webben, " . date('Y-m-d H:i:s');
        $numberofcurrentusernotes  = count($userarray['user_note']);
        $almauserobject['user_note'][$numberofcurrentusernotes]['note_text'] = $note_text;
        $almauserobject['user_note'][$numberofcurrentusernotes]['note_type']['value'] = "POPUP";
        $almauserobject['user_note'][$numberofcurrentusernotes]['note_type']['desc'] = "General";
        $almauserobject['user_note'][$numberofcurrentusernotes]['segment_type'] = "Internal";
        
        //Samtycke
        if (!empty($accept)) { 
            if($accept) {
                $almauserobject = json_encode($almauserobject);
                $almaresponse = self::callAlmaApi('users', 'POST', $almauserobject);
                return $almaresponse;
            }
        }


    }

    /**
     * 
     * Function som skapar en request i Alma
     * 
     * Genererar ett JSON-object som skickas till Almas API
     * 
     * Titel på material enligt vissa kriterier
     * 
     * Bibnote enligt format: 
     * Volume(issue) year pp 23-47
     * 
     * Note enligt format
     * Message
     * Kategori: student etc
     * Skola: kthaffilation
     * 
     * Override = true gör att en user request skapas även om alma rapporterar att materialet redan finns
     * 
     * TODO byt till request i st f requestInput
     */
    public static function createUserResourceSharingRequests($formconfig, Request $request, $requestInput, $citation_type, $format, $user_id, $override) 
    {
        $title = "";
        $journaltitle = "";
        $requestInputkey='form';

        if (!empty($request->input('form.genre'))) {
            if($request->input('form.genre') == 'article' &&  !empty($request->input('form.atitle'))) {
                $title = $request->input('form.atitle');
            }

            if(($request->input('form.genre') == 'book' || $request->input('form.genre') == 'bookitem') &&  !empty($request->input('form.btitle'))) {
                $title = $request->input('form.btitle');
            }

            if(($request->input('form.genre') == 'journal' 
            || $request->input('form.genre') == 'article') 
            &&  !empty($request->input('form.jtitle'))) {
                $journaltitle = $request->input('form.jtitle');
            }

            if(($request->input('form.genre') == 'journal' 
            || $request->input('form.genre') == 'article') 
            &&  !empty($request->input('form.stitle'))) {
                $abbrjournaltitle = $request->input('form.stitle');
            }
        }

        //hantera författarnamn
        $author = "";
        if($request->input('form.genre') == 'book' 
        || $request->input('form.genre') == 'article' 
        || $request->input('form.genre') == 'bookitem' ) {
            if (empty($request->input('form.au'))) {
                $author = $request->input('form.aulast') . ', ' . $request->input('form.aufirst');
            } else {
                $author = $request->input('form.au');
            }
        }

        //Hantera publisher (kan vara både pub och publisher som openurl)

        $bib_note = (!empty($request->input('form.volume')) ? $request->input('form.volume') : "");
        (!empty($request->input('form.issue')) ? $bib_note .= "(" . $request->input('form.issue') . ")" : "");        
        (!empty($request->input('form.year')) ? $bib_note .= " " . $request->input('form.year') : "");
        (!empty($request->input('form.pages')) ? $bib_note .= " pp " . $request->input('form.pages') : "");

        //Hantera kostnader
        foreach ($requestInput['form'] as $score) {
            if($score == 'acceptcost') {
                $willing_to_pay = true;
            }
        }

        //Hantera kategori
        $iam='';
        foreach($formconfig->formfields->iam->options as $option){
            if($option->value == $request->input('form.iam')) {
                $iam =  $option->label->{$request->input('language')};
            }
        }

        //Hantera information som ska läggas i Note
        $note = (!empty($request->input('form.message')) ? $request->input('form.message') : "");
        $note .= (!empty($iam) ? PHP_EOL . 'Kategori: ' . $iam : "");
        $note .= (!empty($request->input('form.kthaffiliation')) ? PHP_EOL . 'Skola: ' . $request->input('form.kthaffiliation') : "");
        $note .= (!empty($request->input('form.coursecode')) ? PHP_EOL . 'Kurskod KTH: ' . $request->input('form.coursecode') : "");
        
        $rsrobject = '
        {
            "format": {
                "value": "' . $format .'",
                "desc": "' . $format .'"
            },
            "title": "' . self::escapeJsonString($title) .'",
            "journal_title": "' . self::escapeJsonString($journaltitle) .'",
            "issn": "' . (!empty($request->input('form.issn')) ? $request->input('form.issn') : "") .'",
            "isbn": "' . (!empty($request->input('form.isbn')) ? $request->input('form.isbn') : "") .'",
            "author": "' . (!empty($author) ? $author : "") .'",
            "year": "' . (!empty($request->input('form.year')) ? $request->input('form.year') : "") .'",
            "oclc_number": null,
            "publisher": "' . (!empty($request->input('form.publisher')) ? $request->input('form.publisher') : "") .'",
            "place_of_publication": "' . (!empty($request->input('form.place')) ? $request->input('form.place') : "") .'",
            "edition": "' . (!empty($request->input('form.edition')) ? $request->input('form.edition') : "") .'",
            "volume": "' . (!empty($request->input('form.volume')) ? $request->input('form.volume') : "") .'",
            "issue": "' . (!empty($request->input('form.issue')) ? $request->input('form.issue') : "") .'",
            "chapter_title": "' . (!empty($request->input('form.ctitle')) ? $request->input('form.ctitle') : "") .'",
            "pages": "' . (!empty($request->input('form.pages')) ? $request->input('form.pages') : "") .'",
            "part": null,
            "source": "' . (!empty($request->input('form.source')) ? $request->input('form.source') : "") .'",
            "doi": "' . (!empty($request->input('form.doi')) ? $request->input('form.doi') : "") .'",
            "pmid": null,
            "call_number": null,
            "note": "' . self::escapeJsonString($note) .'",
            "bib_note": "' . $bib_note . '",
            "request_id": null,
            "willing_to_pay": ' . (!empty($willing_to_pay) ? "true" : "false") . ',
            "allow_other_formats": true,
            "preferred_send_method": {
                "value": "MAIL",
                "desc": "Mail"
            },
            "pickup_location": {
                "value": "' . (!empty($request->input('form.pickup')) ? $request->input('form.pickup') : "") .'",
                "desc": "' . (!empty($request->input('form.pickup')) ? $request->input('form.pickup') : "") .'"
            },
            "last_interest_date": null,
            "use_alternative_address": false,			
            "citation_type": {
                "value": "' . $citation_type .'",
                "desc": "' . $citation_type .'"
            },
            "mms_id": null,
            "agree_to_copyright_terms": true
        }';

        $almaresponse = self::callAlmaApi('users/' . $user_id . '/resource_sharing_requests', 'POST', $rsrobject, '', $override);
        return $almaresponse;
    }

    public static function escapeJsonString($value) {
        $escapers = array("\\", "/", "\"", "\n", "\r", "\t", "\x08", "\x0c");
        $replacements = array("\\\\", "\\/", "\\\"", "\\n", "\\r", "\\t", "\\f", "\\b");
        $result = str_replace($escapers, $replacements, $value);
        return $result;
    }

    /**
     * 
     * Hantera eventuella fel från Alma
     * 
     * Fel från alma returneras ibland i XML-format?
     * Kontrollera om det är XML/JSON som returnerat
     * 
     */
    private static function checkifAlmaerror($almaobject) {
        // s
        if(strpos($almaobject,'HTTP Status 401') !== false) {
            $status = "Error";
            $message = "Fel: HTTP Status 401" ;
            $responseobject = array(
                "status"  => $status,
                "message" => $message
            );
            $json_data = json_encode($responseobject);
            return $json_data ;
        }
        $isxml = false;
		$almaobject = str_replace('&', '&amp;', $almaobject);
		try {  
            $xml = simplexml_load_string($almaobject);  
            $isxml = true;
        }
		catch(\Exception $e) { 
            $isxml = false;
        } 

        if ($isxml === false) {
            $error = json_decode($almaobject, true);
            if(!empty($error['errorList'])) {
                $almaerror = "";
                $status = "Error";
                foreach($error['errorList']['error'] as $err) {
                    $almaerror .= $err['errorMessage'] . " ";
                }
                //Intern Alma-timeout (routing) genererar tydligen felmeddelande som innehåller apikey! 
                //Den får inte returneras här!! 
                if(strpos($almaerror,'apikey=') !== false) {
                    $almaerror = substr($almaerror, 0, strpos($almaerror, 'apikey='));
                }
                $message = $almaerror;
            } else {
                $status = "Success";
                $message = "OK";
            }
        } else {
            foreach( $xml as $nodes ) {
                if ($nodes->getName() == 'errorsExist') { 
                    $error = 1;
                    break;
                }
                else {
                    $error = 0;
                }
            }
            if ($error == 1) {
                //Intern Alma-timeout (routing) genererar tydligen felmeddelande som innehåller apikey! 
                //Den får inte returneras här!! 
                if(strpos($xml->errorList[0]->error->errorMessage,'apikey=') !== false) {
                    $xml->errorList[0]->error->errorMessage = substr($xml->errorList[0]->error->errorMessage, 0, strpos($xml->errorList[0]->error->errorMessage, 'apikey='));
                }
                $status = "Error";
                $message = "Fel: "  . str_replace(array("\r", "\n"), "", $xml->errorList[0]->error->errorMessage);
            }
            else {
                $status = "Success";
                $message = "OK";
            }
        }
        $responseobject = array(
            "status"  => $status,
            "message" => $message
        );
        $json_data = json_encode($responseobject);
        return $json_data ;
    }
    
}
?>
