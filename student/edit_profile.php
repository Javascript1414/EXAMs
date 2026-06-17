<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('/login.php');
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Fetch current profile
$stmt = $pdo->prepare("
    SELECT u.id, u.full_name, u.email, u.phone,
           up.bio, up.profile_photo_path, up.cover_photo_path,
           up.phone_verified, up.aadhaar_number, up.father_name,
           up.mother_name, up.emergency_contact, up.emergency_contact_name,
           up.social_media_links, up.skills, up.certifications,
           up.about_education, up.about_experience, up.website, up.location
    FROM users u
    LEFT JOIN user_profiles up ON u.id = up.user_id
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$profile = $stmt->fetch();

if (!$profile) {
    redirect('/student/index.php');
}

// Parse JSON fields
$social_media = !empty($profile['social_media_links']) ? json_decode($profile['social_media_links'], true) : [];
$skills = !empty($profile['skills']) ? json_decode($profile['skills'], true) : [];
$certifications = !empty($profile['certifications']) ? json_decode($profile['certifications'], true) : [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    try {
        // Get current paths (photos now uploaded via AJAX)
        $profile_photo_path = $profile['profile_photo_path'];
        $cover_photo_path = $profile['cover_photo_path'];

        // Process skills array
        $skills_input = isset($_POST['skills']) ? array_filter(array_map('trim', explode(',', $_POST['skills']))) : [];
        $skills_json = json_encode($skills_input);

        // Process certifications array
        $certifications_input = isset($_POST['certifications']) ? array_filter(array_map('trim', explode(',', $_POST['certifications']))) : [];
        $certifications_json = json_encode($certifications_input);

        // Process social media
        $social_media_input = [];
        if (!empty($_POST['social_platforms'])) {
            foreach ($_POST['social_platforms'] as $index => $platform) {
                if (!empty($platform) && !empty($_POST['social_urls'][$index])) {
                    $social_media_input[] = [
                        'platform' => $platform,
                        'url' => $_POST['social_urls'][$index]
                    ];
                }
            }
        }
        $social_media_json = json_encode($social_media_input);

        // Check if user_profiles exists
        $stmt = $pdo->prepare("SELECT id FROM user_profiles WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $profile_exists = $stmt->fetch();

        if ($profile_exists) {
            // Update existing profile
            $stmt = $pdo->prepare("
                UPDATE user_profiles SET
                    bio = ?,
                    profile_photo_path = ?,
                    cover_photo_path = ?,
                    phone_verified = ?,
                    aadhaar_number = ?,
                    father_name = ?,
                    mother_name = ?,
                    emergency_contact = ?,
                    emergency_contact_name = ?,
                    social_media_links = ?,
                    skills = ?,
                    certifications = ?,
                    about_education = ?,
                    about_experience = ?,
                    website = ?,
                    location = ?
                WHERE user_id = ?
            ");
            $stmt->execute([
                $_POST['bio'],
                $profile_photo_path,
                $cover_photo_path,
                isset($_POST['phone_verified']) ? 1 : 0,
                $_POST['aadhaar_number'],
                $_POST['father_name'],
                $_POST['mother_name'],
                $_POST['emergency_contact'],
                $_POST['emergency_contact_name'],
                $social_media_json,
                $skills_json,
                $certifications_json,
                $_POST['about_education'],
                $_POST['about_experience'],
                $_POST['website'],
                $_POST['location'],
                $user_id
            ]);
        } else {
            // Insert new profile
            $stmt = $pdo->prepare("
                INSERT INTO user_profiles 
                (user_id, bio, profile_photo_path, cover_photo_path, phone_verified,
                 aadhaar_number, father_name, mother_name, emergency_contact,
                 emergency_contact_name, social_media_links, skills, certifications,
                 about_education, about_experience, website, location)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $user_id,
                $_POST['bio'],
                $profile_photo_path,
                $cover_photo_path,
                isset($_POST['phone_verified']) ? 1 : 0,
                $_POST['aadhaar_number'],
                $_POST['father_name'],
                $_POST['mother_name'],
                $_POST['emergency_contact'],
                $_POST['emergency_contact_name'],
                $social_media_json,
                $skills_json,
                $certifications_json,
                $_POST['about_education'],
                $_POST['about_experience'],
                $_POST['website'],
                $_POST['location']
            ]);
        }

        // Update phone in users table
        $stmt = $pdo->prepare("UPDATE users SET phone = ? WHERE id = ?");
        $stmt->execute([$_POST['phone'], $user_id]);

        $message = '✅ Profile updated successfully!';
        
        // Refresh profile data
        $stmt = $pdo->prepare("
            SELECT u.id, u.full_name, u.email, u.phone,
                   up.bio, up.profile_photo_path, up.cover_photo_path,
                   up.phone_verified, up.aadhaar_number, up.father_name,
                   up.mother_name, up.emergency_contact, up.emergency_contact_name,
                   up.social_media_links, up.skills, up.certifications,
                   up.about_education, up.about_experience, up.website, up.location
            FROM users u
            LEFT JOIN user_profiles up ON u.id = up.user_id
            WHERE u.id = ?
        ");
        $stmt->execute([$user_id]);
        $profile = $stmt->fetch();
        $social_media = !empty($profile['social_media_links']) ? json_decode($profile['social_media_links'], true) : [];
        $skills = !empty($profile['skills']) ? json_decode($profile['skills'], true) : [];
        $certifications = !empty($profile['certifications']) ? json_decode($profile['certifications'], true) : [];

    } catch (Exception $e) {
        $error = '❌ Error: ' . $e->getMessage();
        
        // Log detailed error for debugging
        error_log('Profile upload error for user ' . $user_id . ': ' . $e->getMessage());
    }
}

include __DIR__ . '/../includes/header.php';
?>

<style>
    .edit-profile-container {
        max-width: 800px;
        margin: 30px auto;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        padding: 30px;
    }

    .edit-profile-container h1 {
        color: #333;
        margin-top: 0;
    }

    .alert {
        padding: 15px 20px;
        border-radius: 6px;
        margin-bottom: 20px;
        font-weight: 500;
    }

    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .alert-danger {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .form-section {
        margin-bottom: 30px;
        padding-bottom: 30px;
        border-bottom: 1px solid #eee;
    }

    .form-section:last-child {
        border-bottom: none;
    }

    .form-section h3 {
        color: #667eea;
        margin-top: 0;
        margin-bottom: 15px;
        font-size: 16px;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
    }

    .form-row.full {
        grid-template-columns: 1fr;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        color: #333;
        font-weight: 500;
        font-size: 14px;
    }

    .form-group input,
    .form-group textarea,
    .form-group select {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 14px;
        font-family: inherit;
        transition: border-color 0.3s;
    }

    .form-group input:focus,
    .form-group textarea:focus,
    .form-group select:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .form-group textarea {
        resize: vertical;
        min-height: 100px;
    }

    .file-input-wrapper {
        position: relative;
        overflow: hidden;
    }

    .file-input-wrapper input[type="file"] {
        display: none;
    }

    .file-input-label {
        display: block;
        background: #f0f0f0;
        border: 2px dashed #ddd;
        padding: 20px;
        border-radius: 6px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
    }

    .file-input-label:hover {
        border-color: #667eea;
        background: #f9f9ff;
    }

    .photo-preview {
        margin-top: 10px;
        max-width: 200px;
    }

    .photo-preview img {
        width: 100%;
        border-radius: 6px;
        border: 1px solid #ddd;
    }

    .checkbox-group {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .checkbox-group input[type="checkbox"] {
        width: auto;
        margin: 0;
        cursor: pointer;
    }

    .dynamic-inputs {
        background: #f9f9f9;
        padding: 15px;
        border-radius: 6px;
        margin-bottom: 10px;
    }

    .dynamic-inputs .form-row {
        margin-bottom: 10px;
    }

    .add-btn {
        background: #667eea;
        color: white;
        padding: 10px 15px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.3s;
    }

    .add-btn:hover {
        background: #764ba2;
        transform: translateY(-2px);
    }

    .remove-btn {
        background: #dc3545;
        color: white;
        padding: 6px 12px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 13px;
    }

    .remove-btn:hover {
        background: #c82333;
    }

    .button-group {
        display: flex;
        gap: 10px;
        margin-top: 30px;
    }

    .btn {
        padding: 12px 24px;
        border: none;
        border-radius: 6px;
        font-size: 15px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s;
        text-decoration: none;
        display: inline-block;
    }

    .btn-primary {
        background: #667eea;
        color: white;
    }

    .btn-primary:hover {
        background: #764ba2;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }

    .btn-secondary {
        background: #6c757d;
        color: white;
    }

    .btn-secondary:hover {
        background: #5a6268;
    }

    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
        }

        .edit-profile-container {
            padding: 20px;
        }
    }
</style>

<div class="edit-profile-container">
    <h1>✏️ Edit Profile</h1>

    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

        <!-- Photos Section - AJAX Upload -->
        <div class="form-section">
            <h3>📸 Profile Photo</h3>
            
            <div id="photo-message"></div>
            
            <div class="form-group">
                <label>Upload Profile Photo</label>
                <div class="file-input-wrapper" id="photo-dropzone" style="border: 2px dashed #667eea; padding: 30px; border-radius: 6px; text-align: center; cursor: pointer; transition: all 0.3s;">
                    <input type="file" name="profile_photo" id="profile_photo_ajax" accept="image/*" style="display: none;">
                    <div style="font-size: 24px; margin-bottom: 10px;">📤</div>
                    <div style="font-size: 14px; color: #667eea; font-weight: 500;">Click to upload or drag & drop</div>
                    <div style="font-size: 12px; color: #999; margin-top: 5px;">JPG, PNG, GIF - Max 5MB (Min 100×100px)</div>
                </div>
                
                <div id="upload-progress" style="display: none; margin-top: 15px;">
                    <div style="font-size: 12px; margin-bottom: 5px;">Uploading...</div>
                    <div style="width: 100%; height: 6px; background: #e0e0e0; border-radius: 3px; overflow: hidden;">
                        <div id="progress-bar" style="width: 0%; height: 100%; background: #667eea; transition: width 0.3s;"></div>
                    </div>
                </div>
                
                <?php if (!empty($profile['profile_photo_path'])): ?>
                    <div class="photo-preview" style="margin-top: 15px;">
                        <img src="<?php echo htmlspecialchars($profile['profile_photo_path']); ?>" alt="Current Profile" style="max-width: 150px; border-radius: 6px; border: 2px solid #ddd;">
                        <div style="font-size: 12px; color: #666; margin-top: 8px;">✓ Current Photo</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <script>
        document.getElementById('profile_photo_ajax').addEventListener('change', uploadPhoto);
        
        const dropzone = document.getElementById('photo-dropzone');
        dropzone.addEventListener('click', () => document.getElementById('profile_photo_ajax').click());
        
        dropzone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropzone.style.borderColor = '#764ba2';
            dropzone.style.background = '#f9f9ff';
        });
        
        dropzone.addEventListener('dragleave', (e) => {
            dropzone.style.borderColor = '#667eea';
            dropzone.style.background = 'transparent';
        });
        
        dropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropzone.style.borderColor = '#667eea';
            dropzone.style.background = 'transparent';
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                document.getElementById('profile_photo_ajax').files = files;
                uploadPhoto();
            }
        });
        
        function uploadPhoto() {
            const fileInput = document.getElementById('profile_photo_ajax');
            const file = fileInput.files[0];
            
            if (!file) return;
            
            const formData = new FormData();
            formData.append('photo', file);
            
            const messageDiv = document.getElementById('photo-message');
            const progressDiv = document.getElementById('upload-progress');
            const progressBar = document.getElementById('progress-bar');
            
            messageDiv.innerHTML = '';
            progressDiv.style.display = 'block';
            progressBar.style.width = '0%';
            
            const xhr = new XMLHttpRequest();
            
            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable) {
                    const percentComplete = (e.loaded / e.total) * 100;
                    progressBar.style.width = percentComplete + '%';
                }
            });
            
            xhr.addEventListener('load', () => {
                progressDiv.style.display = 'none';
                
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            messageDiv.innerHTML = '<div class="alert alert-success">✅ ' + response.message + '</div>';
                            
                            // Refresh the preview
                            setTimeout(() => {
                                location.reload();
                            }, 1500);
                        } else {
                            messageDiv.innerHTML = '<div class="alert alert-danger">❌ ' + response.error + '</div>';
                        }
                    } catch (e) {
                        messageDiv.innerHTML = '<div class="alert alert-danger">❌ Upload failed</div>';
                    }
                } else {
                    messageDiv.innerHTML = '<div class="alert alert-danger">❌ Upload failed (Server error)</div>';
                }
                
                fileInput.value = '';
            });
            
            xhr.addEventListener('error', () => {
                progressDiv.style.display = 'none';
                messageDiv.innerHTML = '<div class="alert alert-danger">❌ Network error</div>';
            });
            
            xhr.open('POST', 'upload_photo_ajax.php');
            xhr.send(formData);
        }
        </script>

        <!-- Personal Information -->
        <div class="form-section">
            <h3>👤 Personal Information</h3>

            <div class="form-row">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($profile['full_name']); ?>" disabled style="background: #f0f0f0; cursor: not-allowed;">
                    <small style="color: #666;">Cannot be changed</small>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($profile['email']); ?>" disabled style="background: #f0f0f0; cursor: not-allowed;">
                    <small style="color: #666;">Cannot be changed</small>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($profile['phone'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Location</label>
                    <input type="text" name="location" value="<?php echo htmlspecialchars($profile['location'] ?? ''); ?>" placeholder="City, Country">
                </div>
            </div>

            <div class="form-group form-row full">
                <label>Bio</label>
                <textarea name="bio" placeholder="Tell us about yourself..."><?php echo htmlspecialchars($profile['bio'] ?? ''); ?></textarea>
            </div>
        </div>

        <!-- Family Information -->
        <div class="form-section">
            <h3>👨‍👩‍👧 Family Information</h3>

            <div class="form-row">
                <div class="form-group">
                    <label>Father's Name</label>
                    <input type="text" name="father_name" value="<?php echo htmlspecialchars($profile['father_name'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Mother's Name</label>
                    <input type="text" name="mother_name" value="<?php echo htmlspecialchars($profile['mother_name'] ?? ''); ?>">
                </div>
            </div>
        </div>

        <!-- Emergency Contact -->
        <div class="form-section">
            <h3>🚨 Emergency Contact</h3>

            <div class="form-row">
                <div class="form-group">
                    <label>Contact Name</label>
                    <input type="text" name="emergency_contact_name" value="<?php echo htmlspecialchars($profile['emergency_contact_name'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Contact Number</label>
                    <input type="tel" name="emergency_contact" value="<?php echo htmlspecialchars($profile['emergency_contact'] ?? ''); ?>">
                </div>
            </div>
        </div>

        <!-- Verification -->
        <div class="form-section">
            <h3>✅ Verification</h3>

            <div class="form-row">
                <div class="form-group">
                    <label>Aadhaar Number</label>
                    <input type="text" name="aadhaar_number" value="<?php echo htmlspecialchars($profile['aadhaar_number'] ?? ''); ?>" placeholder="Enter 12-digit Aadhaar">
                    <small style="color: #999;">Your Aadhaar is secure and encrypted</small>
                </div>
                <div class="form-group">
                    <div class="checkbox-group" style="margin-top: 26px;">
                        <input type="checkbox" name="phone_verified" id="phone_verified" <?php echo !empty($profile['phone_verified']) ? 'checked' : ''; ?> disabled>
                        <label for="phone_verified" style="margin: 0;">Phone is Verified</label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Education & Experience -->
        <div class="form-section">
            <h3>🎓 Education & Experience</h3>

            <div class="form-group form-row full">
                <label>About Education</label>
                <textarea name="about_education" placeholder="Enter your educational background, qualifications, institutes..."><?php echo htmlspecialchars($profile['about_education'] ?? ''); ?></textarea>
            </div>

            <div class="form-group form-row full">
                <label>About Experience</label>
                <textarea name="about_experience" placeholder="Enter your professional experience..."><?php echo htmlspecialchars($profile['about_experience'] ?? ''); ?></textarea>
            </div>
        </div>

        <!-- Skills -->
        <div class="form-section">
            <h3>🔧 Skills</h3>
            <div class="form-group">
                <label>Skills (comma-separated)</label>
                <textarea name="skills" placeholder="e.g., PHP, JavaScript, MySQL, React, Docker"><?php echo implode(', ', $skills); ?></textarea>
                <small style="color: #666;">Separate multiple skills with commas</small>
            </div>
        </div>

        <!-- Certifications -->
        <div class="form-section">
            <h3>🏆 Certifications</h3>
            <div class="form-group">
                <label>Certifications (comma-separated)</label>
                <textarea name="certifications" placeholder="e.g., AWS Certified Solutions Architect, Google Cloud Associate"><?php echo implode(', ', $certifications); ?></textarea>
                <small style="color: #666;">Separate multiple certifications with commas</small>
            </div>
        </div>

        <!-- Links -->
        <div class="form-section">
            <h3>🔗 Links</h3>

            <div class="form-group">
                <label>Website / Portfolio</label>
                <input type="url" name="website" value="<?php echo htmlspecialchars($profile['website'] ?? ''); ?>" placeholder="https://yourwebsite.com">
            </div>

            <div class="form-group form-row full">
                <label>Social Media Links</label>
                <div id="social-links-container">
                    <?php if (!empty($social_media)): ?>
                        <?php foreach ($social_media as $index => $social): ?>
                            <div class="dynamic-inputs">
                                <div class="form-row">
                                    <input type="text" name="social_platforms[]" placeholder="e.g., LinkedIn, GitHub, Twitter" value="<?php echo htmlspecialchars($social['platform']); ?>">
                                    <input type="url" name="social_urls[]" placeholder="https://..." value="<?php echo htmlspecialchars($social['url']); ?>">
                                </div>
                                <button type="button" class="remove-btn" onclick="this.parentElement.remove()">Remove</button>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="dynamic-inputs">
                            <div class="form-row">
                                <input type="text" name="social_platforms[]" placeholder="e.g., LinkedIn, GitHub, Twitter">
                                <input type="url" name="social_urls[]" placeholder="https://...">
                            </div>
                            <button type="button" class="remove-btn" onclick="this.parentElement.remove()">Remove</button>
                        </div>
                    <?php endif; ?>
                </div>
                <button type="button" class="add-btn" onclick="addSocialLink()">+ Add Social Link</button>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="button-group">
            <button type="submit" class="btn btn-primary">💾 Save Profile</button>
            <a href="<?php echo BASE_URL; ?>/student/profile.php" class="btn btn-secondary">👁️ View Profile</a>
        </div>
    </form>
</div>

<script>
function addSocialLink() {
    const container = document.getElementById('social-links-container');
    const newInput = document.createElement('div');
    newInput.className = 'dynamic-inputs';
    newInput.innerHTML = `
        <div class="form-row">
            <input type="text" name="social_platforms[]" placeholder="e.g., LinkedIn, GitHub, Twitter">
            <input type="url" name="social_urls[]" placeholder="https://...">
        </div>
        <button type="button" class="remove-btn" onclick="this.parentElement.remove()">Remove</button>
    `;
    container.appendChild(newInput);
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
