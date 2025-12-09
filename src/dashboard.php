    <?php
    session_set_cookie_params([
        'lifetime' => 0,    // expire when browser closes
        'path' => '/',
        'httponly' => true,
        'secure' => isset($_SERVER['HTTPS'])
    ]);

    session_start();
    require_once __DIR__ . '/database.php';

    // --- 1. Session Validation and Protection ---

    if (!isset($_SESSION['user'])) {
        header('Location: login.php');
        exit();
    }

    $user_id = $_SESSION['user']['id'];
    $username = $_SESSION['user']['username'];
    $error = '';
    $success_message = ''; // Initialize success message variable

    // A. Handle Task Creation (CREATE)
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_task'])) {
        $new_task_name = trim($_POST['new_task'] ?? '');
        $due_date = $_POST['due_date'] ?? NULL;

        if ($new_task_name) {
            try {
                $stmt = $db->prepare("INSERT INTO tasks (user_id, task_name, due_date) VALUES (?, ?, ?)");
                $stmt->execute([$user_id, $new_task_name, $due_date]);
                $_SESSION['success_message'] = "Task created successfully!"; // SET SUCCESS MESSAGE
                header('Location: dashboard.php');
                exit();
            } catch (\PDOException $e) {
                $error = "Failed to add task: Database error. Please ensure your 'tasks' table has a 'due_date' column of type DATE.";
            }
        } else {
            $_SESSION['error'] = "Task name cannot be empty.";
            header('Location: dashboard.php');
            exit();
        }
    }

    // B. Handle Task Deletion (DELETE)
    if (isset($_GET['delete_id'])) {
        $task_id = (int)$_GET['delete_id'];
        try {
            $stmt = $db->prepare("DELETE FROM tasks WHERE id = ? AND user_id = ?");
            $stmt->execute([$task_id, $user_id]);
            $_SESSION['success_message'] = "Task deleted successfully!"; // SET SUCCESS MESSAGE
            header('Location: dashboard.php');
            exit();
        } catch (\PDOException $e) {
            $error = "Failed to delete task.";
        }
    }

    // C. Handle Task Status Update (UPDATE)
    if (isset($_GET['mark_id'])) {
        $task_id = (int)$_GET['mark_id'];
        try {
            $status_stmt = $db->prepare("SELECT status FROM tasks WHERE id = ? AND user_id = ?");
            $status_stmt->execute([$task_id, $user_id]);
            $current_status = $status_stmt->fetchColumn();

            if ($current_status !== false) {
                $new_status = $current_status == 0 ? 1 : 0;
                $update_stmt = $db->prepare("UPDATE tasks SET status = ? WHERE id = ? AND user_id = ?");
                $update_stmt->execute([$new_status, $task_id, $user_id]);

                $message = $new_status == 1 ? "Task marked as completed!" : "Task marked as pending!";
                $_SESSION['success_message'] = $message; // SET SUCCESS MESSAGE

                header('Location: dashboard.php');
                exit();
            }
        } catch (\PDOException $e) {
            $error = "Failed to update task status.";
        }
    }


    // E. Handle Task Editing (UPDATE) 
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_task'])) {
        $task_id = (int)($_POST['task_id'] ?? 0);
        $task_name = trim($_POST['task_name'] ?? '');
        $due_date = $_POST['due_date'] ?? NULL;

        if ($task_id > 0 && $task_name) {
            try {
                $stmt = $db->prepare("UPDATE tasks SET task_name = ?, due_date = ? WHERE id = ? AND user_id = ?");
                $stmt->execute([$task_name, $due_date, $task_id, $user_id]);
                $_SESSION['success_message'] = "Task edited successfully!"; // SET SUCCESS MESSAGE FOR EDIT
                header('Location: dashboard.php');
                exit();
            } catch (\PDOException $e) {
                $error = "Failed to edit task: Database error.";
            }
        } else {
            $_SESSION['error'] = "Invalid task data provided for editing.";
            header('Location: dashboard.php');
            exit();
        }
    }


    // --- 3. Data Retrieval (READ) and Progress Calculation ---

    $tasks = [];
    $total_tasks = 0;
    $completed_tasks = 0;
    $progress_percent = 0;

    try {
        $stmt = $db->prepare("SELECT id, task_name, status, due_date FROM tasks WHERE user_id = ? ORDER BY status ASC, id DESC");
        $stmt->execute([$user_id]);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $total_tasks = count($tasks);
        if ($total_tasks > 0) {
            $completed_tasks = array_sum(array_column($tasks, 'status'));
            $progress_percent = round(($completed_tasks / $total_tasks) * 100);
        }

        // Load any pending messages from session and clear them
        if (isset($_SESSION['error'])) {
            $error = $_SESSION['error'];
            unset($_SESSION['error']);
        }
        if (isset($_SESSION['success_message'])) {
            $success_message = $_SESSION['success_message'];
            unset($_SESSION['success_message']);
        }
    } catch (\PDOException $e) {
        $error = "Error loading tasks: Database retrieval failed.";
    }
    ?>

    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Dashboard</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link href="./css/output.css" rel="stylesheet" />
        <style>
            .list-group-item-success-tailwind {
                background-color: #d1fae5;
            }

            @keyframes shimmer {
                0% {
                    background-position: -468px 0;
                }

                100% {
                    background-position: 468px 0;
                }
            }

            .skeleton-box {
                background: #f6f7f8;
                background-image: linear-gradient(to right, #f6f7f8 0%, #edeef1 20%, #f6f7f8 40%, #f6f7f8 100%);
                background-repeat: no-repeat;
                background-size: 800px 104px;
                display: inline-block;
                position: relative;
                animation: shimmer 1.2s ease-in-out infinite;
            }
        </style>
    </head>

    <body class="bg-gray-100 min-h-screen flex">

        <aside id="sidebar" class="w-64 bg-blue-950 text-white flex flex-col h-screen fixed transition-all duration-300 ease-in-out">

            <div class="flex items-center justify-between h-16 bg-blue-900 px-4">
                <span class="text-xl font-semibold tracking-wider sidebar-text-only">
                    <img src="../images/1000011819-removebg-preview.png" alt="logo"
                        width="120"
                        height="40">
                </span>

                <button id="sidebar-toggle" class="text-gray-400 hover:text-white transition duration-150">
                    <i class="fas fa-arrow-left"></i>
                </button>
            </div>

            <nav class="flex-grow p-4 space-y-2">

                <a href="dashboard.php" class="flex items-center px-4 py-2 text-gray-100 bg-blue-950 rounded-lg hover:bg-blue-700 transition duration-150">
                    <i class="fas fa-home w-5 h-5 mr-3"></i>
                    <span class="sidebar-text-only">Dashboard</span>
                </a>

                <a href="schedule.php" class="flex items-center px-4 py-2 text-gray-300 hover:bg-blue-700 rounded-lg transition duration-150">
                    <i class="fas fa-calendar-alt w-5 h-5 mr-3"></i>
                    <span class="sidebar-text-only">Schedule</span>
                </a>

                <a href="categories.php" class="flex items-center px-4 py-2 text-gray-300 hover:bg-blue-700 rounded-lg transition duration-150">
                    <i class="fas fa-tags w-5 h-5 mr-3"></i>
                    <span class="sidebar-text-only">Categories</span>
                </a>

                <a href="profile.php" class="flex items-center px-4 py-2 text-gray-300 hover:bg-blue-700 rounded-lg transition duration-150">
                    <i class="fas fa-user w-5 h-5 mr-3"></i>
                    <span class="sidebar-text-only">Profile</span>
                </a>
            </nav>

            <div class="p-4 border-t border-gray-700">
                <p class="text-sm font-medium mb-2 text-gray-400 sidebar-text-only">Logged in as: <?php echo htmlspecialchars($username); ?></p>

                <button type="button" id="logout-button" class="w-full flex items-center px-4 py-2 text-red-400 hover:bg-blue-800 rounded-lg transition duration-150 text-left">
                    <i class="fas fa-sign-out-alt w-5 h-5 mr-3"></i>
                    <span class="sidebar-text-only">Logout</span>
                </button>
            </div>

        </aside>

        <main id="main-content" class="flex-grow ml-64 p-8 transition-all duration-300 ease-in-out">

            <div class="flex justify-between items-center mb-6">
                <h1 class="text-5xl font-extrabold text-gray-800">Dashboard</h1>
                <div>
                    <button type="button" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-700 hover:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" data-modal-target="createTaskModal" onclick="document.getElementById('createTaskModal').classList.remove('hidden')">
                        <i class="fas fa-plus-circle mr-1"></i> Create Task
                    </button>
                </div>
            </div>

            <hr class="border-gray-300 mb-6">

            <?php if ($success_message): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6 flex items-center" role="alert">
                    <i class="fas fa-check-circle mr-3"></i>
                    <span class="font-medium"><?php echo $success_message; ?></span>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-6" role="alert">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                <h3 class="text-xl font-semibold text-gray-600 mb-4"><i class="fas fa-chart-bar mr-2"></i> Your Progress</h3>
                <div class="grid grid-cols-3 text-center gap-4">
                    <div>
                        <p class="text-2xl font-bold mb-0 text-indigo-600"><?php echo $total_tasks; ?></p>
                        <p class="text-gray-500 text-sm">Total Tasks</p>
                    </div>
                    <div>
                        <p class="text-2xl font-bold mb-0 text-green-600"><?php echo $completed_tasks; ?></p>
                        <p class="text-gray-500 text-sm">Tasks Completed</p>
                    </div>
                    <div>
                        <p class="text-2xl font-bold mb-0 text-blue-500"><?php echo $progress_percent; ?>%</p>
                        <p class="text-gray-500 text-sm">Completion Rate</p>
                    </div>
                </div>

                <div class="w-full bg-gray-200 rounded-full h-2.5 mt-4">
                    <div class="bg-green-500 h-2.5 rounded-full" style="width: <?php echo $progress_percent; ?>%"></div>
                </div>
            </div>

            <h2 class="text-2xl font-semibold text-gray-600 mb-4">Task List</h2>

            <div class="rounded-lg shadow-lg overflow-hidden">
                <?php if (empty($tasks)): ?>
                    <div class="p-4 space-y-4 bg-white">

                        <div class="bg-blue-100 text-blue-800 text-center p-4 rounded mb-4" role="alert">
                            You currently have no tasks! Click 'Create Task' to start.
                        </div>

                        <?php for ($i = 0; $i < 1; $i++): ?>
                            <div class="flex justify-between items-center p-4 border-b border-gray-200">
                                <div class="flex flex-col space-y-2 w-3/5">
                                    <span class="skeleton-box h-5 w-4/5 rounded"></span>
                                    <span class="skeleton-box h-3 w-1/3 rounded"></span>
                                </div>
                                <div class="flex space-x-2">
                                    <div class="skeleton-box w-8 h-8 rounded-full"></div>
                                    <div class="skeleton-box w-8 h-8 rounded-full"></div>
                                    <div class="skeleton-box w-8 h-8 rounded-full"></div>
                                </div>
                            </div>
                        <?php endfor; ?>

                    </div>
                <?php else: ?>
                    <?php foreach ($tasks as $task):
                        $is_done = $task['status'] == 1;
                        $task_bg_class = $is_done ? 'list-group-item-success-tailwind' : 'bg-white';
                        $text_style = $is_done ? 'line-through text-gray-500' : 'text-gray-800';

                        $has_due_date = !empty($task['due_date']);
                        $is_overdue = $has_due_date && !$is_done && (strtotime($task['due_date']) < time());
                    ?>
                        <div class="flex justify-between items-center p-4 border-b border-gray-200 last:border-b-0 <?php echo $task_bg_class; ?>">

                            <div class="flex flex-col">
                                <span class="text-lg <?php echo $text_style; ?> font-medium">
                                    <?php echo htmlspecialchars($task['task_name']); ?>
                                </span>

                                <?php if ($has_due_date): ?>
                                    <span class="text-sm mt-1 
                            <?php
                                    if ($is_overdue) {
                                        echo 'text-red-500 font-semibold';
                                    } elseif ($is_done) {
                                        echo 'text-gray-400';
                                    } else {
                                        echo 'text-indigo-500';
                                    }
                            ?>">
                                        <i class="fas fa-calendar-alt mr-1"></i>
                                        Due: <?php echo date('M d, Y', strtotime($task['due_date'])); ?>
                                        <?php if ($is_overdue): ?>
                                            <span class="ml-2 px-1 rounded bg-red-100 text-red-600 text-xs">OVERDUE</span>
                                        <?php endif; ?>
                                    </span>
                                <?php endif; ?>
                            </div>


                            <div class="flex space-x-2">
                                <button type="button"
                                    data-id="<?php echo $task['id']; ?>"
                                    data-name="<?php echo htmlspecialchars($task['task_name']); ?>"
                                    data-due-date="<?php echo htmlspecialchars($task['due_date'] ?? ''); ?>"
                                    class="edit-task-btn p-2 rounded-full bg-blue-500 text-white text-sm hover:bg-blue-600 focus:outline-none transition ease-in-out duration-150"
                                    title="Edit Task"
                                    onclick="openEditModal(this)">
                                    <i class="fas fa-edit"></i>
                                </button>

                                <a href="dashboard.php?mark_id=<?php echo $task['id']; ?>" class="p-2 rounded-full text-white text-sm focus:outline-none transition ease-in-out duration-150 
                        <?php echo $is_done ? 'bg-gray-400 hover:bg-gray-500' : 'bg-green-500 hover:bg-green-600'; ?>"
                                    title="<?php echo $is_done ? 'Mark as Pending' : 'Mark as Done'; ?>">
                                    <i class="fas <?php echo $is_done ? 'fa-undo' : 'fa-check'; ?>"></i>
                                </a>

                                <button type="button"
                                    data-id="<?php echo $task['id']; ?>"
                                    data-name="<?php echo htmlspecialchars($task['task_name']); ?>"
                                    class="delete-task-btn p-2 rounded-full bg-red-500 text-white text-sm hover:bg-red-600 focus:outline-none transition ease-in-out duration-150"
                                    title="Delete Task"
                                    onclick="openDeleteModal(this)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div id="createTaskModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
                <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                    <div class="modal-header flex justify-between items-center pb-3 border-b border-gray-200">
                        <h5 class="text-xl font-medium text-gray-900" id="createTaskModalLabel"><i class="fas fa-list-alt mr-2"></i> Create New Task</h5>
                    </div>
                    <form action="dashboard.php" method="POST">
                        <div class="modal-body py-4 space-y-4">

                            <div>
                                <label for="newTaskName" class="block text-sm font-medium text-gray-700 mb-1">Task Description</label>
                                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" id="newTaskName" name="new_task" placeholder="e.g., Call client meeting on Monday" required>
                            </div>

                            <div>
                                <label for="dueDate" class="block text-sm font-medium text-gray-700 mb-1">Due Date (Optional)</label>
                                <input type="date" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" id="dueDate" name="due_date">
                            </div>

                        </div>
                        <div class="modal-footer flex justify-end pt-3 border-t border-gray-200">
                            <button type="button" class="px-4 py-2 bg-gray-200 text-gray-800 text-sm font-medium rounded-md hover:bg-gray-300 mr-2" onclick="document.getElementById('createTaskModal').classList.add('hidden')">Close</button>
                            <button type="submit" name="add_task" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                                <i class="fas fa-save mr-1"></i> Save Task
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div id="editTaskModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
                <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                    <div class="modal-header flex justify-between items-center pb-3 border-b border-gray-200">
                        <h5 class="text-xl font-medium text-gray-900"><i class="fas fa-edit mr-2"></i> Edit Task</h5>
                    </div>
                    <form action="dashboard.php" method="POST">
                        <div class="modal-body py-4 space-y-4">

                            <input type="hidden" name="task_id" id="editTaskID">

                            <div>
                                <label for="editTaskName" class="block text-sm font-medium text-gray-700 mb-1">Task Description</label>
                                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" id="editTaskName" name="task_name" required>
                            </div>

                            <div>
                                <label for="editDueDate" class="block text-sm font-medium text-gray-700 mb-1">Due Date (Optional)</label>
                                <input type="date" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" id="editDueDate" name="due_date">
                            </div>

                        </div>
                        <div class="modal-footer flex justify-end pt-3 border-t border-gray-200">
                            <button type="button" class="px-4 py-2 bg-gray-200 text-gray-800 text-sm font-medium rounded-md hover:bg-gray-300 mr-2" onclick="document.getElementById('editTaskModal').classList.add('hidden')">Cancel</button>
                            <button type="submit" name="edit_task" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                                <i class="fas fa-save mr-1"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div id="deleteTaskModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
                <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                    <div class="modal-header flex justify-between items-center pb-3 border-b border-gray-200">
                        <h5 class="text-xl font-medium text-gray-900"><i class="fas fa-exclamation-triangle text-red-500 mr-2"></i> Confirm Deletion</h5>
                    </div>

                    <div class="modal-body py-4">
                        <p class="text-gray-700 mb-4">Are you sure you want to delete the task:</p>
                        <p class="text-lg font-semibold text-red-700 italic" id="taskToDeleteName"></p>
                        <p class="text-sm mt-2 text-gray-500">This action cannot be undone.</p>
                    </div>

                    <div class="modal-footer flex justify-end pt-3 border-t border-gray-200">
                        <button type="button" class="px-4 py-2 bg-gray-200 text-gray-800 text-sm font-medium rounded-md hover:bg-gray-300 mr-2" onclick="document.getElementById('deleteTaskModal').classList.add('hidden')">
                            Cancel
                        </button>
                        <a id="confirmDeleteLink" href="#" class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700">
                            <i class="fas fa-trash mr-1"></i> Delete Task
                        </a>
                    </div>
                </div>
            </div>


            <div id="logoutModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
                <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">

                    <div class="modal-header flex justify-between items-center pb-3 border-b border-gray-200">
                        <h5 class="text-xl font-medium text-gray-900"><i class="fas fa-exclamation-triangle text-yellow-500 mr-2"></i> Confirm Logout</h5>
                    </div>

                    <div class="modal-body py-4">
                        <p class="text-gray-700">Are you sure you want to log out?</p>
                    </div>

                    <div class="modal-footer flex justify-end pt-3 border-t border-gray-200">

                        <button type="button" class="px-4 py-2 bg-gray-200 text-gray-800 text-sm font-medium rounded-md hover:bg-gray-300 mr-2" onclick="document.getElementById('logoutModal').classList.add('hidden')">
                            Cancel
                        </button>

                        <a id="confirm-logout-link" href="logout.php" class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700">
                            <i class="fas fa-sign-out-alt mr-1"></i> Log Out
                        </a>
                    </div>
                </div>
            </div>


            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const sidebar = document.getElementById('sidebar');
                    const mainContent = document.getElementById('main-content');
                    const toggleButton = document.getElementById('sidebar-toggle');
                    const sidebarTextElements = document.querySelectorAll('.sidebar-text-only');

                    toggleButton.addEventListener('click', () => {
                        document.body.classList.toggle('sidebar-collapsed');

                        if (document.body.classList.contains('sidebar-collapsed')) {
                            sidebar.classList.replace('w-64', 'w-20');
                            mainContent.classList.replace('ml-64', 'ml-20');
                            sidebarTextElements.forEach(el => el.classList.add('hidden'));
                            toggleButton.querySelector('i').classList.replace('fa-arrow-left', 'fa-bars');
                        } else {
                            sidebar.classList.replace('w-20', 'w-64');
                            mainContent.classList.replace('ml-20', 'ml-64');
                            sidebarTextElements.forEach(el => el.classList.remove('hidden'));
                            toggleButton.querySelector('i').classList.replace('fa-bars', 'fa-arrow-left');
                        }
                    });
                    const createTaskModal = document.getElementById('createTaskModal');
                    const openTaskBtn = document.querySelector('[data-modal-target="createTaskModal"]');

                    function hideCreateTaskModal() {
                        createTaskModal.classList.add('hidden');
                    }

                    if (openTaskBtn) {
                        openTaskBtn.addEventListener('click', () => {
                            createTaskModal.classList.remove('hidden');
                        });
                    }

                    createTaskModal.querySelectorAll('button[type="button"]').forEach(btn => {
                        btn.addEventListener('click', hideCreateTaskModal);
                    });

                    createTaskModal.addEventListener('click', (e) => {
                        if (e.target === createTaskModal) {
                            hideCreateTaskModal();
                        }
                    });
                    const logoutButton = document.getElementById('logout-button');
                    const logoutModal = document.getElementById('logoutModal');

                    function hideLogoutModal() {
                        logoutModal.classList.add('hidden');
                    }

                    if (logoutButton) {
                        logoutButton.addEventListener('click', () => {
                            logoutModal.classList.remove('hidden');
                        });
                    }

                    logoutModal.addEventListener('click', (e) => {
                        if (e.target === logoutModal) {
                            hideLogoutModal();
                        }
                    });

                    const cancelButton = logoutModal.querySelector('.modal-footer button[type="button"]');
                    if (cancelButton) {
                        cancelButton.addEventListener('click', hideLogoutModal);
                    }
                });

                const editTaskModal = document.getElementById('editTaskModal');
                const editTaskID = document.getElementById('editTaskID');
                const editTaskName = document.getElementById('editTaskName');
                const editDueDate = document.getElementById('editDueDate');

                function openEditModal(button) {
                    const taskId = button.getAttribute('data-id');
                    const taskName = button.getAttribute('data-name');
                    const dueDate = button.getAttribute('data-due-date');

                    editTaskID.value = taskId;
                    editTaskName.value = taskName;

                    if (dueDate) {
                        editDueDate.value = dueDate;
                    } else {
                        editDueDate.value = '';
                    }

                    editTaskModal.classList.remove('hidden');
                }

                editTaskModal.addEventListener('click', (e) => {
                    if (e.target === editTaskModal) {
                        editTaskModal.classList.add('hidden');
                    }
                });

                const deleteTaskModal = document.getElementById('deleteTaskModal');
                const taskToDeleteName = document.getElementById('taskToDeleteName');
                const confirmDeleteLink = document.getElementById('confirmDeleteLink');

                function openDeleteModal(button) {
                    const taskId = button.getAttribute('data-id');
                    const taskName = button.getAttribute('data-name');

                    taskToDeleteName.textContent = taskName;
                    confirmDeleteLink.href = 'dashboard.php?delete_id=' + taskId;
                    deleteTaskModal.classList.remove('hidden');
                }
                deleteTaskModal.addEventListener('click', (e) => {
                    if (e.target === deleteTaskModal) {
                        deleteTaskModal.classList.add('hidden');
                    }
                });

                function loadTasks() {
                    document.getElementById('skeleton-container').style.display = 'block';

                    setTimeout(() => {
                        document.getElementById('skeleton-container').style.display = 'none';
                        document.getElementById('actual-content').style.display = 'block';
                    }, 1500);
                }
            </script>

    </body>

    </html>