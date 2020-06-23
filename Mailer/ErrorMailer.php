<?php


namespace SwissthemeSarl\sixpayment\Mailer;

class ErrorMailer
{
    private $_mailer;

    public function __construct(\Swift_Mailer $mailer)
    {
        $this->_mailer = $mailer;
    }

    public function ErrorMail(string $err): void
    {
        $message = new \Swift_Message();
        $message->setSubject('six Payment error');
        $message->setTo('richardblat@gmail.com');
        $message->setFrom('error@parentsolo.ch');
        $message->setBody('<h1>Six Error</h1><div>' . $err . '</div>', 'text/html');
        $this->_mailer->send($message);
    }
}
