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

            const sidebar = document.getElementById('sidebar');
            sidebar.innerHTML = `<h2 class="text-xl font-bold mb-20">Dashboard</h2>`;

            // Posts
            const postsDiv = document.createElement('div');
            postsDiv.className = 'text-center font-bold text-lg hover:bg-blue-500 cursor-pointer py-2 rounded';
            postsDiv.textContent = 'Posts';
            postsDiv.addEventListener('click', () => loadPosts());
            sidebar.appendChild(postsDiv);

            // Comments (admin only)
            if (user.role === 'admin') {
                const commentsDiv = document.createElement('div');
                commentsDiv.className = 'text-center font-bold text-lg hover:bg-blue-500 cursor-pointer py-2 rounded';
                commentsDiv.textContent = 'Comments';
                commentsDiv.addEventListener('click', () => loadComments());
                sidebar.appendChild(commentsDiv);

                const userInfoDiv = document.createElement('div');
                userInfoDiv.className = 'text-center font-bold text-lg hover:bg-blue-500 cursor-pointer py-2 rounded';
                userInfoDiv.textContent = 'User Info';
                userInfoDiv.addEventListener('click', () => loadUserInfo());
                sidebar.appendChild(userInfoDiv);
            }

            // Logout
            const logoutDiv = document.createElement('div');
            logoutDiv.className = 'mt-auto text-center font-bold text-lg hover:bg-red-500 cursor-pointer py-2 rounded bg-red-600';
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
            // 🔹 keep your loadPosts() and loadComments() here
            // -------------------------------

            // 🔹 paste the updated loadUserInfo() function here
            function loadUserInfo() {
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
                    let currentPage = 1;
                    const totalPages = Math.ceil(users.length / itemsPerPage);

                    function renderTable(page) {
                        let start = (page - 1) * itemsPerPage;
                        let end = start + itemsPerPage;
                        let paginatedUsers = users.slice(start, end);

                        let html = `
                        <h2 class="text-xl font-bold mb-4">User Info</h2>
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
                                    <td class="border p-2">
                                        <img src="${u.avatar || 'https://via.placeholder.com/40'}" class="w-10 h-10 rounded-full mx-auto">
                                    </td>
                                    <td class="border p-2">
                                        <button class="bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600" onclick="editUser(${u.id})">Edit</button>
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

        }) // 👈 closes the `.then(user => { ... })`
        .catch(() => window.location.href = '/');
    } // 👈 closes the `else { ... }`
</script>

</body>

</html>
