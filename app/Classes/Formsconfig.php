<?php
namespace App\Classes;
use Illuminate\Support\Facades\View;

class Formsconfig
{
    public static function get_form($formid)
    {
        try {
            $ch = curl_init();
            $url = env("FORMSCONFIG_URL", "missing") . $formid. ".json?ver=1.01";
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_HEADER, FALSE);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            $response = curl_exec($ch);
            curl_close($ch);  
            return json_decode($response);
        } catch(\Exception $e) {
            $responseobject = array(
                "status"  => "Error",
                "message" => $e->getMessage()
            );
            return response()->json($responseobject, 400);
        }
    }    
}
?>
