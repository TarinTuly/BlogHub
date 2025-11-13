// comment.js
const token = localStorage.getItem('auth_token');

/**
 * Fetch comments for a specific post
 * @param {number} postId
 */
export async function loadComments(postId) {
    try {
        const res = await fetch(`/api/posts/${postId}/comments`, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            }
        });
        const comments = await res.json();

        // render in the correct post container
        renderComments(postId, comments);
    } catch (err) {
        console.error(err);
        const container = document.getElementById(`comments-${postId}`);
        if (container) container.innerHTML = `<p class="text-red-500">Failed to load comments.</p>`;
    }
}

/**
 * Render comments recursively for replies
 * @param {number} postId
 * @param {Array} comments
 * @param {HTMLElement} container
 */
function renderComments(postId, comments, container = null) {
    // Target the specific post's comment container
    if (!container) container = document.getElementById(`comments-${postId}`);
    if (!container) return; // fail-safe

    container.innerHTML = ''; // clear previous

    comments.forEach(comment => {
        const commentDiv = document.createElement('div');
        commentDiv.className = 'flex gap-2 mb-2 items-start'; // flex for avatar + content

        // Avatar
        const avatarUrl = comment.user.avatar_url || 'https://via.placeholder.com/40';
        const avatarHtml = `<img src="${avatarUrl}" class="w-8 h-8 rounded-full object-cover" alt="avatar">`;

        // Content
        const contentHtml = `
            <div class="flex-1">
                <p class="font-semibold text-sm">${comment.user.name} <span class="text-gray-500 text-xs">${new Date(comment.created_at).toLocaleString()}</span></p>
                <p id="body-${comment.id}" class="text-gray-800">${comment.body}</p>
                <div class="flex gap-2 mt-1">
                    <button onclick="showReplyForm(${postId}, ${comment.id})" class="text-blue-600 text-xs">Reply</button>
                    ${comment.can_edit ? `<button onclick="editComment(${comment.id})" class="text-green-600 text-xs">Edit</button>` : ''}
                    ${comment.can_delete ? `<button onclick="deleteComment(${comment.id}, ${postId})" class="text-red-600 text-xs">Delete</button>` : ''}
                </div>
                <div id="replies-${comment.id}" class="ml-6 mt-2 space-y-2"></div>
            </div>
        `;

        commentDiv.innerHTML = avatarHtml + contentHtml;
        container.appendChild(commentDiv);

        // Recursively render replies
        if (comment.replies && comment.replies.length > 0) {
            renderComments(postId, comment.replies, commentDiv.querySelector(`#replies-${comment.id}`));
        }
    });
}


/**
 * Show reply form
 */
window.showReplyForm = function(postId, parentId) {
    const replyDiv = document.getElementById(`replies-${parentId}`);
    if (!replyDiv) return;

    // Remove any existing reply form first
    const existingForm = replyDiv.querySelector('form.reply-form');
    if (existingForm) existingForm.remove();

    const form = document.createElement('form');
    form.className = 'mb-2 reply-form'; // mark as reply form
    form.innerHTML = `
        <input type="text" name="body" placeholder="Write a reply..." class="border p-1 rounded w-full" required>
        <button type="submit" class="bg-blue-600 text-white px-2 py-1 rounded mt-1">Reply</button>
    `;

    form.addEventListener('submit', async e => {
        e.preventDefault();
        const body = form.body.value;

        try {
            await fetch(`/api/posts/${postId}/comments`, {
                method: 'POST',
                headers: { 'Authorization': `Bearer ${token}` },
                body: new URLSearchParams({ body, parent_id: parentId })
            });

            // Reload comments for this post after adding
            loadComments(postId);
        } catch (err) {
            alert('Error adding reply');
        }
    });

    // Add form at the **top** of replies
    replyDiv.prepend(form);
};

/**
 * Edit comment
 */
window.editComment = function(commentId, postId) {
    const bodyP = document.getElementById(`body-${commentId}`);
    if (!bodyP) return;

    const oldBody = bodyP.textContent;

    const form = document.createElement('form');
    form.innerHTML = `
        <input type="text" name="body" value="${oldBody}" class="border p-1 rounded w-full" required>
        <button type="submit" class="bg-green-600 text-white px-2 py-1 rounded mt-1">Update</button>
    `;
    bodyP.replaceWith(form);

    form.addEventListener('submit', async e => {
        e.preventDefault();
        const newBody = form.body.value.trim();
        if (!newBody) return alert('Comment cannot be empty');

        try {
            await fetch(`/api/comments/${commentId}`, {
                method: 'PUT',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({ body: newBody })
            });
            loadComments(postId); // reload comments after edit
        } catch (err) {
            alert('Error updating comment');
            console.error(err);
        }
    });
};

/**
 * Delete comment
 */
window.deleteComment = async function(commentId, postId) {
    if (!confirm('Are you sure you want to delete this comment and all its replies?')) return;

    try {
        await fetch(`/api/comments/${commentId}`, {
            method: 'DELETE',
            headers: { 'Authorization': `Bearer ${token}` }
        });
        loadComments(postId); // reload comments after delete
    } catch (err) {
        alert('Error deleting comment');
        console.error(err);
    }
};
