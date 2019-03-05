<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;
use App\RequestMaterial;
use Illuminate\Support\Facades\View;
use DB; 
use App\Classes\Alma;
use App\Classes\Mail;
use App\Classes\Formsconfig;

//TODO
class LibraryaccountController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    

    public function __construct()
    {
        $this->middleware('auth', ['only' => [
            'activateLibraryaccount', 'updateLibraryaccount','deleteLibraryaccount'
        ]]);
        $this->middleware('localization');
    }

    /**
     * 
     * Funktion som skapar låntagare i Alma och skickar ett
     * bekräftelsemail till användaren
     * 
     * Formulärkonfig från JSON
     *      Mailadresser och texter
     * 
     * Byt ut variabler (@@XXXX) i mailtexten som hämtats från formulärkonfig
     * mot värden från request
     */
    public function createLibraryaccount(Request $request)
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
            //'form.iam' => 'required',                      
            'form.firstname' => 'required',
            'form.lastname' => 'required',
            'form.personalnumber' => 'required',
            'form.phone1' => 'required',
            'form.email' => 'required',
            'form.password' => 'required',
            'form.pin' => 'required',
            'form.streetadress' => 'required',
            'form.zipcode' => 'required',
            'form.city' => 'required',
            'form.accept' => 'required'
        ]);


        /*
        if ($request->input('iam')=='other') {
            $this->validate($request, [                        
                'form.otherinfo' => 'required'
            ]);
        }
        */

        $formconfig = Formsconfig::get_form('libraryaccount');

        $emailfromaddresslibrary = $formconfig->emailfromaddresslibrary->emailaddress;
        $emailfromnamelibrary = $formconfig->emailfromaddresslibrary->name->{$request->input('language')};

        try {
            $bodytext = "";
            $emailtosubjectuser = $formconfig->emailtosubjectuser->{$request->input('language')};
            foreach ($formconfig->emailtobodyuser->{$request->input('language')} as $row) {
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

        $bodytext = str_replace('@@email', $request->input('form.email'), $bodytext);
        
        $almauser = Alma::createUser($request->all());
        $almauserobject = json_decode($almauser);
        if ($almauserobject->status == 'Error') {
            return response()->json($almauserobject, 400);
        }

        $mailresponse = Mail::sendemail($request->input('form.email'), $emailfromaddresslibrary, $emailfromnamelibrary, $emailtosubjectuser, $bodytext);
        if ($mailresponse != 'Success'){
            $responseobject = array(
                "status"  => "Error",
                "message" => $mailresponse
            );
            return response()->json($responseobject, 202);
        }

        $responseobject = array(
            "status"  => "Success",
            "message" => "Account created"
        );
        return response()->json($responseobject, 201,[],JSON_UNESCAPED_UNICODE);
    }

    public function activateLibraryaccount(Request $request)
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
            'kthid' => 'required'
        ]);
        
        //Aktivera användarens patronroll(om den inte redan är aktiv)
        $almauser = Alma::activatePatronRole($request->input('kthid'));
        $almauserobject = json_decode($almauser);
        if ($almauserobject->status == 'Error') {
            if (strpos($almauserobject->message,"User with identifier") !== false) {
                //Användaren finns inte.
            }
            //övriga fel
            return response()->json($almauserobject, 400);
        }

        //Bekräftelsemail?
        $responseobject = array(
            "status"  => "Success",
            "message" => "Account activated"
        );
        return response()->json($responseobject, 200,[],JSON_UNESCAPED_UNICODE);
    }

    public function index(Request $request)
    {
        $libraryaccount = DB::table('libraryaccount');
              
        if($request->input('limit')){
            $limit = $request->input('limit');
        } else {
            $limit = 50;
        }
        if (is_numeric($limit)){
            return response()->json($libraryaccount->take($limit)->get());
        } else {
            //return response()->json($systemlog->paginate(2000));
            return response()->json($libraryaccount->get());
        } 
        
    }

    public function getLibraryaccount($id)
    {   
        if (is_numeric($id))
        {
            $libraryaccount = libraryaccount::find($id);
        }
        else
        {
            return response()->json('id måste vara numeriskt');    
        }
        return response()->json($libraryaccount);
    }

    public function updateLibraryaccount(Request $request, $id)
    {
        $libraryaccount = libraryaccount::find($id);
        $libraryaccount->save();
        return response()->json($libraryaccount);   
    }

    public function deleteLibraryaccount($id)
    {
        $libraryaccount = libraryaccount::find($id);
        $libraryaccount->delete();
        return response()->json('deleted');
    }
}
?>