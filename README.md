# GlowWell Beauty Clinic Management System

A web-based beauty clinic management system developed to simplify appointment bookings and daily administrative operations.

## Project Overview

GlowWell Beauty Clinic Management System is a PHP and MySQL-based application designed to manage customer appointments, staff members, beauty services and staff schedules.

The system provides separate functionality for customers and administrators, helping to organise clinic operations and improve appointment management.

**Developer:** Thuto Tlhabane  
**Project Type:** Full-stack Web Application  
**Development Status:** Fully functional and tested locally using XAMPP.

## Technologies Used

- PHP
- MySQL / MariaDB
- HTML5
- CSS3
- JavaScript
- PDO
- XAMPP

## Key Features

### Customer Features
- Customer registration and login.
- Customer dashboard.
- Appointment booking.
- View existing appointments.
- Update customer profile.

### Administrator Features
- Administrator login.
- Administrative dashboard.
- Customer appointment management.
- Staff management.
- Beauty service management.
- Staff schedule management.
- Reports.

## Database Structure

The system uses six main database tables:

1. Customers
2. Admins
3. Staff
4. Services
5. Appointments
6. Staff Schedules

The database structure is included in the `Database` folder.

## Appointment Validation

The system includes booking validation to help prevent scheduling conflicts. This includes:

- Checking that appointment dates are in the future.
- Checking staff availability for the selected weekday.
- Checking working hours.
- Checking for conflicting pending or confirmed appointments.

## Security Features

Security measures implemented include:

- Password hashing.
- Prepared SQL statements.
- Session management.
- Role-based access control.
- CSRF protection on supported forms.
- Input validation.

These measures have not been independently security-audited.

## Screenshots

The `Screenshots` folder contains visual examples of the system's interface and functionality.

### Homepage

![GlowWell Homepage](Screenshots/GlowWell%20Homepage.PNG)

### Customer Registration

![Customer Registration](Screenshots/Customer%20Registration%20page.PNG)

### Appointment Booking

![Appointment Booking](Screenshots/Appointment%20booking%20page.PNG)

### Admin Dashboard

![Admin Dashboard](Screenshots/Admin%20Dashboard.PNG)

### Staff Management

![Staff Management](Screenshots/Staff%20management.PNG)

### Services Management

![Services Management](Screenshots/Services%20management.PNG)

### Appointment Management

![Admin Appointment Management](Screenshots/Admin%20appointment%20management.PNG)

### Staff Schedule Management

![Current Staff Schedule](Screenshots/Current%20Staff%20Schedule.PNG)

### Database Structure

![GlowWell Database Tables](Screenshots/Glowwell%20database%20tables.PNG)

## Testing

The application was tested locally using XAMPP. The project user guide records 16 manual test cases as passed.

## Installation

1. Install XAMPP with Apache, PHP and MySQL/MariaDB.
2. Copy the project folder into the XAMPP `htdocs` directory.
3. Start Apache and MySQL.
4. Create a database named `glowwell_db` in phpMyAdmin.
5. Import `Database/glowwell_db.sql`.
6. Copy config.example.php and rename the copy to config.php. Update the database credentials in config.php to match your local MySQL setup.
7. Update the database credentials in your local configuration file.
8. Open the application in your browser using `http://localhost/glowwell/`.

**Important:** Never upload your real database configuration file or credentials to a public repository.

## Future Improvements

- Email and SMS appointment notifications.
- Automated testing.
- Downloadable administrative reports.
- Improved mobile accessibility.
- Database backup functionality.
- Improved handling of simultaneous booking requests.

## Project Repository

[View GlowWell on GitHub](https://github.com/thuto-tlhabane/GlowWell-Beauty-Clinic-System)

---

Developed by **Thuto Tlhabane**.
