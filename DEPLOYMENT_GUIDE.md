# 🚀 EXAMs LMS - Deployment & Setup Guide

## Quick Start Guide

### 1. Environment Setup

```bash
# Clone/download project
cd /path/to/EXAMs

# Set permissions
chmod 755 /api /student /admin /includes
chmod 644 *.php *.sql

# Configure environment
cp config.example.php config.php
```

### 2. Database Setup

```sql
-- Import schema
mysql -u root -p < database.sql

-- Verify tables
SHOW TABLES;
SHOW CREATE TABLE users;
```

### 3. Configuration

Edit `config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'exams_db');
define('STRIPE_SECRET_KEY', 'sk_live_...');
define('STRIPE_PUBLIC_KEY', 'pk_live_...');
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'app-password');
```

### 4. Create Admin User

```php
<?php
require_once 'config.php';

$email = 'admin@example.com';
$password = password_hash('SecurePassword123', PASSWORD_BCRYPT);

$stmt = $pdo->prepare("
    INSERT INTO users (full_name, email, password, role, is_verified, created_at)
    VALUES (?, ?, ?, 'superadmin', 1, NOW())
");
$stmt->execute(['Admin User', $email, $password]);
echo "Admin created!";
?>
```

### 5. Start Development Server

```bash
# PHP built-in server
php -S localhost:8000

# Access: http://localhost:8000

# Or use XAMPP/WAMP/LAMP
# Place in htdocs/ and access: http://localhost/EXAMs
```

### 6. Default Access Points

| Role | URL | Username | Password |
|------|-----|----------|----------|
| **Admin** | `/admin/` | admin@example.com | SecurePassword123 |
| **Student** | `/student/` | (self-register) | (self-set) |
| **Moderator** | `/moderator/` | (admin assigned) | (admin set) |
| **SuperAdmin** | `/admin/` | (system) | (system) |

---

## 📁 Project Structure Overview

```
EXAMs/
├── /admin/                    # Admin dashboard & management
│   ├── index.php             # Admin dashboard
│   ├── users.php             # User management
│   ├── exams.php             # Exam management
│   ├── analytics_dashboard.php # Advanced analytics
│   └── ...
│
├── /student/                 # Student interface
│   ├── index.php             # Student dashboard
│   ├── exams.php             # Take exams
│   ├── results.php           # View results
│   ├── ai_recommendations.php # AI recommendations
│   ├── video_streaming.php   # Video player
│   ├── realtime_chat.php     # Chat interface
│   ├── course_timeline.php   # Learning timeline
│   ├── subscription.php      # Premium subscription
│   └── ...
│
├── /api/                     # RESTful APIs
│   ├── /analytics/
│   │   └── get_stats.php     # Analytics data
│   ├── /ai/
│   │   └── weak_topics.php   # AI recommendations
│   ├── /chat/
│   │   ├── get_conversations.php
│   │   ├── get_messages.php
│   │   ├── send_message.php
│   │   └── get_notifications.php
│   ├── /payment/
│   │   ├── create_checkout.php
│   │   └── get_billing_history.php
│   ├── /timeline/
│   │   ├── get_timeline.php
│   │   ├── get_stats.php
│   │   ├── get_course_progress.php
│   │   ├── get_skills.php
│   │   └── get_achievements.php
│   ├── /videos/
│   │   ├── get_videos.php
│   │   ├── get_watch_history.php
│   │   ├── get_playlists.php
│   │   └── log_watch.php
│   └── ...
│
├── /includes/                # Shared components
│   ├── header.php           # Top navigation
│   ├── sidebar.php          # Side menu
│   ├── footer.php           # Footer
│   ├── auth_functions.php   # Auth helpers
│   └── db.php               # Database functions
│
├── /assets/
│   ├── /css/
│   │   ├── dark-mode.css     # Dark mode styles
│   │   ├── analytics.css     # Analytics styles
│   │   └── style.css         # Global styles
│   ├── /js/
│   │   ├── dark-mode.js      # Theme manager
│   │   ├── analytics.js      # Analytics charts
│   │   ├── ai-recommendations.js
│   │   ├── video-streaming.js
│   │   ├── realtime-chat.js
│   │   ├── course-timeline.js
│   │   └── app.js            # Global JavaScript
│   └── /images/
│
├── /community/               # Community forum
├── /moderator/               # Moderator dashboard
│
├── config.php               # Configuration file
├── database.sql             # Database schema
├── login.php                # Login page
├── register.php             # Registration page
├── index.php                # Landing page
│
├── DEVELOPMENT_TRACKER.md   # Progress tracking
├── PROJECT_COMPLETION_REPORT.md # Final report
├── DEPLOYMENT_GUIDE.md      # This file
└── README.md                # Project README
```

---

## 🔧 API Endpoints Reference

### Analytics
- `GET /api/analytics/get_stats.php` - Dashboard statistics

### AI Recommendations
- `GET /api/ai/weak_topics.php` - Weak topics
- `GET /api/ai/next_steps.php` - Learning path
- `GET /api/ai/recommended_materials.php` - Material suggestions
- `GET /api/ai/study_groups.php` - Study groups

