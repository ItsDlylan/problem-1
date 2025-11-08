import {
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import { UserInfo } from '@/components/user-info';
import { useMobileNavigation } from '@/hooks/use-mobile-navigation';
import { logout } from '@/routes';
import { edit } from '@/routes/user-profile';
import { type User } from '@/types';
import { Link, router, usePage } from '@inertiajs/react';
import { LogOut, Settings } from 'lucide-react';
import { type SharedData } from '@/types';

interface UserMenuContentProps {
    user: User;
}

export function UserMenuContent({ user }: UserMenuContentProps) {
    const cleanup = useMobileNavigation();
    const { auth } = usePage<SharedData>().props;
    
    // Determine settings URL based on user type
    // Patients use /patient/settings/profile, facility users use /settings/profile
    // Regular users (User model) use /user/settings/profile
    const settingsUrl = auth.userType === 'patient' 
        ? '/patient/settings/profile' 
        : auth.userType === 'facility'
        ? '/settings/profile'
        : edit().url;
    
    // Determine logout URL based on user type
    // Patients use /patient/logout, facility users use /logout
    const logoutUrl = auth.userType === 'patient'
        ? '/patient/logout'
        : logout().url;

    const handleLogout = (e: React.MouseEvent) => {
        e.preventDefault();
        cleanup();
        // Use router.post() for logout since it requires POST method
        // The backend will redirect to '/' (welcome page) after logout
        router.post(logoutUrl, {}, {
            onFinish: () => {
                router.flushAll();
            },
        });
    };

    return (
        <>
            <DropdownMenuLabel className="p-0 font-normal">
                <div className="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                    <UserInfo user={user} showEmail={true} />
                </div>
            </DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuGroup>
                <DropdownMenuItem asChild>
                    <Link
                        className="block w-full"
                        href={settingsUrl}
                        as="button"
                        prefetch
                        onClick={cleanup}
                    >
                        <Settings className="mr-2" />
                        Settings
                    </Link>
                </DropdownMenuItem>
            </DropdownMenuGroup>
            <DropdownMenuSeparator />
            <DropdownMenuItem>
                <button
                    className="flex w-full items-center"
                    onClick={handleLogout}
                    data-test="logout-button"
                    type="button"
                >
                    <LogOut className="mr-2 h-4 w-4" />
                    Log out
                </button>
            </DropdownMenuItem>
        </>
    );
}
