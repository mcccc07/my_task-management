<?php
session_start();
require_once __DIR__ . '/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$username || !$password || !$email) {
        $error = "Email, username, and password are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } else {
        try {
            $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $error = "A user with that username or email already exists.";
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO users (email, username, password) VALUES (?, ?, ?)");
                $stmt->execute([$email, $username, $hashedPassword]);

                $_SESSION['notification_type'] = 'success';
                $_SESSION['notification_message'] = "Account created successfully!";

                header('Location: login.php');
                exit();
            }
        } catch (\PDOException $e) {
            $error = "Database error: Registration failed. Please try again later. (Error Code: " . $e->getCode() . ")";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ToDone-Create Account</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .bg-blue-900 {
            position: relative;
            overflow: hidden;
            display: none;
        }

        .text {
            font-size: 2rem;
            line-height: 2.5rem;
            font-weight: 900;
        }

        @media (min-width: 500px) {
            .bg-blue-900 {
                display: flex;
            }
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div id="notification"
        class="fixed top-5 right-5 z-50 p-4 rounded-lg shadow-xl transition-opacity duration-300 opacity-0 transform translate-x-full ease-out hidden"
        role="alert">
        <div class="flex items-center space-x-3">
            <div id="notification-icon"></div>

            <p id="notification-message" class="text-sm font-medium"></p>

            <button id="close-button" type="button" class="ml-auto -mx-1.5 -my-1.5 p-1.5 rounded-lg focus:ring-2 inline-flex h-8 w-8">
                <span class="sr-only">Close</span>
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
            </button>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-2xl overflow-hidden w-full max-w-md lg:max-w-4xl lg:flex">

        <div class="p-4 sm:p-10 w-full lg:w-5/12 flex flex-col justify-between">
            <div class="space-y-8">

                <div class="flex items-center space-x-2">
                    <a href="../index.html">
                        <img src="../images/1000011819-removebg-preview.png" alt="logo"
                            width="100"
                            height="50">
                    </a>
                </div>

                <div>
                    <h1 class="text-3xl font-extrabold text-gray-800 mb-2">Create Your Account</h1>
                    <p class="text-gray-500 mb-8 text-sm">
                        Start managing your tasks today.
                    </p>

                    <form method="POST" action="register.php" class="space-y-4">

                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                            <input name="email" id="email" type="email" value="<?= htmlspecialchars($input_email ?? '') ?>" required
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                placeholder="sellostore@company.com">
                        </div>

                        <div>
                            <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                            <input name="username" id="username" type="text" value="<?= htmlspecialchars($input_username ?? '') ?>" required
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                placeholder="Choose a unique username">
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                            <input name="password" id="password" type="password" required
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                placeholder="Must be at least 8 characters">
                        </div>

                        <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 mt-6">
                            Create account
                        </button>
                    </form>

                    <p class="text-center text-xs text-gray-500 mt-4">
                        By signing up, you agree to our
                        <a href="#" class="text-indigo-600 hover:text-indigo-500 font-medium">Terms</a> and
                        <a href="#" class="text-indigo-600 hover:text-indigo-500 font-medium">Privacy Policy</a>.
                    </p>

                    <div class="mt-6 border-t pt-4 text-center">
                        <p class="text-sm text-gray-500">
                            Already have an account?
                            <a href="login.php" class="font-medium text-indigo-600 hover:text-indigo-500">
                                Log in
                            </a>
                        </p>
                    </div>
                </div>
            </div>

            <div></div>
        </div>

        <div class="bg-blue-900 hidden lg:flex lg:w-7/12 p-12 text-white flex-col justify-center items-center rounded-tr-xl rounded-br-xl">
            <div class="text-center">
                <h2 class="text-4xl font-extrabold mb-4 z-10">
                    Track, Manage, Succeed.
                </h2>
                <p class="text-indigo-100 text-center mb-10 z-10 max-w-sm">
                    Unlock your full potential with Sellora's comprehensive task management suite.
                </p>

                <div class="text-sm space-y-3 z-10 text-left w-max mx-auto">
                    <p class="flex items-center">
                        <svg class="h-5 w-5 text-green-300 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        Real-time collaboration
                    </p>
                    <p class="flex items-center">
                        <svg class="h-5 w-5 text-green-300 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        Customizable dashboards
                    </p>
                    <p class="flex items-center">
                        <svg class="h-5 w-5 text-green-300 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        Advanced analytics
                    </p>
                </div>
            </div>
        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const notification = document.getElementById('notification');
            const closeButton = document.getElementById('close-button');
            const messageElement = document.getElementById('notification-message');
            const iconElement = document.getElementById('notification-icon');

            const styles = {
                error: {
                    bg: 'bg-red-100 border border-red-400',
                    text: 'text-red-800',
                    icon: '<svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
                    close: 'bg-red-100 text-red-400 hover:text-red-600 hover:bg-red-200 focus:ring-red-300'
                }
            };

            function showNotification(type, message, duration = 6000) {
                const style = styles[type];
                notification.className = 'fixed top-5 right-5 z-50 p-4 rounded-lg shadow-xl transition-opacity duration-300 opacity-0 transform translate-x-full ease-out hidden ' + style.bg;
                messageElement.className = 'text-sm font-medium ' + style.text;
                closeButton.className = 'ml-auto -mx-1.5 -my-1.5 p-1.5 rounded-lg focus:ring-2 inline-flex h-8 w-8 ' + style.close;

                messageElement.innerHTML = message;
                iconElement.innerHTML = style.icon;

                notification.classList.remove('hidden');
                setTimeout(() => {
                    notification.classList.remove('opacity-0', 'translate-x-full');
                    notification.classList.add('opacity-100', 'translate-x-0');
                }, 50);

                const timer = setTimeout(hideNotification, duration);

                closeButton.onclick = () => {
                    clearTimeout(timer);
                    hideNotification();
                };
            }

            function hideNotification() {
                notification.classList.remove('opacity-100', 'translate-x-0');
                notification.classList.add('opacity-0', 'translate-x-full');

                setTimeout(() => {
                    notification.classList.add('hidden');
                }, 300);
            }

            <?php
            if ($error):
                $errorMessage = htmlspecialchars($error);
            ?>
                showNotification('error', '<?php echo $errorMessage; ?>');
            <?php endif; ?>
        });
    </script>

</body>

</html>