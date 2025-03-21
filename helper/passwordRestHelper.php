<?php
namespace Mountkenymilk\Fems\helper;

use Exception;
use PHPMailer\PHPMailer\PHPMailer;

class PasswordRestHelper
{
    public function isSamePassword(string $newHashedPwd, string $oldHashedPwd): bool
    {
        return password_verify($newHashedPwd, $oldHashedPwd);
    }

    public static function sendRestPasswordEmail($usersData, $reset_token)
    {

        error_log("Sending initiated ...");
        $recipientEmail = $usersData->email;
        $userName = htmlspecialchars($usersData->fname, ENT_QUOTES, 'UTF-8');
        $agencyName = $_ENV['AGENCY_NAME'] ?? 'Software Development Support Team';
        error_log("Values Appended ...");
        $mail = new PHPMailer(true);

        try {
            // SMTP Configuration
            $mail->isSMTP();
            $mail->Host = $_ENV['SMTP_HOST'];
            $mail->SMTPAuth = true;
            $mail->Username = $_ENV['SMTP_USERNAME'];
            $mail->Password = $_ENV['SMTP_PASSWORD'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $_ENV['SMTP_PORT'];

            // Email Headers
            $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'] ?? 'no-reply@merudairysupport.com', $_ENV['DEFAULT_FROM_EMAIL'] ?? 'Software Development Support Team');
            $mail->addAddress($recipientEmail, $userName);
            // Email Content
            $mail->isHTML(true);
            $mail->Subject = "Password Reset Request - " . strtoupper($recipientEmail);
            $mail->Body = "
            <div style='font-family: Arial, sans-serif; color: #333; line-height: 1.6; max-width: 600px;'>
                <p style='font-size: 16px;'>Dear <strong>$userName</strong>,</p>
                <p>We have received a request to reset your password!</p>

                <p>Please clik on the link below to reset your password.</p>
                <a href='" . $_ENV['base_site_url'] . "/reset_password?token=" . $reset_token . "' style='background-color: #337ab7; color: #fff; padding: 10px 15px; text-decoration: none;'>Reset Password</a>
                <h4 style='font-size: 16px; font-weight: bold;'>NOTE: This link expires after 30 minutes</h4>
                <p>If you did not make this request, ignore this mail.</p>

                <p>This is a system generated message, do not reply to it unless you want to talk to a robot!</p>

                <p style='font-size: 16px; font-weight: bold;'>$agencyName</p>
            </div>";
            if ($mail->send()) {
                error_log("Sending done..");
                return true;
            } else {
                error_log($mail->send());
                throw new Exception('Error sending email');
            }
        } catch (Exception $e) {
            error_log("Sending error ...".$e->getMessage());
            throw new Exception($e->getMessage());
        }
    }
    public static function sendAccountEmail($usersData, $reset_token)
    {

        error_log("Sending initiated ...");
        $recipientEmail = $usersData->email;
        $userName = htmlspecialchars($usersData->fname, ENT_QUOTES, 'UTF-8');
        $agencyName = $_ENV['AGENCY_NAME'] ?? 'Software Development Support Team';
        error_log("Values Appended ...");
        $mail = new PHPMailer(true);

        try {
            // SMTP Configuration
            $mail->isSMTP();
            $mail->Host = $_ENV['SMTP_HOST'];
            $mail->SMTPAuth = true;
            $mail->Username = $_ENV['SMTP_USERNAME'];
            $mail->Password = $_ENV['SMTP_PASSWORD'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $_ENV['SMTP_PORT'];

            // Email Headers
            $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'] ?? 'no-reply@merudairysupport.com', $_ENV['DEFAULT_FROM_EMAIL'] ?? 'Software Development Support Team');
            $mail->addAddress($recipientEmail, $userName);
            // Email Content
            $mail->isHTML(true);
            $mail->Subject = "Account Created for  - " . strtoupper($recipientEmail);
            $mail->Body = "
            <div style='font-family: Arial, sans-serif; color: #333; line-height: 1.6; max-width: 600px;'>
                <p style='font-size: 16px;'>Dear <strong>$userName</strong>,</p>
                <p>You account was created successfully!</p>
                <h4 style='font-size: 16px; font-weight: bold;'>Email:  $recipientEmail</h4>
                <h4 style='font-size: 16px; font-weight: bold;'>Password:  Dairy123@portal.com</h4>
                <br>
                <p>Please clik on the link below to access your account.</p>
                <a href='" . $_ENV['base_site_url'] . "/" . $reset_token . "' style='background-color: #337ab7; color: #fff; padding: 10px 15px; text-decoration: none;'>Login</a>
                <h4 style='font-size: 16px; font-weight: bold;'>Software Development Desk.</h4>
                <p>If you face any challenges, contact your system admin for help.</p>

                <p>This is a system generated message, do not reply to it unless you want to talk to a robot!</p>

                <p style='font-size: 16px; font-weight: bold;'>$agencyName</p>
            </div>";
            if ($mail->send()) {
                error_log("Sending done..");
                return true;
            } else {
                error_log($mail->send());
                throw new Exception('Error sending email');
            }
        } catch (Exception $e) {
            error_log("Sending error ...".$e->getMessage());
            throw new Exception($e->getMessage());
        }
    }


public static function sendContactEmail( $email, $name, $message)
{
    error_log("Sending contact form email initiated...");
    
    $recipientEmail = $_ENV['CONTACT_RECIPIENT_EMAIL'] ;
    $recipientName = $_ENV['CONTACT_RECIPIENT_NAME'] ; 

    $mail = new PHPMailer(true);
    
    try {
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host = $_ENV['SMTP_HOST'];
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['SMTP_USERNAME'];
        $mail->Password = $_ENV['SMTP_PASSWORD'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $_ENV['SMTP_PORT'];

        // Email Headers
        $mail->setFrom($email, $name);
        $mail->addAddress($recipientEmail, $recipientName); 

        // Email Content
        $mail->isHTML(true);
        $mail->Subject = "New Contact Form Submission from " . htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $mail->Body = "<div style='font-family: Arial, sans-serif; color: #333;'>
            <p><strong>Name:</strong> " . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . "</p>
            <p><strong>Email:</strong> " . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . "</p>
            <p><strong>Message:</strong></p>
            <p>" . nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')) . "</p>
        </div>";

        if ($mail->send()) {
            error_log("Contact form email sent successfully.");
            return true;
        } else {
            throw new Exception('Error sending email');
        }
    } catch (Exception $e) {
        error_log("Error sending contact form email: " . $e->getMessage());
        throw new Exception($e->getMessage());
    }
}


    public function create($senderid, $unicode, $schedule, $sending_time, $sms_list)
{
    $api_key = $_ENV['SMS_API_KEY'];
    $url = "https://send.advantasms.com/client/api/sendmessage";

    // Create the payload (JSON request body)
    $payload = json_encode([
        "apikey" => $api_key,
        "senderid" => $senderid,
        "unicode" => $unicode,
        "schedule" => $schedule,
        "sending-time" => $sending_time,
        "sms-list" => $sms_list
    ]);

    // Initialize cURL session
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($payload)
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

    // Execute the request and get the response
    $response = curl_exec($ch);

    // Check for any errors during execution
    if (curl_errno($ch)) {
        echo 'Request Error:' . curl_error($ch);
        return false; // Return false on error
    }

    // Close the cURL session
    curl_close($ch);

    // Decode the JSON response
    return json_decode($response, true); // Decode the response as an associative array
}


public function sendSMS($phone, $message)
{
    error_log("Sending initiat ............");
    $unicode = "no";
    $senderid = ""; // Set to "yes" if the message has special characters
    $schedule = "no";  // Set to "yes" if scheduling the message
    $sending_time = "";  // Leave blank if not scheduling
    $sms_list = [
        [
            "message" => $message,
            "mobiles" => $phone,
            "client-sms-ids" => ""
        ]
    ];
    
    $response = $this->create($senderid, $unicode, $schedule, $sending_time, $sms_list);
    
    if (isset($response['status']['error-code']) && $response['status']['error-code'] === "000") {
        return true; 
    }

    return false; 
}

}
