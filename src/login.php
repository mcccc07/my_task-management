<?php
session_start();
require_once __DIR__ . '/database.php';

$error = '';
$username = '';

if (isset($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$username || !$password) {
        $error = "Username and password are required.";
    } else {
        try {
            $stmt = $db->prepare("SELECT id, username, password FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user'] = [
                    "id" => $user["id"],
                    "username" => $user["username"]
                ];

                header('Location: dashboard.php');
                exit();
            } else {
                $error = "Invalid username or password.";
            }
        } catch (\PDOException $e) {
            $error = "Database error: Login failed. Please try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ToDone-Log In</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .hero-bg {
            background-color: #3b30ad;
            position: relative;
            overflow: hidden;
        }

        .hero-bg {
            display: none;
        }

        @media (min-width: 768px) {
            .hero-bg {
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
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"></path>
                </svg>
            </button>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-2xl overflow-hidden w-full max-w-md lg:max-w-4xl md:flex">

        <div class="p-4 sm:p-10 w-full md:w-5/12 flex flex-col justify-between">
            <div class="space-y-8">
                <div class="flex items-center space-x-2">
                    <a href="../index.html">
                        <img src="../images/1000011819-removebg-preview.png" alt="logo"
                            width="100"
                            height="50">
                    </a>
                </div>

                <div>
                    <h1 class="text-3xl font-bold text-gray-800 mb-2">Welcome Back</h1>
                    <p class="text-gray-500 mb-8 text-sm">
                        Enter your username and password to access your account.
                    </p>

                    <form method="POST" action="login.php" class="space-y-6">

                        <div>
                            <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                placeholder="johndoe123">
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                            <input type="password" id="password" name="password" required
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                placeholder="************">
                        </div>

                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between text-sm space-y-2 lg:space-y-0">
                            <div class="flex items-center">
                                <input id="remember-me" name="remember-me" type="checkbox" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                <label for="remember-me" class="ml-2 block text-gray-900">
                                    Remember me.
                                </label>
                            </div>
                            <a href="#" class="font-medium text-indigo-600 hover:text-indigo-500">
                                Forgot Your Password?
                            </a>
                        </div>

                        <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-700 hover:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Log In
                        </button>
                    </form>

                    <div class="relative mt-6 mb-4">
                        <div class="absolute inset-0 flex items-center" aria-hidden="true">
                            <div class="w-full border-t border-gray-300"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-2 bg-white text-gray-500">
                                Or Login With
                            </span>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-col lg:flex-row gap-3 space-y-2 lg:space-y-0">
                        <button class="w-full lg:w-1/2 flex items-center justify-center py-2 px-4 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            <img src="../images/google.png" alt="google logo" class="h-6 w-6 mr-2">
                            Google
                        </button>
                        <button class="w-full lg:w-1/2 flex items-center justify-center py-2 px-4 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            <img src="../images/apple.png" alt="apple logo" class="h-6 w-6 mr-2">
                            Apple
                        </button>
                    </div>

                    <p class="text-center text-sm text-gray-500 mt-6">
                        Don't Have An Account?
                        <a href="register.php" class="font-medium text-yellow-600 hover:text-yellow-500">
                            Register Now.
                        </a>
                    </p>
                </div>
            </div>

            <div></div>
        </div>

        <div class="bg-blue-900 hero-bg md:w-7/12 p-12 text-white flex-col justify-center items-center rounded-tr-xl rounded-br-xl">
            <div class="text-center items-center">
                <h2 class="text-4xl font-extrabold mb-4 z-10">
                    Track, <span class="text-yellow-400 font-extrabold"> Manage</span>, Succeed.
                </h2>
                <p class="text-indigo-100 text-center mb-10 z-10 max-w-md">
                    Unlock your full potential with Sellora's comprehensive task management suite.
                </p>

                <div class="text-sm space-y-3 z-10 text-left w-max mx-auto">
                    <p class="flex items-center">
                        <svg class="h-5 w-5 text-green-300 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" />
                        </svg>
                        Real-time collaboration
                    </p>
                    <p class="flex items-center">
                        <svg class="h-5 w-5 text-green-300 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" />
                        </svg>
                        Customizable dashboards
                    </p>
                    <p class="flex items-center">
                        <svg class="h-5 w-5 text-green-300 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" />
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
                success: {
                    bg: 'bg-green-100 border border-green-400',
                    text: 'text-green-800',
                    icon: '<svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
                    close: 'bg-green-100 text-green-400 hover:text-green-600 hover:bg-green-200 focus:ring-green-300'
                },
                error: {
                    bg: 'bg-red-100 border border-red-400',
                    text: 'text-red-800',
                    icon: '<svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
                    close: 'bg-red-100 text-red-400 hover:text-red-600 hover:bg-red-200 focus:ring-red-300'
                }
            };

            function showNotification(type, message, duration = 5000) {
                const style = styles[type] || styles['error'];
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
            $message = '';
            $type = '';

            if (isset($_SESSION['notification_message']) && isset($_SESSION['notification_type'])) {
                $message = htmlspecialchars($_SESSION['notification_message']);
                $type = htmlspecialchars($_SESSION['notification_type']);
                unset($_SESSION['notification_message']);
                unset($_SESSION['notification_type']);
            } elseif ($error) {
                $message = htmlspecialchars($error);
                $type = 'error';
            }

            if ($message): ?>
                showNotification('<?php echo $type; ?>', '<?php echo $message; ?>');
            <?php endif; ?>
        });
    </script>
</body>

</html>