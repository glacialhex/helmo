# University Portal (PHP + MySQL)

This is a simple, professional PHP + MySQL portal for a university with authentication, roles, students/courses/enrollment, attendance, fees/receipts/payments, reports, library, transport, communication, facilities, higher-ed, and EAV custom fields.

Setup
- Import sql/schema.sql via phpMyAdmin (port 3306).
- Update DB credentials in config.php.
- Serve this folder on Apache (DocumentRoot or virtual host).
- Visit /login.php (demo admin/password).

Modules
- Auth & Roles: /login.php, /admin/users.php
- Students: /students, Courses: /courses, Enrollments: /enrollments
- Attendance: /attendance, Grades: /grades
- Fees: /fees, Receipts: /receipts, Payments: /payments
- Reports: /reports
- Library: /library, Facilities: /facilities, Safety: /safety
- Transport: /transport, Communication: /communication
- Higher Ed: /highered, EAV: /eav
- Feedback: /feedback

Notes
- Forms include CSRF protection. Some operations are simplified stubs (payments, SMS, QR).
- Add production features as needed (file uploads, PDF export, provider integrations).