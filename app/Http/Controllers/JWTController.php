<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\View;

class JWTController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
     
    public function __construct()
    {
        //definiera vilka anrop som behöver nyckel/autentisering
        $this->middleware('auth', ['only' => [
            'index'
        ]]);
    }

    /**
     * Get the array of view data.
     *
     * @return array
     */

    public function index(Request $request)
    {
        //Om autentiserad skicka tillbaks ok
        return response()->json([
            'authorized' => true
        ], 200);
    }
   
}
?>