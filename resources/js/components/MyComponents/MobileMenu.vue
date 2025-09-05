<template>
        <!-- Mobile menu -->
        <div :class="{ 'block': isMenuOpen, 'hidden': !isMenuOpen }" class="lg:hidden">
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
                <Link
                    :href="inicio()"
                    @click="closeMenu"
                    :class="addActiveClasses('text-gray-700 hover:text-gray-900 block px-3 py-2 rounded-md text-base font-medium dark:text-gray-300 dark:hover:text-white ', '/inicio')"
                >
                    Inicio
                </Link>
                <Link
                    href="/publicaciones"
                    @click="closeMenu"
                    :class="addActiveClasses('text-gray-700 hover:text-gray-900 block px-3 py-2 rounded-md text-base font-medium dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-700', '/publicaciones')"
                >
                    Publicaciones
                </Link>
                
                <Link
                    v-if="$page.props.auth.user == null" 
                    :href="login()"
                    @click="closeMenu"
                    :class="addActiveClasses('text-gray-700 hover:text-gray-900 block px-3 py-2 rounded-md text-base font-medium dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-700', '/login')"
                >
                    Login
                </Link>
                 <Link
                    v-if="$page.props.auth.user == null" 
                    :href="register()"
                    @click="closeMenu"
                    :class="addActiveClasses('text-gray-700 hover:text-gray-900 block px-3 py-2 rounded-md text-base font-medium dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-700', '/login')"
                >
                    Registrate
                </Link>


                <DropdownMenu>
                    <!-- <DropdownMenuTrigger :as-child="true">
                        <Button
                            variant="ghost"
                            size="icon"
                            class="relative size-10 w-auto rounded-full p-1 focus-within:ring-2 focus-within:ring-primary"
                            >
                            <Avatar class="size-8 overflow-hidden rounded-full">
                                <AvatarImage v-if="$page.props.auth.user.avatar" :src="$page.props.auth.user.avatar" :alt="$page.props.auth.user.name" />
                                <AvatarFallback class="rounded-lg bg-neutral-200 font-semibold text-black dark:bg-neutral-700 dark:text-white">
                                    {{ getInitials(auth.user?.name) }}
                                </AvatarFallback>
                            </Avatar>
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-56">
                        <UserMenuContent :user="$page.props.auth.user " />
                    </DropdownMenuContent> -->
                </DropdownMenu>
            </div>
        </div>
</template>
