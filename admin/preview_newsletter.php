<?php
// admin/preview_newsletter.php - Newsletter preview functionality

// Check if user is logged in and is admin
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit('Access denied');
}

require_once dirname(__DIR__) . '/includes/config.php';

$campaign_id = (int)($_GET['id'] ?? 0);

if (!$campaign_id) {
    http_response_code(400);
    exit('Campaign ID required');
}

try {
    // Get newsletter campaign
    $stmt = $pdo->prepare("
        SELECT nc.*, nt.html_template
        FROM newsletter_campaigns nc
        LEFT JOIN newsletter_templates nt ON nc.template = nt.id
        WHERE nc.id = ?
    ");
    $stmt->execute([$campaign_id]);
    $campaign = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$campaign) {
        http_response_code(404);
        exit('Newsletter not found');
    }

    // Replace template placeholders
    $html = $campaign['html_template'] ?? getDefaultTemplate();

    // Replace placeholders
    $replacements = [
        '{{SUBJECT}}' => htmlspecialchars($campaign['subject']),
        '{{CONTENT}}' => $campaign['content'],
        '{{UNSUBSCRIBE_URL}}' => 'https://' . $_SERVER['HTTP_HOST'] . '/unsubscribe.php?token={{UNSUBSCRIBE_TOKEN}}',
        '{{WEBSITE_URL}}' => 'https://' . $_SERVER['HTTP_HOST'],
        '{{YEAR}}' => date('Y')
    ];

    foreach ($replacements as $placeholder => $value) {
        $html = str_replace($placeholder, $value, $html);
    }

    echo $html;

} catch (Exception $e) {
    http_response_code(500);
    echo 'Error: ' . $e->getMessage();
}

