import AppLogoIcon from './app-logo-icon';

export default function AppLogo() {
    return (
        <>
            {/* Penguin doctor logo - no background container needed for PNG image */}
            <AppLogoIcon className="size-8" />
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-tight font-semibold">
                    MedAI
                </span>
            </div>
        </>
    );
}
