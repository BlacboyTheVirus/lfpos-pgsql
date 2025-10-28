# Product Requirements Document (PRD)
# LfPos - Laravel Point of Sale System

**Version:** 1.2  
**Date:** October 2025  
**Author:** Development Team

---

## 1. Executive Summary

LfPos is a comprehensive Point of Sale (POS) system built using Laravel 12 and Filament 3.3. The system provides complete business management capabilities including customer management, product catalog, invoicing, expense tracking, and role-based access control. It's designed specifically for retail businesses that require area-based pricing calculations and comprehensive financial tracking.

### 1.1 Product Vision
To provide small to medium-sized businesses with a robust, user-friendly POS system that handles complex pricing calculations, multi-payment processing, and comprehensive business analytics.

### 1.2 Key Success Metrics
- Streamlined invoice creation with automated calculations
- 100% accurate area-based pricing for products
- Role-based access control for security
- Export capabilities for business reporting
- Multi-payment type support with validation

---

## 2. Product Overview

### 2.1 Target Users
- **Primary:** Retail business owners and managers
- **Secondary:** Store clerks and cashiers
- **Administrative:** System administrators and accountants

### 2.2 Core Business Problem
Traditional POS systems often lack:
- Complex area-based pricing calculations (width × height × unit price)
- Comprehensive payment tracking with multiple payment types
- Role-based permissions for different user levels
- Automatic code generation and business process automation
- Nigerian Naira currency support with proper formatting

### 2.3 Solution Approach
LfPos addresses these challenges through:
- Advanced area-based pricing engine
- Multi-payment processing with validation
- Comprehensive role-based access control (RBAC)
- Automated business process workflows
- Localized currency formatting and business rules

---

## 3. Functional Requirements

### 3.1 User Management & Authentication

#### 3.1.1 User Authentication
- **Login System:** Secure authentication with email/password
- **User Roles:** Super Admin, Admin, and custom roles
- **Permission System:** Granular permissions for all resources
- **Session Management:** Secure session handling with timeout

#### 3.1.2 User Management
- **User Creation:** Admin can create new users with specific roles
- **Profile Management:** Users can update their personal information
- **Role Assignment:** Assign roles and permissions to users
- **User Tracking:** Track who created records with created_by field

### 3.2 Customer Management

#### 3.2.1 Customer Registration
- **Automatic Code Generation:** System generates unique customer codes (CU-0001, CU-0002, etc.)
- **Customer Information:** Name, phone, email, address. (Customer name must be unique)
- **Contact Management:** Phone and email uniqueness validation
- **Walk-in Customer:** Special customer (CU-0001) for walk-in sales (Must pay all invoice in full)

#### 3.2.2 Customer Features
- **Customer Search:** Search by name, code, phone, or email
- **Customer History:** View all invoices and payment history
- **Customer Validation:** Prevent duplicate phone numbers and emails
- **Soft Delete:** Safe deletion with recovery capability

### 3.3 Product Management

#### 3.3.1 Product Catalog
- **Automatic Code Generation:** System generates unique product codes (PR-0001, PR-0002, etc.)
- **Product Information:** Name, description, unit, price, minimum amount
- **Product Status:** Active/inactive status control
- **Unit Pricing:** Price per unit for area calculations
- **Minimum Amount:** The minimum amount the line item of the product must not be less than

#### 3.3.2 Product Features
- **Product Search:** Search by name, code, or description
- **Price Management:** Set unit prices and minimum amounts
- **Product Defaults:** Predefined dimensions for specific product types (SAV, FLEX, TRANSPARENT)
- **Active Status:** Control product visibility in sales interface

### 3.4 Invoice Management

#### 3.4.1 Invoice Creation
- **Automatic Code Generation:** System generates unique invoice codes (IN-00001, IN-00002, etc.)
- **Customer Selection:** Search and select customers with auto-complete
- **Product Addition:** Dynamic product buttons for quick addition
- **Area Calculation:** Automatic calculation based on width × height × unit price × quantity

#### 3.4.2 Invoice Line Items
Must be responsive and usable on mobile or small screen with horizontal scroll
- **Repeater Table Interface:** Professional tabular data entry
- **Dimension Entry:** Width and height input for area calculations
- **Quantity Management:** Support for multiple quantities
- **Real-time Calculations:** Live updates as values change
- **Product Auto-focus:** Automatic field focusing on Width textfield for efficient data entry

#### 3.4.3 Invoice Calculations
- **Subtotal Calculation:** Sum of all line items
- **Discount Application:** Manual discount entry
- **Minimum Amount Enforcement:** Automatic adjustment for products below minimum
- **Auto-rounding:** Round totals to nearest ₦100
- **Grand Total:** Final calculated amount
- **Amount in Words:** Automatic conversion to written amount

