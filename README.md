# Smart Queue System

A web-based smart cashier queuing system developed as a capstone project for managing client queues, service categories, queue tickets, and cashier transactions.

## Overview

The **Smart Queue System** is a web-based application designed to improve and streamline the cashier queuing process. It provides a centralized platform for registering clients, generating queue tickets, managing service categories, monitoring queue status, and controlling cashier transactions.

The system aims to reduce physical queue congestion, improve the organization of cashier transactions, and provide clients with a convenient way to monitor their queue status.

## Key Features

* Client registration and queue ticket generation
* Service category management
* Unique queue number generation
* QR code generation for queue monitoring
* Priority queue handling
* Real-time queue status monitoring
* Cashier queue management and control
* Queue calling, skipping, recalling, and completion
* Public queue display for current and upcoming queue numbers
* Estimated waiting time monitoring
* Printable queue receipts
* Silent receipt printing through a local USB ESC/POS print service
* Role-based access for authorized personnel
* Administrative management and monitoring

## System Modules

The system consists of the following major modules:

### Front-Desk Module

Allows authorized front-desk personnel to register clients, select service categories, generate queue numbers, and print queue receipts.

### Public Queue Monitoring Module

Allows clients to monitor their queue status without creating an account. Clients can access the queue information through the QR code provided on their queue receipt.

### Cashier Controller Module

Allows cashier personnel to manage the active queue, call the next client, skip or recall queue numbers, and update transaction statuses.

### Display Module

Displays the current queue number, cashier or service information, and upcoming queue numbers for clients waiting in the service area.

### Administrative Module

Provides authorized administrators with tools for managing users, system information, and other administrative functions.

## Technology Stack

* **Backend:** Laravel 12
* **Frontend:** React with Inertia.js
* **Styling:** Tailwind CSS
* **Database:** MySQL
* **Build Tool:** Vite
* **Authentication:** Laravel Sanctum
* **Queue Receipt Printing:** ESC/POS-compatible thermal printer
* **Local Print Service:** Windows USB print service

## Project Documentation

Additional project documentation and technical guides are available in the repository:

* [Deployment Testing Runbook](./DEPLOYMENT-TESTING.md)
* [Project Features — What, Why, How](./PROJECT-FEATURES-WHAT-WHY-HOW.md)

## Local Print Service

A companion Windows USB ESC/POS print service is included in the [`print-service/`](./print-service/README.md) directory.

The print service allows the Laravel application to communicate with a locally connected thermal printer and silently print queue receipts without requiring users to manually open a print dialog.

For installation and configuration instructions, refer to the [Print Service README](./print-service/README.md).

## Getting Started

### 1. Clone the Repository

Clone this repository to your local development environment.

### 2. Install Laravel Dependencies

Install the required PHP dependencies using Composer.

### 3. Install Frontend Dependencies

Install the required Node.js dependencies using npm.

### 4. Configure the Environment

Create and configure the application's `.env` file with the required database and application settings.

### 5. Set Up the Database

Create the required MySQL database and run the Laravel database migrations.

### 6. Run the Application

Start the Laravel development server and the required frontend development/build processes.

Refer to the project documentation for detailed deployment and testing procedures.

## Source Code Repository

The complete source code of the **Smart Queue System** is available through the project's GitHub repository.

**GitHub Repository:**
`https://github.com/joshiecakes1505/Smart-Queue-System`

> Replace the placeholder above with the official GitHub repository URL before submitting the project documentation.

## Capstone Project

This system was developed as part of a capstone project in **Bachelor of Science in Information Systems**.

**Project:** Smart Cashier Queuing System
**Institution:** Batangas Eastern Colleges (BEC)
**Developer**: Joshua Allan A. Lorzano

The repository contains the source code and supporting technical documentation required for the development, deployment, testing, and maintenance of the system.
