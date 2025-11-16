const token = localStorage.getItem('auth_token');
const currentUserId = Number(localStorage.getItem('user_id')); // logged-in user id

/**
 * Add a comment
 */
window.addComment = async function (e, postId) {
    e.preventDefault();
    const body = e.target.body.value.trim();
    if (!body) return;

    try {
        await fetch(`/api/posts/${postId}/comments`, {
            method: "POST",
            headers: {
                "Authorization": `Bearer ${token}`,
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: new URLSearchParams({ body })
        });
        e.target.body.value = "";
        loadComments(postId);
    } catch (err) {
        console.error(err);
        alert("Failed to add comment");
    }
};

/**
 * Load comments
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
        renderComments(postId, comments , currentUserId); // 🌟 FIX 1: Pass currentUserId
        // update comment count
        const countSpan = document.getElementById(`commentCount-${postId}`);
        if(countSpan) countSpan.textContent = countComments(comments);
    } catch (err) {
        console.error(err);
        const container = document.getElementById(`comments-${postId}`);
        if (container) container.innerHTML = `<p class="text-red-500">Failed to load comments.</p>`;
    }
}

/**
 * Recursive comment count
 */
function countComments(comments) {
    let total = 0;
    comments.forEach(c => {
        total += 1;
        if (c.replies) total += countComments(c.replies);
    });
    return total;
}

/**
 * Render comments recursively
 */
/**
 * Render comments recursively (FIXED)
 */
/**
 * Render comments recursively (FIXED for Type Mismatch)
 */
function renderComments(postId, comments, currentUserId, container = null, isRoot = true) {
    if (!container) container = document.getElementById(`comments-${postId}`);
    if (!container) return;

    if (isRoot) container.innerHTML = ''; // clear only top-level

    comments.forEach(comment => {
        const commentDiv = document.createElement('div');
        commentDiv.className = 'flex gap-2 mb-2 items-start';

        const avatarUrl = comment.user.avatar_url || 'https://via.placeholder.com/40';
        const avatarHtml = `<img src="${avatarUrl}" class="w-8 h-8 rounded-full object-cover" alt="avatar">`;

        // 🎯 FIX: Convert comment.user.id to a Number for strict comparison
        const isOwner = comment.user && Number(comment.user.id) === currentUserId;

        const contentHtml = `
            <div class="flex-1">
                <p class="font-semibold text-sm">
                    ${comment.user.name}
                    <span class="text-gray-500 text-xs">${new Date(comment.created_at).toLocaleString()}</span>
                </p>
                <p id="body-${comment.id}" class="text-gray-800">${comment.body}</p>
                <div class="flex gap-2 mt-1">
                    <button onclick="showReplyForm(${postId}, ${comment.id})" class="text-blue-600 text-xs">Reply</button>

                    ${isOwner ? `<button onclick="editComment(${comment.id}, ${postId})" class="text-green-600 text-xs">Edit</button>` : ''}
                    ${isOwner ? `<button onclick="deleteComment(${comment.id}, ${postId})" class="text-red-600 text-xs">Delete</button>` : ''}
                </div>
                <div id="replies-${comment.id}" class="ml-6 mt-2 space-y-2"></div>
            </div>
        `;

        commentDiv.innerHTML = avatarHtml + contentHtml;
        container.appendChild(commentDiv);

        if (comment.replies && comment.replies.length > 0) {
            const replyContainer = commentDiv.querySelector(`#replies-${comment.id}`);
            renderComments(postId, comment.replies, currentUserId, replyContainer, false);
        }
    });
}
/**
 * Show reply form
 */
window.showReplyForm = function(postId, parentId) {
    const replyDiv = document.getElementById(`replies-${parentId}`);
    if (!replyDiv) return;

    const existingForm = replyDiv.querySelector('form.reply-form');
    if (existingForm) existingForm.remove();

    const form = document.createElement('form');
    form.className = 'mb-2 reply-form';
    form.innerHTML = `
        <input type="text" name="body" placeholder="Write a reply..." class="border p-1 rounded w-full" required>
        <button type="submit" class="bg-blue-600 text-white px-2 py-1 rounded mt-1">Reply</button>
    `;

    form.addEventListener('submit', async e => {
        e.preventDefault();
        const body = form.body.value.trim();
        if (!body) return;

        try {
            await fetch(`/api/posts/${postId}/comments`, {
                method: 'POST',
                headers: { 'Authorization': `Bearer ${token}` },
                body: new URLSearchParams({ body, parent_id: parentId })
            });
            loadComments(postId);
        } catch (err) {
            alert('Error adding reply');
        }
    });

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
            loadComments(postId);
        } catch (err) {
            alert('Error updating comment');
            console.error(err);
        }
    });
};

/**
 * Delete comment and all replies
 */
window.deleteComment = async function(commentId, postId) {
    if (!confirm('Are you sure you want to delete this comment and all its replies?')) return;

    try {
        await fetch(`/api/comments/${commentId}`, {
            method: 'DELETE',
            headers: { 'Authorization': `Bearer ${token}` }
        });
        loadComments(postId);
    } catch (err) {
        alert('Error deleting comment');
        console.error(err);
    }
};
