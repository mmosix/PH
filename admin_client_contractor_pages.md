# Admin, Client, and Contractor Pages Documentation

## Admin Pages

### Projects (public/admin/projects.php)
- Access: Admin only (`Auth::checkRole(['admin'])`)
- Functionality: View all projects in the system using `Project::getAll()`
- No filtering - shows complete project list

## Client Pages

### Projects (public/client/projects.php)
- Access: Client only (`Auth::checkRole(['client'])`)
- Functionality: View projects associated with the logged-in client
- Filtered by client ID using `Project::getByClient($userId)`

## Contractor Pages

### Projects (public/contractor/projects.php)
- Access: Contractor only (`Auth::checkRole(['contractor'])`)
- Functionality: View projects assigned to the logged-in contractor
- Filtered by contractor ID using `Project::getByContractor($userId)`

## Shared Pages

### Documents (public/documents.php)
- Access: All roles (`Auth::checkRole(['admin', 'client', 'contractor'])`)
- Functionality:
  - View documents associated with a project
  - Upload new documents
  - Uses `FileUpload` class for handling document operations

### Payments (public/payments.php)
- Access: All roles (`Auth::checkRole(['admin', 'client', 'contractor'])`)
- Functionality:
  - View payments related to the user
  - Integrates with blockchain services (`BlockchainService`)
  - Queries payments from database filtered by user ID

### Reports (public/reports.php)
- Access: All roles (`Auth::checkRole(['admin', 'client', 'contractor'])`)
- Functionality:
  - Generate financial reports
  - Generate project reports
  - Reports are customized based on user role and ID

## Authentication Pages
- login.php: User authentication
- register.php: New user registration
- forgot-password.php: Password recovery
- reset-password.php: Password reset
- verify-email.php: Email verification
- resend-verification.php: Resend verification email

## Common Features Across CRUD Pages
1. Role-based access control using `Auth::checkRole()`
2. Session-based user identification
3. Database connectivity through PDO
4. Project-specific operations through `Project` class
5. Document management through `FileUpload` class
6. Reporting capabilities through `Report` class
7. Blockchain integration for payments