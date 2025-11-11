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













            // ------------------- Load Posts -------------------
// ------------------- Load Posts -------------------
function loadPosts(page = 1) {
    fetch('/api/posts', {
        headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }
    })
    .then(res => res.json())
    .then(posts => {
        if(posts.length === 0){
            document.getElementById('mainContent').innerHTML = `<p class="text-gray-500">No posts available.</p>`;
            return;
        }

        if(user.role === 'admin'){
            renderPaginatedTable(posts, 'mainContent', [
                { header:'ID', key:'id' },
                { header:'Title', key:'title' },
                { header:'Body', key:'body' },
                { header:'Done By', key:'user', render: p => p.user ? p.user.name : 'Unknown' },
                { header:'Date', key:'created_at', render: p => new Date(p.created_at).toLocaleString() }
            ], 10, page, {
                addTopHtml:`<div class="flex justify-between items-center mb-4">
                                <h2 class="text-xl font-bold">Posts</h2>
                                <button class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700" onclick="addPostForm()">+ Add Post</button>
                            </div>`,
                addRowActions: p => `<button class="bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600" onclick="editPostForm(${p.id}, '${p.title}', '${p.body}')">Edit</button>
                                     <button class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600" onclick="deletePost(${p.id})">Delete</button>`
            });
        } else {
            renderPaginatedTable(posts, 'mainContent', [
                { header:'#', key:null, render:(p,i,currentPage,perPage) => (currentPage-1)*perPage + i + 1 },
                { header:'Date', key:'created_at', render:p => new Date(p.created_at).toLocaleString() },
                { header:'Title', key:'title' },
                { header:'Body', key:'body' }
            ], 10, page);
        }

        localStorage.setItem('activeSection', 'posts');
    })
    .catch(err => {
        console.error(err);
        document.getElementById('mainContent').innerHTML = `<p class="text-red-500">Error loading posts.</p>`;
    });
}
//

//------------pagination-------------------


/**
 * Universal paginated table renderer
 * @param {Array} items - array of objects to render
 * @param {string} containerId - element id to render table
 * @param {Array} columns - [{header:'', key:'', render:(item,i,currentPage,perPage)=>''}]
 * @param {number} perPage - entries per page
 * @param {number} currentPage - starting page
 * @param {Object} options - { addTopHtml: '', addRowActions: item=>'' }
 */
function renderPaginatedTable(items, containerId, columns, perPage=10, currentPage=1, options={}) {
    const totalPages = Math.ceil(items.length / perPage);

    function renderPage(page) {
        currentPage = page;
        let start = (page-1)*perPage;
        let end = start + perPage;
        const pageItems = items.slice(start,end);

        let html = options.addTopHtml || '';
        html += `<table class="table-auto w-full border border-gray-300 text-center">
                    <thead><tr class="bg-blue-600 text-white font-bold">`;

        columns.forEach(col => html += `<th class="border p-2">${col.header}</th>`);
        if(options.addRowActions) html += `<th class="border p-2">Action</th>`;
        html += `</tr></thead><tbody>`;

        pageItems.forEach((item,i) => {
            html += `<tr class="hover:bg-gray-100">`;
            columns.forEach(col => {
                html += `<td class="border p-2">${col.render ? col.render(item,i,currentPage,perPage) : item[col.key] || '-'}</td>`;
            });
            if(options.addRowActions) html += `<td class="border p-2">${options.addRowActions(item)}</td>`;
            html += `</tr>`;
        });

        html += `</tbody></table>`;

        if(totalPages > 1){
            html += `<div class="flex justify-center items-center gap-2 mt-4">
                        <button id="prevPage" class="px-3 py-1 bg-gray-300 rounded hover:bg-gray-400" ${currentPage===1?'disabled':''}>Prev</button>
                        <span class="text-gray-700">Page ${currentPage} of ${totalPages}</span>
                        <button id="nextPage" class="px-3 py-1 bg-gray-300 rounded hover:bg-gray-400" ${currentPage===totalPages?'disabled':''}>Next</button>
                     </div>`;
        }

        document.getElementById(containerId).innerHTML = html;

        if(totalPages > 1){
            document.getElementById('prevPage').addEventListener('click', () => {
                if(currentPage>1) renderPage(currentPage-1);
            });
            document.getElementById('nextPage').addEventListener('click', () => {
                if(currentPage<totalPages) renderPage(currentPage+1);
            });
        }
    }

    renderPage(currentPage);
}

