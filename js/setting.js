document.addEventListener('DOMContentLoaded', function() {
    // Fix the form ID to match what's in HTML
    document.getElementById("profile").addEventListener("submit", function (e) {
        e.preventDefault();

        const firstName = document.getElementById("first-name").value;
        const lastName = document.getElementById("last-name").value;
        const email = document.getElementById("email").value;
        const timezone = document.getElementById("timezone").value;
        const birthdate = document.getElementById("birthdate").value;

        // Password Section
        const currentPassword = document.getElementById("current-password").value;
        const newPassword = document.getElementById("new-password").value;
        const confirmPassword = document.getElementById("confirm-password").value;

        // Validate passwords match if new password is provided
        if (newPassword && newPassword !== confirmPassword) {
            const message = document.getElementById("message");
            message.textContent = "New passwords don't match!";
            message.style.color = "red";
            message.classList.remove("hidden");
            return;
        }

        const theme = document.getElementById("theme").value;
        const notifications = document.getElementById("notifications").checked;

        const message = document.getElementById("message");

        // Simulate saving to server
        setTimeout(() => {
            message.textContent = `Profile updated successfully, ${firstName}!`;
            message.style.color = "green";
            message.classList.remove("hidden");

            // Apply theme
            if (theme === "dark") {
                document.body.style.backgroundColor = "#333";
                document.body.style.color = "#fff";
            } else {
                document.body.style.backgroundColor = "#f4f4f4";
                document.body.style.color = "#000";
            }
        }, 500);
    });
});