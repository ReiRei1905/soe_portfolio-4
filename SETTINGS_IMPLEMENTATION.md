# Settings Functionality Implementation Summary

## Overview
I've successfully implemented the Settings feature for both Student and Faculty sides of your E-Portfolio system. This feature allows users to view and manage their account settings, including profile information and password changes.

## Files Created

### Student Side
1. **student_side/student_homepage/student_settings.html** - Settings page UI for students
2. **student_side/student_homepage/student_settings_styles.css** - Styling for student settings page
3. **student_side/student_homepage/student_settings_scripts.js** - JavaScript functionality for student settings
4. **student_side/student_homepage/get_student_settings.php** - Backend API to fetch student settings data
5. **student_side/student_homepage/update_student_profile.php** - Backend API to update student profile (display name)

### Faculty Side
1. **faculty_side/faculty_settings.html** - Settings page UI for faculty
2. **faculty_side/faculty_settings_styles.css** - Styling for faculty settings page
3. **faculty_side/faculty_settings_scripts.js** - JavaScript functionality for faculty settings
4. **faculty_side/get_faculty_settings.php** - Backend API to fetch faculty settings data
5. **faculty_side/update_faculty_profile.php** - Backend API to update faculty profile (display name)

### Database
1. **database/settings_migration.sql** - SQL migration to create faculty_profiles table

## Files Updated

### Student Side
- **student_side/student_homepage/student_homepage.html** - Updated Settings menu link to point to settings.html

### Faculty Side
- **faculty_side/faculty_homepage.html** - Updated Settings menu link to point to faculty_settings.html

## Features Implemented

### Account Settings Section
Both student and faculty settings pages include:

1. **Email Field** (Read-only)
   - Displays the user's email address
   - Cannot be edited from settings page

2. **Change Profile Field** (Editable)
   - Allows users to set/update their display name
   - Updates in real-time to database
   - For students: stored in `student_homepage_profiles` table
   - For faculty: stored in `faculty_profiles` table (to be created)

3. **Password Field** (Read-only with Toggle)
   - Displays the hashed password
   - Eye icon toggle to show/hide password
   - Does not allow editing directly from this page

4. **ID Number** (Read-only)
   - Displays student/faculty ID number
   - Automatically extracted from database

