# Registration System Implementation Guide

## Overview
This document outlines the implementation of a custom registration code system for WordPress that allows administrators to generate and manage unique registration codes for different user roles.

## Current Status
- The registration system is partially implemented but not yet fully functional
- The main class file exists at: `/includes/registration/class-registration-codes.php`
- The system needs to be properly integrated into the theme

## Required Files
1. **Main Class**: `includes/registration/class-registration-codes.php`
   - Handles core registration code functionality
   - Manages database operations
   - Provides admin interface

2. **Admin CSS**: `includes/registration/css/admin.css`
   - Styles for the registration codes admin interface

3. **Admin JS**: `includes/registration/js/admin.js`
   - Handles AJAX requests for code generation and validation

## Database Structure
The system uses a custom table `wp_registration_codes` with the following structure:
- `id` (int) - Primary key
- `code` (varchar) - Unique registration code
- `role` (varchar) - WordPress user role
- `is_used` (tinyint) - Whether the code has been used
- `used_by` (bigint) - User ID of the user who used the code
- `used_at` (datetime) - When the code was used
- `created_at` (datetime) - When the code was created
- `created_by` (bigint) - User ID who created the code

## Implementation Steps

### 1. Database Setup
The system includes a method to create the necessary database table. This should be run on plugin activation.

### 2. Integration with Theme
Add the following to `functions.php`:

```php
// Load registration system
if (file_exists(get_stylesheet_directory() . '/includes/registration/class-registration-codes.php')) {
    require_once get_stylesheet_directory() . '/includes/registration/class-registration-codes.php';
    Registration_Codes::get_instance();
}


''''''''''''''''''''''

<div class="wrap ccr-admin-page" bis_skin_checked="1">
    <h1>Teacher Dashboard</h1>
    
    <div class="ccr-admin-tabs" bis_skin_checked="1">
        <a href="?page=registration-codes" class="nav-tab">Manage Codes</a>
        <a href="?page=teacher-dashboard" class="nav-tab nav-tab-active">Teacher Dashboard</a>
    </div>
    
    <div class="ccr-admin-container" bis_skin_checked="1">
        <div class="ccr-admin-section ccr-generate-codes" bis_skin_checked="1">
            <h2>Generate New Codes for Students</h2>
            
            <form id="ccr-generate-form" class="ccr-form">
                <div class="ccr-form-row" bis_skin_checked="1">
                    <label for="ccr-code-count">Number of Codes:</label>
                    <select id="ccr-code-count" name="count">
                        <option value="10">10</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="custom">Custom</option>
                    </select>
                    <input type="number" id="ccr-custom-count" name="custom_count" min="1" max="1000" value="10" style="display:none;">
                </div>
                
                <div class="ccr-form-row" bis_skin_checked="1">
                    <label for="ccr-group-name">Class/Group Name:</label>
                    <input type="text" id="ccr-group-name" name="group" list="ccr-groups" placeholder="e.g., Class 10A 2025">
                    <datalist id="ccr-groups">
                                            </datalist>
                </div>
                
                <div class="ccr-form-actions" bis_skin_checked="1">
                    <button type="submit" class="button button-primary">Generate Codes</button>
                    <span class="spinner"></span>
                </div>
            </form>
            
            <div id="ccr-generate-results" style="display:none;" bis_skin_checked="1">
                <h3>Generated Codes</h3>
                <div class="ccr-results-info" bis_skin_checked="1"></div>
                <textarea id="ccr-generated-codes" readonly="" rows="10"></textarea>
                <div class="ccr-results-actions" bis_skin_checked="1">
                    <button id="ccr-copy-codes" class="button">Copy to Clipboard</button>
                    <button id="ccr-download-codes" class="button">Download CSV</button>
                </div>
            </div>
        </div>
        
        <div class="ccr-admin-section ccr-student-list" bis_skin_checked="1">
            <h2>Student Management</h2>
            
            <div class="ccr-filters" bis_skin_checked="1">
                <form method="get" class="ccr-filter-form">
                    <input type="hidden" name="page" value="teacher-dashboard">
                    
                    <div class="ccr-filter-row" bis_skin_checked="1">
                        <label for="ccr-filter-group">Class/Group:</label>
                        <select id="ccr-filter-group" name="group">
                            <option value="">All Classes</option>
                                                    </select>
                        
                        <button type="submit" class="button">Filter</button>
                        <a href="?page=teacher-dashboard" class="button">Reset</a>
                    </div>
                </form>
            </div>
            
            <div class="ccr-export-section" bis_skin_checked="1">
                <h3>Export Student Codes</h3>
                <form id="ccr-export-form" class="ccr-form">
                    <div class="ccr-form-row" bis_skin_checked="1">
                        <label for="ccr-export-group">Class/Group:</label>
                        <select id="ccr-export-group" name="group">
                            <option value="">All Classes</option>
                                                    </select>
                    </div>
                    
                    <div class="ccr-form-actions" bis_skin_checked="1">
                        <button type="submit" class="button button-primary">Export Student Codes</button>
                        <span class="spinner"></span>
                    </div>
                </form>
            </div>
            
                            <div class="ccr-no-results" bis_skin_checked="1">
                    <p>No registration codes found.</p>
                </div>
                    </div>
    </div>
</div>