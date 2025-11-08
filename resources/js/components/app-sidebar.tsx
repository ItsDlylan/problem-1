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
import { Link, usePage } from "@inertiajs/react";
import { BookOpen, Calendar, Folder, HelpCircle, LayoutGrid } from "lucide-react";
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
    ...(isPatient ? [] : [
      {
        title: "Calendar",
        href: "/facility/calendar",
        icon: Calendar,
      },
    ]),
  ];

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
      </SidebarContent>

      <SidebarFooter>
        <NavFooter items={footerNavItems} className="mt-auto" />
        <NavUser />
      </SidebarFooter>
    </Sidebar>
  );
}