#### 3.4.4 Payment Processing
- **Multiple Payment Types:** Cash, Transfer, POS (implemented as type-safe backed enum)
- **PaymentType Enum:** Type-safe enum with Cash, Transfer, and POS cases
- **Multiple Payments:** Support for split payments
- **Payment Validation:** Prevent overpayment with enum-based validation
- **Walk-in Validation:** Enforce full payment for walk-in customers
- **Payment History:** Track all payment transactions with enum-based type tracking
- **Color Coding:** Automatic color assignment per payment type (Cash: green, Transfer: blue, POS: orange)

#### 3.4.5 Enhanced Payment Validation
- **Repeater Table Integration:** Fixed validation for existing payments in edit mode
- **Database Record Validation:** Validates actual database records vs form data
- **Walk-in Customer Logic:** Improved validation for walk-in customer payments
- **Form State Management:** Proper handling of relationship-based form components

#### 3.4.6 Invoice Status Management
- **Automatic Status:** Unpaid, Partial, Paid based on payment amounts
- **Status Indicators:** Color-coded status badges
- **Due Amount Tracking:** Automatic calculation of outstanding amounts
- **Payment Reconciliation:** Real-time payment vs invoice matching

### 3.5 Expense Management

#### 3.5.1 Expense Tracking
- **Automatic Code Generation:** System generates unique expense codes (EX-0001, EX-0002, etc.)
- **Autosuggest Expense Description:** Suggest entries based on already saved entries
- **Expense Categories:** Categorize expenses for reporting
- **Date Tracking:** Record expense dates for accurate reporting
- **Amount Recording:** Track expense amounts in Nigerian Naira
- **Notes:** Additional information and descriptions

#### 3.5.2 Expense Features
- **Expense Search:** Search by code, category, or description
- **Date Filtering:** Filter expenses by date ranges
- **Category Management:** Organize expenses by categories
- **Creator Tracking:** Track who recorded each expense

### 3.6 Settings Management

#### 3.6.1 System Configuration
- **Application Settings:** Configure system-wide settings
- **Business Information:** Company details and branding
- **Currency Settings:** Nigerian Naira formatting and symbols
- **User Preferences:** Configurable user interface options

#### 3.6.2 Bank Account Integration
- **Bank Account Settings:** Configurable bank account details for invoices
- **Dynamic Invoice Updates:** Bank details automatically pulled from settings
- **Centralized Management:** Single source of truth for bank information
- **Invoice Templates:** Both screen and print templates use dynamic bank settings
- **Settings Seeder:** Bank account details included in database seeding

### 3.7 Reporting & Export

#### 3.7.1 Export Capabilities
- **CSV Export:** Export data in CSV format
- **Excel Export:** Export data in XLSX format
- **PDF Export:** Generate PDF reports with professional formatting
- **Bulk Export:** Export selected records or all records
- **Date Range Exports:** Export data for specific time periods

#### 3.7.2 Report Types
- **Invoice Reports:** Comprehensive invoice summaries
- **Payment Reports:** Payment tracking and reconciliation
- **Customer Reports:** Customer transaction history
- **Product Reports:** Product sales analysis
- **Expense Reports:** Expense tracking and categorization

---

## 4. Non-Functional Requirements

### 4.1 Performance Requirements
- **Page Load Time:** < 3 seconds for all pages
- **Database Queries:** Optimized queries with proper indexing
- **Real-time Calculations:** Instant updates for form calculations
- **Bulk Operations:** Efficient handling of large datasets

### 4.2 Security Requirements
- **Authentication:** Secure login with session management
- **Authorization:** Role-based access control (RBAC)
- **Data Protection:** Secure handling of business data
- **Input Validation:** Comprehensive validation for all inputs

### 4.3 Usability Requirements
- **Responsive Design:** Mobile-friendly interface
- **Keyboard Shortcuts:** Efficient keyboard navigation
- **Auto-focus:** Automatic field focusing for data entry
- **Search Functionality:** Global search across all resources

### 4.4 Reliability Requirements
- **Data Integrity:** ACID transactions for financial data
- **Backup & Recovery:** Database backup strategies
- **Error Handling:** Graceful error handling and user feedback
- **Data Validation:** Comprehensive validation rules

---

## 5. Technical Architecture

