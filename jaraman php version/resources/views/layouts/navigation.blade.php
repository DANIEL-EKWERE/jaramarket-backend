<!-- Top navbar -->
<div class="relative z-10 flex-shrink-0 flex h-16 bg-white shadow">
    <button id="sidebarToggle" type="button" class="px-4 border-r border-gray-200 text-gray-500 md:hidden" @click="sidebarOpen = true">
        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
            aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
    </button>

    <div class="flex-1 px-4 flex justify-between">
        
        <div class="flex-1 flex">
            <!-- Search 
            <form class="w-full flex md:ml-0" action="#" method="GET">
                <label for="search-field" class="sr-only">Search</label>
                <div class="relative w-full text-gray-400 focus-within:text-gray-600">
                    <div class="absolute inset-y-0 left-0 flex items-center pointer-events-none">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                            aria-hidden="true">
                            <path fill-rule="evenodd"
                                d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <input id="search-field"
                        class="block w-full h-full pl-8 pr-3 py-2 border-transparent text-gray-900 placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-0 focus:border-transparent sm:text-sm"
                        placeholder="Search" type="search" name="search">
                </div>
            </form>
            -->
        </div>
    

        <!-- Right side -->
        <div class="ml-4 flex items-center md:ml-6">

            <!-- Notifications dropdown -->
            <div class="ml-3 relative" id="notification-wrapper">
                <button type="button"
                    class="bg-white p-1 rounded-full text-gray-400 hover:text-gray-500 relative focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                    id="notification-menu-button">
                    <span class="sr-only">View notifications</span>
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    @php
                        $unreadCount = auth()->user()->unreadNotifications()->count();
                    @endphp
                    @if($unreadCount > 0)
                        <span
                            class="absolute -top-1 -right-1 bg-red-600 text-white text-xs font-bold px-1.5 py-0.5 rounded-full">
                            {{ $unreadCount }}
                        </span>
                    @endif
                </button>

                <!-- Dropdown -->
                <div id="notification-dropdown"
                    class="hidden origin-top-right absolute right-0 mt-2 w-80 max-h-96 overflow-y-auto rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-50">
                    
                    <div class="flex justify-between items-center px-4 py-2 border-b">
                        <span class="text-sm font-semibold">Notifications</span>
                        @if($unreadCount > 0)
                            <form method="POST" action="{{ route('notifications.markAllAsRead') }}">
                                @csrf
                                <button type="submit" class="text-xs text-blue-600 hover:underline">Mark all as read</button>
                            </form>
                        @endif
                    </div>

                    @forelse(auth()->user()->unreadNotifications as $notification)
                        <a href="{{ route('notifications.markAsRead', $notification->id) }}"
                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 border-b">
                            {{ $notification->data['message'] ?? 'New notification' }}
                            <span class="block text-xs text-gray-400">
                                {{ $notification->created_at->diffForHumans() }}
                            </span>
                        </a>
                    @empty
                        <div class="px-4 py-2 text-sm text-gray-500">No new notifications</div>
                    @endforelse
                </div>
            </div>

            <!-- Profile dropdown -->
            <div class="ml-3 relative" id="profile-wrapper">
                <button type="button"
                    class="max-w-xs bg-white flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                    id="user-menu-button">
                    <span class="sr-only">Open user menu</span>
                    <img class="h-8 w-8 rounded-full"
                        src="{{ get_media_url(auth()->user()->profile_picture) ?? 'https://e7.pngegg.com/pngimages/84/165/png-clipart-united-states-avatar-organization-information-user-avatar-service-computer-wallpaper-thumbnail.png' }}"
                        alt="User avatar">
                </button>

                <!-- Dropdown -->
                <div id="profile-dropdown"
                    class="hidden origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-50">
                    <a href="{{ route('admin.profile') }}"
                        class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">My Profile</a>
                    <a href="#"
                        class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Settings</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <a href="{{ route('logout') }}" onclick="event.preventDefault(); this.closest('form').submit();"
                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Sign out</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Auto-close dropdowns -->
<script>
    function toggleDropdown(buttonId, dropdownId, wrapperId) {
        const button = document.getElementById(buttonId);
        const dropdown = document.getElementById(dropdownId);
        const wrapper = document.getElementById(wrapperId);

        button.addEventListener('click', () => {
            dropdown.classList.toggle('hidden');
        });

        // Close when clicking outside
        document.addEventListener('click', (event) => {
            if (!wrapper.contains(event.target)) {
                dropdown.classList.add('hidden');
            }
        });
    }

    toggleDropdown('notification-menu-button', 'notification-dropdown', 'notification-wrapper');
    toggleDropdown('user-menu-button', 'profile-dropdown', 'profile-wrapper');
</script>