### Chat
- `GET /api/chat/get_conversations.php` - List conversations
- `GET /api/chat/get_messages.php?conversation_id=X` - Get messages
- `POST /api/chat/send_message.php` - Send message
- `GET /api/chat/get_notifications.php` - Get notifications

### Videos
- `GET /api/videos/get_videos.php` - List videos
- `GET /api/videos/get_watch_history.php` - Watch history
- `GET /api/videos/get_playlists.php` - User playlists
- `POST /api/videos/log_watch.php` - Log watch event

### Timeline
- `GET /api/timeline/get_timeline.php` - Timeline events
- `GET /api/timeline/get_stats.php` - Learning stats
- `GET /api/timeline/get_course_progress.php` - Course progress
- `GET /api/timeline/get_skills.php` - Skill levels
- `GET /api/timeline/get_achievements.php` - Achievements

### Payment
- `POST /api/payment/create_checkout.php` - Create Stripe session
- `GET /api/payment/get_billing_history.php` - Billing history

---

## 🛡️ Security Checklist

Before production deployment, ensure:

- [ ] **SSL/TLS** - HTTPS enabled on all pages
- [ ] **Database** - Strong passwords, limited user permissions
- [ ] **Config Files** - Remove from web root, use environment variables
- [ ] **File Permissions** - 755 for directories, 644 for files
- [ ] **Backups** - Automated daily backups configured
- [ ] **Logs** - Error logs monitored and rotated
- [ ] **Updates** - PHP and dependencies up to date
- [ ] **Firewall** - Configure WAF rules for common attacks
- [ ] **DDoS Protection** - Consider Cloudflare or similar
- [ ] **Regular Audits** - Penetration testing quarterly

---

## 📊 Monitoring & Maintenance

### Daily Checks
```bash
# Check error logs
tail -f /var/log/php/error.log

# Monitor system resources
htop

# Check MySQL
mysql> SHOW PROCESSLIST;
mysql> SHOW STATUS LIKE 'Threads%';
```

### Weekly Tasks
- Review user feedback
- Update course materials
- Monitor analytics trends
- Check backup integrity

### Monthly Tasks
- Security patches
- Database optimization
- Performance analysis
- Feature updates

---

## 🔄 Backup & Recovery

### Automated Backup Script

```bash
#!/bin/bash
# backup.sh - Daily backup script

BACKUP_DIR="/backups"
DB_NAME="exams_db"
DATE=$(date +%Y-%m-%d_%H:%M:%S)

# Database backup
mysqldump -u root -p $DB_NAME > $BACKUP_DIR/db_$DATE.sql

# Files backup
tar -czf $BACKUP_DIR/files_$DATE.tar.gz /var/www/html/EXAMs

# Upload to cloud storage (optional)
aws s3 cp $BACKUP_DIR/ s3://backup-bucket/ --recursive

# Cleanup old backups (keep 30 days)
find $BACKUP_DIR -type f -mtime +30 -delete

echo "Backup completed: $DATE"
```

### Recovery Procedure

```bash
# Restore database
mysql -u root -p < /backups/db_YYYY-MM-DD.sql

# Restore files
tar -xzf /backups/files_YYYY-MM-DD.tar.gz -C /
```

---

## 🚀 Performance Optimization

### Database
```sql
-- Add indexes for common queries
CREATE INDEX idx_user_email ON users(email);
CREATE INDEX idx_exam_course ON exams(course_id);
CREATE INDEX idx_attempt_user ON exam_attempts(user_id);
CREATE INDEX idx_answer_attempt ON exam_answers(attempt_id);

-- Regular maintenance
OPTIMIZE TABLE users;
ANALYZE TABLE exams;
```

### Caching
- Enable Redis for session caching
- Use browser caching (Cache-Control headers)
- Implement query result caching
- Consider CDN for static assets

### Code
- Minimize CSS/JavaScript files
- Use gzip compression
- Lazy load images
- Implement pagination (max 50 records/page)

---

## 📞 Support & Troubleshooting

### Common Issues

**Issue**: "Connection refused" error
```
Solution: Check MySQL is running, verify config.php credentials
```

**Issue**: "Call to undefined function" error
```
Solution: Check file paths, ensure all includes are correct
```

**Issue**: "Permission denied" errors
```
Solution: Run: chmod 755 /api /student /admin
```

**Issue**: Dark mode not working
```
Solution: Check localStorage enabled, clear browser cache
```

**Issue**: Payment not processing
```
Solution: Verify STRIPE keys, check webhook configuration
```

---

## 📖 Additional Resources

- [Bootstrap Documentation](https://getbootstrap.com/docs/)
- [Chart.js Documentation](https://www.chartjs.org/docs/)
- [Stripe Integration Guide](https://stripe.com/docs/payments)
- [PHP PDO Tutorial](https://www.php.net/manual/en/book.pdo.php)
- [MySQL Best Practices](https://dev.mysql.com/)

---

**Last Updated**: June 17, 2026  
**Version**: 1.0.0  
**Status**: ✅ Production Ready
