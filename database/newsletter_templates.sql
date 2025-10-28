-- =====================================================
-- Professional Newsletter Templates for Addins Meals on Wheels
-- =====================================================
-- This file contains 3 professional newsletter templates:
-- 1. Modern Promotional Template (Special Offers & Deals)
-- 2. Clean Announcement Template (News & Updates)
-- 3. Elegant Event Template (Events & Celebrations)
-- =====================================================

-- Insert Template 1: Modern Promotional Template
INSERT INTO `newsletter_templates` (`name`, `description`, `html_template`, `is_active`) VALUES
('Modern Promotional', 'Eye-catching template perfect for special offers, discounts, and promotional campaigns', '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{SUBJECT}}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; line-height: 1.6; color: #333; background: #f5f5f5; }
        .email-wrapper { max-width: 650px; margin: 0 auto; background: #ffffff; }
        .promo-header { background: linear-gradient(135deg, #C1272D 0%, #8B1F23 100%); padding: 40px 30px; text-align: center; position: relative; overflow: hidden; }
        .promo-header::before { content: ""; position: absolute; top: -50%; right: -10%; width: 300px; height: 300px; background: rgba(255,255,255,0.1); border-radius: 50%; }
        .promo-header::after { content: ""; position: absolute; bottom: -30%; left: -5%; width: 200px; height: 200px; background: rgba(255,255,255,0.08); border-radius: 50%; }
        .logo-badge { background: rgba(255,255,255,0.15); width: 80px; height: 80px; border-radius: 50%; margin: 0 auto 20px; display: flex; align-items: center; justify-content: center; font-size: 36px; position: relative; z-index: 1; }
        .promo-title { color: #ffffff; font-size: 32px; font-weight: 700; margin-bottom: 10px; position: relative; z-index: 1; text-shadow: 0 2px 4px rgba(0,0,0,0.2); }
        .promo-subtitle { color: rgba(255,255,255,0.95); font-size: 16px; position: relative; z-index: 1; }
        .offer-banner { background: #FFD700; color: #8B1F23; text-align: center; padding: 15px; font-size: 18px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .content-section { padding: 40px 30px; }
        .content-section h2 { color: #C1272D; font-size: 24px; margin-bottom: 15px; }
        .content-section p { color: #555; margin-bottom: 20px; font-size: 16px; line-height: 1.8; }
        .cta-container { text-align: center; margin: 35px 0; }
        .cta-button { display: inline-block; background: linear-gradient(135deg, #C1272D 0%, #8B1F23 100%); color: #ffffff; padding: 16px 40px; text-decoration: none; border-radius: 50px; font-weight: 700; font-size: 16px; text-transform: uppercase; letter-spacing: 0.5px; box-shadow: 0 6px 20px rgba(193, 39, 45, 0.4); transition: all 0.3s ease; }
        .cta-button:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(193, 39, 45, 0.5); }
        .features-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 20px; margin: 30px 0; }
        .feature-box { background: #f8f9fa; padding: 20px; border-radius: 10px; text-align: center; border: 2px solid #e9ecef; }
        .feature-icon { font-size: 32px; margin-bottom: 10px; }
        .feature-title { font-weight: 600; color: #C1272D; margin-bottom: 5px; }
        .feature-text { font-size: 14px; color: #666; }
        .promo-footer { background: #2c3e50; color: #ffffff; padding: 30px; text-align: center; }
        .social-links { margin: 20px 0; }
        .social-links a { display: inline-block; width: 40px; height: 40px; background: rgba(255,255,255,0.1); border-radius: 50%; margin: 0 8px; line-height: 40px; color: #ffffff; text-decoration: none; transition: all 0.3s; }
        .social-links a:hover { background: #C1272D; transform: translateY(-3px); }
        .footer-text { font-size: 13px; color: #bdc3c7; line-height: 1.6; }
        .footer-text a { color: #FFD700; text-decoration: none; }
        @media (max-width: 600px) {
            .promo-title { font-size: 24px; }
            .content-section { padding: 25px 20px; }
            .features-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="promo-header">
            <div class="logo-badge">🍽️</div>
            <h1 class="promo-title">{{SUBJECT}}</h1>
            <p class="promo-subtitle">Addins Meals on Wheels - Fresh Food Delivered</p>
        </div>
        
        <div class="offer-banner">
            ⚡ Limited Time Offer - Don\'t Miss Out! ⚡
        </div>
        
        <div class="content-section">
            {{CONTENT}}
            
            <div class="cta-container">
                <a href="{{WEBSITE_URL}}" class="cta-button">Order Now</a>
            </div>
            
            <div class="features-grid">
                <div class="feature-box">
                    <div class="feature-icon">🚚</div>
                    <div class="feature-title">Fast Delivery</div>
                    <div class="feature-text">Quick & reliable</div>
                </div>
                <div class="feature-box">
                    <div class="feature-icon">⭐</div>
                    <div class="feature-title">Quality Food</div>
                    <div class="feature-text">Fresh ingredients</div>
                </div>
                <div class="feature-box">
                    <div class="feature-icon">💰</div>
                    <div class="feature-title">Great Prices</div>
                    <div class="feature-text">Best value</div>
                </div>
            </div>
        </div>
        
        <div class="promo-footer">
            <div class="social-links">
                <a href="#">📘</a>
                <a href="#">📷</a>
                <a href="#">🐦</a>
            </div>
            <div class="footer-text">
                <p><strong>Addins Meals on Wheels</strong></p>
                <p>📞 +254 112 855 900 | 📧 info@addinsmeals.com</p>
                <p style="margin-top: 15px;">
                    <a href="{{UNSUBSCRIBE_URL}}">Unsubscribe</a> | 
                    <a href="{{WEBSITE_URL}}">Visit Website</a>
                </p>
                <p style="margin-top: 10px;">&copy; {{YEAR}} Addins Meals on Wheels. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>', 1);

-- Insert Template 2: Clean Announcement Template  
INSERT INTO `newsletter_templates` (`name`, `description`, `html_template`, `is_active`) VALUES
('Clean Announcement', 'Professional template ideal for company news, updates, and general announcements', '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{SUBJECT}}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Georgia, "Times New Roman", serif; line-height: 1.7; color: #2c3e50; background: #ecf0f1; }
        .email-container { max-width: 680px; margin: 30px auto; background: #ffffff; box-shadow: 0 10px 40px rgba(0,0,0,0.1); }
        .announcement-header { background: #ffffff; padding: 40px 40px 30px; border-bottom: 4px solid #C1272D; }
        .brand-section { display: flex; align-items: center; margin-bottom: 25px; }
        .brand-logo { width: 60px; height: 60px; background: linear-gradient(135deg, #C1272D, #8B1F23); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 28px; margin-right: 15px; }
        .brand-name { font-size: 22px; font-weight: 700; color: #2c3e50; }
        .brand-tagline { font-size: 13px; color: #7f8c8d; margin-top: 2px; }
        .announcement-title { font-size: 28px; color: #2c3e50; font-weight: 700; line-height: 1.3; margin-bottom: 10px; }
        .announcement-date { color: #95a5a6; font-size: 14px; font-style: italic; }
        .content-body { padding: 40px; }
        .content-body h2 { color: #C1272D; font-size: 22px; margin: 30px 0 15px; font-weight: 600; }
        .content-body h3 { color: #34495e; font-size: 18px; margin: 25px 0 12px; font-weight: 600; }
        .content-body p { color: #555; margin-bottom: 18px; font-size: 16px; line-height: 1.8; }
        .content-body ul, .content-body ol { margin: 20px 0 20px 25px; color: #555; }
        .content-body li { margin-bottom: 10px; }
        .highlight-box { background: #fff9e6; border-left: 4px solid #FFD700; padding: 20px; margin: 25px 0; border-radius: 4px; }
        .highlight-box p { margin-bottom: 0; color: #7f6b00; }
        .quote-section { background: #f8f9fa; border-left: 5px solid #C1272D; padding: 25px; margin: 30px 0; font-style: italic; color: #555; border-radius: 0 8px 8px 0; }
        .cta-section { text-align: center; margin: 35px 0; padding: 30px; background: #f8f9fa; border-radius: 8px; }
        .cta-button { display: inline-block; background: #C1272D; color: #ffffff; padding: 14px 35px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 15px; transition: all 0.3s; box-shadow: 0 4px 12px rgba(193, 39, 45, 0.3); }
        .cta-button:hover { background: #8B1F23; transform: translateY(-2px); box-shadow: 0 6px 16px rgba(193, 39, 45, 0.4); }
        .announcement-footer { background: #34495e; color: #ecf0f1; padding: 35px 40px; }
        .footer-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 25px; }
        .footer-column h4 { color: #FFD700; font-size: 14px; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 1px; }
        .footer-column p, .footer-column a { color: #bdc3c7; font-size: 13px; line-height: 1.8; text-decoration: none; }
        .footer-column a:hover { color: #FFD700; }
        .footer-bottom { border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px; text-align: center; font-size: 12px; color: #95a5a6; }
        @media (max-width: 600px) {
            .email-container { margin: 0; }
            .announcement-header, .content-body, .announcement-footer { padding: 25px 20px; }
            .footer-grid { grid-template-columns: 1fr; gap: 20px; }
            .announcement-title { font-size: 22px; }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="announcement-header">
            <div class="brand-section">
                <div class="brand-logo">🍽️</div>
                <div>
                    <div class="brand-name">Addins Meals on Wheels</div>
                    <div class="brand-tagline">Fresh Food, Delivered with Care</div>
                </div>
            </div>
            <h1 class="announcement-title">{{SUBJECT}}</h1>
            <p class="announcement-date">Published on ' . date('F j, Y') . '</p>
        </div>
        
        <div class="content-body">
            {{CONTENT}}
            
            <div class="cta-section">
                <p style="margin-bottom: 15px; color: #555;"><strong>Want to learn more?</strong></p>
                <a href="{{WEBSITE_URL}}" class="cta-button">Visit Our Website</a>
            </div>
        </div>
        
        <div class="announcement-footer">
            <div class="footer-grid">
                <div class="footer-column">
                    <h4>Contact Us</h4>
                    <p>📞 +254 112 855 900</p>
                    <p>📧 info@addinsmeals.com</p>
                    <p>🌐 www.addinsmeals.com</p>
                </div>
                <div class="footer-column">
                    <h4>Quick Links</h4>
                    <p><a href="{{WEBSITE_URL}}">Home</a></p>
                    <p><a href="{{WEBSITE_URL}}/menu">Our Menu</a></p>
                    <p><a href="{{UNSUBSCRIBE_URL}}">Unsubscribe</a></p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; {{YEAR}} Addins Meals on Wheels. All rights reserved.</p>
                <p style="margin-top: 8px;">You\'re receiving this because you subscribed to our newsletter.</p>
            </div>
        </div>
    </div>
</body>
</html>', 1);

-- Insert Template 3: Elegant Event Template
INSERT INTO `newsletter_templates` (`name`, `description`, `html_template`, `is_active`) VALUES
('Elegant Event', 'Sophisticated template perfect for special events, celebrations, and exclusive invitations', '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{SUBJECT}}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: "Playfair Display", Georgia, serif; line-height: 1.6; color: #2c2c2c; background: #f5f3f0; }
        .event-wrapper { max-width: 700px; margin: 40px auto; background: #ffffff; box-shadow: 0 15px 50px rgba(0,0,0,0.15); }
        .event-header { background: linear-gradient(135deg, #1a1a1a 0%, #2c2c2c 100%); padding: 50px 40px; text-align: center; position: relative; overflow: hidden; }
        .event-header::before { content: ""; position: absolute; top: -100px; right: -100px; width: 300px; height: 300px; background: radial-gradient(circle, rgba(193, 39, 45, 0.2) 0%, transparent 70%); }
        .event-header::after { content: ""; position: absolute; bottom: -80px; left: -80px; width: 250px; height: 250px; background: radial-gradient(circle, rgba(255, 215, 0, 0.15) 0%, transparent 70%); }
        .event-badge { width: 100px; height: 100px; background: linear-gradient(135deg, #C1272D, #8B1F23); border-radius: 50%; margin: 0 auto 25px; display: flex; align-items: center; justify-content: center; font-size: 45px; position: relative; z-index: 1; box-shadow: 0 8px 20px rgba(193, 39, 45, 0.4); }
        .event-title { color: #ffffff; font-size: 36px; font-weight: 700; margin-bottom: 12px; position: relative; z-index: 1; letter-spacing: 1px; }
        .event-subtitle { color: #FFD700; font-size: 18px; font-weight: 400; position: relative; z-index: 1; font-style: italic; }
        .event-divider { width: 80px; height: 3px; background: linear-gradient(90deg, transparent, #FFD700, transparent); margin: 30px auto; }
        .event-content { padding: 45px 40px; }
        .event-content h2 { color: #C1272D; font-size: 26px; margin: 30px 0 18px; font-weight: 700; text-align: center; }
        .event-content p { color: #4a4a4a; margin-bottom: 20px; font-size: 16px; line-height: 1.9; text-align: justify; }
        .event-details { background: linear-gradient(135deg, #f8f6f4 0%, #faf8f6 100%); border: 2px solid #e8e6e4; border-radius: 12px; padding: 30px; margin: 35px 0; }
        .detail-row { display: flex; align-items: center; margin-bottom: 18px; padding-bottom: 18px; border-bottom: 1px solid #e8e6e4; }
        .detail-row:last-child { margin-bottom: 0; padding-bottom: 0; border-bottom: none; }
        .detail-icon { width: 45px; height: 45px; background: linear-gradient(135deg, #C1272D, #8B1F23); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; margin-right: 18px; flex-shrink: 0; }
        .detail-content { flex: 1; }
        .detail-label { font-size: 12px; color: #888; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px; }
        .detail-value { font-size: 16px; color: #2c2c2c; font-weight: 600; }
        .rsvp-section { text-align: center; margin: 40px 0; padding: 35px; background: linear-gradient(135deg, #1a1a1a 0%, #2c2c2c 100%); border-radius: 12px; }
        .rsvp-text { color: #FFD700; font-size: 18px; margin-bottom: 20px; font-style: italic; }
        .rsvp-button { display: inline-block; background: linear-gradient(135deg, #FFD700, #FFA500); color: #1a1a1a; padding: 16px 45px; text-decoration: none; border-radius: 50px; font-weight: 700; font-size: 16px; text-transform: uppercase; letter-spacing: 1.5px; box-shadow: 0 8px 25px rgba(255, 215, 0, 0.4); transition: all 0.3s; }
        .rsvp-button:hover { transform: translateY(-3px); box-shadow: 0 12px 30px rgba(255, 215, 0, 0.5); }
        .event-footer { background: #1a1a1a; color: #c4c4c4; padding: 40px; text-align: center; }
        .footer-logo { font-size: 32px; margin-bottom: 15px; }
        .footer-brand { color: #FFD700; font-size: 20px; font-weight: 700; margin-bottom: 8px; }
        .footer-contact { font-size: 14px; line-height: 1.8; margin: 20px 0; }
        .footer-contact a { color: #FFD700; text-decoration: none; }
        .footer-social { margin: 25px 0; }
        .footer-social a { display: inline-block; width: 45px; height: 45px; background: rgba(255,255,255,0.1); border-radius: 50%; margin: 0 8px; line-height: 45px; color: #FFD700; text-decoration: none; font-size: 18px; transition: all 0.3s; }
        .footer-social a:hover { background: #C1272D; color: #ffffff; transform: translateY(-3px); }
        .footer-legal { font-size: 12px; color: #888; margin-top: 25px; padding-top: 25px; border-top: 1px solid rgba(255,255,255,0.1); }
        .footer-legal a { color: #FFD700; text-decoration: none; }
        @media (max-width: 600px) {
            .event-wrapper { margin: 0; }
            .event-header, .event-content, .event-footer { padding: 30px 20px; }
            .event-title { font-size: 28px; }
            .event-details { padding: 20px; }
            .detail-row { flex-direction: column; text-align: center; }
            .detail-icon { margin: 0 auto 10px; }
        }
    </style>
</head>
<body>
    <div class="event-wrapper">
        <div class="event-header">
            <div class="event-badge">🎉</div>
            <h1 class="event-title">{{SUBJECT}}</h1>
            <p class="event-subtitle">You\'re Cordially Invited</p>
            <div class="event-divider"></div>
        </div>
        
        <div class="event-content">
            {{CONTENT}}
            
            <div class="event-details">
                <div class="detail-row">
                    <div class="detail-icon">📅</div>
                    <div class="detail-content">
                        <div class="detail-label">Date</div>
                        <div class="detail-value">To Be Announced</div>
                    </div>
                </div>
                <div class="detail-row">
                    <div class="detail-icon">⏰</div>
                    <div class="detail-content">
                        <div class="detail-label">Time</div>
                        <div class="detail-value">Check Event Details</div>
                    </div>
                </div>
                <div class="detail-row">
                    <div class="detail-icon">📍</div>
                    <div class="detail-content">
                        <div class="detail-label">Location</div>
                        <div class="detail-value">Addins Meals on Wheels</div>
                    </div>
                </div>
            </div>
            
            <div class="rsvp-section">
                <p class="rsvp-text">Reserve Your Spot Today</p>
                <a href="{{WEBSITE_URL}}" class="rsvp-button">RSVP Now</a>
            </div>
        </div>
        
        <div class="event-footer">
            <div class="footer-logo">🍽️</div>
            <div class="footer-brand">Addins Meals on Wheels</div>
            <div class="footer-contact">
                <p>📞 <a href="tel:+254112855900">+254 112 855 900</a></p>
                <p>📧 <a href="mailto:info@addinsmeals.com">info@addinsmeals.com</a></p>
            </div>
            <div class="footer-social">
                <a href="#">📘</a>
                <a href="#">📷</a>
                <a href="#">🐦</a>
                <a href="#">📺</a>
            </div>
            <div class="footer-legal">
                <p>&copy; {{YEAR}} Addins Meals on Wheels. All rights reserved.</p>
                <p style="margin-top: 10px;">
                    <a href="{{UNSUBSCRIBE_URL}}">Unsubscribe</a> | 
                    <a href="{{WEBSITE_URL}}">Visit Website</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>', 1);
