# Goals API (PHP + MySQL)

A minimal REST API for managing personal financial goals.  
Built with **Slim 4**, **PHP**, and **MySQL**, featuring JWT authentication, input validation, and Swagger docs.

---

## 🚀 Features
- **Authentication** (email/password login with JWT access + refresh tokens)
- **Goals CRUD** (scoped to authenticated user)
- **Validation & Errors** (consistent error schema, numeric ranges, required fields)
- **Security** (prepared statements, headers-only tokens, no secrets in repo)
- **Extras**
  - Refresh token support
  - Swagger UI (`/docs`)
  - Optional Dockerized setup

---

## 📦 Requirements
- PHP 8.1+
- Composer
- MySQL 8.x
- (Optional) Docker + Docker Compose

---

## ⚙️ Setup

### 1. Clone & Install
```bash
git clone https://github.com/ow3ndesu/goals-api-php.git
cd goals-api-php
composer install
