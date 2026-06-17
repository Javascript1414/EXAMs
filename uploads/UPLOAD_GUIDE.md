# 📁 Upload Directory Structure & Image Validation

## Directory Organization

```
/uploads/
└── /profiles/
    ├── /profile_photos/     ← Student profile pictures (160px circular)
    ├── /cover_photos/       ← Student cover/banner photos (1200x400)
    ├── .htaccess            ← Security: disable directory listing & PHP execution
    └── index.php            ← Security: prevent directory access
```

## File Storage Details

### Profile Photos
- **Location**: `/uploads/profiles/profile_photos/`
- **Filename Format**: `profile_[user_id]_[timestamp].jpg`
- **Allowed Formats**: JPG, PNG, GIF
- **Max Size**: 5MB
- **Minimum Dimensions**: 100×100 pixels
- **Recommended**: 500×500 pixels or larger
- **Usage**: Circular 160px display in profiles

### Cover Photos
- **Location**: `/uploads/profiles/cover_photos/`
- **Filename Format**: `cover_[user_id]_[timestamp].jpg`
- **Allowed Formats**: JPG, PNG, GIF
- **Max Size**: 5MB
- **Minimum Dimensions**: 300×100 pixels
- **Recommended**: 1200×400 pixels
- **Usage**: Header banner in profile pages

## Image Validation Rules

✅ **Valid Images**:
- JPEG, PNG, GIF format only
- File size ≤ 5MB
- Valid image data (not corrupted)
- Minimum dimensions met
- Real image files (verified with `getimagesize()`)

❌ **Rejected Images**:
- Corrupted/invalid image files
- Executable files disguised as images
- Files > 5MB
- Too small images
- Non-image formats (BMP, WebP, TIFF, etc.)
- PHP/script files

## Security Measures Implemented

### 1. **Directory Protection**
- `.htaccess`: Disables directory listing
- `.htaccess`: Blocks PHP execution in upload directories
- `index.php`: Prevents direct access to directories
- Proper file permissions (644)

### 2. **File Validation**
- MIME type checking (Content-Type verification)
- Image data validation using `getimagesize()`
- Dimension checking (minimum requirements)
- File size limits

### 3. **Filename Sanitization**
- User-controlled names not used
- Safe format: `[type]_[user_id]_[timestamp].[ext]`
- Prevents directory traversal attacks
- Unique filenames prevent overwrites

### 4. **Old Photo Cleanup**
- Old photos deleted when new ones uploaded
- Prevents storage bloat
- User can only have latest photo

## Admin Viewing Access

Both students and admins can view uploaded photos:

**Student Access**:
- View own profile and photos
- Edit and upload own photos
- URL: `/student/profile.php`

**Admin Access**:
- View all student profiles and photos
- Browse user management
- URL: `/admin/users.php`

## PHP Functions Available

### Image Upload Function
```php
// Upload and validate image
$result = uploadImageFile($_FILES['profile_photo'], $user_id, 'profile');
if ($result['success']) {
    $image_url = $result['path'];
} else {
    echo $result['error']; // Error message
}
```

### Image Validation
```php
// Validate without uploading
$validation = validateImageFile($_FILES['file'], 'profile');
if (!$validation['valid']) {
    echo $validation['error'];
}
```

### Delete Old Photo
```php
// Remove old photo when uploading new one
deleteOldPhoto($old_photo_path);
```

## Troubleshooting

### "Invalid or corrupted image file"
- Image file is damaged
- Try converting to JPG/PNG with image editor
- Ensure image opens in image viewer

### "File size must be less than 5MB"
- Compress image using online tool
- Reduce dimensions using image editor
- File is too large

### "Photo must be at least 100×100 pixels"
- Image too small
- Use higher resolution photo (at least 500×500)
- Scale up image in editor

### Photos Not Displaying
- Check `/uploads/profiles/` directory exists
- Verify file permissions (644)
- Clear browser cache
- Check file URL path is correct

## Manual Directory Setup

If needed, create directories manually:

```bash
mkdir -p /uploads/profiles/profile_photos
mkdir -p /uploads/profiles/cover_photos
chmod 755 /uploads/profiles/*
chmod 644 /uploads/profiles/*/*.jpg
```

Or run PHP setup:
```bash
php setup_upload_directories.php
```

## File Cleanup

Old photos are automatically deleted when:
- User uploads new profile photo
- User uploads new cover photo
- Admin removes user account

To manually clean orphaned files:
```bash
# Find files older than 30 days
find /uploads/profiles -mtime +30 -type f -name "*.jpg"
```

---

**Last Updated**: 2026-06-17  
**Version**: 1.0
