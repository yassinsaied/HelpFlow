<?php
namespace App\Message ;


class MailAlert 
{
    private int $id ;
    private string  $from ;
    private string  $description ;


    public function __construct(string $description, int $id, string $from)
    {
        $this->description = $description;
        $this->id = $id;
        $this->from = $from;
    }
 
    public function getDescription(): string
    {
        return $this->description;
    }
 
    public function getId(): int
    {
        return $this->id;
    }
 
    public function getFrom(): string
    {
        return $this->from;
    }
}