### 5.1 Backend Technology Stack
- **Framework:** Laravel 12.20.0
- **PHP Version:** 8.4.1
- **Database:** PostgreSQL
- **Admin Panel:** Filament 4
- **Authentication:** Laravel Sanctum/Session
- **Permissions:** Spatie Laravel Permission with Filament Shield
- **Money Handling:** Custom MoneyCast for precision cent-based storage

### 5.2 Frontend Technology Stack
- **UI Framework:** Livewire 3.6.3
- **Component Library:** Flux UI Free 2.2.3
- **Styling:** Tailwind CSS 4.0.7
- **JavaScript:** Alpine.js (included with Livewire)
- **Build Tool:** Vite

### 5.3 Key Packages & Libraries
- **PDF Generation:** Barryvdh/Laravel-DomPDF for PDF exports
- **Code Formatting:** Laravel Pint 1.24.0
- **Testing:** Pest 3.8.2
- **Real-time Features:** Livewire Volt

### 5.4 Database Design

#### 5.4.1 Core Tables
- **users:** User management and authentication
- **customers:** Customer information and management
- **products:** Product catalog and pricing
- **invoices:** Invoice headers and totals
- **invoice_products:** Invoice line items with dimensions
- **invoice_payments:** Payment tracking and reconciliation
- **expenses:** Expense tracking and categorization
- **settings:** System configuration

#### 5.4.2 Permission Tables (Spatie)
- **roles:** User roles definition
- **permissions:** Granular permissions
- **role_has_permissions:** Role-permission assignments
- **model_has_roles:** User-role assignments

#### 5.4.3 Key Database Features
- **Foreign Keys:** Proper relational integrity
- **Soft Deletes:** Safe deletion with recovery
- **Timestamps:** Automatic created_at/updated_at tracking
- **Unique Constraints:** Prevent duplicate codes and contacts
- **Indexes:** Optimized query performance

### 5.5 Enum Architecture

#### 5.5.1 Backed Enums
- **InvoiceStatus:** Enum for invoice status (Unpaid, Partial, Paid) with color coding
- **PaymentType:** Backed enum for payment types (Cash, Transfer, POS) with type safety
- **ExpenseCategory:** Backed enum for expenses category (Miscellaneous, Materials Utilities, Repairs & Cleaning, Staff)

#### 5.5.2 PaymentType Enum Features
- **Type Safety:** Compile-time validation of payment types
- **String Backing:** Database storage as strings, not database enums
- **Method Support:** getLabel(), getOptions(), getColor() helper methods
- **Form Integration:** Seamless integration with Filament form components
- **Color Coding:** Automatic UI color assignment (Cash: success, Transfer: info, POS: warning)

#### 5.5.3 Enum Benefits
- **Maintainability:** Single source of truth for enumerated values
- **Type Safety:** IDE support and compile-time checking
- **Extensibility:** Easy addition of new cases and methods
- **Database Flexibility:** No database-level enum constraints
- **UI Integration:** Automatic form options and color assignments

---

## 6. User Interface Design

### 6.1 Design Principles
- **Clean & Modern:** Professional business interface
- **Responsive:** Mobile-first responsive design
- **Accessible:** WCAG 2.1 accessibility standards
- **Consistent:** Unified design language across all features

### 6.2 Navigation Structure
- **Main Navigation:** Customers, Products, Invoices (All Invoices, Create Invoice), Expenses, Users, Settings
- **Global Search:** Search across all resources with keyboard shortcuts
- **Quick Actions:** Create buttons with keyboard shortcuts
- **User Menu:** Profile management and logout

### 6.3 Key UI Components
- **Dashboard:** Overview of business metrics
- **Data Tables:** Sortable, filterable, searchable tables
- **Forms:** Professional form layouts with validation
- **Modals:** Contextual actions and confirmations
- **Notifications:** User feedback and system messages

#### 6.3.1 Enhanced Global Search
- **Search Slide Effects:** 8px horizontal slide animation with green border
- **Visual Feedback:** Smooth animations on search interaction
- **Keyboard Integration:** Cmd+K shortcut with proper scope limitations
- **Modal Search:** Enhanced search experience in dialog containers
- **Targeted Animation:** Specific to global search, excludes table search fields

### 6.4 Color Scheme & Branding
- **Primary Color:** Lime (defined in Filament config)
- **Currency Symbol:** ₦ (Nigerian Naira)
- **Status Colors:** Color-coded invoice statuses
- **Brand Logo:** Configurable company branding

---

## 7. Business Rules & Logic

