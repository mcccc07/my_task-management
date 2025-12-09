<?php

// SQLite database connection setup

// Path to SQLite database file
$dbFile = __DIR__ . '/../db/users.db';

// Get the directory path of the database file
$dbDir = dirname($dbFile);

// Create the database directory if it doesn't exist
if (!is_dir($dbDir)) {
    // Create directory with full permissions
    if (!mkdir($dbDir, 0777, true)) {
        // Stop if directory creation fails
        die("Fatal Error: Failed to create database directory: $dbDir");
    }
}

try {
    // Create PDO connection
    $db = new PDO("sqlite:" . $dbFile);

    // Enable exception mode for errors
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create required tables
    createTables($db);
} catch (\PDOException $e) {
    // Stop if connection fails
    die("Database Connection Failed: " . $e->getMessage());
}

// Create tables if they do not exist
function createTables(PDO $db)
{
    // Create users table
    $db->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT NOT NULL UNIQUE,
            username TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Create tasks table
    $db->exec("
        CREATE TABLE IF NOT EXISTS tasks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            task_name TEXT NOT NULL,
            status INTEGER NOT NULL DEFAULT 0,
            due_date DATE NULL,
            FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");

    try {
        // Check if a simple ALTER TABLE is needed (SQLite will throw an error if column exists)
        $db->exec("ALTER TABLE tasks ADD COLUMN due_date DATE NULL");
    } catch (\PDOException $e) {
        // Expected exception if the column already exists.
    }
}
