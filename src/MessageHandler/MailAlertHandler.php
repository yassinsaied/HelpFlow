<?php
 
namespace App\MessageHandler;
 
use App\Message\MailAlert;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Mime\Email;
 
class MailAlertHandler implements MessageHandlerInterface
{
    private $mailer;
 
    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }
 
    public function __invoke(MailAlert $message)
    {
        $email = (new Email())
        ->from($message->getFrom())
        ->to('saied.yassin@gmail.com')
        ->subject('New Incident #' . $message->getId() . ' - ' . $message->getFrom())
        ->html('<p>' . $message->getDescription() . '</p>');
 
        sleep(10);
 
        $this->mailer->send($email);
    }
}