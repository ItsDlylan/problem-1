import { useState, useMemo, useEffect } from "react";
import { Head } from "@inertiajs/react";
import { AppointmentCard } from "@/components/appointment-card";
import { AppointmentFilters } from "@/components/appointment-filters";
import { ChatbotPanel } from "@/components/chatbot-panel";
import { Button } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";
import {
  mockAppointments,
  Appointment,
  AppointmentStatus,
} from "@/types/appointment";
import { useDebounce } from "@/hooks/use-debounce";
import { MessageSquare } from "lucide-react";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: "Dashboard",
    href: "/patient/dashboard",
  },
];

export default function Dashboard() {
  const [searchTerm, setSearchTerm] = useState("");
  const debouncedSearchTerm = useDebounce(searchTerm, 300);
  const [statusFilter, setStatusFilter] = useState<AppointmentStatus | "all">(
    "all",
  );
  const [sortOption, setSortOption] = useState("datetime-desc");
  const [isChatbotOpen, setChatbotOpen] = useState(false);
  const [isLoading, setLoading] = useState(true);
  const [jump, setJump] = useState(false);

  const filteredAppointments = useMemo(() => {
    let appointments = mockAppointments.filter((appointment) => {
      const term = debouncedSearchTerm.toLowerCase();
      return (
        appointment.doctorName.toLowerCase().includes(term) ||
        appointment.facilityName.toLowerCase().includes(term) ||
        appointment.facilityLocation.toLowerCase().includes(term)
      );
    });

    if (statusFilter !== "all") {
      appointments = appointments.filter(
        (appointment) => appointment.status === statusFilter,
      );
    }

    const [sortBy, order] = sortOption.split("-");

    appointments.sort((a, b) => {
      const valA =
        sortBy === "datetime"
          ? new Date(a.datetime).getTime()
          : a[sortBy as keyof Omit<Appointment, "datetime">];
      const valB =
        sortBy === "datetime"
          ? new Date(b.datetime).getTime()
          : b[sortBy as keyof Omit<Appointment, "datetime">];

      if (valA < valB) return order === "asc" ? -1 : 1;
      if (valA > valB) return order === "asc" ? 1 : -1;
      return 0;
    });

    return appointments;
  }, [debouncedSearchTerm, statusFilter, sortOption]);

  useEffect(() => {
    const timer = setTimeout(() => setLoading(false), 1000); // Simulate loading
    return () => clearTimeout(timer);
  }, []);

  useEffect(() => {
    const interval = setInterval(() => {
      setJump(true);
      setTimeout(() => setJump(false), 700); // Duration of the animation
    }, 5000); // Trigger animation every 5 seconds

    return () => clearInterval(interval);
  }, []);

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Appointments Dashboard" />
      <div className="flex h-screen overflow-hidden bg-gray-50/50 transition-all duration-300 dark:bg-gray-900/50">
        <div className="flex flex-1 flex-col">
          <main className="flex-grow overflow-y-auto p-4 md:p-6">
            <AppointmentFilters
              onSearch={setSearchTerm}
              onFilterByStatus={setStatusFilter}
              onSort={setSortOption}
            />
            <div className="mx-auto mt-6 grid max-w-3xl grid-cols-1 gap-6">
              {isLoading ? (
                Array.from({ length: 8 }).map((_, i) => (
                  <Skeleton key={i} className="h-[240px] w-full" />
                ))
              ) : filteredAppointments.length > 0 ? (
                filteredAppointments.map((appointment) => (
                  <AppointmentCard
                    key={appointment.id}
                    appointment={appointment}
                  />
                ))
              ) : (
                <p className="text-center text-gray-500">
                  No appointments found.
                </p>
              )}
            </div>
          </main>
        </div>
        <div
          className={`flex-shrink-0 transform bg-white shadow-lg transition-all duration-300 ease-in-out dark:bg-gray-950 ${isChatbotOpen ? "w-96 translate-x-0" : "w-0 -translate-x-full"}`}
        >
          <ChatbotPanel
            isOpen={isChatbotOpen}
            onClose={() => setChatbotOpen(false)}
          />
        </div>
        <Button
            className={`fixed bottom-6 right-6 h-14 w-14 rounded-full shadow-lg transition-transform hover:scale-110 ${
                jump ? "animate-jump" : ""
            }`}
            onClick={() => setChatbotOpen(true)}
        >
            <MessageSquare className="h-7 w-7" />
        </Button>
      </div>
    </AppLayout>
  );
}
