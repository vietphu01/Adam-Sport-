# Adam-Sport 

An online sports shop system – a basic e-commerce platform built with vanilla PHP, featuring product management, shopping cart, order handling, and a chatbot integration.

## Key Features

- **Homepage & About**: Display store information.
- **Product Management**: Product listing and detailed views.
- **Shopping Cart**: Add, remove, and update items in the cart.
- **Checkout & Ordering**: Order placement process with order storage.
- **Order Tracking**: Allow customers to check order status.
- **User Authentication**: Registration, login, and profile management.
- **Simple Chatbot**: Online support with conversation state persistence.
- **Database Testing**: `test_db.php` utility to verify database connectivity.

## Technologies Used

- **Language**: PHP 100%
- **Database**: MySQL (configured in `auth.php`)
- **Frontend**: HTML, CSS, JavaScript (embedded within `.php` files)
- **Environment**: Runs on localhost with XAMPP, WAMP, or any PHP-supported server

## Project Structure (Expected)
Adam-Sport-/
├── index.php # Homepage
├── introduce.php # About page
├── products.php # Product listing
├── cart.php # Shopping cart
├── checkout.php # Checkout page
├── order_success.php # Order confirmation
├── orders.php # Order history
├── order_detail.php # Order details
├── order_tracking.php # Order tracking
├── login.php / register.php # Login / Registration
├── profile.php # User profile
├── logout.php # Logout
├── auth.php # Authentication & DB connection
├── contact_process.php # Contact form handler
├── save_chatbot_state.php # Chatbot state persistence
├── test_db.php # Database connection test
├── requirements.txt # Dependencies (if any)
└── README.md # Project documentation

## Installation & Setup Guide

### 1. System Requirements
- PHP >= 7.4
- MySQL >= 5.7
- Web server (Apache / Nginx) or use XAMPP/WAMP

### 2. Installation Steps
1. **Clone the repository**
   ```bash
2. **Import the database

Create a database named adam_sport (or as configured).

Import the SQL file if available – Note: No .sql file is currently present in the repository. You'll need to create the table structures based on the queries in the code.

3.**Configure database connection

Open auth.php (or the main connection file) and update the credentials:

php
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'adam_sport';

4. **Run the application

Place the project folder in htdocs (XAMPP) or www (WAMP).

Access http://localhost/Adam-Sport-/index.php

   git clone https://github.com/vietphu01/Adam-Sport-.git