### 7.1 Pricing Rules
- **Area Calculation:** Price = Width × Height × Unit Price × Quantity
- **Minimum Amount:** Products have minimum order amounts
- **Auto-rounding:** Grand totals rounded to nearest ₦100
- **Currency Format:** All amounts displayed in Nigerian Naira (₦) without decimals
- **Money Storage:** Stored as cents in database, converted to dollars for display
- **Export Format:** Plain numbers without currency symbols for CSV/Excel

### 7.2 Payment Rules
- **Payment Types:** Cash, Transfer, POS (backed by PaymentType enum)
- **Type Safety:** Compile-time validation of payment types using backed enum
- **Payment Validation:** Cannot exceed invoice total with enum-based validation
- **Walk-in Rules:** Walk-in customers must pay in full
- **Multiple Payments:** Support for split payments with type-safe tracking
- **Payment History:** Immutable payment records with enum-based type storage
- **Default Type:** Cash is the default payment type for new payments

### 7.3 Customer Rules
- **Walk-in Customer:** Special customer (CU-0001) for anonymous sales
- **Contact Uniqueness:** Phone and email must be unique
- **Credit Tracking:** Track outstanding amounts per customer
- **Customer History:** Complete transaction history

### 7.4 Code Generation Rules
When a record is created, the form displays the next available code in readiness for the next record.
- **Customer Codes:** CU-0001, CU-0002, CU-0003...
- **Product Codes:** PR-0001, PR-0002, PR-0003...
- **Invoice Codes:** IN-00001, IN-00002, IN-00003...
- **Expense Codes:** EX-0001, EX-0002, EX-0003...

---

## 8. Security & Permissions

### 8.1 Role-Based Access Control (RBAC)
- **Super Admin:** Full system access and configuration
- **Admin:** Business operations and user management
- **Custom Roles:** Configurable roles with specific permissions

### 8.2 Permission Structure
- **Resource Permissions:** view, view_any, create, update, delete, delete_any
- **Advanced Permissions:** restore, restore_any, replicate, reorder, force_delete, force_delete_any
- **Granular Control:** Permissions for Customers, Products, Invoices, Expenses, Users, Settings

### 8.3 Security Features
- **Authentication:** Secure login with session management
- **Authorization:** Permission-based access control
- **Data Validation:** Comprehensive input validation
- **CSRF Protection:** Cross-site request forgery protection
- **SQL Injection Prevention:** Eloquent ORM protection

#### 8.3.1 Enhanced Deletion Security
- **DELETE Confirmation:** Type "DELETE" confirmation for role deletions
- **Bulk Operation Security:** Confirmation required for bulk role deletions
- **Super Admin Protection:** Super admin roles cannot be deleted
- **Modal Confirmations:** Clear warning dialogs for destructive operations
- **Case-sensitive Validation:** Exact "DELETE" text required to prevent accidents

---

## 9. Data Management

### 9.1 Data Integrity
- **Transactions:** ACID compliance for financial operations
- **Foreign Keys:** Relational integrity constraints
- **Validation:** Comprehensive validation rules
- **Soft Deletes:** Safe deletion with recovery options

### 9.2 Data Export & Import
- **Export Formats:** CSV, Excel, PDF
- **Money Field Exports:** Plain numbers without currency symbols or formatting for spreadsheet calculations
- **Bulk Operations:** Handle large datasets efficiently
- **Date Range Exports:** Filter by specific time periods
- **Formatted Reports:** Professional report layouts with proper currency formatting

### 9.3 Backup & Recovery
- **Database Backups:** Regular automated backups
- **Data Recovery:** Restore capabilities for deleted records
- **Audit Trail:** Track who created/modified records
- **Version Control:** Track changes to important records

---

## 10. Testing & Quality Assurance

### 10.1 Testing Strategy
- **Unit Testing:** Test individual components and methods
- **Feature Testing:** Test complete user workflows
- **Integration Testing:** Test system integrations
- **Performance Testing:** Ensure system performance standards

### 10.2 Testing Tools
- **Pest Framework:** Modern PHP testing framework
- **Database Testing:** Factory-based test data generation
- **Feature Tests:** Complete user workflow testing
- **Browser Testing:** End-to-end user interface testing

### 10.3 Code Quality
- **Laravel Pint:** Automated code formatting
- **Static Analysis:** Code quality analysis
- **Code Reviews:** Peer review process
- **Documentation:** Comprehensive code documentation

---

## 11. Implementation Status

