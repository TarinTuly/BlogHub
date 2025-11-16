// posts.js
import { renderPaginatedTable } from './pagination.js';
import { loadComments } from './comment.js';

// This "factory" creates all post functions, giving them access to token, user, etc.
export function createPostFunctions(token, user) {

    // ------------------- Load Posts -------------------
    function loadPosts(page = 1) {
        const lastPage = parseInt(localStorage.getItem('currentPage')) || page;

        fetch(`/api/posts`, {
            headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(posts => {
           if (posts.length === 0) {
                 document.getElementById('mainContent').innerHTML = `
                 <div class="text-center mt-10">
                    <p class="text-gray-500 mb-4">No posts available.</p>
                    <button class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700" onclick="addPostForm()">+ Add Post</button>
                 </div>`;
                return;
           }

            if (user.role === 'admin' ) {
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
            }else {
    const main = document.getElementById('mainContent');



    main.innerHTML = posts.map(post => `
        <div class="bg-white shadow rounded-md p-4 mb-4" id="post-${post.id}">
            <div class="flex items-center mb-2">
                <!-- Logged-in user's profile pic + name -->
                <div class="w-10 h-10 rounded-full overflow-hidden mr-2">
                    <img src="${user.avatar_url || 'https://via.placeholder.com/40'}" alt="avatar" class="w-full h-full object-cover">
                </div>
                <div>
                    <p class="font-bold text-gray-800">${user.name}</p>
                    <p class="text-gray-500 text-sm">${new Date(post.created_at).toLocaleString()}</p>
                </div>
            </div>
           <div class="flex justify-between items-center mb-1">
    <h3 class="font-semibold text-lg">${post.title}</h3>
    ${user.id ? `
    <div class="space-x-2">
        <button onclick="editPostForm(${post.id}, \`${post.title.replace(/`/g, '\\`')}\`, \`${post.body.replace(/`/g, '\\`')}\`)"
        class="bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600 text-sm">Edit</button>
        <button onclick="deletePost(${post.id})"  class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600 text-sm">Delete</button>
    </div>
` : ''}
</div>
<p class="text-gray-700 mb-2">${post.body}</p>


            <!-- Like + Comment count + Comment toggle -->
            <div class="flex items-center mb-2 space-x-4 text-gray-600">
                <button onclick="toggleLike(${post.id})" class="like-btn" data-liked="false">👍 Like</button>
                <button onclick="toggleComments(${post.id})" class="text-blue-600">
                    💬 Comments (<span id="commentCount-${post.id}">${post.comment_count}</span>)
                </button>
            </div>

            <!-- Comments section -->
            <div id="commentSection-${post.id}" class="hidden">
                <!-- Add Comment Form -->
                <form onsubmit="addComment(event, ${post.id})" class="mt-2">
                    <input type="text" name="body" placeholder="Write a comment..." class="border p-2 w-full rounded" required>
                    <button class="bg-blue-600 text-white px-3 py-1 rounded mt-2">Comment</button>
                </form>

                <!-- Loaded comments -->
                <div id="comments-${post.id}" class="mt-2 space-y-2"></div>
            </div>
        </div>
    `).join('');

    // Load all comments + replies for each post
    posts.forEach(post => {
        loadComments(post.id); // render nested replies too
    });

    localStorage.setItem('activeSection', 'posts');
}



            localStorage.setItem('activeSection', 'posts');
        })
        .catch(err => {
            console.error(err);
            document.getElementById('mainContent').innerHTML = `<p class="text-red-500">Error loading posts.</p>`;
        });
    }

    // ------------------- Load All Posts (excluding own) -------------------



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



function AllloadPosts(page = 1) {
    const lastPage = parseInt(localStorage.getItem('currentPage')) || page;

    fetch(`/api/posts/others`, {
        headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }
    })
    .then(res => res.json())
    .then(posts => {
        const main = document.getElementById('mainContent');
        if (posts.length === 0) {
            main.innerHTML = `<p class="text-gray-500 text-center mt-10">No posts available.</p>`;
            return;
        }

        main.innerHTML = posts.map(post => `
            <div class="bg-white shadow rounded-md p-4 mb-4" id="post-${post.id}">
                <div class="flex items-center mb-2">
                    <div class="w-10 h-10 rounded-full bg-gray-300 flex items-center justify-center text-gray-700 font-bold mr-2">
                        ${post.user ? post.user.name.charAt(0).toUpperCase() : '?'}
                    </div>
                    <div>
                        <p class="font-bold text-gray-800">${post.user ? post.user.name : 'Unknown'}</p>
                        <p class="text-gray-500 text-sm">${new Date(post.created_at).toLocaleString()}</p>
                    </div>
                </div>
                <h3 class="font-semibold text-lg mb-1">${post.title}</h3>
                <p class="text-gray-700 mb-2">${post.body}</p>

                <!-- Like + Comment count + Comment toggle -->
                <div class="flex items-center mb-2 space-x-4 text-gray-600">
                    <button onclick="toggleLike(${post.id})" class="like-btn ${post.liked_by_user ? 'text-blue-600 font-bold' : ''}"
                        data-liked="${post.liked_by_user ? 'true' : 'false'}">
                        👍 ${post.liked_by_user ? 'Liked' : 'Like'} (<span id="likeCount-${post.id}">${post.like_count || 0}</span>)
                    </button>
                    <button onclick="toggleComments(${post.id})" class="text-blue-600">
                        💬 Comments (<span id="commentCount-${post.id}">${post.comment_count}</span>)
                    </button>
                </div>

                <!-- Comments section -->
                <div id="commentSection-${post.id}" class="hidden">
                    <!-- Add Comment Form -->
                    <form onsubmit="addComment(event, ${post.id})" class="mt-2">
                        <input type="text" name="body" placeholder="Write a comment..." class="border p-2 w-full rounded" required>
                        <button class="bg-blue-600 text-white px-3 py-1 rounded mt-2">Comment</button>
                    </form>

                    <!-- Loaded comments -->
                    <div id="comments-${post.id}" class="mt-2 space-y-2"></div>
                </div>
            </div>
        `).join('');

        // Load comments for each post
        posts.forEach(post => {
            loadComments(post.id); // render nested replies
        });

        localStorage.setItem('activeSection', 'AllPosts');
    })
    .catch(err => {
        console.error(err);
        document.getElementById('mainContent').innerHTML = `<p class="text-red-500 text-center mt-10">Error loading posts.</p>`;
    });
}

// Toggle comment section visibility
window.toggleComments = function(postId) {
    const section = document.getElementById(`commentSection-${postId}`);
    if (!section) return;
    section.classList.toggle('hidden');
};

// Toggle like functionality
window.toggleLike = async function(postId) {
    try {
        const res = await fetch(`/api/posts/${postId}/like`, {
            method: 'POST',
            headers: { 'Authorization': 'Bearer ' + token }
        });

        if (!res.ok) throw new Error('Failed to like post');

        const data = await res.json();

        // Update like button and count dynamically
        const likeBtn = document.querySelector(`#post-${postId} .like-btn`);
        if (likeBtn) {
            likeBtn.dataset.liked = data.liked ? 'true' : 'false';
            likeBtn.textContent = data.liked ? '👍 Liked' : '👍 Like';
        }

        const countSpan = document.getElementById(`likeCount-${postId}`);
        if (countSpan) countSpan.textContent = data.like_count;

        // Determine which section is active
        const activeSection = localStorage.getItem('activeSection');
        if (activeSection === 'AllPosts') {
            AllloadPosts();
        } else {
            const currentPage = parseInt(localStorage.getItem('currentPage')) || 1;
            loadPosts(currentPage);
        }
    } catch (err) {
        console.error(err);
        alert('Failed to like post');
    }
};



// ---- Like toggle (you can later connect to backend API) ----
window.toggleLike = async function(postId) {
    try {
        const res = await fetch(`/api/posts/${postId}/like`, {
            method: 'POST',
            headers: { 'Authorization': 'Bearer ' + token }
        });

        const data = await res.json(); // parse JSON only once

        if (!res.ok) throw new Error(data.error || 'Failed to like post');

        // Update like button and count dynamically
        const likeBtn = document.querySelector(`#post-${postId} .like-btn`);
        if (likeBtn) {
            likeBtn.dataset.liked = data.liked ? 'true' : 'false';
            likeBtn.textContent = data.liked ? `👍 Liked (${data.like_count})` : `👍 Like (${data.like_count})`;
        }

        const countSpan = document.getElementById(`likeCount-${postId}`);
        if (countSpan) countSpan.textContent = data.like_count;

    } catch (err) {
        console.error(err);
        alert(err.message);
    }
};





    // Return the main function that other modules will need to call
    return {
        loadPosts,
        AllloadPosts

    };
}
