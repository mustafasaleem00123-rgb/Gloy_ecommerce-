<?php
/**
 * Gloy Skincare - HTTP REST API Mailer Helper (Works on InfinityFree & Localhost)
 * Supports Brevo (Sendinblue) API and Resend API over HTTPS Port 443 (no SMTP blocking).
 */

require_once __DIR__ . '/../config/env.php';

function gloy_send_email($toEmail, $subject, $htmlBody, $plainTextBody = '') {
    $brevoApiKey = gloy_env('BREVO_API_KEY', '');
    $resendApiKey = gloy_env('RESEND_API_KEY', '');
    
    $fromEmail = gloy_env('MAIL_FROM_EMAIL', 'noreply@gloyskincare.com');
    $fromName  = gloy_env('MAIL_FROM_NAME', 'Gloy Skincare');

    // 1. Brevo (Sendinblue) HTTP REST API (HTTPS Port 443)
    if (!empty($brevoApiKey)) {
        $url = 'https://api.brevo.com/v3/smtp/email';
        $payload = [
            'sender'      => ['name' => $fromName, 'email' => $fromEmail],
            'to'          => [['email' => $toEmail]],
            'subject'     => $subject,
            'htmlContent' => $htmlBody
        ];
        if (!empty($plainTextBody)) {
            $payload['textContent'] = $plainTextBody;
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'accept: application/json',
            'api-key: ' . $brevoApiKey,
            'content-type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return true;
        }
        error_log("[Gloy Mailer] Brevo API Error (HTTP $httpCode): " . $response . ' ' . $curlError);
        return false;
    }

    // 2. Resend HTTP REST API (HTTPS Port 443)
    if (!empty($resendApiKey)) {
        $url = 'https://api.resend.com/emails';
        $payload = [
            'from'    => "$fromName <$fromEmail>",
            'to'      => [$toEmail],
            'subject' => $subject,
            'html'    => $htmlBody
        ];
        if (!empty($plainTextBody)) {
            $payload['text'] = $plainTextBody;
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $resendApiKey,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return true;
        }
        error_log("[Gloy Mailer] Resend API Error (HTTP $httpCode): " . $response . ' ' . $curlError);
        return false;
    }

    // 3. Fallback (Development Mode / Missing API Key)
    error_log("[Gloy Mailer] No API Key set. Simulated email to $toEmail: Subject: $subject");
    return true;
}
