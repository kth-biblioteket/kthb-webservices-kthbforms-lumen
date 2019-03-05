<?php
namespace App\Classes;
use Log;
use Illuminate\Support\Facades\View;
use PHPMailer;
require_once($_SERVER['DOCUMENT_ROOT'] . '/PHPMailer/PHPMailerAutoload.php');

class Mail
{
    /**
     * 
     * Funktion som skickar smtp-mail(TLS) via PHPMailer
     */
    public static function sendemail($to, $from, $fromname, $subject, $bodytext, $inlineimage = '', $inlingeimagecid = '') 
    {
        try {
            $mail = new PHPMailer();
            
            $mail->isSMTP();
            $mail->Host = "smtp.kth.se";
            $mail->SMTPAuth   = FALSE;
            $mail->SMTPSecure = "tls";
        
            $mail->CharSet = 'UTF-8';
            $mail->From      = $from;
            $mail->FromName  = $fromname;
            $mail->Subject   = $subject;
            $mail->Body = $bodytext;
            $mail->IsHTML(true);
            //$mail->msgHTML($bodytext);
            $addresses = explode(",",$to);
            
            if(!empty($addresses)){
                foreach ($addresses as $address) {
                    $mail->AddAddress($address);
                }
            } else {
                //Ska inte hända!
                throw new Exception('No emailaddresses found!');
            }
        
            $mail->AddAddress( $to );
            if($inlineimage != '' && $inlingeimagecid != '') {
                $mail->addEmbeddedImage($inlineimage, $inlingeimagecid);	
            }

            //Log::info('Mailbody: '.$bodytext);
            
            if($mail->Send()){
                return 'Success';
            } else {
                return $mail->ErrorInfo;
            }
        } 
        catch (\phpmailerException $e) {
            return $e->errorMessage();
        } 
        catch (\Exception $e) {
            return $e->getMessage();
        }
    }
    
}
?>