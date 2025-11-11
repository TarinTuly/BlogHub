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


   <script type="module" src="{{ asset('js/dashboard.js') }}"></script>
   {{-- <script type="module" src="{{ asset('js/user.js') }}"></script> --}}



</body>
</html>