### 11.1 Completed Features ✅
- User Management: Complete RBAC with Filament Shield
- Customer Management: Full CRUD with unique codes and validation, Walk-in customer exemption in widgets
- Product Management: Complete catalog with area-based pricing
- Invoice Management: Complex invoicing with real-time calculations
- Payment Processing: Multi-payment support with type-safe PaymentType enum, fixed enum display issues
- Expense Tracking: Complete expense management system
- Export System: CSV, Excel, and PDF export capabilities with clean number formatting
- Security System: Role-based permissions for all resources including Invoices
- Enum Architecture: Type-safe backed enums for payment types and statuses
- UI/UX: Responsive design with keyboard shortcuts, right-aligned money columns
- Database Design: Optimized schema with proper relationships
- Money Formatting: Consistent currency display without decimals across all interfaces
- Bank Account Integration: Dynamic bank details in invoice templates from settings
- Enhanced Security: DELETE confirmation dialogs for role management
- Global Search Effects: Animated search interactions with visual feedback
- Payment Validation Fixes: Improved walk-in customer payment validation

### 11.2 Key Technical Achievements ✅
- Area-based Pricing: Complex width × height × price calculations
- Real-time Updates: Live form calculations and status updates
- Form Repeater Integration: Professional tabular data entry
- Auto-rounding: Automatic total rounding to nearest ₦100
- Walk-in Customer Logic: Special handling for anonymous sales, excluded from widgets
- Currency Localization: Nigerian Naira formatting without decimals throughout
- Keyboard Shortcuts: Efficient data entry workflows
- Mobile Responsiveness: Full mobile interface support
- PaymentType Enum: Type-safe payment types with compile-time validation and proper label display
- RBAC Integration: Complete Invoice resource integration with Shield permissions
- Enum-based UI: Automatic form options and color coding from enums
- Money Precision: Custom MoneyCast for accurate cent-based storage and conversion
- Export Optimization: Clean number formatting for spreadsheet compatibility
- Settings Integration: Dynamic bank account details in invoice system
- Advanced Security: Type "DELETE" confirmation for destructive operations
- UI Animations: Smooth search slide effects with proper scoping
- Payment Form Fixes: Repeater relationship validation improvements

### 11.3 System Architecture ✅
- Laravel 12: Latest framework version with modern features
- Filament 4.1: Advanced admin panel with customizations
- PostgreSQL Database: Optimized schema with proper indexing
- Livewire 3.6: Real-time interface updates
- Tailwind CSS 4.0: Modern styling framework
- Spatie Permissions: Comprehensive RBAC system with Shield integration
- Backed Enums: Type-safe enums for payment types, invoice status, and product defaults
- Modern PHP 8.4: Latest PHP features including backed enums and match expressions

---

## 12. Future Enhancements

### 12.1 Potential Features
- **Inventory Management:** Stock tracking and alerts
- **Barcode Scanning:** Product identification and speed
- **Email Notifications:** Automated customer communications
- **Dashboard Analytics:** Business intelligence and reporting
- **Multi-location Support:** Support for multiple store locations

### 12.2 Technical Improvements
- **API Development:** RESTful API for external integrations
- **Mobile App:** Native mobile application
- **Advanced Reporting:** Custom report builder
- **Automated Backups:** Scheduled backup system
- **Performance Optimization:** Query optimization and caching

### 12.3 Business Enhancements
- **Loyalty Programs:** Customer reward systems
- **Promotions:** Discount and promotion management
- **Supplier Management:** Vendor and purchase order tracking
- **Multi-currency:** Support for multiple currencies
- **Tax Management:** Automated tax calculations

---

## 13. Conclusion

LfPos represents a comprehensive, modern Point of Sale solution specifically designed for businesses requiring complex pricing calculations and robust financial management. The system successfully addresses the unique needs of retail businesses with area-based pricing while providing enterprise-level security, reporting, and user management capabilities.

The implementation demonstrates best practices in Laravel development, modern UI/UX design, and comprehensive business logic handling. The system is production-ready and provides a solid foundation for future enhancements and scaling.

### 13.1 Key Strengths
- **Complex Business Logic:** Successfully handles area-based pricing calculations
- **Comprehensive Security:** Full RBAC implementation with granular permissions
- **Professional UI:** Modern, responsive interface with excellent UX
- **Data Integrity:** Robust validation and transaction handling
- **Export Capabilities:** Professional reporting and data export features
- **Nigerian Market:** Proper localization for Nigerian business practices

### 13.2 Technical Excellence
- **Modern Stack:** Latest Laravel, Filament, and supporting technologies
- **Clean Architecture:** Well-structured codebase following best practices
- **Performance:** Optimized queries and efficient data handling
- **Testing:** Comprehensive test coverage with modern tools
- **Security:** Enterprise-level security implementation

---

*This PRD serves as the complete specification for the LfPos Point of Sale System, providing detailed requirements for development, testing, and deployment.*