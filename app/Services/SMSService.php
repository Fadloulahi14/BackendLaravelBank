<?php

namespace App\Services;

class SMSService
{
    
    public function sendSMS(string $phoneNumber, string $message): bool
    {
        
        \Log::info('SMS envoyé', [
            'to' => $phoneNumber,
            'message' => $message,
            'timestamp' => now()
        ]);

     

        return true;
    }
}