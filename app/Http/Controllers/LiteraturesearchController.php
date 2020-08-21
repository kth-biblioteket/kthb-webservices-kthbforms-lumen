<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;
use App\Consultation;
use Illuminate\Support\Facades\View;
use DB; 
use App\Classes\Mail;
use App\Classes\Formsconfig;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

//TODO Validera inkommande data för create/update/delete
class LiteraturesearchController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */

    public function __construct()
    {
        $this->middleware('auth', ['only' => [
            ''
        ]]);
        $this->middleware('localization');
    }

    /**
     * 
     * Funktion som skickar ett mail till
     * RT/EDGE/Funktionsadress
     * 
     * Formulärkonfig från JSON
     *      Mailadresser och texter
     * 
     * Byt ut variabler (@@XXXX) i mailtexten som hämtats från formulärkonfig
     * mot värden från request
     */
    public function sendLiteraturesearchMail(Request $request)
    {
        $formconfig = Formsconfig::get_form('literaturesearch');
        /*
        $this->validate($request, [ 
            'form.name' => 'required',
            'form.email' => 'required',
            'form.subject' => 'required',
            'form.suggesteddate1' => 'required',
            'form.suggesteddate2' => 'required',
            'form.suggesteddate3' => 'required'
        ]);
        */

        $form  = json_decode($request->input('item'));
        //Log::Debug(json_decode($request->input('item')));

        $emailtoaddressedge = $formconfig->emailtoaddressedge->emailaddress;
        $emailfromaddressuser = $form->email;
        $emailfromnameuser = $form->name;                                 

        try {
            $bodytext = "";
            $emailtosubjectedge = $formconfig->emailtosubjectedge->{$request->input('language')};
            foreach ($formconfig->emailtobodyedge->{$request->input('language')} as $row) {
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

        //dynamiska fält...
        foreach ($formconfig->formfields as $field => $val) {
            //$bodytext = str_replace('@@'. $field, $request->input('form.' . $field), $bodytext);
            //Hantera inte files här!
            if(strpos($field,'file') !== false) {
                // Do nothing!
            } else {
                if (is_object($form->$field)) {
                    //endast datum är object? Files också
                    $bodytext = str_replace('@@'. $field, $form->$field->formatted, $bodytext);
                } else {
                    $bodytext = str_replace('@@'. $field, $form->$field, $bodytext);
                }
            }
        }

        //Visa inte rader/rubriker/block där information saknas.
        if (strpos($bodytext,'showcritera=\'\'')!= false) {
            $bodytext = str_replace('showcritera=\'\'', 'style="mso-hide:all;display:none;max-height:0px;overflow:hidden;"', $bodytext);
        }
        
        $mailresponse = Mail::sendemailwithattachments($emailtoaddressedge, $emailfromaddressuser, $emailfromnameuser, $emailtosubjectedge , $bodytext,'','', $request);
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