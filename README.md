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
```

### 2. Configure Environment
```bash
cp .env.example .env
```
And please reach out if there is anything unclear in this part.

### 3. Import Database
Using `db.sql` create the database and the tables necessary. Process may vary in what MySQL UI you are using.

### 4. Run seeder
```bash
php -S localhost:8080 .\seeder.php
```
This will output the credentials of the seeded account (**Already** included in postman collection).

### 5. Run the app
```bash
php -S localhost:8080 -t public public/index.php
```
To test, you can navigate to `http://localhost:8080/docs`. If the API documentation appears, it is working properly.

### 6. Import the Postman Collection
There is a button under **My Workspace** inside Postman that lets you import the exported collection.json. Proceed with the import.

---

## ▶️ Run

### 1. Auth - Login
Hit `/auth/login` under **Auth** using the provided seeded account. After a successful authentication, copy the returned token.

### 2. Authorization
Under **Goals** folder, you'll have to paste the token under **Bearer Token** Auth Type to Authenticate all protected routes of the API.

---

## 📝 Notes
- Please refer to `http://localhost:8080/docs` and **Collection Documentation** to fully understand how to hit all endpoints.
- Docker capabilities will be added below this line.