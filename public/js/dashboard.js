// dashboard.js
import { createPostFunctions } from './posts.js';
import { createUserFunctions } from './user.js';
import { initializeSidebar, restoreActiveSection } from './sidebar.js';
import { initializeProfile } from './profile.js';

const token = localStorage.getItem('auth_token');

if (!token) {
    window.location.href = '/'; // redirect to login
} else {
    fetch('/api/user', {
        headers: {
            'Authorization': 'Bearer ' + token,
            'Accept': 'application/json'
        }
    })
    .then(res => res.json())
    .then(user => {
        // --- 1. Set User-specific UI ---
        document.getElementById('welcomeMsg').textContent = `Welcome, ${user.name}!`;
        const profileIcon = document.getElementById('profileIcon');
        if (user.avatar_url) {
            profileIcon.src = user.avatar_url;
        } else {
            profileIcon.src = 'https://via.placeholder.com/40';
        }

        // --- 2. Create all our functions by passing dependencies ---
        const { loadPosts ,AllloadPosts} = createPostFunctions(token, user);
        const { loadUserInfo } = createUserFunctions(token, user);

        // (Placeholder for comments)
        const loadComments = () => {
            document.getElementById('mainContent').innerHTML = `<h2>Comments (Not Implemented)</h2>`;
            console.log("Load Comments called");
        };

        // --- 3. Pack callbacks for the sidebar ---
        const callbacks = {
            loadPosts,
            AllloadPosts,
            loadUserInfo,
            loadComments,

        };

        // --- 4. Initialize the sidebar ---
        initializeSidebar(user, token, callbacks);

        // --- 5. Restore the last active section ---
        restoreActiveSection(user, callbacks);
        initializeProfile(user, token);

    })
    .catch(() => {
        // Invalid token
        localStorage.removeItem('auth_token');
        window.location.href = '/';
    });
}
