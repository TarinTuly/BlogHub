// posts.js
import { renderPaginatedTable } from './pagination.js';

// This "factory" creates all post functions, giving them access to token, user, etc.
export function createPostFunctions(token, user) {

    // ------------------- Load Posts -------------------
    function loadPosts(page = 1) {
        const lastPage = parseInt(localStorage.getItem('currentPage')) || page;

        fetch('/api/posts', {
            headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(posts => {
            if (posts.length === 0) {
                document.getElementById('mainContent').innerHTML = `<p class="text-gray-500">No posts available.</p>`;
                return;
            }

            if (user.role === 'admin') {
                renderPaginatedTable(posts, 'mainContent', [
                    { header: 'ID', key: 'id' },
                    { header: 'Title', key: 'title' },
                    { header: 'Body', key: 'body' },
                    { header: 'Done By', key: 'user', render: p => p.user ? p.user.name : 'Unknown' },
                    { header: 'Date', key: 'created_at', render: p => new Date(p.created_at).toLocaleString() }
                ], 10, lastPage, {
                    addTopHtml: `<div class="flex justify-between items-center mb-4">
                                    <h2 class="text-xl font-bold">Posts</h2>
                                    <button class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700" onclick="addPostForm()">+ Add Post</button>
                                </div>`,
                    addRowActions: p => `<button class="bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600" onclick="editPostForm(${p.id}, '${p.title}', '${p.body}')">Edit</button>
                                         <button class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600" onclick="deletePost(${p.id})">Delete</button>`
                });
            } else {
                renderPaginatedTable(posts, 'mainContent', [
                    { header: '#', key: null, render: (p, i, currentPage, perPage) => (currentPage - 1) * perPage + i + 1 },
                    { header: 'Date', key: 'created_at', render: p => new Date(p.created_at).toLocaleString() },
                    { header: 'Title', key: 'title' },
                    { header: 'Body', key: 'body' }
                ], 10, lastPage, {
                    addTopHtml: `<div class="flex justify-between items-center mb-4">
                                    <h2 class="text-xl font-bold">Posts</h2>
                                    <button class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700" onclick="addPostForm()">+ Add Post</button>
                                </div>`,
                    addRowActions: p => `<button class="bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600" onclick="editPostForm(${p.id}, '${p.title}', '${p.body}')">Edit</button>
                                         <button class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600" onclick="deletePost(${p.id})">Delete</button>`
                });
            }
            localStorage.setItem('activeSection', 'posts');
        })
        .catch(err => {
            console.error(err);
            document.getElementById('mainContent').innerHTML = `<p class="text-red-500">Error loading posts.</p>`;
        });
    }

    // ------------------- Add Post Form -------------------
    window.addPostForm = function() {
        const main = document.getElementById('mainContent');
        function renderForm(userSelectHtml = '') {
            main.innerHTML = `
                <h2 class="text-xl font-bold mb-4">Add New Post</h2>
                <form id="addPostForm" class="space-y-4">
                    ${userSelectHtml}
                    <input type="text" name="title" placeholder="Title" class="border p-2 w-full rounded" required>
                    <textarea name="body" placeholder="Body" class="border p-2 w-full rounded" required></textarea>
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Save</button>
                </form>
            `;

            const form = document.getElementById('addPostForm');
            form.addEventListener('submit', e => {
                e.preventDefault();
                const formData = new FormData(form);
                if (user.role === 'admin') {
                    if (!form.querySelector('select[name="user_id"]').value) {
                        alert('Please select a user for this post.');
                        return;
                    }
                } else {
                    formData.set('user_id', user.id);
                }

                fetch('/api/posts', {
                    method: 'POST',
                    headers: { 'Authorization': 'Bearer ' + token },
                    body: formData
                })
                .then(res => res.ok ? res.json() : res.json().then(err => { throw err; }))
                .then(() => {
                    alert('Post added successfully!');
                    loadPosts(1); // Go to first page after adding
                })
                .catch(err => alert(err.error || 'Error adding post'));
            });
        }

        if (user.role === 'admin') {
            fetch('/api/users', {
                headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                const options = data.users.map(u => `<option value="${u.id}">${u.name}</option>`).join('');
                const selectHtml = `
                    <select name="user_id" class="border p-2 w-full rounded" required>
                        <option value="">Select User</option>
                        ${options}
                    </select>`;
                renderForm(selectHtml);
            })
            .catch(() => renderForm());
        } else {
            renderForm();
        }
    }

    // ------------------- Edit Post Form -------------------
    window.editPostForm = function(id, title, body) {
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
            fetch(`/api/posts/${id}`, {
                method: 'POST',
                headers: { 'Authorization': 'Bearer ' + token, 'X-HTTP-Method-Override': 'PUT' },
                body: new FormData(e.target)
            })
            .then(res => res.json())
            .then(() => loadPosts(pageBeforeEdit)) // Return to the same page
            .catch(() => alert('Error updating post'));
        });
    }

    // ------------------- Delete Post -------------------
    window.deletePost = function(id) {
        if (!confirm('Are you sure you want to delete this post?')) return;
        const currentPage = parseInt(localStorage.getItem('currentPage')) || 1;

        fetch(`/api/posts/${id}`, {
            method: 'DELETE',
            headers: { 'Authorization': 'Bearer ' + token }
        })
        .then(res => res.json())
        .then(() => {
            // After delete, we need to check if we were on the last page
            // and if this was the last item. This is complex, so for simplicity
            // we'll just reload the current page or page 1.
            loadPosts(currentPage);
        })
        .catch(() => alert('Error deleting post'));
    }

    // Return the main function that other modules will need to call
    return {
        loadPosts
    };
}
