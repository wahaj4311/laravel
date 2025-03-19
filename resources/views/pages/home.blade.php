<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} - Laravel Jenkins Practice</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <nav class="bg-white shadow-lg">
        <div class="max-w-6xl mx-auto px-4">
            <div class="flex justify-between">
                <div class="flex space-x-7">
                    <div>
                        <a href="/" class="flex items-center py-4 px-2">
                            <span class="font-semibold text-gray-500 text-lg">Laravel Jenkins</span>
                        </a>
                    </div>
                    <div class="hidden md:flex items-center space-x-1">
                        <a href="/" class="py-4 px-2 text-blue-500 border-b-4 border-blue-500 font-semibold">Home</a>
                        <a href="/about" class="py-4 px-2 text-gray-500 font-semibold hover:text-blue-500 transition duration-300">About</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h1 class="text-3xl font-bold text-gray-800 mb-4">{{ $title }}</h1>
            <p class="text-gray-600">{{ $description }}</p>
            
            <div class="mt-8">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4">Project Features</h2>
                <div class="grid md:grid-cols-2 gap-4">
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <h3 class="font-semibold text-blue-600">Laravel Framework</h3>
                        <p class="text-gray-600">Built with the latest version of Laravel</p>
                    </div>
                    <div class="bg-green-50 p-4 rounded-lg">
                        <h3 class="font-semibold text-green-600">Jenkins Integration</h3>
                        <p class="text-gray-600">Complete CI/CD pipeline setup</p>
                    </div>
                    <div class="bg-purple-50 p-4 rounded-lg">
                        <h3 class="font-semibold text-purple-600">SQLite Database</h3>
                        <p class="text-gray-600">Simple and efficient local database</p>
                    </div>
                    <div class="bg-yellow-50 p-4 rounded-lg">
                        <h3 class="font-semibold text-yellow-600">Testing</h3>
                        <p class="text-gray-600">Comprehensive test suite included</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 