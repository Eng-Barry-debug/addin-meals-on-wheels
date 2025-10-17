<?php
// includes/newsletter_sender.php - Email sending functionality (PHPMailer version)

require_once 'config.php';
require_once 'PHPMailer/PHPMailer.php';
require_once 'PHPMailer/SMTP.php';
require_once 'PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class NewsletterSender {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function sendNewsletter($campaign_id) {
        try {
            // Get campaign details
            $stmt = $this->pdo->prepare("
                SELECT nc.*, nt.html_template, nt.text_template
                FROM newsletter_campaigns nc
                LEFT JOIN newsletter_templates nt ON nc.template = nt.id
                WHERE nc.id = ?
            ");
            $stmt->execute([$campaign_id]);
            $campaign = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$campaign) {
                throw new Exception('Campaign not found');
            }

            // Get active subscribers
            $stmt = $this->pdo->prepare("
                SELECT id, email, unsubscribe_token
                FROM newsletter_subscriptions
                WHERE is_active = TRUE
                ORDER BY id
            ");
            $stmt->execute();
            $subscribers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($subscribers)) {
                throw new Exception('No active subscribers found');
            }

            // Update campaign status and recipient count
            $stmt = $this->pdo->prepare("
                UPDATE newsletter_campaigns
                SET status = 'sending', total_recipients = ?, sent_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->execute([count($subscribers), $campaign_id]);

            $sent_count = 0;
            $failed_count = 0;

            // Send to subscribers (limit to 5 for testing to avoid overwhelming)
            $max_emails = min(count($subscribers), 5);

            for ($i = 0; $i < $max_emails; $i++) {
                $subscriber = $subscribers[$i];

                try {
                    $this->sendToSubscriber($campaign, $subscriber);
                    $sent_count++;

                    // Update subscriber tracking
                    $stmt = $this->pdo->prepare("
                        UPDATE newsletter_subscriptions
                        SET total_emails_received = total_emails_received + 1,
                            last_email_sent = CURRENT_TIMESTAMP
                        WHERE id = ?
                    ");
                    $stmt->execute([$subscriber['id']]);

                } catch (Exception $e) {
                    $failed_count++;
                    error_log("Failed to send to {$subscriber['email']}: " . $e->getMessage());
                }
            }

            // Update final campaign statistics
            $stmt = $this->pdo->prepare("
                UPDATE newsletter_campaigns
                SET status = 'sent', sent_count = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->execute([$sent_count, $campaign_id]);

            return [
                'success' => true,
                'sent' => $sent_count,
                'failed' => $failed_count,
                'total' => count($subscribers),
                'note' => 'Limited to 5 emails for testing. Configure SMTP for full sending.'
            ];

        } catch (Exception $e) {
            // Update campaign status to indicate failure
            $stmt = $this->pdo->prepare("UPDATE newsletter_campaigns SET status = 'cancelled' WHERE id = ?");
            $stmt->execute([$campaign_id]);

            throw $e;
        }
    }

    private function sendToSubscriber($campaign, $subscriber) {
        try {
            $mail = new PHPMailer(true);

            // Server settings - MAILTRAP SMTP CONFIGURATION
            $mail->isSMTP();
            $mail->Host       = 'bulk.smtp.mailtrap.io';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'api';                        // Mailtrap username
            $mail->Password   = '1760082479';                 // Your Mailtrap token
            $mail->Port       = 587;                          // Recommended port
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

            // Recipients
            $mail->setFrom('noreply@addinsmeals.com', 'Addins Meal on Wheels');
            $mail->addReplyTo('info@addinsmeals.com', 'Addins Meal on Wheels');
            $mail->addAddress($subscriber['email']);
            // Content
            $mail->isHTML(true);
            $mail->Subject = $campaign['subject'];
            $mail->CharSet = 'UTF-8';

            // Get email template
            $template_html = $campaign['html_template'] ?? $this->getDefaultTemplate();

            // Replace placeholders
            $replacements = [
                '{{SUBJECT}}' => htmlspecialchars($campaign['subject']),
                '{{CONTENT}}' => $campaign['content'],
                '{{UNSUBSCRIBE_URL}}' => 'https://' . $_SERVER['HTTP_HOST'] . '/unsubscribe.php?token=' . $subscriber['unsubscribe_token'],
                '{{WEBSITE_URL}}' => 'https://' . $_SERVER['HTTP_HOST'],
                '{{YEAR}}' => date('Y'),
                '{{UNSUBSCRIBE_TOKEN}}' => $subscriber['unsubscribe_token']
            ];

            foreach ($replacements as $placeholder => $value) {
                $template_html = str_replace($placeholder, $value, $template_html);
            }

            $mail->Body    = $template_html;
            $mail->AltBody = strip_tags($campaign['content']); // Plain text version

            $mail->send();

        } catch (Exception $e) {
            throw new Exception("Failed to send email to {$subscriber['email']}: " . $mail->ErrorInfo);
        }
    }

    private function getDefaultTemplate() {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1'>
            <title>{{SUBJECT}}</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px; background-color: #f8f9fa; }
                .container { max-width: 600px; margin: 0 auto; background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .header { text-align: center; border-bottom: 3px solid #C1272D; padding-bottom: 20px; margin-bottom: 30px; }
                .logo { font-size: 24px; font-weight: bold; color: #C1272D; margin-bottom: 10px; }
                .content { margin: 20px 0; }
                .cta-button { display: inline-block; background-color: #C1272D; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #666; text-align: center; }
                .unsubscribe { color: #C1272D; text-decoration: none; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <div class='logo'>Addins Meals on Wheels</div>
                    <h1>{{SUBJECT}}</h1>
                </div>
                <div class='content'>
                    {{CONTENT}}
                </div>
                <div class='footer'>
                    <p>You received this email because you subscribed to our newsletter.</p>
                    <p><a href='{{UNSUBSCRIBE_URL}}' class='unsubscribe'>Unsubscribe</a> | <a href='{{WEBSITE_URL}}'>Visit Website</a></p>
                    <p>&copy; {{YEAR}} Addins Meals on Wheels. All rights reserved.</p>

                    <div style='margin-top: 20px; padding: 15px; background-color: #f8f9fa; border-radius: 5px; text-align: center;'>
                        <h3 style='color: #C1272D; margin-bottom: 10px;'>Payment Information</h3>
                        <p style='margin: 5px 0;'><strong>M-Pesa Paybill:</strong> 116519</p>
                        <p style='margin: 5px 0;'><strong>Account Number:</strong> 007160</p>
                        <p style='margin: 5px 0; font-size: 12px; color: #666;'>Use these details for all M-Pesa payments</p>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}

// Function to trigger newsletter sending
function sendNewsletterCampaign($campaign_id) {
    global $pdo;

    $sender = new NewsletterSender($pdo);
    $result = $sender->sendNewsletter($campaign_id);

    return $result;
}
?>
