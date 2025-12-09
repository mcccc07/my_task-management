// --- Global Variables (Used by functions called directly from HTML) ---
// These are declared here because they are accessed by functions like openEditModal()
// which are called directly from HTML 'onclick' attributes, outside the DOMContentLoaded scope.
const editTaskModal = document.getElementById("editTaskModal");
const editTaskID = document.getElementById("editTaskID");
const editTaskName = document.getElementById("editTaskName");
const editDueDate = document.getElementById("editDueDate");
const deleteTaskModal = document.getElementById("deleteTaskModal");
const taskToDeleteName = document.getElementById("taskToDeleteName");
const confirmDeleteLink = document.getElementById("confirmDeleteLink");

// Notification elements (initialized inside DOMContentLoaded but referenced by functions)
let notification;
let closeButton;
let messageElement;
let iconElement;

// --- Notification Styles ---
const styles = {
  error: {
    bg: "bg-red-100 border border-red-400",
    text: "text-red-800",
    icon: '<svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
    close:
      "bg-red-100 text-red-400 hover:text-red-600 hover:bg-red-200 focus:ring-red-300",
  },
};

// --- Notification Functions ---

function hideNotification() {
  notification.classList.remove("opacity-100", "translate-x-0");
  notification.classList.add("opacity-0", "translate-x-full");

  setTimeout(() => {
    notification.classList.add("hidden");
  }, 300);
}

function showNotification(type, message, duration = 6000) {
  // Ensures elements are retrieved if the function is called before DOMContentLoaded
  if (!notification) {
    notification = document.getElementById("notification");
    closeButton = document.getElementById("close-button");
    messageElement = document.getElementById("notification-message");
    iconElement = document.getElementById("notification-icon");
    if (!notification) return; // Exit if elements still not found
  }

  const style = styles[type];

  notification.className =
    "fixed top-5 right-5 z-50 p-4 rounded-lg shadow-xl transition-opacity duration-300 opacity-0 transform translate-x-full ease-out hidden " +
    style.bg;
  messageElement.className = "text-sm font-medium " + style.text;
  closeButton.className =
    "ml-auto -mx-1.5 -my-1.5 p-1.5 rounded-lg focus:ring-2 inline-flex h-8 w-8 " +
    style.close;

  messageElement.innerHTML = message;
  iconElement.innerHTML = style.icon;

  notification.classList.remove("hidden");
  setTimeout(() => {
    notification.classList.remove("opacity-0", "translate-x-full");
    notification.classList.add("opacity-100", "translate-x-0");
  }, 50);

  const timer = setTimeout(hideNotification, duration);

  closeButton.onclick = () => {
    clearTimeout(timer);
    hideNotification();
  };
}

// --- Modal Functions (Called via HTML 'onclick' attribute) ---

function openEditModal(button) {
  const taskId = button.getAttribute("data-id");
  const taskName = button.getAttribute("data-name");
  const dueDate = button.getAttribute("data-due-date");

  if (editTaskID && editTaskName && editDueDate && editTaskModal) {
    editTaskID.value = taskId;
    editTaskName.value = taskName;
    editDueDate.value = dueDate || "";
    editTaskModal.classList.remove("hidden");
  }
}

function openDeleteModal(button) {
  const taskId = button.getAttribute("data-id");
  const taskName = button.getAttribute("data-name");

  if (taskToDeleteName && confirmDeleteLink && deleteTaskModal) {
    taskToDeleteName.textContent = taskName;
    confirmDeleteLink.href = "dashboard.php?delete_id=" + taskId;
    deleteTaskModal.classList.remove("hidden");
  }
}

// --- Initialization Logic (Merged DOMContentLoaded) ---
document.addEventListener("DOMContentLoaded", function () {
  // A. Dashboard/Sidebar Toggle Logic
  const sidebar = document.getElementById("sidebar");
  const mainContent = document.getElementById("main-content");
  const toggleButton = document.getElementById("sidebar-toggle");
  const sidebarTextElements = document.querySelectorAll(".sidebar-text-only");

  if (toggleButton && sidebar && mainContent) {
    toggleButton.addEventListener("click", () => {
      document.body.classList.toggle("sidebar-collapsed");

      if (document.body.classList.contains("sidebar-collapsed")) {
        sidebar.classList.replace("w-64", "w-20");
        mainContent.classList.replace("ml-64", "ml-20");
        sidebarTextElements.forEach((el) => el.classList.add("hidden"));
        toggleButton
          .querySelector("i")
          .classList.replace("fa-arrow-left", "fa-bars");
      } else {
        sidebar.classList.replace("w-20", "w-64");
        mainContent.classList.replace("ml-20", "ml-64");
        sidebarTextElements.forEach((el) => el.classList.remove("hidden"));
        toggleButton
          .querySelector("i")
          .classList.replace("fa-bars", "fa-arrow-left");
      }
    });
  }

  // B. Create Task Modal Logic
  const createTaskModal = document.getElementById("createTaskModal");
  const openTaskBtn = document.querySelector(
    '[data-modal-target="createTaskModal"]'
  );

  function hideCreateTaskModal() {
    createTaskModal.classList.add("hidden");
  }

  if (openTaskBtn && createTaskModal) {
    openTaskBtn.addEventListener("click", () => {
      createTaskModal.classList.remove("hidden");
    });

    createTaskModal.querySelectorAll('button[type="button"]').forEach((btn) => {
      btn.addEventListener("click", hideCreateTaskModal);
    });

    createTaskModal.addEventListener("click", (e) => {
      if (e.target === createTaskModal) {
        hideCreateTaskModal();
      }
    });
  }

  // C. Logout Modal Logic
  // NOTE: If you are linking directly to logout.php, you can safely remove this entire block
  const logoutButton = document.getElementById("logout-button");
  const logoutModal = document.getElementById("logoutModal");

  if (logoutButton && logoutModal) {
    function hideLogoutModal() {
      logoutModal.classList.add("hidden");
    }

    logoutButton.addEventListener("click", () => {
      logoutModal.classList.remove("hidden");
    });

    logoutModal.addEventListener("click", (e) => {
      if (e.target === logoutModal) {
        hideLogoutModal();
      }
    });

    const cancelButton = logoutModal.querySelector(
      '.modal-footer button[type="button"]'
    );
    if (cancelButton) {
      cancelButton.addEventListener("click", hideLogoutModal);
    }
  }

  // D. General Modal Close Logic (for clicks outside modal)
  if (editTaskModal) {
    editTaskModal.addEventListener("click", (e) => {
      if (e.target === editTaskModal) {
        editTaskModal.classList.add("hidden");
      }
    });
  }

  if (deleteTaskModal) {
    deleteTaskModal.addEventListener("click", (e) => {
      if (e.target === deleteTaskModal) {
        deleteTaskModal.classList.add("hidden");
      }
    });
  }

  // E. Notification Initialization and Display
  // 1. Initialize notification elements globally
  notification = document.getElementById("notification");
  closeButton = document.getElementById("close-button");
  messageElement = document.getElementById("notification-message");
  iconElement = document.getElementById("notification-icon");

  // 2. Check for the error message passed from PHP via the window object
  if (window.globalErrorMessage) {
    showNotification("error", window.globalErrorMessage);
  }
});
