<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit(render_page("Invalid Request", "<h2>Invalid Request</h2><p>This page only accepts form submissions.</p>"));
}

// Honeypot: bots fill hidden fields, humans don't
if (!empty($_POST['website'])) {
    http_response_code(400);
    exit(render_page("Submission Rejected", "<h2>Submission Rejected</h2>"));
}

// Rate limiting: max 1 submission per 60 seconds per session
$now = time();
if (!empty($_SESSION['last_submit']) && ($now - $_SESSION['last_submit']) < 60) {
    http_response_code(429);
    exit(render_page("Too Many Requests", "<h2>Too Many Requests</h2><p>Please wait a minute before submitting again.</p>"));
}

// Sanitize and validate inputs
$name    = trim(htmlspecialchars($_POST['name']    ?? '', ENT_QUOTES, 'UTF-8'));
$email   = trim($_POST['email'] ?? '');
$service = htmlspecialchars($_POST['service']  ?? '', ENT_QUOTES, 'UTF-8');
$message = trim(htmlspecialchars($_POST['message'] ?? '', ENT_QUOTES, 'UTF-8'));

$allowed_services = ['Merchandise', 'Sourcing', 'Food Cart', 'General'];

if (empty($name) || strlen($name) > 100) {
    http_response_code(400);
    exit(render_page("Invalid Input", "<h2>Invalid Name</h2><p>Please enter a valid name (max 100 characters).</p>"));
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
    http_response_code(400);
    exit(render_page("Invalid Input", "<h2>Invalid Email</h2><p>Please enter a valid email address.</p>"));
}
if (!in_array($service, $allowed_services, true)) {
    $service = 'General';
}
if (empty($message) || strlen($message) > 2000) {
    http_response_code(400);
    exit(render_page("Invalid Input", "<h2>Invalid Message</h2><p>Message must be between 1 and 2000 characters.</p>"));
}

$to      = getenv('CONTACT_EMAIL') ?: 'your-email@example.com';
$subject = "New Order from " . $name;
$body    = "Name: $name\nEmail: $email\nService: $service\n\nMessage:\n$message";
$headers = implode("\r\n", [
    "From: noreply@amaraenterprises.com",
    "Reply-To: " . $email,
    "X-Mailer: PHP/" . phpversion(),
    "Content-Type: text/plain; charset=UTF-8",
    "MIME-Version: 1.0",
]);

$_SESSION['last_submit'] = $now;

if (mail($to, $subject, $body, $headers)) {
    echo render_page(
        "Thank You",
        "<h2>Thank You, $name!</h2><p>Your request has been sent successfully. We will get back to you soon.</p><a href='index.html' class='btn'>Return Home</a>"
    );
} else {
    http_response_code(500);
    echo render_page(
        "Error",
        "<h2>Sending Failed</h2><p>There was a problem sending your request. Please try again or contact us directly.</p><a href='contact.html' class='btn'>Go Back</a>"
    );
}

function render_page(string $title, string $body_content): string {
    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>{$title} - Amara Enterprises</title>
    <link rel="stylesheet" href="style.css"/>
</head>
<body>
<header>
    <h1>Amara Enterprises</h1>
    <p>Your Partner in Business Growth</p>
</header>
<nav>
    <a href="index.html">Home</a>
    <a href="services.html">Services</a>
    <a href="order.html">Order Now</a>
    <a href="contact.html">Contact</a>
</nav>
<section class="content">
    {$body_content}
</section>
<footer>
    <p>&copy; 2026 Amara Enterprises |
    <a href="https://www.facebook.com/profile.php?id=100076263330506" target="_blank">Follow us on Facebook</a></p>
</footer>
</body>
</html>
HTML;
}
