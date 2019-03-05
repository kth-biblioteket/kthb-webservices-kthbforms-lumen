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

//TODO Validera inkommande data för create/update/delete
class ConsultationController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */

    public function __construct()
    {
        $this->middleware('auth', ['only' => [
            '', 'getConsultation',  'createConsultation', 'updateConsultation', 'deleteConsultation'
        ]]);
        $this->middleware('localization');
    }

    /**
     * 
     * Funktion som skickar ett Handlednings-mail till
     * RT/EDGE/Funktionsadress
     * 
     * Formulärkonfig från JSON
     *      Mailadresser och texter
     * 
     * Byt ut variabler (@@XXXX) i mailtexten som hämtats från formulärkonfig
     * mot värden från request
     */
    public function sendConsultationMail(Request $request)
    {
        if (!$request->isJson()) {
            $responseobject = array(
                "status"  => "Error",
                "message" => "Please provide json"
            );
            return response()->json($responseobject, 201);
        }
        
        $this->validate($request, [ 
            'language' => 'required',                      
            'form.iam' => 'required',
            'form.name' => 'required',
            'form.phone' => 'required',
            'form.email' => 'required',
            'form.library' => 'required',
            'form.program' => 'required',
            'form.informationabout' => 'required',
            'form.informationwhere' => 'required',
            'form.searchwords' => 'required',
            'form.suggesteddate1' => 'required',
            'form.suggesteddate2' => 'required',
            'form.suggesteddate3' => 'required'
        ]);
        
        $formconfig = Formsconfig::get_form('consultation');

        $emailtoaddressedge = $formconfig->emailtoaddressedge->emailaddress;
        $emailfromaddressuser = $request->input('form.email');
        $emailfromnameuser = $request->input('form.name');                                 

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

        /*
        dynamiska fält... problem med arrayfält(datum)
        foreach ($formconfig->formfields as $field => $val) {
            $bodytext = str_replace('@@'. $field, $request->input('form.' . $field), $bodytext);
        }
        */
        $bodytext = str_replace('@@iam', $request->input('form.iam'), $bodytext);
        $bodytext = str_replace('@@name', $request->input('form.name'), $bodytext);
        $bodytext = str_replace('@@phone', $request->input('form.phone'), $bodytext);
        $bodytext = str_replace('@@email', $request->input('form.email'), $bodytext);
        $bodytext = str_replace('@@library', $request->input('form.library'), $bodytext);
        $bodytext = str_replace('@@program', $request->input('form.program'), $bodytext);
        $bodytext = str_replace('@@informationabout', $request->input('form.informationabout'), $bodytext);
        $bodytext = str_replace('@@informationwhere', $request->input('form.informationwhere'), $bodytext);
        $bodytext = str_replace('@@searchwords', $request->input('form.searchwords'), $bodytext);
        $bodytext = str_replace('@@suggesteddates', $request->input('form.suggesteddates'), $bodytext);
        $bodytext = str_replace('@@suggesteddate1', $request->input('form.suggesteddate1.formatted'), $bodytext);
        $bodytext = str_replace('@@suggesteddate2', $request->input('form.suggesteddate2.formatted'), $bodytext);
        $bodytext = str_replace('@@suggesteddate3', $request->input('form.suggesteddate3.formatted'), $bodytext);
        
        //Visa inte rader/rubriker/block där information saknas.
        if (strpos($bodytext,'showcritera=\'\'')!= false) {
            $bodytext = str_replace('showcritera=\'\'', 'style="mso-hide:all;display:none;max-height:0px;overflow:hidden;"', $bodytext);
        }
        
        $mailresponse = Mail::sendemail($emailtoaddressedge, $emailfromaddressuser, $emailfromnameuser, $emailtosubjectedge, $bodytext);
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
        $consultation = DB::table('consultation');
              
        if($request->input('limit')){
            $limit = $request->input('limit');
        } else {
            $limit = 50;
        }
        if (is_numeric($limit)){
            return response()->json($consultation->take($limit)->get());
        } else {
            //return response()->json($systemlog->paginate(2000));
            return response()->json($consultation->get());
        } 
    }

    public function getConsultation($id)
    {   
        if (is_numeric($id))
        {
            $consultation = Consultation::find($id);
        }
        else
        {
            return response()->json('id måste vara numeriskt');    
        }
        return response()->json($consultation);
    }

    public function createConsultation(Request $request)
    {
        $this->validate($request, [
        ]);

        $consultation = Consultation::create($request->all());
        return response()->json($consultation, 201);
    }

    public function updateConsultation(Request $request, $id)
    {
        $consultation = Consultation::find($id);
        //$user->name = $request->input('name');
        $consultation->save();
        return response()->json($consultation);   
    }

    public function deleteConsultation($id){
        $consultation = Consultation::find($id);
        $consultation->delete();
        return response()->json('deleted');
    }
   
}
?>