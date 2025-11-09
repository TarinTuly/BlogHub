<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Auth Page</title>
<meta name="csrf-token" content="{{ csrf_token() }}">
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">

<div class="w-full max-w-md p-6 bg-white rounded-lg shadow-md">

    <!-- Register Section -->
    <div id="registerSection">
        <h2 class="text-2xl font-bold mb-4 text-center">Register</h2>
        <form id="registerForm" class="flex flex-col">
            <input type="text" name="name" placeholder="Name" class="border p-2 rounded mb-1" required>
            <span id="register_name_error" class="text-red-600 text-sm mb-2"></span>

            <input type="email" name="email" placeholder="Email" class="border p-2 rounded mb-1" required>
            <span id="register_email_error" class="text-red-600 text-sm mb-2"></span>

            <input type="password" name="password" placeholder="Password" class="border p-2 rounded mb-1" required>
            <span id="register_password_error" class="text-red-600 text-sm mb-2"></span>

            <input type="password" name="password_confirmation" placeholder="Confirm Password" class="border p-2 rounded mb-1" required>
            <span id="register_password_confirmation_error" class="text-red-600 text-sm mb-2"></span>

            <button type="submit" class="bg-blue-500 text-white py-2 rounded hover:bg-blue-600 mt-2">Register</button>
        </form>
        <p id="registerMsg" class="mt-2 text-center"></p>
        <p class="mt-4 text-center text-sm">
            Already have an account?
            <a href="#" onclick="toggleSection('login')" class="text-blue-500 hover:underline">Login here</a>
        </p>
    </div>

    <!-- Login Section -->
    <div id="loginSection" class="hidden">
        <h2 class="text-2xl font-bold mb-4 text-center">Login</h2>
        <form id="loginForm" class="flex flex-col">
            <input type="email" name="email" placeholder="Email" class="border p-2 rounded mb-1" required>
            <span id="login_email_error" class="text-red-600 text-sm mb-2"></span>

            <input type="password" name="password" placeholder="Password" class="border p-2 rounded mb-1" required>
            <span id="login_password_error" class="text-red-600 text-sm mb-2"></span>

            <button type="submit" class="bg-green-500 text-white py-2 rounded hover:bg-green-600 mt-2">Login</button>
        </form>
        <p id="loginMsg" class="mt-2 text-center"></p>
        <p class="mt-4 text-center text-sm">
            Don't have an account?
            <a href="#" onclick="toggleSection('register')" class="text-blue-500 hover:underline">Register here</a>
        </p>
    </div>



</div>

<script>
const tokenKey = 'auth_token';

function toggleSection(section) {
    document.getElementById('registerSection').classList.add('hidden');
    document.getElementById('loginSection').classList.add('hidden');

    if(section==='register') document.getElementById('registerSection').classList.remove('hidden');
    if(section==='login') document.getElementById('loginSection').classList.remove('hidden');

}

// Clear errors
function clearErrors(formPrefix){
    ['name','email','password','password_confirmation'].forEach(field=>{
        const el = document.getElementById(`${formPrefix}_${field}_error`);
        if(el) el.textContent='';
    });
}

// Show field-specific errors
function showFieldErrors(formPrefix, errors){
    for(const field in errors){
        const el = document.getElementById(`${formPrefix}_${field}_error`);
        if(el) el.textContent = errors[field].join(', ');
    }
}

// Show general messages
function showMessage(id,msg,isError=false){
    const el = document.getElementById(id);
    el.textContent = msg;
    el.className = isError?'mt-2 text-center text-red-600':'mt-2 text-center text-green-600';
}

// Register
document.getElementById('registerForm').addEventListener('submit', async e=>{
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target).entries());
    clearErrors('register');
    try {
        const res = await fetch('/api/register', {
            method:'POST',
            headers:{
                'Content-Type':'application/json',
                'Accept':'application/json'
            },
            body:JSON.stringify(data)
        });
        const json = await res.json();
        if(res.ok){
            showMessage('registerMsg','Registered successfully! Please login.');
            toggleSection('login');
        } else if(res.status===422){
            showFieldErrors('register', json.errors);
        } else {
            showMessage('registerMsg', json.message, true);
        }
    } catch(err){
        showMessage('registerMsg', err.message, true);
    }
});

// Login
document.getElementById('loginForm').addEventListener('submit', async e=>{
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target).entries());
    clearErrors('login');
    try {
        const res = await fetch('/api/login', {
            method:'POST',
            headers:{
                'Content-Type':'application/json',
                'Accept':'application/json'
            },
            body:JSON.stringify(data)
        });
        const json = await res.json();
        if(res.ok){
            localStorage.setItem(tokenKey,json.token);

            window.location.href = "/welcome";
        } else if(res.status===422){
            showFieldErrors('login', json.errors);
        } else {
            showMessage('loginMsg', json.message, true);
        }
    } catch(err){
        showMessage('loginMsg', err.message, true);
    }
});



// Logout
function logout(){
    const token = localStorage.getItem(tokenKey);
    if(!token) return alert('Not logged in!');
    fetch('/api/logout', {
        method:'POST',
        headers:{
            'Authorization':'Bearer '+token,
            'Accept':'application/json'
        }
    })
    .then(res=>res.json())
    .then(json=>{
        localStorage.removeItem(tokenKey);
        alert(json.message);
        window.location.reload(); // <-- reload page after logout
    });
}


</script>

</body>
</html>
