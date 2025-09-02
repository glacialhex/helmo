# UI Validation Tests

Login
- Valid: admin/password -> Dashboard.
- Invalid: wrong password -> error message.

Enrollment
- Enroll student in a course with room -> success.
- Enroll when capacity reached -> error.

Payments
- Create transaction -> Pending.
- Confirm transaction -> status Confirmed + SMS log.

Receipts
- Create receipt -> appears in list.
- Print -> opens print view with bilingual labels.

Bilingual
- Click EN/AR -> switches direction and labels in header.

Heuristics
- Check contrast, spacing, consistent buttons.
