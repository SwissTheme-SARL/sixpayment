<?php


namespace backndev\sixpayment\Command;


use App\Entity\Payment;
use backndev\sixpayment\Mailer\ErrorMailer;
use backndev\sixpayment\SixPayment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class CapturePaymentCommand extends Command
{
    protected static $defaultName = 'payment:capture';
    private $_em;
    private $_mailer;
    private $_param;

    public function __construct(EntityManagerInterface $entityManager, \Swift_Mailer $mailer, ParameterBagInterface $parameterBag, string $name = null)
    {
        $this->_em = $entityManager;
        $this->_mailer = $mailer;
        $this->_param = $parameterBag;
        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setDescription('Capture all payments');
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $date = new \DateTime('now');
            $date->getTimezone();
            $catchable = $this->_em->getRepository(Payment::class)->findBy(['isCaptured' => false]);
            if (count($catchable) > 0) {
                foreach ($catchable as $catch) {
                    /** @var Payment $catch */
                    $output->writeln($catch->getId());
                    $sixPayment = new SixPayment(
                        $this->_param->get('api.six.customer'),
                        $this->_param->get('api.six.terminal'),
                        $catch->getId(),
                        $this->_param->get('api.six.key'),
                        $this->_param->get('api.six.uri')
                    );
                    $sixPayment->capturePayment($catch->getUniqKey());
                    $catch->setIsCaptured(true);
                    $this->_em->flush();

                    $message = new \Swift_Message();
                    $message->setFrom('cron@parentsolo.ch');
                    $message->setSubject('payment');
                    $message->setTo('damien@backndev.fr');
                    $message->setBody('
                        <h1>Payment</h1>
                        <p>payment to ' . $catch->getPaymentProfil()->getUser()->getEmail() . ' was done  at ' . $date->format('d-m-Y H:i:s') . '</p>
                    ', 'text/html');
                    $this->_mailer->send($message);
                }
            } else {
                $output->write('nothing to catch');
                $message = new \Swift_Message();
                $message->setFrom('cron@parentsolo.ch');
                $message->setSubject('payment');
                $message->setTo('damien@backndev.fr');
                $message->setBody('
                        <h1>Payment</h1>
                        <p>Nothing to catch at ' . $date->format('d-m-Y H:i:s') . '</p>
                    ', 'text/html');
                $this->_mailer->send($message);
            }
        }catch (\Exception $e){
            $errorMail = new ErrorMailer($this->_mailer);
            if (is_array($e)){
                foreach ($e as $error){
                    $errorMail->ErrorMail($error);
                }
            }else{
                $errorMail->ErrorMail($e);
            }
        }
    }
}