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
class RequestController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    
    public function __construct()
    {
        $this->middleware('auth', ['only' => [
            'updateRequest','deleteRequest'
        ]]);

        $this->middleware('localization');
    }

    /**
     * Funktion för att skapa en request i Alma eller RT/EDGE
     * 
     * Libris ska alltid generera en request i Alma oavsett genre(varför?) 
     * 
     * De enda som skickas till EDGE är "journal"(tidskrift eller databas)
     * 
     * Låntagarinfo hämtas från Alma
     * 
     * Formulärkonfig från JSON
     *      Mailadresser och texter
     *      Genrenamn
     * 
     * Vilka typer ska bli book respektive article i Alma.
     * Sätt också formatet för att kunna använda facetterna i Alma för att sortera ut rätt beställningar. 
     * 
     * Returnerar Success eller Error 
     */
    public function createRequest(Request $request)
    {
        if (!$request->isJson()) {
            $responseobject = array(
                "status"  => "Error",
                "message" => "Please provide JSON"
            );
            return response()->json($responseobject, 201);
        }

        $this->validate($request, [
            'language' => 'required',
            'form.iam' => 'required',                      
            'form.username' => 'required',
            'form.genre' => 'required',
            'form.source' => 'required'
        ]);

        $almauser = Alma::callAlmaApi('users/' . $request->input('form.username'), 'GET', '', $request->input('language'), false);
        $almauserobject = json_decode($almauser);
        if (isset($almauserobject->status)) {
            if ($almauserobject->status == 'Error') {
                //User finns inte
                if(strpos($almauserobject->message,'User with identifier') !== false) {
                    if ($request->input('language') == 'swedish') {
                        $almauserobject->message = "Användarnamnet \"" . $request->input('form.username') . "\" är inte registrerat som låntagare hos oss.";
                    } else {
                        $almauserobject->message = "The username \"" . $request->input('form.username') . "\" is not registered as a patron at the library.";
                    }
                }
                
                return response()->json($almauserobject, 400);
            }
        }

        $almafullname = $almauserobject->full_name;
        $almapreferredemail ='';
        foreach($almauserobject->contact_info->email as $email) {
            if ($email->preferred) {
                $almapreferredemail = $email->email_address;
            }
        }

        $almalibraryname = "";
        if($request->input('form.pickup')) {
            $almalibrary = Alma::callAlmaApi('conf/libraries/' . $request->input('form.pickup'), 'GET', '', $request->input('language'), false);
            $almalibraryobject = json_decode($almalibrary);
            if (isset($almalibraryobject->status)) {
                if (isset($almalibraryobject->status) == 'Error') {
                    return response()->json($almalibraryobject, 400);
                }
            }
            $almalibraryname = $almalibraryobject->description;
            //return response()->json($request->input('language'), 400);
        }

        $formconfig = Formsconfig::get_form('requestmaterial');

        $emailtoaddressedge = $formconfig->emailtoaddressedge->emailaddress;
        $emailfromaddresslibrary = $formconfig->emailfromaddresslibrary->emailaddress;
        $emailfromnamelibrary = $formconfig->emailfromaddresslibrary->name->{$request->input('language')};

        try {
            $emailtobodyedge = "";
            $emailtobodyuser = "";
            $emailtosubjectedge = "";
            $emailtosubjectuser = "";
            foreach ($formconfig->emailtobodyedge->{$request->input('language')} as $row) {
                $emailtobodyedge .= $row;
            }
            foreach ($formconfig->emailtobodyuser->{$request->input('language')} as $row) {
                $emailtobodyuser .= $row;
            }
            $emailtosubjectedge = $formconfig->emailtosubjectedge->{$request->input('language')};
            $emailtosubjectuser = $formconfig->emailtosubjectuser->{$request->input('language')};

            $genre = $formconfig->genrenames->{$request->input('form.genre')}->{$request->input('language')};

            //hantera kostnader(costsbooks, costsarticles etc, TODO ändra till generell costs?)
            $cost="";
            
            $requestInput = $request->all();
            foreach ($requestInput['form'] as $key => $score) {
                if($score == 'acceptcost') {
                    foreach($formconfig->formfields->{$key}->options as $option){
                        if ($option->value == $score) {
                            $cost = $option->label->{$request->input('language')};
                        }
                    }
                }
                if($score == 'contact') {
                    foreach($formconfig->formfields->{$key}->options as $option){
                        if ($option->value == $score) {
                            $cost = $option->label->{$request->input('language')};
                        }
                    }
                }
            }
        } 
        catch(\Exception $e) {
            $responseobject = array(
                "status"  => "Error",
                "message" => $e->getMessage()
            );
            return response()->json($responseobject, 400);
        }

        //hantera materialtyper
        if ($request->input('form.genre') == 'bookitem' 
            || $request->input('form.genre') == 'book' 
            || $request->input('form.genre') == 'unknown' 
            || $request->input('form.genre') == '' 
            || $request->input('form.genre') == 'BK') {

            $citation_type = 'BK' ;
            if($request->input('form.genre') == 'bookitem') {
                $format	= "PHYSICAL_NON_RETURNABLE";
            } else {
                $format	= "PHYSICAL";
            }
        } elseif (  $request->input('form.genre') == 'journal' 
                    || $request->input('form.genre') == 'article' 
                    || $request->input('form.genre') == 'CR' 
                    || $request->input('form.genre') == 'proceeding') {

            $citation_type = 'CR' ;
            $format	= "PHYSICAL_NON_RETURNABLE";
        } else {
            $citation_type = 'BK' ;
            $format	= "PHYSICAL";
        }
        
        $emailtobodyedge = self::createmailbody($formconfig, $emailtobodyedge, $genre, $cost, $almafullname, $almapreferredemail, $almalibraryname, $request);
        $emailtobodyuser = self::createmailbody($formconfig, $emailtobodyuser, $genre, $cost, $almafullname, $almapreferredemail, $almalibraryname, $request);
        //echo $emailtobodyuser;
        //return response()->json($request, 400);

        switch($request->input('form.source')) {
            case 'info:sid/libris.kb.se:libris';  
                $almarequest =  Alma::createUserResourceSharingRequests($formconfig, $request, $request->all(), $citation_type, $format, $request->input('form.username'), 'true');
                $almarequestobject = json_decode($almarequest);
                if ($almarequestobject->status == 'Error') {
                    return response()->json($almarequestobject, 400);
                }

                $mailresponse = Mail::sendemail($almapreferredemail, $emailfromaddresslibrary, $emailfromnamelibrary, $emailtosubjectuser, $emailtobodyuser);
                if ($mailresponse != 'Success'){
                    $responseobject = array(
                        "status"  => "Error",
                        "message" => $mailresponse
                    );
                    return response()->json($responseobject, 202);
                }
                break;
            case 'primo';
                if ($request->input('form.genre') == 'journal') {
                    $mailresponse = Mail::sendemail($emailtoaddressedge, $almapreferredemail, $almafullname, $emailtosubjectedge, $emailtobodyedge);
                    if ($mailresponse != 'Success'){
                        $responseobject = array(
                            "status"  => "Error",
                            "message" => $mailresponse
                        );
                        return response()->json($responseobject, 400);
                    }
                } else {
                    $almarequest =  Alma::createUserResourceSharingRequests($formconfig, $request, $request->all(), $citation_type, $format, $request->input('form.username'), 'true');
                    $almarequestobject = json_decode($almarequest);
                    if ($almarequestobject->status == 'Error') {
                        return response()->json($almarequestobject, 400);
                    }

                    $mailresponse = Mail::sendemail($almapreferredemail, $emailfromaddresslibrary, $emailfromnamelibrary, $emailtosubjectuser, $emailtobodyuser);
                    if ($mailresponse != 'Success'){
                        $responseobject = array(
                            "status"  => "Error",
                            "message" => $mailresponse
                        );
                        return response()->json($responseobject, 202);
                    }
                }
                break;
            case 'kthbforms';
                if ($request->input('form.genre') == 'journal' || $request->input('form.genre') == 'database') {
                    $mailresponse = Mail::sendemail($emailtoaddressedge, $almapreferredemail, $almafullname, $emailtosubjectedge, $emailtobodyedge);
                    if ($mailresponse != 'Success'){
                        $responseobject = array(
                            "status"  => "Error",
                            "message" => $mailresponse
                        );
                        return response()->json($responseobject, 400);
                    }
                } else {
                    $almarequest =  Alma::createUserResourceSharingRequests($formconfig, $request, $request->all(), $citation_type, $format, $request->input('form.username'), 'true');
                    $almarequestobject = json_decode($almarequest);
                    if ($almarequestobject->status == 'Error') {
                        /*
                        if(strpos($almarequestobject->message,'Patron has duplicate') !== false) {
                            if ($request->input('language') == 'swedish') {
                                $almarequestobject->message = "Du har redan gjort en beställning av detta material.";
                            } else {
                                $almarequestobject->message = "You already have an active request for this material.";
                            }
                        }
                        if(strpos($almarequestobject->message,'institutional inventory has') !== false) {
                            if ($request->input('language') == 'swedish') {
                                $almarequestobject->message = "Det verkar som att vi redan har det material som du försöker beställa, vänligen kontrollera vår söktjänst.";
                            } else {
                                $almarequestobject->message = "It seems that we already have the material you are trying to request, please check our search tool";
                            }
                        }
                        */
                        return response()->json($almarequestobject, 400);
                    }

                    $mailresponse = Mail::sendemail($almapreferredemail, $emailfromaddresslibrary, $emailfromnamelibrary, $emailtosubjectuser, $emailtobodyuser);
                    if ($mailresponse != 'Success'){
                        $responseobject = array(
                            "status"  => "Error",
                            "message" => $mailresponse
                        );
                        return response()->json($responseobject, 202);
                    }
                }
                break;
            default:
                break;
        }

        $responseobject = array(
            "status"  => "Success",
            "message" => "Request created"
        );
        return response()->json($responseobject, 201,[],JSON_UNESCAPED_UNICODE);
    }

    /**
     * 
     * Funktion för att skapa ett mails innehåll.
     * 
     * Byt ut variabler (@@XXXX) i mailtexten som hämtats från formulärkonfig
     * mot värden från request
     * 
     * Visa inte rader/rubriker/block där information saknas.
     * JSON-exempel:  
     * "<div showcritera='@@cost'><strong>Accepterar kostnad</strong></div>",
     * "<div>@@cost</div>",
     * 
     */
    private static function createmailbody($formconfig, $bodytext, $genre, $cost, $fullname, $almapreferredemail, $almalibraryname, Request $request) {
        
        //hantera titel
        $title = '';
        $jtitle = '';
        $stitle = '';
        
        if($request->input('form.genre') == 'article' &&  $request->input('form.atitle')) {
            $title = $request->input('form.atitle');
        }

        if(($request->input('form.genre') == 'book' || $request->input('form.genre') == 'bookitem') &&  $request->input('form.btitle')) {
            $title = $request->input('form.btitle');
        }
        
        if(($request->input('form.genre') == 'journal' || $request->input('form.genre') == 'article') &&  $request->input('form.jtitle')) {
            $jtitle = $request->input('form.jtitle');
        }

        if(($request->input('form.genre') == 'journal' || $request->input('form.genre') == 'article') &&  $request->input('form.stitle')) {
            $stitle = $request->input('form.stitle');
        }

        //hantera författarnamn
        $author = "";
        if($request->input('form.genre') == 'book' || $request->input('form.genre') == 'article' || $request->input('form.genre') == 'bookitem' ) {
            if ($request->input('form.au') == '') {
                $author = $request->input('form.aulast') . ', ' . $request->input('form.aufirst');
            } else  {
                $author = $request->input('form.au');
            }
        }

        //Hantera kategori
        $iam='';
        foreach($formconfig->formfields->iam->options as $option){
            if($option->value == $request->input('form.iam')) {
                $iam =  $option->label->{$request->input('language')};
            }
        }
        
        $bodytext = str_replace('@@title', $title, $bodytext);
        $bodytext = str_replace('@@btitle', $request->input('form.btitle'), $bodytext);
        $bodytext = str_replace('@@atitle', $request->input('form.atitle'), $bodytext);
        $bodytext = str_replace('@@ctitle', $request->input('form.ctitle'), $bodytext);
        $bodytext = str_replace('@@stitle', $request->input('form.stitle'), $bodytext);
        $bodytext = str_replace('@@jtitle', $request->input('form.jtitle'), $bodytext);
        $bodytext = str_replace('@@dbtitle', $request->input('form.dbtitle'), $bodytext);
        $bodytext = str_replace('@@genre', $genre, $bodytext);
        $bodytext = str_replace('@@au', $author, $bodytext);
        $bodytext = str_replace('@@edition', $request->input('form.edition'), $bodytext);
        $bodytext = str_replace('@@issue', $request->input('form.issue'), $bodytext);
        $bodytext = str_replace('@@pages', $request->input('form.pages'), $bodytext);
        $bodytext = str_replace('@@issn', $request->input('form.issn'), $bodytext);
        $bodytext = str_replace('@@isbn', $request->input('form.isbn'), $bodytext);
        $bodytext = str_replace('@@place', $request->input('form.place'), $bodytext);
        $bodytext = str_replace('@@publisher', $request->input('form.publisher'), $bodytext);
        $bodytext = str_replace('@@year', $request->input('form.year'), $bodytext);
        $bodytext = str_replace('@@volume', $request->input('form.volume'), $bodytext);
        $bodytext = str_replace('@@source', $request->input('form.source'), $bodytext);
        $bodytext = str_replace('@@pickup', $almalibraryname, $bodytext);
        $bodytext = str_replace('@@doi', $request->input('form.doi'), $bodytext);
        $bodytext = str_replace('@@coursecode', $request->input('form.coursecode'), $bodytext);
        $bodytext = str_replace('@@fullname', $fullname, $bodytext);
        $bodytext = str_replace('@@iam', $iam, $bodytext);
        $bodytext = str_replace('@@username', $request->input('form.username'), $bodytext);
        $bodytext = str_replace('@@emailadress', $almapreferredemail, $bodytext);
        $bodytext = str_replace('@@cost', $cost, $bodytext);
        $bodytext = str_replace('@@message', $request->input('form.message'), $bodytext);

        if (strpos($bodytext,'showcritera=\'\'')!= false) {
            $bodytext = str_replace('showcritera=\'\'', 'style="mso-hide:all;display:none;max-height:0px;overflow:hidden;"', $bodytext);
        }
        return $bodytext;
    }

    public function index(Request $request)
    {
        $requestmaterial = DB::table('requestmaterial');
              
        if($request->input('limit')){
            $limit = $request->input('limit');
        } else {
            $limit = 50;
        }
        if (is_numeric($limit)){
            return response()->json($requestmaterial->take($limit)->get());
        } else {
            //return response()->json($systemlog->paginate(2000));
            return response()->json($requestmaterial->get());
        } 
        
    }

    public function getRequest($id)
    {   
        if (is_numeric($id))
        {
            $requestmaterial = RequestMaterial::find($id);
        }
        else
        {
            return response()->json('id måste vara numeriskt');    
        }
        return response()->json($requestmaterial);
    }
    
    public function updateRequest(Request $request, $id)
    {
        $requestmaterial = RequestMaterial::find($id);
        //$user->name = $request->input('name');
        $requestmaterial->save();
        return response()->json($requestmaterial);   
    }

    public function deleteRequest($id){
        $requestmaterial = RequestMaterial::find($id);
        $requestmaterial->delete();
        return response()->json('deleted');
    }
}
?>