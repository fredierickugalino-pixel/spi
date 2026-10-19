# SPI Senior High School Grading System

PHP 8+ / MySQL 8 (or compatible MariaDB) starter implementation for XAMPP. All passwords are stored as PHP password hashes; database actions use MySQLi prepared statements. Passwords must be 8–128 characters and include at least one uppercase letter, one lowercase letter, and one special character. Set DB connection overrides with `SPI_DB_HOST`, `SPI_DB_USER`, `SPI_DB_PASSWORD`, and `SPI_DB_NAME` if needed.

## Install in XAMPP

1. Place this folder at `C:\xampp\htdocs\spi` and start Apache and MySQL.
2. In phpMyAdmin, import `database/spi_grading_system.sql`. It creates the `spi_grading_system` database and tables.
3. If your MySQL root password is not blank, configure the four `SPI_DB_*` environment values for Apache/PHP, or update `config/database.php` for local development. Never use a privileged root account in production.
4. Create the first administrator from a terminal in this folder: `php database/create-admin.php`. Use a unique 12+ character password. The script reads a password on standard input; run it locally and remove or protect the script afterward.
5. Visit `http://localhost/spi/`.

For an existing installation, apply `database/migrations/001_subject_grading_components.sql` and `database/migrations/002_seed_requested_strands.sql` in phpMyAdmin to add subject weight settings and the HUMSS, ICT, ABM, STEM, and HE strand/track choices.

## Workflow

- Students register at **Create a student account**. A request remains pending; the applicant can sign in to see status but cannot access grades.
- Administrators first add/verify an official student record in **Students**, then open **Account requests** and compare the submitted identity with authorized school records before approving. Reject requires a reason, visible only to that applicant and administrators.
- Administrators configure school years, actual strands, sections, subjects, and grading periods in **Academic setup**. Create teacher accounts in **Teachers**, assign students and teachers in **Assignments**.
- Manage the subject catalog and Performance Tasks, Written Works, and Quarterly Assessment weights together in **Subjects**. Weights are configurable and capped at 100% total; the current gradebook still stores one teacher-entered grade and does not calculate component scores.
- Teachers can access only their assigned classes in **My classes**, save grade drafts, and submit grades for review.
- Administrators review submitted grade rows in **Grade review**, then approve, finalize, and release. Students can see only rows linked to their verified student record with status `released`.
- Forgot-password mail uses PHP `mail()`. Configure a local mail transport in XAMPP/PHP for usable delivery. The response does not disclose whether an account exists; reset tokens are stored hashed and expire after one hour.

## Relationship sketch

```text
users 1─0..1 teacher_profiles
users 1─0..1 student_account_requests 0..1─1 students
students 1─many student_enrollments many─1 sections many─1 school_years
sections many─1 strands
subjects many─0..1 strands
users(teacher) 1─many teacher_assignments many─1 subjects/sections/school_years
school_years 1─many grading_periods
students 1─many grades many─1 teacher_assignments and grading_periods
users 1─many audit_logs; users 1─many password_resets
```

## Current scope and school decisions required

This implementation includes authentication, CSRF checks, student registration and matching, student records, academic structure configuration, teacher accounts/assignments, student section assignments, draft/submit/review/finalize/release grade states, released student grade access, password reset, audit logging, and responsive forms/tables.

Before production use, SPI must confirm: grading value range/scale, computation and rounding rules, whether components are used and their approved weights, passing and intervention thresholds, correction workflow after finalization, report-card/class-record format, identity-verification and notification procedure, official subject/strand offerings, applicable privacy/retention requirements, and the approved account email policy. No grading formula, pass mark, subject offering, or report-card format has been invented. Grades are stored as entered; the release workflow does not calculate quarterly/final averages. Password-reset email delivery requires configured mail transport. Deploy behind HTTPS, set secure session cookies, use a least-privilege database user, and remove/protect the initial-admin script.

## Access safeguards

Protected routes enforce role checks on the server. Teacher grade routes check the teacher assignment and class roster on every request. Student grade queries resolve the student's approved account link server-side and return released rows only. State-changing POST forms require a per-session CSRF token. Output is HTML escaped. Historical student and grade rows are retained rather than deleted.
