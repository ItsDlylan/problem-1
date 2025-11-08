import { send } from '@/routes/verification';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Transition } from '@headlessui/react';
import { Form, Head, Link, usePage } from '@inertiajs/react';

import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Profile settings',
        href: '/patient/settings/profile',
    },
];

/**
 * Patient profile edit page.
 * Allows patients to update their profile information including:
 * - First name and last name (instead of single name field)
 * - Email address
 * - Phone number
 * - Date of birth
 * - Preferred language
 */
export default function Edit({ status }: { status?: string }) {
    const { auth } = usePage<SharedData>().props;
    const user = auth.user;

    // Patient model has first_name and last_name instead of name
    // Also has additional fields like phone, dob, preferred_language
    const firstName = (user as any)?.first_name || '';
    const lastName = (user as any)?.last_name || '';
    const phone = (user as any)?.phone || '';
    const dob = (user as any)?.dob || '';
    const preferredLanguage = (user as any)?.preferred_language || '';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Profile settings" />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall
                        title="Profile information"
                        description="Update your profile information"
                    />

                    <Form
                        action="/patient/settings/profile"
                        method="patch"
                        options={{
                            preserveScroll: true,
                        }}
                        className="space-y-6"
                    >
                        {({ processing, recentlySuccessful, errors }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="first_name">First Name</Label>

                                    <Input
                                        id="first_name"
                                        className="mt-1 block w-full"
                                        defaultValue={firstName}
                                        name="first_name"
                                        required
                                        autoComplete="given-name"
                                        placeholder="First name"
                                    />

                                    <InputError
                                        className="mt-2"
                                        message={errors.first_name}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="last_name">Last Name</Label>

                                    <Input
                                        id="last_name"
                                        className="mt-1 block w-full"
                                        defaultValue={lastName}
                                        name="last_name"
                                        required
                                        autoComplete="family-name"
                                        placeholder="Last name"
                                    />

                                    <InputError
                                        className="mt-2"
                                        message={errors.last_name}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="email">Email address</Label>

                                    <Input
                                        id="email"
                                        type="email"
                                        className="mt-1 block w-full"
                                        defaultValue={user?.email || ''}
                                        name="email"
                                        required
                                        autoComplete="username"
                                        placeholder="Email address"
                                    />

                                    <InputError
                                        className="mt-2"
                                        message={errors.email}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="phone">Phone Number</Label>

                                    <Input
                                        id="phone"
                                        type="tel"
                                        className="mt-1 block w-full"
                                        defaultValue={phone}
                                        name="phone"
                                        autoComplete="tel"
                                        placeholder="Phone number"
                                    />

                                    <InputError
                                        className="mt-2"
                                        message={errors.phone}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="dob">Date of Birth</Label>

                                    <Input
                                        id="dob"
                                        type="date"
                                        className="mt-1 block w-full"
                                        defaultValue={dob}
                                        name="dob"
                                        autoComplete="bday"
                                    />

                                    <InputError
                                        className="mt-2"
                                        message={errors.dob}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="preferred_language">Preferred Language</Label>

                                    <Input
                                        id="preferred_language"
                                        className="mt-1 block w-full"
                                        defaultValue={preferredLanguage}
                                        name="preferred_language"
                                        placeholder="e.g., en, es, fr"
                                    />

                                    <InputError
                                        className="mt-2"
                                        message={errors.preferred_language}
                                    />
                                </div>

                                {user?.email_verified_at === null && (
                                    <div>
                                        <p className="-mt-4 text-sm text-muted-foreground">
                                            Your email address is unverified.{' '}
                                            <Link
                                                href={send()}
                                                as="button"
                                                className="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                                            >
                                                Click here to resend the
                                                verification email.
                                            </Link>
                                        </p>

                                        {status ===
                                            'verification-link-sent' && (
                                            <div className="mt-2 text-sm font-medium text-green-600">
                                                A new verification link has been
                                                sent to your email address.
                                            </div>
                                        )}
                                    </div>
                                )}

                                <div className="flex items-center gap-4">
                                    <Button
                                        disabled={processing}
                                        data-test="update-profile-button"
                                    >
                                        Save
                                    </Button>

                                    <Transition
                                        show={recentlySuccessful}
                                        enter="transition ease-in-out"
                                        enterFrom="opacity-0"
                                        leave="transition ease-in-out"
                                        leaveTo="opacity-0"
                                    >
                                        <p className="text-sm text-neutral-600">
                                            Saved
                                        </p>
                                    </Transition>
                                </div>
                            </>
                        )}
                    </Form>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}

