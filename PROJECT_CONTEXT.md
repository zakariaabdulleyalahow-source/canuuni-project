# PROJECT CONTEXT AND DEVELOPMENT INSTRUCTIONS

## 1. Project Overview

This project is a PHP-based web application.

It is NOT a React, Next.js, Node.js or Vite application.

The project must remain a traditional PHP web application.

## 2. Technology Stack

- Backend: PHP 8.4
- Database: MySQL
- Database connection: PHP PDO
- Frontend: HTML5
- Styling: CSS3
- UI Framework: Bootstrap 5
- Client-side functionality: Vanilla JavaScript
- Web Server: Apache
- Local Development Environment: XAMPP

## 3. Project Architecture

The application uses the following structure:

- admin/ — Administration panel
- ajax/ — AJAX requests and processing
- assets/ — CSS, JavaScript, images and other assets
- config/ — Database configuration and application settings
- customer/ — Customer-related functionality
- includes/ — Shared PHP components
- index.php — Main application entry point

## 4. Strict Development Rules

1. Preserve PHP as the primary backend language.
2. Preserve the existing MySQL database architecture.
3. Use PDO for database operations.
4. Continue using Bootstrap 5.
5. Do not migrate the application to React.
6. Do not replace PHP with Node.js.
7. Do not introduce Next.js or Vite.
8. Do not unnecessarily create package.json.
9. Preserve existing functionality when updating the project.
10. Do not delete existing files without authorization.
11. Do not change database schemas without explaining the changes first.
12. Keep the application compatible with XAMPP and Apache.

## 5. Development Environment

The application is intended to run locally using:

http://localhost/project-folder/

Apache and MySQL must be running through XAMPP.

## 6. Instructions for AI

Before modifying the project:

- Read this document.
- Inspect the existing source code.
- Understand the folder structure.
- Identify the existing database connections.
- Understand how authentication and user roles work.
- Explain the proposed changes before implementing them.

Do not rebuild the application using a different technology stack.

## 7. Main Objective

Improve and maintain the existing PHP + Bootstrap + MySQL application while preserving its architecture, existing data and functionality.
