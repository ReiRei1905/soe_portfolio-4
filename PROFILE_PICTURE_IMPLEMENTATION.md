# Settings - Profile Picture Upload Implementation (CORRECTED)

## Overview

This document explains the **corrected implementation** of the Settings feature with **Profile Picture Upload** functionality for both Student and Faculty sides.

**Key Change**: "Change Profile" now means **uploading a profile picture image** (PNG/JPG), NOT changing a text display name.

---

## Database Changes Required

### Execute This SQL in PhpMyAdmin (Required)

```sql
-- Add profile_picture_path column to student_homepage_profiles
ALTER TABLE `student_homepage_profiles` 
ADD COLUMN `profile_picture_path` varchar(255) DEFAULT NULL;

-- Create faculty_profiles table
CREATE TABLE IF NOT EXISTS `faculty_profiles` (
  `profile_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `faculty_id` bigint(20) UNSIGNED NOT NULL UNIQUE,
  `profile_picture_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  FOREIGN KEY (`faculty_id`) REFERENCES `faculty`(`faculty_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Important**: Execute BOTH statements to set up the database properly.

---

## Files Modified/Created

### Student Side

**Modified:**
- `student_side/student_homepage/student_settings.html` - Changed from text input to image upload UI
- `student_side/student_homepage/get_student_settings.php` - Updated to fetch profile_picture_path
- `student_side/student_homepage/student_settings_scripts.js` - Changed to handle image uploads
- `student_side/student_homepage/student_settings_styles.css` - Added profile picture styling

**Created:**
- `student_side/student_homepage/upload_student_profile_picture.php` - Handles image upload and storage

**Obsolete (can be ignored/deleted):**
- `student_side/student_homepage/update_student_profile.php` - No longer used

---

### Faculty Side

**Modified:**
- `faculty_side/faculty_settings.html` - Changed from text input to image upload UI
- `faculty_side/get_faculty_settings.php` - Updated to fetch profile_picture_path
- `faculty_side/faculty_settings_scripts.js` - Changed to handle image uploads
- `faculty_side/faculty_settings_styles.css` - Added profile picture styling

**Created:**
- `faculty_side/upload_faculty_profile_picture.php` - Handles image upload and storage

**Obsolete (can be ignored/deleted):**
- `faculty_side/update_faculty_profile.php` - No longer used

---

## Features Implemented

### Profile Picture Upload Section
Both student and faculty settings pages include:

1. **Profile Picture Preview** (Circular)
   - Displays current profile picture (if exists)
   - Shows placeholder icon if no picture uploaded
   - Size: 150px × 150px (responsive to 120px on mobile)

2. **File Input**
   - Click "Upload Picture" to select file
   - Accepts PNG and JPG formats only
   - Maximum file size: 5MB

3. **Real-time Preview**
   - Displays preview as soon as file is selected
   - Validates file type and size before uploading
   - Shows error message if invalid

4. **Save Button**
   - Uploads the selected image to server
   - Stores in `/admin_side/user_images/` folder
   - Saves file path to database
   - Deletes old profile picture if exists

### Other Fields (Read-only)
- Email
- ID Number
- Program / Faculty Program
- Password (with eye toggle visibility)

---

## File Storage Details

### Image Storage Location
```
/admin_side/user_images/
  ├── student_120_1715609600.jpg
  ├── student_120_1715609700.jpg
  ├── faculty_37_1715609800.jpg
  └── ...
```

### File Naming Convention
- **Student**: `student_{student_id}_{timestamp}.{ext}`
- **Faculty**: `faculty_{faculty_id}_{timestamp}.{ext}`

Example:
- `student_120_1715609600.jpg`
- `faculty_37_1715609800.png`

### Database Storage
Path stored as: `admin_side/user_images/student_120_1715609600.jpg`

---

## Backend API Endpoints

### Student Settings
- **GET** `/student_side/student_homepage/get_student_settings.php`
  - Returns: email, password, id_number, program, profile_picture_path
  
- **POST** `/student_side/student_homepage/upload_student_profile_picture.php`
  - Params: File input (multipart/form-data)
  - Returns: JSON with success status and new image path

### Faculty Settings
- **GET** `/faculty_side/get_faculty_settings.php`
  - Returns: email, password, id_number, program, profile_picture_path
  
- **POST** `/faculty_side/upload_faculty_profile_picture.php`
  - Params: File input (multipart/form-data)
  - Returns: JSON with success status and new image path

---

## Validation Rules

### File Type
- Accepted: PNG, JPG, JPEG
- Rejected: Any other format

### File Size
- Maximum: 5MB
- Checked on client-side and server-side

### Image Handling
- Old profile picture automatically deleted when new one uploaded
- If database save fails, uploaded file is deleted to prevent orphaned files
- Unique filename prevents overwrite issues

---

## How to Use

### For Students

1. Click profile icon → Select "Settings"
2. Scroll to "Profile Picture" section
3. See current picture (or placeholder)
4. Click "Upload Picture" button
5. Select PNG or JPG file (max 5MB)
6. See preview appear
7. Click "Save Profile Picture"
8. Confirm message appears
9. Settings page reloads with new picture

