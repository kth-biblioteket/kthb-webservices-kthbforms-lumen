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

//TODO Validera inkommande data för create/update/delete
class ContactController extends Controller
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
            '', 'getContact',  'createContact', 'updateContact', 'deleteContact'
        ]]);
        //Skicka alla anrop till middleware som sätter locale utifrån parameter/header
        $this->middleware('localization');
    }

    /**
     * 
     * Funktion som skickar Kontakta oss-mail
     * till RT/EDGE funktionsmail
     * 
     * Formulärkonfig från JSON
     *      Mailadresser och texter
     * 
     * Byt ut variabler (@@XXXX) i mailtexten som hämtats från formulärkonfig
     * mot värden från request
     * 
     */
    public function sendContactMail(Request $request)
    {
        if (!$request->isJson()) {
            $responseobject = array(
                "status"  => "Error",
                "message" => "Please provide json"
            );
            return response()->json($responseobject, 201);
        }
        
        $formconfig = Formsconfig::get_form('contact');

        $language = "english";
        if($request->input('language')) {
            $language = $request->input('language');
        }
        /*
        $validationarray = array('language' => 'required');
        foreach ($formconfig->formfields as $field => $val) {
            if ($val->validation->required->value && $val->type != 'grouplabel') {
                $validationarray["form.".$field] = 'required';
            }
        }
        $this->validate($request, $validationarray);
        */
        $emailtoaddressedge = $formconfig->emailtoaddressedge->emailaddress;
        $emailfromaddressuser = $request->input('form.email');
        $emailfromnameuser = $request->input('form.name');                                   

        try {
            $bodytext = "";
            $emailtosubjectedge = $request->input('form.subject');
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
            $bodytext = str_replace('@@'. $field, $request->input('form.' . $field), $bodytext);
        }

        $mailresponse = Mail::sendemail($emailtoaddressedge, $emailfromaddressuser, $emailfromnameuser, $emailtosubjectedge , $bodytext);
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

    public function index(Request $request)
    {
        $contact = DB::table('contact');
              
        if($request->input('limit')){
            $limit = $request->input('limit');
        } else {
            $limit = 50;
        }
        if (is_numeric($limit)){
            return response()->json($contact->take($limit)->get());
        } else {
            //return response()->json($systemlog->paginate(2000));
            return response()->json($contact->get());
        } 
    }

    public function getContact($id)
    {   
        if (is_numeric($id))
        {
            $contact = Contact::find($id);
        }
        else
        {
            return response()->json('id måste vara numeriskt');    
        }
        return response()->json($contact);
    }

    public function createContact(Request $request)
    {
        $this->validate($request, [                        
        ]);

        $contact = Contact::create($request->all());
        return response()->json($contact, 201);
    }

    public function updateContact(Request $request, $id)
    {
        $contact = Contact::find($id);
        $contact->save();
        return response()->json($contact);   
    }

    public function deleteContact($id)
    {
        $contact = Contact::find($id);
        $contact->delete();
        return response()->json('deleted');
    }
   
}
?>