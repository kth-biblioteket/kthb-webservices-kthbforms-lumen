<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;
use App\Form;
use Illuminate\Support\Facades\View;
use DB; 

//TODO Validera inkommande data för create/update/delete
class FormController extends Controller
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
            'createForm', 'updateForm', 'deleteForm'
        ]]);
        //Skicka alla anrop till middleware som sätter locale utifrån parameter/header
        $this->middleware('localization');
    }

    public function index(Request $request)
    {
        $forms = DB::table('forms')->orderBy('name');
              
        if($request->input('limit')){
            $limit = $request->input('limit');
        } else {
            //returnera 50 rader som default
            $limit = 50;
        }
        if (is_numeric($limit)){
            return response()->json($forms->take($limit)->get());
        } else {
            //returnera endast alla om parameter limit = none. Men med paginering
            return response()->json($forms->paginate(2000));
            //returnera alla records
            //return response()->json($forms->get());
        } 
        
    }

    public function getForm($id)
    {   
        if (is_numeric($id))
        {
            $form = Form::find($id);
        }
        else
        {
            return response()->json('id måste vara numeriskt');    
        }
        return response()->json($form);
    }

    public function createForm(Request $request)
    {
        $this->validate($request, [                        
            'mmsid' => 'required',
            'isbn' => 'required',
            'title' => 'required',
            'activationdate' => 'required',
            'dewey' => 'required'
        ]);

        $entry = Form::create($request->all());
        //201 = http-statuskod för att nåt skapats.
        return response()->json($entry, 201);
    }

    public function updateForm(Request $request, $id)
    {
        $object = Form::find($id);
        $object->save();
        return response()->json($object);   
    }

    public function deleteForm($id){
        $object = Form::find($id);
        $object->delete();
        return response()->json('deleted');
    }
   
}
?>