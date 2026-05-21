<?php
/**
 * SMS Bridge for Hope Haven Hospital
 * Integration for Twilio or generic SMS gateways.
 */
class SMSBridge {
    private $sid;
    private $token;
    private $from;

    public function __construct($sid, $token, $from) {
        $this->sid = $sid;
        $this->token = $token;
        $this->from = $from;
    }

    /**
     * Sends an SMS message to a phone number.
     * Phone number should be in E.164 format (e.g. +2348012345678)
     */
    public function send($to, $message) {
        // Basic phone number cleaning - assume Nigerian prefix if missing +
        if (substr($to, 0, 1) !== '+') {
            if (substr($to, 0, 1) === '0') {
                $to = '+234' . substr($to, 1);
            } else {
                $to = '+' . $to;
            }
        }

        $url = "https://api.twilio.com/2010-04-01/Accounts/" . $this->sid . "/Messages.json";
        
        $data = [
            'From' => $this->from,
            'To' => $to,
            'Body' => $message
        ];

        $post = http_build_query($data);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $this->sid . ":" . $this->token);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $res_data = json_decode($response, true);
        
        if ($http_code >= 200 && $http_code < 300) {
            return true;
        } else {
            error_log("SMS Bridge Error ($http_code): " . ($res_data['message'] ?? 'Unknown error'));
            return false;
        }
    }
}
?>