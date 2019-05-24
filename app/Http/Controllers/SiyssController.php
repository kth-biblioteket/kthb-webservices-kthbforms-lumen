<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;
use App\Contact;
use Illuminate\Support\Facades\View;
use DB; 
use App\Classes\Mail;
use App\Classes\Formsconfig;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

//TODO Validera inkommande data för create/update/delete
class SiyssController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    

    public function __construct()
    {
        //definiera vilka anrop som behöver nyckel/autentisering
        //createLogEntry
        $this->middleware('auth', ['only' => [
            '', 'getSiyss',  'createSiyss', 'updateSiyss', 'deleteSiyss'
        ]]);
        //Skicka alla anrop till middleware som sätter locale utifrån parameter/header
        $this->middleware('localization');
    }

    /**
     * 
     * Funktion som skickar Siyss-mail
     * till RT/EDGE funktionsmail?
     * 
     * Formulärkonfig från JSON
     *      Mailadresser och texter
     * 
     * Byt ut variabler (@@XXXX) i mailtexten som hämtats från formulärkonfig
     * mot värden från request
     * 
     * requestobject inte json då det innehåller bifogade/uppladdade filer som ska inkluderas i mailet till EDGE
     */
    public function sendSiyssMail(Request $request)
    {   
        $formconfig = Formsconfig::get_form('siyss');

        $language = "english";
        if($request->input('language')) {
            $language = $request->input('language');
        }

        /* TODO Validering av fält? */
        
        $emailtoaddressedge = $formconfig->emailtoaddressedge->emailaddress;
        $emailfromaddressuser = $request->input('email');
        $emailfromnameuser = $request->input('name');                                   

        try {
            $bodytext = "";
            $emailtosubjectedge = $formconfig->emailtosubjectedge->{$language};
            foreach ($formconfig->emailtobodyedge->{$language} as $row) {
                $bodytext .= $row;
            }
        } 
        catch(\Exception $e) {
            $responseobject = array(
                "status"  => "Error",
                "message" => $e->getMessage()
            );
            return response()->json($responseobject, 400);
        }
        
        foreach ($formconfig->formfields as $field => $val) {
            //hantera true/false-fält (checkbox)
            if($val->type == 'checkbox' ) {
                // Log::Debug($val->type);
                if($request->input($field)=='true') {
                    $bodytext = str_replace('@@'. $field, 'Ja', $bodytext);
                } else {
                    $bodytext = str_replace('@@'. $field, 'Nej', $bodytext);
                }
            } else {
                $bodytext = str_replace('@@'. $field, $request->input($field), $bodytext);
            }
        }

        $mailresponse = Mail::siyssmail($emailtoaddressedge, $emailfromaddressuser, $emailfromnameuser, $emailtosubjectedge , $bodytext,'','', $request);
        
        if ($mailresponse != 'Success'){
            $responseobject = array(
                "status"  => "Error",
                "message" => $mailresponse
            );
            return response()->json($responseobject, 400);
        }

        $responseobject = array(
            "status"  => "Success",
            "message" => "OK"
        );
        return response()->json($responseobject, 200,[],JSON_UNESCAPED_UNICODE); 
    }
}
?>