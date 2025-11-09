import { Head, Link } from "@inertiajs/react";
import { format } from "date-fns";
import { Calendar, Clock, Users, CheckCircle2, XCircle, AlertCircle } from "lucide-react";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem } from "@/types";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: "Dashboard",
    href: "/facility/dashboard",
  },
];

/**
 * Converts raw status values to human-friendly text.
 * Transforms snake_case statuses like "checked_in" to "Checked In".
 * 
 * @param status - The raw status string from the backend
 * @returns Human-friendly status text with proper capitalization
 */
function formatStatus(status: string): string {
  // Map of status values to their human-friendly display text
  const statusMap: Record<string, string> = {
    'checked_in': 'Checked In',
    'in_progress': 'In Progress',
    'completed': 'Completed',
    'cancelled': 'Cancelled',
    'no_show': 'No Show',
    'scheduled': 'Scheduled',
    'booked': 'Booked',
    'open': 'Open',
    'reserved': 'Reserved',
  };

  // Return mapped value if it exists, otherwise format the status
  if (statusMap[status]) {
    return statusMap[status];
  }

  // Fallback: convert snake_case to Title Case
  return status
    .split('_')
    .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
    .join(' ');
}

/**
 * Dashboard props received from the controller.
 */
interface DashboardProps {
  todayStats: {
    total: number;
    booked: number;
    completed: number;
    cancelled: number;
    upcoming: number;
  };
  upcomingAppointments: Array<{
    id: number;
    start_at: string;
    end_at: string;
    status: string;
    patient: {
      first_name: string;
      last_name: string;
    } | null;
    doctor: {
      display_name: string;
    } | null;
  }>;
}

/**
 * Facility Dashboard component.
 * Displays quick links and today's calendar summary.
 * Data is provided server-side via the FacilityDashboardController.
 */
export default function Dashboard({ todayStats, upcomingAppointments }: DashboardProps) {
  const today = new Date();

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Facility Dashboard" />
      <div className="flex h-screen overflow-hidden bg-gray-50/50 transition-all duration-300 dark:bg-gray-900/50">
        <div className="flex flex-1 flex-col">
          <main className="flex-grow overflow-y-auto p-4 md:p-6">
            <div className="mx-auto max-w-7xl">
              {/* Header */}
              <div className="mb-6">
                <h1 className="text-3xl font-bold text-gray-900 dark:text-gray-100">
                  Facility Dashboard
                </h1>
                <p className="mt-2 text-sm text-gray-600 dark:text-gray-400">
                  Welcome back! Here's what's happening today.
                </p>
              </div>

              {/* Quick Links */}
              <div className="mb-6 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <Card className="hover:shadow-md transition-shadow cursor-pointer">
                  <Link href="/facility/calendar" className="block">
                    <CardHeader>
                      <div className="flex items-center gap-3">
                        <div className="rounded-lg bg-blue-100 p-3 dark:bg-blue-900/20">
                          <Calendar className="h-6 w-6 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div className="flex-1">
                          <CardTitle>Calendar</CardTitle>
                          <CardDescription>View and manage schedules</CardDescription>
                        </div>
                      </div>
                    </CardHeader>
                  </Link>
                </Card>
              </div>

              {/* Today's Calendar Summary */}
              <div className="grid gap-6 md:grid-cols-2">
                {/* Statistics Card */}
                <Card>
                  <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                      <Clock className="h-5 w-5" />
                      Today's Schedule - {format(today, 'EEEE, MMMM d')}
                    </CardTitle>
                    <CardDescription>
                      Overview of today's appointments and availability
                    </CardDescription>
                  </CardHeader>
                  <CardContent>
                    <div className="grid grid-cols-2 gap-4">
                      <div className="rounded-lg border bg-card p-4">
                        <div className="flex items-center gap-2 text-sm text-muted-foreground">
                          <CheckCircle2 className="h-4 w-4 text-green-600" />
                          <span>Booked</span>
                        </div>
                        <div className="mt-2 text-2xl font-bold">{todayStats.booked}</div>
                      </div>
                      <div className="rounded-lg border bg-card p-4">
                        <div className="flex items-center gap-2 text-sm text-muted-foreground">
                          <CheckCircle2 className="h-4 w-4 text-green-600" />
                          <span>Completed</span>
                        </div>
                        <div className="mt-2 text-2xl font-bold">{todayStats.completed}</div>
                      </div>
                      <div className="rounded-lg border bg-card p-4">
                        <div className="flex items-center gap-2 text-sm text-muted-foreground">
                          <Users className="h-4 w-4 text-purple-600" />
                          <span>Upcoming</span>
                        </div>
                        <div className="mt-2 text-2xl font-bold">{todayStats.upcoming}</div>
                      </div>
                      <div className="rounded-lg border bg-card p-4">
                        <div className="flex items-center gap-2 text-sm text-muted-foreground">
                          <XCircle className="h-4 w-4 text-gray-600" />
                          <span>Cancelled</span>
                        </div>
                        <div className="mt-2 text-2xl font-bold">{todayStats.cancelled}</div>
                      </div>
                    </div>
                    <div className="mt-4">
                      <Link href="/facility/calendar">
                        <Button variant="outline" className="w-full">
                          <Calendar className="mr-2 h-4 w-4" />
                          View Full Calendar
                        </Button>
                      </Link>
                    </div>
                  </CardContent>
                </Card>

                {/* Upcoming Appointments Card */}
                <Card>
                  <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                      <AlertCircle className="h-5 w-5" />
                      Upcoming Appointments
                    </CardTitle>
                    <CardDescription>
                      Next appointments scheduled for today
                    </CardDescription>
                  </CardHeader>
                  <CardContent>
                    {upcomingAppointments.length === 0 ? (
                      <div className="py-8 text-center text-sm text-muted-foreground">
                        No upcoming appointments for today
                      </div>
                    ) : (
                      <div className="space-y-3">
                        {upcomingAppointments.map((appointment) => {
                          const slotStart = new Date(appointment.start_at);
                          const slotEnd = new Date(appointment.end_at);
                          const status = appointment.status;

                          return (
                            <div
                              key={appointment.id}
                              className="flex items-center justify-between rounded-lg border bg-card p-4"
                            >
                              <div className="flex-1">
                                <div className="font-medium">
                                  {format(slotStart, 'h:mm a')} - {format(slotEnd, 'h:mm a')}
                                </div>
                                {appointment.patient && (
                                  <div className="mt-1 text-sm text-muted-foreground">
                                    {appointment.patient.first_name} {appointment.patient.last_name}
                                  </div>
                                )}
                                {appointment.doctor && (
                                  <div className="mt-1 text-xs text-muted-foreground">
                                    Dr. {appointment.doctor.display_name}
                                  </div>
                                )}
                              </div>
                              <div className="ml-4">
                                <span
                                  className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${
                                    status === 'completed' || status === 'checked_in'
                                      ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400'
                                      : status === 'cancelled'
                                      ? 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-400'
                                      : status === 'no_show'
                                      ? 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400'
                                      : 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400'
                                  }`}
                                >
                                  {formatStatus(status)}
                                </span>
                              </div>
                            </div>
                          );
                        })}
                      </div>
                    )}
                    {upcomingAppointments.length > 0 && (
                      <div className="mt-4">
                        <Link href="/facility/calendar">
                          <Button variant="ghost" className="w-full text-sm">
                            View All Appointments
                          </Button>
                        </Link>
                      </div>
                    )}
                  </CardContent>
                </Card>
              </div>
            </div>
          </main>
        </div>
      </div>
    </AppLayout>
  );
}

