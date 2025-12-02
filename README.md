The Daily Wag - Backend API

The Daily Wag is a comprehensive pet care platform. This repository contains the backend API built with PHP 8.2 (Apache), MySQL 8, and Docker.

Prerequisites

Ensure you have the following installed on your machine:

Docker Desktop (Running)

Git

🚀 Getting Started (Local Setup)

Follow these steps to get the backend running on your local machine.

1. Clone the Repository

git clone https://github.com/pavan-sai-ch/daily-wag-backend.git
cd daily-wag-backend


2. Configure Environment Variables

Create a .env file in the root directory.

Important: Open .env and fill in your AWS Credentials if you want image uploads to work.



3. Build and Start Containers

Run the following command to build the PHP image and start the MySQL and phpMyAdmin containers.

docker-compose up --build -d


The API will be available at: http://localhost:8080

phpMyAdmin will be available at: http://localhost:8082

MySQL runs on port: 3306

4. Install Dependencies (Crucial Step)

Because we use Docker volumes to sync code, the vendor folder (where AWS SDK lives) might be hidden or missing on your host machine. Run this one-time command to install dependencies locally:

docker run --rm -v "${PWD}:/app" composer install


(On Windows PowerShell, use ${PWD}. On Command Prompt, use %cd%).

5. Initialize Database

The database should initialize automatically via schema.sql. If you need to reset it manually:

Go to http://localhost:8082 (phpMyAdmin).

Log in with:

Server: db

Username: root

Password: samyupass (or whatever is in your .env)

Check if the dailywag_db exists and has tables.

🛠️ Development Commands

Stop Containers

docker-compose down


Wipe Database & Restart Fresh

If you change schema.sql or want a clean slate:

docker-compose down -v
docker-compose up --build -d


View Logs

To debug PHP errors:

docker-compose logs -f php


To debug Database errors:

docker-compose logs -f db
