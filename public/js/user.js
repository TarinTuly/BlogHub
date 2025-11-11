// user.js
import { renderPaginatedTable } from './pagination.js';

export function createUserFunctions(token, user) {

    // ------------------- Load User Info -------------------
    function loadUserInfo(page = 1) {
        const lastPage = parseInt(localStorage.getItem('currentPage')) || page;

        fetch('/api/users', {
            headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            renderPaginatedTable(data.users, 'mainContent', [
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
            fetch('/api/register', {
                method: 'POST',
                headers: { 'Authorization': 'Bearer ' + token },
                body: new FormData(e.target)
            })
            .then(res => res.json())
            .then(() => loadUserInfo(1)) // Go to first page
            .catch(() => alert('Error adding user'));
        });
    }

    // ------------------- Edit User Form -------------------
    window.editUserForm = function(id, name, email, role) {
        const lastPage = parseInt(localStorage.getItem('currentPage')) || 1;
        document.getElementById('mainContent').innerHTML = `
            <h2 class="text-xl font-bold mb-4">Edit User</h2>
            <form id="editUserForm" class="space-y-4">
                <input type="text" name="name" value="${name}" class="border p-2 w-full rounded" required>
                <input type="email" name="email" value="${email}" class="border p-2 w-full rounded" required>
                <select name="role" class="border p-2 w-full rounded" required>
                    <option value="admin" ${role === 'admin' ? 'selected' : ''}>Admin</option>
                    <option value="user" ${role === 'user' ? 'selected' : ''}>User</option>
                </select>
                <input type="file" name="avatar" accept="image/*" class="border p-2 w-full">
                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Update</button>
            </form>
        `;

        document.getElementById('editUserForm').addEventListener('submit', e => {
            e.preventDefault();
            fetch(`/api/users/${id}`, {
                method: 'POST',
                headers: { 'Authorization': 'Bearer ' + token, 'X-HTTP-Method-Override': 'PUT' },
                body: new FormData(e.target)
            })
            .then(res => res.json())
            .then(() => loadUserInfo(lastPage)) // Return to same page
            .catch(() => alert('Error updating user'));
        });
    }

    // ------------------- Delete User -------------------
    window.deleteUser = function(id) {
        if (!confirm('Are you sure?')) return;
        const lastPage = parseInt(localStorage.getItem('currentPage')) || 1;

        fetch(`/api/users/${id}`, {
            method: 'DELETE',
            headers: { 'Authorization': 'Bearer ' + token }
        })
        .then(res => res.json())
        .then(() => loadUserInfo(lastPage)) // Reload same page
        .catch(() => alert('Error deleting user'));
    }

    // Return the main function that other modules will need to call
    return {
        loadUserInfo
    };
}
