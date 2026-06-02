# DATABASE MIGRATION - Copy & Paste into PhpMyAdmin SQL Tab

## IMPORTANT: Execute This FIRST Before Using Settings

## Instructions
1. Open PhpMyAdmin
2. Select the `soe_portfolio` database
3. Click on the "SQL" tab
4. Copy and paste the SQL code below into the text area
5. Click "Go" to execute

---

## SQL Code to Execute

```sql
-- 1. Add profile_picture_path column to existing student_homepage_profiles table
ALTER TABLE `student_homepage_profiles` 
ADD COLUMN `profile_picture_path` varchar(255) DEFAULT NULL;

-- 2. Create faculty_profiles table for storing faculty profile pictures
CREATE TABLE IF NOT EXISTS `faculty_profiles` (
  `profile_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `faculty_id` bigint(20) UNSIGNED NOT NULL UNIQUE,
  `profile_picture_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  FOREIGN KEY (`faculty_id`) REFERENCES `faculty`(`faculty_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## What This Does

### ALTER TABLE - student_homepage_profiles
Adds a new column `profile_picture_path` to store the path to student profile pictures:
- Type: varchar(255) - stores file path like "admin_side/user_images/student_120_1715609600.jpg"
- Default: NULL - initially empty until student uploads a picture

### CREATE TABLE - faculty_profiles
Creates a new table for faculty profile information:

| Column | Type | Purpose |
|--------|------|---------|
| `profile_id` | bigint(20) UNSIGNED | Auto-incrementing primary key |
| `faculty_id` | bigint(20) UNSIGNED | Foreign key - unique link to faculty table |
| `profile_picture_path` | varchar(255) | Path to faculty profile picture |
| `created_at` | timestamp | Record creation time |
| `updated_at` | timestamp | Last update time |

---

## Expected Behavior After Execution

✅ Student profile pictures will be stored in: `admin_side/user_images/student_{id}_{timestamp}.jpg`
✅ Faculty profile pictures will be stored in: `admin_side/user_images/faculty_{id}_{timestamp}.jpg`
✅ Database paths stored as: `admin_side/user_images/filename.jpg`

---

## Verification

After execution, verify the tables were updated:

**Check student_homepage_profiles:**
```sql
DESCRIBE student_homepage_profiles;
```
Expected: Should show `profile_picture_path` column

**Check faculty_profiles:**
```sql
SHOW TABLES LIKE 'faculty_profiles';
```
Expected: Should return one row with table name `faculty_profiles`

---

## If Something Goes Wrong

If the SQL fails:
1. Check that you're in the correct `soe_portfolio` database
2. Verify the syntax is copied exactly
3. Check browser console for error messages
4. Tell me the error message

---

## Next Steps After SQL Execution

1. Login as a student
2. Navigate to Settings
3. Upload a profile picture (PNG/JPG, max 5MB)
4. Click "Save Profile Picture"
5. Picture should be stored in `/admin_side/user_images/` folder

Same for faculty users.

---

**CRITICAL: Do NOT proceed with testing until you execute this SQL!**
