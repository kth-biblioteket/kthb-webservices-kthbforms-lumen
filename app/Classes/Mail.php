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
     * Funktion som läser eventuella uppladdade filer
     */
    private static function getFiles() {
        $result = array();
        foreach($_FILES as $name => $fileArray) {
            if (is_array($fileArray['name'])) {
                foreach ($fileArray as $attrib => $list) {
                    foreach ($list as $index => $value) {
                        $result[$name][$index][$attrib]=$value;
                    }
                }
            } else {
                $result[$name][] = $fileArray;
            }
        }
        return $result;
    }

    /**
     * 
     * Funktion som skickar smtp-mail(TLS) via PHPMailer
     */
    public static function sendemail($to, $from, $fromname, $subject, $bodytext, $inlineimage = '', $inlingeimagecid = '', $attachments = '') 
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

    public static function siyssmail($to, $from, $fromname, $subject, $bodytext, $inlineimage = '', $inlingeimagecid = '', $request) 
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

            //Lägg till eventuella attachments
            foreach (self::getFiles() as $fieldName => $files) {
                foreach ($files as $index => $fileArray) {
                    $mail->AddAttachment( $fileArray['tmp_name'] , $fileArray['name'], 'base64', 'application/octet-stream' );
                }
            }
            
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