function getDefaultTemplate() {
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='utf-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1'>
        <title>{{SUBJECT}}</title>
        <style>
            /* Reset and base styles */
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
                line-height: 1.6;
                color: #1f2937;
                background-color: #ffffff;
            }

            /* Desktop styles */
            .desktop-container {
                max-width: 600px;
                margin: 0 auto;
                background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
                border-radius: 16px;
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
                overflow: hidden;
            }

            .desktop-header {
                background: linear-gradient(135deg, #C1272D 0%, #991b1b 100%);
                color: white;
                text-align: center;
                padding: 40px 30px;
                position: relative;
            }

            .desktop-header::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: url('data:image/svg+xml,<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 100 100\"><circle cx=\"20\" cy=\"20\" r=\"2\" fill=\"rgba(255,255,255,0.1)\"/><circle cx=\"80\" cy=\"40\" r=\"1.5\" fill=\"rgba(255,255,255,0.08)\"/><circle cx=\"40\" cy=\"80\" r=\"1\" fill=\"rgba(255,255,255,0.06)\"/></svg>');
                opacity: 0.3;
            }

            .desktop-logo {
                font-size: 28px;
                font-weight: 700;
                margin-bottom: 8px;
                text-shadow: 0 2px 4px rgba(0,0,0,0.1);
                position: relative;
                z-index: 1;
            }

            .desktop-tagline {
                font-size: 16px;
                opacity: 0.9;
                font-weight: 300;
                position: relative;
                z-index: 1;
            }

            .desktop-subject {
                font-size: 32px;
                font-weight: 600;
                margin: 20px 0 10px 0;
                text-shadow: 0 2px 4px rgba(0,0,0,0.1);
                position: relative;
                z-index: 1;
            }

            .desktop-content {
                padding: 40px 30px;
                background: white;
                font-size: 16px;
                line-height: 1.7;
            }

            .desktop-content h1, .desktop-content h2, .desktop-content h3 {
                color: #C1272D;
                margin: 30px 0 15px 0;
                font-weight: 600;
            }

            .desktop-content h1:first-child, .desktop-content h2:first-child, .desktop-content h3:first-child {
                margin-top: 0;
            }

            .desktop-content p {
                margin-bottom: 20px;
                color: #374151;
            }

            .desktop-content ul, .desktop-content ol {
                margin: 20px 0;
                padding-left: 25px;
            }

            .desktop-content li {
                margin-bottom: 8px;
                color: #374151;
            }

            .desktop-content blockquote {
                background: #f3f4f6;
                border-left: 4px solid #C1272D;
                padding: 20px 25px;
                margin: 25px 0;
                font-style: italic;
                border-radius: 0 8px 8px 0;
            }

            .desktop-content a {
                color: #C1272D;
                text-decoration: none;
                font-weight: 500;
                border-bottom: 1px solid transparent;
                transition: border-color 0.2s;
            }

            .desktop-content a:hover {
                border-bottom-color: #C1272D;
            }

            .desktop-footer {
                background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
                padding: 30px;
                text-align: center;
                border-top: 1px solid #e2e8f0;
            }

            .desktop-footer-content {
                font-size: 14px;
                color: #6b7280;
                line-height: 1.6;
            }

            .desktop-unsubscribe {
                margin-top: 20px;
                padding-top: 20px;
                border-top: 1px solid #d1d5db;
            }

            .desktop-social-links {
                margin: 20px 0;
            }

            .desktop-social-links a {
                display: inline-block;
                margin: 0 10px;
                color: #C1272D;
                text-decoration: none;
                font-size: 20px;
            }

            /* Mobile responsive adjustments */
            @media (max-width: 768px) {
                .desktop-container {
                    margin: 0;
                    border-radius: 0;
                    box-shadow: none;
                }

                .desktop-header {
                    padding: 30px 20px;
                }

                .desktop-logo {
                    font-size: 24px;
                }

                .desktop-subject {
                    font-size: 24px;
                }

                .desktop-content {
                    padding: 25px 20px;
                    font-size: 15px;
                }

                .desktop-footer {
                    padding: 25px 20px;
                }
            }

            /* Mobile-specific styles for mobile preview */
            .mobile-container {
                width: 375px;
                margin: 0 auto;
                background: #ffffff;
                border-radius: 12px;
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
                overflow: hidden;
                font-size: 14px;
            }

            .mobile-header {
                background: linear-gradient(135deg, #C1272D 0%, #991b1b 100%);
                color: white;
                text-align: center;
                padding: 25px 15px;
            }

            .mobile-logo {
                font-size: 20px;
                font-weight: 700;
                margin-bottom: 5px;
            }

            .mobile-tagline {
                font-size: 12px;
                opacity: 0.9;
                font-weight: 300;
            }

            .mobile-subject {
                font-size: 18px;
                font-weight: 600;
                margin: 10px 0 5px 0;
            }

            .mobile-content {
                padding: 20px 15px;
                font-size: 14px;
                line-height: 1.6;
            }

            .mobile-content h1, .mobile-content h2, .mobile-content h3 {
                color: #C1272D;
                margin: 20px 0 10px 0;
                font-weight: 600;
                font-size: 1.2em;
            }

            .mobile-content p {
                margin-bottom: 15px;
                color: #374151;
            }

            .mobile-content ul, .mobile-content ol {
                margin: 15px 0;
                padding-left: 20px;
            }

            .mobile-content li {
                margin-bottom: 5px;
                color: #374151;
            }

            .mobile-footer {
                background: #f8fafc;
                padding: 20px 15px;
                text-align: center;
                border-top: 1px solid #e2e8f0;
            }

            .mobile-footer-content {
                font-size: 12px;
                color: #6b7280;
                line-height: 1.5;
            }

            /* Email client compatibility */
            .email-client-info {
                background: #f3f4f6;
                border: 1px solid #d1d5db;
                border-radius: 8px;
                padding: 15px;
                margin: 20px 0;
                font-size: 13px;
                color: #6b7280;
                text-align: center;
            }

            .email-client-info strong {
                color: #374151;
            }

            /* CTA Button styling */
            .cta-button {
                display: inline-block;
                background: linear-gradient(135deg, #C1272D 0%, #991b1b 100%);
                color: white;
                text-decoration: none;
                padding: 12px 24px;
                border-radius: 8px;
                font-weight: 600;
                margin: 15px 0;
                box-shadow: 0 4px 6px -1px rgba(193, 39, 45, 0.3);
                transition: all 0.3s ease;
            }

            .cta-button:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 15px -3px rgba(193, 39, 45, 0.4);
            }

            /* Image styling */
            .newsletter-image {
                max-width: 100%;
                height: auto;
                border-radius: 8px;
                margin: 15px 0;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            }
        </style>
    </head>
    <body>
        <!-- Desktop Version -->
        <div class='desktop-container'>
            <div class='desktop-header'>
                <div class='desktop-logo'>🍽️ Addins Meals on Wheels</div>
                <div class='desktop-tagline'>Delicious Food Delivered Fresh</div>
                <h1 class='desktop-subject'>{{SUBJECT}}</h1>
            </div>
            <div class='desktop-content'>
                {{CONTENT}}
            </div>
            <div class='desktop-footer'>
                <div class='desktop-footer-content'>
                    <p><strong>Thank you for choosing Addins Meals on Wheels!</strong></p>
                    <p>We appreciate your business and look forward to serving you again.</p>

                    <div class='desktop-social-links'>
                        <a href='#' title='Facebook'><i class='fab fa-facebook'></i></a>
                        <a href='#' title='Instagram'><i class='fab fa-instagram'></i></a>
                        <a href='#' title='Twitter'><i class='fab fa-twitter'></i></a>
                    </div>

                    <div class='desktop-unsubscribe'>
                        <p><a href='{{UNSUBSCRIBE_URL}}' style='color: #C1272D;'>Unsubscribe</a> | <a href='{{WEBSITE_URL}}'>Visit Website</a></p>
                        <p>&copy; {{YEAR}} Addins Meals on Wheels. All rights reserved.</p>
                        <p>📞 +254 112 855 900 | 📧 info@addinsmeals.com</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile Version (for mobile preview) -->
        <div class='mobile-container'>
            <div class='mobile-header'>
                <div class='mobile-logo'>🍽️ Addins</div>
                <div class='mobile-tagline'>Fresh Food Delivery</div>
                <h1 class='mobile-subject'>{{SUBJECT}}</h1>
            </div>
            <div class='mobile-content'>
                {{CONTENT}}
            </div>
            <div class='mobile-footer'>
                <div class='mobile-footer-content'>
                    <p><strong>Thank you for choosing us!</strong></p>
                    <p><a href='{{UNSUBSCRIBE_URL}}' style='color: #C1272D;'>Unsubscribe</a></p>
                    <p>&copy; {{YEAR}} Addins Meals</p>
                </div>
            </div>
        </div>

        <!-- Email Client Compatibility Notice -->
        <div class='email-client-info'>
            <p><strong>Newsletter Review Mode</strong></p>
            <p>This review shows how your newsletter will appear in email clients like Gmail, Outlook, and mobile email apps.</p>
        </div>
    </body>
    </html>
    ";
}
?>
