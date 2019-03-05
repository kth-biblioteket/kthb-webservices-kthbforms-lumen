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
class TeachingactivityController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */

    public function __construct()
    {
        $this->middleware('auth', ['only' => [
            '', 'getTeachingactivity',  'createTeachingactivity', 'updateTeachingactivity', 'deleteTeachingactivity'
        ]]);
        $this->middleware('localization');
    }

    /**
     * 
     * Funktion som skickar ett Undervisnings-mail till
     * RT/EDGE/Funktionsadress/emailadress
     * 
     * Formulärkonfig från JSON
     *      Mailadresser och texter
     * 
     * Byt ut variabler (@@XXXX) i mailtexten som hämtats från formulärkonfig
     * mot värden från request
     */
    public function sendTeachingactivityMail(Request $request)
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
            'form.program' => 'required',
            'form.semester' => 'required',
            'form.coursename' => 'required',
            'form.coursecode' => 'required',
            'form.courseedition' => 'required',
            'form.numberofstudents' => 'required',
            'form.email' => 'required',
            'form.task' => 'required',
            'form.courseplan' => 'required',
            'form.goal' => 'required',
            'form.preferreddatesquestion' => 'required',
            'form.otherteaching' => 'required',
            'form.ownfacilityquestion' => 'required'
        ]);

        if ($request->input('preferreddatesquestion')=='yes') {
            $this->validate($request, [                        
                'form.preferreddates' => 'required'
            ]);
        }
        if ($request->input('ownfacilityquestion')=='own') {
            $this->validate($request, [                        
                'form.facility' => 'required'
            ]);
        }
        
        $formconfig = Formsconfig::get_form('teachingactivity');

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

        foreach ($formconfig->formfields as $field => $val) {
            $bodytext = str_replace('@@'. $field, $request->input('form.' . $field), $bodytext);
        }
        //Visa inte rader/rubriker/block där information saknas.
        if (strpos($bodytext,'showcritera=\'\'')!= false) {
            $bodytext = str_replace('showcritera=\'\'', 'style="mso-hide:all;display:none;max-height:0px;overflow:hidden;"', $bodytext);
        }
        //echo $bodytext;
        //return response()->json('TESTAR API', 400);
        
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
        $teachingactivity = DB::table('teachingactivity');
              
        if($request->input('limit')){
            $limit = $request->input('limit');
        } else {
            $limit = 50;
        }
        if (is_numeric($limit)){
            return response()->json($teachingactivity->take($limit)->get());
        } else {
            //return response()->json($systemlog->paginate(2000));
            return response()->json($teachingactivity->get());
        } 
    }

    public function getTeachingactivity($id)
    {   
        if (is_numeric($id))
        {
            $teachingactivity = Teachingactivity::find($id);
        }
        else
        {
            return response()->json('id måste vara numeriskt');    
        }
        return response()->json($teachingactivity);
    }

    public function createTeachingactivity(Request $request)
    {
        $this->validate($request, [
        ]);

        $teachingactivity = Teachingactivity::create($request->all());
        return response()->json($teachingactivity, 201);
    }

    public function updateTeachingactivity(Request $request, $id)
    {
        $teachingactivity = Teachingactivity::find($id);
        //$user->name = $request->input('name');
        $teachingactivity->save();
        return response()->json($teachingactivity);   
    }

    public function deleteTeachingactivity($id){
        $teachingactivity = Teachingactivity::find($id);
        $teachingactivity->delete();
        return response()->json('deleted');
    }
   
}
?>