
markdown_content = """# CYRUSS TECHGEAR HUB: INVENTORY MANAGEMENT SYSTEM (IMS)

A lightweight, role-based PHP web application designed to track warehouse products, manage employee directories, and maintain automated administrative audit logs.

---

## 1. Project Overview
The Inventory Management System (IMS) provides an intuitive dashboard interface for real-time warehouse oversight. It features strict role-based access controls (RBAC), secure user session handling, and structured database asset transaction pipelines to ensure data integrity and track personnel actions.

### Key Features
* **Role-Based Security Layer:** Restrictions enforced on both UI generation and processing endpoints based on account privileges (`Admin` vs. `Staff`).
* **Visual Low-Stock Safeguards:** System highlights product configurations when quantity metrics dip below safe fallback targets.
* **Automated Activity Logging:** Creation of tracking strings inside a dynamic schema to establish strict audit trails for structural user actions.
* **Decoupled System CRUD Operations:** Modulating structural database shifts (`add`, `edit`, `delete`) inside isolated routing scripts away from view panels.

---

## 2. Directory & Architecture Map

Below is the tree topology representing the source distribution of the development environment workspace:

## 3. Core Engine Architecture & Workflows
A. Gatekeeper Layer (auth/)
Every core tracking dashboard screen (dashboard.php, inventory.php, users_management.php, settings.php) invokes a centralized state validator block at its initial execution runtime array line:

The engine checks for a valid authenticated token via $_SESSION['user_id'].

If absent, runtime code parsing halts instantly and issues an explicit root redirect header pointing to auth/index.php.

Logging out executes an explicit session array wipe, clearing volatile system tokens completely.

B. Transaction Management Pipeline (config/)
Data operations route systematically through the central abstraction reference provided by config/db.php.

State Persistence Engine: Leverages the PHP Data Objects (PDO) architecture interface model wrapper layer.

SQL Injection Mitigation: Queries utilizing variable inputs from external vectors use strict execution array bindings ($conn->prepare()), fully isolating input variables from interpretation engines.

C. Decoupled Product Management Operations (actions/)
Rather than creating heavy functional configurations inside view pages, mutation scripts run inside isolated routing boundaries:

Creation & Update: Both add_product.php and update_product.php filter raw context inputs, sanitize parameter strings, and handle dynamic multipart disk file operations targeting the internal folder path (assets/img/products/).

Deletion: delete_product.php drops data entries mapped explicitly via verified unique query values.

## 4. Security Permissions & Role MatrixThe
 platform checks authorization profiles at runtime using session parameters ($_SESSION['role']) to grant or deny access to system actions.Interface Module / Script LocationTarget AudienceAllowed Functional Operationsauth/index.phpGuest / PublicSubmits login attempts; authenticates unique string keys.dashboard.phpStaff, AdminInteracts with metrics blocks and system low-stock visual warning summaries.inventory.phpStaff, AdminCore access to the product catalog list. Interacts with /actions/ routes.users_management.phpAdmin OnlyModifies the system account directory; creates employee accounts (default key: Welcome123); processes profile row deletions.

## 5. System Configuration & Setup Requirements
To host or configure this system within a local or production infrastructure, ensure your server environment matches the following specification profile:

Prerequisites
Web Server Host Engine: Apache 2.4+ or Nginx server setup.

Interpreter Engine: PHP 8.0 or higher configured with the pdo_mysql extension enabled in php.ini.

Database Platform: MySQL 5.7+ or MariaDB 10.3+ database system.

Quick Deployment Instructions
Clone the Repository: Clone the repository workspace layout directly into your local web root folder directory (e.g., /xampp/htdocs/ or /var/www/html/).

Database Schema Setup: Run your infrastructure setup scripts or use a database management client to import your core SQL schema backup file into your target database server.

Database Connectivity Configuration: Open config/db.php and update the parameters to match your local host address credentials:

PHP
# Example config snippet inside config/db.php

**$host = 'localhost';**

**$dbname = 'inventory_db';**

**$username = 'root';**

**$password = "";**


 Permissions Verification: Ensure your web server configuration has read/write privileges for the file-upload directory location **(assets/img/products/)** to allow dynamic asset writing actions.