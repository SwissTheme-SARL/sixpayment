<?php


namespace backndev\sixpayment\Mailer;


use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ErrorMailer
{
    private $_mailer;
    private $_params;

    public function __construct(\Swift_Mailer $mailer)
    {
        $this->_mailer = $mailer;

    }

    public function ErrorMail(string $err) : void {
        $message = new \Swift_Message();
        $message->setSubject('six Payment error');
        $message->setTo('damien@backndev.fr');
        $message->setFrom('error@parentsolo.ch');
        $message->setBody('<h1>Six Error</h1><div>' . $err . '</div>', 'text/html');
        $this->_mailer->send($message);
    }

}