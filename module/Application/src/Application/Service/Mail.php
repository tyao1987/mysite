<?php
namespace Application\Service;

use Zend\Mail as ZendMail;

class Mail {
    public static function mail($to, $subject, $message) {
        $mail = new ZendMail\Message();
        $mail->setBody($message);
        $mail->addTo($to);
        $mail->setSubject($subject);
        
        $transport = new ZendMail\Transport\Sendmail();
        $transport->send($mail);
    }
}