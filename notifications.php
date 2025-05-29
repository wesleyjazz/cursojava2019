<?php
class Notifications {
    public static function sendWhatsApp($to, $message) {
        // Implementação básica - ajuste com suas credenciais
        $url = 'https://api.twilio.com/2010-04-01/Accounts/' . TWILIO_SID . '/Messages.json';
        
        $data = [
            'To' => 'whatsapp:+' . $to,
            'From' => 'whatsapp:+' . TWILIO_NUMBER,
            'Body' => $message
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, TWILIO_SID . ":" . TWILIO_TOKEN);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return $response;
    }
}
