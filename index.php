<?php
declare(strict_types=1);

/*
 * Hostinger/Apache entry point.
 *
 * The Google Ads application itself is a Node.js/TypeScript service.
 * This file prevents a directory-level 403 when the hosting layer expects
 * an index file, and gives a clear response instead of exposing the
 * directory listing.
 */

http_response_code(200);
header('Content-Type: text/html; charset=UTF-8');

?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Google Ads</title>
</head>
<body>
  <main>
    <h1>Google Ads service</h1>
    <p>The application files are present, but this entry point is served by PHP.</p>
    <p>The main application requires Node.js 20+ and must be started with the project's Node/HTTP server.</p>
  </main>
</body>
</html>
