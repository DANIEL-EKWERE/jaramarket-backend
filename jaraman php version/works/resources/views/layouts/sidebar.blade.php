<!-- Sidebar -->
<div id="sidebarWrapper" class="fixed md:relative z-40 md:z-0 inset-y-0 left-0 w-64 bg-green-700 transform -translate-x-full md:translate-x-0 transition-transform duration-300">
    <div class="flex flex-col h-full overflow-y-auto border-r border-green-600">
        <div class="flex flex-col flex-grow pt-5 overflow-y-auto bg-green-700 border-r">
            <div class="flex flex-col items-center flex-shrink-0 px-4">
                <a href="{{ route('dashboard') }}" class="px-8 text-left focus:outline-none">
                    <h2
                        class="block p-2 text-xl font-medium tracking-tighter text-white transition duration-500 ease-in-out transform cursor-pointer">
                        JaraMarket</h2>
                </a>
            </div>
            <div class="flex flex-col flex-grow px-4 mt-5">
                <nav class="flex-1 space-y-1 bg-green-700">
                    <ul>
                        <li>
                            <a class="inline-flex items-center w-full px-4 py-2 mt-1 text-base text-white transition duration-500 ease-in-out transform {{ request()->routeIs('dashboard') ? 'bg-green-600' : 'hover:bg-green-600' }} rounded-lg focus:shadow-outline"
                                href="{{ route('dashboard') }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                                    </path>
                                </svg>
                                <span class="ml-4">Dashboard</span>
                            </a>
                        </li>
                        <li>
                            <a class="inline-flex items-center w-full px-4 py-2 mt-1 text-base text-white transition duration-500 ease-in-out transform {{ request()->routeIs('orders*') ? 'bg-green-600' : 'hover:bg-green-600' }} rounded-lg focus:shadow-outline"
                                href="{{ route('orders.index') }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                                </svg>
                                <span class="ml-4">Orders</span>
                            </a>
                        </li>
                        <li>
                            <a class="inline-flex items-center w-full px-4 py-2 mt-1 text-base text-white transition duration-500 ease-in-out transform {{ request()->routeIs('products*') ? 'bg-green-600' : 'hover:bg-green-600' }} rounded-lg focus:shadow-outline"
                                href="{{ route('products.index') }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z">
                                    </path>
                                </svg>
                                <span class="ml-4">Food</span>
                            </a>
                        </li>
                        <li>
                            <a class="inline-flex items-center w-full px-4 py-2 mt-1 text-base text-white transition duration-500 ease-in-out transform {{ request()->routeIs('ingredients*') ? 'bg-green-600' : 'hover:bg-green-600' }} rounded-lg focus:shadow-outline"
                                href="{{ route('ingredients.index') }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </path>
                                </svg>
                                <span class="ml-4">Ingredients</span>
                            </a>
                        </li>
                        <li>
                            <a class="inline-flex items-center w-full px-4 py-2 mt-1 text-base text-white transition duration-500 ease-in-out transform {{ request()->routeIs('categories*') ? 'bg-green-600' : 'hover:bg-green-600' }} rounded-lg focus:shadow-outline"
                                href="{{ route('categories.index') }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                                    </path>
                                </svg>
                                <span class="ml-4">Categories</span>
                            </a>
                        </li>
                        <li>
                            <a class="inline-flex items-center w-full px-4 py-2 mt-1 text-base text-white transition duration-500 ease-in-out transform {{ request()->routeIs('users*') ? 'bg-green-600' : 'hover:bg-green-600' }} rounded-lg focus:shadow-outline"
                                href="{{ route('users.index') }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                <span class="ml-4">Users</span>
                            </a>
                        </li>
                        <li>
                            <a class="inline-flex items-center w-full px-4 py-2 mt-1 text-base text-white transition duration-500 ease-in-out transform {{ request()->routeIs('vendors*') ? 'bg-green-600' : 'hover:bg-green-600' }} rounded-lg focus:shadow-outline"
                                href="{{ route('vendors.index') }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                <span class="ml-4">Vendors</span>
                            </a>
                        </li>
                        
                        <li>
                            <a class="inline-flex items-center w-full px-4 py-2 mt-1 text-base text-white transition duration-500 ease-in-out transform {{ request()->routeIs('commissions*') ? 'bg-green-600' : 'hover:bg-green-600' }} rounded-lg focus:shadow-outline"
                                href="{{ route('commissions.index') }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                                    </path>
                                </svg>
                                <span class="ml-4">Commissions</span>
                            </a>
                        </li>
                        <li>
                            <a class="inline-flex items-center w-full px-4 py-2 mt-1 text-base text-white transition duration-500 ease-in-out transform {{ request()->routeIs('reports*') ? 'bg-green-600' : 'hover:bg-green-600' }} rounded-lg focus:shadow-outline"
                                href="{{ route('reports.orders') }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                    </path>
                                </svg>
                                <span class="ml-4">Reports</span>
                            </a>
                        </li>
                        <li>
                            <a class="inline-flex items-center w-full px-4 py-2 mt-1 text-base text-white transition duration-500 ease-in-out transform {{ request()->routeIs('summary*') ? 'bg-green-600' : 'hover:bg-green-600' }} rounded-lg focus:shadow-outline"
                                href="{{ route('summary') }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                    </path>
                                </svg>
                                <span class="ml-4">Summary</span>
                            </a>
                        </li>
                        <li>
                            <a class="inline-flex items-center w-full px-4 py-2 mt-1 text-base text-white transition duration-500 ease-in-out transform {{ request()->routeIs('advertisements*') ? 'bg-green-600' : 'hover:bg-green-600' }} rounded-lg focus:shadow-outline"
                                href="{{ route('advertisements.index') }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </path>
                                </svg>
                                <span class="ml-4">Advertisement</span>
                            </a>
                        </li>
                        <li>
                            <a class="inline-flex items-center w-full px-4 py-2 mt-1 text-base text-white transition duration-500 ease-in-out transform {{ request()->routeIs('representatives*') ? 'bg-green-600' : 'hover:bg-green-600' }} rounded-lg focus:shadow-outline"
                                href="{{ route('representatives.index') }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                                    </path>
                                </svg>
                                <span class="ml-4">State Representatives</span>
                            </a>
                        </li>
                       

                        <!-- ... existing sidebar items ... -->

                        <li>
                            <a href="{{ route('payments.index') }}"
                                class="inline-flex items-center w-full px-4 py-2 mt-1 text-base text-white transition duration-500 ease-in-out transform {{ request()->routeIs('payments.*') ? 'bg-green-600' : 'hover:bg-green-600' }} rounded-lg focus:shadow-outline">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                <span>Payments</span>
                            </a>
                        </li>


                        <!-- Admin Management -->
                        <li>
                            <a class="inline-flex items-center w-full px-4 py-2 mt-1 text-base text-white transition duration-500 ease-in-out transform {{ request()->routeIs('admin*') ? 'bg-green-600' : 'hover:bg-green-600' }} rounded-lg focus:shadow-outline"
                                href="{{ route('admin.index') }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                                    </path>
                                </svg>
                                <span class="ml-4">Administrators</span>
                            </a>
                        </li>
                        <li>
                            <a class="inline-flex items-center w-full px-4 py-2 mt-1 text-base text-white transition duration-500 ease-in-out transform {{ request()->routeIs('settings*') ? 'bg-green-600' : 'hover:bg-green-600' }} rounded-lg focus:shadow-outline"
                                href="{{ route('settings.index') }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                                    </path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                <span class="ml-4">Settings</span>
                            </a>
                        </li>
                        <li>
                            <a class="inline-flex items-center w-full px-4 py-2 mt-1 text-base text-white transition duration-500 ease-in-out transform {{ request()->routeIs('log-viewer::dashboard*') ? 'bg-green-600' : 'hover:bg-green-600' }} rounded-lg focus:shadow-outline"
                                href="{{ route('log-viewer::dashboard') }}" target="_blank">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                                    </path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                <span class="ml-4">Error Logs</span>
                            </a>
                        </li>
                        <li>
                            <a class="inline-flex items-center w-full px-4 py-2 mt-1 text-base text-white transition duration-500 ease-in-out transform {{ request()->routeIs('logout*') ? 'bg-green-600' : 'hover:bg-green-600' }} rounded-lg focus:shadow-outline"
                                href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H5a2 2 0 
                                        01-2-2V7a2 2 0 012-2h6a2 2 0 012 2v1" />
                                </svg>
                                <span class="ml-4">Logout</span>
                                <form id="logout-form" action="{{ route('logout') }}" method="post" style="display: none;">
                                    {{ csrf_field() }}
                                </form>
                            </a>
                        </li>


                    </ul>
                </nav>
            </div>
            <div class="flex flex-shrink-0 p-4 px-4 bg-green-700">
                <a href="#" class="flex-shrink-0 block w-full group">
                    <div class="flex items-center">
                        <div>
                            <img class="inline-block rounded-full h-9 w-9"
                                src="https://e7.pngegg.com/pngimages/84/165/png-clipart-united-states-avatar-organization-information-user-avatar-service-computer-wallpaper-thumbnail.png"
                                alt="">
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-white">{{ Auth::user()->name }}</p>
                            <p class="text-xs font-medium text-green-200 group-hover:text-white">View Profile</p>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>
