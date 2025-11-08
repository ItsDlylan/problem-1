import { Head } from "@inertiajs/react";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: "Dashboard",
    href: "/facility/dashboard",
  },
];

export default function Dashboard() {
  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Facility Dashboard" />
      <div className="flex h-screen overflow-hidden bg-gray-50/50 transition-all duration-300 dark:bg-gray-900/50">
        <div className="flex flex-1 flex-col">
          <main className="flex-grow overflow-y-auto p-4 md:p-6">
            <div className="mx-auto max-w-3xl">
              <h1 className="text-3xl font-bold text-gray-900 dark:text-gray-100">
                Facility Dashboard
              </h1>
              <p className="mt-4 text-gray-600 dark:text-gray-400">
                Welcome to the facility dashboard. This page will be expanded with facility-specific features.
              </p>
            </div>
          </main>
        </div>
      </div>
    </AppLayout>
  );
}

