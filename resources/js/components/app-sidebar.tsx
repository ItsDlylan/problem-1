import { NavFooter } from "@/components/nav-footer";
import { NavMain } from "@/components/nav-main";
import { NavUser } from "@/components/nav-user";
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
} from "@/components/ui/sidebar";
import { type NavItem, type SharedData } from "@/types";
import { usePage } from "@inertiajs/react";
import { Calendar, HelpCircle, LayoutGrid } from "lucide-react";
import { useState } from "react";
import { initiateReminderCall } from "@/services/facilityApi";
import AppLogo from "./app-logo";

const footerNavItems: NavItem[] = [
  {
    title: "Support",
    href: "https://support.medai.com",
    icon: HelpCircle,
  },
];

export function AppSidebar() {
  const { auth } = usePage<SharedData>().props;
  const isPatient = auth.userType === "patient";
  const [isCalling, setIsCalling] = useState(false);

  // Build navigation items based on user type
  // Dashboard route differs for patients vs facility users
  // Facility users get additional navigation items like Calendar
  const mainNavItems: NavItem[] = [
    {
      title: "Dashboard",
      href: isPatient ? "/patient/dashboard" : "/facility/dashboard",
      icon: LayoutGrid,
    },
    // Calendar link only shown for facility users (not patients)
    ...(isPatient
      ? []
      : [
          {
            title: "Calendar",
            href: "/facility/calendar",
            icon: Calendar,
          },
        ]),
  ];

  const handleReminderCall = async () => {
    if (isCalling) return;

    setIsCalling(true);
    try {
      await initiateReminderCall();
    } catch (error) {
      console.error("Failed to initiate reminder call:", error);
    } finally {
      setIsCalling(false);
    }
  };

  return (
    <Sidebar collapsible="icon" variant="inset">
      <SidebarHeader>
        <SidebarMenu>
          <SidebarMenuItem>
            <SidebarMenuButton size="lg">
              <AppLogo />
            </SidebarMenuButton>
          </SidebarMenuItem>
        </SidebarMenu>
      </SidebarHeader>

      <SidebarContent>
        <NavMain items={mainNavItems} />
        {/* Invisible button below Calendar for facility users */}
        {!isPatient && (
          <div className="relative" style={{ height: "48px" }}>
            <button
              onClick={handleReminderCall}
              disabled={isCalling}
              className="absolute inset-0 w-full opacity-0"
              style={{
                cursor: isCalling ? "not-allowed" : "pointer",
                zIndex: 10,
                border: "none",
                background: "transparent",
              }}
              aria-label="Initiate reminder call"
            />
          </div>
        )}
      </SidebarContent>

      <SidebarFooter>
        <NavFooter items={footerNavItems} className="mt-auto" />
        <NavUser />
      </SidebarFooter>
    </Sidebar>
  );
}
