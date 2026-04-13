<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    @include('partials.head')
</head>

<body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] flex p-6 lg:p-8 items-center lg:justify-center min-h-screen flex-col">
<header class="w-full text-sm mb-6 not-has-[nav]:hidden">
    @if (Route::has('login'))
        <nav class="flex items-center justify-end gap-4">
            @auth
                <a
                    href="{{ route('applications.index') }}"
                    class="inline-block px-5 py-1.5 dark:text-[#EDEDEC] border-[#19140035] hover:border-[#1915014a] border text-[#1b1b18] dark:border-[#3E3E3A] dark:hover:border-[#62605b] rounded-sm text-sm leading-normal"
                >
                    Job Applications
                </a>
            @else
                <a
                    href="{{ route('login') }}"
                    class="inline-block px-5 py-1.5 dark:text-[#EDEDEC] text-[#1b1b18] border border-transparent hover:border-[#19140035] dark:hover:border-[#3E3E3A] rounded-sm text-sm leading-normal"
                >
                    Log in
                </a>

                @if (Route::has('register'))
                    <a
                        href="{{ route('register') }}"
                        class="inline-block px-5 py-1.5 dark:text-[#EDEDEC] border-[#19140035] hover:border-[#1915014a] border text-[#1b1b18] dark:border-[#3E3E3A] dark:hover:border-[#62605b] rounded-sm text-sm leading-normal">
                        Register
                    </a>
                @endif
            @endauth
        </nav>
    @endif
</header>
<div class="flex items-center w-full transition-opacity opacity-100 duration-750 lg:grow starting:opacity-0">
    <main>
        <h1 class="flex flex-col items-start text-left font-bold leading-none text-gray-900 dark:text-white space-y-4">
            <span class="block text-6xl">You've</span>
            <span class="block text-7xl">got</span>
            <span class="block text-8xl">this</span>
            <span class="block text-9xl">Christiaan!</span>
        </h1>
    </main>
</div>

@if (Route::has('login'))
    <div class="h-14.5 hidden lg:block"></div>
@endif

@fluxScripts
</body>

</html>
