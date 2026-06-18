# Certificate System Setup Guide

## Overview
The certificate system has been fully implemented with the following features:

✅ **Admin Features:**
- Release/Generate certificates for passed exams
- Send certificates via email to students
- Revoke and reissue certificates
- View all certificates with search and filter

✅ **Student Features:**
- Download certificates as PDF
- View certificate details
- Share verification code
- Verify certificates on the verification page

## Installation Steps

### 1. Install Dependencies
Run the following command in the project root:

```bash
composer install
```

This will install:
- **Dompdf** - For PDF generation
- **PHPMailer** - For email functionality

### 2. Environment Setup

#### For Email Functionality:
Set environment variables for Gmail SMTP:

```bash
# On Windows Command Prompt:
set SMTP_USER=your-email@gmail.com
set SMTP_PASS=your-app-password

# On Windows PowerShell:
$env:SMTP_USER = "your-email@gmail.com"
$env:SMTP_PASS = "your-app-password"
```

Or add to `.env` file:
```
SMTP_USER=your-email@gmail.com
SMTP_PASS=your-app-password
```

**Note:** Use an [Gmail App Password](https://myaccount.google.com/apppasswords) for 2FA accounts.

### 3. Database Column Addition (if needed)

If `certificate_generated` column doesn't exist in `results` table, run:

```sql
ALTER TABLE results ADD COLUMN certificate_generated TINYINT DEFAULT 0;
```

## Features

### 📜 Admin Certificate Release (`/admin/release_certificates.php`)
- View all passed exam results
- Search students and exams
- Filter by certificate status (Pending / Issued)
- One-click certificate release
- Send certificates directly via email
- Preview certificate before sending

### 🎓 Certificate Download (`/student/certificate_download.php`)
Students can download their certificates as PDF with:
- Professional formatting
- Issue date
- Score achieved
- Certificate ID
- Verification QR code

### 📧 Email Sending
Admin can email certificates to students with:
- View link
- Download link
- Verification link
- Certificate details

### ✓ Certificate Verification (`/verify.php`)
Public verification page where anyone can verify a certificate by:
- Certificate ID
- Verification Code

## File Structure

```
/admin/
  ├── certificates.php              - Manage all certificates
  ├── release_certificates.php      - Release/generate certificates
  └── certificate_actions.php       - Handle admin actions (email, release, revoke)

/student/
  ├── certificates.php              - Student's certificate list
  ├── certificate_view.php          - View & print certificate
  └── certificate_download.php      - Download as PDF

/
  ├── verify.php                    - Verify certificate by code/ID
  └── includes/sidebar.php          - Navigation links
```

## API Endpoints

### Admin Actions
```
POST /admin/certificate_actions.php

Parameters:
- action: "release" | "send_email" | "revoke" | "reissue"
- id: result_id (for release)
- cert_id: certificate_id (for email/revoke)
- csrf_token: CSRF token
```

### Student Download
```
GET /student/certificate_download.php?id=<result_id>
```

### Verification
```
GET /verify.php?code=<verification_code>
```

## Testing

### 1. Test Certificate Release
1. Go to Admin Dashboard → Release Certificates
2. Find a passed exam result
3. Click "📜 Release Certificate"
4. Verify certificate is created

### 2. Test Download
1. Go to Student Dashboard → Certificates
2. Click "Download PDF" button
3. Verify PDF downloads correctly

### 3. Test Email
1. Go to Admin → Release Certificates
2. Find issued certificate
3. Click "📧 Email" button
4. Check student's email for certificate

### 4. Test Verification
1. Get certificate ID or verification code
2. Go to `/verify.php`
3. Enter code and verify

## Troubleshooting

### Dompdf Not Found
```
Error: Class 'Dompdf\Dompdf' not found
```
Solution: Run `composer install` again or check vendor/autoload.php exists

### Email Not Sending
```
Error: SMTP connection failed
```
Solution:
- Verify SMTP_USER and SMTP_PASS environment variables
- Use Gmail App Password (not regular password)
- Enable "Less secure app access" if needed

### PDF Generation Issues
```
Error: Failed to load image or missing font
```
Solution: Check file permissions and ensure fonts directory exists in Dompdf

## Security Features

✅ CSRF Token Validation
✅ Role-based Access Control
✅ Ownership Verification
✅ Verification Codes (unique per certificate)
✅ Certificate Status Tracking
✅ Audit Trail (generated_by field)

## Future Enhancements

- [ ] Batch certificate generation
- [ ] Certificate template customization
- [ ] Digital signature support
- [ ] Certificate archive/history
- [ ] Scheduled email delivery
- [ ] Multi-language support
