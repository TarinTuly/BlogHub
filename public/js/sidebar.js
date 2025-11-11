// sidebar.js

/**
 * Initializes the sidebar navigation.
 * @param {object} user - The authenticated user object.
 * @param {string} token - The auth token.
 * @param {object} callbacks - An object of functions to call (loadPosts, loadComments, loadUserInfo).
 */
export function initializeSidebar(user, token, callbacks) {
    const sidebarElement = document.getElementById('sidebar');
    sidebarElement.innerHTML = `<h2 class="text-xl font-bold mb-20">Dashboard</h2>`;

    // Posts
    const postsDiv = document.createElement('div');
    postsDiv.className = 'text-center font-bold text-lg hover:bg-blue-500 cursor-pointer py-2 rounded';
    postsDiv.textContent = 'Posts';
    postsDiv.addEventListener('click', () => {
        callbacks.loadPosts(1); // Load page 1
        localStorage.setItem('activeSection', 'posts');
    });
    sidebarElement.appendChild(postsDiv);

    // Admin-only links
    if (user.role === 'admin') {
        // Comments
        const commentsDiv = document.createElement('div');
        commentsDiv.className = 'text-center font-bold text-lg hover:bg-blue-500 cursor-pointer py-2 rounded';
        commentsDiv.textContent = 'Comments';
        commentsDiv.addEventListener('click', () => {
            callbacks.loadComments();
            localStorage.setItem('activeSection', 'comments');
        });
        sidebarElement.appendChild(commentsDiv);

        // User Info
        const userInfoDiv = document.createElement('div');
        userInfoDiv.className = 'text-center font-bold text-lg hover:bg-blue-500 cursor-pointer py-2 rounded';
        userInfoDiv.textContent = 'User Info';
        userInfoDiv.addEventListener('click', () => {
            callbacks.loadUserInfo(1); // Load page 1
            localStorage.setItem('activeSection', 'userInfo');
        });
        sidebarElement.appendChild(userInfoDiv);
    }

    // Logout
    const logoutDiv = document.createElement('div');
    logoutDiv.className = 'mt-auto text-center font-bold text-lg hover:bg-red-500 cursor-pointer py-2 rounded bg-red-600';
    logoutDiv.textContent = 'Logout';
    logoutDiv.addEventListener('click', () => {
        fetch('/api/logout', {
            method: 'POST',
            headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }
        })
        .then(() => {
            localStorage.removeItem('auth_token');
            localStorage.removeItem('activeSection');
            localStorage.removeItem('currentPage');
            window.location.href = '/';
        })
        .catch(() => alert('Error logging out!'));
    });
    sidebarElement.appendChild(logoutDiv);
}

/**
 * Restores the last active section on page load.
 * @param {object} user - The authenticated user object.
 * @param {object} callbacks - An object of functions to call (loadPosts, loadComments, loadUserInfo).
 */
export function restoreActiveSection(user, callbacks) {
    const activeSection = localStorage.getItem('activeSection');
    // Get the last page *viewed* for restoring
    const lastPage = parseInt(localStorage.getItem('currentPage')) || 1;

    if (activeSection === 'posts') {
        callbacks.loadPosts(lastPage);
    } else if (activeSection === 'userInfo' && user.role === 'admin') {
        callbacks.loadUserInfo(lastPage);
    } else if (activeSection === 'comments' && user.role === 'admin') {
        callbacks.loadComments();
    } else {
        // Default view after login
        document.getElementById('mainContent').innerHTML = `
            <div class="text-center py-10">
                <h2 class="text-2xl font-bold text-gray-800">Welcome, ${user.name}!</h2>
                <p class="text-gray-600 mt-2">Use the sidebar to navigate.</p>
            </div>
        `;
    }
}
