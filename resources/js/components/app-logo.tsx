import AppLogoIcon from './app-logo-icon';

export default function AppLogo() {
    return (
        <>
            {/* Container for the doctor icon - uses sidebar theme colors */}
            {/* In light mode: dark background (sidebar-primary) with white icon */}
            {/* In dark mode: white background (sidebar-primary) with dark icon */}
            {/* Using explicit colors ensures proper contrast in both modes */}
            <div className="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-white dark:text-foreground">
                {/* Doctor icon - uses currentColor which inherits the text color */}
                {/* Light mode: white icon on dark background */}
                {/* Dark mode: dark icon (foreground) on white background */}
                <AppLogoIcon className="size-6 fill-current" />
            </div>
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-tight font-semibold">
                    MedAI
                </span>
            </div>
        </>
    );
}