5. **Program Field** (Read-only)
   - For Students: Displays student's enrolled program
   - For Faculty: Displays "Faculty Program" (the program they're assigned to)
   - Retrieved from the programs table

6. **Action Buttons**
   - **Save Changes**: Saves the display name changes to database in real-time
   - **Change Password**: Redirects to the existing password recovery/change page in user_info_V3

## Database Changes Required

### SQL Migration
You need to execute the following SQL in your PhpMyAdmin to create the faculty_profiles table:

```sql
CREATE TABLE IF NOT EXISTS `faculty_profiles` (
  `profile_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `faculty_id` bigint(20) UNSIGNED NOT NULL UNIQUE,
  `display_name` varchar(120) NOT NULL,
  `bio` varchar(160) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  FOREIGN KEY (`faculty_id`) REFERENCES `faculty`(`faculty_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## API Endpoints

### Student Settings APIs
- **GET: `/student_side/student_homepage/get_student_settings.php`**
  - Fetches student email, password, ID number, program, and display name
  - Requires: Active session with user_id

- **POST: `/student_side/student_homepage/update_student_profile.php`**
  - Updates student display name
  - Parameters: `display_name` (string)
  - Requires: Active session with user_id

### Faculty Settings APIs
- **GET: `/faculty_side/get_faculty_settings.php`**
  - Fetches faculty email, password, ID number, program, and display name
  - Requires: Active session with user_id

- **POST: `/faculty_side/update_faculty_profile.php`**
  - Updates faculty display name
  - Parameters: `display_name` (string)
  - Requires: Active session with user_id

## Design Features

### User Interface
- Clean, modern card-based design matching your wireframe
- Orange border (#e5b55b) for section containers
- Blue (#003366) save button with orange (#e5b55b) password change button
- Responsive design that works on mobile and desktop
- Password visibility toggle with eye icon

### Styling
- Clean, organized CSS with proper spacing and typography
- Hover effects on buttons for better UX
- Readonly fields styled differently to indicate they cannot be edited
- Professional color scheme matching your existing design

### JavaScript Functionality
- Automatic loading of settings on page load
- Real-time password visibility toggle
- AJAX-based profile updates without page refresh
- Error handling and user feedback
- Session validation

## How to Use

### For Students
1. Click on the user profile icon in the header
2. Select "Settings" from the dropdown menu
3. You'll see your account information:
   - Email: Your student email (read-only)
   - Change Profile: Your display name (editable)
   - Password: Your password with toggle (read-only, can change via button)
   - ID Number: Your student ID (read-only)
   - Program: Your enrolled program (read-only)
4. Edit your display name and click "Save Changes"
5. Click "Change Password" to be redirected to the password change page

### For Faculty
1. Click on the user profile icon in the header
2. Select "Settings" from the dropdown menu
3. You'll see your account information:
   - Email: Your faculty email (read-only)
   - Change Profile: Your display name (editable)
   - Password: Your password with toggle (read-only, can change via button)
   - ID Number: Your faculty ID (read-only)
   - Faculty Program: Your assigned program (read-only)
4. Edit your display name and click "Save Changes"
5. Click "Change Password" to be redirected to the password change page

## File Structure

```
soe_portfolio-4/
├── student_side/
│   └── student_homepage/
│       ├── student_settings.html
│       ├── student_settings_styles.css
│       ├── student_settings_scripts.js
│       ├── get_student_settings.php
│       └── update_student_profile.php
│
├── faculty_side/
│   ├── faculty_settings.html
│   ├── faculty_settings_styles.css
│   ├── faculty_settings_scripts.js
│   ├── get_faculty_settings.php
│   └── update_faculty_profile.php
│
└── database/
    └── settings_migration.sql
```

## Technical Details

### Backend Technology
- **Language**: PHP 7.4+
- **Database**: MySQL/MariaDB
- **Session Management**: PHP Sessions with $_SESSION['user_id']
- **Password Handling**: Bcrypt hashed passwords (stored, not editable from settings)

### Frontend Technology
- **HTML5**: Semantic markup
- **CSS3**: Modern styling with flexbox
- **JavaScript**: Vanilla JS with Fetch API for AJAX
- **Icons**: Font Awesome 6.0

### Security Features
- Session-based authentication
- SQL prepared statements to prevent injection
- Read-only password field (display only, not editable)
- User-specific data queries

## Next Steps

1. **Execute SQL Migration**: Run the SQL code in PhpMyAdmin to create the faculty_profiles table
2. **Test Settings Page**: Navigate to settings for both student and faculty accounts
3. **Verify Data**: Confirm that settings load correctly and updates work in real-time
4. **Test Password Change**: Verify that "Change Password" button redirects correctly to recovery page

## Notes

- All passwords displayed are bcrypt hashed and are for reference only
- The "Change Profile" field updates in real-time to the database
- Both student and faculty can toggle password visibility with the eye icon
- Password changes should be done through the existing user_info_V3 reset_psw.php functionality
- Year of Enrollment is not shown for students (as per your requirements, since it's in the ID number)
- All styling follows your existing design patterns and color scheme

## Troubleshooting

### Settings not loading?
- Ensure user is logged in (session_start() is working)
- Check browser console for JavaScript errors
- Verify database connection in connect.php

### Faculty profile updates not working?
- Ensure faculty_profiles table is created using the SQL migration
- Check that the faculty_id exists in the database
- Verify file permissions for update_faculty_profile.php

### Password change not redirecting?
- Ensure user_info_V3/recover_psw.php exists
- Check that the path is correct relative to the settings.html location
- Verify that recover_psw.php accepts sessionless requests or redirects appropriately
