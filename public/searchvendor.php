<?php
/**
 * VARUNA System - API Entry Point for Android Application
 *
 * This file is placed in the public web root to handle requests
 * from the Android app. It forwards the request to the actual API
 * script located in the /api/ directory.
 */

// This line loads and executes the real API file, passing along any
// GET parameters like '?q=...'.
require_once __DIR__ . '/api/searchvendor.php';