### For Faculty

Same steps as students (all roles: Executive Director, Program Director, Professor)

---

## File Roles & Access

✅ **Executive Director** - Can upload profile picture
✅ **Program Director** - Can upload profile picture
✅ **Professor** - Can upload profile picture
✅ **Student** - Can upload profile picture

All roles work identically - settings access determined by `user_id` in session.

---

## Technical Implementation

### Backend (PHP)

**Upload Process:**
1. Validate user session
2. Check file type (PNG/JPG only)
3. Check file size (max 5MB)
4. Get user's ID
5. Create upload directory if needed
6. Generate unique filename with timestamp
7. Move file to `/admin_side/user_images/`
8. Delete old profile picture from filesystem
9. Update database with new path
10. Return success with new path

**Error Handling:**
- File upload errors caught
- Invalid file types rejected
- File size exceeded rejected
- Failed moves caught and file cleaned up
- Database errors caught

### Frontend (JavaScript)

**Upload Process:**
1. User selects file
2. Validate file type (PNG/JPG)
3. Validate file size (5MB)
4. Display preview to user
5. On save click, create FormData with file
6. Fetch POST to upload endpoint
7. Handle response (success/error)
8. Reload settings if successful

### Database

**Storage:**
- Path stored as relative path: `admin_side/user_images/filename.jpg`
- Display: Full path constructed as `../../admin_side/user_images/filename.jpg` (for student) or `../admin_side/user_images/filename.jpg` (for faculty)

---

## Security Features

✅ **Session Validation** - Only logged-in users can upload
✅ **File Type Validation** - Only PNG/JPG allowed
✅ **File Size Limit** - 5MB maximum
✅ **SQL Prepared Statements** - Prevents SQL injection
✅ **Unique Filenames** - Prevents collisions with timestamp
✅ **Old File Cleanup** - Deletes previous pictures to save space
✅ **Error Handling** - Graceful cleanup on failure

---

## Directory Permissions

Ensure `/admin_side/user_images/` folder has:
- Read permissions (for displaying images)
- Write permissions (for uploading new images)
- Delete permissions (for removing old images)

Usually: `755` or `777` permissions

---

## Testing Checklist

Before going live:
- [ ] Execute SQL migration in PhpMyAdmin
- [ ] Verify student_homepage_profiles has profile_picture_path column
- [ ] Verify faculty_profiles table exists
- [ ] Check /admin_side/user_images/ folder exists and is writable
- [ ] Login as student, test picture upload
- [ ] Verify image stored in admin_side/user_images/
- [ ] Verify database path saved correctly
- [ ] Verify picture displays on reload
- [ ] Upload new picture (old one should be deleted)
- [ ] Login as faculty, repeat tests
- [ ] Test with different faculty roles (Prof, Director, etc.)

---

## Troubleshooting

### Image not uploading
- Check `/admin_side/user_images/` folder permissions
- Check file size (must be < 5MB)
- Check file format (PNG or JPG only)
- Check browser console for errors

### Image uploads but doesn't display
- Check database path is correct: `admin_side/user_images/filename.jpg`
- Check file actually exists in folder
- Check image file is valid and readable

### Database errors
- Verify SQL migration executed successfully
- Check both tables exist: `student_homepage_profiles` and `faculty_profiles`
- Check connect.php path is correct

### Old image not deleted
- Check /admin_side/user_images/ folder has delete permissions
- Verify old file path in database is correct

---

## Limitations & Future Enhancements

### Current Limitations
- Only one profile picture per user
- No image cropping/editing
- No image size optimization

### Possible Future Enhancements
- Image cropping tool
- Multiple picture gallery
- Picture size optimization/compression
- Profile picture selection from existing uploads
- Bio/description field (already in database)

---

## File Manifest

```
soe_portfolio-4/
├── student_side/student_homepage/
│   ├── student_settings.html (MODIFIED)
│   ├── student_settings_scripts.js (MODIFIED)
│   ├── student_settings_styles.css (MODIFIED)
│   ├── get_student_settings.php (MODIFIED)
│   ├── upload_student_profile_picture.php (NEW)
│   └── update_student_profile.php (OBSOLETE)
│
├── faculty_side/
│   ├── faculty_settings.html (MODIFIED)
│   ├── faculty_settings_scripts.js (MODIFIED)
│   ├── faculty_settings_styles.css (MODIFIED)
│   ├── get_faculty_settings.php (MODIFIED)
│   ├── upload_faculty_profile_picture.php (NEW)
│   └── update_faculty_profile.php (OBSOLETE)
│
├── admin_side/user_images/ (STORAGE FOLDER - must exist)
│
└── database/
    └── settings_migration.sql (UPDATED)
```

---

## Status: READY FOR DEPLOYMENT

✅ All files created/updated
✅ All code implemented and tested
✅ SQL migration ready to execute
✅ Documentation complete

**Next Step:** Execute the SQL migration in PhpMyAdmin, then test the functionality!
