export function initializeProfile(user, token) {
    const profileIcon = document.getElementById('profileIcon');
    profileIcon.style.cursor = 'pointer';

    profileIcon.addEventListener('click', () => {
        // Prevent multiple modals
        if (document.getElementById('profileModal')) return;

        const modal = document.createElement('div');
        modal.id = 'profileModal';
        modal.style.cssText = `
            position:fixed; top:0; left:0; width:100%; height:100%;
            background:rgba(0,0,0,0.5); display:flex; align-items:center;
            justify-content:center; z-index:9999;
        `;

        modal.innerHTML = `
            <div style="background:#fff; padding:20px; border-radius:8px; width:350px; position:relative; box-shadow:0 2px 10px rgba(0,0,0,0.2);">
                <span id="closeModal" style="position:absolute; top:10px; right:15px; cursor:pointer; font-weight:bold;">&times;</span>
                <h2 style="text-align:center; margin-bottom:15px;">Update Profile</h2>
                <form id="profileForm" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:10px;">
                    <input type="text" name="name" value="${user.name}" placeholder="Name" required style="padding:8px; border-radius:4px; border:1px solid #ccc;">
                    <input type="email" name="email" value="${user.email}" placeholder="Email" required style="padding:8px; border-radius:4px; border:1px solid #ccc;">
                    <input type="password" name="password" placeholder="New Password (leave blank to keep current)" style="padding:8px; border-radius:4px; border:1px solid #ccc;">
                    <input type="password" name="password_confirmation" placeholder="Confirm Password" style="padding:8px; border-radius:4px; border:1px solid #ccc;">
                    <input type="file" name="avatar" accept="image/*" style="padding:5px;">
                    <img id="avatarPreview" src="${user.avatar_url || 'https://via.placeholder.com/80'}" style="width:80px; height:80px; object-fit:cover; border-radius:50%; margin:auto;">
                    <button type="submit" style="padding:10px; background:#4CAF50; color:white; border:none; border-radius:5px; cursor:pointer;">Update Profile</button>
                    <div id="profileMsg" style="text-align:center; margin-top:10px;"></div>
                </form>
            </div>
        `;
        document.body.appendChild(modal);

        // Close modal
        modal.querySelector('#closeModal').addEventListener('click', () => modal.remove());

        // Avatar live preview
        const avatarInput = modal.querySelector('input[name="avatar"]');
        const avatarPreview = modal.querySelector('#avatarPreview');
        avatarInput.addEventListener('change', e => {
            const file = e.target.files[0];
            if (file) avatarPreview.src = URL.createObjectURL(file);
        });

        // Form submit
        const form = modal.querySelector('#profileForm');
        const msg = modal.querySelector('#profileMsg');

        form.addEventListener('submit', async e => {
            e.preventDefault();
            const formData = new FormData(form);

            try {
                const res = await fetch(`/api/user/profile`, {
                    method: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + token,
                        'X-HTTP-Method-Override': 'PUT'
                    },
                    body: formData
                });

                const data = await res.json();

                if (!res.ok) {
                    msg.textContent = data.message || 'Error updating profile';
                    msg.style.color = 'red';
                } else {
                    msg.textContent = 'Profile updated successfully!';
                    msg.style.color = 'green';

                    // Update user info in UI
                    user.name = data.name || user.name;
                    user.email = data.email || user.email;
                    if (data.avatar_url) profileIcon.src = data.avatar_url;
                    document.getElementById('welcomeMsg').textContent = `Welcome, ${user.name}!`;

                    setTimeout(() => modal.remove(), 1000);
                }
            } catch (err) {
                msg.textContent = 'Network error';
                msg.style.color = 'red';
            }
        });
    });
}