//-------------------------edit and delete post-----------------------
// ------------------------- Edit Post -------------------------
window.editPostForm = function(id, title, body) {
    // Save current page at the time of clicking edit
    const pageBeforeEdit = parseInt(localStorage.getItem('currentPage')) || 1;

    document.getElementById('mainContent').innerHTML = `
        <h2 class="text-xl font-bold mb-4">Edit Post</h2>
        <form id="editPostForm" class="space-y-4">
            <input type="text" name="title" value="${title}" class="border p-2 w-full rounded" required>
            <textarea name="body" class="border p-2 w-full rounded" required>${body}</textarea>
            <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Update</button>
        </form>
    `;

    document.getElementById('editPostForm').addEventListener('submit', e => {
        e.preventDefault();
        const formData = new FormData(e.target);

        fetch(`/api/posts/${id}`, {
            method: 'POST',
            headers: {
                'Authorization': 'Bearer ' + token,
                'X-HTTP-Method-Override': 'PUT'
            },
            body: formData
        })
        .then(res => res.json())
        .then(() => {
            // fetch all posts to find the page containing the edited post
            fetch('/api/posts', {
                headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(posts => {
                const perPage = 10; // same as your pagination
                const index = posts.findIndex(p => p.id === id);
                const page = Math.floor(index / perPage) + 1;

                // fallback if index not found
                loadPosts(page || pageBeforeEdit);
            });
        })
        .catch(() => alert('Error updating post'));
    });
}

// Delete Post
window.deletePost = function(id) {
    // Ask before deleting
    if (!confirm('Are you sure you want to delete this post?')) return;

    // Save current page
    const currentPage = parseInt(localStorage.getItem('currentPage')) || 1;

    fetch(`/api/posts/${id}`, {
        method: 'DELETE',
        headers: { 'Authorization': 'Bearer ' + token }
    })
    .then(res => res.json())
    .then(() => {
        // Reload posts on the same page
        fetch('/api/posts', {
            headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(posts => {
            const perPage = 10;
            const totalPages = Math.ceil(posts.length / perPage);
            const pageToLoad = currentPage > totalPages ? totalPages : currentPage;
            loadPosts(pageToLoad || 1);
        });
    })
    .catch(() => alert('Error deleting post'));
}

// ------------------- Add Post Form -------------------
// ------------------- Add Post Form -------------------
window.addPostForm = function() {
    const main = document.getElementById('mainContent');

    // Function to render the form
    function renderForm(userSelectHtml = '') {
        main.innerHTML = `
            <h2 class="text-xl font-bold mb-4">Add New Post</h2>
            <form id="addPostForm" class="space-y-4">
                ${userSelectHtml}
                <input type="text" name="title" placeholder="Title" class="border p-2 w-full rounded" required>
                <textarea name="body" placeholder="Body" class="border p-2 w-full rounded" required></textarea>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    Save
                </button>
            </form>
        `;

        const form = document.getElementById('addPostForm');
        form.addEventListener('submit', e => {
            e.preventDefault();
            const formData = new FormData(form);

            // Admin: check if user is selected
            if (user.role === 'admin') {
                const userSelect = form.querySelector('select[name="user_id"]');
                if (!userSelect.value) {
                    alert('Please select a user for this post.');
                    return;
                }
            } else {
                // Normal user: force post to self
                formData.set('user_id', user.id);
            }

            fetch('/api/posts', {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + localStorage.getItem('auth_token')
                },
                body: formData
            })
            .then(res => {
                if (!res.ok) return res.json().then(err => { throw err; });
                return res.json();
            })
            .then(data => {
                alert('Post added successfully!');
                loadPosts(); // refresh posts
            })
            .catch(err => {
                console.error(err);
                alert(err.error || 'Error adding post');
            });
        });
    }

    // Admin: fetch users for select dropdown
    if (user.role === 'admin') {
        fetch('/api/users', {
            headers: {
                'Authorization': 'Bearer ' + localStorage.getItem('auth_token'),
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            const options = data.users.map(u => `<option value="${u.id}">${u.name}</option>`).join('');
            const selectHtml = `
                <select name="user_id" class="border p-2 w-full rounded" required>
                    <option value="">Select User</option>
                    ${options}
                </select>
            `;
            renderForm(selectHtml);
        })
        .catch(() => renderForm()); // fallback if fetch fails
    } else {
        // Normal user: no select needed
        renderForm();
    }
}













                // -------------------------------
                // Functions for User Info
                // -------------------------------
                function loadUserInfo(page = 1) {
                    fetch('/api/users', {
                        headers: {
                            'Authorization': 'Bearer ' + token,
                            'Accept': 'application/json'
                        }
                    })
                        .then(res => res.json())
                        .then(data => {
                            const users = data.users;
                            const lastPage = parseInt(localStorage.getItem('currentPage')) || page;
                            renderPaginatedTable(users, 'mainContent', [
                                { header: 'Name', key: 'name' },
                                { header: 'Email', key: 'email' },
                                { header: 'Role', key: 'role' },
                                { header: 'Profile', key: 'avatar_url', render: u => u.avatar_url ? `<img src="${u.avatar_url}" class="w-12 h-12 mx-auto">` : 'No avatar' }
                            ], 10, lastPage, {
                                addTopHtml: `<div class="flex justify-between items-center mb-4">
                           <h2 class="text-xl font-bold">User Info</h2>
                           <button class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700" onclick="addUserForm()">+ Add User</button>
                       </div>`,
                                addRowActions: u => `<button class="bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600" onclick="editUserForm(${u.id}, '${u.name}', '${u.email}', '${u.role}')">Edit</button>
                                 <button class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600" onclick="deleteUser(${u.id})">Delete</button>`
                            });

                            localStorage.setItem('activeSection', 'userInfo');

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

    // Default view after login
    document.getElementById('mainContent').innerHTML = `
        <div class="text-center py-10">
            <h2 class="text-2xl font-bold text-gray-800">Welcome, ${user.name}!</h2>
            <p class="text-gray-600 mt-2">Use the sidebar to navigate.</p>
        </div>
    `;
}



            })
            .catch(() => window.location.href = '/');
        }
    </script>

</body>
</html>
