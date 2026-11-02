# SIMTANI

SIMTANI is a web application for coordinating agricultural work and managing the people, schedules, attendance, and payments involved. It provides administrators and members with role-specific tools for managing work from scheduling through completion and wage payment.

## Application overview

The application supports these core workflows:

- Maintain member profiles, employment status, and job skills.
- Create agricultural work schedules with dates, locations, staffing requirements, and wage budgets.
- Assign members manually or generate suggested assignments based on skills, availability, and workload.
- Track assignment progress, daily attendance, completion reports, and administrator verification.
- Record leave and absence, review staffing gaps, and assign replacements when needed.
- Submit and process assignment change requests.
- Manage wage records and payment status.
- Review operational reports and export report data.

## User roles

### Administrators

Administrators manage members, job categories, schedules, and assignments. They can review attendance and completion reports, verify completed work, address change requests and staffing gaps, manage wage payments, and access operational reports.

### Members

Members can view their assigned work and schedules, record attendance, update assignment progress, submit completion reports and change requests, and review their wage and payment status. Contact options for administrators are available when configured.

## Assignment workflow

Assignments typically move through the following statuses:

1. **Assigned:** A member has been assigned to scheduled work.
2. **In progress:** The member has started the work.
3. **Waiting for verification:** The member has submitted a completion report.
4. **Completed:** An administrator has verified the work.

Attendance is recorded for each assignment and work date. Wage records are managed after the applicable completion and verification requirements are met.

## Technology

- PHP 8.3 or later
- Laravel 13
- MySQL
- Blade
- Vite and Tailwind CSS for frontend assets

## Production and maintenance

SIMTANI is intended to run with production configuration. Keep environment settings and credentials private, disable debug mode in production, and create administrator accounts through the authorized production process.

Before deploying application updates, back up the production database and uploaded files, then apply only the required database migrations. Do not run demo seeders or destructive database commands against production data.

## Access

Use the production URL provided by the SIMTANI administrator. Sign-in access is managed by the organization.

### Demo accounts

These accounts are created by the demo seeder for local and testing environments only. They are not production credentials.

| Role | Email | Password |
|---|---|---|
| Administrator | `admin1@simtani.test` | `password` |
| Member | `dedi.prasetyo@simtani.local` | `anggota123` |
