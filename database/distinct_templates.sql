-- =====================================================
-- DISTINCT Professional Newsletter Templates for Addins Meals on Wheels
-- =====================================================
-- 3 Completely Different Visual Styles:
-- 1. Bold Promotional (Bright, energetic, sales-focused)
-- 2. Minimalist News (Clean, professional, content-focused)
-- 3. Premium Event (Elegant, sophisticated, invitation-style)
-- =====================================================

-- Template 1: Bold Promotional (Bright Orange/Blue Theme)
INSERT INTO `newsletter_templates` (`name`, `description`, `html_template`, `is_active`) VALUES
('Bold Promotional', 'Energetic template with bright colors perfect for flash sales, special offers, and promotional campaigns', '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{SUBJECT}}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: "Poppins", -apple-system, BlinkMacSystemFont, sans-serif; line-height: 1.6; color: #1a1a1a; background: #f0f8ff; }
        .promo-wrapper { max-width: 620px; margin: 0 auto; background: #ffffff; border-radius: 20px; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.1); }
        .promo-header { background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%); padding: 50px 35px; text-align: center; position: relative; }
        .promo-header::before { content: "⚡"; position: absolute; top: 20px; right: 20px; font-size: 40px; opacity: 0.3; }
        .promo-header::after { content: "💥"; position: absolute; bottom: 20px; left: 20px; font-size: 35px; opacity: 0.3; }
        .promo-badge { background: #FFE135; color: #1a1a1a; font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; padding: 8px 20px; border-radius: 25px; display: inline-block; margin-bottom: 20px; box-shadow: 0 4px 12px rgba(255,225,53,0.4); }
        .promo-title { color: #ffffff; font-size: 38px; font-weight: 800; margin-bottom: 12px; text-shadow: 0 3px 6px rgba(0,0,0,0.3); line-height: 1.2; }
        .promo-subtitle { color: rgba(255,255,255,0.95); font-size: 18px; font-weight: 500; }
        .sale-banner { background: linear-gradient(90deg, #FFE135, #FFD700); color: #1a1a1a; text-align: center; padding: 20px; font-size: 24px; font-weight: 800; text-transform: uppercase; letter-spacing: 2px; margin: 35px 0; border-radius: 15px; box-shadow: 0 6px 20px rgba(255,215,0,0.4); }
        .content-area { padding: 45px 35px; background: #f8f9fa; }
        .content-area h2 { color: #FF6B35; font-size: 26px; margin: 25px 0 15px; font-weight: 700; }
        .content-area p { color: #2c3e50; margin-bottom: 18px; font-size: 16px; line-height: 1.8; }
        .highlight-box { background: linear-gradient(135deg, #4A90E2, #357ABD); color: #ffffff; padding: 25px; margin: 25px 0; border-radius: 15px; box-shadow: 0 8px 25px rgba(74,144,226,0.3); }
        .highlight-box h3 { color: #FFE135; font-size: 20px; margin-bottom: 10px; }
        .discount-circles { display: flex; justify-content: space-around; margin: 30px 0; }
        .discount-circle { width: 100px; height: 100px; background: linear-gradient(135deg, #FF6B35, #F7931E); border-radius: 50%; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #ffffff; font-weight: 700; box-shadow: 0 8px 20px rgba(255,107,53,0.4); }
        .discount-number { font-size: 24px; line-height: 1; }
        .discount-text { font-size: 10px; text-transform: uppercase; }
        .cta-primary { display: inline-block; background: linear-gradient(135deg, #4A90E2, #357ABD); color: #ffffff; padding: 18px 50px; text-decoration: none; border-radius: 50px; font-weight: 800; font-size: 18px; text-transform: uppercase; letter-spacing: 1px; box-shadow: 0 8px 25px rgba(74,144,226,0.4); transition: all 0.3s; margin: 20px 10px 20px 0; }
        .cta-primary:hover { transform: translateY(-3px); box-shadow: 0 12px 35px rgba(74,144,226,0.5); }
        .cta-secondary { display: inline-block; background: linear-gradient(135deg, #FF6B35, #F7931E); color: #ffffff; padding: 18px 50px; text-decoration: none; border-radius: 50px; font-weight: 800; font-size: 18px; text-transform: uppercase; letter-spacing: 1px; box-shadow: 0 8px 25px rgba(255,107,53,0.4); transition: all 0.3s; }
        .cta-secondary:hover { transform: translateY(-3px); box-shadow: 0 12px 35px rgba(255,107,53,0.5); }
        .cta-container { text-align: center; margin: 40px 0; }
        .features-list { background: #ffffff; padding: 25px; border-radius: 15px; margin: 30px 0; border: 2px solid #e9ecef; }
        .feature-item { display: flex; align-items: center; margin-bottom: 15px; }
        .feature-icon { width: 40px; height: 40px; background: linear-gradient(135deg, #4A90E2, #357ABD); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px; margin-right: 15px; }
        .feature-text { font-weight: 600; color: #2c3e50; }
        .urgency-banner { background: linear-gradient(90deg, #FF4757, #FF3838); color: #ffffff; text-align: center; padding: 15px; font-size: 16px; font-weight: 700; margin: 25px 0; border-radius: 10px; }
        .promo-footer { background: linear-gradient(135deg, #2c3e50, #34495e); color: #ffffff; padding: 35px; text-align: center; }
        .footer-brand { font-size: 20px; font-weight: 700; margin-bottom: 15px; }
        .footer-contact { font-size: 14px; line-height: 1.8; margin: 20px 0; }
        .footer-contact a { color: #4A90E2; text-decoration: none; }
        .footer-social { margin: 20px 0; }
        .footer-social a { display: inline-block; width: 40px; height: 40px; background: rgba(255,255,255,0.1); border-radius: 50%; margin: 0 8px; line-height: 40px; color: #ffffff; text-decoration: none; font-size: 18px; transition: all 0.3s; }
        .footer-social a:hover { background: #FF6B35; transform: translateY(-3px); }
        @media (max-width: 600px) {
            .promo-title { font-size: 28px; }
            .content-area { padding: 30px 20px; }
            .discount-circles { flex-direction: column; align-items: center; }
            .discount-circle { margin-bottom: 15px; }
            .cta-primary, .cta-secondary { display: block; margin: 10px 0; }
        }
    </style>
</head>
<body>
    <div class="promo-wrapper">
        <div class="promo-header">
            <div class="promo-badge">🔥 Flash Sale</div>
            <h1 class="promo-title">{{SUBJECT}}</h1>
            <p class="promo-subtitle">Don\'t Miss These Amazing Deals!</p>
        </div>

        <div class="sale-banner">
            🔥 UP TO 50% OFF 🔥
        </div>

        <div class="content-area">
            {{CONTENT}}

            <div class="urgency-banner">
                ⏰ Limited Time Only - Offer Ends Soon!
            </div>

            <div class="cta-container">
                <a href="{{WEBSITE_URL}}" class="cta-primary">Shop Now</a>
                <a href="{{WEBSITE_URL}}/menu" class="cta-secondary">View Menu</a>
            </div>

            <div class="features-list">
                <div class="feature-item">
                    <div class="feature-icon">🚚</div>
                    <div class="feature-text">Free Delivery Available</div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">⭐</div>
                    <div class="feature-text">Premium Quality Guaranteed</div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">💰</div>
                    <div class="feature-text">Best Prices in Town</div>
                </div>
            </div>
        </div>

        <div class="promo-footer">
            <div class="footer-brand">🍽️ Addins Meals on Wheels</div>
            <div class="footer-contact">
                <p>📞 <a href="tel:+254112855900">+254 112 855 900</a></p>
                <p>📧 <a href="mailto:info@addinsmeals.com">info@addinsmeals.com</a></p>
            </div>
            <div class="footer-social">
                <a href="#">📘</a>
                <a href="#">📷</a>
                <a href="#">🐦</a>
                <a href="#">📱</a>
            </div>
            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); font-size: 12px; color: #bdc3c7;">
                <p><a href="{{UNSUBSCRIBE_URL}}" style="color: #4A90E2;">Unsubscribe</a> | <a href="{{WEBSITE_URL}}" style="color: #4A90E2;">Visit Website</a></p>
                <p style="margin-top: 8px;">&copy; {{YEAR}} Addins Meals on Wheels. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>', 1);

-- Template 2: Minimalist News (Clean Black/White Theme)
INSERT INTO `newsletter_templates` (`name`, `description`, `html_template`, `is_active`) VALUES
('Minimalist News', 'Ultra-clean template with minimal design perfect for company news, updates, and professional announcements', '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{SUBJECT}}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: "Inter", -apple-system, BlinkMacSystemFont, sans-serif; line-height: 1.7; color: #1a1a1a; background: #fafafa; }
        .news-container { max-width: 600px; margin: 0 auto; background: #ffffff; box-shadow: 0 2px 20px rgba(0,0,0,0.08); }
        .news-header { background: #1a1a1a; color: #ffffff; padding: 40px 30px; text-align: center; }
        .news-logo { font-size: 28px; font-weight: 300; margin-bottom: 8px; letter-spacing: -0.5px; }
        .news-tagline { font-size: 12px; text-transform: uppercase; letter-spacing: 2px; opacity: 0.8; }
        .news-title { font-size: 24px; font-weight: 400; margin-top: 20px; line-height: 1.4; }
        .news-date { font-size: 13px; opacity: 0.7; margin-top: 15px; }
        .news-content { padding: 40px 30px; }
        .news-content h2 { color: #1a1a1a; font-size: 20px; margin: 30px 0 15px; font-weight: 600; border-bottom: 2px solid #000; padding-bottom: 8px; }
        .news-content h3 { color: #333; font-size: 16px; margin: 25px 0 12px; font-weight: 600; }
        .news-content p { color: #444; margin-bottom: 16px; font-size: 15px; line-height: 1.8; }
        .news-content ul, .news-content ol { margin: 20px 0 20px 25px; color: #444; }
        .news-content li { margin-bottom: 8px; }
        .quote-block { background: #f8f8f8; border-left: 4px solid #1a1a1a; padding: 20px 25px; margin: 25px 0; font-style: italic; color: #333; font-size: 16px; }
        .image-placeholder { background: #f0f0f0; border: 2px dashed #ddd; padding: 40px; text-align: center; margin: 25px 0; color: #888; font-size: 14px; }
        .divider { height: 1px; background: #e5e5e5; margin: 30px 0; }
        .highlight-stat { background: #1a1a1a; color: #ffffff; padding: 15px; margin: 20px 0; border-radius: 4px; text-align: center; }
        .highlight-stat .stat-number { font-size: 24px; font-weight: 700; display: block; }
        .highlight-stat .stat-label { font-size: 12px; text-transform: uppercase; letter-spacing: 1px; opacity: 0.8; }
        .cta-minimal { display: inline-block; background: #1a1a1a; color: #ffffff; padding: 12px 30px; text-decoration: none; border-radius: 4px; font-weight: 500; font-size: 14px; transition: all 0.3s; margin: 20px 10px 20px 0; }
        .cta-minimal:hover { background: #333; transform: translateY(-1px); }
        .news-footer { background: #fafafa; padding: 30px; border-top: 1px solid #e5e5e5; }
        .footer-info { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 20px; }
        .footer-section h4 { color: #1a1a1a; font-size: 12px; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 1px; font-weight: 600; }
        .footer-section p, .footer-section a { color: #666; font-size: 13px; line-height: 1.6; text-decoration: none; }
        .footer-section a:hover { color: #1a1a1a; }
        .footer-bottom { text-align: center; font-size: 11px; color: #999; padding-top: 20px; border-top: 1px solid #e5e5e5; }
        @media (max-width: 600px) {
            .news-header, .news-content, .news-footer { padding: 25px 20px; }
            .news-title { font-size: 20px; }
            .footer-info { grid-template-columns: 1fr; gap: 15px; }
        }
    </style>
</head>
<body>
    <div class="news-container">
        <div class="news-header">
            <div class="news-logo">Addins Meals</div>
            <div class="news-tagline">Company Updates</div>
            <h1 class="news-title">{{SUBJECT}}</h1>
            <p class="news-date">Published on ' . date('F j, Y') . '</p>
        </div>

        <div class="news-content">
            {{CONTENT}}

            <div class="divider"></div>

            <div style="text-align: center; margin: 30px 0;">
                <a href="{{WEBSITE_URL}}" class="cta-minimal">Read Full Article</a>
                <a href="{{WEBSITE_URL}}/menu" class="cta-minimal">View Menu</a>
            </div>
        </div>

        <div class="news-footer">
            <div class="footer-info">
                <div class="footer-section">
                    <h4>Contact</h4>
                    <p>📞 +254 112 855 900</p>
                    <p>📧 info@addinsmeals.com</p>
                    <p>🌐 www.addinsmeals.com</p>
                </div>
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <p><a href="{{WEBSITE_URL}}">Home</a></p>
                    <p><a href="{{WEBSITE_URL}}/menu">Menu</a></p>
                    <p><a href="{{UNSUBSCRIBE_URL}}">Unsubscribe</a></p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; {{YEAR}} Addins Meals on Wheels. All rights reserved.</p>
                <p style="margin-top: 5px;">You\'re receiving this newsletter because you subscribed to our updates.</p>
            </div>
        </div>
    </div>
</body>
</html>', 1);

-- Template 3: Premium Event (Gold/Black Luxury Theme)
INSERT INTO `newsletter_templates` (`name`, `description`, `html_template`, `is_active`) VALUES
('Premium Event', 'Luxurious invitation-style template with gold accents perfect for VIP events, celebrations, and exclusive gatherings', '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{SUBJECT}}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: "Playfair Display", Georgia, serif; line-height: 1.8; color: #1a1a1a; background: linear-gradient(135deg, #0f0f0f 0%, #1a1a1a 100%); }
        .event-container { max-width: 650px; margin: 50px auto; background: linear-gradient(135deg, #1a1a1a 0%, #0f0f0f 100%); border-radius: 20px; overflow: hidden; box-shadow: 0 25px 80px rgba(0,0,0,0.5); border: 2px solid rgba(255,215,0,0.3); }
        .event-header { background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%); padding: 60px 40px; text-align: center; position: relative; }
        .event-header::before { content: ""; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: radial-gradient(circle at center, rgba(255,215,0,0.1) 0%, transparent 70%); }
        .event-emblem { width: 120px; height: 120px; background: linear-gradient(135deg, #FFD700, #FFA500); border-radius: 50%; margin: 0 auto 30px; display: flex; align-items: center; justify-content: center; font-size: 50px; box-shadow: 0 10px 30px rgba(255,215,0,0.4); border: 4px solid rgba(255,255,255,0.2); }
        .event-title { color: #FFD700; font-size: 42px; font-weight: 700; margin-bottom: 15px; letter-spacing: 2px; text-shadow: 0 4px 8px rgba(0,0,0,0.5); }
        .event-subtitle { color: rgba(255,215,0,0.8); font-size: 20px; font-weight: 400; font-style: italic; text-shadow: 0 2px 4px rgba(0,0,0,0.5); }
        .event-decoration { width: 100px; height: 2px; background: linear-gradient(90deg, transparent, #FFD700, transparent); margin: 25px auto; }
        .event-content { padding: 50px 40px; background: linear-gradient(135deg, #1a1a1a 0%, #0f0f0f 100%); }
        .event-content h2 { color: #FFD700; font-size: 28px; margin: 35px 0 20px; font-weight: 700; text-align: center; border-bottom: 2px solid rgba(255,215,0,0.3); padding-bottom: 10px; }
        .event-content p { color: rgba(255,255,255,0.9); margin-bottom: 20px; font-size: 16px; line-height: 1.9; text-align: justify; }
        .invitation-card { background: linear-gradient(135deg, rgba(255,215,0,0.1) 0%, rgba(255,215,0,0.05) 100%); border: 2px solid rgba(255,215,0,0.3); border-radius: 15px; padding: 35px; margin: 40px 0; backdrop-filter: blur(10px); }
        .invitation-text { font-size: 18px; color: #FFD700; text-align: center; margin-bottom: 25px; font-style: italic; }
        .event-details-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 25px; margin: 30px 0; }
        .event-detail-card { background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.05) 100%); border: 1px solid rgba(255,215,0,0.2); border-radius: 12px; padding: 25px; text-align: center; backdrop-filter: blur(5px); }
        .detail-emoji { font-size: 30px; margin-bottom: 10px; }
        .detail-label { color: #FFD700; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; opacity: 0.8; }
        .detail-value { color: #ffffff; font-size: 16px; font-weight: 600; }
        .rsvp-section { text-align: center; margin: 45px 0; padding: 40px; background: linear-gradient(135deg, rgba(255,215,0,0.15) 0%, rgba(255,215,0,0.08) 100%); border-radius: 15px; border: 2px solid rgba(255,215,0,0.3); }
        .rsvp-title { color: #FFD700; font-size: 24px; margin-bottom: 15px; font-weight: 700; }
        .rsvp-text { color: rgba(255,255,255,0.9); font-size: 16px; margin-bottom: 25px; }
        .rsvp-button { display: inline-block; background: linear-gradient(135deg, #FFD700, #FFA500); color: #1a1a1a; padding: 20px 60px; text-decoration: none; border-radius: 50px; font-weight: 800; font-size: 18px; text-transform: uppercase; letter-spacing: 2px; box-shadow: 0 10px 30px rgba(255,215,0,0.5); transition: all 0.3s; border: 3px solid rgba(255,255,255,0.2); }
        .rsvp-button:hover { transform: translateY(-4px); box-shadow: 0 15px 40px rgba(255,215,0,0.6); border-color: rgba(255,215,0,0.5); }
        .premium-footer { background: linear-gradient(135deg, #0f0f0f 0%, #1a1a1a 100%); padding: 45px 40px; text-align: center; border-top: 2px solid rgba(255,215,0,0.3); }
        .footer-emblem { font-size: 40px; margin-bottom: 20px; opacity: 0.7; }
        .footer-brand { color: #FFD700; font-size: 24px; font-weight: 700; margin-bottom: 10px; letter-spacing: 1px; }
        .footer-subtitle { color: rgba(255,215,0,0.7); font-size: 14px; margin-bottom: 20px; font-style: italic; }
        .footer-contact { color: rgba(255,255,255,0.8); font-size: 14px; line-height: 1.8; margin: 25px 0; }
        .footer-contact a { color: #FFD700; text-decoration: none; font-weight: 600; }
        .footer-social { margin: 25px 0; }
        .footer-social a { display: inline-block; width: 50px; height: 50px; background: rgba(255,215,0,0.1); border: 2px solid rgba(255,215,0,0.3); border-radius: 50%; margin: 0 10px; line-height: 46px; color: #FFD700; text-decoration: none; font-size: 20px; transition: all 0.3s; }
        .footer-social a:hover { background: #FFD700; color: #1a1a1a; transform: translateY(-3px); border-color: #FFD700; }
        .footer-legal { margin-top: 30px; padding-top: 30px; border-top: 1px solid rgba(255,215,0,0.2); font-size: 12px; color: rgba(255,255,255,0.6); }
        .footer-legal a { color: #FFD700; text-decoration: none; }
        @media (max-width: 600px) {
            .event-container { margin: 0; border-radius: 0; }
            .event-header, .event-content, .premium-footer { padding: 35px 20px; }
            .event-title { font-size: 32px; }
            .event-details-grid { grid-template-columns: 1fr; }
            .rsvp-button { padding: 15px 40px; font-size: 16px; }
        }
    </style>
</head>
<body>
    <div class="event-container">
        <div class="event-header">
            <div class="event-emblem">👑</div>
            <h1 class="event-title">{{SUBJECT}}</h1>
            <p class="event-subtitle">Exclusive VIP Invitation</p>
            <div class="event-decoration"></div>
        </div>

        <div class="event-content">
            <div class="invitation-card">
                <p class="invitation-text">You are cordially invited to join us for an unforgettable experience</p>
            </div>

            {{CONTENT}}

            <div class="event-details-grid">
                <div class="event-detail-card">
                    <div class="detail-emoji">📅</div>
                    <div class="detail-label">Date</div>
                    <div class="detail-value">To Be Announced</div>
                </div>
                <div class="event-detail-card">
                    <div class="detail-emoji">⏰</div>
                    <div class="detail-label">Time</div>
                    <div class="detail-value">Check Details</div>
                </div>
                <div class="event-detail-card">
                    <div class="detail-emoji">📍</div>
                    <div class="detail-label">Venue</div>
                    <div class="detail-value">Addins Premium</div>
                </div>
            </div>

            <div class="rsvp-section">
                <h3 class="rsvp-title">Reserve Your Place</h3>
                <p class="rsvp-text">Limited seats available for this exclusive event</p>
                <a href="{{WEBSITE_URL}}" class="rsvp-button">RSVP Now</a>
            </div>
        </div>

        <div class="premium-footer">
            <div class="footer-emblem">🍽️</div>
            <div class="footer-brand">Addins Meals on Wheels</div>
            <div class="footer-subtitle">Premium Dining Experience</div>
            <div class="footer-contact">
                <p>📞 <a href="tel:+254112855900">+254 112 855 900</a></p>
                <p>📧 <a href="mailto:vip@addinsmeals.com">vip@addinsmeals.com</a></p>
                <p>🌐 <a href="{{WEBSITE_URL}}">www.addinsmeals.com</a></p>
            </div>
            <div class="footer-social">
                <a href="#">📘</a>
                <a href="#">📷</a>
                <a href="#">🐦</a>
                <a href="#">💎</a>
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
