<!-- resources/views/dashboard.blade.php -->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-gray-100 flex relative">

    <!-- Sidebar -->
    <div id="sidebar"
        class="absolute top-0 left-0 h-full w-[200px] bg-blue-600 flex flex-col justify-start text-white space-y-4 z-10 p-4">
        <h2 class="text-xl font-bold mb-6">Dashboard</h2>
        <!-- Sections will be injected dynamically -->
    </div>

    <!-- Top bar -->
    <div class="absolute top-0 left-0 w-full h-[100px] bg-blue-600 z-0"></div>

    <!-- Profile icon top-right -->
    <div class="absolute top-4 right-6 z-20">
        <img id="profileIcon" src="https://via.placeholder.com/40" alt="Profile"
            class="w-12 h-12 rounded-full border-2 border-white shadow-lg">
    </div>

    <!-- Main content -->
    <div id="mainArea" class="ml-[200px] mt-[100px] p-6 flex-1">
        <h1 id="welcomeMsg" class="text-4xl font-bold text-gray-800 mb-6"></h1>
        <div id="mainContent" class="bg-white p-4 rounded shadow-md">
            <p>Select a section to view information...</p>
        </div>
    </div>

    <script>
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
                document.getElementById('welcomeMsg').textContent = `Welcome, ${user.name}!`;
                const profileIcon = document.getElementById('profileIcon');
                if (user.avatar_url) {
                           profileIcon.src = user.avatar_url; // show user's uploaded avatar
                       } else {
                       profileIcon.src = 'https://via.placeholder.com/40'; // fallback placeholder
                       }
                const sidebar = document.getElementById('sidebar');
                sidebar.innerHTML = `<h2 class="text-xl font-bold mb-20">Dashboard</h2>`;



                // ----------------- Sidebar  post-------------------
                const postsDiv = document.createElement('div');
                postsDiv.className = 'text-center font-bold text-lg hover:bg-blue-500 cursor-pointer py-2 rounded';
                postsDiv.textContent = 'Posts';
                postsDiv.addEventListener('click', () => {
                 // set active
                loadPosts();
                localStorage.setItem('activeSection', 'posts');
               });
                sidebar.appendChild(postsDiv);

                // Comments (admin only)
                if (user.role === 'admin') {
                    const commentsDiv = document.createElement('div');
                    commentsDiv.className = 'text-center font-bold text-lg hover:bg-blue-500 cursor-pointer py-2 rounded';
                    commentsDiv.textContent = 'Comments';
                    commentsDiv.addEventListener('click', () =>{
                        localStorage.setItem('activeSection', 'comments');
                        loadComments();
                    });
                    sidebar.appendChild(commentsDiv);

                    const userInfoDiv = document.createElement('div');
                    userInfoDiv.className = 'text-center font-bold text-lg hover:bg-blue-500 cursor-pointer py-2 rounded';
                    userInfoDiv.textContent = 'User Info';
                    userInfoDiv.addEventListener('click', () => {

                          loadUserInfo(1); // default page 1
                        localStorage.setItem('activeSection', 'userInfo');
                           });
                    sidebar.appendChild(userInfoDiv);
                }

                // Logout
                const logoutDiv = document.createElement('div');
                logoutDiv.className =
                    'mt-auto text-center font-bold text-lg hover:bg-red-500 cursor-pointer py-2 rounded bg-red-600';
                logoutDiv.textContent = 'Logout';
                logoutDiv.addEventListener('click', () => {
                    fetch('/api/logout', {
                        method: 'POST',
                        headers: {
                            'Authorization': 'Bearer ' + token,
                            'Accept': 'application/json'
                        }
                    })
                    .then(() => {
                        localStorage.removeItem('auth_token');
                        window.location.href = '/';
                    })
                    .catch(() => alert('Error logging out!'));
                });
                sidebar.appendChild(logoutDiv);

                // -------------------------------
                // Functions for User Info
                // -------------------------------
                function loadUserInfo(page =1) {
                    fetch('/api/users', {
                        headers: {
                            'Authorization': 'Bearer ' + token,
                            'Accept': 'application/json'
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        const users = data.users;
                        const itemsPerPage = 10;
                        let currentPage = page;
                        const totalPages = Math.ceil(users.length / itemsPerPage);

                        function renderTable(page) {
                            currentPage = page;
                            localStorage.setItem('currentPage', currentPage);
                            let start = (page - 1) * itemsPerPage;
                            let end = start + itemsPerPage;
                            let paginatedUsers = users.slice(start, end);

                            let html = `
                                <div class="flex justify-between items-center mb-4">
                                    <h2 class="text-xl font-bold">User Info</h2>
                                    <button class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700" onclick="addUserForm()">+ Add User</button>
                                </div>

                                <table class="table-auto w-full border border-gray-300 text-center">
                                    <thead>
                                        <tr class="bg-blue-600 text-white font-bold">
                                            <th class="border p-2">Name</th>
                                            <th class="border p-2">Email</th>
                                            <th class="border p-2">Role</th>
                                            <th class="border p-2">Profile</th>
                                            <th class="border p-2">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                            `;

                            paginatedUsers.forEach(u => {
                                html += `
                                    <tr class="hover:bg-gray-100">
                                        <td class="border p-2">${u.name}</td>
                                        <td class="border p-2">${u.email}</td>
                                        <td class="border p-2">${u.role || '-'}</td>

                                        <td class="border p-2 text-center">
                                         ${u.avatar_url ? `<img src="${u.avatar_url}" class="w-12 h-12 mx-auto">` : 'No avatar'}</td>

                                        <td class="border p-2">
                                            <button class="bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600" onclick="editUserForm(${u.id}, '${u.name}', '${u.email}', '${u.role}')">Edit</button>
                                            <button class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600" onclick="deleteUser(${u.id})">Delete</button>
                                        </td>
                                    </tr>
                                `;
                            });

                            html += `</tbody></table>
                                <div class="flex justify-center items-center gap-2 mt-4">
                                    <button id="prevPage" class="px-3 py-1 bg-gray-300 rounded hover:bg-gray-400" ${page === 1 ? 'disabled' : ''}>Prev</button>
                                    <span class="text-gray-700">Page ${page} of ${totalPages}</span>
                                    <button id="nextPage" class="px-3 py-1 bg-gray-300 rounded hover:bg-gray-400" ${page === totalPages ? 'disabled' : ''}>Next</button>
                                </div>
                            `;

                            document.getElementById('mainContent').innerHTML = html;

                            document.getElementById('prevPage').addEventListener('click', () => {
                                if (currentPage > 1) {
                                    currentPage--;
                                    renderTable(currentPage);
                                }
                            });

                            document.getElementById('nextPage').addEventListener('click', () => {
                                if (currentPage < totalPages) {
                                    currentPage++;
                                    renderTable(currentPage);
                                }
                            });
                        }

                        renderTable(currentPage);
                    })
                    .catch(() => {
                        document.getElementById('mainContent').innerHTML = `<p>Error fetching users</p>`;
                    });
                }


                       // ---------------------- Add User ------------------------



                // ------------------- Add User Form -------------------
window.addUserForm = function() {
    document.getElementById('mainContent').innerHTML = `
        <h2 class="text-xl font-bold mb-4">Add New User</h2>
        <form id="addUserForm" class="space-y-4">
            <input type="text" name="name" placeholder="Name" class="border p-2 w-full rounded" required>
            <input type="email" name="email" placeholder="Email" class="border p-2 w-full rounded" required>
            <input type="password" name="password" placeholder="Password" class="border p-2 w-full rounded" required>
            <input type="password" name="password_confirmation" placeholder="Confirm Password" class="border p-2 w-full rounded" required>
            <select name="role" class="border p-2 w-full rounded" required>
                <option value="">Select Role</option>
                <option value="admin">Admin</option>
                <option value="user">User</option>
            </select>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Save</button>
        </form>
    `;

    document.getElementById('addUserForm').addEventListener('submit', e => {
        e.preventDefault();
        const formData = new FormData(e.target);

        fetch('/api/register', {
            method: 'POST',
            headers: { 'Authorization': 'Bearer ' + token },
            body: formData
        })
        .then(res => res.json())
        //.then(() => loadUserInfo()) // 🔹 refresh table like loadUserInfo
        .then(() => {
          const lastPage = parseInt(localStorage.getItem('currentPage')) || 1;
        loadUserInfo(lastPage);
         })
        .catch(() => alert('Error adding user'));
    });
}

// ------------------- Edit User Form -------------------
window.editUserForm = function(id, name, email, role) {
    document.getElementById('mainContent').innerHTML = `
        <h2 class="text-xl font-bold mb-4">Edit User</h2>
        <form id="editUserForm" class="space-y-4">
            <input type="text" name="name" value="${name}" class="border p-2 w-full rounded" required>
            <input type="email" name="email" value="${email}" class="border p-2 w-full rounded" required>
            <select name="role" class="border p-2 w-full rounded" required>
                <option value="admin" ${role==='admin'?'selected':''}>Admin</option>
                <option value="user" ${role==='user'?'selected':''}>User</option>
            </select>
            <input type="file" name="avatar" accept="image/*" class="border p-2 w-full">
            <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Update</button>
        </form>
    `;

    document.getElementById('editUserForm').addEventListener('submit', e => {
        e.preventDefault();
        const formData = new FormData(e.target);

        fetch(`/api/users/${id}`, {
            method: 'POST',
            headers: {
                'Authorization': 'Bearer ' + token,
                'X-HTTP-Method-Override': 'PUT'
            },
            body: formData
        })
        .then(res => res.json())
        .then(() => {
    const lastPage = parseInt(localStorage.getItem('currentPage')) || 1;
    loadUserInfo(lastPage);
})
        .catch(() => alert('Error updating user'));
    });
}

// ------------------- Delete User -------------------
window.deleteUser = function(id) {
    if(!confirm('Are you sure?')) return;

    fetch(`/api/users/${id}`, {
        method: 'DELETE',
        headers: { 'Authorization': 'Bearer ' + token }
    })
    .then(res => res.json())
    .then(() => {
    const lastPage = parseInt(localStorage.getItem('currentPage')) || 1;
    loadUserInfo(lastPage);
})
    .catch(() => alert('Error deleting user'));
}

// Restore last active section on refresh
// -------------------------------
const activeSection = localStorage.getItem('activeSection');
const lastPage = parseInt(localStorage.getItem('currentPage')) || 1;

if(activeSection === 'posts') {
    loadPosts();
} else if(activeSection === 'userInfo' && user.role === 'admin') {
    loadUserInfo(lastPage); // pass last page
} else {
    document.getElementById('mainContent').innerHTML = `<p>Select a section to view information...</p>`;
}



            })
            .catch(() => window.location.href = '/');
        }
    </script>

</body>
</html>
